<template>
  <div class="fashion-photo-validator">
    <div class="validator-header">
      <h3>📷 Валидация качества фото</h3>
      <p class="subtitle">Критерии качества для Fashion вертикали</p>
    </div>

    <div class="upload-section">
      <div
        class="upload-zone"
        :class="{ 'drag-over': isDragOver, 'has-image': !!previewUrl }"
        @dragover.prevent="isDragOver = true"
        @dragleave.prevent="isDragOver = false"
        @drop.prevent="handleDrop"
        @click="triggerFileInput"
      >
        <input
          ref="fileInput"
          type="file"
          accept="image/*"
          @change="handleFileSelect"
          style="display: none"
        />
        <img v-if="previewUrl" :src="previewUrl" alt="Preview" class="preview-image" />
        <div v-else class="upload-placeholder">
          <div class="upload-icon">📁</div>
          <p>Перетащите фото сюда или нажмите для выбора</p>
          <p class="upload-hint">PNG, JPG, WEBP (макс. 10MB)</p>
        </div>
      </div>
    </div>

    <div v-if="validationResult" class="validation-results">
      <div class="overall-score">
        <div :class="['score-circle', validationResult.level]">
          <span class="score-number">{{ validationResult.score }}</span>
          <span class="score-label">/100</span>
        </div>
        <div class="score-text">
          <h4>{{ validationResult.statusText }}</h4>
          <p>{{ validationResult.stars }}</p>
        </div>
      </div>

      <div class="criteria-breakdown">
        <h4>Детальный анализ критериев</h4>
        <div class="criteria-list">
          <div
            v-for="criterion in validationResult.criteria"
            :key="criterion.name"
            :class="['criterion-item', criterion.status]"
          >
            <div class="criterion-header">
              <span class="criterion-icon">{{ criterion.icon }}</span>
              <span class="criterion-name">{{ criterion.name }}</span>
              <span class="criterion-score">{{ criterion.score }}/{{ criterion.maxScore }}</span>
            </div>
            <div class="criterion-bar">
              <div
                class="criterion-progress"
                :style="{ width: `${(criterion.score / criterion.maxScore) * 100}%` }"
              />
            </div>
            <p class="criterion-message">{{ criterion.message }}</p>
          </div>
        </div>
      </div>

      <div v-if="validationResult.warnings.length > 0" class="warnings">
        <h4>⚠️ Рекомендации</h4>
        <ul>
          <li v-for="warning in validationResult.warnings" :key="warning">{{ warning }}</li>
        </ul>
      </div>

      <div class="image-specs">
        <h4>📊 Характеристики изображения</h4>
        <div class="specs-grid">
          <div class="spec-item">
            <span class="spec-label">Разрешение:</span>
            <span class="spec-value">{{ validationResult.specs.resolution }}</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Соотношение сторон:</span>
            <span class="spec-value">{{ validationResult.specs.aspectRatio }}</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Размер файла:</span>
            <span class="spec-value">{{ validationResult.specs.fileSize }}</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Формат:</span>
            <span class="spec-value">{{ validationResult.specs.format }}</span>
          </div>
        </div>
      </div>

      <div class="validator-actions">
        <button @click="resetValidator" class="btn btn-secondary">
          🔄 Сбросить
        </button>
        <button
          @click="$emit('approve', validationResult)"
          class="btn btn-primary"
          :disabled="validationResult.score < 50"
        >
          ✓ Одобрить
        </button>
      </div>
    </div>

    <div v-else class="criteria-reference">
      <h4>📋 Критерии качества фото для Fashion</h4>
      <div class="reference-grid">
        <div class="reference-card">
          <div class="reference-icon">📐</div>
          <h5>Разрешение</h5>
          <p>Минимум: 800x800px<br>Рекомендуется: 1200x1200px+<br>Оптимально: 1500x1500px+</p>
        </div>
        <div class="reference-card">
          <div class="reference-icon">⚖️</div>
          <h5>Пропорции</h5>
          <p>Идеально: 1:1 (квадрат)<br>Допустимо: 3:4 или 4:3<br>Минимально: 2:3 или 3:2</p>
        </div>
        <div class="reference-card">
          <div class="reference-icon">💾</div>
          <h5>Размер файла</h5>
          <p>Минимум: 50KB<br>Оптимально: 100-500KB<br>Максимум: 2MB</p>
        </div>
        <div class="reference-card">
          <div class="reference-icon">🎨</div>
          <h5>Формат</h5>
          <p>Рекомендуется: WEBP<br>Допустимо: JPG, PNG<br>Избегать: BMP, TIFF</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface ValidationCriterion {
  name: string
  icon: string
  score: number
  maxScore: number
  status: 'excellent' | 'good' | 'fair' | 'poor'
  message: string
}

interface ValidationResult {
  score: number
  level: 'high' | 'medium' | 'low'
  statusText: string
  stars: string
  criteria: ValidationCriterion[]
  warnings: string[]
  specs: {
    resolution: string
    aspectRatio: string
    fileSize: string
    format: string
  }
}

const emit = defineEmits<{
  'validate': [result: ValidationResult]
  'approve': [result: ValidationResult]
}>()

const fileInput = ref<HTMLInputElement | null>(null)
const isDragOver = ref(false)
const previewUrl = ref<string | null>(null)
const validationResult = ref<ValidationResult | null>(null)

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    processFile(file)
  }
}

const handleDrop = (event: DragEvent) => {
  isDragOver.value = false
  const file = event.dataTransfer?.files[0]
  if (file && file.type.startsWith('image/')) {
    processFile(file)
  }
}

const processFile = (file: File) => {
  // Проверка размера файла
  const maxSize = 10 * 1024 * 1024 // 10MB
  if (file.size > maxSize) {
    alert('Файл слишком большой. Максимальный размер: 10MB')
    return
  }

  const reader = new FileReader()
  reader.onload = (e) => {
    previewUrl.value = e.target?.result as string
    const img = new Image()
    img.onload = () => {
      validateImage(img, file)
    }
    img.src = previewUrl.value!
  }
  reader.readAsDataURL(file)
}

const validateImage = (img: HTMLImageElement, file: File) => {
  const width = img.naturalWidth
  const height = img.naturalHeight
  const sizeKB = file.size / 1024
  const aspectRatio = width / height

  let totalScore = 0
  const criteria: ValidationCriterion[] = []
  const warnings: string[] = []

  // Критерий 1: Разрешение
  let resolutionScore = 0
  let resolutionStatus: 'excellent' | 'good' | 'fair' | 'poor'
  let resolutionMessage = ''

  if (width >= 1500 && height >= 1500) {
    resolutionScore = 35
    resolutionStatus = 'excellent'
    resolutionMessage = 'Отличное разрешение для детализации'
  } else if (width >= 1200 && height >= 1200) {
    resolutionScore = 30
    resolutionStatus = 'excellent'
    resolutionMessage = 'Хорошее разрешение для Fashion'
  } else if (width >= 800 && height >= 800) {
    resolutionScore = 20
    resolutionStatus = 'good'
    resolutionMessage = 'Приемлемое разрешение'
  } else if (width >= 500 && height >= 500) {
    resolutionScore = 10
    resolutionStatus = 'fair'
    resolutionMessage = 'Минимальное разрешение'
    warnings.push('Рекомендуется увеличить разрешение до минимум 800x800px')
  } else {
    resolutionScore = 0
    resolutionStatus = 'poor'
    resolutionMessage = 'Недостаточное разрешение'
    warnings.push('Разрешение слишком низкое для качественного отображения')
  }

  criteria.push({
    name: 'Разрешение',
    icon: '📐',
    score: resolutionScore,
    maxScore: 35,
    status: resolutionStatus,
    message: resolutionMessage,
  })
  totalScore += resolutionScore

  // Критерий 2: Пропорции
  let aspectScore = 0
  let aspectStatus: 'excellent' | 'good' | 'fair' | 'poor'
  let aspectMessage = ''

  if (aspectRatio >= 0.95 && aspectRatio <= 1.05) {
    aspectScore = 30
    aspectStatus = 'excellent'
    aspectMessage = 'Идеальные пропорции (квадрат)'
  } else if (aspectRatio >= 0.8 && aspectRatio <= 1.2) {
    aspectScore = 25
    aspectStatus = 'excellent'
    aspectMessage = 'Отличные пропорции'
  } else if (aspectRatio >= 0.6 && aspectRatio <= 1.5) {
    aspectScore = 15
    aspectStatus = 'good'
    aspectMessage = 'Хорошие пропорции'
  } else {
    aspectScore = 5
    aspectStatus = 'poor'
    aspectMessage = 'Пропорции требуют улучшения'
    warnings.push('Рекомендуется использовать квадратные или близкие к квадрату пропорции')
  }

  criteria.push({
    name: 'Пропорции',
    icon: '⚖️',
    score: aspectScore,
    maxScore: 30,
    status: aspectStatus,
    message: aspectMessage,
  })
  totalScore += aspectScore

  // Критерий 3: Размер файла
  let fileSizeScore = 0
  let fileSizeStatus: 'excellent' | 'good' | 'fair' | 'poor'
  let fileSizeMessage = ''

  if (sizeKB >= 100 && sizeKB <= 500) {
    fileSizeScore = 20
    fileSizeStatus = 'excellent'
    fileSizeMessage = 'Оптимальный размер файла'
  } else if (sizeKB >= 50 && sizeKB <= 1000) {
    fileSizeScore = 15
    fileSizeStatus = 'good'
    fileSizeMessage = 'Хороший размер файла'
  } else if (sizeKB >= 30 && sizeKB <= 1500) {
    fileSizeScore = 10
    fileSizeStatus = 'fair'
    fileSizeMessage = 'Приемлемый размер файла'
  } else if (sizeKB < 30) {
    fileSizeScore = 5
    fileSizeStatus = 'poor'
    fileSizeMessage = 'Файл слишком маленький, возможна потеря качества'
    warnings.push('Файл слишком маленький, возможна сильная компрессия')
  } else {
    fileSizeScore = 5
    fileSizeStatus = 'poor'
    fileSizeMessage = 'Файл слишком большой'
    warnings.push('Рекомендуется оптимизировать размер файла')
  }

  criteria.push({
    name: 'Размер файла',
    icon: '💾',
    score: fileSizeScore,
    maxScore: 20,
    status: fileSizeStatus,
    message: fileSizeMessage,
  })
  totalScore += fileSizeScore

  // Критерий 4: Формат
  let formatScore = 0
  let formatStatus: 'excellent' | 'good' | 'fair' | 'poor'
  let formatMessage = ''

  const format = file.type.split('/')[1]?.toUpperCase() || 'UNKNOWN'

  if (format === 'WEBP') {
    formatScore = 15
    formatStatus = 'excellent'
    formatMessage = 'Современный формат с хорошей компрессией'
  } else if (format === 'JPEG' || format === 'JPG') {
    formatScore = 10
    formatStatus = 'good'
    formatMessage = 'Стандартный формат для фото'
  } else if (format === 'PNG') {
    formatScore = 8
    formatStatus = 'good'
    formatMessage = 'Формат без потерь, но большой размер'
    warnings.push('PNG имеет большой размер, рассмотрите WEBP или JPG')
  } else {
    formatScore = 0
    formatStatus = 'poor'
    formatMessage = 'Нерекомендуемый формат'
    warnings.push('Используйте WEBP, JPG или PNG')
  }

  criteria.push({
    name: 'Формат',
    icon: '🎨',
    score: formatScore,
    maxScore: 15,
    status: formatStatus,
    message: formatMessage,
  })
  totalScore += formatScore

  // Определение общего уровня
  let level: 'high' | 'medium' | 'low'
  let statusText: string
  let stars: string

  if (totalScore >= 80) {
    level = 'high'
    statusText = 'Отличное качество'
    stars = '★★★★★'
  } else if (totalScore >= 60) {
    level = 'medium'
    statusText = 'Хорошее качество'
    stars = '★★★★☆'
  } else if (totalScore >= 40) {
    level = 'medium'
    statusText = 'Приемлемое качество'
    stars = '★★★☆☆'
  } else {
    level = 'low'
    statusText = 'Требует улучшения'
    stars = '★★☆☆☆'
  }

  validationResult.value = {
    score: totalScore,
    level,
    statusText,
    stars,
    criteria,
    warnings,
    specs: {
      resolution: `${width}x${height}`,
      aspectRatio: aspectRatio.toFixed(2),
      fileSize: `${sizeKB.toFixed(1)} KB`,
      format,
    },
  }

  emit('validate', validationResult.value)
}

const resetValidator = () => {
  previewUrl.value = null
  validationResult.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}
</script>

<style scoped>
.fashion-photo-validator {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.validator-header {
  text-align: center;
  margin-bottom: 2rem;
}

.validator-header h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 0.5rem;
}

.subtitle {
  color: #666;
  font-size: 0.95rem;
}

.upload-section {
  margin-bottom: 2rem;
}

.upload-zone {
  border: 3px dashed #d1d5db;
  border-radius: 12px;
  padding: 3rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  min-height: 300px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.upload-zone:hover,
.upload-zone.drag-over {
  border-color: #6366f1;
  background: #f5f5ff;
}

.upload-zone.has-image {
  padding: 1rem;
  border-style: solid;
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.upload-icon {
  font-size: 3rem;
}

.upload-hint {
  color: #999;
  font-size: 0.85rem;
}

.preview-image {
  max-width: 100%;
  max-height: 400px;
  object-fit: contain;
  border-radius: 8px;
}

.validation-results {
  margin-top: 2rem;
}

.overall-score {
  display: flex;
  align-items: center;
  gap: 2rem;
  padding: 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  color: white;
  margin-bottom: 2rem;
}

.score-circle {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: white;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #6366f1;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.score-circle.high {
  color: #10b981;
}

.score-circle.medium {
  color: #f59e0b;
}

.score-circle.low {
  color: #ef4444;
}

.score-number {
  font-size: 2rem;
  font-weight: 700;
  line-height: 1;
}

.score-label {
  font-size: 0.85rem;
  font-weight: 500;
}

.score-text h4 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 0.25rem 0;
}

.score-text p {
  font-size: 1.5rem;
  margin: 0;
}

.criteria-breakdown {
  margin-bottom: 2rem;
}

.criteria-breakdown h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 1rem;
}

.criteria-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.criterion-item {
  padding: 1rem;
  border-radius: 8px;
  background: #f9fafb;
}

.criterion-item.excellent {
  border-left: 4px solid #10b981;
}

.criterion-item.good {
  border-left: 4px solid #3b82f6;
}

.criterion-item.fair {
  border-left: 4px solid #f59e0b;
}

.criterion-item.poor {
  border-left: 4px solid #ef4444;
}

.criterion-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.criterion-icon {
  font-size: 1.25rem;
}

.criterion-name {
  flex: 1;
  font-weight: 600;
  color: #1a1a1a;
}

.criterion-score {
  font-weight: 700;
  color: #6366f1;
}

.criterion-bar {
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.criterion-progress {
  height: 100%;
  background: linear-gradient(90deg, #6366f1, #8b5cf6);
  transition: width 0.5s ease;
}

.criterion-message {
  font-size: 0.85rem;
  color: #666;
  margin: 0;
}

.warnings {
  padding: 1rem;
  background: #fef3c7;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.warnings h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #92400e;
  margin: 0 0 0.75rem 0;
}

.warnings ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.warnings li {
  padding: 0.25rem 0;
  padding-left: 1.5rem;
  position: relative;
  color: #92400e;
}

.warnings li::before {
  content: '⚠️';
  position: absolute;
  left: 0;
}

.image-specs {
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.image-specs h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 1rem 0;
}

.specs-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.spec-item {
  display: flex;
  flex-direction: column;
}

.spec-label {
  font-size: 0.75rem;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.25rem;
}

.spec-value {
  font-weight: 600;
  color: #1a1a1a;
}

.validator-actions {
  display: flex;
  gap: 1rem;
}

.btn {
  flex: 1;
  padding: 1rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
}

.btn-primary {
  background: #6366f1;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #5558e3;
}

.btn-primary:disabled {
  background: #d1d5db;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  border: 2px solid #e5e7eb;
  color: #1a1a1a;
}

.btn-secondary:hover {
  border-color: #6366f1;
  color: #6366f1;
}

.criteria-reference {
  margin-top: 2rem;
}

.criteria-reference h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 1rem;
}

.reference-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.reference-card {
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 8px;
  text-align: center;
}

.reference-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.reference-card h5 {
  font-size: 1rem;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 0.5rem 0;
}

.reference-card p {
  font-size: 0.85rem;
  color: #666;
  line-height: 1.5;
  margin: 0;
}
</style>
