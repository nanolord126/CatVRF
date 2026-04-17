<script setup lang="ts">
import { computed } from 'vue';
import { Car, Clock, DollarSign, Zap, Shield, Users, Tag } from 'lucide-vue-next';

interface Tariff {
  id: number;
  code: string;
  name: string;
  description: string;
  vehicle_class: string;
  vehicle_class_name: string;
  icon: string;
  color: string;
  is_active: boolean;
  is_available_now: boolean;
  pricing: {
    base_price: number;
    price_per_km: number;
    price_per_minute: number;
    minimum_price: number;
    waiting_price_per_minute: number;
    currency: string;
  };
  surge: {
    current_multiplier: number;
    max_multiplier: number;
    is_surge_active: boolean;
  };
  features: {
    fixed_price_available: boolean;
    preorder_available: boolean;
    split_payment_available: boolean;
    corporate_payment_available: boolean;
    voice_order_available: boolean;
  };
  vehicle_requirements: {
    min_year: number;
    min_rating: number;
    required_features: string[];
    passenger_capacity: number;
    luggage_capacity: number;
  };
  estimated_time: {
    average_wait_time_minutes: number;
    max_wait_time_minutes: number;
  };
  availability: {
    available_drivers_count: number;
    is_available: boolean;
  };
  b2b_pricing: {
    enabled: boolean;
    discount_percentage: number;
    monthly_limit: number;
  };
}

interface Props {
  tariff: Tariff;
  showFeatures?: boolean;
  showSurge?: boolean;
  showB2B?: boolean;
  compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showFeatures: true,
  showSurge: true,
  showB2B: false,
  compact: false,
});

const emit = defineEmits<{
  (e: 'select', tariffId: number): void;
  (e: 'view', tariffId: number): void;
}>();

const classColor = computed(() => {
  const colors: Record<string, string> = {
    economy: 'from-gray-500 to-gray-600',
    comfort: 'from-blue-500 to-blue-600',
    comfort_plus: 'from-purple-500 to-purple-600',
    business: 'from-amber-500 to-amber-600',
    premium: 'from-rose-500 to-rose-600',
    van: 'from-green-500 to-green-600',
    cargo: 'from-orange-500 to-orange-600',
  };
  return colors[props.tariff.vehicle_class] || 'from-gray-500 to-gray-600';
});

const classTextColor = computed(() => {
  const colors: Record<string, string> = {
    economy: 'text-gray-600',
    comfort: 'text-blue-600',
    comfort_plus: 'text-purple-600',
    business: 'text-amber-600',
    premium: 'text-rose-600',
    van: 'text-green-600',
    cargo: 'text-orange-600',
  };
  return colors[props.tariff.vehicle_class] || 'text-gray-600';
});

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
  }).format(price);
};

const formatTime = (minutes: number) => {
  if (minutes < 60) {
    return `${minutes} мин`;
  }
  const hours = Math.floor(minutes / 60);
  const mins = minutes % 60;
  return mins > 0 ? `${hours}ч ${mins}м` : `${hours}ч`;
};
</script>

<template>
  <div 
    class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100"
    :class="{ 'p-4': !compact, 'p-3': compact }"
  >
    <!-- Header with class badge -->
    <div class="flex items-start justify-between mb-3">
      <div class="flex items-center gap-3">
        <div 
          class="w-14 h-14 rounded-xl flex items-center justify-center text-white text-2xl font-bold bg-gradient-to-br"
          :class="classColor"
        >
          {{ tariff.icon }}
        </div>
        <div>
          <h3 class="font-bold text-gray-900 text-lg" :class="{ 'text-base': compact }">
            {{ tariff.name }}
          </h3>
          <div class="flex items-center gap-2 mt-1">
            <span 
              class="px-2 py-0.5 rounded-full text-xs font-medium"
              :class="classTextColor"
            >
              {{ tariff.vehicle_class_name }}
            </span>
            <span v-if="!tariff.is_available_now" class="text-xs text-red-500 font-medium">
              Недоступно
            </span>
            <span v-else class="text-xs text-green-500 font-medium">
              {{ tariff.availability.available_drivers_count }} водителей
            </span>
          </div>
        </div>
      </div>
      <div class="text-right">
        <div class="text-xl font-bold text-gray-900">{{ formatPrice(tariff.pricing.base_price) }}</div>
        <div class="text-xs text-gray-500">Базовая цена</div>
      </div>
    </div>

    <!-- Description -->
    <p v-if="tariff.description && !compact" class="text-sm text-gray-600 mb-3">
      {{ tariff.description }}
    </p>

    <!-- Pricing details -->
    <div class="grid grid-cols-2 gap-2 mb-3 p-3 bg-gray-50 rounded-xl">
      <div>
        <div class="text-xs text-gray-500 mb-1">За км</div>
        <div class="font-semibold text-gray-900">{{ formatPrice(tariff.pricing.price_per_km) }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500 mb-1">За минуту</div>
        <div class="font-semibold text-gray-900">{{ formatPrice(tariff.pricing.price_per_minute) }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500 mb-1">Минимум</div>
        <div class="font-semibold text-gray-900">{{ formatPrice(tariff.pricing.minimum_price) }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500 mb-1">Ожидание</div>
        <div class="font-semibold text-gray-900">{{ formatPrice(tariff.pricing.waiting_price_per_minute) }}/мин</div>
      </div>
    </div>

    <!-- Surge indicator -->
    <div v-if="showSurge && tariff.surge.is_surge_active" class="mb-3 p-3 bg-orange-50 rounded-xl">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <Zap :size="18" class="text-orange-600" />
          <span class="font-semibold text-orange-900 text-sm">Surge pricing</span>
        </div>
        <div class="text-lg font-bold text-orange-600">
          x{{ tariff.surge.current_multiplier.toFixed(1) }}
        </div>
      </div>
      <div class="text-xs text-orange-700 mt-1">
        Максимальный множитель: x{{ tariff.surge.max_multiplier.toFixed(1) }}
      </div>
    </div>

    <!-- Wait time -->
    <div v-if="!compact" class="mb-3 p-3 bg-blue-50 rounded-xl">
      <div class="flex items-center gap-2 mb-2">
        <Clock :size="16" class="text-blue-600" />
        <span class="font-semibold text-blue-900 text-sm">Время ожидания</span>
      </div>
      <div class="flex items-center justify-between">
        <span class="text-sm text-gray-700">Среднее:</span>
        <span class="font-semibold text-gray-900">{{ formatTime(tariff.estimated_time.average_wait_time_minutes) }}</span>
      </div>
      <div class="flex items-center justify-between mt-1">
        <span class="text-sm text-gray-700">Максимум:</span>
        <span class="font-semibold text-gray-900">{{ formatTime(tariff.estimated_time.max_wait_time_minutes) }}</span>
      </div>
    </div>

    <!-- Features -->
    <div v-if="showFeatures && !compact" class="flex flex-wrap gap-2 mb-3">
      <div 
        v-if="tariff.features.fixed_price_available"
        class="flex items-center gap-1 px-2 py-1 bg-green-50 text-green-700 rounded-full text-xs font-medium"
      >
        <Tag :size="12" />
        Фиксированная цена
      </div>
      <div 
        v-if="tariff.features.preorder_available"
        class="flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-medium"
      >
        <Clock :size="12" />
        Предзаказ
      </div>
      <div 
        v-if="tariff.features.split_payment_available"
        class="flex items-center gap-1 px-2 py-1 bg-purple-50 text-purple-700 rounded-full text-xs font-medium"
      >
        <Users :size="12" />
        Split payment
      </div>
      <div 
        v-if="tariff.features.corporate_payment_available"
        class="flex items-center gap-1 px-2 py-1 bg-amber-50 text-amber-700 rounded-full text-xs font-medium"
      >
        <Shield :size="12" />
        Корпоративная оплата
      </div>
      <div 
        v-if="tariff.features.voice_order_available"
        class="flex items-center gap-1 px-2 py-1 bg-pink-50 text-pink-700 rounded-full text-xs font-medium"
      >
        🎤 Голосовой заказ
      </div>
    </div>

    <!-- Vehicle requirements -->
    <div v-if="!compact" class="mb-3 p-3 bg-gray-50 rounded-xl">
      <div class="flex items-center gap-2 mb-2">
        <Car :size="16" class="text-gray-600" />
        <span class="font-semibold text-gray-900 text-sm">Требования к авто</span>
      </div>
      <div class="grid grid-cols-2 gap-2 text-xs">
        <div>
          <span class="text-gray-500">Мин. год:</span>
          <span class="font-medium text-gray-900 ml-1">{{ tariff.vehicle_requirements.min_year }}</span>
        </div>
        <div>
          <span class="text-gray-500">Мин. рейтинг:</span>
          <span class="font-medium text-gray-900 ml-1">{{ tariff.vehicle_requirements.min_rating.toFixed(1) }}</span>
        </div>
        <div>
          <span class="text-gray-500">Пассажиры:</span>
          <span class="font-medium text-gray-900 ml-1">{{ tariff.vehicle_requirements.passenger_capacity }}</span>
        </div>
        <div>
          <span class="text-gray-500">Багаж:</span>
          <span class="font-medium text-gray-900 ml-1">{{ tariff.vehicle_requirements.luggage_capacity }}</span>
        </div>
      </div>
    </div>

    <!-- B2B pricing -->
    <div v-if="showB2B && tariff.b2b_pricing.enabled" class="mb-3 p-3 bg-amber-50 rounded-xl">
      <div class="flex items-center gap-2 mb-2">
        <DollarSign :size="16" class="text-amber-600" />
        <span class="font-semibold text-amber-900 text-sm">B2B тарифы</span>
      </div>
      <div class="grid grid-cols-2 gap-2 text-xs">
        <div>
          <span class="text-gray-500">Скидка:</span>
          <span class="font-medium text-gray-900 ml-1">{{ tariff.b2b_pricing.discount_percentage }}%</span>
        </div>
        <div>
          <span class="text-gray-500">Лимит:</span>
          <span class="font-medium text-gray-900 ml-1">{{ formatPrice(tariff.b2b_pricing.monthly_limit) }}/мес</span>
        </div>
      </div>
    </div>

    <!-- Action button -->
    <button 
      @click="emit('select', tariff.id)"
      :disabled="!tariff.is_available_now"
      class="w-full py-2.5 bg-gradient-to-r text-white rounded-xl font-medium transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
      :class="[classColor, tariff.is_available_now ? 'hover:opacity-90' : '']"
    >
      {{ tariff.is_available_now ? 'Выбрать' : 'Недоступно' }}
    </button>
  </div>
</template>
