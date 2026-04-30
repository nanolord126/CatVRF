<template>
  <div class="analytics-section">
    <div class="section-header">
      <h2>{{ $t('crm.analytics.title') }}</h2>
      <div class="header-actions">
        <select v-model="dateRange" class="filter-select" @change="loadAnalytics">
          <option value="7">{{ $t('crm.analytics.last7Days') }}</option>
          <option value="30">{{ $t('crm.analytics.last30Days') }}</option>
          <option value="90">{{ $t('crm.analytics.last90Days') }}</option>
          <option value="365">{{ $t('crm.analytics.lastYear') }}</option>
        </select>
        <select v-model="selectedVertical" class="filter-select" @change="loadAnalytics">
          <option value="all">{{ $t('crm.analytics.allVerticals') }}</option>
          <option v-for="vertical in verticals" :key="vertical" :value="vertical">
            {{ vertical }}
          </option>
        </select>
      </div>
    </div>

    <div class="analytics-tabs">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        class="tab-button"
        :class="{ active: activeTab === tab.id }"
        @click="activeTab = tab.id"
      >
        {{ $t(tab.label) }}
      </button>
    </div>

    <div class="analytics-content">
      <DemandMetrics
        v-if="activeTab === 'demand'"
        :metrics="analyticsData.demand"
      />
      <SalesMetrics
        v-if="activeTab === 'sales'"
        :metrics="analyticsData.sales"
      />
      <QualityMetrics
        v-if="activeTab === 'quality'"
        :metrics="analyticsData.quality"
      />
      <PublicMetrics
        v-if="activeTab === 'public'"
        :metrics="analyticsData.public"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import DemandMetrics from './analytics/DemandMetrics.vue';
import SalesMetrics from './analytics/SalesMetrics.vue';
import QualityMetrics from './analytics/QualityMetrics.vue';
import PublicMetrics from './analytics/PublicMetrics.vue';

const { t } = useI18n();

const props = defineProps<{
  tenantId: string;
  branchId?: string;
}>();

const dateRange = ref('30');
const selectedVertical = ref('all');
const activeTab = ref('demand');
const analyticsData = ref({
  demand: null,
  sales: null,
  quality: null,
  public: null,
});

const verticals = ref([
  'supermarket',
  'restaurant',
  'hotels',
  'fashion',
  'beauty',
  'fitness',
]);

const tabs = [
  { id: 'demand', label: 'crm.analytics.tabs.demand' },
  { id: 'sales', label: 'crm.analytics.tabs.sales' },
  { id: 'quality', label: 'crm.analytics.tabs.quality' },
  { id: 'public', label: 'crm.analytics.tabs.public' },
];

const loadAnalytics = async () => {
  try {
    const response = await fetch(
      `/api/analytics/vertical?tenant_id=${props.tenantId}&branch_id=${props.branchId || ''}&days=${dateRange.value}&vertical=${selectedVertical.value}`
    );
    const data = await response.json();
    analyticsData.value = data;
  } catch (error) {
    console.error('Failed to load analytics:', error);
  }
};

onMounted(() => {
  loadAnalytics();
});
</script>

<style scoped>
.analytics-section {
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

.analytics-tabs {
  display: flex;
  gap: 0.5rem;
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 0;
}

.tab-button {
  padding: 0.75rem 1.5rem;
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  cursor: pointer;
  font-weight: 500;
  color: var(--text-secondary);
  transition: all 0.2s;
}

.tab-button:hover {
  color: var(--text-primary);
}

.tab-button.active {
  color: var(--primary-color);
  border-bottom-color: var(--primary-color);
}

.analytics-content {
  min-height: 400px;
}
</style>
