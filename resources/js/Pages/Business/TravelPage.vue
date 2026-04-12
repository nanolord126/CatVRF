<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import TravelPanel from '@/Components/Business/Travel/TravelPanel.vue';
</script>

<template>
    <Head title="Travel — Business" />
    <BusinessLayout>
        <TravelPanel />
    </BusinessLayout>
</template>
