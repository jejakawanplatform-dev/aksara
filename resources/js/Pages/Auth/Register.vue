<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <GuestLayout title="Daftar">
        <form class="space-y-4" @submit.prevent="submit">
            <Field label="Nama" for-id="name" required :error="form.errors.name">
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="aksara-input"
                    required
                    autofocus
                    autocomplete="name"
                />
            </Field>

            <Field label="Email" for-id="email" required :error="form.errors.email">
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="aksara-input"
                    required
                    autocomplete="username"
                />
            </Field>

            <Field label="Password" for-id="password" required :error="form.errors.password">
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

            <div class="flex items-center justify-between gap-3 pt-2">
                <Link href="/login" class="text-sm text-aksara-teal hover:underline">
                    Sudah punya akun?
                </Link>
                <Btn type="submit" :disabled="form.processing">Daftar</Btn>
            </div>
        </form>
    </GuestLayout>
</template>
