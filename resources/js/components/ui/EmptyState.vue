<script setup lang="ts">
withDefaults(defineProps<{ title: string; body?: string; tone?: 'neutral' | 'error' }>(), { body: undefined, tone: 'neutral' });
</script>

<template>
    <div class="rounded-2xl border border-dashed border-rule bg-card/60 px-6 py-14 text-center" :role="tone === 'error' ? 'alert' : undefined">
        <p class="font-display text-4xl text-ink-faint" aria-hidden="true">{{ tone === 'error' ? '⁂' : '§' }}</p>
        <h2 class="mt-3 font-display text-xl font-semibold">{{ title }}</h2>
        <p v-if="body" class="mx-auto mt-2 max-w-md text-sm text-ink-soft">{{ body }}</p>
        <div v-if="$slots.default" class="mt-6 flex justify-center gap-3"><slot /></div>
    </div>
</template>
