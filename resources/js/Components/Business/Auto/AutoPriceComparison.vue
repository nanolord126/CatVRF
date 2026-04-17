<script setup lang="ts">
import { ref, onMounted } from 'vue';

interface PriceOffer {
  id: number;
  seller: string;
  seller_type: 'official' | 'dealer' | 'private';
  price: number;
  original_price: number;
  discount_percent: number;
  location: string;
  rating: number;
  is_available: boolean;
  delivery_time: number;
}

const carVin = ref('');
const isSearching = ref(false);
const priceOffers = ref<PriceOffer[]>([]);
const selectedOffer = ref<PriceOffer | null>(null);

const searchPrices = async () => {
  if (!carVin.value) return;

  isSearching.value = true;
  try {
    const response = await fetch(`/api/v1/auto/price-comparison?vin=${carVin.value}`);
    const data = await response.json();
    if (data.success) {
      priceOffers.value = data.offers.sort((a: PriceOffer, b: PriceOffer) => a.price - b.price);
    }
  } catch (error) {
    console.error('Error searching prices:', error);
  } finally {
    isSearching.value = false;
  }
};

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(price);
};

const getSellerTypeColor = (type: string): string => {
  switch (type) {
    case 'official': return 'bg-blue-100 text-blue-800';
    case 'dealer': return 'bg-purple-100 text-purple-800';
    case 'private': return 'bg-gray-100 text-gray-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const getSellerTypeLabel = (type: string): string => {
  switch (type) {
    case 'official': return 'Официальный дилер';
    case 'dealer': return 'Дилер';
    case 'private': return 'Частное лицо';
    default: return type;
  }
};
</script>

<template>
  <div class="auto-price-comparison max-w-5xl mx-auto p-6 bg-white rounded-xl shadow-lg">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Сравнение цен на авто</h2>

    <div class="mb-6">
      <div class="flex gap-4">
        <input 
          v-model="carVin"
          type="text" 
          placeholder="Введите VIN код"
          maxlength="17"
          class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase"
          @input="carVin = carVin.toUpperCase()"
        />
        <button 
          @click="searchPrices"
          :disabled="isSearching || !carVin"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
        >
          {{ isSearching ? 'Поиск...' : 'Найти цены' }}
        </button>
      </div>
    </div>

    <div v-if="isSearching" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Поиск предложений...</p>
    </div>

    <div v-else-if="priceOffers.length === 0 && carVin" class="text-center py-8 bg-gray-50 rounded-lg">
      <p class="text-gray-600">Предложения не найдены</p>
    </div>

    <div v-else-if="priceOffers.length > 0" class="space-y-4">
      <div class="text-sm text-gray-600">
        Найдено предложений: {{ priceOffers.length }}
      </div>

      <div 
        v-for="(offer, index) in priceOffers" 
        :key="offer.id"
        @click="selectedOffer = offer"
        class="p-4 border rounded-lg cursor-pointer transition-all hover:shadow-lg"
        :class="{
          'border-green-500 bg-green-50': index === 0,
          'border-gray-200 hover:border-blue-300': index !== 0,
          'border-blue-500 bg-blue-50': selectedOffer?.id === offer.id
        }"
      >
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span 
                class="px-2 py-1 rounded-full text-xs font-medium"
                :class="getSellerTypeColor(offer.seller_type)"
              >
                {{ getSellerTypeLabel(offer.seller_type) }}
              </span>
              <span v-if="index === 0" class="px-2 py-1 bg-green-500 text-white rounded-full text-xs font-medium">
                Лучшая цена
              </span>
            </div>

            <p class="font-bold text-lg text-gray-900">{{ offer.seller }}</p>
            <p class="text-sm text-gray-600">📍 {{ offer.location }}</p>
            
            <div class="flex items-center gap-4 mt-2 text-sm">
              <span class="flex items-center gap-1">
                ⭐ {{ offer.rating }}
              </span>
              <span class="flex items-center gap-1">
                🚚 {{ offer.delivery_time }} дней
              </span>
              <span v-if="!offer.is_available" class="text-red-600">
                Нет в наличии
              </span>
            </div>
          </div>

          <div class="text-right">
            <p class="text-sm text-gray-500 line-through">
              {{ formatPrice(offer.original_price) }}
            </p>
            <p class="text-2xl font-bold text-blue-600">
              {{ formatPrice(offer.price) }}
            </p>
            <p class="text-sm text-green-600">
              -{{ offer.discount_percent }}%
            </p>
          </div>
        </div>
      </div>

      <div v-if="selectedOffer" class="mt-6 p-4 bg-blue-50 rounded-lg">
        <h3 class="font-semibold text-gray-900 mb-2">Выбранное предложение</h3>
        <div class="flex justify-between items-center">
          <div>
            <p class="font-medium">{{ selectedOffer.seller }}</p>
            <p class="text-sm text-gray-600">{{ selectedOffer.location }}</p>
          </div>
          <div class="text-right">
            <p class="text-xl font-bold text-blue-600">
              {{ formatPrice(selectedOffer.price) }}
            </p>
            <button class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
              Связаться
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auto-price-comparison {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>
