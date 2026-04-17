<script setup lang="ts">
import { ref, onMounted } from 'vue';

interface FleetVehicle {
  id: number;
  vin: string;
  make: string;
  model: string;
  year: number;
  status: 'active' | 'maintenance' | 'inactive';
  mileage: number;
  last_service: string;
  next_service: string;
  driver_id?: number;
  driver_name?: string;
}

const fleetVehicles = ref<FleetVehicle[]>([]);
const isLoading = ref(false);
const selectedVehicle = ref<FleetVehicle | null>(null);

const loadFleet = async () => {
  isLoading.value = true;
  try {
    const response = await fetch('/api/v1/auto/fleet');
    const data = await response.json();
    if (data.success) {
      fleetVehicles.value = data.vehicles;
    }
  } catch (error) {
    console.error('Error loading fleet:', error);
  } finally {
    isLoading.value = false;
  }
};

const getStatusColor = (status: string): string => {
  switch (status) {
    case 'active': return 'bg-green-100 text-green-800';
    case 'maintenance': return 'bg-yellow-100 text-yellow-800';
    case 'inactive': return 'bg-red-100 text-red-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const getStatusLabel = (status: string): string => {
  switch (status) {
    case 'active': return 'Активен';
    case 'maintenance': return 'На обслуживании';
    case 'inactive': return 'Неактивен';
    default: return status;
  }
};

const formatMileage = (mileage: number): string => {
  return new Intl.NumberFormat('ru-RU').format(mileage) + ' км';
};

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU');
};

const selectVehicle = (vehicle: FleetVehicle) => {
  selectedVehicle.value = vehicle;
};

const closeDetails = () => {
  selectedVehicle.value = null;
};

onMounted(() => {
  loadFleet();
});
</script>

<template>
  <div class="auto-fleet-manager max-w-6xl mx-auto p-6 bg-white rounded-xl shadow-lg">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Управление автопарком</h2>
      <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
        + Добавить авто
      </button>
    </div>

    <div v-if="isLoading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Загрузка...</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div 
        v-for="vehicle in fleetVehicles" 
        :key="vehicle.id"
        @click="selectVehicle(vehicle)"
        class="p-4 border rounded-lg cursor-pointer transition-all hover:shadow-lg"
        :class="{
          'border-gray-200': !selectedVehicle || selectedVehicle.id !== vehicle.id,
          'border-blue-500 bg-blue-50': selectedVehicle?.id === vehicle.id
        }"
      >
        <div class="flex justify-between items-start mb-2">
          <div>
            <p class="font-bold text-gray-900">{{ vehicle.make }} {{ vehicle.model }}</p>
            <p class="text-sm text-gray-600">{{ vehicle.year }} • {{ vehicle.vin }}</p>
          </div>
          <span 
            class="px-2 py-1 rounded-full text-xs font-medium"
            :class="getStatusColor(vehicle.status)"
          >
            {{ getStatusLabel(vehicle.status) }}
          </span>
        </div>

        <div class="space-y-1 text-sm text-gray-600">
          <p>📊 Пробег: {{ formatMileage(vehicle.mileage) }}</p>
          <p>🔧 Последнее ТО: {{ formatDate(vehicle.last_service) }}</p>
          <p>📅 Следующее ТО: {{ formatDate(vehicle.next_service) }}</p>
          <p v-if="vehicle.driver_name">👤 Водитель: {{ vehicle.driver_name }}</p>
        </div>
      </div>
    </div>

    <div v-if="selectedVehicle" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-start mb-4">
          <h3 class="text-xl font-bold text-gray-900">
            {{ selectedVehicle.make }} {{ selectedVehicle.model }}
          </h3>
          <button 
            @click="closeDetails"
            class="p-2 hover:bg-gray-100 rounded-full"
          >
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="p-3 bg-gray-50 rounded-lg">
              <p class="text-sm text-gray-600">VIN</p>
              <p class="font-medium">{{ selectedVehicle.vin }}</p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
              <p class="text-sm text-gray-600">Год</p>
              <p class="font-medium">{{ selectedVehicle.year }}</p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
              <p class="text-sm text-gray-600">Пробег</p>
              <p class="font-medium">{{ formatMileage(selectedVehicle.mileage) }}</p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
              <p class="text-sm text-gray-600">Статус</p>
              <p class="font-medium">{{ getStatusLabel(selectedVehicle.status) }}</p>
            </div>
          </div>

          <div class="p-3 bg-blue-50 rounded-lg">
            <p class="text-sm text-gray-600">Последнее ТО</p>
            <p class="font-medium">{{ formatDate(selectedVehicle.last_service) }}</p>
          </div>

          <div class="p-3 bg-yellow-50 rounded-lg">
            <p class="text-sm text-gray-600">Следующее ТО</p>
            <p class="font-medium">{{ formatDate(selectedVehicle.next_service) }}</p>
          </div>

          <div v-if="selectedVehicle.driver_name" class="p-3 bg-purple-50 rounded-lg">
            <p class="text-sm text-gray-600">Водитель</p>
            <p class="font-medium">{{ selectedVehicle.driver_name }}</p>
          </div>

          <div class="flex gap-3 mt-6">
            <button class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
              Записать на ТО
            </button>
            <button class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
              История
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auto-fleet-manager {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>
