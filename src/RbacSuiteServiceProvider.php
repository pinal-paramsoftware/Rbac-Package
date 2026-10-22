<?php

namespace Parth\LaravelRbacSuite;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Parth\LaravelRbacSuite\Console\Commands\InstallCommand;
use Parth\LaravelRbacSuite\Models\Permission;
use Parth\LaravelRbacSuite\Models\Role;
use Parth\LaravelRbacSuite\Support\RbacControllerResolver;
use Parth\LaravelRbacSuite\Support\RbacModelResolver;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

class RbacSuiteServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        if (file_exists(__DIR__.'/helpers.php')) {
            require_once __DIR__.'/helpers.php';
        }

        $this->mergeConfigFrom(
            __DIR__.'/../config/rbac-suite.php',
            'rbac-suite'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../config/auth-pipeline.php',
            'auth-pipeline'
        );

        $this->app->singleton('rbac-suite', function () {
            return new RbacSuite;
        });

        $this->app->alias('rbac-suite', RbacSuite::class);

        $this->registerControllerBindings();
        $this->registerModelBindings();

        $this->app->booting(function () {
            $this->registerRoutes();
        });
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerResources();
        $this->registerMiddleware();
        $this->registerCommands();
        $this->registerBladeDirectives();
    }

    /**
     * Bind application-owned controller overrides into the service container.
     */
    protected function registerControllerBindings(): void
    {
        foreach (RbacControllerResolver::getActiveMap() as $packageClass => $activeClass) {
            if ($activeClass !== $packageClass) {
                $this->app->bind($packageClass, $activeClass);
            }
        }
    }

    /**
     * Bind application-owned model overrides into the service container.
     */
    protected function registerModelBindings(): void
    {
        foreach (RbacModelResolver::getActiveMap() as $packageClass => $activeClass) {
            if ($activeClass !== $packageClass) {
                $this->app->bind($packageClass, $activeClass);
            }
        }

        // Keep Spatie Permission model configurations synchronized
        $activeRoleModel = RbacModelResolver::resolve('role');
        if ($activeRoleModel !== Role::class && ! config()->has('permission.models.role')) {
            config(['permission.models.role' => $activeRoleModel]);
        }

        $activePermissionModel = RbacModelResolver::resolve('permission');
        if ($activePermissionModel !== Permission::class && ! config()->has('permission.models.permission')) {
            config(['permission.models.permission' => $activePermissionModel]);
        }
    }

    /**
     * Register package and Spatie middleware aliases.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        if (class_exists(RoleMiddleware::class)) {
            $router->aliasMiddleware('role', RoleMiddleware::class);
        }

        if (class_exists(PermissionMiddleware::class)) {
            $router->aliasMiddleware('permission', PermissionMiddleware::class);
        }

        if (class_exists(RoleOrPermissionMiddleware::class)) {
            $router->aliasMiddleware('role_or_permission', RoleOrPermissionMiddleware::class);
        }
    }

    /**
     * Register package Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        Blade::directive('rbacAsset', function ($expression) {
            return "<?php echo \\Parth\\LaravelRbacSuite\\Support\\RbacAsset::url({$expression}); ?>";
        });
    }

    /**
     * Register package publishing options.
     */
    protected function registerPublishing(): void
    {
        // 1. Publish Configurations
        $this->publishes([
            __DIR__.'/../config/rbac-suite.php' => config_path('rbac-suite.php'),
            __DIR__.'/../config/auth-pipeline.php' => config_path('auth-pipeline.php'),
        ], 'rbac-suite-config');

        // 2. Publish Controllers
        $this->publishes([
            __DIR__.'/../stubs/controllers' => app_path('Http/Controllers/Rbac'),
        ], 'rbac-suite-controllers');

        // 3. Publish Models
        $this->publishes([
            __DIR__.'/../stubs/models' => app_path('Models/Rbac'),
        ], 'rbac-suite-models');

        // 4. Publish Views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/rbac-suite'),
        ], 'rbac-suite-views');

        // 5. Publish Dedicated Route File
        $this->publishes([
            __DIR__.'/../routes/rbac.php' => base_path('routes/rbac.php'),
        ], 'rbac-suite-routes');

        // 6. Publish Migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'rbac-suite-migrations');

        // 7. Publish Seeders
        $this->publishes([
            __DIR__.'/../stubs/seeders' => database_path('seeders'),
        ], 'rbac-suite-seeders');

        // 8. Publish Assets
        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/rbac-suite'),
        ], 'rbac-suite-assets');

        // 9. Core Resources Bundle (Config, Routes, Views, Assets)
        $this->publishes([
            __DIR__.'/../config/rbac-suite.php' => config_path('rbac-suite.php'),
            __DIR__.'/../config/auth-pipeline.php' => config_path('auth-pipeline.php'),
            __DIR__.'/../routes/rbac.php' => base_path('routes/rbac.php'),
            __DIR__.'/../resources/views' => resource_path('views/vendor/rbac-suite'),
            __DIR__.'/../resources/assets' => public_path('vendor/rbac-suite'),
        ], 'rbac-suite');

        // 10. Complete Supported Set (All 8 Subsets)
        $this->publishes([
            __DIR__.'/../config/rbac-suite.php' => config_path('rbac-suite.php'),
            __DIR__.'/../config/auth-pipeline.php' => config_path('auth-pipeline.php'),
            __DIR__.'/../stubs/controllers' => app_path('Http/Controllers/Rbac'),
            __DIR__.'/../stubs/models' => app_path('Models/Rbac'),
            __DIR__.'/../routes/rbac.php' => base_path('routes/rbac.php'),
            __DIR__.'/../resources/views' => resource_path('views/vendor/rbac-suite'),
            __DIR__.'/../resources/assets' => public_path('vendor/rbac-suite'),
            __DIR__.'/../database/migrations' => database_path('migrations'),
            __DIR__.'/../stubs/seeders' => database_path('seeders'),
        ], 'rbac-suite-all');
    }

    /**
     * Register package resources (views, migrations).
     */
    protected function registerResources(): void
    {
        // Load Namespaced Views (published views take precedence automatically)
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'rbac-suite');

        // Layout fallback validation
        $configuredLayout = config('rbac-suite.layout');
        if (! empty($configuredLayout) && ! $this->app['view']->exists($configuredLayout)) {
            config(['rbac-suite.layout' => 'rbac-suite::layouts.master']);
        }

        // Automatically load package migrations if running in console, enabled,
        // and migrations have not already been published to database/migrations
        if ($this->app->runningInConsole() && config('rbac-suite.load_migrations', true)) {
            $publishedMigration = database_path('migrations/2026_01_01_000001_create_rbac_permissions_tables.php');
            if (! file_exists($publishedMigration)) {
                $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
            }
        }
    }

    /**
     * Register package routes.
     *
     * Custom route priority:
     * 1. Explicit file configured in config('rbac-suite.routes.file')
     * 2. Published dedicated route file at routes/rbac.php
     * 3. Package default routes/rbac.php
     * 4. Package fallback routes/web.php
     */
    protected function registerRoutes(): void
    {
        if (! config('rbac-suite.routes.enabled', true)) {
            return;
        }

        $configuredFile = config('rbac-suite.routes.file');
        $publishedFile = base_path('routes/rbac.php');
        $packageFile = __DIR__.'/../routes/rbac.php';

        if (! empty($configuredFile) && file_exists($configuredFile)) {
            $routesFile = $configuredFile;
        } elseif (file_exists($publishedFile)) {
            $routesFile = $publishedFile;
        } elseif (file_exists($packageFile)) {
            $routesFile = $packageFile;
        } else {
            $routesFile = __DIR__.'/../routes/web.php';
        }

        if (! file_exists($routesFile)) {
            return;
        }

        Route::group($this->routeConfiguration(), function () use ($routesFile) {
            $this->loadRoutesFrom($routesFile);
        });
    }

    /**
     * Get the route group configuration array.
     *
     * @return array<string, mixed>
     */
    protected function routeConfiguration(): array
    {
        return [
            'prefix' => config('rbac-suite.routes.prefix', ''),
            'middleware' => config('rbac-suite.routes.middleware', ['web']),
            'as' => config('rbac-suite.routes.name_prefix', ''),
        ];
    }

    /**
     * Register Artisan commands if running in console.
     */
    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
        ]);
    }
}
