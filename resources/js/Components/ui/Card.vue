<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
defineProps({
    title: { type: String, default: null },
    description: { type: String, default: null },
});
</script>

<template>
    <div class="aksara-card">
        <div v-if="title || $slots.actions" class="aksara-card-header">
            <div>
                <h3 v-if="title" class="text-base font-semibold text-aksara-ink">{{ title }}</h3>
                <p v-if="description" class="mt-0.5 text-xs text-aksara-muted">{{ description }}</p>
            </div>
            <div v-if="$slots.actions" class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
        <div class="aksara-card-body">
            <slot />
        </div>
    </div>
</template>
