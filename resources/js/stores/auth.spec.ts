import { AxiosError, AxiosHeaders } from 'axios';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useAuthStore } from './auth';

const fetchCurrentUser = vi.fn();

vi.mock('@/api/auth', () => ({
    fetchCurrentUser: () => fetchCurrentUser(),
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
}));

const failure = (status: number) =>
    new AxiosError('x', String(status), undefined, undefined, {
        status,
        data: {},
        statusText: '',
        headers: {},
        config: { headers: new AxiosHeaders() },
    });

const user = { id: 1, name: 'Sam', email: 'sam@example.com', role: 'speaker', permissions: ['proposals.create'] };

describe('auth store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        fetchCurrentUser.mockReset();
    });

    it('loads the session once and exposes permissions', async () => {
        fetchCurrentUser.mockResolvedValue(user);
        const auth = useAuthStore();

        await Promise.all([auth.ensureLoaded(), auth.ensureLoaded()]);

        expect(fetchCurrentUser).toHaveBeenCalledOnce();
        expect(auth.can('proposals.create')).toBe(true);
        expect(auth.can('proposals.review')).toBe(false);
    });

    it('treats 401 as a guest and does not ask again', async () => {
        fetchCurrentUser.mockRejectedValue(failure(401));
        const auth = useAuthStore();

        await auth.ensureLoaded();
        await auth.ensureLoaded();

        expect(auth.isAuthenticated).toBe(false);
        expect(fetchCurrentUser).toHaveBeenCalledOnce();
    });

    it('continues as guest on other failures and retries on the next call', async () => {
        fetchCurrentUser.mockRejectedValueOnce(failure(500)).mockResolvedValueOnce(user);
        const auth = useAuthStore();

        await expect(auth.ensureLoaded()).resolves.toBeUndefined();
        expect(auth.isAuthenticated).toBe(false);

        await auth.ensureLoaded();
        expect(auth.isAuthenticated).toBe(true);
    });
});
