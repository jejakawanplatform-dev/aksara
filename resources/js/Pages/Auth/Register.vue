<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { computed, reactive, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';
import Alert from '@/Components/ui/Alert.vue';
import PasswordInput from '@/Components/ui/PasswordInput.vue';
import {
    nameError,
    emailError,
    passwordError,
    confirmationError,
    resolveError,
    inputClass,
} from '@/Composables/authValidation';

const PASSWORD_MIN = 8;

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const touched = reactive({
    name: false,
    email: false,
    password: false,
    password_confirmation: false,
});
const submitted = ref(false);

const local = computed(() => ({
    name: nameError(form.name),
    email: emailError(form.email),
    password: passwordError(form.password, { min: PASSWORD_MIN }),
    password_confirmation: confirmationError(form.password, form.password_confirmation),
}));

const errors = computed(() => ({
    name: resolveError(form.errors.name, local.value.name, submitted.value || touched.name),
    email: resolveError(form.errors.email, local.value.email, submitted.value || touched.email),
    password: resolveError(form.errors.password, local.value.password, submitted.value || touched.password),
    password_confirmation: resolveError(
        form.errors.password_confirmation,
        local.value.password_confirmation,
        submitted.value || touched.password_confirmation,
    ),
}));

const formAlert = computed(() => {
    if (form.errors.email) return form.errors.email;
    return null;
});

const canSubmit = computed(() => {
    if (form.processing) return false;
    if (Object.values(local.value).some(Boolean)) return false;
    if (form.hasErrors) return false;
    return true;
});

function touch(field) {
    touched[field] = true;
    form.clearErrors(field);
}

function submit() {
    submitted.value = true;
    Object.keys(touched).forEach((key) => {
        touched[key] = true;
    });
    if (Object.values(local.value).some(Boolean)) return;

    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <GuestLayout
        title="Daftar"
        heading="Buat akun"
        description="Daftar untuk mulai memakai ruang kerja pembelajaran."
    >
        <Alert v-if="formAlert" tone="danger" class="mb-4">
            {{ formAlert }}
        </Alert>

        <form class="space-y-4" novalidate @submit.prevent="submit">
            <Field label="Nama" for-id="name" required :error="errors.name">
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    :class="inputClass(!!errors.name)"
                    :aria-invalid="errors.name ? 'true' : 'false'"
                    autofocus
                    autocomplete="name"
                    @input="touch('name')"
                    @blur="touch('name')"
                />
            </Field>

            <Field label="Email" for-id="email" required :error="errors.email">
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    :class="inputClass(!!errors.email)"
                    :aria-invalid="errors.email ? 'true' : 'false'"
                    autocomplete="username"
                    @input="touch('email')"
                    @blur="touch('email')"
                />
            </Field>

            <Field
                label="Password"
                for-id="password"
                required
                :error="errors.password"
                :hint="!errors.password ? `Minimal ${PASSWORD_MIN} karakter.` : null"
            >
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    autocomplete="new-password"
                    :invalid="!!errors.password"
                    @input="touch('password')"
                    @blur="touch('password')"
                />
            </Field>

            <Field
                label="Konfirmasi password"
                for-id="password_confirmation"
                required
                :error="errors.password_confirmation"
            >
                <PasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    autocomplete="new-password"
                    :invalid="!!errors.password_confirmation"
                    @input="touch('password_confirmation')"
                    @blur="touch('password_confirmation')"
                />
            </Field>

            <Btn type="submit" class="w-full" :disabled="!canSubmit">
                {{ form.processing ? 'Memproses…' : 'Daftar' }}
            </Btn>

            <p class="pt-1 text-center text-sm text-aksara-muted">
                Sudah punya akun?
                <Link href="/login" class="font-semibold text-aksara-teal hover:underline">Masuk</Link>
            </p>
        </form>
    </GuestLayout>
</template>
