import { ProposalStatus } from '@/types/api';
import { describe, expect, it } from 'vitest';
import { parseListQuery, toApiQuery, toLocationQuery } from './query';

describe('list query', () => {
    it('parses a full query string', () => {
        expect(parseListQuery({ search: 'vue', tags: ['PHP', 'Testing'], status: 'approved', page: '3' })).toEqual({
            search: 'vue',
            tags: ['PHP', 'Testing'],
            status: ProposalStatus.Approved,
            awaitingReview: false,
            page: 3,
        });
    });

    it('falls back to defaults for missing or malformed values', () => {
        expect(parseListQuery({ status: 'archived', page: '-2', tags: 'Solo' })).toEqual({ search: '', tags: ['Solo'], status: '', awaitingReview: false, page: 1 });
    });

    it('omits defaults when writing the URL, and round-trips', () => {
        const state = { search: '', tags: ['PHP'], status: '' as const, awaitingReview: false, page: 1 };

        expect(toLocationQuery(state)).toEqual({ tags: ['PHP'] });
        expect(parseListQuery(toLocationQuery(state) as never)).toEqual(state);
    });

    it('drops empty values from API requests', () => {
        expect(toApiQuery({ search: '', tags: [], status: '', awaitingReview: false, page: 2 })).toEqual({
            search: undefined,
            tags: undefined,
            status: undefined,
            awaiting_review: undefined,
            page: 2,
        });
    });

    it('round-trips the reviewer queue flag through the URL', () => {
        const state = { search: '', tags: [], status: '' as const, awaitingReview: true, page: 1 };

        expect(toLocationQuery(state)).toEqual({ awaiting_review: '1' });
        expect(parseListQuery({ awaiting_review: '1' })).toEqual(state);
        expect(parseListQuery({ awaiting_review: 'yes' }).awaitingReview).toBe(false);
        expect(toApiQuery(state).awaiting_review).toBe(1); // Laravel's boolean rule rejects "true"
    });
});
