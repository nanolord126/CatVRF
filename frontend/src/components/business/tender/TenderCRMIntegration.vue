<template>
  <div class="tender-crm-integration">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Тендеры (интеграция CRM)</h2>
      <button
        @click="showCreateFromCRM = true"
        class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition"
      >
        Создать из CRM
      </button>
    </div>

    <!-- CRM Leads -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Лиды из CRM для тендеров</h3>
      
      <div v-if="loadingLeads" class="text-center py-4">
        <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-cyan-600"></div>
      </div>

      <div v-else-if="crmLeads.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
        <p class="text-gray-600">Нет лидов из CRM</p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="lead in crmLeads"
          :key="lead.id"
          class="flex justify-between items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition"
        >
          <div>
            <p class="font-medium text-gray-900">{{ lead.company_name }}</p>
            <p class="text-sm text-gray-600">{{ lead.requirement }}</p>
            <div class="flex gap-2 mt-2">
              <span class="px-2 py-1 bg-cyan-100 text-cyan-800 rounded text-xs">
                {{ lead.budget_range }}
              </span>
              <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                {{ lead.category }}
              </span>
            </div>
          </div>
          <button
            @click="createTenderFromLead(lead)"
            class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition text-sm"
          >
            Создать тендер
          </button>
        </div>
      </div>
    </div>

    <!-- Active Tenders with CRM Link -->
    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Активные тендеры (связанные с CRM)</h3>
      
      <div v-if="loadingTenders" class="text-center py-4">
        <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
      </div>

      <div v-else-if="linkedTenders.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
        <p class="text-gray-600">Нет связанных тендеров</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="tender in linkedTenders"
          :key="tender.id"
          class="border rounded-lg p-4 hover:shadow-md transition"
        >
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <span
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded',
                    getStatusClass(tender.status)
                  ]"
                >
                  {{ getStatusLabel(tender.status) }}
                </span>
                <span
                  v-if="tender.crm_lead_id"
                  class="px-2 py-1 text-xs bg-cyan-100 text-cyan-800 rounded flex items-center gap-1"
                >
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                  </svg>
                  CRM Lead #{{ tender.crm_lead_id }}
                </span>
              </div>
              
              <h4 class="font-semibold text-gray-900">{{ tender.title }}</h4>
              <p v-if="tender.description" class="text-sm text-gray-600 mt-1 line-clamp-2">
                {{ tender.description }}
              </p>
              
              <div class="flex gap-4 mt-3 text-sm text-gray-600">
                <span>Бюджет: {{ formatCurrency(tender.estimated_budget) }}</span>
                <span>Заявок: {{ tender.bids_count }}</span>
                <span v-if="tender.ends_at">
                  До: {{ formatDate(tender.ends_at) }}
                </span>
              </div>
            </div>

            <div class="flex gap-2">
              <button
                @click="viewTender(tender.id)"
                class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
              >
                Подробнее
              </button>
              <button
                @click="viewCRMLead(tender.crm_lead_id)"
                class="px-3 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition text-sm"
              >
                CRM
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create from CRM modal -->
    <TenderCreateFromCRM
      v-if="showCreateFromCRM"
      :crm-lead="selectedLead"
      @close="showCreateFromCRM = false"
      @created="onTenderCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface CRMLead {
  id: number
  company_name: string
  requirement: string
  budget_range: string
  category: string
  contact_person: string
  contact_email: string
  contact_phone: string
}

interface Tender {
  id: number
  title: string
  description: string | null
  status: string
  estimated_budget: number | null
  crm_lead_id: number | null
  bids_count: number
  ends_at: string | null
}

const crmLeads = ref<CRMLead[]>([])
const linkedTenders = ref<Tender[]>([])
const loadingLeads = ref(false)
const loadingTenders = ref(false)
const showCreateFromCRM = ref(false)
const selectedLead = ref<CRMLead | null>(null)

const formatCurrency = (amount: number | null) => {
  if (!amount) return 'Не указан'
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(amount)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    draft: 'Черновик',
    published: 'Опубликован',
    active: 'Активен',
    closed: 'Закрыт',
    awarded: 'Исполнен',
    cancelled: 'Отменен'
  }
  return labels[status] || status
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    published: 'bg-blue-100 text-blue-800',
    active: 'bg-green-100 text-green-800',
    closed: 'bg-yellow-100 text-yellow-800',
    awarded: 'bg-purple-100 text-purple-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const createTenderFromLead = (lead: CRMLead) => {
  selectedLead.value = lead
  showCreateFromCRM.value = true
}

const viewTender = (id: number) => {
  console.log('View tender:', id)
}

const viewCRMLead = (leadId: number | null) => {
  if (leadId) {
    console.log('View CRM lead:', leadId)
  }
}

const onTenderCreated = () => {
  showCreateFromCRM.value = false
  loadLinkedTenders()
}

const loadCRMLeads = async () => {
  loadingLeads.value = true
  try {
    // API call to load CRM leads
    // const response = await api.get('/crm/leads?for_tenders=true')
    // crmLeads.value = response.data
    
    // Mock data for now
    crmLeads.value = []
  } catch (error) {
    console.error('Error loading CRM leads:', error)
  } finally {
    loadingLeads.value = false
  }
}

const loadLinkedTenders = async () => {
  loadingTenders.value = true
  try {
    // API call to load linked tenders
    // const response = await api.get('/tenders?crm_linked=true')
    // linkedTenders.value = response.data
    
    // Mock data for now
    linkedTenders.value = []
  } catch (error) {
    console.error('Error loading linked tenders:', error)
  } finally {
    loadingTenders.value = false
  }
}

onMounted(() => {
  loadCRMLeads()
  loadLinkedTenders()
})
</script>
