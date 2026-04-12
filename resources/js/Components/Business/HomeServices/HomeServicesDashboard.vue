<script setup>
const metrics=[{label:'Заявок',value:'1 164',trend:'+9.0%',icon:'🏠'},{label:'Мастеров online',value:'324',trend:'+4.7%',icon:'👷'},{label:'First visit fix',value:'88%',trend:'+1.5%',icon:'✅'},{label:'Повторные вызовы',value:'5.2%',trend:'-0.6%',icon:'🔁'}];
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="i in metrics" :key="i.label" class="rounded-2xl border p-4" style="background:var(--t-surface);border-color:var(--t-border);"><div class="text-xs" style="color:var(--t-text-3);">{{i.label}}</div><div class="mt-2 text-xl font-bold" style="color:var(--t-text);">{{i.value}}</div><div class="mt-1 text-xs" style="color:var(--t-primary);">{{i.trend}} · {{i.icon}}</div></article></section></template>
