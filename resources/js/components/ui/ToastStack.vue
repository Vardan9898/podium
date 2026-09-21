<script setup lang="ts">
import { useToastStore } from '@/stores/toasts';
import { RouterLink } from 'vue-router';

const store = useToastStore();

const accent = { info: 'bg-signal', success: 'bg-approved', error: 'bg-rejected' } as const;
</script>

<template>
    <div class="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex flex-col items-end gap-2 sm:inset-x-auto sm:right-6 sm:bottom-6" aria-live="polite">
        <TransitionGroup
            enter-from-class="translate-y-2 opacity-0"
            leave-to-class="translate-x-4 opacity-0"
            enter-active-class="transition duration-300"
            leave-active-class="transition duration-200"
        >
            <div
                v-for="toast in store.toasts"
                :key="toast.id"
                class="pointer-events-auto relative flex w-full max-w-sm overflow-hidden rounded-xl border border-ink/10 bg-ink text-card shadow-[0_18px_40px_-18px_rgb(27_26_23/0.6)]"
                :role="toast.tone === 'error' ? 'alert' : 'status'"
            >
                <span :class="accent[toast.tone]" class="w-1.5 shrink-0" aria-hidden="true" />
                <div class="min-w-0 flex-1 px-4 py-3">
                    <RouterLink v-if="toast.to" :to="toast.to" class="block truncate font-medium hover:underline" @click="store.dismiss(toast.id)">
                        {{ toast.title }}
                    </RouterLink>
                    <p v-else class="truncate font-medium">{{ toast.title }}</p>
                    <p v-if="toast.body" class="mt-0.5 text-sm text-card/70">{{ toast.body }}</p>
                </div>
                <button class="px-3 text-card/60 hover:text-card" @click="store.dismiss(toast.id)">
                    <span aria-hidden="true">✕</span><span class="sr-only">Dismiss</span>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
