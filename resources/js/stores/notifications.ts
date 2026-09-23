import * as notificationsApi from '@/api/notifications';
import type { ActivityPayload, AppNotification } from '@/types/api';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { useToastStore } from './toasts';

/** Shape delivered by Echo's `.notification()` for a broadcast Laravel notification. */
export type BroadcastNotification = ActivityPayload & { id: string };

export const useNotificationStore = defineStore('notifications', () => {
    /** Whose notifications these are; a response for a different owner is discarded. */
    const owner = ref<number | null>(null);
    const items = ref<AppNotification[]>([]);
    const unreadCount = ref(0);
    /** Bumped on every live event; pages watch it to refresh what they show. */
    const lastActivity = ref<ActivityPayload | null>(null);

    const hasUnread = computed(() => unreadCount.value > 0);

    async function load(): Promise<void> {
        const requestedFor = owner.value;
        const page = await notificationsApi.listNotifications();

        if (owner.value !== requestedFor) {
            return; // the user changed while this was in flight
        }

        items.value = page.data;
        unreadCount.value = page.unread_count;
    }

    function receive({ id, ...payload }: BroadcastNotification): void {
        items.value = [
            { id, type: payload.type, data: payload, read_at: null, created_at: new Date().toISOString() },
            ...items.value.filter((item) => item.id !== id),
        ];
        unreadCount.value++;
        lastActivity.value = payload;

        useToastStore().push({
            title: payload.proposal_title,
            body: payload.message,
            tone: 'info',
            to: `/proposals/${payload.proposal_id}`,
        });
    }

    async function markRead(ids?: string[]): Promise<void> {
        const previousItems = items.value;
        const previousUnread = unreadCount.value;
        const now = new Date().toISOString();
        const targets = new Set(ids ?? items.value.map((item) => item.id));
        const newlyRead = items.value.filter((item) => item.read_at === null && targets.has(item.id)).length;

        items.value = items.value.map((item) => (targets.has(item.id) && item.read_at === null ? { ...item, read_at: now } : item));
        unreadCount.value = ids ? Math.max(0, unreadCount.value - newlyRead) : 0;

        try {
            await notificationsApi.markNotificationsRead(ids);
        } catch (error: unknown) {
            // Put the badge back rather than claiming something was read that wasn't.
            items.value = previousItems;
            unreadCount.value = previousUnread;
            useToastStore().push({ title: 'Could not mark as read', tone: 'error' });

            throw error;
        }
    }

    function reset(nextOwner: number | null = null): void {
        owner.value = nextOwner;
        items.value = [];
        unreadCount.value = 0;
        lastActivity.value = null;
    }

    return { owner, items, unreadCount, hasUnread, lastActivity, load, receive, markRead, reset };
});
