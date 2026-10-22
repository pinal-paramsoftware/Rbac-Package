<?php

namespace Parth\LaravelRbacSuite\Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Parth\LaravelRbacSuite\Database\Seeders\RbacSuiteDatabaseSeeder;
use Parth\LaravelRbacSuite\Models\Role;
use Parth\LaravelRbacSuite\RbacSuite;
use Parth\LaravelRbacSuite\RbacSuiteServiceProvider;
use Parth\LaravelRbacSuite\Support\RbacAsset;
use Parth\LaravelRbacSuite\Support\SchemaTracker;
use Parth\LaravelRbacSuite\Tests\TestCase;

class AdminPanelUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSuiteDatabaseSeeder::class);
    }

    public function test_bundled_layout_renders_default_minia_theme(): void
    {
        $adminUser = $this->createAdminUser();

        $response = $this->actingAs($adminUser)->get(route('roles.index'));

        $response->assertStatus(200);
        $response->assertSee('layout-wrapper', false);
        $response->assertSee('id="page-topbar"', false);
        $response->assertSee('class="vertical-menu"', false);
        $response->assertSee('Roles List');
    }

    public function test_custom_layout_configuration_is_respected(): void
    {
        View::addLocation(dirname(__DIR__).'/fixtures');

        config(['rbac-suite.layout' => 'custom-layout']);

        $this->assertEquals('custom-layout', RbacSuite::getLayout());

        $adminUser = $this->createAdminUser();
        $response = $this->actingAs($adminUser)->get(route('roles.index'));

        $response->assertStatus(200);
        $response->assertSee('id="custom-host-layout-shell"', false);
        $response->assertSee('Roles List');
    }

    public function test_missing_or_invalid_layout_falls_back_safely_to_bundled_layout(): void
    {
        config(['rbac-suite.layout' => 'nonexistent.invalid.layout.path']);

        // getLayout() should detect that view does not exist and return safe bundled layout
        $layout = RbacSuite::getLayout();
        $this->assertEquals('rbac-suite::layouts.master', $layout);

        $adminUser = $this->createAdminUser();
        $response = $this->actingAs($adminUser)->get(route('roles.index'));

        $response->assertStatus(200);
        $response->assertSee('layout-wrapper', false);
        $response->assertSee('Roles List');
    }

    public function test_rbac_asset_helper_and_blade_directive(): void
    {
        $url = RbacAsset::url('css/bootstrap.css');
        $this->assertNotEmpty($url);
        $this->assertStringContainsString('css/bootstrap.css', $url);

        $rendered = Blade::render("@rbacAsset('css/icons.css')");
        $this->assertStringContainsString('css/icons.css', $rendered);
    }

    public function test_sidebar_navigation_is_strictly_permission_aware(): void
    {
        $adminUser = $this->createAdminUser();
        $standardUser = $this->createStandardUser();

        // 1. Admin sees all modules
        $adminResponse = $this->actingAs($adminUser)->get(route('roles.index'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Dashboard');
        $adminResponse->assertSee('Roles');
        $adminResponse->assertSee('Users');
        $adminResponse->assertSee('Permissions');
        $adminResponse->assertSee('Auth Setting');

        // 2. Standard user (only dashboard-view) is blocked from viewing admin modules
        $standardResponse = $this->actingAs($standardUser)->get(route('roles.index'));
        $standardResponse->assertStatus(403);

        // Standard user on a permitted route (e.g. dashboard/home)
        $homeResponse = $this->actingAs($standardUser)->get('/');
        // Sidebar rendered for standard user must NOT contain Roles, Users, Permissions, or Settings
        $homeResponse->assertDontSee('href="'.route('roles.index').'"', false);
        $homeResponse->assertDontSee('href="'.route('permissions.index').'"', false);
        $homeResponse->assertDontSee('href="'.route('auth.settings').'"', false);
    }

    public function test_active_menu_state_on_nested_crud_routes(): void
    {
        $adminUser = $this->createAdminUser();

        // On roles.index
        $indexResponse = $this->actingAs($adminUser)->get(route('roles.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('mm-active', false);
        $indexResponse->assertSee('href="'.route('roles.index').'"', false);

        // On roles.create
        $createResponse = $this->actingAs($adminUser)->get(route('roles.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertSee('mm-active', false);
        $createResponse->assertSee('Create Role');
    }

    public function test_publishable_assets_are_registered_properly(): void
    {
        $pathsToPublish = RbacSuiteServiceProvider::pathsToPublish();

        $this->assertNotEmpty($pathsToPublish);
        $this->assertArrayHasKey('rbac-suite-assets', RbacSuiteServiceProvider::$publishGroups);
        $this->assertArrayHasKey('rbac-suite-config', RbacSuiteServiceProvider::$publishGroups);
        $this->assertArrayHasKey('rbac-suite-views', RbacSuiteServiceProvider::$publishGroups);
        $this->assertArrayHasKey('rbac-suite-migrations', RbacSuiteServiceProvider::$publishGroups);
        $this->assertArrayHasKey('rbac-suite-seeders', RbacSuiteServiceProvider::$publishGroups);
        $this->assertArrayHasKey('rbac-suite', RbacSuiteServiceProvider::$publishGroups);
    }

    public function test_critical_migration_rollback_audit_preserves_preexisting_host_tables_and_columns(): void
    {
        // Simulate a pre-existing host table that was NOT created by the package
        SchemaTracker::ensureManifestTable();

        // 1. Manually create a mock pre-existing table 'host_existing_table'
        Schema::create('host_existing_table', function ($table) {
            $table->id();
            $table->string('data');
        });

        // Verify it was NOT recorded in manifest
        $this->assertFalse(SchemaTracker::wasCreatedByPackage('table', 'host_existing_table'));

        // 2. Call rollback logic on permissions migration
        $permissionsMigration = require dirname(__DIR__, 2).'/database/migrations/2026_01_01_000001_create_rbac_permissions_tables.php';

        // Fake 'host_existing_table' as table in permission config
        config(['permission.table_names.permissions' => 'host_existing_table']);

        // Running down() must NOT drop 'host_existing_table' because wasCreatedByPackage is false!
        $permissionsMigration->down();

        $this->assertTrue(
            Schema::hasTable('host_existing_table'),
            'Pre-existing table MUST NOT be dropped during package rollback!'
        );

        // Clean up test table
        Schema::dropIfExists('host_existing_table');

        // 3. Test pre-existing user column safety
        $usersTable = config('rbac-suite.tables.users', 'users');
        $this->assertTrue(Schema::hasTable($usersTable));

        // Add a custom pre-existing column directly without recording it in manifest
        if (! Schema::hasColumn($usersTable, 'host_preexisting_column')) {
            Schema::table($usersTable, function ($table) {
                $table->string('host_preexisting_column')->nullable();
            });
        }

        // Verify it is NOT in manifest
        $this->assertFalse(SchemaTracker::wasCreatedByPackage('column', $usersTable, 'host_preexisting_column'));

        // Run user columns migration down()
        $userMigration = require dirname(__DIR__, 2).'/database/migrations/2026_01_01_000002_add_rbac_columns_to_users_table.php';
        $userMigration->down();

        // Verify pre-existing column is STILL there!
        $this->assertTrue(
            Schema::hasColumn($usersTable, 'host_preexisting_column'),
            'Pre-existing user column MUST NOT be dropped during package rollback!'
        );

        // Clean up mock column
        Schema::table($usersTable, function ($table) {
            $table->dropColumn('host_preexisting_column');
        });
    }

    protected function createAdminUser()
    {
        $userModel = config('rbac-suite.user_model', 'App\\Models\\User');
        $user = $userModel::firstOrCreate(
            ['email' => 'admin.test@example.com'],
            [
                'name' => 'Admin Tester',
                'password' => Hash::make('Password@123'),
                'is_active' => '1',
            ]
        );

        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole && ! $user->hasRole('Admin')) {
            $user->assignRole($adminRole);
        }

        return $user;
    }

    protected function createStandardUser()
    {
        $userModel = config('rbac-suite.user_model', 'App\\Models\\User');
        $user = $userModel::firstOrCreate(
            ['email' => 'standard.test@example.com'],
            [
                'name' => 'Standard Tester',
                'password' => Hash::make('Password@123'),
                'is_active' => '1',
            ]
        );

        $userRole = Role::where('name', 'User')->first();
        if ($userRole && ! $user->hasRole('User')) {
            $user->assignRole($userRole);
        }

        return $user;
    }
}
