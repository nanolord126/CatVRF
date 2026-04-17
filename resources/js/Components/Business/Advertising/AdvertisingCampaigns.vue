<template>
  <div class="ad-campaigns">
    <div class="header"><h2>Campaigns</h2><button @click="addCampaign" class="btn-primary">Add</button></div>
    <div class="filters"><select v-model="statusFilter"><option value="">All Status</option><option value="active">Active</option><option value="paused">Paused</option><option value="completed">Completed</option></select></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Name</th><th>Budget</th><th>Impressions</th><th>Status</th><th>Actions</th></tr></thead><tbody><tr v-for="c in filtered" :key="c.id"><td>#{{ c.id }}</td><td>{{ c.name }}</td><td>{{ c.budget }}</td><td>{{ c.impressions }}</td><td><span :class="['badge', c.status]">{{ c.status }}</span></td><td><button @click="view(c)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
interface C { id: number; name: string; budget: string; impressions: string; status: string }
const list = ref<C[]>([])
const statusFilter = ref('')
const filtered = computed(() => statusFilter.value ? list.value.filter(x => x.status === statusFilter.value) : list.value)
const addCampaign = () => {}
const view = (c: C) => {}
onMounted(async () => { const res = await fetch('/api/advertising/campaigns'); list.value = await res.json() })
</script>
<style scoped>
.ad-campaigns { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.filters select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.badge { padding: 4px 8px; border-radius: 4px; font-size: 12px }
.badge.active { background: #d1fae5; color: #065f46 }
.badge.paused { background: #fef3c7; color: #92400e }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
