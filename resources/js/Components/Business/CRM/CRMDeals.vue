<template>
  <div class="crm-deals">
    <div class="header">
      <h2>Deals Pipeline</h2>
      <button @click="addDeal" class="btn-primary">Add Deal</button>
    </div>

    <div class="pipeline">
      <div v-for="stage in stages" :key="stage.name" class="pipeline-stage">
        <div class="stage-header">
          <h3>{{ stage.name }}</h3>
          <span class="count">{{ stage.deals.length }}</span>
        </div>
        <div class="stage-deals">
          <div v-for="deal in stage.deals" :key="deal.id" class="deal-card">
            <h4>{{ deal.title }}</h4>
            <p class="client">{{ deal.client }}</p>
            <p class="value">{{ formatCurrency(deal.value) }}</p>
            <div class="actions">
              <button @click="viewDeal(deal)" class="btn-sm">View</button>
              <button @click="editDeal(deal)" class="btn-sm">Edit</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Deal {
  id: number
  title: string
  client: string
  value: number
  stage: string
}

interface Stage {
  name: string
  deals: Deal[]
}

const stages = ref<Stage[]>([])

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const addDeal = () => {
  // Open modal to add new deal
}

const viewDeal = (deal: Deal) => {
  // Open deal details
}

const editDeal = (deal: Deal) => {
  // Open edit modal
}

const fetchDeals = async () => {
  try {
    const response = await fetch('/api/crm/deals')
    const data = await response.json()
    stages.value = data
  } catch (error) {
    console.error('Failed to fetch deals:', error)
  }
}

onMounted(() => {
  fetchDeals()
})
</script>

<style scoped>
.crm-deals {
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

.pipeline {
  display: flex;
  gap: 20px;
  overflow-x: auto;
  padding-bottom: 20px;
}

.pipeline-stage {
  min-width: 300px;
  background: #f9fafb;
  border-radius: 8px;
  padding: 16px;
}

.stage-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.stage-header h3 {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.count {
  background: #e5e7eb;
  color: #374151;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.stage-deals {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.deal-card {
  background: white;
  border-radius: 6px;
  padding: 12px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.deal-card h4 {
  margin: 0 0 8px 0;
  font-size: 14px;
  font-weight: 600;
}

.client {
  margin: 0 0 4px 0;
  font-size: 12px;
  color: #6b7280;
}

.value {
  margin: 0 0 8px 0;
  font-size: 14px;
  font-weight: 600;
  color: #059669;
}

.actions {
  display: flex;
  gap: 8px;
}

.btn-sm {
  flex: 1;
  padding: 6px 8px;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 11px;
}
</style>
