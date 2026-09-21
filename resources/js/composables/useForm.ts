import { messageOf, statusOf } from '@/api/http';
import type { ValidationErrorBody } from '@/types/api';
import { AxiosError } from 'axios';
import { reactive, ref, type Ref, type UnwrapRef } from 'vue';

export type FieldErrors = Record<string, string>;

/**
 * Laravel 422 → one message per field. Array item errors ("tags.2") are kept under their own
 * key and also surfaced on the parent field ("tags") so a single control can display them.
 */
export function mapValidationErrors(errors: ValidationErrorBody['errors']): FieldErrors {
    const mapped: FieldErrors = {};

    for (const [key, messages] of Object.entries(errors)) {
        const message = messages[0];

        if (message === undefined) {
            continue;
        }

        mapped[key] = message;

        const parent = key.split('.')[0];

        if (parent !== undefined && parent !== key) {
            mapped[parent] ??= message;
        }
    }

    return mapped;
}

export interface Form<T extends object> {
    data: UnwrapRef<T>;
    errors: Ref<FieldErrors>;
    formError: Ref<string | null>;
    processing: Ref<boolean>;
    submit<R>(request: (data: UnwrapRef<T>) => Promise<R>): Promise<R | undefined>;
    clearError(field: string): void;
}

/**
 * Loading state + error mapping for any request. Resolves with the response, or undefined when
 * the request failed (field errors on 422, a single `formError` otherwise).
 */
export function useForm<T extends object>(initial: T): Form<T> {
    const data = reactive(structuredClone(initial)) as UnwrapRef<T>;
    const errors = ref<FieldErrors>({});
    const formError = ref<string | null>(null);
    const processing = ref(false);

    async function submit<R>(request: (payload: UnwrapRef<T>) => Promise<R>): Promise<R | undefined> {
        processing.value = true;
        errors.value = {};
        formError.value = null;

        try {
            return await request(data);
        } catch (error: unknown) {
            if (statusOf(error) === 422 && error instanceof AxiosError) {
                const body = error.response?.data as ValidationErrorBody;
                errors.value = mapValidationErrors(body.errors ?? {});
                formError.value = Object.keys(errors.value).length === 0 ? body.message : null;
            } else {
                formError.value = messageOf(error);
            }

            return undefined;
        } finally {
            processing.value = false;
        }
    }

    function clearError(field: string): void {
        const { [field]: _removed, ...rest } = errors.value;
        errors.value = rest;
    }

    return { data, errors, formError, processing, submit, clearError };
}
