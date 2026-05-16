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
        //
        // purchase_orders: add missing tax, discount, quotation_id
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_orders', 'quotation_id')) {
                $table->foreignId('quotation_id')
                    ->nullable()
                    ->after('date')
                    ->constrained('quotations')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('purchase_orders', 'tax')) {
                $table->decimal('tax', 5, 2)->default(0)->after('status');
            }
            if (! Schema::hasColumn('purchase_orders', 'discount')) {
                $table->decimal('discount', 12, 2)->default(0)->after('tax');
            }
            if (! Schema::hasColumn('purchase_orders', 'converted_to_sale_id')) {
                $table->unsignedBigInteger('converted_to_sale_id')->nullable()->after('final_total');
            }
        });
 
        // purchase_order_items: add missing category column
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_order_items', 'category')) {
                $table->string('category')->nullable()->after('product_id');
            }
        });
 
        // sale_items: add missing category column (used by SaleForm)
        // NOTE: if you do NOT want category on sale_items, remove it from SaleForm instead.
        // This migration adds it so the form does not throw a column-not-found error.
        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'category')) {
                $table->string('category')->nullable()->after('product_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['quotation_id']);
            $table->dropColumn(['quotation_id', 'tax', 'discount', 'converted_to_sale_id']);
        });
 
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('category');
        });
 
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
