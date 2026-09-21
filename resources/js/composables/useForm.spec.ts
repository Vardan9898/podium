import { AxiosError, AxiosHeaders } from 'axios';
import { describe, expect, it } from 'vitest';
import { mapValidationErrors, useForm } from './useForm';

function httpError(status: number, data: unknown): AxiosError {
    return new AxiosError('Request failed', String(status), undefined, undefined, {
        status,
        data,
        statusText: '',
        headers: {},
        config: { headers: new AxiosHeaders() },
    });
}

describe('mapValidationErrors', () => {
    it('keeps the first message per field', () => {
        expect(mapValidationErrors({ title: ['Required.', 'Too short.'] })).toEqual({ title: 'Required.' });
    });

    it('surfaces array item errors on the parent field without overriding its own message', () => {
        expect(mapValidationErrors({ 'tags.1': ['Duplicate tag.'] })).toEqual({ 'tags.1': 'Duplicate tag.', tags: 'Duplicate tag.' });
        expect(mapValidationErrors({ tags: ['Too many.'], 'tags.0': ['Too short.'] })).toEqual({ tags: 'Too many.', 'tags.0': 'Too short.' });
    });

    it('ignores fields with no messages', () => {
        expect(mapValidationErrors({ title: [] })).toEqual({});
    });
});

describe('useForm', () => {
    it('resolves with the response and toggles processing', async () => {
        const form = useForm({ title: 'Hello' });
        let seenProcessing = false;

        const result = await form.submit(async (data) => {
            seenProcessing = form.processing.value;
            return data.title.toUpperCase();
        });

        expect(result).toBe('HELLO');
        expect(seenProcessing).toBe(true);
        expect(form.processing.value).toBe(false);
    });

    it('maps a 422 to field errors and resolves undefined', async () => {
        const form = useForm({ title: '' });

        const result = await form.submit(() => Promise.reject(httpError(422, { message: 'Invalid.', errors: { title: ['The title field is required.'] } })));

        expect(result).toBeUndefined();
        expect(form.errors.value).toEqual({ title: 'The title field is required.' });
        expect(form.formError.value).toBeNull();
    });

    it('turns other failures into a single form error', async () => {
        const form = useForm({});

        await form.submit(() => Promise.reject(httpError(403, { message: 'This action is unauthorized.' })));
        expect(form.formError.value).toBe('This action is unauthorized.');

        await form.submit(() => Promise.reject(new Error('network down')));
        expect(form.formError.value).toBe('Something went wrong. Please try again.');
    });

    it('clears previous errors on the next submit and can clear one field', async () => {
        const form = useForm({ a: '', b: '' });
        await form.submit(() => Promise.reject(httpError(422, { message: 'x', errors: { a: ['A'], b: ['B'] } })));

        form.clearError('a');
        expect(form.errors.value).toEqual({ b: 'B' });

        await form.submit(async () => true);
        expect(form.errors.value).toEqual({});
    });

    it('does not share state between forms created from the same initial object', () => {
        const initial = { tags: [] as string[] };
        const first = useForm(initial);
        first.data.tags.push('php');

        expect(useForm(initial).data.tags).toEqual([]);
    });
});
