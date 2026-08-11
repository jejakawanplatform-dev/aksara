<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { computed, reactive, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';
import Alert from '@/Components/ui/Alert.vue';
import PasswordInput from '@/Components/ui/PasswordInput.vue';
import {
    emailError,
    passwordError,
    resolveError,
    inputClass,
} from '@/Composables/authValidation';

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

const touched = reactive({ email: false, password: false });
const submitted = ref(false);

const local = computed(() => ({
    email: emailError(form.email),
    password: passwordError(form.password),
}));

const errors = computed(() => ({
    email: resolveError(form.errors.email, local.value.email, submitted.value || touched.email),
    password: resolveError(form.errors.password, local.value.password, submitted.value || touched.password),
}));

const canSubmit = computed(() => {
    if (form.processing) return false;
    if (local.value.email || local.value.password) return false;
    if (form.hasErrors) return false;
    return true;
});

function touch(field) {
    touched[field] = true;
    form.clearErrors(field);
}

function submit() {
    submitted.value = true;
    touched.email = true;
    touched.password = true;
    if (local.value.email || local.value.password) return;

    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <GuestLayout
        title="Masuk"
        heading="Masuk"
        description="Masuk dengan akun sekolah atau bimtek Anda."
    >
        <Alert v-if="status || flashStatus" tone="ok" class="mb-4">
            {{ status || flashStatus }}
        </Alert>
        <Alert v-else-if="form.hasErrors" tone="danger" class="mb-4">
            {{ form.errors.email || form.errors.password || 'Periksa kembali isian Anda.' }}
        </Alert>

        <form class="space-y-4" novalidate @submit.prevent="submit">
            <Field label="Email" for-id="email" required :error="errors.email">
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    :class="inputClass(!!errors.email)"
                    :aria-invalid="errors.email ? 'true' : 'false'"
                    autofocus
                    autocomplete="username"
                    @input="touch('email')"
                    @blur="touch('email')"
                />
            </Field>

            <Field label="Password" for-id="password" required :error="errors.password">
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    autocomplete="current-password"
                    :invalid="!!errors.password"
                    @input="touch('password')"
                    @blur="touch('password')"
                />
            </Field>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="inline-flex items-center gap-2 text-sm text-aksara-muted">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="rounded border-aksara-line text-aksara-teal focus:ring-aksara-teal"
                    />
                    Ingat saya
                </label>
                <Link
                    v-if="canResetPassword"
                    href="/forgot-password"
                    class="text-sm font-medium text-aksara-teal hover:underline"
                >
                    Lupa password?
                </Link>
            </div>

            <Btn type="submit" class="w-full" :disabled="!canSubmit">
                {{ form.processing ? 'Memproses…' : 'Masuk' }}
            </Btn>

            <p class="pt-1 text-center text-sm text-aksara-muted">
                Belum punya akun?
                <Link href="/register" class="font-semibold text-aksara-teal hover:underline">Daftar</Link>
            </p>
        </form>
    </GuestLayout>
</template>
