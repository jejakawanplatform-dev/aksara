<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import Icon from './Icon.vue';

defineProps({
    name: { type: String, required: true },
});
</script>

<template>
    <Icon :name="name" v-bind="$attrs" />
</template>
