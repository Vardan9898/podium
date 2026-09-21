<script setup lang="ts">
import { ATTACHMENT } from '@/lib/config';
import { ref, useId } from 'vue';

defineProps<{ error?: string; progress: number | null }>();
const file = defineModel<File | null>({ required: true });

const id = useId();
const localError = ref<string | null>(null);
const dragging = ref(false);
const input = ref<HTMLInputElement | null>(null);

/** UX-only pre-check; the server re-validates the actual file contents. */
function accept(candidate: File | undefined): void {
    localError.value = null;

    if (!candidate) {
        return;
    }

    if (candidate.type !== ATTACHMENT.mimeType && !candidate.name.toLowerCase().endsWith('.pdf')) {
        localError.value = 'Only PDF files are accepted.';
    } else if (candidate.size > ATTACHMENT.maxBytes) {
        localError.value = 'The file must be 4 MB or smaller.';
    } else {
        file.value = candidate;
    }
}

function clear(): void {
    file.value = null;

    if (input.value) {
        input.value.value = '';
    }
}

const size = (bytes: number): string => `${(bytes / 1024 / 1024).toFixed(bytes < 102400 ? 2 : 1)} MB`;
</script>

<template>
    <div class="space-y-1.5">
        <span :id="`${id}-label`" class="block text-sm font-medium">Slides or outline <span class="font-normal text-ink-faint">(optional)</span></span>
        <div
            v-if="!file"
            class="relative rounded-xl border-2 border-dashed px-6 py-8 text-center transition"
            :class="dragging ? 'border-signal bg-signal/5' : error || localError ? 'border-rejected/60' : 'border-rule hover:border-ink/40'"
            @dragover.prevent="dragging = true"
            @dragleave="dragging = false"
            @drop.prevent="((dragging = false), accept($event.dataTransfer?.files[0]))"
        >
            <input
                :id="id"
                ref="input"
                type="file"
                accept="application/pdf,.pdf"
                class="absolute inset-0 cursor-pointer opacity-0"
                :aria-labelledby="`${id}-label`"
                :aria-describedby="`${id}-help`"
                @change="accept(($event.target as HTMLInputElement).files?.[0])"
            />
            <p class="font-display text-lg">Drop a PDF here, or <span class="text-signal underline underline-offset-4">browse</span></p>
            <p :id="`${id}-help`" class="mt-1 font-mono text-xs text-ink-faint">PDF only · up to 4 MB</p>
        </div>
        <div v-else class="flex items-center gap-3 rounded-xl border border-rule bg-card px-4 py-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-signal/10 font-mono text-[10px] font-medium text-signal">PDF</span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium">{{ file.name }}</p>
                <p class="font-mono text-xs text-ink-faint">{{ size(file.size) }}</p>
                <div v-if="progress !== null" class="mt-2 h-1 overflow-hidden rounded-full bg-paper-deep" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100" aria-label="Upload progress">
                    <div class="h-full bg-signal transition-[width]" :style="{ width: `${progress}%` }" />
                </div>
            </div>
            <button v-if="progress === null" type="button" class="text-sm text-ink-soft hover:text-rejected" @click="clear">Remove</button>
        </div>
        <p v-if="error || localError" class="text-sm text-rejected" role="alert">{{ error ?? localError }}</p>
    </div>
</template>
