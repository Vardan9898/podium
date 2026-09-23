import { Permission } from '@/types/api';
import { describe, expect, it, vi } from 'vitest';
import type { RouteLocationNormalized } from 'vue-router';
import { authGuard, HOME, safeRedirect, type AuthState } from './guards';

function route(meta: RouteLocationNormalized['meta'], fullPath = '/somewhere', query: Record<string, string> = {}): RouteLocationNormalized {
    return { meta, fullPath, query } as unknown as RouteLocationNormalized;
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

    it('sends a signed-in user to a safe pending redirect, ignoring an unsafe one', async () => {
        const guestOnly = { guestOnly: true };

        expect(await authGuard(route(guestOnly, '/login', { redirect: '/proposals/12' }), auth(true))).toBe('/proposals/12');
        expect(await authGuard(route(guestOnly, '/login', { redirect: 'https://evil.example.com' }), auth(true))).toEqual(HOME);
    });

    it('does not decide before the session has loaded', async () => {
        let settled = false;
        const state = { ...auth(false), ensureLoaded: () => new Promise<void>((resolve) => setTimeout(() => { settled = true; resolve(); }, 10)) };

        const decision = authGuard(route({ requiresAuth: true }), state);
        expect(settled).toBe(false);
        await decision;
        expect(settled).toBe(true);
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
