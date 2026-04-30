<template>
  <div class="warehouse-section">
    <div class="section-header">
      <h2>{{ $t('crm.warehouse.title') }}</h2>
      <div class="header-actions">
        <select v-model="orderTypeFilter" class="filter-select">
          <option value="all">{{ $t('crm.warehouse.allTypes') }}</option>
          <option value="b2b">B2B</option>
          <option value="b2c">B2C</option>
          <option value="shared">{{ $t('crm.warehouse.shared') }}</option>
        </select>
        <button class="btn-primary" @click="createMovement">
          {{ $t('crm.warehouse.createMovement') }}
        </button>
      </div>
    </div>

    <div class="warehouse-stats">
      <div class="stat-card">
        <div class="stat-value">{{ stats.totalItems }}</div>
        <div class="stat-label">{{ $t('crm.warehouse.totalItems') }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.b2bItems }}</div>
        <div class="stat-label">B2B {{ $t('crm.warehouse.items') }}</div>
        <div class="stat-color" style="background: #4A5568;"></div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.b2cItems }}</div>
        <div class="stat-label">B2C {{ $t('crm.warehouse.items') }}</div>
        <div class="stat-color" style="background: #48BB78;"></div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.sharedItems }}</div>
        <div class="stat-label">{{ $t('crm.warehouse.shared') }}</div>
        <div class="stat-color" style="background: #A0AEC0;"></div>
      </div>
    </div>

    <div class="color-legend">
      <div class="legend-item">
        <div class="legend-color" style="background: #4A5568;"></div>
        <span>B2B ({{ $t('crm.warehouse.baseColor') }})</span>
      </div>
      <div class="legend-item">
        <div class="legend-color" style="background: #48BB78;"></div>
        <span>B2C ({{ $t('crm.warehouse.brighterBy17') }})</span>
      </div>
      <div class="legend-item">
        <div class="legend-color" style="background: #A0AEC0;"></div>
        <span>{{ $t('crm.warehouse.shared') }}</span>
      </div>
    </div>

    <div class="inventory-table">
      <table>
        <thead>
          <tr>
            <th>{{ $t('crm.warehouse.sku') }}</th>
            <th>{{ $t('crm.warehouse.product') }}</th>
            <th>{{ $t('crm.warehouse.type') }}</th>
            <th>{{ $t('crm.warehouse.quantity') }}</th>
            <th>{{ $t('crm.warehouse.reserved') }}</th>
            <th>{{ $t('crm.warehouse.available') }}</th>
            <th>{{ $t('crm.warehouse.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in filteredItems" :key="item.id" class="inventory-row">
            <td>{{ item.product_sku }}</td>
            <td>{{ item.product_name }}</td>
            <td>
              <span
                class="type-badge"
                :style="{ backgroundColor: getItemColor(item.order_type) }"
              >
                {{ item.order_type ? item.order_type.toUpperCase() : 'SHARED' }}
              </span>
            </td>
            <td>{{ item.quantity }}</td>
            <td>{{ item.reserved_quantity }}</td>
            <td>{{ item.available_quantity }}</td>
            <td>
              <button class="btn-icon" @click="convertItem(item.id)">
                <RefreshCw :size="16" />
              </button>
              <button class="btn-icon" @click="viewItem(item.id)">
                <Eye :size="16" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <ConversionModal
      v-if="showConversionModal"
      :item-id="selectedItemId"
      @close="showConversionModal = false"
      @converted="loadInventory"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { Eye, RefreshCw } from 'lucide-vue-next';
import ConversionModal from './ConversionModal.vue';

const { t } = useI18n();

const props = defineProps<{
  tenantId: string;
  branchId?: string;
}>();

const inventory = ref([]);
const stats = ref({
  totalItems: 0,
  b2bItems: 0,
  b2cItems: 0,
  sharedItems: 0,
});
const orderTypeFilter = ref('all');
const showConversionModal = ref(false);
const selectedItemId = ref(null);

const filteredItems = computed(() => {
  if (orderTypeFilter.value === 'all') {
    return inventory.value;
  }
  if (orderTypeFilter.value === 'shared') {
    return inventory.value.filter(i => !i.order_type);
  }
  return inventory.value.filter(i => i.order_type === orderTypeFilter.value);
});

const loadInventory = async () => {
  try {
    const response = await fetch(
      `/api/warehouse/inventory?tenant_id=${props.tenantId}&branch_id=${props.branchId || ''}`
    );
    const data = await response.json();
    inventory.value = data.items;
    stats.value = data.stats;
  } catch (error) {
    console.error('Failed to load inventory:', error);
  }
};

const getItemColor = (orderType: string | null) => {
  if (orderType === 'b2c') {
    return '#48BB78';
  }
  if (orderType === 'b2b') {
    return '#4A5568';
  }
  return '#A0AEC0';
};

const convertItem = (itemId: number) => {
  selectedItemId.value = itemId;
  showConversionModal.value = true;
};

const viewItem = (itemId: number) => {
  // Open item details modal
};

const createMovement = () => {
  // Open movement creation modal
};

onMounted(() => {
  loadInventory();
});
</script>

<style scoped>
.warehouse-section {
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

.warehouse-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.stat-card {
  background: var(--bg-secondary);
  padding: 1.5rem;
  border-radius: 0.75rem;
  border: 1px solid var(--border-color);
  position: relative;
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

.stat-color {
  position: absolute;
  top: 1rem;
  right: 1rem;
  width: 12px;
  height: 12px;
  border-radius: 50%;
}

.color-legend {
  display: flex;
  gap: 2rem;
  padding: 1rem;
  background: var(--bg-secondary);
  border-radius: 0.5rem;
  border: 1px solid var(--border-color);
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.legend-color {
  width: 16px;
  height: 16px;
  border-radius: 4px;
}

.inventory-table {
  background: var(--bg-secondary);
  border-radius: 0.75rem;
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.inventory-table table {
  width: 100%;
  border-collapse: collapse;
}

.inventory-table th,
.inventory-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid var(--border-color);
}

.inventory-table th {
  background: var(--bg-tertiary);
  font-weight: 600;
  color: var(--text-secondary);
}

.inventory-row:hover {
  background: var(--bg-tertiary);
}

.type-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  color: white;
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
