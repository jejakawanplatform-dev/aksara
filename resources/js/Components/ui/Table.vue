<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
defineProps({
    headers: { type: Array, default: null },
});
</script>

<template>
    <div class="aksara-surface overflow-x-auto">
        <table class="aksara-table">
            <thead v-if="headers?.length || $slots.headers">
                <tr>
                    <slot name="headers">
                        <th v-for="(header, i) in headers" :key="i" class="aksara-th">{{ header }}</th>
                    </slot>
                </tr>
            </thead>
            <tbody class="divide-y divide-aksara-line/60">
                <slot />
            </tbody>
        </table>
    </div>
</template>
