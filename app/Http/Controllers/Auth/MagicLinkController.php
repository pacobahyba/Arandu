<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MagicLinkController extends Controller
{
    public function send(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Gere um código OTP de 6 dígitos
        $otp = rand(100000, 999999);

        // Salve o OTP em cache por 10 minutos
        Cache::put('otp_' . $request->email, $otp, now()->addMinutes(10));

        // Envie o OTP por e-mail
        Mail::raw("Seu código de acesso: $otp", function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Seu código de acesso');
        });

        return response()->json(['message' => 'Token enviado para o e-mail.']);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required'
        ]);

        $cachedOtp = Cache::get('otp_' . $request->email);

        if ($cachedOtp && $request->otp == $cachedOtp) {
            // Exemplo: defina o perfil conforme sua lógica de usuário
            $perfil = 'Aluno'; // ou 'Professor', 'Administrador', etc.
            $token = Str::random(60); // Gere um token fake ou use JWT/sanctum

            // Opcional: remova o OTP do cache após uso
            Cache::forget('otp_' . $request->email);

            return response()->json([
                'perfil' => $perfil,
                'token' => $token
            ]);
        }

        return response()->json(['message' => 'Token inválido ou expirado.'], 401);
    }
}