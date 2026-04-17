<template>
  <div class="food-delivery-tracker">
    <div v-if="loading" class="flex items-center justify-center p-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
    </div>

    <div v-else-if="delivery" class="space-y-4">
      <!-- Delivery Status Header -->
      <div class="bg-gradient-to-r from-primary/10 to-primary/5 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Доставка #{{ delivery.id }}</h3>
            <p class="text-sm text-gray-600">{{ getOrderNumber }}</p>
          </div>
          <div :class="getStatusBadgeClass" class="px-3 py-1 rounded-full text-sm font-medium">
            {{ getStatusText }}
          </div>
        </div>
      </div>

      <!-- Delivery Progress -->
      <div class="bg-white rounded-lg shadow-sm border p-4">
        <h4 class="text-sm font-medium text-gray-700 mb-3">Статус доставки</h4>
        <div class="relative">
          <div class="flex items-center justify-between">
            <div
              v-for="(step, index) in deliverySteps"
              :key="step.status"
              class="flex flex-col items-center"
              :class="{ 'flex-1': index < deliverySteps.length - 1 }"
            >
              <div
                :class="[
                  'w-8 h-8 rounded-full flex items-center justify-center text-sm',
                  getStepClass(step.status)
                ]"
              >
                <component :is="step.icon" v-if="isStepCompleted(step.status)" class="w-4 h-4" />
                <span v-else>{{ index + 1 }}</span>
              </div>
              <span class="text-xs mt-2 text-center" :class="isStepCompleted(step.status) ? 'text-gray-900 font-medium' : 'text-gray-500'">
                {{ step.label }}
              </span>
            </div>
          </div>
          <div class="absolute top-4 left-0 right-0 h-0.5 bg-gray-200 -z-10 mx-8"></div>
          <div
            class="absolute top-4 left-0 h-0.5 bg-primary -z-10 mx-8 transition-all duration-500"
            :style="{ width: progressWidth + '%' }"
          ></div>
        </div>
      </div>

      <!-- Delivery Details -->
      <div class="bg-white rounded-lg shadow-sm border p-4 space-y-3">
        <h4 class="text-sm font-medium text-gray-700">Детали доставки</h4>
        
        <div class="flex items-start space-x-3">
          <MapPin class="w-5 h-5 text-gray-400 mt-0.5" />
          <div class="flex-1">
            <p class="text-sm text-gray-600">Адрес доставки</p>
            <p class="text-sm font-medium text-gray-900">{{ delivery.customer_address }}</p>
          </div>
        </div>

        <div v-if="delivery.eta_minutes" class="flex items-start space-x-3">
          <Clock class="w-5 h-5 text-gray-400 mt-0.5" />
          <div class="flex-1">
            <p class="text-sm text-gray-600">Ожидаемое время</p>
            <p class="text-sm font-medium text-gray-900">{{ delivery.eta_minutes }} мин</p>
          </div>
        </div>

        <div v-if="delivery.distance_km" class="flex items-start space-x-3">
          <Route class="w-5 h-5 text-gray-400 mt-0.5" />
          <div class="flex-1">
            <p class="text-sm text-gray-600">Расстояние</p>
            <p class="text-sm font-medium text-gray-900">{{ delivery.distance_km }} км</p>
          </div>
        </div>

        <div v-if="delivery.courier" class="flex items-start space-x-3">
          <User class="w-5 h-5 text-gray-400 mt-0.5" />
          <div class="flex-1">
            <p class="text-sm text-gray-600">Курьер</p>
            <p class="text-sm font-medium text-gray-900">{{ delivery.courier.name }}</p>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div v-if="delivery.status === 'pending' || delivery.status === 'accepted'" class="flex space-x-2">
        <button
          @click="handleCancelDelivery"
          class="flex-1 px-4 py-2 border border-red-300 text-red-700 rounded-lg hover:bg-red-50 transition-colors text-sm font-medium"
        >
          Отменить доставку
        </button>
      </div>

      <!-- Cancellation Reason -->
      <div v-if="delivery.status === 'cancelled' && delivery.cancellation_reason" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-sm text-red-800">
          <strong>Причина отмены:</strong> {{ delivery.cancellation_reason }}
        </p>
      </div>
    </div>

    <div v-else class="text-center py-8 text-gray-500">
      <Package class="w-12 h-12 mx-auto mb-2 text-gray-300" />
      <p>Информация о доставке недоступна</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { MapPin, Clock, Route, User, Package, Check, Truck, Home } from 'lucide-vue-next';

interface Delivery {
  id: number;
  uuid: string;
  status: string;
  customer_address: string;
  eta_minutes: number | null;
  distance_km: number | null;
  courier?: { name: string };
  cancellation_reason: string | null;
  order?: { uuid: string };
}

interface Props {
  orderId?: string;
  deliveryId?: string;
}

const props = defineProps<Props>();

const loading = ref(true);
const delivery = ref<Delivery | null>(null);

const deliverySteps = [
  { status: 'pending', label: 'Ожидание', icon: null },
  { status: 'accepted', label: 'Принято', icon: null },
  { status: 'on_way', label: 'В пути', icon: Truck },
  { status: 'delivered', label: 'Доставлено', icon: Check },
];

const getOrderNumber = computed(() => {
  return delivery.value?.order?.uuid || 'N/A';
});

const getStatusText = computed(() => {
  const statusMap: Record<string, string> = {
    pending: 'В ожидании',
    accepted: 'Принята',
    on_way: 'В пути',
    delivered: 'Доставлена',
    cancelled: 'Отменена',
  };
  return statusMap[delivery.value?.status || ''] || 'Неизвестно';
});

const getStatusBadgeClass = computed(() => {
  const status = delivery.value?.status || '';
  const classMap: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    accepted: 'bg-blue-100 text-blue-800',
    on_way: 'bg-info-100 text-info-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  };
  return classMap[status] || 'bg-gray-100 text-gray-800';
});

const progressWidth = computed(() => {
  const status = delivery.value?.status || '';
  const progressMap: Record<string, number> = {
    pending: 0,
    accepted: 33,
    on_way: 66,
    delivered: 100,
    cancelled: 0,
  };
  return progressMap[status] || 0;
});

const isStepCompleted = (stepStatus: string) => {
  const status = delivery.value?.status || '';
  const statusOrder = ['pending', 'accepted', 'on_way', 'delivered'];
  const stepIndex = statusOrder.indexOf(stepStatus);
  const currentIndex = statusOrder.indexOf(status);
  return stepIndex <= currentIndex && status !== 'cancelled';
};

const getStepClass = (stepStatus: string) => {
  const completed = isStepCompleted(stepStatus);
  const current = delivery.value?.status === stepStatus;
  
  if (completed) {
    return 'bg-primary text-white';
  } else if (current) {
    return 'bg-primary/20 text-primary border-2 border-primary';
  } else {
    return 'bg-gray-200 text-gray-500';
  }
};

const fetchDelivery = async () => {
  try {
    loading.value = true;
    const endpoint = props.deliveryId
      ? `/api/v1/food/deliveries/${props.deliveryId}`
      : `/api/v1/food/orders/${props.orderId}/delivery`;
    
    const response = await fetch(endpoint);
    if (response.ok) {
      const data = await response.json();
      delivery.value = data.data;
    }
  } catch (error) {
    console.error('Failed to fetch delivery:', error);
  } finally {
    loading.value = false;
  }
};

const handleCancelDelivery = async () => {
  if (!confirm('Вы уверены, что хотите отменить доставку?')) return;
  
  try {
    const response = await fetch(`/api/v1/food/deliveries/${delivery.value?.id}/cancel`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ reason: 'Customer request' }),
    });
    
    if (response.ok) {
      await fetchDelivery();
    }
  } catch (error) {
    console.error('Failed to cancel delivery:', error);
  }
};

onMounted(() => {
  fetchDelivery();
  
  // Auto-refresh every 30 seconds if delivery is in progress
  const interval = setInterval(() => {
    if (delivery.value?.status === 'pending' || delivery.value?.status === 'on_way') {
      fetchDelivery();
    }
  }, 30000);
  
  return () => clearInterval(interval);
});
</script>

<style scoped>
.food-delivery-tracker {
  @apply max-w-md mx-auto;
}
</style>
