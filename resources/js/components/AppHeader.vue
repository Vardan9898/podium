<script setup lang="ts">
import NotificationBell from '@/components/NotificationBell.vue';
import AppButton from '@/components/ui/AppButton.vue';
import { useCan } from '@/composables/useCan';
import { useAuthStore } from '@/stores/auth';
import { Permission } from '@/types/api';
import { ref } from 'vue';
import { RouterLink } from 'vue-router';

const auth = useAuthStore();
const canSubmit = useCan(Permission.CreateProposals);
const signingOut = ref(false);

async function signOut(): Promise<void> {
    signingOut.value = true;

    try {
        await auth.logout();
    } finally {
        signingOut.value = false;
    }
}
</script>

<template>
    <header class="sticky top-0 z-30 border-b border-rule/80 bg-paper/85 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4 sm:px-6">
            <RouterLink :to="auth.isAuthenticated ? { name: 'proposals.index' } : { name: 'login' }" class="group flex items-baseline gap-2">
                <span class="font-display text-2xl leading-none font-bold tracking-tight">Podium</span>
                <span class="hidden font-mono text-[10px] tracking-[0.2em] text-ink-faint uppercase sm:inline">Call for papers</span>
            </RouterLink>

            <nav v-if="auth.user" class="ml-auto flex items-center gap-1 sm:gap-2" aria-label="Main">
<!-- The wrappers own the breakpoint: AppButton sets its own display utility, which a
                     "hidden" class passed from here cannot reliably override. -->
                <span v-if="canSubmit" class="hidden sm:contents">
                    <AppButton :to="{ name: 'proposals.create' }">New proposal</AppButton>
                </span>
                <RouterLink
                    v-if="canSubmit"
                    :to="{ name: 'proposals.create' }"
                    class="grid size-10 shrink-0 place-items-center rounded-full bg-ink text-xl text-card sm:hidden"
                    aria-label="New proposal"
                >
                    +
                </RouterLink>
                <NotificationBell />
                <div class="flex items-center gap-2 border-l border-rule pl-2 sm:pl-3">
                    <div class="hidden text-right leading-tight md:block">
                        <p class="text-sm font-medium">{{ auth.user.name }}</p>
                        <p class="font-mono text-[10px] tracking-widest text-ink-faint uppercase">{{ auth.user.role }}</p>
                    </div>
                    <AppButton variant="ghost" :loading="signingOut" @click="signOut">Sign out</AppButton>
                </div>
            </nav>
        </div>
    </header>
</template>
