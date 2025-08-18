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
        Schema::table('maintenance_requests', function (Blueprint $table) {
            // Optional vendor relationship for external contractors
            $table->foreignId('supplier_id')->nullable()->after('assigned_staff_id')->constrained('suppliers')->onDelete('set null');

            // Vendor quote information
            $table->decimal('vendor_quote_amount', 10, 2)->nullable()->after('actual_cost');
            $table->text('vendor_quote_description')->nullable()->after('vendor_quote_amount');
            $table->json('vendor_quote_attachments')->nullable()->after('vendor_quote_description'); // Array of file paths/URLs
            $table->date('vendor_quote_valid_until')->nullable()->after('vendor_quote_attachments');

            // Project type indicator
            $table->enum('project_type', ['internal', 'external'])->default('internal')->after('priority');

            // Additional vendor contact info (if different from supplier record)
            $table->string('vendor_contact_name')->nullable()->after('vendor_quote_valid_until');
            $table->string('vendor_contact_phone')->nullable()->after('vendor_contact_name');
            $table->string('vendor_contact_email')->nullable()->after('vendor_contact_phone');

            // Indexes for better performance
            $table->index(['supplier_id', 'status']);
            $table->index(['project_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropIndex(['supplier_id', 'status']);
            $table->dropIndex(['project_type', 'status']);

            $table->dropColumn([
                'supplier_id',
                'vendor_quote_amount',
                'vendor_quote_description',
                'vendor_quote_attachments',
                'vendor_quote_valid_until',
                'project_type',
                'vendor_contact_name',
                'vendor_contact_phone',
                'vendor_contact_email',
            ]);
        });
    }
};
