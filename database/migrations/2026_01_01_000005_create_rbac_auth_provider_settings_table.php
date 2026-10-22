<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Parth\LaravelRbacSuite\Support\SchemaTracker;

return new class extends Migration
{
    /**
     * Resolve the target table name.
     */
    protected function getTableName(): string
    {
        return config('rbac-suite.tables.auth_provider_settings', 'auth_provider_settings');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = $this->getTableName();

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('provider_code', 30)->unique();
                $table->string('host')->nullable();
                $table->unsignedInteger('port')->nullable();
                $table->string('encryption', 20)->nullable();
                $table->string('from_address')->nullable();
                $table->string('from_name')->nullable();
                $table->string('url')->nullable();
                $table->text('token')->nullable();
                $table->string('username')->nullable();
                $table->text('password')->nullable();
                $table->string('sender_id')->nullable();
                $table->string('template')->nullable();
                $table->unsignedInteger('timeout')->nullable();
                $table->boolean('verify_ssl')->default(true);
                $table->timestamps();
            });

            SchemaTracker::recordCreatedTable($tableName);
        }
    }

    /**
     * Reverse the migrations safely.
     */
    public function down(): void
    {
        $tableName = $this->getTableName();

        if (SchemaTracker::wasCreatedByPackage('table', $tableName)) {
            Schema::dropIfExists($tableName);
            SchemaTracker::forgetTrackedItem('table', $tableName);
        }

        SchemaTracker::dropManifestIfEmpty();
    }
};
