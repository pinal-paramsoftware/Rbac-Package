<?php

namespace Parth\LaravelRbacSuite\Database\Seeders;

use Illuminate\Database\Seeder;
use Parth\LaravelRbacSuite\Models\AuthProviderSetting;

class DefaultAuthProviderSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AuthProviderSetting::firstOrCreate(
            ['provider_code' => AuthProviderSetting::PROVIDER_WHATSAPP],
            [
                'url' => 'https://whatsapp-gateway.example.com/send',
                'token' => 'dummy-whatsapp-token',
                'username' => '',
                'password' => '',
                'sender_id' => 'APP_WHATSAPP',
                'template' => 'otp_template',
                'timeout' => 5,
                'verify_ssl' => true,
            ]
        );

        AuthProviderSetting::firstOrCreate(
            ['provider_code' => AuthProviderSetting::PROVIDER_SMS],
            [
                'url' => 'https://sms-gateway.example.com/api/send',
                'token' => 'dummy-sms-token',
                'username' => 'dummy_sms_username',
                'password' => 'dummy_sms_password',
                'sender_id' => 'APP_SMS',
                'template' => 'otp_template',
                'timeout' => 10,
                'verify_ssl' => true,
            ]
        );

        AuthProviderSetting::firstOrCreate(
            ['provider_code' => AuthProviderSetting::PROVIDER_SMTP],
            [
                'host' => '127.0.0.1',
                'port' => 2525,
                'encryption' => 'tls',
                'username' => 'smtp_user',
                'password' => 'smtp_password',
                'from_address' => 'noreply@example.com',
                'from_name' => 'RBAC Suite',
                'verify_ssl' => false,
            ]
        );
    }
}
