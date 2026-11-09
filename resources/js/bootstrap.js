/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Se a API responder 429 (Too Many Attempts), espera o tempo indicado em
 * Retry-After e tenta de novo (até 2 vezes), em vez de perder a nota que
 * estava sendo salva. Vale apenas para a nossa API (/api/...), não para o Sophia.
 */
const MAX_TENTATIVAS_429 = 2;

axios.interceptors.response.use(undefined, async (error) => {
    const config = error.config;
    const ehNossaApi = typeof config?.url === 'string' && config.url.startsWith('/api/');

    if (error.response?.status !== 429 || !ehNossaApi) {
        return Promise.reject(error);
    }

    config.__tentativas429 = (config.__tentativas429 || 0) + 1;
    if (config.__tentativas429 > MAX_TENTATIVAS_429) {
        return Promise.reject(error);
    }

    const retryAfter = Number(error.response.headers?.['retry-after']);
    const segundos = Number.isFinite(retryAfter) && retryAfter > 0 ? Math.min(retryAfter, 60) : 2;
    await new Promise(resolve => setTimeout(resolve, segundos * 1000));

    return axios(config);
});

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });
