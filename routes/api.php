<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\TurmaController;
// use App\Http\Controllers\AnoController;
// use App\Http\Controllers\AlunoController;
use App\Http\Controllers\SophiaAuthController;
use App\Http\Controllers\ObjetivoController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\PeriodoAvaliacaoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*//Salvando turma
Route::post('/turmas', [TurmaController::class, 'store']);

//Listando turmas
Route::get('/turmas', [TurmaController::class, 'index']);

//Listando anos
Route::get('/anos', [AnoController::class, 'index']);

//Atualizando turma
Route::put('/turmas/{id}', [TurmaController::class, 'update']);

//Listando alunos
Route::get('/alunos', [AlunoController::class, 'index']);

//Listando matérias
Route::get('/materias', function() {
    return \DB::table('materias')->get();
});

//Listando professores
Route::get('/professores', function(\Illuminate\Http\Request $request) {
    $materiaId = $request->query('materia_id');
    return \DB::table('vw_professores_materias_turmas')->where('id_materia', $materiaId)->get();
});

//Listando turmas por professor
Route::get('/turmas-professor', function(\Illuminate\Http\Request $request) {
    $professorId = $request->query('professor_id');
    $materiaId = $request->query('materia_id');
    return \DB::table('vw_professores_materias_turmas')
        ->where('id_professor', $professorId)
        ->where('id_materia', $materiaId)
        ->get();
});*/

//Listando objetivos
Route::get('/objetivos', function(\Illuminate\Http\Request $request) {
    $materiaId = $request->query('id_materia');
    $turmaId = $request->query('turma_id');
    $trimestre = $request->query('trimestre');
    return \DB::table('objetivos')
        ->where('id_materia', $materiaId)
        ->where('id_turma', $turmaId)
        ->where('periodo_objetivo', $trimestre)
        ->get();
});

//Atualizando objetivos
Route::put('/objetivos/{id}', function($id, \Illuminate\Http\Request $request) {
    return \DB::table('objetivos')
        ->where('id_objetivo', $id)
        ->update(['nome_objetivo' => $request->nome_objetivo]);
});

// Salvando ou atualizando notas
Route::post('/notas', function(\Illuminate\Http\Request $request) {
    return \DB::table('notas')->updateOrInsert(
        [
            'id_objetivo' => $request->id_objetivo,
            'tipo_nota' => $request->tipo_nota,
            'id_aluno' => $request->id_aluno,
            'periodo' => $request->periodo
        ],
        [
            'nota' => $request->nota
        ]
    );
});

// Listando notas
Route::get('/notas', function(\Illuminate\Http\Request $request) {
    $alunoId = $request->query('aluno_id');
    $periodo = $request->query('periodo');
    return \DB::table('notas')
        ->where('id_aluno', $alunoId)
        ->where('periodo', $periodo)
        ->get();
});

/*//Listando matérias por professor
Route::get('/materias-professor', function(\Illuminate\Http\Request $request) {
    $professorId = $request->query('id_professor');
    return \DB::table('vw_professores_materias_turmas')->where('id_professor', $professorId)->get();
});*/

// Atualizando porcentagem de objetivos
Route::post('/porcentagem-objetivos', function(\Illuminate\Http\Request $request) {
    return \DB::table('porcentagem_objetivos')->updateOrInsert(
        [
            'id_materia' => $request->id_materia,
            'id_turma' => $request->id_turma,
            'periodo' => $request->periodo
        ],
        [
            'porcentagem_conceitual' => $request->porcentagem_conceitual,
            'porcentagem_atitudinal' => $request->porcentagem_atitudinal
        ]
    );
});

//Recuperando porcentagem de objetivos por matéria, turma e período
Route::get('/porcentagem-objetivos', function(\Illuminate\Http\Request $request) {
    return \DB::table('porcentagem_objetivos')
        ->where('id_materia', $request->id_materia)
        ->where('id_turma', $request->id_turma)
        ->where('periodo', $request->periodo)
        ->first();
});

// Salvando objetivos
Route::post('/objetivos', function(\Illuminate\Http\Request $request) {
    return \DB::table('objetivos')->insert([
        'nome_objetivo' => $request->nome_objetivo,
        'tipo_objetivo' => $request->tipo_objetivo,
        'id_materia' => $request->id_materia,
        'id_turma' => $request->id_turma,
        'periodo_objetivo' => $request->periodo_objetivo
    ]);
});

// Rota para autenticação Sophia
Route::get('/token-sophia', [SophiaAuthController::class, 'autenticar']);

// Rota para deletar objetivos
Route::delete('/objetivos/{id}', [ObjetivoController::class, 'destroy']);

// Rota para envio de link mágico
Route::post('/auth/send-magic-link', [MagicLinkController::class, 'send']);

// Rota para verificação de OTP
Route::post('/auth/verify-otp', [MagicLinkController::class, 'verify']);

// Rota para salvamento de períodos de avaliação
Route::post('/periodos-avaliacao', [PeriodoAvaliacaoController::class, 'store']);

// Rota para listagem de períodos de avaliação
Route::get('/periodos-avaliacao', [PeriodoAvaliacaoController::class, 'showByPeriodo']);