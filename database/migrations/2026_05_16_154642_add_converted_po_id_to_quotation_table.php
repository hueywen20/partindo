<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            // Proper FK to purchase_orders for the PO conversion path
            $table->foreignId('converted_to_po_id')
                ->nullable()
                ->after('converted_to_sale_id')
                ->constrained('purchase_orders')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropForeign(['converted_to_po_id']);
            $table->dropColumn('converted_to_po_id');
        });
    }
};
