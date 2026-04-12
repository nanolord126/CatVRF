<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import AnalyticsPanel from '@/Components/Business/Analytics/AnalyticsPanel.vue';
</script>

<template>
    <Head title="Analytics — Business" />
    <BusinessLayout>
        <AnalyticsPanel />
    </BusinessLayout>
</template>
