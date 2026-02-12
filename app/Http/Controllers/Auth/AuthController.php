<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Cadastro/definição de senha
    public function definirSenha(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'senha' => 'required|min:8'
        ]);

        $email = $request->email;
        $senha = $request->senha;
        $salt = bin2hex(random_bytes(32));
        $password_hash = hash('sha256', $senha . $salt);

        // Se já existe, atualiza. Se não, cria.
        $usuario = DB::table('usuarios')->where('email', $email)->first();
        if ($usuario) {
            DB::table('usuarios')->where('email', $email)->update([
                'password_hash' => $password_hash,
                'salt' => $salt,
                'updated_at' => now(),
                'verificado' => 0 // Marca como não verificado até o usuário confirmar via e-mail
            ]);
        } else {
            DB::table('usuarios')->insert([
                'email' => $email,
                'password_hash' => $password_hash,
                'salt' => $salt,
                'created_at' => now(),
                'updated_at' => now(),
                'verificado' => 0 // Marca como não verificado até o usuário confirmar via e-mail
            ]);
        }

        // Gere um token único e salve em cache (ou em uma tabela, se preferir)
        $token = Str::random(64);
        cache()->put('verifica_senha_' . $token, $request->email, now()->addMinutes(30));

        // Envie o e-mail de confirmação
        $link = url('/api/confirmar-alteracao-senha?token=' . $token);
        Mail::raw(
            "Clique no link para confirmar a alteração de senha no sistema Arandu:\n\n$link\n\nSe não foi você, ignore este e-mail.",
            function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Confirmação de alteração de senha - Arandu');
            }
        );

        return response()->json(['success' => true]);
    }

    // Login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'senha' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Dados inválidos.'], 422);
        }

        $email = $request->email;
        $senha = $request->senha;

        // PERFIL DESENVOLVEDOR: Login direto sem checagens
        if ($email === 'arandu@arandu.com.br' && $senha === 'Arandu.123') {
            $token = Str::random(60);
            $tokenSophia = Str::random(60); // Token fictício para Sophia
            
            return response()->json([
                'success' => true,
                'token' => $token,
                'tokenSophia' => $tokenSophia,
                'perfil' => 'Desenvolvedor',
                'usuario' => [
                    'id' => 0,
                    'email' => $email
                ]
            ]);
        }

        $usuario = \DB::table('usuarios')
            ->where('email', $email)
            ->first();

        if (!$usuario) {
            return response()->json(['message' => 'E-mail ou senha inválidos.'], 401);
        }

        // Converte para inteiro para evitar problemas de tipo
        if ((int)$usuario->verificado === 0) {
            return response()->json(['message' => 'E-mail não verificado.'], 401);
        }

        $password_hash = hash('sha256', $senha . $usuario->salt);
        if ($password_hash !== $usuario->password_hash) {
            return response()->json(['message' => 'E-mail ou senha inválidos.'], 401);
        }

        // Gera um token de sessão simples
        $token = Str::random(60);
        
        // Define o perfil como "Sem perfil" inicialmente
        // O App.vue irá buscar o perfil correto na Sophia após o login
        $perfil = 'Sem perfil';

        // Retorna os dados necessários para o frontend
        return response()->json([
            'success' => true,
            'token' => $token,
            'perfil' => $perfil,
            'usuario' => [
                'id' => $usuario->id,
                'email' => $usuario->email
            ]
        ]);
    }

    public function confirmarAlteracaoSenha(Request $request)
    {
        $token = $request->query('token');
        $email = cache()->pull('verifica_senha_' . $token);

        if (!$email) {
            return response('Token inválido ou expirado.', 400);
        }

        // Atualiza o campo verificado
        \DB::table('usuarios')->where('email', $email)->update(['verificado' => 1]);

        // Você pode retornar uma view ou mensagem simples
        return response('Senha confirmada e conta verificada com sucesso! Agora você pode fazer login.<br><a href="/login">Ir para login</a>', 200);
    }
}