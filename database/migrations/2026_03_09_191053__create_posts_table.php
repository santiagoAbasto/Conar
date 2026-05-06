<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {

            $table->id();

            // multi idioma
            $table->json('title');

            $table->json('excerpt')->nullable();

            $table->json('content')->nullable();

            // URL
            $table->string('slug')->unique();

            // imagen de la card
            $table->string('image')->nullable();

            // PRODUCTOS / EMPRESA
            $table->string('category')->nullable()->index();

            // destacado en home
            $table->boolean('is_featured')->default(false)->index();

            // activo / inactivo
            $table->boolean('is_active')->default(true)->index();

            // fecha publicación
            $table->timestamp('published_at')->nullable()->index();

            // orden manual
            $table->string('order', 10)->default('A')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};