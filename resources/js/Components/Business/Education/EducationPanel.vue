<script setup lang="ts">
import { ref } from 'vue'
import VTabs from '../../UI/VTabs.vue'
import VCard from '../../UI/VCard.vue'
import VBadge from '../../UI/VBadge.vue'
import EducationDashboard from './EducationDashboard.vue'

interface Tab {
  key: string
  label: string
  icon: string
}

const activeTab = ref<string>('dashboard')
const tabs: Tab[] = [
  { key: 'dashboard', label: 'Дашборд', icon: '📊' },
  { key: 'courses', label: 'Курсы', icon: '📚' },
  { key: 'students', label: 'Студенты', icon: '👥' }
]
</script>
<template><section class="space-y-4"><header class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-bold" style="color: var(--t-text);">Education Panel</h2><p class="text-sm" style="color: var(--t-text-3);">Курсы, прогресс и учебная аналитика.</p></div><VBadge text="PRODUCTION" variant="success" size="sm" dot /></header><VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" /><EducationDashboard v-if="activeTab === 'dashboard'" /><VCard v-else-if="activeTab === 'courses'" title="Курсы"><div class="text-sm" style="color: var(--t-text-2);">Каталог курсов, прайсинг, retention.</div></VCard><VCard v-else title="Студенты"><div class="text-sm" style="color: var(--t-text-2);">Вовлечение, completion, churn risk.</div></VCard></section></template>
