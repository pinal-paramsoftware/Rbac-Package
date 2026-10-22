<?php

namespace Parth\LaravelRbacSuite\Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Parth\LaravelRbacSuite\Models\AuthProviderSetting;
use Parth\LaravelRbacSuite\Models\AuthSetting;
use Parth\LaravelRbacSuite\Models\EmailLoginOtp;
use Parth\LaravelRbacSuite\Models\UserTwoFactor;
use Parth\LaravelRbacSuite\Tests\TestCase;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAndOtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 2525,
            'mail.from.address' => 'noreply@example.com',
            'mail.from.name' => 'RBAC Suite',
        ]);

        AuthProviderSetting::create([
            'provider_code' => AuthProviderSetting::PROVIDER_SMTP,
            'host' => '127.0.0.1',
            'port' => 2525,
            'username' => 'testuser',
            'password' => 'testpass',
            'encryption' => 'none',
            'from_address' => 'noreply@example.com',
            'from_name' => 'RBAC Suite',
        ]);

        AuthSetting::ensureDefaultSettings();
    }

    public function test_email_otp_challenge_issued_and_verified(): void
    {
        Mail::fake();

        AuthSetting::where('AuthCode', AuthSetting::CODE_EMAIL_OTP)->update([
            'IsEnabled' => 1,
            'OTPAttempt' => 5,
            'OTPResetTime' => 5,
            'OTPExpiryTime' => 10,
        ]);
        AuthSetting::where('AuthCode', AuthSetting::CODE_CAPTCHA)->update(['IsEnabled' => 0]);

        $userModel = config('rbac-suite.user_model', 'App\\Models\\User');
        $user = $userModel::create([
            'name' => 'OTP User',
            'email' => 'otp.user@example.com',
            'password' => Hash::make('Password@123'),
            'is_active' => '1',
        ]);

        // Stage 1 Login -> Redirects to OTP form
        $response = $this->post('/login', [
            'email' => 'otp.user@example.com',
            'password' => 'Password@123',
        ]);

        $response->assertRedirect('/otp-verify');
        $this->assertEquals($user->id, session('otp_user_id'));

        // Retrieve generated challenge
        $challenge = EmailLoginOtp::where('emp_id', $user->id)
            ->where('otp_type', AuthSetting::CODE_EMAIL_OTP)
            ->where('is_used', 0)
            ->first();

        $this->assertNotNull($challenge);

        // Stage 2 Verification with incorrect OTP
        $badResponse = $this->from('/otp-verify')->withSession([
            'otp_user_id' => $user->id,
            'otp_user_email' => $user->email,
        ])->post('/otp-verify', ['otp' => '000000']);

        $badResponse->assertRedirect('/otp-verify');
        $challenge->refresh();
        $this->assertEquals(1, $challenge->attempt_count);

        // Stage 2 Verification with correct OTP (we compute matching code or set hash)
        $validOtp = '123456';
        $challenge->update(['otp_hash' => Hash::make($validOtp)]);

        $goodResponse = $this->withSession([
            'otp_user_id' => $user->id,
            'otp_user_email' => $user->email,
        ])->post('/otp-verify', ['otp' => $validOtp]);

        $goodResponse->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_otp_attempt_limit_enforced(): void
    {
        Mail::fake();

        AuthSetting::where('AuthCode', AuthSetting::CODE_EMAIL_OTP)->update([
            'IsEnabled' => 1,
            'OTPAttempt' => 3,
            'OTPResetTime' => 5,
            'OTPExpiryTime' => 10,
        ]);
        AuthSetting::where('AuthCode', AuthSetting::CODE_CAPTCHA)->update(['IsEnabled' => 0]);

        $userModel = config('rbac-suite.user_model', 'App\\Models\\User');
        $user = $userModel::create([
            'name' => 'Limit User',
            'email' => 'limit.user@example.com',
            'password' => Hash::make('Password@123'),
            'is_active' => '1',
        ]);

        $this->post('/login', [
            'email' => 'limit.user@example.com',
            'password' => 'Password@123',
        ]);

        // Simulate 3 invalid attempts
        for ($i = 1; $i <= 3; $i++) {
            $this->withSession([
                'otp_user_id' => $user->id,
                'otp_user_email' => $user->email,
            ])->post('/otp-verify', ['otp' => '999999']);
        }

        $challenge = EmailLoginOtp::where('emp_id', $user->id)->first();
        $this->assertEquals(3, $challenge->attempt_count);

        // 4th attempt should be rejected due to attempt limit reached
        $response = $this->withSession([
            'otp_user_id' => $user->id,
            'otp_user_email' => $user->email,
        ])->post('/otp-verify', ['otp' => '999999']);

        $response->assertRedirect('/login');
    }

    public function test_google_authenticator_totp_verification(): void
    {
        AuthSetting::where('AuthCode', AuthSetting::CODE_TWO_FACTOR)->update([
            'IsEnabled' => 1,
            'OTPAttempt' => 5,
        ]);
        AuthSetting::where('AuthCode', AuthSetting::CODE_CAPTCHA)->update(['IsEnabled' => 0]);

        $userModel = config('rbac-suite.user_model', 'App\\Models\\User');
        $user = $userModel::create([
            'name' => '2FA User',
            'email' => '2fa.user@example.com',
            'password' => Hash::make('Password@123'),
            'is_active' => '1',
        ]);

        $google2fa = new Google2FA;
        $secretKey = $google2fa->generateSecretKey();

        UserTwoFactor::create([
            'emp_id' => $user->id,
            'user_id' => $user->id,
            'secret_key' => $secretKey,
            'google2fa_secret' => $secretKey,
            'is_enabled' => true,
            'google2fa_enabled' => true,
            'is_active' => true,
        ]);

        $loginResponse = $this->post('/login', [
            'email' => '2fa.user@example.com',
            'password' => 'Password@123',
        ]);

        $loginResponse->assertRedirect('/two-factor');

        // Generate valid TOTP code
        $validTotp = $google2fa->getCurrentOtp($secretKey);

        $verifyResponse = $this->withSession([
            'otp_user_id' => $user->id,
            'otp_user_email' => $user->email,
            'twofa_secret' => $secretKey,
        ])->post('/two-factor', ['code' => $validTotp]);

        $verifyResponse->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }
}
