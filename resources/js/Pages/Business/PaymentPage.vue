<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import PaymentPanel from '@/Components/Business/Payment/PaymentPanel.vue';
</script>

<template>
    <Head title="Payment — Business" />
    <BusinessLayout>
        <PaymentPanel />
    </BusinessLayout>
</template>
