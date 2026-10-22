<?php

namespace Parth\LaravelRbacSuite\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string userModel()
 * @method static \Illuminate\Database\Eloquent\Model newUserModel()
 * @method static string guard()
 * @method static string adminRoleName()
 * @method static bool isAdmin(?\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static string layout()
 * @method static string getLayout()
 *
 * @see \Parth\LaravelRbacSuite\RbacSuite
 */
class RbacSuite extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'rbac-suite';
    }
}
