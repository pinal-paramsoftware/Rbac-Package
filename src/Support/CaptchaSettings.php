<?php

namespace Parth\LaravelRbacSuite\Support;

use Illuminate\Support\Facades\Schema;
use Parth\LaravelRbacSuite\Models\AuthProviderSetting;

class CaptchaSettings
{
    /**
     * @return array{
     *   url: string,
     *   site_key: string,
     *   secret: string,
     *   timeout: int,
     *   verify_ssl: bool,
     *   has_secret: bool
     * }
     */
    public static function resolve(): array
    {
        $settings = [
            'url' => trim((string) config('services.captcha.url', '')),
            'site_key' => trim((string) config('services.captcha.site_key', '')),
            'secret' => (string) config('services.captcha.secret', ''),
            'timeout' => max(1, (int) config('services.captcha.timeout', 10)),
            'verify_ssl' => (bool) config('services.captcha.verify_ssl', true),
        ];

        try {
            $tableName = (new AuthProviderSetting)->getTable();
            if (! Schema::hasTable($tableName)) {
                return array_merge($settings, [
                    'has_secret' => $settings['secret'] !== '',
                ]);
            }

            $providerSettings = AuthProviderSetting::query()
                ->where('provider_code', AuthProviderSetting::PROVIDER_CAPTCHA)
                ->first();

            if (! $providerSettings) {
                return array_merge($settings, [
                    'has_secret' => $settings['secret'] !== '',
                ]);
            }

            $secret = (string) ($providerSettings->token ?? $providerSettings->password ?? $settings['secret']);
            $siteKey = trim((string) ($providerSettings->username ?? $providerSettings->sender_id ?? $settings['site_key']));
            $url = trim((string) ($providerSettings->url ?? $settings['url']));
            $timeout = $providerSettings->timeout !== null ? max(1, (int) $providerSettings->timeout) : $settings['timeout'];
            $verifySsl = $providerSettings->verify_ssl !== null ? (bool) $providerSettings->verify_ssl : $settings['verify_ssl'];

            return [
                'url' => $url,
                'site_key' => $siteKey,
                'secret' => $secret,
                'timeout' => $timeout,
                'verify_ssl' => $verifySsl,
                'has_secret' => $secret !== '',
            ];
        } catch (\Throwable) {
            return array_merge($settings, [
                'has_secret' => $settings['secret'] !== '',
            ]);
        }
    }

    public static function isConfigured(): bool
    {
        $settings = static::resolve();

        if ($settings['url'] !== '') {
            if (! filter_var($settings['url'], FILTER_VALIDATE_URL)) {
                return false;
            }
            if ($settings['secret'] === '') {
                return false;
            }

            return true;
        }

        return extension_loaded('gd') && function_exists('imagecreate');
    }

    public static function apply(): void
    {
        $settings = static::resolve();

        config([
            'services.captcha.url' => $settings['url'],
            'services.captcha.site_key' => $settings['site_key'],
            'services.captcha.secret' => $settings['secret'],
            'services.captcha.timeout' => $settings['timeout'],
            'services.captcha.verify_ssl' => $settings['verify_ssl'],
        ]);
    }
}
