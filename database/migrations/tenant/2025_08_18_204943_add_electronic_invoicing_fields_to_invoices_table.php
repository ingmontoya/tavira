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
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('electronic_invoice_status', ['pending', 'sent', 'failed'])->nullable()->after('status');
            $table->string('electronic_invoice_uuid')->nullable()->after('electronic_invoice_status');
            $table->string('electronic_invoice_cufe')->nullable()->after('electronic_invoice_uuid');
            $table->timestamp('electronic_invoice_sent_at')->nullable()->after('electronic_invoice_cufe');
            $table->text('electronic_invoice_error')->nullable()->after('electronic_invoice_sent_at');
            $table->boolean('can_be_electronic')->default(false)->after('electronic_invoice_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'electronic_invoice_status',
                'electronic_invoice_uuid',
                'electronic_invoice_cufe',
                'electronic_invoice_sent_at',
                'electronic_invoice_error',
                'can_be_electronic',
            ]);
        });
    }
};
