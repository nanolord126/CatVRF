<template>
<div class="space-y-4">
    <!-- ═══ HEADER + FILTERS ═══ -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold" style="color:var(--t-text)">📜 История взаимодействий</h2>
        <div class="flex items-center gap-2">
            <VButton size="sm" variant="outline" @click="exportHistory('xlsx')">📤 Экспорт</VButton>
            <VButton size="sm" variant="outline" @click="refreshHistory">🔄 Обновить</VButton>
        </div>
    </div>

    <!-- Filter bar -->
    <div class="p-3 rounded-xl border flex flex-wrap gap-3 items-end"
         style="background:var(--t-surface);border-color:var(--t-border)">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[11px] mb-1 font-medium" style="color:var(--t-text-2)">Поиск</label>
            <VInput v-model="filters.search" placeholder="🔍 Клиент, мастер, событие..." class="w-full" />
        </div>
        <div class="w-44">
            <label class="block text-[11px] mb-1 font-medium" style="color:var(--t-text-2)">Тип события</label>
            <select v-model="filters.eventType" class="w-full px-3 py-2 rounded-lg text-sm border"
                    style="background:var(--t-bg);color:var(--t-text);border-color:var(--t-border)">
                <option value="">Все типы</option>
                <option v-for="et in eventTypes" :key="et.key" :value="et.key">{{ et.icon }} {{ et.label }}</option>
            </select>
        </div>
        <div class="w-40">
            <label class="block text-[11px] mb-1 font-medium" style="color:var(--t-text-2)">Сотрудник</label>
            <select v-model="filters.employee" class="w-full px-3 py-2 rounded-lg text-sm border"
                    style="background:var(--t-bg);color:var(--t-text);border-color:var(--t-border)">
                <option value="">Все</option>
                <option v-for="emp in employees" :key="emp" :value="emp">{{ emp }}</option>
            </select>
        </div>
        <div class="w-36">
            <label class="block text-[11px] mb-1 font-medium" style="color:var(--t-text-2)">Период</label>
            <select v-model="filters.period" class="w-full px-3 py-2 rounded-lg text-sm border"
                    style="background:var(--t-bg);color:var(--t-text);border-color:var(--t-border)">
                <option value="today">Сегодня</option>
                <option value="week">Неделя</option>
                <option value="month">Месяц</option>
                <option value="quarter">Квартал</option>
                <option value="year">Год</option>
                <option value="all">Всё время</option>
            </select>
        </div>
        <div class="w-36">
            <label class="block text-[11px] mb-1 font-medium" style="color:var(--t-text-2)">Клиент</label>
            <select v-model="filters.clientId" class="w-full px-3 py-2 rounded-lg text-sm border"
                    style="background:var(--t-bg);color:var(--t-text);border-color:var(--t-border)">
                <option :value="null">Все клиенты</option>
                <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
        </div>
        <VButton size="sm" variant="outline" @click="resetFilters">✕ Сбросить</VButton>
    </div>

    <!-- Stats row -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <VStatCard title="Всего событий" :value="String(filteredEvents.length)" icon="📋" />
        <VStatCard title="Визиты" :value="String(countByType('booking'))" icon="📅" />
        <VStatCard title="Сообщения" :value="String(countByType('message'))" icon="💬" />
        <VStatCard title="Бонусные операции" :value="String(countByType('bonus'))" icon="🎁" />
        <VStatCard title="Отзывы" :value="String(countByType('review'))" icon="⭐" />
    </div>

    <!-- Timeline -->
    <VCard title="📜 Хронология событий">
        <div class="space-y-0.5">
            <!-- Date groups -->
            <template v-for="group in groupedEvents" :key="group.date">
                <div class="sticky top-0 z-10 py-2 px-3 -mx-3 text-xs font-bold"
                     style="background:var(--t-surface);color:var(--t-primary)">
                    {{ group.dateLabel }}
                    <span class="font-normal ml-1" style="color:var(--t-text-3)">· {{ group.events.length }} событий</span>
                </div>

                <div v-for="ev in group.events" :key="ev.id"
                     class="flex gap-3 p-3 rounded-lg border hover:shadow transition cursor-pointer group"
                     style="background:var(--t-bg);border-color:var(--t-border)"
                     @click="showEventDetail(ev)">
                    <!-- Icon -->
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg shrink-0"
                         :style="`background:${eventColor(ev.type)}20;color:${eventColor(ev.type)}`">
                        {{ eventIcon(ev.type) }}
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-sm font-medium" style="color:var(--t-text)">{{ ev.title }}</span>
                            <VBadge :color="eventBadgeColor(ev.type)" size="sm">{{ eventLabel(ev.type) }}</VBadge>
                            <span v-if="ev.amount" class="text-xs font-bold"
                                  :style="`color:${ev.amount > 0 ? '#22c55e' : '#ef4444'}`">
                                {{ ev.amount > 0 ? '+' : '' }}{{ fmtMoney(ev.amount) }}
                            </span>
                        </div>
                        <div class="text-xs" style="color:var(--t-text-2)">{{ ev.description }}</div>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-[10px]" style="color:var(--t-text-3)">
                                👤 {{ ev.clientName }}
                            </span>
                            <span v-if="ev.employeeName" class="text-[10px]" style="color:var(--t-text-3)">
                                🧑‍💼 {{ ev.employeeName }}
                            </span>
                            <span class="text-[10px]" style="color:var(--t-text-3)">
                                🕒 {{ ev.time }}
                            </span>
                            <span v-if="ev.channel" class="text-[10px]" style="color:var(--t-text-3)">
                                {{ channelIcon(ev.channel) }} {{ ev.channel }}
                            </span>
                        </div>
                    </div>

                    <!-- Action -->
                    <div class="opacity-0 group-hover:opacity-100 transition shrink-0 flex items-center">
                        <VButton size="sm" variant="outline" @click.stop="openClientFromEvent(ev)">→ Клиент</VButton>
                    </div>
                </div>
            </template>

            <!-- Empty state -->
            <div v-if="!filteredEvents.length" class="text-center py-12">
                <div class="text-4xl mb-3">🔍</div>
                <div class="text-sm font-medium" style="color:var(--t-text-2)">Нет событий по выбранным фильтрам</div>
                <VButton size="sm" variant="outline" class="mt-3" @click="resetFilters">Сбросить фильтры</VButton>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between mt-4 pt-3 border-t"
             style="border-color:var(--t-border)">
            <div class="text-xs" style="color:var(--t-text-3)">
                Показано {{ (currentPage - 1) * perPage + 1 }}–{{ Math.min(currentPage * perPage, filteredEvents.length) }}
                из {{ filteredEvents.length }}
            </div>
            <div class="flex gap-1">
                <VButton size="sm" variant="outline" :disabled="currentPage <= 1" @click="currentPage--">←</VButton>
                <template v-for="p in paginationRange" :key="p">
                    <button v-if="p !== '...'" class="w-8 h-8 rounded-lg text-xs font-medium transition"
                            :style="p === currentPage
                                ? 'background:var(--t-primary);color:#fff'
                                : 'color:var(--t-text-2)'"
                            @click="currentPage = p">{{ p }}</button>
                    <span v-else class="w-8 h-8 flex items-center justify-center text-xs" style="color:var(--t-text-3)">…</span>
                </template>
                <VButton size="sm" variant="outline" :disabled="currentPage >= totalPages" @click="currentPage++">→</VButton>
            </div>
        </div>
    </VCard>

    <!-- Activity heatmap -->
    <VCard title="🗓️ Активность по дням недели и часам">
        <div class="overflow-x-auto">
            <table class="w-full text-[10px]" style="color:var(--t-text-3)">
                <thead>
                    <tr>
                        <th class="text-left p-1 w-16">День</th>
                        <th v-for="h in hours" :key="h" class="text-center p-1 w-8">{{ h }}:00</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="day in heatmapData" :key="day.name">
                        <td class="p-1 font-medium" style="color:var(--t-text-2)">{{ day.name }}</td>
                        <td v-for="(val, hi) in day.hours" :key="hi" class="p-0.5">
                            <div class="w-6 h-6 rounded flex items-center justify-center text-[9px] font-bold"
                                 :style="`background:var(--t-primary);opacity:${Math.max(0.08, val / maxHeatVal)};color:var(--t-text)`">
                                {{ val || '' }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </VCard>

    <!-- Recent communications log -->
    <VCard title="💬 Последние коммуникации">
        <div class="space-y-2 max-h-64 overflow-y-auto">
            <div v-for="comm in recentComms" :key="comm.id"
                 class="p-3 rounded-lg border flex items-center gap-3"
                 style="background:var(--t-bg);border-color:var(--t-border)">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm"
                     :style="`background:${channelColor(comm.channel)}20;color:${channelColor(comm.channel)}`">
                    {{ channelIcon(comm.channel) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium" style="color:var(--t-text)">{{ comm.clientName }}</span>
                        <VBadge :color="comm.direction === 'in' ? 'green' : 'blue'" size="sm">
                            {{ comm.direction === 'in' ? '← Входящее' : '→ Исходящее' }}
                        </VBadge>
                    </div>
                    <div class="text-xs truncate" style="color:var(--t-text-2)">{{ comm.text }}</div>
                </div>
                <div class="text-right shrink-0">
                    <div class="text-[10px]" style="color:var(--t-text-3)">{{ comm.date }}</div>
                    <div class="text-[10px]" style="color:var(--t-text-3)">{{ comm.time }}</div>
                </div>
            </div>
        </div>
    </VCard>

    <!-- Event Detail Modal -->
    <VModal :show="showDetail" @close="showDetail = false" :title="detailEvent?.title || 'Событие'" size="lg">
        <div v-if="detailEvent" class="space-y-4">
            <div class="grid md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <div class="text-xs" style="color:var(--t-text-3)">Тип события</div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg">{{ eventIcon(detailEvent.type) }}</span>
                        <VBadge :color="eventBadgeColor(detailEvent.type)">{{ eventLabel(detailEvent.type) }}</VBadge>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs" style="color:var(--t-text-3)">Дата и время</div>
                    <div class="text-sm font-medium" style="color:var(--t-text)">{{ detailEvent.date }} {{ detailEvent.time }}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs" style="color:var(--t-text-3)">Клиент</div>
                    <div class="text-sm font-medium" style="color:var(--t-text)">{{ detailEvent.clientName }}</div>
                </div>
                <div v-if="detailEvent.employeeName" class="space-y-2">
                    <div class="text-xs" style="color:var(--t-text-3)">Сотрудник</div>
                    <div class="text-sm font-medium" style="color:var(--t-text)">{{ detailEvent.employeeName }}</div>
                </div>
            </div>
            <div class="space-y-2">
                <div class="text-xs" style="color:var(--t-text-3)">Описание</div>
                <div class="text-sm" style="color:var(--t-text)">{{ detailEvent.description }}</div>
            </div>
            <div v-if="detailEvent.amount" class="p-3 rounded-lg" style="background:var(--t-bg)">
                <div class="text-xs" style="color:var(--t-text-3)">Сумма</div>
                <div class="text-lg font-bold" :style="`color:${detailEvent.amount > 0 ? '#22c55e' : '#ef4444'}`">
                    {{ detailEvent.amount > 0 ? '+' : '' }}{{ fmtMoney(detailEvent.amount) }}
                </div>
            </div>
            <div v-if="detailEvent.metadata" class="space-y-2">
                <div class="text-xs" style="color:var(--t-text-3)">Дополнительные данные</div>
                <div class="grid grid-cols-2 gap-2">
                    <div v-for="(val, key) in detailEvent.metadata" :key="key"
                         class="p-2 rounded-lg text-xs" style="background:var(--t-bg)">
                        <span style="color:var(--t-text-3)">{{ key }}:</span>
                        <span class="ml-1 font-medium" style="color:var(--t-text)">{{ val }}</span>
                    </div>
                </div>
            </div>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showDetail = false">Закрыть</VButton>
            <VButton @click="openClientFromEvent(detailEvent); showDetail = false">→ Перейти к клиенту</VButton>
        </template>
    </VModal>
</div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import VButton from '../../UI/VButton.vue';
import VStatCard from '../../UI/VStatCard.vue';
import VCard from '../../UI/VCard.vue';
import VInput from '../../UI/VInput.vue';
import VModal from '../../UI/VModal.vue';
import VBadge from '../../UI/VBadge.vue';

/* ═══════════════════════════════════════════════════════════════════ */
/*  PROPS & EMITS                                                      */
/* ═══════════════════════════════════════════════════════════════════ */
const props = defineProps({
    clients: { type: Array, default: () => [] },
    masters: { type: Array, default: () => [] },
});

const emit = defineEmits(['open-client']);

/* ═══════════════════════════════════════════════════════════════════ */
/*  EVENT TYPE DEFINITIONS                                             */
/* ═══════════════════════════════════════════════════════════════════ */
const eventTypes = [
    { key: 'booking',       icon: '📅', label: 'Запись / визит',        color: '#3b82f6' },
    { key: 'booking_cancel', icon: '❌', label: 'Отмена записи',        color: '#ef4444' },
    { key: 'booking_change', icon: '🔄', label: 'Перенос записи',       color: '#f59e0b' },
    { key: 'message',       icon: '💬', label: 'Сообщение',             color: '#8b5cf6' },
    { key: 'call',          icon: '📞', label: 'Звонок',                color: '#06b6d4' },
    { key: 'bonus',         icon: '🎁', label: 'Бонусная операция',     color: '#22c55e' },
    { key: 'bonus_deduct',  icon: '➖', label: 'Списание бонусов',      color: '#f97316' },
    { key: 'review',        icon: '⭐', label: 'Отзыв',                 color: '#eab308' },
    { key: 'complaint',     icon: '😤', label: 'Жалоба',                color: '#ef4444' },
    { key: 'segment_change', icon: '📂', label: 'Смена сегмента',       color: '#6366f1' },
    { key: 'loyalty_up',    icon: '🏆', label: 'Повышение уровня',      color: '#a855f7' },
    { key: 'card_update',   icon: '✏️', label: 'Изменение карточки',    color: '#64748b' },
    { key: 'note',          icon: '📝', label: 'Заметка',               color: '#78716c' },
    { key: 'campaign',      icon: '📣', label: 'Маркетинговая кампания', color: '#ec4899' },
    { key: 'new_client',    icon: '🆕', label: 'Новый клиент',          color: '#10b981' },
    { key: 'payment',       icon: '💳', label: 'Оплата',                color: '#0ea5e9' },
];

const employees = ['Анна Соколова', 'Ольга Демидова', 'Светлана Романова', 'Кристина Лебедева', 'Игорь Волков', 'Администратор', 'Система'];
const hours = Array.from({ length: 13 }, (_, i) => i + 8); // 8:00–20:00

/* ═══════════════════════════════════════════════════════════════════ */
/*  FILTERS                                                            */
/* ═══════════════════════════════════════════════════════════════════ */
const filters = reactive({ search: '', eventType: '', employee: '', period: 'month', clientId: null });
const currentPage = ref(1);
const perPage = 25;

function resetFilters() {
    Object.assign(filters, { search: '', eventType: '', employee: '', period: 'month', clientId: null });
    currentPage.value = 1;
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  EVENT DATA (demo)                                                  */
/* ═══════════════════════════════════════════════════════════════════ */
const allEvents = ref([
    { id: 1,  type: 'booking',        title: 'Визит завершён',            clientName: 'Мария Королёва',      clientId: 1,  employeeName: 'Анна Соколова',      date: '09.04.2026', time: '11:30', description: 'Окрашивание + стрижка, 2.5 часа', amount: 6500,  channel: null, metadata: { service: 'Окрашивание Balayage', duration: '2.5ч', rating: '5 ⭐' } },
    { id: 2,  type: 'bonus',          title: 'Начислены бонусы',          clientName: 'Мария Королёва',      clientId: 1,  employeeName: 'Система',            date: '09.04.2026', time: '11:35', description: 'Кэшбэк 10% за визит', amount: 650,   channel: null, metadata: { rule: 'Platinum cashback 10%' } },
    { id: 3,  type: 'message',        title: 'Исходящее сообщение',       clientName: 'Виктория Соловьёва',  clientId: 9,  employeeName: 'Администратор',      date: '09.04.2026', time: '09:00', description: 'Напоминание о записи на косметологию', amount: null, channel: 'WhatsApp', metadata: null },
    { id: 4,  type: 'review',         title: 'Новый отзыв',              clientName: 'Виктория Соловьёва',  clientId: 9,  employeeName: null,                 date: '09.04.2026', time: '15:00', description: 'Великолепный сервис! Светлана — лучший мастер! ⭐⭐⭐⭐⭐', amount: null, channel: null, metadata: { rating: 5, platform: 'Маркетплейс' } },
    { id: 5,  type: 'booking',        title: 'Визит завершён',            clientName: 'Мария Королёва',      clientId: 1,  employeeName: 'Анна Соколова',      date: '08.04.2026', time: '10:00', description: 'Укладка, 45 минут', amount: 2500,  channel: null, metadata: { service: 'Укладка праздничная', duration: '45м' } },
    { id: 6,  type: 'payment',        title: 'Оплата картой',             clientName: 'Мария Королёва',      clientId: 1,  employeeName: 'Система',            date: '08.04.2026', time: '10:50', description: 'Безналичная оплата за услуги', amount: 2500,  channel: null, metadata: { method: 'Visa •••4532', receipt: '#R-20260408-001' } },
    { id: 7,  type: 'booking_cancel', title: 'Отмена записи',            clientName: 'Елена Петрова',       clientId: 2,  employeeName: null,                 date: '08.04.2026', time: '08:15', description: 'Клиент отменил запись на маникюр (14:00)', amount: null, channel: 'Telegram', metadata: { reason: 'Личные обстоятельства', penalty: 'Нет' } },
    { id: 8,  type: 'message',        title: 'Входящее сообщение',        clientName: 'Елена Петрова',       clientId: 2,  employeeName: null,                 date: '07.04.2026', time: '10:30', description: 'Можно ли перенести запись на 15:00?', amount: null, channel: 'WhatsApp', metadata: null },
    { id: 9,  type: 'booking_change', title: 'Перенос записи',            clientName: 'Елена Петрова',       clientId: 2,  employeeName: 'Ольга Демидова',     date: '07.04.2026', time: '10:45', description: 'Маникюр перенесён: 14:00 → 15:00', amount: null, channel: null, metadata: { from: '14:00', to: '15:00' } },
    { id: 10, type: 'booking',        title: 'Визит завершён',            clientName: 'Елена Петрова',       clientId: 2,  employeeName: 'Ольга Демидова',     date: '07.04.2026', time: '16:00', description: 'Маникюр классический, 1 час', amount: 3200,  channel: null, metadata: { service: 'Маникюр классический', duration: '1ч' } },
    { id: 11, type: 'new_client',     title: 'Новый клиент зарегистрирован', clientName: 'Наталья Белова',  clientId: 5,  employeeName: 'Система',            date: '06.04.2026', time: '14:00', description: 'Источник: Реклама ВК', amount: null, channel: null, metadata: { source: 'Реклама ВК', utm: 'vk_beauty_apr26' } },
    { id: 12, type: 'booking',        title: 'Визит завершён',            clientName: 'Наталья Белова',      clientId: 5,  employeeName: 'Кристина Лебедева',  date: '06.04.2026', time: '15:30', description: 'Коррекция бровей, 30 минут', amount: 1200,  channel: null, metadata: { service: 'Коррекция бровей', duration: '30м' } },
    { id: 13, type: 'bonus',          title: 'Приветственный бонус',      clientName: 'Наталья Белова',      clientId: 5,  employeeName: 'Система',            date: '06.04.2026', time: '15:35', description: 'Бонус для нового клиента', amount: 200,   channel: null, metadata: { rule: 'Welcome bonus' } },
    { id: 14, type: 'campaign',       title: 'Рассылка «Весенняя акция»', clientName: 'Группа: Лояльные',    clientId: null, employeeName: 'Администратор',   date: '05.04.2026', time: '12:00', description: 'SMS-рассылка: скидка 20% на окрашивание. Отправлено: 45, Открыто: 32', amount: null, channel: 'SMS', metadata: { sent: 45, opened: 32, clicks: 18 } },
    { id: 15, type: 'booking',        title: 'Визит завершён',            clientName: 'Ирина Морозова',      clientId: 4,  employeeName: 'Светлана Романова',  date: '05.04.2026', time: '13:30', description: 'Массаж лица, 40 минут', amount: 4200,  channel: null, metadata: { service: 'Массаж лица', duration: '40м' } },
    { id: 16, type: 'loyalty_up',     title: 'Повышение уровня лояльности', clientName: 'Алина Фёдорова',  clientId: 11, employeeName: 'Система',            date: '04.04.2026', time: '09:00', description: 'Silver → Gold (сумма покупок > 40 000 ₽)', amount: null, channel: null, metadata: { from: 'Silver', to: 'Gold', totalSpent: 48200 } },
    { id: 17, type: 'complaint',      title: 'Жалоба клиента',            clientName: 'Полина Зайцева',      clientId: 12, employeeName: null,                 date: '03.04.2026', time: '17:00', description: 'Недовольна результатом окрашивания. Цвет не соответствует ожиданиям.', amount: null, channel: 'Звонок', metadata: { priority: 'Высокий', status: 'В работе' } },
    { id: 18, type: 'bonus_deduct',   title: 'Списание бонусов',          clientName: 'Елена Петрова',       clientId: 2,  employeeName: 'Ольга Демидова',     date: '03.04.2026', time: '15:30', description: 'Частичная оплата бонусами', amount: -500,  channel: null, metadata: { service: 'Маникюр' } },
    { id: 19, type: 'card_update',    title: 'Обновление карточки',       clientName: 'Анастасия Кузнецова', clientId: 6,  employeeName: 'Администратор',      date: '03.04.2026', time: '11:00', description: 'Добавлен тег «укладка», обновлены предпочтения', amount: null, channel: null, metadata: { field: 'tags, preferences' } },
    { id: 20, type: 'note',           title: 'Заметка добавлена',         clientName: 'Мария Королёва',      clientId: 1,  employeeName: 'Анна Соколова',      date: '02.04.2026', time: '13:00', description: 'Хочет попробовать балаяж в следующий раз. Обсудили варианты.', amount: null, channel: null, metadata: null },
    { id: 21, type: 'segment_change', title: 'Смена сегмента',            clientName: 'Регина Карпова',      clientId: 10, employeeName: 'Система',            date: '01.04.2026', time: '03:00', description: 'Лояльная → Потерянная (нет визитов > 30 дней)', amount: null, channel: null, metadata: { from: 'Лояльная', to: 'Потерянная' } },
    { id: 22, type: 'call',           title: 'Исходящий звонок',          clientName: 'Регина Карпова',      clientId: 10, employeeName: 'Администратор',      date: '01.04.2026', time: '10:00', description: 'Попытка вернуть клиента. Не ответила.', amount: null, channel: 'Звонок', metadata: { duration: '0:00', result: 'Нет ответа' } },
    { id: 23, type: 'booking',        title: 'Визит завершён',            clientName: 'Дарья Волкова',       clientId: 3,  employeeName: 'Анна Соколова',      date: '01.04.2026', time: '14:00', description: 'Окрашивание корней, 1.5 часа', amount: 5800,  channel: null, metadata: { service: 'Окрашивание корней', duration: '1.5ч' } },
    { id: 24, type: 'message',        title: 'Автоматическая рассылка',   clientName: 'Оксана Егорова',      clientId: 8,  employeeName: 'Система',            date: '09.04.2026', time: '10:00', description: 'Напоминание о предстоящей записи', amount: null, channel: 'SMS', metadata: { template: 'appointment_reminder' } },
    { id: 25, type: 'booking',        title: 'Новая запись создана',      clientName: 'Оксана Егорова',      clientId: 8,  employeeName: 'Администратор',      date: '09.04.2026', time: '09:30', description: 'Запись на стрижку 11.04 в 16:00 к Анне Соколовой', amount: null, channel: null, metadata: { master: 'Анна Соколова', date: '11.04.2026', time: '16:00' } },
    { id: 26, type: 'booking',        title: 'Визит завершён',            clientName: 'Татьяна Новикова',    clientId: 7,  employeeName: 'Ольга Демидова',     date: '25.03.2026', time: '18:30', description: 'Педикюр аппаратный, 1.5 часа', amount: 4800,  channel: null, metadata: { service: 'Педикюр аппаратный', duration: '1.5ч' } },
    { id: 27, type: 'review',         title: 'Новый отзыв',              clientName: 'Татьяна Новикова',    clientId: 7,  employeeName: null,                 date: '25.03.2026', time: '20:00', description: 'Отличный педикюр, Ольга — профессионал! ⭐⭐⭐⭐', amount: null, channel: null, metadata: { rating: 4, platform: 'Google' } },
    { id: 28, type: 'booking',        title: 'Визит завершён',            clientName: 'Алина Фёдорова',      clientId: 11, employeeName: 'Кристина Лебедева',  date: '03.04.2026', time: '10:00', description: 'Ламинирование ресниц, 1 час', amount: 3400,  channel: null, metadata: { service: 'Ламинирование ресниц', duration: '1ч' } },
    { id: 29, type: 'message',        title: 'Автоматическое поздравление', clientName: 'Алина Фёдорова',    clientId: 11, employeeName: 'Система',            date: '14.04.2026', time: '08:00', description: 'С днём рождения! 🎂 Начислено 1000 бонусов', amount: 1000, channel: 'WhatsApp', metadata: { template: 'birthday_congrats', trigger: 'auto_birthday' } },
    { id: 30, type: 'payment',        title: 'Оплата наличными',          clientName: 'Дарья Волкова',       clientId: 3,  employeeName: 'Анна Соколова',      date: '01.04.2026', time: '15:30', description: 'Наличная оплата за окрашивание', amount: 5800,  channel: null, metadata: { method: 'Наличные', receipt: '#R-20260401-003' } },
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  COMPUTED FILTERS                                                   */
/* ═══════════════════════════════════════════════════════════════════ */
const filteredEvents = computed(() => {
    let list = [...allEvents.value];
    const q = filters.search.toLowerCase();

    if (q) {
        list = list.filter(e =>
            e.title.toLowerCase().includes(q) ||
            e.clientName.toLowerCase().includes(q) ||
            (e.employeeName || '').toLowerCase().includes(q) ||
            e.description.toLowerCase().includes(q)
        );
    }
    if (filters.eventType) {
        list = list.filter(e => e.type === filters.eventType);
    }
    if (filters.employee) {
        list = list.filter(e => e.employeeName === filters.employee);
    }
    if (filters.clientId) {
        list = list.filter(e => e.clientId === filters.clientId);
    }

    // Sort by date descending
    list.sort((a, b) => {
        const da = a.date.split('.').reverse().join('') + a.time.replace(':', '');
        const db = b.date.split('.').reverse().join('') + b.time.replace(':', '');
        return db.localeCompare(da);
    });
    return list;
});

const paginatedEvents = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return filteredEvents.value.slice(start, start + perPage);
});

const totalPages = computed(() => Math.ceil(filteredEvents.value.length / perPage));

const paginationRange = computed(() => {
    const total = totalPages.value;
    const cur = currentPage.value;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const pages = [1];
    if (cur > 3) pages.push('...');
    for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i);
    if (cur < total - 2) pages.push('...');
    pages.push(total);
    return pages;
});

/* Group events by date */
const groupedEvents = computed(() => {
    const groups = {};
    for (const ev of paginatedEvents.value) {
        if (!groups[ev.date]) {
            groups[ev.date] = { date: ev.date, dateLabel: formatDateLabel(ev.date), events: [] };
        }
        groups[ev.date].events.push(ev);
    }
    return Object.values(groups);
});

function countByType(type) {
    return filteredEvents.value.filter(e => e.type === type || e.type.startsWith(type)).length;
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  HEATMAP DATA                                                       */
/* ═══════════════════════════════════════════════════════════════════ */
const heatmapData = computed(() => {
    const days = [
        { name: 'Пн', hours: [3, 5, 8, 12, 15, 11, 9, 7, 4, 2, 1, 0, 0] },
        { name: 'Вт', hours: [2, 6, 10, 14, 12, 10, 8, 6, 3, 2, 0, 0, 0] },
        { name: 'Ср', hours: [4, 7, 11, 13, 16, 14, 11, 8, 5, 3, 1, 0, 0] },
        { name: 'Чт', hours: [3, 5, 9, 11, 13, 12, 10, 7, 4, 2, 1, 0, 0] },
        { name: 'Пт', hours: [5, 8, 12, 15, 18, 16, 13, 10, 7, 5, 3, 1, 0] },
        { name: 'Сб', hours: [8, 12, 18, 22, 20, 17, 14, 11, 8, 5, 3, 1, 0] },
        { name: 'Вс', hours: [2, 4, 6, 8, 7, 5, 4, 3, 2, 1, 0, 0, 0] },
    ];
    return days;
});

const maxHeatVal = computed(() => {
    let mx = 1;
    for (const day of heatmapData.value) {
        for (const v of day.hours) {
            if (v > mx) mx = v;
        }
    }
    return mx;
});

/* ═══════════════════════════════════════════════════════════════════ */
/*  RECENT COMMS                                                       */
/* ═══════════════════════════════════════════════════════════════════ */
const recentComms = computed(() => [
    { id: 1, clientName: 'Виктория Соловьёва', channel: 'WhatsApp', direction: 'out', text: 'Напоминание о записи на косметологию 10.04 в 12:00', date: '09.04.2026', time: '09:00' },
    { id: 2, clientName: 'Виктория Соловьёва', channel: 'WhatsApp', direction: 'in',  text: 'Отлично, жду!', date: '09.04.2026', time: '09:15' },
    { id: 3, clientName: 'Оксана Егорова',     channel: 'SMS',      direction: 'out', text: 'Напоминание о предстоящей записи', date: '09.04.2026', time: '10:00' },
    { id: 4, clientName: 'Мария Королёва',     channel: 'WhatsApp', direction: 'out', text: 'Спасибо за визит! Начислили 650 бонусов 🎁', date: '08.04.2026', time: '13:00' },
    { id: 5, clientName: 'Мария Королёва',     channel: 'WhatsApp', direction: 'in',  text: 'Спасибо, до встречи!', date: '08.04.2026', time: '13:05' },
    { id: 6, clientName: 'Елена Петрова',      channel: 'WhatsApp', direction: 'in',  text: 'Можно ли перенести на 15:00?', date: '07.04.2026', time: '10:30' },
    { id: 7, clientName: 'Елена Петрова',      channel: 'Telegram', direction: 'out', text: 'Запись перенесена на 15:00. Ждём вас!', date: '07.04.2026', time: '10:50' },
    { id: 8, clientName: 'Регина Карпова',     channel: 'Звонок',   direction: 'out', text: 'Попытка вернуть клиента. Нет ответа.', date: '01.04.2026', time: '10:00' },
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  HELPERS                                                            */
/* ═══════════════════════════════════════════════════════════════════ */
function fmtMoney(v) {
    if (v == null) return '0 ₽';
    return Math.abs(Number(v)).toLocaleString('ru-RU') + ' ₽';
}

function eventIcon(type) {
    const et = eventTypes.find(t => t.key === type);
    return et?.icon || '📋';
}
function eventLabel(type) {
    const et = eventTypes.find(t => t.key === type);
    return et?.label || type;
}
function eventColor(type) {
    const et = eventTypes.find(t => t.key === type);
    return et?.color || '#64748b';
}
function eventBadgeColor(type) {
    const colors = {
        booking: 'blue', booking_cancel: 'red', booking_change: 'yellow',
        message: 'purple', call: 'blue', bonus: 'green', bonus_deduct: 'yellow',
        review: 'yellow', complaint: 'red', segment_change: 'purple',
        loyalty_up: 'purple', card_update: 'gray', note: 'gray',
        campaign: 'purple', new_client: 'green', payment: 'blue',
    };
    return colors[type] || 'gray';
}
function channelIcon(ch) {
    const map = { WhatsApp: '📱', Telegram: '✈️', SMS: '📩', Email: '📧', Push: '🔔', 'Звонок': '📞' };
    return map[ch] || '💬';
}
function channelColor(ch) {
    const map = { WhatsApp: '#25d366', Telegram: '#0088cc', SMS: '#3b82f6', Email: '#8b5cf6', 'Звонок': '#06b6d4' };
    return map[ch] || '#64748b';
}
function formatDateLabel(dateStr) {
    const today = new Date();
    const [d, m, y] = dateStr.split('.');
    const date = new Date(Number(y), Number(m) - 1, Number(d));
    const diff = Math.floor((today.getTime() - date.getTime()) / 86400000);

    if (diff === 0) return '📅 Сегодня';
    if (diff === 1) return '📅 Вчера';
    if (diff <= 6) return `📅 ${diff} дн. назад`;

    const months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    return `📅 ${Number(d)} ${months[Number(m) - 1]} ${y}`;
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  EVENT DETAIL MODAL                                                 */
/* ═══════════════════════════════════════════════════════════════════ */
const showDetail = ref(false);
const detailEvent = ref(null);

function showEventDetail(ev) {
    detailEvent.value = ev;
    showDetail.value = true;
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  ACTIONS                                                            */
/* ═══════════════════════════════════════════════════════════════════ */
function openClientFromEvent(ev) {
    if (ev?.clientId) {
        emit('open-client', ev.clientId);
    }
}
function exportHistory(format) {
    const events = filteredEvents.value;
    const header = '\uFEFFДата;Тип;Клиент;Описание\n';
    const rows = events.map(e => `${e.date || ''};${e.type || ''};${e.clientName || ''};${e.description || ''}`).join('\n');
    const ext = format === 'json' ? 'json' : 'csv';
    const content = format === 'json' ? JSON.stringify(events, null, 2) : header + rows;
    const mime = format === 'json' ? 'application/json' : 'text/csv;charset=utf-8;';
    const blob = new Blob([content], { type: mime });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `crm_history_${Date.now()}.${ext}`;
    a.click();
    URL.revokeObjectURL(url);
}
function refreshHistory() {
    Object.assign(filters, { period: 'all', search: '', type: '' });
}
</script>
