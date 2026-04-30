<template>
  <div class="wallet-modal-overlay" @click="$emit('close')">
    <div class="wallet-modal" @click.stop>
      <div class="modal-header">
        <h2>{{ $t('wallet.title') }}</h2>
        <button class="close-button" @click="$emit('close')">
          <X :size="24" />
        </button>
      </div>

      <div class="modal-tabs">
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

      <div class="modal-content">
        <BalanceTab v-if="activeTab === 'balance'" :tenant-id="tenantId" />
        <DocumentsTab v-if="activeTab === 'documents'" :tenant-id="tenantId" />
        <TransactionsTab v-if="activeTab === 'transactions'" :tenant-id="tenantId" />
        <ReconciliationsTab v-if="activeTab === 'reconciliations'" :tenant-id="tenantId" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { X } from 'lucide-vue-next';
import BalanceTab from './wallet/BalanceTab.vue';
import DocumentsTab from './wallet/DocumentsTab.vue';
import TransactionsTab from './wallet/TransactionsTab.vue';
import ReconciliationsTab from './wallet/ReconciliationsTab.vue';

const { t } = useI18n();

defineProps<{
  tenantId: string;
}>();

defineEmits<{
  close: [];
}>();

const activeTab = ref('balance');

const tabs = [
  { id: 'balance', label: 'wallet.tabs.balance' },
  { id: 'documents', label: 'wallet.tabs.documents' },
  { id: 'transactions', label: 'wallet.tabs.transactions' },
  { id: 'reconciliations', label: 'wallet.tabs.reconciliations' },
];
</script>

<style scoped>
.wallet-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.wallet-modal {
  background: var(--bg-primary);
  border-radius: 1rem;
  width: 90%;
  max-width: 900px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid var(--border-color);
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.close-button {
  padding: 0.5rem;
  background: transparent;
  border: none;
  cursor: pointer;
  color: var(--text-secondary);
  border-radius: 0.5rem;
}

.close-button:hover {
  background: var(--bg-tertiary);
  color: var(--text-primary);
}

.modal-tabs {
  display: flex;
  gap: 0.5rem;
  padding: 1rem 1.5rem 0;
  border-bottom: 1px solid var(--border-color);
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

.modal-content {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}
</style>
