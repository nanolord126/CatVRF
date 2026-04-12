<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import AIPanel from '@/Components/Business/AI/AIPanel.vue';
</script>

<template>
    <Head title="AI — Business" />
    <BusinessLayout>
        <AIPanel />
    </BusinessLayout>
</template>
