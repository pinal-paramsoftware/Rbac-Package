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
        return config('rbac-suite.tables.password_rules', 'password_rules');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = $this->getTableName();

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id('RuleId');
                $table->string('RuleCode', 50)->unique();
                $table->string('RuleName', 100);
                $table->boolean('IsEnabled')->default(1);
                $table->integer('RuleValue')->nullable();
                $table->timestamp('CreatedOn')->nullable();
                $table->timestamp('UpdatedOn')->nullable();
                $table->boolean('IsActive')->default(1);
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
