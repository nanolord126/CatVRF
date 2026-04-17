<template>
  <div class="geologistics-routes">
    <div class="header"><h2>Routes</h2><button @click="addRoute" class="btn-primary">Add</button></div>
    <div class="filters"><select v-model="statusFilter"><option value="">All Status</option><option value="active">Active</option><option value="completed">Completed</option></select></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Origin</th><th>Destination</th><th>Distance</th><th>Status</th><th>Actions</th></tr></thead><tbody><tr v-for="r in filtered" :key="r.id"><td>#{{ r.id }}</td><td>{{ r.origin }}</td><td>{{ r.destination }}</td><td>{{ r.distance }}</td><td><span :class="['badge', r.status]">{{ r.status }}</span></td><td><button @click="view(r)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
interface R { id: number; origin: string; destination: string; distance: string; status: string }
const list = ref<R[]>([])
const statusFilter = ref('')
const filtered = computed(() => statusFilter.value ? list.value.filter(x => x.status === statusFilter.value) : list.value)
const addRoute = () => {}
const view = (r: R) => {}
onMounted(async () => { const res = await fetch('/api/geologistics/routes'); list.value = await res.json() })
</script>
<style scoped>
.geologistics-routes { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.filters select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.badge { padding: 4px 8px; border-radius: 4px; font-size: 12px }
.badge.active { background: #dbeafe; color: #1e40af }
.badge.completed { background: #d1fae5; color: #065f46 }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
