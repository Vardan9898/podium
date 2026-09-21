<script setup lang="ts" generic="T extends string">
import { useId } from 'vue';

defineProps<{
    label: string;
    options: ReadonlyArray<{ value: T; label: string }>;
    placeholder?: string;
    hideLabel?: boolean;
}>();

const model = defineModel<T | ''>({ required: true });
const id = useId();
</script>

<template>
    <div class="space-y-1.5">
        <label :for="id" :class="hideLabel ? 'sr-only' : 'block text-sm font-medium'">{{ label }}</label>
        <select
            :id="id"
            v-model="model"
            class="w-full cursor-pointer appearance-none rounded-lg border border-rule bg-card bg-[length:12px] bg-[right_0.9rem_center] bg-no-repeat px-3.5 py-2.5 pr-9 text-[15px] focus:border-ink focus:outline-none focus:ring-2 focus:ring-signal/25"
            style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%231b1a17' stroke-width='1.6' fill='none'/%3E%3C/svg%3E&quot;)"
        >
            <option v-if="placeholder !== undefined" value="">{{ placeholder }}</option>
            <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
    </div>
</template>
