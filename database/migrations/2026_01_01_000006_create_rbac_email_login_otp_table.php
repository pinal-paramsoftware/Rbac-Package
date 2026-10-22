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
        return config('rbac-suite.tables.email_login_otp', 'email_login_otp');
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
                $table->string('challenge_key', 100)->nullable()->unique();
                $table->integer('emp_id')->nullable();
                $table->string('email', 256)->nullable();
                $table->string('otp_type', 20);
                $table->string('otp_hash', 256);
                $table->unsignedInteger('attempt_count')->default(0);
                $table->dateTime('expiry_time');
                $table->dateTime('resend_available_at')->nullable();
                $table->boolean('is_used')->default(0);
                $table->dateTime('used_at')->nullable();
                $table->timestamps();

                $table->index(['emp_id', 'otp_type', 'is_used'], 'email_login_otp_lookup_idx');
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
