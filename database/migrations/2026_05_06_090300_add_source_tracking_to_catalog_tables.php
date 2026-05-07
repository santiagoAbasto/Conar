<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'product_categories',
            'product_subcategories',
            'products',
            'product_specifications',
            'product_downloads',
            'product_images',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('source_provider', 60)->nullable()->after('id');
                $table->string('source_id', 191)->nullable()->after('source_provider');
                $table->text('source_url')->nullable()->after('source_id');
                $table->foreignId('last_import_run_id')
                    ->nullable()
                    ->after('source_url')
                    ->constrained('import_runs')
                    ->nullOnDelete();

                $table->index(['source_provider', 'source_id']);
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'product_categories',
            'product_subcategories',
            'products',
            'product_specifications',
            'product_downloads',
            'product_images',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex([$table->getTable() . '_source_provider_source_id_index']);
                $table->dropConstrainedForeignId('last_import_run_id');
                $table->dropColumn([
                    'source_provider',
                    'source_id',
                    'source_url',
                ]);
            });
        }
    }
};
