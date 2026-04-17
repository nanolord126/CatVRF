<template>
  <div class="str-bookings">
    <div class="header"><h2>Bookings</h2><button @click="addBooking" class="btn-primary">Add</button></div>
    <div class="filters"><select v-model="statusFilter"><option value="">All Status</option><option value="confirmed">Confirmed</option><option value="completed">Completed</option></select></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Guest</th><th>Property</th><th>Dates</th><th>Status</th><th>Actions</th></tr></thead><tbody><tr v-for="b in filtered" :key="b.id"><td>#{{ b.id }}</td><td>{{ b.guest }}</td><td>{{ b.property }}</td><td>{{ b.dates }}</td><td><span :class="['badge', b.status]">{{ b.status }}</span></td><td><button @click="view(b)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
interface B { id: number; guest: string; property: string; dates: string; status: string }
const list = ref<B[]>([])
const statusFilter = ref('')
const filtered = computed(() => statusFilter.value ? list.value.filter(x => x.status === statusFilter.value) : list.value)
const addBooking = () => {}
const view = (b: B) => {}
onMounted(async () => { const res = await fetch('/api/str/bookings'); list.value = await res.json() })
</script>
<style scoped>
.str-bookings { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.filters select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.badge { padding: 4px 8px; border-radius: 4px; font-size: 12px }
.badge.confirmed { background: #d1fae5; color: #065f46 }
.badge.completed { background: #e5e7eb; color: #374151 }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
