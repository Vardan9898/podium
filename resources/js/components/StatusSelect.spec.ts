import type { Proposal } from '@/types/api';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import StatusSelect from './StatusSelect.vue';

const changeStatus = vi.fn();
vi.mock('@/api/proposals', () => ({ changeStatus: (...args: unknown[]) => changeStatus(...args) }));

const proposal = { id: 7, status: 'pending' } as Proposal;

const radios = (w: ReturnType<typeof mount>) =>
    w.findAll('input[type=radio]').map((r) => ({ value: r.attributes('value'), checked: (r.element as HTMLInputElement).checked }));

describe('StatusSelect', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('keeps showing the real status when the request fails', async () => {
        changeStatus.mockRejectedValueOnce(new Error('nope'));
        const wrapper = mount(StatusSelect, { props: { proposal } });

        await wrapper.findAll('input[type=radio]')[1]!.setValue(true);
        await flushPromises();

        expect(wrapper.emitted('changed')).toBeUndefined();
        expect(radios(wrapper)).toEqual([
            { value: 'pending', checked: true },
            { value: 'approved', checked: false },
            { value: 'rejected', checked: false },
        ]);
    });
});
