<?php

namespace Parth\LaravelRbacSuite\Http\Controllers\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Parth\LaravelRbacSuite\Http\Controllers\BaseController;
use Parth\LaravelRbacSuite\Models\AuthSetting;
use Parth\LaravelRbacSuite\Models\EmailLoginOtp;
use Parth\LaravelRbacSuite\Models\UserTwoFactor;
use Parth\LaravelRbacSuite\Services\DeviceAuthorizationService;
use Parth\LaravelRbacSuite\Services\MobileOtpSmsGatewayService;
use Parth\LaravelRbacSuite\Services\WhatsAppOtpGatewayService;
use Parth\LaravelRbacSuite\Support\CaptchaConfiguration;
use Parth\LaravelRbacSuite\Support\CaptchaSettings;
use Parth\LaravelRbacSuite\Support\EmailOtpMailConfiguration;
use Parth\LaravelRbacSuite\Support\EmailOtpSmtpSettings;
use Parth\LaravelRbacSuite\Support\MobileOtpSmsConfiguration;
use Parth\LaravelRbacSuite\Support\WhatsAppOtpConfiguration;
use PragmaRX\Google2FA\Google2FA;

class LoginController extends BaseController
{
    use AuthenticatesUsers;

    private const CAPTCHA_SESSION_KEY = 'captcha_challenge';

    private const CAPTCHA_EXPIRY_SECONDS = 120;

    private const TWO_FACTOR_CHALLENGE_SESSION_KEY = 'twofa_challenge';

    protected $redirectTo = '/otp-verify';

    protected function getUserModelClass(): string
    {
        return config('rbac-suite.user_model', 'App\\Models\\User');
    }

    protected function authView(string $viewName, array $data = [])
    {
        return rbac_view($viewName, $data);
    }

    protected Google2FA $google2fa;

    private static array $authSettingsCache = [];

    public function __construct(
        protected ?DeviceAuthorizationService $deviceAuthorizationService = null,
    ) {
        $this->deviceAuthorizationService ??= app(DeviceAuthorizationService::class);
        $this->middleware('guest')->except('logout');
        $this->google2fa = new Google2FA;
    }

    public static function resetSettingsCache(): void
    {
        self::$authSettingsCache = [];
    }

    /**
     * The user has logged out of the application.
     */
    protected function loggedOut(Request $request)
    {
        return Route::has('login') ? redirect()->route('login') : redirect('/login');
    }

    public function showLoginForm()
    {
        $registrationEnabled = AuthSetting::isRegistrationEnabled();

        return rbac_view('auth.login', compact('registrationEnabled'));
    }

    public function generateCaptcha()
    {
        $captchaEnabled = $this->isAuthEnabled(AuthSetting::CODE_CAPTCHA);
        if (! $captchaEnabled) {
            $img = imagecreate(120, 40);
            imagecolorallocate($img, 255, 255, 255);
            header('Content-Type: image/png');
            imagepng($img);
            imagedestroy($img);

            return;
        }

        CaptchaSettings::apply();

        $configValidation = CaptchaConfiguration::validate();
        if (! $configValidation['valid']) {
            session()->forget(self::CAPTCHA_SESSION_KEY);
            Log::error('Captcha generation failed: '.$configValidation['message']);
            $img = imagecreate(120, 40);
            imagecolorallocate($img, 255, 255, 255);
            header('Content-Type: image/png');
            imagepng($img);
            imagedestroy($img);

            return;
        }

        $code = strtoupper(Str::random(5));
        session([
            self::CAPTCHA_SESSION_KEY => [
                'code' => $code,
                'generated_at' => now()->timestamp,
                'used_at' => null,
            ],
        ]);

        $img = imagecreate(120, 40);
        imagecolorallocate($img, 255, 255, 255);
        $txt = imagecolorallocate($img, 60, 60, 60);

        for ($i = 0; $i < 80; $i++) {
            imagesetpixel(
                $img,
                rand(0, 120),
                rand(0, 40),
                imagecolorallocate($img, rand(0, 255), rand(0, 255), rand(0, 255))
            );
        }

        for ($i = 0; $i < strlen($code); $i++) {
            imagestring($img, 5, 20 + ($i * 18), 10, $code[$i], $txt);
        }

        header('Content-Type: image/png');
        imagepng($img);
        imagedestroy($img);
    }

    protected function validateLogin(Request $request)
    {
        $captchaEnabled = $this->isAuthEnabled(AuthSetting::CODE_CAPTCHA);
        $rules = [
            $this->username() => 'required|string',
            'password' => 'required|string',
        ];

        if ($captchaEnabled) {
            $rules['captcha'] = 'required|string';
        }

        $request->validate($rules);

        if ($captchaEnabled) {
            $configValidation = CaptchaConfiguration::validate();
            if (! $configValidation['valid']) {
                $this->failCaptchaValidation($configValidation['message']);
            }
            $this->validateCaptchaResponse((string) $request->input('captcha'));
        }
    }

    private function validateCaptchaResponse(string $submittedCaptcha): void
    {
        $challenge = session(self::CAPTCHA_SESSION_KEY);

        if (! is_array($challenge)) {
            $this->failCaptchaValidation('Captcha verification service is unavailable. Please refresh captcha and try again.');
        }

        $expectedCaptcha = $challenge['code'] ?? null;
        $generatedAt = $challenge['generated_at'] ?? null;
        $usedAt = $challenge['used_at'] ?? null;

        if (! is_string($expectedCaptcha) || trim($expectedCaptcha) === '' || ! is_numeric($generatedAt)) {
            session()->forget(self::CAPTCHA_SESSION_KEY);
            $this->failCaptchaValidation('Captcha configuration is incomplete. Please refresh captcha and try again.');
        }

        if (! is_null($usedAt)) {
            session()->forget(self::CAPTCHA_SESSION_KEY);
            $this->failCaptchaValidation('Captcha response has already been used. Please refresh captcha and try again.');
        }

        $issuedAt = (int) $generatedAt;
        if ($issuedAt <= 0 || (now()->timestamp - $issuedAt) > self::CAPTCHA_EXPIRY_SECONDS) {
            session()->forget(self::CAPTCHA_SESSION_KEY);
            $this->failCaptchaValidation('Captcha has expired. Please refresh captcha and try again.');
        }

        $normalizedSubmittedCaptcha = strtoupper(trim($submittedCaptcha));
        $normalizedExpectedCaptcha = strtoupper(trim($expectedCaptcha));

        if ($normalizedSubmittedCaptcha === '' || ! hash_equals($normalizedExpectedCaptcha, $normalizedSubmittedCaptcha)) {
            session()->forget(self::CAPTCHA_SESSION_KEY);
            $this->failCaptchaValidation('Invalid captcha.');
        }

        session([
            self::CAPTCHA_SESSION_KEY => [
                'code' => $normalizedExpectedCaptcha,
                'generated_at' => $issuedAt,
                'used_at' => now()->timestamp,
            ],
        ]);
    }

    private function failCaptchaValidation(string $message): void
    {
        throw ValidationException::withMessages([
            'captcha' => [$message],
        ]);
    }

    protected function attemptLogin(Request $request)
    {
        $user = $this->getUserModelClass()::where($this->username(), $request->input($this->username()))->first();

        if ($user && Hash::check((string) $request->input('password'), $user->password)) {
            if ((string) $user->is_active === '0') {
                session()->flash('error', 'Your account is inactive. Please contact the administrator.');

                throw ValidationException::withMessages([
                    $this->username() => ['Your account is inactive. Please contact the administrator.'],
                ]);
            }
        }

        return $this->guard()->attempt(
            $this->credentials($request), $request->boolean('remember')
        );
    }

    protected function authenticated(Request $request, $user)
    {
        if ((string) $user->is_active === '0') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            session()->flash('error', 'Your account is inactive. Please contact the administrator.');

            throw ValidationException::withMessages([
                $this->username() => ['Your account is inactive. Please contact the administrator.'],
            ]);
        }

        AuthSetting::ensureDefaultSettings();

        if ($this->isAuthEnabled(AuthSetting::CODE_MAC_FUNCTIONALITY)) {
            $deviceVerification = $this->deviceAuthorizationService->verifyDevice($request, $user);

            if (! $deviceVerification['authorized']) {
                return $this->handleMacRestrictionFailure($request, $deviceVerification['message']);
            }

            session(['mac_verified' => true]);
        }

        session([
            'otp_user_id' => $user->id,
            'otp_user_email' => $this->getUserEmail($user),
        ]);

        try {
            if ($this->isAuthEnabled(AuthSetting::CODE_EMAIL_OTP)) {
                $mailConfigValidation = EmailOtpMailConfiguration::validate();
                if (! $mailConfigValidation['valid']) {
                    throw new \RuntimeException($mailConfigValidation['message']);
                }

                $issue = $this->issueOtpChallenge($user, AuthSetting::CODE_EMAIL_OTP, true);
                if ($issue['status'] === 'generated') {
                    try {
                        $this->sendEmailOtp($user, $issue['otp']);
                        Session::flash('toast', [
                            'type' => 'success',
                            'message' => 'OTP sent to your email successfully!',
                        ]);
                    } catch (\Throwable $exception) {
                        $this->invalidateOtpChallenge($user->id, AuthSetting::CODE_EMAIL_OTP);
                        Log::error('Failed to send email OTP', [
                            'user_id' => $user->id,
                            'error' => $exception->getMessage(),
                        ]);

                        Auth::logout();
                        Session::flash('toast', [
                            'type' => 'error',
                            'message' => 'Failed to send OTP email due to mail server error. Please verify your SMTP settings.',
                        ]);

                        return $this->redirectToLogin();
                    }
                } elseif ($issue['status'] === 'cooldown') {
                    Session::flash('toast', [
                        'type' => 'info',
                        'message' => 'An OTP was already sent recently. Please use the existing code or wait before requesting a new one.',
                    ]);
                } elseif ($issue['status'] === 'max_attempts') {
                    throw new \RuntimeException('Maximum attempts reached for email OTP.');
                }
            }

            if ($this->isAuthEnabled(AuthSetting::CODE_MOBILE_OTP)) {
                $mobileValidation = $this->validateMobileOtpReadiness($user);
                if (! $mobileValidation['valid']) {
                    throw new \RuntimeException($mobileValidation['message']);
                }

                $issue = $this->issueOtpChallenge($user, AuthSetting::CODE_MOBILE_OTP, true);
                if ($issue['status'] === 'generated') {
                    try {
                        $smsResult = $this->sendMobileOtp($user, $issue['otp']);
                        if (! $smsResult['success']) {
                            $this->invalidateOtpChallenge($user->id, AuthSetting::CODE_MOBILE_OTP);
                            throw new \RuntimeException('SMS gateway request was not successful.');
                        }

                        Session::flash('toast', [
                            'type' => 'success',
                            'message' => 'OTP sent to your verified mobile number successfully!',
                        ]);
                    } catch (\Throwable $exception) {
                        $this->invalidateOtpChallenge($user->id, AuthSetting::CODE_MOBILE_OTP);
                        throw $exception;
                    }
                } elseif ($issue['status'] === 'cooldown') {
                    Session::flash('toast', [
                        'type' => 'info',
                        'message' => 'A mobile OTP was already sent recently. Please wait before requesting a new one.',
                    ]);
                } elseif ($issue['status'] === 'max_attempts') {
                    throw new \RuntimeException('Maximum attempts reached for mobile OTP.');
                }
            }

            if ($this->isAuthEnabled(AuthSetting::CODE_WHATSAPP_OTP)) {
                $whatsAppValidation = $this->validateWhatsAppOtpReadiness($user);
                if (! $whatsAppValidation['valid']) {
                    throw new \RuntimeException($whatsAppValidation['message']);
                }

                $issue = $this->issueOtpChallenge($user, AuthSetting::CODE_WHATSAPP_OTP, true);
                if ($issue['status'] === 'generated') {
                    try {
                        $whatsAppResult = $this->sendWhatsAppOtp($user, $issue['otp']);
                        if (! $whatsAppResult['success']) {
                            $this->invalidateOtpChallenge($user->id, AuthSetting::CODE_WHATSAPP_OTP);
                            throw new \RuntimeException('WhatsApp gateway request was not successful.');
                        }

                        Session::flash('toast', [
                            'type' => 'success',
                            'message' => 'OTP sent to your verified WhatsApp number successfully!',
                        ]);
                    } catch (\Throwable $exception) {
                        $this->invalidateOtpChallenge($user->id, AuthSetting::CODE_WHATSAPP_OTP);
                        throw $exception;
                    }
                } elseif ($issue['status'] === 'cooldown') {
                    Session::flash('toast', [
                        'type' => 'info',
                        'message' => 'A WhatsApp OTP was already sent recently. Please wait before requesting a new one.',
                    ]);
                } elseif ($issue['status'] === 'max_attempts') {
                    throw new \RuntimeException('Maximum attempts reached for WhatsApp OTP.');
                }
            }
        } catch (\Throwable $exception) {
            Log::error('Unable to initialize OTP challenge', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);

            Auth::logout();
            Session::flash('toast', [
                'type' => 'error',
                'message' => 'Unable to initialize the verification flow. Please try again.',
            ]);

            return $this->redirectToLogin();
        }

        $twoFactorRecord = UserTwoFactor::where('emp_id', $user->id)->first();
        if ($twoFactorRecord && $twoFactorRecord->is_enabled) {
            session(['twofa_secret' => $twoFactorRecord->secret_key]);
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_TWO_FACTOR)) {
            $maxAttempts = $this->getAttemptLimit(AuthSetting::CODE_TWO_FACTOR);
            $rateLimitKey = $this->twoFactorRateLimitKey($user->id);
            if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
                Auth::logout();
                $this->clearAuthSession();
                Session::flash('toast', [
                    'type' => 'error',
                    'message' => 'Maximum attempts reached. Please login again.',
                ]);

                return $this->redirectToLogin();
            }

            $this->initializeTwoFactorChallenge();
        }

        Auth::logout();

        return $this->startVerificationFlow();
    }

    public function showOtpForm()
    {
        if (! $this->isAuthEnabled(AuthSetting::CODE_EMAIL_OTP)) {
            return $this->checkAndRedirectToNextStep();
        }

        if (session('email_verified')) {
            return $this->checkAndRedirectToNextStep();
        }

        $user = $this->getChallengeUser();
        if (! $user) {
            return $this->redirectToLogin();
        }

        $challenge = $this->getOtpChallengeForUpdate($user->id, AuthSetting::CODE_EMAIL_OTP);
        if (! $challenge || $challenge->is_used || now()->gte($challenge->expiry_time)) {
            Session::flash('toast', [
                'type' => 'error',
                'message' => 'OTP has expired. Please login again.',
            ]);

            return $this->redirectToLogin();
        }

        $maskedEmail = $this->maskEmail($this->getUserEmail($user));
        $maxAttempts = $this->getAttemptLimit(AuthSetting::CODE_EMAIL_OTP);
        $attemptsLeft = max(0, $maxAttempts - (int) $challenge->attempt_count);
        $canResend = ! $challenge->resend_available_at || now()->gte($challenge->resend_available_at);
        $secondsLeft = $canResend ? 0 : now()->diffInSeconds($challenge->resend_available_at);
        $expiresInSeconds = max(0, now()->diffInSeconds($challenge->expiry_time, false));

        return $this->authView('auth.otp-verify', [
            'email' => $maskedEmail,
            'canResend' => $canResend,
            'secondsLeft' => $secondsLeft,
            'attemptsLeft' => $attemptsLeft,
            'expiresInSeconds' => $expiresInSeconds,
            'otpResetTime' => $this->getResetSeconds(AuthSetting::CODE_EMAIL_OTP),
            'emailSetting' => true,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        if (! $this->isAuthEnabled(AuthSetting::CODE_EMAIL_OTP)) {
            return $this->checkAndRedirectToNextStep();
        }

        if (session('email_verified')) {
            return $this->checkAndRedirectToNextStep();
        }

        $request->validate(['otp' => 'required|digits:6']);

        $user = $this->getChallengeUser();
        if (! $user) {
            return $this->redirectToLogin();
        }

        $result = $this->verifyOtpChallenge($user->id, AuthSetting::CODE_EMAIL_OTP, (string) $request->otp);

        if ($result['status'] === 'success') {
            session(['email_verified' => true]);
            Session::flash('toast', [
                'type' => 'success',
                'message' => 'Email verified successfully!',
            ]);

            return $this->checkAndRedirectToNextStep();
        }

        if ($result['status'] === 'expired') {
            Session::flash('toast', [
                'type' => 'error',
                'message' => 'OTP has expired. Please request a new one.',
            ]);

            return back()->withErrors(['otp' => 'OTP has expired. Please request a new one.']);
        }

        if ($result['status'] === 'max_attempts' || $result['status'] === 'unavailable') {
            Session::flash('toast', [
                'type' => 'error',
                'message' => 'Maximum attempts reached. Please login again.',
            ]);

            return $this->redirectToLogin();
        }

        $attemptsLeft = (int) $result['attempts_left'];
        Session::flash('toast', [
            'type' => 'error',
            'message' => $attemptsLeft > 0
                ? "Invalid OTP. {$attemptsLeft} attempts left."
                : 'Maximum attempts reached. Please login again.',
        ]);

        if ($attemptsLeft <= 0) {
            return $this->redirectToLogin();
        }

        return back()->withErrors(['otp' => "Invalid OTP. {$attemptsLeft} attempts left."]);
    }

    public function resendOtp(Request $request)
    {
        if (! $this->isAuthEnabled(AuthSetting::CODE_EMAIL_OTP)) {
            return response()->json([
                'success' => false,
                'message' => 'Email OTP is not enabled',
            ]);
        }

        $user = $this->getChallengeUser();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'redirect' => true,
            ]);
        }

        $mailConfigValidation = EmailOtpMailConfiguration::validate();
        if (! $mailConfigValidation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $mailConfigValidation['message'],
                'redirect' => true,
            ], 422);
        }

        $resendSeconds = $this->getResetSeconds(AuthSetting::CODE_EMAIL_OTP);
        $rateLimitKey = $this->otpResendRateLimitKey($user->id, AuthSetting::CODE_EMAIL_OTP);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting a new OTP',
                'seconds_left' => RateLimiter::availableIn($rateLimitKey),
            ]);
        }

        $issue = $this->issueOtpChallenge($user, AuthSetting::CODE_EMAIL_OTP, true);

        if ($issue['status'] === 'cooldown') {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting a new OTP',
                'seconds_left' => $issue['seconds_left'],
            ]);
        }

        if ($issue['status'] === 'max_attempts') {
            return response()->json([
                'success' => false,
                'message' => 'Maximum attempts reached. Please login again.',
                'redirect' => true,
            ]);
        }

        try {
            $this->sendEmailOtp($user, $issue['otp']);
        } catch (\Throwable $exception) {
            $this->invalidateOtpChallenge($user->id, AuthSetting::CODE_EMAIL_OTP);

            Log::error('Unable to send resent OTP email', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send OTP email due to a mail server connection or authentication error. Please try again later.',
                'redirect' => true,
            ], 500);
        }

        RateLimiter::hit($rateLimitKey, $resendSeconds);

        return response()->json([
            'success' => true,
            'message' => 'New OTP sent successfully',
            'seconds_left' => $resendSeconds,
            'expires_in' => $this->getExpirySeconds(AuthSetting::CODE_EMAIL_OTP),
        ]);
    }

    private function clearAuthSession(): void
    {
        session()->forget([
            'otp_user_id',
            'otp_user_email',
            'mac_verified',
            'email_verified',
            'mobile_verified',
            'whatsapp_verified',
            'twofa_verified',
            'twofa_secret',
            'twofa_qr_code',
            'is_first_time_2fa',
            'show_verify_screen',
            self::TWO_FACTOR_CHALLENGE_SESSION_KEY,
        ]);
    }

    private function redirectToLogin()
    {
        $this->clearAuthSession();

        return redirect()->route('login');
    }

    public function showMobileOtpForm()
    {
        if (! $this->isAuthEnabled(AuthSetting::CODE_MOBILE_OTP)) {
            return $this->checkAndRedirectToNextStep();
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_EMAIL_OTP) && ! session('email_verified')) {
            return redirect()->route('otp.form');
        }

        if (session('mobile_verified')) {
            return $this->checkAndRedirectToNextStep();
        }

        $user = $this->getChallengeUser();
        if (! $user) {
            return $this->redirectToLogin();
        }

        $challenge = $this->getOtpChallengeForUpdate($user->id, AuthSetting::CODE_MOBILE_OTP);
        if (! $challenge || $challenge->is_used || now()->gte($challenge->expiry_time)) {
            $mobileValidation = $this->validateMobileOtpReadiness($user);
            if (! $mobileValidation['valid']) {
                Session::flash('toast', [
                    'type' => 'error',
                    'message' => $mobileValidation['message'],
                ]);

                return $this->redirectToLogin();
            }

            $issue = $this->issueOtpChallenge($user, AuthSetting::CODE_MOBILE_OTP, false);
            if ($issue['status'] === 'max_attempts') {
                Session::flash('toast', [
                    'type' => 'error',
                    'message' => 'Maximum attempts reached. Please login again.',
                ]);

                return $this->redirectToLogin();
            }

            if ($issue['status'] === 'generated') {
                $smsResult = $this->sendMobileOtp($user, $issue['otp']);
                if (! $smsResult['success']) {
                    $this->invalidateOtpChallenge($user->id, AuthSetting::CODE_MOBILE_OTP);

                    Session::flash('toast', [
                        'type' => 'error',
                        'message' => 'Unable to send mobile OTP. Please login again.',
                    ]);

                    return $this->redirectToLogin();
                }
            }
        }

        return $this->authView('auth.mobile-otp-verify');
    }

    public function resendMobileOtp(Request $request)
    {
        if (! $this->isAuthEnabled(AuthSetting::CODE_MOBILE_OTP)) {
            return response()->json([
                'success' => false,
                'message' => 'Mobile OTP is not enabled',
            ]);
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_EMAIL_OTP) && ! session('email_verified')) {
            return response()->json([
                'success' => false,
                'message' => 'Complete email verification before requesting a mobile OTP.',
            ], 422);
        }

        $user = $this->getChallengeUser();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'redirect' => true,
            ]);
        }

        $mobileValidation = $this->validateMobileOtpReadiness($user);
        if (! $mobileValidation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $mobileValidation['message'],
                'redirect' => true,
            ], 422);
        }

        $resendSeconds = $this->getResetSeconds(AuthSetting::CODE_MOBILE_OTP);
        $rateLimitKey = $this->otpResendRateLimitKey($user->id, AuthSetting::CODE_MOBILE_OTP);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting a new OTP',
                'seconds_left' => RateLimiter::availableIn($rateLimitKey),
            ]);
        }

        $issue = $this->issueOtpChallenge($user, AuthSetting::CODE_MOBILE_OTP, true);
        if ($issue['status'] === 'cooldown') {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting a new OTP',
                'seconds_left' => $issue['seconds_left'],
            ]);
        }

        if ($issue['status'] === 'max_attempts') {
            return response()->json([
                'success' => false,
                'message' => 'Maximum attempts reached. Please login again.',
                'redirect' => true,
            ]);
        }

        $smsResult = $this->sendMobileOtp($user, $issue['otp']);
        if (! $smsResult['success']) {
            $this->invalidateOtpChallenge($user->id, AuthSetting::CODE_MOBILE_OTP);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send OTP. Please try again later.',
                'redirect' => true,
            ], 500);
        }

        RateLimiter::hit($rateLimitKey, $resendSeconds);

        return response()->json([
            'success' => true,
            'message' => 'New OTP sent successfully',
            'seconds_left' => $resendSeconds,
            'expires_in' => $this->getExpirySeconds(AuthSetting::CODE_MOBILE_OTP),
        ]);
    }

    public function verifyMobileOtp(Request $request)
    {
        if (! $this->isAuthEnabled(AuthSetting::CODE_MOBILE_OTP)) {
            return $this->checkAndRedirectToNextStep();
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_EMAIL_OTP) && ! session('email_verified')) {
            return redirect()->route('otp.form');
        }

        if (session('mobile_verified')) {
            return $this->checkAndRedirectToNextStep();
        }

        $request->validate(['otp' => 'required|digits:6']);

        $user = $this->getChallengeUser();
        if (! $user) {
            return $this->redirectToLogin();
        }

        $result = $this->verifyOtpChallenge($user->id, AuthSetting::CODE_MOBILE_OTP, (string) $request->otp);
        if ($result['status'] === 'success') {
            Session::flash('toast', [
                'type' => 'success',
                'message' => 'Mobile verified successfully!',
            ]);
            session(['mobile_verified' => true]);

            return $this->checkAndRedirectToNextStep();
        }

        if ($result['status'] === 'expired') {
            Session::flash('toast', [
                'type' => 'error',
                'message' => 'OTP has expired. Please login again.',
            ]);

            return $this->redirectToLogin();
        }

        if ($result['status'] === 'max_attempts' || $result['status'] === 'unavailable') {
            Session::flash('toast', [
                'type' => 'error',
                'message' => 'Maximum attempts reached. Please login again.',
            ]);

            return $this->redirectToLogin();
        }

        $attemptsLeft = (int) $result['attempts_left'];
        Session::flash('toast', [
            'type' => 'error',
            'message' => $attemptsLeft > 0
                ? "Invalid Mobile OTP. {$attemptsLeft} attempts left."
                : 'Maximum attempts reached. Please login again.',
        ]);

        if ($attemptsLeft <= 0) {
            return $this->redirectToLogin();
        }

        return back()->withErrors(['otp' => "Invalid Mobile OTP. {$attemptsLeft} attempts left."]);
    }

    public function showWhatsAppOtpForm()
    {
        if (! $this->isAuthEnabled(AuthSetting::CODE_WHATSAPP_OTP)) {
            return $this->checkAndRedirectToNextStep();
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_EMAIL_OTP) && ! session('email_verified')) {
            return redirect()->route('otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_MOBILE_OTP) && ! session('mobile_verified')) {
            return redirect()->route('mobile.otp.form');
        }

        if (session('whatsapp_verified')) {
            return $this->checkAndRedirectToNextStep();
        }

        $user = $this->getChallengeUser();
        if (! $user) {
            return $this->redirectToLogin();
        }

        $challenge = $this->getOtpChallengeForUpdate($user->id, AuthSetting::CODE_WHATSAPP_OTP);
        if (! $challenge || $challenge->is_used || now()->gte($challenge->expiry_time)) {
            $whatsAppValidation = $this->validateWhatsAppOtpReadiness($user);
            if (! $whatsAppValidation['valid']) {
                Session::flash('toast', [
                    'type' => 'error',
                    'message' => $whatsAppValidation['message'],
                ]);

                return $this->redirectToLogin();
            }

            $issue = $this->issueOtpChallenge($user, AuthSetting::CODE_WHATSAPP_OTP, false);
            if ($issue['status'] === 'max_attempts') {
                Session::flash('toast', [
                    'type' => 'error',
                    'message' => 'Maximum attempts reached. Please login again.',
                ]);

                return $this->redirectToLogin();
            }

            if ($issue['status'] === 'generated') {
                $whatsAppResult = $this->sendWhatsAppOtp($user, $issue['otp']);
                if (! $whatsAppResult['success']) {
                    $this->invalidateOtpChallenge($user->id, AuthSetting::CODE_WHATSAPP_OTP);

                    Session::flash('toast', [
                        'type' => 'error',
                        'message' => 'Unable to send WhatsApp OTP. Please login again.',
                    ]);

                    return $this->redirectToLogin();
                }
            }
        }

        return $this->authView('auth.whatsapp-otp-verify');
    }

    public function resendWhatsAppOtp(Request $request)
    {
        if (! $this->isAuthEnabled(AuthSetting::CODE_WHATSAPP_OTP)) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp OTP is not enabled',
            ]);
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_EMAIL_OTP) && ! session('email_verified')) {
            return response()->json([
                'success' => false,
                'message' => 'Complete email verification before requesting a WhatsApp OTP.',
            ], 422);
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_MOBILE_OTP) && ! session('mobile_verified')) {
            return response()->json([
                'success' => false,
                'message' => 'Complete mobile verification before requesting a WhatsApp OTP.',
            ], 422);
        }

        $user = $this->getChallengeUser();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'redirect' => true,
            ]);
        }

        $whatsAppValidation = $this->validateWhatsAppOtpReadiness($user);
        if (! $whatsAppValidation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $whatsAppValidation['message'],
                'redirect' => true,
            ], 422);
        }

        $resendSeconds = $this->getResetSeconds(AuthSetting::CODE_WHATSAPP_OTP);
        $rateLimitKey = $this->otpResendRateLimitKey($user->id, AuthSetting::CODE_WHATSAPP_OTP);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting a new OTP',
                'seconds_left' => RateLimiter::availableIn($rateLimitKey),
            ]);
        }

        $issue = $this->issueOtpChallenge($user, AuthSetting::CODE_WHATSAPP_OTP, true);
        if ($issue['status'] === 'cooldown') {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting a new OTP',
                'seconds_left' => $issue['seconds_left'],
            ]);
        }

        if ($issue['status'] === 'max_attempts') {
            return response()->json([
                'success' => false,
                'message' => 'Maximum attempts reached. Please login again.',
                'redirect' => true,
            ]);
        }

        $whatsAppResult = $this->sendWhatsAppOtp($user, $issue['otp']);
        if (! $whatsAppResult['success']) {
            $this->invalidateOtpChallenge($user->id, AuthSetting::CODE_WHATSAPP_OTP);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send OTP. Please try again later.',
                'redirect' => true,
            ], 500);
        }

        RateLimiter::hit($rateLimitKey, $resendSeconds);

        return response()->json([
            'success' => true,
            'message' => 'New OTP sent successfully',
            'seconds_left' => $resendSeconds,
            'expires_in' => $this->getExpirySeconds(AuthSetting::CODE_WHATSAPP_OTP),
        ]);
    }

    public function verifyWhatsAppOtp(Request $request)
    {
        if (! $this->isAuthEnabled(AuthSetting::CODE_WHATSAPP_OTP)) {
            return $this->checkAndRedirectToNextStep();
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_EMAIL_OTP) && ! session('email_verified')) {
            return redirect()->route('otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_MOBILE_OTP) && ! session('mobile_verified')) {
            return redirect()->route('mobile.otp.form');
        }

        if (session('whatsapp_verified')) {
            return $this->checkAndRedirectToNextStep();
        }

        $request->validate(['otp' => 'required|digits:6']);

        $user = $this->getChallengeUser();
        if (! $user) {
            return $this->redirectToLogin();
        }

        $result = $this->verifyOtpChallenge($user->id, AuthSetting::CODE_WHATSAPP_OTP, (string) $request->otp);
        if ($result['status'] === 'success') {
            Session::flash('toast', [
                'type' => 'success',
                'message' => 'WhatsApp verified successfully!',
            ]);
            session(['whatsapp_verified' => true]);

            return $this->checkAndRedirectToNextStep();
        }

        if ($result['status'] === 'expired') {
            Session::flash('toast', [
                'type' => 'error',
                'message' => 'OTP has expired. Please login again.',
            ]);

            return $this->redirectToLogin();
        }

        if ($result['status'] === 'max_attempts' || $result['status'] === 'unavailable') {
            Session::flash('toast', [
                'type' => 'error',
                'message' => 'Maximum attempts reached. Please login again.',
            ]);

            return $this->redirectToLogin();
        }

        $attemptsLeft = (int) $result['attempts_left'];
        Session::flash('toast', [
            'type' => 'error',
            'message' => $attemptsLeft > 0
                ? "Invalid WhatsApp OTP. {$attemptsLeft} attempts left."
                : 'Maximum attempts reached. Please login again.',
        ]);

        if ($attemptsLeft <= 0) {
            return $this->redirectToLogin();
        }

        return back()->withErrors(['otp' => "Invalid WhatsApp OTP. {$attemptsLeft} attempts left."]);
    }

    public function showTwoFactorForm()
    {
        if (! $this->isAuthEnabled(AuthSetting::CODE_TWO_FACTOR)) {
            return $this->checkAndRedirectToNextStep();
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_EMAIL_OTP) && ! session('email_verified')) {
            return redirect()->route('otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_MOBILE_OTP) && ! session('mobile_verified')) {
            return redirect()->route('mobile.otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_WHATSAPP_OTP) && ! session('whatsapp_verified')) {
            return redirect()->route('whatsapp.otp.form');
        }

        if (session('twofa_verified')) {
            return $this->checkAndRedirectToNextStep();
        }

        $userId = session('otp_user_id');
        $user = $this->getUserModelClass()::find($userId);
        if (! $user || (string) $user->is_active === '0') {
            Auth::logout();
            $this->clearAuthSession();
            Session::flash('error', 'Your account is inactive. Please contact the administrator.');

            return $this->redirectToLogin();
        }

        $maxAttempts = $this->getAttemptLimit(AuthSetting::CODE_TWO_FACTOR);
        $rateLimitKey = $this->twoFactorRateLimitKey($user->id);
        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            Session::flash('toast', [
                'type' => 'error',
                'message' => 'Maximum attempts reached. Please login again.',
            ]);

            return $this->redirectToLogin();
        }

        if (! $this->isTwoFactorChallengeActive()) {
            Session::flash('toast', [
                'type' => 'error',
                'message' => 'Two-factor verification has expired. Please login again.',
            ]);

            return $this->redirectToLogin();
        }

        if (session('show_verify_screen')) {
            session()->forget('show_verify_screen');

            return $this->showVerifyScreen($user);
        }

        $twoFactorRecord = UserTwoFactor::where('emp_id', $user->id)->first();
        if ($twoFactorRecord && $twoFactorRecord->is_enabled == 1) {
            return $this->showVerifyScreen($user);
        }

        return $this->showSetupScreen($user);
    }

    private function showSetupScreen($user)
    {
        $secret = $this->google2fa->generateSecretKey();
        session(['twofa_secret' => $secret]);

        return $this->authView('auth.two-factor-setup', [
            'user' => $user,
            'secret' => $secret,
            'qrCodeUrl' => $this->generateQRCode($user, $secret),
        ]);
    }

    private function showVerifyScreen($user)
    {
        $secret = session('twofa_secret');
        if (! $secret) {
            $twoFactorRecord = UserTwoFactor::where('emp_id', $user->id)->first();
            if ($twoFactorRecord && $twoFactorRecord->secret_key) {
                $secret = $twoFactorRecord->secret_key;
                session(['twofa_secret' => $secret]);
            } else {
                $secret = $this->google2fa->generateSecretKey();
                session(['twofa_secret' => $secret]);
            }
        }

        return $this->authView('auth.two-factor-verify', [
            'user' => $user,
        ]);
    }

    public function showTwoFactorVerify()
    {
        if ($this->isAuthEnabled(AuthSetting::CODE_TWO_FACTOR) === false) {
            return $this->checkAndRedirectToNextStep();
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_EMAIL_OTP) && ! session('email_verified')) {
            return redirect()->route('otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_MOBILE_OTP) && ! session('mobile_verified')) {
            return redirect()->route('mobile.otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_WHATSAPP_OTP) && ! session('whatsapp_verified')) {
            return redirect()->route('whatsapp.otp.form');
        }

        if (session('twofa_verified')) {
            return $this->checkAndRedirectToNextStep();
        }

        $userId = session('otp_user_id');
        $user = $this->getUserModelClass()::find($userId);
        if (! $user || (string) $user->is_active === '0') {
            Auth::logout();
            $this->clearAuthSession();
            Session::flash('error', 'Your account is inactive. Please contact the administrator.');

            return $this->redirectToLogin();
        }

        if (! $this->isTwoFactorChallengeActive()) {
            Session::flash('toast', [
                'type' => 'error',
                'message' => 'Two-factor verification has expired. Please login again.',
            ]);

            return $this->redirectToLogin();
        }

        session(['show_verify_screen' => true]);

        return $this->showVerifyScreen($user);
    }

    public function verifyTwoFactor(Request $request)
    {
        if (! $this->isAuthEnabled(AuthSetting::CODE_TWO_FACTOR)) {
            return $this->checkAndRedirectToNextStep();
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_EMAIL_OTP) && ! session('email_verified')) {
            return redirect()->route('otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_MOBILE_OTP) && ! session('mobile_verified')) {
            return redirect()->route('mobile.otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_WHATSAPP_OTP) && ! session('whatsapp_verified')) {
            return redirect()->route('whatsapp.otp.form');
        }

        if (session('twofa_verified')) {
            return $this->checkAndRedirectToNextStep();
        }

        $request->validate(['code' => 'required|digits:6']);

        $user = $this->getChallengeUser();
        if (! $user) {
            return $this->redirectToLogin();
        }

        $maxAttempts = $this->getAttemptLimit(AuthSetting::CODE_TWO_FACTOR);
        $rateLimitKey = $this->twoFactorRateLimitKey($user->id);
        if (! $this->isTwoFactorChallengeActive()) {
            Session::flash('toast', [
                'type' => 'error',
                'message' => 'Two-factor verification has expired. Please login again.',
            ]);

            return $this->redirectToLogin();
        }

        $challengeAttempts = $this->getTwoFactorChallengeAttempts();
        if ($challengeAttempts >= $maxAttempts) {
            Session::flash('toast', [
                'type' => 'error',
                'message' => 'Maximum attempts reached. Please login again.',
            ]);

            return $this->redirectToLogin();
        }

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            Session::flash('toast', [
                'type' => 'error',
                'message' => 'Maximum attempts reached. Please login again.',
            ]);

            return $this->redirectToLogin();
        }

        $secret = session('twofa_secret');
        if (! $secret) {
            $twoFactorRecord = UserTwoFactor::where('emp_id', $user->id)->first();
            if ($twoFactorRecord && $twoFactorRecord->secret_key) {
                $secret = $twoFactorRecord->secret_key;
                session(['twofa_secret' => $secret]);
            } else {
                Session::flash('toast', [
                    'type' => 'error',
                    'message' => '2FA setup error. Please login again.',
                ]);

                return $this->redirectToLogin();
            }
        }

        $window = 1;
        $twoFactorRecord = UserTwoFactor::where('emp_id', $user->id)->first();
        $recordedWindow = (int) ($twoFactorRecord->last_verified_window ?? 0);
        $verificationResult = $twoFactorRecord
            ? $this->google2fa->verifyKeyNewer($secret, (string) $request->code, $recordedWindow, $window)
            : $this->google2fa->verifyKey($secret, (string) $request->code, $window);

        if ($verificationResult === false) {
            RateLimiter::hit($rateLimitKey, $this->getTwoFactorAttemptDecaySeconds());
            $challengeAttempts = $this->incrementTwoFactorChallengeAttempts();
            $attemptsLeft = max(0, $maxAttempts - RateLimiter::attempts($rateLimitKey));
            $attemptsLeft = min($attemptsLeft, max(0, $maxAttempts - $challengeAttempts));

            Session::flash('toast', [
                'type' => 'error',
                'message' => $attemptsLeft > 0
                    ? "Invalid authentication code. {$attemptsLeft} attempts left."
                    : 'Maximum attempts reached. Please login again.',
            ]);

            if ($attemptsLeft <= 0) {
                return $this->redirectToLogin();
            }

            return back()->withErrors(['code' => "Invalid authentication code. {$attemptsLeft} attempts left."]);
        }

        if (! $twoFactorRecord || $twoFactorRecord->is_enabled != 1) {
            $twoFactorRecord = UserTwoFactor::updateOrCreate(
                ['emp_id' => $user->id],
                [
                    'secret_key' => $secret,
                    'is_enabled' => 1,
                    'is_active' => 1,
                ]
            );

            Session::flash('toast', [
                'type' => 'success',
                'message' => 'Two-factor authentication has been set up successfully!',
            ]);
        } else {
            Session::flash('toast', [
                'type' => 'success',
                'message' => 'Two-factor authentication verified successfully!',
            ]);
        }

        if (is_int($verificationResult) && $twoFactorRecord) {
            $twoFactorRecord->last_verified_window = $verificationResult;
            $twoFactorRecord->save();
        }

        RateLimiter::clear($rateLimitKey);
        session(['twofa_verified' => true]);

        return $this->checkAndRedirectToNextStep();
    }

    public function isAuthEnabled($authCode): bool
    {
        $setting = $this->getAuthSetting($authCode);

        return $setting !== null && (int) $setting->IsEnabled === 1;
    }

    private function issueOtpChallenge(Model $user, string $authCode, bool $enforceResendWindow): array
    {
        $challengeKey = $this->otpChallengeKey($user->id, $authCode);
        $now = now();
        $maxAttempts = $this->getAttemptLimit($authCode);
        $resetSeconds = $this->getResetSeconds($authCode);
        $expirySeconds = $this->getExpirySeconds($authCode);

        return DB::transaction(function () use ($challengeKey, $user, $authCode, $maxAttempts, $resetSeconds, $expirySeconds, $now, $enforceResendWindow) {
            $challenge = EmailLoginOtp::where('challenge_key', $challengeKey)->lockForUpdate()->first();

            if ($challenge && ! $challenge->is_used && $now->lt($challenge->expiry_time) && (int) $challenge->attempt_count >= $maxAttempts) {
                return ['status' => 'max_attempts'];
            }

            if (
                $challenge &&
                ! $challenge->is_used &&
                $now->lt($challenge->expiry_time) &&
                $enforceResendWindow &&
                $challenge->resend_available_at &&
                $now->lt($challenge->resend_available_at)
            ) {
                return [
                    'status' => 'cooldown',
                    'seconds_left' => $now->diffInSeconds($challenge->resend_available_at),
                ];
            }

            $attemptCount = 0;
            if ($challenge && ! $challenge->is_used && $now->lt($challenge->expiry_time)) {
                $attemptCount = (int) $challenge->attempt_count;
            }

            $otp = $this->generateOtpCode($authCode);
            $payload = [
                'emp_id' => $user->id,
                'email' => $this->getOtpRecipientIdentifier($user, $authCode),
                'otp_type' => $authCode,
                'otp_hash' => Hash::make($otp),
                'attempt_count' => $attemptCount,
                'expiry_time' => $now->copy()->addSeconds($expirySeconds),
                'resend_available_at' => $now->copy()->addSeconds($resetSeconds),
                'is_used' => 0,
                'used_at' => null,
            ];

            if ($challenge) {
                $challenge->fill($payload)->save();
            } else {
                EmailLoginOtp::create(array_merge($payload, ['challenge_key' => $challengeKey]));
            }

            return [
                'status' => 'generated',
                'otp' => $otp,
            ];
        });
    }

    private function verifyOtpChallenge(int $userId, string $authCode, string $submittedOtp): array
    {
        $challengeKey = $this->otpChallengeKey($userId, $authCode);
        $maxAttempts = $this->getAttemptLimit($authCode);
        $now = now();

        return DB::transaction(function () use ($challengeKey, $submittedOtp, $maxAttempts, $now) {
            $challenge = EmailLoginOtp::where('challenge_key', $challengeKey)->lockForUpdate()->first();
            if (! $challenge) {
                return ['status' => 'unavailable'];
            }

            if ($challenge->is_used) {
                return ['status' => 'unavailable'];
            }

            if ($now->gte($challenge->expiry_time)) {
                return ['status' => 'expired'];
            }

            $attemptCount = (int) $challenge->attempt_count;
            if ($attemptCount >= $maxAttempts) {
                return ['status' => 'max_attempts'];
            }

            if (! Hash::check($submittedOtp, $challenge->otp_hash)) {
                $attemptCount++;
                $challenge->attempt_count = $attemptCount;
                $challenge->save();

                return [
                    'status' => $attemptCount >= $maxAttempts ? 'max_attempts' : 'invalid',
                    'attempts_left' => max(0, $maxAttempts - $attemptCount),
                ];
            }

            $challenge->is_used = 1;
            $challenge->used_at = $now;
            $challenge->save();

            return ['status' => 'success'];
        });
    }

    private function getOtpChallengeForUpdate(int $userId, string $authCode): ?EmailLoginOtp
    {
        return EmailLoginOtp::where('challenge_key', $this->otpChallengeKey($userId, $authCode))->first();
    }

    private function otpChallengeKey(int $userId, string $authCode): string
    {
        return sprintf('otp:%d:%s', $userId, $authCode);
    }

    private function otpResendRateLimitKey(int $userId, string $authCode): string
    {
        return sprintf('otp-resend:%d:%s', $userId, $authCode);
    }

    private function twoFactorRateLimitKey(int $userId): string
    {
        return sprintf('twofa:%d', $userId);
    }

    private function invalidateOtpChallenge(int $userId, string $authCode): void
    {
        EmailLoginOtp::where('challenge_key', $this->otpChallengeKey($userId, $authCode))
            ->update([
                'is_used' => 1,
                'used_at' => now(),
            ]);
    }

    private function sendEmailOtp(Model $user, string $otp): void
    {
        $email = $this->getUserEmail($user);
        if ($email === '') {
            throw new \RuntimeException('User email is not available.');
        }

        EmailOtpSmtpSettings::apply();

        $fromAddress = (string) config('mail.from.address');
        $fromName = (string) config('mail.from.name');

        $emailView = rbac_view_name('emails.otp');

        Mail::send($emailView, [
            'user' => $user,
            'otp' => $otp,
            'expiry' => $this->getExpiryMinutes(AuthSetting::CODE_EMAIL_OTP),
            'attempts' => $this->getAttemptLimit(AuthSetting::CODE_EMAIL_OTP),
        ], function ($message) use ($email, $fromAddress, $fromName): void {
            if ($fromAddress !== '' && method_exists($message, 'from')) {
                $message->from($fromAddress, $fromName !== '' ? $fromName : null);
            }
            $message->to($email)->subject('Your OTP Code');
        });
    }

    private function generateOtpCode(string $authCode): string
    {
        return (string) random_int(100000, 999999);
    }

    private function generateQRCode($user, $secret)
    {
        try {
            $appName = rawurlencode(config('app.name', 'My Application'));
            $email = rawurlencode($this->getUserEmail($user));

            return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='.
                urlencode("otpauth://totp/{$appName}:{$email}?secret={$secret}&issuer={$appName}");
        } catch (\Exception $e) {
            Log::error('QR Code Generation Error: '.$e->getMessage());

            return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='.urlencode($secret);
        }
    }

    private function maskEmail($email)
    {
        if (! $email) {
            return 'your email';
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }

        $username = $parts[0];
        $domain = $parts[1];
        $maskedUsername = substr($username, 0, 2).str_repeat('*', max(0, strlen($username) - 4)).substr($username, -2);

        $domainParts = explode('.', $domain);
        if (count($domainParts) >= 2) {
            $maskedDomain = substr($domainParts[0], 0, 2).str_repeat('*', max(0, strlen($domainParts[0]) - 4)).'.'.$domainParts[1];
        } else {
            $maskedDomain = $domain;
        }

        return $maskedUsername.'@'.$maskedDomain;
    }

    private function getUserEmail(Model $user): string
    {
        return (string) ($user->Email ?? $user->email ?? '');
    }

    private function getOtpRecipientIdentifier(Model $user, string $authCode): string
    {
        if (in_array($authCode, [AuthSetting::CODE_MOBILE_OTP, AuthSetting::CODE_WHATSAPP_OTP], true)) {
            return $this->getUserMobileNumber($user);
        }

        return $this->getUserEmail($user);
    }

    private function getUserMobileNumber(Model $user): string
    {
        $mobile = (string) ($user->mobile_number ?? $user->MobileNumber ?? '');

        return trim($mobile);
    }

    /**
     * @return array{valid: bool, message: string}
     */
    private function validateMobileOtpReadiness(Model $user): array
    {
        $mobileNumber = $this->getUserMobileNumber($user);
        if ($mobileNumber === '') {
            return [
                'valid' => false,
                'message' => 'No registered mobile number found for this user.',
            ];
        }

        if (! preg_match('/^\+?[1-9]\d{7,14}$/', $mobileNumber)) {
            return [
                'valid' => false,
                'message' => 'Registered mobile number format is invalid.',
            ];
        }

        if (! $user->mobile_verified_at) {
            return [
                'valid' => false,
                'message' => 'Mobile number must be verified before login.',
            ];
        }

        $configValidation = MobileOtpSmsConfiguration::validate();
        if (! $configValidation['valid']) {
            return $configValidation;
        }

        return [
            'valid' => true,
            'message' => 'Mobile OTP checks passed.',
        ];
    }

    /**
     * @return array{valid: bool, message: string}
     */
    private function validateWhatsAppOtpReadiness(Model $user): array
    {
        $mobileNumber = $this->getUserMobileNumber($user);
        if ($mobileNumber === '') {
            return [
                'valid' => false,
                'message' => 'No registered mobile number found for this user.',
            ];
        }

        if (! preg_match('/^\+?[1-9]\d{7,14}$/', $mobileNumber)) {
            return [
                'valid' => false,
                'message' => 'Registered mobile number format is invalid.',
            ];
        }

        if (! $user->mobile_verified_at) {
            return [
                'valid' => false,
                'message' => 'Mobile number must be verified before login.',
            ];
        }

        $configValidation = WhatsAppOtpConfiguration::validate();
        if (! $configValidation['valid']) {
            return $configValidation;
        }

        return [
            'valid' => true,
            'message' => 'WhatsApp OTP checks passed.',
        ];
    }

    /**
     * @return array{success: bool, error?: string}
     */
    private function sendMobileOtp(Model $user, string $otp): array
    {
        $mobileNumber = $this->getUserMobileNumber($user);
        if ($mobileNumber === '') {
            return [
                'success' => false,
                'error' => 'missing_mobile_number',
            ];
        }

        $result = app(MobileOtpSmsGatewayService::class)->sendOtp($mobileNumber, $otp);
        if (! $result['success']) {
            Log::warning('Failed to send mobile OTP through gateway', [
                'user_id' => $user->id,
                'error' => $result['error'] ?? 'unknown_error',
            ]);
        }

        return $result;
    }

    /**
     * @return array{success: bool, error?: string}
     */
    private function sendWhatsAppOtp(Model $user, string $otp): array
    {
        $mobileNumber = $this->getUserMobileNumber($user);
        if ($mobileNumber === '') {
            return [
                'success' => false,
                'error' => 'missing_mobile_number',
            ];
        }

        $result = app(WhatsAppOtpGatewayService::class)->sendOtp($mobileNumber, $otp);
        if (! $result['success']) {
            Log::warning('Failed to send WhatsApp OTP through gateway', [
                'user_id' => $user->id,
                'error' => $result['error'] ?? 'unknown_error',
            ]);
        }

        return $result;
    }

    private function getChallengeUser(): ?Model
    {
        $userId = (int) session('otp_user_id');
        if ($userId <= 0) {
            return null;
        }

        $user = $this->getUserModelClass()::find($userId);
        if (! $user || (string) $user->is_active === '0') {
            $this->clearAuthSession();
            Session::flash('error', 'Your account is inactive. Please contact the administrator.');

            return null;
        }

        return $user;
    }

    private function startVerificationFlow()
    {
        if ($this->isAuthEnabled(AuthSetting::CODE_EMAIL_OTP)) {
            return redirect()->route('otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_MOBILE_OTP)) {
            return redirect()->route('mobile.otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_WHATSAPP_OTP)) {
            return redirect()->route('whatsapp.otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_TWO_FACTOR)) {
            return redirect()->route('twofa.form');
        }

        return $this->completeLogin();
    }

    private function checkAndRedirectToNextStep()
    {
        if ($this->isAuthEnabled(AuthSetting::CODE_EMAIL_OTP) && ! session('email_verified')) {
            return redirect()->route('otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_MOBILE_OTP) && ! session('mobile_verified')) {
            return redirect()->route('mobile.otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_WHATSAPP_OTP) && ! session('whatsapp_verified')) {
            return redirect()->route('whatsapp.otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_TWO_FACTOR) && ! session('twofa_verified')) {
            return redirect()->route('twofa.form');
        }

        return $this->completeLogin();
    }

    private function completeLogin()
    {
        $userId = session('otp_user_id');
        if (! $userId) {
            return $this->redirectToLogin();
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_MAC_FUNCTIONALITY) && ! session('mac_verified')) {
            return $this->handleMacRestrictionFailure(request());
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_EMAIL_OTP) && ! session('email_verified')) {
            return redirect()->route('otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_MOBILE_OTP) && ! session('mobile_verified')) {
            return redirect()->route('mobile.otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_WHATSAPP_OTP) && ! session('whatsapp_verified')) {
            return redirect()->route('whatsapp.otp.form');
        }

        if ($this->isAuthEnabled(AuthSetting::CODE_TWO_FACTOR) && ! session('twofa_verified')) {
            return redirect()->route('twofa.form');
        }

        $user = $this->getUserModelClass()::find($userId);
        if (! $user || (string) $user->is_active === '0') {
            Auth::logout();
            $this->clearAuthSession();
            Session::flash('error', 'Your account is inactive. Please contact the administrator.');

            return $this->redirectToLogin();
        }

        Auth::login($user);
        $this->clearAuthSession();

        Session::flash('toast', [
            'type' => 'success',
            'message' => 'Login successful!',
        ]);

        // Clear any stale intended URL pointing to settings/login
        $intendedUrl = session()->get('url.intended');
        if ($intendedUrl && (str_contains($intendedUrl, 'AuthPasswordSetting') || str_contains($intendedUrl, 'auth.settings') || str_contains($intendedUrl, '/login'))) {
            session()->forget('url.intended');
        }

        $homeRoute = config('rbac-suite.routes.dashboard_route', config('rbac-suite.routes.home', 'dashboard'));
        if (! empty($homeRoute) && Route::has($homeRoute)) {
            $dashboardUrl = route($homeRoute, [], false);
            if ($dashboardUrl !== '/' && $dashboardUrl !== '') {
                return redirect()->intended(route($homeRoute));
            }
        }

        if (Route::has('dashboard')) {
            return redirect()->intended(route('dashboard'));
        }

        return redirect()->intended('/dashboard');
    }

    private function getAuthSetting(string $authCode): ?AuthSetting
    {
        if (array_key_exists($authCode, self::$authSettingsCache)) {
            return self::$authSettingsCache[$authCode];
        }

        self::$authSettingsCache[$authCode] = AuthSetting::where('AuthCode', $authCode)
            ->where('IsActive', 1)
            ->first();

        return self::$authSettingsCache[$authCode];
    }

    private function getAttemptLimit(string $authCode): int
    {
        $attemptLimit = (int) optional($this->getAuthSetting($authCode))->OTPAttempt;

        return $attemptLimit > 0 ? $attemptLimit : 3;
    }

    private function getResetMinutes(string $authCode): int
    {
        $minutes = (int) optional($this->getAuthSetting($authCode))->OTPResetTime;

        return $minutes > 0 ? $minutes : 1;
    }

    private function getExpiryMinutes(string $authCode): int
    {
        $minutes = (int) optional($this->getAuthSetting($authCode))->OTPExpiryTime;

        return $minutes > 0 ? $minutes : 10;
    }

    private function getResetSeconds(string $authCode): int
    {
        return $this->getResetMinutes($authCode) * 60;
    }

    private function getExpirySeconds(string $authCode): int
    {
        return $this->getExpiryMinutes($authCode) * 60;
    }

    private function getTwoFactorAttemptDecaySeconds(): int
    {
        return $this->getExpirySeconds(AuthSetting::CODE_TWO_FACTOR);
    }

    private function initializeTwoFactorChallenge(): void
    {
        session([
            self::TWO_FACTOR_CHALLENGE_SESSION_KEY => [
                'issued_at' => now()->timestamp,
                'expires_at' => now()->addSeconds($this->getExpirySeconds(AuthSetting::CODE_TWO_FACTOR))->timestamp,
                'attempt_count' => 0,
            ],
        ]);
    }

    private function isTwoFactorChallengeActive(): bool
    {
        $challenge = session(self::TWO_FACTOR_CHALLENGE_SESSION_KEY);
        if (! is_array($challenge)) {
            return false;
        }

        $expiresAt = $challenge['expires_at'] ?? null;
        if (! is_numeric($expiresAt)) {
            return false;
        }

        return now()->timestamp <= (int) $expiresAt;
    }

    private function getTwoFactorChallengeAttempts(): int
    {
        $challenge = session(self::TWO_FACTOR_CHALLENGE_SESSION_KEY);
        if (! is_array($challenge)) {
            return 0;
        }

        $attempts = $challenge['attempt_count'] ?? 0;

        return is_numeric($attempts) ? max(0, (int) $attempts) : 0;
    }

    private function incrementTwoFactorChallengeAttempts(): int
    {
        $challenge = session(self::TWO_FACTOR_CHALLENGE_SESSION_KEY);
        if (! is_array($challenge)) {
            $this->initializeTwoFactorChallenge();
            $challenge = session(self::TWO_FACTOR_CHALLENGE_SESSION_KEY, []);
        }

        $currentAttempts = $this->getTwoFactorChallengeAttempts();
        $challenge['attempt_count'] = $currentAttempts + 1;
        session([self::TWO_FACTOR_CHALLENGE_SESSION_KEY => $challenge]);

        return (int) $challenge['attempt_count'];
    }

    private function handleMacRestrictionFailure(
        Request $request,
        string $message = 'Login denied. The MAC address does not match an authorized device.'
    ): RedirectResponse|JsonResponse {
        Auth::logout();
        $this->clearAuthSession();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], 403);
        }

        Session::flash('toast', [
            'type' => 'error',
            'message' => $message,
        ]);

        return redirect()->route('login');
    }
}
