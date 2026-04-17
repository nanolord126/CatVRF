<script setup lang="ts">
import { computed } from 'vue';
import { Car, Shield, Star, Users, Package, Clock } from 'lucide-vue-next';

interface Vehicle {
  id: number;
  uuid: string;
  plate_number: string;
  plate_number_formatted: string;
  brand: string;
  model: string;
  year: number;
  color: string;
  color_hex: string;
  vehicle_class: string;
  vehicle_class_name: string;
  photo_url: string;
  photo_thumbnail_url: string;
  rating: number;
  is_active: boolean;
  is_insured: boolean;
  inspection_status: string;
  inspection_valid_until: string;
  insurance_valid_until: string;
  capacity: {
    passengers: number;
    luggage: number;
  };
  features: {
    air_conditioner: boolean;
    wifi: boolean;
    usb_charger: boolean;
    child_seat: boolean;
    booster_seat: boolean;
    pet_friendly: boolean;
    smoking_allowed: boolean;
    wheelchair_accessible: boolean;
  };
  tariff_class: string;
  base_tariff_price: number;
}

interface Props {
  vehicle: Vehicle;
  showFeatures?: boolean;
  showDocuments?: boolean;
  compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showFeatures: true,
  showDocuments: false,
  compact: false,
});

const emit = defineEmits<{
  (e: 'select', vehicleId: number): void;
  (e: 'view', vehicleId: number): void;
}>();

const ratingColor = computed(() => {
  if (props.vehicle.rating >= 4.8) return 'text-green-500';
  if (props.vehicle.rating >= 4.5) return 'text-blue-500';
  if (props.vehicle.rating >= 4.0) return 'text-yellow-500';
  return 'text-red-500';
});

const classColor = computed(() => {
  const colors: Record<string, string> = {
    economy: 'bg-gray-100 text-gray-800',
    comfort: 'bg-blue-100 text-blue-800',
    comfort_plus: 'bg-purple-100 text-purple-800',
    business: 'bg-amber-100 text-amber-800',
    premium: 'bg-rose-100 text-rose-800',
    van: 'bg-green-100 text-green-800',
    cargo: 'bg-orange-100 text-orange-800',
  };
  return colors[props.vehicle.vehicle_class] || 'bg-gray-100 text-gray-800';
});

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
  }).format(price);
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};
</script>

<template>
  <div 
    class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100"
    :class="{ 'p-4': !compact, 'p-3': compact }"
  >
    <!-- Header with photo and basic info -->
    <div class="flex items-start gap-4 mb-3">
      <div class="relative">
        <img 
          :src="vehicle.photo_thumbnail_url || vehicle.photo_url" 
          :alt="`${vehicle.brand} ${vehicle.model}`"
          class="w-24 h-20 rounded-xl object-cover border-2 border-white shadow-md"
          :class="{ 'w-20 h-16': compact }"
        />
        <div 
          class="absolute top-0 right-0 px-2 py-1 rounded-lg text-xs font-bold text-white"
          :class="classColor"
        >
          {{ vehicle.vehicle_class_name }}
        </div>
      </div>
      <div class="flex-1 min-w-0">
        <h3 class="font-bold text-gray-900 text-lg" :class="{ 'text-base': compact }">
          {{ vehicle.brand }} {{ vehicle.model }}
        </h3>
        <div class="flex items-center gap-2 mt-1">
          <Car :size="16" class="text-gray-500" />
          <span class="text-gray-600 font-medium">{{ vehicle.plate_number_formatted }}</span>
        </div>
        <div class="flex items-center gap-2 mt-1">
          <div 
            class="w-4 h-4 rounded-full border border-gray-300"
            :style="{ backgroundColor: vehicle.color_hex }"
          />
          <span class="text-gray-600 text-sm">{{ vehicle.color }}</span>
          <span class="text-gray-400">•</span>
          <span class="text-gray-600 text-sm">{{ vehicle.year }}</span>
        </div>
        <div class="flex items-center gap-1 mt-2">
          <Star 
            :size="compact ? 14 : 16" 
            :class="ratingColor"
            fill="currentColor"
          />
          <span 
            class="font-semibold text-sm"
            :class="ratingColor"
          >
            {{ vehicle.rating.toFixed(1) }}
          </span>
        </div>
      </div>
      <div class="text-right">
        <div class="text-lg font-bold text-gray-900">{{ formatPrice(vehicle.base_tariff_price) }}</div>
        <div class="text-xs text-gray-500">Базовый тариф</div>
      </div>
    </div>

    <!-- Capacity -->
    <div class="grid grid-cols-2 gap-2 mb-3 p-3 bg-gray-50 rounded-xl">
      <div class="flex items-center gap-2">
        <Users :size="18" class="text-gray-600" />
        <div>
          <div class="font-semibold text-gray-900">{{ vehicle.capacity.passengers }}</div>
          <div class="text-xs text-gray-500">Пассажиров</div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Package :size="18" class="text-gray-600" />
        <div>
          <div class="font-semibold text-gray-900">{{ vehicle.capacity.luggage }}</div>
          <div class="text-xs text-gray-500">Мест багажа</div>
        </div>
      </div>
    </div>

    <!-- Features -->
    <div v-if="showFeatures && !compact" class="flex flex-wrap gap-2 mb-3">
      <div 
        v-if="vehicle.features.air_conditioner"
        class="flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-medium"
      >
        ❄️ Кондиционер
      </div>
      <div 
        v-if="vehicle.features.wifi"
        class="flex items-center gap-1 px-2 py-1 bg-green-50 text-green-700 rounded-full text-xs font-medium"
      >
        📶 WiFi
      </div>
      <div 
        v-if="vehicle.features.usb_charger"
        class="flex items-center gap-1 px-2 py-1 bg-purple-50 text-purple-700 rounded-full text-xs font-medium"
      >
        🔌 USB
      </div>
      <div 
        v-if="vehicle.features.child_seat"
        class="flex items-center gap-1 px-2 py-1 bg-pink-50 text-pink-700 rounded-full text-xs font-medium"
      >
        👶 Детское кресло
      </div>
      <div 
        v-if="vehicle.features.pet_friendly"
        class="flex items-center gap-1 px-2 py-1 bg-orange-50 text-orange-700 rounded-full text-xs font-medium"
      >
        🐕 С животными
      </div>
      <div 
        v-if="vehicle.features.wheelchair_accessible"
        class="flex items-center gap-1 px-2 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-medium"
      >
        ♿ Доступность
      </div>
    </div>

    <!-- Documents -->
    <div v-if="showDocuments && !compact" class="p-3 bg-amber-50 rounded-xl mb-3">
      <div class="flex items-center gap-2 mb-2">
        <Shield :size="16" class="text-amber-600" />
        <span class="font-semibold text-amber-900 text-sm">Документы</span>
      </div>
      <div class="grid grid-cols-2 gap-2 text-xs">
        <div>
          <span class="text-gray-500">Страховка до:</span>
          <span class="font-medium text-gray-900 ml-1">{{ formatDate(vehicle.insurance_valid_until) }}</span>
        </div>
        <div>
          <span class="text-gray-500">ТО до:</span>
          <span class="font-medium text-gray-900 ml-1">{{ formatDate(vehicle.inspection_valid_until) }}</span>
        </div>
      </div>
    </div>

    <!-- Status indicators -->
    <div class="flex items-center gap-2 mb-3">
      <div 
        class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium"
        :class="vehicle.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
      >
        {{ vehicle.is_active ? '✓ Активен' : '✗ Неактивен' }}
      </div>
      <div 
        v-if="vehicle.is_insured"
        class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700"
      >
        ✓ Застрахован
      </div>
    </div>

    <!-- Action button -->
    <button 
      @click="emit('view', vehicle.id)"
      class="w-full py-2.5 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-medium transition-colors"
    >
      Подробнее
    </button>
  </div>
</template>
