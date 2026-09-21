import { useAuthStore } from '@/stores/auth';
import { useConfigStore } from '@/stores/config';
import { Permission } from '@/types/api';
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { authGuard } from './guards';

export const routes: RouteRecordRaw[] = [
    { path: '/', redirect: { name: 'proposals.index' } },
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/LoginPage.vue'),
        meta: { guestOnly: true, title: 'Sign in' },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/pages/RegisterPage.vue'),
        meta: { guestOnly: true, title: 'Create account' },
    },
    {
        path: '/proposals',
        name: 'proposals.index',
        component: () => import('@/pages/proposals/IndexPage.vue'),
        meta: { requiresAuth: true, title: 'Proposals' },
    },
    {
        path: '/proposals/new',
        name: 'proposals.create',
        component: () => import('@/pages/proposals/CreatePage.vue'),
        meta: { requiresAuth: true, permission: Permission.CreateProposals, title: 'Submit a talk' },
    },
    {
        path: '/proposals/:id(\\d+)',
        name: 'proposals.show',
        component: () => import('@/pages/proposals/ShowPage.vue'),
        props: (route) => ({ id: Number(route.params.id) }),
        meta: { requiresAuth: true, title: 'Proposal' },
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/pages/NotFoundPage.vue'),
        meta: { title: 'Not found' },
    },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: (_to, _from, saved) => saved ?? { top: 0 },
});

router.beforeEach(async (to) => {
    await useConfigStore().ensureLoaded();

    return authGuard(to, useAuthStore());
});

router.afterEach((to) => {
    document.title = [to.meta.title, import.meta.env.VITE_APP_NAME].filter(Boolean).join(' · ');
});
