<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import FoodPanel from '@/Components/Business/Food/FoodPanel.vue';
</script>

<template>
    <Head title="Food — Business" />
    <BusinessLayout>
        <FoodPanel />
    </BusinessLayout>
</template>
