<?php

namespace Parth\LaravelRbacSuite\Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Parth\LaravelRbacSuite\Database\Seeders\DefaultAdminUserSeeder;
use Parth\LaravelRbacSuite\Database\Seeders\RbacSuiteDatabaseSeeder;
use Parth\LaravelRbacSuite\Database\Seeders\RolePermissionSeeder;
use Parth\LaravelRbacSuite\Http\Controllers\Auth\LoginController;
use Parth\LaravelRbacSuite\Http\Controllers\DashboardController;
use Parth\LaravelRbacSuite\Models\AuthSetting;
use Parth\LaravelRbacSuite\Models\Role;
use Parth\LaravelRbacSuite\RbacSuiteServiceProvider;
use Parth\LaravelRbacSuite\Support\RbacControllerResolver;
use Parth\LaravelRbacSuite\Support\RbacModelResolver;
use Parth\LaravelRbacSuite\Support\RbacViewResolver;
use Parth\LaravelRbacSuite\Tests\TestCase;

class PublishingAndCustomizationTest extends TestCase
{
    use RefreshDatabase;

    protected string $tempTestDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempTestDir = sys_get_temp_dir().'/rbac_publish_test_'.uniqid();
        File::makeDirectory($this->tempTestDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->tempTestDir)) {
            File::deleteDirectory($this->tempTestDir);
        }

        parent::tearDown();
    }

    /**
     * Test 1: Verify all advertised publish tags exist in the service provider.
     */
    public function test_all_advertised_publish_tags_are_registered(): void
    {
        $tags = RbacSuiteServiceProvider::publishableGroups();

        $expectedTags = [
            'rbac-suite-config',
            'rbac-suite-controllers',
            'rbac-suite-models',
            'rbac-suite-views',
            'rbac-suite-routes',
            'rbac-suite-migrations',
            'rbac-suite-seeders',
            'rbac-suite-assets',
            'rbac-suite',
            'rbac-suite-all',
        ];

        foreach ($expectedTags as $tag) {
            $this->assertContains(
                $tag,
                $tags,
                "The publish tag [{$tag}] must be registered by RbacSuiteServiceProvider."
            );

            $paths = RbacSuiteServiceProvider::pathsToPublish(RbacSuiteServiceProvider::class, $tag);
            $this->assertNotEmpty(
                $paths,
                "The publish tag [{$tag}] must have mapped file destinations."
            );
        }
    }

    /**
     * Test 2: Verify all source files mapped in publish tags actually exist on disk.
     */
    public function test_all_published_source_files_exist_on_disk(): void
    {
        $expectedTags = [
            'rbac-suite-config',
            'rbac-suite-controllers',
            'rbac-suite-models',
            'rbac-suite-views',
            'rbac-suite-routes',
            'rbac-suite-migrations',
            'rbac-suite-seeders',
            'rbac-suite-assets',
            'rbac-suite',
            'rbac-suite-all',
        ];

        foreach ($expectedTags as $tag) {
            $paths = RbacSuiteServiceProvider::pathsToPublish(RbacSuiteServiceProvider::class, $tag);
            foreach ($paths as $source => $dest) {
                $this->assertTrue(
                    File::exists($source),
                    "Source path [{$source}] for tag [{$tag}] must exist on disk."
                );
            }
        }
    }

    /**
     * Test 3: Verify destination paths follow conventional Laravel locations.
     */
    public function test_published_destination_paths_follow_laravel_conventions(): void
    {
        $configPaths = RbacSuiteServiceProvider::pathsToPublish(RbacSuiteServiceProvider::class, 'rbac-suite-config');
        $this->assertContains(config_path('rbac-suite.php'), array_values($configPaths));
        $this->assertContains(config_path('auth-pipeline.php'), array_values($configPaths));

        $controllerPaths = RbacSuiteServiceProvider::pathsToPublish(RbacSuiteServiceProvider::class, 'rbac-suite-controllers');
        $this->assertContains(app_path('Http/Controllers/Rbac'), array_values($controllerPaths));

        $modelPaths = RbacSuiteServiceProvider::pathsToPublish(RbacSuiteServiceProvider::class, 'rbac-suite-models');
        $this->assertContains(app_path('Models/Rbac'), array_values($modelPaths));

        $viewPaths = RbacSuiteServiceProvider::pathsToPublish(RbacSuiteServiceProvider::class, 'rbac-suite-views');
        $this->assertContains(resource_path('views'), array_values($viewPaths));

        $routePaths = RbacSuiteServiceProvider::pathsToPublish(RbacSuiteServiceProvider::class, 'rbac-suite-routes');
        $this->assertContains(base_path('routes/rbac.php'), array_values($routePaths));

        $migrationPaths = RbacSuiteServiceProvider::pathsToPublish(RbacSuiteServiceProvider::class, 'rbac-suite-migrations');
        $this->assertContains(database_path('migrations'), array_values($migrationPaths));

        $seederPaths = RbacSuiteServiceProvider::pathsToPublish(RbacSuiteServiceProvider::class, 'rbac-suite-seeders');
        $this->assertContains(database_path('seeders'), array_values($seederPaths));

        $assetPaths = RbacSuiteServiceProvider::pathsToPublish(RbacSuiteServiceProvider::class, 'rbac-suite-assets');
        $this->assertContains(public_path('vendor/rbac-suite'), array_values($assetPaths));
    }

    /**
     * Test 4: Verify RbacControllerResolver dynamically respects published application classes.
     */
    public function test_controller_resolver_prioritizes_custom_and_app_controllers(): void
    {
        // 1. By default, returns package controller
        $resolved = RbacControllerResolver::resolve(DashboardController::class);
        $this->assertEquals(DashboardController::class, $resolved);

        // 2. Short name resolution
        $resolvedLogin = RbacControllerResolver::resolve('LoginController');
        $this->assertEquals(LoginController::class, $resolvedLogin);

        // 3. Fallback when override class does not exist
        Config::set('rbac-suite.controllers.DashboardController', 'App\\NonExistent\\Controller');
        $this->assertEquals(
            DashboardController::class,
            RbacControllerResolver::resolve(DashboardController::class)
        );
    }

    /**
     * Test 5: Verify RbacModelResolver resolves model overrides with fallbacks.
     */
    public function test_model_resolver_prioritizes_custom_models(): void
    {
        $roleModel = RbacModelResolver::resolve('role');
        $this->assertEquals(Role::class, $roleModel);

        $authSettingModel = RbacModelResolver::resolve('auth_setting');
        $this->assertEquals(AuthSetting::class, $authSettingModel);
    }

    /**
     * Test 6: Verify published direct host views take precedence over vendor fallback views.
     */
    public function test_published_views_take_precedence(): void
    {
        // Default vendor view resolves
        $this->assertTrue(View::exists('rbac-suite::dashboard'));

        // Direct host view in resources/views takes precedence
        $customViewDir = $this->tempTestDir.'/views';
        File::makeDirectory($customViewDir, 0755, true);
        File::put(
            $customViewDir.'/custom-test.blade.php',
            '<h1>Published Host View Content</h1>'
        );

        View::addLocation($customViewDir);
        $this->assertTrue(View::exists('custom-test'));
        $this->assertEquals('custom-test', RbacViewResolver::resolve('custom-test'));
        $rendered = rbac_view('custom-test')->render();
        $this->assertStringContainsString('Published Host View Content', $rendered);
    }

    /**
     * Test 7: Verify dedicated route file routes/rbac.php is loaded and no duplicate routes exist.
     */
    public function test_dedicated_route_file_registers_expected_named_routes(): void
    {
        $expectedRoutes = [
            'login',
            'logout',
            'dashboard',
            'roles.index',
            'permissions.index',
            'auth.settings',
            'master.user.index',
        ];

        foreach ($expectedRoutes as $name) {
            $this->assertTrue(Route::has($name), "Expected route [{$name}] must be registered.");
        }

        // Verify route URIs are unique
        $routes = Route::getRoutes()->getRoutes();
        $uris = [];
        foreach ($routes as $route) {
            foreach ($route->methods() as $method) {
                if ($method === 'HEAD') {
                    continue;
                }
                $key = $method.':'.$route->uri();
                $this->assertFalse(
                    isset($uris[$key]),
                    "Duplicate route detected for [{$key}]."
                );
                $uris[$key] = true;
            }
        }
    }

    /**
     * Test 8: Verify installer command execution and safe non-destructive behavior.
     */
    public function test_install_command_executes_safely_and_is_idempotent(): void
    {
        // First run
        $this->artisan('rbac:install')
            ->assertSuccessful();

        // Second run must be idempotent and succeed without errors
        $this->artisan('rbac:install')
            ->assertSuccessful();
    }

    /**
     * Test 9: Verify seeders are idempotent and admin role assignment is preserved.
     */
    public function test_seeders_are_strictly_idempotent(): void
    {
        $this->seed(RbacSuiteDatabaseSeeder::class);

        $roleCount = Role::count();
        $this->assertGreaterThan(0, $roleCount);

        // Re-run seeder
        $this->seed(RbacSuiteDatabaseSeeder::class);
        $this->assertEquals($roleCount, Role::count(), 'Role count must remain identical after re-seed');

        $this->seed(DefaultAdminUserSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->assertEquals($roleCount, Role::count());
    }

    /**
     * Test 10: Verify public asset URL resolution for both /vendor/rbac-suite and /vendor/rbac-suite/assets.
     */
    public function test_rbac_asset_helper_resolves_clean_asset_urls(): void
    {
        $url1 = rbac_asset('css/bootstrap.css');
        $this->assertNotEmpty($url1);
        $this->assertStringContainsString('css/bootstrap.css', $url1);

        $url2 = rbac_asset('images/logo-dark.png');
        $this->assertNotEmpty($url2);
        $this->assertStringContainsString('images/logo-dark.png', $url2);
    }
}
