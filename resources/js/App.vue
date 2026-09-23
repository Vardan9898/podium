<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue';
import ToastStack from '@/components/ui/ToastStack.vue';
import { useNotifications } from '@/composables/useNotifications';
import { useAuthStore } from '@/stores/auth';
import { nextTick, ref, watch } from 'vue';
import { RouterView, useRoute, useRouter } from 'vue-router';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

useNotifications();

// Route changes replace the page without a browser navigation, so focus and screen readers
// have to be told: move focus to <main> and announce the new title.
const main = ref<HTMLElement | null>(null);
const announcement = ref('');

// Deliberately route.path, not fullPath: filters and paging live in the query string, and
// moving focus there would yank it out of the search box mid-keystroke.
watch(
    () => route.path,
    async () => {
        await nextTick();
        main.value?.focus();
        announcement.value = typeof route.meta.title === 'string' ? route.meta.title : '';
    },
);

// Signed out or session expired: leave protected pages. Only an expired session is sent
// back to where it was; after a deliberate sign-out the next person starts fresh.
watch(
    () => auth.isAuthenticated,
    (authenticated, wasAuthenticated) => {
        if (wasAuthenticated && !authenticated && route.meta.requiresAuth) {
            void router.push({ name: 'login', query: auth.sessionExpired ? { redirect: route.fullPath } : {} });
        }
    },
);
</script>

<template>
    <a href="#main" class="sr-only z-50 rounded bg-ink px-4 py-2 text-card focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Skip to content</a>
    <AppHeader />
    <main id="main" ref="main" tabindex="-1" class="mx-auto max-w-6xl px-4 pt-8 pb-24 focus:outline-none sm:px-6 sm:pt-12">
        <RouterView />
    </main>
    <p aria-live="polite" class="sr-only">{{ announcement }}</p>
    <ToastStack />
</template>
