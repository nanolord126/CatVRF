<script setup>
const metrics=[{label:'Forecast jobs',value:'1 204',trend:'+8.4%',icon:'📈'},{label:'MAPE',value:'6.2%',trend:'-0.4%',icon:'🎯'},{label:'Low-stock alerts',value:'184',trend:'+12',icon:'⚠️'},{label:'Auto-replenishment',value:'72%',trend:'+2.1%',icon:'🔁'}];
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="i in metrics" :key="i.label" class="rounded-2xl border p-4" style="background:var(--t-surface);border-color:var(--t-border);"><div class="text-xs" style="color:var(--t-text-3);">{{i.label}}</div><div class="mt-2 text-xl font-bold" style="color:var(--t-text);">{{i.value}}</div><div class="mt-1 text-xs" style="color:var(--t-primary);">{{i.trend}} · {{i.icon}}</div></article></section></template>
