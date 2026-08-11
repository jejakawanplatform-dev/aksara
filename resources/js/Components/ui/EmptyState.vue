<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
defineProps({
    title: { type: String, default: 'Belum ada data' },
    actionHref: { type: String, default: null },
    actionLabel: { type: String, default: null },
});
</script>

<template>
    <div class="aksara-surface-dashed px-6 py-12 text-center">
        <p class="text-lg font-semibold text-aksara-ink">{{ title }}</p>
        <div class="mt-2 text-sm text-aksara-muted">
            <slot />
        </div>
        <a
            v-if="actionHref && actionLabel"
            :href="actionHref"
            class="aksara-btn-primary mt-5 inline-flex"
        >
            {{ actionLabel }}
        </a>
    </div>
</template>
