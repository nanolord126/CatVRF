<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import HotelsPanel from '@/Components/Business/Hotels/HotelsPanel.vue';
</script>

<template>
    <Head title="Hotels — Business" />
    <BusinessLayout>
        <HotelsPanel />
    </BusinessLayout>
</template>
