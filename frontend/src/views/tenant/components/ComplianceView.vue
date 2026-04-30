<template>
  <div class="compliance-view">
    <!-- Sub-navigation -->
    <div class="compliance-nav">
      <button
        v-for="tab in complianceTabs"
        :key="tab.id"
        @click="activeComplianceTab = tab.id"
        :class="[
          'compliance-nav-btn',
          activeComplianceTab === tab.id ? 'active' : ''
        ]"
      >
        <span class="tab-icon">{{ tab.icon }}</span>
        <span class="tab-label">{{ tab.label }}</span>
      </button>
    </div>

    <!-- Content -->
    <div class="compliance-content">
      <!-- Dashboard -->
      <ComplianceDashboard v-if="activeComplianceTab === 'dashboard'" />

      <!-- AML Checks -->
      <AMLChecksViewer v-if="activeComplianceTab === 'aml'" />

      <!-- Fiscal Receipts -->
      <FiscalReceiptsViewer v-if="activeComplianceTab === 'fiscal'" />

      <!-- Payment Rules -->
      <PaymentRulesManagement v-if="activeComplianceTab === 'rules'" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import ComplianceDashboard from '@/components/business/compliance/ComplianceDashboard.vue'
import AMLChecksViewer from '@/components/business/compliance/AMLChecksViewer.vue'
import FiscalReceiptsViewer from '@/components/business/compliance/FiscalReceiptsViewer.vue'
import PaymentRulesManagement from '@/components/business/compliance/PaymentRulesManagement.vue'

const activeComplianceTab = ref('dashboard')

const complianceTabs = [
  { id: 'dashboard', label: 'Dashboard', icon: '📊' },
  { id: 'aml', label: 'ФЗ-115 AML', icon: '🔍' },
  { id: 'fiscal', label: '54-ФЗ Fiscal', icon: '🧾' },
  { id: 'rules', label: 'ФЗ-161 Rules', icon: '⚖️' },
]
</script>

<style scoped>
.compliance-view {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.compliance-nav {
  display: flex;
  gap: 4px;
  padding: 8px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.compliance-nav-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: none;
  background: transparent;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s;
}

.compliance-nav-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

.compliance-nav-btn.active {
  background: white;
  color: #3b82f6;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.tab-icon {
  font-size: 16px;
}

.compliance-content {
  padding: 0;
  min-height: 600px;
}
</style>
