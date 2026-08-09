<?php
// app/Models/PeriodoAvaliacao.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodoAvaliacao extends Model
{
    protected $table = 'periodos_avaliacao';

    protected $fillable = [
        'periodo',
        'dt_primeiro_dia',
        'dt_ultimo_dia',
        'ano',
        'id_turma',
        'origem_turma',
    ];

    protected $casts = [
        'ano'      => 'integer',
        'id_turma' => 'integer',
    ];

    protected $primaryKey = 'id_periodo_avaliacao'; // Use o nome correto da sua chave primária

    /**
     * Calendário efetivo de (ano, período, turma):
     * override da turma -> calendário padrão do ano.
     */
    public static function resolver(int $ano, string $periodo, ?int $idTurma, ?string $origem): ?self
    {
        if ($idTurma) {
            $override = static::where('ano', $ano)
                ->where('periodo', $periodo)
                ->where('id_turma', $idTurma)
                ->where('origem_turma', RegimeAvaliacao::normalizarOrigem($origem))
                ->first();

            if ($override) {
                return $override;
            }
        }

        return static::where('ano', $ano)
            ->where('periodo', $periodo)
            ->where('id_turma', RegimeAvaliacao::TURMA_TODAS)
            ->where('origem_turma', RegimeAvaliacao::ORIGEM_TODAS)
            ->first();
    }
}
