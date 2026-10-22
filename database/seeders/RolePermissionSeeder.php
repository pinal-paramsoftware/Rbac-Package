<?php

namespace Parth\LaravelRbacSuite\Database\Seeders;

use Illuminate\Database\Seeder;
use Parth\LaravelRbacSuite\Models\Permission;
use Parth\LaravelRbacSuite\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $guard = config('rbac-suite.guard', 'web');
        $adminRoleName = config('rbac-suite.roles.admin_name', 'Admin');
        $userRoleName = config('rbac-suite.roles.default_user_role', 'User');

        $roleClass = config('permission.models.role', Role::class);
        $permissionClass = config('permission.models.permission', Permission::class);

        $permissions = [
            'dashboard-view',
            'role-view',
            'role-create',
            'role-edit',
            'role-delete',
            'permission-view',
            'permission-create',
            'permission-edit',
            'permission-delete',
            'user-view',
            'user-create',
            'user-edit',
            'user-delete',
            'user-password-change',
            'auth_setting-view',
            'auth_setting-edit',
            'report-view',
            'report-create',
            'report-edit',
            'report-delete',
        ];

        foreach ($permissions as $permissionName) {
            $permissionClass::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guard,
            ]);
        }

        /** @var \Spatie\Permission\Contracts\Role $adminRole */
        $adminRole = $roleClass::firstOrCreate([
            'name' => $adminRoleName,
            'guard_name' => $guard,
        ]);

        /** @var \Spatie\Permission\Contracts\Role $userRole */
        $userRole = $roleClass::firstOrCreate([
            'name' => $userRoleName,
            'guard_name' => $guard,
        ]);

        // Admin gets all defined permissions
        $allPermissions = $permissionClass::where('guard_name', $guard)->get();
        if (method_exists($adminRole, 'syncPermissions')) {
            $adminRole->syncPermissions($allPermissions);
        }

        // Standard user gets basic dashboard access
        if (method_exists($userRole, 'givePermissionTo')) {
            $userRole->givePermissionTo('dashboard-view');
        }

        // Clear cache again
        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
