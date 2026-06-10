<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_item_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_item_id')
                ->constrained('purchase_order_items')
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

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_item_components');
    }
};