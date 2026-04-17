<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { modelViewer } from '@google/model-viewer';

interface Property {
  id: number;
  uuid: string;
  title: string;
  description: string;
  address: string;
  price: number;
  type: string;
  area_sqm: number;
  photos: string[];
  features: {
    ai_virtual_tour_url?: string;
    ar_viewing_url?: string;
    webrtc_enabled?: boolean;
    dynamic_pricing_enabled?: boolean;
  };
}

interface ViewingSlot {
  date: string;
  time: string;
  available: boolean;
}

const props = defineProps<{
  property: Property;
}>();

const emit = defineEmits<{
  (e: 'book-viewing', slot: ViewingSlot): void;
  (e: 'start-virtual-tour'): void;
  (e: 'start-ar-viewing'): void;
  (e: 'video-call'): void;
}>();

const selectedViewingDate = ref<string>('');
const selectedViewingTime = ref<string>('');
const isVirtualTourActive = ref(false);
const isARViewingActive = ref(false);
const isVideoCallActive = ref(false);
const dynamicPrice = ref<number>(props.property.price);

const viewingSlots = ref<ViewingSlot[]>([]);
const loading = ref(false);

const pricePerSqm = computed(() => {
  return (dynamicPrice.value / props.property.area_sqm).toFixed(2);
});

const formattedPrice = computed(() => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
  }).format(dynamicPrice.value);
});

const canBookViewing = computed(() => {
  return selectedViewingDate.value && selectedViewingTime.value;
});

const loadViewingSlots = async () => {
  loading.value = true;
  try {
    const response = await fetch(`/api/v1/real-estate/properties/${props.property.id}/viewing-slots`);
    const data = await response.json();
    viewingSlots.value = data.slots;
  } catch (error) {
    console.error('Failed to load viewing slots:', error);
  } finally {
    loading.value = false;
  }
};

const loadDynamicPrice = async () => {
  try {
    const response = await fetch(`/api/v1/real-estate/transactions/properties/${props.property.id}/pricing`);
    const data = await response.json();
    if (data.success) {
      dynamicPrice.value = data.pricing.final_price;
    }
  } catch (error) {
    console.error('Failed to load dynamic price:', error);
  }
};

const bookViewing = () => {
  const slot = viewingSlots.value.find(
    s => s.date === selectedViewingDate.value && s.time === selectedViewingTime.value
  );
  if (slot) {
    emit('book-viewing', slot);
  }
};

const startVirtualTour = () => {
  isVirtualTourActive.value = true;
  emit('start-virtual-tour');
};

const startARViewing = () => {
  isARViewingActive.value = true;
  emit('start-ar-viewing');
};

const startVideoCall = () => {
  isVideoCallActive.value = true;
  emit('video-call');
};

const closeVirtualTour = () => {
  isVirtualTourActive.value = false;
};

const closeARViewing = () => {
  isARViewingActive.value = false;
};

const closeVideoCall = () => {
  isVideoCallActive.value = false;
};

onMounted(() => {
  loadViewingSlots();
  loadDynamicPrice();
});

onUnmounted(() => {
  isVirtualTourActive.value = false;
  isARViewingActive.value = false;
  isVideoCallActive.value = false;
});
</script>

<template>
  <div class="property-viewer">
    <div class="property-header">
      <h1 class="property-title">{{ property.title }}</h1>
      <p class="property-address">{{ property.address }}</p>
      <div class="property-price">
        <span class="price">{{ formattedPrice }}</span>
        <span class="price-per-sqm">{{ pricePerSqm }} ₽/м²</span>
      </div>
    </div>

    <div class="property-gallery">
      <div v-for="(photo, index) in property.photos" :key="index" class="photo-item">
        <img :src="photo" :alt="`${property.title} - фото ${index + 1}`" />
      </div>
    </div>

    <div class="property-features">
      <div class="feature-item">
        <span class="feature-label">Площадь:</span>
        <span class="feature-value">{{ property.area_sqm }} м²</span>
      </div>
      <div class="feature-item">
        <span class="feature-label">Тип:</span>
        <span class="feature-value">{{ property.type }}</span>
      </div>
      <div v-if="property.features.webrtc_enabled" class="feature-item">
        <span class="feature-label">Видео-звонок:</span>
        <span class="feature-value">Доступен</span>
      </div>
    </div>

    <div class="property-description">
      <h2>Описание</h2>
      <p>{{ property.description }}</p>
    </div>

    <div class="ai-features">
      <h2>AI-функции</h2>
      <div class="ai-buttons">
        <button
          v-if="property.features.ai_virtual_tour_url"
          @click="startVirtualTour"
          class="ai-button virtual-tour"
        >
          <span class="icon">🎥</span>
          Виртуальный тур 360°
        </button>
        <button
          v-if="property.features.ar_viewing_url"
          @click="startARViewing"
          class="ai-button ar-viewing"
        >
          <span class="icon">📱</span>
          AR-просмотр
        </button>
        <button
          v-if="property.features.webrtc_enabled"
          @click="startVideoCall"
          class="ai-button video-call"
        >
          <span class="icon">📞</span>
          Видео-звонок
        </button>
      </div>
    </div>

    <div class="viewing-booking">
      <h2>Бронирование просмотра</h2>
      <div v-if="loading" class="loading">Загрузка слотов...</div>
      <div v-else class="booking-form">
        <div class="form-group">
          <label>Дата:</label>
          <select v-model="selectedViewingDate">
            <option value="">Выберите дату</option>
            <option v-for="slot in viewingSlots" :key="slot.date" :value="slot.date">
              {{ slot.date }}
            </option>
          </select>
        </div>
        <div class="form-group">
          <label>Время:</label>
          <select v-model="selectedViewingTime">
            <option value="">Выберите время</option>
            <option
              v-for="slot in viewingSlots.filter(s => s.date === selectedViewingDate)"
              :key="`${slot.date}-${slot.time}`"
              :value="slot.time"
              :disabled="!slot.available"
            >
              {{ slot.time }} {{ !slot.available ? '(занято)' : '' }}
            </option>
          </select>
        </div>
        <button
          @click="bookViewing"
          :disabled="!canBookViewing"
          class="book-button"
        >
          Забронировать
        </button>
      </div>
    </div>

    <!-- Virtual Tour Modal -->
    <div v-if="isVirtualTourActive" class="modal virtual-tour-modal" @click="closeVirtualTour">
      <div class="modal-content" @click.stop>
        <button class="close-button" @click="closeVirtualTour">×</button>
        <model-viewer
          :src="property.features.ai_virtual_tour_url"
          auto-rotate
          camera-controls
          ar-modes="webxr scene-viewer quick-look"
          shadow-intensity="1"
          camera-orbit="-90deg 75deg 2.5m"
        ></model-viewer>
      </div>
    </div>

    <!-- AR Viewing Modal -->
    <div v-if="isARViewingActive" class="modal ar-modal" @click="closeARViewing">
      <div class="modal-content" @click.stop>
        <button class="close-button" @click="closeARViewing">×</button>
        <div class="ar-viewer">
          <p>AR-просмотр загружается...</p>
          <p>Убедитесь, что вы используете устройство с поддержкой AR</p>
          <a :href="property.features.ar_viewing_url" target="_blank" class="ar-link">
            Открыть AR-просмотр
          </a>
        </div>
      </div>
    </div>

    <!-- Video Call Modal -->
    <div v-if="isVideoCallActive" class="modal video-call-modal" @click="closeVideoCall">
      <div class="modal-content" @click.stop>
        <button class="close-button" @click="closeVideoCall">×</button>
        <div class="video-call-container">
          <p>Видео-звонок с риелтором</p>
          <p>WebRTC room будет создан после подтверждения бронирования</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.property-viewer {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.property-header {
  margin-bottom: 30px;
}

.property-title {
  font-size: 32px;
  font-weight: 700;
  margin-bottom: 8px;
  color: #1a1a1a;
}

.property-address {
  font-size: 16px;
  color: #666;
  margin-bottom: 16px;
}

.property-price {
  display: flex;
  align-items: baseline;
  gap: 12px;
}

.price {
  font-size: 36px;
  font-weight: 700;
  color: #2563eb;
}

.price-per-sqm {
  font-size: 14px;
  color: #666;
}

.property-gallery {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 16px;
  margin-bottom: 30px;
}

.photo-item img {
  width: 100%;
  height: 250px;
  object-fit: cover;
  border-radius: 8px;
}

.property-features {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 30px;
}

.feature-item {
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
}

.feature-label {
  display: block;
  font-size: 14px;
  color: #666;
  margin-bottom: 4px;
}

.feature-value {
  font-size: 16px;
  font-weight: 600;
  color: #1a1a1a;
}

.property-description {
  margin-bottom: 30px;
}

.property-description h2 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 12px;
  color: #1a1a1a;
}

.property-description p {
  font-size: 16px;
  line-height: 1.6;
  color: #333;
}

.ai-features {
  margin-bottom: 30px;
}

.ai-features h2 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 16px;
  color: #1a1a1a;
}

.ai-buttons {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.ai-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 24px;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.ai-button .icon {
  font-size: 24px;
}

.virtual-tour {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.virtual-tour:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.ar-viewing {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
}

.ar-viewing:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(245, 87, 108, 0.4);
}

.video-call {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  color: white;
}

.video-call:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(79, 172, 254, 0.4);
}

.viewing-booking {
  margin-bottom: 30px;
}

.viewing-booking h2 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 16px;
  color: #1a1a1a;
}

.loading {
  padding: 20px;
  text-align: center;
  color: #666;
}

.booking-form {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  align-items: end;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 14px;
  font-weight: 600;
  color: #333;
}

.form-group select {
  padding: 12px 16px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 16px;
  background: white;
}

.book-button {
  padding: 12px 24px;
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.book-button:hover:not(:disabled) {
  background: #1d4ed8;
}

.book-button:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  position: relative;
  background: white;
  border-radius: 12px;
  padding: 24px;
  max-width: 90vw;
  max-height: 90vh;
  overflow: auto;
}

.close-button {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 32px;
  height: 32px;
  border: none;
  background: #f1f5f9;
  border-radius: 50%;
  font-size: 24px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-button:hover {
  background: #e2e8f0;
}

.virtual-tour-modal model-viewer {
  width: 800px;
  height: 600px;
}

.ar-viewer {
  text-align: center;
  padding: 40px;
}

.ar-link {
  display: inline-block;
  margin-top: 16px;
  padding: 12px 24px;
  background: #2563eb;
  color: white;
  text-decoration: none;
  border-radius: 6px;
  font-weight: 600;
}

.video-call-container {
  text-align: center;
  padding: 40px;
}
</style>
