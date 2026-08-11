<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
/**
 * Page-level intro block (below Topbar). Spec 17 — title hierarchy SoT.
 * Literata hanya brand/hero singkat; judul page pakai sans.
 */
defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="aksara-page-header flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div class="min-w-0">
            <div v-if="$slots.meta" class="mb-2 flex flex-wrap items-center gap-2">
                <slot name="meta" />
            </div>
            <h2 v-if="title || $slots.title" class="text-xl font-bold tracking-tight text-aksara-ink sm:text-2xl">
                <slot name="title">{{ title }}</slot>
            </h2>
            <p v-if="description || $slots.description" class="mt-1 max-w-2xl text-sm text-aksara-muted">
                <slot name="description">{{ description }}</slot>
            </p>
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
