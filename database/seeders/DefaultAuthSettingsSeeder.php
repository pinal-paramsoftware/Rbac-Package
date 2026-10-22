<?php

namespace Parth\LaravelRbacSuite\Database\Seeders;

use Illuminate\Database\Seeder;
use Parth\LaravelRbacSuite\Models\AuthSetting;

class DefaultAuthSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = AuthSetting::defaultSettings();
        $now = now();

        foreach ($defaultSettings as $setting) {
            $payload = array_merge($setting, [
                'CreatedOn' => $now,
                'UpdatedOn' => $now,
                'IsActive' => 1,
            ]);

            AuthSetting::firstOrCreate(
                ['AuthCode' => $setting['AuthCode']],
                $payload
            );
        }

        AuthSetting::clearCache();
    }
}
