<script setup>
import { useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';

const props = defineProps({
    email: { type: String, default: '' },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <GuestLayout title="Reset password">
        <form class="space-y-4" @submit.prevent="submit">
            <Field label="Email" for-id="email" required :error="form.errors.email">
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="aksara-input"
                    required
                    autofocus
                    autocomplete="username"
                />
            </Field>

            <Field label="Password baru" for-id="password" required :error="form.errors.password">
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="aksara-input"
                    required
                    autocomplete="new-password"
                />
            </Field>

            <Field
                label="Konfirmasi password"
                for-id="password_confirmation"
                required
                :error="form.errors.password_confirmation"
            >
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="aksara-input"
                    required
                    autocomplete="new-password"
                />
            </Field>

            <div class="flex justify-end pt-2">
                <Btn type="submit" :disabled="form.processing">Reset password</Btn>
            </div>
        </form>
    </GuestLayout>
</template>
