<?php
// app/Enums/RegimePeriodo.php
namespace App\Enums;

/**
 * Fonte única da verdade dos períodos de avaliação.
 *
 * Os rótulos ("1º Tri", "1º Bim", "1º Sem") são gravados crus nas colunas
 * varchar de objetivos.periodo_objetivo, notas.periodo, porcentagem_objetivos.periodo
 * e periodos_avaliacao.periodo. Por isso o formato NÃO pode mudar: alterar um
 * rótulo aqui órfã todos os registros já gravados com o rótulo antigo.
 */
enum RegimePeriodo: string
{
    case Trimestral = 'TRI';
    case Bimestral  = 'BIM';
    case Semestral  = 'SEM';

    /** Regime aplicado quando o ano/turma não tem nada configurado. */
    public const PADRAO = self::Trimestral;

    public function quantidade(): int
    {
        return match ($this) {
            self::Trimestral => 3,
            self::Bimestral  => 4,
            self::Semestral  => 2,
        };
    }

    /** Sufixo usado no rótulo gravado no banco. */
    public function sufixo(): string
    {
        return match ($this) {
            self::Trimestral => 'Tri',
            self::Bimestral  => 'Bim',
            self::Semestral  => 'Sem',
        };
    }

    /** Substantivo exibido como label do select ("Trimestre:", "Bimestre:"). */
    public function substantivo(): string
    {
        return match ($this) {
            self::Trimestral => 'Trimestre',
            self::Bimestral  => 'Bimestre',
            self::Semestral  => 'Semestre',
        };
    }

    /** Nome do regime para os selects do administrativo. */
    public function nome(): string
    {
        return match ($this) {
            self::Trimestral => 'Trimestral',
            self::Bimestral  => 'Bimestral',
            self::Semestral  => 'Semestral',
        };
    }

    /** @return string[] ex.: ['1º Tri', '2º Tri', '3º Tri'] */
    public function periodos(): array
    {
        $periodos = [];
        for ($i = 1; $i <= $this->quantidade(); $i++) {
            $periodos[] = "{$i}º {$this->sufixo()}";
        }
        return $periodos;
    }

    /**
     * Todos os rótulos de todos os regimes — usado na validação das rotas.
     *
     * @return string[]
     */
    public static function todosRotulos(): array
    {
        $rotulos = [];
        foreach (self::cases() as $regime) {
            $rotulos = array_merge($rotulos, $regime->periodos());
        }
        return $rotulos;
    }

    /** Descobre o regime a partir de um rótulo gravado ("2º Bim" -> Bimestral). */
    public static function doRotulo(?string $rotulo): ?self
    {
        foreach (self::cases() as $regime) {
            if (in_array($rotulo, $regime->periodos(), true)) {
                return $regime;
            }
        }
        return null;
    }

    /** Catálogo consumido pelo frontend. */
    public function paraArray(): array
    {
        return [
            'regime'      => $this->value,
            'nome'        => $this->nome(),
            'substantivo' => $this->substantivo(),
            'periodos'    => $this->periodos(),
        ];
    }
}
