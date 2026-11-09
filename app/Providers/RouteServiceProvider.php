<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // As rotas da API ainda não usam autenticação no servidor, então
        // $request->user() é sempre null. Limitar só por IP fazia todos os
        // usuários atrás do mesmo proxy/NAT dividirem 60 req/min. A chave
        // agora é o token de sessão enviado pelo front (um por login); o
        // teto por IP é só uma proteção contra abuso.
        RateLimiter::for('api', function (Request $request) {
            $token = $request->bearerToken();
            $chave = $request->user()?->id
                ?: ($token ? 'tok:' . sha1($token) : 'ip:' . $request->ip());

            return [
                Limit::perMinute(300)->by($chave)->response(
                    fn (Request $request, array $headers) => $this->respostaLimite($request, $headers, 'api')
                ),
                Limit::perMinute(1000)->by('ip-teto:' . $request->ip())->response(
                    fn (Request $request, array $headers) => $this->respostaLimite($request, $headers, 'api-ip')
                ),
            ];
        });

        // Limite mais rígido para login/OTP, para evitar força bruta.
        RateLimiter::for('auth', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return Limit::perMinute(10)->by('auth:' . $request->ip() . '|' . $email)->response(
                fn (Request $request, array $headers) => $this->respostaLimite($request, $headers, 'auth')
            );
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Resposta 429 com registro em log para diagnóstico. O log mostra se o
     * Laravel enxerga o IP real do usuário ou só o do proxy (ver TRUSTED_PROXIES).
     */
    private function respostaLimite(Request $request, array $headers, string $limiter)
    {
        Log::warning('Rate limit atingido', [
            'limiter' => $limiter,
            'rota' => $request->method() . ' ' . $request->path(),
            'ip' => $request->ip(),
            'remote_addr' => $request->server('REMOTE_ADDR'),
            'x_forwarded_for' => $request->header('X-Forwarded-For'),
            'tem_token' => (bool) $request->bearerToken(),
        ]);

        return response()->json(['message' => 'Too Many Attempts.'], 429, $headers);
    }
}
