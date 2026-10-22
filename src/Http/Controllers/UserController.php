<?php

namespace Parth\LaravelRbacSuite\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Parth\LaravelRbacSuite\Models\PasswordRule;
use Parth\LaravelRbacSuite\Rules\ConfiguredPasswordRule;
use Parth\LaravelRbacSuite\Traits\ResolvesEncryptedId;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UserController extends BaseController
{
    use ResolvesEncryptedId;

    /**
     * Get the configured user model class name.
     */
    protected function getUserClass(): string
    {
        return config('rbac-suite.user_model', 'App\\Models\\User');
    }

    /**
     * Display a listing of the users (Web view & Yajra DataTable AJAX).
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $userClass = $this->getUserClass();
            $users = $userClass::with('roles')
                ->select([
                    'id',
                    'name',
                    'email',
                    'mobile_number',
                    'is_active',
                    'created_at',
                ]);

            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('role', function ($user) {
                    $roles = $user->roles->pluck('name');
                    if ($roles->isEmpty()) {
                        return '<span class="badge bg-secondary">No Role</span>';
                    }

                    return $roles->map(function ($role) {
                        return '<span class="badge bg-info me-1">'.e($role).'</span>';
                    })->implode(' ');
                })
                ->addColumn('status', function ($user) {
                    if (method_exists($user, 'trashed') && $user->trashed()) {
                        return '<span class="badge bg-secondary">Deleted</span>';
                    }

                    $isAdmin = method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
                    $canEdit = (bool) auth()->user()?->can('user-edit');
                    $isDisabled = $isAdmin || ! $canEdit;
                    $isActive = (string) $user->is_active === '1';

                    $checkedAttr = $isActive ? 'checked' : '';
                    $disabledAttr = $isDisabled ? 'disabled' : '';
                    $badgeClass = $isActive ? 'bg-success' : 'bg-danger';
                    $badgeText = $isActive ? 'Active' : 'Inactive';

                    $tooltipAttr = '';
                    if ($isAdmin) {
                        $tooltipAttr = 'data-bs-toggle="tooltip" title="Admin users cannot be activated or deactivated"';
                    } elseif (! $canEdit) {
                        $tooltipAttr = 'data-bs-toggle="tooltip" title="Permission required to change status"';
                    }

                    return sprintf(
                        '<div class="d-flex align-items-center gap-2" %s>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input user-status-toggle" 
                                       type="checkbox" 
                                       role="switch" 
                                       id="userStatus_%s" 
                                       data-id="%s" 
                                       data-name="%s" 
                                       data-url="%s" 
                                       %s %s>
                            </div>
                            <span class="badge %s user-status-badge-%s">%s</span>
                        </div>',
                        $tooltipAttr,
                        $user->id,
                        $user->id,
                        e($user->name),
                        route('master.user.toggle-status', $user->id),
                        $checkedAttr,
                        $disabledAttr,
                        $badgeClass,
                        $user->id,
                        $badgeText
                    );
                })
                ->filterColumn('status', function ($query, $keyword) {
                    if (stripos($keyword, 'act') !== false && stripos($keyword, 'inact') === false) {
                        $query->where('is_active', '1');
                    } elseif (stripos($keyword, 'inact') !== false) {
                        $query->where('is_active', '0');
                    }
                })
                ->editColumn('created_at', function ($user) {
                    return $user->created_at ? $user->created_at->format('d M Y, h:i A') : '-';
                })
                ->editColumn('mobile_number', function ($user) {
                    return $user->mobile_number ?: '-';
                })
                ->addColumn('action', function ($user) {
                    $actions = '<div class="d-flex justify-content-center align-items-center gap-1">';

                    if (auth()->user()?->can('user-view')) {
                        $actions .= sprintf(
                            '<button type="button" class="btn btn-sm btn-info btn-view-user" data-id="%s" data-bs-toggle="tooltip" title="View User"><i class="uil uil-eye"></i></button>',
                            $user->id
                        );
                    }

                    if (auth()->user()?->can('user-edit')) {
                        $editUrl = route('master.user.edit', $user->id);
                        $actions .= sprintf(
                            '<a href="%s" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Edit User"><i class="uil uil-edit"></i></a>',
                            $editUrl
                        );
                    }

                    if (auth()->user()?->can('user-password-change')) {
                        $actions .= sprintf(
                            '<button type="button" class="btn btn-sm btn-dark btn-change-password" data-id="%s" data-name="%s" data-email="%s" data-url="%s" data-bs-toggle="tooltip" title="Change Password"><i class="uil uil-key-skeleton"></i></button>',
                            $user->id,
                            e($user->name),
                            e($user->email),
                            route('master.user.change-password', $user->id)
                        );
                    }

                    $isAdmin = method_exists($user, 'isAdmin') ? $user->isAdmin() : false;

                    if (auth()->user()?->can('user-delete') && ! $isAdmin) {
                        $deleteUrl = route('master.user.destroy', $user->id);

                        $actions .= sprintf(
                            '<button type="button" class="btn btn-sm btn-danger btn-delete-user" data-id="%s" data-url="%s" data-name="%s" data-bs-toggle="tooltip" title="Delete User"><i class="uil uil-trash-alt"></i></button>',
                            $user->id,
                            $deleteUrl,
                            e($user->name)
                        );
                    }

                    $actions .= '</div>';

                    return $actions;
                })
                ->rawColumns(['role', 'status', 'action'])
                ->make(true);
        }

        return view('rbac-suite::master.user.index');
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $userClass = $this->getUserClass();
        $user = new $userClass;
        $user->is_active = '1';
        $roles = Role::orderBy('name')->get();
        $isEdit = false;
        $selectedRole = '';

        return view('rbac-suite::master.user.create', compact('user', 'roles', 'isEdit', 'selectedRole'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $userClass = $this->getUserClass();
        $userTable = (new $userClass)->getTable();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique($userTable, 'email'),
            ],
            'mobile_number' => [
                'nullable',
                'string',
                'regex:/^(?:\+91)?[6-9]\d{9}$/',
            ],
            'password' => PasswordRule::getValidationRules(true),
            'role' => ['required', 'string', 'exists:roles,name'],
            'is_active' => ['nullable', 'in:0,1'],
            'address' => ['nullable', 'string', 'max:500'],
        ], [
            'email.unique' => 'This email address is already registered.',
            'mobile_number.regex' => 'Please enter a valid 10-digit mobile number.',
            'role.required' => 'Please select a role.',
            'role.exists' => 'The selected role is invalid.',
            'is_active.in' => 'The active status must be Active or Inactive.',
        ]);

        DB::beginTransaction();

        try {
            $user = $userClass::create([
                'name' => trim((string) $request->input('name')),
                'email' => strtolower(trim((string) $request->input('email'))),
                'mobile_number' => $request->filled('mobile_number') ? trim((string) $request->input('mobile_number')) : null,
                'password' => Hash::make((string) $request->input('password')),
                'is_active' => $request->filled('is_active') ? (string) $request->input('is_active') : '1',
                'address' => $request->filled('address') ? trim((string) $request->input('address')) : null,
            ]);

            $user->assignRole($request->input('role'));

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User created successfully.',
                    'user' => $user,
                ], 201);
            }

            return redirect()
                ->route('master.user.index')
                ->with('success', 'User created successfully.');
        } catch (Exception $e) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create user: '.$e->getMessage(),
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'Failed to create user: '.$e->getMessage());
        }
    }

    /**
     * Display the specified user (returns JSON for view modal or View response).
     */
    public function show(Request $request, string|int $id): JsonResponse|View
    {
        $userId = $this->resolveEncryptedId($id, 'user');
        $userClass = $this->getUserClass();

        $query = $userClass::query();
        if (method_exists($userClass, 'withTrashed')) {
            $query->withTrashed();
        }

        $user = $query->with(['roles', 'creator', 'updater', 'passwordUpdater'])
            ->findOrFail($userId);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile_number' => $user->mobile_number ?: '-',
                    'address' => $user->address ?: '-',
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'is_active' => (string) $user->is_active,
                    'status' => (method_exists($user, 'trashed') && $user->trashed()) ? 'Deleted' : ((string) $user->is_active === '1' ? 'Active' : 'Inactive'),
                    'created_at' => $user->created_at ? $user->created_at->format('d M Y, h:i A') : '-',
                    'updated_at' => $user->updated_at ? $user->updated_at->format('d M Y, h:i A') : '-',
                    'created_by' => $user->creator ? $user->creator->name : '-',
                    'updated_by' => $user->updater ? $user->updater->name : '-',
                    'password_updated_at' => $user->password_updated_at ? $user->password_updated_at->format('d M Y, h:i A') : '-',
                    'password_updated_by' => $user->passwordUpdater ? $user->passwordUpdater->name : '-',
                ],
            ]);
        }

        return view('rbac-suite::master.user.create', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
            'isEdit' => true,
            'selectedRole' => $user->roles->first()?->name ?? '',
            'isReadOnly' => true,
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(string|int $id): View
    {
        $userId = $this->resolveEncryptedId($id, 'user');
        $userClass = $this->getUserClass();
        $user = $userClass::with('roles')->findOrFail($userId);
        $roles = Role::orderBy('name')->get();
        $isEdit = true;
        $selectedRole = $user->roles->first()?->name ?? '';

        return view('rbac-suite::master.user.create', compact('user', 'roles', 'isEdit', 'selectedRole'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, string|int $id): RedirectResponse|JsonResponse
    {
        $userId = $this->resolveEncryptedId($id, 'user');
        $userClass = $this->getUserClass();
        $user = $userClass::with('roles')->findOrFail($userId);
        $userTable = $user->getTable();
        $isTargetAdmin = method_exists($user, 'isAdmin') ? $user->isAdmin() : false;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique($userTable, 'email')->ignore($user->id),
            ],
            'mobile_number' => [
                'nullable',
                'string',
                'regex:/^(?:\+91)?[6-9]\d{9}$/',
            ],
            'is_active' => ['nullable', 'in:0,1'],
            'address' => ['nullable', 'string', 'max:500'],
        ];

        // Non-admin users require role validation
        if (! $isTargetAdmin) {
            $rules['role'] = ['required', 'string', 'exists:roles,name'];
        }

        if ($request->filled('password')) {
            $rules['password'] = [
                'required',
                'string',
                'confirmed',
                new ConfiguredPasswordRule,
            ];
        }

        $request->validate($rules, [
            'email.unique' => 'This email address is already registered.',
            'mobile_number.regex' => 'Please enter a valid 10-digit mobile number.',
            'role.required' => 'Please select a role.',
            'role.exists' => 'The selected role is invalid.',
            'is_active.in' => 'The active status must be Active or Inactive.',
        ]);

        DB::beginTransaction();

        try {
            $user->name = trim((string) $request->input('name'));
            $user->email = strtolower(trim((string) $request->input('email')));
            $user->mobile_number = $request->filled('mobile_number') ? trim((string) $request->input('mobile_number')) : null;
            $user->address = $request->filled('address') ? trim((string) $request->input('address')) : null;

            if (! $isTargetAdmin && $request->has('is_active')) {
                $user->is_active = (string) $request->input('is_active');
            }

            if ($request->filled('password')) {
                $user->password = Hash::make((string) $request->input('password'));
            }

            $user->save();

            // Only sync roles for non-admin users (preserve Admin role)
            if (! $isTargetAdmin && $request->filled('role')) {
                $user->syncRoles([$request->input('role')]);
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User updated successfully.',
                    'user' => $user,
                ]);
            }

            return redirect()
                ->route('master.user.index')
                ->with('success', 'User updated successfully.');
        } catch (Exception $e) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update user: '.$e->getMessage(),
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'Failed to update user: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified user from storage (Soft Delete).
     */
    public function destroy(Request $request, string|int $id): RedirectResponse|JsonResponse
    {
        $userId = $this->resolveEncryptedId($id, 'user');
        $userClass = $this->getUserClass();
        $user = $userClass::findOrFail($userId);

        if (auth()->check() && (int) auth()->id() === (int) $user->id) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account.',
                ], 422);
            }

            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.',
            ]);
        }

        return redirect()
            ->route('master.user.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(Request $request, string|int $id): RedirectResponse|JsonResponse
    {
        $userId = $this->resolveEncryptedId($id, 'user');
        $userClass = $this->getUserClass();

        if (! method_exists($userClass, 'onlyTrashed')) {
            abort(404, 'Restore not supported on this model.');
        }

        $user = $userClass::onlyTrashed()->findOrFail($userId);

        // Check if an active user already has this email
        if ($userClass::where('email', $user->email)->where('id', '!=', $user->id)->exists()) {
            $msg = 'Cannot restore user: email address is already in use by an active user.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        $user->restore();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User restored successfully.',
            ]);
        }

        return redirect()
            ->route('master.user.index')
            ->with('success', 'User restored successfully.');
    }

    /**
     * Change a user's password (Administrator action via sidebar/offcanvas).
     */
    public function changePassword(Request $request, string|int $id): JsonResponse
    {
        $userId = $this->resolveEncryptedId($id, 'user');
        $userClass = $this->getUserClass();
        $user = $userClass::findOrFail($userId);

        $request->validate([
            'password' => PasswordRule::getValidationRules(true),
        ], [
            'password.required' => 'The new password field is required.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        DB::beginTransaction();

        try {
            $user->password = Hash::make((string) $request->input('password'));
            $user->password_updated_at = now();
            $user->password_updated_id = auth()->id();
            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Password for user "'.$user->name.'" updated successfully.',
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update password: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle a user's active/inactive status.
     */
    public function toggleStatus(Request $request, string|int $id): JsonResponse
    {
        $userId = $this->resolveEncryptedId($id, 'user');
        $userClass = $this->getUserClass();
        $user = $userClass::findOrFail($userId);

        if (! auth()->user()?->can('user-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }

        // Admin role restriction: Admin users cannot be activated or deactivated
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin users cannot be activated or deactivated.',
            ], 403);
        }

        // Normalize boolean/numeric inputs to string '0' or '1'
        $request->merge([
            'is_active' => match ($request->input('is_active')) {
                true, 1, '1' => '1',
                false, 0, '0' => '0',
                default => $request->input('is_active'),
            },
        ]);

        $validated = $request->validate([
            'is_active' => ['required', 'in:0,1'],
        ]);

        $newStatus = (string) $validated['is_active'];
        $user->is_active = $newStatus;
        $user->updated_id = auth()->id();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $newStatus === '1'
                ? 'User "'.$user->name.'" activated successfully.'
                : 'User "'.$user->name.'" deactivated successfully.',
            'is_active' => $newStatus,
            'status_label' => $newStatus === '1' ? 'Active' : 'Inactive',
            'user_id' => $user->id,
        ]);
    }
}
