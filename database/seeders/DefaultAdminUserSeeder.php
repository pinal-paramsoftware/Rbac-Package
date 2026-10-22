<?php

namespace Parth\LaravelRbacSuite\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Parth\LaravelRbacSuite\Models\Permission;
use Parth\LaravelRbacSuite\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DefaultAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guard = config('rbac-suite.guard') ?: config('auth.defaults.guard', 'web');
        $adminRoleName = config('rbac-suite.roles.admin_name', 'Admin');
        $roleClass = config('permission.models.role', Role::class);
        $permissionClass = config('permission.models.permission', Permission::class);

        // 1. Ensure the Admin role exists through established flow
        $role = $this->resolveAdminRole($roleClass, $adminRoleName, $guard);

        // 2. Ensure all catalogue permissions are seeded and assigned to Admin role
        $allPermissions = $permissionClass::where('guard_name', $guard)->get();
        if ($allPermissions->isEmpty()) {
            $this->call(RolePermissionSeeder::class);
            $allPermissions = $permissionClass::where('guard_name', $guard)->get();
        }

        if (method_exists($role, 'syncPermissions') && $allPermissions->isNotEmpty()) {
            $role->syncPermissions($allPermissions);
        }

        // 3. Resolve target User model class
        $userClass = $this->resolveUserModelClass();

        // 4. Locate or create default admin user
        $user = $userClass::where('email', 'admin@gmail.com')->first();

        if (! $user) {
            $user = new $userClass;
            $user->name = 'Admin';
            $user->email = 'admin@gmail.com';
            $user->password = Hash::make('password');

            $tableName = $user->getTable();

            if (Schema::hasColumn($tableName, 'email_verified_at')) {
                $user->email_verified_at = now();
            }

            if (Schema::hasColumn($tableName, 'is_active')) {
                $user->is_active = '1';
            }

            $user->save();
        }

        // 5. Assign Admin role to the user and ensure model_has_roles is ALWAYS populated
        $assigned = false;
        if (method_exists($user, 'hasRole') && method_exists($user, 'assignRole')) {
            try {
                if (! $user->hasRole($role->name, $guard)) {
                    $user->assignRole($role);
                }
                $assigned = true;
            } catch (\Throwable $e) {
                // Fall back to relationships or direct pivot insertion
            }
        }

        if (! $assigned && method_exists($user, 'roles')) {
            try {
                $user->roles()->syncWithoutDetaching([$role->id]);
                $assigned = true;
            } catch (\Throwable $e) {
                // Fall back to direct pivot insertion
            }
        }

        // 6. Direct pivot verification & insertion: guarantees model_has_roles is never empty
        // even if the user model lacked the HasRoles trait at seed time
        $pivotTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $morphKey = config('permission.column_names.model_morph_key', 'model_id');
        $teamForeignKey = config('permission.column_names.team_foreign_key', 'team_id');
        $teams = config('permission.teams', false);

        $pivotAttributes = [
            'role_id' => $role->id,
            'model_type' => $user->getMorphClass(),
            $morphKey => $user->getKey(),
        ];

        if ($teams && Schema::hasColumn($pivotTable, $teamForeignKey)) {
            $pivotAttributes[$teamForeignKey] = getPermissionsTeamId();
        }

        DB::table($pivotTable)->updateOrInsert($pivotAttributes, $pivotAttributes);

        // 7. Invalidate Spatie's permission cache
        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        // 8. Confirm pivot row actually exists in model_has_roles
        $existsInPivot = DB::table($pivotTable)
            ->where('role_id', $role->id)
            ->where('model_type', $user->getMorphClass())
            ->where($morphKey, $user->getKey())
            ->exists();

        if (! $existsInPivot) {
            throw new \RuntimeException("Failed to verify Admin role assignment in {$pivotTable} table.");
        }
    }

    /**
     * Resolve or create the admin role using the established role/permission flow.
     *
     * @param  class-string<\Spatie\Permission\Contracts\Role>  $roleClass
     */
    protected function resolveAdminRole(string $roleClass, string $adminRoleName, string $guard): mixed
    {
        /** @var \Spatie\Permission\Contracts\Role|null $role */
        $role = $roleClass::where('guard_name', $guard)
            ->where(function ($query) use ($adminRoleName) {
                $query->where('name', $adminRoleName)
                    ->orWhereRaw('LOWER(name) = ?', [strtolower($adminRoleName)]);
            })
            ->first();

        if (! $role) {
            $this->call(RolePermissionSeeder::class);

            $role = $roleClass::where('guard_name', $guard)
                ->where(function ($query) use ($adminRoleName) {
                    $query->where('name', $adminRoleName)
                        ->orWhereRaw('LOWER(name) = ?', [strtolower($adminRoleName)]);
                })
                ->first();

            if (! $role) {
                $role = $roleClass::firstOrCreate([
                    'name' => $adminRoleName,
                    'guard_name' => $guard,
                ]);
            }
        }

        return $role;
    }

    /**
     * Resolve the configured User model class.
     *
     * @return class-string<Model>
     */
    protected function resolveUserModelClass(): string
    {
        $guard = config('rbac-suite.guard') ?: config('auth.defaults.guard', 'web');
        $provider = config("auth.guards.{$guard}.provider", 'users');
        $providerModel = config("auth.providers.{$provider}.model");

        /** @var class-string<Model> $userClass */
        $userClass = config('rbac-suite.user_model')
            ?: ($providerModel ?: (config('auth.providers.users.model') ?: 'App\\Models\\User'));

        if (! class_exists($userClass)) {
            if (class_exists('Parth\\LaravelRbacSuite\\Tests\\Fixtures\\User')) {
                $userClass = 'Parth\\LaravelRbacSuite\\Tests\\Fixtures\\User';
            }
        }

        return $userClass;
    }
}
