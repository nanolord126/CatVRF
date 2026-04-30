<template>
  <div class="supermarket-order-tracker">
    <!-- Order Header -->
    <div class="order-header">
      <div class="order-info">
        <h3 class="order-number">Заказ #{{ order.order_number }}</h3>
        <div class="order-meta">
          <span class="delivery-type" :class="order.delivery_type">
            {{ order.delivery_type === 'courier' ? '🚚 Доставка' : '📍 Самозабор' }}
          </span>
          <span class="total-amount">{{ formatCurrency(order.total_amount) }}</span>
        </div>
      </div>
      <div class="order-status">
        <span class="status-badge" :class="order.order_status">
          {{ getStatusLabel(order.order_status) }}
        </span>
      </div>
    </div>

    <!-- Progress Bar -->
    <div class="progress-section">
      <div class="progress-bar">
        <div 
          class="progress-fill" 
          :style="{ width: progressPercentage + '%' }"
          :class="getProgressColor(order.order_status)"
        ></div>
      </div>
      <div class="progress-labels">
        <span 
          v-for="(label, index) in progressSteps" 
          :key="index"
          class="progress-label"
          :class="{ active: currentStepIndex >= index }"
        >
          {{ label }}
        </span>
      </div>
    </div>

    <!-- Timeline -->
    <div class="timeline">
      <div 
        v-for="(event, index) in timelineEvents" 
        :key="index"
        class="timeline-item"
        :class="getTimelineClass(event.status)"
      >
        <div class="timeline-content">
          <div class="timeline-header">
            <span class="timeline-title">{{ event.title }}</span>
            <span class="timeline-time">{{ formatTime(event.timestamp) }}</span>
          </div>
          <p class="timeline-description">{{ event.description }}</p>
          <div v-if="event.details" class="timeline-details">
            <div v-for="(value, key) in event.details" :key="key" class="detail-row">
              <span class="detail-key">{{ key }}:</span>
              <span class="detail-value">{{ value }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Courier Info (for delivery) -->
    <div v-if="order.delivery_type === 'courier' && order.courier_name" class="courier-info">
      <div class="courier-header">
        <h4>🚚 Курьер</h4>
      </div>
      <div class="courier-details">
        <div class="courier-row">
          <span class="courier-label">Имя:</span>
          <span class="courier-value">{{ order.courier_name }}</span>
        </div>
        <div class="courier-row">
          <span class="courier-label">Телефон:</span>
          <a :href="'tel:' + order.courier_phone" class="courier-value courier-phone">
            {{ order.courier_phone }}
          </a>
        </div>
        <div v-if="order.delivery_eta" class="courier-row">
          <span class="courier-label">Ожидаемое время:</span>
          <span class="courier-value eta">{{ formatTime(order.delivery_eta) }}</span>
        </div>
      </div>
      <div v-if="showMap && order.courier_location_lat" class="courier-map">
        <CourierMarker 
          :lat="order.courier_location_lat" 
          :lng="order.courier_location_lng"
          :destination="order.delivery_address"
        />
      </div>
    </div>

    <!-- Pickup Info (for pickup) -->
    <div v-if="order.delivery_type === 'pickup'" class="pickup-info">
      <div class="pickup-header">
        <h4>📍 Самозабор</h4>
      </div>
      <div class="pickup-details">
        <div v-if="order.pickup_zone" class="pickup-row">
          <span class="pickup-label">Зона выдачи:</span>
          <span class="pickup-value">{{ order.pickup_zone }}</span>
        </div>
        <div v-if="order.locker_number" class="pickup-row">
          <span class="pickup-label">Ячейка:</span>
          <span class="pickup-value locker">{{ order.locker_number }}</span>
        </div>
        <div v-if="order.pickup_window_start && order.pickup_window_end" class="pickup-row">
          <span class="pickup-label">Время выдачи:</span>
          <span class="pickup-value window">
            {{ formatTime(order.pickup_window_start) }} - {{ formatTime(order.pickup_window_end) }}
          </span>
        </div>
        <div v-if="order.qr_code" class="pickup-qr">
          <div class="qr-code">
            <img :src="'/api/qr/' + order.qr_code" alt="QR Code" />
          </div>
          <p class="qr-hint">Покажите этот QR код при получении</p>
        </div>
      </div>
    </div>

    <!-- Nutritional Summary -->
    <div v-if="showNutrition && order.total_calories > 0" class="nutritional-summary">
      <div class="nutrition-header">
        <h4>🥗 Нутриенты</h4>
      </div>
      <div class="nutrition-chart">
        <div class="nutritional-bar">
          <div 
            class="nutritional-segment proteins" 
            :style="{ width: getProteinPercentage(order) + '%' }"
          ></div>
          <div 
            class="nutritional-segment fats" 
            :style="{ width: getFatPercentage(order) + '%' }"
          ></div>
          <div 
            class="nutritional-segment carbs" 
            :style="{ width: getCarbPercentage(order) + '%' }"
          ></div>
        </div>
        <div class="nutrition-legend">
          <div class="legend-item">
            <span class="legend-color proteins"></span>
            <span class="legend-label">Белки: {{ order.total_proteins }}г</span>
          </div>
          <div class="legend-item">
            <span class="legend-color fats"></span>
            <span class="legend-label">Жиры: {{ order.total_fats }}г</span>
          </div>
          <div class="legend-item">
            <span class="legend-color carbs"></span>
            <span class="legend-label">Углеводы: {{ order.total_carbs }}г</span>
          </div>
        </div>
        <div class="nutrition-calories">
          <span class="calories-value">{{ order.total_calories }}</span>
          <span class="calories-label">ккал</span>
        </div>
      </div>
    </div>

    <!-- Allergen Warnings -->
    <div v-if="order.allergen_warnings && order.allergen_warnings.length > 0" class="allergen-warnings">
      <div v-for="(warning, index) in order.allergen_warnings" :key="index" 
           class="allergen-warning" :class="warning.severity">
        <span class="warning-icon">⚠️</span>
        <span class="warning-message">{{ warning.message }}</span>
      </div>
    </div>

    <!-- Dietary Badges -->
    <div v-if="order.dietary_restrictions && order.dietary_restrictions.length > 0" class="dietary-badges">
      <span v-for="restriction in order.dietary_restrictions" :key="restriction" 
            class="dietary-badge" :class="restriction">
        {{ getDietaryLabel(restriction) }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import CourierMarker from '@/components/CourierMarker.vue';

interface OrderEvent {
  status: string;
  title: string;
  description: string;
  timestamp: string;
  details?: Record<string, string>;
}

interface SupermarketOrder {
  order_number: string;
  delivery_type: 'courier' | 'pickup';
  order_status: string;
  total_amount: number;
  courier_name?: string;
  courier_phone?: string;
  courier_location_lat?: number;
  courier_location_lng?: number;
  delivery_eta?: string;
  delivery_address?: string;
  pickup_zone?: string;
  locker_number?: string;
  pickup_window_start?: string;
  pickup_window_end?: string;
  qr_code?: string;
  total_calories?: number;
  total_proteins?: number;
  total_fats?: number;
  total_carbs?: number;
  allergen_warnings?: Array<{ severity: string; message: string }>;
  dietary_restrictions?: string[];
}

const props = defineProps<{
  order: SupermarketOrder;
  showMap?: boolean;
  showNutrition?: boolean;
}>();

const progressSteps = [
  'Создан',
  'Подтверждён',
  'В сборке',
  'Готов',
  'В пути',
  'Доставлен'
];

const timelineEvents = computed<OrderEvent[]>(() => {
  const events: OrderEvent[] = [
    {
      status: 'created',
      title: 'Заказ создан',
      description: 'Ваш заказ успешно оформлен',
      timestamp: props.order.created_at || new Date().toISOString(),
    }
  ];

  if (props.order.order_status === 'confirmed' || 
      ['processing', 'ready', 'delivering', 'delivered', 'picked_up'].includes(props.order.order_status)) {
    events.push({
      status: 'confirmed',
      title: 'Заказ подтверждён',
      description: 'Оплата прошла успешно, заказ передан в сборку',
      timestamp: props.order.confirmed_at || new Date().toISOString(),
    });
  }

  if (props.order.order_status === 'processing' || 
      ['ready', 'delivering', 'delivered', 'picked_up'].includes(props.order.order_status)) {
    events.push({
      status: 'processing',
      title: 'Сборка заказа',
      description: 'Заказ собирается на складе',
      timestamp: props.order.processing_started_at || new Date().toISOString(),
    });
  }

  if (props.order.order_status === 'ready' || 
      ['delivering', 'delivered', 'picked_up'].includes(props.order.order_status)) {
    events.push({
      status: 'ready',
      title: props.order.delivery_type === 'courier' ? 'Передан курьеру' : 'Готов к выдаче',
      description: props.order.delivery_type === 'courier' 
        ? 'Заказ передан курьеру для доставки'
        : 'Заказ готов к самозабору',
      timestamp: props.order.pickup_ready_at || props.order.courier_assigned_at || new Date().toISOString(),
    });
  }

  if (props.order.delivery_type === 'courier' && 
      ['delivering', 'delivered'].includes(props.order.order_status)) {
    events.push({
      status: 'delivering',
      title: 'Курьер в пути',
      description: 'Курьер едет к вам',
      timestamp: props.order.courier_assigned_at || new Date().toISOString(),
      details: props.order.courier_name ? {
        'Курьер': props.order.courier_name,
        'Телефон': props.order.courier_phone || '',
      } : undefined,
    });
  }

  if (props.order.order_status === 'delivered' || props.order.order_status === 'picked_up') {
    events.push({
      status: 'delivered',
      title: props.order.delivery_type === 'courier' ? 'Доставлен' : 'Получен',
      description: props.order.delivery_type === 'courier' 
        ? 'Заказ успешно доставлен'
        : 'Заказ получен',
      timestamp: props.order.delivery_actual_at || props.order.pickup_actual_at || new Date().toISOString(),
    });
  }

  return events;
});

const currentStepIndex = computed(() => {
  const statusOrder = ['pending', 'confirmed', 'processing', 'ready', 'delivering', 'delivered'];
  const currentIndex = statusOrder.indexOf(props.order.order_status);
  return currentIndex === -1 ? 0 : currentIndex;
});

const progressPercentage = computed(() => {
  return (currentStepIndex.value / (progressSteps.length - 1)) * 100;
});

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    pending: 'Ожидает',
    confirmed: 'Подтверждён',
    processing: 'В сборке',
    ready: 'Готов',
    delivering: 'В пути',
    delivered: 'Доставлен',
    picked_up: 'Получен',
    cancelled: 'Отменён',
    failed: 'Ошибка',
  };
  return labels[status] || status;
}

function getTimelineClass(status: string): string {
  const currentIndex = currentStepIndex.value;
  const statusOrder = ['created', 'confirmed', 'processing', 'ready', 'delivering', 'delivered'];
  const eventIndex = statusOrder.indexOf(status);
  
  if (eventIndex < currentIndex) return 'completed';
  if (eventIndex === currentIndex) return 'current';
  return 'pending';
}

function getProgressColor(status: string): string {
  const colors: Record<string, string> = {
    pending: 'pending',
    confirmed: 'confirmed',
    processing: 'processing',
    ready: 'ready',
    delivering: 'delivering',
    delivered: 'delivered',
    picked_up: 'delivered',
  };
  return colors[status] || 'pending';
}

function formatTime(dateStr: string): string {
  const date = new Date(dateStr);
  return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
}

function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(amount);
}

function getProteinPercentage(order: SupermarketOrder): number {
  if (!order.total_proteins) return 0;
  const total = (order.total_proteins || 0) + (order.total_fats || 0) + (order.total_carbs || 0);
  return total > 0 ? (order.total_proteins / total) * 100 : 0;
}

function getFatPercentage(order: SupermarketOrder): number {
  if (!order.total_fats) return 0;
  const total = (order.total_proteins || 0) + (order.total_fats || 0) + (order.total_carbs || 0);
  return total > 0 ? (order.total_fats / total) * 100 : 0;
}

function getCarbPercentage(order: SupermarketOrder): number {
  if (!order.total_carbs) return 0;
  const total = (order.total_proteins || 0) + (order.total_fats || 0) + (order.total_carbs || 0);
  return total > 0 ? (order.total_carbs / total) * 100 : 0;
}

function getDietaryLabel(restriction: string): string {
  const labels: Record<string, string> = {
    halal: 'Халяль',
    kosher: 'Кошерное',
    vegan: 'Веган',
    vegetarian: 'Вегетарианское',
    gluten_free: 'Без глютена',
    lactose_free: 'Без лактозы',
  };
  return labels[restriction] || restriction;
}
</script>

<style scoped>
.supermarket-order-tracker {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
 

  text-transform: uppercase;
}

.progress-section {
  margin-bottom: 2rem;
}

.progress-bar {
  height: 0.5rem;
  background-color: #e5e7eb;
  border-radius: 9999px;
  overflow: hidden;
  margin-bottom: 0.75rem;
}

.progress-fill {
  height: 100%;
  border-radius: 9999px;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.progress-fill::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.3),
    transparent
  );
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.progress-fill.pending {
  background: linear-gradient(90deg, #fbbf24, #f59e0b);
}
deliveringP 1.5seae-in-oute;
}

@keyframes delivringPulse {
  0%, 100% {
    opacity: 1
    box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.4);
  }
  50% {
    opacity: 0.8;
    box-shadow: 0 0 0 8px rgba(56, 189, 248, 0);
  }
.progress-fill.confirmed {
  background: linear-gradient(90deg, #60a5fa, #3b82f6);
}

.progress-fill.processing {
  background: linear-gradient(90deg, #818cf8, #6366f1);
}

.progress-fill.ready {
  background: linear-gradient(90deg, #34d399, #10b981);
}

.progress-fill.delivering {
  background: linear-gradient(90deg, #38bdf8, #0ea5e9);
  animation: pulse 2s infinite;
}

.progress-fill.delivered {
  background: linear-gradient(90deg, #22c55e, #16a34a);
}

.progress-labels {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: #6b7280;
}

.progress-label.active {
  color: #111827;
  font-weight: 600;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.timeline {
  position: relative;
  padding-left: 2rem;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 0.5rem;
  top: 0;
  bottom: 0;
  width: 2px;
  background-color: #e5e7eb;
}

.timeline-item {
  position: relative4cubic-bezir(0.4, 0, 0.2, 1);
  z-indx: 1
  margin-bottom: 1.5rem;
}

.timeline-item::before {
  content: '';
  position: absolute;
  left: -1.75rem;
  top: 0.25rem;
  width: 1rem;
  height: 1rem;
  border-radius: 50%;
  background-color: #e5e7eb;
  border: 2px solid white;
  transition: all 0.3s ease;
}5e;
  animation: scaleIn 0.3s ease-out;
}

@keyframes scaleIn {
  from {
    transform: scale(0.);
    opacity: 0;
  }
  to {
    transform: scal(1);
    opacity: 1
  }

.timeline-item.completed::before {
  background-color: #22c55e;
}
currentP 1.5seae-in-out
  box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.4);
}

@keyframes currentPulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.4);
  }
  50% {
    transform: scale(1.1);
    box-shadow: 0 0 0 8px rgba(249, 115, 22, 0);
  }
.timeline-item.current::before {
  background-color: #f97316;
  animation: pulse 2s infinite;
}

.timeline-item.pending::before {
  background-color: #d1d5db;
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.25rem;
}

.timeline-title {
  font-weight: 600;
  color: #111827;
}

.timeline-time {
  font-size: 0.75rem;
  color: #6b7280;
}

.timeline-description {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0;
}

.timeline-details {
  margin-top: 0.5rem;
  padding: 0.5rem;
  background-color: #f9fafb;
  border-radius: 0.5rem;
}

.detail-row {
  display: flex;
  gap: 0.5rem;
  font-size: 0.75rem;
}

.detail-key {
  color: #6b7280;
}

.detail-value {
  color: #111827;
  font-weight: 500;
}

.courier-info,
.pickup-info {
  margin-top: 1.5rem;
  padding: 1rem;
  background-color: #f9fafb;
  border-radius: 0.75rem;
}

.courier-header,
.pickup-header {
  margin-bottom: 0.75rem;
}

.courier-header h4,
.pickup-header h4 {
  margin: 0;
  font-size: 1rem;
  color: #111827;
}

.courier-row,
.pickup-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e5e7eb;
}

.courier-row:last-child,
.pickup-row:last-child {
  border-bottom: none;
}

.courier-label,
.pickup-label {
  color: #6b7280;
  font-size: 0.875rem;
}

.courier-value,
.pickup-value {
  color: #111827;
  font-weight: 500;
  font-size: 0.875rem;
}

.courier-phone {
  color: #3b82f6;
  text-decoration: none;
}

.courier-phone:hover {
  text-decoration: underline;
}

.eta {
  color: #f97316;
  font-weight: 600;
}

.locker {
  font-family: monospace;
  font-size: 1rem;
  font-weight: 600;
}

.window {
  color: #10b981;
  font-weight: 600;
}

.pickup-qr {
  margin-top: 1rem;
  text-align: center;
}

.qr-code {
  display: inline-block;
  padding: 0.5rem;
  background-color: white;
  border-radius: 0.5rem;
}

.qr-code img {
  width: 150px;
  height: 150px;
}

.qr-hint {
  margin: 0.5rem 0 0 0;
  font-size: 0.75rem;
  color: #6b7280;
}

.nutritional-summary {
  margin-top: 1.5rem;
  padding: 1rem;
  background-color: #f9fafb;
  border-radius: 0.75rem;
}

.nutrition-header {
  margin-bottom: 0.75rem;
}

.nutrition-header h4 {
  margin: 0;
  font-size: 1rem;
  color: #111827;
}

.nutrition-chart {
  margin-top: 0.75rem;
}

.nutritional-bar {
  height: 1.5rem;
  background-color: #e5e7eb;
  border-radius: 0.5rem;
  overflow: hidden;
  display: flex;
  margin-bottom: 0.75rem;
}

.nutritional-segment {
  height: 100%;
  transition: width 0.3s ease;
}

.nutritional-segment.proteins {
  background-color: #3b82f6;
}

.nutritional-segment.fats {
  background-color: #f97316;
}

.nutritional-segment.carbs {
  background-color: #22c55e;
}

.nutrition-legend {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: #6b7280;
}

.legend-color {
  width: 12px;
  height: 12px;
  border-radius: 2px;
}

.legend-color.proteins {
  background-color: #3b82f6;
}

.legend-color.fats {
  background-color: #f97316;
}

.legend-color.carbs {
  background-color: #22c55e;
}

.nutrition-calories {
  text-align: center;
  padding-top: 0.5rem;
  border-top: 1px solid #e5e7eb;
}

.calories-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
}

.calories-label {
  font-size: 0.75rem;
  color: #6b7280;
  margin-left: 0.25rem;
}

.allergen-warnings {
  margin-top: 1rem;
}

.allergen-warning {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  border-radius: 0.5rem;
  margin-bottom: 0.5rem;
  animation: slideInLeft 0.3s ease;
}

.allergen-warning.high {
  background-color: rgba(239, 68, 68, 0.1);
  border-left: 4px solid #ef4444;
}

.allergen-warning.medium {
  background-color: rgba(249, 115, 22, 0.1);
  border-left: 4px solid #f97316;
}

.allergen-warning.low {
  background-color: rgba(251, 191, 36, 0.1);
  border-left: 4px solid #fbbf24;
}

@keyframes slideInLeft {
  from {
    transform: translateX(-100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.dietary-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 1rem;
}

.dietary-badge {
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.625rem;
  font-weight: 600;
  text-transform: uppercase;
  color: white;
}

.dietary-badge.halal {
  background-color: #22c55e;
}

.dietary-badge.kosher {
  background-color: #3b82f6;
}

.dietary-badge.vegan {
  background-color: #10b981;
}

.dietary-badge.vegetarian {
  background-color: #14b8a6;
}

.dietary-badge.gluten-free {
  background-color: #f59e0b;
}

.dietary-badge.lactose-free {
  background-color: #8b5cf6;
}
</style>
.dietary-badge.vegetarian {
  background-color: #14b8a6;
}

.dietary-badge.gluten-free {
  background-color: #f59e0b;
}

.dietary-badge.lactose-free {
  background-color: #8b5cf6;
}
</style>
