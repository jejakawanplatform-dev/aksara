<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';
import Alert from '@/Components/ui/Alert.vue';
import Modal from '@/Components/ui/Modal.vue';

const props = defineProps({
    mustVerifyEmail: { type: Boolean, default: false },
    status: { type: String, default: null },
    user: { type: Object, required: true },
});

const page = usePage();
const flashStatus = computed(() => props.status || page.props.flash?.status || null);

const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const deleteForm = useForm({
    password: '',
});

const showDeleteConfirm = ref(false);

function updateProfile() {
    profileForm.patch('/profile', { preserveScroll: true });
}

function updatePassword() {
    passwordForm.put('/password', {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

function resendVerification() {
    profileForm.post('/email/verification-notification', { preserveScroll: true });
}

function destroyAccount() {
    deleteForm.delete('/profile', {
        errorBag: 'userDeletion',
        preserveScroll: true,
        onError: () => {
            showDeleteConfirm.value = true;
        },
        onFinish: () => deleteForm.reset(),
    });
}
</script>

<template>
    <AppLayout title="Profil">
        <template #header>Profil</template>

        <div class="mx-auto max-w-3xl space-y-5">
            <PageHeader
                title="Profil"
                description="Perbarui informasi akun, password, dan preferensi keamanan Anda."
            />

            <Card title="Informasi profil" description="Perbarui nama dan alamat email akun Anda.">
                <form class="space-y-4" @submit.prevent="updateProfile">
                    <Field label="Nama" for-id="name" required :error="profileForm.errors.name">
                        <input
                            id="name"
                            v-model="profileForm.name"
                            type="text"
                            class="aksara-input"
                            required
                            autofocus
                            autocomplete="name"
                        />
                    </Field>

                    <Field label="Email" for-id="email" required :error="profileForm.errors.email">
                        <input
                            id="email"
                            v-model="profileForm.email"
                            type="email"
                            class="aksara-input"
                            required
                            autocomplete="username"
                        />
                    </Field>

                    <Alert v-if="mustVerifyEmail && !user.email_verified_at" tone="warn">
                        <p>Email Anda belum diverifikasi.</p>
                        <button
                            type="button"
                            class="mt-2 font-semibold text-aksara-teal hover:underline"
                            @click="resendVerification"
                        >
                            Kirim ulang email verifikasi
                        </button>
                        <p v-if="flashStatus === 'verification-link-sent'" class="mt-2 font-medium text-aksara-ok">
                            Tautan verifikasi baru telah dikirim.
                        </p>
                    </Alert>

                    <div class="flex items-center justify-end gap-3">
                        <p
                            v-if="flashStatus === 'profile-updated'"
                            class="text-sm text-aksara-muted"
                        >
                            Tersimpan.
                        </p>
                        <Btn type="submit" size="sm" :disabled="profileForm.processing">Simpan</Btn>
                    </div>
                </form>
            </Card>

            <Card
                title="Ubah password"
                description="Gunakan password yang panjang dan acak agar akun tetap aman."
            >
                <form class="space-y-4" @submit.prevent="updatePassword">
                    <Field
                        label="Password saat ini"
                        for-id="current_password"
                        required
                        :error="passwordForm.errors.current_password"
                    >
                        <input
                            id="current_password"
                            v-model="passwordForm.current_password"
                            type="password"
                            class="aksara-input"
                            autocomplete="current-password"
                        />
                    </Field>

                    <Field
                        label="Password baru"
                        for-id="password"
                        required
                        :error="passwordForm.errors.password"
                    >
                        <input
                            id="password"
                            v-model="passwordForm.password"
                            type="password"
                            class="aksara-input"
                            autocomplete="new-password"
                        />
                    </Field>

                    <Field
                        label="Konfirmasi password"
                        for-id="password_confirmation"
                        required
                        :error="passwordForm.errors.password_confirmation"
                    >
                        <input
                            id="password_confirmation"
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            class="aksara-input"
                            autocomplete="new-password"
                        />
                    </Field>

                    <div class="flex items-center justify-end gap-3">
                        <p
                            v-if="flashStatus === 'password-updated'"
                            class="text-sm text-aksara-muted"
                        >
                            Tersimpan.
                        </p>
                        <Btn type="submit" size="sm" :disabled="passwordForm.processing">Simpan</Btn>
                    </div>
                </form>
            </Card>

            <Card
                title="Hapus akun"
                description="Setelah dihapus, semua data akun akan hilang secara permanen."
            >
                <Btn
                    type="button"
                    variant="danger"
                    size="sm"
                    @click="showDeleteConfirm = true"
                >
                    Hapus akun
                </Btn>

                <Modal
                    :open="showDeleteConfirm"
                    title="Yakin ingin menghapus akun?"
                    description="Masukkan password untuk mengonfirmasi penghapusan permanen."
                    max-width="md"
                    @close="showDeleteConfirm = false"
                >
                    <form class="space-y-4" @submit.prevent="destroyAccount">
                        <Field
                            label="Password"
                            for-id="delete_password"
                            :error="deleteForm.errors.password"
                        >
                            <input
                                id="delete_password"
                                v-model="deleteForm.password"
                                type="password"
                                class="aksara-input"
                                placeholder="Password"
                            />
                        </Field>

                        <div class="flex justify-end gap-2">
                            <Btn
                                type="button"
                                variant="secondary"
                                size="sm"
                                @click="showDeleteConfirm = false"
                            >
                                Batal
                            </Btn>
                            <Btn
                                type="submit"
                                variant="danger"
                                size="sm"
                                :disabled="deleteForm.processing"
                            >
                                Hapus akun
                            </Btn>
                        </div>
                    </form>
                </Modal>
            </Card>
        </div>
    </AppLayout>
</template>
