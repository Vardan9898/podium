import { echo } from '@/lib/echo';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore, type BroadcastNotification } from '@/stores/notifications';
import { useToastStore } from '@/stores/toasts';
import { watch } from 'vue';

const channelFor = (userId: number): string => `App.Models.User.${userId}`;

/**
 * Keeps the notification store in sync with the signed-in user: loads history and
 * subscribes to their private channel on login, leaves it and clears state on logout.
 * Mount once, at the app root.
 */
export function useNotifications(): void {
    const auth = useAuthStore();
    const store = useNotificationStore();
    const toasts = useToastStore();

    watch(
        () => auth.user?.id,
        (userId, previousId) => {
            if (previousId !== undefined) {
                echo().leave(channelFor(previousId));
                store.reset();
                toasts.clear(); // don't leave the previous user's proposal titles on a shared screen
            }

            if (userId !== undefined) {
                void store.load();
                echo()
                    .private(channelFor(userId))
                    .notification((notification: BroadcastNotification) => store.receive(notification));
            }
        },
        { immediate: true },
    );
}
