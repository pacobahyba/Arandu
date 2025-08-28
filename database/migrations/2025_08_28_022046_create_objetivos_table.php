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
        Schema::create('objetivos', function (Blueprint $table) {
            $table->integer('id_objetivo', true);
            $table->integer('id_turma');
            $table->integer('id_materia');
            $table->string('periodo_objetivo', 45);
            $table->string('nome_objetivo', 200);
            $table->string('tipo_objetivo', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('objetivos');
    }
};
