<script setup lang="ts">
import { isAbort, messageOf, statusOf } from '@/api/http';
import { getProposal } from '@/api/proposals';
import RatingMeter from '@/components/RatingMeter.vue';
import ReviewForm from '@/components/ReviewForm.vue';
import StatusSelect from '@/components/StatusSelect.vue';
import AppButton from '@/components/ui/AppButton.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import StatusBadge from '@/components/ui/StatusBadge.vue';
import { useCan } from '@/composables/useCan';
import { formatDate, reference, timeAgo } from '@/lib/format';
import { HOME } from '@/router/guards';
import { useAuthStore } from '@/stores/auth';
import { useConfigStore } from '@/stores/config';
import { useNotificationStore } from '@/stores/notifications';
import { Permission, type Proposal, type ProposalStatus } from '@/types/api';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps<{ id: number }>();

const auth = useAuthStore();
const notifications = useNotificationStore();
const config = useConfigStore();
const canReview = useCan(Permission.ReviewProposals);
const canChangeStatus = useCan(Permission.ChangeProposalStatus);

const proposal = ref<Proposal | null>(null);
const loading = ref(true);
const error = ref<{ title: string; body: string } | null>(null);
let controller: AbortController | undefined;

const myReview = computed(() => proposal.value?.reviews?.find((review) => review.reviewer?.id === auth.user?.id) ?? null);

async function load({ quiet = false } = {}): Promise<void> {
    controller?.abort();
    controller = new AbortController();
    const { signal } = controller;
    loading.value = !quiet || proposal.value === null;
    error.value = null;

    try {
        proposal.value = await getProposal(props.id, signal);
    } catch (e: unknown) {
        if (isAbort(e)) {
            return;
        }

        error.value = {
            403: { title: 'This proposal is not yours to see.', body: 'Speakers can only open their own submissions.' },
            404: { title: 'Proposal not found.', body: 'It may have been removed.' },
        }[statusOf(e) ?? 0] ?? { title: 'We could not load this proposal.', body: messageOf(e) };
    } finally {
        if (!signal.aborted) {
            loading.value = false;
        }
    }
}

function onStatusChanged(status: ProposalStatus): void {
    if (proposal.value) {
        proposal.value = { ...proposal.value, status };
    }
}

watch(() => props.id, () => load(), { immediate: true });
watch(
    () => notifications.lastActivity,
    (activity) => {
        if (activity?.proposal_id === props.id) {
            void load({ quiet: true });
        }
    },
);
onBeforeUnmount(() => controller?.abort());
</script>

<template>
    <div v-if="loading" class="animate-pulse space-y-4" aria-busy="true" aria-label="Loading proposal">
        <div class="h-3 w-24 rounded bg-paper-deep" />
        <div class="h-12 w-3/4 rounded bg-paper-deep" />
        <div class="h-64 rounded-3xl bg-paper-deep/70" />
    </div>

    <EmptyState v-else-if="error" tone="error" :title="error.title" :body="error.body">
        <AppButton :to="HOME" variant="secondary">Back to proposals</AppButton>
    </EmptyState>

    <article v-else-if="proposal" class="grid gap-10 lg:grid-cols-[1fr_22rem]">
        <div class="min-w-0 animate-rise">
            <RouterLink :to="HOME" class="-my-2 inline-block py-2 font-mono text-xs tracking-widest text-ink-faint uppercase hover:text-signal">← All proposals</RouterLink>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <span class="font-mono text-sm text-ink-faint">{{ reference(proposal.id) }}</span>
                <StatusBadge :status="proposal.status" />
            </div>
            <h1 class="mt-3 font-display text-4xl leading-[1.05] font-bold tracking-tight text-balance sm:text-5xl">{{ proposal.title }}</h1>
            <p class="mt-4 text-sm text-ink-soft">
                by <span class="font-medium text-ink">{{ proposal.author?.name }}</span> · submitted {{ formatDate(proposal.created_at) }}
            </p>

            <ul v-if="proposal.tags?.length" class="mt-5 flex flex-wrap gap-2" aria-label="Tags">
                <li v-for="tag in proposal.tags" :key="tag.id">
                    <RouterLink
                        :to="{ name: 'proposals.index', query: { tags: [tag.name] } }"
                        class="inline-block rounded-full border border-rule bg-card px-3 py-1 text-sm hover:border-ink"
                    >
                        {{ tag.name }}
                    </RouterLink>
                </li>
            </ul>

            <div class="mt-8 rounded-3xl border border-rule bg-card p-6 sm:p-9">
                <h2 class="font-mono text-xs tracking-[0.25em] text-ink-faint uppercase">Abstract</h2>
                <p class="mt-4 text-[17px] leading-relaxed whitespace-pre-line">{{ proposal.description }}</p>

                <a
                    v-if="proposal.attachment"
                    :href="proposal.attachment.url"
                    class="mt-8 flex items-center gap-3 rounded-xl border border-rule px-4 py-3 transition hover:border-ink hover:bg-paper"
                    download
                >
                    <span class="grid size-10 place-items-center rounded-lg bg-signal/10 font-mono text-[10px] font-medium text-signal">PDF</span>
                    <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ proposal.attachment.name }}</span>
                    <span class="text-sm text-ink-soft">Download ↓</span>
                </a>
            </div>

            <section v-if="proposal.reviews" class="mt-10" aria-labelledby="reviews-heading">
                <h2 id="reviews-heading" class="font-display text-2xl font-semibold">Reviews</h2>
                <p v-if="proposal.reviews.length === 0" class="mt-3 text-sm text-ink-faint">No reviews yet.</p>
                <ul v-else class="mt-4 space-y-3">
                    <li v-for="review in proposal.reviews" :key="review.id" class="rounded-2xl border border-rule bg-card p-5">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-medium">
                                {{ review.reviewer?.name }}<span v-if="review.reviewer?.id === auth.user?.id" class="text-ink-faint"> (you)</span>
                            </p>
                            <p class="font-mono text-sm">
                                <span class="font-display text-xl font-semibold">{{ review.rating }}</span><span class="text-ink-faint">/{{ config.settings.rating.max }}</span>
                            </p>
                        </div>
                        <p class="mt-2 text-sm leading-relaxed whitespace-pre-line text-ink-soft">{{ review.comment }}</p>
                        <p class="mt-3 font-mono text-[11px] text-ink-faint">{{ timeAgo(review.updated_at) }}</p>
                    </li>
                </ul>
            </section>
        </div>

        <aside class="space-y-5 lg:sticky lg:top-24 lg:self-start">
            <div v-if="proposal.reviews_count !== undefined" class="rounded-3xl border border-rule bg-card p-6">
                <h2 class="font-mono text-xs tracking-[0.25em] text-ink-faint uppercase">Score</h2>
                <div class="mt-3"><RatingMeter :average="proposal.average_rating" :count="proposal.reviews_count" /></div>
            </div>

            <div v-if="canChangeStatus" class="rounded-3xl border border-rule bg-card p-6">
                <h2 class="font-mono text-xs tracking-[0.25em] text-ink-faint uppercase">Decision</h2>
                <div class="mt-4"><StatusSelect :proposal="proposal" @changed="onStatusChanged" /></div>
            </div>

            <div v-if="canReview" class="rounded-3xl border-2 border-ink bg-card p-6">
                <h2 class="font-display text-xl font-semibold">{{ myReview ? 'Your review' : 'Review this talk' }}</h2>
                <div class="mt-5">
                    <ReviewForm :key="myReview?.updated_at ?? 'new'" :proposal-id="proposal.id" :existing="myReview" @saved="load({ quiet: true })" />
                </div>
            </div>
        </aside>
    </article>
</template>
