<script setup lang="ts">
import SelectField from '@/components/ui/SelectField.vue';
import TagInput from '@/components/TagInput.vue';
import { STATUS_OPTIONS } from '@/lib/format';
import type { ProposalStatus } from '@/types/api';
import { onBeforeUnmount, ref, useId, watch } from 'vue';

export interface Filters {
    search: string;
    tags: string[];
    status: ProposalStatus | '';
}

const SEARCH_DEBOUNCE_MS = 300;

const filters = defineModel<Filters>({ required: true });

const searchId = useId();
const search = ref(filters.value.search);
let timer: ReturnType<typeof setTimeout> | undefined;

// Typing is debounced; tags and status apply immediately.
watch(search, (value) => {
    clearTimeout(timer);
    timer = setTimeout(() => (filters.value = { ...filters.value, search: value }), SEARCH_DEBOUNCE_MS);
});

// Back/forward navigation changes the URL, and therefore the model, underneath us.
watch(
    () => filters.value.search,
    (value) => {
        if (value !== search.value) {
            search.value = value;
        }
    },
);

onBeforeUnmount(() => clearTimeout(timer));

const hasFilters = (): boolean => filters.value.search !== '' || filters.value.tags.length > 0 || filters.value.status !== '';
</script>

<template>
    <section aria-label="Filter proposals" class="relative z-10 grid gap-4 rounded-2xl border border-rule bg-card/80 p-4 backdrop-blur md:grid-cols-[1.4fr_1.6fr_0.9fr] md:items-end">
        <div class="space-y-1.5">
            <label :for="searchId" class="block text-sm font-medium">Search titles</label>
            <div class="relative">
                <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.6" />
                    <path d="m14 14 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                </svg>
                <input
                    :id="searchId"
                    v-model="search"
                    type="search"
                    placeholder="e.g. queues"
                    class="w-full rounded-lg border border-rule bg-card py-2.5 pr-3 pl-9 text-[15px] placeholder:text-ink-faint focus:border-ink focus:ring-2 focus:ring-signal/25 focus:outline-none"
                />
            </div>
        </div>
        <TagInput
            :model-value="filters.tags"
            label="Tags (any of)"
            :allow-create="false"
            placeholder="Filter by tag…"
            @update:model-value="(tags) => (filters = { ...filters, tags })"
        />
        <div class="flex items-end gap-2">
            <SelectField
                class="flex-1"
                :model-value="filters.status"
                label="Status"
                :options="STATUS_OPTIONS"
                placeholder="Any status"
                @update:model-value="(status) => (filters = { ...filters, status })"
            />
            <button
                v-if="hasFilters()"
                type="button"
                class="mb-1 rounded-full px-3 py-2 text-sm text-ink-soft underline-offset-4 hover:text-signal hover:underline"
                @click="((search = ''), (filters = { search: '', tags: [], status: '' }))"
            >
                Reset
            </button>
        </div>
    </section>
</template>
