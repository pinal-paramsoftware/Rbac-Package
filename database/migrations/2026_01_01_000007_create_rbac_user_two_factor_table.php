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
        return config('rbac-suite.tables.user_two_factor', 'user_two_factor');
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
                $table->integer('emp_id')->nullable();
                $table->integer('user_id')->nullable();
                $table->string('secret_key', 100)->nullable();
                $table->string('google2fa_secret', 255)->nullable();
                $table->boolean('is_enabled')->default(0);
                $table->boolean('google2fa_enabled')->default(0);
                $table->boolean('is_active')->nullable()->default(1);
                $table->boolean('is_setup_completed')->default(0)->nullable();
                $table->unsignedBigInteger('last_verified_window')->nullable();
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
