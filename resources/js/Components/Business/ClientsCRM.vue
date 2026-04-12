<script setup>
/**
 * ClientsCRM — CRM-система: база клиентов, сегментация, теги,
 * история взаимодействий, лояльность и бонусы.
 * Интеграция с UserBehaviorAnalyzerService + BonusService.
 */
import { ref, computed } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VTabs from '../UI/VTabs.vue';
import VInput from '../UI/VInput.vue';
import VModal from '../UI/VModal.vue';
import VStatCard from '../UI/VStatCard.vue';
import VTable from '../UI/VTable.vue';

const activeTab = ref('clients');
const tabs = [
    { key: 'clients', label: 'Клиенты' },
    { key: 'segments', label: 'Сегменты' },
    { key: 'loyalty', label: 'Лояльность' },
    { key: 'tags', label: 'Теги' },
    { key: 'interactions', label: 'История' },
];

const searchQuery = ref('');
const filterSegment = ref('all');
const showClientDetail = ref(false);
const showAddTag = ref(false);
const selectedClient = ref(null);

const metrics = ref({
    totalClients: 4287,
    newThisMonth: 342,
    returning: 67,
    averageLTV: '12 450 ₽',
    churnRisk: 128,
    nps: 72,
});

const clients = ref([
    { id: 1, name: 'Анна Смирнова', email: 'anna@mail.ru', phone: '+7-900-111-22-33', segment: 'vip', ltv: 156000, orders: 28, lastVisit: '2 часа назад', tags: ['VIP', 'Beauty'], avatar: '👩', isOnline: true },
    { id: 2, name: 'Дмитрий Козлов', email: 'dmitry@yandex.ru', phone: '+7-900-222-33-44', segment: 'regular', ltv: 45000, orders: 12, lastVisit: '1 день назад', tags: ['B2B', 'Food'], avatar: '👨', isOnline: false },
    { id: 3, name: 'Елена Петрова', email: 'elena@gmail.com', phone: '+7-900-333-44-55', segment: 'new', ltv: 3200, orders: 2, lastVisit: '3 дня назад', tags: ['New', 'Fashion'], avatar: '👩‍🦰', isOnline: false },
    { id: 4, name: 'ООО «СтройМастер»', email: 'office@stroymaster.ru', phone: '+7-495-000-11-22', segment: 'b2b', ltv: 890000, orders: 45, lastVisit: '5 часов назад', tags: ['B2B', 'Furniture', 'Gold'], avatar: '🏢', isOnline: true },
    { id: 5, name: 'Мария Волкова', email: 'maria@inbox.ru', phone: '+7-900-444-55-66', segment: 'at_risk', ltv: 28000, orders: 8, lastVisit: '30 дней назад', tags: ['Churn Risk', 'Beauty'], avatar: '👩‍🦱', isOnline: false },
    { id: 6, name: 'Сергей Новиков', email: 'sergey@corp.ru', phone: '+7-900-555-66-77', segment: 'regular', ltv: 67000, orders: 15, lastVisit: '6 часов назад', tags: ['Fitness', 'Regular'], avatar: '🧑', isOnline: true },
    { id: 7, name: 'ИП Белова К.А.', email: 'belova@bk.ru', phone: '+7-900-666-77-88', segment: 'b2b', ltv: 345000, orders: 22, lastVisit: '2 дня назад', tags: ['B2B', 'Beauty', 'Silver'], avatar: '🏪', isOnline: false },
    { id: 8, name: 'Татьяна Лебедева', email: 'tanya@mail.ru', phone: '+7-900-777-88-99', segment: 'vip', ltv: 98000, orders: 19, lastVisit: '1 час назад', tags: ['VIP', 'Fashion', 'Loyalty'], avatar: '👱‍♀️', isOnline: true },
]);

const segments = ref([
    { key: 'vip', name: 'VIP', count: 186, color: '#f59e0b', icon: '👑', criteria: 'LTV > 80 000 ₽, 15+ заказов', churn: 2 },
    { key: 'regular', name: 'Постоянные', count: 1240, color: '#22d3ee', icon: '🔄', criteria: 'LTV 20-80k ₽, 5-15 заказов', churn: 8 },
    { key: 'new', name: 'Новые', count: 892, color: '#34d399', icon: '🌱', criteria: 'Регистрация < 30 дней', churn: 35 },
    { key: 'b2b', name: 'B2B', count: 124, color: '#a78bfa', icon: '🏢', criteria: 'Юр. лицо / ИП', churn: 3 },
    { key: 'at_risk', name: 'Под угрозой', count: 128, color: '#f87171', icon: '⚠️', criteria: 'Нет активности 21+ дней', churn: 60 },
    { key: 'dormant', name: 'Спящие', count: 1717, color: '#6b7280', icon: '💤', criteria: 'Нет активности 90+ дней', churn: 85 },
]);

const loyaltyTiers = ref([
    { name: 'Bronze', icon: '🥉', minSpend: 0, cashback: 3, clients: 2800, color: 'from-amber-900/20 to-amber-700/10', border: 'border-amber-700/30' },
    { name: 'Silver', icon: '🥈', minSpend: 30000, cashback: 5, clients: 980, color: 'from-gray-400/20 to-gray-300/10', border: 'border-gray-400/30' },
    { name: 'Gold', icon: '🥇', minSpend: 80000, cashback: 7, clients: 380, color: 'from-yellow-400/20 to-yellow-300/10', border: 'border-yellow-400/30' },
    { name: 'Platinum', icon: '💎', minSpend: 200000, cashback: 10, clients: 127, color: 'from-cyan-400/20 to-cyan-300/10', border: 'border-cyan-400/30' },
]);

const allTags = ref([
    { name: 'VIP', count: 186, color: '#f59e0b' },
    { name: 'B2B', count: 124, color: '#a78bfa' },
    { name: 'Beauty', count: 890, color: '#ec4899' },
    { name: 'Fashion', count: 620, color: '#8b5cf6' },
    { name: 'Food', count: 540, color: '#f97316' },
    { name: 'Furniture', count: 210, color: '#10b981' },
    { name: 'Fitness', count: 380, color: '#3b82f6' },
    { name: 'Churn Risk', count: 128, color: '#ef4444' },
    { name: 'New', count: 892, color: '#22d3ee' },
    { name: 'Loyalty', count: 1487, color: '#fbbf24' },
    { name: 'Regular', count: 1240, color: '#6366f1' },
    { name: 'Gold', count: 380, color: '#eab308' },
    { name: 'Silver', count: 980, color: '#9ca3af' },
]);

const interactionHistory = ref([
    { id: 1, client: 'Анна Смирнова', type: 'purchase', desc: 'Заказ #12045 — Beauty набор (14 200 ₽)', date: '2 часа назад', icon: '🛒' },
    { id: 2, client: 'ООО «СтройМастер»', type: 'support', desc: 'Обращение в поддержку: срок доставки', date: '5 часов назад', icon: '💬' },
    { id: 3, client: 'Мария Волкова', type: 'ai_usage', desc: 'Использовала AI-конструктор Beauty', date: '1 день назад', icon: '🤖' },
    { id: 4, client: 'Татьяна Лебедева', type: 'review', desc: 'Оставила отзыв ⭐⭐⭐⭐⭐', date: '1 день назад', icon: '⭐' },
    { id: 5, client: 'Дмитрий Козлов', type: 'return', desc: 'Возврат товара — Food набор (2 300 ₽)', date: '2 дня назад', icon: '↩️' },
    { id: 6, client: 'Сергей Новиков', type: 'bonus', desc: 'Начислено 500 бонусных баллов', date: '3 дня назад', icon: '🎁' },
    { id: 7, client: 'ИП Белова К.А.', type: 'purchase', desc: 'Оптовый заказ #B2B-089 — Косметика (125 000 ₽)', date: '3 дня назад', icon: '🛒' },
]);

const segmentColors = { vip: 'warning', regular: 'info', new: 'success', b2b: 'primary', at_risk: 'danger', dormant: 'secondary' };
const segmentLabels = { vip: 'VIP', regular: 'Постоянный', new: 'Новый', b2b: 'B2B', at_risk: 'Под угрозой', dormant: 'Спящий' };

const filteredClients = computed(() => {
    let result = clients.value;
    if (filterSegment.value !== 'all') {
        result = result.filter(c => c.segment === filterSegment.value);
    }
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(c => c.name.toLowerCase().includes(q) || c.email.toLowerCase().includes(q));
    }
    return result;
});

function openClient(client) {
    selectedClient.value = client;
    showClientDetail.value = true;
}

function formatLTV(val) {
    if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M ₽';
    if (val >= 1000) return Math.round(val / 1000) + 'k ₽';
    return val + ' ₽';
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-linear-to-br from-(--t-primary-dim) to-(--t-card-hover) border border-(--t-primary)/20 flex items-center justify-center text-2xl shadow-lg shadow-(--t-glow)">
                    👥
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-bold text-(--t-text)">CRM и клиенты</h1>
                    <p class="text-xs text-(--t-text-3)">Сегментация, лояльность, аналитика клиентов</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <VButton variant="secondary" size="sm">📥 Экспорт</VButton>
                <VButton variant="primary" size="sm">➕ Добавить клиента</VButton>
            </div>
        </div>

        <!-- Metrics -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <VStatCard title="Всего клиентов" :value="metrics.totalClients.toLocaleString()" icon="👥" />
            <VStatCard title="Новых / мес" :value="'+' + metrics.newThisMonth" icon="🌱" trend-direction="up" trend-value="+18%" />
            <VStatCard title="Возвращаемость" :value="metrics.returning + '%'" icon="🔄" />
            <VStatCard title="Средний LTV" :value="metrics.averageLTV" icon="💰" />
            <VStatCard title="Риск оттока" :value="String(metrics.churnRisk)" icon="⚠️" trend-direction="down" trend-value="-5%" />
            <VStatCard title="NPS" :value="String(metrics.nps)" icon="📊" />
        </div>

        <!-- Tabs -->
        <VTabs :tabs="tabs" v-model="activeTab" variant="underline" />

        <!-- ===== CLIENTS TABLE ===== -->
        <template v-if="activeTab === 'clients'">
            <VCard>
                <div class="flex flex-col sm:flex-row gap-3 mb-4">
                    <VInput v-model="searchQuery" placeholder="🔍 Поиск по имени, email..." class="flex-1" size="sm" />
                    <div class="flex items-center gap-2 flex-wrap">
                        <button v-for="seg in [{key:'all',label:'Все'}, ...segments.map(s=>({key:s.key,label:s.name}))]" :key="seg.key"
                                @click="filterSegment = seg.key"
                                :class="['px-3 py-1.5 rounded-lg text-xs cursor-pointer transition-all active:scale-95',
                                         filterSegment === seg.key ? 'bg-(--t-primary) text-white font-medium' : 'bg-(--t-card-hover) text-(--t-text-3) hover:text-(--t-text)']"
                        >
                            {{ seg.label }}
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <div v-for="client in filteredClients" :key="client.id"
                         @click="openClient(client)"
                         class="flex items-center gap-3 p-3 rounded-xl border border-(--t-border) hover:bg-(--t-card-hover) hover:shadow-md hover:shadow-(--t-glow) transition-all cursor-pointer active:scale-[0.99]"
                    >
                        <div class="relative shrink-0">
                            <div class="w-10 h-10 rounded-xl bg-(--t-card-hover) flex items-center justify-center text-lg">{{ client.avatar }}</div>
                            <div v-if="client.isOnline" class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-400 border-2 border-(--t-surface)" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-(--t-text) truncate">{{ client.name }}</span>
                                <VBadge :text="segmentLabels[client.segment]" :variant="segmentColors[client.segment]" size="xs" />
                            </div>
                            <div class="text-[10px] text-(--t-text-3)">{{ client.email }} • {{ client.lastVisit }}</div>
                        </div>
                        <div class="hidden sm:flex items-center gap-2 shrink-0">
                            <span v-for="tag in client.tags.slice(0, 2)" :key="tag"
                                  class="px-1.5 py-0.5 rounded bg-(--t-primary-dim) text-[9px] text-(--t-primary)"
                            >{{ tag }}</span>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm font-bold text-(--t-text)">{{ formatLTV(client.ltv) }}</div>
                            <div class="text-[10px] text-(--t-text-3)">{{ client.orders }} заказов</div>
                        </div>
                    </div>
                </div>

                <template #footer>
                    <span class="text-xs text-(--t-text-3)">Показано {{ filteredClients.length }} из {{ clients.length }}</span>
                    <VButton variant="ghost" size="xs" class="ml-auto">Показать ещё</VButton>
                </template>
            </VCard>
        </template>

        <!-- ===== SEGMENTS ===== -->
        <template v-if="activeTab === 'segments'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="seg in segments" :key="seg.key"
                     class="p-5 rounded-xl border border-(--t-border) bg-(--t-surface) hover:shadow-lg hover:shadow-(--t-glow) transition-all cursor-pointer active:scale-[0.98]"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl" :style="{backgroundColor: seg.color + '20'}">{{ seg.icon }}</div>
                            <div>
                                <div class="text-sm font-bold text-(--t-text)">{{ seg.name }}</div>
                                <div class="text-[10px] text-(--t-text-3)">{{ seg.criteria }}</div>
                            </div>
                        </div>
                        <div class="text-lg font-bold text-(--t-text)">{{ seg.count }}</div>
                    </div>

                    <!-- Churn risk bar -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-[10px]">
                            <span class="text-(--t-text-3)">Риск оттока</span>
                            <span :class="seg.churn > 50 ? 'text-red-400' : seg.churn > 20 ? 'text-yellow-400' : 'text-emerald-400'">{{ seg.churn }}%</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-(--t-border) overflow-hidden">
                            <div class="h-full rounded-full transition-all" :class="seg.churn > 50 ? 'bg-red-400' : seg.churn > 20 ? 'bg-yellow-400' : 'bg-emerald-400'" :style="{width: seg.churn + '%'}" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Segment funnel -->
            <VCard title="📊 Воронка конверсии клиентов" class="mt-6">
                <div class="flex flex-col items-center gap-1.5 py-4">
                    <div v-for="(step, i) in [{label:'Посетители',val:28500,w:'100%'},{label:'Регистрация',val:4287,w:'72%'},{label:'Первый заказ',val:2860,w:'55%'},{label:'Повторный заказ',val:1426,w:'38%'},{label:'Лояльный клиент',val:566,w:'24%'}]"
                         :key="i"
                         class="w-full"
                    >
                        <div class="flex items-center justify-between px-4 py-2.5 rounded-xl bg-linear-to-r from-(--t-primary-dim) to-transparent transition-all"
                             :style="{width: step.w, margin: '0 auto'}">
                            <span class="text-xs font-medium text-(--t-text)">{{ step.label }}</span>
                            <span class="text-xs font-bold text-(--t-primary)">{{ step.val.toLocaleString() }}</span>
                        </div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- ===== LOYALTY ===== -->
        <template v-if="activeTab === 'loyalty'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="tier in loyaltyTiers" :key="tier.name"
                     :class="['p-5 rounded-xl border bg-linear-to-br transition-all hover:scale-[1.02] cursor-pointer', tier.border, tier.color]"
                >
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-2xl">{{ tier.icon }}</span>
                        <span class="text-lg font-bold text-(--t-text)">{{ tier.name }}</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-(--t-text-3)">Мин. расход</span>
                            <span class="text-(--t-text)">{{ tier.minSpend.toLocaleString() }} ₽</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-(--t-text-3)">Кэшбэк</span>
                            <span class="font-bold text-emerald-400">{{ tier.cashback }}%</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-(--t-text-3)">Клиентов</span>
                            <span class="font-bold text-(--t-primary)">{{ tier.clients.toLocaleString() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bonus rules -->
            <VCard title="🎁 Правила начисления бонусов" class="mt-6">
                <div class="space-y-3">
                    <div v-for="rule in [{event:'Покупка',bonus:'3–10% от суммы',icon:'🛒'},{event:'День рождения',bonus:'500 баллов',icon:'🎂'},{event:'Реферал',bonus:'1 000 баллов',icon:'🤝'},{event:'Отзыв с фото',bonus:'200 баллов',icon:'📸'},{event:'AI-конструктор',bonus:'100 баллов',icon:'🤖'},{event:'Повторный заказ (7 дней)',bonus:'×1.5 множитель',icon:'🔄'}]"
                         :key="rule.event"
                         class="flex items-center gap-3 p-3 rounded-xl bg-(--t-card-hover)"
                    >
                        <div class="w-9 h-9 rounded-lg bg-(--t-primary-dim) flex items-center justify-center text-lg shrink-0">{{ rule.icon }}</div>
                        <div class="flex-1">
                            <div class="text-sm text-(--t-text)">{{ rule.event }}</div>
                        </div>
                        <span class="text-xs font-bold text-emerald-400">{{ rule.bonus }}</span>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- ===== TAGS ===== -->
        <template v-if="activeTab === 'tags'">
            <VCard title="🏷️ Управление тегами" subtitle="Автоматические и ручные теги для сегментации">
                <template #header-action>
                    <VButton variant="primary" size="sm" @click="showAddTag = true">➕ Создать тег</VButton>
                </template>
                <div class="flex flex-wrap gap-2">
                    <div v-for="tag in allTags" :key="tag.name"
                         class="flex items-center gap-2 px-3 py-2 rounded-xl border border-(--t-border) hover:border-(--t-primary)/20 hover:bg-(--t-card-hover) transition-all cursor-pointer active:scale-95"
                    >
                        <div class="w-3 h-3 rounded-full" :style="{backgroundColor: tag.color}" />
                        <span class="text-sm text-(--t-text)">{{ tag.name }}</span>
                        <span class="text-[10px] text-(--t-text-3) bg-(--t-card-hover) px-1.5 py-0.5 rounded-md">{{ tag.count }}</span>
                    </div>
                </div>
            </VCard>

            <!-- Auto-tagging rules -->
            <VCard title="⚙️ Правила авто-тегирования" class="mt-4">
                <div class="space-y-2">
                    <div v-for="rule in [{condition:'LTV > 80 000 ₽',tag:'VIP',icon:'👑'},{condition:'Нет активности 21+ дней',tag:'Churn Risk',icon:'⚠️'},{condition:'Есть ИНН / business_card_id',tag:'B2B',icon:'🏢'},{condition:'Регистрация < 30 дней',tag:'New',icon:'🌱'},{condition:'5+ покупок в Beauty',tag:'Beauty',icon:'💄'}]"
                         :key="rule.tag"
                         class="flex items-center gap-3 p-3 rounded-xl bg-(--t-card-hover)"
                    >
                        <span class="text-lg">{{ rule.icon }}</span>
                        <div class="flex-1 text-sm text-(--t-text)">{{ rule.condition }}</div>
                        <span class="text-xs font-medium text-(--t-primary) bg-(--t-primary-dim) px-2 py-1 rounded-lg">→ {{ rule.tag }}</span>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- ===== INTERACTION HISTORY ===== -->
        <template v-if="activeTab === 'interactions'">
            <VCard title="📜 История взаимодействий" subtitle="Все действия клиентов в реальном времени">
                <div class="space-y-2">
                    <div v-for="item in interactionHistory" :key="item.id"
                         class="flex items-center gap-3 p-3 rounded-xl border border-(--t-border) hover:bg-(--t-card-hover) transition-all"
                    >
                        <div class="w-9 h-9 rounded-xl bg-(--t-card-hover) flex items-center justify-center text-lg shrink-0">{{ item.icon }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-(--t-text) truncate">
                                <span class="font-medium">{{ item.client }}</span>
                                <span class="text-(--t-text-3)"> — {{ item.desc }}</span>
                            </div>
                        </div>
                        <span class="text-[10px] text-(--t-text-3) shrink-0 whitespace-nowrap">{{ item.date }}</span>
                    </div>
                </div>
                <template #footer>
                    <VButton variant="ghost" size="xs">Загрузить ещё</VButton>
                </template>
            </VCard>
        </template>

        <!-- Client Detail Modal -->
        <VModal v-model="showClientDetail" :title="selectedClient?.name || 'Клиент'" size="lg">
            <template v-if="selectedClient">
                <div class="space-y-4">
                    <!-- Client header -->
                    <div class="flex items-center gap-4 p-4 rounded-xl bg-(--t-card-hover)">
                        <div class="w-14 h-14 rounded-xl bg-(--t-surface) flex items-center justify-center text-3xl">{{ selectedClient.avatar }}</div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-(--t-text)">{{ selectedClient.name }}</h3>
                                <VBadge :text="segmentLabels[selectedClient.segment]" :variant="segmentColors[selectedClient.segment]" size="xs" />
                            </div>
                            <div class="text-xs text-(--t-text-3)">{{ selectedClient.email }} • {{ selectedClient.phone }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-emerald-400">{{ formatLTV(selectedClient.ltv) }}</div>
                            <div class="text-[10px] text-(--t-text-3)">LTV</div>
                        </div>
                    </div>

                    <!-- Stats grid -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="p-3 rounded-xl bg-(--t-card-hover) text-center">
                            <div class="text-lg font-bold text-(--t-text)">{{ selectedClient.orders }}</div>
                            <div class="text-[9px] text-(--t-text-3)">Заказов</div>
                        </div>
                        <div class="p-3 rounded-xl bg-(--t-card-hover) text-center">
                            <div class="text-lg font-bold text-(--t-primary)">{{ selectedClient.tags.length }}</div>
                            <div class="text-[9px] text-(--t-text-3)">Тегов</div>
                        </div>
                        <div class="p-3 rounded-xl bg-(--t-card-hover) text-center">
                            <div class="text-lg font-bold text-(--t-text)">{{ selectedClient.lastVisit }}</div>
                            <div class="text-[9px] text-(--t-text-3)">Последний визит</div>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div>
                        <label class="text-xs text-(--t-text-2) mb-2 block">Теги</label>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="tag in selectedClient.tags" :key="tag"
                                  class="px-2 py-1 rounded-lg bg-(--t-primary-dim) text-xs text-(--t-primary)"
                            >{{ tag }}</span>
                            <button class="px-2 py-1 rounded-lg border border-dashed border-(--t-border) text-xs text-(--t-text-3) cursor-pointer hover:border-(--t-primary) transition-colors active:scale-95">+ Тег</button>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <VButton variant="ghost" @click="showClientDetail = false">Закрыть</VButton>
                <VButton variant="primary">💬 Написать</VButton>
            </template>
        </VModal>

        <!-- Add Tag Modal -->
        <VModal v-model="showAddTag" title="Создать тег" size="sm">
            <div class="space-y-4">
                <VInput label="Название тега" placeholder="Новый тег" required />
                <div>
                    <label class="text-xs text-(--t-text-2) mb-2 block">Цвет</label>
                    <div class="flex gap-2">
                        <div v-for="c in ['#ef4444','#f59e0b','#22c55e','#3b82f6','#8b5cf6','#ec4899','#06b6d4']" :key="c"
                             class="w-8 h-8 rounded-lg cursor-pointer hover:scale-110 transition-transform border-2 border-transparent hover:border-white/30 active:scale-95"
                             :style="{backgroundColor: c}"
                        />
                    </div>
                </div>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showAddTag = false">Отмена</VButton>
                <VButton variant="primary">Создать</VButton>
            </template>
        </VModal>
    </div>
</template>
