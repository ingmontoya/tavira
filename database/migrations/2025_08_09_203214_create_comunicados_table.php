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
        Schema::create('comunicados', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('priority')->default('normal'); // urgent, important, normal
            $table->string('type')->default('general'); // general, administrative, maintenance, emergency
            $table->string('status')->default('published'); // draft, published, archived
            $table->boolean('is_pinned')->default(false);
            $table->boolean('requires_confirmation')->default(false);
            $table->json('attachments')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            $table->index(['status', 'published_at']);
            $table->index(['priority', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comunicados');
    }
};
