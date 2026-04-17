<template>
  <div class="legal-consultations">
    <div class="header">
      <h2>Consultations</h2>
      <button @click="addConsultation" class="btn-primary">Add Consultation</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="scheduled">Scheduled</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <select v-model="priorityFilter">
        <option value="">All Priorities</option>
        <option value="low">Low</option>
        <option value="medium">Medium</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
      </select>
    </div>

    <div class="consultations-grid">
      <div v-for="consultation in filteredConsultations" :key="consultation.id" class="consultation-card">
        <div class="consultation-header">
          <span class="client">{{ consultation.client }}</span>
          <span :class="['priority-badge', consultation.priority]">{{ consultation.priority }}</span>
        </div>
        <div class="consultation-details">
          <h3>{{ consultation.topic }}</h3>
          <p class="lawyer">Lawyer: {{ consultation.lawyer }}</p>
          <div class="datetime">
            <span>{{ formatDate(consultation.date) }}</span>
            <span>{{ consultation.time }}</span>
          </div>
          <div class="duration">{{ consultation.duration }} min</div>
          <div class="price">{{ formatCurrency(consultation.fee) }}</div>
        </div>
        <div class="consultation-actions">
          <button @click="viewConsultation(consultation)" class="btn-sm">View</button>
          <button @click="editConsultation(consultation)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Consultation {
  id: number
  client: string
  topic: string
  lawyer: string
  date: string
  time: string
  duration: number
  fee: number
  priority: string
  status: string
}

const consultations = ref<Consultation[]>([])
const statusFilter = ref('')
const priorityFilter = ref('')

const filteredConsultations = computed(() => {
  return consultations.value.filter(consultation => {
    if (statusFilter.value && consultation.status !== statusFilter.value) return false
    if (priorityFilter.value && consultation.priority !== priorityFilter.value) return false
    return true
  })
})

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const addConsultation = () => {
  // Open modal to add new consultation
}

const viewConsultation = (consultation: Consultation) => {
  // Open consultation details
}

const editConsultation = (consultation: Consultation) => {
  // Open edit modal
}

const fetchConsultations = async () => {
  try {
    const response = await fetch('/api/legal/consultations')
    const data = await response.json()
    consultations.value = data
  } catch (error) {
    console.error('Failed to fetch consultations:', error)
  }
}

onMounted(() => {
  fetchConsultations()
})
</script>

<style scoped>
.legal-consultations {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.filters select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.consultations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.consultation-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.consultation-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.client {
  font-size: 12px;
  font-weight: 600;
  color: #374151;
}

.priority-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}

.priority-badge.low {
  background: #d1fae5;
  color: #065f46;
}

.priority-badge.medium {
  background: #dbeafe;
  color: #1e40af;
}

.priority-badge.high {
  background: #fef3c7;
  color: #92400e;
}

.priority-badge.urgent {
  background: #fee2e2;
  color: #991b1b;
}

.consultation-details {
  padding: 16px;
}

.consultation-details h3 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.lawyer {
  margin: 0 0 12px 0;
  font-size: 12px;
  color: #6b7280;
}

.datetime {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 13px;
  color: #374151;
}

.duration {
  margin-bottom: 8px;
  font-size: 13px;
  color: #6b7280;
}

.price {
  font-size: 16px;
  font-weight: 600;
  color: #059669;
}

.consultation-actions {
  padding: 12px 16px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 8px;
}

.btn-sm {
  flex: 1;
  padding: 8px;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}
</style>
