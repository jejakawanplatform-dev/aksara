<script setup>
defineProps({
    headers: { type: Array, default: null },
});
</script>

<template>
    <div class="overflow-x-auto rounded-2xl border border-aksara-line bg-white shadow-sm">
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
