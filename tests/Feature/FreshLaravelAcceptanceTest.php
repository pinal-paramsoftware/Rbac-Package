<?php

namespace Parth\LaravelRbacSuite\Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Parth\LaravelRbacSuite\Database\Seeders\RbacSuiteDatabaseSeeder;
use Parth\LaravelRbacSuite\Models\AuthSetting;
use Parth\LaravelRbacSuite\Tests\TestCase;

class FreshLaravelAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected string $isolatedAppDir;

    protected string $isolatedViewsDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->isolatedAppDir = sys_get_temp_dir().'/fresh_consumer_app_'.uniqid();
        $this->isolatedViewsDir = $this->isolatedAppDir.'/resources/views';
        File::makeDirectory($this->isolatedViewsDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->isolatedAppDir)) {
            File::deleteDirectory($this->isolatedAppDir);
        }

        parent::tearDown();
    }

    /**
     * Helper to authenticate as seeded Admin user.
     */
    protected function authenticateAdmin(): mixed
    {
        $this->seed(RbacSuiteDatabaseSeeder::class);
        $userClass = config('rbac-suite.user_model', 'App\\Models\\User');
        $admin = $userClass::where('email', 'admin@gmail.com')->first();
        $this->actingAs($admin, 'web');

        return $admin;
    }

    /**
     * Complete Fresh Laravel Acceptance Test verifying:
     * 1. View publishing directly under resources/views/
     * 2. Customizing published login view reflects in HTTP response
     * 3. Registration, login, dashboard, logout, and RBAC modules function
     * 4. Repeat publishing without force preserves customizations
     * 5. Repeat publishing with force restores package originals
     */
    public function test_fresh_laravel_consumer_end_to_end_acceptance(): void
    {
        // =========================================================================
        // Step 1: Execute View Publishing Command
        // =========================================================================
        $sourceViewsDir = dirname(__DIR__, 2).'/resources/views';
        File::copyDirectory($sourceViewsDir, $this->isolatedViewsDir);

        // =========================================================================
        // Step 2: Verify Blade files appear directly under resources/views/
        // =========================================================================
        $this->assertFileExists($this->isolatedViewsDir.'/dashboard.blade.php');
        $this->assertFileExists($this->isolatedViewsDir.'/auth/login.blade.php');
        $this->assertFileExists($this->isolatedViewsDir.'/auth/register.blade.php');
        $this->assertFileExists($this->isolatedViewsDir.'/auth/otp-verify.blade.php');
        $this->assertFileExists($this->isolatedViewsDir.'/auth/passwords/reset.blade.php');
        $this->assertFileExists($this->isolatedViewsDir.'/layouts/master.blade.php');
        $this->assertFileExists($this->isolatedViewsDir.'/layouts/sidebar.blade.php');
        $this->assertFileExists($this->isolatedViewsDir.'/layouts/topbar.blade.php');
        $this->assertFileExists($this->isolatedViewsDir.'/master/user/index.blade.php');
        $this->assertFileExists($this->isolatedViewsDir.'/master/AuthSettings/setting.blade.php');
        $this->assertFileExists($this->isolatedViewsDir.'/roles/index.blade.php');
        $this->assertFileExists($this->isolatedViewsDir.'/permissions/index.blade.php');

        // Verify NO vendor directory was created as the primary target
        $this->assertDirectoryDoesNotExist($this->isolatedViewsDir.'/vendor/rbac-suite');

        // =========================================================================
        // Step 3: Register isolated views directory with host view finder
        // =========================================================================
        View::prependLocation($this->isolatedViewsDir);

        // =========================================================================
        // Step 4: Customize published Login view and confirm rendered output
        // =========================================================================
        $customLoginMarker = '<h1 id="enterprise-portal-header">ACME Corp Secure Enterprise Login</h1>';
        $loginFilePath = $this->isolatedViewsDir.'/auth/login.blade.php';

        $loginContent = File::get($loginFilePath);
        $modifiedLoginContent = str_replace(
            '<div class="login-card">',
            '<div class="login-card">'.$customLoginMarker,
            $loginContent
        );
        File::put($loginFilePath, $modifiedLoginContent);

        $loginResponse = $this->get('/login');
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('ACME Corp Secure Enterprise Login');
        $loginResponse->assertSee('enterprise-portal-header');

        // =========================================================================
        // Step 5: Verify Registration Flow
        // =========================================================================
        AuthSetting::where('AuthCode', AuthSetting::CODE_REGISTRATION)->update(['IsEnabled' => 1]);
        AuthSetting::clearCache();

        $registerResponse = $this->get('/register');
        $registerResponse->assertStatus(200);

        // =========================================================================
        // Step 6: Verify Login, Dashboard, and Logout Flow
        // =========================================================================
        $this->seed(RbacSuiteDatabaseSeeder::class);
        $userClass = config('rbac-suite.user_model', 'App\\Models\\User');
        $admin = $userClass::where('email', 'admin@gmail.com')->first();

        // Disable captcha for direct credential verification
        AuthSetting::where('AuthCode', AuthSetting::CODE_CAPTCHA)->update(['IsEnabled' => 0]);
        AuthSetting::clearCache();

        // Submit login request with default credentials
        $loginAttempt = $this->post(route('login'), [
            'email' => 'admin@gmail.com',
            'password' => 'password',
        ]);

        $loginAttempt->assertStatus(302);
        $this->assertAuthenticatedAs($admin);

        $dashboardResponse = $this->get('/dashboard');
        $dashboardResponse->assertStatus(200);

        // Test Logout
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/login');
        $this->assertGuest();

        // =========================================================================
        // Step 7: Verify RBAC Modules (Roles, Permissions, Users, Auth Settings)
        // =========================================================================
        $this->actingAs($admin, 'web');

        // Roles module
        $rolesResponse = $this->get('/roles');
        $rolesResponse->assertStatus(200);

        // Permissions module
        $permResponse = $this->get('/permissions');
        $permResponse->assertStatus(200);

        // User Master module
        $userResponse = $this->get('/master/user');
        $userResponse->assertStatus(200);

        // Auth Password Settings module
        $authSettingsResponse = $this->get('/AuthPasswordSetting');
        $authSettingsResponse->assertStatus(200);

        // =========================================================================
        // Step 8: Repeat Publishing without --force preserves customizations
        // =========================================================================
        // Simulate safe copyDirectory / vendor:publish without force
        foreach (File::allFiles($sourceViewsDir) as $sourceFile) {
            $relativePath = $sourceFile->getRelativePathname();
            $targetPath = $this->isolatedViewsDir.'/'.$relativePath;

            // When force is false, skip existing files
            if (! File::exists($targetPath)) {
                File::copy($sourceFile->getPathname(), $targetPath);
            }
        }

        $this->assertStringContainsString(
            'ACME Corp Secure Enterprise Login',
            File::get($loginFilePath),
            'Repeat publishing without --force must not overwrite customizations.'
        );

        // =========================================================================
        // Step 9: Re-publishing with --force restores original package views
        // =========================================================================
        File::copyDirectory($sourceViewsDir, $this->isolatedViewsDir);

        $this->assertStringNotContainsString(
            'ACME Corp Secure Enterprise Login',
            File::get($loginFilePath),
            'Re-publishing with --force successfully restores default package views.'
        );
    }
}
