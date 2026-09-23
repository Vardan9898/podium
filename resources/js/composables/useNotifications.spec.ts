import { ActivityType } from '@/types/api';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, nextTick } from 'vue';
import { mount } from '@vue/test-utils';
import { useNotifications } from './useNotifications';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notifications';
import { useToastStore } from '@/stores/toasts';

const channel = { notification: vi.fn() };
const echoInstance = { private: vi.fn(() => channel), leave: vi.fn() };
const disconnectEcho = vi.fn();

vi.mock('@/lib/echo', () => ({ echo: () => echoInstance, disconnectEcho: () => disconnectEcho() }));
vi.mock('@/api/notifications', () => ({
    listNotifications: () => Promise.resolve({ data: [], meta: {}, unread_count: 0 }),
    markNotificationsRead: () => Promise.resolve(),
}));
vi.mock('@/api/auth', () => ({ fetchCurrentUser: vi.fn(), login: vi.fn(), register: vi.fn(), logout: vi.fn() }));

const Host = defineComponent({ setup: () => useNotifications(), template: '<div />' });
const user = (id: number) => ({ id, name: `User ${id}`, email: `u${id}@example.com`, role: 'reviewer' as const, permissions: [] });

describe('useNotifications', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        channel.notification.mockReset();
        echoInstance.private.mockClear();
        echoInstance.leave.mockClear();
        disconnectEcho.mockClear();
    });

    it('subscribes to the signed-in user\'s private channel', async () => {
        const auth = useAuthStore();
        auth.user = user(7);
        mount(Host);
        await nextTick();

        expect(echoInstance.private).toHaveBeenCalledWith('App.Models.User.7');
        expect(useNotificationStore().owner).toBe(7);
    });

    it('routes a broadcast into the store', async () => {
        useAuthStore().user = user(7);
        mount(Host);
        await nextTick();
        const handler = channel.notification.mock.calls[0]![0] as (n: unknown) => void;

        handler({ id: 'n1', type: ActivityType.Submitted, proposal_id: 3, proposal_title: 'A talk', message: 'New proposal.', actor_name: 'Sam' });

        expect(useNotificationStore().unreadCount).toBe(1);
        expect(useToastStore().toasts).toHaveLength(1);
    });

    it('leaves the channel, disconnects and clears everything on sign-out', async () => {
        const auth = useAuthStore();
        auth.user = user(7);
        mount(Host);
        await nextTick();
        useToastStore().push({ title: 'Someone else\'s talk', tone: 'info' });

        auth.user = null;
        await nextTick();

        expect(echoInstance.leave).toHaveBeenCalledWith('App.Models.User.7');
        expect(disconnectEcho).toHaveBeenCalledOnce();
        expect(useNotificationStore().owner).toBeNull();
        expect(useToastStore().toasts).toEqual([]);
    });

    it('switches channels when a different user signs in', async () => {
        const auth = useAuthStore();
        auth.user = user(7);
        mount(Host);
        await nextTick();

        auth.user = user(9);
        await nextTick();

        expect(echoInstance.leave).toHaveBeenCalledWith('App.Models.User.7');
        expect(echoInstance.private).toHaveBeenLastCalledWith('App.Models.User.9');
        expect(useNotificationStore().owner).toBe(9);
    });
});
