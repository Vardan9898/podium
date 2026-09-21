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

export const useToastStore = defineStore('toasts', () => {
    const toasts = ref<Toast[]>([]);
    let nextId = 1;

    function dismiss(id: number): void {
        toasts.value = toasts.value.filter((toast) => toast.id !== id);
    }

    function push(toast: Omit<Toast, 'id'>): void {
        const id = nextId++;
        toasts.value = [...toasts.value.slice(-3), { ...toast, id }];
        window.setTimeout(() => dismiss(id), DISMISS_AFTER_MS);
    }

    return { toasts, push, dismiss };
});
