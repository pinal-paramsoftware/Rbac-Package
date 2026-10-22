<?php

namespace Parth\LaravelRbacSuite\Tests;

use Composer\Autoload\ClassLoader;
use Illuminate\Support\Facades\Schema;
use Parth\LaravelRbacSuite\Models\AuthSetting;
use Parth\LaravelRbacSuite\RbacSuiteServiceProvider;
use Parth\LaravelRbacSuite\Tests\Fixtures\User;
use PragmaRX\Google2FALaravel\ServiceProvider as Google2FAServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use Yajra\DataTables\DataTablesServiceProvider;

if (! class_exists(RbacSuiteServiceProvider::class)) {
    $loader = new ClassLoader;
    $loader->addPsr4('Parth\\LaravelRbacSuite\\', dirname(__DIR__).'/src/');
    $loader->addPsr4('Parth\\LaravelRbacSuite\\Database\\Seeders\\', dirname(__DIR__).'/database/seeders/');
    $loader->addPsr4('Parth\\LaravelRbacSuite\\Tests\\', __DIR__.'/');
    $loader->register();
} else {
    $loader = new ClassLoader;
    $loader->addPsr4('Parth\\LaravelRbacSuite\\Database\\Seeders\\', dirname(__DIR__).'/database/seeders/');
    $loader->register();
}

if (file_exists(dirname(__DIR__).'/src/helpers.php')) {
    require_once dirname(__DIR__).'/src/helpers.php';
}

if (file_exists(__DIR__.'/fixtures/User.php')) {
    require_once __DIR__.'/fixtures/User.php';
} elseif (file_exists(__DIR__.'/Fixtures/User.php')) {
    require_once __DIR__.'/Fixtures/User.php';
}

if (! class_exists('App\Models\User') && class_exists(User::class)) {
    class_alias(User::class, 'App\Models\User');
}

if (class_exists(\Tests\TestCase::class)) {
    class_alias(\Tests\TestCase::class, 'Parth\LaravelRbacSuite\Tests\PackageBaseTestCase');
} elseif (class_exists(\Orchestra\Testbench\TestCase::class)) {
    class_alias(\Orchestra\Testbench\TestCase::class, 'Parth\LaravelRbacSuite\Tests\PackageBaseTestCase');
} else {
    class_alias(\Illuminate\Foundation\Testing\TestCase::class, 'Parth\LaravelRbacSuite\Tests\PackageBaseTestCase');
}

abstract class TestCase extends PackageBaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (isset($this->app) && ! $this->app->providerIsLoaded(RbacSuiteServiceProvider::class)) {
            $this->app->register(RbacSuiteServiceProvider::class);
        }

        $this->setUpDatabase();
    }

    /**
     * Set up baseline database schema for standalone Testbench environment.
     */
    protected function setUpDatabase(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function ($table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        $migrationFiles = [
            '2026_01_01_000001_create_rbac_permissions_tables.php',
            '2026_01_01_000002_add_rbac_columns_to_users_table.php',
            '2026_01_01_000003_create_rbac_authentication_settings_table.php',
            '2026_01_01_000004_create_rbac_password_rules_table.php',
            '2026_01_01_000005_create_rbac_auth_provider_settings_table.php',
            '2026_01_01_000006_create_rbac_email_login_otp_table.php',
            '2026_01_01_000007_create_rbac_user_two_factor_table.php',
            '2026_01_01_000008_create_rbac_user_devices_table.php',
        ];

        foreach ($migrationFiles as $file) {
            $path = dirname(__DIR__).'/database/migrations/'.$file;
            if (file_exists($path)) {
                $migration = require $path;
                $migration->up();
            }
        }
    }

    /**
     * Define package service providers for Orchestra Testbench.
     */
    protected function getPackageProviders($app): array
    {
        $providers = [
            RbacSuiteServiceProvider::class,
        ];

        if (class_exists(PermissionServiceProvider::class)) {
            $providers[] = PermissionServiceProvider::class;
        }

        if (class_exists(Google2FAServiceProvider::class)) {
            $providers[] = Google2FAServiceProvider::class;
        }

        if (class_exists(DataTablesServiceProvider::class)) {
            $providers[] = DataTablesServiceProvider::class;
        }

        return $providers;
    }

    /**
     * Define environment setup for Orchestra Testbench.
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:yk3+7Wbda58stg1z77m1m7/u59/G6214G03/Z4/V+1U=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => 'App\\Models\\User',
        ]);
        $app['config']->set('auth.passwords.users', [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ]);

        $app['config']->set('rbac-suite.user_model', 'App\\Models\\User');
    }

    protected function tearDown(): void
    {
        if (class_exists(AuthSetting::class)) {
            AuthSetting::clearCache();
        }

        parent::tearDown();
    }
}
