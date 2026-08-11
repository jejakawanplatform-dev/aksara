<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
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
        class="mb-4 rounded-xl border border-aksara-ok/25 bg-aksara-ok/5 px-4 py-3 text-sm font-medium text-aksara-ink"
        role="status"
    >
        {{ message }}
    </div>
    <div
        v-if="error"
        class="mb-4 rounded-xl border border-aksara-danger/25 bg-aksara-danger/5 px-4 py-3 text-sm font-medium text-aksara-ink"
        role="alert"
    >
        {{ error }}
    </div>
</template>
