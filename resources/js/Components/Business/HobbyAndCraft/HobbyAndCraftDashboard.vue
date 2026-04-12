<script setup>
const metrics=[{label:'SKU хобби',value:'15 330',trend:'+5.8%',icon:'🧵'},{label:'Заказы',value:'1 422',trend:'+7.1%',icon:'🛒'},{label:'Повторные',value:'44%',trend:'+2.2%',icon:'🔁'},{label:'Возвраты',value:'1.2%',trend:'-0.2%',icon:'✅'}];
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="i in metrics" :key="i.label" class="rounded-2xl border p-4" style="background:var(--t-surface);border-color:var(--t-border);"><div class="text-xs" style="color:var(--t-text-3);">{{i.label}}</div><div class="mt-2 text-xl font-bold" style="color:var(--t-text);">{{i.value}}</div><div class="mt-1 text-xs" style="color:var(--t-primary);">{{i.trend}} · {{i.icon}}</div></article></section></template>
