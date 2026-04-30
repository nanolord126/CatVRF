<template>
  <div class="supplier-registration-list">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Регистрации поставщиков</h2>
      <div class="flex gap-2">
        <select v-model="selectedStatus" class="px-4 py-2 border rounded-lg">
          <option value="">Все статусы</option>
          <option value="pending">Ожидают</option>
          <option value="under_review">На рассмотрении</option>
          <option value="approved">Одобрены</option>
          <option value="rejected">Отклонены</option>
          <option value="suspended">Приостановлены</option>
        </select>
        <select v-model="selectedType" class="px-4 py-2 border rounded-lg">
          <option value="">Все типы</option>
          <option value="b2b_only">Только B2B</option>
          <option value="b2c_only">Только B2C</option>
          <option value="both">B2B и B2C</option>
        </select>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Всего заявок</div>
        <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">На рассмотрении</div>
        <div class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Одобрено</div>
        <div class="text-2xl font-bold text-green-600">{{ stats.approved }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Отклонено</div>
        <div class="text-2xl font-bold text-red-600">{{ stats.rejected }}</div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Загрузка...</p>
    </div>

    <!-- Empty state -->
    <div v-else-if="filteredRegistrations.length === 0" class="text-center py-12 bg-gray-50 rounded-lg">
      <p class="text-gray-600">Нет регистраций</p>
    </div>

    <!-- Registration list -->
    <div v-else class="space-y-4">
      <div
        v-for="registration in filteredRegistrations"
        :key="registration.id"
        class="bg-white border rounded-lg p-6 hover:shadow-md transition"
      >
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span
                :class="[
                  'px-2 py-1 text-xs font-medium rounded',
                  getStatusClass(registration.status)
                ]"
              >
                {{ getStatusLabel(registration.status) }}
              </span>
              <span
                :class="[
                  'px-2 py-1 text-xs font-medium rounded',
                  getTypeClass(registration.registration_type)
                ]"
              >
                {{ getTypeLabel(registration.registration_type) }}
              </span>
              <span class="text-xs text-gray-500">{{ formatDate(registration.created_at) }}</span>
            </div>

            <h3 class="text-lg font-semibold text-gray-900">{{ registration.company_name }}</h3>
            
            <div class="grid grid-cols-2 gap-4 mt-4">
              <div>
                <p class="text-sm text-gray-600">ИНН</p>
                <p class="font-medium text-gray-900">{{ maskINN(registration.inn) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Уровень поставщика</p>
                <p class="font-medium text-gray-900">{{ registration.supplier_tier_name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Контактное лицо</p>
                <p class="font-medium text-gray-900">{{ registration.contact_person }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Email</p>
                <p class="font-medium text-gray-900">{{ registration.contact_email }}</p>
              </div>
            </div>

            <div v-if="registration.warehouse_ids && registration.warehouse_ids.length > 0" class="mt-4">
              <p class="text-sm text-gray-600">Склады: {{ registration.warehouse_ids.length }}</p>
            </div>

            <div v-if="registration.rejection_reason" class="mt-4 bg-red-50 rounded p-3">
              <p class="text-sm text-red-800">{{ registration.rejection_reason }}</p>
            </div>
          </div>

          <div class="flex gap-2">
            <button
              v-if="registration.status === 'pending' || registration.status === 'under_review'"
              @click="approveRegistration(registration)"
              class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm"
            >
              Одобрить
            </button>
            <button
              v-if="registration.status === 'pending' || registration.status === 'under_review'"
              @click="rejectRegistration(registration)"
              class="px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition text-sm"
            >
              Отклонить
            </button>
            <button
              @click="viewDetails(registration)"
              class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
            >
              Подробнее
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface SupplierRegistration {
  id: number
  company_name: string
  inn: string
  supplier_tier_id: number
  supplier_tier_name: string
  registration_type: 'b2b_only' | 'b2c_only' | 'both'
  contact_person: string
  contact_email: string
  contact_phone: string
  status: 'pending' | 'under_review' | 'approved' | 'rejected' | 'suspended'
  warehouse_ids: number[] | null
  rejection_reason: string | null
  created_at: string
  approved_at: string | null
}

const registrations = ref<SupplierRegistration[]>([])
const loading = ref(false)
const selectedStatus = ref<string>('')
const selectedType = ref<string>('')

const stats = ref({
  total: 0,
  pending: 0,
  approved: 0,
  rejected: 0
})

const filteredRegistrations = computed(() => {
  let result = registrations.value
  
  if (selectedStatus.value) {
    result = result.filter(r => r.status === selectedStatus.value)
  }
  
  if (selectedType.value) {
    result = result.filter(r => r.registration_type === selectedType.value)
  }
  
  return result
})

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    pending: 'Ожидает',
    under_review: 'На рассмотрении',
    approved: 'Одобрено',
    rejected: 'Отклонено',
    suspended: 'Приостановлено'
  }
  return labels[status] || status
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    under_review: 'bg-blue-100 text-blue-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    suspended: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    b2b_only: 'B2B',
    b2c_only: 'B2C',
    both: 'B2B + B2C'
  }
  return labels[type] || type
}

const getTypeClass = (type: string) => {
  const classes: Record<string, string> = {
    b2b_only: 'bg-blue-100 text-blue-800',
    b2c_only: 'bg-green-100 text-green-800',
    both: 'bg-purple-100 text-purple-800'
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const maskINN = (inn: string) => {
  if (inn.length <= 4) return inn
  return inn.substring(0, 4) + '******'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const approveRegistration = async (registration: SupplierRegistration) => {
  // API call to approve
  console.log('Approve:', registration.id)
}

const rejectRegistration = async (registration: SupplierRegistration) => {
  const reason = prompt('Причина отклонения:')
  if (reason) {
    // API call to reject
    console.log('Reject:', registration.id, reason)
  }
}

const viewDetails = (registration: SupplierRegistration) => {
  console.log('View details:', registration.id)
}
</script>
