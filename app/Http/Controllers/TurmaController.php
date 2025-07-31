<?php

namespace App\Http\Controllers;

use App\Models\Turma;
use Illuminate\Http\Request;

class TurmaController extends Controller
{
    /**
     * Salva uma nova turma no banco de dados.
     */
    public function store(Request $request)
    {
        $turma = Turma::create([
            'ano' => $request->ano,
            'nome' => $request->nome,
        ]);
        return response()->json($turma, 201);
    }

    /**
     * Lista todas as turmas.
     */
    public function index()
    {
        // Lê os dados da view vw_turmas ao invés da tabela turmas
        $turmas = \DB::table('vw_turmas')->get();
        return response()->json($turmas);
    }

    /**
     * Atualiza uma turma existente.
     */
    public function update(Request $request, $id)
    {
        $turma = Turma::findOrFail($id);
        $turma->update([
            'ano' => $request->ano,
            'nome' => $request->nome,
        ]);
        return response()->json($turma);
    }
}