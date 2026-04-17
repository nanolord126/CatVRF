<script setup>
import { ref, computed } from 'vue';

const activeTab = ref('overview');
const tabs = [
    { key: 'overview', label: 'Обзор' },
    { key: 'events', label: 'События' },
    { key: 'alerts', label: 'Оповещения' },
    { key: 'audit', label: 'Аудит' },
];

const metrics = ref([
    { label: 'Security Score', value: '92/100', trend: '+2', icon: '🛡️' },
    { label: 'Блокировок сегодня', value: '127', trend: '+15', icon: '🚫' },
    { label: 'Активных угроз', value: '3', trend: '-1', icon: '⚠️' },
    { label: 'Аудит записей', value: '2 456', trend: '+89', icon: '📋' },
]);

const securityEvents = ref([
    { id: 1, type: 'rate_limit', userId: 1001, ip: '192.168.1.100', severity: 'low', description: 'Rate limit exceeded', createdAt: '2026-04-15 10:30', status: 'blocked' },
    { id: 2, type: 'fraud_score', userId: 1002, ip: '192.168.1.101', severity: 'high', description: 'Fraud score > 0.65', createdAt: '2026-04-15 09:15', status: 'flagged' },
    { id: 3, type: 'auth_failure', userId: null, ip: '192.168.1.102', severity: 'medium', description: 'Multiple auth failures', createdAt: '2026-04-14 16:45', status: 'blocked' },
    { id: 4, type: 'api_abuse', userId: 1003, ip: '192.168.1.103', severity: 'medium', description: 'API rate limit exceeded', createdAt: '2026-04-14 11:20', status: 'blocked' },
    { id: 5, type: 'suspicious_activity', userId: 1004, ip: '192.168.1.104', severity: 'high', description: 'Unusual payment pattern', createdAt: '2026-04-13 14:00', status: 'investigating' },
]);

const alerts = ref([
    { id: 1, type: 'critical', title: 'Массовая атака на API', description: 'Обнаружено 500+ запросов с одного IP', affectedUsers: 0, createdAt: '2026-04-15 10:30', status: 'active' },
    { id: 2, type: 'warning', title: 'Высокий fraud score', description: 'Пользователь #1002 имеет fraud score 0.72', affectedUsers: 1, createdAt: '2026-04-15 09:15', status: 'investigating' },
    { id: 3, type: 'info', title: 'Обновление правил безопасности', description: 'Правила rate limit обновлены', affectedUsers: 0, createdAt: '2026-04-14 16:00', status: 'resolved' },
]);

const auditLogs = ref([
    { id: 1, action: 'payment_initiated', userId: 1001, details: 'Order #1001', createdAt: '2026-04-15 10:30', ip: '192.168.1.100' },
    { id: 2, action: 'wallet_debit', userId: 1002, details: 'Payout #500', createdAt: '2026-04-15 09:15', ip: '192.168.1.101' },
    { id: 3, action: 'user_login', userId: 1003, details: 'Successful login', createdAt: '2026-04-14 16:45', ip: '192.168.1.102' },
    { id: 4, action: 'fraud_check', userId: 1004, details: 'Score: 0.72', createdAt: '2026-04-14 11:20', ip: '192.168.1.103' },
]);

const severityLabels = {
    low: { label: 'Низкий', color: 'text-green-600', bg: 'bg-green-50' },
    medium: { label: 'Средний', color: 'text-yellow-600', bg: 'bg-yellow-50' },
    high: { label: 'Высокий', color: 'text-red-600', bg: 'bg-red-50' },
    critical: { label: 'Критический', color: 'text-red-700', bg: 'bg-red-100' },
};

const alertTypeLabels = {
    critical: { label: 'Критический', color: 'text-red-600', bg: 'bg-red-50', icon: '🚨' },
    warning: { label: 'Предупреждение', color: 'text-yellow-600', bg: 'bg-yellow-50', icon: '⚠️' },
    info: { label: 'Информация', color: 'text-blue-600', bg: 'bg-blue-50', icon: 'ℹ️' },
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

        <!-- Overview Tab -->
        <div v-if="activeTab === 'overview'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-2xl border p-5" style="background: var(--t-surface); border-color: var(--t-border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--t-text);">Статус безопасности</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm" style="color: var(--t-text-2);">Firewall</span>
                        <span class="px-2 py-1 rounded-full text-xs font-medium" style="background: var(--t-primary-dim); color: var(--t-primary);">Активен</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm" style="color: var(--t-text-2);">Rate Limiting</span>
                        <span class="px-2 py-1 rounded-full text-xs font-medium" style="background: var(--t-primary-dim); color: var(--t-primary);">Активен</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm" style="color: var(--t-text-2);">Fraud Detection</span>
                        <span class="px-2 py-1 rounded-full text-xs font-medium" style="background: var(--t-primary-dim); color: var(--t-primary);">Активен</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm" style="color: var(--t-text-2);">Audit Logging</span>
                        <span class="px-2 py-1 rounded-full text-xs font-medium" style="background: var(--t-primary-dim); color: var(--t-primary);">Активен</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border p-5" style="background: var(--t-surface); border-color: var(--t-border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--t-text);">Активные оповещения</h3>
                <div class="space-y-3">
                    <div v-for="alert in alerts.slice(0, 3)" :key="alert.id" class="p-3 rounded-xl" style="background: var(--t-surface-alt);">
                        <div class="flex items-center gap-2 mb-1">
                            <span>{{ alertTypeLabels[alert.type].icon }}</span>
                            <span class="text-sm font-medium" style="color: var(--t-text);">{{ alert.title }}</span>
                        </div>
                        <div class="text-xs" style="color: var(--t-text-2);">{{ alert.description }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events Tab -->
        <div v-if="activeTab === 'events'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Тип</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Пользователь</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">IP</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Описание</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Степень</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Статус</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="event in securityEvents" :key="event.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ event.id }}</td>
                        <td class="px-4 py-3 font-mono text-xs" style="color: var(--t-text-2);">{{ event.type }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ event.userId || '-' }}</td>
                        <td class="px-4 py-3 font-mono text-xs" style="color: var(--t-text-2);">{{ event.ip }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ event.description }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="severityLabels[event.severity]">
                                {{ severityLabels[event.severity].label }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="event.status === 'blocked' ? severityLabels.high : event.status === 'flagged' ? severityLabels.medium : severityLabels.low">
                                {{ event.status === 'blocked' ? 'Заблокирован' : event.status === 'flagged' ? 'Помечен' : 'Расследование' }}
                            </span>
                        </td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ event.createdAt }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Alerts Tab -->
        <div v-if="activeTab === 'alerts'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Тип</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Название</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Описание</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Затронуто</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Статус</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="alert in alerts" :key="alert.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ alert.id }}</td>
                        <td class="px-4 py-3">
                            <span class="flex items-center gap-1" :class="alertTypeLabels[alert.type].color">
                                {{ alertTypeLabels[alert.type].icon }} {{ alertTypeLabels[alert.type].label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium" style="color: var(--t-text);">{{ alert.title }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ alert.description }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ alert.affectedUsers }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="alert.status === 'active' ? severityLabels.high : alert.status === 'investigating' ? severityLabels.medium : severityLabels.low">
                                {{ alert.status === 'active' ? 'Активен' : alert.status === 'investigating' ? 'Расследование' : 'Решён' }}
                            </span>
                        </td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ alert.createdAt }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Audit Tab -->
        <div v-if="activeTab === 'audit'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Действие</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Пользователь</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Детали</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">IP</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="log in auditLogs" :key="log.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ log.id }}</td>
                        <td class="px-4 py-3 font-mono text-xs" style="color: var(--t-text-2);">{{ log.action }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ log.userId }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ log.details }}</td>
                        <td class="px-4 py-3 font-mono text-xs" style="color: var(--t-text-2);">{{ log.ip }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ log.createdAt }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
