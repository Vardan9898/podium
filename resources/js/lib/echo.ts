import { http } from '@/api/http';
import Echo from 'laravel-echo';
import type { ChannelAuthorizationCallback } from 'pusher-js';

type AuthData = Parameters<ChannelAuthorizationCallback>[1];
import Pusher from 'pusher-js';

let instance: Echo<'reverb'> | null = null;

/**
 * Lazily connects to Reverb. Channel auth goes through the shared axios client so the
 * Sanctum session cookie and XSRF header are sent exactly like every other API call.
 */
/** Closes the socket after sign-out so no authenticated connection lingers in the tab. */
export function disconnectEcho(): void {
    instance?.disconnect();
    instance = null;
}

export function echo(): Echo<'reverb'> {
    if (instance) {
        return instance;
    }

    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';

    instance = new Echo({
        broadcaster: 'reverb',
        Pusher,
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authorizer: (channel: { name: string }) => ({
            authorize: (socketId: string, callback: ChannelAuthorizationCallback) => {
                http.post<AuthData>('/broadcasting/auth', { socket_id: socketId, channel_name: channel.name })
                    .then(({ data }) => callback(null, data))
                    .catch((error: Error) => callback(error, null));
            },
        }),
    });

    return instance;
}
