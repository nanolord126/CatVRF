<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

interface Milestone {
  id: number
  module_title: string
  amount_rub: number
  status: 'pending' | 'held' | 'paid'
}

const props = defineProps<{
  enrollmentId: number
}>()

const milestones = ref<Milestone[]>([])
const loading = ref(true)
const totalPaid = computed(() => 
  milestones.value.filter(m => m.status === 'paid').reduce((sum, m) => sum + m.amount_rub, 0)
)
const totalAmount = computed(() => 
  milestones.value.reduce((sum, m) => sum + m.amount_rub, 0)
)

const fetchMilestones = async () => {
  try {
    loading.value = true
    const response = await axios.get(`/api/v1/education/payments/milestones/${props.enrollmentId}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'X-Correlation-ID': crypto.randomUUID()
      }
    })
    milestones.value = response.data
  } catch (err: any) {
    console.error('Failed to load milestones:', err)
  } finally {
    loading.value = false
  }
}

const getStatusClass = (status: string) => {
  return {
    'status-pending': status === 'pending',
    'status-held': status === 'held',
    'status-paid': status === 'paid'
  }
}

onMounted(() => {
  fetchMilestones()
})
</script>

<template>
  <div class="course-progress">
    <h3>Payment Progress</h3>
    
    <div v-if="loading" class="loading">Loading payment milestones...</div>
    
    <div v-else class="progress-summary">
      <div class="summary-card">
        <span class="label">Total Amount</span>
        <span class="value">{{ (totalAmount / 100).toFixed(2) }} ₽</span>
      </div>
      <div class="summary-card">
        <span class="label">Paid</span>
        <span class="value paid">{{ (totalPaid / 100).toFixed(2) }} ₽</span>
      </div>
      <div class="summary-card">
        <span class="label">Remaining</span>
        <span class="value">{{ ((totalAmount - totalPaid) / 100).toFixed(2) }} ₽</span>
      </div>
    </div>

    <div class="milestones-timeline">
      <div v-for="(milestone, index) in milestones" :key="milestone.id" class="milestone-item">
        <div class="milestone-connector" v-if="index < milestones.length - 1"></div>
        <div class="milestone-dot" :class="getStatusClass(milestone.status)"></div>
        <div class="milestone-content">
          <h4>{{ milestone.module_title }}</h4>
          <p class="amount">{{ (milestone.amount_rub / 100).toFixed(2) }} ₽</p>
          <span class="status" :class="getStatusClass(milestone.status)">{{ milestone.status }}</span>
        </div>
      </div>
    </div>

    <div class="info-box">
      <p><strong>Payment Schedule:</strong> You pay as you progress through the course. Each module completion triggers a payment hold (4 hours) before final capture to the marketplace.</p>
    </div>
  </div>
</template>

<style scoped>
.course-progress {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
}

.progress-summary {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}

.summary-card {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 6px;
  text-align: center;
}

.summary-card .label {
  display: block;
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.summary-card .value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
}

.summary-card .value.paid {
  color: #10b981;
}

.milestones-timeline {
  position: relative;
  padding-left: 2rem;
}

.milestone-item {
  position: relative;
  padding-bottom: 2rem;
}

.milestone-connector {
  position: absolute;
  left: -1.5rem;
  top: 1rem;
  width: 2px;
  height: calc(100% + 1rem);
  background: #e5e7eb;
}

.milestone-dot {
  position: absolute;
  left: -1.75rem;
  top: 0.25rem;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #9ca3af;
}

.milestone-dot.status-pending {
  background: #9ca3af;
}

.milestone-dot.status-held {
  background: #f59e0b;
}

.milestone-dot.status-paid {
  background: #10b981;
}

.milestone-content {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 6px;
}

.milestone-content h4 {
  margin: 0 0 0.5rem 0;
  font-size: 1rem;
}

.milestone-content .amount {
  margin: 0;
  color: #6b7280;
  font-size: 0.875rem;
}

.milestone-content .status {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-top: 0.5rem;
}

.status.status-pending {
  background: #e5e7eb;
  color: #6b7280;
}

.status.status-held {
  background: #fef3c7;
  color: #92400e;
}

.status.status-paid {
  background: #d1fae5;
  color: #065f46;
}

.info-box {
  margin-top: 2rem;
  padding: 1rem;
  background: #eff6ff;
  border-left: 4px solid #3b82f6;
  border-radius: 4px;
}

.info-box p {
  margin: 0;
  font-size: 0.875rem;
  color: #1e40af;
}
</style>
