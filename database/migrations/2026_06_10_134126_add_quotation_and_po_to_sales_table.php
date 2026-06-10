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
        Schema::table('sales', function (Blueprint $table) {
            //
            $table->foreignId('quotation_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('quotations')
                ->nullOnDelete();
 
            $table->foreignId('purchase_order_id')
                ->nullable()
                ->after('quotation_id')
                ->constrained('purchase_orders')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            //
            $table->dropForeignIdFor(\App\Models\Quotation::class);
            $table->dropForeignIdFor(\App\Models\PurchaseOrder::class);
            $table->dropColumn(['quotation_id', 'purchase_order_id']);
        });
    }
};
