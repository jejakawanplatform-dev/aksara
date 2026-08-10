<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Btn from '@/Components/ui/Btn.vue';

const props = defineProps({
    status: { type: String, default: null },
});

const page = usePage();
const flashStatus = computed(() => props.status || page.props.flash?.status || null);

const resendForm = useForm({});
const logoutForm = useForm({});

function resend() {
    resendForm.post('/email/verification-notification');
}

function logout() {
    logoutForm.post('/logout');
}
</script>

<template>
    <GuestLayout title="Verifikasi email">
        <p class="mb-4 text-sm text-aksara-muted">
            Terima kasih sudah mendaftar. Silakan verifikasi email Anda melalui tautan yang kami
            kirim. Jika belum menerima, Anda dapat meminta email baru.
        </p>

        <div
            v-if="flashStatus === 'verification-link-sent'"
            class="mb-4 text-sm font-medium text-green-600"
        >
            Tautan verifikasi baru telah dikirim ke email Anda.
        </div>

        <div class="mt-4 flex items-center justify-between gap-3">
            <Btn type="button" :disabled="resendForm.processing" @click="resend">
                Kirim ulang email
            </Btn>
            <button
                type="button"
                class="text-sm text-aksara-muted underline hover:text-aksara-ink"
                :disabled="logoutForm.processing"
                @click="logout"
            >
                Keluar
            </button>
        </div>
    </GuestLayout>
</template>
