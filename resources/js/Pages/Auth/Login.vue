<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';

defineProps({
    canResetPassword: { type: Boolean, default: true },
    status: { type: String, default: null },
});

const page = usePage();
const flashStatus = computed(() => page.props.flash?.status || null);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <GuestLayout title="Masuk">
        <div v-if="status || flashStatus" class="mb-4 text-sm font-medium text-green-600">
            {{ status || flashStatus }}
        </div>

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

            <Field label="Password" for-id="password" required :error="form.errors.password">
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="aksara-input"
                    required
                    autocomplete="current-password"
                />
            </Field>

            <label class="inline-flex items-center gap-2 text-sm text-aksara-muted">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    class="rounded border-aksara-line text-aksara-teal focus:ring-aksara-teal"
                />
                Ingat saya
            </label>

            <div class="flex items-center justify-between gap-3 pt-2">
                <Link
                    v-if="canResetPassword"
                    href="/forgot-password"
                    class="text-sm text-aksara-teal hover:underline"
                >
                    Lupa password?
                </Link>
                <span v-else />
                <Btn type="submit" :disabled="form.processing">Masuk</Btn>
            </div>

            <p class="pt-2 text-center text-sm text-aksara-muted">
                Belum punya akun?
                <Link href="/register" class="font-semibold text-aksara-teal hover:underline">Daftar</Link>
            </p>
        </form>
    </GuestLayout>
</template>
