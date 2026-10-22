<?php

namespace Parth\LaravelRbacSuite;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class RbacSuite
{
    /**
     * Package version.
     */
    public const VERSION = '1.0.5';

    /**
     * Resolve the active controller class.
     */
    public function controller(string $class): string
    {
        return Support\RbacControllerResolver::resolve($class);
    }

    /**
     * Resolve the active model class.
     */
    public function model(string $key): string
    {
        return Support\RbacModelResolver::resolve($key);
    }

    /**
     * Get the configured user model class name.
     */
    public function userModel(): string
    {
        return config('rbac-suite.user_model', 'App\\Models\\User');
    }

    /**
     * Create a new instance of the configured user model.
     */
    public function newUserModel(): Model
    {
        $class = $this->userModel();

        return new $class;
    }

    /**
     * Get the configured authentication guard.
     */
    public function guard(): string
    {
        return (string) config('rbac-suite.guard', 'web');
    }

    /**
     * Get the admin role name.
     */
    public function adminRoleName(): string
    {
        return (string) config('rbac-suite.roles.admin_name', 'Admin');
    }

    /**
     * Determine if a user is an administrator.
     */
    public function isAdmin(?Authenticatable $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (method_exists($user, 'isAdmin')) {
            return (bool) $user->isAdmin();
        }

        if (method_exists($user, 'hasRole')) {
            return (bool) $user->hasRole($this->adminRoleName());
        }

        return false;
    }

    /**
     * Get the configured master layout view string with safe fallback.
     */
    public function layout(): string
    {
        $layout = (string) config('rbac-suite.layout', 'rbac-suite::layouts.master');

        if (! empty($layout) && app('view')->exists($layout)) {
            return $layout;
        }

        return 'rbac-suite::layouts.master';
    }

    /**
     * Static accessor for layout string resolution.
     */
    public static function getLayout(): string
    {
        $layout = (string) config('rbac-suite.layout', 'rbac-suite::layouts.master');

        if (! empty($layout) && app('view')->exists($layout)) {
            return $layout;
        }

        return 'rbac-suite::layouts.master';
    }
}
