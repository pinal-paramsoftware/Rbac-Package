<?php

namespace Parth\LaravelRbacSuite\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Parth\LaravelRbacSuite\Models\AuthProviderSetting;
use Parth\LaravelRbacSuite\Models\AuthSetting;
use Parth\LaravelRbacSuite\Models\PasswordRule;
use Parth\LaravelRbacSuite\Models\UserDevice;
use Parth\LaravelRbacSuite\Models\UserTwoFactor;
use Parth\LaravelRbacSuite\Services\DeviceAuthorizationService;
use Parth\LaravelRbacSuite\Support\CaptchaConfiguration;
use Parth\LaravelRbacSuite\Support\CaptchaSettings;
use Parth\LaravelRbacSuite\Support\EmailOtpMailConfiguration;
use Parth\LaravelRbacSuite\Support\EmailOtpSmtpSettings;
use Parth\LaravelRbacSuite\Support\MobileOtpSmsConfiguration;
use Parth\LaravelRbacSuite\Support\MobileOtpSmsSettings;
use Parth\LaravelRbacSuite\Support\WhatsAppGatewaySettings;
use Parth\LaravelRbacSuite\Support\WhatsAppOtpConfiguration;
use PragmaRX\Google2FA\Google2FA;

class AuthSettingController extends BaseController
{
    public function __construct(
        protected DeviceAuthorizationService $deviceAuthorizationService
    ) {}

    protected function getUserModelClass(): string
    {
        return config('rbac-suite.user_model', 'App\\Models\\User');
    }

    public function index(Request $request)
    {
        $this->authorizeSettingsAccess($request);

        $title = 'Auth-Settings';

        return rbac_view('master.AuthSettings.setting', compact('title'));
    }

    /* =========================
       GET AUTH SETTINGS
    ==========================*/
    public function getAuthenticationSettings(Request $request)
    {
        $this->authorizeSettingsAccess($request);

        AuthSetting::ensureDefaultSettings();

        $settings = AuthSetting::where('IsActive', 1)->get();

        return response()->json(
            $settings->map(function ($s) {
                return [
                    'authSettingId' => $s->AuthSettingId,
                    'authCode' => $s->AuthCode,
                    'isEnabled' => (bool) $s->IsEnabled,
                    'otpattempt' => $s->OTPAttempt,
                    'otpresetTime' => $s->OTPResetTime,
                    'otpexpiryTime' => $s->OTPExpiryTime,
                ];
            })
        );
    }

    public function getSmtpSettings(Request $request)
    {
        $this->authorizeSettingsAccess($request);

        $settings = EmailOtpSmtpSettings::resolve();
        $emailSetting = AuthSetting::where('AuthCode', AuthSetting::CODE_EMAIL_OTP)->first();

        return response()->json([
            'host' => $settings['host'],
            'port' => $settings['port'],
            'username' => $settings['username'],
            'encryption' => $settings['encryption'],
            'from_address' => $settings['from_address'],
            'from_name' => $settings['from_name'],
            'has_password' => $settings['has_password'],
            'authSettingId' => $emailSetting?->AuthSettingId,
            'isEnabled' => (bool) ($emailSetting?->IsEnabled ?? false),
            'otpattempt' => $emailSetting?->OTPAttempt ?? 5,
            'otpresetTime' => $emailSetting?->OTPResetTime ?? 5,
            'otpexpiryTime' => $emailSetting?->OTPExpiryTime ?? 10,
        ]);
    }

    public function updateSmtpSettings(Request $request)
    {
        $this->authorizeSettingsUpdate($request);

        $normalizedData = [
            'host' => trim((string) ($request->input('host') ?? $request->input('smtp_host') ?? $request->input('smtp_server') ?? '')),
            'port' => $request->input('port') ?? $request->input('smtp_port'),
            'username' => trim((string) ($request->input('username') ?? $request->input('smtp_username') ?? '')),
            'password' => $request->input('password') ?? $request->input('smtp_password'),
            'encryption' => strtolower(trim((string) ($request->input('encryption') ?? $request->input('smtp_encryption') ?? 'tls'))),
            'from_address' => trim((string) ($request->input('from_address') ?? $request->input('from_email') ?? $request->input('email') ?? '')),
            'from_name' => trim((string) ($request->input('from_name') ?? $request->input('smtp_from_name') ?? '')),
            'otpattempt' => $request->input('otpattempt') ?? $request->input('Otpattempt') ?? $request->input('email_otp_attempts'),
            'otpresetTime' => $request->input('otpresetTime') ?? $request->input('OtpresetTime') ?? $request->input('email_otp_reset'),
            'otpexpiryTime' => $request->input('otpexpiryTime') ?? $request->input('OtpexpiryTime') ?? $request->input('email_otp_expiry'),
        ];

        if ($normalizedData['encryption'] === '') {
            $normalizedData['encryption'] = 'none';
        }

        $validator = validator($normalizedData, [
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:2048',
            'encryption' => 'required|string|in:tls,ssl,none',
            'from_address' => 'required|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'otpattempt' => 'nullable|integer|min:1|max:10',
            'otpresetTime' => 'nullable|integer|min:1|max:60',
            'otpexpiryTime' => 'nullable|integer|min:1|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => 'Validation error: '.implode(', ', Arr::flatten($validator->errors()->toArray())),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        DB::beginTransaction();
        try {
            $providerSettings = AuthProviderSetting::query()
                ->firstOrNew(['provider_code' => AuthProviderSetting::PROVIDER_SMTP]);

            $existingPassword = (string) ($providerSettings->password ?? '');
            $submittedPassword = (string) ($data['password'] ?? '');

            $resolvedPassword = trim($submittedPassword) !== ''
                ? $submittedPassword
                : $existingPassword;

            $hasHostCol = Schema::hasColumn('auth_provider_settings', 'host');
            $hasPortCol = Schema::hasColumn('auth_provider_settings', 'port');
            $hasEncCol = Schema::hasColumn('auth_provider_settings', 'encryption');
            $hasFromCol = Schema::hasColumn('auth_provider_settings', 'from_address');
            $hasNameCol = Schema::hasColumn('auth_provider_settings', 'from_name');

            $attributes = [
                'provider_code' => AuthProviderSetting::PROVIDER_SMTP,
                'url' => $data['host'],
                'username' => $data['username'] !== '' ? $data['username'] : null,
                'password' => $resolvedPassword !== '' ? $resolvedPassword : null,
                'sender_id' => $data['from_address'],
                'template' => $data['from_name'] !== '' ? $data['from_name'] : null,
            ];

            if ($hasHostCol) {
                $attributes['host'] = $data['host'];
            }
            if ($hasPortCol) {
                $attributes['port'] = (int) $data['port'];
            }
            if ($hasEncCol) {
                $attributes['encryption'] = $data['encryption'];
            }
            if ($hasFromCol) {
                $attributes['from_address'] = $data['from_address'];
            }
            if ($hasNameCol) {
                $attributes['from_name'] = $data['from_name'] !== '' ? $data['from_name'] : null;
            }

            $providerSettings->fill($attributes);
            $providerSettings->save();

            $emailSetting = AuthSetting::where('AuthCode', AuthSetting::CODE_EMAIL_OTP)->first();
            if ($emailSetting) {
                $dirtyEmail = false;
                if (isset($data['otpattempt']) && $data['otpattempt'] !== null) {
                    $emailSetting->OTPAttempt = (int) $data['otpattempt'];
                    $dirtyEmail = true;
                }
                if (isset($data['otpresetTime']) && $data['otpresetTime'] !== null) {
                    $emailSetting->OTPResetTime = (int) $data['otpresetTime'];
                    $dirtyEmail = true;
                }
                if (isset($data['otpexpiryTime']) && $data['otpexpiryTime'] !== null) {
                    $emailSetting->OTPExpiryTime = (int) $data['otpexpiryTime'];
                    $dirtyEmail = true;
                }
                if ($dirtyEmail) {
                    $emailSetting->UpdatedOn = Carbon::now();
                    $emailSetting->save();
                }
            }

            DB::commit();

            EmailOtpSmtpSettings::apply();
            AuthSetting::clearCache();

            return response()->json([
                'result' => true,
                'message' => 'SMTP settings saved successfully.',
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('Error saving SMTP settings', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'result' => false,
                'message' => 'Unable to save SMTP settings. Please try again.',
            ], 500);
        }
    }

    public function updateEmailSettings(Request $request)
    {
        return $this->updateSmtpSettings($request);
    }

    public function sendTestEmail(Request $request)
    {
        $this->authorizeSettingsUpdate($request);

        $recipientEmail = trim((string) ($request->input('recipient_email') ?? $request->input('email') ?? ''));

        $validator = validator(
            ['recipient_email' => $recipientEmail],
            ['recipient_email' => 'required|email|max:255']
        );

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => 'Validation error: '.implode(', ', Arr::flatten($validator->errors()->toArray())),
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! EmailOtpSmtpSettings::isConfigured()) {
            return response()->json([
                'result' => false,
                'message' => 'SMTP configuration is incomplete or not saved yet. Please configure and save SMTP settings first.',
            ], 422);
        }

        $savedSettings = EmailOtpSmtpSettings::resolve();

        $submittedHost = $request->input('host') ?? $request->input('smtp_host');
        $submittedPort = $request->input('port') ?? $request->input('smtp_port');
        $submittedFromAddress = $request->input('from_address') ?? $request->input('from_email');
        $submittedUsername = $request->input('username') ?? $request->input('smtp_username');
        $submittedEncryption = $request->input('encryption') ?? $request->input('smtp_encryption');
        $submittedFromName = $request->input('from_name') ?? $request->input('smtp_from_name');
        $submittedPassword = $request->input('password') ?? $request->input('smtp_password');

        if (
            $submittedHost !== null ||
            $submittedPort !== null ||
            $submittedFromAddress !== null ||
            $submittedUsername !== null ||
            $submittedEncryption !== null ||
            $submittedFromName !== null ||
            ! empty($submittedPassword)
        ) {
            $hasChanges = false;
            if ($submittedHost !== null && trim((string) $submittedHost) !== (string) $savedSettings['host']) {
                $hasChanges = true;
            }
            if ($submittedPort !== null && (int) $submittedPort !== (int) $savedSettings['port']) {
                $hasChanges = true;
            }
            if ($submittedFromAddress !== null && trim((string) $submittedFromAddress) !== (string) $savedSettings['from_address']) {
                $hasChanges = true;
            }
            if ($submittedUsername !== null && trim((string) $submittedUsername) !== (string) $savedSettings['username']) {
                $hasChanges = true;
            }
            if ($submittedEncryption !== null && strtolower(trim((string) $submittedEncryption)) !== strtolower((string) ($savedSettings['encryption'] ?? 'tls'))) {
                $hasChanges = true;
            }
            if ($submittedFromName !== null && trim((string) $submittedFromName) !== (string) ($savedSettings['from_name'] ?? '')) {
                $hasChanges = true;
            }
            if (! empty($submittedPassword)) {
                $hasChanges = true;
            }

            if ($hasChanges) {
                return response()->json([
                    'result' => false,
                    'message' => 'You have unsaved SMTP configuration changes. Please save the settings before sending a test email.',
                ], 422);
            }
        }

        try {
            EmailOtpSmtpSettings::apply();

            $fromAddress = (string) config('mail.from.address');
            $fromName = (string) config('mail.from.name');

            $emailView = rbac_view_name('emails.smtp-test');
            Mail::send($emailView, [], function ($message) use ($recipientEmail, $fromAddress, $fromName): void {
                if ($fromAddress !== '' && method_exists($message, 'from')) {
                    $message->from($fromAddress, $fromName !== '' ? $fromName : null);
                }
                $message->to($recipientEmail)
                    ->subject('SMTP Configuration Test Email');
            });

            return response()->json([
                'result' => true,
                'message' => 'Test email sent successfully to the specified email address.',
            ]);
        } catch (\Throwable $exception) {
            Log::error('SMTP test email sending failed', [
                'error' => $exception->getMessage(),
            ]);

            $userMessage = 'Failed to send test email due to mail server error. Please verify your SMTP settings and connection.';
            $msg = $exception->getMessage();
            if (stripos($msg, 'authenticate') !== false || stripos($msg, 'auth') !== false || stripos($msg, 'password') !== false || stripos($msg, 'credential') !== false) {
                $userMessage = 'Failed to send test email: SMTP authentication failed. Please check your username and password.';
            } elseif (stripos($msg, 'connect') !== false || stripos($msg, 'refused') !== false || stripos($msg, 'timed out') !== false || stripos($msg, 'timeout') !== false) {
                $userMessage = 'Failed to send test email: Unable to connect to the SMTP server. Please verify the host and port.';
            }

            return response()->json([
                'result' => false,
                'message' => $userMessage,
            ], 500);
        }
    }

    public function getWhatsAppGatewaySettings(Request $request)
    {
        $this->authorizeSettingsAccess($request);

        $settings = WhatsAppGatewaySettings::resolve();
        $whatsAppSetting = AuthSetting::where('AuthCode', AuthSetting::CODE_WHATSAPP_OTP)->first();

        return response()->json([
            'url' => $settings['url'],
            'username' => $settings['username'],
            'sender_id' => $settings['sender_id'],
            'template' => $settings['template'],
            'timeout' => $settings['timeout'],
            'verify_ssl' => $settings['verify_ssl'],
            'has_token' => $settings['has_token'],
            'has_password' => $settings['has_password'],
            'authSettingId' => $whatsAppSetting?->AuthSettingId,
            'isEnabled' => (bool) ($whatsAppSetting?->IsEnabled ?? false),
            'otpattempt' => $whatsAppSetting?->OTPAttempt ?? 5,
            'otpresetTime' => $whatsAppSetting?->OTPResetTime ?? 5,
            'otpexpiryTime' => $whatsAppSetting?->OTPExpiryTime ?? 10,
        ]);
    }

    public function updateWhatsAppGatewaySettings(Request $request)
    {
        $this->authorizeSettingsUpdate($request);

        $normalizedInput = array_merge($request->all(), [
            'otpattempt' => $request->input('otpattempt') ?? $request->input('Otpattempt') ?? $request->input('whatsapp_otp_attempts'),
            'otpresetTime' => $request->input('otpresetTime') ?? $request->input('OtpresetTime') ?? $request->input('whatsapp_otp_reset'),
            'otpexpiryTime' => $request->input('otpexpiryTime') ?? $request->input('OtpexpiryTime') ?? $request->input('whatsapp_otp_expiry'),
        ]);

        $data = validator($normalizedInput, [
            'url' => 'required|url|max:2048',
            'token' => 'nullable|string|max:2048',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:2048',
            'sender_id' => 'required|string|max:255',
            'template' => 'required|string|max:255',
            'timeout' => 'required|integer|min:1|max:120',
            'verify_ssl' => 'required|boolean',
            'otpattempt' => 'nullable|integer|min:1|max:10',
            'otpresetTime' => 'nullable|integer|min:1|max:60',
            'otpexpiryTime' => 'nullable|integer|min:1|max:120',
        ])->validate();

        $providerSettings = AuthProviderSetting::query()
            ->firstOrNew(['provider_code' => AuthProviderSetting::PROVIDER_WHATSAPP]);

        $existingToken = (string) ($providerSettings->token ?? '');
        $existingPassword = (string) ($providerSettings->password ?? '');

        if ($existingToken === '') {
            $existingToken = trim((string) config('services.whatsapp_gateway.token'));
        }
        if ($existingPassword === '') {
            $existingPassword = (string) config('services.whatsapp_gateway.password');
        }

        $resolvedToken = trim((string) ($data['token'] ?? '')) !== ''
            ? trim((string) $data['token'])
            : $existingToken;

        $resolvedUsername = trim((string) ($data['username'] ?? ''));
        if ($resolvedUsername === '' && $providerSettings->username) {
            $resolvedUsername = (string) $providerSettings->username;
        }

        $resolvedPassword = trim((string) ($data['password'] ?? '')) !== ''
            ? (string) $data['password']
            : $existingPassword;

        if ($resolvedToken === '' && ($resolvedUsername === '' || $resolvedPassword === '')) {
            return response()->json([
                'result' => false,
                'message' => 'Provide either WhatsApp token or username/password.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $providerSettings->fill([
                'url' => trim((string) $data['url']),
                'token' => $resolvedToken,
                'username' => $resolvedUsername !== '' ? $resolvedUsername : null,
                'password' => $resolvedPassword !== '' ? $resolvedPassword : null,
                'sender_id' => trim((string) $data['sender_id']),
                'template' => trim((string) $data['template']),
                'timeout' => (int) $data['timeout'],
                'verify_ssl' => (bool) $data['verify_ssl'],
            ]);
            $providerSettings->save();

            $whatsAppSetting = AuthSetting::where('AuthCode', AuthSetting::CODE_WHATSAPP_OTP)->first();
            if ($whatsAppSetting) {
                $dirtyWhatsApp = false;
                if (isset($data['otpattempt']) && $data['otpattempt'] !== null) {
                    $whatsAppSetting->OTPAttempt = (int) $data['otpattempt'];
                    $dirtyWhatsApp = true;
                }
                if (isset($data['otpresetTime']) && $data['otpresetTime'] !== null) {
                    $whatsAppSetting->OTPResetTime = (int) $data['otpresetTime'];
                    $dirtyWhatsApp = true;
                }
                if (isset($data['otpexpiryTime']) && $data['otpexpiryTime'] !== null) {
                    $whatsAppSetting->OTPExpiryTime = (int) $data['otpexpiryTime'];
                    $dirtyWhatsApp = true;
                }
                if ($dirtyWhatsApp) {
                    $whatsAppSetting->UpdatedOn = Carbon::now();
                    $whatsAppSetting->save();
                }
            }

            DB::commit();

            WhatsAppGatewaySettings::apply();
            AuthSetting::clearCache();

            return response()->json([
                'result' => true,
                'message' => 'WhatsApp gateway settings saved successfully.',
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('Unable to save WhatsApp gateway settings', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'result' => false,
                'message' => 'Unable to save WhatsApp gateway settings.',
            ], 500);
        }
    }

    public function getMobileOtpGatewaySettings(Request $request)
    {
        $this->authorizeSettingsAccess($request);

        $settings = MobileOtpSmsSettings::resolve();
        $mobileSetting = AuthSetting::where('AuthCode', AuthSetting::CODE_MOBILE_OTP)->first();

        return response()->json([
            'url' => $settings['url'],
            'username' => $settings['username'],
            'sender_id' => $settings['sender_id'],
            'template' => $settings['template'],
            'timeout' => $settings['timeout'],
            'verify_ssl' => $settings['verify_ssl'],
            'has_token' => $settings['has_token'],
            'has_password' => $settings['has_password'],
            'authSettingId' => $mobileSetting?->AuthSettingId,
            'isEnabled' => (bool) ($mobileSetting?->IsEnabled ?? false),
            'otpattempt' => $mobileSetting?->OTPAttempt ?? 5,
            'otpresetTime' => $mobileSetting?->OTPResetTime ?? 5,
            'otpexpiryTime' => $mobileSetting?->OTPExpiryTime ?? 10,
        ]);
    }

    public function updateMobileOtpGatewaySettings(Request $request)
    {
        $this->authorizeSettingsUpdate($request);

        $normalizedInput = array_merge($request->all(), [
            'otpattempt' => $request->input('otpattempt') ?? $request->input('Otpattempt') ?? $request->input('mobile_otp_attempts'),
            'otpresetTime' => $request->input('otpresetTime') ?? $request->input('OtpresetTime') ?? $request->input('mobile_otp_reset'),
            'otpexpiryTime' => $request->input('otpexpiryTime') ?? $request->input('OtpexpiryTime') ?? $request->input('mobile_otp_expiry'),
        ]);

        $data = validator($normalizedInput, [
            'url' => 'required|url|max:2048',
            'token' => 'nullable|string|max:2048',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:2048',
            'sender_id' => 'nullable|string|max:255',
            'template' => 'nullable|string|max:255',
            'timeout' => 'required|integer|min:1|max:120',
            'verify_ssl' => 'required|boolean',
            'otpattempt' => 'nullable|integer|min:1|max:10',
            'otpresetTime' => 'nullable|integer|min:1|max:60',
            'otpexpiryTime' => 'nullable|integer|min:1|max:120',
        ])->validate();

        $providerSettings = AuthProviderSetting::query()
            ->firstOrNew(['provider_code' => AuthProviderSetting::PROVIDER_SMS]);

        $existingToken = (string) ($providerSettings->token ?? '');
        $existingPassword = (string) ($providerSettings->password ?? '');

        if ($existingToken === '') {
            $existingToken = trim((string) config('services.sms_gateway.token'));
        }
        if ($existingPassword === '') {
            $existingPassword = (string) config('services.sms_gateway.password');
        }

        $resolvedToken = trim((string) ($data['token'] ?? '')) !== ''
            ? trim((string) $data['token'])
            : $existingToken;

        $resolvedUsername = trim((string) ($data['username'] ?? ''));
        if ($resolvedUsername === '' && $providerSettings->username) {
            $resolvedUsername = (string) $providerSettings->username;
        }

        $resolvedPassword = trim((string) ($data['password'] ?? '')) !== ''
            ? (string) $data['password']
            : $existingPassword;

        if ($resolvedToken === '' && ($resolvedUsername === '' || $resolvedPassword === '')) {
            return response()->json([
                'result' => false,
                'message' => 'Provide either SMS gateway token or username/password credentials.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $providerSettings->fill([
                'url' => trim((string) $data['url']),
                'token' => $resolvedToken,
                'username' => $resolvedUsername !== '' ? $resolvedUsername : null,
                'password' => $resolvedPassword !== '' ? $resolvedPassword : null,
                'sender_id' => trim((string) ($data['sender_id'] ?? '')) !== '' ? trim((string) $data['sender_id']) : null,
                'template' => trim((string) ($data['template'] ?? '')) !== '' ? trim((string) $data['template']) : null,
                'timeout' => (int) $data['timeout'],
                'verify_ssl' => (bool) $data['verify_ssl'],
            ]);
            $providerSettings->save();

            $mobileSetting = AuthSetting::where('AuthCode', AuthSetting::CODE_MOBILE_OTP)->first();
            if ($mobileSetting) {
                $dirtyMobile = false;
                if (isset($data['otpattempt']) && $data['otpattempt'] !== null) {
                    $mobileSetting->OTPAttempt = (int) $data['otpattempt'];
                    $dirtyMobile = true;
                }
                if (isset($data['otpresetTime']) && $data['otpresetTime'] !== null) {
                    $mobileSetting->OTPResetTime = (int) $data['otpresetTime'];
                    $dirtyMobile = true;
                }
                if (isset($data['otpexpiryTime']) && $data['otpexpiryTime'] !== null) {
                    $mobileSetting->OTPExpiryTime = (int) $data['otpexpiryTime'];
                    $dirtyMobile = true;
                }
                if ($dirtyMobile) {
                    $mobileSetting->UpdatedOn = Carbon::now();
                    $mobileSetting->save();
                }
            }

            DB::commit();

            MobileOtpSmsSettings::apply();
            AuthSetting::clearCache();

            return response()->json([
                'result' => true,
                'message' => 'Mobile OTP SMS gateway settings saved successfully.',
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('Unable to save Mobile OTP SMS gateway settings', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'result' => false,
                'message' => 'Unable to save Mobile OTP SMS gateway settings.',
            ], 500);
        }
    }

    public function getSmsGatewaySettings(Request $request)
    {
        return $this->getMobileOtpGatewaySettings($request);
    }

    public function updateSmsGatewaySettings(Request $request)
    {
        return $this->updateMobileOtpGatewaySettings($request);
    }

    public function getCaptchaSettings(Request $request)
    {
        $this->authorizeSettingsAccess($request);

        $settings = CaptchaSettings::resolve();

        return response()->json([
            'url' => $settings['url'],
            'site_key' => $settings['site_key'],
            'timeout' => $settings['timeout'],
            'verify_ssl' => $settings['verify_ssl'],
            'has_secret' => $settings['has_secret'],
            'has_token' => $settings['has_secret'],
        ]);
    }

    public function updateCaptchaSettings(Request $request)
    {
        $this->authorizeSettingsUpdate($request);

        $data = $request->validate([
            'url' => 'nullable|url|max:2048',
            'site_key' => 'nullable|string|max:255',
            'secret' => 'nullable|string|max:2048',
            'token' => 'nullable|string|max:2048',
            'timeout' => 'nullable|integer|min:1|max:120',
            'verify_ssl' => 'nullable|boolean',
        ]);

        $providerSettings = AuthProviderSetting::query()
            ->firstOrNew(['provider_code' => AuthProviderSetting::PROVIDER_CAPTCHA]);

        $existingSecret = (string) ($providerSettings->token ?? $providerSettings->password ?? '');
        if ($existingSecret === '') {
            $existingSecret = (string) config('services.captcha.secret');
        }

        $submittedSecret = trim((string) ($data['secret'] ?? $data['token'] ?? ''));
        $resolvedSecret = $submittedSecret !== '' ? $submittedSecret : $existingSecret;
        $siteKey = trim((string) ($data['site_key'] ?? $providerSettings->username ?? config('services.captcha.site_key') ?? ''));

        DB::beginTransaction();
        try {
            $providerSettings->fill([
                'provider_code' => AuthProviderSetting::PROVIDER_CAPTCHA,
                'url' => trim((string) ($data['url'] ?? $providerSettings->url ?? '')),
                'username' => $siteKey !== '' ? $siteKey : null,
                'sender_id' => $siteKey !== '' ? $siteKey : null,
                'token' => $resolvedSecret !== '' ? $resolvedSecret : null,
                'password' => $resolvedSecret !== '' ? $resolvedSecret : null,
                'timeout' => isset($data['timeout']) ? (int) $data['timeout'] : ($providerSettings->timeout ?? 10),
                'verify_ssl' => isset($data['verify_ssl']) ? (bool) $data['verify_ssl'] : ($providerSettings->verify_ssl ?? true),
            ]);
            $providerSettings->save();

            DB::commit();

            CaptchaSettings::apply();
            AuthSetting::clearCache();

            return response()->json([
                'result' => true,
                'message' => 'Captcha settings saved successfully.',
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('Error saving Captcha settings', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'result' => false,
                'message' => 'Unable to save Captcha settings.',
            ], 500);
        }
    }

    public function getTwoFactorSettings(Request $request)
    {
        $this->authorizeSettingsAccess($request);

        AuthSetting::ensureDefaultSettings();

        $setting = AuthSetting::query()
            ->where('AuthCode', AuthSetting::CODE_TWO_FACTOR)
            ->where('IsActive', 1)
            ->first();

        $twoFactorProfile = $this->resolveTwoFactorProfile();
        $secret = (string) ($twoFactorProfile?->google2fa_secret ?? $twoFactorProfile?->secret_key ?? '');

        return response()->json([
            'authSettingId' => $setting?->AuthSettingId,
            'isEnabled' => (bool) ($setting?->IsEnabled ?? false),
            'otpattempt' => $setting?->OTPAttempt,
            'otpexpiryTime' => $setting?->OTPExpiryTime,
            'google2fa_enabled' => (bool) ($twoFactorProfile?->google2fa_enabled ?? $twoFactorProfile?->is_enabled ?? false),
            'has_secret' => $secret !== '',
            'has_google2fa_secret' => $secret !== '',
            'totalUsers' => $this->getUserModelClass()::query()->count(),
            'configuredUsers' => UserTwoFactor::query()->where('is_enabled', 1)->count(),
            'activeRows' => UserTwoFactor::query()->where('is_active', 1)->count(),
        ]);
    }

    public function updateTwoFactorSettings(Request $request)
    {
        $this->authorizeSettingsUpdate($request);

        AuthSetting::ensureDefaultSettings();

        $settingId = $request->input('AuthSettingId') ?? $request->input('authSettingId');
        if (! $settingId) {
            $settingId = AuthSetting::where('AuthCode', AuthSetting::CODE_TWO_FACTOR)->value('AuthSettingId');
        }

        $normalized = array_merge($request->all(), [
            'AuthSettingId' => $settingId,
            'Otpattempt' => $request->input('Otpattempt') ?? $request->input('otpattempt'),
            'OtpexpiryTime' => $request->input('OtpexpiryTime') ?? $request->input('otpexpiryTime'),
        ]);

        $data = validator($normalized, [
            'AuthSettingId' => 'required|integer',
            'IsEnabled' => 'required|boolean',
            'Otpattempt' => 'nullable|integer|min:1|max:10',
            'OtpexpiryTime' => 'nullable|integer|min:1|max:120',
            'google2fa_enabled' => 'nullable|boolean',
            'google2fa_secret' => 'nullable|string|min:16|max:255',
        ])->validate();

        if ((bool) $data['IsEnabled'] && (($data['Otpattempt'] ?? null) === null || ($data['OtpexpiryTime'] ?? null) === null)) {
            return response()->json([
                'result' => false,
                'message' => '2FA attempts and expiry time are required when Two-Factor Authentication is enabled.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $setting = AuthSetting::find($data['AuthSettingId']);
            if (! $setting) {
                $setting = AuthSetting::where('AuthCode', AuthSetting::CODE_TWO_FACTOR)->first();
            }

            if ($setting) {
                $setting->fill([
                    'IsEnabled' => $data['IsEnabled'] ? 1 : 0,
                    'OTPAttempt' => $data['Otpattempt'] ?? $setting->OTPAttempt,
                    'OTPExpiryTime' => $data['OtpexpiryTime'] ?? $setting->OTPExpiryTime ?? 10,
                    'UpdatedOn' => Carbon::now(),
                ]);
                $setting->save();
            } else {
                AuthSetting::create([
                    'AuthCode' => AuthSetting::CODE_TWO_FACTOR,
                    'AuthName' => 'Two-Factor Authentication',
                    'IsEnabled' => $data['IsEnabled'] ? 1 : 0,
                    'OTPAttempt' => $data['Otpattempt'] ?? 5,
                    'OTPResetTime' => null,
                    'OTPExpiryTime' => $data['OtpexpiryTime'] ?? 10,
                    'CreatedOn' => Carbon::now(),
                    'UpdatedOn' => Carbon::now(),
                    'IsActive' => 1,
                ]);
            }

            $this->syncUserTwoFactorRows((bool) $data['IsEnabled']);
            $this->saveTwoFactorProfileSettings($data, (bool) $data['IsEnabled']);

            DB::commit();

            AuthSetting::clearCache();

            return response()->json([
                'result' => true,
                'message' => 'Two-factor settings updated successfully.',
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('Unable to update two-factor settings', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'result' => false,
                'message' => 'Unable to update two-factor settings.',
            ], 500);
        }
    }

    /* =========================
       UPDATE AUTH SETTINGS
    ==========================*/
    public function updateAuthenticationSettings(Request $request)
    {
        $this->authorizeSettingsUpdate($request);

        AuthSetting::ensureDefaultSettings();

        $validAuthCodes = collect(AuthSetting::defaultSettings())
            ->pluck('AuthCode')
            ->all();

        $rawInput = $request->all();
        if (isset($rawInput['settings']) && is_array($rawInput['settings'])) {
            $rawInput = $rawInput['settings'];
        }
        if (Arr::isAssoc($rawInput) && (isset($rawInput['AuthCode']) || isset($rawInput['authCode']))) {
            $rawInput = [$rawInput];
        }

        $normalizedInput = [];
        if (is_array($rawInput)) {
            foreach ($rawInput as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $normalizedInput[] = [
                    'AuthSettingId' => $item['AuthSettingId'] ?? $item['authSettingId'] ?? null,
                    'AuthCode' => $item['AuthCode'] ?? $item['authCode'] ?? null,
                    'IsEnabled' => $item['IsEnabled'] ?? $item['isEnabled'] ?? null,
                    'Otpattempt' => $item['OTPAttempt'] ?? $item['Otpattempt'] ?? $item['otpattempt'] ?? null,
                    'OtpresetTime' => $item['OTPResetTime'] ?? $item['OtpresetTime'] ?? $item['otpresetTime'] ?? null,
                    'OtpexpiryTime' => $item['OTPExpiryTime'] ?? $item['OtpexpiryTime'] ?? $item['otpexpiryTime'] ?? null,
                ];
            }
        }

        $validator = validator($normalizedInput, [
            '*.AuthSettingId' => 'nullable|integer',
            '*.AuthCode' => 'required|string|in:'.implode(',', $validAuthCodes),
            '*.IsEnabled' => 'required|boolean',
            '*.Otpattempt' => 'nullable|integer|min:1|max:10',
            '*.OtpresetTime' => 'nullable|integer|min:1|max:60',
            '*.OtpexpiryTime' => 'nullable|integer|min:1|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => 'Validation error: '.implode(', ', Arr::flatten($validator->errors()->toArray())),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (empty($data)) {
            return response()->json([
                'result' => false,
                'message' => 'No authentication settings were provided.',
                'errors' => [
                    'settings' => ['No authentication settings were provided.'],
                ],
            ], 422);
        }

        // Verify that at least one authentication method remains enabled
        $loginMethods = [
            AuthSetting::CODE_CAPTCHA,
            AuthSetting::CODE_EMAIL_OTP,
            AuthSetting::CODE_MOBILE_OTP,
            AuthSetting::CODE_WHATSAPP_OTP,
            AuthSetting::CODE_TWO_FACTOR,
            AuthSetting::CODE_MAC_FUNCTIONALITY,
        ];

        $submittedLoginMethods = collect($data)->whereIn('AuthCode', $loginMethods);
        if ($submittedLoginMethods->isNotEmpty()) {
            $anySubmittedLoginEnabled = $submittedLoginMethods->contains(fn ($item) => (bool) $item['IsEnabled']);
            if (! $anySubmittedLoginEnabled) {
                $submittedCodes = collect($data)->pluck('AuthCode')->all();
                $existingEnabledOther = AuthSetting::query()
                    ->where('IsActive', 1)
                    ->where('IsEnabled', 1)
                    ->whereIn('AuthCode', $loginMethods)
                    ->whereNotIn('AuthCode', $submittedCodes)
                    ->exists();

                if (! $existingEnabledOther) {
                    return response()->json([
                        'result' => false,
                        'message' => 'At least one authentication method must be enabled.',
                        'errors' => [
                            'settings' => ['At least one authentication method must be enabled.'],
                        ],
                    ], 422);
                }
            }
        }

        foreach ($data as $item) {
            $authCode = $item['AuthCode'];
            $isEnabled = (bool) $item['IsEnabled'];

            $isOtpMethod = in_array($authCode, [
                AuthSetting::CODE_EMAIL_OTP,
                AuthSetting::CODE_MOBILE_OTP,
                AuthSetting::CODE_WHATSAPP_OTP,
            ], true);

            if ($isOtpMethod && $isEnabled) {
                $existing = AuthSetting::where('AuthCode', $authCode)->first();
                $attemptCheck = $item['Otpattempt'] ?? $existing?->OTPAttempt;
                $resetCheck = $item['OtpresetTime'] ?? $existing?->OTPResetTime;
                $expiryCheck = $item['OtpexpiryTime'] ?? $existing?->OTPExpiryTime;

                if ($attemptCheck === null || $resetCheck === null || $expiryCheck === null) {
                    return response()->json([
                        'result' => false,
                        'message' => 'OTP attempt, resend interval, and expiry time are required for enabled OTP methods.',
                        'errors' => [
                            'settings' => ['OTP attempt, resend interval, and expiry time are required for enabled OTP methods.'],
                        ],
                    ], 422);
                }
            }

            if ($authCode === AuthSetting::CODE_CAPTCHA && $isEnabled) {
                $captchaValidation = CaptchaConfiguration::validate();
                if (! $captchaValidation['valid']) {
                    return response()->json([
                        'result' => false,
                        'message' => $captchaValidation['message'],
                        'errors' => [
                            'settings' => [$captchaValidation['message']],
                        ],
                    ], 422);
                }
            }

            if ($authCode === AuthSetting::CODE_EMAIL_OTP && $isEnabled) {
                $mailConfigValidation = EmailOtpMailConfiguration::validate();
                if (! $mailConfigValidation['valid']) {
                    return response()->json([
                        'result' => false,
                        'message' => $mailConfigValidation['message'],
                        'errors' => [
                            'settings' => [$mailConfigValidation['message']],
                        ],
                    ], 422);
                }
            }

            if ($authCode === AuthSetting::CODE_MOBILE_OTP && $isEnabled) {
                $mobileConfigValidation = MobileOtpSmsConfiguration::validate();
                if (! $mobileConfigValidation['valid']) {
                    return response()->json([
                        'result' => false,
                        'message' => $mobileConfigValidation['message'],
                        'errors' => [
                            'settings' => [$mobileConfigValidation['message']],
                        ],
                    ], 422);
                }
            }

            if ($authCode === AuthSetting::CODE_WHATSAPP_OTP && $isEnabled) {
                $whatsAppConfigValidation = WhatsAppOtpConfiguration::validate();
                if (! $whatsAppConfigValidation['valid']) {
                    return response()->json([
                        'result' => false,
                        'message' => $whatsAppConfigValidation['message'],
                        'errors' => [
                            'settings' => [$whatsAppConfigValidation['message']],
                        ],
                    ], 422);
                }
            }

            if ($authCode === AuthSetting::CODE_TWO_FACTOR && $isEnabled) {
                $attempt = $item['Otpattempt'] ?? null;
                $expiry = $item['OtpexpiryTime'] ?? null;

                if ($attempt === null || $expiry === null) {
                    $existing = AuthSetting::where('AuthCode', AuthSetting::CODE_TWO_FACTOR)->first();
                    $attempt ??= $existing?->OTPAttempt;
                    $expiry ??= $existing?->OTPExpiryTime;
                }

                if ($attempt === null || $expiry === null) {
                    return response()->json([
                        'result' => false,
                        'message' => '2FA attempts and expiry time are required when Two-Factor Authentication is enabled.',
                        'errors' => [
                            'settings' => ['2FA attempts and expiry time are required when Two-Factor Authentication is enabled.'],
                        ],
                    ], 422);
                }
            }
        }

        DB::beginTransaction();
        try {
            foreach ($data as $item) {
                $authCode = $item['AuthCode'];
                $setting = null;
                if (! empty($item['AuthSettingId'])) {
                    $setting = AuthSetting::find($item['AuthSettingId']);
                }
                if (! $setting) {
                    $setting = AuthSetting::where('AuthCode', $authCode)->first();
                }

                $attempt = $item['Otpattempt'] ?? null;
                $reset = $item['OtpresetTime'] ?? null;
                $expiry = $item['OtpexpiryTime'] ?? null;

                if ($authCode === AuthSetting::CODE_TWO_FACTOR && $expiry === null && $setting) {
                    $expiry = $setting->OTPExpiryTime ?? 10;
                }

                if ($setting) {
                    $updateAttributes = [
                        'IsEnabled' => $item['IsEnabled'] ? 1 : 0,
                        'UpdatedOn' => Carbon::now(),
                    ];
                    if (array_key_exists('Otpattempt', $item) && $item['Otpattempt'] !== null) {
                        $updateAttributes['OTPAttempt'] = $attempt;
                    }
                    if (array_key_exists('OtpresetTime', $item) && $item['OtpresetTime'] !== null) {
                        $updateAttributes['OTPResetTime'] = $reset;
                    }
                    if (array_key_exists('OtpexpiryTime', $item) && $item['OtpexpiryTime'] !== null) {
                        $updateAttributes['OTPExpiryTime'] = $expiry;
                    }
                    $setting->fill($updateAttributes);
                    $setting->save();
                } else {
                    $authName = $authCode === AuthSetting::CODE_REGISTRATION ? 'Signup / Registration' : $authCode;
                    AuthSetting::create([
                        'AuthCode' => $authCode,
                        'AuthName' => $authName,
                        'IsEnabled' => $item['IsEnabled'] ? 1 : 0,
                        'OTPAttempt' => $attempt,
                        'OTPResetTime' => $reset,
                        'OTPExpiryTime' => $expiry,
                        'CreatedOn' => Carbon::now(),
                        'UpdatedOn' => Carbon::now(),
                        'IsActive' => 1,
                    ]);
                }
            }

            $twoFactorSetting = collect($data)
                ->firstWhere('AuthCode', AuthSetting::CODE_TWO_FACTOR);

            if (is_array($twoFactorSetting)) {
                $this->syncUserTwoFactorRows((bool) $twoFactorSetting['IsEnabled']);
            }

            DB::commit();

            AuthSetting::clearCache();

            return response()->json([
                'result' => true,
                'message' => 'Authentication settings updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'result' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function syncUserTwoFactorRows(bool $isEnabled): void
    {
        $now = Carbon::now();

        $this->getUserModelClass()::query()
            ->select('id')
            ->orderBy('id')
            ->chunk(200, function ($users) use ($isEnabled, $now): void {
                foreach ($users as $user) {
                    UserTwoFactor::query()->updateOrCreate(
                        ['emp_id' => $user->id],
                        [
                            'user_id' => $user->id,
                            'is_active' => $isEnabled ? 1 : 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            });
    }

    private function saveTwoFactorProfileSettings(array $data, bool $isTwoFactorGloballyEnabled): void
    {
        $google2faEnabled = array_key_exists('google2fa_enabled', $data)
            ? (bool) $data['google2fa_enabled']
            : $isTwoFactorGloballyEnabled;

        $targetUserId = Auth::id();
        if (! $targetUserId) {
            $targetUserId = (int) $this->getUserModelClass()::query()->orderBy('id')->value('id');
        }

        if ($targetUserId <= 0) {
            return;
        }

        $existing = UserTwoFactor::query()
            ->where('emp_id', $targetUserId)
            ->orWhere('user_id', $targetUserId)
            ->first();

        if (! $existing) {
            $existing = $this->resolveTwoFactorProfile();
        }

        $existingSecret = (string) ($existing?->google2fa_secret ?? $existing?->secret_key ?? '');

        // If a secret was explicitly provided in the request, use it.
        // Otherwise, ALWAYS preserve the existing secret so it is never overwritten or lost.
        $submittedSecret = trim((string) ($data['google2fa_secret'] ?? ''));
        if ($submittedSecret !== '') {
            $secret = $submittedSecret;
        } elseif ($existingSecret !== '') {
            $secret = $existingSecret;
        } elseif ($google2faEnabled) {
            $secret = app(Google2FA::class)->generateSecretKey();
        } else {
            $secret = null;
        }

        UserTwoFactor::query()->updateOrCreate(
            ['emp_id' => $targetUserId],
            [
                'user_id' => $targetUserId,
                'is_active' => $isTwoFactorGloballyEnabled ? 1 : 0,
                'is_enabled' => $google2faEnabled ? 1 : 0,
                'google2fa_enabled' => $google2faEnabled ? 1 : 0,
                'secret_key' => $secret,
                'google2fa_secret' => $secret,
                'updated_at' => Carbon::now(),
            ]
        );
    }

    private function resolveTwoFactorProfile(): ?UserTwoFactor
    {
        $userId = Auth::id();
        if ($userId) {
            $profile = UserTwoFactor::query()
                ->where('emp_id', $userId)
                ->orWhere('user_id', $userId)
                ->first();
            if ($profile) {
                return $profile;
            }
        }

        return UserTwoFactor::query()->orderBy('id')->first();
    }

    /* =========================
       GET PASSWORD RULES
    ==========================*/
    public function getPasswordRules(Request $request)
    {
        $this->authorizeSettingsAccess($request);

        PasswordRule::ensureDefaultRules();

        $rules = PasswordRule::where('IsActive', 1)->get();

        return response()->json(
            $rules->map(function ($r) {
                return [
                    'ruleId' => $r->RuleId,
                    'ruleCode' => $r->RuleCode,
                    'ruleName' => $r->RuleName,
                    'ruleValue' => (int) $r->RuleValue,
                    'isEnabled' => (bool) $r->IsEnabled,
                ];
            })
        );
    }

    /* =========================
       UPDATE PASSWORD RULES
    ==========================*/
    public function updatePasswordRules(Request $request)
    {
        $this->authorizeSettingsUpdate($request);

        try {
            $rawRules = $request->input('rules', $request->all());
            if (Arr::isAssoc($rawRules) && (isset($rawRules['RuleCode']) || isset($rawRules['ruleCode']))) {
                $rawRules = [$rawRules];
            }

            if (! is_array($rawRules)) {
                return response()->json([
                    'result' => false,
                    'message' => 'Invalid data format. Expected an array of rules.',
                ], 422);
            }

            $normalizedRules = [];
            foreach ($rawRules as $rule) {
                if (! is_array($rule)) {
                    continue;
                }

                $ruleCode = strtoupper(trim((string) ($rule['RuleCode'] ?? $rule['ruleCode'] ?? '')));
                $isEnabled = filter_var($rule['IsEnabled'] ?? $rule['isEnabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

                $ruleValue = isset($rule['RuleValue']) ? (int) $rule['RuleValue'] : (isset($rule['ruleValue']) ? (int) $rule['ruleValue'] : null);
                if ($ruleValue === null || $ruleValue <= 0) {
                    $ruleValue = ($ruleCode === PasswordRule::RULE_MIN_LENGTH) ? 8 : 1;
                }

                $normalizedRules[] = [
                    'RuleId' => (int) ($rule['RuleId'] ?? $rule['ruleId'] ?? 0),
                    'RuleCode' => $ruleCode,
                    'IsEnabled' => $isEnabled,
                    'RuleValue' => $ruleValue,
                ];
            }

            $validator = validator($normalizedRules, [
                '*' => 'required|array',
                '*.RuleCode' => 'required|string|in:MIN_LENGTH,UPPERCASE,LOWERCASE,NUMBER,SPECIAL_CHAR',
                '*.IsEnabled' => 'required|boolean',
                '*.RuleValue' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => 'Validation error: '.implode(', ', Arr::flatten($validator->errors()->toArray())),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            DB::beginTransaction();

            $ruleNameMap = [
                PasswordRule::RULE_MIN_LENGTH => 'Minimum Length',
                PasswordRule::RULE_UPPERCASE => 'Uppercase Letters',
                PasswordRule::RULE_LOWERCASE => 'Lowercase Letters',
                PasswordRule::RULE_NUMBER => 'Numbers',
                PasswordRule::RULE_SPECIAL_CHAR => 'Special Characters',
            ];

            $updatedCount = 0;
            foreach ($data as $rule) {
                $ruleName = $ruleNameMap[$rule['RuleCode']] ?? $rule['RuleCode'];

                $ruleRecord = PasswordRule::firstOrNew(['RuleCode' => $rule['RuleCode']]);
                $ruleRecord->RuleName = $ruleName;
                $ruleRecord->RuleValue = $rule['RuleValue'];
                $ruleRecord->IsEnabled = $rule['IsEnabled'] ? 1 : 0;
                $ruleRecord->IsActive = 1;
                $ruleRecord->UpdatedOn = Carbon::now();
                if (! $ruleRecord->exists) {
                    $ruleRecord->CreatedOn = Carbon::now();
                }
                $ruleRecord->save();

                $updatedCount++;
            }

            DB::commit();

            PasswordRule::clearCache();

            return response()->json([
                'result' => true,
                'message' => 'Password rules updated successfully',
                'updatedCount' => $updatedCount,
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error in updatePasswordRules:', $e->errors());

            return response()->json([
                'result' => false,
                'message' => 'Validation error: '.implode(', ', Arr::flatten($e->errors())),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updating password rules:', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'result' => false,
                'message' => 'Error updating password rules: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getRegistrationSettings(Request $request)
    {
        $this->authorizeSettingsAccess($request);

        AuthSetting::ensureDefaultSettings();

        $setting = AuthSetting::where('AuthCode', AuthSetting::CODE_REGISTRATION)
            ->where('IsActive', 1)
            ->first();

        return response()->json([
            'authSettingId' => $setting?->AuthSettingId,
            'authCode' => AuthSetting::CODE_REGISTRATION,
            'isEnabled' => (bool) ($setting?->IsEnabled ?? false),
        ]);
    }

    public function updateRegistrationSettings(Request $request)
    {
        $this->authorizeSettingsUpdate($request);

        $data = $request->validate([
            'isEnabled' => 'required|boolean',
        ]);

        AuthSetting::ensureDefaultSettings();

        $setting = AuthSetting::where('AuthCode', AuthSetting::CODE_REGISTRATION)->first();
        if ($setting) {
            $setting->fill([
                'IsEnabled' => $data['isEnabled'] ? 1 : 0,
                'UpdatedOn' => Carbon::now(),
            ]);
            $setting->save();
        }

        AuthSetting::clearCache();

        return response()->json([
            'result' => true,
            'message' => 'Registration setting updated successfully.',
            'isEnabled' => (bool) $data['isEnabled'],
        ]);
    }

    /* =========================
       USER MAC SETTINGS
    ==========================*/
    public function getUserMacSettings(Request $request)
    {
        $this->authorizeSettingsAccess($request);

        $search = trim((string) $request->query('search', ''));

        $query = $this->getUserModelClass()::query()
            ->select(['id', 'name', 'email', 'mobile_number'])
            ->with(['devices' => function ($q) {
                $q->orderByDesc('updated_at');
            }]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->get();

        $totalUsers = $this->getUserModelClass()::query()->count();
        $configuredUsers = UserDevice::query()
            ->whereNotNull('device_mac')
            ->where('device_mac', '!=', '')
            ->distinct('user_id')
            ->count('user_id');
        $unconfiguredUsers = max(0, $totalUsers - $configuredUsers);

        $data = $users->map(function ($u) {
            $device = $u->devices->firstWhere(fn ($d) => ! empty($d->device_mac)) ?? $u->devices->first();
            $hasMac = ! empty($device?->device_mac);
            $isAuthorized = (bool) ($device?->is_authorized ?? true);

            $status = 'not_configured';
            $statusLabel = 'Not Configured';
            if ($hasMac) {
                if ($isAuthorized) {
                    $status = 'configured';
                    $statusLabel = 'Authorized';
                } else {
                    $status = 'disabled';
                    $statusLabel = 'Unauthorized';
                }
            }

            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'mobile_number' => $u->mobile_number,
                'device_id' => $device?->id,
                'device_name' => $device?->device_name ?? 'Authorized Device',
                'device_mac' => $device?->device_mac,
                'is_authorized' => $isAuthorized,
                'has_mac' => $hasMac,
                'status' => $status,
                'status_label' => $statusLabel,
            ];
        });

        return response()->json([
            'result' => true,
            'totalUsers' => $totalUsers,
            'configuredUsers' => $configuredUsers,
            'unconfiguredUsers' => $unconfiguredUsers,
            'users' => $data,
        ]);
    }

    public function updateUserMacSetting(Request $request)
    {
        $this->authorizeSettingsUpdate($request);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'device_mac' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'is_authorized' => ['nullable'],
            'device_id' => ['nullable', 'integer'],
        ], [
            'user_id.required' => 'User is required.',
            'user_id.exists' => 'The selected user does not exist.',
            'device_mac.required' => 'MAC address is required.',
        ]);

        $rawMac = trim((string) $validated['device_mac']);

        if (! $this->deviceAuthorizationService->isValidMac($rawMac)) {
            return response()->json([
                'result' => false,
                'message' => 'Invalid MAC address format. Example format: 00:1A:2B:3C:4D:5E',
                'errors' => [
                    'device_mac' => ['Invalid MAC address format. Example format: 00:1A:2B:3C:4D:5E'],
                ],
            ], 422);
        }

        $normalizedMac = $this->deviceAuthorizationService->normalizeMac($rawMac);
        $user = $this->getUserModelClass()::findOrFail($validated['user_id']);

        // Check for duplicate MAC assignment to another user
        $cleanMacDigits = strtoupper((string) preg_replace('/[^0-9A-Fa-f]/', '', $normalizedMac));
        $existingDeviceWithMac = UserDevice::query()
            ->where('user_id', '!=', $user->id)
            ->whereNotNull('device_mac')
            ->where('device_mac', '!=', '')
            ->with('user')
            ->get()
            ->first(function ($dev) use ($cleanMacDigits) {
                $devClean = strtoupper((string) preg_replace('/[^0-9A-Fa-f]/', '', (string) $dev->device_mac));

                return $devClean === $cleanMacDigits;
            });

        if ($existingDeviceWithMac !== null) {
            $assignedUserName = $existingDeviceWithMac->user?->name ?? 'another user';

            return response()->json([
                'result' => false,
                'message' => "The MAC address is already assigned to {$assignedUserName}.",
                'errors' => [
                    'device_mac' => ["This MAC address is already assigned to {$assignedUserName}."],
                ],
            ], 422);
        }

        $isAuthorized = true;
        if ($request->has('is_authorized')) {
            $isAuthorized = filter_var($request->input('is_authorized'), FILTER_VALIDATE_BOOLEAN);
        }

        $deviceName = ! empty($validated['device_name']) ? trim((string) $validated['device_name']) : 'Authorized Device';

        try {
            DB::beginTransaction();

            $device = null;
            if (! empty($validated['device_id'])) {
                $device = UserDevice::query()
                    ->where('user_id', $user->id)
                    ->where('id', $validated['device_id'])
                    ->first();
            }

            if ($device === null) {
                $device = UserDevice::query()
                    ->where('user_id', $user->id)
                    ->orderByDesc('updated_at')
                    ->first();
            }

            if ($device !== null) {
                // Update existing device record without creating duplicates
                $device->device_mac = $normalizedMac;
                $device->device_name = $deviceName;
                $device->is_authorized = $isAuthorized;
                $device->save();
            } else {
                // Register new device
                $registration = $this->deviceAuthorizationService->registerDevice(
                    $user,
                    $deviceName,
                    $normalizedMac,
                    $isAuthorized
                );
                $device = $registration['device'];
            }

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => 'MAC address configuration saved successfully.',
                'device' => [
                    'id' => $device->id,
                    'user_id' => $user->id,
                    'device_name' => $device->device_name,
                    'device_mac' => $device->device_mac,
                    'is_authorized' => (bool) $device->is_authorized,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to save user MAC configuration: '.$e->getMessage(), [
                'user_id' => $user->id,
                'exception' => $e,
            ]);

            return response()->json([
                'result' => false,
                'message' => 'Failed to save MAC configuration due to a database error.',
            ], 500);
        }
    }

    public function deleteUserMacSetting(Request $request)
    {
        $this->authorizeSettingsUpdate($request);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'device_id' => ['nullable', 'integer'],
        ], [
            'user_id.required' => 'User is required.',
            'user_id.exists' => 'The selected user does not exist.',
        ]);

        $user = $this->getUserModelClass()::findOrFail($validated['user_id']);

        $device = null;
        if (! empty($validated['device_id'])) {
            $device = UserDevice::query()
                ->where('user_id', $user->id)
                ->where('id', $validated['device_id'])
                ->first();

            if ($device === null) {
                return response()->json([
                    'result' => false,
                    'message' => 'The specified device record does not exist or does not belong to this user.',
                ], 404);
            }
        } else {
            $device = UserDevice::query()
                ->where('user_id', $user->id)
                ->whereNotNull('device_mac')
                ->where('device_mac', '!=', '')
                ->first();

            if ($device === null) {
                return response()->json([
                    'result' => false,
                    'message' => 'No MAC address record found for this user.',
                ], 404);
            }
        }

        try {
            DB::beginTransaction();

            $deletedDeviceId = $device->id;
            $deletedMac = $device->device_mac;

            $device->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => 'MAC address deleted successfully.',
                'user_id' => $user->id,
                'device_id' => $deletedDeviceId,
                'deleted_mac' => $deletedMac,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to delete user MAC configuration: '.$e->getMessage(), [
                'user_id' => $user->id,
                'device_id' => $device->id ?? null,
                'exception' => $e,
            ]);

            return response()->json([
                'result' => false,
                'message' => 'Failed to delete MAC configuration due to a database error.',
            ], 500);
        }
    }

    protected function authorizeSettingsAccess(Request $request): void
    {
        $user = $request->user() ?? Auth::user();

        if ($user !== null) {
            if (! $this->userIsAuthorized($user)) {
                abort(403, 'Unauthorized. You do not have permission to view authentication settings.');
            }

            return;
        }

        if (! app()->environment('testing') || config('auth.enforce_settings_auth', false)) {
            abort(403, 'Unauthorized. Authentication is required to view authentication settings.');
        }
    }

    protected function authorizeSettingsUpdate(Request $request): void
    {
        $user = $request->user() ?? Auth::user();

        if ($user !== null) {
            if (! $this->userIsAuthorized($user)) {
                abort(403, 'Unauthorized. You do not have permission to update authentication settings.');
            }

            return;
        }

        if (! app()->environment('testing') || config('auth.enforce_settings_auth', false)) {
            abort(403, 'Unauthorized. Authentication is required to update authentication settings.');
        }
    }

    protected function userIsAuthorized($user): bool
    {
        try {
            $adminRole = config('rbac-suite.roles.admin_name', 'Admin');
            if ($user->hasRole($adminRole)) {
                return true;
            }
        } catch (\Throwable $e) {
            // Role table empty or role doesn't exist
        }

        $permissions = [
            'auth_setting-edit',
            'auth_setting-view',
            'manage-auth-settings',
            'auth-settings',
        ];

        foreach ($permissions as $permission) {
            try {
                if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($permission)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Ignore missing permission
            }

            try {
                if (method_exists($user, 'can') && $user->can($permission)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Ignore missing permission
            }
        }

        return false;
    }
}
