<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';

defineProps({
    userName: { type: String, default: '' },
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>Dashboard</template>

        <Card>
            <p class="text-aksara-ink">
                Halo{{ userName ? `, ${userName}` : '' }}. Anda sudah masuk.
            </p>
        </Card>
    </AppLayout>
</template>
