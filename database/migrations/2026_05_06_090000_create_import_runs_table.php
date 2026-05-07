<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source_provider', 60);
            $table->string('source_type', 60);
            $table->string('scope', 120)->nullable();
            $table->text('source_url')->nullable();
            $table->json('options')->nullable();
            $table->json('counters')->nullable();
            $table->string('status', 30)->default('pending');
            $table->boolean('dry_run')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['source_provider', 'source_type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_runs');
    }
};
