<script setup lang="ts">
import { timeAgo } from '@/lib/format';
import { useNotificationStore } from '@/stores/notifications';
import type { AppNotification } from '@/types/api';
import { onBeforeUnmount, onMounted, ref, useId } from 'vue';
import { useRouter } from 'vue-router';

const store = useNotificationStore();
const router = useRouter();
const open = ref(false);
const root = ref<HTMLElement | null>(null);
const trigger = ref<HTMLButtonElement | null>(null);
const panelId = useId();
const headingId = `${panelId}-heading`;

function close({ restoreFocus = false } = {}): void {
    open.value = false;

    if (restoreFocus) {
        trigger.value?.focus();
    }
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape' && open.value) {
        close({ restoreFocus: true });
    }
}

function openNotification(notification: AppNotification): void {
    close();

    if (notification.read_at === null) {
        store.markRead([notification.id]).catch(() => undefined); // the store restores state and warns
    }

    void router.push({ name: 'proposals.show', params: { id: notification.data.proposal_id } });
}

function markAllRead(): void {
    store.markRead().catch(() => undefined);
}

function onDocumentClick(event: MouseEvent): void {
    if (root.value && !root.value.contains(event.target as Node)) {
        close();
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div ref="root" class="relative">
        <button
            ref="trigger"
            type="button"
            class="relative grid size-10 place-items-center rounded-full text-ink hover:bg-paper-deep"
            :aria-expanded="open"
            :aria-controls="panelId"
            :aria-label="store.hasUnread ? `Notifications, ${store.unreadCount} unread` : 'Notifications'"
            @click="open = !open"
        >
            <svg viewBox="0 0 24 24" class="size-5" fill="none" aria-hidden="true">
                <path d="M6 9a6 6 0 1 1 12 0c0 5 2 6.5 2 6.5H4S6 14 6 9Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                <path d="M10 19a2 2 0 0 0 4 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            </svg>
            <span
                v-if="store.hasUnread"
                class="absolute -top-0.5 -right-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-signal px-1 font-mono text-[10px] font-medium text-card ring-2 ring-paper"
                aria-hidden="true"
            >
                {{ store.unreadCount > 99 ? '99+' : store.unreadCount }}
            </span>
        </button>

        <Transition enter-from-class="opacity-0 -translate-y-1" leave-to-class="opacity-0" enter-active-class="transition duration-150" leave-active-class="transition duration-100">
            <div
                v-if="open"
                :id="panelId"
                role="dialog"
                :aria-labelledby="headingId"
                class="fixed inset-x-3 top-16 z-40 overflow-hidden rounded-2xl border border-rule bg-card shadow-[0_24px_60px_-24px_rgb(27_26_23/0.45)] sm:absolute sm:inset-x-auto sm:top-12 sm:right-0 sm:w-96"
            >
                <div class="flex items-center justify-between border-b border-rule px-4 py-3">
                    <h2 :id="headingId" class="font-display font-semibold">Notifications</h2>
                    <button v-if="store.hasUnread" type="button" class="text-xs text-ink-soft hover:text-signal" @click="markAllRead">Mark all read</button>
                </div>
                <ul v-if="store.items.length" class="max-h-[60vh] divide-y divide-rule overflow-auto">
                    <li v-for="notification in store.items" :key="notification.id">
                        <button type="button" class="flex w-full gap-3 px-4 py-3 text-left hover:bg-paper" @click="openNotification(notification)">
                            <span class="mt-1.5 size-2 shrink-0 rounded-full" :class="notification.read_at ? 'bg-transparent' : 'bg-signal'" aria-hidden="true" />
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium">{{ notification.data.proposal_title }}</span>
                                <span class="block text-sm text-ink-soft">{{ notification.data.message }}</span>
                                <span class="mt-0.5 block font-mono text-[11px] text-ink-faint">
                                    {{ timeAgo(notification.created_at) }}<span v-if="!notification.read_at" class="sr-only">, unread</span>
                                </span>
                            </span>
                        </button>
                    </li>
                </ul>
                <p v-else class="px-4 py-10 text-center text-sm text-ink-faint">You're all caught up.</p>
            </div>
        </Transition>
    </div>
</template>
