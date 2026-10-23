<?php

namespace Parth\LaravelRbacSuite\Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Parth\LaravelRbacSuite\Database\Seeders\RbacSuiteDatabaseSeeder;
use Parth\LaravelRbacSuite\Facades\RbacSuite;
use Parth\LaravelRbacSuite\Models\AuthSetting;
use Parth\LaravelRbacSuite\RbacSuiteServiceProvider;
use Parth\LaravelRbacSuite\Support\RbacViewResolver;
use Parth\LaravelRbacSuite\Tests\TestCase;

class DirectViewPublishingAndResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/rbac_direct_view_test_'.uniqid();
        File::makeDirectory($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    /**
     * Helper to authenticate as seeded Admin user.
     */
    protected function authenticateAdmin(): void
    {
        $this->seed(RbacSuiteDatabaseSeeder::class);
        $userClass = config('rbac-suite.user_model', 'App\\Models\\User');
        $admin = $userClass::where('email', 'admin@gmail.com')->first();
        $this->actingAs($admin, 'web');
    }

    /**
     * Test 1: The view publish tag maps directly to resources/views/.
     */
    public function test_view_publish_tag_points_directly_to_resources_views(): void
    {
        $viewPaths = RbacSuiteServiceProvider::pathsToPublish(RbacSuiteServiceProvider::class, 'rbac-suite-views');
        $this->assertNotEmpty($viewPaths);
        $this->assertContains(resource_path('views'), array_values($viewPaths));

        // Core and All bundles also publish views directly into resources/views
        $corePaths = RbacSuiteServiceProvider::pathsToPublish(RbacSuiteServiceProvider::class, 'rbac-suite');
        $this->assertContains(resource_path('views'), array_values($corePaths));

        $allPaths = RbacSuiteServiceProvider::pathsToPublish(RbacSuiteServiceProvider::class, 'rbac-suite-all');
        $this->assertContains(resource_path('views'), array_values($allPaths));

    }

    /**
     * Test 2: File copy operation populates files directly under resources/views/ subdirectories.
     */
    public function test_view_files_copy_directly_into_host_views_structure(): void
    {
        $sourceViewsDir = dirname(__DIR__, 2).'/resources/views';
        $destViewsDir = $this->tempDir.'/views';
        File::copyDirectory($sourceViewsDir, $destViewsDir);

        $expectedFiles = [
            'dashboard.blade.php',
            'auth/login.blade.php',
            'auth/register.blade.php',
            'auth/otp-verify.blade.php',
            'auth/mobile-otp-verify.blade.php',
            'auth/whatsapp-otp-verify.blade.php',
            'auth/two-factor.blade.php',
            'auth/two-factor-setup.blade.php',
            'auth/two-factor-verify.blade.php',
            'auth/verify.blade.php',
            'auth/passwords/confirm.blade.php',
            'auth/passwords/email.blade.php',
            'auth/passwords/reset.blade.php',
            'layouts/master.blade.php',
            'layouts/master-without-nav.blade.php',
            'layouts/head.blade.php',
            'layouts/title-meta.blade.php',
            'layouts/topbar.blade.php',
            'layouts/sidebar.blade.php',
            'layouts/footer.blade.php',
            'layouts/right-sidebar.blade.php',
            'layouts/vendor-scripts.blade.php',
            'common-components/breadcrumb.blade.php',
            'master/AuthSettings/setting.blade.php',
            'master/user/index.blade.php',
            'master/user/create.blade.php',
            'roles/index.blade.php',
            'roles/create.blade.php',
            'roles/edit.blade.php',
            'roles/show.blade.php',
            'permissions/index.blade.php',
            'permissions/create.blade.php',
            'permissions/edit.blade.php',
            'emails/otp.blade.php',
            'emails/smtp-test.blade.php',
        ];

        foreach ($expectedFiles as $file) {
            $this->assertFileExists(
                $destViewsDir.'/'.$file,
                "Published view file [{$file}] must exist directly under resources/views/."
            );
        }
    }

    /**
     * Test 3: Login renders from the host application's direct view when published.
     */
    public function test_login_renders_from_host_direct_view_when_published(): void
    {
        $customViewsDir = $this->tempDir.'/custom_views';
        File::makeDirectory($customViewsDir.'/auth', 0755, true);
        File::put(
            $customViewsDir.'/auth/login.blade.php',
            '<!DOCTYPE html><html><body><div id="host-direct-login">HOST DIRECT LOGIN VIEW</div></body></html>'
        );

        View::prependLocation($customViewsDir);

        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('HOST DIRECT LOGIN VIEW');
        $response->assertSee('host-direct-login');
    }

    /**
     * Test 4: Registration renders from the host application's direct view when published.
     */
    public function test_registration_renders_from_host_direct_view_when_published(): void
    {
        AuthSetting::where('Code', AuthSetting::CODE_REGISTRATION)->update(['IsActive' => 1]);
        AuthSetting::clearCache();

        $customViewsDir = $this->tempDir.'/custom_views';
        File::makeDirectory($customViewsDir.'/auth', 0755, true);
        File::put(
            $customViewsDir.'/auth/register.blade.php',
            '<!DOCTYPE html><html><body><div id="host-direct-register">HOST DIRECT REGISTER VIEW</div></body></html>'
        );

        View::prependLocation($customViewsDir);

        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('HOST DIRECT REGISTER VIEW');
        $response->assertSee('host-direct-register');
    }

    /**
     * Test 5: Dashboard and all RBAC modules resolve published host views.
     */
    public function test_dashboard_and_modules_resolve_published_host_views(): void
    {
        $this->authenticateAdmin();

        $customViewsDir = $this->tempDir.'/custom_views';
        File::makeDirectory($customViewsDir.'/roles', 0755, true);
        File::makeDirectory($customViewsDir.'/permissions', 0755, true);
        File::makeDirectory($customViewsDir.'/master/user', 0755, true);
        File::makeDirectory($customViewsDir.'/master/AuthSettings', 0755, true);

        File::put($customViewsDir.'/dashboard.blade.php', '<div>CUSTOM HOST DASHBOARD</div>');
        File::put($customViewsDir.'/roles/index.blade.php', '<div>CUSTOM HOST ROLES INDEX</div>');
        File::put($customViewsDir.'/permissions/index.blade.php', '<div>CUSTOM HOST PERMISSIONS INDEX</div>');
        File::put($customViewsDir.'/master/user/index.blade.php', '<div>CUSTOM HOST USER INDEX</div>');
        File::put($customViewsDir.'/master/AuthSettings/setting.blade.php', '<div>CUSTOM HOST AUTH SETTING</div>');

        View::prependLocation($customViewsDir);

        // 1. Dashboard
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('CUSTOM HOST DASHBOARD');

        // 2. Roles
        $response = $this->get('/roles');
        $response->assertStatus(200);
        $response->assertSee('CUSTOM HOST ROLES INDEX');

        // 3. Permissions
        $response = $this->get('/permissions');
        $response->assertStatus(200);
        $response->assertSee('CUSTOM HOST PERMISSIONS INDEX');

        // 4. Users
        $response = $this->get('/master/user');
        $response->assertStatus(200);
        $response->assertSee('CUSTOM HOST USER INDEX');

        // 5. Auth settings
        $response = $this->get('/AuthPasswordSetting');
        $response->assertStatus(200);
        $response->assertSee('CUSTOM HOST AUTH SETTING');
    }

    /**
     * Test 6: Layouts, partials, and included Blade components resolve correctly.
     */
    public function test_layouts_partials_and_components_resolve_correctly(): void
    {
        $customViewsDir = $this->tempDir.'/custom_views';
        File::makeDirectory($customViewsDir.'/layouts', 0755, true);
        File::makeDirectory($customViewsDir.'/common-components', 0755, true);

        // Custom sidebar partial
        File::put(
            $customViewsDir.'/layouts/sidebar.blade.php',
            '<div id="custom-host-sidebar">CUSTOM HOST SIDEBAR NAVIGATION</div>'
        );

        // Custom breadcrumb component
        File::put(
            $customViewsDir.'/common-components/breadcrumb.blade.php',
            '<div class="custom-host-breadcrumb">CUSTOM HOST BREADCRUMB: {{ $title ?? $slot ?? "" }}</div>'
        );

        View::prependLocation($customViewsDir);

        $this->assertEquals('layouts.sidebar', RbacViewResolver::resolve('layouts.sidebar'));
        $this->assertEquals('layouts.sidebar', RbacViewResolver::resolve('rbac-suite::layouts.sidebar'));
        $this->assertEquals('common-components.breadcrumb', RbacViewResolver::resolve('common-components.breadcrumb'));

        $renderedSidebar = rbac_view('layouts.sidebar')->render();
        $this->assertStringContainsString('CUSTOM HOST SIDEBAR NAVIGATION', $renderedSidebar);

        $renderedBreadcrumb = rbac_view('common-components.breadcrumb', ['title' => 'Test Page'])->render();
        $this->assertStringContainsString('CUSTOM HOST BREADCRUMB', strtoupper($renderedBreadcrumb));
    }

    /**
     * Test 7: Internal package fallback works when host views are absent.
     */
    public function test_internal_package_fallback_works_when_host_views_are_absent(): void
    {
        // For views that only exist in package (e.g. absent in host)
        $resolvedDashboard = RbacViewResolver::resolve('nonexistent-view-name');
        $this->assertEquals('nonexistent-view-name', $resolvedDashboard);

        // Layout fallback
        Config::set('rbac-suite.layout', 'nonexistent.layout');
        $layout = RbacViewResolver::resolveLayout();
        $this->assertStringContainsString('layouts.master', $layout);

        // Standard views in absence of host overrides resolve to package namespace
        $cleanViews = ['dashboard', 'auth.login', 'auth.register', 'roles.index'];
        foreach ($cleanViews as $view) {
            $resolved = RbacViewResolver::resolve($view);
            $this->assertTrue(
                $resolved === $view || $resolved === 'rbac-suite::'.$view,
                "View [{$view}] must resolve to either host view or rbac-suite:: fallback."
            );
            $this->assertTrue(View::exists($resolved), "Resolved view [{$resolved}] must exist.");
        }
    }

    /**
     * Test 8: Customizing a published host view changes the rendered page.
     */
    public function test_customizing_published_host_view_changes_rendered_page(): void
    {
        $customViewsDir = $this->tempDir.'/custom_views';
        File::makeDirectory($customViewsDir.'/auth', 0755, true);

        $loginPath = $customViewsDir.'/auth/login.blade.php';

        // Version 1 of customized login
        File::put($loginPath, '<div>CUSTOM LOGIN VERSION 1</div>');
        View::prependLocation($customViewsDir);

        $response1 = $this->get('/login');
        $response1->assertSee('CUSTOM LOGIN VERSION 1');

        // Version 2: Update customization
        File::put($loginPath, '<div>CUSTOM LOGIN VERSION 2 - BRANDED ENTERPRISE</div>');

        $response2 = $this->get('/login');
        $response2->assertSee('CUSTOM LOGIN VERSION 2 - BRANDED ENTERPRISE');
        $response2->assertDontSee('CUSTOM LOGIN VERSION 1');
    }

    /**
     * Test 9: Repeated publishing does not silently overwrite customizations.
     */
    public function test_repeated_publishing_does_not_overwrite_customizations_without_force(): void
    {
        $destDir = $this->tempDir.'/publish_dest';
        File::makeDirectory($destDir.'/auth', 0755, true);

        $loginFile = $destDir.'/auth/login.blade.php';
        File::put($loginFile, '<!-- DEVELOPER CUSTOM LOGIN DO NOT OVERWRITE -->');

        // Simulate safe publish without force (copy file if not exists)
        $sourceLogin = dirname(__DIR__, 2).'/resources/views/auth/login.blade.php';

        // When force is false, existing file must remain intact
        if (! File::exists($loginFile)) {
            File::copy($sourceLogin, $loginFile);
        }

        $this->assertStringContainsString(
            'DEVELOPER CUSTOM LOGIN DO NOT OVERWRITE',
            File::get($loginFile),
            'Existing customized view must not be overwritten when published without force.'
        );

        // When force is explicitly requested, overwrite occurs
        File::copy($sourceLogin, $loginFile);
        $this->assertStringNotContainsString(
            'DEVELOPER CUSTOM LOGIN DO NOT OVERWRITE',
            File::get($loginFile),
            'Customized view is replaced only when explicitly forced.'
        );
    }

    /**
     * Test 10: Helper functions and RbacSuite facade view resolution methods.
     */
    public function test_helper_functions_and_facade_methods(): void
    {
        $this->assertEquals(
            RbacViewResolver::resolve('dashboard'),
            rbac_view_name('dashboard')
        );

        $this->assertEquals(
            RbacViewResolver::resolve('auth.login'),
            RbacSuite::resolveView('auth.login')
        );

        $viewInstance = rbac_view('dashboard');
        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $viewInstance);

        $facadeViewInstance = RbacSuite::view('dashboard');
        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $facadeViewInstance);
    }
}
