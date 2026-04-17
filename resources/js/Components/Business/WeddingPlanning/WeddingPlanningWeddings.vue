<template>
  <div class="wedding-planning-weddings">
    <div class="header">
      <h2>Weddings</h2>
      <button @click="addWedding" class="btn-primary">Add Wedding</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="planning">Planning</option>
        <option value="confirmed">Confirmed</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
      </select>
      <select v-model="seasonFilter">
        <option value="">All Seasons</option>
        <option value="spring">Spring</option>
        <option value="summer">Summer</option>
        <option value="autumn">Autumn</option>
        <option value="winter">Winter</option>
      </select>
    </div>

    <div class="weddings-grid">
      <div v-for="wedding in filteredWeddings" :key="wedding.id" class="wedding-card">
        <div class="wedding-header">
          <span class="couple">{{ wedding.couple }}</span>
          <span :class="['status-badge', wedding.status]">{{ wedding.status }}</span>
        </div>
        <div class="wedding-details">
          <p class="venue">{{ wedding.venue }}</p>
          <div class="dates">
            <span>{{ formatDate(wedding.date) }}</span>
          </div>
          <div class="guests">{{ wedding.guests }} guests</div>
          <div class="budget">Budget: {{ formatCurrency(wedding.budget) }}</div>
          <div class="spent">Spent: {{ formatCurrency(wedding.spent) }}</div>
        </div>
        <div class="wedding-actions">
          <button @click="viewWedding(wedding)" class="btn-sm">View</button>
          <button @click="editWedding(wedding)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Wedding {
  id: number
  couple: string
  venue: string
  date: string
  guests: number
  budget: number
  spent: number
  status: string
}

const weddings = ref<Wedding[]>([])
const statusFilter = ref('')
const seasonFilter = ref('')

const filteredWeddings = computed(() => {
  return weddings.value.filter(wedding => {
    if (statusFilter.value && wedding.status !== statusFilter.value) return false
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

const addWedding = () => {
  // Open modal to add new wedding
}

const viewWedding = (wedding: Wedding) => {
  // Open wedding details
}

const editWedding = (wedding: Wedding) => {
  // Open edit modal
}

const fetchWeddings = async () => {
  try {
    const response = await fetch('/api/wedding-planning/weddings')
    const data = await response.json()
    weddings.value = data
  } catch (error) {
    console.error('Failed to fetch weddings:', error)
  }
}

onMounted(() => {
  fetchWeddings()
})
</script>

<style scoped>
.wedding-planning-weddings {
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

.weddings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.wedding-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.wedding-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.couple {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge.planning {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.confirmed {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.in_progress {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.completed {
  background: #e5e7eb;
  color: #374151;
}

.wedding-details {
  padding: 16px;
}

.venue {
  margin: 0 0 12px 0;
  font-size: 12px;
  color: #6b7280;
}

.dates {
  margin-bottom: 8px;
  font-size: 13px;
  color: #374151;
}

.guests {
  margin-bottom: 8px;
  font-size: 13px;
  color: #374151;
}

.budget {
  margin-bottom: 4px;
  font-size: 14px;
  font-weight: 600;
  color: #059669;
}

.spent {
  font-size: 13px;
  color: #6b7280;
}

.wedding-actions {
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
