<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds unique constraints on sale_inv_no / purchase_inv_no.
     * (quotation_no and po_no already carry unique() from their original
     * create-table migrations, so they're untouched here.)
     *
     * Wrapped in try/catch because this table may already have the index —
     * either from an earlier partially-applied migration attempt (local
     * dev databases) or not at all (a fresh database, e.g. a new Render
     * deploy). Any other kind of failure is re-thrown as normal.
     */
    public function up(): void
    {
        $this->addUniqueIfMissing('sales', 'sale_inv_no');
        $this->addUniqueIfMissing('purchases', 'purchase_inv_no');
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['sale_inv_no']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique(['purchase_inv_no']);
        });
    }

    private function addUniqueIfMissing(string $table, string $column): void
    {
        try {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($column) {
                $tableBlueprint->unique($column);
            });
        } catch (QueryException $e) {
            $message = $e->getMessage();

            $alreadyExists = str_contains($message, 'already exists')  // SQLite
                || str_contains($message, 'Duplicate key name')         // MySQL
                || str_contains($message, 'already exists')             // PostgreSQL phrasing overlaps
                || str_contains($message, '1061');                      // MySQL error code, duplicate key

            if (! $alreadyExists) {
                throw $e;
            }
        }
    }
};