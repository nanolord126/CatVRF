<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { io, Socket } from 'socket.io-client';

interface ServiceSlot {
  id: number;
  start_time: string;
  end_time: string;
  master_id: number;
  master_name: string;
  price: number;
  is_available: boolean;
}

interface MasterLocation {
  master_id: number;
  latitude: number;
  longitude: number;
  last_update: string;
}

const selectedDate = ref(new Date().toISOString().split('T')[0]);
const availableSlots = ref<ServiceSlot[]>([]);
const selectedSlot = ref<ServiceSlot | null>(null);
const masterLocations = ref<MasterLocation[]>([]);
const isLoading = ref(false);
const socket = ref<Socket | null>(null);

const loadAvailableSlots = async () => {
  isLoading.value = true;
  try {
    const response = await fetch(`/api/v1/auto/diagnostics/slots?date=${selectedDate.value}`);
    const data = await response.json();
    availableSlots.value = data.slots || [];
  } catch (error) {
    console.error('Error loading slots:', error);
  } finally {
    isLoading.value = false;
  }
};

const selectSlot = (slot: ServiceSlot) => {
  selectedSlot.value = slot;
};

const bookSlot = async () => {
  if (!selectedSlot.value) return;
  
  try {
    const response = await fetch('/api/v1/auto/diagnostics/book', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        slot_id: selectedSlot.value.id,
        date: selectedDate.value,
      }),
    });
    
    if (response.ok) {
      alert('Слот успешно забронирован!');
      loadAvailableSlots();
    }
  } catch (error) {
    console.error('Error booking slot:', error);
  }
};

const initWebSocket = () => {
  socket.value = io(config('services.websocket.url'), {
    transports: ['websocket'],
  });
  
  socket.value.on('master_location_update', (data: MasterLocation) => {
    const index = masterLocations.value.findIndex(m => m.master_id === data.master_id);
    if (index !== -1) {
      masterLocations.value[index] = data;
    } else {
      masterLocations.value.push(data);
    }
  });
  
  socket.value.on('slot_available', (data: ServiceSlot) => {
    if (data.start_time.startsWith(selectedDate.value)) {
      availableSlots.value.push(data);
    }
  });
  
  socket.value.on('slot_booked', (data: { slot_id: number }) => {
    const index = availableSlots.value.findIndex(s => s.id === data.slot_id);
    if (index !== -1) {
      availableSlots.value.splice(index, 1);
    }
  });
};

onMounted(() => {
  loadAvailableSlots();
  initWebSocket();
});

onUnmounted(() => {
  if (socket.value) {
    socket.value.disconnect();
  }
});
</script>

<template>
  <div class="instant-booking max-w-6xl mx-auto p-6">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Мгновенное бронирование слота</h2>
    
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Выберите дату</label>
      <input 
        v-model="selectedDate"
        type="date" 
        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
        @change="loadAvailableSlots"
      />
    </div>

    <div v-if="isLoading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Загрузка доступных слотов...</p>
    </div>

    <div v-else-if="availableSlots.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
      <p class="text-gray-600">Нет доступных слотов на выбранную дату</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div 
        v-for="slot in availableSlots" 
        :key="slot.id"
        @click="selectSlot(slot)"
        class="p-4 border rounded-lg cursor-pointer transition-all hover:shadow-lg"
        :class="{
          'border-blue-500 bg-blue-50': selectedSlot?.id === slot.id,
          'border-gray-200 hover:border-blue-300': selectedSlot?.id !== slot.id
        }"
      >
        <div class="flex justify-between items-start mb-2">
          <h3 class="font-semibold text-gray-900">{{ slot.master_name }}</h3>
          <span class="text-sm text-gray-500">{{ new Date(slot.start_time).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' }) }}</span>
        </div>
        
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
          <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
          <span>Онлайн</span>
        </div>
        
        <p class="font-bold text-blue-600">{{ new Intl.NumberFormat('ru-RU').format(slot.price) }} ₽</p>
      </div>
    </div>

    <div v-if="selectedSlot" class="mt-6 p-4 bg-blue-50 rounded-lg">
      <h3 class="font-semibold text-gray-900 mb-2">Выбранный слот</h3>
      <div class="flex justify-between items-center">
        <div>
          <p class="font-medium">{{ selectedSlot.master_name }}</p>
          <p class="text-sm text-gray-600">
            {{ new Date(selectedSlot.start_time).toLocaleString('ru-RU') }}
          </p>
        </div>
        <div class="text-right">
          <p class="font-bold text-xl">{{ new Intl.NumberFormat('ru-RU').format(selectedSlot.price) }} ₽</p>
          <button 
            @click="bookSlot"
            class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Забронировать
          </button>
        </div>
      </div>
    </div>

    <div class="mt-8 p-4 bg-gray-50 rounded-lg">
      <h3 class="font-semibold text-gray-900 mb-4">Местоположение мастеров (реальное время)</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
        <div 
          v-for="master in masterLocations.slice(0, 8)" 
          :key="master.master_id"
          class="p-2 bg-white rounded border text-sm"
        >
          <p class="font-medium">Мастер #{{ master.master_id }}</p>
          <p class="text-xs text-gray-500">
            {{ master.latitude.toFixed(4) }}, {{ master.longitude.toFixed(4) }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.instant-booking {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>
