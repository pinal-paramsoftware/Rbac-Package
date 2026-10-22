<?php

namespace Parth\LaravelRbacSuite\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Parth\LaravelRbacSuite\Traits\ResolvesEncryptedId;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends BaseController
{
    use ResolvesEncryptedId;

    /**
     * List all permissions.
     */
    public function index(): View
    {
        $permissions = Permission::get();

        return view('rbac-suite::permissions.index', compact('permissions'));
    }

    /**
     * Show permission creation form.
     */
    public function create(): View
    {
        return view('rbac-suite::permissions.create');
    }

    /**
     * Create permissions for a module with multiple operations.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'operations' => 'required|array|min:1',
            'operations.*' => 'required|string|max:255|regex:/^[a-z]+$/',
        ], [
            'operations.*.regex' => 'Each operation must contain only lowercase letters.',
        ]);

        $permissionName = strtolower(trim((string) $request->input('name')));
        $operations = array_values(array_unique(array_filter($request->input('operations', []))));

        if (empty($operations)) {
            $errorMsg = 'At least one valid operation must be provided.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 422);
            }

            return redirect()->back()->withInput()->with('error', $errorMsg);
        }

        DB::beginTransaction();

        try {
            $adminRoleName = config('rbac-suite.roles.admin_name', 'Admin');
            $adminRole = Role::whereRaw('LOWER(name) = ?', [strtolower($adminRoleName)])->first();
            $guardName = config('rbac-suite.guard', 'web');
            $createdPermissions = [];

            // Create permission for each operation and assign to admin
            foreach ($operations as $operation) {
                $name = $permissionName.'-'.trim((string) $operation);
                $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => $guardName]);

                if ($adminRole && ! $adminRole->hasPermissionTo($permission)) {
                    $adminRole->givePermissionTo($permission);
                }

                $createdPermissions[] = $permission;
            }

            DB::commit();

            $successMsg = 'Permission created successfully.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'permissions' => $createdPermissions,
                ], 201);
            }

            return redirect()->route('permissions.index')->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create permission: '.$e->getMessage(), ['exception' => $e]);

            $errorMsg = 'Failed to create permission. Please try again.';

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
     * Show permission edit form with operations.
     */
    public function edit(string|int $id): View
    {
        $permission = $this->resolvePermissionModel($id);

        $parts = explode('-', $permission->name);
        $operation = array_pop($parts);
        $baseName = implode('-', $parts);

        $basePermissions = Permission::where('name', 'like', $baseName.'-%')->get();
        $operations = $basePermissions->map(function ($perm) use ($baseName) {
            return str_replace($baseName.'-', '', $perm->name);
        })->toArray();

        return view('rbac-suite::permissions.edit', compact('permission', 'baseName', 'operation', 'operations'));
    }

    /**
     * Update permission module and operations with assignment validation.
     */
    public function update(Request $request, Permission|string|int $id): RedirectResponse|JsonResponse
    {
        $permission = $this->resolvePermissionModel($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'operations' => 'required|array|min:1',
            'operations.*' => 'required|string|max:255|regex:/^[a-z]+$/',
        ], [
            'operations.*.regex' => 'Each operation must contain only lowercase letters.',
        ]);

        $oldBaseName = explode('-', $permission->name)[0];
        $newBaseName = strtolower(trim((string) $request->input('name')));
        $operations = array_values(array_unique(array_filter($request->input('operations', []))));

        if (empty($operations)) {
            $errorMsg = 'At least one valid operation must be provided.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 422);
            }

            return redirect()->back()->withInput()->with('error', $errorMsg);
        }

        $oldPermissions = Permission::where('name', 'like', $oldBaseName.'-%')->get();

        // If module name changed, check for assignments before deleting
        if ($oldBaseName !== $newBaseName) {
            foreach ($oldPermissions as $oldPerm) {
                if ($this->isAssignedToNonAdmin($oldPerm)) {
                    $errorMsg = 'Cannot update permission(s) that are assigned to roles or users. Please remove the permission from all roles and users first.';
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $errorMsg,
                        ], 422);
                    }

                    return redirect()->route('permissions.index')->with('error', $errorMsg);
                }
            }
        } else {
            // Remove operations no longer in list
            $newPermissionNames = array_map(fn ($op) => $newBaseName.'-'.$op, $operations);
            foreach ($oldPermissions as $oldPerm) {
                if (! in_array($oldPerm->name, $newPermissionNames)) {
                    if ($this->isAssignedToNonAdmin($oldPerm)) {
                        $errorMsg = 'Cannot delete permission "'.$oldPerm->name.'" that is assigned to roles or users. Please remove it first.';
                        if ($request->ajax() || $request->wantsJson()) {
                            return response()->json([
                                'success' => false,
                                'message' => $errorMsg,
                            ], 422);
                        }

                        return redirect()->route('permissions.index')->with('error', $errorMsg);
                    }
                }
            }
        }

        DB::beginTransaction();

        try {
            if ($oldBaseName !== $newBaseName) {
                Permission::where('name', 'like', $oldBaseName.'-%')->delete();
            } else {
                $newPermissionNames = array_map(fn ($op) => $newBaseName.'-'.$op, $operations);
                foreach ($oldPermissions as $oldPerm) {
                    if (! in_array($oldPerm->name, $newPermissionNames)) {
                        $oldPerm->delete();
                    }
                }
            }

            // Create new permissions and assign to admin role
            $adminRoleName = config('rbac-suite.roles.admin_name', 'Admin');
            $adminRole = Role::whereRaw('LOWER(name) = ?', [strtolower($adminRoleName)])->first();
            $guardName = config('rbac-suite.guard', 'web');
            $updatedPermissions = [];

            foreach ($operations as $operation) {
                $name = $newBaseName.'-'.$operation;
                $newPerm = Permission::firstOrCreate(['name' => $name, 'guard_name' => $guardName]);

                if ($adminRole && ! $adminRole->hasPermissionTo($newPerm)) {
                    $adminRole->givePermissionTo($newPerm);
                }

                $updatedPermissions[] = $newPerm;
            }

            DB::commit();

            $successMsg = 'Permission updated successfully.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'permissions' => $updatedPermissions,
                ]);
            }

            return redirect()->route('permissions.index')->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update permission: '.$e->getMessage(), ['exception' => $e]);

            $errorMsg = 'Failed to update permission. Please try again.';

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
     * Delete permission and all related operations if not assigned.
     */
    public function destroy(Request $request, Permission|string|int $id): RedirectResponse|JsonResponse
    {
        $permission = $this->resolvePermissionModel($id);

        $parts = explode('-', $permission->name);
        $operation = array_pop($parts);
        $baseName = implode('-', $parts);

        $permissionsToDelete = Permission::where('name', 'like', $baseName.'-%')->get();

        // Prevent deletion if assigned to roles (other than admin) or users
        foreach ($permissionsToDelete as $perm) {
            if ($this->isAssignedToNonAdmin($perm)) {
                $errorMsg = 'Cannot delete permission(s) that are assigned to roles or users. Please remove the permission from all roles and users first.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                    ], 422);
                }

                return redirect()->route('permissions.index')->with('error', $errorMsg);
            }
        }

        DB::beginTransaction();

        try {
            Permission::where('name', 'like', $baseName.'-%')->delete();

            DB::commit();

            $successMsg = 'Permissions deleted successfully.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                ]);
            }

            return redirect()->route('permissions.index')->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete permission: '.$e->getMessage(), ['exception' => $e]);

            $errorMsg = 'Failed to delete permission. Please try again.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 500);
            }

            return redirect()->back()->with('error', $errorMsg);
        }
    }

    /**
     * Display a permission.
     */
    public function show(Permission|string|int $id): View
    {
        $permission = $this->resolvePermissionModel($id);

        return view('rbac-suite::permissions.show', compact('permission'));
    }

    /**
     * Resolve encrypted, numeric, or model instance.
     */
    protected function resolvePermissionModel(Permission|string|int $permission): Permission
    {
        if ($permission instanceof Permission) {
            return $permission;
        }

        $id = $this->resolveEncryptedId($permission, 'permission');

        return Permission::findOrFail($id);
    }

    /**
     * Check if permission is assigned to users or non-admin roles.
     */
    protected function isAssignedToNonAdmin(Permission $permission): bool
    {
        if ($permission->users()->exists()) {
            return true;
        }

        $adminRoleName = strtolower(config('rbac-suite.roles.admin_name', 'Admin'));

        return $permission->roles()
            ->whereRaw('LOWER(name) != ?', [$adminRoleName])
            ->exists();
    }
}
