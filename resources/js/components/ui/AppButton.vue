<script setup lang="ts">
import { computed } from 'vue';
import { RouterLink, type RouteLocationRaw } from 'vue-router';

const props = withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'ghost';
        type?: 'button' | 'submit';
        to?: RouteLocationRaw;
        loading?: boolean;
        disabled?: boolean;
        block?: boolean;
    }>(),
    { variant: 'primary', type: 'button', to: undefined, loading: false, disabled: false, block: false },
);

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-full px-5 py-2.5 text-sm font-medium transition',
    'disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-offset-2',
    props.block && 'w-full',
    {
        primary: 'bg-ink text-card hover:bg-signal active:translate-y-px',
        secondary: 'border border-ink/80 bg-card text-ink hover:bg-paper-deep',
        ghost: 'text-ink-soft hover:bg-paper-deep hover:text-ink',
    }[props.variant],
]);
</script>

<template>
    <RouterLink v-if="to" :to="to" :class="classes"><slot /></RouterLink>
    <button v-else :type="type" :class="classes" :disabled="disabled || loading" :aria-busy="loading">
        <span
            v-if="loading"
            class="size-3.5 animate-spin rounded-full border-2 border-current border-r-transparent"
            aria-hidden="true"
        />
        <slot />
    </button>
</template>
