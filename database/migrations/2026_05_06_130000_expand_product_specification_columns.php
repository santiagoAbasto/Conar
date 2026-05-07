<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE product_specifications MODIFY name TEXT NOT NULL');
        DB::statement('ALTER TABLE product_specifications MODIFY value TEXT NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE product_specifications MODIFY name VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE product_specifications MODIFY value VARCHAR(255) NOT NULL');
    }
};
