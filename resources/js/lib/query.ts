import type { ProposalQuery } from '@/api/proposals';
import { ProposalStatus } from '@/types/api';
import type { LocationQuery, LocationQueryRaw, LocationQueryValue } from 'vue-router';

type QueryValue = LocationQueryValue | LocationQueryValue[] | undefined;

export interface ListState {
    search: string;
    tags: string[];
    status: ProposalStatus | '';
    page: number;
}

const STATUSES: readonly string[] = Object.values(ProposalStatus);

const first = (value: QueryValue): string => (Array.isArray(value) ? (value[0] ?? '') : (value ?? ''));
const all = (value: QueryValue): string[] => (Array.isArray(value) ? value : value ? [value] : []).filter((v): v is string => !!v);

/** URL → list state. Anything malformed falls back to a sensible default instead of erroring. */
export function parseListQuery(query: LocationQuery): ListState {
    const status = first(query.status);
    const page = Number.parseInt(first(query.page), 10);

    return {
        search: first(query.search),
        tags: all(query.tags),
        status: STATUSES.includes(status) ? (status as ProposalStatus) : '',
        page: Number.isInteger(page) && page > 0 ? page : 1,
    };
}

/** List state → URL, omitting defaults so links stay short and shareable. */
export function toLocationQuery(state: ListState): LocationQueryRaw {
    return {
        ...(state.search ? { search: state.search } : {}),
        ...(state.tags.length ? { tags: state.tags } : {}),
        ...(state.status ? { status: state.status } : {}),
        ...(state.page > 1 ? { page: String(state.page) } : {}),
    };
}

export function toApiQuery(state: ListState): ProposalQuery {
    return {
        search: state.search || undefined,
        tags: state.tags.length ? state.tags : undefined,
        status: state.status || undefined,
        page: state.page,
    };
}
