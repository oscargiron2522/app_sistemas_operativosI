<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('algoritmo');
            $table->unsignedInteger('quantum')->nullable();
            $table->json('procesos');
            $table->json('resultado');
            $table->decimal('espera_promedio', 10, 2);
            $table->decimal('retorno_promedio', 10, 2);
            $table->decimal('utilizacion', 6, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulaciones');
    }
};
