<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Btn from '@/Components/ui/Btn.vue';
import Alert from '@/Components/ui/Alert.vue';

const props = defineProps({
    status: { type: String, default: null },
});

const page = usePage();
const flashStatus = computed(() => props.status || page.props.flash?.status || null);

const resendForm = useForm({});
const logoutForm = useForm({});

const canResend = computed(() => !resendForm.processing);

function resend() {
    resendForm.post('/email/verification-notification');
}

function logout() {
    logoutForm.post('/logout');
}
</script>

<template>
    <GuestLayout
        title="Verifikasi email"
        heading="Cek email Anda"
        description="Kami mengirim tautan verifikasi. Belum terima? Kirim ulang di bawah."
    >
        <Alert v-if="flashStatus === 'verification-link-sent'" tone="ok" class="mb-4">
            Tautan verifikasi baru telah dikirim ke email Anda.
        </Alert>

        <div class="space-y-4">
            <Btn type="button" class="w-full" :disabled="!canResend" @click="resend">
                {{ resendForm.processing ? 'Mengirim…' : 'Kirim ulang email' }}
            </Btn>
            <button
                type="button"
                class="w-full text-center text-sm font-medium text-aksara-muted transition hover:text-aksara-ink disabled:opacity-50"
                :disabled="logoutForm.processing"
                @click="logout"
            >
                {{ logoutForm.processing ? 'Keluar…' : 'Keluar' }}
            </button>
        </div>
    </GuestLayout>
</template>
