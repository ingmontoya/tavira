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
        Schema::create('work_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->onDelete('cascade');
            $table->enum('item_type', ['material', 'tool', 'service'])->default('material');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_cost', 8, 2)->nullable();
            $table->decimal('total_cost', 8, 2)->nullable();
            $table->string('supplier')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->index(['work_order_id', 'item_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_items');
    }
};
