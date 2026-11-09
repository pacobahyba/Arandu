<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Controle, pelo professor, da visibilidade das notas para os alunos.
 *
 * Uma linha por (ano, matéria, turma, período). Ausência de linha significa
 * "nunca configurado" — ver LiberacaoNotas::estaLiberado() para o padrão.
 *
 * origem_turma segue a convenção de regimes_avaliacao: 'S' = turma do Sophia,
 * 'C' = turma customizada (os ids das duas fontes podem colidir).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('liberacoes_notas')) {
            return;
        }

        Schema::create('liberacoes_notas', function (Blueprint $table) {
            $table->integer('id_liberacao', true);
            $table->integer('ano');
            $table->integer('id_materia');
            $table->integer('id_turma');
            $table->char('origem_turma', 1)->default('S');
            $table->string('periodo', 45);
            $table->boolean('liberado')->default(false);
            // Código Sophia do professor que fez a última alteração.
            $table->integer('codigo_professor')->nullable();
            $table->timestamps();

            $table->unique(
                ['ano', 'id_materia', 'id_turma', 'origem_turma', 'periodo'],
                'liberacoes_notas_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liberacoes_notas');
    }
};
