import type { AppNotification, Paginated } from '@/types/api';
import { http } from './http';

export type NotificationPage = Paginated<AppNotification> & { unread_count: number };

export async function listNotifications(): Promise<NotificationPage> {
    const { data } = await http.get<NotificationPage>('/notifications');

    return data;
}

/** Omit ids to mark everything as read. */
export async function markNotificationsRead(ids?: string[]): Promise<void> {
    await http.post('/notifications/read', ids ? { ids } : {});
}
