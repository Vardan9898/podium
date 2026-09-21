<script setup lang="ts">
import { messageOf } from '@/api/http';
import { changeStatus } from '@/api/proposals';
import { STATUS_OPTIONS } from '@/lib/format';
import { useToastStore } from '@/stores/toasts';
import type { Proposal, ProposalStatus } from '@/types/api';
import { ref } from 'vue';

const props = defineProps<{ proposal: Proposal }>();
const emit = defineEmits<{ changed: [status: ProposalStatus] }>();

const toasts = useToastStore();
const saving = ref<ProposalStatus | null>(null);

async function select(status: ProposalStatus): Promise<void> {
    if (status === props.proposal.status || saving.value) {
        return;
    }

    saving.value = status;

    try {
        const updated = await changeStatus(props.proposal.id, status);
        emit('changed', updated.status);
        toasts.push({ title: `Marked as ${status}`, tone: 'success' });
    } catch (error: unknown) {
        toasts.push({ title: 'Status not changed', body: messageOf(error), tone: 'error' });
    } finally {
        saving.value = null;
    }
}
</script>

<template>
    <div role="radiogroup" aria-label="Proposal status" class="grid grid-cols-3 gap-1 rounded-full border border-rule bg-paper p-1">
        <button
            v-for="option in STATUS_OPTIONS"
            :key="option.value"
            type="button"
            role="radio"
            :aria-checked="proposal.status === option.value"
            :disabled="saving !== null"
            class="rounded-full px-3 py-2 text-sm font-medium transition disabled:cursor-wait"
            :class="proposal.status === option.value ? 'bg-ink text-card shadow' : 'text-ink-soft hover:bg-card hover:text-ink'"
            @click="select(option.value)"
        >
            {{ saving === option.value ? '…' : option.label }}
        </button>
    </div>
</template>
