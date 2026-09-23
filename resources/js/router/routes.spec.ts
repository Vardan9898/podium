import { Permission } from '@/types/api';
import { describe, expect, it } from 'vitest';
import type { RouteRecordRaw } from 'vue-router';
import { routes } from './index';

const byName = (name: string): RouteRecordRaw => routes.find((r) => r.name === name)!;

describe('routes', () => {
    it('guards every page it should', () => {
        expect(byName('login').meta).toMatchObject({ guestOnly: true });
        expect(byName('register').meta).toMatchObject({ guestOnly: true });
        expect(byName('proposals.index').meta).toMatchObject({ requiresAuth: true });
        expect(byName('proposals.show').meta).toMatchObject({ requiresAuth: true });
        expect(byName('proposals.create').meta).toMatchObject({
            requiresAuth: true,
            permission: Permission.CreateProposals,
        });
    });

    it('gives every route a title and every auth route a guard', () => {
        for (const route of routes.filter((r) => r.name)) {
            expect(route.meta?.title, `${String(route.name)} has no title`).toBeTruthy();
        }
    });

    it('passes the proposal id to the detail page as a number', () => {
        const props = byName('proposals.show').props as (route: { params: Record<string, string> }) => { id: number };

        expect(props({ params: { id: '42' } })).toEqual({ id: 42 });
    });
});
