<template>
  <div class="contraindication-check-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <h3 class="text-lg font-bold text-gray-900">Contraindication Check</h3>
      <p class="text-sm text-gray-500">Review potential interactions and risks</p>
    </div>

    <!-- Patient Info -->
    <div class="bg-gray-50 rounded-lg p-3 mb-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
          <span class="text-blue-600 font-bold">{{ patient.initials }}</span>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-900">{{ patient.name }}</p>
          <p class="text-xs text-gray-500">{{ patient.age }} • {{ patient.gender }}</p>
        </div>
      </div>
    </div>

    <!-- Medications -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Current Medications</p>
      <div class="flex flex-wrap gap-2">
        <span 
          v-for="med in patient.medications" 
          :key="med"
          class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs"
        >
          {{ med }}
        </span>
      </div>
    </div>

    <!-- Allergies -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Known Allergies</p>
      <div class="flex flex-wrap gap-2">
        <span 
          v-for="allergy in patient.allergies" 
          :key="allergy"
          class="px-2 py-1 bg-red-50 text-red-700 rounded text-xs"
        >
          {{ allergy }}
        </span>
      </div>
    </div>

    <!-- Check Results -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Check Results</p>
      <div class="space-y-2">
        <div 
          v-for="result in checkResults" 
          :key="result.id"
          :class="getSeverityClass(result.severity)"
          class="p-3 rounded-lg"
        >
          <div class="flex items-start gap-2">
            <span class="text-lg">{{ getSeverityIcon(result.severity) }}</span>
            <div>
              <p class="font-medium">{{ result.title }}</p>
              <p class="text-sm">{{ result.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="viewDetails"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Full Report
      </button>
      <button 
        @click="approve"
        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Approve
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Patient {
  name: string
  initials: string
  age: string
  gender: string
  medications: string[]
  allergies: string[]
}

interface CheckResult {
  id: string
  severity: 'high' | 'medium' | 'low'
  title: string
  description: string
}

const props = defineProps<{
  patient: Patient
  checkResults: CheckResult[]
}>()

const emit = defineEmits<{
  viewDetails: []
  approve: []
}>()

const getSeverityClass = (severity: string) => {
  const classes = {
    'high': 'bg-red-50 border border-red-200',
    'medium': 'bg-yellow-50 border border-yellow-200',
    'low': 'bg-green-50 border border-green-200',
  }
  return classes[severity as keyof typeof classes] || 'bg-gray-50'
}

const getSeverityIcon = (severity: string) => {
  const icons = {
    'high': '⚠️',
    'medium': '⚡',
    'low': '✓',
  }
  return icons[severity as keyof typeof icons] || 'ℹ️'
}

const viewDetails = () => {
  emit('viewDetails')
}

const approve = () => {
  emit('approve')
}
</script>
