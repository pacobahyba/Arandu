<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SophiaAuthController extends Controller
{
    public function autenticar()
    {
        $response = Http::post(config('services.sophia_auth.url'), [
            'usuario' => config('services.sophia_auth.login'),
            'senha' => config('services.sophia_auth.senha'),
        ]);

        \Log::info('Sophia response', ['body' => $response->body()]);

        if ($response->successful()) {
            // Se o corpo é só o token puro, retorne como { "token": ... }
            return response()->json(['token' => $response->body()]);
        } else {
            return response()->json([
                'error' => 'Falha na autenticação',
                'sophia_response' => $response->body(),
                'status' => $response->status()
            ], $response->status());
        }
    }
}