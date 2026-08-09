<?php
// app/Models/RegimeAvaliacao.php
namespace App\Models;

use App\Enums\RegimePeriodo;
use Illuminate\Database\Eloquent\Model;

class RegimeAvaliacao extends Model
{
    /** Sentinelas da linha "padrão do ano" (ver migration). */
    public const TURMA_TODAS  = 0;
    public const ORIGEM_TODAS = 'A';

    public const ORIGEM_SOPHIA       = 'S';
    public const ORIGEM_CUSTOMIZADA  = 'C';

    protected $table = 'regimes_avaliacao';

    protected $primaryKey = 'id_regime';

    protected $fillable = [
        'ano',
        'id_turma',
        'origem_turma',
        'regime',
    ];

    protected $casts = [
        'ano'      => 'integer',
        'id_turma' => 'integer',
        'regime'   => RegimePeriodo::class,
    ];

    /**
     * Normaliza a origem vinda da requisição.
     * Aceita 'S'/'C' ou o booleano isCustomizada enviado pelo frontend.
     */
    public static function normalizarOrigem($origem): string
    {
        if ($origem === true || $origem === 'true' || $origem === 1 || $origem === '1') {
            return self::ORIGEM_CUSTOMIZADA;
        }

        $origem = is_string($origem) ? strtoupper($origem) : null;

        return in_array($origem, [self::ORIGEM_SOPHIA, self::ORIGEM_CUSTOMIZADA], true)
            ? $origem
            : self::ORIGEM_SOPHIA;
    }

    /**
     * Regime efetivo de uma turma: exceção da turma -> padrão do ano -> Trimestral.
     */
    public static function resolver(int $ano, ?int $idTurma, ?string $origem): RegimePeriodo
    {
        if ($idTurma) {
            $excecao = static::where('ano', $ano)
                ->where('id_turma', $idTurma)
                ->where('origem_turma', static::normalizarOrigem($origem))
                ->first();

            if ($excecao) {
                return $excecao->regime;
            }
        }

        return static::padraoDoAno($ano);
    }

    public static function padraoDoAno(int $ano): RegimePeriodo
    {
        $padrao = static::where('ano', $ano)
            ->where('id_turma', self::TURMA_TODAS)
            ->where('origem_turma', self::ORIGEM_TODAS)
            ->first();

        return $padrao?->regime ?? RegimePeriodo::PADRAO;
    }
}
