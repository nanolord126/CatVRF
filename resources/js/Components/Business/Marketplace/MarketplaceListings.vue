<template>
  <div class="marketplace-listings">
    <div class="header"><h2>Listings</h2><button @click="addListing" class="btn-primary">Add</button></div>
    <div class="filters"><select v-model="statusFilter"><option value="">All Status</option><option value="active">Active</option><option value="sold">Sold</option></select></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Title</th><th>Seller</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead><tbody><tr v-for="l in filtered" :key="l.id"><td>#{{ l.id }}</td><td>{{ l.title }}</td><td>{{ l.seller }}</td><td>{{ l.price }}</td><td><span :class="['badge', l.status]">{{ l.status }}</span></td><td><button @click="view(l)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
interface L { id: number; title: string; seller: string; price: string; status: string }
const list = ref<L[]>([])
const statusFilter = ref('')
const filtered = computed(() => statusFilter.value ? list.value.filter(x => x.status === statusFilter.value) : list.value)
const addListing = () => {}
const view = (l: L) => {}
onMounted(async () => { const res = await fetch('/api/marketplace/listings'); list.value = await res.json() })
</script>
<style scoped>
.marketplace-listings { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.filters select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.badge { padding: 4px 8px; border-radius: 4px; font-size: 12px }
.badge.active { background: #d1fae5; color: #065f46 }
.badge.sold { background: #e5e7eb; color: #374151 }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
