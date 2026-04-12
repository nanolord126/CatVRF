<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import CRMPanel from '@/Components/Business/CRM/CRMPanel.vue';
</script>

<template>
    <Head title="CRM — Business" />
    <BusinessLayout>
        <CRMPanel />
    </BusinessLayout>
</template>
