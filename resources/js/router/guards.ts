import type { Permission } from '@/types/api';
import type { RouteLocationNormalized, RouteLocationRaw } from 'vue-router';

declare module 'vue-router' {
    interface RouteMeta {
        requiresAuth?: boolean;
        guestOnly?: boolean;
        permission?: Permission;
        title?: string;
    }
}

export interface AuthState {
    readonly isAuthenticated: boolean;
    ensureLoaded(): Promise<void>;
    can(permission: Permission): boolean;
}

export const HOME: RouteLocationRaw = { name: 'proposals.index' };

/** Only same-app paths are honoured, so `?redirect=` can never send users off-site. */
export function safeRedirect(value: unknown): string | null {
    return typeof value === 'string' && value.startsWith('/') && !value.startsWith('//') ? value : null;
}

export async function authGuard(to: RouteLocationNormalized, auth: AuthState): Promise<true | RouteLocationRaw> {
    await auth.ensureLoaded();

    if (to.meta.guestOnly && auth.isAuthenticated) {
        return safeRedirect(to.query.redirect) ?? HOME;
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.permission && !auth.can(to.meta.permission)) {
        return HOME;
    }

    return true;
}
