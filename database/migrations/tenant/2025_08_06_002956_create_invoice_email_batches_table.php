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
        Schema::create('invoice_email_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // Batch configuration
            $table->json('filters'); // apartment_ids, invoice_periods, invoice_types, statuses
            $table->json('email_settings'); // template, subject, sender_name, reply_to

            // Batch statistics
            $table->integer('total_invoices')->default(0);
            $table->integer('total_recipients')->default(0);
            $table->integer('emails_sent')->default(0);
            $table->integer('emails_delivered')->default(0);
            $table->integer('emails_failed')->default(0);
            $table->integer('emails_opened')->default(0);
            $table->integer('emails_clicked')->default(0);

            // Batch status and timing
            $table->enum('status', ['draft', 'scheduled', 'processing', 'completed', 'failed', 'cancelled'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Processing details
            $table->text('failure_reason')->nullable();
            $table->json('processing_log')->nullable();
            $table->decimal('estimated_cost', 10, 4)->default(0);
            $table->decimal('actual_cost', 10, 4)->default(0);

            // User tracking
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['status', 'created_at']);
            $table->index(['created_by', 'created_at']);
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_email_batches');
    }
};
