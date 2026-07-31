<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();

            $table->string('return_no')->unique();
            $table->date('date');

            $table->foreignId('purchase_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();

            // pending -> approved | rejected. Stock/cost effects only
            // apply once a return is approved.
            $table->string('status')->default('pending');

            $table->string('reason')->nullable();
            $table->text('notes')->nullable();

            $table->decimal('tax', 5, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('final_total', 12, 2)->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();

            // Link to the specific original purchase line being returned.
            $table->foreignId('purchase_item_id')->constrained()->restrictOnDelete();

            $table->foreignId('product_id')->constrained();

            $table->string('part_no')->nullable();
            $table->string('brand')->nullable();

            $table->decimal('qty', 12, 2)->default(0);

            // Tax-inclusive unit cost snapshot, matching how avg_cost is
            // computed elsewhere (see PurchaseItemObserver).
            $table->decimal('price', 12, 2)->default(0);

            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
    }
};