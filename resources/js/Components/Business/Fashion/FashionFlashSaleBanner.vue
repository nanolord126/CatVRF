<template>
  <div v-if="isFlashSale" class="fashion-flash-sale-banner bg-gradient-to-r from-red-500 to-orange-500 rounded-xl p-6 text-white shadow-lg animate-pulse">
    <div class="flex items-center justify-between">
      <div>
        <div class="flex items-center gap-2 mb-2">
          <span class="bg-white text-red-600 px-3 py-1 rounded-full text-sm font-bold">FLASH SALE</span>
          <span class="text-sm opacity-90">Limited time offer</span>
        </div>
        <h3 class="text-2xl font-bold mb-1">{{ productName }}</h3>
        <div class="flex items-center gap-3">
          <span class="text-3xl font-bold">{{ discountedPrice }} ₽</span>
          <span class="text-lg line-through opacity-70">{{ originalPrice }} ₽</span>
          <span class="bg-white/20 px-2 py-1 rounded text-sm font-semibold">-{{ discountPercent }}%</span>
        </div>
      </div>
      <div class="text-center">
        <div class="text-sm mb-1">Ends in</div>
        <div class="text-3xl font-mono font-bold">{{ timeRemaining }}</div>
        <div class="text-sm opacity-90">{{ endTimeFormatted }}</div>
      </div>
    </div>

    <div class="mt-4 flex items-center gap-4 text-sm">
      <div class="flex items-center gap-2">
        <div class="w-24 bg-white/30 rounded-full h-2">
          <div class="bg-white rounded-full h-2" :style="{ width: stockPercentage + '%' }"></div>
        </div>
        <span>{{ stockLeft }} left</span>
      </div>
      <div class="flex items-center gap-2">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
        </svg>
        <span>Trend Score: {{ trendScore.toFixed(2) }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { format } from 'date-fns';

interface FlashSaleData {
  productId: number;
  productName: string;
  originalPrice: number;
  discountedPrice: number;
  discountPercent: number;
  endTime: string;
  stockLevel: number;
  maxStock: number;
  trendScore: number;
}

const props = defineProps<{
  productId: number;
}>();

const isFlashSale = ref(false);
const productName = ref('');
const originalPrice = ref(0);
const discountedPrice = ref(0);
const discountPercent = ref(0);
const endTime = ref(new Date());
const stockLevel = ref(0);
const maxStock = ref(100);
const trendScore = ref(0);

let timer: number | null = null;

const timeRemaining = ref('00:00:00');
const endTimeFormatted = computed(() => format(endTime.value, 'HH:mm'));

const stockPercentage = computed(() => {
  if (maxStock.value === 0) return 0;
  return (stockLevel.value / maxStock.value) * 100;
});

const stockLeft = computed(() => `${stockLevel.value} / ${maxStock.value}`);

const updateTimeRemaining = () => {
  const now = new Date();
  const diff = endTime.value.getTime() - now.getTime();

  if (diff <= 0) {
    timeRemaining.value = '00:00:00';
    isFlashSale.value = false;
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
    return;
  }

  const hours = Math.floor(diff / (1000 * 60 * 60));
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
  const seconds = Math.floor((diff % (1000 * 60)) / 1000);

  timeRemaining.value = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
};

onMounted(async () => {
  try {
    const response = await fetch(`/api/fashion/products/${props.productId}/flash-sale`);
    if (response.ok) {
      const data: FlashSaleData = await response.json();
      isFlashSale.value = true;
      productName.value = data.productName;
      originalPrice.value = data.originalPrice;
      discountedPrice.value = data.discountedPrice;
      discountPercent.value = data.discountPercent;
      endTime.value = new Date(data.endTime);
      stockLevel.value = data.stockLevel;
      maxStock.value = data.maxStock;
      trendScore.value = data.trendScore;

      timer = window.setInterval(updateTimeRemaining, 1000);
      updateTimeRemaining();
    }
  } catch (error) {
    console.error('Failed to load flash sale data:', error);
  }
});

onUnmounted(() => {
  if (timer) {
    clearInterval(timer);
  }
});
</script>
