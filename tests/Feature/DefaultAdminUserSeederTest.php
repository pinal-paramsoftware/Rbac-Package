<?php

namespace Parth\LaravelRbacSuite\Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Parth\LaravelRbacSuite\Database\Seeders\DefaultAdminUserSeeder;
use Parth\LaravelRbacSuite\Database\Seeders\RbacSuiteDatabaseSeeder;
use Parth\LaravelRbacSuite\Database\Seeders\RolePermissionSeeder;
use Parth\LaravelRbacSuite\Models\Permission;
use Parth\LaravelRbacSuite\Models\Role;
use Parth\LaravelRbacSuite\Tests\TestCase;

class DefaultAdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_admin_user_is_created_when_missing(): void
    {
        $this->assertDatabaseMissing('users', ['email' => 'admin@gmail.com']);

        $this->seed(DefaultAdminUserSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@gmail.com',
            'name' => 'Admin',
        ]);

        /** @var User $user */
        $user = User::where('email', 'admin@gmail.com')->first();
        $this->assertNotNull($user);

        // Password must be hashed and match default
        $this->assertTrue(Hash::check('password', $user->password));

        // Role must be assigned
        $this->assertTrue($user->hasRole('Admin'));
        $this->assertTrue($user->isAdmin());
    }

    public function test_admin_role_is_created_via_role_seeder_if_not_present(): void
    {
        // Ensure no roles exist yet
        $this->assertEquals(0, Role::count());

        $this->seed(DefaultAdminUserSeeder::class);

        // RolePermissionSeeder should have been called, creating Admin and User roles
        $this->assertTrue(Role::where('name', 'Admin')->exists());
        $this->assertTrue(Role::where('name', 'User')->exists());
        $this->assertTrue(Permission::where('name', 'dashboard-view')->exists());

        /** @var User $user */
        $user = User::where('email', 'admin@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Admin'));
        $this->assertTrue($user->hasPermissionTo('role-create'));
    }

    public function test_running_seeder_twice_is_idempotent_and_creates_no_duplicates(): void
    {
        // First run
        $this->seed(RbacSuiteDatabaseSeeder::class);

        $initialUserCount = User::where('email', 'admin@gmail.com')->count();
        $initialRoleCount = Role::count();
        $initialPermissionCount = Permission::count();

        $this->assertEquals(1, $initialUserCount);

        // Second run
        $this->seed(DefaultAdminUserSeeder::class);
        $this->seed(RbacSuiteDatabaseSeeder::class);

        $this->assertEquals(1, User::where('email', 'admin@gmail.com')->count(), 'Duplicate admin user must not be created');
        $this->assertEquals($initialRoleCount, Role::count(), 'Duplicate roles must not be created');
        $this->assertEquals($initialPermissionCount, Permission::count(), 'Duplicate permissions must not be created');
    }

    public function test_existing_user_password_and_profile_are_not_overwritten(): void
    {
        // Pre-create user with custom password, name, and address
        $customPasswordHash = Hash::make('custom-secure-password-1234');
        $user = new User;
        $user->name = 'Pre Existing User';
        $user->email = 'admin@gmail.com';
        $user->password = $customPasswordHash;
        $user->address = 'Original Head Office Address';
        $user->is_active = '1';
        $user->save();

        // Seed roles beforehand
        $this->seed(RolePermissionSeeder::class);

        // Run DefaultAdminUserSeeder
        $this->seed(DefaultAdminUserSeeder::class);

        $user->refresh();

        // Details must NOT be overwritten
        $this->assertEquals('Pre Existing User', $user->name);
        $this->assertEquals('Original Head Office Address', $user->address);
        $this->assertTrue(Hash::check('custom-secure-password-1234', $user->password));
        $this->assertFalse(Hash::check('password', $user->password));

        // Role must still be assigned
        $this->assertTrue($user->hasRole('Admin'));
    }

    public function test_rbac_suite_database_seeder_includes_admin_user_seeder(): void
    {
        $this->assertDatabaseMissing('users', ['email' => 'admin@gmail.com']);

        $this->seed(RbacSuiteDatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'admin@gmail.com']);
        $this->assertDatabaseHas('roles', ['name' => 'Admin']);

        /** @var User $user */
        $user = User::where('email', 'admin@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Admin'));
    }

    public function test_admin_role_receives_all_catalogue_permissions_when_admin_user_seeder_runs_standalone(): void
    {
        // Pre-create empty Admin role with 0 permissions
        Role::create(['name' => 'Admin', 'guard_name' => 'web']);

        $this->seed(DefaultAdminUserSeeder::class);

        /** @var Role $adminRole */
        $adminRole = Role::where('name', 'Admin')->first();
        $this->assertNotNull($adminRole);

        $totalPermissions = Permission::count();
        $this->assertEquals(20, $totalPermissions, 'Catalogue must have exactly 20 permissions');
        $this->assertEquals(20, $adminRole->permissions()->count(), 'Admin role must receive all 20 permissions');

        /** @var User $user */
        $user = User::where('email', 'admin@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasPermissionTo('dashboard-view'));
        $this->assertTrue($user->hasPermissionTo('role-create'));
        $this->assertTrue($user->hasPermissionTo('auth_setting-view'));
        $this->assertTrue($user->hasPermissionTo('user-password-change'));
        $this->assertTrue($user->hasPermissionTo('report-delete'));
    }

    public function test_model_has_roles_contains_correct_pivot_row_for_admin_user(): void
    {
        $this->seed(DefaultAdminUserSeeder::class);

        /** @var User $user */
        $user = User::where('email', 'admin@gmail.com')->first();
        $this->assertNotNull($user);

        /** @var Role $adminRole */
        $adminRole = Role::where('name', 'Admin')->first();
        $this->assertNotNull($adminRole);

        $pivotTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $morphKey = config('permission.column_names.model_morph_key', 'model_id');

        $this->assertDatabaseHas($pivotTable, [
            'role_id' => $adminRole->id,
            'model_type' => $user->getMorphClass(),
            $morphKey => $user->getKey(),
        ]);
    }
}
