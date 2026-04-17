<template>
  <div class="office-menus">
    <div class="header"><h2>Menus</h2><button @click="addMenu" class="btn-primary">Add</button></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Price</th><th>Actions</th></tr></thead><tbody><tr v-for="m in menus" :key="m.id"><td>#{{ m.id }}</td><td>{{ m.name }}</td><td>{{ m.type }}</td><td>{{ m.price }}</td><td><button @click="view(m)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
interface M { id: number; name: string; type: string; price: string }
const menus = ref<M[]>([])
const addMenu = () => {}
const view = (m: M) => {}
onMounted(async () => { const res = await fetch('/api/officecatering/menus'); menus.value = await res.json() })
</script>
<style scoped>
.office-menus { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
