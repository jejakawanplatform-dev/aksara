<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    userName: { type: String, default: 'Admin' },
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
    content: {
        type: Object,
        default: () => ({
            plansTotal: 0,
            plansPublished: 0,
            materialsPublished: 0,
            aiToday: 0,
        }),
    },
    urls: { type: Object, required: true },
});

/** Palet enterprise (teal / slate / semantic) — tanpa ungu */
const roleMeta = [
    { key: 'admin', label: 'Administrator', color: '#0f1c24' },
    { key: 'teacher', label: 'Guru', color: '#0f766e' },
    { key: 'homeroom', label: 'Wali Kelas', color: '#0369a1' },
    { key: 'student', label: 'Siswa', color: '#64748b' },
    { key: 'parent', label: 'Wali Murid', color: '#b45309' },
];

const totalUsers = computed(() =>
    roleMeta.reduce((sum, role) => sum + (Number(props.counts[role.key]) || 0), 0),
);

const roleSlices = computed(() => {
    const total = totalUsers.value || 1;
    let offset = 0;
    return roleMeta.map((role) => {
        const value = Number(props.counts[role.key]) || 0;
        const pct = (value / total) * 100;
        const slice = { ...role, value, pct, offset };
        offset += pct;
        return slice;
    });
});

const plansDraft = computed(() =>
    Math.max(0, (Number(props.content.plansTotal) || 0) - (Number(props.content.plansPublished) || 0)),
);

const contentBars = computed(() => {
    const published = Number(props.content.plansPublished) || 0;
    const draft = plansDraft.value;
    const materials = Number(props.content.materialsPublished) || 0;
    const ai = Number(props.content.aiToday) || 0;
    const max = Math.max(published + draft, materials, ai, 1);

    return [
        {
            key: 'plans',
            label: 'Modul ajar',
            segments: [
                { label: 'Terbit', value: published, color: '#0f766e', width: (published / max) * 100 },
                { label: 'Draf', value: draft, color: '#94a3b8', width: (draft / max) * 100 },
            ],
            caption: `${published} terbit · ${draft} draf`,
        },
        {
            key: 'materials',
            label: 'Materi terbit',
            segments: [
                { label: 'Terbit', value: materials, color: '#0369a1', width: (materials / max) * 100 },
            ],
            caption: `${materials} siap siswa`,
        },
        {
            key: 'ai',
            label: 'AI hari ini',
            segments: [
                { label: 'Generasi', value: ai, color: '#115e59', width: (ai / max) * 100 },
            ],
            caption: `${ai} generasi (semua guru)`,
        },
    ];
});

const shortcuts = computed(() => [
    { href: props.urls.users, title: 'Pengguna', body: 'Kelola akun & role' },
    { href: props.urls.access, title: 'Hak akses', body: 'Matrix permission' },
    { href: props.urls.references, title: 'Referensi', body: 'Kurikulum & rombel' },
    { href: props.urls.settings, title: 'Pengaturan', body: 'Sistem & AI' },
]);

/** Donut geometry */
const R = 40;
const C = 2 * Math.PI * R;
</script>

<template>
    <AppLayout title="Dashboard Administrator">
        <template #header>
            <div class="flex w-full items-center justify-between gap-3">
                <span>Dashboard Administrator</span>
                <span class="text-xs font-medium text-aksara-muted">Peran: Administrator</span>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Hero -->
            <div
                class="rounded-xl border border-aksara-line border-l-4 border-l-aksara-teal bg-white p-5 shadow-sm"
            >
                <div class="flex flex-col justify-between gap-5 md:flex-row md:items-center">
                    <div class="max-w-2xl space-y-1">
                        <h3 class="text-xl font-bold tracking-tight text-aksara-ink">
                            Halo, {{ userName }}
                        </h3>
                        <p class="text-sm leading-relaxed text-aksara-muted">
                            Tahun ajaran
                            <span class="font-semibold text-aksara-ink">{{ activeYear || '—' }}</span>
                            · {{ rombelCount }} rombel · {{ totalUsers }} akun
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        <Link :href="urls.users" class="aksara-btn-primary !px-3.5 !py-2 text-xs">
                            Kelola pengguna
                        </Link>
                        <Link :href="urls.access" class="aksara-btn-secondary !px-3.5 !py-2 text-xs">
                            Hak akses
                        </Link>
                        <Link :href="urls.settings" class="aksara-btn-secondary !px-3.5 !py-2 text-xs">
                            Pengaturan
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Infografis: 1 panel, 2 kolom — bukan deretan kartu -->
            <section class="rounded-xl border border-aksara-line bg-white shadow-sm">
                <div class="grid lg:grid-cols-2 lg:divide-x lg:divide-aksara-line">
                    <!-- Donut peran -->
                    <div class="p-5 sm:p-6">
                        <h2 class="text-base font-semibold text-aksara-ink">Distribusi pengguna</h2>
                        <p class="mt-0.5 text-sm text-aksara-muted">Proporsi akun per peran</p>

                        <div class="mt-6 flex flex-col items-center gap-6 sm:flex-row sm:items-center sm:justify-center sm:gap-8">
                            <div class="relative h-40 w-40 shrink-0">
                                <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90" aria-hidden="true">
                                    <circle
                                        cx="50"
                                        cy="50"
                                        :r="R"
                                        fill="none"
                                        stroke="#d7e4e8"
                                        stroke-width="12"
                                    />
                                    <circle
                                        v-for="slice in roleSlices"
                                        :key="slice.key"
                                        cx="50"
                                        cy="50"
                                        :r="R"
                                        fill="none"
                                        :stroke="slice.color"
                                        stroke-width="12"
                                        :stroke-dasharray="`${(slice.pct / 100) * C} ${C}`"
                                        :stroke-dashoffset="`${-(slice.offset / 100) * C}`"
                                        stroke-linecap="butt"
                                    />
                                </svg>
                                <div
                                    class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center"
                                >
                                    <span class="text-2xl font-bold tabular-nums text-aksara-ink">
                                        {{ totalUsers }}
                                    </span>
                                    <span class="text-[11px] text-aksara-muted">total</span>
                                </div>
                            </div>

                            <ul class="w-full max-w-xs space-y-2.5">
                                <li
                                    v-for="slice in roleSlices"
                                    :key="slice.key"
                                    class="flex items-center justify-between gap-3 text-sm"
                                >
                                    <span class="flex min-w-0 items-center gap-2">
                                        <span
                                            class="h-2.5 w-2.5 shrink-0 rounded-full"
                                            :style="{ backgroundColor: slice.color }"
                                        />
                                        <span class="truncate text-aksara-ink">{{ slice.label }}</span>
                                    </span>
                                    <span class="shrink-0 tabular-nums text-aksara-muted">
                                        {{ slice.value }}
                                        <span class="text-aksara-muted/70">({{ Math.round(slice.pct) }}%)</span>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Bar konten -->
                    <div class="border-t border-aksara-line p-5 sm:p-6 lg:border-t-0">
                        <h2 class="text-base font-semibold text-aksara-ink">Konten sekolah</h2>
                        <p class="mt-0.5 text-sm text-aksara-muted">Modul, materi, dan AI hari ini</p>

                        <div class="mt-6 space-y-5">
                            <div v-for="bar in contentBars" :key="bar.key">
                                <div class="mb-1.5 flex items-baseline justify-between gap-2">
                                    <span class="text-sm font-medium text-aksara-ink">{{ bar.label }}</span>
                                    <span class="text-xs text-aksara-muted">{{ bar.caption }}</span>
                                </div>
                                <div
                                    class="flex h-3 overflow-hidden rounded-full bg-aksara-mist"
                                    role="img"
                                    :aria-label="`${bar.label}: ${bar.caption}`"
                                >
                                    <div
                                        v-for="(seg, i) in bar.segments"
                                        :key="i"
                                        class="h-full transition-[width] duration-500"
                                        :style="{
                                            width: `${Math.max(seg.value > 0 ? seg.width : 0, 0)}%`,
                                            backgroundColor: seg.color,
                                            minWidth: seg.value > 0 ? '4px' : '0',
                                        }"
                                        :title="`${seg.label}: ${seg.value}`"
                                    />
                                </div>
                            </div>
                        </div>

                        <p class="mt-6 text-xs text-aksara-muted">
                            Panjang batang relatif terhadap nilai terbesar di panel ini.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Pintasan: satu panel, baris — bukan kartu terpisah -->
            <section class="overflow-hidden rounded-xl border border-aksara-line bg-white shadow-sm">
                <div class="border-b border-aksara-line px-5 py-3.5">
                    <h2 class="text-base font-semibold text-aksara-ink">Pintasan kelola</h2>
                    <p class="mt-0.5 text-sm text-aksara-muted">Area administrasi utama</p>
                </div>
                <ul class="divide-y divide-aksara-line sm:grid sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4">
                    <li
                        v-for="(item, index) in shortcuts"
                        :key="item.title"
                        class="sm:border-aksara-line"
                        :class="{
                            'sm:border-r': index % 2 === 0,
                            'lg:border-r': index < shortcuts.length - 1,
                        }"
                    >
                        <Link
                            :href="item.href"
                            class="flex h-full flex-col px-5 py-4 transition hover:bg-aksara-mist/70"
                        >
                            <span class="text-sm font-semibold text-aksara-teal">{{ item.title }}</span>
                            <span class="mt-0.5 text-xs text-aksara-muted">{{ item.body }}</span>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>
