<template>
  <div class="construction-projects">
    <div class="header"><h2>Projects</h2><button @click="addProject" class="btn-primary">Add</button></div>
    <div class="filters"><select v-model="statusFilter"><option value="">All Status</option><option value="planning">Planning</option><option value="in_progress">In Progress</option><option value="completed">Completed</option></select></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Budget</th><th>Status</th><th>Actions</th></tr></thead><tbody><tr v-for="p in filtered" :key="p.id"><td>#{{ p.id }}</td><td>{{ p.name }}</td><td>{{ p.type }}</td><td>{{ p.budget }}</td><td><span :class="['badge', p.status]">{{ p.status }}</span></td><td><button @click="view(p)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
interface P { id: number; name: string; type: string; budget: string; status: string }
const list = ref<P[]>([])
const statusFilter = ref('')
const filtered = computed(() => statusFilter.value ? list.value.filter(x => x.status === statusFilter.value) : list.value)
const addProject = () => {}
const view = (p: P) => {}
onMounted(async () => { const res = await fetch('/api/construction/projects'); list.value = await res.json() })
</script>
<style scoped>
.construction-projects { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.filters select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.badge { padding: 4px 8px; border-radius: 4px; font-size: 12px }
.badge.planning { background: #dbeafe; color: #1e40af }
.badge.in_progress { background: #fef3c7; color: #92400e }
.badge.completed { background: #d1fae5; color: #065f46 }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
