<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procesos_prueba', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pid');
            $table->string('inicio_proc'); // starttime de /proc/PID/stat, evita confundir un PID reutilizado
            $table->string('comando');
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procesos_prueba');
    }
};
