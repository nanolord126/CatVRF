<script setup>
const metrics=[{label:'Заказов grocery',value:'2 940',trend:'+10.7%',icon:'🛒'},{label:'Сборка',value:'14 мин',trend:'-1.2',icon:'📦'},{label:'On-time delivery',value:'94.9%',trend:'+1.0%',icon:'🚚'},{label:'Substitution rate',value:'3.1%',trend:'-0.3%',icon:'✅'}];
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="i in metrics" :key="i.label" class="rounded-2xl border p-4" style="background:var(--t-surface);border-color:var(--t-border);"><div class="text-xs" style="color:var(--t-text-3);">{{i.label}}</div><div class="mt-2 text-xl font-bold" style="color:var(--t-text);">{{i.value}}</div><div class="mt-1 text-xs" style="color:var(--t-primary);">{{i.trend}} · {{i.icon}}</div></article></section></template>
