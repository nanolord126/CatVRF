<template>
  <div class="confectionery-orders">
    <div class="header"><h2>Orders</h2><button @click="addOrder" class="btn-primary">Add</button></div>
    <div class="filters"><select v-model="statusFilter"><option value="">All Status</option><option value="pending">Pending</option><option value="ready">Ready</option></select></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead><tbody><tr v-for="o in filtered" :key="o.id"><td>#{{ o.id }}</td><td>{{ o.customer }}</td><td>{{ o.items }}</td><td>{{ o.total }}</td><td><span :class="['badge', o.status]">{{ o.status }}</span></td><td><button @click="view(o)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
interface O { id: number; customer: string; items: string; total: string; status: string }
const list = ref<O[]>([])
const statusFilter = ref('')
const filtered = computed(() => statusFilter.value ? list.value.filter(x => x.status === statusFilter.value) : list.value)
const addOrder = () => {}
const view = (o: O) => {}
onMounted(async () => { const res = await fetch('/api/confectionery/orders'); list.value = await res.json() })
</script>
<style scoped>
.confectionery-orders { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.filters select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.badge { padding: 4px 8px; border-radius: 4px; font-size: 12px }
.badge.pending { background: #fef3c7; color: #92400e }
.badge.ready { background: #d1fae5; color: #065f46 }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
