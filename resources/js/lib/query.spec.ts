import { ProposalStatus } from '@/types/api';
import { describe, expect, it } from 'vitest';
import { parseListQuery, toApiQuery, toLocationQuery } from './query';

describe('list query', () => {
    it('parses a full query string', () => {
        expect(parseListQuery({ search: 'vue', tags: ['PHP', 'Testing'], status: 'approved', page: '3' })).toEqual({
            search: 'vue',
            tags: ['PHP', 'Testing'],
            status: ProposalStatus.Approved,
            page: 3,
        });
    });

    it('falls back to defaults for missing or malformed values', () => {
        expect(parseListQuery({ status: 'archived', page: '-2', tags: 'Solo' })).toEqual({ search: '', tags: ['Solo'], status: '', page: 1 });
    });

    it('omits defaults when writing the URL, and round-trips', () => {
        const state = { search: '', tags: ['PHP'], status: '' as const, page: 1 };

        expect(toLocationQuery(state)).toEqual({ tags: ['PHP'] });
        expect(parseListQuery(toLocationQuery(state) as never)).toEqual(state);
    });

    it('drops empty values from API requests', () => {
        expect(toApiQuery({ search: '', tags: [], status: '', page: 2 })).toEqual({ search: undefined, tags: undefined, status: undefined, page: 2 });
    });
});
