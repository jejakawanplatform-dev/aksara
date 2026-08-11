<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
defineProps({
    label: { type: String, default: null },
    required: { type: Boolean, default: false },
    error: { type: String, default: null },
    hint: { type: String, default: null },
    forId: { type: String, default: null },
});
</script>

<template>
    <div class="space-y-1.5">
        <label v-if="label" :for="forId" class="aksara-label">
            {{ label }}
            <span v-if="required" class="font-bold text-aksara-danger">*</span>
        </label>
        <slot />
        <p v-if="hint && !error" class="text-xs text-aksara-muted">{{ hint }}</p>
        <p v-if="error" class="text-xs font-medium text-aksara-danger" role="alert">{{ error }}</p>
    </div>
</template>
