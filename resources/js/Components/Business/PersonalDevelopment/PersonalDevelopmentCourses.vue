<template>
  <div class="pd-courses">
    <div class="header"><h2>Courses</h2><button @click="addCourse" class="btn-primary">Add</button></div>
    <div class="table"><table><thead><tr><th>ID</th><th>Title</th><th>Category</th><th>Price</th><th>Actions</th></tr></thead><tbody><tr v-for="c in courses" :key="c.id"><td>#{{ c.id }}</td><td>{{ c.title }}</td><td>{{ c.category }}</td><td>{{ c.price }}</td><td><button @click="view(c)" class="btn-sm">View</button></td></tr></tbody></table></div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
interface C { id: number; title: string; category: string; price: string }
const courses = ref<C[]>([])
const addCourse = () => {}
const view = (c: C) => {}
onMounted(async () => { const res = await fetch('/api/personaldevelopment/courses'); courses.value = await res.json() })
</script>
<style scoped>
.pd-courses { padding: 20px }
.header { display: flex; justify-content: space-between; margin-bottom: 20px }
.btn-primary { background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer }
.table { background: white; border-radius: 8px; overflow: hidden }
table { width: 100%; border-collapse: collapse }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb }
.btn-sm { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer }
</style>
