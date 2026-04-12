<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import LogisticsPanel from '@/Components/Business/Logistics/LogisticsPanel.vue';
</script>

<template>
    <Head title="Logistics — Business" />
    <BusinessLayout>
        <LogisticsPanel />
    </BusinessLayout>
</template>
