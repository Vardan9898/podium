<script setup lang="ts">
import AuthCard from '@/components/AuthCard.vue';
import AppButton from '@/components/ui/AppButton.vue';
import TextField from '@/components/ui/TextField.vue';
import { useForm } from '@/composables/useForm';
import { HOME, safeRedirect } from '@/router/guards';
import { useAuthStore } from '@/stores/auth';
import { RouterLink, useRoute, useRouter } from 'vue-router';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const form = useForm({ email: '', password: '', remember: false });

async function submit(): Promise<void> {
    await form.submit((data) => auth.login(data));

    if (auth.isAuthenticated) {
        await router.replace(safeRedirect(route.query.redirect) ?? HOME);
    }
}
</script>

<template>
    <AuthCard eyebrow="Welcome back" title="The stage is waiting.">
        <form class="space-y-5" novalidate @submit.prevent="submit">
            <TextField v-model="form.data.email" label="Email" type="email" autocomplete="email" :error="form.errors.value.email" required autofocus />
            <TextField v-model="form.data.password" label="Password" type="password" autocomplete="current-password" :error="form.errors.value.password" required />
            <label class="flex items-center gap-2 text-sm text-ink-soft">
                <input v-model="form.data.remember" type="checkbox" class="size-4 rounded border-rule accent-ink" />
                Keep me signed in
            </label>
            <p v-if="form.formError.value" class="text-sm text-rejected" role="alert">{{ form.formError.value }}</p>
            <AppButton type="submit" :loading="form.processing.value" block>Sign in</AppButton>
        </form>
        <p class="mt-6 text-center text-sm text-ink-soft">
            New here?
            <RouterLink :to="{ name: 'register' }" class="font-medium text-ink underline decoration-signal decoration-2 underline-offset-4">Create an account</RouterLink>
        </p>
        <details class="mt-6 rounded-xl bg-paper px-4 py-3 text-sm text-ink-soft">
            <summary class="cursor-pointer font-medium text-ink">Demo accounts</summary>
            <p class="mt-2 font-mono text-xs leading-6">speaker@ · reviewer@ · admin@example.com<br />password: <strong>password</strong></p>
        </details>
    </AuthCard>
</template>
