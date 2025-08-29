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
            'periodo' => 'required|string',
            'dt_primeiro_dia' => 'required|date',
            'dt_ultimo_dia' => 'nullable|date',
        ]);

        $periodo = PeriodoAvaliacao::create([
            'periodo' => $request->periodo,
            'dt_primeiro_dia' => $request->dt_primeiro_dia,
            'dt_ultimo_dia' => $request->dt_ultimo_dia,
        ]);

        return response()->json(['success' => true, 'periodo' => $periodo]);
    }

    public function showByPeriodo(Request $request)
    {
        $periodo = $request->query('periodo');
        $registro = \App\Models\PeriodoAvaliacao::where('periodo', $periodo)->first();
        if ($registro) {
            return response()->json($registro);
        }
        return response()->json([], 204);
    }
}