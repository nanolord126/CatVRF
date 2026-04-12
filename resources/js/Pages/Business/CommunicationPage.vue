<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import CommunicationPanel from '@/Components/Business/Communication/CommunicationPanel.vue';
</script>

<template>
    <Head title="Communication — Business" />
    <BusinessLayout>
        <CommunicationPanel />
    </BusinessLayout>
</template>
