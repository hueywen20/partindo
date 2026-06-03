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
        Schema::create('sale_item_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_item_id')
                ->constrained('sale_items')
                ->cascadeOnDelete();
            $table->foreignId('slot_id')
                ->constrained('product_recipe_slots')
                ->cascadeOnDelete();
            $table->foreignId('chosen_product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->unsignedInteger('qty_used');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_item_components');
    }
};
