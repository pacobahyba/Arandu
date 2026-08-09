<?php
// app/Http/Controllers/PeriodoAvaliacaoController.php
namespace App\Http\Controllers;

use App\Enums\RegimePeriodo;
use App\Models\PeriodoAvaliacao;
use App\Models\RegimeAvaliacao;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PeriodoAvaliacaoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'ano'             => 'required|integer',
            'periodo'         => ['required', Rule::in(RegimePeriodo::todosRotulos())],
            'dt_primeiro_dia' => 'required|date',
            'dt_ultimo_dia'   => 'nullable|date',
            'id_turma'        => 'nullable',
            'origem'          => 'nullable|string',
        ]);

        [$idTurma, $origem] = $this->escopoTurma($request->input('id_turma'), $request->input('origem'));

        // Verifica se já existe um período cadastrado para o mesmo ano, período e escopo
        $existente = PeriodoAvaliacao::where('ano', $request->ano)
            ->where('periodo', $request->periodo)
            ->where('id_turma', $idTurma)
            ->where('origem_turma', $origem)
            ->first();

        if ($existente) {
            // Atualiza o registro existente
            $existente->dt_primeiro_dia = $request->dt_primeiro_dia;
            $existente->dt_ultimo_dia = $request->dt_ultimo_dia;
            $existente->save();
            return response()->json([
                'success' => true,
                'updated' => true,
                'periodo' => $existente
            ]);
        }

        $periodo = PeriodoAvaliacao::create([
            'periodo'         => $request->periodo,
            'dt_primeiro_dia' => $request->dt_primeiro_dia,
            'dt_ultimo_dia'   => $request->dt_ultimo_dia,
            'ano'             => $request->ano,
            'id_turma'        => $idTurma,
            'origem_turma'    => $origem,
        ]);

        return response()->json(['success' => true, 'created' => true, 'periodo' => $periodo]);
    }

    /**
     * Calendário de um período. Quando a turma é informada, um calendário
     * específico dela tem prioridade sobre o calendário padrão do ano.
     *
     * Quando não encontra, responde 200 com corpo vazio — o frontend trata a
     * ausência de dt_ultimo_dia como "período não expirado".
     */
    public function showByPeriodo(Request $request)
    {
        $periodo = $request->query('periodo');
        $ano = $request->query('ano');

        if (!$periodo || !$ano) {
            return response()->json(['message' => 'Período e ano são obrigatórios'], 400);
        }

        $registro = PeriodoAvaliacao::resolver(
            (int) $ano,
            $periodo,
            $this->idTurma($request->query('id_turma')),
            $request->query('origem')
        );

        return response()->json($registro);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'periodo'         => ['required', Rule::in(RegimePeriodo::todosRotulos())],
            'dt_primeiro_dia' => 'required|date',
            'dt_ultimo_dia'   => 'nullable|date',
            'ano'             => 'nullable|integer',
        ]);

        $periodo = PeriodoAvaliacao::findOrFail($id);

        $periodo->update([
            'periodo'         => $request->periodo,
            'dt_primeiro_dia' => $request->dt_primeiro_dia,
            'dt_ultimo_dia'   => $request->dt_ultimo_dia,
            // Mantém o ano do registro quando não informado — antes isso era
            // sobrescrito com date('Y'), corrompendo períodos de outros anos.
            'ano'             => $request->filled('ano') ? (int) $request->ano : $periodo->ano,
        ]);

        return response()->json(['success' => true, 'periodo' => $periodo]);
    }

    /** Resolve o escopo de gravação: turma específica ou padrão do ano. */
    private function escopoTurma($idTurma, $origem): array
    {
        $idTurma = $this->idTurma($idTurma);

        return $idTurma === null
            ? [RegimeAvaliacao::TURMA_TODAS, RegimeAvaliacao::ORIGEM_TODAS]
            : [$idTurma, RegimeAvaliacao::normalizarOrigem($origem)];
    }

    private function idTurma($valor): ?int
    {
        if ($valor === null || $valor === '' || (int) $valor === 0) {
            return null;
        }
        return (int) $valor;
    }
}
