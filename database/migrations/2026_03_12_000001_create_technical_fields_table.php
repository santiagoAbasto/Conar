<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technical_fields', function (Blueprint $table) {

            $table->id();

            $table->foreignId('subcategory_id')
                ->constrained('product_subcategories')
                ->cascadeOnDelete();

            $table->string('label');   // Ej: "Fluido"
            $table->string('name');    // Ej: "fluido" (clave del array process_data)
            $table->string('type')->default('text'); // text | number | select
            $table->integer('order')->default(0);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technical_fields');
    }
};
