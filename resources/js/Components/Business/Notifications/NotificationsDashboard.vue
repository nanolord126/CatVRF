<script setup>
import { ref, computed } from 'vue';

const activeTab = ref('overview');
const tabs = [
    { key: 'overview', label: 'Обзор' },
    { key: 'channels', label: 'Каналы' },
    { key: 'templates', label: 'Шаблоны' },
    { key: 'history', label: 'История' },
];

const metrics = ref([
    { label: 'Отправлено сегодня', value: '12 456', trend: '+1 234', icon: '📤' },
    { label: 'Доставлено', value: '11 892', trend: '+1 156', icon: '✅' },
    { label: 'Failed rate', value: '4.5%', trend: '-0.2%', icon: '❌' },
    { label: 'Open rate', value: '68.2%', trend: '+2.1%', icon: '📧' },
]);

const channels = ref([
    { id: 'email', name: 'Email', enabled: true, sentToday: 5234, delivered: 5100, failed: 134, rate: 97.4 },
    { id: 'push', name: 'Push Notifications', enabled: true, sentToday: 4120, delivered: 3980, failed: 140, rate: 96.6 },
    { id: 'sms', name: 'SMS', enabled: true, sentToday: 2100, delivered: 2050, failed: 50, rate: 97.6 },
    { id: 'telegram', name: 'Telegram', enabled: false, sentToday: 0, delivered: 0, failed: 0, rate: 0 },
    { id: 'slack', name: 'Slack', enabled: true, sentToday: 1002, delivered: 762, failed: 240, rate: 76.0 },
]);

const templates = ref([
    { id: 1, name: 'Order Confirmation', channel: 'email', language: 'ru', usageCount: 4521, lastUsed: '2026-04-15 10:30' },
    { id: 2, name: 'Payment Success', channel: 'push', language: 'ru', usageCount: 3892, lastUsed: '2026-04-15 09:15' },
    { id: 3, name: 'Delivery Update', channel: 'sms', language: 'ru', usageCount: 2156, lastUsed: '2026-04-14 16:45' },
    { id: 4, name: 'Welcome Email', channel: 'email', language: 'en', usageCount: 1234, lastUsed: '2026-04-14 11:20' },
]);

const notifications = ref([
    { id: 1, userId: 1001, channel: 'email', template: 'Order Confirmation', status: 'delivered', sentAt: '2026-04-15 10:30', deliveredAt: '2026-04-15 10:31' },
    { id: 2, userId: 1002, channel: 'push', template: 'Payment Success', status: 'delivered', sentAt: '2026-04-15 09:15', deliveredAt: '2026-04-15 09:16' },
    { id: 3, userId: 1003, channel: 'sms', template: 'Delivery Update', status: 'failed', sentAt: '2026-04-14 16:45', deliveredAt: null },
    { id: 4, userId: 1004, channel: 'email', template: 'Welcome Email', status: 'delivered', sentAt: '2026-04-14 11:20', deliveredAt: '2026-04-14 11:21' },
]);

const statusLabels = {
    delivered: { label: 'Доставлено', color: 'text-green-600', bg: 'bg-green-50' },
    failed: { label: 'Ошибка', color: 'text-red-600', bg: 'bg-red-50' },
    pending: { label: 'В очереди', color: 'text-yellow-600', bg: 'bg-yellow-50' },
    sent: { label: 'Отправлено', color: 'text-blue-600', bg: 'bg-blue-50' },
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
                <h3 class="text-lg font-semibold mb-4" style="color: var(--t-text);">Статус каналов</h3>
                <div class="space-y-3">
                    <div v-for="channel in channels" :key="channel.id" class="flex items-center justify-between p-3 rounded-xl" style="background: var(--t-surface-alt);">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full" :style="channel.enabled ? 'background: var(--t-primary);' : 'background: var(--t-border);'"></div>
                            <span class="font-medium" style="color: var(--t-text);">{{ channel.name }}</span>
                        </div>
                        <div class="text-sm" style="color: var(--t-text-2);">
                            {{ channel.rate > 0 ? channel.rate + '% доставлено' : 'Отключен' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border p-5" style="background: var(--t-surface); border-color: var(--t-border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--t-text);">Популярные шаблоны</h3>
                <div class="space-y-3">
                    <div v-for="template in templates.slice(0, 4)" :key="template.id" class="flex items-center justify-between p-3 rounded-xl" style="background: var(--t-surface-alt);">
                        <div>
                            <div class="text-sm font-medium" style="color: var(--t-text);">{{ template.name }}</div>
                            <div class="text-xs" style="color: var(--t-text-2);">{{ template.channel }} · {{ template.language }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold" style="color: var(--t-text);">{{ template.usageCount }}</div>
                            <div class="text-xs" style="color: var(--t-text-3);">использований</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Channels Tab -->
        <div v-if="activeTab === 'channels'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Канал</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Статус</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Отправлено</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Доставлено</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Ошибки</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="channel in channels" :key="channel.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3 font-medium" style="color: var(--t-text);">{{ channel.name }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :style="channel.enabled ? 'background: var(--t-primary-dim); color: var(--t-primary);' : 'background: var(--t-border); color: var(--t-text-2);'">
                                {{ channel.enabled ? 'Активен' : 'Отключен' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-text);">{{ channel.sentToday.toLocaleString() }}</td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-primary);">{{ channel.delivered.toLocaleString() }}</td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-text-2);">{{ channel.failed.toLocaleString() }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-semibold" :style="channel.rate >= 95 ? 'color: var(--t-primary);' : 'color: #f59e0b;'">
                                {{ channel.rate }}%
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Templates Tab -->
        <div v-if="activeTab === 'templates'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Название</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Канал</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Язык</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Использований</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Последнее использование</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="template in templates" :key="template.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ template.id }}</td>
                        <td class="px-4 py-3 font-medium" style="color: var(--t-text);">{{ template.name }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ template.channel }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ template.language }}</td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-text);">{{ template.usageCount.toLocaleString() }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ template.lastUsed }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- History Tab -->
        <div v-if="activeTab === 'history'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Пользователь</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Канал</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Шаблон</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Статус</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Отправлено</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Доставлено</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="notif in notifications" :key="notif.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ notif.id }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ notif.userId }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ notif.channel }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ notif.template }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusLabels[notif.status]">
                                {{ statusLabels[notif.status].label }}
                            </span>
                        </td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ notif.sentAt }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ notif.deliveredAt || '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
