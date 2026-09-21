import { defineStore } from 'pinia';
import { ref } from 'vue';

export type ToastTone = 'info' | 'success' | 'error';

export interface Toast {
    id: number;
    title: string;
    body?: string;
    tone: ToastTone;
    to?: string;
}

const DISMISS_AFTER_MS = 6000;
const MAX_VISIBLE = 4;

export const useToastStore = defineStore('toasts', () => {
    const toasts = ref<Toast[]>([]);
    const timers = new Map<number, ReturnType<typeof setTimeout>>();
    let nextId = 1;

    function dismiss(id: number): void {
        clearTimeout(timers.get(id));
        timers.delete(id);
        toasts.value = toasts.value.filter((toast) => toast.id !== id);
    }

    /** (Re)starts the auto-dismiss countdown. */
    function release(id: number): void {
        clearTimeout(timers.get(id));
        timers.set(id, setTimeout(() => dismiss(id), DISMISS_AFTER_MS));
    }

    /** Pauses auto-dismiss while the toast is hovered or focused, so its link stays reachable. */
    function hold(id: number): void {
        clearTimeout(timers.get(id));
        timers.delete(id);
    }

    function push(toast: Omit<Toast, 'id'>): void {
        const id = nextId++;
        const overflow = toasts.value.length - (MAX_VISIBLE - 1);
        toasts.value.slice(0, Math.max(0, overflow)).forEach((old) => dismiss(old.id));
        toasts.value = [...toasts.value, { ...toast, id }];
        release(id);
    }

    function clear(): void {
        [...toasts.value].forEach((toast) => dismiss(toast.id));
    }

    return { toasts, push, dismiss, hold, release, clear };
});
