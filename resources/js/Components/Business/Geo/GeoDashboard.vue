<script setup>
const metrics=[{label:'Geo events',value:'8.4 млн',trend:'+9.2%',icon:'📍'},{label:'Точность геокластера',value:'96.8%',trend:'+0.6%',icon:'🛰️'},{label:'Гео-конверсии',value:'4.2%',trend:'+0.4%',icon:'🧭'},{label:'Ошибки геокода',value:'0.5%',trend:'-0.1%',icon:'✅'}];
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="i in metrics" :key="i.label" class="rounded-2xl border p-4" style="background:var(--t-surface);border-color:var(--t-border);"><div class="text-xs" style="color:var(--t-text-3);">{{i.label}}</div><div class="mt-2 text-xl font-bold" style="color:var(--t-text);">{{i.value}}</div><div class="mt-1 text-xs" style="color:var(--t-primary);">{{i.trend}} · {{i.icon}}</div></article></section></template>
