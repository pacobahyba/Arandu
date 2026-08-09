<?php
// app/Http/Controllers/RegimeAvaliacaoController.php
namespace App\Http\Controllers;

use App\Enums\RegimePeriodo;
use App\Models\RegimeAvaliacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RegimeAvaliacaoController extends Controller
{
    /** Catálogo estático dos regimes — alimenta os selects do administrativo. */
    public function catalogo()
    {
        return response()->json(
            array_map(fn (RegimePeriodo $r) => $r->paraArray(), RegimePeriodo::cases())
        );
    }

    /**
     * Regime efetivo de uma turma. É o endpoint que Objetivos, Avaliação e
     * Exportar consomem para montar o select de período.
     */
    public function daTurma(Request $request)
    {
        $request->validate([
            'ano'      => 'required|integer',
            'id_turma' => 'nullable',
            'origem'   => 'nullable|string',
        ]);

        $regime = RegimeAvaliacao::resolver(
            (int) $request->query('ano'),
            $this->idTurma($request->query('id_turma')),
            $request->query('origem')
        );

        return response()->json($regime->paraArray());
    }

    /** Padrão do ano + exceções, para a tela de administração. */
    public function index(Request $request)
    {
        $request->validate(['ano' => 'required|integer']);
        $ano = (int) $request->query('ano');

        $excecoes = RegimeAvaliacao::where('ano', $ano)
            ->where('origem_turma', '!=', RegimeAvaliacao::ORIGEM_TODAS)
            ->get()
            ->map(fn (RegimeAvaliacao $r) => [
                'id_regime'    => $r->id_regime,
                'id_turma'     => $r->id_turma,
                'origem_turma' => $r->origem_turma,
                'regime'       => $r->regime->value,
                'nome'         => $r->regime->nome(),
            ]);

        $padrao = RegimeAvaliacao::padraoDoAno($ano);

        return response()->json([
            'ano'      => $ano,
            'padrao'   => $padrao->paraArray(),
            'excecoes' => $excecoes,
        ]);
    }

    /**
     * Grava o padrão do ano (sem id_turma) ou uma exceção de turma.
     *
     * Trocar o regime órfã objetivos/notas já lançados sob rótulos do regime
     * antigo, então exigimos `confirmar: true` quando houver dados afetados.
     */
    public function store(Request $request)
    {
        $request->validate([
            'ano'       => 'required|integer',
            'regime'    => ['required', Rule::in(array_column(RegimePeriodo::cases(), 'value'))],
            'id_turma'  => 'nullable',
            'origem'    => 'nullable|string',
            'confirmar' => 'nullable|boolean',
        ]);

        $ano      = (int) $request->input('ano');
        $regime   = RegimePeriodo::from($request->input('regime'));
        $idTurma  = $this->idTurma($request->input('id_turma'));
        $ehPadrao = $idTurma === null;

        if (! $request->boolean('confirmar')) {
            $conflitos = $this->contarConflitos($ano, $idTurma, $regime);

            if ($conflitos['objetivos'] > 0 || $conflitos['notas'] > 0) {
                return response()->json([
                    'conflito'  => true,
                    'objetivos' => $conflitos['objetivos'],
                    'notas'     => $conflitos['notas'],
                    'message'   => 'Já existem lançamentos neste ano em períodos que não pertencem ao novo regime. '
                        . 'Eles deixarão de aparecer nas telas se você continuar.',
                ], 409);
            }
        }

        $registro = RegimeAvaliacao::updateOrCreate(
            [
                'ano'          => $ano,
                'id_turma'     => $ehPadrao ? RegimeAvaliacao::TURMA_TODAS : $idTurma,
                'origem_turma' => $ehPadrao
                    ? RegimeAvaliacao::ORIGEM_TODAS
                    : RegimeAvaliacao::normalizarOrigem($request->input('origem')),
            ],
            ['regime' => $regime->value]
        );

        return response()->json([
            'success' => true,
            'regime'  => $registro->regime->paraArray(),
            'id_regime' => $registro->id_regime,
        ]);
    }

    /** Remove uma exceção — a turma volta a seguir o padrão do ano. */
    public function destroy($id)
    {
        $registro = RegimeAvaliacao::findOrFail($id);

        if ($registro->origem_turma === RegimeAvaliacao::ORIGEM_TODAS) {
            return response()->json([
                'message' => 'O regime padrão do ano não pode ser removido, apenas alterado.',
            ], 422);
        }

        $registro->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Conta objetivos e notas do ano gravados sob rótulos que não existem no
     * novo regime. Quando é o padrão do ano, considera todas as turmas que não
     * têm exceção própria.
     */
    private function contarConflitos(int $ano, ?int $idTurma, RegimePeriodo $regime): array
    {
        $objetivos = DB::table('objetivos')
            ->where('ano_objetivo', $ano)
            ->whereNotIn('periodo_objetivo', $regime->periodos());

        if ($idTurma !== null) {
            $objetivos->where('id_turma', $idTurma);
        }

        $ids = (clone $objetivos)->pluck('id_objetivo');

        return [
            'objetivos' => $ids->count(),
            'notas'     => $ids->isEmpty()
                ? 0
                : DB::table('notas')->whereIn('id_objetivo', $ids)->count(),
        ];
    }

    /** Trata '', '0' e null como "sem turma" (= padrão do ano). */
    private function idTurma($valor): ?int
    {
        if ($valor === null || $valor === '' || (int) $valor === 0) {
            return null;
        }
        return (int) $valor;
    }
}
