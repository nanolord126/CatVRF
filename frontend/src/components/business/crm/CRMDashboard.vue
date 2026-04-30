<template>
  <div class="crm-dashboard">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-900">CRM Панель</h2>
      <div class="flex gap-2">
        <button
          @click="showCreateLead = true"
          class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition"
        >
          + Создать лида
        </button>
        <button
          @click="showCreateTender = true"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
        >
          + Создать тендер
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Всего контактов</div>
        <div class="text-2xl font-bold text-gray-900">{{ stats.totalContacts }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Активные лиды</div>
        <div class="text-2xl font-bold text-cyan-600">{{ stats.activeLeads }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Тендеры</div>
        <div class="text-2xl font-bold text-blue-600">{{ stats.tenders }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Конверсия</div>
        <div class="text-2xl font-bold text-green-600">{{ stats.conversion }}%</div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
      <div class="flex gap-2 border-b">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          @click="activeTab = tab.value"
          :class="[
            'px-4 py-2 border-b-2 transition',
            activeTab === tab.value
              ? 'border-cyan-600 text-cyan-600'
              : 'border-transparent text-gray-600 hover:text-gray-900'
          ]"
        >
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- Tab Content -->
    <div v-if="activeTab === 'leads'">
      <LeadList @create-tender="handleCreateTenderFromLead" />
    </div>

    <div v-if="activeTab === 'contacts'">
      <ContactList />
    </div>

    <div v-if="activeTab === 'tenders'">
      <TenderCRMIntegration />
    </div>

    <div v-if="activeTab === 'analytics'">
      <CRMAnalytics />
    </div>

    <!-- Create Lead Modal -->
    <LeadCreate
      v-if="showCreateLead"
      @close="showCreateLead = false"
      @created="onLeadCreated"
    />

    <!-- Create Tender Modal -->
    <TenderCreateFromCRM
      v-if="showCreateTender"
      @close="showCreateTender = false"
      @created="onTenderCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import LeadList from './LeadList.vue'
import ContactList from './ContactList.vue'
import TenderCRMIntegration from '../tender/TenderCRMIntegration.vue'
import CRMAnalytics from './CRMAnalytics.vue'
import LeadCreate from './LeadCreate.vue'
import TenderCreateFromCRM from '../tender/TenderCreateFromCRM.vue'

const activeTab = ref('leads')
const showCreateLead = ref(false)
const showCreateTender = ref(false)

const tabs = [
  { value: 'leads', label: 'Лиды' },
  { value: 'contacts', label: 'Контакты' },
  { value: 'tenders', label: 'Тендеры' },
  { value: 'analytics', label: 'Аналитика' }
]

const stats = ref({
  totalContacts: 0,
  activeLeads: 0,
  tenders: 0,
  conversion: 0
})

const handleCreateTenderFromLead = (lead: any) => {
  // Open tender creation modal with lead data
  console.log('Create tender from lead:', lead)
}

const onLeadCreated = () => {
  showCreateLead.value = false
  // Reload stats
}

const onTenderCreated = () => {
  showCreateTender.value = false
  // Reload stats
}

const loadStats = async () => {
  try {
    // API call to load stats
    // const response = await api.get('/crm/stats')
    // stats.value = response.data
  } catch (error) {
    console.error('Error loading stats:', error)
  }
}

onMounted(() => {
  loadStats()
})
</script>
