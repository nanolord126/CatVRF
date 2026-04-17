<template>
  <div class="fashion-outfit-builder bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-4 text-white">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-bold text-lg">Outfit Builder</h3>
          <p class="text-sm text-indigo-200">Create your perfect look</p>
        </div>
        <button
          @click="saveOutfit"
          :disabled="selectedItems.length === 0"
          class="bg-white text-indigo-600 px-4 py-2 rounded-lg font-semibold hover:bg-indigo-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Save Outfit
        </button>
      </div>
    </div>

    <div class="p-4">
      <!-- Outfit Preview -->
      <div class="mb-6">
        <h4 class="font-semibold text-gray-900 mb-3">Your Outfit</h4>
        <div class="grid grid-cols-4 gap-4">
          <div
            v-for="category in categories"
            :key="category.id"
            class="aspect-square bg-gray-100 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center relative overflow-hidden"
            :class="{ 'border-indigo-500': selectedItems[category.id] }"
          >
            <img
              v-if="selectedItems[category.id]"
              :src="selectedItems[category.id].image_url"
              :alt="selectedItems[category.id].name"
              class="w-full h-full object-cover"
            >
            <div v-else class="text-center text-gray-400">
              <svg class="w-8 h-8 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
              </svg>
              <span class="text-xs">{{ category.name }}</span>
            </div>
            <button
              v-if="selectedItems[category.id]"
              @click="removeItem(category.id)"
              class="absolute top-2 right-2 bg-red-500 text-white w-6 h-6 rounded-full flex items-center justify-center hover:bg-red-600 transition-colors"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Product Selection -->
      <div>
        <div class="flex items-center justify-between mb-3">
          <h4 class="font-semibold text-gray-900">Select Items</h4>
          <select
            v-model="selectedCategory"
            class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            <option value="">All Categories</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>

        <div class="grid grid-cols-3 gap-3 max-h-64 overflow-y-auto">
          <div
            v-for="product in filteredProducts"
            :key="product.id"
            @click="selectItem(product)"
            class="cursor-pointer border rounded-lg overflow-hidden hover:shadow-lg transition-shadow"
            :class="{ 'ring-2 ring-indigo-500': isSelected(product.id) }"
          >
            <img
              :src="product.image_url"
              :alt="product.name"
              class="w-full aspect-square object-cover"
            >
            <div class="p-2">
              <p class="text-xs font-medium truncate">{{ product.name }}</p>
              <p class="text-xs text-gray-600">{{ product.price_b2c }} ₽</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Outfit Summary -->
      <div v-if="selectedItemsCount > 0" class="mt-6 p-4 bg-indigo-50 rounded-lg">
        <div class="flex items-center justify-between mb-2">
          <span class="font-semibold text-indigo-900">Total Price</span>
          <span class="font-bold text-indigo-900">{{ totalPrice }} ₽</span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-indigo-700">Items: {{ selectedItemsCount }}</span>
          <span class="text-indigo-700">Savings: {{ savings }} ₽</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

interface Product {
  id: number;
  name: string;
  image_url: string;
  price_b2c: number;
  category_id: number;
}

interface Category {
  id: number;
  name: string;
}

const emit = defineEmits<{
  outfitSaved: [outfit: Record<number, Product>];
}>();

const categories = ref<Category[]>([
  { id: 1, name: 'Top' },
  { id: 2, name: 'Bottom' },
  { id: 3, name: 'Shoes' },
  { id: 4, name: 'Accessory' },
]);

const selectedItems = ref<Record<number, Product>>({});
const selectedCategory = ref<number | ''>('');
const products = ref<Product[]>([]);

const filteredProducts = computed(() => {
  if (!selectedCategory.value) {
    return products.value;
  }
  return products.value.filter(p => p.category_id === selectedCategory.value);
});

const selectedItemsCount = computed(() => {
  return Object.keys(selectedItems.value).length;
});

const totalPrice = computed(() => {
  return Object.values(selectedItems.value).reduce((sum, item) => sum + item.price_b2c, 0);
});

const savings = computed(() => {
  // Calculate 10% bundle discount
  return Math.round(totalPrice.value * 0.1);
});

const isSelected = (productId: number): boolean => {
  return Object.values(selectedItems.value).some(item => item.id === productId);
};

const selectItem = (product: Product) => {
  // Find which category this product belongs to
  const category = categories.value.find(c => c.id === product.category_id);
  if (category) {
    selectedItems.value[category.id] = product;
  }
};

const removeItem = (categoryId: number) => {
  delete selectedItems.value[categoryId];
};

const saveOutfit = () => {
  emit('outfitSaved', selectedItems.value);
};

onMounted(async () => {
  // Load products from API
  try {
    const response = await fetch('/api/fashion/products');
    products.value = await response.json();
  } catch (error) {
    console.error('Failed to load products:', error);
  }
});
</script>
