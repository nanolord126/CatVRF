<template>
  <div class="geo-locations">
    <div class="header"><h2>Locations</h2><button @click="addLocation" class="btn-primary">Add</button></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Name</th><th>Address</th><th>Type</th><th>Actions</th></tr></thead><tbody><tr v-for="l in locations" :key="l.id"><td>#{{ l.id }}</td><td>{{ l.name }}</td><td>{{ l.address }}</td><td>{{ l.type }}</td><td><button @click="view(l)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
interface L { id: number; name: string; address: string; type: string }
const locations = ref<L[]>([])
const addLocation = () => {}
const view = (l: L) => {}
onMounted(async () => { const res = await fetch('/api/geo/locations'); locations.value = await res.json() })
</script>
<style scoped>
.geo-locations { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
