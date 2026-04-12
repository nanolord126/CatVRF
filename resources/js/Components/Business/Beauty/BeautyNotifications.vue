<script setup>
/**
 * BeautyNotifications — центр уведомлений Beauty B2B.
 * 6 табов: обзор, напоминания, рассылки, шаблоны, история, настройки.
 * 5 тем (mint/day/night/sunset/lavender) через CSS custom properties.
 */
import { ref, computed, inject } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VStatCard from '../../UI/VStatCard.vue';
import VButton from '../../UI/VButton.vue';
import VBadge from '../../UI/VBadge.vue';
import VModal from '../../UI/VModal.vue';
import VInput from '../../UI/VInput.vue';

const props = defineProps({
    masters: { type: Array, default: () => [] },
    salons:  { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
});
const emit = defineEmits([
    'send-notification', 'save-template', 'delete-template',
    'toggle-reminder', 'export-report', 'send-bulk', 'open-client',
]);

const t = inject('theme', {
    bg: 'var(--t-bg)', surface: 'var(--t-surface)', border: 'var(--t-border)',
    primary: 'var(--t-primary)', text: 'var(--t-text)', text2: 'var(--t-text-2)',
    text3: 'var(--t-text-3)',
});

/* ─── Tabs ─── */
const tabs = [
    { key: 'overview',   label: '📊 Обзор' },
    { key: 'reminders',  label: '⏰ Напоминания' },
    { key: 'bulk',       label: '📤 Рассылки' },
    { key: 'templates',  label: '📝 Шаблоны' },
    { key: 'history',    label: '📋 История' },
    { key: 'settings',   label: '⚙️ Настройки' },
];
const activeTab = ref('overview');

/* ─── Stats ─── */
const notifStats = ref([
    { label: 'Отправлено сегодня', value: '247', trend: '+18%', icon: '📤' },
    { label: 'Доставлено (%)', value: '96.4%', trend: '+0.3%', icon: '✅' },
    { label: 'Открыто (%)', value: '42.8%', trend: '+5%', icon: '👁️' },
    { label: 'Конверсия (%)', value: '8.2%', trend: '+1.1%', icon: '🎯' },
    { label: 'Напоминаний активных', value: '12', trend: '', icon: '⏰' },
    { label: 'Шаблонов', value: '18', trend: '+3', icon: '📝' },
]);

/* ─── Reminder Rules ─── */
const reminders = ref([
    { id: 1, name: 'За 24 часа до визита', type: 'before_visit', hours: 24, channel: 'sms', isActive: true, template: 'Здравствуйте, {name}! Напоминаем о записи в {salon} завтра в {time}. Мастер: {master}. Подтвердите визит, ответив «Да».', sent: 4820 },
    { id: 2, name: 'За 2 часа до визита', type: 'before_visit', hours: 2, channel: 'push', isActive: true, template: 'Ваш визит в {salon} через 2 часа ⏰ Мастер {master} ждёт вас!', sent: 4650 },
    { id: 3, name: 'После визита (отзыв)', type: 'after_visit', hours: 2, channel: 'sms', isActive: true, template: 'Спасибо за визит в {salon}! 🌸 Будем рады вашему отзыву: {link}', sent: 3200 },
    { id: 4, name: 'Повторная запись (7 дней)', type: 'rebooking', hours: 168, channel: 'push', isActive: true, template: 'Давно не виделись! 💇‍♀️ Запишитесь на повторный визит в {salon} со скидкой 10%.', sent: 1800 },
    { id: 5, name: 'Истёк абонемент', type: 'expiration', hours: 0, channel: 'email', isActive: false, template: 'Ваш абонемент на {service} истёк. Продлите со скидкой 15%!', sent: 420 },
    { id: 6, name: 'День рождения клиента', type: 'birthday', hours: 0, channel: 'sms', isActive: true, template: '🎂 С днём рождения, {name}! Дарим вам 500 бонусов и скидку 20% на любую процедуру!', sent: 890 },
]);

/* ─── Templates ─── */
const templates = ref([
    { id: 1, name: 'Подтверждение записи', channel: 'sms', category: 'booking', text: 'Запись подтверждена! {salon}, {date} в {time}. Мастер: {master}. Услуга: {service}.', variables: ['{name}', '{salon}', '{date}', '{time}', '{master}', '{service}'], isActive: true },
    { id: 2, name: 'Отмена записи', channel: 'sms', category: 'booking', text: 'Ваша запись в {salon} на {date} отменена. Для перезаписи: {link}', variables: ['{name}', '{salon}', '{date}', '{link}'], isActive: true },
    { id: 3, name: 'Новая акция', channel: 'push', category: 'promo', text: '🔥 Новая акция в {salon}! {promo_text} Подробности: {link}', variables: ['{salon}', '{promo_text}', '{link}'], isActive: true },
    { id: 4, name: 'Бонусы начислены', channel: 'push', category: 'bonus', text: '🎁 Вам начислено {amount} бонусов! Баланс: {balance}. Потратьте при следующем визите.', variables: ['{name}', '{amount}', '{balance}'], isActive: true },
    { id: 5, name: 'Еженедельная рассылка', channel: 'email', category: 'marketing', text: 'Добрый день, {name}! Обзор новинок {salon}: {content}', variables: ['{name}', '{salon}', '{content}'], isActive: false },
    { id: 6, name: 'Ответ на отзыв', channel: 'push', category: 'review', text: 'Салон {salon} ответил на ваш отзыв. Посмотреть: {link}', variables: ['{salon}', '{link}'], isActive: true },
]);

/* ─── Notification History ─── */
const history = ref([
    { id: 1, client: 'Мария К.', channel: 'sms', template: 'За 24 часа до визита', status: 'delivered', sentAt: '08.04.2026 09:00', readAt: '08.04.2026 09:05' },
    { id: 2, client: 'Елена П.', channel: 'push', template: 'За 2 часа до визита', status: 'delivered', sentAt: '08.04.2026 11:30', readAt: '08.04.2026 11:32' },
    { id: 3, client: 'Татьяна С.', channel: 'sms', template: 'После визита (отзыв)', status: 'delivered', sentAt: '07.04.2026 18:00', readAt: '07.04.2026 18:15' },
    { id: 4, client: 'Ирина М.', channel: 'push', template: 'Новая акция', status: 'delivered', sentAt: '07.04.2026 12:00', readAt: null },
    { id: 5, client: 'Наталья Б.', channel: 'email', template: 'Еженедельная рассылка', status: 'bounced', sentAt: '06.04.2026 10:00', readAt: null },
    { id: 6, client: 'Ольга Р.', channel: 'sms', template: 'День рождения клиента', status: 'delivered', sentAt: '05.04.2026 08:00', readAt: '05.04.2026 08:22' },
    { id: 7, client: 'Алла Ш.', channel: 'push', template: 'Бонусы начислены', status: 'failed', sentAt: '05.04.2026 14:00', readAt: null },
    { id: 8, client: 'Светлана В.', channel: 'sms', template: 'Подтверждение записи', status: 'delivered', sentAt: '05.04.2026 16:30', readAt: '05.04.2026 16:31' },
]);

/* ─── Bulk Messaging ─── */
const bulkForm = ref({ channel: 'sms', audience: 'all', template: '', customText: '' });
const audienceOptions = [
    { value: 'all', label: 'Все клиенты', count: 1842 },
    { value: 'active', label: 'Активные (визит за 30 дней)', count: 624 },
    { value: 'inactive', label: 'Неактивные (>60 дней)', count: 318 },
    { value: 'vip', label: 'VIP-клиенты', count: 47 },
    { value: 'birthday_week', label: 'День рождения на этой неделе', count: 12 },
];

/* ─── Settings ─── */
const settings = ref({
    smsEnabled: true,
    pushEnabled: true,
    emailEnabled: true,
    quietHoursFrom: '22:00',
    quietHoursTo: '08:00',
    maxSmsPerDay: 3,
    maxPushPerDay: 5,
    maxEmailPerWeek: 2,
    autoReminders: true,
    autoReviewRequest: true,
    autoRebooking: true,
    birthdayGreetings: true,
});

/* ─── Modals ─── */
const showTemplateModal = ref(false);
const showBulkConfirm = ref(false);
const newTemplate = ref({ name: '', channel: 'sms', category: 'booking', text: '' });

function saveTemplate() {
    if (!newTemplate.value.name.trim() || !newTemplate.value.text.trim()) return;
    templates.value.push({ ...newTemplate.value, id: Date.now(), variables: [], isActive: true });
    emit('save-template', newTemplate.value);
    showTemplateModal.value = false;
    newTemplate.value = { name: '', channel: 'sms', category: 'booking', text: '' };
    toast('Шаблон сохранён');
}

function deleteTemplate(tpl) { templates.value = templates.value.filter(x => x.id !== tpl.id); emit('delete-template', tpl); toast(`Шаблон «${tpl.name}» удалён`); }

function toggleReminder(reminder) {
    reminder.isActive = !reminder.isActive;
    emit('toggle-reminder', { id: reminder.id, isActive: reminder.isActive });
    toast(`Напоминание «${reminder.name}» ${reminder.isActive ? 'включено' : 'выключено'}`);
}

function sendBulk() {
    emit('send-bulk', bulkForm.value);
    showBulkConfirm.value = false;
    toast('Рассылка поставлена в очередь');
}

function saveSettings() { toast('Настройки уведомлений сохранены'); }

/* ─── Toast ─── */
const showToast = ref(false);
const toastMessage = ref('');
function toast(msg) { toastMessage.value = msg; showToast.value = true; setTimeout(() => { showToast.value = false; }, 3000); }

/* ─── Export ─── */
function exportCSV(data, filename) {
    if (!data.length) return;
    const keys = Object.keys(data[0]);
    const csv = [keys.join(';'), ...data.map(r => keys.map(k => String(r[k] ?? '')).join(';'))].join('\r\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = `${filename}_${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
    toast(`Экспорт «${filename}» завершён`);
    emit('export-report', { filename, format: 'csv' });
}

function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }
const channelIcon = (ch) => ch === 'sms' ? '📱' : ch === 'push' ? '🔔' : '📧';
const statusColor = (s) => s === 'delivered' ? 'green' : s === 'bounced' ? 'yellow' : 'red';
const statusLabel = (s) => s === 'delivered' ? 'Доставлено' : s === 'bounced' ? 'Возврат' : 'Ошибка';
</script>

<template>
<div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-xl font-bold" style="color:var(--t-text)">🔔 Центр уведомлений</h1>
        <div class="flex gap-2">
            <VButton size="sm" variant="outline" @click="exportCSV(history, 'notifications_history')">📥 Экспорт</VButton>
            <VButton size="sm" @click="showBulkConfirm = true">📤 Новая рассылка</VButton>
        </div>
    </div>

    <VTabs :tabs="tabs" v-model="activeTab" />

    <!-- ═══ OVERVIEW ═══ -->
    <div v-if="activeTab === 'overview'" class="space-y-4">
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <VStatCard v-for="s in notifStats" :key="s.label" :label="s.label" :value="s.value" :trend="s.trend" :icon="s.icon" />
        </div>
        <div class="grid lg:grid-cols-2 gap-4">
            <VCard title="📊 Каналы доставки">
                <div class="space-y-3">
                    <div v-for="ch in [{name:'SMS',icon:'📱',sent:4820,delivered:4650,opened:2100,color:'blue'},{name:'Push',icon:'🔔',sent:3200,delivered:3100,opened:1800,color:'green'},{name:'Email',icon:'📧',sent:1200,delivered:1050,opened:380,color:'purple'}]" :key="ch.name" class="p-3 rounded-lg" style="background:var(--t-bg)">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-bold" style="color:var(--t-text)">{{ ch.icon }} {{ ch.name }}</span>
                            <span class="text-xs" style="color:var(--t-text-3)">{{ fmt(ch.sent) }} отправлено</span>
                        </div>
                        <div class="h-2 rounded-full overflow-hidden" style="background:var(--t-border)">
                            <div class="h-full rounded-full" :style="{ width: ch.sent ? ((ch.delivered / ch.sent) * 100) + '%' : '0%', background: 'var(--t-primary)' }"></div>
                        </div>
                        <div class="flex justify-between mt-1">
                            <span class="text-xs" style="color:var(--t-text-3)">Доставлено: {{ ((ch.delivered / ch.sent) * 100).toFixed(1) }}%</span>
                            <span class="text-xs" style="color:var(--t-text-3)">Открыто: {{ ((ch.opened / ch.sent) * 100).toFixed(1) }}%</span>
                        </div>
                    </div>
                </div>
            </VCard>
            <VCard title="📋 Последние отправки">
                <div class="space-y-2">
                    <div v-for="h in history.slice(0, 5)" :key="h.id" class="flex items-center gap-3 p-2 rounded-lg" style="background:var(--t-bg)">
                        <span class="text-base">{{ channelIcon(h.channel) }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium truncate" style="color:var(--t-text)">{{ h.client }}</div>
                            <div class="text-xs" style="color:var(--t-text-3)">{{ h.template }}</div>
                        </div>
                        <VBadge :color="statusColor(h.status)" size="sm">{{ statusLabel(h.status) }}</VBadge>
                    </div>
                </div>
            </VCard>
        </div>
    </div>

    <!-- ═══ REMINDERS ═══ -->
    <div v-if="activeTab === 'reminders'" class="space-y-4">
        <h2 class="text-lg font-semibold" style="color:var(--t-text)">Автоматические напоминания</h2>
        <div class="space-y-3">
            <div v-for="rem in reminders" :key="rem.id" class="p-4 rounded-xl border" style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="flex items-start gap-3 flex-wrap">
                    <div class="flex-1 min-w-[200px]">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-bold text-sm" style="color:var(--t-text)">{{ rem.name }}</span>
                            <VBadge :color="rem.channel === 'sms' ? 'blue' : rem.channel === 'push' ? 'green' : 'purple'" size="sm">{{ channelIcon(rem.channel) }} {{ rem.channel.toUpperCase() }}</VBadge>
                            <VBadge :color="rem.isActive ? 'green' : 'red'" size="sm">{{ rem.isActive ? 'Активен' : 'Выкл.' }}</VBadge>
                        </div>
                        <div class="text-sm p-2 rounded-lg mt-1 font-mono" style="background:var(--t-bg);color:var(--t-text-2)">{{ rem.template }}</div>
                        <div class="text-xs mt-2" style="color:var(--t-text-3)">Отправлено: {{ fmt(rem.sent) }} раз</div>
                    </div>
                    <div class="flex gap-2 items-center">
                        <VButton size="sm" :variant="rem.isActive ? 'outline' : 'primary'" @click="toggleReminder(rem)">{{ rem.isActive ? '⏸️ Выкл.' : '▶️ Вкл.' }}</VButton>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ BULK MESSAGING ═══ -->
    <div v-if="activeTab === 'bulk'" class="space-y-4">
        <VCard title="📤 Создать рассылку">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Канал</label>
                    <select v-model="bulkForm.channel" class="w-full rounded-lg px-3 py-2 border text-sm" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                        <option value="sms">📱 SMS</option>
                        <option value="push">🔔 Push</option>
                        <option value="email">📧 Email</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Аудитория</label>
                    <div class="space-y-2">
                        <label v-for="aud in audienceOptions" :key="aud.value" class="flex items-center gap-3 p-3 rounded-lg cursor-pointer" style="background:var(--t-bg)">
                            <input type="radio" :value="aud.value" v-model="bulkForm.audience" class="accent-(--t-primary)">
                            <div class="flex-1">
                                <span class="text-sm font-medium" style="color:var(--t-text)">{{ aud.label }}</span>
                                <span class="text-xs ml-2" style="color:var(--t-text-3)">({{ fmt(aud.count) }})</span>
                            </div>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Шаблон</label>
                    <select v-model="bulkForm.template" class="w-full rounded-lg px-3 py-2 border text-sm" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                        <option value="">— Выбрать шаблон —</option>
                        <option v-for="tpl in templates" :key="tpl.id" :value="tpl.name">{{ tpl.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Или свой текст</label>
                    <textarea v-model="bulkForm.customText" rows="3" class="w-full rounded-lg px-3 py-2 border text-sm resize-none" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)" placeholder="Введите текст рассылки..."></textarea>
                </div>
                <div class="flex justify-end">
                    <VButton @click="showBulkConfirm = true">📤 Отправить рассылку</VButton>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ TEMPLATES ═══ -->
    <div v-if="activeTab === 'templates'" class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold" style="color:var(--t-text)">Шаблоны сообщений</h2>
            <VButton size="sm" @click="showTemplateModal = true">➕ Новый шаблон</VButton>
        </div>
        <div class="space-y-3">
            <div v-for="tpl in templates" :key="tpl.id" class="p-4 rounded-xl border" style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="flex items-start gap-3 flex-wrap">
                    <div class="flex-1 min-w-[200px]">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-bold text-sm" style="color:var(--t-text)">{{ tpl.name }}</span>
                            <VBadge :color="tpl.channel === 'sms' ? 'blue' : tpl.channel === 'push' ? 'green' : 'purple'" size="sm">{{ tpl.channel.toUpperCase() }}</VBadge>
                            <VBadge color="gray" size="sm">{{ tpl.category }}</VBadge>
                        </div>
                        <div class="text-sm p-2 rounded-lg mt-1 font-mono" style="background:var(--t-bg);color:var(--t-text-2)">{{ tpl.text }}</div>
                        <div v-if="tpl.variables.length" class="flex gap-1 mt-2 flex-wrap">
                            <span v-for="v in tpl.variables" :key="v" class="text-xs px-2 py-0.5 rounded-full" style="background:var(--t-primary-dim);color:var(--t-primary)">{{ v }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <label class="flex items-center gap-1 cursor-pointer">
                            <input type="checkbox" v-model="tpl.isActive" class="w-5 h-5 accent-(--t-primary)">
                            <span class="text-xs" :style="{ color: tpl.isActive ? 'var(--t-primary)' : 'var(--t-text-3)' }">{{ tpl.isActive ? 'Вкл.' : 'Выкл.' }}</span>
                        </label>
                        <VButton size="sm" variant="outline" @click="deleteTemplate(tpl)">🗑️</VButton>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ HISTORY ═══ -->
    <div v-if="activeTab === 'history'" class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-2">
            <h2 class="text-lg font-semibold" style="color:var(--t-text)">История уведомлений</h2>
            <VButton size="sm" variant="outline" @click="exportCSV(history, 'notif_history')">📥 Экспорт</VButton>
        </div>
        <div class="overflow-x-auto rounded-xl border" style="border-color:var(--t-border)">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background:var(--t-bg);border-bottom:2px solid var(--t-border)">
                        <th class="text-left p-3" style="color:var(--t-text-3)">Клиент</th>
                        <th class="text-left p-3" style="color:var(--t-text-3)">Канал</th>
                        <th class="text-left p-3" style="color:var(--t-text-3)">Шаблон</th>
                        <th class="text-left p-3" style="color:var(--t-text-3)">Статус</th>
                        <th class="text-left p-3" style="color:var(--t-text-3)">Отправлено</th>
                        <th class="text-left p-3" style="color:var(--t-text-3)">Прочитано</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="h in history" :key="h.id" style="border-bottom:1px solid var(--t-border)" class="hover:opacity-90 transition">
                        <td class="p-3 cursor-pointer" style="color:var(--t-text)" @click="emit('open-client', h)">
                            <span class="font-medium">{{ h.client }}</span>
                        </td>
                        <td class="p-3" style="color:var(--t-text-2)">{{ channelIcon(h.channel) }} {{ h.channel.toUpperCase() }}</td>
                        <td class="p-3" style="color:var(--t-text-2)">{{ h.template }}</td>
                        <td class="p-3"><VBadge :color="statusColor(h.status)" size="sm">{{ statusLabel(h.status) }}</VBadge></td>
                        <td class="p-3 text-xs" style="color:var(--t-text-3)">{{ h.sentAt }}</td>
                        <td class="p-3 text-xs" style="color:var(--t-text-3)">{{ h.readAt || '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══ SETTINGS ═══ -->
    <div v-if="activeTab === 'settings'" class="space-y-4">
        <VCard title="⚙️ Настройки уведомлений">
            <div class="space-y-6">
                <div>
                    <h3 class="font-bold text-sm mb-3" style="color:var(--t-text)">Каналы</h3>
                    <div class="space-y-2">
                        <label v-for="ch in [{key:'smsEnabled',label:'📱 SMS'},{key:'pushEnabled',label:'🔔 Push'},{key:'emailEnabled',label:'📧 Email'}]" :key="ch.key" class="flex items-center justify-between p-3 rounded-lg" style="background:var(--t-bg)">
                            <span class="text-sm font-medium" style="color:var(--t-text)">{{ ch.label }}</span>
                            <input type="checkbox" v-model="settings[ch.key]" class="w-5 h-5 accent-(--t-primary)">
                        </label>
                    </div>
                </div>
                <div>
                    <h3 class="font-bold text-sm mb-3" style="color:var(--t-text)">Тихие часы</h3>
                    <div class="flex items-center gap-3">
                        <VInput label="С" v-model="settings.quietHoursFrom" type="time" />
                        <VInput label="До" v-model="settings.quietHoursTo" type="time" />
                    </div>
                </div>
                <div>
                    <h3 class="font-bold text-sm mb-3" style="color:var(--t-text)">Лимиты отправки</h3>
                    <div class="grid sm:grid-cols-3 gap-3">
                        <VInput label="SMS / день" v-model.number="settings.maxSmsPerDay" type="number" />
                        <VInput label="Push / день" v-model.number="settings.maxPushPerDay" type="number" />
                        <VInput label="Email / неделя" v-model.number="settings.maxEmailPerWeek" type="number" />
                    </div>
                </div>
                <div>
                    <h3 class="font-bold text-sm mb-3" style="color:var(--t-text)">Автоматизация</h3>
                    <div class="space-y-2">
                        <label v-for="opt in [{key:'autoReminders',label:'Автонапоминания о визитах'},{key:'autoReviewRequest',label:'Запрос отзыва после визита'},{key:'autoRebooking',label:'Напоминание о повторной записи'},{key:'birthdayGreetings',label:'Поздравления с днём рождения'}]" :key="opt.key" class="flex items-center justify-between p-3 rounded-lg" style="background:var(--t-bg)">
                            <span class="text-sm" style="color:var(--t-text)">{{ opt.label }}</span>
                            <input type="checkbox" v-model="settings[opt.key]" class="w-5 h-5 accent-(--t-primary)">
                        </label>
                    </div>
                </div>
                <div class="flex justify-end">
                    <VButton @click="saveSettings">💾 Сохранить настройки</VButton>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ MODALS ═══ -->
    <VModal :show="showTemplateModal" @close="showTemplateModal = false" title="📝 Новый шаблон">
        <div class="space-y-4">
            <VInput label="Название" v-model="newTemplate.name" placeholder="Подтверждение записи..." />
            <div>
                <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Канал</label>
                <select v-model="newTemplate.channel" class="w-full rounded-lg px-3 py-2 border text-sm" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                    <option value="sms">📱 SMS</option>
                    <option value="push">🔔 Push</option>
                    <option value="email">📧 Email</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Категория</label>
                <select v-model="newTemplate.category" class="w-full rounded-lg px-3 py-2 border text-sm" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                    <option value="booking">Запись</option>
                    <option value="promo">Акция</option>
                    <option value="bonus">Бонусы</option>
                    <option value="review">Отзыв</option>
                    <option value="marketing">Маркетинг</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Текст шаблона</label>
                <textarea v-model="newTemplate.text" rows="4" class="w-full rounded-lg px-3 py-2 border text-sm resize-none" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)" placeholder="Используйте {name}, {salon}, {date}..."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <VButton variant="outline" @click="showTemplateModal = false">Отмена</VButton>
                <VButton @click="saveTemplate">💾 Сохранить</VButton>
            </div>
        </div>
    </VModal>

    <VModal :show="showBulkConfirm" @close="showBulkConfirm = false" title="📤 Подтвердить рассылку">
        <div class="space-y-4">
            <div class="p-4 rounded-lg" style="background:var(--t-bg)">
                <div class="text-sm" style="color:var(--t-text)">
                    <p><b>Канал:</b> {{ channelIcon(bulkForm.channel) }} {{ bulkForm.channel.toUpperCase() }}</p>
                    <p class="mt-1"><b>Аудитория:</b> {{ audienceOptions.find(a => a.value === bulkForm.audience)?.label }} ({{ fmt(audienceOptions.find(a => a.value === bulkForm.audience)?.count || 0) }})</p>
                    <p v-if="bulkForm.template" class="mt-1"><b>Шаблон:</b> {{ bulkForm.template }}</p>
                </div>
            </div>
            <p class="text-sm" style="color:var(--t-text-2)">Вы уверены, что хотите отправить рассылку выбранной аудитории?</p>
            <div class="flex justify-end gap-3">
                <VButton variant="outline" @click="showBulkConfirm = false">Отмена</VButton>
                <VButton @click="sendBulk">📤 Отправить</VButton>
            </div>
        </div>
    </VModal>
</div>

<!-- Toast -->
<Teleport to="body">
    <Transition name="fade">
        <div v-if="showToast" class="fixed bottom-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-2xl text-sm font-medium" style="background:var(--t-primary);color:#fff">
            {{ toastMessage }}
        </div>
    </Transition>
</Teleport>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .3s, transform .3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(12px); }
</style>
