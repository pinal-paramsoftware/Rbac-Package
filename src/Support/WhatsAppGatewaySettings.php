<?php

namespace Parth\LaravelRbacSuite\Support;

use Illuminate\Support\Facades\Schema;
use Parth\LaravelRbacSuite\Models\AuthProviderSetting;

class WhatsAppGatewaySettings
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
            'url' => trim((string) config('services.whatsapp_gateway.url')),
            'token' => trim((string) config('services.whatsapp_gateway.token')),
            'username' => trim((string) config('services.whatsapp_gateway.username')),
            'password' => (string) config('services.whatsapp_gateway.password'),
            'sender_id' => trim((string) config('services.whatsapp_gateway.sender_id')),
            'template' => trim((string) config('services.whatsapp_gateway.template')),
            'timeout' => max(1, (int) config('services.whatsapp_gateway.timeout', 10)),
            'verify_ssl' => (bool) config('services.whatsapp_gateway.verify_ssl', true),
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
                ->where('provider_code', AuthProviderSetting::PROVIDER_WHATSAPP)
                ->first();

            if (! $providerSettings) {
                return array_merge($settings, [
                    'has_token' => $settings['token'] !== '',
                    'has_password' => $settings['password'] !== '',
                ]);
            }

            $token = trim((string) ($providerSettings->token ?? $settings['token']));
            $password = (string) ($providerSettings->password ?? $settings['password']);

            return [
                'url' => trim((string) ($providerSettings->url ?? $settings['url'])),
                'token' => $token,
                'username' => trim((string) ($providerSettings->username ?? $settings['username'])),
                'password' => $password,
                'sender_id' => trim((string) ($providerSettings->sender_id ?? $settings['sender_id'])),
                'template' => trim((string) ($providerSettings->template ?? $settings['template'])),
                'timeout' => $providerSettings->timeout !== null ? max(1, (int) $providerSettings->timeout) : $settings['timeout'],
                'verify_ssl' => $providerSettings->verify_ssl ?? $settings['verify_ssl'],
                'has_token' => $token !== '',
                'has_password' => $password !== '',
            ];
        } catch (\Throwable $exception) {
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

        return $settings['sender_id'] !== '' && $settings['template'] !== '';
    }

    public static function apply(): void
    {
        $settings = static::resolve();

        config([
            'services.whatsapp_gateway.url' => $settings['url'],
            'services.whatsapp_gateway.token' => $settings['token'],
            'services.whatsapp_gateway.username' => $settings['username'],
            'services.whatsapp_gateway.password' => $settings['password'],
            'services.whatsapp_gateway.sender_id' => $settings['sender_id'],
            'services.whatsapp_gateway.template' => $settings['template'],
            'services.whatsapp_gateway.timeout' => $settings['timeout'],
            'services.whatsapp_gateway.verify_ssl' => $settings['verify_ssl'],
        ]);
    }
}
