<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';

defineProps({
    status: { type: String, default: null },
});

const page = usePage();
const flashStatus = computed(() => page.props.flash?.status || null);

const form = useForm({
    email: '',
});

function submit() {
    form.post('/forgot-password');
}
</script>

<template>
    <GuestLayout title="Lupa password">
        <p class="mb-4 text-sm text-aksara-muted">
            Masukkan email akun Anda. Kami akan mengirim tautan untuk mengatur ulang password.
        </p>

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

            <div class="flex items-center justify-between gap-3 pt-2">
                <Link href="/login" class="text-sm text-aksara-teal hover:underline">Kembali masuk</Link>
                <Btn type="submit" :disabled="form.processing">Kirim tautan reset</Btn>
            </div>
        </form>
    </GuestLayout>
</template>
