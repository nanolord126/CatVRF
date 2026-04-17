<template>
  <div class="flowers-bouquets">
    <div class="header"><h2>Bouquets</h2><button @click="addBouquet" class="btn-primary">Add</button></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Name</th><th>Price</th><th>Flowers</th><th>Actions</th></tr></thead><tbody><tr v-for="b in bouquets" :key="b.id"><td>#{{ b.id }}</td><td>{{ b.name }}</td><td>{{ b.price }}</td><td>{{ b.flowers }}</td><td><button @click="view(b)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
interface B { id: number; name: string; price: string; flowers: string }
const bouquets = ref<B[]>([])
const addBouquet = () => {}
const view = (b: B) => {}
onMounted(async () => { const res = await fetch('/api/flowers/bouquets'); bouquets.value = await res.json() })
</script>
<style scoped>
.flowers-bouquets { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
