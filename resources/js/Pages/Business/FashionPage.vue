<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import FashionPanel from '@/Components/Business/Fashion/FashionPanel.vue';
</script>

<template>
    <Head title="Fashion — Business" />
    <BusinessLayout>
        <FashionPanel />
    </BusinessLayout>
</template>
