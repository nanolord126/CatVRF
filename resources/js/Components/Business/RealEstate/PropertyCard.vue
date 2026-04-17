<template>
  <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
    <div class="relative">
      <img
        v-if="property.images?.[0]"
        :src="property.images[0]"
        :alt="property.title"
        class="w-full h-48 object-cover"
      />
      <div v-else class="w-full h-48 bg-gray-200 flex items-center justify-center">
        <span class="text-gray-400">No image</span>
      </div>

      <div v-if="property.virtual_tour_url" class="absolute top-2 right-2 bg-blue-600 text-white px-2 py-1 rounded text-xs">
        360° Tour
      </div>
      <div v-if="property.ar_model_url" class="absolute top-2 left-2 bg-purple-600 text-white px-2 py-1 rounded text-xs">
        AR Ready
      </div>
    </div>

    <div class="p-4">
      <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ property.title }}</h3>
      <p class="text-gray-600 text-sm mb-2 line-clamp-2">{{ property.description }}</p>

      <div class="flex items-center text-sm text-gray-500 mb-2">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        {{ property.city }}
      </div>

      <div class="grid grid-cols-3 gap-2 text-sm text-gray-600 mb-3">
        <div v-if="property.area">
          <span class="font-medium">{{ property.area }}</span> m²
        </div>
        <div v-if="property.rooms">
          <span class="font-medium">{{ property.rooms }}</span> rooms
        </div>
        <div v-if="property.floor">
          Floor <span class="font-medium">{{ property.floor }}</span>
        </div>
      </div>

      <div class="flex items-center justify-between">
        <div class="text-2xl font-bold text-indigo-600">
          {{ formatPrice(property.price) }}
        </div>
        <button
          @click="handleBook"
          :disabled="property.status !== 'available'"
          class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-sm"
        >
          {{ property.status === 'available' ? 'Book Viewing' : 'Not Available' }}
        </button>
      </div>

      <div v-if="property.tags?.length" class="mt-3 flex flex-wrap gap-1">
        <span
          v-for="tag in property.tags"
          :key="tag"
          class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded"
        >
          {{ tag }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Property {
  id: number;
  title: string;
  description: string;
  city: string;
  price: number;
  area?: number;
  rooms?: number;
  floor?: number;
  status: string;
  images?: string[];
  virtual_tour_url?: string;
  ar_model_url?: string;
  tags?: string[];
}

const props = defineProps<{
  property: Property;
}>();

const emit = defineEmits<{
  book: [property: Property];
  viewVirtualTour: [url: string];
  viewAR: [url: string];
}>();

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(price);
};

const handleBook = () => {
  emit('book', props.property);
};
</script>
