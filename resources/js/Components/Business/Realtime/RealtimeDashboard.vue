<script setup>
import { ref, computed } from 'vue';

const activeTab = ref('connections');
const tabs = [
    { key: 'connections', label: 'Соединения' },
    { key: 'channels', label: 'Каналы' },
    { key: 'events', label: 'События' },
];

const metrics = ref([
    { label: 'Активных соединений', value: '1 247', trend: '+45', icon: '🔌' },
    { label: 'Каналов активных', value: '12', trend: '+2', icon: '📡' },
    { label: 'Событий/сек', value: '847', trend: '+123', icon: '⚡' },
    { label: 'Avg latency', value: '12ms', trend: '-2ms', icon: '📊' },
]);

const connections = ref([
    { id: 1, userId: 1001, socketId: 'sock_001', channel: 'delivery.12345', connectedAt: '2026-04-15 10:30', lastPing: '2026-04-15 10:45', status: 'connected' },
    { id: 2, userId: 1002, socketId: 'sock_002', channel: 'courier.67890', connectedAt: '2026-04-15 09:15', lastPing: '2026-04-15 10:44', status: 'connected' },
    { id: 3, userId: 1003, socketId: 'sock_003', channel: 'tenant.42', connectedAt: '2026-04-14 16:45', lastPing: '2026-04-15 10:43', status: 'connected' },
    { id: 4, userId: 1004, socketId: 'sock_004', channel: 'delivery.12346', connectedAt: '2026-04-14 11:20', lastPing: '2026-04-15 10:42', status: 'idle' },
]);

const channels = ref([
    { id: 'delivery', name: 'Delivery Tracking', pattern: 'delivery.{id}', subscribers: 456, messagesToday: 12450, avgLatency: 8 },
    { id: 'courier', name: 'Courier Location', pattern: 'courier.{id}.location', subscribers: 234, messagesToday: 8920, avgLatency: 12 },
    { id: 'tenant', name: 'Tenant Updates', pattern: 'tenant.{id}', subscribers: 567, messagesToday: 15670, avgLatency: 15 },
    { id: 'orders', name: 'Order Updates', pattern: 'orders.{id}', subscribers: 345, messagesToday: 9876, avgLatency: 10 },
]);

const events = ref([
    { id: 1, channel: 'delivery.12345', event: 'location.updated', payload: { lat: 55.75, lon: 37.62 }, publishedAt: '2026-04-15 10:45:12', subscribers: 5 },
    { id: 2, channel: 'courier.67890', event: 'status.changed', payload: { status: 'in_transit' }, publishedAt: '2026-04-15 10:45:10', subscribers: 3 },
    { id: 3, channel: 'tenant.42', event: 'order.created', payload: { orderId: 1001 }, publishedAt: '2026-04-15 10:45:08', subscribers: 12 },
    { id: 4, channel: 'orders.1001', event: 'payment.captured', payload: { amount: 15000 }, publishedAt: '2026-04-15 10:45:05', subscribers: 2 },
]);

const statusLabels = {
    connected: { label: 'Подключен', color: 'text-green-600', bg: 'bg-green-50' },
    idle: { label: 'Бездействует', color: 'text-yellow-600', bg: 'bg-yellow-50' },
    disconnected: { label: 'Отключен', color: 'text-gray-600', bg: 'bg-gray-50' },
    error: { label: 'Ошибка', color: 'text-red-600', bg: 'bg-red-50' },
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

        <!-- Connections Tab -->
        <div v-if="activeTab === 'connections'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Пользователь</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Socket ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Канал</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Подключен</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Последний ping</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="conn in connections" :key="conn.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ conn.id }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ conn.userId }}</td>
                        <td class="px-4 py-3 font-mono text-xs" style="color: var(--t-text-2);">{{ conn.socketId }}</td>
                        <td class="px-4 py-3 font-mono text-xs" style="color: var(--t-text-2);">{{ conn.channel }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ conn.connectedAt }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ conn.lastPing }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusLabels[conn.status]">
                                {{ statusLabels[conn.status].label }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Channels Tab -->
        <div v-if="activeTab === 'channels'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Название</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Паттерн</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Подписчиков</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Сообщений сегодня</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Avg latency</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="channel in channels" :key="channel.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3 font-medium" style="color: var(--t-text);">{{ channel.id }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ channel.name }}</td>
                        <td class="px-4 py-3 font-mono text-xs" style="color: var(--t-text-2);">{{ channel.pattern }}</td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-text);">{{ channel.subscribers.toLocaleString() }}</td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-text);">{{ channel.messagesToday.toLocaleString() }}</td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-text);">{{ channel.avgLatency }}ms</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Events Tab -->
        <div v-if="activeTab === 'events'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Канал</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Событие</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Payload</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Подписчиков</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Опубликовано</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="event in events" :key="event.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ event.id }}</td>
                        <td class="px-4 py-3 font-mono text-xs" style="color: var(--t-text-2);">{{ event.channel }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ event.event }}</td>
                        <td class="px-4 py-3 font-mono text-xs" style="color: var(--t-text-2);">{{ JSON.stringify(event.payload) }}</td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-text);">{{ event.subscribers }}</td>
                        <td class="px-4 py-3 font-mono text-xs" style="color: var(--t-text-2);">{{ event.publishedAt }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
