<script setup>
import { ref } from 'vue'; import VTabs from '../../UI/VTabs.vue'; import VCard from '../../UI/VCard.vue'; import VBadge from '../../UI/VBadge.vue';
const activeTab=ref('overview'); const tabs=[{key:'overview',label:'Обзор',icon:'📊'},{key:'events',label:'События',icon:'🏅'},{key:'store',label:'Store',icon:'🛒'}];
const kpi=[{label:'Users active',value:'120 500'},{label:'Tickets sold',value:'48 100'},{label:'Merch GMV',value:'96 млн ₽'},{label:'NPS',value:'74'}];
</script>
<template><section class="space-y-4"><header class="flex items-center justify-between"><h2 class="text-xl font-bold" style="color:var(--t-text);">Sports Panel</h2><VBadge text="PRODUCTION" variant="success" size="sm" dot/></header><VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm"/><VCard title="KPI"><div class="grid grid-cols-2 md:grid-cols-4 gap-3"><article v-for="i in kpi" :key="i.label" class="rounded-xl border p-3" style="border-color:var(--t-border);"><div class="text-xs" style="color:var(--t-text-3);">{{i.label}}</div><div class="text-lg font-bold" style="color:var(--t-text);">{{i.value}}</div></article></div></VCard></section></template>
