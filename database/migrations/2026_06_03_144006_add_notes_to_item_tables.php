<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('total');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('total');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('total');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('grand_total');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items',           fn ($t) => $t->dropColumn('notes'));
        Schema::table('quotation_items',      fn ($t) => $t->dropColumn('notes'));
        Schema::table('purchase_order_items', fn ($t) => $t->dropColumn('notes'));
        Schema::table('purchase_items',       fn ($t) => $t->dropColumn('notes'));
    }
};