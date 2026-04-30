<script setup>
/**
 * B2BDocuments — Document management for B2B operations including
 * invoices, contracts, acts, and compliance documents.
 */
import { ref, computed } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VInput from '../UI/VInput.vue';
import VModal from '../UI/VModal.vue';
import VTable from '../UI/VTable.vue';

const activeTab = ref('all');
const searchQuery = ref('');
const selectedDocuments = ref([]);
const showUploadModal = ref(false);
const showSignModal = ref(false);
const selectedDocument = ref(null);

const tabs = [
    { id: 'all', label: 'Все', count: 45 },
    { id: 'invoices', label: 'Счёта', count: 12 },
    { id: 'contracts', label: 'Договоры', count: 8 },
    { id: 'acts', label: 'Акты', count: 15 },
    { id: 'compliance', label: 'Комплаенс', count: 10 },
];

const documentTypes = [
    { id: 'invoice', name: 'Счёт на оплату', icon: '📄' },
    { id: 'invoice_factura', name: 'Счёт-фактура', icon: '🧾' },
    { id: 'contract', name: 'Договор', icon: '📋' },
    { id: 'act', name: 'Акт приёмки', icon: '📝' },
    { id: 'upd', name: 'УПД', icon: '📊' },
    { id: 'compliance', name: 'Комплаенс', icon: '🛡️' },
];

const documents = [
    {
        id: 1,
        type: 'invoice',
        name: 'Счёт на оплату №1234',
        number: 'СЧ-1234',
        orderId: 'B-2042',
        company: 'ООО «Альфа-Центр»',
        amount: 435000,
        date: '2026-04-15',
        dueDate: '2026-05-01',
        status: 'sent',
        signed: false,
        file: 'invoice_1234.pdf',
        fileSize: '245 KB',
    },
    {
        id: 2,
        type: 'contract',
        name: 'Договор поставки №56',
        number: 'ДГ-56',
        company: 'ООО «Альфа-Центр»',
        date: '2026-04-15',
        validUntil: '2027-04-15',
        status: 'signed',
        signed: true,
        file: 'contract_56.pdf',
        fileSize: '1.2 MB',
    },
    {
        id: 3,
        type: 'invoice_factura',
        name: 'Счёт-фактура №789',
        number: 'СФ-789',
        orderId: 'B-2042',
        company: 'ООО «Альфа-Центр»',
        amount: 435000,
        date: '2026-04-17',
        status: 'pending',
        signed: false,
        file: 'invoice_factura_789.pdf',
        fileSize: '180 KB',
    },
    {
        id: 4,
        type: 'act',
        name: 'Акт приёмки-передачи №45',
        number: 'АКТ-45',
        orderId: 'B-2039',
        company: 'ИП Петров А.В.',
        amount: 89000,
        date: '2026-04-12',
        status: 'signed',
        signed: true,
        file: 'act_45.pdf',
        fileSize: '320 KB',
    },
    {
        id: 5,
        type: 'compliance',
        name: 'Сертификат соответствия',
        number: 'СС-2026-001',
        company: 'ООО «Альфа-Центр»',
        date: '2026-03-01',
        validUntil: '2027-03-01',
        status: 'valid',
        signed: true,
        file: 'cert_001.pdf',
        fileSize: '2.5 MB',
    },
];

const columns = [
    { key: 'name', label: 'Документ', sortable: true },
    { key: 'number', label: 'Номер' },
    { key: 'company', label: 'Компания', sortable: true },
    { key: 'amount', label: 'Сумма', align: 'right' },
    { key: 'date', label: 'Дата', sortable: true },
    { key: 'status', label: 'Статус', align: 'center' },
];

const statusColors = {
    sent: { bg: 'rgba(96,165,250,0.12)', text: '#60a5fa', border: 'rgba(96,165,250,0.3)' },
    pending: { bg: 'rgba(251,191,36,0.12)', text: '#fbbf24', border: 'rgba(251,191,36,0.3)' },
    signed: { bg: 'rgba(52,211,153,0.12)', text: '#34d399', border: 'rgba(52,211,153,0.3)' },
    valid: { bg: 'rgba(52,211,153,0.12)', text: '#34d399', border: 'rgba(52,211,153,0.3)' },
    expired: { bg: 'rgba(248,113,113,0.12)', text: '#f87171', border: 'rgba(248,113,113,0.3)' },
};

const statusLabels = {
    sent: 'Отправлен',
    pending: 'Ожидает',
    signed: 'Подписан',
    valid: 'Действует',
    expired: 'Истёк',
};

const filteredDocuments = computed(() => {
    let result = [...documents];

    // Tab filter
    if (activeTab.value !== 'all') {
        result = result.filter(d => d.type === activeTab.value || 
            (activeTab.value === 'compliance' && d.type === 'compliance'));
    }

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(d => 
            d.name.toLowerCase().includes(query) || 
            d.number.toLowerCase().includes(query) ||
            d.company.toLowerCase().includes(query)
        );
    }

    return result;
});

const selectedCount = computed(() => selectedDocuments.value.length);

function openDocument(doc) {
    selectedDocument.value = doc;
}

function signDocument(doc) {
    selectedDocument.value = doc;
    showSignModal.value = true;
}

function uploadDocument() {
    showUploadModal.value = true;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-(--t-text)">📄 Документы B2B</h1>
                <p class="text-sm text-(--t-text-3) mt-1">Управление документами и комплаенс</p>
            </div>
            <div class="flex items-center gap-2">
                <VButton variant="secondary" size="sm">📥 Импорт из 1С</VButton>
                <VButton variant="b2b" size="sm" @click="uploadDocument">➕ Загрузить</VButton>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <VCard flat>
                <div class="text-center p-3">
                    <p class="text-2xl font-bold text-(--t-text)">{{ documents.length }}</p>
                    <p class="text-xs text-(--t-text-3)">Всего документов</p>
                </div>
            </VCard>
            <VCard flat>
                <div class="text-center p-3">
                    <p class="text-2xl font-bold text-amber-400">{{ documents.filter(d => d.status === 'pending').length }}</p>
                    <p class="text-xs text-(--t-text-3)">Ожидают подписи</p>
                </div>
            </VCard>
            <VCard flat>
                <div class="text-center p-3">
                    <p class="text-2xl font-bold text-emerald-400">{{ documents.filter(d => d.signed).length }}</p>
                    <p class="text-xs text-(--t-text-3)">Подписано</p>
                </div>
            </VCard>
            <VCard flat>
                <div class="text-center p-3">
                    <p class="text-2xl font-bold text-blue-400">{{ documents.filter(d => d.type === 'compliance').length }}</p>
                    <p class="text-xs text-(--t-text-3)">Комплаенс</p>
                </div>
            </VCard>
        </div>

        <!-- Filters -->
        <VCard flat>
            <div class="flex flex-col lg:flex-row gap-4">
                <VInput 
                    v-model="searchQuery" 
                    placeholder="Поиск по названию, номеру или компании..." 
                    prefix-icon="🔍"
                    class="flex-1"
                />
                <select class="px-4 py-2 rounded-xl bg-(--t-surface) border border-(--t-border) text-(--t-text) text-sm focus:outline-none focus:border-(--t-primary)">
                    <option>Все типы</option>
                    <option v-for="type in documentTypes" :key="type.id">{{ type.icon }} {{ type.name }}</option>
                </select>
            </div>
        </VCard>

        <!-- Tabs -->
        <div class="flex gap-1 p-1 rounded-xl border" style="background: var(--t-surface); border-color: var(--t-border);">
            <button 
                v-for="tab in tabs" 
                :key="tab.id"
                @click="activeTab = tab.id"
                class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-all duration-300 active:scale-95 cursor-pointer"
                :style="activeTab === tab.id
                    ? { background: 'var(--t-primary-dim)', color: 'var(--t-text)', boxShadow: '0 0 8px var(--t-glow)' }
                    : { color: 'var(--t-text-3)' }"
            >
                {{ tab.label }}
                <span v-if="tab.count" class="ml-1 opacity-60">{{ tab.count }}</span>
            </button>
        </div>

        <!-- Documents Table -->
        <VCard>
            <template #header-action>
                <div v-if="selectedCount > 0" class="flex items-center gap-2">
                    <span class="text-sm text-(--t-text-3)">Выбрано: {{ selectedCount }}</span>
                    <VButton variant="secondary" size="xs">📥 Скачать</VButton>
                    <VButton variant="danger" size="xs">🗑️ Удалить</VButton>
                </div>
            </template>

            <VTable :columns="columns" :rows="filteredDocuments">
                <template #cell-name="{ value, row }">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">{{ documentTypes.find(t => t.id === row.type)?.icon || '📄' }}</span>
                        <div>
                            <p class="font-medium text-(--t-text)">{{ value }}</p>
                            <p class="text-[10px] text-(--t-text-3)">{{ row.file }} • {{ row.fileSize }}</p>
                        </div>
                    </div>
                </template>
                <template #cell-number="{ value }">
                    <span class="font-mono text-xs text-(--t-text)">{{ value }}</span>
                </template>
                <template #cell-company="{ value }">
                    <span class="text-sm text-(--t-text)">{{ value }}</span>
                </template>
                <template #cell-amount="{ value, row }">
                    <span v-if="row.amount" class="font-bold text-(--t-text)">{{ value.toLocaleString() }} ₽</span>
                    <span v-else class="text-(--t-text-3)">—</span>
                </template>
                <template #cell-date="{ value, row }">
                    <div class="text-sm text-(--t-text)">{{ value }}</div>
                    <div v-if="row.validUntil" class="text-[10px] text-(--t-text-3)">до {{ row.validUntil }}</div>
                </template>
                <template #cell-status="{ value, row }">
                    <div class="flex items-center gap-2">
                        <VBadge 
                            :text="statusLabels[value]" 
                            :variant="value === 'sent' ? 'info' : value === 'pending' ? 'warning' : value === 'signed' || value === 'valid' ? 'success' : 'danger'" 
                            size="xs" 
                            dot 
                        />
                        <span v-if="row.signed" class="text-emerald-400">✓</span>
                    </div>
                </template>
            </VTable>
        </VCard>

        <!-- Grid View Alternative -->
        <div v-if="activeTab === 'compliance'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <VCard 
                v-for="doc in filteredDocuments" 
                :key="doc.id"
                clickable
                glow
                @click="openDocument(doc)"
            >
                <div class="text-center py-4">
                    <div class="text-4xl mb-3">{{ documentTypes.find(t => t.id === doc.type)?.icon || '📄' }}</div>
                    <h3 class="font-semibold text-(--t-text) text-sm">{{ doc.name }}</h3>
                    <p class="text-xs text-(--t-text-3) mt-1">{{ doc.number }}</p>
                    <div class="mt-3 flex items-center justify-center gap-2">
                        <VBadge 
                            :text="statusLabels[doc.status]" 
                            :variant="doc.status === 'valid' ? 'success' : 'danger'" 
                            size="xs" 
                        />
                    </div>
                    <div class="mt-3 text-xs text-(--t-text-3)">
                        <p>Действует до: {{ doc.validUntil }}</p>
                    </div>
                </div>
            </VCard>
        </div>

        <!-- Empty State -->
        <div v-if="filteredDocuments.length === 0" class="text-center py-16">
            <p class="text-5xl mb-4">📄</p>
            <p class="text-(--t-text-2) text-lg">Документы не найдены</p>
            <p class="text-(--t-text-3) text-sm mt-2">Загрузите документы или измените фильтры</p>
            <VButton variant="b2b" size="md" class="mt-6" @click="uploadDocument">Загрузить документ</VButton>
        </div>

        <!-- Upload Modal -->
        <VModal v-model="showUploadModal" title="📤 Загрузка документа" size="md">
            <div class="space-y-4">
                <div class="border-2 border-dashed border-(--t-border) rounded-xl p-8 text-center hover:border-(--t-primary)/50 transition-colors cursor-pointer">
                    <p class="text-4xl mb-3">📁</p>
                    <p class="text-sm font-medium text-(--t-text)">Перетащите файл сюда</p>
                    <p class="text-xs text-(--t-text-3) mt-1">или нажмите для выбора</p>
                    <p class="text-[10px] text-(--t-text-3) mt-2">PDF, DOC, DOCX, XLS, XLSX (макс. 10MB)</p>
                </div>
                <VInput label="Название документа" placeholder="Счёт на оплату №1234" />
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-(--t-text) mb-2">Тип документа</label>
                        <select class="w-full px-4 py-3 rounded-xl bg-(--t-surface) border border-(--t-border) text-(--t-text)">
                            <option v-for="type in documentTypes" :key="type.id">{{ type.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-(--t-text) mb-2">Компания</label>
                        <select class="w-full px-4 py-3 rounded-xl bg-(--t-surface) border border-(--t-border) text-(--t-text)">
                            <option>ООО «Альфа-Центр»</option>
                            <option>ИП Петров А.В.</option>
                            <option>ООО «Гамма»</option>
                        </select>
                    </div>
                </div>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showUploadModal = false">Отмена</VButton>
                <VButton variant="b2b">Загрузить</VButton>
            </template>
        </VModal>

        <!-- Sign Modal -->
        <VModal v-model="showSignModal" title="✍️ Подписание документа" size="md" v-if="selectedDocument">
            <div class="space-y-4">
                <div class="p-4 rounded-xl bg-(--t-card-hover)">
                    <p class="font-medium text-(--t-text)">{{ selectedDocument.name }}</p>
                    <p class="text-sm text-(--t-text-3) mt-1">{{ selectedDocument.number }}</p>
                </div>
                <div class="space-y-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="sign_method" checked class="mt-1 text-(--t-primary)" />
                        <div>
                            <p class="font-medium text-(--t-text)">Электронная подпись</p>
                            <p class="text-xs text-(--t-text-3)">Подписать с помощью сертификата ЭЦП</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="sign_method" class="mt-1 text-(--t-primary)" />
                        <div>
                            <p class="font-medium text-(--t-text)">Подпись через SMS</p>
                            <p class="text-xs text-(--t-text-3)">Код подтверждения будет отправлен на телефон</p>
                        </div>
                    </label>
                </div>
                <VInput label="Комментарий (необязательно)" placeholder="Дополнительная информация" />
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showSignModal = false">Отмена</VButton>
                <VButton variant="b2b">Подписать</VButton>
            </template>
        </VModal>
    </div>
</template>
