<script setup lang="ts">
import { isAbort } from '@/api/http';
import { searchTags } from '@/api/tags';
import { LIMITS } from '@/lib/config';
import type { Tag } from '@/types/api';
import { computed, onBeforeUnmount, ref, useId, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        label: string;
        /** When false, only existing tags can be picked (used by filters). */
        allowCreate?: boolean;
        max?: number;
        error?: string;
        hint?: string;
        placeholder?: string;
    }>(),
    { allowCreate: true, max: Infinity, error: undefined, hint: undefined, placeholder: 'Type to search…' },
);

const model = defineModel<string[]>({ required: true });

const id = useId();
const listboxId = `${id}-listbox`;
const query = ref('');
const suggestions = ref<Tag[]>([]);
/** The term `suggestions` were fetched for; guards Enter against stale results. */
const suggestionsTerm = ref<string | null>(null);
const open = ref(false);
const activeIndex = ref(-1);
let debounce: ReturnType<typeof setTimeout> | undefined;
let controller: AbortController | undefined;

const normalize = (name: string): string => name.trim().replace(/\s+/g, ' ');
const sameName = (a: string, b: string): boolean => a.localeCompare(b, undefined, { sensitivity: 'accent' }) === 0;
const isSelected = (name: string): boolean => model.value.some((selected) => sameName(selected, name));
const isFull = computed(() => model.value.length >= props.max);

const visibleSuggestions = computed(() => suggestions.value.filter((tag) => !isSelected(tag.name)));

/** Offer "Create …" only when the typed text doesn't match an existing tag or chip. */
const createCandidate = computed(() => {
    const name = normalize(query.value);

    if (!props.allowCreate || name.length < LIMITS.tagMin || isSelected(name)) {
        return null;
    }

    return suggestions.value.some((tag) => sameName(tag.name, name)) ? null : name;
});

const options = computed(() => [
    ...visibleSuggestions.value.map((tag) => ({ key: `tag-${tag.id}`, name: tag.name, isNew: false })),
    ...(createCandidate.value ? [{ key: 'create', name: createCandidate.value, isNew: true }] : []),
]);

const optionId = (index: number): string => `${id}-option-${index}`;

/** Adds in one batch: a v-model only reflects the parent's value after the next render. */
function add(...names: string[]): void {
    const next = [...model.value];

    for (const name of names.map(normalize)) {
        const duplicate = next.some((selected) => sameName(selected, name));

        if (name.length >= LIMITS.tagMin && !duplicate && next.length < props.max) {
            next.push(name);
        }
    }

    if (next.length !== model.value.length) {
        model.value = next;
    }

    query.value = '';
    activeIndex.value = -1;
}

function remove(name: string): void {
    model.value = model.value.filter((selected) => selected !== name);
}

function commitTyped(): void {
    const active = options.value[activeIndex.value];

    if (active) {
        add(active.name);
    } else if (createCandidate.value) {
        add(createCandidate.value);
    } else {
        // Without creation, Enter picks the exact match, else the best (first) suggestion.
        const typed = normalize(query.value);
        const fresh = suggestionsTerm.value === typed ? visibleSuggestions.value : [];
        const match = fresh.find((tag) => sameName(tag.name, typed)) ?? fresh[0];

        if (match) {
            add(match.name);
        }
    }
}

function onKeydown(event: KeyboardEvent): void {
    switch (event.key) {
        case 'Enter':
            if (query.value.trim() !== '' || activeIndex.value >= 0) {
                event.preventDefault();
                commitTyped();
            }
            break;
        case 'ArrowDown':
            event.preventDefault();
            open.value = true;
            activeIndex.value = Math.min(activeIndex.value + 1, options.value.length - 1);
            break;
        case 'ArrowUp':
            event.preventDefault();
            activeIndex.value = Math.max(activeIndex.value - 1, -1);
            break;
        case 'Escape':
            open.value = false;
            activeIndex.value = -1;
            break;
        case 'Backspace': {
            const last = model.value.at(-1);

            if (query.value === '' && last !== undefined) {
                remove(last);
            }
            break;
        }
    }
}

async function fetchSuggestions(term: string): Promise<void> {
    controller?.abort();
    controller = new AbortController();

    try {
        suggestions.value = await searchTags(term, controller.signal);
        suggestionsTerm.value = term;
    } catch (error: unknown) {
        if (!isAbort(error)) {
            suggestions.value = [];
        }
    }
}

watch(query, (term) => {
    // Commas separate tags whether typed or pasted ("php, vue, testing").
    if (props.allowCreate && term.includes(',')) {
        const parts = term.split(',');
        const rest = parts.pop() ?? '';
        add(...parts);
        query.value = rest;

        return;
    }

    activeIndex.value = -1;
    open.value = true;
    clearTimeout(debounce);
    debounce = setTimeout(() => void fetchSuggestions(normalize(term)), 200);
});

function onFocus(): void {
    open.value = true;

    if (suggestions.value.length === 0) {
        void fetchSuggestions('');
    }
}

onBeforeUnmount(() => {
    clearTimeout(debounce);
    controller?.abort();
});
</script>

<template>
    <div class="space-y-1.5">
        <label :for="id" class="block text-sm font-medium">{{ label }}</label>
        <div class="relative">
            <div
                class="flex min-h-11 flex-wrap items-center gap-1.5 rounded-lg border bg-card px-2 py-1.5 transition focus-within:border-ink focus-within:ring-2 focus-within:ring-signal/25"
                :class="error ? 'border-rejected' : 'border-rule'"
            >
                <span
                    v-for="tag in model"
                    :key="tag"
                    class="inline-flex items-center gap-1 rounded-full bg-ink py-0.5 pr-1 pl-2.5 text-xs font-medium text-card"
                >
                    {{ tag }}
                    <button
                        type="button"
                        class="grid size-4 place-items-center rounded-full text-card/70 hover:bg-card/20 hover:text-card"
                        :aria-label="`Remove ${tag}`"
                        @click="remove(tag)"
                    >
                        ×
                    </button>
                </span>
                <input
                    :id="id"
                    v-model="query"
                    type="text"
                    role="combobox"
                    autocomplete="off"
                    :maxlength="LIMITS.tagMax"
                    class="min-w-32 flex-1 bg-transparent px-1.5 py-1 text-[15px] placeholder:text-ink-faint focus:outline-none"
                    :placeholder="isFull ? 'Tag limit reached' : placeholder"
                    :disabled="isFull"
                    :aria-expanded="open && options.length > 0"
                    :aria-controls="listboxId"
                    :aria-activedescendant="activeIndex >= 0 ? optionId(activeIndex) : undefined"
                    aria-autocomplete="list"
                    :aria-invalid="!!error"
                    :aria-describedby="error || hint ? `${id}-help` : undefined"
                    @keydown="onKeydown"
                    @focus="onFocus"
                    @blur="open = false"
                />
            </div>
            <ul
                v-show="open && options.length > 0"
                :id="listboxId"
                role="listbox"
                class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-rule bg-card py-1 shadow-lg"
            >
                <li
                    v-for="(option, index) in options"
                    :id="optionId(index)"
                    :key="option.key"
                    role="option"
                    :aria-selected="index === activeIndex"
                    class="flex cursor-pointer items-center justify-between px-3 py-2 text-sm"
                    :class="index === activeIndex ? 'bg-paper-deep' : 'hover:bg-paper'"
                    @mousedown.prevent="add(option.name)"
                >
                    <span>{{ option.name }}</span>
                    <span v-if="option.isNew" class="font-mono text-[11px] tracking-wider text-signal uppercase">New tag</span>
                </li>
            </ul>
        </div>
        <p v-if="error" :id="`${id}-help`" class="text-sm text-rejected">{{ error }}</p>
        <p v-else-if="hint" :id="`${id}-help`" class="text-xs text-ink-faint">{{ hint }}</p>
    </div>
</template>
