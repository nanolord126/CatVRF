<template>
  <div class="fashion-ar-preview bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-4 text-white">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
              <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
            </svg>
          </div>
          <div>
            <h3 class="font-bold">AR Try-On</h3>
            <p class="text-sm text-blue-200">{{ productName }}</p>
          </div>
        </div>
        <button
          @click="toggleFullscreen"
          class="bg-white/20 px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors"
        >
          {{ isFullscreen ? 'Exit' : 'Fullscreen' }}
        </button>
      </div>
    </div>

    <div ref="arContainer" class="relative aspect-square bg-gray-100" :class="{ 'fullscreen' : isFullscreen }">
      <model-viewer
        v-if="arModelUrl"
        :src="arModelUrl"
        :ios-src="arModelUrl"
        alt="3D Model"
        auto-rotate
        camera-controls
        ar
        ar-modes="webxr scene-viewer quick-look"
        class="w-full h-full"
      ></model-viewer>

      <div v-else class="absolute inset-0 flex items-center justify-center">
        <div class="text-center text-gray-500">
          <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
          </svg>
          <p>Loading 3D model...</p>
        </div>
      </div>

      <div v-if="fitScore !== null" class="absolute bottom-4 left-4 bg-white/90 backdrop-blur rounded-lg p-3 shadow-lg">
        <div class="flex items-center gap-2">
          <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="fitScoreColor">
            <span class="text-white font-bold">{{ Math.round(fitScore * 100) }}%</span>
          </div>
          <div>
            <div class="font-semibold text-sm">Fit Score</div>
            <div class="text-xs text-gray-600">{{ fitScoreLabel }}</div>
          </div>
        </div>
      </div>

      <div v-if="arModelUrl" class="absolute top-4 right-4 flex flex-col gap-2">
        <button
          @click="changeColor('red')"
          class="w-8 h-8 rounded-full bg-red-500 border-2 border-white shadow-lg hover:scale-110 transition-transform"
        ></button>
        <button
          @click="changeColor('blue')"
          class="w-8 h-8 rounded-full bg-blue-500 border-2 border-white shadow-lg hover:scale-110 transition-transform"
        ></button>
        <button
          @click="changeColor('black')"
          class="w-8 h-8 rounded-full bg-black border-2 border-white shadow-lg hover:scale-110 transition-transform"
        ></button>
        <button
          @click="changeColor('white')"
          class="w-8 h-8 rounded-full bg-white border-2 border-gray-300 shadow-lg hover:scale-110 transition-transform"
        ></button>
      </div>
    </div>

    <div class="p-4 border-t">
      <div class="grid grid-cols-3 gap-4 text-center">
        <div>
          <div class="text-2xl font-bold text-purple-600">{{ fitScore !== null ? Math.round(fitScore * 100) : '-' }}%</div>
          <div class="text-sm text-gray-600">Fit Score</div>
        </div>
        <div>
          <div class="text-2xl font-bold text-blue-600">{{ embeddingSimilarity !== null ? Math.round(embeddingSimilarity * 100) : '-' }}%</div>
          <div class="text-sm text-gray-600">Style Match</div>
        </div>
        <div>
          <div class="text-2xl font-bold text-green-600">{{ inStock ? 'Yes' : 'No' }}</div>
          <div class="text-sm text-gray-600">In Stock</div>
        </div>
      </div>

      <button
        @click="addToCart"
        :disabled="!inStock"
        class="mt-4 w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ inStock ? 'Add to Cart' : 'Out of Stock' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

interface ARPreviewData {
  arModelUrl: string;
  fitScore: number;
  embeddingSimilarity: number;
  inStock: boolean;
  price: number;
}

const props = defineProps<{
  designId: number;
  productId: number;
  productName: string;
}>();

const arContainer = ref<HTMLElement>();
const arModelUrl = ref('');
const fitScore = ref<number | null>(null);
const embeddingSimilarity = ref<number | null>(null);
const inStock = ref(false);
const isFullscreen = ref(false);

const fitScoreColor = computed(() => {
  if (!fitScore.value) return 'bg-gray-500';
  if (fitScore.value >= 0.8) return 'bg-green-500';
  if (fitScore.value >= 0.6) return 'bg-yellow-500';
  return 'bg-red-500';
});

const fitScoreLabel = computed(() => {
  if (!fitScore.value) return 'Calculating...';
  if (fitScore.value >= 0.8) return 'Excellent fit';
  if (fitScore.value >= 0.6) return 'Good fit';
  return 'Poor fit';
});

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value;
  if (isFullscreen.value && arContainer.value) {
    arContainer.value.requestFullscreen?.();
  }
};

const changeColor = (color: string) => {
  // Implement color change logic
  console.log('Change color to:', color);
};

const addToCart = () => {
  // Implement add to cart logic
  console.log('Add to cart clicked');
};

onMounted(async () => {
  try {
    const response = await fetch(`/api/fashion/ar-preview/${props.designId}/${props.productId}`);
    const data: ARPreviewData = await response.json();
    arModelUrl.value = data.arModelUrl;
    fitScore.value = data.fitScore;
    embeddingSimilarity.value = data.embeddingSimilarity;
    inStock.value = data.inStock;

    // Load model-viewer script dynamically
    if (!document.querySelector('script[src*="model-viewer"]')) {
      const script = document.createElement('script');
      script.src = 'https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js';
      script.type = 'module';
      document.head.appendChild(script);
    }
  } catch (error) {
    console.error('Failed to load AR preview:', error);
  }
});
</script>

<style scoped>
.fullscreen {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 9999;
  border-radius: 0;
}

model-viewer {
  --poster-color: transparent;
}
</style>
