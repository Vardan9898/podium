import { fetchClientConfig } from '@/api/config';
import { Role, type ClientConfig } from '@/types/api';
import { defineStore } from 'pinia';
import { ref } from 'vue';

/** Mirrors the server defaults so the UI still works if /api/config is briefly unavailable. */
const DEFAULTS: ClientConfig = {
    registerable_roles: [Role.Speaker],
    rating: { min: 1, max: 10 },
    attachment_max_kilobytes: 4096,
    tags_max_per_proposal: 10,
};

export const useConfigStore = defineStore('config', () => {
    const settings = ref<ClientConfig>(DEFAULTS);
    let loading: Promise<void> | null = null;

    /**
     * Awaited once by the router guard. A failure falls back to the defaults above and is not
     * retried per navigation, so a broken endpoint can never stall routing.
     */
    function ensureLoaded(): Promise<void> {
        loading ??= fetchClientConfig()
            .then((config) => {
                settings.value = config;
            })
            .catch(() => undefined);

        return loading;
    }

    return { settings, ensureLoaded };
});
