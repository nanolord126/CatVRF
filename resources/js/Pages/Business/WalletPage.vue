<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import WalletPanel from '@/Components/Business/Wallet/WalletPanel.vue';
</script>

<template>
    <Head title="Wallet — Business" />
    <BusinessLayout>
        <WalletPanel />
    </BusinessLayout>
</template>
