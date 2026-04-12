<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import DeliveryPanel from '@/Components/Business/Delivery/DeliveryPanel.vue';
</script>

<template>
    <Head title="Delivery — Business" />
    <BusinessLayout>
        <DeliveryPanel />
    </BusinessLayout>
</template>
