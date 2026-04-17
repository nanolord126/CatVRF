<template>
  <div class="art-artworks">
    <div class="header"><h2>Artworks</h2><button @click="addArtwork" class="btn-primary">Add</button></div>
    <div class="filters"><select v-model="categoryFilter"><option value="">All Categories</option><option value="painting">Painting</option><option value="sculpture">Sculpture</option><option value="digital">Digital</option></select></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Title</th><th>Artist</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead><tbody><tr v-for="a in filtered" :key="a.id"><td>#{{ a.id }}</td><td>{{ a.title }}</td><td>{{ a.artist }}</td><td>{{ a.price }}</td><td><span :class="['badge', a.status]">{{ a.status }}</span></td><td><button @click="view(a)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
interface A { id: number; title: string; artist: string; price: string; status: string }
const list = ref<A[]>([])
const categoryFilter = ref('')
const filtered = computed(() => categoryFilter.value ? list.value.filter(x => x.status === categoryFilter.value) : list.value)
const addArtwork = () => {}
const view = (a: A) => {}
onMounted(async () => { const res = await fetch('/api/art/artworks'); list.value = await res.json() })
</script>
<style scoped>
.art-artworks { padding: 20px }
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
