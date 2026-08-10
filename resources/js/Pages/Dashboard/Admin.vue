<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Btn from '@/Components/ui/Btn.vue';

defineProps({
    activeYear: { type: String, default: null },
    rombelCount: { type: Number, default: 0 },
    counts: {
        type: Object,
        default: () => ({
            admin: 0,
            teacher: 0,
            homeroom: 0,
            student: 0,
            parent: 0,
        }),
    },
    urls: { type: Object, required: true },
});

const roleLabels = [
    { key: 'admin', label: 'Administrator' },
    { key: 'teacher', label: 'Guru' },
    { key: 'homeroom', label: 'Wali Kelas' },
    { key: 'student', label: 'Siswa' },
    { key: 'parent', label: 'Wali Murid' },
];
</script>

<template>
    <AppLayout title="Dashboard Administrator">
        <template #header>Dashboard Administrator</template>

        <div class="space-y-6">
            <Card>
                <p class="text-sm text-aksara-muted">Tahun ajaran aktif</p>
                <p class="mt-1 font-display text-xl font-semibold text-aksara-ink">
                    {{ activeYear || '—' }}
                </p>
                <p class="mt-1 text-sm text-aksara-muted">{{ rombelCount }} rombel terdaftar</p>
            </Card>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
                <Card v-for="role in roleLabels" :key="role.key">
                    <p class="text-xs font-semibold uppercase tracking-wide text-aksara-muted">
                        {{ role.label }}
                    </p>
                    <p class="mt-2 font-display text-2xl font-semibold text-aksara-ink">
                        {{ counts[role.key] ?? 0 }}
                    </p>
                </Card>
            </div>

            <div class="flex flex-wrap gap-3">
                <Btn :href="urls.users">Kelola pengguna</Btn>
                <Btn :href="urls.access" variant="secondary">Hak akses</Btn>
                <Btn :href="urls.references" variant="secondary">Lihat referensi</Btn>
            </div>
        </div>
    </AppLayout>
</template>
