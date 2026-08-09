<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SophiaAuthController;
use App\Http\Controllers\ObjetivoController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\PeriodoAvaliacaoController;
use App\Http\Controllers\RegimeAvaliacaoController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\DB;

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

//Listando objetivos
Route::get('/objetivos', function(\Illuminate\Http\Request $request) {
    // `trimestre` é o nome legado do parâmetro; `periodo` é o nome atual.
    $periodo = $request->input('periodo', $request->input('trimestre'));

    $query = \DB::table('objetivos')
        ->where('id_materia', $request->id_materia)
        ->where('id_turma', $request->turma_id)
        ->where('periodo_objetivo', $periodo)
        ->where('ano_objetivo', $request->ano);

    $idAluno = $request->id_aluno;

    if ($idAluno) {
        // Objetivos gerais (sem adaptação) OU objetivos adaptados para este aluno
        $query->where(function($q) use ($idAluno) {
            $q->whereNotExists(function($sub) {
                $sub->select(\DB::raw(1))
                    ->from('objetivos_adaptados as oa_all')
                    ->whereColumn('oa_all.id_objetivo', 'objetivos.id_objetivo');
            })->orWhereExists(function($sub) use ($idAluno) {
                $sub->select(\DB::raw(1))
                    ->from('objetivos_adaptados as oa_aluno')
                    ->whereColumn('oa_aluno.id_objetivo', 'objetivos.id_objetivo')
                    ->where('oa_aluno.id_aluno', $idAluno);
            });
        });
        
        // Excluir objetivos marcados como excluídos para este aluno
        $query->whereNotExists(function($sub) use ($idAluno) {
            $sub->select(\DB::raw(1))
                ->from('objetivos_excluidos as oe')
                ->whereColumn('oe.id_objetivo', 'objetivos.id_objetivo')
                ->where('oe.id_aluno', $idAluno);
        });
    }

    return $query->orderBy('id_objetivo')->get();
});

Route::get('/objetivos/turmas', function(\Illuminate\Http\Request $request) {
    $periodo = $request->input('periodo', $request->input('trimestre'));

    $turmas = \DB::table('objetivos')
        ->select('id_turma')
        ->where('id_materia', $request->id_materia)
        ->where('periodo_objetivo', $periodo)
        ->where('ano_objetivo', $request->ano)
        ->distinct()
        ->orderBy('id_turma')
        ->pluck('id_turma')
        ->toArray();

    return response()->json($turmas);
});

//Atualizando objetivos
Route::put('/objetivos/{id}', function($id, \Illuminate\Http\Request $request) {
    return \DB::table('objetivos')
        ->where('id_objetivo', $id)
        ->update([
            'nome_objetivo' => $request->nome_objetivo,
            'tipo_objetivo' => $request->tipo_objetivo
        ]);
});

// Salvando ou atualizando notas
Route::post('/notas', function(\Illuminate\Http\Request $request) {
    $perfil = $request->perfil;

    if ($perfil === 'Aluno' && $request->tipo_nota !== 'Est') {
        return response()->json(['message' => 'Alunos só podem preencher notas do tipo Est.'], 403);
    }

    return \DB::table('notas')->updateOrInsert(
        [
            'id_objetivo' => $request->id_objetivo,
            'id_aluno' => $request->id_aluno,
            'tipo_nota' => $request->tipo_nota,
            'periodo' => $request->periodo
        ],
        [
            'nota' => $request->nota
        ]
    );
});

// Listando notas
Route::get('/notas', function(\Illuminate\Http\Request $request) {
    return \DB::table('notas')
        ->where('id_aluno', $request->id_aluno)
        ->where('periodo', $request->periodo)
        // Remova ou ajuste este filtro conforme sua lógica:
        // ->where('id_objetivo', ...) // se quiser filtrar por objetivo
        ->get();
});

// Atualizando porcentagem de objetivos
Route::post('/porcentagem-objetivos', function(\Illuminate\Http\Request $request) {
    $id_materia = $request->id_materia;
    $id_turma = $request->id_turma;
    $periodo = $request->periodo;
    $ano = $request->ano ?: date('Y');

    $dados = [
        'porcentagem_conceitual' => $request->porcentagem_conceitual,
        'porcentagem_atitudinal' => $request->porcentagem_atitudinal,
    ];

    // Tenta atualizar, se não existir faz insert
    $existe = \DB::table('porcentagem_objetivos')
        ->where('id_materia', $id_materia)
        ->where('id_turma', $id_turma)
        ->where('periodo', $periodo)
        ->where('ano_porcentagem', $ano)
        ->first();

    if ($existe) {
        \DB::table('porcentagem_objetivos')
            ->where('id_materia', $id_materia)
            ->where('id_turma', $id_turma)
            ->where('periodo', $periodo)
            ->where('ano_porcentagem', $ano)
            ->update($dados);
    } else {
        \DB::table('porcentagem_objetivos')->insert([
            'id_materia' => $id_materia,
            'id_turma' => $id_turma,
            'periodo' => $periodo,
            'ano_porcentagem' => $ano,
            'porcentagem_conceitual' => $dados['porcentagem_conceitual'],
            'porcentagem_atitudinal' => $dados['porcentagem_atitudinal'],
        ]);
    }

    return response()->json(['success' => true]);
});

//Recuperando porcentagem de objetivos por matéria, turma e período
Route::get('/porcentagem-objetivos', function(Request $request) {
    $row = \DB::table('porcentagem_objetivos')
        ->where('id_materia', $request->id_materia)
        ->where('id_turma', $request->id_turma)
        ->where('periodo', $request->periodo)
        ->where('ano_porcentagem', $request->ano ?? date('Y'))
        ->first();
    if (!$row) {
        // Retorna estrutura default para evitar null no frontend
        return response()->json([
            'id_materia' => $request->id_materia,
            'id_turma' => $request->id_turma,
            'periodo' => $request->periodo,
            'ano_porcentagem' => $request->ano ?? date('Y'),
            'porcentagem_conceitual' => 50,
            'porcentagem_atitudinal' => 50,
            'default' => true
        ]);
    }
    return response()->json($row);
});

// Salvando objetivos
Route::post('/objetivos', function(\Illuminate\Http\Request $request) {
    $existe = \DB::table('objetivos')
        ->where('nome_objetivo', $request->nome_objetivo)
        ->where('id_materia', $request->id_materia)
        ->where('id_turma', $request->id_turma)
        ->where('periodo_objetivo', $request->periodo_objetivo)
        ->where('ano_objetivo', date('Y'))
        ->first();

    if ($existe) {
        return response()->json(['id_objetivo' => $existe->id_objetivo, 'duplicado' => true]);
    }

    $idObjetivo = \DB::table('objetivos')->insertGetId([
        'nome_objetivo' => $request->nome_objetivo,
        'tipo_objetivo' => $request->tipo_objetivo,
        'id_materia' => $request->id_materia,
        'id_turma' => $request->id_turma,
        'periodo_objetivo' => $request->periodo_objetivo,
        'ano_objetivo' => date('Y')
    ]);

    return response()->json(['id_objetivo' => $idObjetivo]);
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
Route::put('/periodos-avaliacao/{id}', [PeriodoAvaliacaoController::class, 'update']);

// Regimes de período (Trimestral / Bimestral / Semestral)
Route::get('/regimes', [RegimeAvaliacaoController::class, 'catalogo']);
Route::get('/regime-turma', [RegimeAvaliacaoController::class, 'daTurma']);
Route::get('/regimes-avaliacao', [RegimeAvaliacaoController::class, 'index']);
Route::post('/regimes-avaliacao', [RegimeAvaliacaoController::class, 'store']);
Route::delete('/regimes-avaliacao/{id}', [RegimeAvaliacaoController::class, 'destroy']);

// Períodos que realmente existem no histórico de objetivos de um ano.
// Alimenta o seletor de "objetivos passados", que não deve depender do regime
// configurado hoje (anos antigos podem ter usado outro).
Route::get('/periodos-objetivos', function(\Illuminate\Http\Request $request) {
    $periodos = \DB::table('objetivos')
        ->select('periodo_objetivo')
        ->where('ano_objetivo', $request->ano)
        ->distinct()
        ->orderBy('periodo_objetivo')
        ->pluck('periodo_objetivo')
        ->filter()
        ->values()
        ->toArray();

    return response()->json($periodos);
});

// Rota para checar email
Route::post('/checar-email', [MagicLinkController::class, 'checarEmail']);

// Rota para definir senha
Route::post('/definir-senha', [AuthController::class, 'definirSenha']);

// Rota para login
Route::post('/auth/login', [AuthController::class, 'login']);

// Rota para confirmar alteração de senha
Route::get('/confirmar-alteracao-senha', [AuthController::class, 'confirmarAlteracaoSenha']);

// Rota para listar anos de objetivos
Route::get('/anos-objetivos', function() {
    $anos = \DB::table('objetivos')
        ->select('ano_objetivo')
        ->distinct()
        ->orderByDesc('ano_objetivo')
        ->pluck('ano_objetivo')
        ->toArray();

    // Garante que o ano atual sempre aparece
    $anoAtual = date('Y');
    if (!in_array($anoAtual, $anos)) {
        array_unshift($anos, $anoAtual);
    }

    return response()->json($anos);
});

// Rota para salvar objetivos adaptados
Route::post('/objetivos-adaptados', function(\Illuminate\Http\Request $request) {
    return \DB::table('objetivos_adaptados')->insert([
        'id_objetivo' => $request->id_objetivo,
        'id_aluno' => $request->id_aluno
    ]);
});

// Rota para listar objetivos adaptados
Route::get('/objetivos-adaptados', function(\Illuminate\Http\Request $request) {
    if ($request->has('id_objetivo')) {
        $idObjetivo = $request->query('id_objetivo');
        return \DB::table('objetivos_adaptados')
            ->where('id_objetivo', $idObjetivo)
            ->get(['id_objetivo','id_aluno']);
    } elseif ($request->has('id_aluno')) {
        $idAluno = $request->query('id_aluno');
        return \DB::table('objetivos_adaptados')
            ->where('id_aluno', $idAluno)
            ->get(['id_objetivo']);
    } else {
        $turmaId = $request->query('turma_id');
        $ano = $request->query('ano');
        return \DB::table('objetivos_adaptados')
            ->join('objetivos', 'objetivos_adaptados.id_objetivo', '=', 'objetivos.id_objetivo')
            ->where('objetivos.id_turma', $turmaId)
            ->where('objetivos.ano_objetivo', $ano)
            ->select('objetivos_adaptados.id_objetivo', 'objetivos_adaptados.id_aluno')
            ->get();
    }
});

// Rota para deletar objetivos adaptados
Route::delete('/objetivos-adaptados/{id_objetivo}', function($id_objetivo) {
    return \DB::table('objetivos_adaptados')
        ->where('id_objetivo', $id_objetivo)
        ->delete();
});

// Rotas para objetivos excluídos (ocultos para alunos específicos)
Route::post('/objetivos-excluidos', function(\Illuminate\Http\Request $request) {
    return \DB::table('objetivos_excluidos')->insert([
        'id_objetivo' => $request->id_objetivo,
        'id_aluno' => $request->id_aluno
    ]);
});

Route::get('/objetivos-excluidos', function(\Illuminate\Http\Request $request) {
    if ($request->has('id_objetivo')) {
        $idObjetivo = $request->query('id_objetivo');
        return \DB::table('objetivos_excluidos')
            ->where('id_objetivo', $idObjetivo)
            ->get(['id_objetivo','id_aluno']);
    } elseif ($request->has('id_aluno')) {
        $idAluno = $request->query('id_aluno');
        return \DB::table('objetivos_excluidos')
            ->where('id_aluno', $idAluno)
            ->get(['id_objetivo']);
    } else {
        $turmaId = $request->query('turma_id');
        $ano = $request->query('ano');
        return \DB::table('objetivos_excluidos')
            ->join('objetivos', 'objetivos_excluidos.id_objetivo', '=', 'objetivos.id_objetivo')
            ->where('objetivos.id_turma', $turmaId)
            ->where('objetivos.ano_objetivo', $ano)
            ->select('objetivos_excluidos.id_objetivo', 'objetivos_excluidos.id_aluno')
            ->get();
    }
});

Route::delete('/objetivos-excluidos/{id_objetivo}', function($id_objetivo, \Illuminate\Http\Request $request) {
    $query = \DB::table('objetivos_excluidos')
        ->where('id_objetivo', $id_objetivo);
    
    if ($request->has('id_aluno')) {
        $query->where('id_aluno', $request->query('id_aluno'));
    }
    
    return $query->delete();
});

// Endpoints em batch para otimizar requisições simultâneas
Route::post('/objetivos-excluidos/batch/adicionar', function(\Illuminate\Http\Request $request) {
    $id_objetivo = $request->id_objetivo;
    $alunos = $request->alunos ?? []; // Array de id_aluno
    
    if (empty($alunos) || !$id_objetivo) {
        return response()->json(['error' => 'Dados inválidos'], 400);
    }
    
    // Preparar dados para inserção em massa
    $inserts = array_map(function($id_aluno) use ($id_objetivo) {
        return [
            'id_objetivo' => $id_objetivo,
            'id_aluno' => $id_aluno
        ];
    }, $alunos);
    
    // Usar insertOrIgnore para evitar duplicatas
    $resultado = \DB::table('objetivos_excluidos')->insertOrIgnore($inserts);
    
    \Log::info('Batch adicionar objetivos_excluidos', [
        'id_objetivo' => $id_objetivo,
        'quantidade_alunos' => count($alunos),
        'rows_inserted' => $resultado
    ]);
    
    return response()->json(['success' => true, 'rows_inserted' => $resultado]);
});

Route::post('/objetivos-excluidos/batch/remover', function(\Illuminate\Http\Request $request) {
    $id_objetivo = $request->id_objetivo;
    $alunos = $request->alunos ?? []; // Array de id_aluno
    
    if (!$id_objetivo) {
        return response()->json(['error' => 'id_objetivo é obrigatório'], 400);
    }
    
    $query = \DB::table('objetivos_excluidos')
        ->where('id_objetivo', $id_objetivo);
    
    if (!empty($alunos)) {
        $query->whereIn('id_aluno', $alunos);
    }
    
    $resultado = $query->delete();
    
    \Log::info('Batch remover objetivos_excluidos', [
        'id_objetivo' => $id_objetivo,
        'quantidade_alunos' => count($alunos),
        'rows_deleted' => $resultado
    ]);
    
    return response()->json(['success' => true, 'rows_deleted' => $resultado]);
});

// Rota para turmas customizadas
Route::get('/turmas-customizadas', function(\Illuminate\Http\Request $request) {
    $q = \DB::table('turmas_customizadas')
        ->select(
            \DB::raw('id_turma as id'), // ou 'id' se já for esse o nome
            'id_disciplina_mae',
            'nome_turma',
            'dt_criacao'
        );

    if ($request->filled('id_disciplina_mae')) {
        $q->where('id_disciplina_mae', (int)$request->id_disciplina_mae);
    }

    return $q->orderBy('dt_criacao','desc')->get();
});

Route::post('/turmas-customizadas', function(\Illuminate\Http\Request $request) {
    $idDisciplina = (int)$request->input('id_disciplina_mae');
    $nomeTurma = trim((string)$request->input('nome_turma'));

    if (!$idDisciplina || $nomeTurma === '') {
        return response()->json(['message' => 'Campos inválidos'], 422);
    }

    $id = \DB::table('turmas_customizadas')->insertGetId([
        'id_disciplina_mae' => $idDisciplina,
        'nome_turma'        => $nomeTurma,
        'dt_criacao'        => \DB::raw('CURRENT_TIMESTAMP'),
    ]);

    $registro = \DB::table('turmas_customizadas')
        ->select('id_turma','id_disciplina_mae','nome_turma','dt_criacao')
        ->where('id_turma',$id)
        ->first();

    return response()->json($registro, 201);
});

// LISTAR alunos já atribuídos (apenas ativos) para a tabela turmas_alunos_customizados
Route::get('/turmas-customizadas/{id}/alunos', function(\Illuminate\Http\Request $request, $id) {
    $rows = \DB::table('turmas_alunos_customizados') 
        ->where('id_turma', (int)$id)
        ->whereNull('dt_fim')
        ->select('id_turma','id_aluno','dt_inicio','dt_fim')
        ->orderBy('dt_inicio','asc')
        ->get();
    return response()->json($rows);
});

// "Remoção": encerra vínculo atribuindo dt_fim para a tabela turmas_alunos_customizados
Route::delete('/turmas-customizadas/{id}/alunos/{idAluno}', function (Request $request, $id, $idAluno) {
    $atualizados = \DB::table('turmas_alunos_customizados')
        ->where('id_turma', (int)$id)
        ->where('id_aluno', (int)$idAluno)
        ->whereNull('dt_fim')
        ->update([
            'dt_fim' => \DB::raw('CURRENT_TIMESTAMP')
        ]);

    return response()->json([
        'success' => true,
        'updated' => $atualizados
    ]);
});

// ADICIONAR aluno à turma customizada (cria ou reativa)
Route::post('/turmas-customizadas/{id}/alunos', function (Request $request, $id) {
    $idTurma = (int)$id;
    $idAluno = (int)$request->input('id_aluno');

    if (!$idTurma || !$idAluno) {
        return response()->json(['message' => 'Dados inválidos'], 422);
    }

    // Já ativo? para a tabela turmas_alunos_customizados
    $ativo = \DB::table('turmas_alunos_customizados') 
        ->where('id_turma', $idTurma)
        ->where('id_aluno', $idAluno)
        ->whereNull('dt_fim')
        ->first();

    if ($ativo) {
        return response()->json([
            'success' => true,
            'status' => 'ja_existe'
        ]);
    }

    // Existiu, mas foi encerrado? Reativa (dt_fim -> NULL, mantém dt_inicio original) para a tabela turmas_alunos_customizados
    $reativado = \DB::table('turmas_alunos_customizados') 
        ->where('id_turma', $idTurma)
        ->where('id_aluno', $idAluno)
        ->whereNotNull('dt_fim')
        ->orderByDesc('dt_fim')
        ->first();

    if ($reativado) { // para a tabela turmas_alunos_customizados
        \DB::table('turmas_alunos_customizados') 
            ->where('id_turma', $idTurma)
            ->where('id_aluno', $idAluno)
            ->where('dt_inicio', $reativado->dt_inicio)
            ->update(['dt_fim' => null]);

        return response()->json([
            'success' => true,
            'status' => 'reativado'
        ]);
    }

    // Novo vínculo para a tabela turmas_alunos_customizados
    \DB::table('turmas_alunos_customizados')->insert([ 
        'id_turma'  => $idTurma,
        'id_aluno'  => $idAluno,
        'dt_inicio' => \DB::raw('CURRENT_TIMESTAMP'),
        'dt_fim'    => null
    ]);

    return response()->json([
        'success' => true,
        'status' => 'criado'
    ]);
});

// Nova rota para listar turmas e alunos customizados para a tabela turmas_alunos_customizados
Route::get('/turmas-alunos-customizados', function(\Illuminate\Http\Request $request) {
    $idAluno = (int)$request->query('id_aluno');
    $rows = \DB::table('turmas_alunos_customizados')
        ->join('turmas_customizadas', 'turmas_customizadas.id_turma', '=', 'turmas_alunos_customizados.id_turma')
        ->where('turmas_alunos_customizados.id_aluno', $idAluno)
        ->whereNull('turmas_alunos_customizados.dt_fim')
        ->select('turmas_customizadas.id_turma', 'turmas_customizadas.nome_turma')
        ->get();
    return response()->json($rows);
});

// A closure duplicada de GET /periodos-avaliacao que existia aqui sobrescrevia
// a rota registrada acima, tornando PeriodoAvaliacaoController::showByPeriodo
// código morto. Removida: o controller agora resolve o override por turma.

