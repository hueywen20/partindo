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
            $table->string('sale_inv_no')->nullable()->after('date');
            $table->decimal('tax', 5, 2)->default(0)->after('customer_id');
            $table->decimal('discount', 12, 2)->default(0)->after('tax');
            $table->decimal('grand_total', 12, 2)->default(0)->after('discount');
            $table->decimal('final_total', 12, 2)->default(0)->after('grand_total');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('total', 12, 2)->default(0)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['sale_inv_no', 'tax', 'discount', 'grand_total', 'final_total']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('total');
        });
    }
};
