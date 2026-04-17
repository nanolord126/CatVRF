<template>
  <div class="vet-patients">
    <div class="header">
      <h2>Patients</h2>
      <button @click="addPatient" class="btn-primary">Add</button>
    </div>
    <div class="table">
      <table>
        <thead><tr><th>ID</th><th>Name</th><th>Species</th><th>Breed</th><th>Owner</th><th>Actions</th></tr></thead>
        <tbody>
          <tr v-for="p in patients" :key="p.id">
            <td>#{{ p.id }}</td><td>{{ p.name }}</td><td>{{ p.species }}</td><td>{{ p.breed }}</td><td>{{ p.owner }}</td>
            <td><button @click="view(p)" class="btn-sm">View</button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
interface P { id: number; name: string; species: string; breed: string; owner: string }
const patients = ref<P[]>([])
const addPatient = () => {}
const view = (p: P) => {}
onMounted(async () => { const res = await fetch('/api/vet/patients'); patients.value = await res.json() })
</script>

<style scoped>
.vet-patients { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
