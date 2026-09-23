import axios, { type AxiosError, type InternalAxiosRequestConfig } from 'axios';

type RetriableConfig = InternalAxiosRequestConfig & { _csrfRetried?: boolean };

/**
 * The only HTTP client in the app. Sanctum SPA auth: session cookie + XSRF-TOKEN cookie
 * echoed back as the X-XSRF-TOKEN header (axios does that for same-origin requests).
 */
export const http = axios.create({
    baseURL: '/api',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

export function ensureCsrfCookie(): Promise<unknown> {
    return axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}

let onUnauthorized: () => void = () => {};

/** Registered by the auth store so this module stays free of store/router imports. */
export function setUnauthorizedHandler(handler: () => void): void {
    onUnauthorized = handler;
}

// axios.isAxiosError (not instanceof) so the check still holds if a second copy of axios
// is ever loaded — instanceof compares classes, and each copy brings its own.
http.interceptors.response.use(undefined, async (error: unknown) => {
    if (!axios.isAxiosError(error) || !error.response || !error.config) {
        throw error;
    }

    const config = error.config as RetriableConfig;

    // 419: CSRF token expired (e.g. tab left open). Refresh once and replay.
    if (error.response.status === 419 && !config._csrfRetried) {
        config._csrfRetried = true;
        await ensureCsrfCookie();

        return http.request(config);
    }

    if (error.response.status === 401) {
        onUnauthorized();
    }

    throw error;
});

export function isAbort(error: unknown): boolean {
    return axios.isCancel(error);
}

export function statusOf(error: unknown): number | null {
    return axios.isAxiosError(error) ? (error.response?.status ?? null) : null;
}

export function messageOf(error: unknown, fallback = 'Something went wrong. Please try again.'): string {
    if (axios.isAxiosError(error)) {
        const message = (error.response?.data as { message?: unknown } | undefined)?.message;

        if (typeof message === 'string' && message !== '') {
            return message;
        }
    }

    return fallback;
}
