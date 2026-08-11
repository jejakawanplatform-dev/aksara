<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import BrandCopyright from '@/Components/brand/BrandCopyright.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const appName = computed(() => page.props.appName || 'Aksara');

const scrolled = ref(false);
function onScroll() {
    scrolled.value = window.scrollY > 8;
}

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});
onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
});

const features = [
    {
        title: 'Rencana pembelajaran',
        body: 'Susun modul ajar dengan kerangka CP/TP/ATP. Draf AI tersedia, keputusan akhir tetap di guru.',
    },
    {
        title: 'Materi & editor TipTap',
        body: 'Tulis dan terbitkan bahan ajar kaya teks, media, dan rumus — siap dibaca siswa di kelas.',
    },
    {
        title: 'Kehadiran & evaluasi',
        body: 'Catat absensi, pantau kuis, dan refleksikan hasil pembelajaran dalam satu alur.',
    },
    {
        title: 'Laporan & supervisi',
        body: 'Rekap untuk wali kelas dan jurnal guru tanpa keluar dari ruang kerja yang sama.',
    },
];

const roles = [
    { label: 'Guru', body: 'Modul ajar, materi, kehadiran, kuis, dan evaluasi kelas.' },
    { label: 'Siswa', body: 'Akses materi terbit dan kerjakan kuis dengan umpan balik cepat.' },
    { label: 'Wali kelas', body: 'Pantau kehadiran dan kesehatan pembelajaran rombel.' },
    { label: 'Admin', body: 'Kelola pengguna, referensi kurikulum, dan pengaturan sekolah.' },
];

const steps = [
    { n: '1', title: 'Masuk dengan akun sekolah', body: 'Gunakan kredensial yang diberikan admin atau bimtek.' },
    { n: '2', title: 'Bangun alur pembelajaran', body: 'Dari rencana → materi → kehadiran & evaluasi.' },
    { n: '3', title: 'Pantau dan sempurnakan', body: 'Lihat rekap, refleksi, dan sesuaikan untuk pertemuan berikutnya.' },
];
</script>

<template>
    <div class="landing min-h-screen bg-aksara-paper font-sans text-aksara-ink antialiased">
        <Head :title="`${appName} — Pembelajaran AI untuk sekolah`" />

        <!-- Header -->
        <header
            class="landing-header sticky top-0 z-50 border-b transition-[background-color,border-color,box-shadow] duration-300"
            :class="
                scrolled
                    ? 'border-aksara-line bg-white shadow-sm'
                    : 'border-transparent bg-transparent'
            "
        >
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                <a href="#atas" class="flex items-center gap-2.5">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-aksara-teal font-display text-sm font-bold text-white"
                    >
                        A
                    </span>
                    <span class="leading-tight">
                        <span class="block font-display text-base font-semibold">Aksara</span>
                        <span class="hidden text-[11px] text-aksara-muted sm:block">Pembelajaran AI</span>
                    </span>
                </a>

                <nav class="hidden items-center gap-6 text-sm text-aksara-muted md:flex">
                    <a href="#fitur" class="transition hover:text-aksara-ink">Fitur</a>
                    <a href="#peran" class="transition hover:text-aksara-ink">Untuk siapa</a>
                    <a href="#alur" class="transition hover:text-aksara-ink">Cara kerja</a>
                </nav>

                <div class="flex items-center gap-2">
                    <template v-if="user">
                        <Link href="/dashboard" class="aksara-btn-primary !px-3.5 !py-2 text-xs sm:text-sm">
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            href="/login"
                            class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-aksara-ink transition hover:bg-aksara-mist sm:inline-flex"
                        >
                            Masuk
                        </Link>
                        <Link href="/register" class="aksara-btn-primary !px-3.5 !py-2 text-xs sm:text-sm">
                            Daftar
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <main id="atas">
            <!-- Hero: one composition -->
            <section class="landing-hero relative overflow-hidden border-b border-aksara-line">
                <div class="landing-hero__wash pointer-events-none absolute inset-0" aria-hidden="true" />
                <div
                    class="relative mx-auto grid max-w-6xl gap-10 px-4 pb-16 pt-10 sm:px-6 sm:pb-20 sm:pt-14 lg:grid-cols-2 lg:items-center lg:gap-12"
                >
                    <div class="landing-fade-up max-w-xl">
                        <p class="font-display text-3xl font-bold tracking-tight text-aksara-teal sm:text-4xl">
                            Aksara
                        </p>
                        <h1 class="mt-3 text-3xl font-bold tracking-tight text-aksara-ink sm:text-4xl lg:text-[2.75rem] lg:leading-tight">
                            Ruang kerja pembelajaran AI untuk sekolah.
                        </h1>
                        <p class="mt-4 text-base leading-relaxed text-aksara-muted sm:text-lg">
                            Satu tempat untuk modul ajar, materi, kehadiran, dan evaluasi — dengan asisten AI
                            yang tetap di bawah kendali guru.
                        </p>
                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            <Link
                                v-if="user"
                                href="/dashboard"
                                class="aksara-btn-primary"
                            >
                                Buka Dashboard
                            </Link>
                            <template v-else>
                                <Link href="/login" class="aksara-btn-primary">Masuk ke Aksara</Link>
                                <Link href="/register" class="aksara-btn-secondary">Buat akun</Link>
                            </template>
                        </div>
                        <p class="mt-5 text-xs text-aksara-muted">
                            Cocok untuk bimtek dan operasional sekolah sehari-hari. Tidak perlu keluar aplikasi untuk
                            menyusun, menerbitkan, dan memantau pembelajaran.
                        </p>
                    </div>

                    <!-- Product visual anchor (not a card cluster) -->
                    <div class="landing-fade-up landing-fade-up--delay relative lg:justify-self-end" aria-hidden="true">
                        <div class="landing-preview relative w-full max-w-lg overflow-hidden rounded-xl border border-aksara-line bg-white shadow-aksara">
                            <div class="flex items-center gap-2 border-b border-aksara-line bg-aksara-mist/80 px-4 py-2.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-aksara-line" />
                                <span class="h-2.5 w-2.5 rounded-full bg-aksara-line" />
                                <span class="h-2.5 w-2.5 rounded-full bg-aksara-line" />
                                <span class="ml-3 text-[11px] font-medium text-aksara-muted">Dashboard Guru</span>
                            </div>
                            <div class="grid grid-cols-[4.5rem_1fr] sm:grid-cols-[5.5rem_1fr]">
                                <div class="space-y-2 border-r border-aksara-line bg-white p-3">
                                    <div class="h-6 w-6 rounded-md bg-aksara-teal/90" />
                                    <div class="h-2 w-full rounded bg-aksara-mist" />
                                    <div class="h-2 w-4/5 rounded bg-aksara-mist" />
                                    <div class="mt-3 h-2 w-full rounded bg-aksara-teal/20" />
                                    <div class="h-2 w-3/4 rounded bg-aksara-mist" />
                                    <div class="h-2 w-full rounded bg-aksara-mist" />
                                </div>
                                <div class="space-y-3 p-4">
                                    <div class="rounded-lg border border-aksara-line border-l-4 border-l-aksara-teal bg-white p-3">
                                        <div class="h-3 w-32 rounded bg-aksara-ink/80" />
                                        <div class="mt-2 h-2 w-48 max-w-full rounded bg-aksara-mist" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="rounded-lg border border-aksara-line p-3">
                                            <div class="h-2 w-16 rounded bg-aksara-muted/40" />
                                            <div class="mt-2 h-6 w-10 rounded bg-aksara-ink/70" />
                                        </div>
                                        <div class="rounded-lg border border-aksara-line p-3">
                                            <div class="h-2 w-16 rounded bg-aksara-muted/40" />
                                            <div class="mt-2 h-6 w-8 rounded bg-aksara-teal/70" />
                                        </div>
                                    </div>
                                    <div class="rounded-lg border border-aksara-line p-3">
                                        <div class="mb-2 h-2 w-40 rounded bg-aksara-mist" />
                                        <div class="space-y-1.5">
                                            <div class="h-2 w-full rounded bg-aksara-mist" />
                                            <div class="h-2 w-5/6 rounded bg-aksara-mist" />
                                            <div class="h-2 w-4/5 rounded bg-aksara-mist" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Fitur -->
            <section id="fitur" class="border-b border-aksara-line bg-white">
                <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
                    <div class="max-w-2xl">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Yang bisa Anda lakukan</h2>
                        <p class="mt-2 text-aksara-muted">
                            Informasi singkat sebelum masuk — fokus pada pekerjaan nyata di sekolah.
                        </p>
                    </div>
                    <ul class="mt-10 grid gap-8 sm:grid-cols-2">
                        <li
                            v-for="(item, i) in features"
                            :key="item.title"
                            class="landing-fade-in border-t border-aksara-line pt-5"
                            :style="{ animationDelay: `${i * 60}ms` }"
                        >
                            <h3 class="text-base font-semibold text-aksara-ink">{{ item.title }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-aksara-muted">{{ item.body }}</p>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Peran -->
            <section id="peran" class="border-b border-aksara-line bg-aksara-paper">
                <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
                    <div class="max-w-2xl">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Untuk siapa</h2>
                        <p class="mt-2 text-aksara-muted">
                            Peran berbeda, satu platform. Akses disesuaikan setelah Anda masuk.
                        </p>
                    </div>
                    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div v-for="role in roles" :key="role.label" class="border-l-2 border-aksara-teal pl-4">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-aksara-teal">
                                {{ role.label }}
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-aksara-muted">{{ role.body }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Alur -->
            <section id="alur" class="border-b border-aksara-line bg-white">
                <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
                    <div class="max-w-2xl">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Cara kerja</h2>
                        <p class="mt-2 text-aksara-muted">Tiga langkah sederhana dari login hingga pantauan kelas.</p>
                    </div>
                    <ol class="mt-10 grid gap-8 md:grid-cols-3">
                        <li v-for="step in steps" :key="step.n" class="flex gap-4">
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-aksara-line bg-aksara-mist text-sm font-bold text-aksara-teal"
                            >
                                {{ step.n }}
                            </span>
                            <div>
                                <h3 class="text-base font-semibold">{{ step.title }}</h3>
                                <p class="mt-1.5 text-sm leading-relaxed text-aksara-muted">{{ step.body }}</p>
                            </div>
                        </li>
                    </ol>
                    <div class="mt-12 flex flex-wrap items-center gap-3 border-t border-aksara-line pt-8">
                        <p class="mr-auto text-sm text-aksara-muted">Siap mencoba ruang kerja Anda?</p>
                        <Link v-if="user" href="/dashboard" class="aksara-btn-primary">Buka Dashboard</Link>
                        <template v-else>
                            <Link href="/login" class="aksara-btn-primary">Masuk</Link>
                            <Link href="/register" class="aksara-btn-secondary">Daftar</Link>
                        </template>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-white">
            <div class="mx-auto flex max-w-6xl flex-col gap-8 px-4 py-12 sm:px-6 md:flex-row md:items-start md:justify-between">
                <div class="max-w-sm">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-aksara-teal font-display text-xs font-bold text-white"
                        >
                            A
                        </span>
                        <span class="font-display text-base font-semibold">Aksara</span>
                    </div>
                    <p class="mt-3 text-sm leading-relaxed text-aksara-muted">
                        Platform manajemen pembelajaran berbasis AI untuk sekolah.
                    </p>
                </div>
                <div class="flex flex-wrap gap-10 text-sm">
                    <div>
                        <p class="font-semibold text-aksara-ink">Jelajahi</p>
                        <ul class="mt-3 space-y-2 text-aksara-muted">
                            <li><a href="#fitur" class="hover:text-aksara-teal">Fitur</a></li>
                            <li><a href="#peran" class="hover:text-aksara-teal">Untuk siapa</a></li>
                            <li><a href="#alur" class="hover:text-aksara-teal">Cara kerja</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold text-aksara-ink">Akun</p>
                        <ul class="mt-3 space-y-2 text-aksara-muted">
                            <li v-if="user">
                                <Link href="/dashboard" class="hover:text-aksara-teal">Dashboard</Link>
                            </li>
                            <template v-else>
                                <li><Link href="/login" class="hover:text-aksara-teal">Masuk</Link></li>
                                <li><Link href="/register" class="hover:text-aksara-teal">Daftar</Link></li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="border-t border-aksara-line">
                <div
                    class="mx-auto max-w-6xl px-4 py-4 text-xs text-aksara-muted sm:px-6"
                >
                    <BrandCopyright variant="landing" />
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.landing-hero__wash {
    background:
        radial-gradient(ellipse 85% 55% at 8% 0%, rgba(15, 118, 110, 0.11), transparent 58%),
        radial-gradient(ellipse 55% 45% at 92% 15%, rgba(14, 165, 180, 0.08), transparent 52%),
        linear-gradient(180deg, #ffffff 0%, var(--aksara-paper) 100%);
}

.landing-fade-up {
    animation: landing-fade-up 0.7s ease-out both;
}

.landing-fade-up--delay {
    animation-delay: 0.12s;
}

.landing-fade-in {
    animation: landing-fade-in 0.55s ease-out both;
}

.landing-preview {
    animation: landing-float 6s ease-in-out infinite;
}

@keyframes landing-fade-up {
    from {
        opacity: 0;
        transform: translateY(14px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes landing-fade-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes landing-float {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-6px);
    }
}

@media (prefers-reduced-motion: reduce) {
    .landing-fade-up,
    .landing-fade-in,
    .landing-preview {
        animation: none;
    }
}
</style>
