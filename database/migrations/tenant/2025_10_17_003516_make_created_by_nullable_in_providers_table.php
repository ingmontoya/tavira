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
        // Try to drop the foreign key constraint if it exists (PostgreSQL)
        \DB::statement('ALTER TABLE providers DROP CONSTRAINT IF EXISTS providers_created_by_foreign');
        \DB::statement('ALTER TABLE providers DROP CONSTRAINT IF EXISTS suppliers_created_by_foreign');

        Schema::table('providers', function (Blueprint $table) {
            // Make created_by nullable for synced providers
            $table->foreignId('created_by')->nullable()->change();

            // Re-add the foreign key constraint allowing null values
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            // Drop the nullable foreign key
            $table->dropForeign(['created_by']);

            // Note: Can't make created_by NOT NULL again without ensuring all records have values
            // This would require assigning a user to all synced providers
        });
    }
};
