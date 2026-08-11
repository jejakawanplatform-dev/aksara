<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { computed, reactive, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';
import Alert from '@/Components/ui/Alert.vue';
import PasswordInput from '@/Components/ui/PasswordInput.vue';
import { passwordError, resolveError } from '@/Composables/authValidation';

const form = useForm({
    password: '',
});

const touched = reactive({ password: false });
const submitted = ref(false);

const local = computed(() => ({
    password: passwordError(form.password),
}));

const errors = computed(() => ({
    password: resolveError(form.errors.password, local.value.password, submitted.value || touched.password),
}));

const canSubmit = computed(() => {
    if (form.processing) return false;
    if (local.value.password) return false;
    if (form.hasErrors) return false;
    return true;
});

function touch() {
    touched.password = true;
    form.clearErrors('password');
}

function submit() {
    submitted.value = true;
    touched.password = true;
    if (local.value.password) return;

    form.post('/confirm-password', {
        onFinish: () => form.reset(),
    });
}
</script>

<template>
    <GuestLayout
        title="Konfirmasi password"
        heading="Konfirmasi password"
        description="Masukkan password untuk melanjutkan ke area aman."
    >
        <Alert v-if="form.errors.password && !local.password" tone="danger" class="mb-4">
            {{ form.errors.password }}
        </Alert>

        <form class="space-y-4" novalidate @submit.prevent="submit">
            <Field label="Password" for-id="password" required :error="errors.password">
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    autocomplete="current-password"
                    :invalid="!!errors.password"
                    autofocus
                    @input="touch"
                    @blur="touch"
                />
            </Field>

            <Btn type="submit" class="w-full" :disabled="!canSubmit">
                {{ form.processing ? 'Memproses…' : 'Konfirmasi' }}
            </Btn>
        </form>
    </GuestLayout>
</template>
