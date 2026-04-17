<template>
  <div class="vet-appointments">
    <div class="header">
      <h2>Appointments</h2>
      <button @click="addAppointment" class="btn-primary">Add</button>
    </div>
    <div class="filters">
      <select v-model="statusFilter"><option value="">All Status</option><option value="scheduled">Scheduled</option><option value="in_progress">In Progress</option><option value="completed">Completed</option></select>
    </div>
    <div class="table">
      <table>
        <thead><tr><th>ID</th><th>Pet</th><th>Owner</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <tr v-for="a in filtered" :key="a.id">
            <td>#{{ a.id }}</td><td>{{ a.pet }}</td><td>{{ a.owner }}</td><td>{{ a.date }}</td>
            <td><span :class="['badge', a.status]">{{ a.status }}</span></td>
            <td><button @click="view(a)" class="btn-sm">View</button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
interface A { id: number; pet: string; owner: string; date: string; status: string }
const list = ref<A[]>([])
const statusFilter = ref('')
const filtered = computed(() => statusFilter.value ? list.value.filter(x => x.status === statusFilter.value) : list.value)
const addAppointment = () => {}
const view = (a: A) => {}
onMounted(async () => { const res = await fetch('/api/vet/appointments'); list.value = await res.json() })
</script>

<style scoped>
.vet-appointments { padding: 20px }
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
