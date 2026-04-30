<template>
  <div class="documents-tab">
    <div class="documents-filters">
      <select v-model="documentType" class="filter-select">
        <option value="all">{{ $t('wallet.documents.allTypes') }}</option>
        <option value="act">{{ $t('wallet.documents.act') }}</option>
        <option value="invoice">{{ $t('wallet.documents.invoice') }}</option>
        <option value="reconciliation">{{ $t('wallet.documents.reconciliation') }}</option>
        <option value="closing">{{ $t('wallet.documents.closing') }}</option>
      </select>
      <input
        v-model="searchQuery"
        type="text"
        class="search-input"
        :placeholder="$t('wallet.documents.search')"
      />
      <button class="btn-primary" @click="generateDocument">
        {{ $t('wallet.documents.generate') }}
      </button>
    </div>

    <div class="documents-list">
      <div
        v-for="doc in filteredDocuments"
        :key="doc.id"
        class="document-item"
      >
        <div class="document-icon">
          <FileText :size="24" />
        </div>
        <div class="document-info">
          <div class="document-name">{{ doc.name }}</div>
          <div class="document-meta">
            <span class="document-type">{{ $t(`wallet.documents.types.${doc.type}`) }}</span>
            <span class="document-date">{{ formatDate(doc.date) }}</span>
            <span class="document-amount">{{ formatCurrency(doc.amount) }}</span>
          </div>
        </div>
        <div class="document-actions">
          <button class="btn-icon" @click="viewDocument(doc.id)" :title="$t('common.view')">
            <Eye :size="18" />
          </button>
          <button class="btn-icon" @click="downloadDocument(doc.id)" :title="$t('common.download')">
            <Download :size="18" />
          </button>
          <button class="btn-icon" @click="sendDocument(doc.id)" :title="$t('wallet.documents.send')">
            <Send :size="18" />
          </button>
        </div>
      </div>
    </div>

    <DocumentGenerationModal
      v-if="showGenerationModal"
      :tenant-id="tenantId"
      @close="showGenerationModal = false"
      @generated="loadDocuments"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { FileText, Eye, Download, Send } from 'lucide-vue-next';
import DocumentGenerationModal from './DocumentGenerationModal.vue';

const { t } = useI18n();

defineProps<{
  tenantId: string;
}>();

const documents = ref([]);
const documentType = ref('all');
const searchQuery = ref('');
const showGenerationModal = ref(false);

const filteredDocuments = computed(() => {
  let filtered = documents.value;

  if (documentType.value !== 'all') {
    filtered = filtered.filter(d => d.type === documentType.value);
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(d => 
      d.name.toLowerCase().includes(query) ||
      d.number.toLowerCase().includes(query)
    );
  }

  return filtered;
});

const loadDocuments = async () => {
  try {
    const response = await fetch(`/api/wallet/documents?tenant_id=${props.tenantId}`);
    const data = await response.json();
    documents.value = data.documents;
  } catch (error) {
    console.error('Failed to load documents:', error);
  }
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

const viewDocument = (id: number) => {
  window.open(`/api/wallet/documents/${id}/view`, '_blank');
};

const downloadDocument = async (id: number) => {
  try {
    const response = await fetch(`/api/wallet/documents/${id}/download`);
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `document_${id}.pdf`;
    a.click();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Failed to download document:', error);
  }
};

const sendDocument = (id: number) => {
  // Open send document modal
};

const generateDocument = () => {
  showGenerationModal.value = true;
};

onMounted(() => {
  loadDocuments();
});
</script>

<style scoped>
.documents-tab {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.documents-filters {
  display: flex;
  gap: 0.75rem;
}

.filter-select,
.search-input {
  padding: 0.5rem 1rem;
  border: 1px solid var(--border-color);
  border-radius: 0.5rem;
  background: var(--bg-secondary);
  color: var(--text-primary);
}

.search-input {
  flex: 1;
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

.documents-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.document-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 0.75rem;
  transition: all 0.2s;
}

.document-item:hover {
  background: var(--bg-tertiary);
  border-color: var(--primary-color);
}

.document-icon {
  color: var(--primary-color);
}

.document-info {
  flex: 1;
}

.document-name {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}

.document-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.document-amount {
  font-weight: 600;
  color: var(--primary-color);
}

.document-actions {
  display: flex;
  gap: 0.5rem;
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
  background: var(--bg-primary);
  color: var(--text-primary);
}
</style>
