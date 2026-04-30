<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute } from 'vue-router';

interface Courier {
  id: number;
  vehicle_type: string;
  is_taxi_driver: boolean;
  current_lat: number;
  current_lng: number;
  rating: number;
  battery_level?: number;
}

interface Shipment {
  id: number;
  uuid: string;
  status: string;
  fulfillment_type: string;
  eta_minutes: number;
  distance_km: number;
  courier?: Courier;
  pickup_point?: {
    id: number;
    name: string;
    address: string;
    lat: number;
    lng: number;
  };
  assigned_at?: string;
  delivered_at?: string;
  qr_code?: string;
  pickup_code?: string;
}

interface Props {
  shipmentId: number;
}

const props = defineProps<Props>();
const route = useRoute();

const shipment = ref<Shipment | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);
const refreshInterval = ref<number | null>(null);

const statusColor = computed(() => {
  if (!shipment.value) return 'gray';
  const colors: Record<string, string> = {
    pending: 'gray',
    assigned: 'blue',
    picked: 'warning',
    in_transit: 'info',
    delivered: 'success',
    issued_at_pvz: 'success',
    cancelled: 'danger',
    failed: 'danger',
  };
  return colors[shipment.value.status] || 'gray';
});

const statusText = computed(() => {
  if (!shipment.value) return '';
  const texts: Record<string, string> = {
    pending: 'Ожидает назначения',
    assigned: 'Назначен курьер',
    picked: 'Забран курьером',
    in_transit: 'В пути',
    delivered: 'Доставлен',
    issued_at_pvz: 'Выдан в ПВЗ',
    cancelled: 'Отменен',
    failed: 'Ошибка доставки',
  };
  return texts[shipment.value.status] || shipment.value.status;
});

const isCourierBased = computed(() => {
  return shipment.value?.fulfillment_type === 'courier' || shipment.value?.fulfillment_type === 'taxi';
});

const isPvzBased = computed(() => {
  return shipment.value?.fulfillment_type === 'pickup_point';
});

const isActive = computed(() => {
  if (!shipment.value) return false;
  return ['pending', 'assigned', 'picked', 'in_transit'].includes(shipment.value.status);
});

const fetchShipment = async () => {
  try {
    loading.value = true;
    error.value = null;
    
    const response = await fetch(`/api/logistics/shipments/${props.shipmentId}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'X-Correlation-ID': crypto.randomUUID(),
      },
    });
    
    if (!response.ok) {
      throw new Error('Failed to fetch shipment');
    }
    
    const data = await response.json();
    shipment.value = data.data;
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unknown error';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchShipment();
  
  // Auto-refresh every 30 seconds for active shipments
  if (isActive.value) {
    refreshInterval.value = window.setInterval(fetchShipment, 30000);
  }
});

onUnmounted(() => {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value);
  }
});

const formatTimestamp = (timestamp?: string) => {
  if (!timestamp) return '-';
  return new Date(timestamp).toLocaleString('ru-RU');
};
</script>

<template>
  <div class="logistics-tracker">
    <div v-if="loading" class="loading">
      Загрузка данных...
    </div>
    
    <div v-else-if="error" class="error">
      Ошибка: {{ error }}
    </div>
    
    <div v-else-if="shipment" class="shipment-details">
      <!-- Status Badge -->
      <div class="status-badge" :class="statusColor">
        {{ statusText }}
      </div>
      
      <!-- Courier Information -->
      <div v-if="isCourierBased && shipment.courier" class="courier-info">
        <h3>Курьер</h3>
        <div class="courier-details">
          <div class="detail-row">
            <span class="label">Тип:</span>
            <span class="value">
              {{ shipment.courier.is_taxi_driver ? 'Такси' : shipment.courier.vehicle_type }}
            </span>
          </div>
          <div class="detail-row">
            <span class="label">Рейтинг:</span>
            <span class="value">⭐ {{ shipment.courier.rating?.toFixed(1) }}</span>
          </div>
          <div v-if="shipment.courier.battery_level" class="detail-row">
            <span class="label">Батарея:</span>
            <span class="value">{{ shipment.courier.battery_level }}%</span>
          </div>
        </div>
      </div>
      
      <!-- PVZ Information -->
      <div v-if="isPvzBased && shipment.pickup_point" class="pvz-info">
        <h3>Пункт выдачи</h3>
        <div class="pvz-details">
          <div class="detail-row">
            <span class="label">Название:</span>
            <span class="value">{{ shipment.pickup_point.name }}</span>
          </div>
          <div class="detail-row">
            <span class="label">Адрес:</span>
            <span class="value">{{ shipment.pickup_point.address }}</span>
          </div>
          <div v-if="shipment.pickup_code" class="pickup-code">
            <span class="label">Код выдачи:</span>
            <span class="code">{{ shipment.pickup_code }}</span>
          </div>
          <div v-if="shipment.qr_code" class="qr-code">
            <span class="label">QR-код:</span>
            <span class="code">{{ shipment.qr_code }}</span>
          </div>
        </div>
      </div>
      
      <!-- ETA and Distance -->
      <div class="eta-info">
        <div class="detail-row">
          <span class="label">ETA:</span>
          <span class="value">{{ shipment.eta_minutes }} мин</span>
        </div>
        <div class="detail-row">
          <span class="label">Расстояние:</span>
          <span class="value">{{ shipment.distance_km?.toFixed(2) }} км</span>
        </div>
      </div>
      
      <!-- Timeline -->
      <div class="timeline">
        <div class="timeline-item" :class="{ active: shipment.status !== 'pending' }">
          <div class="timeline-dot"></div>
          <div class="timeline-content">
            <div class="timeline-title">Назначен</div>
            <div class="timeline-time">{{ formatTimestamp(shipment.assigned_at) }}</div>
          </div>
        </div>
        
        <div v-if="isCourierBased" class="timeline-item" :class="{ active: ['picked', 'in_transit', 'delivered'].includes(shipment.status) }">
          <div class="timeline-dot"></div>
          <div class="timeline-content">
            <div class="timeline-title">Забран</div>
            <div class="timeline-time">{{ shipment.picked_at ? formatTimestamp(shipment.picked_at) : '-' }}</div>
          </div>
        </div>
        
        <div class="timeline-item" :class="{ active: ['delivered', 'issued_at_pvz'].includes(shipment.status) }">
          <div class="timeline-dot"></div>
          <div class="timeline-content">
            <div class="timeline-title">{{ isCourierBased ? 'Доставлен' : 'Выдан' }}</div>
            <div class="timeline-time">{{ formatTimestamp(shipment.delivered_at || shipment.issued_at_pvz) }}</div>
          </div>
        </div>
      </div>
      
      <!-- Refresh Button -->
      <button @click="fetchShipment" class="refresh-btn">
        Обновить
      </button>
    </div>
  </div>
</template>

<style scoped>
.logistics-tracker {
  padding: 20px;
  max-width: 600px;
  margin: 0 auto;
}

.loading, .error {
  text-align: center;
  padding: 40px;
  font-size: 16px;
}

.error {
  color: #dc2626;
}

.shipment-details {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.status-badge {
  display: inline-block;
  padding: 8px 16px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 20px;
}

.status-badge.gray { background: #f3f4f6; color: #6b7280; }
.status-badge.blue { background: #dbeafe; color: #2563eb; }
.status-badge.warning { background: #fef3c7; color: #d97706; }
.status-badge.info { background: #e0f2fe; color: #0891b2; }
.status-badge.success { background: #d1fae5; color: #059669; }
.status-badge.danger { background: #fee2e2; color: #dc2626; }

.courier-info, .pvz-info, .eta-info {
  margin-bottom: 24px;
  padding-bottom: 24px;
  border-bottom: 1px solid #e5e7eb;
}

.courier-info h3, .pvz-info h3 {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
}

.label {
  color: #6b7280;
  font-size: 14px;
}

.value {
  color: #111827;
  font-weight: 500;
  font-size: 14px;
}

.pickup-code, .qr-code {
  margin-top: 12px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 8px;
}

.code {
  font-family: monospace;
  font-size: 16px;
  font-weight: 600;
  letter-spacing: 2px;
}

.timeline {
  margin: 24px 0;
}

.timeline-item {
  display: flex;
  align-items: flex-start;
  padding: 12px 0;
  position: relative;
}

.timeline-item:not(:last-child)::before {
  content: '';
  position: absolute;
  left: 8px;
  top: 20px;
  bottom: 0;
  width: 2px;
  background: #e5e7eb;
}

.timeline-dot {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #e5e7eb;
  margin-right: 16px;
  flex-shrink: 0;
}

.timeline-item.active .timeline-dot {
  background: #10b981;
}

.timeline-content {
  flex: 1;
}

.timeline-title {
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 4px;
}

.timeline-time {
  color: #6b7280;
  font-size: 12px;
}

.refresh-btn {
  width: 100%;
  padding: 12px;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.refresh-btn:hover {
  background: #059669;
}
</style>
