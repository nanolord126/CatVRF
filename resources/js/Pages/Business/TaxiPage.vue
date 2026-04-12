<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import TaxiPanel from '@/Components/Business/Taxi/TaxiPanel.vue';
</script>

<template>
    <Head title="Taxi — Business" />
    <BusinessLayout>
        <TaxiPanel />
    </BusinessLayout>
</template>
