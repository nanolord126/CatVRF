<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import MedicalPanel from '@/Components/Business/Medical/MedicalPanel.vue';
</script>

<template>
    <Head title="Medical — Business" />
    <BusinessLayout>
        <MedicalPanel />
    </BusinessLayout>
</template>
