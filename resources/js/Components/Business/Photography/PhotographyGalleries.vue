<template>
  <div class="photography-galleries">
    <div class="header"><h2>Galleries</h2><button @click="addGallery" class="btn-primary">Add</button></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Name</th><th>Photos</th><th>Date</th><th>Actions</th></tr></thead><tbody><tr v-for="g in galleries" :key="g.id"><td>#{{ g.id }}</td><td>{{ g.name }}</td><td>{{ g.photos }}</td><td>{{ g.date }}</td><td><button @click="view(g)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
interface G { id: number; name: string; photos: string; date: string }
const galleries = ref<G[]>([])
const addGallery = () => {}
const view = (g: G) => {}
onMounted(async () => { const res = await fetch('/api/photography/galleries'); galleries.value = await res.json() })
</script>
<style scoped>
.photography-galleries { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
