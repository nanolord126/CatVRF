<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';

interface StyleProfile {
  color_type: string;
  contrast_level: string;
  figure_type: string;
  preferred_palette: string[];
  recommended_cuts: string[];
  avoid_cuts: string[];
  confidence_score: number;
}

interface CapsuleItem {
  product_id: number;
  name: string;
  category: string;
  price: number;
  in_stock: boolean;
  color_match_score: number;
  ar_try_on_url: string | null;
}

interface AnalysisResult {
  design_id: number;
  style_profile: StyleProfile;
  capsule_wardrobe: CapsuleItem[];
  recommendations: CapsuleItem[];
  photo_url: string;
  ar_try_on_url: string;
  three_d_models_url: string;
}

const photo = ref<File | null>(null);
const previewUrl = ref<string>('');
const eventType = ref<string>('');
const isB2B = ref<boolean>(false);
const loading = ref<boolean>(false);
const result = ref<AnalysisResult | null>(null);
const error = ref<string>('');

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    photo.value = target.files[0];
    previewUrl.value = URL.createObjectURL(photo.value);
  }
};

const analyzeStyle = async () => {
  if (!photo.value) {
    error.value = 'Please select a photo';
    return;
  }

  loading.value = true;
  error.value = '';

  const formData = new FormData();
  formData.append('photo', photo.value);
  if (eventType.value) {
    formData.append('event_type', eventType.value);
  }
  formData.append('is_b2b', isB2B.value ? '1' : '0');

  try {
    const response = await axios.post('/api/fashion/ai/analyze', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'Accept': 'application/json',
      },
    });

    result.value = response.data.data;
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Analysis failed';
  } finally {
    loading.value = false;
  }
};

const colorTypeLabel = computed(() => {
  const labels: Record<string, string> = {
    spring: 'Весна',
    summer: 'Лето',
    autumn: 'Осень',
    winter: 'Зима',
  };
  return labels[result.value?.style_profile.color_type || ''] || result.value?.style_profile.color_type;
});

const figureTypeLabel = computed(() => {
  const labels: Record<string, string> = {
    hourglass: 'Песочные часы',
    pear: 'Груша',
    apple: 'Яблоко',
    rectangle: 'Прямоугольник',
    inverted_triangle: 'Перевернутый треугольник',
  };
  return labels[result.value?.style_profile.figure_type || ''] || result.value?.style_profile.figure_type;
});
</script>

<template>
  <div class="fashion-style-analyzer">
    <div class="analyzer-header">
      <h2>AI-конструктор стиля</h2>
      <p>Загрузите фото для персонализированного анализа стиля</p>
    </div>

    <div class="analyzer-content">
      <div class="upload-section" v-if="!result">
        <div class="photo-upload" @click="$refs.fileInput.click()">
          <div v-if="previewUrl" class="preview">
            <img :src="previewUrl" alt="Preview" />
          </div>
          <div v-else class="placeholder">
            <svg class="upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p>Нажмите для загрузки фото</p>
          </div>
        </div>
        <input
          ref="fileInput"
          type="file"
          accept="image/*"
          @change="handleFileChange"
          class="hidden"
        />

        <div class="options">
          <div class="form-group">
            <label>Тип мероприятия</label>
            <select v-model="eventType">
              <option value="">Не выбрано</option>
              <option value="wedding">Свадьба</option>
              <option value="office">Офис</option>
              <option value="evening">Вечерний выход</option>
              <option value="casual">Каждодневный</option>
            </select>
          </div>

          <div class="form-group">
            <label>
              <input type="checkbox" v-model="isB2B" />
              B2B режим
            </label>
          </div>
        </div>

        <button
          class="analyze-btn"
          :disabled="loading || !photo"
          @click="analyzeStyle"
        >
          {{ loading ? 'Анализируем...' : 'Анализировать' }}
        </button>

        <div v-if="error" class="error-message">{{ error }}</div>
      </div>

      <div class="results-section" v-if="result">
        <div class="style-profile">
          <h3>Ваш цветотип</h3>
          <div class="color-type-badge">{{ colorTypeLabel }}</div>
          
          <h4>Тип фигуры</h4>
          <div class="figure-type-badge">{{ figureTypeLabel }}</div>

          <h4>Рекомендуемая палитра</h4>
          <div class="color-palette">
            <div
              v-for="color in result.style_profile.preferred_palette"
              :key="color"
              class="color-swatch"
              :style="{ backgroundColor: color }"
            />
          </div>

          <h4>Рекомендуемые фасоны</h4>
          <div class="tags">
            <span
              v-for="cut in result.style_profile.recommended_cuts"
              :key="cut"
              class="tag recommended"
            >
              {{ cut }}
            </span>
          </div>

          <h4>Избегать</h4>
          <div class="tags">
            <span
              v-for="cut in result.style_profile.avoid_cuts"
              :key="cut"
              class="tag avoid"
            >
              {{ cut }}
            </span>
          </div>
        </div>

        <div class="capsule-wardrobe">
          <h3>Капсульный гардероб</h3>
          <div class="capsule-grid">
            <div
              v-for="item in result.capsule_wardrobe"
              :key="item.product_id"
              class="capsule-item"
            >
              <div class="item-info">
                <h4>{{ item.name }}</h4>
                <p class="category">{{ item.category }}</p>
                <p class="price">${{ item.price }}</p>
                <div class="match-score">
                  Совпадение: {{ (item.color_match_score * 100).toFixed(0) }}%
                </div>
              </div>
              <button
                v-if="item.ar_try_on_url"
                class="try-on-btn"
                @click="$router.push(item.ar_try_on_url)"
              >
                Примерить AR
              </button>
            </div>
          </div>
        </div>

        <button class="new-analysis-btn" @click="result = null; photo = null; previewUrl = ''">
          Новый анализ
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fashion-style-analyzer {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.analyzer-header {
  text-align: center;
  margin-bottom: 2rem;
}

.analyzer-header h2 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.analyzer-content {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.photo-upload {
  border: 2px dashed #ccc;
  border-radius: 8px;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.3s;
  margin-bottom: 1.5rem;
}

.photo-upload:hover {
  border-color: #4f46e5;
}

.preview img {
  max-width: 100%;
  max-height: 400px;
  border-radius: 8px;
}

.upload-icon {
  width: 48px;
  height: 48px;
  margin-bottom: 1rem;
}

.options {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.form-group select {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.analyze-btn {
  width: 100%;
  padding: 1rem;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.analyze-btn:hover:not(:disabled) {
  background: #4338ca;
}

.analyze-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.error-message {
  margin-top: 1rem;
  padding: 1rem;
  background: #fee2e2;
  color: #991b1b;
  border-radius: 4px;
}

.style-profile {
  margin-bottom: 2rem;
}

.color-type-badge,
.figure-type-badge {
  display: inline-block;
  padding: 0.5rem 1rem;
  background: #e0e7ff;
  color: #4f46e5;
  border-radius: 20px;
  font-weight: 600;
  margin: 0.5rem 0;
}

.color-palette {
  display: flex;
  gap: 0.5rem;
  margin: 1rem 0;
}

.color-swatch {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid #e5e7eb;
}

.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin: 0.5rem 0;
}

.tag {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.875rem;
}

.tag.recommended {
  background: #d1fae5;
  color: #065f46;
}

.tag.avoid {
  background: #fee2e2;
  color: #991b1b;
}

.capsule-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
}

.capsule-item {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem;
}

.item-info h4 {
  margin: 0 0 0.5rem 0;
}

.category {
  color: #6b7280;
  font-size: 0.875rem;
}

.price {
  font-weight: 600;
  color: #4f46e5;
  margin: 0.5rem 0;
}

.match-score {
  font-size: 0.875rem;
  color: #059669;
}

.try-on-btn {
  width: 100%;
  padding: 0.5rem;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 0.5rem;
}

.new-analysis-btn {
  padding: 1rem 2rem;
  background: #6b7280;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.hidden {
  display: none;
}
</style>
