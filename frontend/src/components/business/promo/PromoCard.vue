<template>
  <div class="promo-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <h3 class="text-lg font-bold text-gray-900">{{ promo.name }}</h3>
      <p class="text-sm text-gray-500">{{ promo.description }}</p>
    </div>

    <!-- Promo Type Badge -->
    <div class="mb-4">
      <span :class="getTypeClass()" class="text-xs font-medium px-2 py-1 rounded-full">
        {{ promo.type }}
      </span>
    </div>

    <!-- Discount Info -->
    <div class="mb-4 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-4">
      <p class="text-2xl font-bold text-indigo-600">{{ promo.discount }}</p>
      <p class="text-sm text-gray-600">{{ promo.discountDescription }}</p>
    </div>

    <!-- Usage Stats -->
    <div class="grid grid-cols-2 gap-3 mb-4">
      <div class="bg-gray-50 rounded-lg p-3">
        <p class="text-xs text-gray-600 mb-1">Used</p>
        <p class="text-lg font-bold text-gray-900">{{ promo.usage.used }}</p>
      </div>
      <div class="bg-gray-50 rounded-lg p-3">
        <p class="text-xs text-gray-600 mb-1">Remaining</p>
        <p class="text-lg font-bold" :class="promo.usage.remaining > 0 ? 'text-green-600' : 'text-red-600'">
          {{ promo.usage.remaining }}
        </p>
      </div>
    </div>

    <!-- Validity -->
    <div class="mb-4">
      <p class="text-sm text-gray-700">
        <span class="font-medium">Valid:</span> {{ promo.validFrom }} - {{ promo.validTo }}
      </p>
    </div>

    <!-- Code Display -->
    <div v-if="promo.code" class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">Promo Code</label>
      <div class="flex items-center gap-2">
        <input 
          :value="promo.code"
          readonly
          class="flex-1 bg-gray-100 border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono"
        />
        <button 
          @click="copyCode"
          class="bg-gray-100 hover:bg-gray-200 text-gray-900 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
        >
          Copy
        </button>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="applyPromo"
        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Apply
      </button>
      <button 
        @click="sharePromo"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Share
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Usage {
  used: number
  remaining: number
}

interface Promo {
  name: string
  description: string
  type: 'Percentage' | 'Fixed' | 'BOGO' | 'Free Shipping'
  discount: string
  discountDescription: string
  usage: Usage
  validFrom: string
  validTo: string
  code?: string
}

const props = defineProps<{
  promo: Promo
}>()

const emit = defineEmits<{
  applyPromo: []
  sharePromo: []
}>()

const getTypeClass = () => {
  switch (props.promo.type) {
    case 'Percentage':
      return 'bg-green-100 text-green-800'
    case 'Fixed':
      return 'bg-blue-100 text-blue-800'
    case 'BOGO':
      return 'bg-purple-100 text-purple-800'
    case 'Free Shipping':
      return 'bg-orange-100 text-orange-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const copyCode = () => {
  if (props.promo.code) {
    navigator.clipboard.writeText(props.promo.code)
  }
}

const applyPromo = () => {
  emit('applyPromo')
}

const sharePromo = () => {
  emit('sharePromo')
}
</script>
