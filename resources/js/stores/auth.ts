import * as authApi from '@/api/auth';
import { setUnauthorizedHandler, statusOf } from '@/api/http';
import type { CurrentUser, Permission } from '@/types/api';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const useAuthStore = defineStore('auth', () => {
    const user = ref<CurrentUser | null>(null);
    /** True when the server ended the session (401), as opposed to the user signing out. */
    const sessionExpired = ref(false);
    let loading: Promise<void> | null = null;

    const isAuthenticated = computed(() => user.value !== null);

    function can(permission: Permission): boolean {
        return user.value?.permissions.includes(permission) ?? false;
    }

    /** Resolves the session once per page load; later calls reuse the same promise. */
    function ensureLoaded(): Promise<void> {
        loading ??= authApi
            .fetchCurrentUser()
            .then((current) => {
                user.value = current;
            })
            .catch((error: unknown) => {
                if (statusOf(error) !== 401) {
                    throw error;
                }
            });

        return loading;
    }

    async function login(credentials: authApi.Credentials): Promise<void> {
        user.value = await authApi.login(credentials);
        sessionExpired.value = false;
    }

    async function register(payload: authApi.Registration): Promise<void> {
        user.value = await authApi.register(payload);
        sessionExpired.value = false;
    }

    async function logout(): Promise<void> {
        try {
            await authApi.logout();
        } finally {
            user.value = null;
        }
    }

    function expire(): void {
        sessionExpired.value = user.value !== null;
        user.value = null;
    }

    setUnauthorizedHandler(expire);

    return { user, sessionExpired, isAuthenticated, can, ensureLoaded, login, register, logout };
});
