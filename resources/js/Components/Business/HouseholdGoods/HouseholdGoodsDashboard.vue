<script setup>
const metrics=[{label:'SKU household',value:'33 200',trend:'+6.9%',icon:'🧺'},{label:'Orders/day',value:'2 104',trend:'+9.6%',icon:'🛒'},{label:'AOV',value:'1 860 ₽',trend:'+2.8%',icon:'💳'},{label:'OOS',value:'3.7%',trend:'-0.5%',icon:'📦'}];
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="i in metrics" :key="i.label" class="rounded-2xl border p-4" style="background:var(--t-surface);border-color:var(--t-border);"><div class="text-xs" style="color:var(--t-text-3);">{{i.label}}</div><div class="mt-2 text-xl font-bold" style="color:var(--t-text);">{{i.value}}</div><div class="mt-1 text-xs" style="color:var(--t-primary);">{{i.trend}} · {{i.icon}}</div></article></section></template>
