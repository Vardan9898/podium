<script setup lang="ts">
import { saveReview } from '@/api/proposals';
import AppButton from '@/components/ui/AppButton.vue';
import TextField from '@/components/ui/TextField.vue';
import { useForm } from '@/composables/useForm';
import { LIMITS, RATING } from '@/lib/config';
import { useToastStore } from '@/stores/toasts';
import type { Review } from '@/types/api';
import { useId } from 'vue';

const props = defineProps<{ proposalId: number; existing: Review | null }>();
const emit = defineEmits<{ saved: [review: Review] }>();

const toasts = useToastStore();
const form = useForm({ rating: (props.existing?.rating ?? null) as number | null, comment: props.existing?.comment ?? '' });
const scale = Array.from({ length: RATING.max - RATING.min + 1 }, (_, i) => RATING.min + i);
const groupId = useId();

async function submit(): Promise<void> {
    const review = await form.submit((data) => saveReview(props.proposalId, data));

    if (review) {
        toasts.push({ title: props.existing ? 'Review updated' : 'Review submitted', tone: 'success' });
        emit('saved', review);
    }
}
</script>

<template>
    <form class="space-y-5" novalidate @submit.prevent="submit">
        <fieldset :aria-describedby="form.errors.value.rating ? `${groupId}-error` : undefined">
            <legend class="text-sm font-medium">Rating <span class="text-ink-faint">({{ RATING.min }}–{{ RATING.max }})</span></legend>
            <div class="mt-2 grid grid-cols-5 gap-1.5 sm:grid-cols-10">
                <label v-for="value in scale" :key="value" class="relative">
                    <input v-model="form.data.rating" type="radio" :name="groupId" :value="value" class="peer sr-only" @change="form.clearError('rating')" />
                    <span
                        class="grid h-10 cursor-pointer place-items-center rounded-lg border border-rule bg-card font-mono text-sm transition peer-checked:border-ink peer-checked:bg-ink peer-checked:text-card peer-focus-visible:outline-2 peer-focus-visible:outline-signal hover:border-ink"
                    >
                        {{ value }}
                    </span>
                </label>
            </div>
            <p v-if="form.errors.value.rating" :id="`${groupId}-error`" class="mt-1.5 text-sm text-rejected">{{ form.errors.value.rating }}</p>
        </fieldset>

        <TextField
            v-model="form.data.comment"
            label="Comment"
            multiline
            :rows="4"
            :maxlength="LIMITS.commentMax"
            :error="form.errors.value.comment"
            required
        />

        <p v-if="form.formError.value" class="text-sm text-rejected" role="alert">{{ form.formError.value }}</p>

        <AppButton type="submit" :loading="form.processing.value" block>
            {{ existing ? 'Update my review' : 'Submit review' }}
        </AppButton>
    </form>
</template>
