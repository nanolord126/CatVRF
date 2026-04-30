<script setup>
/**
 * B2BCompanyProfile — Company profile management with tier settings,
 * credit line configuration, branches, and team management.
 */
import { ref, computed } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VInput from '../UI/VInput.vue';
import VModal from '../UI/VModal.vue';
import VTable from '../UI/VTable.vue';

const activeTab = ref('overview');
const showEditModal = ref(false);
const showAddBranchModal = ref(false);
const showAddUserModal = ref(false);

const tabs = [
    { id: 'overview', label: 'Обзор', icon: '📊' },
    { id: 'details', label: 'Реквизиты', icon: '📋' },
    { id: 'branches', label: 'Филиалы', icon: '🏢' },
    { id: 'team', label: 'Команда', icon: '👥' },
    { id: 'settings', label: 'Настройки', icon: '⚙️' },
];

const company = ref({
    id: '1',
    name: 'ООО «Альфа-Центр»',
    type: 'ooo',
    inn: '7712345678',
    kpp: '771201001',
    ogrn: '1027700132195',
    legalAddress: 'Москва, ул. Ленина 15',
    actualAddress: 'Москва, ул. Ленина 15, офис 301',
    registrationDate: '2015-06-15',
    tier: 'gold',
    status: 'active',
    creditLimit: 500000,
    creditUsed: 187500,
    paymentTermDays: 14,
    contactPerson: 'Иванов Иван Иванович',
    contactPhone: '+7 (999) 123-45-67',
    contactEmail: 'ivanov@alpha-center.ru',
    website: 'www.alpha-center.ru',
});

const branches = ref([
    { id: 1, name: 'Главный офис', address: 'Москва, ул. Ленина 15', city: 'Москва', manager: 'Иванов И.И.', phone: '+7 (999) 123-45-67', status: 'active' },
    { id: 2, name: 'Филиал СПб', address: 'СПб, Невский 42', city: 'Санкт-Петербург', manager: 'Петров П.П.', phone: '+7 (999) 987-65-43', status: 'active' },
]);

const team = ref([
    { id: 1, name: 'Иванов Иван Иванович', position: 'Генеральный директор', email: 'ivanov@alpha-center.ru', phone: '+7 (999) 123-45-67', role: 'admin', status: 'active' },
    { id: 2, name: 'Петров Пётр Петрович', position: 'Менеджер по закупкам', email: 'petrov@alpha-center.ru', phone: '+7 (999) 234-56-78', role: 'manager', status: 'active' },
    { id: 3, name: 'Сидорова Анна Сергеевна', position: 'Бухгалтер', email: 'sidorova@alpha-center.ru', phone: '+7 (999) 345-67-89', role: 'accountant', status: 'active' },
]);

const tierOptions = [
    { id: 'standard', name: 'Standard', discount: 5, creditMultiplier: 1, color: 'neutral' },
    { id: 'silver', name: 'Silver', discount: 10, creditMultiplier: 1.5, color: 'info' },
    { id: 'gold', name: 'Gold', discount: 15, creditMultiplier: 2, color: 'b2b' },
    { id: 'platinum', name: 'Platinum', discount: 20, creditMultiplier: 3, color: 'success' },
];

const branchColumns = [
    { key: 'name', label: 'Название', sortable: true },
    { key: 'address', label: 'Адрес' },
    { key: 'city', label: 'Город' },
    { key: 'manager', label: 'Менеджер' },
    { key: 'status', label: 'Статус', align: 'center' },
];

const teamColumns = [
    { key: 'name', label: 'ФИО', sortable: true },
    { key: 'position', label: 'Должность' },
    { key: 'email', label: 'Email' },
    { key: 'role', label: 'Роль', align: 'center' },
    { key: 'status', label: 'Статус', align: 'center' },
];

const creditUtilization = computed(() => (company.value.creditUsed / company.value.creditLimit * 100).toFixed(1));
const availableCredit = computed(() => company.value.creditLimit - company.value.creditUsed);
const currentTier = computed(() => tierOptions.find(t => t.id === company.value.tier));

function saveCompany() {
    showEditModal.value = false;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-linear-to-br from-amber-500/20 to-orange-500/20 border border-amber-500/20 flex items-center justify-center text-3xl shadow-lg shadow-amber-500/10">
                    🏢
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold text-(--t-text)">{{ company.name }}</h1>
                        <VBadge :text="company.tier.toUpperCase()" variant="b2b" size="sm" />
                        <VBadge :text="company.status === 'active' ? 'Активен' : 'Неактивен'" variant="success" size="xs" dot />
                    </div>
                    <p class="text-sm text-(--t-text-3) mt-1">ИНН: {{ company.inn }} • с {{ company.registrationDate }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <VButton variant="secondary" size="sm">📄 Экспорт</VButton>
                <VButton variant="b2b" size="sm" @click="showEditModal = true">✏️ Редактировать</VButton>
            </div>
        </div>

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
                {{ tab.icon }} {{ tab.label }}
            </button>
        </div>

        <!-- Overview Tab -->
        <template v-if="activeTab === 'overview'">
            <!-- Credit Line Banner -->
            <div class="relative overflow-hidden rounded-2xl bg-linear-to-r from-amber-900/30 via-orange-900/20 to-amber-900/30 border border-amber-500/20 p-6">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <div class="text-xs text-amber-400/60 uppercase tracking-wider mb-1">Тариф</div>
                        <div class="text-2xl font-bold text-amber-100">{{ currentTier?.name }}</div>
                        <div class="text-xs text-amber-300/50 mt-1">Скидка {{ currentTier?.discount }}%</div>
                    </div>
                    <div>
                        <div class="text-xs text-amber-400/60 uppercase tracking-wider mb-1">Кредитный лимит</div>
                        <div class="text-2xl font-bold text-amber-100">{{ (company.creditLimit/1000).toFixed(0) }}k ₽</div>
                    </div>
                    <div>
                        <div class="text-xs text-amber-400/60 uppercase tracking-wider mb-1">Использовано</div>
                        <div class="text-2xl font-bold text-orange-300">{{ creditUtilization }}%</div>
                        <div class="mt-1 h-1.5 rounded-full bg-amber-900/40 overflow-hidden">
                            <div class="h-full rounded-full bg-linear-to-r from-amber-400 to-orange-400" :style="{width: creditUtilization + '%'}" />
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-amber-400/60 uppercase tracking-wider mb-1">Доступно</div>
                        <div class="text-2xl font-bold text-amber-100">{{ (availableCredit/1000).toFixed(0) }}k ₽</div>
                    </div>
                </div>
                <div class="absolute -right-16 -bottom-16 w-48 h-48 rounded-full bg-amber-500/5 blur-3xl pointer-events-none" />
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <VCard flat>
                    <div class="text-center p-4">
                        <p class="text-3xl mb-2">📦</p>
                        <p class="text-2xl font-bold text-(--t-text)">142</p>
                        <p class="text-xs text-(--t-text-3)">Заказов</p>
                    </div>
                </VCard>
                <VCard flat>
                    <div class="text-center p-4">
                        <p class="text-3xl mb-2">💰</p>
                        <p class="text-2xl font-bold text-(--t-text)">2.4M ₽</p>
                        <p class="text-xs text-(--t-text-3)">Оборот</p>
                    </div>
                </VCard>
                <VCard flat>
                    <div class="text-center p-4">
                        <p class="text-3xl mb-2">🏢</p>
                        <p class="text-2xl font-bold text-(--t-text)">{{ branches.length }}</p>
                        <p class="text-xs text-(--t-text-3)">Филиалов</p>
                    </div>
                </VCard>
                <VCard flat>
                    <div class="text-center p-4">
                        <p class="text-3xl mb-2">👥</p>
                        <p class="text-2xl font-bold text-(--t-text)">{{ team.length }}</p>
                        <p class="text-xs text-(--t-text-3)">Сотрудников</p>
                    </div>
                </VCard>
            </div>

            <!-- Quick Info -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <VCard title="📍 Контактная информация">
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">Контактное лицо</span>
                            <span class="text-sm font-medium text-(--t-text)">{{ company.contactPerson }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">Телефон</span>
                            <span class="text-sm font-medium text-(--t-text)">{{ company.contactPhone }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">Email</span>
                            <span class="text-sm font-medium text-(--t-text)">{{ company.contactEmail }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">Сайт</span>
                            <span class="text-sm font-medium text-(--t-primary)">{{ company.website }}</span>
                        </div>
                    </div>
                </VCard>

                <VCard title="💳 Условия сотрудничества">
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">Тарифный план</span>
                            <VBadge :text="company.tier.toUpperCase()" variant="b2b" size="xs" />
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">Скидка</span>
                            <span class="text-sm font-medium text-(--t-text)">{{ currentTier?.discount }}%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">Отсрочка платежа</span>
                            <span class="text-sm font-medium text-(--t-text)">{{ company.paymentTermDays }} дней</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">Кредитный лимит</span>
                            <span class="text-sm font-medium text-(--t-text)">{{ company.creditLimit.toLocaleString() }} ₽</span>
                        </div>
                    </div>
                </VCard>
            </div>
        </template>

        <!-- Details Tab -->
        <template v-if="activeTab === 'details'">
            <VCard title="📋 Юридические реквизиты">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-(--t-text-3) mb-1">Полное название</p>
                            <p class="text-sm font-medium text-(--t-text)">{{ company.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-(--t-text-3) mb-1">Организационно-правовая форма</p>
                            <p class="text-sm font-medium text-(--t-text)">
                                {{ company.type === 'ooo' ? 'Общество с ограниченной ответственностью' : 
                                   company.type === 'ip' ? 'Индивидуальный предприниматель' : 'Акционерное общество' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-(--t-text-3) mb-1">ИНН</p>
                            <p class="text-sm font-medium text-(--t-text)">{{ company.inn }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-(--t-text-3) mb-1">КПП</p>
                            <p class="text-sm font-medium text-(--t-text)">{{ company.kpp }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-(--t-text-3) mb-1">ОГРН</p>
                            <p class="text-sm font-medium text-(--t-text)">{{ company.ogrn }}</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-(--t-text-3) mb-1">Юридический адрес</p>
                            <p class="text-sm font-medium text-(--t-text)">{{ company.legalAddress }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-(--t-text-3) mb-1">Фактический адрес</p>
                            <p class="text-sm font-medium text-(--t-text)">{{ company.actualAddress }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-(--t-text-3) mb-1">Дата регистрации</p>
                            <p class="text-sm font-medium text-(--t-text)">{{ company.registrationDate }}</p>
                        </div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- Branches Tab -->
        <template v-if="activeTab === 'branches'">
            <VCard title="🏢 Филиалы">
                <template #header-action>
                    <VButton variant="b2b" size="sm" @click="showAddBranchModal = true">➕ Добавить филиал</VButton>
                </template>
                <VTable :columns="branchColumns" :rows="branches">
                    <template #cell-name="{ value }">
                        <span class="font-medium text-(--t-text)">{{ value }}</span>
                    </template>
                    <template #cell-status="{ value }">
                        <VBadge :text="value === 'active' ? 'Активен' : 'Неактивен'" :variant="value === 'active' ? 'success' : 'danger'" size="xs" dot />
                    </template>
                </VTable>
            </VCard>
        </template>

        <!-- Team Tab -->
        <template v-if="activeTab === 'team'">
            <VCard title="👥 Команда">
                <template #header-action>
                    <VButton variant="b2b" size="sm" @click="showAddUserModal = true">➕ Добавить сотрудника</VButton>
                </template>
                <VTable :columns="teamColumns" :rows="team">
                    <template #cell-name="{ value }">
                        <span class="font-medium text-(--t-text)">{{ value }}</span>
                    </template>
                    <template #cell-role="{ value }">
                        <VBadge 
                            :text="value === 'admin' ? 'Администратор' : value === 'manager' ? 'Менеджер' : 'Бухгалтер'" 
                            :variant="value === 'admin' ? 'danger' : value === 'manager' ? 'b2b' : 'info'" 
                            size="xs" 
                        />
                    </template>
                    <template #cell-status="{ value }">
                        <VBadge :text="value === 'active' ? 'Активен' : 'Неактивен'" :variant="value === 'active' ? 'success' : 'danger'" size="xs" dot />
                    </template>
                </VTable>
            </VCard>
        </template>

        <!-- Settings Tab -->
        <template v-if="activeTab === 'settings'">
            <div class="space-y-6">
                <VCard title="⚙️ Настройки тарифа">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-(--t-text) mb-3">Тарифный план</label>
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                                <label 
                                    v-for="tier in tierOptions" 
                                    :key="tier.id"
                                    class="p-4 rounded-xl border cursor-pointer transition-all text-center"
                                    :class="company.tier === tier.id 
                                        ? 'border-(--t-primary) bg-(--t-primary-dim)' 
                                        : 'border-(--t-border) hover:border-(--t-primary)/30'"
                                >
                                    <input type="radio" v-model="company.tier" :value="tier.id" class="sr-only" />
                                    <p class="font-bold text-(--t-text)">{{ tier.name }}</p>
                                    <p class="text-xs text-(--t-text-3) mt-1">Скидка {{ tier.discount }}%</p>
                                    <p class="text-xs text-(--t-text-3)">Кредит x{{ tier.creditMultiplier }}</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </VCard>

                <VCard title="🔐 Безопасность">
                    <div class="space-y-4">
                        <VButton variant="secondary" full-width>🔑 Изменить пароль</VButton>
                        <VButton variant="secondary" full-width>📱 Настроить 2FA</VButton>
                        <VButton variant="secondary" full-width>🔑 Управление API-ключами</VButton>
                    </div>
                </VCard>

                <VCard title="🔔 Уведомления">
                    <div class="space-y-3">
                        <label class="flex items-center justify-between cursor-pointer">
                            <span class="text-sm text-(--t-text)">Email-уведомления о заказах</span>
                            <input type="checkbox" checked class="rounded border-(--t-border) text-(--t-primary)" />
                        </label>
                        <label class="flex items-center justify-between cursor-pointer">
                            <span class="text-sm text-(--t-text)">SMS-уведомления</span>
                            <input type="checkbox" checked class="rounded border-(--t-border) text-(--t-primary)" />
                        </label>
                        <label class="flex items-center justify-between cursor-pointer">
                            <span class="text-sm text-(--t-text)">Push-уведомления</span>
                            <input type="checkbox" class="rounded border-(--t-border) text-(--t-primary)" />
                        </label>
                    </div>
                </VCard>
            </div>
        </template>

        <!-- Edit Modal -->
        <VModal v-model="showEditModal" title="✏️ Редактирование профиля" size="lg">
            <div class="space-y-4">
                <VInput v-model="company.name" label="Название компании" required />
                <div class="grid grid-cols-2 gap-4">
                    <VInput v-model="company.contactPerson" label="Контактное лицо" required />
                    <VInput v-model="company.contactPhone" label="Телефон" required />
                </div>
                <VInput v-model="company.contactEmail" label="Email" type="email" required />
                <VInput v-model="company.website" label="Сайт" />
                <VInput v-model="company.actualAddress" label="Фактический адрес" />
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showEditModal = false">Отмена</VButton>
                <VButton variant="b2b" @click="saveCompany">Сохранить</VButton>
            </template>
        </VModal>
    </div>
</template>
