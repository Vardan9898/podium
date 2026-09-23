<script setup lang="ts">
import { isAbort, messageOf } from '@/api/http';
import { getProposalSummary, listProposals } from '@/api/proposals';
import DashboardStrip from '@/components/DashboardStrip.vue';
import ProposalFilters, { type Filters } from '@/components/ProposalFilters.vue';
import ProposalList from '@/components/ProposalList.vue';
import AppButton from '@/components/ui/AppButton.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import PaginationNav from '@/components/ui/PaginationNav.vue';
import { useCan } from '@/composables/useCan';
import { parseListQuery, toApiQuery, toLocationQuery } from '@/lib/query';
import { useNotificationStore } from '@/stores/notifications';
import { Permission, type Paginated, type Proposal, type ProposalStatus, type ProposalSummary } from '@/types/api';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const notifications = useNotificationStore();
const canSubmit = useCan(Permission.CreateProposals);
const seesEverything = useCan(Permission.ViewAnyProposals);

const result = ref<Paginated<Proposal> | null>(null);
const summary = ref<ProposalSummary | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);
let controller: AbortController | undefined;

// The URL is the single source of truth for filters and page.
const state = computed(() => parseListQuery(route.query));
const isFiltered = computed(
    () => state.value.search !== '' || state.value.tags.length > 0 || state.value.status !== '' || state.value.awaitingReview,
);

const filters = computed<Filters>({
    get: () => ({ search: state.value.search, tags: state.value.tags, status: state.value.status }),
    set: (next) => void router.replace({ query: toLocationQuery({ ...state.value, ...next, page: 1 }) }),
});

/** The strip filters the same list, so it writes to the same URL — and like the filter bar
 *  it replaces the history entry; only paging is worth a Back step. */
function apply(change: Partial<{ status: ProposalStatus | ''; awaitingReview: boolean }>): void {
    void router.replace({ query: toLocationQuery({ ...state.value, ...change, page: 1 }) });
}

function clearFilters(): void {
    void router.replace({ query: {} });
}

function goToPage(page: number): void {
    void router.push({ query: toLocationQuery({ ...state.value, page }) });
}

async function load({ quiet = false } = {}): Promise<void> {
    controller?.abort();
    controller = new AbortController();
    const { signal } = controller;

    // A quiet refresh keeps the skeleton up if there is nothing to show yet.
    loading.value = !quiet || result.value === null;
    error.value = null;

    try {
        result.value = await listProposals(toApiQuery(state.value), signal);
    } catch (e: unknown) {
        if (!isAbort(e)) {
            error.value = messageOf(e, 'We could not load proposals.');
        }
    } finally {
        if (!signal.aborted) {
            loading.value = false;
        }
    }
}

/** Counts ignore the filters, so they load once and refresh only when something happened.
 *  Their failure degrades the strip; it must not blank the list. */
async function loadSummary(): Promise<void> {
    try {
        summary.value = await getProposalSummary();
    } catch {
        summary.value = null;
    }
}

watch(state, () => load(), { immediate: true });
void loadSummary();
// Live updates: refresh silently when anything happens to a proposal.
// Guarded: resetting the store on sign-out also changes lastActivity (to null).
watch(
    () => notifications.lastActivity,
    (activity) => {
        if (activity) {
            void load({ quiet: true });
            void loadSummary();
        }
    },
);
onBeforeUnmount(() => controller?.abort());
</script>

<template>
    <div class="space-y-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="font-mono text-xs tracking-[0.25em] text-signal uppercase">{{ seesEverything ? 'All submissions' : 'Your submissions' }}</p>
                <h1 class="mt-2 font-display text-4xl font-bold tracking-tight sm:text-5xl">Proposals</h1>
            </div>
            <p v-if="result" class="font-mono text-sm text-ink-faint">{{ result.meta.total }} {{ result.meta.total === 1 ? 'talk' : 'talks' }}</p>
        </header>

        <DashboardStrip
            :summary="summary"
            :status="state.status"
            :awaiting-review="state.awaitingReview"
            @status="apply({ status: $event })"
            @awaiting="apply({ awaitingReview: $event })"
            @show-all="clearFilters"
        />

        <ProposalFilters v-model="filters" :other-filters-active="state.awaitingReview" @reset="clearFilters" />

        <div v-if="loading" class="space-y-px overflow-hidden rounded-2xl border border-rule bg-card" aria-busy="true" aria-label="Loading proposals">
            <div v-for="n in 5" :key="n" role="status" class="animate-pulse space-y-3 px-6 py-6">
                <div class="h-3 w-16 rounded bg-paper-deep" />
                <div class="h-5 w-2/3 rounded bg-paper-deep" />
                <div class="h-3 w-1/2 rounded bg-paper-deep/70" />
            </div>
        </div>

        <EmptyState v-else-if="error" tone="error" title="Something went sideways." :body="error">
            <AppButton variant="secondary" @click="load()">Try again</AppButton>
        </EmptyState>

        <EmptyState
            v-else-if="result && result.data.length === 0"
            :title="isFiltered ? 'No proposals match these filters.' : 'No proposals yet.'"
            :body="isFiltered ? 'Try a different search or remove a filter.' : canSubmit ? 'Your first talk is one form away.' : 'New submissions will appear here — live.'"
        >
            <AppButton v-if="isFiltered" variant="secondary" @click="clearFilters">Clear filters</AppButton>
            <AppButton v-else-if="canSubmit" :to="{ name: 'proposals.create' }">Submit a proposal</AppButton>
        </EmptyState>

        <template v-else-if="result">
            <ProposalList :proposals="result.data" />
            <PaginationNav :meta="result.meta" @change="goToPage" />
        </template>
    </div>
</template>
