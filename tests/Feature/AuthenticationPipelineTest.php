<?php

namespace Parth\LaravelRbacSuite\Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Parth\LaravelRbacSuite\Models\AuthSetting;
use Parth\LaravelRbacSuite\Tests\TestCase;

class AuthenticationPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.default' => 'array',
            'mail.mailers.array.transport' => 'array',
            'mail.from.address' => 'noreply@example.com',
        ]);

        AuthSetting::ensureDefaultSettings();
    }

    public function test_successful_login_with_valid_credentials(): void
    {
        // Disable secondary OTPs for direct login test
        AuthSetting::whereIn('AuthCode', [
            AuthSetting::CODE_EMAIL_OTP,
            AuthSetting::CODE_MOBILE_OTP,
            AuthSetting::CODE_WHATSAPP_OTP,
            AuthSetting::CODE_TWO_FACTOR,
            AuthSetting::CODE_CAPTCHA,
            AuthSetting::CODE_MAC_FUNCTIONALITY,
        ])->update(['IsEnabled' => 0]);

        $userModel = config('rbac-suite.user_model', 'App\\Models\\User');
        $user = $userModel::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => Hash::make('Secret@123'),
            'is_active' => '1',
        ]);

        $response = $this->post('/login', [
            'email' => 'john.doe@example.com',
            'password' => 'Secret@123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_login_credentials_rejected(): void
    {
        AuthSetting::where('AuthCode', AuthSetting::CODE_CAPTCHA)->update(['IsEnabled' => 0]);

        $userModel = config('rbac-suite.user_model', 'App\\Models\\User');
        $userModel::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => Hash::make('Secret@123'),
            'is_active' => '1',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'john.doe@example.com',
            'password' => 'WrongPassword!456',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_account_rejected(): void
    {
        AuthSetting::where('AuthCode', AuthSetting::CODE_CAPTCHA)->update(['IsEnabled' => 0]);

        $userModel = config('rbac-suite.user_model', 'App\\Models\\User');
        $userModel::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => Hash::make('Secret@123'),
            'is_active' => '0',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'Secret@123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_protected_routes_blocked_for_guests(): void
    {
        $response = $this->get('/roles');
        $response->assertRedirect('/login');

        $response = $this->get('/permissions');
        $response->assertRedirect('/login');

        $response = $this->get('/master/user');
        $response->assertRedirect('/login');
    }

    public function test_logout_invalidates_session_and_clears_auth(): void
    {
        $userModel = config('rbac-suite.user_model', 'App\\Models\\User');
        $user = $userModel::create([
            'name' => 'Active User',
            'email' => 'active@example.com',
            'password' => Hash::make('Secret@123'),
            'is_active' => '1',
        ]);

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post('/logout');
        $response->assertRedirect();
        $this->assertGuest();
    }
}
