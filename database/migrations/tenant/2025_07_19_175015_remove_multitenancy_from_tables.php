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
        // Remove conjunto_config_id from apartments table
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropForeign(['conjunto_config_id']);
            $table->dropUnique('apartments_conjunto_config_id_tower_number_unique');
            $table->dropIndex('apartments_conjunto_config_id_tower_floor_index');
            $table->dropColumn('conjunto_config_id');
        });

        // Remove conjunto_config_id from apartment_types table
        Schema::table('apartment_types', function (Blueprint $table) {
            $table->dropForeign(['conjunto_config_id']);
            $table->dropUnique(['conjunto_config_id', 'name']);
            $table->dropColumn('conjunto_config_id');
        });

        // Remove conjunto_config_id from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['conjunto_config_id']);
            $table->dropIndex(['role', 'conjunto_config_id']);
            $table->dropColumn(['role', 'conjunto_config_id']);
        });

        // Remove conjunto_config_id from payment_concepts table
        if (Schema::hasTable('payment_concepts')) {
            Schema::table('payment_concepts', function (Blueprint $table) {
                $table->dropForeign(['conjunto_config_id']);
                $table->dropIndex('payment_concepts_conjunto_config_id_type_index');
                $table->dropColumn('conjunto_config_id');
            });
        }

        // Remove conjunto_config_id from invoices table
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropForeign(['conjunto_config_id']);
                $table->dropIndex('invoices_conjunto_config_id_type_index');
                $table->dropColumn('conjunto_config_id');
            });
        }

        // Remove conjunto_config_id from payment_agreements table
        if (Schema::hasTable('payment_agreements')) {
            Schema::table('payment_agreements', function (Blueprint $table) {
                $table->dropForeign(['conjunto_config_id']);
                $table->dropIndex('payment_agreements_conjunto_config_id_status_index');
                $table->dropColumn('conjunto_config_id');
            });
        }

        // Drop conjunto_configs table entirely
        Schema::dropIfExists('conjunto_configs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate conjunto_configs table
        Schema::create('conjunto_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->integer('number_of_towers');
            $table->integer('floors_per_tower');
            $table->integer('apartments_per_floor');
            $table->boolean('is_active')->default(true);
            $table->json('tower_names')->nullable();
            $table->json('configuration_metadata')->nullable();
            $table->timestamps();
        });

        // Add back conjunto_config_id to apartments table
        Schema::table('apartments', function (Blueprint $table) {
            $table->foreignId('conjunto_config_id')->constrained()->onDelete('cascade');
        });

        // Add back conjunto_config_id to apartment_types table
        Schema::table('apartment_types', function (Blueprint $table) {
            $table->foreignId('conjunto_config_id')->constrained()->onDelete('cascade');
            $table->unique(['conjunto_config_id', 'name']);
        });

        // Add back columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['company', 'individual'])->default('individual');
            $table->foreignId('conjunto_config_id')->nullable()->constrained()->onDelete('set null');
        });

        // Add back conjunto_config_id to payment_concepts table
        if (Schema::hasTable('payment_concepts')) {
            Schema::table('payment_concepts', function (Blueprint $table) {
                $table->foreignId('conjunto_config_id')->constrained()->onDelete('cascade');
                $table->index(['conjunto_config_id', 'type']);
            });
        }

        // Add back conjunto_config_id to invoices table
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('conjunto_config_id')->constrained()->onDelete('cascade');
                $table->index(['conjunto_config_id', 'type']);
            });
        }

        // Add back conjunto_config_id to payment_agreements table
        if (Schema::hasTable('payment_agreements')) {
            Schema::table('payment_agreements', function (Blueprint $table) {
                $table->foreignId('conjunto_config_id')->constrained()->onDelete('cascade');
                $table->index(['conjunto_config_id', 'status']);
            });
        }
    }
};
