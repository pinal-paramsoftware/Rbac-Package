<?php

namespace Parth\LaravelRbacSuite\Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Parth\LaravelRbacSuite\Database\Seeders\RbacSuiteDatabaseSeeder;
use Parth\LaravelRbacSuite\Models\AuthProviderSetting;
use Parth\LaravelRbacSuite\Models\AuthSetting;
use Parth\LaravelRbacSuite\Models\PasswordRule;
use Parth\LaravelRbacSuite\Models\Permission;
use Parth\LaravelRbacSuite\Models\Role;
use Parth\LaravelRbacSuite\Support\SchemaTracker;
use Parth\LaravelRbacSuite\Tests\TestCase;

class MigrationsAndSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_package_tables_and_columns_exist(): void
    {
        // Spatie RBAC tables
        $this->assertTrue(Schema::hasTable('permissions'), 'Permissions table must exist');
        $this->assertTrue(Schema::hasTable('roles'), 'Roles table must exist');
        $this->assertTrue(Schema::hasTable('model_has_permissions'), 'Model has permissions table must exist');
        $this->assertTrue(Schema::hasTable('model_has_roles'), 'Model has roles table must exist');
        $this->assertTrue(Schema::hasTable('role_has_permissions'), 'Role has permissions table must exist');

        // RBAC Authentication and Security tables
        $this->assertTrue(Schema::hasTable('authentication_settings'), 'Authentication settings table must exist');
        $this->assertTrue(Schema::hasTable('password_rules'), 'Password rules table must exist');
        $this->assertTrue(Schema::hasTable('auth_provider_settings'), 'Auth provider settings table must exist');
        $this->assertTrue(Schema::hasTable('email_login_otp'), 'Email login OTP table must exist');
        $this->assertTrue(Schema::hasTable('user_two_factor'), 'User two factor table must exist');
        $this->assertTrue(Schema::hasTable('user_devices'), 'User devices table must exist');

        // Users table RBAC columns
        $userColumns = [
            'address',
            'mobile_number',
            'mobile_verified_at',
            'is_active',
            'created_id',
            'updated_id',
            'deleted_id',
            'deleted_at',
            'password_updated_at',
            'password_updated_id',
        ];

        foreach ($userColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('users', $column),
                "Users table must have column '{$column}'"
            );
        }
    }

    public function test_migrations_are_safe_and_idempotent_when_rerun(): void
    {
        // Re-run package migrations directly when tables already exist
        $migrationFiles = glob(dirname(__DIR__, 2).'/database/migrations/*.php');
        $this->assertNotEmpty($migrationFiles);

        foreach ($migrationFiles as $file) {
            $migration = require $file;
            // Running up() again should not throw duplicate table or column exceptions
            $migration->up();
        }

        // Verify tables still exist
        $this->assertTrue(Schema::hasTable('authentication_settings'));
        $this->assertTrue(Schema::hasTable('password_rules'));
    }

    public function test_migrations_rollback_safely(): void
    {
        // Explicitly track tables as package-owned in SchemaTracker to verify package rollback
        SchemaTracker::recordCreatedTable('authentication_settings');
        SchemaTracker::recordCreatedTable('password_rules');

        $authSettingsMigration = require dirname(__DIR__, 2).'/database/migrations/2026_01_01_000003_create_rbac_authentication_settings_table.php';
        $passwordRulesMigration = require dirname(__DIR__, 2).'/database/migrations/2026_01_01_000004_create_rbac_password_rules_table.php';

        // Down should cleanly drop the package-owned tables
        $authSettingsMigration->down();
        $this->assertFalse(Schema::hasTable('authentication_settings'));

        $passwordRulesMigration->down();
        $this->assertFalse(Schema::hasTable('password_rules'));

        // Up should safely recreate them
        $authSettingsMigration->up();
        $this->assertTrue(Schema::hasTable('authentication_settings'));

        $passwordRulesMigration->up();
        $this->assertTrue(Schema::hasTable('password_rules'));
    }

    public function test_rbac_suite_database_seeder_populates_all_defaults(): void
    {
        $this->seed(RbacSuiteDatabaseSeeder::class);

        // 1. Roles & Permissions verification
        $adminRole = Role::where('name', 'Admin')->first();
        $userRole = Role::where('name', 'User')->first();

        $this->assertNotNull($adminRole);
        $this->assertNotNull($userRole);

        $this->assertTrue(Permission::where('name', 'dashboard-view')->exists());
        $this->assertTrue(Permission::where('name', 'role-create')->exists());
        $this->assertTrue(Permission::where('name', 'user-password-change')->exists());
        $this->assertTrue(Permission::where('name', 'auth_setting-view')->exists());

        $this->assertTrue($adminRole->hasPermissionTo('role-create'));
        $this->assertTrue($userRole->hasPermissionTo('dashboard-view'));
        $this->assertFalse($userRole->hasPermissionTo('role-create'));

        // 2. Authentication Settings verification
        $this->assertEquals(7, AuthSetting::count());
        $this->assertTrue(AuthSetting::where('AuthCode', AuthSetting::CODE_EMAIL_OTP)->exists());
        $this->assertTrue(AuthSetting::where('AuthCode', AuthSetting::CODE_SIGNUP_REGISTRATION)->exists());

        // 3. Password Rules verification
        $this->assertEquals(5, PasswordRule::count());
        $minLengthRule = PasswordRule::where('RuleCode', PasswordRule::RULE_MIN_LENGTH)->first();
        $this->assertNotNull($minLengthRule);
        $this->assertEquals(8, $minLengthRule->RuleValue);

        // 4. Auth Provider Settings verification
        $this->assertEquals(3, AuthProviderSetting::count());
        $this->assertTrue(AuthProviderSetting::where('provider_code', AuthProviderSetting::PROVIDER_WHATSAPP)->exists());
        $this->assertTrue(AuthProviderSetting::where('provider_code', AuthProviderSetting::PROVIDER_SMS)->exists());
        $this->assertTrue(AuthProviderSetting::where('provider_code', AuthProviderSetting::PROVIDER_SMTP)->exists());

        // 5. Default Admin User verification
        $this->assertDatabaseHas('users', ['email' => 'admin@gmail.com']);
        $adminUser = User::where('email', 'admin@gmail.com')->first();
        $this->assertNotNull($adminUser);
        $this->assertTrue($adminUser->hasRole('Admin'));
        $this->assertTrue(Hash::check('password', $adminUser->password));
    }

    public function test_seeders_are_idempotent_and_preserve_customized_values(): void
    {
        // First run
        $this->seed(RbacSuiteDatabaseSeeder::class);

        // Customize some values
        $emailSetting = AuthSetting::where('AuthCode', AuthSetting::CODE_EMAIL_OTP)->first();
        $emailSetting->update([
            'IsEnabled' => 0,
            'OTPExpiryTime' => 25,
        ]);

        $minLengthRule = PasswordRule::where('RuleCode', PasswordRule::RULE_MIN_LENGTH)->first();
        $minLengthRule->update([
            'RuleValue' => 14,
            'IsEnabled' => 1,
        ]);

        $smsProvider = AuthProviderSetting::where('provider_code', AuthProviderSetting::PROVIDER_SMS)->first();
        $smsProvider->update([
            'token' => 'custom-production-token',
        ]);

        // Re-run the master seeder
        $this->seed(RbacSuiteDatabaseSeeder::class);

        // Verify no duplicate records were created
        $this->assertEquals(7, AuthSetting::count(), 'Auth settings count must remain 7 without duplicates');
        $this->assertEquals(5, PasswordRule::count(), 'Password rules count must remain 5 without duplicates');
        $this->assertEquals(3, AuthProviderSetting::count(), 'Auth providers count must remain 3 without duplicates');
        $this->assertEquals(2, Role::whereIn('name', ['Admin', 'User'])->count(), 'Roles count must remain 2');

        // Verify customized values were preserved and NOT overwritten
        $emailSetting->refresh();
        $this->assertEquals(0, $emailSetting->IsEnabled, 'Customized IsEnabled should be preserved');
        $this->assertEquals(25, $emailSetting->OTPExpiryTime, 'Customized OTPExpiryTime should be preserved');

        $minLengthRule->refresh();
        $this->assertEquals(14, $minLengthRule->RuleValue, 'Customized RuleValue should be preserved');
        $this->assertEquals(1, $minLengthRule->IsEnabled, 'Customized IsEnabled should be preserved');

        $smsProvider->refresh();
        $this->assertEquals('custom-production-token', $smsProvider->token, 'Customized provider token should be preserved');
    }
}
