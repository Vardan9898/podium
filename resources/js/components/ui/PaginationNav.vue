<script setup lang="ts">
import type { PaginationMeta } from '@/types/api';
import { computed } from 'vue';

const props = defineProps<{ meta: PaginationMeta }>();
const emit = defineEmits<{ change: [page: number] }>();

/** First, last, current ±1, with gaps collapsed to null ("…"). */
const pages = computed<(number | null)[]>(() => {
    const { current_page: current, last_page: last } = props.meta;
    const wanted = [...new Set([1, current - 1, current, current + 1, last])].filter((p) => p >= 1 && p <= last).sort((a, b) => a - b);

    return wanted.flatMap((page, i) => (i > 0 && page - (wanted[i - 1] ?? page) > 1 ? [null, page] : [page]));
});

const buttonClass = 'grid h-9 min-w-9 place-items-center rounded-full px-3 text-sm transition disabled:opacity-40';
</script>

<template>
    <nav v-if="meta.last_page > 1" aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-4">
        <p class="font-mono text-xs text-ink-faint">{{ meta.from }}–{{ meta.to }} of {{ meta.total }}</p>
        <ul class="flex items-center gap-1">
            <li>
                <button :class="[buttonClass, 'hover:bg-paper-deep']" :disabled="meta.current_page === 1" @click="emit('change', meta.current_page - 1)">
                    <span aria-hidden="true">←</span><span class="sr-only">Previous page</span>
                </button>
            </li>
            <li v-for="(page, i) in pages" :key="page ?? `gap-${i}`">
                <span v-if="page === null" class="px-1 text-ink-faint" aria-hidden="true">…</span>
                <button
                    v-else
                    :class="[buttonClass, page === meta.current_page ? 'bg-ink text-card' : 'hover:bg-paper-deep']"
                    :aria-current="page === meta.current_page ? 'page' : undefined"
                    @click="emit('change', page)"
                >
                    {{ page }}
                </button>
            </li>
            <li>
                <button
                    :class="[buttonClass, 'hover:bg-paper-deep']"
                    :disabled="meta.current_page === meta.last_page"
                    @click="emit('change', meta.current_page + 1)"
                >
                    <span aria-hidden="true">→</span><span class="sr-only">Next page</span>
                </button>
            </li>
        </ul>
    </nav>
</template>
