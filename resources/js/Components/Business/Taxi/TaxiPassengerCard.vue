<script setup lang="ts">
import { computed } from 'vue';
import { User, Star, MapPin, Wallet, Award, Settings, Phone, Mail, Heart } from 'lucide-vue-next';

interface Passenger {
  id: number;
  uuid: string;
  name: string;
  surname: string;
  patronymic: string;
  full_name: string;
  avatar_url: string;
  avatar_thumbnail_url: string;
  phone: string;
  email: string;
  rating: number;
  rating_count: number;
  verification_status: string;
  is_verified: boolean;
  is_b2b_user: boolean;
  business_group_id: number | null;
  business_card_id: string | null;
  stats: {
    total_rides: number;
    rides_this_month: number;
    total_spent: number;
    spent_this_month: number;
    cancelled_rides: number;
    completion_rate: number;
  };
  preferences: {
    favorite_tariff: string;
    favorite_drivers: number[];
    saved_addresses: any[];
    payment_methods: string[];
    default_payment_method: string;
    notifications_enabled: boolean;
    marketing_notifications: boolean;
    voice_order_enabled: boolean;
    biometric_auth_enabled: boolean;
  };
  loyalty: {
    loyalty_level: string;
    loyalty_points: number;
    bonuses_balance: number;
    next_level_points: number;
    discount_percentage: number;
  };
  wallet: {
    balance: number;
    currency: string;
    is_active: boolean;
  };
  favorite_places: {
    home: string;
    work: string;
    other: string[];
  };
  created_at: string;
  last_ride_at: string | null;
  last_active_at: string | null;
}

interface Props {
  passenger: Passenger;
  showStats?: boolean;
  showLoyalty?: boolean;
  showPreferences?: boolean;
  compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showStats: true,
  showLoyalty: true,
  showPreferences: false,
  compact: false,
});

const emit = defineEmits<{
  (e: 'edit', passengerId: number): void;
  (e: 'call', passengerId: number): void;
  (e: 'view', passengerId: number): void;
}>();

const ratingColor = computed(() => {
  if (props.passenger.rating >= 4.8) return 'text-green-500';
  if (props.passenger.rating >= 4.5) return 'text-blue-500';
  if (props.passenger.rating >= 4.0) return 'text-yellow-500';
  return 'text-red-500';
});

const loyaltyColor = computed(() => {
  const colors: Record<string, string> = {
    bronze: 'from-amber-600 to-amber-700',
    silver: 'from-gray-400 to-gray-500',
    gold: 'from-yellow-500 to-yellow-600',
    platinum: 'from-purple-500 to-purple-600',
  };
  return colors[props.passenger.loyalty.loyalty_level] || 'from-gray-400 to-gray-500';
});

const loyaltyLevelName = computed(() => {
  const names: Record<string, string> = {
    bronze: 'Бронза',
    silver: 'Серебро',
    gold: 'Золото',
    platinum: 'Платина',
  };
  return names[props.passenger.loyalty.loyalty_level] || 'Бронза';
});

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
  }).format(price);
};

const formatDate = (date: string | null) => {
  if (!date) return 'Никогда';
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
    <!-- Header with avatar and basic info -->
    <div class="flex items-start gap-4 mb-4">
      <div class="relative">
        <img 
          :src="passenger.avatar_thumbnail_url || passenger.avatar_url" 
          :alt="passenger.full_name"
          class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-lg"
          :class="{ 'w-16 h-16': compact }"
        />
        <div 
          v-if="passenger.is_verified"
          class="absolute -bottom-1 -right-1 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center border-2 border-white"
        >
          <Award :size="14" class="text-white" />
        </div>
      </div>
      <div class="flex-1">
        <div class="flex items-center gap-2">
          <h3 class="font-bold text-gray-900 text-lg" :class="{ 'text-base': compact }">
            {{ passenger.full_name }}
          </h3>
          <span 
            v-if="passenger.is_b2b_user"
            class="px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700"
          >
            B2B
          </span>
        </div>
        <div class="flex items-center gap-1 mt-1">
          <Star 
            :size="compact ? 14 : 16" 
            :class="ratingColor"
            fill="currentColor"
          />
          <span 
            class="font-semibold text-sm"
            :class="ratingColor"
          >
            {{ passenger.rating.toFixed(1) }}
          </span>
          <span class="text-gray-400 text-sm">
            ({{ passenger.rating_count }})
          </span>
        </div>
        <div class="flex items-center gap-3 mt-2 text-sm text-gray-500">
          <div class="flex items-center gap-1">
            <Phone :size="14" />
            {{ passenger.phone }}
          </div>
          <div v-if="passenger.email" class="flex items-center gap-1">
            <Mail :size="14" />
            {{ passenger.email }}
          </div>
        </div>
      </div>
      <div class="flex gap-2">
        <button 
          @click="emit('call', passenger.id)"
          class="p-2 rounded-full hover:bg-gray-100 transition-colors"
        >
          <Phone :size="18" class="text-gray-600" />
        </button>
        <button 
          @click="emit('edit', passenger.id)"
          class="p-2 rounded-full hover:bg-gray-100 transition-colors"
        >
          <Settings :size="18" class="text-gray-600" />
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div v-if="showStats && !compact" class="grid grid-cols-3 gap-2 mb-4 p-3 bg-gray-50 rounded-xl">
      <div class="text-center">
        <div class="text-lg font-bold text-gray-900">{{ passenger.stats.total_rides }}</div>
        <div class="text-xs text-gray-500">Всего поездок</div>
      </div>
      <div class="text-center">
        <div class="text-lg font-bold text-gray-900">{{ passenger.stats.completion_rate }}%</div>
        <div class="text-xs text-gray-500">Выполнено</div>
      </div>
      <div class="text-center">
        <div class="text-lg font-bold text-gray-900">{{ formatPrice(passenger.stats.total_spent) }}</div>
        <div class="text-xs text-gray-500">Потрачено</div>
      </div>
    </div>

    <!-- Loyalty -->
    <div v-if="showLoyalty && !compact" class="mb-4 p-3 bg-gradient-to-r rounded-xl" :class="loyaltyColor">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <Award :size="20" class="text-white" />
          <div>
            <div class="font-semibold text-white">{{ loyaltyLevelName }}</div>
            <div class="text-xs text-white/80">{{ passenger.loyalty.loyalty_points }} баллов</div>
          </div>
        </div>
        <div class="text-right">
          <div class="font-bold text-white">{{ passenger.loyalty.discount_percentage }}%</div>
          <div class="text-xs text-white/80">Скидка</div>
        </div>
      </div>
      <div class="mt-2 bg-white/20 rounded-full h-2">
        <div 
          class="h-2 rounded-full bg-white"
          :style="{ width: `${(passenger.loyalty.loyalty_points / passenger.loyalty.next_level_points) * 100}%` }"
        />
      </div>
      <div class="text-xs text-white/80 mt-1">
        До следующего уровня: {{ passenger.loyalty.next_level_points - passenger.loyalty.loyalty_points }} баллов
      </div>
    </div>

    <!-- Wallet -->
    <div v-if="!compact" class="mb-4 p-3 bg-green-50 rounded-xl">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <Wallet :size="18" class="text-green-600" />
          <span class="font-semibold text-green-900 text-sm">Кошелек</span>
        </div>
        <div class="text-right">
          <div class="text-lg font-bold text-green-700">{{ formatPrice(passenger.wallet.balance) }}</div>
          <div class="text-xs text-green-600">
            {{ passenger.wallet.is_active ? 'Активен' : 'Неактивен' }}
          </div>
        </div>
      </div>
    </div>

    <!-- Preferences -->
    <div v-if="showPreferences && !compact" class="mb-4 p-3 bg-blue-50 rounded-xl">
      <div class="flex items-center gap-2 mb-2">
        <Settings :size="16" class="text-blue-600" />
        <span class="font-semibold text-blue-900 text-sm">Предпочтения</span>
      </div>
      <div class="flex flex-wrap gap-2">
        <div 
          v-if="passenger.preferences.voice_order_enabled"
          class="flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs"
        >
          🎤 Голос
        </div>
        <div 
          v-if="passenger.preferences.biometric_auth_enabled"
          class="flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs"
        >
          👆 Биометрия
        </div>
        <div 
          v-if="passenger.preferences.notifications_enabled"
          class="flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs"
        >
          🔔 Уведомления
        </div>
      </div>
    </div>

    <!-- Favorite places -->
    <div v-if="!compact" class="mb-4 p-3 bg-purple-50 rounded-xl">
      <div class="flex items-center gap-2 mb-2">
        <MapPin :size="16" class="text-purple-600" />
        <span class="font-semibold text-purple-900 text-sm">Избранные места</span>
      </div>
      <div class="space-y-1">
        <div v-if="passenger.favorite_places.home" class="flex items-center gap-2 text-sm">
          <span class="text-purple-600">🏠</span>
          <span class="text-gray-700 truncate">{{ passenger.favorite_places.home }}</span>
        </div>
        <div v-if="passenger.favorite_places.work" class="flex items-center gap-2 text-sm">
          <span class="text-purple-600">💼</span>
          <span class="text-gray-700 truncate">{{ passenger.favorite_places.work }}</span>
        </div>
      </div>
    </div>

    <!-- Last activity -->
    <div v-if="!compact" class="flex items-center justify-between text-xs text-gray-500 mb-3">
      <span>Последняя поездка: {{ formatDate(passenger.last_ride_at) }}</span>
      <span>Активен: {{ formatDate(passenger.last_active_at) }}</span>
    </div>

    <!-- Action button -->
    <button 
      @click="emit('view', passenger.id)"
      class="w-full py-2.5 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-medium transition-colors"
    >
      Открыть профиль
    </button>
  </div>
</template>
