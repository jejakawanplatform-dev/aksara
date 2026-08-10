<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const message = computed(() => page.props.flash?.message);
const error = computed(() => page.props.flash?.error);
</script>

<template>
    <div
        v-if="message"
        class="mb-4 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800"
    >
        <span>{{ message }}</span>
    </div>
    <div
        v-if="error"
        class="mb-4 flex items-center justify-between rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800"
    >
        <span>{{ error }}</span>
    </div>
</template>
