<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import '@google/model-viewer'

interface Model3D {
  id: number
  title: string
  model_url: string
  thumbnail_url: string
  ar_enabled: boolean
  vr_enabled: boolean
}

const props = defineProps<{
  courseId: number
  moduleId: number
}>()

const models = ref<Model3D[]>([])
const loading = ref(true)
const selectedModel = ref<Model3D | null>(null)
const isARSupported = ref(false)
const isVRSupported = ref(false)
const error = ref<string | null>(null)

const fetchModels = async () => {
  try {
    loading.value = true
    const response = await axios.get(`/api/v1/education/arvr/models/${props.moduleId}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'X-Correlation-ID': crypto.randomUUID()
      }
    })
    models.value = response.data
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load 3D models'
  } finally {
    loading.value = false
  }
}

const selectModel = (model: Model3D) => {
  selectedModel.value = model
}

const checkARSupport = () => {
  isARSupported.value = 'xr' in navigator
}

const checkVRSupport = () => {
  isVRSupported.value = navigator.xr !== undefined && navigator.xr.isSessionSupported !== undefined
}

const launchAR = async () => {
  if (!selectedModel.value || !isARSupported.value) return
  
  try {
    const viewer = document.querySelector('model-viewer') as any
    if (viewer && viewer.enterAR) {
      await viewer.enterAR()
    }
  } catch (err) {
    console.error('AR launch failed:', err)
  }
}

const launchVR = async () => {
  if (!selectedModel.value || !isVRSupported.value) return
  
  try {
    const viewer = document.querySelector('model-viewer') as any
    if (viewer && viewer.enterVR) {
      await viewer.enterVR()
    }
  } catch (err) {
    console.error('VR launch failed:', err)
  }
}

onMounted(() => {
  checkARSupport()
  checkVRSupport()
  fetchModels()
})

onUnmounted(() => {
  selectedModel.value = null
})
</script>

<template>
  <div class="arvr-practice">
    <h2>AR/VR Practice</h2>
    
    <div v-if="loading" class="loading">Loading 3D models...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    
    <div v-else class="arvr-container">
      <div class="models-sidebar">
        <h3>Available Models</h3>
        <div class="models-list">
          <div 
            v-for="model in models" 
            :key="model.id"
            class="model-item"
            :class="{ active: selectedModel?.id === model.id }"
            @click="selectModel(model)"
          >
            <img :src="model.thumbnail_url" :alt="model.title" class="model-thumbnail" />
            <div class="model-info">
              <h4>{{ model.title }}</h4>
              <div class="model-badges">
                <span v-if="model.ar_enabled" class="badge ar">AR</span>
                <span v-if="model.vr_enabled" class="badge vr">VR</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="model-viewer-container" v-if="selectedModel">
        <model-viewer
          :src="selectedModel.model_url"
          :alt="selectedModel.title"
          auto-rotate
          camera-controls
          ar
          vr
          class="model-viewer"
        >
          <slot></slot>
        </model-viewer>

        <div class="viewer-controls">
          <button 
            v-if="selectedModel.ar_enabled && isARSupported"
            @click="launchAR"
            class="btn-control btn-ar"
          >
            Launch AR
          </button>
          <button 
            v-if="selectedModel.vr_enabled && isVRSupported"
            @click="launchVR"
            class="btn-control btn-vr"
          >
            Launch VR
          </button>
          <button @click="selectedModel = null" class="btn-control btn-close">
            Close
          </button>
        </div>

        <div class="viewer-info">
          <h3>{{ selectedModel.title }}</h3>
          <p>Use mouse to rotate, scroll to zoom, right-click to pan</p>
          <p v-if="!isARSupported && selectedModel.ar_enabled" class="warning">
            AR is not supported on this device
          </p>
          <p v-if="!isVRSupported && selectedModel.vr_enabled" class="warning">
            VR is not supported on this device
          </p>
        </div>
      </div>

      <div v-else class="empty-state">
        <p>Select a model to view in 3D</p>
      </div>
    </div>

    <div class="capabilities">
      <div class="capability-item" :class="{ supported: isARSupported }">
        <span class="icon">📱</span>
        <span>AR Support: {{ isARSupported ? 'Available' : 'Not Available' }}</span>
      </div>
      <div class="capability-item" :class="{ supported: isVRSupported }">
        <span class="icon">🥽</span>
        <span>VR Support: {{ isVRSupported ? 'Available' : 'Not Available' }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.arvr-practice {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

.arvr-container {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 2rem;
  margin-top: 2rem;
}

.models-sidebar {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  border: 1px solid #e5e7eb;
}

.models-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-top: 1rem;
}

.model-item {
  display: flex;
  gap: 1rem;
  padding: 0.75rem;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.model-item:hover {
  background: #f3f4f6;
}

.model-item.active {
  background: #eff6ff;
  border: 2px solid #3b82f6;
}

.model-thumbnail {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 4px;
}

.model-info h4 {
  margin: 0 0 0.5rem 0;
  font-size: 0.875rem;
}

.model-badges {
  display: flex;
  gap: 0.5rem;
}

.badge {
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.625rem;
  font-weight: 600;
}

.badge.ar {
  background: #dbeafe;
  color: #1e40af;
}

.badge.vr {
  background: #fce7f3;
  color: #9d174d;
}

.model-viewer-container {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  border: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
}

.model-viewer {
  width: 100%;
  height: 500px;
  border-radius: 8px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.viewer-controls {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}

.btn-control {
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  border: none;
}

.btn-ar {
  background: #3b82f6;
  color: white;
}

.btn-vr {
  background: #8b5cf6;
  color: white;
}

.btn-close {
  background: #e5e7eb;
}

.viewer-info {
  margin-top: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 6px;
}

.viewer-info h3 {
  margin: 0 0 0.5rem 0;
}

.viewer-info p {
  margin: 0.25rem 0;
  color: #6b7280;
  font-size: 0.875rem;
}

.warning {
  color: #dc2626;
}

.empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 500px;
  background: #f9fafb;
  border-radius: 8px;
  border: 2px dashed #e5e7eb;
}

.capabilities {
  display: flex;
  gap: 2rem;
  margin-top: 2rem;
}

.capability-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  background: white;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.capability-item.supported {
  border-color: #10b981;
  background: #f0fdf4;
}

.capability-item .icon {
  font-size: 1.5rem;
}
</style>
