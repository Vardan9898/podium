<script setup lang="ts">
import RatingMeter from '@/components/RatingMeter.vue';
import StatusBadge from '@/components/ui/StatusBadge.vue';
import { formatDate, reference } from '@/lib/format';
import type { Proposal } from '@/types/api';
import { RouterLink } from 'vue-router';

defineProps<{ proposals: Proposal[] }>();
</script>

<template>
    <ol class="divide-y divide-rule overflow-hidden rounded-2xl border border-rule bg-card">
        <li
            v-for="(proposal, index) in proposals"
            :key="proposal.id"
            class="group relative grid animate-rise gap-3 px-5 py-5 transition hover:bg-paper/60 sm:px-6 md:grid-cols-[5.5rem_1fr_auto] md:gap-6"
            :style="{ animationDelay: `${Math.min(index, 10) * 35}ms` }"
        >
            <div class="flex items-center gap-3 md:block md:space-y-2">
                <p class="font-mono text-xs text-ink-faint">{{ reference(proposal.id) }}</p>
                <StatusBadge :status="proposal.status" />
            </div>

            <div class="min-w-0">
                <h2 class="font-display text-xl leading-snug font-semibold text-balance">
                    <RouterLink :to="{ name: 'proposals.show', params: { id: proposal.id } }" class="after:absolute after:inset-0 group-hover:text-signal">
                        {{ proposal.title }}
                    </RouterLink>
                </h2>
                <p class="mt-1 line-clamp-2 text-sm text-ink-soft">{{ proposal.description }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-ink-faint">
                    <span>{{ proposal.author?.name }} · {{ formatDate(proposal.created_at) }}</span>
                    <span v-if="proposal.attachment" class="inline-flex items-center gap-1">
                        <svg viewBox="0 0 16 16" class="size-3.5" fill="none" aria-hidden="true"><path d="M10.5 5 6 9.5a1.4 1.4 0 0 0 2 2L13 6.6a2.8 2.8 0 1 0-4-4L4 7.5a4.2 4.2 0 0 0 6 6l3.5-3.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" /></svg>
                        PDF
                    </span>
                    <ul v-if="proposal.tags?.length" class="flex flex-wrap gap-1.5" aria-label="Tags">
                        <li v-for="tag in proposal.tags" :key="tag.id" class="rounded-full border border-rule px-2 py-0.5 text-ink-soft">{{ tag.name }}</li>
                    </ul>
                </div>
            </div>

            <div v-if="proposal.reviews_count !== undefined" class="md:self-center">
                <RatingMeter :average="proposal.average_rating" :count="proposal.reviews_count" />
            </div>
        </li>
    </ol>
</template>
