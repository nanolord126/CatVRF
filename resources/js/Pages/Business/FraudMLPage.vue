<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import FraudMLPanel from '@/Components/Business/FraudML/FraudMLPanel.vue';
</script>

<template>
    <Head title="FraudML — Business" />
    <BusinessLayout>
        <FraudMLPanel />
    </BusinessLayout>
</template>
