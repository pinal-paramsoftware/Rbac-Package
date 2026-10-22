<?php

namespace Parth\LaravelRbacSuite\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Parth\LaravelRbacSuite\Traits\ResolvesEncryptedId;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends BaseController
{
    use ResolvesEncryptedId;

    /**
     * List all roles with permissions.
     */
    public function index()
    {
        $roles = Role::with('permissions')->get();

        return view('rbac-suite::roles.index', compact('roles'));
    }

    /**
     * Show role creation form with permissions grouped by module.
     */
    public function create()
    {
        $permissions = Permission::orderBy('name')->get()
            ->groupBy(function ($perm) {
                return explode('-', $perm->name)[0];
            });

        $operations = Permission::all()->map(function ($perm) {
            $parts = explode('-', $perm->name);

            return array_pop($parts);
        })->unique()->sort()->values();

        return view('rbac-suite::roles.create', compact('permissions', 'operations'));
    }

    /**
     * Create new role with selected permissions.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'required|array',
        ], [
            'name.required' => 'The role name field is required.',
            'name.unique' => 'The role name has already been taken.',
            'permissions.required' => 'At least one permission must be selected.',
            'permissions.array' => 'The permissions must be an array.',
        ]);

        DB::beginTransaction();

        try {
            $role = Role::create([
                'name' => trim((string) $request->name),
                'guard_name' => config('rbac-suite.guard', 'web'),
            ]);
            $role->syncPermissions($request->permissions);

            DB::commit();

            $successMsg = 'Role created successfully.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'role' => $role,
                ], 201);
            }

            return redirect()->route('roles.index')->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create role: '.$e->getMessage(), ['exception' => $e]);

            $errorMsg = 'Something went wrong while creating the role.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', $errorMsg);
        }
    }

    /**
     * Display role with its permissions.
     */
    public function show($id)
    {
        $roleId = $this->resolveEncryptedId($id, 'role');
        $role = Role::with('permissions')->findOrFail($roleId);

        return view('rbac-suite::roles.show', compact('role'));
    }

    /**
     * Show role edit form with permission options.
     */
    public function edit($id)
    {
        $roleId = $this->resolveEncryptedId($id, 'role');
        $role = Role::findOrFail($roleId);

        $permissions = Permission::all()->groupBy(function ($perm) {
            return explode('-', $perm->name)[0];
        });

        $operations = Permission::all()->map(function ($perm) {
            $parts = explode('-', $perm->name);

            return array_pop($parts);
        })->unique()->sort()->values();

        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('rbac-suite::roles.edit', compact(
            'role',
            'permissions',
            'operations',
            'rolePermissions'
        ));
    }

    /**
     * Update role name and permissions (protects admin role).
     */
    public function update(Request $request, $id)
    {
        $roleId = $this->resolveEncryptedId($id, 'role');
        $role = Role::findOrFail($roleId);
        $adminRoleName = config('rbac-suite.roles.admin_name', 'Admin');

        $request->validate([
            'name' => 'required|unique:roles,name,'.$role->id,
            'permissions' => 'nullable|array',
        ], [
            'name.required' => 'The role name field is required.',
            'name.unique' => 'The role name has already been taken.',
        ]);

        DB::beginTransaction();

        try {
            $role->update([
                'name' => trim((string) $request->name),
            ]);

            $permissions = $request->permissions ?? [];

            // Preserve admin role's core permissions
            if (strcasecmp($role->name, $adminRoleName) === 0) {
                $existingPerms = $role->permissions->pluck('name')->toArray();
                $protectedModules = ['role', 'user', 'permission'];

                foreach ($existingPerms as $perm) {
                    $module = explode('-', $perm)[0];
                    if (in_array($module, $protectedModules) && ! in_array($perm, $permissions)) {
                        $permissions[] = $perm;
                    }
                }
            }

            $role->syncPermissions($permissions);

            DB::commit();

            $successMsg = 'Role updated successfully.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'role' => $role,
                ]);
            }

            return redirect()->route('roles.index')->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update role: '.$e->getMessage(), ['exception' => $e]);

            $errorMsg = 'Something went wrong while updating the role.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', $errorMsg);
        }
    }

    /**
     * Delete role (prevents admin role deletion and in-use roles).
     */
    public function destroy(Request $request, $id)
    {
        $roleId = $this->resolveEncryptedId($id, 'role');
        $role = Role::find($roleId);
        $adminRoleName = config('rbac-suite.roles.admin_name', 'Admin');

        if (! $role) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found.',
                ], 404);
            }

            return redirect()->back()->with('error', 'Role not found.');
        }

        if (strcasecmp($role->name, $adminRoleName) === 0) {
            $errorMsg = 'Admin role cannot be deleted.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 403);
            }

            return redirect()->back()->with('error', $errorMsg);
        }

        // Check if any users are currently assigned to this role
        if (method_exists($role, 'users') && $role->users()->count() > 0) {
            $count = $role->users()->count();
            $errorMsg = "Cannot delete role because it is currently assigned to {$count} user(s).";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 422);
            }

            return redirect()->back()->with('error', $errorMsg);
        }

        try {
            $role->delete();

            $successMsg = 'Role deleted successfully.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                ]);
            }

            return redirect()->route('roles.index')->with('success', $successMsg);
        } catch (\Exception $e) {
            Log::error('Failed to delete role: '.$e->getMessage(), ['exception' => $e]);

            $errorMsg = 'Something went wrong while deleting the role.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 500);
            }

            return redirect()->back()->with('error', $errorMsg);
        }
    }
}
