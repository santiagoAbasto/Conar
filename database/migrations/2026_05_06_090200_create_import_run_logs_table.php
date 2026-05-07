<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_run_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_run_id')
                ->constrained('import_runs')
                ->cascadeOnDelete();
            $table->string('level', 20)->default('info');
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['import_run_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_run_logs');
    }
};
