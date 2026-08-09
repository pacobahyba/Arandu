<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Regime de períodos (Trimestral/Bimestral/Semestral) por ano letivo.
 *
 * Uma linha com id_turma = 0 e origem_turma = 'A' é o padrão do ano.
 * Linhas com id_turma real são exceções para aquela turma.
 *
 * Usamos os sentinelas 0/'A' em vez de NULL porque no MySQL um índice UNIQUE
 * não considera NULLs iguais entre si — com NULL seria possível gravar vários
 * padrões para o mesmo ano.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('regimes_avaliacao')) {
            return;
        }

        Schema::create('regimes_avaliacao', function (Blueprint $table) {
            $table->integer('id_regime', true);
            $table->integer('ano');
            $table->integer('id_turma')->default(0);
            // 'A' = todas as turmas (padrão do ano), 'S' = turma do Sophia,
            // 'C' = turma customizada (turmas_customizadas)
            $table->char('origem_turma', 1)->default('A');
            $table->string('regime', 3);
            $table->timestamps();

            $table->unique(['ano', 'id_turma', 'origem_turma'], 'regimes_avaliacao_ano_turma_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regimes_avaliacao');
    }
};
