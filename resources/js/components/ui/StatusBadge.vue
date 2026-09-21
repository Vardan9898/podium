<script setup lang="ts">
import { statusLabel } from '@/lib/format';
import { ProposalStatus } from '@/types/api';

defineProps<{ status: ProposalStatus }>();

const tones: Record<ProposalStatus, string> = {
    [ProposalStatus.Pending]: 'text-pending border-pending/50 bg-pending/5',
    [ProposalStatus.Approved]: 'text-approved border-approved/50 bg-approved/5',
    [ProposalStatus.Rejected]: 'text-rejected border-rejected/50 bg-rejected/5',
};
</script>

<template>
    <span
        :class="tones[status]"
        class="inline-flex -rotate-1 items-center gap-1.5 rounded-sm border-[1.5px] border-dashed px-2 py-0.5 font-mono text-[11px] font-medium tracking-[0.12em] uppercase"
    >
        <span class="size-1.5 rounded-full bg-current" aria-hidden="true" />
        {{ statusLabel(status) }}
    </span>
</template>
