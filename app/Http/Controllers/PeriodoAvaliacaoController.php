<?php
// app/Http/Controllers/PeriodoAvaliacaoController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PeriodoAvaliacao;

class PeriodoAvaliacaoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'ano' => 'required|integer',
            'periodo' => 'required|string',
            'dt_primeiro_dia' => 'required|date',
            'dt_ultimo_dia' => 'nullable|date',
        ]);

        // Verifica se já existe um período cadastrado para o mesmo ano e trimestre
        $existente = PeriodoAvaliacao::where('ano', $request->ano)
            ->where('periodo', $request->periodo)
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
            'periodo' => $request->periodo,
            'dt_primeiro_dia' => $request->dt_primeiro_dia,
            'dt_ultimo_dia' => $request->dt_ultimo_dia,
            'ano' => $request->ano,
        ]);

        return response()->json(['success' => true, 'created' => true, 'periodo' => $periodo]);
    }

    public function showByPeriodo(Request $request)
    {
        $periodo = $request->query('periodo');
        $ano = $request->query('ano');

        if (!$periodo || !$ano) {
            return response()->json(['message' => 'Período e ano são obrigatórios'], 400);
        }

        $registro = \App\Models\PeriodoAvaliacao::where('periodo', $periodo)
            ->where('ano', $ano)
            ->first();

        if ($registro) {
            return response()->json($registro);
        }
        return response()->json([], 204);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'periodo' => 'required|string',
            'dt_primeiro_dia' => 'required|date',
            'dt_ultimo_dia' => 'nullable|date',
        ]);

        $periodo = PeriodoAvaliacao::findOrFail($id);

        $periodo->update([
            'periodo' => $request->periodo,
            'dt_primeiro_dia' => $request->dt_primeiro_dia,
            'dt_ultimo_dia' => $request->dt_ultimo_dia,
            // O ano pode ser atualizado se necessário:
            'ano' => date('Y'),
        ]);

        return response()->json(['success' => true, 'periodo' => $periodo]);
    }
}