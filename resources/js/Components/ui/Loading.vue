<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
defineProps({
    text: { type: String, default: 'Memproses data...' },
    show: { type: Boolean, default: true },
});
</script>

<template>
    <div
        v-if="show"
        class="flex items-center justify-center gap-3 rounded-xl border border-aksara-teal/20 bg-aksara-mist p-4 text-sm font-medium text-aksara-teal-dark shadow-sm"
    >
        <svg class="h-5 w-5 animate-spin text-aksara-teal" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
        </svg>
        <span>{{ text }}</span>
    </div>
</template>
