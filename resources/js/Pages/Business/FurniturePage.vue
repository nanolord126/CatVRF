<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import FurniturePanel from '@/Components/Business/Furniture/FurniturePanel.vue';
</script>

<template>
    <Head title="Furniture — Business" />
    <BusinessLayout>
        <FurniturePanel />
    </BusinessLayout>
</template>
