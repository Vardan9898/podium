import { fetchClientConfig } from '@/api/config';
import type { ClientConfig } from '@/types/api';
import { defineStore } from 'pinia';
import { ref } from 'vue';

/** Mirrors the server defaults so the UI still works if /api/config is briefly unavailable. */
const DEFAULTS: ClientConfig = {
    allow_admin_registration: false,
    rating: { min: 1, max: 10 },
    attachment_max_kilobytes: 4096,
    tags_max_per_proposal: 10,
};

export const useConfigStore = defineStore('config', () => {
    const settings = ref<ClientConfig>(DEFAULTS);
    let loading: Promise<void> | null = null;

    function ensureLoaded(): Promise<void> {
        loading ??= fetchClientConfig()
            .then((config) => {
                settings.value = config;
            })
            .catch(() => {
                loading = null; // retry on the next navigation
            });

        return loading;
    }

    return { settings, ensureLoaded };
});
