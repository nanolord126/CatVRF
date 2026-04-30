<template>
  <div class="iot-device-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ device.name }}</h3>
          <p class="text-sm text-gray-500">{{ device.id }}</p>
        </div>
        <span :class="getStatusClass(device.status)" class="px-3 py-1 rounded-full text-xs font-bold">
          {{ device.status }}
        </span>
      </div>
    </div>

    <!-- Device Type -->
    <div class="mb-4">
      <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs">
        {{ device.type }}
      </span>
    </div>

    <!-- Telemetry -->
    <div class="bg-gray-50 rounded-lg p-3 mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Latest Telemetry</p>
      <div class="space-y-2 text-sm">
        <div 
          v-for="(value, key) in device.telemetry" 
          :key="key"
          class="flex justify-between"
        >
          <span class="text-gray-600">{{ formatKey(key) }}:</span>
          <span class="font-medium">{{ value }}</span>
        </div>
      </div>
    </div>

    <!-- Last Seen -->
    <div class="mb-4">
      <p class="text-sm text-gray-600">Last seen: {{ device.lastSeen }}</p>
    </div>

    <!-- Battery -->
    <div v-if="device.batteryLevel !== undefined" class="mb-4">
      <div class="flex justify-between text-sm mb-1">
        <span class="text-gray-600">Battery</span>
        <span :class="getBatteryClass(device.batteryLevel)" class="font-medium">
          {{ device.batteryLevel }}%
        </span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div 
          :class="getBatteryBarClass(device.batteryLevel)"
          class="h-2 rounded-full" 
          :style="{ width: device.batteryLevel + '%' }"
        ></div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="viewDetails"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Details
      </button>
      <button 
        @click="configure"
        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Configure
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface IoTDevice {
  id: string
  name: string
  type: string
  status: 'Online' | 'Offline' | 'Error'
  telemetry: Record<string, string>
  lastSeen: string
  batteryLevel?: number
}

const props = defineProps<{
  device: IoTDevice
}>()

const emit = defineEmits<{
  viewDetails: [device: IoTDevice]
  configure: [device: IoTDevice]
}>()

const formatKey = (key: string) => {
  return key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ')
}

const getStatusClass = (status: string) => {
  const classes = {
    'Online': 'bg-green-100 text-green-900',
    'Offline': 'bg-gray-100 text-gray-900',
    'Error': 'bg-red-100 text-red-900',
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const getBatteryClass = (level: number) => {
  if (level > 50) return 'text-green-600'
  if (level > 20) return 'text-yellow-600'
  return 'text-red-600'
}

const getBatteryBarClass = (level: number) => {
  if (level > 50) return 'bg-green-600'
  if (level > 20) return 'bg-yellow-600'
  return 'bg-red-600'
}

const viewDetails = () => {
  emit('viewDetails', props.device)
}

const configure = () => {
  emit('configure', props.device)
}
</script>
