<?php

namespace Parth\LaravelRbacSuite\Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Parth\LaravelRbacSuite\Database\Seeders\RbacSuiteDatabaseSeeder;
use Parth\LaravelRbacSuite\Http\Controllers\RootRedirectController;
use Parth\LaravelRbacSuite\Models\AuthSetting;
use Parth\LaravelRbacSuite\Models\Role;
use Parth\LaravelRbacSuite\Tests\TestCase;

class FirstRunLoginFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_visiting_root_url_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_guest_visiting_login_url_loads_login_screen_with_200(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Login');
    }

    public function test_default_admin_user_can_authenticate_with_default_credentials(): void
    {
        $this->seed(RbacSuiteDatabaseSeeder::class);

        /** @var User $admin */
        $admin = User::where('email', 'admin@gmail.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('password', $admin->password));

        // Disable captcha for direct authentication verification
        AuthSetting::where('AuthCode', AuthSetting::CODE_CAPTCHA)->update(['IsEnabled' => 0]);

        // Submit login request with default credentials
        $response = $this->post(route('login'), [
            'email' => 'admin@gmail.com',
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_authenticated_admin_visiting_root_url_redirects_or_loads_dashboard(): void
    {
        $this->seed(RbacSuiteDatabaseSeeder::class);

        /** @var User $admin */
        $admin = User::where('email', 'admin@gmail.com')->first();
        $this->assertNotNull($admin);

        // 1. Direct unit verification of package RootRedirectController
        $controller = new RootRedirectController;
        $request = Request::create('/', 'GET');
        Auth::login($admin);
        $directResponse = $controller($request);

        $this->assertEquals(302, $directResponse->getStatusCode());
        $expectedDashboard = config('rbac-suite.routes.dashboard_route', 'dashboard');
        $this->assertEquals(route($expectedDashboard), $directResponse->headers->get('Location'));

        // 2. HTTP Route verification
        $httpResponse = $this->actingAs($admin)->get('/');
        $this->assertTrue(
            in_array($httpResponse->getStatusCode(), [200, 302]),
            'Root request for authenticated admin must either render dashboard (200) or redirect to dashboard (302).'
        );
    }

    public function test_admin_role_has_complete_catalogue_permissions_and_can_access_modules(): void
    {
        $this->seed(RbacSuiteDatabaseSeeder::class);

        /** @var Role $adminRole */
        $adminRole = Role::where('name', 'Admin')->first();
        $this->assertNotNull($adminRole);

        // Expected full catalogue permissions (20 permissions)
        $expectedPermissions = [
            'dashboard-view',
            'role-view',
            'role-create',
            'role-edit',
            'role-delete',
            'permission-view',
            'permission-create',
            'permission-edit',
            'permission-delete',
            'user-view',
            'user-create',
            'user-edit',
            'user-delete',
            'user-password-change',
            'auth_setting-view',
            'auth_setting-edit',
            'report-view',
            'report-create',
            'report-edit',
            'report-delete',
        ];

        foreach ($expectedPermissions as $perm) {
            $this->assertTrue(
                $adminRole->hasPermissionTo($perm),
                "Admin role must have permission [{$perm}]"
            );
        }

        $this->assertEquals(count($expectedPermissions), $adminRole->permissions()->count());

        /** @var User $admin */
        $admin = User::where('email', 'admin@gmail.com')->first();
        $this->assertTrue($admin->hasRole('Admin'));

        // Admin can access administrative modules
        $this->actingAs($admin)->get(route('auth.settings'))->assertStatus(200);
        $this->actingAs($admin)->get(route('roles.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('permissions.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('master.user.index'))->assertStatus(200);
    }

    public function test_standard_user_does_not_receive_admin_permissions(): void
    {
        $this->seed(RbacSuiteDatabaseSeeder::class);

        $standardUser = User::create([
            'name' => 'Standard User',
            'email' => 'standard@example.com',
            'password' => Hash::make('Secret123!'),
            'is_active' => '1',
        ]);

        $standardUser->assignRole('User');

        $this->assertTrue($standardUser->hasRole('User'));
        $this->assertTrue($standardUser->hasPermissionTo('dashboard-view'));
        $this->assertFalse($standardUser->hasPermissionTo('role-create'));
        $this->assertFalse($standardUser->hasPermissionTo('auth_setting-view'));
        $this->assertFalse($standardUser->hasPermissionTo('user-delete'));

        // Access to protected admin modules is rejected
        $this->actingAs($standardUser)->get(route('roles.index'))->assertStatus(403);
        $this->actingAs($standardUser)->get(route('permissions.index'))->assertStatus(403);
        $this->actingAs($standardUser)->get(route('auth.settings'))->assertStatus(403);
    }

    public function test_unauthenticated_access_to_protected_routes_is_redirected_to_login(): void
    {
        $protectedRoutes = [
            'roles.index',
            'permissions.index',
            'master.user.index',
            'auth.settings',
        ];

        foreach ($protectedRoutes as $routeName) {
            if (Route::has($routeName)) {
                $response = $this->get(route($routeName));
                $this->assertTrue(
                    in_array($response->getStatusCode(), [302, 401, 403]),
                    "Route [{$routeName}] should reject unauthenticated access."
                );

                if ($response->getStatusCode() === 302) {
                    $response->assertRedirect(route('login'));
                }
            }
        }
    }

    public function test_logout_clears_session_and_redirects_to_login(): void
    {
        $this->seed(RbacSuiteDatabaseSeeder::class);

        /** @var User $admin */
        $admin = User::where('email', 'admin@gmail.com')->first();

        $response = $this->actingAs($admin)->post(route('logout'));

        $this->assertGuest();
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_no_redirect_loops_occur_on_root_or_login_routes(): void
    {
        // 1. Guest visits root -> redirects to login -> loads with 200 (not a loop)
        $response = $this->get('/');
        $response->assertRedirect(route('login'));

        $loginResponse = $this->get(route('login'));
        $loginResponse->assertStatus(200);

        // 2. Authenticated user visits root -> loads dashboard or redirects cleanly
        $this->seed(RbacSuiteDatabaseSeeder::class);
        $admin = User::where('email', 'admin@gmail.com')->first();

        $authRootResponse = $this->actingAs($admin)->get('/');
        $this->assertTrue(in_array($authRootResponse->getStatusCode(), [200, 302]));

        $dashboardResponse = $this->actingAs($admin)->get(route('dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Welcome to RBAC');

        // Verify AuthPasswordSetting remains accessible
        $settingsResponse = $this->actingAs($admin)->get(route('auth.settings'));
        $settingsResponse->assertStatus(200);
    }
}
