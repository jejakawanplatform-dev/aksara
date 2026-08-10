<script setup>
import { useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';

const form = useForm({
    password: '',
});

function submit() {
    form.post('/confirm-password', {
        onFinish: () => form.reset(),
    });
}
</script>

<template>
    <GuestLayout title="Konfirmasi password">
        <p class="mb-4 text-sm text-aksara-muted">
            Area aman aplikasi. Konfirmasikan password Anda sebelum melanjutkan.
        </p>

        <form class="space-y-4" @submit.prevent="submit">
            <Field label="Password" for-id="password" required :error="form.errors.password">
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="aksara-input"
                    required
                    autofocus
                    autocomplete="current-password"
                />
            </Field>

            <div class="flex justify-end pt-2">
                <Btn type="submit" :disabled="form.processing">Konfirmasi</Btn>
            </div>
        </form>
    </GuestLayout>
</template>
