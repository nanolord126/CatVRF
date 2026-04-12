<script setup>
const metrics=[{label:'Активных задач',value:'1 842',trend:'+7.4%',icon:'💻'},{label:'Исполнителей online',value:'612',trend:'+5.1%',icon:'🧑‍💻'},{label:'SLA дедлайна',value:'93.7%',trend:'+1.2%',icon:'📆'},{label:'Диспуты',value:'1.6%',trend:'-0.2%',icon:'🛡️'}];
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="i in metrics" :key="i.label" class="rounded-2xl border p-4" style="background:var(--t-surface);border-color:var(--t-border);"><div class="text-xs" style="color:var(--t-text-3);">{{i.label}}</div><div class="mt-2 text-xl font-bold" style="color:var(--t-text);">{{i.value}}</div><div class="mt-1 text-xs" style="color:var(--t-primary);">{{i.trend}} · {{i.icon}}</div></article></section></template>
