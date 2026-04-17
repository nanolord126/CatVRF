<template>
  <div class="pharmacy-prescriptions">
    <div class="header"><h2>Prescriptions</h2><button @click="addPrescription" class="btn-primary">Add</button></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead><tbody><tr v-for="p in prescriptions" :key="p.id"><td>#{{ p.id }}</td><td>{{ p.patient }}</td><td>{{ p.doctor }}</td><td>{{ p.date }}</td><td><span :class="['badge', p.status]">{{ p.status }}</span></td><td><button @click="view(p)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
interface P { id: number; patient: string; doctor: string; date: string; status: string }
const prescriptions = ref<P[]>([])
const addPrescription = () => {}
const view = (p: P) => {}
onMounted(async () => { const res = await fetch('/api/pharmacy/prescriptions'); prescriptions.value = await res.json() })
</script>
<style scoped>
.pharmacy-prescriptions { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.badge { padding: 4px 8px; border-radius: 4px; font-size: 12px }
.badge.pending { background: #fef3c7; color: #92400e }
.badge.filled { background: #d1fae5; color: #065f46 }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
