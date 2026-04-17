<template>
  <div class="beauty-master-matching">
    <div class="upload-section">
      <h3>Загрузите фото для подбора мастера</h3>
      <input
        type="file"
        accept="image/*"
        @change="handlePhotoUpload"
        ref="photoInput"
        class="photo-input"
      />
      <button @click="triggerPhotoUpload" class="btn-upload">
        <Camera class="icon" />
        Выбрать фото
      </button>
    </div>

    <div v-if="previewPhoto" class="preview-section">
      <img :src="previewPhoto" alt="Preview" class="preview-image" />
      <button @click="removePhoto" class="btn-remove">Удалить</button>
    </div>

    <div class="filters-section">
      <div class="filter-group">
        <label>Тип услуги:</label>
        <select v-model="filters.serviceType">
          <option value="">Все</option>
          <option value="haircut">Стрижка</option>
          <option value="coloring">Окрашивание</option>
          <option value="styling">Укладка</option>
          <option value="makeup">Макияж</option>
          <option value="facial">Уход за лицом</option>
          <option value="nails">Маникюр/Педикюр</option>
          <option value="spa">SPA</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Пол мастера:</label>
        <select v-model="filters.preferredGender">
          <option value="">Любой</option>
          <option value="male">Мужской</option>
          <option value="female">Женский</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Мин. рейтинг:</label>
        <input type="number" v-model="filters.minRating" min="0" max="5" step="0.1" />
      </div>

      <div class="filter-group">
        <label>Цена от:</label>
        <input type="number" v-model="filters.priceMin" min="0" />
      </div>

      <div class="filter-group">
        <label>Цена до:</label>
        <input type="number" v-model="filters.priceMax" min="0" />
      </div>
    </div>

    <button @click="matchMasters" :disabled="loading || !previewPhoto" class="btn-match">
      <Search class="icon" />
      {{ loading ? 'Подбор...' : 'Найти мастеров' }}
    </button>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>

    <div v-if="result" class="results-section">
      <div class="analysis-info">
        <h4>Анализ фото:</h4>
        <p>Тон кожи: {{ result.analysis.skin_tone }}</p>
        <p>Тип волос: {{ result.analysis.hair_type }}</p>
        <p>Форма лица: {{ result.analysis.face_shape }}</p>
        <p>Уверенность: {{ (result.analysis.confidence * 100).toFixed(0) }}%</p>
      </div>

      <div class="matched-masters">
        <h4>Подобранные мастера ({{ result.total_matches }}):</h4>
        <div v-for="master in result.matched_masters" :key="master.id" class="master-card">
          <img :src="master.avatar" :alt="master.name" class="master-avatar" />
          <div class="master-info">
            <h5>{{ master.name }}</h5>
            <p class="rating">⭐ {{ master.rating }} ({{ master.reviews_count }} отзывов)</p>
            <p class="price">{{ master.base_price }} ₽</p>
            <p class="specializations">{{ master.specializations.join(', ') }}</p>
            <p class="ml-score">Совпадение: {{ (master.ml_score * 100).toFixed(0) }}%</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Camera, Search } from 'lucide-vue-next';

const photoInput = ref<HTMLInputElement | null>(null);
const previewPhoto = ref<string | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);
const result = ref<any>(null);

const filters = reactive({
  serviceType: '',
  preferredGender: '',
  minRating: 0,
  priceMin: 0,
  priceMax: 0,
});

const triggerPhotoUpload = () => {
  photoInput.value?.click();
};

const handlePhotoUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      previewPhoto.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
};

const removePhoto = () => {
  previewPhoto.value = null;
  if (photoInput.value) {
    photoInput.value.value = '';
  }
};

const matchMasters = async () => {
  if (!previewPhoto.value) return;

  loading.value = true;
  error.value = null;

  try {
    const formData = new FormData();
    formData.append('photo', photoInput.value);
    formData.append('user_id', '1');
    formData.append('service_type', filters.serviceType);
    formData.append('preferred_gender', filters.preferredGender);
    formData.append('min_rating', filters.minRating.toString());
    formData.append('price_min', filters.priceMin.toString());
    formData.append('price_max', filters.priceMax.toString());

    const response = await fetch('/api/beauty/masters/match-by-photo', {
      method: 'POST',
      headers: {
        'X-Correlation-ID': crypto.randomUUID(),
        'X-Tenant-ID': '1',
      },
      body: formData,
    });

    const data = await response.json();
    
    if (data.success) {
      result.value = data.data;
    } else {
      error.value = 'Ошибка при подборе мастеров';
    }
  } catch (err) {
    error.value = 'Произошла ошибка. Попробуйте позже.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.beauty-master-matching {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.upload-section {
  text-align: center;
  margin-bottom: 20px;
}

.photo-input {
  display: none;
}

.btn-upload {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
  transition: transform 0.2s;
}

.btn-upload:hover {
  transform: scale(1.05);
}

.preview-section {
  text-align: center;
  margin-bottom: 20px;
}

.preview-image {
  max-width: 400px;
  max-height: 400px;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.btn-remove {
  margin-top: 10px;
  padding: 8px 16px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.filters-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
  padding: 20px;
  background: #f9fafb;
  border-radius: 8px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.filter-group label {
  font-weight: 600;
  font-size: 14px;
  color: #374151;
}

.filter-group select,
.filter-group input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.btn-match {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 28px;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
  transition: transform 0.2s;
  width: 100%;
  justify-content: center;
}

.btn-match:hover:not(:disabled) {
  transform: scale(1.02);
}

.btn-match:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error-message {
  margin-top: 16px;
  padding: 12px;
  background: #fee2e2;
  color: #dc2626;
  border-radius: 6px;
  text-align: center;
}

.results-section {
  margin-top: 24px;
}

.analysis-info {
  padding: 16px;
  background: #e0e7ff;
  border-radius: 8px;
  margin-bottom: 20px;
}

.analysis-info h4 {
  margin: 0 0 8px 0;
  color: #3730a3;
}

.analysis-info p {
  margin: 4px 0;
  color: #4338ca;
}

.matched-masters {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
}

.master-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px;
  background: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s;
}

.master-card:hover {
  transform: translateY(-4px);
}

.master-avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  object-fit: cover;
  margin-bottom: 12px;
}

.master-info h5 {
  margin: 0 0 8px 0;
  color: #111827;
}

.master-info .rating {
  margin: 4px 0;
  color: #f59e0b;
  font-weight: 600;
}

.master-info .price {
  margin: 4px 0;
  color: #10b981;
  font-weight: 700;
  font-size: 18px;
}

.master-info .specializations {
  margin: 4px 0;
  color: #6b7280;
  font-size: 14px;
}

.master-info .ml-score {
  margin: 8px 0 0 0;
  color: #6366f1;
  font-weight: 700;
  font-size: 14px;
}

.icon {
  width: 20px;
  height: 20px;
}
</style>
