<template>
  <div class="analytics-reports">
    <div class="header">
      <h2>Analytics Reports</h2>
      <button @click="createReport" class="btn-primary">Create Report</button>
    </div>

    <div class="filters">
      <select v-model="categoryFilter">
        <option value="">All Categories</option>
        <option value="sales">Sales</option>
        <option value="traffic">Traffic</option>
        <option value="conversion">Conversion</option>
        <option value="retention">Retention</option>
      </select>
      <select v-model="periodFilter">
        <option value="">All Periods</option>
        <option value="daily">Daily</option>
        <option value="weekly">Weekly</option>
        <option value="monthly">Monthly</option>
      </select>
    </div>

    <div class="reports-grid">
      <div v-for="report in filteredReports" :key="report.id" class="report-card">
        <div class="report-header">
          <span class="report-name">{{ report.name }}</span>
          <span class="report-category">{{ report.category }}</span>
        </div>
        <div class="report-details">
          <p class="description">{{ report.description }}</p>
          <div class="stats">
            <span>{{ report.period }}</span>
            <span>{{ formatDate(report.created_at) }}</span>
          </div>
          <div class="metrics">
            <span v-for="metric in report.metrics" :key="metric" class="metric-tag">{{ metric }}</span>
          </div>
        </div>
        <div class="report-actions">
          <button @click="viewReport(report)" class="btn-sm">View</button>
          <button @click="downloadReport(report)" class="btn-sm btn-primary">Download</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Report {
  id: number
  name: string
  category: string
  description: string
  period: string
  created_at: string
  metrics: string[]
}

const reports = ref<Report[]>([])
const categoryFilter = ref('')
const periodFilter = ref('')

const filteredReports = computed(() => {
  return reports.value.filter(report => {
    if (categoryFilter.value && report.category !== categoryFilter.value) return false
    if (periodFilter.value && report.period.toLowerCase() !== periodFilter.value) return false
    return true
  })
})

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const createReport = () => {
  // Open modal to create new report
}

const viewReport = (report: Report) => {
  // Open report details
}

const downloadReport = (report: Report) => {
  // Download report file
}

const fetchReports = async () => {
  try {
    const response = await fetch('/api/analytics/reports')
    const data = await response.json()
    reports.value = data
  } catch (error) {
    console.error('Failed to fetch reports:', error)
  }
}

onMounted(() => {
  fetchReports()
})
</script>

<style scoped>
.analytics-reports {
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

.reports-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.report-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.report-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.report-name {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.report-category {
  font-size: 12px;
  color: #6b7280;
  text-transform: uppercase;
}

.report-details {
  padding: 16px;
}

.description {
  margin: 0 0 12px 0;
  font-size: 13px;
  color: #6b7280;
  line-height: 1.5;
}

.stats {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
  font-size: 12px;
  color: #374151;
}

.metrics {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 12px;
}

.metric-tag {
  background: #dbeafe;
  color: #1e40af;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
}

.report-actions {
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

.btn-primary {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}
</style>
