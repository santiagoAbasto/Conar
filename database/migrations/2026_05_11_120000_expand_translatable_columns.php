<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->columns() as $table => $columns) {
            foreach ($columns as $column => $type) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$type} NULL");
            }
        }
    }

    public function down(): void
    {
        //
    }

    private function columns(): array
    {
        return [
            'products' => [
                'name' => 'LONGTEXT',
                'subtitle' => 'LONGTEXT',
                'short_description' => 'LONGTEXT',
                'description' => 'LONGTEXT',
            ],
            'product_categories' => [
                'name' => 'LONGTEXT',
                'description' => 'LONGTEXT',
            ],
            'product_subcategories' => [
                'name' => 'LONGTEXT',
            ],
            'industries' => [
                'title' => 'LONGTEXT',
                'name' => 'LONGTEXT',
                'description' => 'LONGTEXT',
            ],
            'posts' => [
                'title' => 'LONGTEXT',
                'excerpt' => 'LONGTEXT',
                'content' => 'LONGTEXT',
                'category' => 'VARCHAR(500)',
            ],
            'product_specifications' => [
                'name' => 'LONGTEXT',
                'value' => 'LONGTEXT',
            ],
            'product_downloads' => [
                'name' => 'LONGTEXT',
            ],
            'downloads' => [
                'name' => 'LONGTEXT',
                'category' => 'LONGTEXT',
                'description' => 'LONGTEXT',
            ],
            'hero_slides' => [
                'title' => 'LONGTEXT',
                'subtitle' => 'LONGTEXT',
                'content' => 'LONGTEXT',
                'button_text' => 'LONGTEXT',
            ],
            'seo_metas' => [
                'title' => 'LONGTEXT',
                'description' => 'LONGTEXT',
                'keywords' => 'LONGTEXT',
            ],
            'technical_fields' => [
                'label' => 'LONGTEXT',
            ],
        ];
    }
};
