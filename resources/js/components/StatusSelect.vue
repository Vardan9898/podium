<script setup lang="ts">
import { messageOf } from '@/api/http';
import { changeStatus } from '@/api/proposals';
import { STATUS_OPTIONS } from '@/lib/format';
import { useToastStore } from '@/stores/toasts';
import type { Proposal, ProposalStatus } from '@/types/api';
import { ref, useId } from 'vue';

const props = defineProps<{ proposal: Proposal }>();
const emit = defineEmits<{ changed: [status: ProposalStatus] }>();

const toasts = useToastStore();
const saving = ref<ProposalStatus | null>(null);
const name = useId();

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
    <!-- Native radios: arrow keys, focus and screen-reader semantics come for free. -->
    <fieldset class="grid grid-cols-3 gap-1 rounded-full border border-rule bg-paper p-1" :disabled="saving !== null" :aria-busy="saving !== null">
        <legend class="sr-only">Proposal status</legend>
        <label v-for="option in STATUS_OPTIONS" :key="option.value" class="relative">
            <input
                type="radio"
                class="peer sr-only"
                :name="name"
                :value="option.value"
                :checked="proposal.status === option.value"
                @change="select(option.value)"
            />
            <span
                class="block cursor-pointer rounded-full px-3 py-2 text-center text-sm font-medium text-ink-soft transition peer-checked:bg-ink peer-checked:text-card peer-checked:shadow peer-focus-visible:outline-2 peer-focus-visible:outline-signal peer-disabled:cursor-wait hover:bg-card hover:text-ink peer-checked:hover:bg-ink peer-checked:hover:text-card"
            >
                {{ saving === option.value ? '…' : option.label }}
            </span>
        </label>
    </fieldset>
</template>
