<script setup lang="ts">
import { RATING } from '@/lib/config';
import { computed } from 'vue';

const props = defineProps<{ average: number | null | undefined; count: number | undefined }>();

const percent = computed(() => ((props.average ?? 0) / RATING.max) * 100);
</script>

<template>
    <div class="flex items-center gap-3" :aria-label="average == null ? 'No reviews yet' : `Average rating ${average} out of ${RATING.max} from ${count} reviews`">
        <template v-if="average != null">
            <span class="font-display text-lg leading-none font-semibold tabular-nums">{{ average.toFixed(1) }}</span>
            <span class="relative h-1.5 w-16 overflow-hidden rounded-full bg-paper-deep" aria-hidden="true">
                <span class="absolute inset-y-0 left-0 rounded-full bg-signal" :style="{ width: `${percent}%` }" />
            </span>
            <span class="font-mono text-xs text-ink-faint">{{ count }} review{{ count === 1 ? '' : 's' }}</span>
        </template>
        <span v-else class="font-mono text-xs text-ink-faint">No reviews yet</span>
    </div>
</template>
