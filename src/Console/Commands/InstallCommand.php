<?php

namespace Parth\LaravelRbacSuite\Console\Commands;

use Database\Seeders\RbacSuiteDatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:install
                            {--publish : Publish core configurations, routes, views, and public assets}
                            {--publish-all : Publish complete supported set (configs, routes, views, assets, controllers, models, migrations, seeders)}
                            {--publish-config : Publish package configuration files}
                            {--publish-controllers : Publish customizable controllers to app/Http/Controllers/Rbac}
                            {--publish-models : Publish customizable models to app/Models/Rbac}
                            {--publish-views : Publish customizable views directly to resources/views}
                            {--publish-routes : Publish customizable routes to routes/rbac.php}
                            {--publish-migrations : Publish database migrations to database/migrations}
                            {--with-migrations : Alias for --publish-migrations}
                            {--publish-seeders : Publish customizable seeders to database/seeders}
                            {--publish-assets : Publish admin UI assets to public/vendor/rbac-suite}
                            {--migrate : Execute database migrations immediately}
                            {--seed : Execute baseline RBAC seeders immediately}
                            {--force : Overwrite existing published files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure the Laravel RBAC Suite package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('  Laravel RBAC Suite — Enterprise Access Control');
        $this->info('====================================================');
        $this->newLine();

        $force = (bool) $this->option('force');

        // Determine publishing operations
        $this->handlePublishing($force);

        // Check host application user model and route integration non-destructively
        $this->checkUserModelIntegration();
        $this->checkRootRouteIntegration();

        // Run migrations if requested
        if ($this->option('migrate')) {
            $this->newLine();
            $this->line('Running database migrations...');
            $this->call('migrate');
        }

        // Run seeders if requested
        if ($this->option('seed')) {
            $this->newLine();
            $this->line('Seeding initial RBAC roles, permissions, and default admin user...');

            $seederClass = class_exists(RbacSuiteDatabaseSeeder::class)
                ? RbacSuiteDatabaseSeeder::class
                : 'Parth\\LaravelRbacSuite\\Database\\Seeders\\RbacSuiteDatabaseSeeder';

            $this->call('db:seed', [
                '--class' => $seederClass,
                '--force' => true,
            ]);

            // Post-seed verification of model_has_roles
            $this->verifyAdminRoleAssignment();
        }

        $this->newLine();
        $this->info('Laravel RBAC Suite installation completed successfully!');
        $this->newLine();
        $this->line('Default Administrator Account:');
        $this->line('  • Email:    admin@gmail.com');
        $this->line('  • Password: password');
        $this->line('  • Role:     Admin (granted all 20 package permissions)');
        $this->warn('  CRITICAL: You must change the default password immediately after first login!');
        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Open the login screen at: /login or root URL /');
        $this->line('  2. Land on your RBAC Dashboard at: /dashboard');
        $this->line('  3. Access the administrative panel at: /AuthPasswordSetting');

        return Command::SUCCESS;
    }

    /**
     * Coordinate all publishing operations based on passed flags.
     */
    protected function handlePublishing(bool $force): void
    {
        $publishAll = (bool) $this->option('publish-all');
        $publishCore = (bool) $this->option('publish');
        $publishConfig = (bool) $this->option('publish-config');
        $publishControllers = (bool) $this->option('publish-controllers');
        $publishModels = (bool) $this->option('publish-models');
        $publishViews = (bool) $this->option('publish-views');
        $publishRoutes = (bool) $this->option('publish-routes');
        $publishMigrations = (bool) $this->option('publish-migrations') || (bool) $this->option('with-migrations');
        $publishSeeders = (bool) $this->option('publish-seeders');
        $publishAssets = (bool) $this->option('publish-assets');

        // Default behavior if no explicit publish flag was passed: publish config & assets
        $hasSpecificFlag = $publishAll || $publishCore || $publishConfig || $publishControllers ||
            $publishModels || $publishViews || $publishRoutes || $publishMigrations ||
            $publishSeeders || $publishAssets;

        if (! $hasSpecificFlag) {
            $publishConfig = true;
            $publishAssets = true;
        }

        if ($publishAll) {
            $this->publishTag('rbac-suite-all', 'complete package resources', $force);

            return;
        }

        if ($publishCore) {
            $this->publishTag('rbac-suite-config', 'configuration', $force);
            $this->publishTag('rbac-suite-routes', 'routes/rbac.php', $force);
            $this->publishTag('rbac-suite-views', 'views', $force);
            $this->publishTag('rbac-suite-assets', 'public assets', $force);
        }

        if ($publishConfig && ! $publishCore) {
            $this->publishTag('rbac-suite-config', 'configuration', $force);
        }

        if ($publishControllers) {
            $this->publishTag('rbac-suite-controllers', 'customizable controllers (app/Http/Controllers/Rbac)', $force);
        }

        if ($publishModels) {
            $this->publishTag('rbac-suite-models', 'customizable models (app/Models/Rbac)', $force);
        }

        if ($publishViews && ! $publishCore) {
            $this->publishTag('rbac-suite-views', 'views directly to resources/views', $force);
        }

        if ($publishRoutes && ! $publishCore) {
            $this->publishTag('rbac-suite-routes', 'dedicated route file (routes/rbac.php)', $force);
        }

        if ($publishMigrations) {
            $this->publishTag('rbac-suite-migrations', 'migrations (database/migrations)', $force);
            $this->warn('NOTE: Migrations are now managed in database/migrations.');
        }

        if ($publishSeeders) {
            $this->publishTag('rbac-suite-seeders', 'seeders (database/seeders)', $force);
        }

        if ($publishAssets && ! $publishCore) {
            $this->publishTag('rbac-suite-assets', 'public assets (public/vendor/rbac-suite)', $force);
        }
    }

    /**
     * Safely publish an individual tag.
     */
    protected function publishTag(string $tag, string $description, bool $force): void
    {
        $this->line("Publishing {$description} [tag: {$tag}]...");

        $this->call('vendor:publish', [
            '--tag' => $tag,
            '--force' => $force,
        ]);
    }

    /**
     * Inspect the application User model non-destructively and offer guidance.
     */
    protected function checkUserModelIntegration(): void
    {
        $userClass = config('rbac-suite.user_model', 'App\\Models\\User');

        if (! class_exists($userClass)) {
            $this->comment("Notice: Configured user model [{$userClass}] not yet loaded or does not exist.");

            return;
        }

        $traits = class_uses_recursive($userClass);

        $hasRbacUser = in_array('Parth\\LaravelRbacSuite\\Traits\\HasRbacUser', $traits, true);
        $hasSpatieRoles = in_array('Spatie\\Permission\\Traits\\HasRoles', $traits, true);

        if ($hasRbacUser) {
            $this->info("✓ User model [{$userClass}] incorporates HasRbacUser trait.");
        } elseif ($hasSpatieRoles) {
            $this->info("✓ User model [{$userClass}] incorporates Spatie HasRoles trait.");
        } else {
            $this->newLine();
            $this->warn("! Action Required: User model [{$userClass}] requires role capabilities.");
            $this->line('  Please add the HasRbacUser or HasRoles trait to your model:');
            $this->line('  use Parth\\LaravelRbacSuite\\Traits\\HasRbacUser;');
            $this->line('  // or use Spatie\\Permission\\Traits\\HasRoles;');
            $this->newLine();
        }
    }

    /**
     * Inspect routes/web.php non-destructively without modifying files.
     */
    protected function checkRootRouteIntegration(): void
    {
        $webRoutesPath = base_path('routes/web.php');

        if (! File::exists($webRoutesPath)) {
            return;
        }

        $content = File::get($webRoutesPath);

        // Check if root route '/' is defined in routes/web.php
        $pattern = '/Route::get\(\s*[\'"]\/[\'"]\s*,/';

        if (preg_match($pattern, $content) && config('rbac-suite.routes.redirect_root', true)) {
            $this->comment("Info: Host application defines a '/' route in routes/web.php.");
            $this->comment('  To let the RBAC suite handle root redirection to /dashboard or /login,');
            $this->comment('  you can adjust routes/web.php or set RBAC_REDIRECT_ROOT=false in .env.');
        }
    }

    /**
     * Verify that the Admin user and model_has_roles row exist after seeding.
     */
    protected function verifyAdminRoleAssignment(): void
    {
        $guard = config('rbac-suite.guard', 'web');
        $adminRoleName = config('rbac-suite.roles.admin_name', 'Admin');
        $userClass = config('rbac-suite.user_model', 'App\\Models\\User');

        if (! class_exists($userClass)) {
            return;
        }

        $pivotTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $morphKey = config('permission.column_names.model_morph_key', 'model_id');

        if (! Schema::hasTable($pivotTable) || ! Schema::hasTable('roles') || ! Schema::hasTable('users')) {
            return;
        }

        $adminUser = $userClass::where('email', 'admin@gmail.com')->first();
        $adminRole = DB::table('roles')->where('name', $adminRoleName)->where('guard_name', $guard)->first();

        if (! $adminUser || ! $adminRole) {
            $this->warn('Could not find admin user or admin role for verification.');

            return;
        }

        $pivotExists = DB::table($pivotTable)
            ->where('role_id', $adminRole->id)
            ->where('model_type', $adminUser->getMorphClass())
            ->where($morphKey, $adminUser->getKey())
            ->exists();

        if (! $pivotExists) {
            $this->warn('Role pivot missing in '.$pivotTable.' — performing corrective assignment...');
            DB::table($pivotTable)->insertOrIgnore([
                'role_id' => $adminRole->id,
                'model_type' => $adminUser->getMorphClass(),
                $morphKey => $adminUser->getKey(),
            ]);

            if (app()->bound(PermissionRegistrar::class)) {
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        }

        $this->info('✓ Verified Admin role assigned to '.$adminUser->email.' in '.$pivotTable.' (Role ID: '.$adminRole->id.', Model ID: '.$adminUser->getKey().').');
    }
}
