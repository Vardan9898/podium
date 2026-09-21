<script setup lang="ts">
defineProps<{ eyebrow: string; title: string }>();
</script>

<template>
    <div class="mx-auto grid max-w-5xl items-center gap-10 md:grid-cols-[1.1fr_1fr] md:gap-16">
        <div class="hidden animate-rise md:block">
            <p class="font-mono text-xs tracking-[0.25em] text-signal uppercase">{{ eyebrow }}</p>
            <h1 class="mt-4 font-display text-6xl leading-[0.95] font-bold tracking-tight text-balance">{{ title }}</h1>
            <p class="mt-6 max-w-sm text-ink-soft">
                Speakers submit. Reviewers score. Admins decide. Everyone hears about it the moment it happens.
            </p>
            <div class="mt-10 flex gap-6 font-mono text-xs text-ink-faint">
                <span>① Submit</span><span>② Review</span><span>③ Decide</span>
            </div>
        </div>
        <div class="animate-rise rounded-3xl border border-rule bg-card p-6 shadow-[0_30px_80px_-40px_rgb(27_26_23/0.35)] [animation-delay:80ms] sm:p-9">
            <h1 class="mb-6 font-display text-3xl font-bold md:hidden">{{ title }}</h1>
            <slot />
        </div>
    </div>
</template>
