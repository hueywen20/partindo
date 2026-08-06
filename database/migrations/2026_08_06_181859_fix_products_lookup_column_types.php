<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
        DB::statement("ALTER TABLE products ALTER COLUMN brand TYPE bigint USING NULLIF(brand, '')::bigint");
        DB::statement("ALTER TABLE products ALTER COLUMN category TYPE bigint USING NULLIF(category, '')::bigint");
        DB::statement("ALTER TABLE products ALTER COLUMN uom TYPE bigint USING NULLIF(uom, '')::bigint");
        DB::statement("ALTER TABLE products ALTER COLUMN location TYPE bigint USING NULLIF(location, '')::bigint");

        Schema::table('products', function ($table) {
            $table->foreign('brand')->references('id')->on('brands')->nullOnDelete();
            $table->foreign('category')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('uom')->references('id')->on('uoms')->restrictOnDelete();
            $table->foreign('location')->references('id')->on('product_locations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('products', function ($table) {
            $table->dropForeign(['brand']);
            $table->dropForeign(['category']);
            $table->dropForeign(['uom']);
            $table->dropForeign(['location']);
        });

        DB::statement("ALTER TABLE products ALTER COLUMN brand TYPE character varying(255)");
        DB::statement("ALTER TABLE products ALTER COLUMN category TYPE character varying(255)");
        DB::statement("ALTER TABLE products ALTER COLUMN uom TYPE character varying(255)");
        DB::statement("ALTER TABLE products ALTER COLUMN location TYPE character varying(255)");
    }
};
