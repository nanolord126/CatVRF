<template>
  <div class="orders-section">
    <div class="section-header">
      <h2>{{ $t('crm.orders.title') }}</h2>
      <div class="header-actions">
        <select v-model="orderTypeFilter" class="filter-select">
          <option value="all">{{ $t('crm.orders.allTypes') }}</option>
          <option value="b2b">B2B</option>
          <option value="b2c">B2C</option>
        </select>
        <select v-model="statusFilter" class="filter-select">
          <option value="all">{{ $t('crm.orders.allStatuses') }}</option>
          <option value="pending">{{ $t('crm.orders.pending') }}</option>
          <option value="processing">{{ $t('crm.orders.processing') }}</option>
          <option value="completed">{{ $t('crm.orders.completed') }}</option>
          <option value="cancelled">{{ $t('crm.orders.cancelled') }}</option>
        </select>
        <button class="btn-primary" @click="createOrder">
          {{ $t('crm.orders.create') }}
        </button>
      </div>
    </div>

    <div class="orders-stats">
      <div class="stat-card">
        <div class="stat-value">{{ stats.total }}</div>
        <div class="stat-label">{{ $t('crm.orders.totalOrders') }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.pending }}</div>
        <div class="stat-label">{{ $t('crm.orders.pending') }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.completed }}</div>
        <div class="stat-label">{{ $t('crm.orders.completed') }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ formatCurrency(stats.revenue) }}</div>
        <div class="stat-label">{{ $t('crm.orders.revenue') }}</div>
      </div>
    </div>

    <div class="orders-table">
      <table>
        <thead>
          <tr>
            <th>{{ $t('crm.orders.id') }}</th>
            <th>{{ $t('crm.orders.customer') }}</th>
            <th>{{ $t('crm.orders.type') }}</th>
            <th>{{ $t('crm.orders.amount') }}</th>
            <th>{{ $t('crm.orders.status') }}</th>
            <th>{{ $t('crm.orders.date') }}</th>
            <th>{{ $t('crm.orders.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in filteredOrders" :key="order.id" class="order-row">
            <td>{{ order.id }}</td>
            <td>{{ order.customer_name }}</td>
            <td>
              <span
                class="order-type-badge"
                :class="order.order_type"
                :style="{ backgroundColor: getOrderColor(order.order_type) }"
              >
                {{ order.order_type.toUpperCase() }}
              </span>
            </td>
            <td>{{ formatCurrency(order.amount) }}</td>
            <td>
              <span class="status-badge" :class="order.status">
                {{ $t(`crm.orders.statuses.${order.status}`) }}
              </span>
            </td>
            <td>{{ formatDate(order.created_at) }}</td>
            <td>
              <button class="btn-icon" @click="viewOrder(order.id)">
                <Eye :size="16" />
              </button>
              <button class="btn-icon" @click="editOrder(order.id)">
                <Edit :size="16" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="pagination">
      <button
        class="btn-secondary"
        :disabled="currentPage === 1"
        @click="currentPage--"
      >
        {{ $t('common.previous') }}
      </button>
      <span class="page-info">{{ currentPage }} / {{ totalPages }}</span>
      <button
        class="btn-secondary"
        :disabled="currentPage === totalPages"
        @click="currentPage++"
      >
        {{ $t('common.next') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Eye, Edit } from 'lucide-vue-next';

const { t } = useI18n();

const props = defineProps<{
  tenantId: string;
  branchId?: string;
}>();

const orders = ref([]);
const stats = ref({
  total: 0,
  pending: 0,
  completed: 0,
  revenue: 0,
});
const orderTypeFilter = ref('all');
const statusFilter = ref('all');
const currentPage = ref(1);
const perPage = 20;

const filteredOrders = computed(() => {
  let filtered = orders.value;

  if (orderTypeFilter.value !== 'all') {
    filtered = filtered.filter(o => o.order_type === orderTypeFilter.value);
  }

  if (statusFilter.value !== 'all') {
    filtered = filtered.filter(o => o.status === statusFilter.value);
  }

  const start = (currentPage.value - 1) * perPage;
  return filtered.slice(start, start + perPage);
});

const totalPages = computed(() => {
  return Math.ceil(orders.value.length / perPage);
});

const loadOrders = async () => {
  try {
    const response = await fetch(
      `/api/crm/orders?tenant_id=${props.tenantId}&branch_id=${props.branchId || ''}`
    );
    const data = await response.json();
    orders.value = data.orders;
    stats.value = data.stats;
  } catch (error) {
    console.error('Failed to load orders:', error);
  }
};

const getOrderColor = (orderType: string) => {
  if (orderType === 'b2c') {
    return '#48BB78';
  }
  return '#4A5568';
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(amount);
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU');
};

const createOrder = () => {
  // Open order creation modal
};

const viewOrder = (orderId: number) => {
  // Open order details modal
};

const editOrder = (orderId: number) => {
  // Open order edit modal
};

watch([orderTypeFilter, statusFilter], () => {
  currentPage.value = 1;
});

onMounted(() => {
  loadOrders();
});
</script>

<style scoped>
.orders-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.section-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.header-actions {
  display: flex;
  gap: 0.5rem;
}

.filter-select {
  padding: 0.5rem 1rem;
  border: 1px solid var(--border-color);
  border-radius: 0.5rem;
  background: var(--bg-secondary);
  color: var(--text-primary);
}

.orders-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.stat-card {
  background: var(--bg-secondary);
  padding: 1.5rem;
  border-radius: 0.75rem;
  border: 1px solid var(--border-color);
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--primary-color);
}

.stat-label {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
}

.orders-table {
  background: var(--bg-secondary);
  border-radius: 0.75rem;
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.orders-table table {
  width: 100%;
  border-collapse: collapse;
}

.orders-table th,
.orders-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid var(--border-color);
}

.orders-table th {
  background: var(--bg-tertiary);
  font-weight: 600;
  color: var(--text-secondary);
}

.order-row:hover {
  background: var(--bg-tertiary);
}

.order-type-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  color: white;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge.pending {
  background: #FBBF24;
  color: #78350F;
}

.status-badge.processing {
  background: #60A5FA;
  color: #1E3A8A;
}

.status-badge.completed {
  background: #34D399;
  color: #065F46;
}

.status-badge.cancelled {
  background: #F87171;
  color: #7F1D1D;
}

.btn-icon {
  padding: 0.5rem;
  background: transparent;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  border-radius: 0.25rem;
}

.btn-icon:hover {
  background: var(--bg-tertiary);
  color: var(--text-primary);
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
}

.btn-primary,
.btn-secondary {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  border: none;
  cursor: pointer;
  font-weight: 500;
}

.btn-primary {
  background: var(--primary-color);
  color: white;
}

.btn-secondary {
  background: var(--bg-secondary);
  color: var(--text-primary);
  border: 1px solid var(--border-color);
}

.btn-secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
