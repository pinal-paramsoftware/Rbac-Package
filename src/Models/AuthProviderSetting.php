<?php

namespace Parth\LaravelRbacSuite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AuthProviderSetting extends Model
{
    use HasFactory;

    public const PROVIDER_WHATSAPP = 'WHATSAPP';

    public const PROVIDER_SMTP = 'SMTP';

    public const PROVIDER_SMS = 'SMS';

    public const PROVIDER_MOBILE_OTP = 'SMS';

    public const PROVIDER_CAPTCHA = 'CAPTCHA';

    protected $fillable = [
        'provider_code',
        'host',
        'port',
        'encryption',
        'from_address',
        'from_name',
        'url',
        'token',
        'username',
        'password',
        'sender_id',
        'template',
        'timeout',
        'verify_ssl',
    ];

    protected $casts = [
        'port' => 'integer',
        'timeout' => 'integer',
        'verify_ssl' => 'boolean',
    ];

    protected $hidden = [
        'token',
        'password',
    ];

    public function getTable(): string
    {
        return config('rbac-suite.tables.auth_provider_settings', 'auth_provider_settings');
    }

    public function getTokenAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    public function setTokenAttribute(?string $value): void
    {
        $this->attributes['token'] = ($value !== null && $value !== '')
            ? Crypt::encryptString($value)
            : null;
    }

    public function getPasswordAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['password'] = ($value !== null && $value !== '')
            ? Crypt::encryptString($value)
            : null;
    }
}
