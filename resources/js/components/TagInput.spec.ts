import { flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';
import TagInput from './TagInput.vue';

vi.mock('@/api/tags', () => ({
    searchTags: vi.fn(async (term: string) =>
        [
            { id: 1, name: 'Laravel', slug: 'laravel' },
            { id: 2, name: 'Vue.js', slug: 'vue-js' },
        ].filter((tag) => tag.name.toLowerCase().includes(term.toLowerCase())),
    ),
}));

function mountInput(props: Record<string, unknown> = {}) {
    const model = ref<string[]>([]);
    const wrapper = mount(TagInput, {
        props: {
            label: 'Tags',
            modelValue: model.value,
            'onUpdate:modelValue': (value: string[]) => {
                model.value = value;
                void wrapper.setProps({ modelValue: value });
            },
            ...props,
        },
    });

    return { wrapper, model };
}

async function type(wrapper: VueWrapper, text: string): Promise<void> {
    await wrapper.find('input').setValue(text);
    vi.advanceTimersByTime(250);
    await flushPromises();
}

describe('TagInput', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    it('creates a new tag on Enter', async () => {
        const { wrapper, model } = mountInput();
        await type(wrapper, '  Web   Performance ');
        await wrapper.find('input').trigger('keydown', { key: 'Enter' });

        expect(model.value).toEqual(['Web Performance']);
        expect((wrapper.find('input').element as HTMLInputElement).value).toBe('');
    });

    it('splits typed or pasted text on commas', async () => {
        const { wrapper, model } = mountInput();
        await type(wrapper, 'php, testing, ci');
        await nextTick();

        expect(model.value).toEqual(['php', 'testing']);
        expect((wrapper.find('input').element as HTMLInputElement).value).toBe(' ci');
    });

    it('de-duplicates case-insensitively', async () => {
        const { wrapper, model } = mountInput({ modelValue: ['Laravel'] });
        model.value = ['Laravel'];
        await type(wrapper, 'LARAVEL');
        await wrapper.find('input').trigger('keydown', { key: 'Enter' });

        expect(model.value).toEqual(['Laravel']);
    });

    it('offers matching existing tags and picks one with the keyboard', async () => {
        const { wrapper, model } = mountInput();
        await type(wrapper, 'vu');

        const options = wrapper.findAll('[role=option]');
        expect(options.map((o) => o.text())).toEqual(['Vue.js', 'vuNew tag']);

        await wrapper.find('input').trigger('keydown', { key: 'ArrowDown' });
        await wrapper.find('input').trigger('keydown', { key: 'Enter' });

        expect(model.value).toEqual(['Vue.js']);
    });

    it('only allows existing tags when creation is disabled', async () => {
        const { wrapper, model } = mountInput({ allowCreate: false });
        await type(wrapper, 'Something new');
        await wrapper.find('input').trigger('keydown', { key: 'Enter' });
        expect(model.value).toEqual([]);

        await type(wrapper, 'lara');
        await wrapper.find('input').trigger('keydown', { key: 'Enter' });
        expect(model.value).toEqual(['Laravel']);
    });

    it('removes chips with the button and with Backspace on an empty input', async () => {
        const { wrapper, model } = mountInput({ modelValue: ['A1', 'B2', 'C3'] });
        model.value = ['A1', 'B2', 'C3'];

        await wrapper.find('[aria-label="Remove B2"]').trigger('click');
        expect(model.value).toEqual(['A1', 'C3']);

        await wrapper.find('input').trigger('keydown', { key: 'Backspace' });
        expect(model.value).toEqual(['A1']);
    });

    it('respects the maximum number of tags', async () => {
        const { wrapper, model } = mountInput({ max: 2, modelValue: ['A1', 'B2'] });
        model.value = ['A1', 'B2'];

        expect(wrapper.find('input').attributes('disabled')).toBeDefined();
    });
});
