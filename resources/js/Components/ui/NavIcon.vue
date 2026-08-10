<script setup>
import Icon from './Icon.vue';

defineProps({
    name: { type: String, required: true },
});
</script>

<template>
    <Icon :name="name" v-bind="$attrs" />
</template>
