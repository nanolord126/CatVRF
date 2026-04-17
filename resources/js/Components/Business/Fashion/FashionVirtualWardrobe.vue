<template>
  <div class="fashion-virtual-wardrobe bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="bg-gradient-to-r from-teal-600 to-cyan-600 p-4 text-white">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-bold text-lg">Virtual Wardrobe</h3>
          <p class="text-sm text-teal-200">Your personal collection</p>
        </div>
        <button
          @click="addItem"
          class="bg-white text-teal-600 px-4 py-2 rounded-lg font-semibold hover:bg-teal-50 transition-colors"
        >
          + Add Item
        </button>
      </div>
    </div>

    <div class="p-4">
      <!-- Filter Tabs -->
      <div class="flex gap-2 mb-4 overflow-x-auto pb-2">
        <button
          v-for="category in categories"
          :key="category.id"
          @click="selectedCategory = category.id"
          :class="selectedCategory === category.id ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-700'"
          class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-colors"
        >
          {{ category.name }}
        </button>
      </div>

      <!-- Wardrobe Grid -->
      <div class="grid grid-cols-3 gap-3 max-h-96 overflow-y-auto">
        <div
          v-for="item in filteredItems"
          :key="item.id"
          class="relative group cursor-pointer"
          @click="selectItem(item)"
        >
          <img
            :src="item.image_url"
            :alt="item.name"
            class="w-full aspect-square object-cover rounded-lg border-2 transition-all"
            :class="selectedItems.has(item.id) ? 'border-teal-500 ring-2 ring-teal-300' : 'border-transparent'"
          >
          <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
            <button
              @click.stop="removeItem(item.id)"
              class="bg-red-500 text-white w-8 h-8 rounded-full flex items-center justify-center hover:bg-red-600"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
              </svg>
            </button>
          </div>
          <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-2 rounded-b-lg">
            <p class="text-white text-xs font-medium truncate">{{ item.name }}</p>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div v-if="selectedItems.size > 0" class="mt-4 flex gap-2">
        <button
          @click="createOutfit"
          class="flex-1 bg-gradient-to-r from-teal-600 to-cyan-600 text-white py-3 rounded-lg font-semibold hover:from-teal-700 hover:to-cyan-700 transition-colors"
        >
          Create Outfit ({{ selectedItems.size }})
        </button>
        <button
          @click="clearSelection"
          class="px-4 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors"
        >
          Clear
        </button>
      </div>

      <!-- Statistics -->
      <div class="mt-4 grid grid-cols-3 gap-3">
        <div class="bg-teal-50 rounded-lg p-3 text-center">
          <div class="text-2xl font-bold text-teal-600">{{ totalItems }}</div>
          <div class="text-xs text-teal-700">Total Items</div>
        </div>
        <div class="bg-cyan-50 rounded-lg p-3 text-center">
          <div class="text-2xl font-bold text-cyan-600">{{ totalValue }} ₽</div>
          <div class="text-xs text-cyan-700">Total Value</div>
        </div>
        <div class="bg-blue-50 rounded-lg p-3 text-center">
          <div class="text-2xl font-bold text-blue-600">{{ outfitsCreated }}</div>
          <div class="text-xs text-blue-700">Outfits Created</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

interface WardrobeItem {
  id: number;
  name: string;
  image_url: string;
  category_id: number;
  price: number;
  added_at: string;
}

interface Category {
  id: number;
  name: string;
}

const emit = defineEmits<{
  outfitCreated: [items: WardrobeItem[]];
  itemAdded: [];
}>();

const categories = ref<Category[]>([
  { id: 1, name: 'All' },
  { id: 2, name: 'Tops' },
  { id: 3, name: 'Bottoms' },
  { id: 4, name: 'Dresses' },
  { id: 5, name: 'Shoes' },
  { id: 6, name: 'Accessories' },
]);

const selectedCategory = ref(1);
const wardrobeItems = ref<WardrobeItem[]>([]);
const selectedItems = ref<Map<number, WardrobeItem>>(new Map());
const outfitsCreated = ref(0);

const filteredItems = computed(() => {
  if (selectedCategory.value === 1) {
    return wardrobeItems.value;
  }
  return wardrobeItems.value.filter(item => item.category_id === selectedCategory.value);
});

const totalItems = computed(() => wardrobeItems.value.length);

const totalValue = computed(() => {
  return wardrobeItems.value.reduce((sum, item) => sum + item.price, 0);
});

const selectItem = (item: WardrobeItem) => {
  if (selectedItems.value.has(item.id)) {
    selectedItems.value.delete(item.id);
  } else {
    selectedItems.value.set(item.id, item);
  }
};

const removeItem = (itemId: number) => {
  wardrobeItems.value = wardrobeItems.value.filter(item => item.id !== itemId);
  selectedItems.value.delete(itemId);
};

const addItem = () => {
  emit('itemAdded');
};

const createOutfit = () => {
  const items = Array.from(selectedItems.value.values());
  emit('outfitCreated', items);
  outfitsCreated.value++;
  clearSelection();
};

const clearSelection = () => {
  selectedItems.value.clear();
};

onMounted(async () => {
  // Load wardrobe items from API
  try {
    const response = await fetch('/api/fashion/virtual-wardrobe');
    wardrobeItems.value = await response.json();
  } catch (error) {
    console.error('Failed to load wardrobe items:', error);
  }
});
</script>
