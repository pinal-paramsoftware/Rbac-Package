<?php

namespace Parth\LaravelRbacSuite\Support;

use Illuminate\Support\Facades\Schema;
use Parth\LaravelRbacSuite\Models\AuthProviderSetting;

class EmailOtpSmtpSettings
{
    /**
     * @return array{
     *   host: string,
     *   port: int,
     *   username: string,
     *   password: string,
     *   encryption: string,
     *   from_address: string,
     *   from_name: string,
     *   has_password: bool
     * }
     */
    public static function resolve(): array
    {
        $defaultConfig = [
            'host' => trim((string) config('mail.mailers.smtp.host', '')),
            'port' => (int) config('mail.mailers.smtp.port', 587),
            'username' => trim((string) config('mail.mailers.smtp.username', '')),
            'password' => (string) config('mail.mailers.smtp.password', ''),
            'encryption' => trim((string) config('mail.mailers.smtp.encryption', 'tls')),
            'from_address' => trim((string) config('mail.from.address', '')),
            'from_name' => trim((string) config('mail.from.name', '')),
            'has_password' => (string) config('mail.mailers.smtp.password', '') !== '',
        ];

        try {
            $tableName = (new AuthProviderSetting)->getTable();
            if (! Schema::hasTable($tableName)) {
                return $defaultConfig;
            }

            $providerSettings = AuthProviderSetting::query()
                ->where('provider_code', AuthProviderSetting::PROVIDER_SMTP)
                ->first();

            if (! $providerSettings) {
                return $defaultConfig;
            }

            $hasHostCol = Schema::hasColumn($tableName, 'host');
            $hasPortCol = Schema::hasColumn($tableName, 'port');
            $hasEncCol = Schema::hasColumn($tableName, 'encryption');
            $hasFromCol = Schema::hasColumn($tableName, 'from_address');
            $hasNameCol = Schema::hasColumn($tableName, 'from_name');

            $dbHost = $hasHostCol ? (string) ($providerSettings->host ?? '') : (string) ($providerSettings->url ?? '');
            $dbPort = $hasPortCol ? $providerSettings->port : null;
            $dbEnc = $hasEncCol ? (string) ($providerSettings->encryption ?? '') : '';
            $dbFrom = $hasFromCol ? (string) ($providerSettings->from_address ?? '') : (string) ($providerSettings->sender_id ?? '');
            $dbName = $hasNameCol ? (string) ($providerSettings->from_name ?? '') : (string) ($providerSettings->template ?? '');

            $password = (string) ($providerSettings->password ?? '');

            return [
                'host' => trim($dbHost !== '' ? $dbHost : $defaultConfig['host']),
                'port' => $dbPort !== null && $dbPort > 0 ? (int) $dbPort : $defaultConfig['port'],
                'username' => trim($providerSettings->username !== null ? (string) $providerSettings->username : $defaultConfig['username']),
                'password' => $password !== '' ? $password : $defaultConfig['password'],
                'encryption' => $dbEnc !== '' ? trim($dbEnc) : $defaultConfig['encryption'],
                'from_address' => trim($dbFrom !== '' ? $dbFrom : $defaultConfig['from_address']),
                'from_name' => trim($dbName !== '' ? $dbName : $defaultConfig['from_name']),
                'has_password' => $password !== '' || $defaultConfig['has_password'],
            ];
        } catch (\Throwable) {
            return $defaultConfig;
        }
    }

    public static function isConfigured(): bool
    {
        try {
            $tableName = (new AuthProviderSetting)->getTable();
            if (! Schema::hasTable($tableName)) {
                return false;
            }

            $providerSettings = AuthProviderSetting::query()
                ->where('provider_code', AuthProviderSetting::PROVIDER_SMTP)
                ->first();

            if (! $providerSettings) {
                return false;
            }

            $hasHostCol = Schema::hasColumn($tableName, 'host');
            $hasPortCol = Schema::hasColumn($tableName, 'port');
            $hasFromCol = Schema::hasColumn($tableName, 'from_address');

            $host = trim($hasHostCol ? (string) ($providerSettings->host ?? '') : (string) ($providerSettings->url ?? ''));
            $port = (int) ($hasPortCol ? ($providerSettings->port ?? 0) : 0);
            $fromAddress = trim($hasFromCol ? (string) ($providerSettings->from_address ?? '') : (string) ($providerSettings->sender_id ?? ''));

            return $host !== '' && $port > 0 && $fromAddress !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    public static function apply(): void
    {
        $settings = static::resolve();

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $settings['host'],
            'mail.mailers.smtp.port' => $settings['port'],
            'mail.mailers.smtp.encryption' => $settings['encryption'] === 'none' ? null : $settings['encryption'],
            'mail.mailers.smtp.username' => $settings['username'],
            'mail.mailers.smtp.password' => $settings['password'],
            'mail.from.address' => $settings['from_address'],
            'mail.from.name' => $settings['from_name'],
        ]);
    }
}
