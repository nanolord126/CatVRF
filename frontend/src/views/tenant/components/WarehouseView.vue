<template>
  <div class="space-y-6">
    <!-- Capacity Toggle -->
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-900">Переключение емкости B2B/B2C</h3>
        <button @click="showCapacityModal = true" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
          Изменить емкость
        </button>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- B2B Capacity -->
        <div class="p-4 rounded-lg border-2" :style="{ borderColor: B2B_COLOR }">
          <div class="flex items-center justify-between mb-3">
            <span class="font-semibold" :style="{ color: B2B_COLOR }">B2B Емкость</span>
            <span class="text-2xl font-bold" :style="{ color: B2B_COLOR }">{{ warehouseStats.b2b.percentage }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
            <div class="h-3 rounded-full transition-all" :style="{ width: warehouseStats.b2b.percentage + '%', backgroundColor: B2B_COLOR }"></div>
          </div>
          <div class="flex justify-between text-sm text-gray-600">
            <span>Использовано: {{ warehouseStats.b2b.used }}</span>
            <span>Всего: {{ warehouseStats.b2b.capacity }}</span>
          </div>
        </div>

        <!-- B2C Capacity -->
        <div class="p-4 rounded-lg border-2" :style="{ borderColor: B2C_COLOR }">
          <div class="flex items-center justify-between mb-3">
            <span class="font-semibold" :style="{ color: B2C_COLOR }">B2C Емкость</span>
            <span class="text-2xl font-bold" :style="{ color: B2C_COLOR }">{{ warehouseStats.b2c.percentage }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
            <div class="h-3 rounded-full transition-all" :style="{ width: warehouseStats.b2c.percentage + '%', backgroundColor: B2C_COLOR }"></div>
          </div>
          <div class="flex justify-between text-sm text-gray-600">
            <span>Использовано: {{ warehouseStats.b2c.used }}</span>
            <span>Всего: {{ warehouseStats.b2c.capacity }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Stock Overview -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-900">Складские остатки</h3>
          <div class="flex space-x-3">
            <select class="px-3 py-2 border rounded-lg text-sm">
              <option>Все склады</option>
              <option>Основной склад</option>
              <option>Склад №2</option>
            </select>
            <button class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
              Пополнить
            </button>
          </div>
        </div>
      </div>

      <div class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div v-for="item in stockItems" :key="item.sku" class="p-4 border rounded-lg hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start mb-2">
              <div>
                <p class="font-medium text-gray-900">{{ item.name }}</p>
                <p class="text-sm text-gray-500">SKU: {{ item.sku }}</p>
              </div>
              <span :class="getStockClass(item.quantity, item.minQuantity)" class="px-2 py-1 rounded text-xs font-medium">
                {{ item.quantity }} шт
              </span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">B2B: {{ item.b2bQuantity }}</span>
              <span class="text-gray-500">B2C: {{ item.b2cQuantity }}</span>
            </div>
            <div class="mt-2 pt-2 border-t border-gray-100">
              <p class="text-sm font-medium" :style="{ color: item.orderType === 'b2b' ? B2B_COLOR : B2C_COLOR }">
                {{ item.orderType === 'b2b' ? 'B2B заказ' : 'B2C заказ' }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reservations -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-900">Активные резервирования</h3>
      </div>
      <div class="p-4">
        <div v-for="reservation in reservations" :key="reservation.id" 
             class="py-3 border-b border-gray-100 last:border-0"
             :style="{ borderLeft: `4px solid ${reservation.orderType === 'b2b' ? B2B_COLOR : B2C_COLOR}` }">
          <div class="flex justify-between items-start">
            <div>
              <p class="font-medium text-gray-900">Заказ #{{ reservation.orderId }}</p>
              <p class="text-sm text-gray-500">{{ reservation.items }} позиций</p>
            </div>
            <div class="text-right">
              <span class="px-2 py-1 rounded text-xs font-medium"
                    :style="{ backgroundColor: reservation.orderType === 'b2b' ? B2B_COLORS.light : B2C_COLORS.light, color: reservation.orderType === 'b2b' ? B2B_COLORS.text : B2C_COLORS.text }">
                {{ reservation.orderType.toUpperCase() }}
              </span>
              <p class="text-sm text-gray-500 mt-1">Истекает: {{ reservation.expiresAt }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Capacity Modal -->
    <div v-if="showCapacityModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold mb-4">Изменить емкость</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">B2B → B2C</label>
            <input type="number" v-model="capacityChange.b2bToB2c" class="w-full px-3 py-2 border rounded-lg" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">B2C → B2B</label>
            <input type="number" v-model="capacityChange.b2cToB2b" class="w-full px-3 py-2 border rounded-lg" />
          </div>
        </div>
        <div class="flex justify-end space-x-3 mt-6">
          <button @click="showCapacityModal = false" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Отмена</button>
          <button @click="applyCapacityChange" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Применить</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { B2B_COLOR, B2C_COLOR, B2B_COLORS, B2C_COLORS } from '@/constants/colors';

const showCapacityModal = ref(false);
const capacityChange = ref({ b2bToB2c: 0, b2cToB2b: 0 });

const warehouseStats = ref({
  total: { capacity: 10000, used: 6500, percentage: 65 },
  b2b: { capacity: 6000, used: 3500, percentage: 58 },
  b2c: { capacity: 4000, used: 3000, percentage: 75 },
});

const stockItems = ref([
  { sku: 'SKU001', name: 'Товар 1', quantity: 150, minQuantity: 50, b2bQuantity: 80, b2cQuantity: 70, orderType: 'b2b' },
  { sku: 'SKU002', name: 'Товар 2', quantity: 30, minQuantity: 40, b2bQuantity: 15, b2cQuantity: 15, orderType: 'b2c' },
  { sku: 'SKU003', name: 'Товар 3', quantity: 200, minQuantity: 100, b2bQuantity: 120, b2cQuantity: 80, orderType: 'b2b' },
]);

const reservations = ref([
  { id: 1, orderId: 'ORD-001', items: 5, orderType: 'b2b', expiresAt: '27.04.2026 18:00' },
  { id: 2, orderId: 'ORD-002', items: 3, orderType: 'b2c', expiresAt: '27.04.2026 17:30' },
  { id: 3, orderId: 'ORD-003', items: 8, orderType: 'b2b', expiresAt: '27.04.2026 19:00' },
]);

function getStockClass(quantity: number, minQuantity: number): string {
  if (quantity <= minQuantity) return 'bg-red-100 text-red-800';
  if (quantity <= minQuantity * 1.5) return 'bg-yellow-100 text-yellow-800';
  return 'bg-green-100 text-green-800';
}

function applyCapacityChange() {
  // Apply capacity change logic
  showCapacityModal.value = false;
}
</script>
