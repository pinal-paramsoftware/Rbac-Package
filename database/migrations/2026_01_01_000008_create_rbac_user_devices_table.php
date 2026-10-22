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
        return config('rbac-suite.tables.user_devices', 'user_devices');
    }

    /**
     * Resolve the target users table name.
     */
    protected function getUsersTable(): string
    {
        $userModel = config('rbac-suite.user_model');
        if ($userModel && class_exists($userModel)) {
            return (new $userModel)->getTable();
        }

        return config('rbac-suite.tables.users', 'users');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = $this->getTableName();
        $usersTable = $this->getUsersTable();

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) use ($usersTable) {
                $table->id();

                if (Schema::hasTable($usersTable)) {
                    $table->foreignId('user_id')->constrained($usersTable)->cascadeOnDelete();
                } else {
                    $table->unsignedBigInteger('user_id')->index();
                }

                $table->string('device_uuid', 64)->unique();
                $table->string('device_name', 255)->default('Authorized Device');
                $table->string('device_mac', 32)->nullable()->index();
                $table->string('device_token_hash', 64);
                $table->boolean('is_authorized')->default(true);
                $table->timestamp('last_authenticated_at')->nullable();
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
