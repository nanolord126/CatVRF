<template>
  <div class="pharmacy-medications">
    <div class="header"><h2>Medications</h2><button @click="addMedication" class="btn-primary">Add</button></div>
    <div class="filters"><select v-model="categoryFilter"><option value="">All Categories</option><option value="prescription">Prescription</option><option value="otc">OTC</option><option value="supplements">Supplements</option></select></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Stock</th><th>Actions</th></tr></thead><tbody><tr v-for="m in filtered" :key="m.id"><td>#{{ m.id }}</td><td>{{ m.name }}</td><td>{{ m.category }}</td><td>{{ m.stock }}</td><td><button @click="view(m)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
interface M { id: number; name: string; category: string; stock: number }
const list = ref<M[]>([])
const categoryFilter = ref('')
const filtered = computed(() => categoryFilter.value ? list.value.filter(x => x.category === categoryFilter.value) : list.value)
const addMedication = () => {}
const view = (m: M) => {}
onMounted(async () => { const res = await fetch('/api/pharmacy/medications'); list.value = await res.json() })
</script>
<style scoped>
.pharmacy-medications { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.filters select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
