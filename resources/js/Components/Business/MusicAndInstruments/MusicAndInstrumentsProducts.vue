<template>
  <div class="music-instruments">
    <div class="header"><h2>Instruments</h2><button @click="addInstrument" class="btn-primary">Add</button></div>
    <div class="filters"><select v-model="categoryFilter"><option value="">All Types</option><option value="guitar">Guitar</option><option value="piano">Piano</option><option value="drums">Drums</option></select></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead><tbody><tr v-for="i in filtered" :key="i.id"><td>#{{ i.id }}</td><td>{{ i.name }}</td><td>{{ i.type }}</td><td>{{ i.price }}</td><td>{{ i.stock }}</td><td><button @click="view(i)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
interface I { id: number; name: string; type: string; price: string; stock: number }
const list = ref<I[]>([])
const categoryFilter = ref('')
const filtered = computed(() => categoryFilter.value ? list.value.filter(x => x.type === categoryFilter.value) : list.value)
const addInstrument = () => {}
const view = (i: I) => {}
onMounted(async () => { const res = await fetch('/api/music/instruments'); list.value = await res.json() })
</script>
<style scoped>
.music-instruments { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.filters select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
