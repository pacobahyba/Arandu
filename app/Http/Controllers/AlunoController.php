<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlunoController extends Controller
{
    public function index(Request $request)
    {
        $turmaId = $request->query('turma_id');
        $alunos = DB::table('vw_alunos')
            ->where('turma_id', $turmaId)
            ->get();
        return response()->json($alunos);
    }
}