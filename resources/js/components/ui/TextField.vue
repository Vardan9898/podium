<script setup lang="ts">
import { computed, useId } from 'vue';

const props = withDefaults(
    defineProps<{
        label: string;
        type?: string;
        error?: string;
        hint?: string;
        multiline?: boolean;
        rows?: number;
        maxlength?: number;
        autocomplete?: string;
        required?: boolean;
    }>(),
    { type: 'text', error: undefined, hint: undefined, rows: 6, maxlength: undefined, autocomplete: undefined },
);

defineOptions({ inheritAttrs: false });

const model = defineModel<string>({ required: true });
const id = useId();
const describedBy = computed(() => (props.error ? `${id}-error` : props.hint || props.maxlength ? `${id}-hint` : undefined));

const inputClass = computed(() => [
    'w-full rounded-lg border bg-card px-3.5 py-2.5 text-[15px] shadow-[inset_0_1px_0_rgb(0_0_0/0.03)] transition',
    'placeholder:text-ink-faint focus:border-ink focus:outline-none focus-visible:outline-none focus:ring-2 focus:ring-signal/25',
    props.error ? 'border-rejected' : 'border-rule',
]);
</script>

<template>
    <div class="space-y-1.5">
        <label :for="id" class="block text-sm font-medium text-ink">
            {{ label }}<span v-if="required" class="text-signal" aria-hidden="true"> *</span>
        </label>
        <textarea
            v-if="multiline"
            :id="id"
            v-model="model"
            :rows="rows"
            :maxlength="maxlength"
            :required="required"
            :aria-invalid="!!error"
            :aria-describedby="describedBy"
            :class="[inputClass, 'resize-y leading-relaxed']"
            v-bind="$attrs"
        />
        <input
            v-else
            :id="id"
            v-model="model"
            :type="type"
            :maxlength="maxlength"
            :autocomplete="autocomplete"
            :required="required"
            :aria-invalid="!!error"
            :aria-describedby="describedBy"
            :class="inputClass"
            v-bind="$attrs"
        />
        <p v-if="error" :id="`${id}-error`" class="text-sm text-rejected">{{ error }}</p>
        <p v-else-if="hint || maxlength" :id="`${id}-hint`" class="flex justify-between gap-4 text-xs text-ink-faint">
            <span>{{ hint }}</span>
            <span v-if="maxlength" class="font-mono tabular-nums">{{ model.length }}/{{ maxlength }}</span>
        </p>
    </div>
</template>
