import { Role } from '@/types/api';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useConfigStore } from './config';

const fetchClientConfig = vi.fn();
vi.mock('@/api/config', () => ({ fetchClientConfig: () => fetchClientConfig() }));

const server = {
    registerable_roles: [Role.Speaker, Role.Reviewer, Role.Admin],
    rating: { min: 1, max: 5 },
    attachment_max_kilobytes: 2048,
    tags_max_per_proposal: 4,
};

describe('config store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        fetchClientConfig.mockReset().mockResolvedValue(server);
    });

    it('serves server settings once loaded', async () => {
        const store = useConfigStore();
        expect(store.settings.registerable_roles).toEqual([Role.Speaker]);

        await store.ensureLoaded();

        expect(store.settings).toEqual(server);
        expect(store.settings.registerable_roles).toEqual([Role.Speaker, Role.Reviewer, Role.Admin]);
    });

    it('loads once, however many navigations happen', async () => {
        const store = useConfigStore();

        await Promise.all([store.ensureLoaded(), store.ensureLoaded()]);
        await store.ensureLoaded();

        expect(fetchClientConfig).toHaveBeenCalledOnce();
    });

    it('falls back to defaults and never blocks routing when the endpoint fails', async () => {
        fetchClientConfig.mockRejectedValue(new Error('down'));
        const store = useConfigStore();

        await expect(store.ensureLoaded()).resolves.toBeUndefined();

        expect(store.settings.rating).toEqual({ min: 1, max: 10 });
    });
});
