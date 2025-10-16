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
        // Rename the suppliers table to providers
        Schema::rename('suppliers', 'providers');

        // Rename supplier_id to provider_id in expenses table
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropIndex(['supplier_id']);
            $table->renameColumn('supplier_id', 'provider_id');
        });

        // Re-add the foreign key with the new name
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign('provider_id')->references('id')->on('providers')->onDelete('cascade');
            $table->index(['provider_id']);
        });

        // Rename supplier_id to provider_id in maintenance_requests table
        if (Schema::hasTable('maintenance_requests') && Schema::hasColumn('maintenance_requests', 'supplier_id')) {
            Schema::table('maintenance_requests', function (Blueprint $table) {
                $table->dropForeign(['supplier_id']);
                $table->renameColumn('supplier_id', 'provider_id');
            });

            // Re-add the foreign key with the new name
            Schema::table('maintenance_requests', function (Blueprint $table) {
                $table->foreign('provider_id')->references('id')->on('providers')->onDelete('cascade');
            });
        }

        // Update indexes on providers table to match new naming
        Schema::table('providers', function (Blueprint $table) {
            // Add global_provider_id for synced resources (nullable for local providers)
            $table->unsignedBigInteger('global_provider_id')->nullable()->after('id');
            $table->index('global_provider_id');

            // Add category column (new field for provider categorization)
            $table->string('category')->nullable()->after('name');
        });

        // Drop old indexes and constraints by name (PostgreSQL renames them when table is renamed)
        \DB::statement('DROP INDEX IF EXISTS suppliers_conjunto_config_id_is_active_index');
        \DB::statement('DROP INDEX IF EXISTS suppliers_conjunto_config_id_name_index');
        \DB::statement('ALTER TABLE providers DROP CONSTRAINT IF EXISTS suppliers_conjunto_config_id_foreign');

        Schema::table('providers', function (Blueprint $table) {
            // Remove conjunto_config_id column since providers are now global/synced
            $table->dropColumn('conjunto_config_id');

            // Add new indexes
            $table->index(['is_active']);
            $table->index(['name']);
            $table->index(['category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert providers table changes
        Schema::table('providers', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['name']);
            $table->dropIndex(['category']);
            $table->dropIndex(['global_provider_id']);
            $table->dropColumn('global_provider_id');

            // Re-add conjunto_config_id
            $table->foreignId('conjunto_config_id')->after('id')->constrained()->onDelete('cascade');
            $table->index(['conjunto_config_id', 'is_active']);
            $table->index(['conjunto_config_id', 'name']);
        });

        // Revert maintenance_requests if exists
        if (Schema::hasTable('maintenance_requests') && Schema::hasColumn('maintenance_requests', 'provider_id')) {
            Schema::table('maintenance_requests', function (Blueprint $table) {
                $table->dropForeign(['provider_id']);
                $table->renameColumn('provider_id', 'supplier_id');
            });

            Schema::table('maintenance_requests', function (Blueprint $table) {
                $table->foreign('supplier_id')->references('id')->on('providers')->onDelete('cascade');
            });
        }

        // Revert expenses table
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['provider_id']);
            $table->dropIndex(['provider_id']);
            $table->renameColumn('provider_id', 'supplier_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign('supplier_id')->references('id')->on('providers')->onDelete('cascade');
            $table->index(['supplier_id']);
        });

        // Rename table back
        Schema::rename('providers', 'suppliers');
    }
};
