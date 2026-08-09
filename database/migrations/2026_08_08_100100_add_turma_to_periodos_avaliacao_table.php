<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite calendários específicos por turma.
 *
 * Linhas existentes recebem id_turma = 0 / origem_turma = 'A' pelos defaults,
 * virando automaticamente o calendário padrão do ano — que é exatamente o
 * comportamento que elas já tinham.
 *
 * A coluna `ano` também é criada aqui se estiver ausente: ela é usada em todo
 * o código (PeriodoAvaliacao::$fillable, routes/api.php) mas nunca teve
 * migration — foi adicionada à mão no banco de produção.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('periodos_avaliacao')) {
            return;
        }

        Schema::table('periodos_avaliacao', function (Blueprint $table) {
            if (! Schema::hasColumn('periodos_avaliacao', 'ano')) {
                $table->integer('ano')->nullable();
            }
            if (! Schema::hasColumn('periodos_avaliacao', 'id_turma')) {
                $table->integer('id_turma')->default(0);
            }
            if (! Schema::hasColumn('periodos_avaliacao', 'origem_turma')) {
                $table->char('origem_turma', 1)->default('A');
            }
        });

        // Índice separado do bloco acima para não falhar caso as colunas já existissem.
        Schema::table('periodos_avaliacao', function (Blueprint $table) {
            $table->index(['ano', 'periodo', 'id_turma'], 'periodos_avaliacao_ano_periodo_turma_idx');
        });
    }

    public function down(): void
    {
        Schema::table('periodos_avaliacao', function (Blueprint $table) {
            $table->dropIndex('periodos_avaliacao_ano_periodo_turma_idx');
            $table->dropColumn(['id_turma', 'origem_turma']);
        });
    }
};
