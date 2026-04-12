<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import RealEstatePanel from '@/Components/Business/RealEstate/RealEstatePanel.vue';
</script>

<template>
    <Head title="RealEstate — Business" />
    <BusinessLayout>
        <RealEstatePanel />
    </BusinessLayout>
</template>
