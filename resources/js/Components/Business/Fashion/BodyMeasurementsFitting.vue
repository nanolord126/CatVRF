<template>
  <div class="body-measurements-fitting">
    <div class="fitting-header">
      <h2>📐 Онлайн Примерочная</h2>
      <p class="subtitle">Введите параметры вашей фигуры для точного подбора одежды</p>
    </div>

    <div class="fitting-layout">
      <div class="measurements-panel">
        <div class="panel-section">
          <h3>Основные параметры</h3>
          <div class="measurement-grid">
            <div class="measurement-field">
              <label>Рост (см) *</label>
              <input
                v-model.number="body.height"
                type="number"
                min="140"
                max="220"
                placeholder="165"
                @change="calculateAll"
              />
              <span v-if="errors.height" class="error">{{ errors.height }}</span>
            </div>
            <div class="measurement-field">
              <label>Вес (кг) *</label>
              <input
                v-model.number="body.weight"
                type="number"
                min="35"
                max="150"
                placeholder="60"
                @change="calculateAll"
              />
              <span v-if="errors.weight" class="error">{{ errors.weight }}</span>
            </div>
          </div>
        </div>

        <div class="panel-section">
          <h3>Обхват (см)</h3>
          <div class="measurement-grid">
            <div class="measurement-field">
              <label>Грудь</label>
              <input
                v-model.number="body.bust"
                type="number"
                min="70"
                max="150"
                @change="calculateAll"
              />
            </div>
            <div class="measurement-field">
              <label>Под грудью</label>
              <input
                v-model.number="body.underbust"
                type="number"
                min="60"
                max="130"
                @change="calculateAll"
              />
            </div>
            <div class="measurement-field">
              <label>Талия</label>
              <input
                v-model.number="body.waist"
                type="number"
                min="50"
                max="130"
                @change="calculateAll"
              />
            </div>
            <div class="measurement-field">
              <label>Бедра</label>
              <input
                v-model.number="body.hips"
                type="number"
                min="70"
                max="160"
                @change="calculateAll"
              />
            </div>
          </div>
        </div>

        <div class="panel-section">
          <h3>Плечи и руки</h3>
          <div class="measurement-grid">
            <div class="measurement-field">
              <label>Ширина плеч (см)</label>
              <input
                v-model.number="body.shoulderWidth"
                type="number"
                min="30"
                max="60"
                @change="calculateAll"
              />
            </div>
            <div class="measurement-field">
              <label>Длина рукава (см)</label>
              <input
                v-model.number="body.sleeveLength"
                type="number"
                min="50"
                max="80"
                @change="calculateAll"
              />
            </div>
            <div class="measurement-field">
              <label>Обхват рукава (см)</label>
              <input
                v-model.number="body.armCircumference"
                type="number"
                min="20"
                max="50"
                @change="calculateAll"
              />
            </div>
          </div>
        </div>

        <div class="panel-section">
          <h3>Ноги</h3>
          <div class="measurement-grid">
            <div class="measurement-field">
              <label>Длина ноги (см)</label>
              <input
                v-model.number="body.legLength"
                type="number"
                min="70"
                max="120"
                @change="calculateAll"
              />
            </div>
            <div class="measurement-field">
              <label>Обхват бедра (см)</label>
              <input
                v-model.number="body.thighCircumference"
                type="number"
                min="40"
                max="80"
                @change="calculateAll"
              />
            </div>
            <div class="measurement-field">
              <label>Обхват голени (см)</label>
              <input
                v-model.number="body.calfCircumference"
                type="number"
                min="25"
                max="50"
                @change="calculateAll"
              />
            </div>
          </div>
        </div>

        <div class="panel-section">
          <h3>Дополнительные параметры</h3>
          <div class="measurement-grid">
            <div class="measurement-field">
              <label>Обхват шеи (см)</label>
              <input
                v-model.number="body.neckCircumference"
                type="number"
                min="25"
                max="45"
                @change="calculateAll"
              />
            </div>
            <div class="measurement-field">
              <label>Длина спины (см)</label>
              <input
                v-model.number="body.backLength"
                type="number"
                min="35"
                max="60"
                @change="calculateAll"
              />
            </div>
          </div>
        </div>
      </div>

      <div class="results-panel">
        <div class="body-visualization">
          <h3>👤 Ваша фигура</h3>
          <div class="figure-type">
            <span class="figure-label">Тип фигуры:</span>
            <span class="figure-value">{{ figureType }}</span>
          </div>
          <div class="bmi-result">
            <span class="bmi-label">ИМТ:</span>
            <span :class="['bmi-value', bmiClass]">{{ bmi }}</span>
            <span class="bmi-status">{{ bmiStatus }}</span>
          </div>
          <div class="proportions">
            <h4>Пропорции</h4>
            <div class="proportion-item">
              <span>Талия/Бедра:</span>
              <span>{{ waistToHipRatio }}</span>
            </div>
            <div class="proportion-item">
              <span>Грудь/Талия:</span>
              <span>{{ bustToWaistRatio }}</span>
            </div>
          </div>
        </div>

        <div class="recommended-sizes">
          <h3>📏 Рекомендуемые размеры</h3>
          <div class="size-grid">
            <div class="size-card">
              <span class="size-category">Верхняя одежда</span>
              <span class="size-value">{{ topSize }}</span>
            </div>
            <div class="size-card">
              <span class="size-category">Брюки/Джинсы</span>
              <span class="size-value">{{ bottomSize }}</span>
            </div>
            <div class="size-card">
              <span class="size-category">Платья</span>
              <span class="size-value">{{ dressSize }}</span>
            </div>
            <div class="size-card">
              <span class="size-category">Бюстгальтер</span>
              <span class="size-value">{{ braSize }}</span>
            </div>
            <div class="size-card">
              <span class="size-category">Обувь</span>
              <span class="size-value">{{ shoeSize }}</span>
            </div>
          </div>
        </div>

        <div class="style-recommendations">
          <h3>✨ Стилевые рекомендации</h3>
          <div class="recommendation-list">
            <div
              v-for="(rec, index) in styleRecommendations"
              :key="index"
              class="recommendation-item"
            >
              <span class="rec-icon">{{ rec.icon }}</span>
              <p>{{ rec.text }}</p>
            </div>
          </div>
        </div>

        <div class="fit-tips">
          <h3>💡 Советы по посадке</h3>
          <div class="tips-list">
            <div class="tip-item">
              <span class="tip-icon">📏</span>
              <p>Обратите внимание на длину рукава и штанин при выборе размера</p>
            </div>
            <div class="tip-item">
              <span class="tip-icon">🎯</span>
              <p>При разном размере верхней и нижней одежды ориентируйтесь на бедра</p>
            </div>
            <div class="tip-item">
              <span class="tip-icon">🔄</span>
              <p>Для облегающей одежды выбирайте размер на 1 больше</p>
            </div>
          </div>
        </div>

        <div class="actions">
          <button @click="saveMeasurements" class="btn btn-primary">
            💾 Сохранить параметры
          </button>
          <button @click="resetMeasurements" class="btn btn-secondary">
            🔄 Сбросить
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface BodyMeasurements {
  height: number
  weight: number
  bust: number
  underbust: number
  waist: number
  hips: number
  shoulderWidth: number
  sleeveLength: number
  armCircumference: number
  legLength: number
  thighCircumference: number
  calfCircumference: number
  neckCircumference: number
  backLength: number
}

const emit = defineEmits<{
  'save-measurements': [measurements: BodyMeasurements]
  'get-recommendations': [measurements: BodyMeasurements]
}>()

const body = ref<BodyMeasurements>({
  height: 0,
  weight: 0,
  bust: 0,
  underbust: 0,
  waist: 0,
  hips: 0,
  shoulderWidth: 0,
  sleeveLength: 0,
  armCircumference: 0,
  legLength: 0,
  thighCircumference: 0,
  calfCircumference: 0,
  neckCircumference: 0,
  backLength: 0,
})

const errors = ref<Record<string, string>>({})

const figureType = computed(() => {
  if (!body.value.waist || !body.value.hips || !body.value.bust) return '—'
  
  const waist = body.value.waist
  const hips = body.value.hips
  const bust = body.value.bust
  
  const waistToHip = waist / hips
  const bustToWaist = bust / waist
  
  if (waistToHip < 0.7 && bustToWaist > 1.2) return 'Песочные часы'
  if (waistToHip > 0.85 && bustToWaist < 1.1) return 'Прямоугольник'
  if (hips > bust && hips > waist) return 'Груша'
  if (bust > hips && bust > waist) return 'Треугольник'
  if (Math.abs(bust - hips) < 5 && Math.abs(waist - hips) < 5) return 'Яблоко'
  if (bust > waist && hips > waist && Math.abs(bust - hips) < 10) return 'Стандарт'
  
  return 'Стандарт'
})

const bmi = computed(() => {
  if (!body.value.height || !body.value.weight) return '—'
  
  const heightInMeters = body.value.height / 100
  const bmiValue = body.value.weight / (heightInMeters * heightInMeters)
  
  return bmiValue.toFixed(1)
})

const bmiClass = computed(() => {
  const bmiValue = parseFloat(bmi.value)
  if (bmiValue < 18.5) return 'underweight'
  if (bmiValue < 25) return 'normal'
  if (bmiValue < 30) return 'overweight'
  return 'obese'
})

const bmiStatus = computed(() => {
  const bmiValue = parseFloat(bmi.value)
  if (bmiValue < 18.5) return 'Ниже нормы'
  if (bmiValue < 25) return 'Норма'
  if (bmiValue < 30) return 'Избыточный вес'
  return 'Ожирение'
})

const waistToHipRatio = computed(() => {
  if (!body.value.waist || !body.value.hips) return '—'
  return (body.value.waist / body.value.hips).toFixed(2)
})

const bustToWaistRatio = computed(() => {
  if (!body.value.bust || !body.value.waist) return '—'
  return (body.value.bust / body.value.waist).toFixed(2)
})

const topSize = computed(() => {
  const bust = body.value.bust
  const waist = body.value.waist
  
  if (!bust) return '—'
  
  const size = bust + waist
  if (size < 150) return 'XS'
  if (size < 165) return 'S'
  if (size < 180) return 'M'
  if (size < 195) return 'L'
  if (size < 210) return 'XL'
  return 'XXL'
})

const bottomSize = computed(() => {
  const waist = body.value.waist
  const hips = body.value.hips
  
  if (!waist || !hips) return '—'
  
  const size = waist + hips
  if (size < 150) return 'XS'
  if (size < 165) return 'S'
  if (size < 180) return 'M'
  if (size < 195) return 'L'
  if (size < 210) return 'XL'
  return 'XXL'
})

const dressSize = computed(() => {
  const bust = body.value.bust
  const waist = body.value.waist
  const hips = body.value.hips
  
  if (!bust || !waist || !hips) return '—'
  
  const avgSize = (bust + waist + hips) / 3
  
  if (avgSize < 80) return 'XS'
  if (avgSize < 90) return 'S'
  if (avgSize < 100) return 'M'
  if (avgSize < 110) return 'L'
  if (avgSize < 120) return 'XL'
  return 'XXL'
})

const braSize = computed(() => {
  const bust = body.value.bust
  const underbust = body.value.underbust
  
  if (!bust || !underbust) return '—'
  
  const bandSize = Math.round(underbust / 5) * 5
  const cupSize = bust - underbust
  
  const cupLetters = ['AA', 'A', 'B', 'C', 'D', 'DD', 'E', 'F', 'G']
  const cupIndex = Math.min(Math.max(cupSize - 10, 0), cupLetters.length - 1)
  
  return `${bandSize}${cupLetters[cupIndex]}`
})

const shoeSize = computed(() => {
  const footLength = body.value.legLength ? body.value.legLength * 0.15 : 0
  
  if (!footLength) return '—'
  
  const euSize = Math.round(footLength * 1.5 + 2)
  return `${euSize} EU`
})

const styleRecommendations = computed(() => {
  const recommendations = []
  const figType = figureType.value
  
  if (figType === 'Песочные часы') {
    recommendations.push({ icon: '✨', text: 'Подчеркните талию поясами и приталенными силуэтами' })
    recommendations.push({ icon: '👗', text: 'Платья-футляр и A-силуэт идеально подойдут' })
  } else if (figType === 'Груша') {
    recommendations.push({ icon: '👔', text: 'Добавляйте объем верхней части с помощью деталей' })
    recommendations.push({ icon: '👖', text: 'Выбирайте брюки с умеренной посадкой' })
  } else if (figType === 'Треугольник') {
    recommendations.push({ icon: '👚', text: 'V-образные вырезы визуально уменьшают плечи' })
    recommendations.push({ icon: '👗', text: 'Платья с расклешенным низом сбалансируют фигуру' })
  } else if (figType === 'Прямоугольник') {
    recommendations.push({ icon: '🎨', text: 'Создавайте иллюзию талии с помощью поясов' })
    recommendations.push({ icon: '✨', text: 'Многослойность поможет добавить объем' })
  }
  
  recommendations.push({ icon: '📏', text: 'Всегда ориентируйтесь на таблицу размеров бренда' })
  
  return recommendations
})

const calculateAll = () => {
  validateMeasurements()
}

const validateMeasurements = () => {
  errors.value = {}
  
  if (body.value.height < 140 || body.value.height > 220) {
    errors.value.height = 'Рост должен быть от 140 до 220 см'
  }
  
  if (body.value.weight < 35 || body.value.weight > 150) {
    errors.value.weight = 'Вес должен быть от 35 до 150 кг'
  }
}

const saveMeasurements = () => {
  validateMeasurements()
  
  if (Object.keys(errors.value).length === 0) {
    emit('save-measurements', body.value)
    emit('get-recommendations', body.value)
  }
}

const resetMeasurements = () => {
  body.value = {
    height: 0,
    weight: 0,
    bust: 0,
    underbust: 0,
    waist: 0,
    hips: 0,
    shoulderWidth: 0,
    sleeveLength: 0,
    armCircumference: 0,
    legLength: 0,
    thighCircumference: 0,
    calfCircumference: 0,
    neckCircumference: 0,
    backLength: 0,
  }
  errors.value = {}
}
</script>

<style scoped>
.body-measurements-fitting {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
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

.fitting-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

@media (max-width: 1024px) {
  .fitting-layout {
    grid-template-columns: 1fr;
  }
}

.measurements-panel,
.results-panel {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.panel-section {
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid #e5e7eb;
}

.panel-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.panel-section h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 1rem;
}

.measurement-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

.measurement-field {
  display: flex;
  flex-direction: column;
}

.measurement-field label {
  font-size: 0.9rem;
  font-weight: 500;
  color: #666;
  margin-bottom: 0.5rem;
}

.measurement-field input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.measurement-field input:focus {
  outline: none;
  border-color: #6366f1;
}

.error {
  color: #ef4444;
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

.body-visualization,
.recommended-sizes,
.style-recommendations,
.fit-tips {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 12px;
}

.body-visualization h3,
.recommended-sizes h3,
.style-recommendations h3,
.fit-tips h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 1rem;
}

.figure-type,
.bmi-result {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.figure-label,
.bmi-label {
  font-size: 0.95rem;
  color: #666;
}

.figure-value,
.bmi-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #6366f1;
}

.bmi-value.underweight {
  color: #3b82f6;
}

.bmi-value.normal {
  color: #10b981;
}

.bmi-value.overweight {
  color: #f59e0b;
}

.bmi-value.obese {
  color: #ef4444;
}

.bmi-status {
  font-size: 0.85rem;
  color: #666;
}

.proportions h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 0.5rem;
}

.proportion-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.size-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1rem;
}

.size-card {
  background: white;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.size-category {
  display: block;
  font-size: 0.85rem;
  color: #666;
  margin-bottom: 0.5rem;
}

.size-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: #6366f1;
}

.recommendation-list,
.tips-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.recommendation-item,
.tip-item {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.rec-icon,
.tip-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.recommendation-item p,
.tip-item p {
  color: #666;
  line-height: 1.5;
  margin: 0;
}

.actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
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
</style>
