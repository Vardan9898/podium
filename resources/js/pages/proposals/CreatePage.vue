<script setup lang="ts">
import { createProposal } from '@/api/proposals';
import FileDrop from '@/components/FileDrop.vue';
import TagInput from '@/components/TagInput.vue';
import AppButton from '@/components/ui/AppButton.vue';
import TextField from '@/components/ui/TextField.vue';
import { useForm } from '@/composables/useForm';
import { LIMITS } from '@/lib/config';
import { useConfigStore } from '@/stores/config';
import { useToastStore } from '@/stores/toasts';
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';

const router = useRouter();
const toasts = useToastStore();
const maxTags = useConfigStore().settings.tags_max_per_proposal;
const progress = ref<number | null>(null);

const form = useForm({ title: '', description: '', tags: [] as string[], attachment: null as File | null });

async function submit(): Promise<void> {
    progress.value = form.data.attachment ? 0 : null;

    const proposal = await form.submit((data) => createProposal(data, (percent) => (progress.value = percent)));

    progress.value = null;

    if (proposal) {
        toasts.push({ title: 'Proposal submitted', body: 'Reviewers have been notified.', tone: 'success' });
        await router.push({ name: 'proposals.show', params: { id: proposal.id } });
    }
}
</script>

<template>
    <div class="mx-auto max-w-3xl">
        <RouterLink :to="{ name: 'proposals.index' }" class="-my-2 inline-block py-2 font-mono text-xs tracking-widest text-ink-faint uppercase hover:text-signal">← All proposals</RouterLink>
        <h1 class="mt-4 font-display text-4xl font-bold tracking-tight sm:text-5xl">Submit a talk</h1>
        <p class="mt-3 text-ink-soft">Tell reviewers what you'll cover and why it matters. You can attach slides or an outline.</p>

        <form class="mt-10 space-y-7 rounded-3xl border border-rule bg-card p-6 sm:p-9" novalidate @submit.prevent="submit">
            <TextField v-model="form.data.title" label="Title" :maxlength="LIMITS.titleMax" :error="form.errors.value.title" required />
            <TextField
                v-model="form.data.description"
                label="Description"
                multiline
                :rows="8"
                :maxlength="LIMITS.descriptionMax"
                hint="Abstract, audience, key takeaways."
                :error="form.errors.value.description"
                required
            />
            <TagInput
                v-model="form.data.tags"
                label="Tags"
                :max="maxTags"
                :hint="`Pick existing tags or type a new one and press Enter. Up to ${maxTags}.`"
                :error="form.errors.value.tags"
            />
            <FileDrop v-model="form.data.attachment" :progress="progress" :error="form.errors.value.attachment" />

            <p v-if="form.formError.value" class="text-sm text-rejected" role="alert">{{ form.formError.value }}</p>

            <div class="flex flex-col-reverse gap-3 border-t border-rule pt-6 sm:flex-row sm:justify-end">
                <AppButton variant="ghost" :to="{ name: 'proposals.index' }">Cancel</AppButton>
                <AppButton type="submit" :loading="form.processing.value">Submit proposal</AppButton>
            </div>
        </form>
    </div>
</template>
