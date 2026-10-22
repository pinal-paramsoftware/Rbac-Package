<?php

namespace Parth\LaravelRbacSuite\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchemaTracker
{
    /**
     * Internal manifest table name.
     */
    public const MANIFEST_TABLE = 'rbac_suite_schema_manifest';

    /**
     * Ensure the manifest table exists.
     */
    public static function ensureManifestTable(): void
    {
        if (! Schema::hasTable(self::MANIFEST_TABLE)) {
            Schema::create(self::MANIFEST_TABLE, function (Blueprint $table) {
                $table->id();
                $table->string('item_type', 20); // 'table', 'column'
                $table->string('table_name', 100);
                $table->string('column_name', 100)->nullable();
                $table->timestamps();

                $table->index(['item_type', 'table_name', 'column_name'], 'rbac_manifest_lookup_idx');
            });
        }
    }

    /**
     * Record that a table was created by the package.
     */
    public static function recordCreatedTable(string $table): void
    {
        self::ensureManifestTable();

        $exists = DB::table(self::MANIFEST_TABLE)
            ->where('item_type', 'table')
            ->where('table_name', $table)
            ->whereNull('column_name')
            ->exists();

        if (! $exists) {
            DB::table(self::MANIFEST_TABLE)->insert([
                'item_type' => 'table',
                'table_name' => $table,
                'column_name' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Record that a column was created by the package.
     */
    public static function recordCreatedColumn(string $table, string $column): void
    {
        self::ensureManifestTable();

        $exists = DB::table(self::MANIFEST_TABLE)
            ->where('item_type', 'column')
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->exists();

        if (! $exists) {
            DB::table(self::MANIFEST_TABLE)->insert([
                'item_type' => 'column',
                'table_name' => $table,
                'column_name' => $column,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Check if a table or column was created by this package.
     */
    public static function wasCreatedByPackage(string $type, string $table, ?string $column = null): bool
    {
        if (! Schema::hasTable(self::MANIFEST_TABLE)) {
            return false;
        }

        $query = DB::table(self::MANIFEST_TABLE)
            ->where('item_type', $type)
            ->where('table_name', $table);

        if ($column !== null) {
            $query->where('column_name', $column);
        } else {
            $query->whereNull('column_name');
        }

        return $query->exists();
    }

    /**
     * Remove a tracked item after it has been rolled back.
     */
    public static function forgetTrackedItem(string $type, string $table, ?string $column = null): void
    {
        if (! Schema::hasTable(self::MANIFEST_TABLE)) {
            return;
        }

        $query = DB::table(self::MANIFEST_TABLE)
            ->where('item_type', $type)
            ->where('table_name', $table);

        if ($column !== null) {
            $query->where('column_name', $column);
        } else {
            $query->whereNull('column_name');
        }

        $query->delete();
    }

    /**
     * Drop the manifest table if no more package items are tracked.
     */
    public static function dropManifestIfEmpty(): void
    {
        if (Schema::hasTable(self::MANIFEST_TABLE)) {
            if (DB::table(self::MANIFEST_TABLE)->count() === 0) {
                Schema::dropIfExists(self::MANIFEST_TABLE);
            }
        }
    }
}
