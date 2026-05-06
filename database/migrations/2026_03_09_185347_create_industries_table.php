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
        Schema::create('industries', function (Blueprint $table) {

            $table->id();

            // título interno / SEO (no visible en el grid)
            $table->string('title')->nullable();

            // nombre visible en el sitio
            $table->string('name');

            // slug para URLs
            $table->string('slug')->unique();

            // descripción de la industria (para página individual)
            $table->text('description')->nullable();

            // imagen del grid de industrias
            $table->string('image')->nullable();

            // orden manual (A, AA, AB, AC...)
            $table->string('order', 10)->default('A')->index();

            // activar / desactivar industria
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('industries');
    }
};