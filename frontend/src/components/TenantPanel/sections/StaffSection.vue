<template>
  <div class="staff-section">
    <div class="section-header">
      <h2>{{ $t('crm.staff.title') }}</h2>
      <div class="header-actions">
        <button class="btn-primary" @click="addStaff">
          {{ $t('crm.staff.add') }}
        </button>
      </div>
    </div>

    <div class="staff-stats">
      <div class="stat-card">
        <div class="stat-value">{{ stats.total }}</div>
        <div class="stat-label">{{ $t('crm.staff.totalStaff') }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.active }}</div>
        <div class="stat-label">{{ $t('crm.staff.active') }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.onShift }}</div>
        <div class="stat-label">{{ $t('crm.staff.onShift') }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ formatCurrency(stats.payroll) }}</div>
        <div class="stat-label">{{ $t('crm.staff.monthlyPayroll') }}</div>
      </div>
    </div>

    <div class="staff-table">
      <table>
        <thead>
          <tr>
            <th>{{ $t('crm.staff.name') }}</th>
            <th>{{ $t('crm.staff.position') }}</th>
            <th>{{ $t('crm.staff.department') }}</th>
            <th>{{ $t('crm.staff.status') }}</th>
            <th>{{ $t('crm.staff.shift') }}</th>
            <th>{{ $t('crm.staff.rating') }}</th>
            <th>{{ $t('crm.staff.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="staff in staffList" :key="staff.id" class="staff-row">
            <td>
              <div class="staff-name">
                <img :src="staff.avatar" class="staff-avatar" />
                <span>{{ staff.name }}</span>
              </div>
            </td>
            <td>{{ staff.position }}</td>
            <td>{{ staff.department }}</td>
            <td>
              <span class="status-badge" :class="staff.status">
                {{ $t(`crm.staff.statuses.${staff.status}`) }}
              </span>
            </td>
            <td>{{ staff.shift || '-' }}</td>
            <td>{{ staff.rating || '-' }}</td>
            <td>
              <button class="btn-icon" @click="viewStaff(staff.id)">
                <Eye :size="16" />
              </button>
              <button class="btn-icon" @click="editStaff(staff.id)">
                <Edit :size="16" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { Eye, Edit } from 'lucide-vue-next';

const { t } = useI18n();

const props = defineProps<{
  tenantId: string;
  branchId?: string;
}>();

const staffList = ref([]);
const stats = ref({
  total: 0,
  active: 0,
  onShift: 0,
  payroll: 0,
});

const loadStaff = async () => {
  try {
    const response = await fetch(
      `/api/crm/staff?tenant_id=${props.tenantId}&branch_id=${props.branchId || ''}`
    );
    const data = await response.json();
    staffList.value = data.staff;
    stats.value = data.stats;
  } catch (error) {
    console.error('Failed to load staff:', error);
  }
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(amount);
};

const addStaff = () => {};
const viewStaff = (id: number) => {};
const editStaff = (id: number) => {};

onMounted(() => {
  loadStaff();
});
</script>

<style scoped>
.staff-section {
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

.staff-stats {
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

.staff-table {
  background: var(--bg-secondary);
  border-radius: 0.75rem;
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.staff-table table {
  width: 100%;
  border-collapse: collapse;
}

.staff-table th,
.staff-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid var(--border-color);
}

.staff-table th {
  background: var(--bg-tertiary);
  font-weight: 600;
  color: var(--text-secondary);
}

.staff-row:hover {
  background: var(--bg-tertiary);
}

.staff-name {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.staff-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge.active {
  background: #34D399;
  color: #065F46;
}

.status-badge.inactive {
  background: #F87171;
  color: #7F1D1D;
}

.status-badge.on_shift {
  background: #60A5FA;
  color: #1E3A8A;
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

.btn-primary {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  border: none;
  cursor: pointer;
  font-weight: 500;
  background: var(--primary-color);
  color: white;
}
</style>
