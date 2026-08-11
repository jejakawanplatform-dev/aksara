<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';

defineProps({
    userName: { type: String, default: '' },
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>Dashboard</template>

        <div class="space-y-5">
            <PageHeader
                :title="userName ? `Halo, ${userName}` : 'Dashboard'"
                description="Anda sudah masuk ke Aksara."
            />

            <div class="aksara-surface p-4 sm:p-5">
                <p class="text-sm text-aksara-ink">
                    Halo{{ userName ? `, ${userName}` : '' }}. Anda sudah masuk.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
