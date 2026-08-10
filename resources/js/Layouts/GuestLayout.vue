<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: '' },
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-aksara-mist via-aksara-paper to-aksara-paper font-sans text-aksara-ink antialiased">
        <Head :title="title" />

        <div class="flex min-h-screen flex-col items-center px-4 pt-8 sm:justify-center sm:pt-0">
            <div class="mb-6 text-center">
                <Link href="/" class="inline-flex items-center gap-3">
                    <span
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-aksara-teal font-display text-lg font-bold text-white shadow-sm"
                    >
                        A
                    </span>
                    <span class="text-left">
                        <span class="block font-display text-xl font-semibold leading-none">Aksara</span>
                        <span class="block text-xs text-aksara-muted">Masuk ke ruang pembelajaran</span>
                    </span>
                </Link>
            </div>

            <div
                class="w-full overflow-hidden rounded-2xl border border-aksara-line bg-white px-6 py-6 shadow-sm sm:max-w-md"
            >
                <slot />
            </div>
        </div>
    </div>
</template>
