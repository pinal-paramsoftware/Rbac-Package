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
        return config('rbac-suite.tables.authentication_settings', 'authentication_settings');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = $this->getTableName();

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id('AuthSettingId');
                $table->string('AuthCode', 50)->unique();
                $table->string('AuthName', 100);
                $table->boolean('IsEnabled')->default(1);
                $table->integer('OTPAttempt')->nullable();
                $table->integer('OTPResetTime')->nullable();
                $table->integer('OTPExpiryTime')->nullable();
                $table->timestamp('CreatedOn')->nullable();
                $table->timestamp('UpdatedOn')->nullable();
                $table->timestamp('DeletionDate')->nullable();
                $table->boolean('IsActive')->nullable()->default(1);
                $table->integer('UserId')->nullable();
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
