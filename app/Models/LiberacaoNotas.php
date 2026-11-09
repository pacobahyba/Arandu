<?php
// app/Models/LiberacaoNotas.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiberacaoNotas extends Model
{
    /**
     * Primeiro ano em que a visibilidade das notas passou a ser controlada
     * pelo professor. Anos anteriores, sem registro, continuam liberados —
     * os alunos já viam essas notas antes do recurso existir.
     *
     * É um ano fixo (e não "anterior ao atual") para que períodos trancados
     * não se liberem sozinhos na virada do ano.
     */
    public const ANO_INICIO_CONTROLE = 2026;

    protected $table = 'liberacoes_notas';

    protected $primaryKey = 'id_liberacao';

    protected $fillable = [
        'ano',
        'id_materia',
        'id_turma',
        'origem_turma',
        'periodo',
        'liberado',
        'codigo_professor',
    ];

    protected $casts = [
        'ano'              => 'integer',
        'id_materia'       => 'integer',
        'id_turma'         => 'integer',
        'liberado'         => 'boolean',
        'codigo_professor' => 'integer',
    ];

    /** Registro de (ano, matéria, turma, período), se o professor já configurou. */
    public static function buscar(int $ano, int $idMateria, int $idTurma, ?string $origem, string $periodo): ?self
    {
        return static::where('ano', $ano)
            ->where('id_materia', $idMateria)
            ->where('id_turma', $idTurma)
            ->where('origem_turma', RegimeAvaliacao::normalizarOrigem($origem))
            ->where('periodo', $periodo)
            ->first();
    }

    /** Sem registro: trancado a partir de ANO_INICIO_CONTROLE, liberado antes. */
    public static function padrao(int $ano): bool
    {
        return $ano < self::ANO_INICIO_CONTROLE;
    }

    public static function estaLiberado(int $ano, int $idMateria, int $idTurma, ?string $origem, string $periodo): bool
    {
        $registro = static::buscar($ano, $idMateria, $idTurma, $origem, $periodo);

        return $registro ? $registro->liberado : static::padrao($ano);
    }
}
