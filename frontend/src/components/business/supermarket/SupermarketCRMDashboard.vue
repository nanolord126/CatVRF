<template>
  <div class="supermarket-crm-dashboard">
    <div class="dashboard-header">
      <h2 class="dashboard-title">CRM Супермаркета</h2>
      <div class="header-controls">
        <select v-model="selectedSegment" class="segment-select">
          <option value="all">Все сегменты</option>
          <option value="champions">Чемпионы</option>
          <option value="loyal">Лояльные</option>
          <option value="potential">Потенциальные</option>
          <option value="at_risk">В зоне риска</option>
        </select>
        <button @click="createCampaign" class="campaign-btn">
          <Mail class="btn-icon" />
          Кампания
        </button>
      </div>
    </div>

    <!-- Customer Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon total">
          <Users class="icon" />
        </div>
        <div class="stat-content">
          <span class="stat-value">{{ formatNumber(stats.totalCustomers) }}</span>
          <span class="stat-label">Всего клиентов</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon active">
          <Activity class="icon" />
        </div>
        <div class="stat-content">
          <span class="stat-value">{{ formatNumber(stats.activeCustomers) }}</span>
          <span class="stat-label">Активных (30 дней)</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon churn">
          <TrendingDown class="icon" />
        </div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.churnRate }}%</span>
          <span class="stat-label">Отток</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon ltv">
          <DollarSign class="icon" />
        </div>
        <div class="stat-content">
          <span class="stat-value">{{ formatCurrency(stats.avgLTV) }}</span>
          <span class="stat-label">Средний LTV</span>
        </div>
      </div>
    </div>

    <!-- Segments Overview -->
    <div class="section-card">
      <div class="section-header">
        <h3>Сегменты клиентов</h3>
      </div>
      <div class="segments-grid">
        <div 
          v-for="segment in segments" 
          :key="segment.slug"
          class="segment-card"
          :class="{ active: selectedSegment === segment.slug }"
          @click="selectSegment(segment.slug)"
        >
          <div class="segment-header">
            <span class="segment-name">{{ segment.name }}</span>
            <span class="segment-percentage">{{ segment.percentage }}%</span>
          </div>
          <div class="segment-count">{{ formatNumber(segment.count }} клиентов</div>
          <div class="segment-metrics">
            <div class="segment-metric">
              <span class="metric-label">LTV</span>
              <span class="metric-value">{{ formatCurrency(segment.ltv) }}</span>
            </div>
            <div class="segment-metric">
              <span class="metric-label">Частота</span>
              <span class="metric-value">{{ segment.frequency }}/мес</span>
            </div>
            <div class="segment-metric">
              <span class="metric-label">Отток</span>
              <span class="metric-value">{{ segment.churn }}%</span>
            </div>
          </div>
          <div class="segment-actions">
            <button @click.stop="viewSegment(segment.slug)" class="action-btn">
              Подробнее
            </button>
            <button @click.stop="targetSegment(segment.slug)" class="action-btn secondary">
              Кампанию
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Customer List -->
    <div class="section-card">
      <div class="section-header">
        <h3>Клиенты</h3>
        <div class="header-actions">
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Поиск по имени, email, телефону..."
            class="search-input"
          >
          <button @click="exportCustomers" class="export-btn">
            <Download class="btn-icon" />
            Экспорт
          </button>
        </div>
      </div>
      <div class="customer-table-container">
        <table class="customer-table">
          <thead>
            <tr>
              <th>Клиент</th>
              <th>Сегмент</th>
              <th>Tier</th>
              <th>LTV</th>
              <th>Заказы</th>
              <th>Последний заказ</th>
              <th>Вероятность оттока</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="customer in filteredCustomers" :key="customer.id">
              <td>
                <div class="customer-info">
                  <div class="customer-name">{{ customer.name }}</div>
                  <div class="customer-email">{{ customer.email }}</div>
                </div>
              </td>
              <td>
                <span :class="['segment-badge', customer.segment]">
                  {{ getSegmentLabel(customer.segment) }}
                </span>
              </td>
              <td>
                <span :class="['tier-badge', customer.tier]">
                  {{ customer.tier }}
                </span>
              </td>
              <td>{{ formatCurrency(customer.ltv) }}</td>
              <td>{{ customer.orders }}</td>
              <td>{{ formatDate(customer.lastOrder) }}</td>
              <td>
                <div class="churn-indicator">
                  <div class="churn-bar">
                    <div 
                      class="churn-fill"
                      :style="{ width: (customer.churnProbability * 100) + '%' }"
                      :class="getChurnClass(customer.churnProbability)"
                    ></div>
                  </div>
                  <span :class="['churn-value', getChurnClass(customer.churnProbability)]">
                    {{ Math.round(customer.churnProbability * 100) }}%
                  </span>
                </div>
              </td>
              <td>
                <div class="table-actions">
                  <button @click="viewCustomer(customer.id)" class="table-action-btn">
                    <Eye class="action-icon" />
                  </button>
                  <button @click="sendMessage(customer.id)" class="table-action-btn">
                    <MessageSquare class="action-icon" />
                  </button>
                  <button @click="addToCampaign(customer.id)" class="table-action-btn">
                    <MailPlus class="action-icon" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="pagination">
        <button :disabled="currentPage === 1" @click="prevPage" class="page-btn">
          Назад
        </button>
        <span class="page-info">Страница {{ currentPage }} из {{ totalPages }}</span>
        <button :disabled="currentPage === totalPages" @click="nextPage" class="page-btn">
          Вперёд
        </button>
      </div>
    </div>

    <!-- Campaigns -->
    <div class="section-card">
      <div class="section-header">
        <h3>Активные кампании</h3>
        <button @click="createCampaign" class="create-campaign-btn">
          <Plus class="btn-icon" />
          Создать кампанию
        </button>
      </div>
      <div class="campaigns-list">
        <div v-for="campaign in campaigns" :key="campaign.id" class="campaign-item">
          <div class="campaign-info">
            <span class="campaign-name">{{ campaign.name }}</span>
            <span class="campaign-type">{{ campaign.type }}</span>
          </div>
          <div class="campaign-stats">
            <div class="campaign-stat">
              <span class="stat-label">Отправлено</span>
              <span class="stat-value">{{ campaign.sent }}</span>
            </div>
            <div class="campaign-stat">
              <span class="stat-label">Открыто</span>
              <span class="stat-value">{{ campaign.opened }}</span>
            </div>
            <div class="campaign-stat">
              <span class="stat-label">CTR</span>
              <span class="stat-value">{{ campaign.ctr }}%</span>
            </div>
          </div>
          <div class="campaign-status" :class="campaign.status">
            {{ getCampaignStatusLabel(campaign.status) }}
          </div>
          <div class="campaign-actions">
            <button @click="viewCampaign(campaign.id)" class="action-btn">
              Подробнее
            </button>
            <button @click="pauseCampaign(campaign.id)" class="action-btn secondary">
              Пауза
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { 
  Users, 
  Activity, 
  TrendingDown, 
  DollarSign, 
  Mail, 
  Download, 
  Eye, 
  MessageSquare, 
  MailPlus, 
  Plus 
} from 'lucide-vue-next'

interface Customer {
  id: string
  name: string
  email: string
  segment: string
  tier: string
  ltv: number
  orders: number
  lastOrder: string
  churnProbability: number
}

interface Campaign {
  id: string
  name: string
  type: string
  sent: number
  opened: number
  ctr: number
  status: 'active' | 'paused' | 'completed'
}

const selectedSegment = ref('all')
const searchQuery = ref('')
const currentPage = ref(1)
const totalPages = ref(10)

const stats = ref({
  totalCustomers: 4500,
  activeCustomers: 2800,
  churnRate: 8.5,
  avgLTV: 18500,
})

const segments = ref([
  { slug: 'champions', name: 'Чемпионы', count: 450, percentage: 10, ltv: 45000, frequency: 8, churn: 2 },
  { slug: 'loyal', name: 'Лояльные', count: 1125, percentage: 25, ltv: 25000, frequency: 5, churn: 5 },
  { slug: 'potential', name: 'Потенциальные', count: 900, percentage: 20, ltv: 15000, frequency: 3, churn: 8 },
  { slug: 'new', name: 'Новые', count: 1350, percentage: 30, ltv: 5000, frequency: 1, churn: 15 },
  { slug: 'at_risk', name: 'В зоне риска', count: 450, percentage: 10, ltv: 18000, frequency: 4, churn: 25 },
  { slug: 'hibernating', name: 'Спящие', count: 180, percentage: 4, ltv: 12000, frequency: 2, churn: 40 },
  { slug: 'lost', name: 'Потерянные', count: 45, percentage: 1, ltv: 8000, frequency: 1, churn: 60 },
])

const customers = ref<Customer[]>([
  {
    id: '1',
    name: 'Алексей Петров',
    email: 'alex@example.com',
    segment: 'champions',
    tier: 'platinum',
    ltv: 52000,
    orders: 24,
    lastOrder: '2026-04-26',
    churnProbability: 0.05,
  },
  {
    id: '2',
    name: 'Мария Иванова',
    email: 'maria@example.com',
    segment: 'loyal',
    tier: 'gold',
    ltv: 28000,
    orders: 12,
    lastOrder: '2026-04-25',
    churnProbability: 0.12,
  },
  {
    id: '3',
    name: 'Дмитрий Сидоров',
    email: 'dmitry@example.com',
    segment: 'at_risk',
    tier: 'silver',
    ltv: 18500,
    orders: 6,
    lastOrder: '2026-04-10',
    churnProbability: 0.45,
  },
])

const campaigns = ref<Campaign[]>([
  {
    id: '1',
    name: 'Возвращаем спящих',
    type: 'email',
    sent: 250,
    opened: 125,
    ctr: 50,
    status: 'active',
  },
  {
    id: '2',
    name: 'Персональные рекомендации',
    type: 'push',
    sent: 500,
    opened: 300,
    ctr: 60,
    status: 'active',
  },
])

const filteredCustomers = computed(() => {
  let result = customers.value

  if (selectedSegment.value !== 'all') {
    result = result.filter(c => c.segment === selectedSegment.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(c => 
      c.name.toLowerCase().includes(query) || 
      c.email.toLowerCase().includes(query)
    )
  }

  return result
})

const formatNumber = (value: number): string => {
  return new Intl.NumberFormat('ru-RU').format(value)
}

const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
  }).format(value)
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const getSegmentLabel = (segment: string): string => {
  const labels: Record<string, string> = {
    champions: 'Чемпионы',
    loyal: 'Лояльные',
    potential: 'Потенциальные',
    new: 'Новые',
    at_risk: 'В зоне риска',
    hibernating: 'Спящие',
    lost: 'Потерянные',
  }
  return labels[segment] || segment
}

const getChurnClass = (probability: number): string => {
  if (probability >= 0.5) return 'high'
  if (probability >= 0.3) return 'medium'
  return 'low'
}

const getCampaignStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    active: 'Активна',
    paused: 'На паузе',
    completed: 'Завершена',
  }
  return labels[status] || status
}

const selectSegment = (segment: string) => {
  selectedSegment.value = segment
}

const viewSegment = (segment: string) => {
  // TODO: View segment details
}

const targetSegment = (segment: string) => {
  // TODO: Create campaign for segment
}

const viewCustomer = (id: string) => {
  // TODO: View customer details
}

const sendMessage = (id: string) => {
  // TODO: Send message
}

const addToCampaign = (id: string) => {
  // TODO: Add to campaign
}

const exportCustomers = () => {
  // TODO: Export
}

const createCampaign = () => {
  // TODO: Create campaign
}

const viewCampaign = (id: string) => {
  // TODO: View campaign
}

const pauseCampaign = (id: string) => {
  // TODO: Pause campaign
}

const prevPage = () => {
  if (currentPage.value > 1) currentPage.value--
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value++
}
</script>

<style scoped>
.supermarket-crm-dashboard {
  padding: 1.5rem;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.dashboard-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.header-controls {
  display: flex;
  gap: 1rem;
}

.segment-select {
  padding: 0.5rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  background: white;
  cursor: pointer;
}

.campaign-btn,
.create-campaign-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.campaign-btn:hover,
.create-campaign-btn:hover {
  background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(34, 197, 94, 0.3);
}

.btn-icon {
  width: 1rem;
  height: 1rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  display: flex;
  gap: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-icon.total {
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
}

.stat-icon.total .icon {
  color: #16a34a;
}

.stat-icon.active {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}

.stat-icon.active .icon {
  color: #2563eb;
}

.stat-icon.churn {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
}

.stat-icon.churn .icon {
  color: #dc2626;
}

.stat-icon.ltv {
  background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
}

.stat-icon.ltv .icon {
  color: #4f46e5;
}

.stat-icon .icon {
  width: 1.5rem;
  height: 1.5rem;
}

.stat-content {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
}

.section-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.search-input {
  padding: 0.5rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  width: 300px;
}

.export-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
}

.export-btn:hover {
  background: #e5e7eb;
}

.segments-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.segment-card {
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.75rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.segment-card:hover {
  border-color: #22c55e;
  transform: translateY(-2px);
}

.segment-card.active {
  border-color: #22c55e;
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
}

.segment-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.segment-name {
  font-weight: 600;
  color: #111827;
}

.segment-percentage {
  font-size: 0.875rem;
  color: #6b7280;
}

.segment-count {
  font-size: 1.25rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 0.75rem;
}

.segment-metrics {
  display: flex;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.segment-metric {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.metric-label {
  font-size: 0.75rem;
  color: #6b7280;
}

.metric-value {
  font-size: 0.875rem;
  font-weight: 600;
  color: #111827;
}

.segment-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  flex: 1;
  padding: 0.375rem 0.75rem;
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  color: white;
  border: none;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
}

.action-btn.secondary {
  background: #f3f4f6;
  color: #374151;
}

.customer-table-container {
  overflow-x: auto;
}

.customer-table {
  width: 100%;
  border-collapse: collapse;
}

.customer-table th {
  text-align: left;
  padding: 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  border-bottom: 1px solid #e5e7eb;
}

.customer-table td {
  padding: 0.75rem;
  font-size: 0.875rem;
  border-bottom: 1px solid #f3f4f6;
}

.customer-name {
  font-weight: 600;
  color: #111827;
}

.customer-email {
  font-size: 0.75rem;
  color: #6b7280;
}

.segment-badge,
.tier-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.segment-badge.champions {
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
  color: #166534;
}

.segment-badge.loyal {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  color: #1e40af;
}

.segment-badge.at_risk {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #991b1b;
}

.tier-badge.platinum {
  background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
  color: #3730a3;
}

.tier-badge.gold {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  color: #92400e;
}

.tier-badge.silver {
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  color: #374151;
}

.churn-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.churn-bar {
  width: 50px;
  height: 0.375rem;
  background: #e5e7eb;
  border-radius: 9999px;
  overflow: hidden;
}

.churn-fill {
  height: 100%;
  border-radius: 9999px;
  transition: width 0.3s ease;
}

.churn-fill.low {
  background: #22c55e;
}

.churn-fill.medium {
  background: #f59e0b;
}

.churn-fill.high {
  background: #ef4444;
}

.churn-value {
  font-size: 0.75rem;
  font-weight: 600;
}

.churn-value.low {
  color: #16a34a;
}

.churn-value.medium {
  color: #d97706;
}

.churn-value.high {
  color: #dc2626;
}

.table-actions {
  display: flex;
  gap: 0.25rem;
}

.table-action-btn {
  padding: 0.375rem;
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
}

.table-action-btn:hover {
  background: #e5e7eb;
}

.action-icon {
  width: 1rem;
  height: 1rem;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 1rem;
}

.page-btn {
  padding: 0.5rem 1rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  cursor: pointer;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  font-size: 0.875rem;
  color: #6b7280;
}

.campaigns-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.campaign-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.campaign-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.campaign-name {
  font-weight: 600;
  color: #111827;
}

.campaign-type {
  font-size: 0.75rem;
  color: #6b7280;
}

.campaign-stats {
  display: flex;
  gap: 1.5rem;
}

.campaign-stat {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.campaign-stat .stat-label {
  font-size: 0.75rem;
  color: #6b7280;
}

.campaign-stat .stat-value {
  font-size: 0.875rem;
  font-weight: 600;
  color: #111827;
}

.campaign-status {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.campaign-status.active {
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
  color: #166534;
}

.campaign-status.paused {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  color: #92400e;
}

.campaign-status.completed {
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  color: #374151;
}

.campaign-actions {
  display: flex;
  gap: 0.5rem;
}
</style>
