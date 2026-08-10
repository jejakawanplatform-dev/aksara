<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const appName = computed(() => page.props.appName || 'Aksara');
</script>

<template>
    <div class="min-h-screen bg-aksara-paper font-sans text-aksara-ink antialiased">
        <Head :title="appName" />
        <div class="mx-auto flex min-h-screen max-w-5xl flex-col justify-center px-6 py-16">
            <div class="mb-10 flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-aksara-teal font-display text-lg font-bold text-white">A</span>
                <div>
                    <p class="font-display text-2xl font-semibold leading-none">Aksara</p>
                    <p class="mt-1 text-sm text-aksara-muted">Ruang pembelajaran AI untuk sekolah</p>
                </div>
            </div>

            <h1 class="max-w-2xl font-display text-4xl font-bold tracking-tight sm:text-5xl">
                Modul ajar, materi, dan evaluasi dalam satu alur.
            </h1>
            <p class="mt-4 max-w-xl text-base text-aksara-muted">
                Aksara membantu guru merancang pembelajaran, menyusun bahan ajar dengan TipTap, dan memantau kelas —
                dengan asisten AI Co-Pilot yang tetap di bawah kendali guru.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                <Link
                    v-if="user"
                    href="/dashboard"
                    class="aksara-btn-primary"
                >
                    Buka Dashboard
                </Link>
                <template v-else>
                    <Link href="/login" class="aksara-btn-primary">Masuk</Link>
                    <Link href="/register" class="aksara-btn-secondary">Daftar</Link>
                </template>
            </div>
        </div>
    </div>
</template>
