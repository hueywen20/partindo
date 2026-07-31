<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();

            $table->string('return_no')->unique();
            $table->date('date');

            // Returns must reference the original invoice — protects
            // referential integrity, so a Sale can't be deleted out from
            // under an existing return record.
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();

            // Denormalized for fast per-customer queries (statement of
            // account, debt report) without joining through sales.
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();

            // pending -> approved | rejected. Stock/balance effects only
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

        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_return_id')->constrained()->cascadeOnDelete();

            // Link to the specific original sale line, so we know exactly
            // what's being returned and can't return more than was sold.
            $table->foreignId('sale_item_id')->constrained()->restrictOnDelete();

            $table->foreignId('product_id')->constrained();

            $table->string('part_no')->nullable();
            $table->string('brand')->nullable();

            $table->decimal('qty', 12, 2)->default(0);

            // Snapshots from the original sale item at the time of return,
            // so this stays accurate even if the product's price/cost
            // changes later.
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('cost_price', 12, 2)->default(0);

            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
    }
};