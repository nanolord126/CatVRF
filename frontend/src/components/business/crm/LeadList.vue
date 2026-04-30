<template>
  <div class="lead-list">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-lg font-semibold text-gray-900">Лиды</h3>
      <div class="flex gap-2">
        <select v-model="selectedStatus" class="px-3 py-2 border rounded-lg text-sm">
          <option value="">Все статусы</option>
          <option value="new">Новые</option>
          <option value="contacted">Контактированы</option>
          <option value="qualified">Квалифицированы</option>
          <option value="converted">Конвертированы</option>
          <option value="lost">Потеряны</option>
        </select>
        <button
          @click="$emit('create-lead')"
          class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition text-sm"
        >
          + Создать лида
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-cyan-600"></div>
    </div>

    <div v-else-if="filteredLeads.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
      <p class="text-gray-600">Нет лидов</p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="lead in filteredLeads"
        :key="lead.id"
        class="bg-white border rounded-lg p-4 hover:shadow-md transition cursor-pointer"
        @click="viewLead(lead.id)"
      >
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span
                :class="[
                  'px-2 py-1 text-xs font-medium rounded',
                  getStatusClass(lead.status)
                ]"
              >
                {{ getStatusLabel(lead.status) }}
              </span>
              <span class="text-xs text-gray-500">{{ formatDate(lead.created_at) }}</span>
            </div>
            
            <h4 class="font-medium text-gray-900">{{ lead.company_name }}</h4>
            <p v-if="lead.requirement" class="text-sm text-gray-600 mt-1 line-clamp-2">
              {{ lead.requirement }}
            </p>
            
            <div class="flex gap-4 mt-3 text-sm text-gray-600">
              <span>{{ lead.contact_person }}</span>
              <span>{{ lead.contact_email }}</span>
            </div>

            <div class="mt-2 flex gap-2">
              <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                {{ lead.budget_range }}
              </span>
              <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs">
                {{ lead.category }}
              </span>
            </div>
          </div>

          <div class="flex gap-2">
            <button
              v-if="lead.status === 'new' || lead.status === 'contacted'"
              @click.stop="convertToTender(lead)"
              class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
            >
              В тендер
            </button>
            <button
              @click.stop="editLead(lead)"
              class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm"
            >
              Редактировать
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Lead {
  id: number
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: string
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  created_at: string
}

const props = defineProps<{
  leads: Lead[]
  loading: boolean
}>()

const emit = defineEmits<{
  'create-lead': []
  'create-tender': [lead: Lead]
  'view-lead': [id: number]
  'edit-lead': [lead: Lead]
}>()

const selectedStatus = ref<string>('')

const filteredLeads = computed(() => {
  if (!selectedStatus.value) return props.leads
  return props.leads.filter(l => l.status === selectedStatus.value)
})

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    new: 'Новый',
    contacted: 'Контактирован',
    qualified: 'Квалифицирован',
    converted: 'Конвертирован',
    lost: 'Потерян'
  }
  return labels[status] || status
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    new: 'bg-blue-100 text-blue-800',
    contacted: 'bg-yellow-100 text-yellow-800',
    qualified: 'bg-green-100 text-green-800',
    converted: 'bg-purple-100 text-purple-800',
    lost: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const viewLead = (id: number) => {
  emit('view-lead', id)
}

const editLead = (lead: Lead) => {
  emit('edit-lead', lead)
}

const convertToTender = (lead: Lead) => {
  emit('create-tender', lead)
}
</script>
