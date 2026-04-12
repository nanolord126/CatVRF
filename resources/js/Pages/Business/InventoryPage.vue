<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import InventoryPanel from '@/Components/Business/Inventory/InventoryPanel.vue';
</script>

<template>
    <Head title="Inventory — Business" />
    <BusinessLayout>
        <InventoryPanel />
    </BusinessLayout>
</template>
