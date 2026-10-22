<?php

namespace Parth\LaravelRbacSuite\Support;

use Parth\LaravelRbacSuite\Models\AuthProviderSetting;
use Parth\LaravelRbacSuite\Models\AuthSetting;
use Parth\LaravelRbacSuite\Models\EmailLoginOtp;
use Parth\LaravelRbacSuite\Models\PasswordRule;
use Parth\LaravelRbacSuite\Models\Permission;
use Parth\LaravelRbacSuite\Models\Role;
use Parth\LaravelRbacSuite\Models\UserDevice;
use Parth\LaravelRbacSuite\Models\UserTwoFactor;

class RbacModelResolver
{
    /**
     * Map of model keys to package classes.
     *
     * @var array<string, string>
     */
    protected static array $models = [
        'role' => Role::class,
        'permission' => Permission::class,
        'auth_setting' => AuthSetting::class,
        'auth_provider_setting' => AuthProviderSetting::class,
        'password_rule' => PasswordRule::class,
        'email_login_otp' => EmailLoginOtp::class,
        'user_two_factor' => UserTwoFactor::class,
        'user_device' => UserDevice::class,
    ];

    /**
     * Resolve the active model class name (custom application override or package default).
     *
     * Resolution order:
     * 1. Explicit configuration mapping in `config('rbac-suite.models')`
     * 2. Published model in `App\Models\Rbac\...`
     * 3. Package fallback model
     */
    public static function resolve(string $keyOrClass): string
    {
        $packageClass = static::$models[$keyOrClass] ?? $keyOrClass;
        $shortName = class_basename($packageClass);

        // 1. Explicit config override
        $configured = config("rbac-suite.models.{$keyOrClass}")
            ?? config("rbac-suite.models.{$shortName}")
            ?? config("rbac-suite.models.{$packageClass}");

        if (! empty($configured) && class_exists($configured)) {
            return $configured;
        }

        // 2. Published application model under App\Models\Rbac\...
        $appClass = 'App\\Models\\Rbac\\'.$shortName;
        if (class_exists($appClass)) {
            return $appClass;
        }

        // 3. Fallback to package vendor model
        return $packageClass;
    }

    /**
     * Get all mapped models with their active implementations.
     *
     * @return array<string, string>
     */
    public static function getActiveMap(): array
    {
        $map = [];
        foreach (static::$models as $key => $packageClass) {
            $map[$packageClass] = static::resolve($key);
        }

        return $map;
    }
}
