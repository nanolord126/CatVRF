<template>
  <div class="content-media">
    <div class="header">
      <h2>Media Library</h2>
      <button @click="uploadMedia" class="btn-primary">Upload Media</button>
    </div>

    <div class="filters">
      <select v-model="typeFilter">
        <option value="">All Types</option>
        <option value="image">Images</option>
        <option value="video">Videos</option>
        <option value="audio">Audio</option>
        <option value="document">Documents</option>
      </select>
      <select v-model="categoryFilter">
        <option value="">All Categories</option>
        <option value="marketing">Marketing</option>
        <option value="product">Product</option>
        <option value="blog">Blog</option>
        <option value="social">Social</option>
      </select>
    </div>

    <div class="media-grid">
      <div v-for="item in filteredMedia" :key="item.id" class="media-card">
        <div class="media-preview">
          <img v-if="item.type === 'image'" :src="item.thumbnail" :alt="item.name" />
          <div v-else class="media-placeholder">
            <span class="icon">{{ getIcon(item.type) }}</span>
          </div>
        </div>
        <div class="media-details">
          <h3>{{ item.name }}</h3>
          <p class="meta">{{ item.type }} • {{ formatSize(item.size) }}</p>
          <p class="date">{{ formatDate(item.uploaded_at) }}</p>
        </div>
        <div class="media-actions">
          <button @click="viewMedia(item)" class="btn-sm">View</button>
          <button @click="editMedia(item)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface MediaItem {
  id: number
  name: string
  type: string
  category: string
  size: number
  thumbnail: string
  uploaded_at: string
}

const media = ref<MediaItem[]>([])
const typeFilter = ref('')
const categoryFilter = ref('')

const filteredMedia = computed(() => {
  return media.value.filter(item => {
    if (typeFilter.value && item.type !== typeFilter.value) return false
    if (categoryFilter.value && item.category !== categoryFilter.value) return false
    return true
  })
})

const formatSize = (bytes: number): string => {
  if (bytes >= 1048576) {
    return (bytes / 1048576).toFixed(2) + ' MB'
  } else if (bytes >= 1024) {
    return (bytes / 1024).toFixed(2) + ' KB'
  }
  return bytes + ' B'
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const getIcon = (type: string): string => {
  const icons: Record<string, string> = {
    video: '🎬',
    audio: '🎵',
    document: '📄'
  }
  return icons[type] || '📁'
}

const uploadMedia = () => {
  // Open upload modal
}

const viewMedia = (item: MediaItem) => {
  // View media details
}

const editMedia = (item: MediaItem) => {
  // Edit media metadata
}

const fetchMedia = async () => {
  try {
    const response = await fetch('/api/content/media')
    const data = await response.json()
    media.value = data
  } catch (error) {
    console.error('Failed to fetch media:', error)
  }
}

onMounted(() => {
  fetchMedia()
})
</script>

<style scoped>
.content-media {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.filters select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.media-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
}

.media-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.media-preview {
  width: 100%;
  height: 180px;
  background: #f9fafb;
}

.media-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.media-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon {
  font-size: 48px;
}

.media-details {
  padding: 16px;
}

.media-details h3 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.meta {
  margin: 0 0 4px 0;
  font-size: 12px;
  color: #6b7280;
}

.date {
  margin: 0 0 12px 0;
  font-size: 11px;
  color: #9ca3af;
}

.media-actions {
  padding: 12px 16px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 8px;
}

.btn-sm {
  flex: 1;
  padding: 8px;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}
</style>
