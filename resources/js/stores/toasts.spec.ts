import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useToastStore } from './toasts';

describe('toast store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.useFakeTimers();
    });
    afterEach(() => vi.useRealTimers());

    it('auto-dismisses, but not while held', () => {
        const store = useToastStore();
        store.push({ title: 'A', tone: 'info' });
        const [toast] = store.toasts;

        store.hold(toast!.id);
        vi.advanceTimersByTime(10_000);
        expect(store.toasts).toHaveLength(1);

        store.release(toast!.id);
        vi.advanceTimersByTime(6_000);
        expect(store.toasts).toHaveLength(0);
    });

    it('keeps at most four, dropping the oldest', () => {
        const store = useToastStore();
        ['A', 'B', 'C', 'D', 'E'].forEach((title) => store.push({ title, tone: 'info' }));

        expect(store.toasts.map((t) => t.title)).toEqual(['B', 'C', 'D', 'E']);
    });

    it('clears everything at once', () => {
        const store = useToastStore();
        store.push({ title: 'A', tone: 'info' });
        store.clear();

        expect(store.toasts).toEqual([]);
    });
});
