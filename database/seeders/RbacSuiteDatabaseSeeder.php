<?php

namespace Parth\LaravelRbacSuite\Database\Seeders;

use Illuminate\Database\Seeder;

class RbacSuiteDatabaseSeeder extends Seeder
{
    /**
     * Run all package database seeds.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DefaultAdminUserSeeder::class,
            DefaultAuthSettingsSeeder::class,
            DefaultPasswordRulesSeeder::class,
            DefaultAuthProviderSettingsSeeder::class,
        ]);
    }
}
