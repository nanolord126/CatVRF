<template>
  <div class="content-campaigns">
    <div class="header">
      <h2>Content Campaigns</h2>
      <button @click="addCampaign" class="btn-primary">Add Campaign</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="draft">Draft</option>
        <option value="active">Active</option>
        <option value="paused">Paused</option>
        <option value="completed">Completed</option>
      </select>
      <select v-model="typeFilter">
        <option value="">All Types</option>
        <option value="social_media">Social Media</option>
        <option value="email">Email</option>
        <option value="blog">Blog</option>
        <option value="video">Video</option>
      </select>
    </div>

    <div class="campaigns-grid">
      <div v-for="campaign in filteredCampaigns" :key="campaign.id" class="campaign-card">
        <div class="campaign-header">
          <span class="campaign-name">{{ campaign.name }}</span>
          <span :class="['status-badge', campaign.status]">{{ campaign.status }}</span>
        </div>
        <div class="campaign-details">
          <p class="type">{{ campaign.type }}</p>
          <div class="dates">
            <span>{{ formatDate(campaign.start_date) }}</span>
            <span>→</span>
            <span>{{ formatDate(campaign.end_date) }}</span>
          </div>
          <div class="budget">Budget: {{ formatCurrency(campaign.budget) }}</div>
          <div class="metrics">
            <span>{{ campaign.impressions }} impressions</span>
            <span>{{ campaign.clicks }} clicks</span>
          </div>
        </div>
        <div class="campaign-actions">
          <button @click="viewCampaign(campaign)" class="btn-sm">View</button>
          <button @click="editCampaign(campaign)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Campaign {
  id: number
  name: string
  type: string
  status: string
  start_date: string
  end_date: string
  budget: number
  impressions: number
  clicks: number
}

const campaigns = ref<Campaign[]>([])
const statusFilter = ref('')
const typeFilter = ref('')

const filteredCampaigns = computed(() => {
  return campaigns.value.filter(campaign => {
    if (statusFilter.value && campaign.status !== statusFilter.value) return false
    if (typeFilter.value && campaign.type !== typeFilter.value) return false
    return true
  })
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const addCampaign = () => {
  // Open modal to add new campaign
}

const viewCampaign = (campaign: Campaign) => {
  // Open campaign details
}

const editCampaign = (campaign: Campaign) => {
  // Open edit modal
}

const fetchCampaigns = async () => {
  try {
    const response = await fetch('/api/content/campaigns')
    const data = await response.json()
    campaigns.value = data
  } catch (error) {
    console.error('Failed to fetch campaigns:', error)
  }
}

onMounted(() => {
  fetchCampaigns()
})
</script>

<style scoped>
.content-campaigns {
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

.campaigns-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.campaign-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.campaign-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.campaign-name {
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

.status-badge.draft {
  background: #e5e7eb;
  color: #374151;
}

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.paused {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.completed {
  background: #dbeafe;
  color: #1e40af;
}

.campaign-details {
  padding: 16px;
}

.type {
  margin: 0 0 12px 0;
  font-size: 12px;
  color: #6b7280;
}

.dates {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 13px;
  color: #374151;
}

.budget {
  margin-bottom: 8px;
  font-size: 13px;
  color: #374151;
}

.metrics {
  display: flex;
  gap: 12px;
  font-size: 12px;
  color: #6b7280;
}

.campaign-actions {
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
