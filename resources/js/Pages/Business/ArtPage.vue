<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import ArtPanel from '@/Components/Business/Art/ArtPanel.vue';
</script>

<template>
    <Head title="Art — Business" />
    <BusinessLayout>
        <ArtPanel />
    </BusinessLayout>
</template>
