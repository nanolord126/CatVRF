<template>
  <div class="sports-ar-equipment-viewer">
    <div class="viewer-container">
      <div class="viewer-header">
        <h3 class="viewer-title">{{ equipment.name }}</h3>
        <div class="viewer-controls">
          <button 
            @click="toggleAR" 
            :class="['ar-toggle-btn', { active: isARActive }]"
            :disabled="!arSupported"
          >
            <Camera v-if="!isARActive" :size="20" />
            <CameraOff v-else :size="20" />
            {{ isARActive ? 'Exit AR' : 'Start AR' }}
          </button>
          <button @click="resetView" class="reset-btn">
            <RefreshCw :size="20" />
            Reset
          </button>
        </div>
      </div>

      <div class="model-viewer-wrapper" ref="viewerContainer">
        <model-viewer
          ref="modelViewer"
          :src="equipment.modelUrl"
          :alt="equipment.name"
          :auto-rotate="autoRotate"
          :camera-controls="cameraControls"
          :interaction-prompt="interactionPrompt"
          :ar="arSupported"
          :ar-modes="arModes"
          :ar-scale="arScale"
          :background-color="backgroundColor"
          @load="onModelLoad"
          @error="onModelError"
          class="model-viewer"
        >
          <div slot="poster" class="model-poster">
            <Loader2 class="loading-spinner" :size="48" />
            <p>Loading 3D model...</p>
          </div>
        </model-viewer>

        <div v-if="!arSupported" class="ar-not-supported">
          <AlertTriangle :size="24" />
          <p>AR is not supported on this device</p>
        </div>
      </div>

      <div class="viewer-info">
        <div class="info-section">
          <h4>Specifications</h4>
          <ul class="specs-list">
            <li v-for="(value, key) in equipment.specifications" :key="key">
              <span class="spec-label">{{ formatLabel(key) }}:</span>
              <span class="spec-value">{{ value }}</span>
            </li>
          </ul>
        </div>

        <div class="info-section">
          <h4>Features</h4>
          <div class="features-grid">
            <div 
              v-for="feature in equipment.features" 
              :key="feature" 
              class="feature-tag"
            >
              <Check :size="16" />
              {{ feature }}
            </div>
          </div>
        </div>

        <div class="info-section">
          <h4>Price</h4>
          <div class="price-display">
            <span v-if="equipment.discountPrice" class="original-price">
              {{ formatPrice(equipment.originalPrice) }}
            </span>
            <span class="current-price">
              {{ formatPrice(equipment.discountPrice || equipment.originalPrice) }}
            </span>
            <span v-if="equipment.discountPrice" class="discount-badge">
              -{{ calculateDiscount() }}%
            </span>
          </div>
        </div>

        <div class="viewer-actions">
          <button @click="addToCart" class="add-to-cart-btn">
            <ShoppingCart :size="20" />
            Add to Cart
          </button>
          <button @click="addToWishlist" class="wishlist-btn">
            <Heart :size="20" />
            {{ isInWishlist ? 'Remove' : 'Save' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="showARInstructions" class="ar-instructions-overlay" @click="closeInstructions">
      <div class="instructions-content" @click.stop>
        <div class="instructions-header">
          <h3>AR View Instructions</h3>
          <button @click="closeInstructions" class="close-btn">
            <X :size="24" />
          </button>
        </div>
        <div class="instructions-body">
          <div class="instruction-step">
            <div class="step-number">1</div>
            <p>Point your camera at a flat surface</p>
          </div>
          <div class="instruction-step">
            <div class="step-number">2</div>
            <p>Move your device to place the equipment</p>
          </div>
          <div class="instruction-step">
            <div class="step-number">3</div>
            <p>Pinch to resize, rotate to adjust angle</p>
          </div>
          <div class="instruction-step">
            <div class="step-number">4</div>
            <p>Tap to take a screenshot</p>
          </div>
        </div>
        <button @click="startAR" class="start-ar-btn">
          <Camera :size="20" />
          Start AR Experience
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { 
  Camera, 
  CameraOff, 
  RefreshCw, 
  Loader2, 
  AlertTriangle,
  Check,
  ShoppingCart,
  Heart,
  X
} from 'lucide-vue-next';

interface Equipment {
  id: number;
  name: string;
  modelUrl: string;
  originalPrice: number;
  discountPrice?: number;
  specifications: Record<string, string>;
  features: string[];
}

interface Props {
  equipment: Equipment;
  autoRotate?: boolean;
  cameraControls?: boolean;
  backgroundColor?: string;
}

const props = withDefaults(defineProps<Props>(), {
  autoRotate: true,
  cameraControls: true,
  backgroundColor: '#ffffff',
});

const emit = defineEmits<{
  addToCart: [equipmentId: number];
  addToWishlist: [equipmentId: number];
  modelLoaded: [];
  modelError: [error: Error];
}>();

const modelViewer = ref<any>(null);
const viewerContainer = ref<HTMLElement | null>(null);
const isARActive = ref(false);
const arSupported = ref(false);
const showARInstructions = ref(false);
const isInWishlist = ref(false);
const autoRotate = ref(props.autoRotate);
const cameraControls = ref(props.cameraControls);
const interactionPrompt = ref('none');
const arModes = ref(['webxr', 'scene-viewer']);
const arScale = ref('auto');

const checkARSupport = () => {
  arSupported.value = 'xr' in window.navigator;
};

const toggleAR = () => {
  if (!arSupported.value) return;
  
  if (!isARActive.value) {
    showARInstructions.value = true;
  } else {
    isARActive.value = false;
    if (modelViewer.value) {
      modelViewer.value.exitAR();
    }
  }
};

const startAR = () => {
  showARInstructions.value = false;
  isARActive.value = true;
  if (modelViewer.value) {
    modelViewer.value.enterAR();
  }
};

const closeInstructions = () => {
  showARInstructions.value = false;
};

const resetView = () => {
  if (modelViewer.value) {
    modelViewer.value.resetCamera();
  }
  autoRotate.value = props.autoRotate;
  cameraControls.value = props.cameraControls;
};

const onModelLoad = () => {
  emit('modelLoaded');
};

const onModelError = (error: Error) => {
  emit('modelError', error);
};

const addToCart = () => {
  emit('addToCart', props.equipment.id);
};

const addToWishlist = () => {
  isInWishlist.value = !isInWishlist.value;
  emit('addToWishlist', props.equipment.id);
};

const formatLabel = (key: string): string => {
  return key
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
};

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
  }).format(price);
};

const calculateDiscount = (): number => {
  if (!props.equipment.discountPrice) return 0;
  return Math.round(
    ((props.equipment.originalPrice - props.equipment.discountPrice) / 
     props.equipment.originalPrice) * 100
  );
};

onMounted(() => {
  checkARSupport();
  
  if (viewerContainer.value) {
    const resizeObserver = new ResizeObserver(() => {
      if (modelViewer.value) {
        modelViewer.value.requestUpdate();
      }
    });
    resizeObserver.observe(viewerContainer.value);
    
    onUnmounted(() => {
      resizeObserver.disconnect();
    });
  }
});
</script>

<style scoped>
.sports-ar-equipment-viewer {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.viewer-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.viewer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
}

.viewer-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #111827;
}

.viewer-controls {
  display: flex;
  gap: 12px;
}

.ar-toggle-btn,
.reset-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: 1px solid #e5e7eb;
  background: white;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s;
}

.ar-toggle-btn:hover:not(:disabled),
.reset-btn:hover {
  background: #f9fafb;
  border-color: #d1d5db;
}

.ar-toggle-btn.active {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

.ar-toggle-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.model-viewer-wrapper {
  position: relative;
  width: 100%;
  height: 500px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.model-viewer {
  width: 100%;
  height: 100%;
}

.model-poster {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: white;
}

.loading-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.ar-not-supported {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  color: white;
  text-align: center;
}

.viewer-info {
  padding: 24px;
}

.info-section {
  margin-bottom: 24px;
}

.info-section h4 {
  margin: 0 0 12px;
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
}

.specs-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.specs-list li {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #f3f4f6;
}

.spec-label {
  font-weight: 500;
  color: #6b7280;
}

.spec-value {
  font-weight: 600;
  color: #111827;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
}

.feature-tag {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #166534;
}

.price-display {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.original-price {
  font-size: 1.125rem;
  color: #9ca3af;
  text-decoration: line-through;
}

.current-price {
  font-size: 2rem;
  font-weight: 700;
  color: #111827;
}

.discount-badge {
  padding: 4px 12px;
  background: #fef3c7;
  color: #92400e;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
}

.viewer-actions {
  display: flex;
  gap: 12px;
  margin-top: 24px;
}

.add-to-cart-btn,
.wishlist-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  flex: 1;
  padding: 14px 24px;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.add-to-cart-btn {
  background: #3b82f6;
  color: white;
}

.add-to-cart-btn:hover {
  background: #2563eb;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.wishlist-btn {
  background: white;
  border: 2px solid #e5e7eb;
  color: #374151;
}

.wishlist-btn:hover {
  background: #f9fafb;
  border-color: #d1d5db;
}

.ar-instructions-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.instructions-content {
  background: white;
  border-radius: 16px;
  max-width: 500px;
  width: 100%;
  padding: 32px;
}

.instructions-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.instructions-header h3 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #111827;
}

.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
  padding: 4px;
}

.close-btn:hover {
  color: #111827;
}

.instructions-body {
  margin-bottom: 24px;
}

.instruction-step {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 20px;
}

.step-number {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: #3b82f6;
  color: white;
  border-radius: 50%;
  font-weight: 600;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.instruction-step p {
  margin: 0;
  color: #374151;
  line-height: 1.5;
}

.start-ar-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px 24px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.start-ar-btn:hover {
  background: #2563eb;
  transform: translateY(-2px);
}

@media (max-width: 768px) {
  .viewer-header {
    flex-direction: column;
    gap: 16px;
    align-items: flex-start;
  }

  .model-viewer-wrapper {
    height: 350px;
  }

  .features-grid {
    grid-template-columns: 1fr;
  }

  .viewer-actions {
    flex-direction: column;
  }
}
</style>
