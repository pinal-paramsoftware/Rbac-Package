<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Parth\LaravelRbacSuite\Support\SchemaTracker;

return new class extends Migration
{
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
        $tableName = $this->getUsersTable();

        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (! Schema::hasColumn($tableName, 'address')) {
                $table->text('address')->nullable();
                SchemaTracker::recordCreatedColumn($tableName, 'address');
            }

            if (! Schema::hasColumn($tableName, 'mobile_number')) {
                $table->string('mobile_number', 20)->nullable();
                SchemaTracker::recordCreatedColumn($tableName, 'mobile_number');
            }

            if (! Schema::hasColumn($tableName, 'mobile_verified_at')) {
                $table->timestamp('mobile_verified_at')->nullable();
                SchemaTracker::recordCreatedColumn($tableName, 'mobile_verified_at');
            }

            if (! Schema::hasColumn($tableName, 'is_active')) {
                $table->string('is_active', 1)->default('1');
                SchemaTracker::recordCreatedColumn($tableName, 'is_active');
            }

            if (! Schema::hasColumn($tableName, 'created_id')) {
                $table->unsignedBigInteger('created_id')->nullable();
                SchemaTracker::recordCreatedColumn($tableName, 'created_id');
            }

            if (! Schema::hasColumn($tableName, 'updated_id')) {
                $table->unsignedBigInteger('updated_id')->nullable();
                SchemaTracker::recordCreatedColumn($tableName, 'updated_id');
            }

            if (! Schema::hasColumn($tableName, 'deleted_id')) {
                $table->unsignedBigInteger('deleted_id')->nullable();
                SchemaTracker::recordCreatedColumn($tableName, 'deleted_id');
            }

            if (! Schema::hasColumn($tableName, 'deleted_at')) {
                $table->softDeletes();
                SchemaTracker::recordCreatedColumn($tableName, 'deleted_at');
            }

            if (! Schema::hasColumn($tableName, 'password_updated_at')) {
                $table->timestamp('password_updated_at')->nullable();
                SchemaTracker::recordCreatedColumn($tableName, 'password_updated_at');
            }

            if (! Schema::hasColumn($tableName, 'password_updated_id')) {
                $table->unsignedBigInteger('password_updated_id')->nullable();
                SchemaTracker::recordCreatedColumn($tableName, 'password_updated_id');
            }
        });
    }

    /**
     * Reverse the migrations safely without dropping pre-existing host columns.
     */
    public function down(): void
    {
        $tableName = $this->getUsersTable();

        if (! Schema::hasTable($tableName)) {
            return;
        }

        $candidates = [
            'password_updated_id',
            'password_updated_at',
            'deleted_id',
            'updated_id',
            'created_id',
            'is_active',
            'mobile_verified_at',
            'mobile_number',
            'address',
            'deleted_at',
        ];

        $columnsToDrop = [];

        foreach ($candidates as $column) {
            if (Schema::hasColumn($tableName, $column) && SchemaTracker::wasCreatedByPackage('column', $tableName, $column)) {
                $columnsToDrop[] = $column;
                SchemaTracker::forgetTrackedItem('column', $tableName, $column);
            }
        }

        if (! empty($columnsToDrop)) {
            Schema::table($tableName, function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }

        SchemaTracker::dropManifestIfEmpty();
    }
};
