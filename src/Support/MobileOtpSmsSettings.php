<?php

namespace Parth\LaravelRbacSuite\Support;

use Illuminate\Support\Facades\Schema;
use Parth\LaravelRbacSuite\Models\AuthProviderSetting;

class MobileOtpSmsSettings
{
    /**
     * @return array{
     *   url: string,
     *   token: string,
     *   username: string,
     *   password: string,
     *   sender_id: string,
     *   template: string,
     *   timeout: int,
     *   verify_ssl: bool,
     *   has_token: bool,
     *   has_password: bool
     * }
     */
    public static function resolve(): array
    {
        $settings = [
            'url' => trim((string) config('services.sms_gateway.url', '')),
            'token' => trim((string) config('services.sms_gateway.token', '')),
            'username' => trim((string) config('services.sms_gateway.username', '')),
            'password' => (string) config('services.sms_gateway.password', ''),
            'sender_id' => trim((string) config('services.sms_gateway.sender_id', '')),
            'template' => trim((string) config('services.sms_gateway.template', '')),
            'timeout' => max(1, (int) config('services.sms_gateway.timeout', 10)),
            'verify_ssl' => (bool) config('services.sms_gateway.verify_ssl', true),
        ];

        try {
            $tableName = (new AuthProviderSetting)->getTable();
            if (! Schema::hasTable($tableName)) {
                return array_merge($settings, [
                    'has_token' => $settings['token'] !== '',
                    'has_password' => $settings['password'] !== '',
                ]);
            }

            $providerSettings = AuthProviderSetting::query()
                ->where('provider_code', AuthProviderSetting::PROVIDER_SMS)
                ->first();

            if (! $providerSettings) {
                return array_merge($settings, [
                    'has_token' => $settings['token'] !== '',
                    'has_password' => $settings['password'] !== '',
                ]);
            }

            $url = trim((string) ($providerSettings->url ?? $settings['url']));
            $token = trim((string) ($providerSettings->token ?? $settings['token']));
            $username = trim((string) ($providerSettings->username ?? $settings['username']));
            $password = (string) ($providerSettings->password ?? $settings['password']);
            $senderId = trim((string) ($providerSettings->sender_id ?? $settings['sender_id']));
            $template = trim((string) ($providerSettings->template ?? $settings['template']));
            $timeout = $providerSettings->timeout !== null ? max(1, (int) $providerSettings->timeout) : $settings['timeout'];
            $verifySsl = $providerSettings->verify_ssl !== null ? (bool) $providerSettings->verify_ssl : $settings['verify_ssl'];

            return [
                'url' => $url,
                'token' => $token,
                'username' => $username,
                'password' => $password,
                'sender_id' => $senderId,
                'template' => $template,
                'timeout' => $timeout,
                'verify_ssl' => $verifySsl,
                'has_token' => $token !== '',
                'has_password' => $password !== '',
            ];
        } catch (\Throwable) {
            return array_merge($settings, [
                'has_token' => $settings['token'] !== '',
                'has_password' => $settings['password'] !== '',
            ]);
        }
    }

    public static function isConfigured(): bool
    {
        $settings = static::resolve();

        if ($settings['url'] === '' || ! filter_var($settings['url'], FILTER_VALIDATE_URL)) {
            return false;
        }

        if ($settings['token'] === '' && ($settings['username'] === '' || $settings['password'] === '')) {
            return false;
        }

        return true;
    }

    public static function apply(): void
    {
        $settings = static::resolve();

        config([
            'services.sms_gateway.url' => $settings['url'],
            'services.sms_gateway.token' => $settings['token'],
            'services.sms_gateway.username' => $settings['username'],
            'services.sms_gateway.password' => $settings['password'],
            'services.sms_gateway.sender_id' => $settings['sender_id'],
            'services.sms_gateway.template' => $settings['template'],
            'services.sms_gateway.timeout' => $settings['timeout'],
            'services.sms_gateway.verify_ssl' => $settings['verify_ssl'],
        ]);
    }
}
