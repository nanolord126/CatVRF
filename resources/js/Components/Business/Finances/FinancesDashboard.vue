<script setup>
const metrics=[{label:'Cashflow 30д',value:'42.8 млн ₽',trend:'+9.3%',icon:'💸'},{label:'Маржа',value:'18.4%',trend:'+1.1%',icon:'📊'},{label:'Payout SLA',value:'96.7%',trend:'+0.8%',icon:'🏦'},{label:'Chargeback',value:'0.7%',trend:'-0.1%',icon:'🛡️'}];
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="i in metrics" :key="i.label" class="rounded-2xl border p-4" style="background:var(--t-surface);border-color:var(--t-border);"><div class="text-xs" style="color:var(--t-text-3);">{{i.label}}</div><div class="mt-2 text-xl font-bold" style="color:var(--t-text);">{{i.value}}</div><div class="mt-1 text-xs" style="color:var(--t-primary);">{{i.trend}} · {{i.icon}}</div></article></section></template>
