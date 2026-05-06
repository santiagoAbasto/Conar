<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_downloads', function (Blueprint $table) {

            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // nombre del archivo
            $table->string('name');

            // archivo guardado
            $table->string('file');

            // tipo de archivo
            $table->string('type')->nullable(); 
            // ejemplo: PDF, DOC, Certificado

            // tamaño del archivo
            $table->string('size')->nullable();

            // orden tipo A AA AB
            $table->string('order')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_downloads');
    }
};