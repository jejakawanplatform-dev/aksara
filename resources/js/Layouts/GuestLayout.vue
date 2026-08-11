<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import BrandCopyright from '@/Components/brand/BrandCopyright.vue';

defineProps({
    title: { type: String, default: '' },
    heading: { type: String, default: '' },
    description: { type: String, default: '' },
});
</script>

<template>
    <!--
      Auth shell ≠ marketing Welcome.
      Pola populer: split brand + form fokus (Linear / Notion / Vercel-style).
      GuestLayout tetap dipakai agar Login/Register/dll DRY — bukan salinan landing.
    -->
    <div class="min-h-screen bg-white font-sans text-aksara-ink antialiased lg:grid lg:grid-cols-2">
        <Head :title="title" />

        <!-- Brand panel (desktop) -->
        <aside
            class="relative hidden overflow-hidden border-r border-aksara-line bg-aksara-paper lg:flex lg:flex-col lg:justify-between lg:px-12 lg:py-10 xl:px-16"
        >
            <div
                class="pointer-events-none absolute inset-0"
                aria-hidden="true"
                style="
                    background:
                        radial-gradient(ellipse 75% 55% at 15% 0%, rgba(15, 118, 110, 0.14), transparent 58%),
                        radial-gradient(ellipse 55% 45% at 95% 85%, rgba(14, 165, 180, 0.09), transparent 52%);
                "
            />

            <Link href="/" class="relative z-10 inline-flex items-center gap-2.5">
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-aksara-teal font-display text-sm font-bold text-white"
                >
                    A
                </span>
                <span class="font-display text-lg font-semibold tracking-tight">Aksara</span>
            </Link>

            <div class="relative z-10 max-w-md">
                <p class="font-display text-3xl font-bold leading-tight tracking-tight xl:text-4xl">
                    Ruang kerja pembelajaran AI untuk sekolah.
                </p>
                <p class="mt-4 text-sm leading-relaxed text-aksara-muted xl:text-base">
                    Modul ajar, materi, kehadiran, dan evaluasi dalam satu alur — dengan AI yang tetap di bawah
                    kendali guru.
                </p>
                <ul class="mt-8 space-y-3 text-sm text-aksara-muted">
                    <li class="flex gap-3">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-aksara-teal" />
                        Draf rencana pembelajaran dengan kerangka kurikulum
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-aksara-teal" />
                        Editor materi TipTap siap terbit ke siswa
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-aksara-teal" />
                        Kehadiran, kuis, dan laporan di satu tempat
                    </li>
                </ul>
            </div>

            <div class="relative z-10">
                <BrandCopyright variant="guest" />
            </div>
        </aside>

        <!-- Form column -->
        <div class="flex min-h-screen flex-col">
            <div class="flex flex-1 flex-col justify-center px-5 py-10 sm:px-8">
                <div class="mx-auto w-full max-w-[24rem]">
                    <!-- Mobile brand (desktop sudah di panel kiri) -->
                    <Link href="/" class="mb-8 inline-flex items-center gap-2.5 lg:hidden">
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-aksara-teal font-display text-sm font-bold text-white"
                        >
                            A
                        </span>
                        <span class="font-display text-base font-semibold">Aksara</span>
                    </Link>

                    <div v-if="heading || description || $slots.intro" class="mb-8">
                        <slot name="intro">
                            <h1 v-if="heading" class="text-2xl font-bold tracking-tight text-aksara-ink">
                                {{ heading }}
                            </h1>
                            <p v-if="description" class="mt-2 text-sm leading-relaxed text-aksara-muted">
                                {{ description }}
                            </p>
                        </slot>
                    </div>

                    <!-- Form langsung di permukaan putih — tanpa kartu ganda -->
                    <slot />

                    <p class="mt-10 text-center text-xs text-aksara-muted lg:text-left">
                        <Link href="/" class="font-medium text-aksara-teal hover:underline">← Kembali ke beranda</Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
