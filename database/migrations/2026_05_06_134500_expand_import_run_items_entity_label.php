<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE import_run_items MODIFY entity_label TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE import_run_items MODIFY entity_label VARCHAR(191) NULL');
    }
};
