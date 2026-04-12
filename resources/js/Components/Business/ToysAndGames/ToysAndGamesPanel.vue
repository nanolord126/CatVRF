<script setup>
import { ref } from 'vue'; import VTabs from '../../UI/VTabs.vue'; import VCard from '../../UI/VCard.vue'; import VBadge from '../../UI/VBadge.vue';
const activeTab=ref('overview'); const tabs=[{key:'overview',label:'Обзор',icon:'📊'},{key:'catalog',label:'Каталог',icon:'🧸'},{key:'season',label:'Сезон',icon:'🎁'}];
const kpi=[{label:'SKU',value:'11 240'},{label:'Orders/day',value:'980'},{label:'Gift share',value:'38%'},{label:'AOV',value:'2 780 ₽'}];
</script>
<template><section class="space-y-4"><header class="flex items-center justify-between"><h2 class="text-xl font-bold" style="color:var(--t-text);">ToysAndGames Panel</h2><VBadge text="PRODUCTION" variant="success" size="sm" dot/></header><VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm"/><VCard title="KPI"><div class="grid grid-cols-2 md:grid-cols-4 gap-3"><article v-for="i in kpi" :key="i.label" class="rounded-xl border p-3" style="border-color:var(--t-border);"><div class="text-xs" style="color:var(--t-text-3);">{{i.label}}</div><div class="text-lg font-bold" style="color:var(--t-text);">{{i.value}}</div></article></div></VCard></section></template>
