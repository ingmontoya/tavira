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
        Schema::create('comunicado_confirmations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comunicado_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->foreign('comunicado_id')->references('id')->on('comunicados')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['comunicado_id', 'user_id']);
            $table->index(['user_id', 'confirmed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comunicado_confirmations');
    }
};
