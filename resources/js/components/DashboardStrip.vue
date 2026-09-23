<script setup lang="ts">
import { useCan } from '@/composables/useCan';
import { statusLabel } from '@/lib/format';
import { Permission, ProposalStatus, type ProposalSummary } from '@/types/api';
import { computed } from 'vue';

const props = defineProps<{ summary: ProposalSummary | null; status: ProposalStatus | ''; awaitingReview: boolean }>();
const emit = defineEmits<{ status: [status: ProposalStatus | '']; awaiting: [value: boolean] }>();

const canReview = useCan(Permission.ReviewProposals);
const seesEverything = useCan(Permission.ViewAnyProposals);

const accents: Record<ProposalStatus, string> = {
    [ProposalStatus.Pending]: 'text-pending',
    [ProposalStatus.Approved]: 'text-approved',
    [ProposalStatus.Rejected]: 'text-rejected',
};

/** Speakers read this as "my talks"; reviewers and admins as "the programme". */
const totalLabel = computed(() => (seesEverything.value ? 'All proposals' : 'Your proposals'));
const tiles = computed(() => Object.values(ProposalStatus).map((value) => ({ value, count: props.summary?.by_status[value] ?? 0 })));

const tile = 'group relative rounded-2xl border px-4 py-3 text-left transition focus-visible:outline-2 focus-visible:outline-signal';
</script>

<template>
    <section v-if="summary" aria-label="Summary" class="grid animate-rise grid-cols-2 gap-2 sm:gap-3 lg:grid-cols-[auto_repeat(3,minmax(0,1fr))_auto]">
        <div :class="[tile, 'border-ink bg-ink text-card']">
            <p class="font-mono text-[11px] tracking-[0.15em] text-card/60 uppercase">{{ totalLabel }}</p>
            <p class="mt-1 font-display text-3xl leading-none font-semibold tabular-nums">{{ summary.total }}</p>
        </div>

        <button
            v-for="item in tiles"
            :key="item.value"
            type="button"
            :class="[tile, status === item.value ? 'border-ink bg-card shadow-sm' : 'border-rule bg-card/60 hover:border-ink/50']"
            :aria-pressed="status === item.value"
            @click="emit('status', status === item.value ? '' : item.value)"
        >
            <p class="font-mono text-[11px] tracking-[0.15em] text-ink-faint uppercase">{{ statusLabel(item.value) }}</p>
            <p class="mt-1 font-display text-3xl leading-none font-semibold tabular-nums" :class="accents[item.value]">{{ item.count }}</p>
        </button>

        <button
            v-if="canReview && summary.awaiting_my_review !== null"
            type="button"
            :class="[tile, 'col-span-2 lg:col-span-1', awaitingReview ? 'border-signal bg-signal/10' : 'border-dashed border-signal/50 bg-card/60 hover:bg-signal/5']"
            :aria-pressed="awaitingReview"
            @click="emit('awaiting', !awaitingReview)"
        >
            <p class="font-mono text-[11px] tracking-[0.15em] text-signal uppercase">Awaiting your review</p>
            <p class="mt-1 flex items-baseline gap-2">
                <span class="font-display text-3xl leading-none font-semibold text-signal tabular-nums">{{ summary.awaiting_my_review }}</span>
                <span class="text-xs text-ink-soft">{{ awaitingReview ? 'showing these' : 'show only these' }}</span>
            </p>
        </button>
    </section>
</template>
