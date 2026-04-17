<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const enrollmentId = ref<number>(Number(route.params.id))
const learningPath = ref<any>(null)
const loading = ref(true)
const error = ref<string | null>(null)

const fetchLearningPath = async () => {
  try {
    loading.value = true
    const response = await axios.get(`/api/v1/education/learning-paths/${enrollmentId.value}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'X-Correlation-ID': crypto.randomUUID()
      }
    })
    learningPath.value = response.data
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load learning path'
  } finally {
    loading.value = false
  }
}

const progressPercent = computed(() => {
  if (!learningPath.value) return 0
  const completed = learningPath.value.modules.filter((m: any) => m.completed).length
  return Math.round((completed / learningPath.value.modules.length) * 100)
})

onMounted(() => {
  fetchLearningPath()
})
</script>

<template>
  <div class="learning-path-viewer">
    <div v-if="loading" class="loading">Loading learning path...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    <div v-else-if="learningPath" class="path-content">
      <div class="path-header">
        <h2>Personalized Learning Path</h2>
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: `${progressPercent}%` }"></div>
        </div>
        <span class="progress-text">{{ progressPercent }}% Complete</span>
      </div>

      <div class="modules-list">
        <div v-for="(module, index) in learningPath.modules" :key="module.id" class="module-card" :class="{ completed: module.completed }">
          <div class="module-header">
            <span class="module-number">{{ index + 1 }}</span>
            <h3>{{ module.title }}</h3>
            <span v-if="module.completed" class="status-badge completed">Completed</span>
            <span v-else class="status-badge pending">In Progress</span>
          </div>
          <p class="module-description">{{ module.description }}</p>
          <div class="module-meta">
            <span class="duration">{{ module.estimated_hours }}h</span>
            <span class="difficulty">{{ module.difficulty }}</span>
          </div>
        </div>
      </div>

      <div class="path-footer">
        <button @click="$router.push(`/courses/${learningPath.course_id}`)" class="btn-back">Back to Course</button>
        <button @click="adaptPath" class="btn-adapt">Adapt Path</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.learning-path-viewer {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem;
}

.path-header {
  margin-bottom: 2rem;
}

.progress-bar {
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
  margin: 1rem 0;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6);
  transition: width 0.3s ease;
}

.modules-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.module-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1.5rem;
  transition: all 0.2s;
}

.module-card.completed {
  border-color: #10b981;
  background: #f0fdf4;
}

.module-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.module-number {
  width: 32px;
  height: 32px;
  background: #3b82f6;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge.completed {
  background: #10b981;
  color: white;
}

.status-badge.pending {
  background: #f59e0b;
  color: white;
}

.module-meta {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.path-footer {
  display: flex;
  justify-content: space-between;
  margin-top: 2rem;
}

.btn-back, .btn-adapt {
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.btn-back {
  background: #e5e7eb;
  border: none;
}

.btn-adapt {
  background: #3b82f6;
  color: white;
  border: none;
}
</style>
