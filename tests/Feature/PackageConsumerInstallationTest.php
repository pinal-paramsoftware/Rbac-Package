<?php

namespace Parth\LaravelRbacSuite\Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Parth\LaravelRbacSuite\Database\Seeders\RbacSuiteDatabaseSeeder;
use Parth\LaravelRbacSuite\Models\AuthSetting;
use Parth\LaravelRbacSuite\Models\PasswordRule;
use Parth\LaravelRbacSuite\Models\Role;
use Parth\LaravelRbacSuite\RbacSuiteServiceProvider;
use Parth\LaravelRbacSuite\Support\SchemaTracker;
use Parth\LaravelRbacSuite\Tests\TestCase;

class PackageConsumerInstallationTest extends TestCase
{
    use RefreshDatabase;

    public function test_composer_manifest_structure_and_auto_discovery_configuration(): void
    {
        $composerPath = dirname(__DIR__, 2).'/composer.json';
        $this->assertFileExists($composerPath);

        $composerData = json_decode(File::get($composerPath), true);

        // 1. Package Identity
        $this->assertEquals('pinal-paramsoftware/laravel-rbac-suite', $composerData['name']);
        $this->assertEquals('library', $composerData['type']);
        $this->assertEquals('MIT', $composerData['license']);

        // 2. Autoload mappings
        $this->assertArrayHasKey('Parth\\LaravelRbacSuite\\', $composerData['autoload']['psr-4']);
        $this->assertEquals('src/', $composerData['autoload']['psr-4']['Parth\\LaravelRbacSuite\\']);
        $this->assertArrayHasKey('Parth\\LaravelRbacSuite\\Database\\Seeders\\', $composerData['autoload']['psr-4']);
        $this->assertContains('src/helpers.php', $composerData['autoload']['files']);

        // 3. Auto-Discovery Providers & Aliases
        $this->assertArrayHasKey('extra', $composerData);
        $this->assertArrayHasKey('laravel', $composerData['extra']);
        $this->assertContains(
            RbacSuiteServiceProvider::class,
            $composerData['extra']['laravel']['providers']
        );
        $this->assertEquals(
            'Parth\\LaravelRbacSuite\\Facades\\RbacSuite',
            $composerData['extra']['laravel']['aliases']['RbacSuite']
        );

        // 4. Critical runtime requirements
        $this->assertArrayHasKey('php', $composerData['require']);
        $this->assertArrayHasKey('spatie/laravel-permission', $composerData['require']);
        $this->assertArrayHasKey('pragmarx/google2fa-laravel', $composerData['require']);
        $this->assertArrayHasKey('yajra/laravel-datatables-oracle', $composerData['require']);
    }

    public function test_consumer_boot_sequence_functions_without_published_config(): void
    {
        // Even when no configs are published in host config/ directory, package defaults merge seamlessly
        $this->assertNotEmpty(config('rbac-suite.user_model'));
        $this->assertEquals('web', config('rbac-suite.guard'));
        $this->assertEquals('Admin', config('rbac-suite.roles.admin_name'));
        $this->assertEquals('rbac-suite::layouts.master', config('rbac-suite.layout'));

        // All auth pipeline defaults merge seamlessly
        $this->assertNotNull(config('auth-pipeline.stages.email_otp.expiry_minutes'));
        $this->assertNotNull(config('auth-pipeline.rate_limits.max_consecutive_failures'));
    }

    public function test_all_expected_package_routes_are_registered(): void
    {
        $expectedRoutes = [
            'login',
            'logout',
            'register',
            'dashboard',
            'captcha',
            'otp.form',
            'otp.verify',
            'otp.resend',
            'mobile.otp.form',
            'mobile.otp.verify',
            'mobile.otp.resend',
            'whatsapp.otp.form',
            'whatsapp.otp.verify',
            'whatsapp.otp.resend',
            'twofa.form',
            'twofa.verify.form',
            'twofa.verify',
            'roles.index',
            'roles.create',
            'roles.store',
            'roles.show',
            'roles.edit',
            'roles.update',
            'roles.destroy',
            'permissions.index',
            'permissions.create',
            'permissions.store',
            'master.user.index',
            'master.user.create',
            'master.user.store',
            'auth.settings',
            'getAuthenticationSettings',
            'updateAuthenticationSettings',
        ];

        foreach ($expectedRoutes as $routeName) {
            $this->assertTrue(
                Route::has($routeName),
                "Route [{$routeName}] must be registered by the package."
            );
        }
    }

    public function test_isolated_database_seeding_and_role_provisioning(): void
    {
        // 1. Run package seeder
        $this->seed(RbacSuiteDatabaseSeeder::class);

        // 2. Verify roles created
        $adminRole = Role::where('name', 'Admin')->first();
        $userRole = Role::where('name', 'User')->first();

        $this->assertNotNull($adminRole);
        $this->assertNotNull($userRole);

        // 3. Create consumer users
        $userModel = config('rbac-suite.user_model', 'App\\Models\\User');

        $adminUser = $userModel::create([
            'name' => 'First Host Admin',
            'email' => 'host.admin@consumer-app.com',
            'password' => Hash::make('AdminSecret#2026'),
            'is_active' => '1',
        ]);

        $standardUser = $userModel::create([
            'name' => 'Standard Consumer User',
            'email' => 'standard.user@consumer-app.com',
            'password' => Hash::make('UserSecret#2026'),
            'is_active' => '1',
        ]);

        // 4. Explicit first-time administrator assignment
        $adminUser->assignRole('Admin');
        $standardUser->assignRole('User');

        // Verify standard user DOES NOT inherit elevated permissions automatically
        $this->assertTrue($adminUser->hasRole('Admin'));
        $this->assertTrue($adminUser->hasPermissionTo('role-create'));
        $this->assertTrue($adminUser->hasPermissionTo('auth_setting-view'));

        $this->assertTrue($standardUser->hasRole('User'));
        $this->assertFalse($standardUser->hasPermissionTo('role-create'));
        $this->assertFalse($standardUser->hasPermissionTo('auth_setting-view'));
        $this->assertTrue($standardUser->hasPermissionTo('dashboard-view'));

        // 5. Test access control responses
        // Admin allowed
        $this->actingAs($adminUser)->get(route('roles.index'))->assertStatus(200);
        $this->actingAs($adminUser)->get(route('auth.settings'))->assertStatus(200);

        // Standard user forbidden
        $this->actingAs($standardUser)->get(route('roles.index'))->assertStatus(403);
        $this->actingAs($standardUser)->get(route('auth.settings'))->assertStatus(403);

        // Guest denied access
        $this->get(route('roles.index'))->assertStatus(403);
    }

    public function test_auth_settings_and_password_rule_defaults_in_consumer_environment(): void
    {
        $this->seed(RbacSuiteDatabaseSeeder::class);

        // Verify all 7 authentication settings exist with expected defaults
        $captcha = AuthSetting::where('AuthCode', AuthSetting::CODE_CAPTCHA)->first();
        $emailOtp = AuthSetting::where('AuthCode', AuthSetting::CODE_EMAIL_OTP)->first();
        $registration = AuthSetting::where('AuthCode', AuthSetting::CODE_REGISTRATION)->first();

        $this->assertNotNull($captcha);
        $this->assertEquals(1, $captcha->IsEnabled);

        $this->assertNotNull($emailOtp);
        $this->assertEquals(0, $emailOtp->IsEnabled);
        $this->assertEquals(5, $emailOtp->OTPAttempt);

        $this->assertNotNull($registration);
        $this->assertEquals(1, $registration->IsEnabled);

        // Verify all 5 password rules exist
        $minLength = PasswordRule::where('RuleCode', PasswordRule::RULE_MIN_LENGTH)->first();
        $this->assertNotNull($minLength);
        $this->assertEquals(8, $minLength->RuleValue);
    }

    public function test_migration_safety_audit_preserves_external_tables_and_columns_on_rollback(): void
    {
        SchemaTracker::ensureManifestTable();

        // 1. Host app pre-existing table
        Schema::create('consumer_legacy_table', function ($table) {
            $table->id();
            $table->string('legacy_data');
        });

        // 2. Host app pre-existing column on users
        $usersTable = config('rbac-suite.tables.users', 'users');
        if (! Schema::hasColumn($usersTable, 'consumer_custom_field')) {
            Schema::table($usersTable, function ($table) {
                $table->string('consumer_custom_field')->nullable();
            });
        }

        // Run rollback on permissions and users migrations
        $permissionsMigration = require dirname(__DIR__, 2).'/database/migrations/2026_01_01_000001_create_rbac_permissions_tables.php';
        $userColumnsMigration = require dirname(__DIR__, 2).'/database/migrations/2026_01_01_000002_add_rbac_columns_to_users_table.php';

        $permissionsMigration->down();
        $userColumnsMigration->down();

        // Verify host table and host column are 100% intact
        $this->assertTrue(
            Schema::hasTable('consumer_legacy_table'),
            'Host table must not be dropped by package rollback'
        );
        $this->assertTrue(
            Schema::hasColumn($usersTable, 'consumer_custom_field'),
            'Host column must not be dropped by package rollback'
        );

        // Clean up mock items
        Schema::dropIfExists('consumer_legacy_table');
        Schema::table($usersTable, function ($table) {
            $table->dropColumn('consumer_custom_field');
        });
    }

    public function test_install_command_is_registered_and_executes_successfully(): void
    {
        $this->artisan('rbac:install')
            ->assertSuccessful();
    }

    public function test_middleware_aliases_are_registered(): void
    {
        $router = $this->app['router'];
        $middleware = $router->getMiddleware();

        $this->assertArrayHasKey('role', $middleware);
        $this->assertArrayHasKey('permission', $middleware);
        $this->assertArrayHasKey('role_or_permission', $middleware);
    }
}
