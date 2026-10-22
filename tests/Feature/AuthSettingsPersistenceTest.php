<?php

namespace Parth\LaravelRbacSuite\Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Parth\LaravelRbacSuite\Models\AuthSetting;
use Parth\LaravelRbacSuite\Models\PasswordRule;
use Parth\LaravelRbacSuite\Models\Role;
use Parth\LaravelRbacSuite\Tests\TestCase;

class AuthSettingsPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AuthSetting::ensureDefaultSettings();
        PasswordRule::ensureDefaultRules();
    }

    public function test_default_settings_initialized_correctly(): void
    {
        $this->assertTrue(AuthSetting::where('AuthCode', AuthSetting::CODE_CAPTCHA)->exists());
        $this->assertTrue(AuthSetting::where('AuthCode', AuthSetting::CODE_EMAIL_OTP)->exists());
        $this->assertTrue(AuthSetting::where('AuthCode', AuthSetting::CODE_MOBILE_OTP)->exists());
        $this->assertTrue(AuthSetting::where('AuthCode', AuthSetting::CODE_WHATSAPP_OTP)->exists());
        $this->assertTrue(AuthSetting::where('AuthCode', AuthSetting::CODE_TWO_FACTOR)->exists());
        $this->assertTrue(AuthSetting::where('AuthCode', AuthSetting::CODE_MAC_FUNCTIONALITY)->exists());
        $this->assertTrue(AuthSetting::where('AuthCode', AuthSetting::CODE_REGISTRATION)->exists());
    }

    public function test_admin_can_update_authentication_settings(): void
    {
        $role = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        $userModel = config('rbac-suite.user_model', 'App\\Models\\User');
        $admin = $userModel::create([
            'name' => 'Admin User',
            'email' => 'admin.test@example.com',
            'password' => Hash::make('Admin@1234'),
            'is_active' => '1',
        ]);

        if (method_exists($admin, 'assignRole')) {
            $admin->assignRole($role);
        }

        $emailSetting = AuthSetting::where('AuthCode', AuthSetting::CODE_EMAIL_OTP)->first();

        $response = $this->actingAs($admin)->postJson('/AuthPasswordSetting/UpdateAuthenticationSettings', [
            'settings' => [
                [
                    'authSettingId' => $emailSetting->AuthSettingId,
                    'authCode' => AuthSetting::CODE_EMAIL_OTP,
                    'isEnabled' => true,
                    'otpattempt' => 4,
                    'otpresetTime' => 3,
                    'otpexpiryTime' => 15,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['result' => true]);

        $emailSetting->refresh();
        $this->assertTrue((bool) $emailSetting->IsEnabled);
        $this->assertEquals(4, $emailSetting->OTPAttempt);
        $this->assertEquals(3, $emailSetting->OTPResetTime);
        $this->assertEquals(15, $emailSetting->OTPExpiryTime);
    }

    public function test_admin_can_update_password_rules(): void
    {
        $role = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        $userModel = config('rbac-suite.user_model', 'App\\Models\\User');
        $admin = $userModel::create([
            'name' => 'Admin User 2',
            'email' => 'admin2.test@example.com',
            'password' => Hash::make('Admin@1234'),
            'is_active' => '1',
        ]);

        if (method_exists($admin, 'assignRole')) {
            $admin->assignRole($role);
        }

        $minRule = PasswordRule::where('RuleCode', PasswordRule::RULE_MIN_LENGTH)->first();

        $response = $this->actingAs($admin)->postJson('/AuthPasswordSetting/UpdatePasswordRules', [
            'rules' => [
                [
                    'ruleId' => $minRule->RuleId,
                    'ruleCode' => PasswordRule::RULE_MIN_LENGTH,
                    'isEnabled' => true,
                    'ruleValue' => 10,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['result' => true]);

        $minRule->refresh();
        $this->assertTrue((bool) $minRule->IsEnabled);
        $this->assertEquals(10, $minRule->RuleValue);
    }
}
