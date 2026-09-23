import { ActivityType, type AppNotification } from '@/types/api';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useNotificationStore } from './notifications';
import { useToastStore } from './toasts';

const listNotifications = vi.fn();
const markNotificationsRead = vi.fn();

vi.mock('@/api/notifications', () => ({
    listNotifications: () => listNotifications(),
    markNotificationsRead: (ids?: string[]) => markNotificationsRead(ids),
}));

const stored = (id: string, read = false): AppNotification => ({
    id,
    type: ActivityType.Submitted,
    data: { type: ActivityType.Submitted, proposal_id: 1, proposal_title: 'A talk', message: 'New proposal.', actor_name: 'Sam' },
    read_at: read ? '2026-01-01T00:00:00Z' : null,
    created_at: '2026-01-01T00:00:00Z',
});

describe('notification store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        listNotifications.mockReset().mockResolvedValue({ data: [stored('a'), stored('b', true)], meta: {}, unread_count: 1 });
        markNotificationsRead.mockReset().mockResolvedValue(undefined);
    });

    it('a live event prepends the item, bumps the badge and raises a toast', () => {
        const store = useNotificationStore();

        store.receive({ id: 'live-1', type: ActivityType.Reviewed, proposal_id: 42, proposal_title: 'Edge caching', message: 'Reviewed.', actor_name: 'Riley' });

        expect(store.items[0]!.id).toBe('live-1');
        expect(store.unreadCount).toBe(1);
        expect(store.lastActivity?.proposal_id).toBe(42);
        expect(useToastStore().toasts[0]).toMatchObject({ title: 'Edge caching', to: '/proposals/42' });
    });

    it('ignores a duplicate of an item it already holds', () => {
        const store = useNotificationStore();
        const event = { id: 'x', type: ActivityType.Submitted, proposal_id: 1, proposal_title: 'A', message: 'm', actor_name: 'n' };

        store.receive(event);
        store.receive(event);

        expect(store.items).toHaveLength(1);
    });

    it('drops a response that belongs to the previous user', async () => {
        const store = useNotificationStore();
        store.reset(1);
        const inFlight = store.load();
        store.reset(2); // the user signed out and someone else signed in

        await inFlight;

        expect(store.items).toEqual([]);
        expect(store.unreadCount).toBe(0);
    });

    it('keeps a response for the user who asked for it', async () => {
        const store = useNotificationStore();
        store.reset(1);

        await store.load();

        expect(store.items).toHaveLength(2);
        expect(store.unreadCount).toBe(1);
    });

    it('restores the badge and warns when marking as read fails', async () => {
        const store = useNotificationStore();
        store.reset(1);
        await store.load();
        markNotificationsRead.mockRejectedValueOnce(new Error('offline'));

        await expect(store.markRead()).rejects.toThrow('offline');

        expect(store.unreadCount).toBe(1);
        expect(store.items[0]!.read_at).toBeNull();
        expect(useToastStore().toasts[0]).toMatchObject({ tone: 'error' });
    });

    it('marks only the given ids and adjusts the count', async () => {
        const store = useNotificationStore();
        store.reset(1);
        await store.load();

        await store.markRead(['a']);

        expect(markNotificationsRead).toHaveBeenCalledWith(['a']);
        expect(store.items.find((i) => i.id === 'a')!.read_at).not.toBeNull();
        expect(store.unreadCount).toBe(0);
    });
});
