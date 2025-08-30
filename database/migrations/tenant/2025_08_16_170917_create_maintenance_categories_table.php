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
        Schema::create('maintenance_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained('conjunto_configs')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color')->default('#3B82F6');
            $table->integer('priority_level')->default(3); // 1=critical, 2=high, 3=medium, 4=low
            $table->boolean('requires_approval')->default(false);
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_categories');
    }
};
