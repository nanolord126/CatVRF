<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import BooksAndLiteraturePanel from '@/Components/Business/BooksAndLiterature/BooksAndLiteraturePanel.vue';
</script>

<template>
    <Head title="BooksAndLiterature — Business" />
    <BusinessLayout>
        <BooksAndLiteraturePanel />
    </BusinessLayout>
</template>
