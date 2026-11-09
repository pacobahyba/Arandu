<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * Lê os proxies confiáveis de TRUSTED_PROXIES no .env ("*" ou lista de IPs/CIDRs
     * separados por vírgula). Sem isso, atrás de um proxy reverso, $request->ip()
     * devolve o IP do proxy e todos os usuários compartilham o mesmo rate limit.
     */
    protected function proxies()
    {
        $proxies = trim((string) config('app.trusted_proxies', ''));

        if ($proxies === '') {
            return $this->proxies;
        }

        return $proxies === '*'
            ? '*'
            : array_values(array_filter(array_map('trim', explode(',', $proxies))));
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
