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
        Schema::create('products', function (Blueprint $table) {

            $table->id();

            $table->foreignId('category_id')
                ->constrained('product_categories')
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('slug')->unique();

            // subtitulo que vimos en el figma
            $table->string('subtitle')->nullable();

            // descripción corta del producto
            $table->text('short_description')->nullable();

            // descripción larga
            $table->longText('description')->nullable();

            // imagen principal
            $table->string('image')->nullable();

            // orden tipo A AA AB
            $table->string('order')->nullable();

            $table->boolean('is_featured')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};