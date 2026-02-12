<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

class MagicLinkController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = $request->email;

        $otp = rand(100000, 999999);
        Cache::put('otp_' . $email, $otp, now()->addMinutes(10));

        Mail::raw("Seu código de acesso Arandu: $otp", function ($message) use ($email) {
            $message->to($email)
                    ->subject('Seu código de acesso Arandu');
        });

        return response()->json(['message' => 'Token enviado para o e-mail informado.']);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required'
        ]);

        $email = $request->email;

        $cachedOtp = Cache::get('otp_' . $email);

        if ($cachedOtp && $request->otp == $cachedOtp) {
            // Remova o OTP do cache após uso
            Cache::forget('otp_' . $request->email);

            // Inicialização de variáveis
            $perfil = 'Sem perfil';
            $token = Str::random(60);
            $tokenSophia = null;
            $codigoColaborador = null;
            $codigoAluno = null;
            $nomeColaborador = null;
            $nomeAluno = null;
            $turmasMaterias = [];
            $alunoCodigosTurma = [];

            try {
                // 1. Autenticar na Sophia
                $client = new Client(['verify' => false]);
                $respSophia = $client->post('https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Autenticacao', [
                    'json' => [
                        'usuario' => 'FichadeAvaliacao',
                        'senha' => '123456'
                    ],
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ]
                ]);
                
                // Obter token da resposta
                $responseBody = $respSophia->getBody();
                $dataSophia = json_decode($responseBody, true);

                // Verificar se o resultado é um array válido ou usar a resposta como token diretamente
                if (is_array($dataSophia) && !empty($dataSophia)) {
                    $tokenSophia = $dataSophia['token'] ?? $dataSophia['Token'] ?? null;
                } else {
                    // Se não for JSON válido, a resposta inteira pode ser o token
                    $tokenSophia = trim($responseBody, '"'); // Remove aspas se presentes
                }

                if ($tokenSophia) {
                    Log::info("Autenticado na Sophia com sucesso");

                    // 2. Busca colaborador pelo e-mail
                    $respColab = $client->get('https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Colaboradores?Email=' . urlencode($request->email), [
                        'headers' => [
                            'Token' => $tokenSophia
                        ]
                    ]);
                    $colabs = json_decode($respColab->getBody(), true);
                    $encontrouColaborador = false;
                    
                    if (is_array($colabs) && count($colabs) > 0) {
                        Log::info("Encontrou colaborador: {$colabs[0]['nome']}");
                        $codigoColaborador = $colabs[0]['codigo'];
                        $nomeColaborador = $colabs[0]['nome'] ?? null;
                        
                        // Verificação mais robusta para a chave 'leciona'
                        if (isset($colabs[0]['leciona']) && ($colabs[0]['leciona'] === true || $colabs[0]['leciona'] === 'true' || $colabs[0]['leciona'] === 1 || $colabs[0]['leciona'] === '1')) {
                            $perfil = 'Professor';
                            Log::info("Colaborador definido como Professor porque leciona=true");
                        } else {
                            $perfil = 'Administrativo';
                            Log::info("Colaborador definido como Administrativo porque leciona=" . (isset($colabs[0]['leciona']) ? var_export($colabs[0]['leciona'], true) : 'não definido'));
                        }
                        // Força o perfil Administrativo para estes e-mails
                        if (
                            $email === 'pedrohenriqueb@ogamita.com.br' ||
                            $email === 'bethsaraiva@ogamita.com.br' ||
                            $email === 'monica@ogamita.com.br'
                        ) {
                            $perfil = 'Administrativo';
                        }
                        $encontrouColaborador = true;
                    } else {
                        Log::info("Não encontrou colaborador para o email: {$request->email}");
                    }

                    // Busca aluno pelo e-mail APENAS se NÃO encontrou colaborador
                    if (!$encontrouColaborador) {
                        Log::info("Buscando como aluno...");
                        
                        $respAlunos = $client->get('https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Alunos?Unidades=2&Cursos=20,7', [
                            'headers' => [
                                'Token' => $tokenSophia
                            ]
                        ]);
                        
                        $alunos = json_decode($respAlunos->getBody(), true);
                        $encontrouAluno = false;

                        if (is_array($alunos)) {
                            foreach ($alunos as $aluno) {
                                if (isset($aluno['email']) && strtolower($aluno['email']) === strtolower($request->email)) {
                                    $codigoAluno = $aluno['codigo'];
                                    $nomeAluno = $aluno['nome'] ?? null;
                                    $alunoCodigosTurma = isset($aluno['turmas']) 
                                        ? array_column($aluno['turmas'], 'codigo')
                                        : [];
                                    
                                    $perfil = 'Aluno';
                                    $encontrouAluno = true;
                                    break;
                                }
                            }
                        }
                        
                        // Corrigido: definindo como Visitante apenas se não encontrou aluno
                        if (!$encontrouAluno) {
                            $perfil = 'Visitante';
                        }
                    }

                    // 4. Se for professor, busca turmas/matérias
                    if ($codigoColaborador) {
                        $respTurmas = $client->get('https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2', [
                            'headers' => [
                                'Token' => $tokenSophia
                            ]
                        ]);
                        $turmas = json_decode($respTurmas->getBody(), true);

                        // Filtra turmas e disciplinas do colaborador
                        $turmasMaterias = [];
                        $codigosDisciplina = [];
                        
                        foreach ($turmas as $turma) {
                            $disciplinasDoColaborador = [];
                            if (isset($turma['professoresDisciplinas'])) {
                                foreach ($turma['professoresDisciplinas'] as $pd) {
                                    $isTitular = isset($pd['colaboradores']) && collect($pd['colaboradores'])->pluck('codigo')->contains($codigoColaborador);
                                    $isAuxiliar = isset($pd['colaboradoresAuxiliares']) && collect($pd['colaboradoresAuxiliares'])->pluck('codigo')->contains($codigoColaborador);
                                    if ($isTitular || $isAuxiliar) {
                                        $disciplinasDoColaborador[] = $pd;
                                        
                                        // Coleta código da disciplina no momento do loop
                                        if (isset($pd['disciplina']['codigo'])) {
                                            $codigosDisciplina[] = $pd['disciplina']['codigo'];
                                        }
                                    }
                                }
                            }
                            if (count($disciplinasDoColaborador) > 0) {
                                $turmaFiltrada = $turma;
                                $turmaFiltrada['professoresDisciplinas'] = $disciplinasDoColaborador;
                                $turmasMaterias[] = $turmaFiltrada;
                            }
                        }
                        
                        // Remove duplicatas dos códigos de disciplina
                        $codigosDisciplina = array_values(array_unique($codigosDisciplina));
                    }
                }

                // Armazenar em sessão com base no perfil
                session(['perfil' => $perfil, 'tokenSophia' => $tokenSophia]);
                
                if ($codigoColaborador) {
                    session([
                        'codigo' => $codigoColaborador,
                        'nome' => $nomeColaborador
                    ]);
                    
                    if (!empty($codigosDisciplina)) {
                        session(['codigo_disciplina' => $codigosDisciplina]);
                    }
                }
                
                if ($codigoAluno) {
                    session([
                        'aluno_codigo' => $codigoAluno,
                        'aluno_nome' => $nomeAluno,
                        'aluno_codigos_turma' => $alunoCodigosTurma
                    ]);
                }

                // Retorna tudo que o frontend precisa em um único lugar
                return response()->json([
                    'perfil' => $perfil,
                    'token' => $token,
                    'tokenSophia' => $tokenSophia,
                    'codigo' => $codigoColaborador,
                    'nome' => $nomeColaborador ?? $nomeAluno ?? null,
                    'turmasMaterias' => array_values($turmasMaterias),
                    'codigo_disciplina' => $codigosDisciplina ?? [],
                    'aluno_codigo' => $codigoAluno,
                    'aluno_nome' => $nomeAluno,
                    'aluno_codigos_turma' => $alunoCodigosTurma
                ]);
            } catch (\Exception $e) {
                Log::error('Erro ao verificar usuário: ' . $e->getMessage());
                return response()->json([
                    'message' => 'Erro ao autenticar.',
                    'error' => $e->getMessage() // remova em produção
                ], 500);
            }
        }

        return response()->json(['message' => 'Token inválido ou expirado.'], 401);
    }
    
    public function checarEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = $request->email;

        // PERFIL DESENVOLVEDOR: Permite login direto sem verificação Sophia
        if ($email === 'arandu@arandu.com.br') {
            return response()->json(['tipo' => 'desenvolvedor']);
        }

        try {
            // 1. Autenticar na Sophia
            $client = new \GuzzleHttp\Client(['verify' => false]);
            $respSophia = $client->post('https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Autenticacao', [
                'json' => [
                    'usuario' => 'FichadeAvaliacao',
                    'senha' => '123456'
                ],
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);
            $responseBody = $respSophia->getBody();
            $dataSophia = json_decode($responseBody, true);
            $tokenSophia = is_array($dataSophia) && !empty($dataSophia)
                ? ($dataSophia['token'] ?? $dataSophia['Token'] ?? null)
                : trim($responseBody, '"');

            if (!$tokenSophia) {
                return response()->json(['message' => 'Erro ao autenticar no Sophia.'], 500);
            }

            // 2. Verifica se é colaborador (professor)
            $respColab = $client->get('https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Colaboradores?Email=' . urlencode($email), [
                'headers' => [
                    'Token' => $tokenSophia
                ]
            ]);
            $colabs = json_decode($respColab->getBody(), true);

            if (is_array($colabs) && count($colabs) > 0) {
                return response()->json(['tipo' => 'colaborador']);
            }

            // 3. Verifica se é aluno
            $respAlunos = $client->get('https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Alunos?Unidades=2&Cursos=20,7', [
                'headers' => [
                    'Token' => $tokenSophia
                ]
            ]);
            $alunos = json_decode($respAlunos->getBody(), true);

            if (is_array($alunos)) {
                foreach ($alunos as $aluno) {
                    if (isset($aluno['email']) && strtolower($aluno['email']) === strtolower($email)) {
                        return response()->json(['tipo' => 'aluno']);
                    }
                }
            }
            
            // Não achou em nenhum dos dois
            return response()->json([
                'message' => 'O email fornecido não está vinculado a aluno nem professor no Sophia. Por favor faça o cadastro no Sophia e tente novamente.'
            ], 404);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao consultar Sophia.', 'error' => $e->getMessage()], 500);
        }
    }
}