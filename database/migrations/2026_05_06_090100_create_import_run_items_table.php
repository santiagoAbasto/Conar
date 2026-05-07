<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_run_id')
                ->constrained('import_runs')
                ->cascadeOnDelete();
            $table->string('entity_type', 120);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('action', 30);
            $table->string('source_key', 191)->nullable();
            $table->string('entity_label', 191)->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['import_run_id', 'entity_type']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_run_items');
    }
};
