<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | Define the Eloquent model representing users in the host application.
    | The model may use the `Parth\LaravelRbacSuite\Traits\HasRbacUser` trait
    | or Spatie's `Spatie\Permission\Traits\HasRoles` trait.
    |
    */
    'user_model' => env('RBAC_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Authentication Guard
    |--------------------------------------------------------------------------
    |
    | The default authentication guard used for access control and sessions.
    |
    */
    'guard' => env('RBAC_GUARD', 'web'),

    /*
    |--------------------------------------------------------------------------
    | Default Roles & Administrator Designation
    |--------------------------------------------------------------------------
    |
    | Configure the role names used for system administrators and standard users.
    | The admin role is protected from accidental deletion.
    |
    */
    'roles' => [
        'admin_name' => 'Admin',
        'default_user_role' => 'User',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing Configuration
    |--------------------------------------------------------------------------
    |
    | Controls whether package routes are registered, their URL prefix,
    | route name prefix, middleware stack, and custom route file.
    | If routes/rbac.php is published, it takes precedence automatically.
    |
    */
    'routes' => [
        'enabled' => env('RBAC_ROUTES_ENABLED', true),
        'file' => env('RBAC_ROUTES_FILE', null),
        'prefix' => env('RBAC_ROUTE_PREFIX', ''),
        'name_prefix' => env('RBAC_ROUTE_NAME_PREFIX', ''),
        'middleware' => ['web'],
        'auth_middleware' => ['web', 'auth'],
        'redirect_root' => env('RBAC_REDIRECT_ROOT', true),
        'dashboard_route' => env('RBAC_DASHBOARD_ROUTE', 'dashboard'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Customizable Application Controllers
    |--------------------------------------------------------------------------
    |
    | Map package controllers to custom application-owned controllers.
    | When published via `vendor:publish --tag=rbac-suite-controllers`, classes
    | in App\Http\Controllers\Rbac will automatically be detected and executed.
    | You can also define explicit class overrides here.
    |
    */
    'controllers' => [
        // 'DashboardController' => App\Http\Controllers\Rbac\DashboardController::class,
        // 'LoginController' => App\Http\Controllers\Rbac\Auth\LoginController::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Customizable Application Models
    |--------------------------------------------------------------------------
    |
    | Map package models to custom application-owned models.
    | When published via `vendor:publish --tag=rbac-suite-models`, classes
    | in App\Models\Rbac will automatically be detected and utilized.
    |
    */
    'models' => [
        // 'role' => App\Models\Rbac\Role::class,
        // 'permission' => App\Models\Rbac\Permission::class,
        // 'auth_setting' => App\Models\Rbac\AuthSetting::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Master Layout Inheritance
    |--------------------------------------------------------------------------
    |
    | Package views will extend this master layout. By default, it uses the
    | package's included Minia/Bootstrap 5 admin shell. You can point this
    | to your host application's layout (e.g. 'layouts.app' or 'layouts.master').
    |
    */
    'layout' => env('RBAC_LAYOUT', 'rbac-suite::layouts.master'),

    /*
    |--------------------------------------------------------------------------
    | Application Branding
    |--------------------------------------------------------------------------
    |
    | Brand title and logos displayed in the topbar, sidebar, and auth screens.
    |
    */
    'brand' => [
        'name' => env('APP_NAME', 'RBAC Suite'),
        'logo_sm' => '/vendor/rbac-suite/images/logo-sm.png',
        'logo_dark' => '/vendor/rbac-suite/images/logo-dark.png',
        'logo_light' => '/vendor/rbac-suite/images/logo-light.png',
    ],

    /*
    |--------------------------------------------------------------------------
    | Public Assets Prefix
    |--------------------------------------------------------------------------
    |
    | Default prefix used by rbac_asset() helper when serving assets.
    |
    */
    'assets_prefix' => env('RBAC_ASSETS_PREFIX', 'vendor/rbac-suite'),

    /*
    |--------------------------------------------------------------------------
    | Database Migrations
    |--------------------------------------------------------------------------
    |
    | When enabled, package database migrations will be automatically loaded
    | by the service provider unless already published to database/migrations.
    | Set to false if you manage migrations exclusively in the host application.
    |
    */
    'load_migrations' => env('RBAC_LOAD_MIGRATIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Database Table Overrides
    |--------------------------------------------------------------------------
    |
    | Custom table names if the host application has specific naming policies.
    |
    */
    'tables' => [
        'users' => 'users',
        'authentication_settings' => 'authentication_settings',
        'auth_provider_settings' => 'auth_provider_settings',
        'password_rules' => 'password_rules',
        'email_login_otp' => 'email_login_otp',
        'user_two_factor' => 'user_two_factor',
        'user_devices' => 'user_devices',
    ],

];
