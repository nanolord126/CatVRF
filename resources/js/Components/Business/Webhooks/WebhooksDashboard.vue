<script setup>
import { ref, computed } from 'vue';

const activeTab = ref('endpoints');
const tabs = [
    { key: 'endpoints', label: 'Эндпоинты' },
    { key: 'deliveries', label: 'Доставки' },
    { key: 'logs', label: 'Логи' },
];

const metrics = ref([
    { label: 'Активных эндпоинтов', value: '24', trend: '+3', icon: '🔗' },
    { label: 'Доставлено сегодня', value: '1 847', trend: '+234', icon: '✅' },
    { label: 'Failed rate', value: '2.3%', trend: '-0.5%', icon: '❌' },
    { label: 'Avg latency', value: '245ms', trend: '-12ms', icon: '⚡' },
]);

const endpoints = ref([
    { id: 1, name: 'Payment Webhook', url: 'https://api.example.com/webhooks/payment', events: ['payment.captured', 'payment.failed'], secret: 'whsec_***', status: 'active', lastTriggered: '2026-04-15 10:30', deliveryCount: 4521 },
    { id: 2, name: 'Order Webhook', url: 'https://api.example.com/webhooks/order', events: ['order.created', 'order.updated'], secret: 'whsec_***', status: 'active', lastTriggered: '2026-04-15 09:15', deliveryCount: 3892 },
    { id: 3, name: 'User Webhook', url: 'https://api.example.com/webhooks/user', events: ['user.created', 'user.updated'], secret: 'whsec_***', status: 'paused', lastTriggered: '2026-04-14 16:45', deliveryCount: 2156 },
    { id: 4, name: 'Fraud Webhook', url: 'https://api.example.com/webhooks/fraud', events: ['fraud.detected', 'fraud.blocked'], secret: 'whsec_***', status: 'active', lastTriggered: '2026-04-14 11:20', deliveryCount: 1234 },
]);

const deliveries = ref([
    { id: 1, endpointId: 1, eventType: 'payment.captured', status: 'delivered', attempts: 1, responseCode: 200, latency: 145, sentAt: '2026-04-15 10:30', deliveredAt: '2026-04-15 10:30' },
    { id: 2, endpointId: 2, eventType: 'order.created', status: 'delivered', attempts: 1, responseCode: 200, latency: 89, sentAt: '2026-04-15 09:15', deliveredAt: '2026-04-15 09:15' },
    { id: 3, endpointId: 3, eventType: 'user.updated', status: 'failed', attempts: 3, responseCode: 503, latency: 5123, sentAt: '2026-04-14 16:45', deliveredAt: null },
    { id: 4, endpointId: 4, eventType: 'fraud.detected', status: 'delivered', attempts: 2, responseCode: 200, latency: 234, sentAt: '2026-04-14 11:20', deliveredAt: '2026-04-14 11:20' },
]);

const logs = ref([
    { id: 1, endpointId: 1, level: 'info', message: 'Webhook delivered successfully', timestamp: '2026-04-15 10:30:15' },
    { id: 2, endpointId: 2, level: 'info', message: 'Webhook delivered successfully', timestamp: '2026-04-15 09:15:22' },
    { id: 3, endpointId: 3, level: 'error', message: 'Webhook delivery failed after 3 attempts', timestamp: '2026-04-14 16:45:33' },
    { id: 4, endpointId: 4, level: 'warning', message: 'First attempt failed, retrying', timestamp: '2026-04-14 11:20:10' },
]);

const statusLabels = {
    active: { label: 'Активен', color: 'text-green-600', bg: 'bg-green-50' },
    paused: { label: 'Пауза', color: 'text-yellow-600', bg: 'bg-yellow-50' },
    disabled: { label: 'Отключен', color: 'text-gray-600', bg: 'bg-gray-50' },
    delivered: { label: 'Доставлено', color: 'text-green-600', bg: 'bg-green-50' },
    failed: { label: 'Ошибка', color: 'text-red-600', bg: 'bg-red-50' },
    pending: { label: 'В очереди', color: 'text-blue-600', bg: 'bg-blue-50' },
};

const logLevelLabels = {
    info: { label: 'INFO', color: 'text-blue-600', bg: 'bg-blue-50' },
    warning: { label: 'WARN', color: 'text-yellow-600', bg: 'bg-yellow-50' },
    error: { label: 'ERROR', color: 'text-red-600', bg: 'bg-red-50' },
};
</script>

<template>
    <section class="space-y-4">
        <!-- Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
            <article v-for="item in metrics" :key="item.label" class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
                <div class="text-xs" style="color: var(--t-text-3);">{{ item.label }}</div>
                <div class="mt-2 flex items-end justify-between">
                    <div class="text-xl font-bold" style="color: var(--t-text);">{{ item.value }}</div>
                    <div class="text-xs font-semibold" style="color: var(--t-primary);">{{ item.trend }}</div>
                </div>
                <div class="mt-1 text-xl">{{ item.icon }}</div>
            </article>
        </div>

        <!-- Tabs -->
        <div class="flex flex-wrap gap-2">
            <button 
                v-for="tab in tabs" 
                :key="tab.key" 
                class="px-3 py-1.5 rounded-xl border text-sm cursor-pointer"
                :style="activeTab === tab.key
                    ? 'border-color: var(--t-primary); color: var(--t-primary); background: var(--t-primary-dim);'
                    : 'border-color: var(--t-border); color: var(--t-text-2); background: var(--t-surface);'"
                @click="activeTab = tab.key"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Endpoints Tab -->
        <div v-if="activeTab === 'endpoints'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Название</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">URL</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">События</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Статус</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Доставок</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Последний триггер</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="endpoint in endpoints" :key="endpoint.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ endpoint.id }}</td>
                        <td class="px-4 py-3 font-medium" style="color: var(--t-text);">{{ endpoint.name }}</td>
                        <td class="px-4 py-3 font-mono text-xs" style="color: var(--t-text-2);">{{ endpoint.url }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <span v-for="event in endpoint.events.slice(0, 2)" :key="event" class="px-2 py-1 rounded-full text-xs" style="background: var(--t-surface-alt); color: var(--t-text-2);">
                                    {{ event }}
                                </span>
                                <span v-if="endpoint.events.length > 2" class="px-2 py-1 rounded-full text-xs" style="background: var(--t-surface-alt); color: var(--t-text-2);">
                                    +{{ endpoint.events.length - 2 }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusLabels[endpoint.status]">
                                {{ statusLabels[endpoint.status].label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-text);">{{ endpoint.deliveryCount.toLocaleString() }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ endpoint.lastTriggered }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Deliveries Tab -->
        <div v-if="activeTab === 'deliveries'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Эндпоинт</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Событие</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Статус</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Попыток</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Код ответа</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Latency</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Отправлено</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="delivery in deliveries" :key="delivery.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ delivery.id }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ delivery.endpointId }}</td>
                        <td class="px-4 py-3 font-mono text-xs" style="color: var(--t-text-2);">{{ delivery.eventType }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusLabels[delivery.status]">
                                {{ statusLabels[delivery.status].label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-text);">{{ delivery.attempts }}</td>
                        <td class="px-4 py-3 text-right" :style="delivery.responseCode >= 400 ? 'color: #ef4444;' : 'color: var(--t-primary);'">
                            {{ delivery.responseCode }}
                        </td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-text);">{{ delivery.latency }}ms</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ delivery.sentAt }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Logs Tab -->
        <div v-if="activeTab === 'logs'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Эндпоинт</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Уровень</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Сообщение</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Время</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="log in logs" :key="log.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ log.id }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ log.endpointId }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="logLevelLabels[log.level]">
                                {{ logLevelLabels[log.level].label }}
                            </span>
                        </td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ log.message }}</td>
                        <td class="px-4 py-3 font-mono text-xs" style="color: var(--t-text-2);">{{ log.timestamp }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
