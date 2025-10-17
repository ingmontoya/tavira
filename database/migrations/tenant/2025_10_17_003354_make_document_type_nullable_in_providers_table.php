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
        Schema::table('providers', function (Blueprint $table) {
            // Make document_type and document_number nullable to align with central database structure
            $table->string('document_type')->nullable()->change();
            $table->string('document_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            // Revert to NOT NULL with default values
            $table->string('document_type')->default('NIT')->change();
            // Note: Can't make document_number NOT NULL again without ensuring all records have values
            // Leave it nullable on rollback to prevent data loss
        });
    }
};
