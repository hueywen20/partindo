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
        Schema::create('product_recipe_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('composite_product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->string('slot_name');          // e.g. "Filter Unit", "Gasket"
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_recipe_slots');
    }
};
