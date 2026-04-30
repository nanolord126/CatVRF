<template>
  <div class="ai-constructor-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <h3 class="text-lg font-bold text-gray-900">{{ constructor.name }}</h3>
      <p class="text-sm text-gray-500">{{ constructor.description }}</p>
    </div>

    <!-- AI Model Selection -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">AI Model</label>
      <select 
        v-model="selectedModel"
        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
      >
        <option v-for="model in models" :key="model.id" :value="model.id">
          {{ model.name }}
        </option>
      </select>
    </div>

    <!-- Configuration Parameters -->
    <div class="mb-4 space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Temperature</label>
        <input 
          v-model.number="config.temperature"
          type="range" 
          min="0" 
          max="1" 
          step="0.1"
          class="w-full"
        />
        <span class="text-xs text-gray-500">{{ config.temperature }}</span>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Max Tokens</label>
        <input 
          v-model.number="config.maxTokens"
          type="number" 
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
        />
      </div>
    </div>

    <!-- Status -->
    <div class="mb-4">
      <div class="flex items-center justify-between">
        <span class="text-sm text-gray-700">Status</span>
        <span :class="getStatusClass()" class="text-xs font-medium px-2 py-1 rounded-full">
          {{ status }}
        </span>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="testModel"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Test
      </button>
      <button 
        @click="deployModel"
        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Deploy
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface AIModel {
  id: string
  name: string
}

interface AIConstructor {
  name: string
  description: string
}

interface Config {
  temperature: number
  maxTokens: number
}

const props = defineProps<{
  constructor: AIConstructor
  models: AIModel[]
}>()

const emit = defineEmits<{
  testModel: []
  deployModel: []
}>()

const selectedModel = ref(props.models[0]?.id || '')
const config = ref<Config>({
  temperature: 0.7,
  maxTokens: 2048
})
const status = ref('Ready')

const getStatusClass = () => {
  switch (status.value) {
    case 'Ready':
      return 'bg-green-100 text-green-800'
    case 'Testing':
      return 'bg-yellow-100 text-yellow-800'
    case 'Deployed':
      return 'bg-blue-100 text-blue-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const testModel = () => {
  status.value = 'Testing'
  emit('testModel')
  setTimeout(() => {
    status.value = 'Ready'
  }, 2000)
}

const deployModel = () => {
  status.value = 'Deployed'
  emit('deployModel')
}
</script>
