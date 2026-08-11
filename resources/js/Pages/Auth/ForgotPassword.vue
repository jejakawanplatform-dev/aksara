<script setup>
import { computed, reactive, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';
import Alert from '@/Components/ui/Alert.vue';
import { emailError, resolveError, inputClass } from '@/Composables/authValidation';

defineProps({
    status: { type: String, default: null },
});

const page = usePage();
const flashStatus = computed(() => page.props.flash?.status || null);

const form = useForm({
    email: '',
});

const touched = reactive({ email: false });
const submitted = ref(false);

const local = computed(() => ({
    email: emailError(form.email),
}));

const errors = computed(() => ({
    email: resolveError(form.errors.email, local.value.email, submitted.value || touched.email),
}));

const canSubmit = computed(() => {
    if (form.processing) return false;
    if (local.value.email) return false;
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
    if (local.value.email) return;
    form.post('/forgot-password');
}
</script>

<template>
    <GuestLayout
        title="Lupa password"
        heading="Lupa password?"
        description="Masukkan email akun. Kami kirim tautan untuk membuat password baru."
    >
        <Alert v-if="status || flashStatus" tone="ok" class="mb-4">
            {{ status || flashStatus }}
        </Alert>
        <Alert v-else-if="form.errors.email && !local.email" tone="danger" class="mb-4">
            {{ form.errors.email }}
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

            <Btn type="submit" class="w-full" :disabled="!canSubmit">
                {{ form.processing ? 'Mengirim…' : 'Kirim tautan reset' }}
            </Btn>

            <p class="pt-1 text-center text-sm text-aksara-muted">
                <Link href="/login" class="font-semibold text-aksara-teal hover:underline">Kembali masuk</Link>
            </p>
        </form>
    </GuestLayout>
</template>
