<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import FitnessPanel from '@/Components/Business/Fitness/FitnessPanel.vue';
</script>

<template>
    <Head title="Fitness — Business" />
    <BusinessLayout>
        <FitnessPanel />
    </BusinessLayout>
</template>
