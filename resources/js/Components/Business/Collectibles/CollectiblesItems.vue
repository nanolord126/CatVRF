<template>
  <div class="collectibles-items">
    <div class="header"><h2>Items</h2><button @click="addItem" class="btn-primary">Add</button></div>
    <div class="filters"><select v-model="categoryFilter"><option value="">All Categories</option><option value="coins">Coins</option><option value="stamps">Stamps</option><option value="art">Art</option></select></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Value</th><th>Status</th><th>Actions</th></tr></thead><tbody><tr v-for="i in filtered" :key="i.id"><td>#{{ i.id }}</td><td>{{ i.name }}</td><td>{{ i.category }}</td><td>{{ i.value }}</td><td><span :class="['badge', i.status]">{{ i.status }}</span></td><td><button @click="view(i)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
interface I { id: number; name: string; category: string; value: string; status: string }
const list = ref<I[]>([])
const categoryFilter = ref('')
const filtered = computed(() => categoryFilter.value ? list.value.filter(x => x.category === categoryFilter.value) : list.value)
const addItem = () => {}
const view = (i: I) => {}
onMounted(async () => { const res = await fetch('/api/collectibles/items'); list.value = await res.json() })
</script>
<style scoped>
.collectibles-items { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.filters select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.badge { padding: 4px 8px; border-radius: 4px; font-size: 12px }
.badge.available { background: #d1fae5; color: #065f46 }
.badge.sold { background: #e5e7eb; color: #374151 }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
