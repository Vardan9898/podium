<script setup lang="ts">
import AuthCard from '@/components/AuthCard.vue';
import AppButton from '@/components/ui/AppButton.vue';
import TextField from '@/components/ui/TextField.vue';
import { useForm } from '@/composables/useForm';
import { HOME } from '@/router/guards';
import { useAuthStore } from '@/stores/auth';
import { Role } from '@/types/api';
import { RouterLink, useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();

const roles = [
    { value: Role.Speaker, title: 'Speaker', description: 'Submit talks and follow their status.' },
    { value: Role.Reviewer, title: 'Reviewer', description: 'Read every proposal, rate and comment.' },
    { value: Role.Admin, title: 'Admin', description: 'Approve or reject proposals.' },
] as const;

const form = useForm({ name: '', email: '', password: '', password_confirmation: '', role: Role.Speaker as Role });

async function submit(): Promise<void> {
    await form.submit((data) => auth.register(data));

    if (auth.isAuthenticated) {
        await router.replace(HOME);
    }
}
</script>

<template>
    <AuthCard eyebrow="Join the programme" title="Every great talk starts as a proposal.">
        <form class="space-y-5" novalidate @submit.prevent="submit">
            <fieldset>
                <legend class="text-sm font-medium">I am joining as</legend>
                <div class="mt-2 grid gap-2 sm:grid-cols-3">
                    <label v-for="role in roles" :key="role.value" class="relative cursor-pointer">
                        <input v-model="form.data.role" type="radio" name="role" :value="role.value" class="peer sr-only" />
                        <span class="block h-full rounded-xl border border-rule p-3 transition peer-checked:border-ink peer-checked:bg-ink peer-checked:text-card peer-focus-visible:outline-2 peer-focus-visible:outline-signal hover:border-ink">
                            <span class="block font-medium">{{ role.title }}</span>
                            <span class="mt-1 block text-xs opacity-70">{{ role.description }}</span>
                        </span>
                    </label>
                </div>
                <p v-if="form.errors.value.role" class="mt-1.5 text-sm text-rejected">{{ form.errors.value.role }}</p>
            </fieldset>
            <TextField v-model="form.data.name" label="Full name" autocomplete="name" :error="form.errors.value.name" required />
            <TextField v-model="form.data.email" label="Email" type="email" autocomplete="email" :error="form.errors.value.email" required />
            <div class="grid gap-5 sm:grid-cols-2">
                <TextField v-model="form.data.password" label="Password" type="password" autocomplete="new-password" :error="form.errors.value.password" required />
                <TextField v-model="form.data.password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />
            </div>
            <p v-if="form.formError.value" class="text-sm text-rejected" role="alert">{{ form.formError.value }}</p>
            <AppButton type="submit" :loading="form.processing.value" block>Create account</AppButton>
        </form>
        <p class="mt-6 text-center text-sm text-ink-soft">
            Already registered?
            <RouterLink :to="{ name: 'login' }" class="font-medium text-ink underline decoration-signal decoration-2 underline-offset-4">Sign in</RouterLink>
        </p>
    </AuthCard>
</template>
