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
        Schema::create('porcentagem_objetivos', function (Blueprint $table) {
            $table->integer('id_porc_obj', true);
            $table->integer('porcentagem_conceitual');
            $table->integer('porcentagem_atitudinal');
            $table->integer('id_materia');
            $table->integer('id_turma');
            $table->string('periodo', 45);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('porcentagem_objetivos');
    }
};
