<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table) {

            $table->id();

            // selección técnica
            $table->string('instrumental')->nullable();
            $table->string('solution')->nullable();

            $table->integer('quantity')->nullable();

            $table->string('attachment')->nullable();

            // datos contacto
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('company')->nullable();

            $table->text('message')->nullable();

            // estado interno
            $table->boolean('is_read')->default(false)->index();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};