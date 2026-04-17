<template>
  <div class="lingerie-fitting-room">
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Проверка доступа...</p>
    </div>

    <div v-else-if="accessDenied" class="access-denied">
      <div class="denied-icon">🚫</div>
      <h2>{{ accessDenied.message }}</h2>
      <p v-if="accessDenied.reason === 'account_blocked'" class="block-info">
        Блокировка истекает: {{ formatDateTime(accessDenied.block_expires_at) }}
      </p>
      <button @contactSupport class="btn btn-secondary">Связаться с поддержкой</button>
    </div>

    <div v-else-if="accessWarning" class="access-warning">
      <div class="warning-icon">⚠️</div>
      <h2>{{ accessWarning.message }}</h2>
      <p>Ваша активность мониторится в целях безопасности</p>
      <button @click="acknowledgeWarning" class="btn btn-primary">Понятно</button>
    </div>

    <div v-else class="fitting-room-container">
      <div class="fitting-header">
        <h2>👗 Онлайн Примерочная</h2>
        <p class="subtitle">Виртуальная примерка нижнего белья</p>
        <div v-if="userAge < 18" class="age-warning">
          ⚠️ Эта функция доступна только для пользователей 18+
        </div>
      </div>

      <div class="fitting-content">
        <div class="measurement-section">
          <h3>📏 Параметры фигуры</h3>
          <div class="measurements-grid">
            <div class="measurement-input">
              <label>Рост (см)</label>
              <input
                v-model.number="measurements.height"
                type="number"
                min="140"
                max="220"
                @change="updateRecommendations"
              />
            </div>
            <div class="measurement-input">
              <label>Обхват груди (см)</label>
              <input
                v-model.number="measurements.bust"
                type="number"
                min="70"
                max="150"
                @change="updateRecommendations"
              />
            </div>
            <div class="measurement-input">
              <label>Обхват под грудью (см)</label>
              <input
                v-model.number="measurements.underbust"
                type="number"
                min="60"
                max="130"
                @change="updateRecommendations"
              />
            </div>
            <div class="measurement-input">
              <label>Обхват талии (см)</label>
              <input
                v-model.number="measurements.waist"
                type="number"
                min="50"
                max="120"
                @change="updateRecommendations"
              />
            </div>
            <div class="measurement-input">
              <label>Обхват бедер (см)</label>
              <input
                v-model.number="measurements.hips"
                type="number"
                min="70"
                max="150"
                @change="updateRecommendations"
              />
            </div>
          </div>

          <div class="calculated-sizes">
            <div class="size-result">
              <span class="size-label">Размер бюстгальтера:</span>
              <span class="size-value">{{ calculatedBraSize }}</span>
            </div>
            <div class="size-result">
              <span class="size-label">Размер белья:</span>
              <span class="size-value">{{ calculatedPantySize }}</span>
            </div>
            <div class="size-result">
              <span class="size-label">Тип фигуры:</span>
              <span class="size-value">{{ bodyType }}</span>
            </div>
          </div>
        </div>

        <div class="style-preference-section">
          <h3>🎨 Предпочтения по стилю</h3>
          <div class="style-options">
            <div class="style-category">
              <h4>Тип белья</h4>
              <div class="style-buttons">
                <button
                  v-for="type in lingerieTypes"
                  :key="type.id"
                  :class="['style-btn', { active: selectedStyle.type === type.id }]"
                  @click="selectedStyle.type = type.id"
                >
                  {{ type.label }}
                </button>
              </div>
            </div>

            <div class="style-category">
              <h4>Материал</h4>
              <div class="style-buttons">
                <button
                  v-for="material in materials"
                  :key="material.id"
                  :class="['style-btn', { active: selectedStyle.material === material.id }]"
                  @click="selectedStyle.material = material.id"
                >
                  {{ material.label }}
                </button>
              </div>
            </div>

            <div class="style-category">
              <h4>Цветовая гамма</h4>
              <div class="color-options">
                <button
                  v-for="color in colors"
                  :key="color.id"
                  :class="['color-btn', { active: selectedStyle.color === color.id }]"
                  :style="{ backgroundColor: color.hex }"
                  :title="color.label"
                  @click="selectedStyle.color = color.id"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="recommendations-section">
          <h3>✨ Рекомендации</h3>
          <div v-if="recommendations.length > 0" class="recommendations-grid">
            <div
              v-for="item in recommendations"
              :key="item.id"
              class="recommendation-card"
            >
              <div class="card-image">
                <img :src="item.image" :alt="item.name" />
              </div>
              <div class="card-content">
                <h4>{{ item.name }}</h4>
                <p class="brand">{{ item.brand }}</p>
                <p class="price">{{ formatPrice(item.price) }}</p>
                <div class="fit-score">
                  <span class="score-label">Подходит:</span>
                  <span :class="['score-value', item.fitScore >= 80 ? 'high' : item.fitScore >= 60 ? 'medium' : 'low']">
                    {{ item.fitScore }}%
                  </span>
                </div>
                <button @click="addToWishlist(item)" class="btn btn-secondary">
                  ❤️ В избранное
                </button>
              </div>
            </div>
          </div>
          <div v-else class="no-recommendations">
            <p>Введите параметры фигуры для получения рекомендаций</p>
          </div>
        </div>

        <div class="tips-section">
          <h3>💡 Советы по выбору</h3>
          <div class="tips-list">
            <div class="tip-item">
              <span class="tip-icon">📏</span>
              <p>Правильный размер бюстгальтера обеспечивает комфорт и поддержку</p>
            </div>
            <div class="tip-item">
              <span class="tip-icon">🎨</span>
              <p>Натуральные материалы лучше дышат и вызывают меньше раздражений</p>
            </div>
            <div class="tip-item">
              <span class="tip-icon">✨</span>
              <p>Выбирайте белье, которое подчеркивает вашу фигуру</p>
            </div>
          </div>
        </div>
      </div>

      <div class="safety-notice">
        <p>🔒 Все данные конфиденциальны и защищены</p>
        <p>Эта функция предназначена исключительно для женских аккаунтов</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Measurements {
  height: number
  bust: number
  underbust: number
  waist: number
  hips: number
}

interface StylePreference {
  type: string
  material: string
  color: string
}

interface Recommendation {
  id: number
  name: string
  brand: string
  price: number
  image: string
  fitScore: number
}

interface AccessCheckResult {
  allowed: boolean
  reason?: string
  message: string
  block_expires_at?: string
  warning?: string
}

const props = defineProps<{
  userId: number
  userGender: string
  userAge: number
}>()

const emit = defineEmits<{
  'add-to-wishlist': [item: Recommendation]
}>()

const loading = ref(true)
const accessDenied = ref<AccessCheckResult | null>(null)
const accessWarning = ref<{ message: string } | null>(null)
const measurements = ref<Measurements>({
  height: 165,
  bust: 90,
  underbust: 75,
  waist: 70,
  hips: 95,
})
const selectedStyle = ref<StylePreference>({
  type: 'everyday',
  material: 'cotton',
  color: 'black',
})
const recommendations = ref<Recommendation[]>([])

const lingerieTypes = [
  { id: 'everyday', label: 'Ежедневное' },
  { id: 'pushup', label: 'Пуш-ап' },
  { id: 'sports', label: 'Спортивное' },
  { id: 'seamless', label: 'Бесшовное' },
  { id: 'lace', label: 'Кружевное' },
]

const materials = [
  { id: 'cotton', label: 'Хлопок' },
  { id: 'silk', label: 'Шелк' },
  { id: 'lace', label: 'Кружево' },
  { id: 'microfiber', label: 'Микроволокно' },
  { id: 'bamboo', label: 'Бамбук' },
]

const colors = [
  { id: 'black', label: 'Черный', hex: '#000000' },
  { id: 'white', label: 'Белый', hex: '#FFFFFF' },
  { id: 'beige', label: 'Бежевый', hex: '#F5F5DC' },
  { id: 'red', label: 'Красный', hex: '#DC143C' },
  { id: 'pink', label: 'Розовый', hex: '#FFC0CB' },
  { id: 'nude', label: 'Телесный', hex: '#E8BEAC' },
]

const calculatedBraSize = computed(() => {
  const bust = measurements.value.bust
  const underbust = measurements.value.underbust
  
  if (!bust || !underbust) return '—'
  
  const bandSize = Math.round(underbust / 5) * 5
  const cupSize = bust - underbust
  
  const cupLetters = ['AA', 'A', 'B', 'C', 'D', 'DD', 'E', 'F', 'G']
  const cupIndex = Math.min(Math.max(cupSize - 10, 0), cupLetters.length - 1)
  
  return `${bandSize}${cupLetters[cupIndex]}`
})

const calculatedPantySize = computed(() => {
  const waist = measurements.value.waist
  const hips = measurements.value.hips
  
  if (!waist || !hips) return '—'
  
  const avgSize = (waist + hips) / 2
  
  if (avgSize < 80) return 'XS'
  if (avgSize < 90) return 'S'
  if (avgSize < 100) return 'M'
  if (avgSize < 110) return 'L'
  if (avgSize < 120) return 'XL'
  return 'XXL'
})

const bodyType = computed(() => {
  const bust = measurements.value.bust
  const waist = measurements.value.waist
  const hips = measurements.value.hips
  
  if (!bust || !waist || !hips) return '—'
  
  const waistToHipRatio = waist / hips
  const bustToWaistRatio = bust / waist
  
  if (waistToHipRatio < 0.7 && bustToWaistRatio > 1.2) return 'Песочные часы'
  if (waistToHipRatio > 0.85) return 'Прямоугольник'
  if (hips > bust && hips > waist) return 'Груша'
  if (bust > hips && bust > waist) return 'Треугольник'
  if (bust === hips && hips === waist) return 'Яблоко'
  
  return 'Стандарт'
})

const checkAccess = async () => {
  try {
    const response = await fetch('/api/v1/fashion/fitting/check-access', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
      },
      body: JSON.stringify({
        user_id: props.userId,
        user_gender: props.userGender,
      }),
    })

    const result: AccessCheckResult = await response.json()

    if (!result.allowed) {
      accessDenied.value = result
    } else if (result.warning) {
      accessWarning.value = { message: result.message }
    }

    loading.value = false
  } catch (error) {
    console.error('Access check failed:', error)
    loading.value = false
    accessDenied.value = {
      allowed: false,
      message: 'Ошибка проверки доступа. Попробуйте позже.',
    }
  }
}

const updateRecommendations = async () => {
  if (!measurements.value.height || !measurements.value.bust) {
    recommendations.value = []
    return
  }

  try {
    const response = await fetch('/api/v1/fashion/fitting/recommendations', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
      },
      body: JSON.stringify({
        measurements: measurements.value,
        style: selectedStyle.value,
      }),
    })

    const data = await response.json()
    recommendations.value = data.recommendations || []
  } catch (error) {
    console.error('Failed to get recommendations:', error)
  }
}

const addToWishlist = (item: Recommendation) => {
  emit('add-to-wishlist', item)
}

const acknowledgeWarning = () => {
  accessWarning.value = null
}

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(price)
}

const formatDateTime = (dateStr: string): string => {
  return new Date(dateStr).toLocaleString('ru-RU')
}

onMounted(() => {
  if (props.userAge < 18) {
    accessDenied.value = {
      allowed: false,
      message: 'Эта функция доступна только для пользователей 18+',
    }
    loading.value = false
  } else {
    checkAccess()
  }
})
</script>

<style scoped>
.lingerie-fitting-room {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.loading-state {
  text-align: center;
  padding: 3rem;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.access-denied,
.access-warning {
  text-align: center;
  padding: 3rem;
  background: #fef2f2;
  border-radius: 12px;
  border: 2px solid #ef4444;
}

.access-warning {
  background: #fef3c7;
  border-color: #f59e0b;
}

.denied-icon,
.warning-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.access-denied h2,
.access-warning h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 0.5rem;
}

.block-info {
  color: #666;
  margin: 1rem 0;
}

.fitting-room-container {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.fitting-header {
  text-align: center;
  margin-bottom: 2rem;
}

.fitting-header h2 {
  font-size: 2rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 0.5rem;
}

.subtitle {
  color: #666;
  font-size: 1.1rem;
}

.age-warning {
  margin-top: 1rem;
  padding: 0.75rem;
  background: #fef3c7;
  border-radius: 8px;
  color: #92400e;
  font-weight: 500;
}

.measurement-section,
.style-preference-section,
.recommendations-section,
.tips-section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 12px;
}

.measurement-section h3,
.style-preference-section h3,
.recommendations-section h3,
.tips-section h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 1rem;
}

.measurements-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.measurement-input label {
  display: block;
  font-size: 0.9rem;
  font-weight: 500;
  color: #666;
  margin-bottom: 0.5rem;
}

.measurement-input input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
}

.calculated-sizes {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  padding: 1rem;
  background: white;
  border-radius: 8px;
}

.size-result {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.size-label {
  font-size: 0.9rem;
  color: #666;
}

.size-value {
  font-size: 1.1rem;
  font-weight: 700;
  color: #6366f1;
}

.style-category {
  margin-bottom: 1.5rem;
}

.style-category h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 0.75rem;
}

.style-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.style-btn {
  padding: 0.5rem 1rem;
  border: 2px solid #d1d5db;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
}

.style-btn:hover {
  border-color: #6366f1;
}

.style-btn.active {
  background: #6366f1;
  color: white;
  border-color: #6366f1;
}

.color-options {
  display: flex;
  gap: 0.75rem;
}

.color-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 3px solid transparent;
  cursor: pointer;
  transition: all 0.3s ease;
}

.color-btn:hover {
  transform: scale(1.1);
}

.color-btn.active {
  border-color: #6366f1;
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

.recommendations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1.5rem;
}

.recommendation-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease;
}

.recommendation-card:hover {
  transform: translateY(-4px);
}

.card-image {
  height: 200px;
  overflow: hidden;
}

.card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.card-content {
  padding: 1rem;
}

.card-content h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 0.25rem;
}

.brand {
  font-size: 0.85rem;
  color: #666;
  margin-bottom: 0.5rem;
}

.price {
  font-size: 1.1rem;
  font-weight: 700;
  color: #6366f1;
  margin-bottom: 0.75rem;
}

.fit-score {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}

.score-label {
  font-size: 0.85rem;
  color: #666;
}

.score-value {
  font-weight: 600;
}

.score-value.high {
  color: #10b981;
}

.score-value.medium {
  color: #f59e0b;
}

.score-value.low {
  color: #ef4444;
}

.btn {
  padding: 0.5rem 1rem;
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

.btn-primary:hover {
  background: #5558e3;
}

.btn-secondary {
  background: white;
  border: 2px solid #d1d5db;
  color: #1a1a1a;
}

.btn-secondary:hover {
  border-color: #6366f1;
  color: #6366f1;
}

.tips-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.tip-item {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.tip-icon {
  font-size: 1.5rem;
}

.tip-item p {
  color: #666;
  line-height: 1.5;
  margin: 0;
}

.safety-notice {
  margin-top: 2rem;
  padding: 1rem;
  background: #ecfdf5;
  border-radius: 8px;
  text-align: center;
  color: #065f46;
}

.safety-notice p {
  margin: 0.25rem 0;
}
</style>
