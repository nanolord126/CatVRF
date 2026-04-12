<script setup>
const metrics=[{label:'SKU сад',value:'9 410',trend:'+6.0%',icon:'🌱'},{label:'Сезонные заказы',value:'1 230',trend:'+12.4%',icon:'📦'},{label:'Повторные покупки',value:'39%',trend:'+2.1%',icon:'🔁'},{label:'Возвраты',value:'1.3%',trend:'-0.1%',icon:'↩️'}];
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="i in metrics" :key="i.label" class="rounded-2xl border p-4" style="background:var(--t-surface);border-color:var(--t-border);"><div class="text-xs" style="color:var(--t-text-3);">{{i.label}}</div><div class="mt-2 text-xl font-bold" style="color:var(--t-text);">{{i.value}}</div><div class="mt-1 text-xs" style="color:var(--t-primary);">{{i.trend}} · {{i.icon}}</div></article></section></template>
