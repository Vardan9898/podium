import { Permission } from '@/types/api';
import { describe, expect, it, vi } from 'vitest';
import type { RouteLocationNormalized } from 'vue-router';
import { authGuard, HOME, safeRedirect, type AuthState } from './guards';

function route(meta: RouteLocationNormalized['meta'], fullPath = '/somewhere'): RouteLocationNormalized {
    return { meta, fullPath } as RouteLocationNormalized;
}

function auth(authenticated: boolean, permissions: Permission[] = []): AuthState {
    return {
        isAuthenticated: authenticated,
        ensureLoaded: vi.fn(() => Promise.resolve()),
        can: (permission) => permissions.includes(permission),
    };
}

describe('authGuard', () => {
    it('waits for the session before deciding', async () => {
        const state = auth(true);
        await authGuard(route({}), state);

        expect(state.ensureLoaded).toHaveBeenCalledOnce();
    });

    it('sends guests to login and remembers where they were going', async () => {
        expect(await authGuard(route({ requiresAuth: true }, '/proposals?page=2'), auth(false))).toEqual({
            name: 'login',
            query: { redirect: '/proposals?page=2' },
        });
    });

    it('keeps signed-in users out of guest-only pages', async () => {
        expect(await authGuard(route({ guestOnly: true }), auth(true))).toEqual(HOME);
    });

    it('redirects users lacking the route permission', async () => {
        const meta = { requiresAuth: true, permission: Permission.CreateProposals };

        expect(await authGuard(route(meta), auth(true, [Permission.ReviewProposals]))).toEqual(HOME);
        expect(await authGuard(route(meta), auth(true, [Permission.CreateProposals]))).toBe(true);
    });

    it('lets everyone through public routes', async () => {
        expect(await authGuard(route({}), auth(false))).toBe(true);
    });
});

describe('safeRedirect', () => {
    it.each([
        ['/proposals/4', '/proposals/4'],
        ['//evil.example.com', null],
        ['https://evil.example.com', null],
        ['proposals', null],
        [undefined, null],
        [['/a'], null],
    ])('%s → %s', (input, expected) => {
        expect(safeRedirect(input)).toBe(expected);
    });
});
