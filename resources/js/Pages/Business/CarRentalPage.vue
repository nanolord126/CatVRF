<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import CarRentalPanel from '@/Components/Business/CarRental/CarRentalPanel.vue';
</script>

<template>
    <Head title="CarRental — Business" />
    <BusinessLayout>
        <CarRentalPanel />
    </BusinessLayout>
</template>
