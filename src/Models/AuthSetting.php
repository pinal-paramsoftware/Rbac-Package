<?php

namespace Parth\LaravelRbacSuite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Parth\LaravelRbacSuite\Http\Controllers\Auth\LoginController;

class AuthSetting extends Model
{
    use HasFactory;

    public const CODE_CAPTCHA = 'CAPTCHA';

    public const CODE_EMAIL_OTP = 'EMAIL_VERIFY';

    public const CODE_MOBILE_OTP = 'MOBILE_VERIFY';

    public const CODE_WHATSAPP_OTP = 'WHATSAPP_VERIFY';

    public const CODE_TWO_FACTOR = 'TWO_FACTOR';

    public const CODE_MAC_FUNCTIONALITY = 'MAC_FUNCTIONALITY';

    public const CODE_REGISTRATION = 'SIGNUP_REGISTRATION';

    public const CODE_SIGNUP_REGISTRATION = 'SIGNUP_REGISTRATION';

    protected $primaryKey = 'AuthSettingId';

    protected $fillable = [
        'AuthCode',
        'AuthName',
        'IsEnabled',
        'OTPAttempt',
        'OTPResetTime',
        'OTPExpiryTime',
        'CreatedOn',
        'UpdatedOn',
        'DeletionDate',
        'IsActive',
        'UserId',
    ];

    public $timestamps = false;

    protected $dates = ['CreatedOn', 'UpdatedOn', 'DeletionDate'];

    public function getTable(): string
    {
        return config('rbac-suite.tables.authentication_settings', 'authentication_settings');
    }

    /**
     * Helper method to get setting by AuthCode.
     */
    public static function getSetting(string $authCode): ?self
    {
        return self::where('AuthCode', $authCode)
            ->where('IsEnabled', 1)
            ->where('IsActive', 1)
            ->first();
    }

    /**
     * Default authentication methods that must exist in the table.
     *
     * @return array<int, array<string, int|string|null>>
     */
    public static function defaultSettings(): array
    {
        return [
            [
                'AuthCode' => self::CODE_CAPTCHA,
                'AuthName' => 'Captcha',
                'IsEnabled' => 1,
                'OTPAttempt' => null,
                'OTPResetTime' => null,
                'OTPExpiryTime' => null,
                'IsActive' => 1,
            ],
            [
                'AuthCode' => self::CODE_EMAIL_OTP,
                'AuthName' => 'Email OTP',
                'IsEnabled' => 0,
                'OTPAttempt' => 5,
                'OTPResetTime' => 5,
                'OTPExpiryTime' => 10,
                'IsActive' => 1,
            ],
            [
                'AuthCode' => self::CODE_MOBILE_OTP,
                'AuthName' => 'Mobile OTP',
                'IsEnabled' => 0,
                'OTPAttempt' => 5,
                'OTPResetTime' => 5,
                'OTPExpiryTime' => 10,
                'IsActive' => 1,
            ],
            [
                'AuthCode' => self::CODE_WHATSAPP_OTP,
                'AuthName' => 'WhatsApp OTP',
                'IsEnabled' => 0,
                'OTPAttempt' => 5,
                'OTPResetTime' => 5,
                'OTPExpiryTime' => 10,
                'IsActive' => 1,
            ],
            [
                'AuthCode' => self::CODE_TWO_FACTOR,
                'AuthName' => 'Two-Factor Authentication',
                'IsEnabled' => 0,
                'OTPAttempt' => 5,
                'OTPResetTime' => null,
                'OTPExpiryTime' => 10,
                'IsActive' => 1,
            ],
            [
                'AuthCode' => self::CODE_MAC_FUNCTIONALITY,
                'AuthName' => 'MAC Functionality',
                'IsEnabled' => 0,
                'OTPAttempt' => null,
                'OTPResetTime' => null,
                'OTPExpiryTime' => null,
                'IsActive' => 1,
            ],
            [
                'AuthCode' => self::CODE_REGISTRATION,
                'AuthName' => 'Signup / Registration',
                'IsEnabled' => 1,
                'OTPAttempt' => null,
                'OTPResetTime' => null,
                'OTPExpiryTime' => null,
                'IsActive' => 1,
            ],
        ];
    }

    /**
     * Check if registration is enabled.
     */
    public static function isRegistrationEnabled(): bool
    {
        self::ensureDefaultSettings();

        return (bool) self::where('AuthCode', self::CODE_REGISTRATION)
            ->where('IsActive', 1)
            ->value('IsEnabled');
    }

    protected static function booted(): void
    {
        static::saved(function () {
            self::clearCache();
        });

        static::deleted(function () {
            self::clearCache();
        });
    }

    public static function clearCache(): void
    {
        if (class_exists(LoginController::class)) {
            LoginController::resetSettingsCache();
        }
    }

    public static function ensureDefaultSettings(): void
    {
        self::clearCache();
        $now = now();

        foreach (self::defaultSettings() as $setting) {
            self::firstOrCreate(
                ['AuthCode' => $setting['AuthCode']],
                array_merge($setting, [
                    'CreatedOn' => $now,
                    'UpdatedOn' => $now,
                    'DeletionDate' => null,
                    'UserId' => null,
                ])
            );
        }
    }
}
