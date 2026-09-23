import axios from 'axios';
import MockAdapter from 'axios-mock-adapter';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { http, setUnauthorizedHandler } from './http';

let mock: MockAdapter;
let csrfMock: MockAdapter;

const csrfCalls = () => csrfMock.history.get.filter((r) => r.url === '/sanctum/csrf-cookie').length;

describe('http client', () => {
    beforeEach(() => {
        mock = new MockAdapter(http);
        csrfMock = new MockAdapter(axios);
        csrfMock.onGet('/sanctum/csrf-cookie').reply(204);
    });
    afterEach(() => {
        mock.restore();
        csrfMock.restore();
    });

    it('refreshes the CSRF cookie once and replays the request after a 419', async () => {
        mock.onPost('/proposals').replyOnce(419).onPost('/proposals').replyOnce(201, { ok: true });

        const { data } = await http.post('/proposals', { title: 'x' });

        expect(data).toEqual({ ok: true });
        expect(csrfCalls()).toBe(1);
        expect(mock.history.post).toHaveLength(2);
    });

    it('gives up after one retry instead of looping', async () => {
        mock.onPost('/proposals').reply(419);

        await expect(http.post('/proposals', {})).rejects.toMatchObject({ response: { status: 419 } });
        expect(csrfCalls()).toBe(1);
        expect(mock.history.post).toHaveLength(2);
    });

    it('notifies the app once the session is gone', async () => {
        const onUnauthorized = vi.fn();
        setUnauthorizedHandler(onUnauthorized);
        mock.onGet('/me').reply(401);

        await expect(http.get('/me')).rejects.toBeTruthy();
        expect(onUnauthorized).toHaveBeenCalledOnce();
        setUnauthorizedHandler(() => {});
    });

    it('leaves other failures alone', async () => {
        const onUnauthorized = vi.fn();
        setUnauthorizedHandler(onUnauthorized);
        mock.onGet('/proposals').reply(500);

        await expect(http.get('/proposals')).rejects.toBeTruthy();
        expect(onUnauthorized).not.toHaveBeenCalled();
        expect(csrfCalls()).toBe(0);
        setUnauthorizedHandler(() => {});
    });
});
