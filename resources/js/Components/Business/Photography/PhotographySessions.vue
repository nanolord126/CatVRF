<template>
  <div class="photography-sessions">
    <div class="header"><h2>Sessions</h2><button @click="addSession" class="btn-primary">Add</button></div>
    <div class="filters"><select v-model="statusFilter"><option value="">All Status</option><option value="scheduled">Scheduled</option><option value="completed">Completed</option></select></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Client</th><th>Type</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead><tbody><tr v-for="s in filtered" :key="s.id"><td>#{{ s.id }}</td><td>{{ s.client }}</td><td>{{ s.type }}</td><td>{{ s.date }}</td><td><span :class="['badge', s.status]">{{ s.status }}</span></td><td><button @click="view(s)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
interface S { id: number; client: string; type: string; date: string; status: string }
const list = ref<S[]>([])
const statusFilter = ref('')
const filtered = computed(() => statusFilter.value ? list.value.filter(x => x.status === statusFilter.value) : list.value)
const addSession = () => {}
const view = (s: S) => {}
onMounted(async () => { const res = await fetch('/api/photography/sessions'); list.value = await res.json() })
</script>
<style scoped>
.photography-sessions { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.filters select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.badge { padding: 4px 8px; border-radius: 4px; font-size: 12px }
.badge.scheduled { background: #dbeafe; color: #1e40af }
.badge.completed { background: #d1fae5; color: #065f46 }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
