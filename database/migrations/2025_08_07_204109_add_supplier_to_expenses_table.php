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
        Schema::table('expenses', function (Blueprint $table) {
            // Add supplier relationship (nullable to maintain backward compatibility)
            $table->foreignId('supplier_id')->nullable()->after('expense_category_id')->constrained()->onDelete('cascade');

            // Keep existing vendor fields for backward compatibility but make them nullable
            $table->string('vendor_name')->nullable()->change();
            $table->string('vendor_document')->nullable()->change();
            $table->string('vendor_email')->nullable()->change();
            $table->string('vendor_phone')->nullable()->change();

            $table->index(['supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropIndex(['supplier_id']);
            $table->dropColumn('supplier_id');

            // Restore original constraints
            $table->string('vendor_name')->nullable(false)->change();
        });
    }
};
