<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Metric {
  label: string
  value: string
  trend: string
  icon: string
}

const metrics = ref<Metric[]>([
  { label: 'Активных курсов', value: '412', trend: '+8.0%', icon: '🎓' },
  { label: 'Студентов', value: '28 410', trend: '+10.4%', icon: '👩‍🎓' },
  { label: 'Completion rate', value: '63%', trend: '+2.8%', icon: '✅' },
  { label: 'NPS', value: '69', trend: '+2', icon: '⭐' },
])

const fetchMetrics = async () => {
  try {
    const response = await fetch('/api/education/metrics')
    const data = await response.json()
    metrics.value = data
  } catch (error) {
    console.error('Failed to fetch metrics:', error)
  }
}

onMounted(() => {
  fetchMetrics()
})
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="item in metrics" :key="item.label" class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);"><div class="text-xs" style="color: var(--t-text-3);">{{ item.label }}</div><div class="mt-2 text-xl font-bold" style="color: var(--t-text);">{{ item.value }}</div><div class="mt-1 text-xs" style="color: var(--t-primary);">{{ item.trend }} · {{ item.icon }}</div></article></section></template>
