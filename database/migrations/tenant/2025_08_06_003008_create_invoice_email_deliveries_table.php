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
        Schema::create('invoice_email_deliveries', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('batch_id')->constrained('invoice_email_batches')->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('apartment_id')->constrained('apartments');

            // Recipient information
            $table->string('recipient_email');
            $table->string('recipient_name');
            $table->string('apartment_number');

            // Email details
            $table->string('email_subject');
            $table->text('email_template_used');
            $table->json('email_variables')->nullable(); // Template variables used
            $table->json('attachments')->nullable(); // List of attached files

            // Delivery status and tracking
            $table->enum('status', [
                'pending',      // Queued but not sent
                'sending',      // Currently being sent
                'sent',         // Successfully sent to email server
                'delivered',    // Confirmed delivered to recipient
                'opened',       // Recipient opened the email
                'clicked',      // Recipient clicked a link
                'bounced',      // Email bounced
                'failed',       // Failed to send
                'rejected',     // Rejected by email server
                'complained',   // Marked as spam
                'unsubscribed',  // Recipient unsubscribed
            ])->default('pending');

            // Timing information
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Error tracking
            $table->text('failure_reason')->nullable();
            $table->string('bounce_type')->nullable(); // soft, hard
            $table->json('smtp_response')->nullable();

            // Email service provider tracking
            $table->string('provider')->nullable(); // sendgrid, ses, mailgun, etc.
            $table->string('provider_message_id')->nullable();
            $table->json('provider_metadata')->nullable();

            // Retry mechanism
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();

            // Cost tracking
            $table->decimal('cost', 8, 4)->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['batch_id', 'status']);
            $table->index(['invoice_id', 'status']);
            $table->index(['apartment_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['recipient_email', 'batch_id']);
            $table->index('provider_message_id');
            $table->index('next_retry_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_email_deliveries');
    }
};
