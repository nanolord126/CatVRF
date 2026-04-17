<template>
  <div class="communication-channels">
    <div class="header"><h2>Channels</h2><button @click="addChannel" class="btn-primary">Add</button></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Members</th><th>Status</th><th>Actions</th></tr></thead><tbody><tr v-for="c in channels" :key="c.id"><td>#{{ c.id }}</td><td>{{ c.name }}</td><td>{{ c.type }}</td><td>{{ c.members }}</td><td><span :class="['badge', c.status]">{{ c.status }}</span></td><td><button @click="view(c)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
interface C { id: number; name: string; type: string; members: string; status: string }
const channels = ref<C[]>([])
const addChannel = () => {}
const view = (c: C) => {}
onMounted(async () => { const res = await fetch('/api/communication/channels'); channels.value = await res.json() })
</script>
<style scoped>
.communication-channels { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.badge { padding: 4px 8px; border-radius: 4px; font-size: 12px }
.badge.active { background: #d1fae5; color: #065f46 }
.badge.inactive { background: #e5e7eb; color: #374151 }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
