<script setup>
/**
 * IntegrationsPanel — управление интеграциями, API, вебхуками,
 * внешними сервисами. Полный CRUD для webhook-подписок.
 * Интеграция с B2BApiKeyService + SecurityMonitoringService.
 */
import { ref, computed } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VTabs from '../UI/VTabs.vue';
import VInput from '../UI/VInput.vue';
import VModal from '../UI/VModal.vue';
import VStatCard from '../UI/VStatCard.vue';

const activeTab = ref('services');
const tabs = [
    { key: 'services', label: 'Сервисы' },
    { key: 'api', label: 'API-ключи' },
    { key: 'webhooks', label: 'Вебхуки' },
    { key: 'logs', label: 'Логи' },
];

const showConnectService = ref(false);
const showCreateApiKey = ref(false);
const showAddWebhook = ref(false);
const showApiKeyDetail = ref(false);
const selectedService = ref(null);
const selectedApiKey = ref(null);

const integrations = ref([
    { id: 1, name: '1С:Предприятие', icon: '🔵', category: 'erp', status: 'connected', lastSync: '5 мин назад', description: 'Синхронизация номенклатуры, остатков и заказов', events: 12450 },
    { id: 2, name: 'Telegram Bot', icon: '✈️', category: 'messenger', status: 'connected', lastSync: '1 мин назад', description: 'Уведомления о заказах, фрод-алерты', events: 8920 },
    { id: 3, name: 'Yandex Maps', icon: '🗺️', category: 'geo', status: 'connected', lastSync: '3 мин назад', description: 'Геокодирование, маршруты доставки', events: 34200 },
    { id: 4, name: 'Tinkoff Pay', icon: '💳', category: 'payment', status: 'connected', lastSync: '30 сек назад', description: 'Приём платежей, холды, возвраты', events: 5670 },
    { id: 5, name: 'SMS.ru', icon: '📱', category: 'notification', status: 'connected', lastSync: '2 часа назад', description: 'SMS-уведомления клиентам', events: 1890 },
    { id: 6, name: 'Slack', icon: '💬', category: 'messenger', status: 'disconnected', lastSync: 'Не подключён', description: 'Уведомления команде в каналы', events: 0 },
    { id: 7, name: 'Google Analytics', icon: '📊', category: 'analytics', status: 'connected', lastSync: '10 мин назад', description: 'Аналитика трафика и конверсий', events: 45800 },
    { id: 8, name: 'OpenAI', icon: '🤖', category: 'ai', status: 'connected', lastSync: '1 мин назад', description: 'AI-конструкторы, Vision API, GPT-4o', events: 2340 },
    { id: 9, name: 'Firebase', icon: '🔥', category: 'notification', status: 'connected', lastSync: '5 мин назад', description: 'Push-уведомления (iOS/Android)', events: 7650 },
    { id: 10, name: 'Sber Pay', icon: '🟢', category: 'payment', status: 'inactive', lastSync: 'Неактивен', description: 'Альтернативный платёжный шлюз', events: 0 },
    { id: 11, name: 'ClickHouse', icon: '🏠', category: 'analytics', status: 'connected', lastSync: '1 мин назад', description: 'Аналитическое хранилище Big Data', events: 128900 },
    { id: 12, name: 'Redis', icon: '🔴', category: 'cache', status: 'connected', lastSync: 'Реал-тайм', description: 'Кэширование, очереди, сессии', events: 890000 },
]);

const apiKeys = ref([
    { id: 1, name: 'Интеграция с 1С', key: 'b2b_sk_live_4f7a9c...xz12', created: '2025-11-01', expires: '2026-11-01', permissions: ['orders.read', 'orders.write', 'stock.read', 'reports'], status: 'active', requests: 125400 },
    { id: 2, name: 'Мобильное приложение', key: 'b2b_sk_live_8d3e1b...qw78', created: '2026-01-15', expires: '2027-01-15', permissions: ['orders.read', 'products.read', 'stock.read'], status: 'active', requests: 89200 },
    { id: 3, name: 'Тестовый ключ', key: 'b2b_sk_test_1a2b3c...mn45', created: '2026-03-01', expires: '2026-06-01', permissions: ['orders.read'], status: 'expiring', requests: 340 },
]);

const webhooks = ref([
    { id: 1, url: 'https://erp.company.ru/api/webhook/orders', events: ['order.created', 'order.updated', 'order.cancelled'], status: 'active', lastTriggered: '2 мин назад', successRate: 99.8 },
    { id: 2, url: 'https://notify.company.ru/api/fraud-alerts', events: ['fraud.detected', 'fraud.blocked'], status: 'active', lastTriggered: '1 час назад', successRate: 100 },
    { id: 3, url: 'https://analytics.company.ru/api/events', events: ['payment.completed', 'delivery.completed'], status: 'active', lastTriggered: '5 мин назад', successRate: 98.5 },
    { id: 4, url: 'https://old-system.company.ru/hook', events: ['order.created'], status: 'paused', lastTriggered: '30 дней назад', successRate: 87.2 },
]);

const apiLogs = ref([
    { id: 1, method: 'GET', endpoint: '/api/b2b/v1/products', status: 200, time: '45ms', source: '1С', date: '2 мин назад' },
    { id: 2, method: 'POST', endpoint: '/api/b2b/v1/orders', status: 201, time: '120ms', source: '1С', date: '5 мин назад' },
    { id: 3, method: 'GET', endpoint: '/api/b2b/v1/stock', status: 200, time: '38ms', source: 'Mobile App', date: '8 мин назад' },
    { id: 4, method: 'POST', endpoint: '/api/b2b/v1/orders/bulk', status: 422, time: '89ms', source: '1С', date: '15 мин назад' },
    { id: 5, method: 'GET', endpoint: '/api/b2b/v1/reports/turnover', status: 200, time: '250ms', source: 'Mobile App', date: '20 мин назад' },
    { id: 6, method: 'PATCH', endpoint: '/api/b2b/v1/products/142', status: 200, time: '67ms', source: '1С', date: '30 мин назад' },
    { id: 7, method: 'POST', endpoint: '/api/b2b/v1/orders/import', status: 500, time: '1200ms', source: '1С', date: '1 час назад' },
    { id: 8, method: 'GET', endpoint: '/api/b2b/v1/stock', status: 429, time: '12ms', source: 'Unknown', date: '2 часа назад' },
]);

const categoryIcons = { erp: '🔵', messenger: '💬', geo: '🗺️', payment: '💳', notification: '📱', analytics: '📊', ai: '🤖', cache: '⚡' };
const statusColors = { connected: 'success', disconnected: 'danger', inactive: 'secondary', active: 'success', paused: 'warning', expiring: 'warning' };
const statusLabels = { connected: 'Подключён', disconnected: 'Отключён', inactive: 'Неактивен', active: 'Активен', paused: 'Приостановлен', expiring: 'Истекает' };
const httpMethodColors = { GET: 'text-emerald-400', POST: 'text-blue-400', PATCH: 'text-yellow-400', PUT: 'text-yellow-400', DELETE: 'text-red-400' };
const httpStatusColors = (s) => s < 300 ? 'text-emerald-400' : s < 400 ? 'text-yellow-400' : s < 500 ? 'text-orange-400' : 'text-red-400';

const connectedCount = computed(() => integrations.value.filter(i => i.status === 'connected').length);
const totalEvents = computed(() => integrations.value.reduce((sum, i) => sum + i.events, 0));

const allWebhookEvents = [
    'order.created', 'order.updated', 'order.cancelled', 'order.completed',
    'payment.completed', 'payment.refunded',
    'delivery.assigned', 'delivery.completed',
    'fraud.detected', 'fraud.blocked',
    'stock.low', 'stock.updated',
];

function openServiceDetail(svc) {
    selectedService.value = svc;
    showConnectService.value = true;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-linear-to-br from-(--t-primary-dim) to-(--t-card-hover) border border-(--t-primary)/20 flex items-center justify-center text-2xl shadow-lg shadow-(--t-glow)">
                    🔌
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-bold text-(--t-text)">Интеграции</h1>
                    <p class="text-xs text-(--t-text-3)">Внешние сервисы, API, вебхуки и логи</p>
                </div>
            </div>
        </div>

        <!-- Metrics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <VStatCard title="Подключено" :value="connectedCount + '/' + integrations.length" icon="🔗" />
            <VStatCard title="API-ключей" :value="String(apiKeys.length)" icon="🔑" />
            <VStatCard title="Вебхуков" :value="String(webhooks.length)" icon="🪝" />
            <VStatCard title="Событий / мес" :value="(totalEvents / 1000).toFixed(0) + 'k'" icon="📡" />
        </div>

        <!-- Tabs -->
        <VTabs :tabs="tabs" v-model="activeTab" variant="underline" />

        <!-- ===== SERVICES ===== -->
        <template v-if="activeTab === 'services'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="svc in integrations" :key="svc.id"
                     @click="openServiceDetail(svc)"
                     :class="['p-4 rounded-xl border bg-(--t-surface) transition-all cursor-pointer active:scale-[0.98]',
                              svc.status === 'connected' ? 'border-emerald-500/20 hover:shadow-lg hover:shadow-emerald-500/5' :
                              svc.status === 'disconnected' ? 'border-red-500/20 opacity-60 hover:opacity-100' :
                              'border-(--t-border) opacity-60 hover:opacity-100']"
                >
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-(--t-card-hover) flex items-center justify-center text-xl">{{ svc.icon }}</div>
                            <div>
                                <div class="text-sm font-bold text-(--t-text)">{{ svc.name }}</div>
                                <div class="text-[10px] text-(--t-text-3)">{{ svc.lastSync }}</div>
                            </div>
                        </div>
                        <VBadge :text="statusLabels[svc.status]" :variant="statusColors[svc.status]" size="xs" />
                    </div>
                    <p class="text-xs text-(--t-text-3) mb-3">{{ svc.description }}</p>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-(--t-text-3)">{{ svc.events.toLocaleString() }} событий</span>
                        <VButton v-if="svc.status === 'disconnected'" variant="primary" size="xs">Подключить</VButton>
                        <VButton v-else-if="svc.status === 'connected'" variant="ghost" size="xs">⚙️</VButton>
                    </div>
                </div>
            </div>
        </template>

        <!-- ===== API KEYS ===== -->
        <template v-if="activeTab === 'api'">
            <VCard title="🔑 API-ключи" subtitle="Управление доступом к B2B API">
                <template #header-action>
                    <VButton variant="primary" size="sm" @click="showCreateApiKey = true">➕ Создать ключ</VButton>
                </template>
                <div class="space-y-3">
                    <div v-for="key in apiKeys" :key="key.id"
                         class="p-4 rounded-xl border border-(--t-border) hover:bg-(--t-card-hover) transition-all cursor-pointer active:scale-[0.99]"
                         @click="selectedApiKey = key; showApiKeyDetail = true"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-(--t-primary-dim) flex items-center justify-center text-lg">🔑</div>
                                <div>
                                    <div class="text-sm font-bold text-(--t-text)">{{ key.name }}</div>
                                    <code class="text-[10px] text-(--t-text-3) font-mono">{{ key.key }}</code>
                                </div>
                            </div>
                            <VBadge :text="statusLabels[key.status]" :variant="statusColors[key.status]" size="xs" />
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span v-for="p in key.permissions" :key="p"
                                      class="px-1.5 py-0.5 rounded bg-(--t-card-hover) text-[9px] text-(--t-text-3)">{{ p }}</span>
                            </div>
                            <span class="text-[10px] text-(--t-text-3)">{{ key.requests.toLocaleString() }} запросов</span>
                        </div>
                        <div class="flex items-center justify-between mt-2 text-[10px] text-(--t-text-3)">
                            <span>Создан: {{ key.created }}</span>
                            <span>Истекает: {{ key.expires }}</span>
                        </div>
                    </div>
                </div>
            </VCard>

            <!-- API Docs quick ref -->
            <VCard title="📖 Быстрая справка API" class="mt-4">
                <div class="space-y-2">
                    <div v-for="ep in [{method:'GET',path:'/api/b2b/v1/products',desc:'Список товаров'},{method:'POST',path:'/api/b2b/v1/orders',desc:'Создать заказ'},{method:'GET',path:'/api/b2b/v1/stock',desc:'Остатки на складах'},{method:'POST',path:'/api/b2b/v1/orders/bulk',desc:'Массовое создание заказов'},{method:'POST',path:'/api/b2b/v1/orders/import',desc:'Импорт из Excel'},{method:'GET',path:'/api/b2b/v1/reports/turnover',desc:'Отчёт по обороту'}]" :key="ep.path"
                         class="flex items-center gap-3 p-2.5 rounded-lg bg-(--t-card-hover)"
                    >
                        <code :class="['text-xs font-mono font-bold w-12', httpMethodColors[ep.method]]">{{ ep.method }}</code>
                        <code class="text-xs font-mono text-(--t-text) flex-1">{{ ep.path }}</code>
                        <span class="text-[10px] text-(--t-text-3)">{{ ep.desc }}</span>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- ===== WEBHOOKS ===== -->
        <template v-if="activeTab === 'webhooks'">
            <VCard title="🪝 Вебхуки" subtitle="Подписки на события платформы">
                <template #header-action>
                    <VButton variant="primary" size="sm" @click="showAddWebhook = true">➕ Добавить вебхук</VButton>
                </template>
                <div class="space-y-3">
                    <div v-for="hook in webhooks" :key="hook.id"
                         class="p-4 rounded-xl border border-(--t-border) hover:bg-(--t-card-hover) transition-all"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">🪝</span>
                                <code class="text-xs font-mono text-(--t-primary) break-all">{{ hook.url }}</code>
                            </div>
                            <VBadge :text="statusLabels[hook.status]" :variant="statusColors[hook.status]" size="xs" />
                        </div>
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            <span v-for="ev in hook.events" :key="ev"
                                  class="px-2 py-0.5 rounded-lg bg-(--t-primary-dim) text-[9px] text-(--t-primary)">{{ ev }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-(--t-text-3)">
                            <span>Последний вызов: {{ hook.lastTriggered }}</span>
                            <span :class="hook.successRate >= 99 ? 'text-emerald-400' : hook.successRate >= 95 ? 'text-yellow-400' : 'text-red-400'">
                                Успешность: {{ hook.successRate }}%
                            </span>
                        </div>
                        <div class="mt-2 h-1.5 rounded-full bg-(--t-border) overflow-hidden">
                            <div class="h-full rounded-full transition-all" :class="hook.successRate >= 99 ? 'bg-emerald-400' : hook.successRate >= 95 ? 'bg-yellow-400' : 'bg-red-400'"
                                 :style="{width: hook.successRate + '%'}" />
                        </div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- ===== API LOGS ===== -->
        <template v-if="activeTab === 'logs'">
            <VCard title="📋 Логи API-запросов" subtitle="Последние запросы к B2B API">
                <div class="space-y-1.5">
                    <div v-for="log in apiLogs" :key="log.id"
                         class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-(--t-card-hover) transition-colors font-mono text-xs"
                    >
                        <code :class="['font-bold w-12 shrink-0', httpMethodColors[log.method]]">{{ log.method }}</code>
                        <code class="text-(--t-text) flex-1 truncate">{{ log.endpoint }}</code>
                        <code :class="['font-bold shrink-0', httpStatusColors(log.status)]">{{ log.status }}</code>
                        <span class="text-(--t-text-3) shrink-0 w-16 text-right">{{ log.time }}</span>
                        <span class="text-(--t-text-3) shrink-0 w-24 text-right hidden sm:block">{{ log.source }}</span>
                        <span class="text-(--t-text-3) shrink-0 w-20 text-right hidden md:block">{{ log.date }}</span>
                    </div>
                </div>
                <template #footer>
                    <span class="text-xs text-(--t-text-3)">Последние 8 запросов</span>
                    <VButton variant="ghost" size="xs" class="ml-auto">Все логи</VButton>
                </template>
            </VCard>
        </template>

        <!-- Connect Service Modal -->
        <VModal v-model="showConnectService" :title="selectedService?.name || 'Настройка сервиса'" size="md">
            <template v-if="selectedService">
                <div class="space-y-4">
                    <div class="flex items-center gap-4 p-4 rounded-xl bg-(--t-card-hover)">
                        <div class="w-14 h-14 rounded-xl bg-(--t-surface) flex items-center justify-center text-3xl">{{ selectedService.icon }}</div>
                        <div>
                            <h3 class="text-lg font-bold text-(--t-text)">{{ selectedService.name }}</h3>
                            <p class="text-xs text-(--t-text-3)">{{ selectedService.description }}</p>
                        </div>
                    </div>
                    <VInput label="API Key / Token" placeholder="Вставьте ключ доступа" />
                    <VInput label="Webhook URL (опционально)" placeholder="https://..." />
                </div>
            </template>
            <template #footer>
                <VButton variant="secondary" @click="showConnectService = false">Закрыть</VButton>
                <VButton variant="primary">💾 Сохранить</VButton>
            </template>
        </VModal>

        <!-- Create API Key Modal -->
        <VModal v-model="showCreateApiKey" title="Создать API-ключ" size="md">
            <div class="space-y-4">
                <VInput label="Название ключа" placeholder="Интеграция с 1С" required />
                <div>
                    <label class="text-xs text-(--t-text-2) mb-2 block">Права доступа</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label v-for="perm in ['orders.read','orders.write','products.read','products.write','stock.read','stock.write','reports','sync']" :key="perm"
                               class="flex items-center gap-2 p-2.5 rounded-xl border border-(--t-border) hover:bg-(--t-card-hover) transition-all cursor-pointer"
                        >
                            <input type="checkbox" class="rounded text-(--t-primary)" />
                            <span class="text-xs text-(--t-text)">{{ perm }}</span>
                        </label>
                    </div>
                </div>
                <VInput label="Срок действия (дней)" type="number" model-value="365" />
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showCreateApiKey = false">Отмена</VButton>
                <VButton variant="primary">🔑 Создать ключ</VButton>
            </template>
        </VModal>

        <!-- Add Webhook Modal -->
        <VModal v-model="showAddWebhook" title="Добавить вебхук" size="md">
            <div class="space-y-4">
                <VInput label="URL обработчика" placeholder="https://your-server.com/api/webhook" required prefix-icon="🔗" />
                <div>
                    <label class="text-xs text-(--t-text-2) mb-2 block">Подписка на события</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label v-for="ev in allWebhookEvents" :key="ev"
                               class="flex items-center gap-2 p-2 rounded-lg border border-(--t-border) hover:bg-(--t-card-hover) transition-all cursor-pointer"
                        >
                            <input type="checkbox" class="rounded text-(--t-primary)" />
                            <span class="text-xs text-(--t-text)">{{ ev }}</span>
                        </label>
                    </div>
                </div>
                <VInput label="Секретный ключ (HMAC)" placeholder="Автоматически сгенерирован" />
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showAddWebhook = false">Отмена</VButton>
                <VButton variant="primary">🪝 Создать вебхук</VButton>
            </template>
        </VModal>

        <!-- API Key Detail Modal -->
        <VModal v-model="showApiKeyDetail" :title="selectedApiKey?.name || 'API-ключ'" size="md">
            <template v-if="selectedApiKey">
                <div class="space-y-4">
                    <div class="p-4 rounded-xl bg-(--t-card-hover)">
                        <label class="text-[10px] text-(--t-text-3) block mb-1">Ключ</label>
                        <div class="flex items-center gap-2">
                            <code class="text-sm font-mono text-(--t-primary) flex-1">{{ selectedApiKey.key }}</code>
                            <VButton variant="ghost" size="xs">📋</VButton>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-xl bg-(--t-card-hover) text-center">
                            <div class="text-lg font-bold text-(--t-text)">{{ selectedApiKey.requests.toLocaleString() }}</div>
                            <div class="text-[9px] text-(--t-text-3)">Запросов</div>
                        </div>
                        <div class="p-3 rounded-xl bg-(--t-card-hover) text-center">
                            <div class="text-lg font-bold text-(--t-text)">{{ selectedApiKey.permissions.length }}</div>
                            <div class="text-[9px] text-(--t-text-3)">Прав</div>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-(--t-text-2) mb-2 block">Права доступа</label>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="p in selectedApiKey.permissions" :key="p"
                                  class="px-2 py-1 rounded-lg bg-(--t-primary-dim) text-xs text-(--t-primary)">{{ p }}</span>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <VButton variant="danger" size="sm">🗑️ Удалить</VButton>
                <VButton variant="secondary" @click="showApiKeyDetail = false">Закрыть</VButton>
            </template>
        </VModal>
    </div>
</template>
