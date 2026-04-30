<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';

interface PickupPoint {
  id: number;
  name: string;
  address: string;
  lat: number;
  lng: number;
  distance_km: number;
  is_24h: boolean;
  working_hours: string;
  load_percentage: number;
  has_available_slots: boolean;
}

interface Props {
  userLat: number;
  userLng: number;
  radiusKm?: number;
}

const props = withDefaults(defineProps<Props>(), {
  radiusKm: 3.0,
});

const emit = defineEmits<{
  select: [pvz: PickupPoint];
}>();

const pickupPoints = ref<PickupPoint[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const selectedPvz = ref<PickupPoint | null>(null);

const loadPvz = async () => {
  try {
    loading.value = true;
    error.value = null;
    
    const response = await fetch(
      `/api/logistics/pvz/nearby?lat=${props.userLat}&lng=${props.userLng}&radius_km=${props.radiusKm}`,
      {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'X-Correlation-ID': crypto.randomUUID(),
        },
      }
    );
    
    if (!response.ok) {
      throw new Error('Failed to fetch pickup points');
    }
    
    const data = await response.json();
    pickupPoints.value = data.data;
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unknown error';
  } finally {
    loading.value = false;
  }
};

const selectPvz = (pvz: PickupPoint) => {
  selectedPvz.value = pvz;
  emit('select', pvz);
};

const getLoadColor = (percentage: number) => {
  if (percentage < 40) return '#10b981'; // green
  if (percentage < 70) return '#f59e0b'; // yellow
  if (percentage < 85) return '#f97316'; // orange
  return '#ef4444'; // red
};

const formatWorkingHours = (hours: string, is24h: boolean) => {
  if (is24h) return 'Круглосуточно';
  return hours;
};

onMounted(() => {
  loadPvz();
});
</script>

<template>
  <div class="pvz-map">
    <div v-if="loading" class="loading">
      Загрузка пунктов выдачи...
    </div>
    
    <div v-else-if="error" class="error">
      Ошибка: {{ error }}
    </div>
    
    <div v-else class="pvz-container">
      <!-- Map Placeholder -->
      <div class="map-placeholder">
        <div class="map-center">
          <div class="user-marker">📍</div>
          <div class="radius-circle" :style="{ width: radiusKm * 50 + 'px', height: radiusKm * 50 + 'px' }"></div>
        </div>
        <div class="pvz-markers">
          <div
            v-for="pvz in pickupPoints"
            :key="pvz.id"
            class="pvz-marker"
            :class="{ selected: selectedPvz?.id === pvz.id }"
            :style="{
              left: ((pvz.lng - userLng) * 10000 + 150) + 'px',
              top: ((userLat - pvz.lat) * 10000 + 150) + 'px',
            }"
            @click="selectPvz(pvz)"
          >
            <div class="marker-dot" :style="{ background: getLoadColor(pvz.load_percentage) }"></div>
            <div class="marker-label">{{ pvz.distance_km.toFixed(1) }}км</div>
          </div>
        </div>
      </div>
      
      <!-- PVZ List -->
      <div class="pvz-list">
        <h3>Пункты выдачи ({{ pickupPoints.length }})</h3>
        
        <div v-if="pickupPoints.length === 0" class="empty-state">
          В этом районе нет доступных пунктов выдачи
        </div>
        
        <div
          v-for="pvz in pickupPoints"
          :key="pvz.id"
          class="pvz-card"
          :class="{ selected: selectedPvz?.id === pvz.id }"
          @click="selectPvz(pvz)"
        >
          <div class="pvz-header">
            <h4>{{ pvz.name }}</h4>
            <span class="distance">{{ pvz.distance_km.toFixed(1) }} км</span>
          </div>
          
          <div class="pvz-address">{{ pvz.address }}</div>
          
          <div class="pvz-meta">
            <div class="meta-item">
              <span class="meta-icon">🕐</span>
              <span>{{ formatWorkingHours(pvz.working_hours, pvz.is_24h) }}</span>
            </div>
            <div class="meta-item">
              <span class="meta-icon">📦</span>
              <span>Загрузка: {{ pvz.load_percentage.toFixed(0) }}%</span>
            </div>
          </div>
          
          <div class="pvz-status">
            <div class="load-bar">
              <div
                class="load-fill"
                :style="{
                  width: pvz.load_percentage + '%',
                  background: getLoadColor(pvz.load_percentage),
                }"
              ></div>
            </div>
            <span
              v-if="!pvz.has_available_slots"
              class="slots-warning"
            >
              ⚠️ Нет свободных слотов
            </span>
          </div>
          
          <button v-if="selectedPvz?.id === pvz.id" class="select-btn">
            Выбран
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pvz-map {
  display: flex;
  flex-direction: column;
  height: 100%;
  max-height: 800px;
}

.loading, .error {
  text-align: center;
  padding: 40px;
  font-size: 16px;
}

.error {
  color: #dc2626;
}

.pvz-container {
  display: flex;
  flex: 1;
  gap: 20px;
  overflow: hidden;
}

.map-placeholder {
  flex: 1;
  background: #f3f4f6;
  border-radius: 12px;
  position: relative;
  min-height: 300px;
  overflow: hidden;
}

.map-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.user-marker {
  font-size: 32px;
  position: relative;
  z-index: 10;
}

.radius-circle {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  border: 2px dashed #9ca3af;
  border-radius: 50%;
  pointer-events: none;
}

.pvz-markers {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.pvz-marker {
  position: absolute;
  transform: translate(-50%, -50%);
  cursor: pointer;
  transition: transform 0.2s;
}

.pvz-marker:hover {
  transform: translate(-50%, -50%) scale(1.2);
}

.pvz-marker.selected .marker-dot {
  border: 3px solid #1f2937;
}

.marker-dot {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.marker-label {
  position: absolute;
  top: 28px;
  left: 50%;
  transform: translateX(-50%);
  background: white;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.pvz-list {
  flex: 0 0 400px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.pvz-list h3 {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #6b7280;
}

.pvz-list-scroll {
  flex: 1;
  overflow-y: auto;
  padding-right: 8px;
}

.pvz-card {
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.pvz-card:hover {
  border-color: #9ca3af;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.pvz-card.selected {
  border-color: #10b981;
  background: #f0fdf4;
}

.pvz-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 8px;
}

.pvz-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  flex: 1;
}

.distance {
  background: #dbeafe;
  color: #1e40af;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
  margin-left: 8px;
}

.pvz-address {
  color: #6b7280;
  font-size: 14px;
  margin-bottom: 12px;
}

.pvz-meta {
  display: flex;
  gap: 16px;
  margin-bottom: 12px;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 13px;
  color: #4b5563;
}

.meta-icon {
  font-size: 16px;
}

.pvz-status {
  margin-bottom: 12px;
}

.load-bar {
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 8px;
}

.load-fill {
  height: 100%;
  transition: width 0.3s;
}

.slots-warning {
  color: #dc2626;
  font-size: 12px;
  font-weight: 600;
}

.select-btn {
  width: 100%;
  padding: 10px;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.select-btn:hover {
  background: #059669;
}

@media (max-width: 768px) {
  .pvz-container {
    flex-direction: column;
  }
  
  .map-placeholder {
    min-height: 250px;
  }
  
  .pvz-list {
    flex: 1;
    flex-basis: auto;
  }
}
</style>
