<?php

namespace Parth\LaravelRbacSuite\Support;

use Parth\LaravelRbacSuite\Http\Controllers\Auth\ConfirmPasswordController;
use Parth\LaravelRbacSuite\Http\Controllers\Auth\ForgotPasswordController;
use Parth\LaravelRbacSuite\Http\Controllers\Auth\LoginController;
use Parth\LaravelRbacSuite\Http\Controllers\Auth\RegisterController;
use Parth\LaravelRbacSuite\Http\Controllers\Auth\ResetPasswordController;
use Parth\LaravelRbacSuite\Http\Controllers\Auth\VerificationController;
use Parth\LaravelRbacSuite\Http\Controllers\AuthSettingController;
use Parth\LaravelRbacSuite\Http\Controllers\DashboardController;
use Parth\LaravelRbacSuite\Http\Controllers\PermissionController;
use Parth\LaravelRbacSuite\Http\Controllers\RoleController;
use Parth\LaravelRbacSuite\Http\Controllers\RootRedirectController;
use Parth\LaravelRbacSuite\Http\Controllers\UserController;

class RbacControllerResolver
{
    /**
     * Map of controller aliases / short names to package classes.
     *
     * @var array<string, string>
     */
    protected static array $packageClasses = [
        'AuthSettingController' => AuthSettingController::class,
        'DashboardController' => DashboardController::class,
        'PermissionController' => PermissionController::class,
        'RoleController' => RoleController::class,
        'RootRedirectController' => RootRedirectController::class,
        'UserController' => UserController::class,
        'ConfirmPasswordController' => ConfirmPasswordController::class,
        'ForgotPasswordController' => ForgotPasswordController::class,
        'LoginController' => LoginController::class,
        'RegisterController' => RegisterController::class,
        'ResetPasswordController' => ResetPasswordController::class,
        'VerificationController' => VerificationController::class,
    ];

    /**
     * Resolve the active controller class name (custom application override or package default).
     *
     * Resolution order:
     * 1. Explicit configuration mapping in `config('rbac-suite.controllers')`
     * 2. Published controller in `App\Http\Controllers\Rbac\...`
     * 3. Package fallback controller
     */
    public static function resolve(string $controllerClassOrAlias): string
    {
        $packageClass = static::$packageClasses[$controllerClassOrAlias] ?? $controllerClassOrAlias;
        $shortName = class_basename($packageClass);

        // 1. Explicit config override (by full class name, alias, or short name)
        $configured = config("rbac-suite.controllers.{$packageClass}")
            ?? config("rbac-suite.controllers.{$shortName}")
            ?? config("rbac-suite.controllers.{$controllerClassOrAlias}");

        if (! empty($configured) && class_exists($configured)) {
            return $configured;
        }

        // 2. Published application controller under App\Http\Controllers\Rbac\...
        $prefix = 'Parth\\LaravelRbacSuite\\Http\\Controllers\\';
        if (str_starts_with($packageClass, $prefix)) {
            $relative = substr($packageClass, strlen($prefix));
            $appClass = 'App\\Http\\Controllers\\Rbac\\'.$relative;

            if (class_exists($appClass)) {
                return $appClass;
            }
        }

        // Direct App\Http\Controllers\Rbac\{ShortName} check
        $directAppClass = 'App\\Http\\Controllers\\Rbac\\'.$shortName;
        if (class_exists($directAppClass)) {
            return $directAppClass;
        }

        // 3. Fallback to package vendor controller
        return $packageClass;
    }

    /**
     * Get all mapped controllers with their active implementations.
     *
     * @return array<string, string>
     */
    public static function getActiveMap(): array
    {
        $map = [];
        foreach (static::$packageClasses as $alias => $packageClass) {
            $map[$packageClass] = static::resolve($packageClass);
        }

        return $map;
    }
}
