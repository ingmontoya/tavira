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
        Schema::create('invoice_email_settings', function (Blueprint $table) {
            $table->id();
            
            // Setting identification
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->default('general'); // general, templates, smtp, notifications
            
            // Setting value and configuration
            $table->text('value')->nullable();
            $table->json('options')->nullable(); // For select/multi-select type settings
            $table->enum('type', [
                'string', 
                'text', 
                'integer', 
                'decimal', 
                'boolean', 
                'select', 
                'multi_select',
                'json',
                'email',
                'url'
            ])->default('string');
            
            // Validation and constraints
            $table->json('validation_rules')->nullable(); // Laravel validation rules
            $table->text('default_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_encrypted')->default(false);
            
            // UI Configuration
            $table->string('input_type')->default('text'); // text, textarea, select, checkbox, etc.
            $table->text('help_text')->nullable();
            $table->string('placeholder')->nullable();
            $table->integer('sort_order')->default(0);
            
            // Environment and visibility
            $table->json('environments')->nullable(); // Which environments this setting applies to
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_editable')->default(true);
            $table->boolean('requires_restart')->default(false);
            
            // System tracking
            $table->boolean('is_system')->default(false); // System-defined vs user-defined
            $table->timestamp('last_modified_at')->nullable();
            $table->foreignId('last_modified_by')->nullable()->constrained('users');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['category', 'sort_order']);
            $table->index(['is_visible', 'is_editable']);
            $table->index('last_modified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_email_settings');
    }
};