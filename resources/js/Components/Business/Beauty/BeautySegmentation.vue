<script setup>
/**
 * BeautySegmentation — Блок «Сегментация клиентов» для Beauty B2B.
 *
 * Функционал:
 * — Панель карточек всех сегментов (авто + ручные)
 * — RFM-анализ, фильтры, поиск
 * — Создание ручных / автоматических сегментов
 * — Массовые действия (рассылки, бонусы, промокоды, теги, экспорт)
 * — Автотриггеры (реактивация спящих, поздравления с ДР)
 * — Детальный просмотр сегмента (список клиентов, аналитика)
 * — Интеграция с BeautyCRM / BeautyClientCard
 */
import { ref, computed, reactive, watch } from 'vue';
import VCard     from '../../UI/VCard.vue';
import VStatCard from '../../UI/VStatCard.vue';
import VButton   from '../../UI/VButton.vue';
import VBadge    from '../../UI/VBadge.vue';
import VModal    from '../../UI/VModal.vue';
import VInput    from '../../UI/VInput.vue';

const props = defineProps({
    clients:  { type: Array,  default: () => [] },
    masters:  { type: Array,  default: () => [] },
    salons:   { type: Array,  default: () => [] },
    services: { type: Array,  default: () => [] },
});

const emit = defineEmits([
    'close',
    'open-client',
    'send-message',
    'award-bonus',
    'create-promo',
    'export',
    'add-tag',
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  HELPERS                                                            */
/* ═══════════════════════════════════════════════════════════════════ */
function fmtMoney(v) { return v == null ? '0 ₽' : v.toLocaleString('ru-RU') + ' ₽'; }
function fmt(v)      { return v == null ? '0' : v.toLocaleString('ru-RU'); }
function pct(part, total) { return total ? Math.round((part / total) * 100) : 0; }
function plural(n, one, few, many) {
    const abs = Math.abs(n) % 100;
    if (abs >= 11 && abs <= 19) return many;
    const last = abs % 10;
    if (last === 1) return one;
    if (last >= 2 && last <= 4) return few;
    return many;
}
function daysAgo(dateStr) {
    if (!dateStr) return 999;
    const parts = dateStr.split('.');
    const d = new Date(+parts[2], +parts[1] - 1, +parts[0]);
    return Math.floor((Date.now() - d.getTime()) / 86400000);
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  DEMO CLIENTS (when props.clients is empty)                         */
/* ═══════════════════════════════════════════════════════════════════ */
const allClients = computed(() => props.clients.length > 0 ? props.clients : [
    { id: 1,  name: 'Мария Иванова',     phone: '+7 916 111-11-11', segment: 'VIP',         totalSpent: 485000, visits: 72, lastVisit: '07.04.2026', avgCheck: 6730, bonusBalance: 12400, source: 'Онлайн-запись',   favoriteMaster: 'Анна Соколова',     rating: 4.95, loyaltyLevel: 'Diamond', birthday: '15.05.1990', churnRisk: 3 },
    { id: 2,  name: 'Елена Петрова',     phone: '+7 916 222-22-22', segment: 'VIP',         totalSpent: 312000, visits: 48, lastVisit: '05.04.2026', avgCheck: 6500, bonusBalance: 8200,  source: 'Рекомендация',   favoriteMaster: 'Анна Соколова',     rating: 4.90, loyaltyLevel: 'Platinum', birthday: '22.08.1988', churnRisk: 5 },
    { id: 3,  name: 'Дарья Волкова',     phone: '+7 916 333-33-33', segment: 'Лояльная',    totalSpent: 148000, visits: 34, lastVisit: '01.04.2026', avgCheck: 4350, bonusBalance: 4100,  source: 'Instagram',      favoriteMaster: 'Ольга Демидова',    rating: 4.80, loyaltyLevel: 'Gold', birthday: '10.12.1995', churnRisk: 8 },
    { id: 4,  name: 'Ирина Морозова',    phone: '+7 916 444-44-44', segment: 'Лояльная',    totalSpent: 96000,  visits: 22, lastVisit: '28.03.2026', avgCheck: 4360, bonusBalance: 2800,  source: 'Сайт',           favoriteMaster: 'Анна Соколова',     rating: 4.70, loyaltyLevel: 'Gold', birthday: '03.03.1992', churnRisk: 12 },
    { id: 5,  name: 'Наталья Быкова',    phone: '+7 916 555-55-55', segment: 'Лояльная',    totalSpent: 72000,  visits: 18, lastVisit: '20.03.2026', avgCheck: 4000, bonusBalance: 1500,  source: 'Рекомендация',   favoriteMaster: 'Светлана Романова', rating: 4.60, loyaltyLevel: 'Silver', birthday: '28.07.1985', churnRisk: 15 },
    { id: 6,  name: 'Ольга Романова',    phone: '+7 916 666-66-66', segment: 'Активная',    totalSpent: 45000,  visits: 12, lastVisit: '15.03.2026', avgCheck: 3750, bonusBalance: 900,   source: 'Яндекс.Карты',   favoriteMaster: 'Игорь Волков',      rating: 4.50, loyaltyLevel: 'Silver', birthday: '14.02.1993', churnRisk: 20 },
    { id: 7,  name: 'Татьяна Смирнова',  phone: '+7 916 777-77-77', segment: 'Активная',    totalSpent: 38000,  visits: 10, lastVisit: '10.03.2026', avgCheck: 3800, bonusBalance: 600,   source: 'Instagram',      favoriteMaster: 'Ольга Демидова',    rating: 4.40, loyaltyLevel: 'Silver', birthday: '19.09.1991', churnRisk: 18 },
    { id: 8,  name: 'Анастасия Козлова', phone: '+7 916 888-88-88', segment: 'Новичок',     totalSpent: 4500,   visits: 1,  lastVisit: '06.04.2026', avgCheck: 4500, bonusBalance: 500,   source: 'Акция',          favoriteMaster: '',                  rating: 5.00, loyaltyLevel: 'Bronze', birthday: '01.06.2000', churnRisk: 45 },
    { id: 9,  name: 'Юлия Лебедева',     phone: '+7 916 999-99-99', segment: 'Новичок',     totalSpent: 3200,   visits: 1,  lastVisit: '03.04.2026', avgCheck: 3200, bonusBalance: 300,   source: 'Сайт',           favoriteMaster: '',                  rating: 4.00, loyaltyLevel: 'Bronze', birthday: '25.11.1998', churnRisk: 50 },
    { id: 10, name: 'Алина Новикова',    phone: '+7 916 100-10-10', segment: 'Новичок',     totalSpent: 2800,   visits: 1,  lastVisit: '01.04.2026', avgCheck: 2800, bonusBalance: 200,   source: 'Рекомендация',   favoriteMaster: '',                  rating: 0,    loyaltyLevel: 'Bronze', birthday: '07.04.2001', churnRisk: 55 },
    { id: 11, name: 'Светлана Егорова',  phone: '+7 916 110-11-11', segment: 'Спящая',      totalSpent: 28000,  visits: 8,  lastVisit: '05.01.2026', avgCheck: 3500, bonusBalance: 400,   source: 'Instagram',      favoriteMaster: 'Анна Соколова',     rating: 4.20, loyaltyLevel: 'Silver', birthday: '30.06.1987', churnRisk: 68 },
    { id: 12, name: 'Виктория Зайцева',  phone: '+7 916 120-12-12', segment: 'Спящая',      totalSpent: 18000,  visits: 6,  lastVisit: '20.12.2025', avgCheck: 3000, bonusBalance: 100,   source: 'Яндекс.Карты',   favoriteMaster: 'Игорь Волков',      rating: 4.00, loyaltyLevel: 'Bronze', birthday: '12.10.1994', churnRisk: 75 },
    { id: 13, name: 'Полина Кузнецова',  phone: '+7 916 130-13-13', segment: 'Потерянная',   totalSpent: 22000,  visits: 5,  lastVisit: '15.08.2025', avgCheck: 4400, bonusBalance: 0,     source: 'Сайт',           favoriteMaster: 'Анна Соколова',     rating: 3.50, loyaltyLevel: 'Bronze', birthday: '18.01.1996', churnRisk: 92 },
    { id: 14, name: 'Кристина Попова',   phone: '+7 916 140-14-14', segment: 'Потерянная',   totalSpent: 8500,   visits: 3,  lastVisit: '01.06.2025', avgCheck: 2830, bonusBalance: 0,     source: 'Акция',          favoriteMaster: '',                  rating: 3.00, loyaltyLevel: 'Bronze', birthday: '05.04.1999', churnRisk: 95 },
    { id: 15, name: 'Евгения Степанова', phone: '+7 916 150-15-15', segment: 'Проблемная',   totalSpent: 12000,  visits: 4,  lastVisit: '10.02.2026', avgCheck: 3000, bonusBalance: 0,     source: 'Рекомендация',   favoriteMaster: 'Светлана Романова', rating: 2.50, loyaltyLevel: 'Bronze', birthday: '23.09.1997', churnRisk: 80 },
    { id: 16, name: 'Арина Фёдорова',    phone: '+7 916 160-16-16', segment: 'Активная',    totalSpent: 52000,  visits: 14, lastVisit: '04.04.2026', avgCheck: 3710, bonusBalance: 1100,  source: 'Онлайн-запись',   favoriteMaster: 'Ольга Демидова',    rating: 4.60, loyaltyLevel: 'Silver', birthday: '11.11.1993', churnRisk: 10 },
    { id: 17, name: 'Вероника Соколова', phone: '+7 916 170-17-17', segment: 'Лояльная',    totalSpent: 86000,  visits: 20, lastVisit: '06.04.2026', avgCheck: 4300, bonusBalance: 2200,  source: 'Instagram',      favoriteMaster: 'Анна Соколова',     rating: 4.85, loyaltyLevel: 'Gold', birthday: '02.02.1990', churnRisk: 6 },
    { id: 18, name: 'Диана Титова',      phone: '+7 916 180-18-18', segment: 'Активная',    totalSpent: 31000,  visits: 9,  lastVisit: '02.04.2026', avgCheck: 3440, bonusBalance: 700,   source: 'Акция',          favoriteMaster: 'Игорь Волков',      rating: 4.30, loyaltyLevel: 'Silver', birthday: '16.06.1996', churnRisk: 22 },
]);

const totalClients = computed(() => allClients.value.length);

/* ═══════════════════════════════════════════════════════════════════ */
/*  SEGMENTS DEFINITION                                                */
/* ═══════════════════════════════════════════════════════════════════ */

/* ─── Segment colors/icons ─── */
const segmentMeta = {
    'VIP':          { color: '#eab308', bg: 'rgba(234,179,8,.12)',  icon: '👑', border: '#eab308' },
    'Лояльная':     { color: '#22c55e', bg: 'rgba(34,197,94,.10)',  icon: '💚', border: '#22c55e' },
    'Активная':     { color: '#3b82f6', bg: 'rgba(59,130,246,.10)', icon: '🔥', border: '#3b82f6' },
    'Новичок':      { color: '#06b6d4', bg: 'rgba(6,182,212,.10)',  icon: '🆕', border: '#06b6d4' },
    'Спящая':       { color: '#9ca3af', bg: 'rgba(156,163,175,.10)',icon: '😴', border: '#9ca3af' },
    'Потерянная':   { color: '#ef4444', bg: 'rgba(239,68,68,.10)',  icon: '💔', border: '#ef4444' },
    'Проблемная':   { color: '#f97316', bg: 'rgba(249,115,22,.10)', icon: '⚠️', border: '#f97316' },
    'Высокий чек':  { color: '#8b5cf6', bg: 'rgba(139,92,246,.10)', icon: '💎', border: '#8b5cf6' },
    'По акции':     { color: '#ec4899', bg: 'rgba(236,72,153,.10)', icon: '🏷️', border: '#ec4899' },
    'С отзывами':   { color: '#14b8a6', bg: 'rgba(20,184,166,.10)', icon: '⭐', border: '#14b8a6' },
    'Аллергии':     { color: '#dc2626', bg: 'rgba(220,38,38,.10)',  icon: '🚨', border: '#dc2626' },
    'Именинники':   { color: '#f59e0b', bg: 'rgba(245,158,11,.12)', icon: '🎂', border: '#f59e0b' },
};
const defaultMeta = { color: 'var(--t-primary)', bg: 'var(--t-primary-dim)', icon: '📂', border: 'var(--t-primary)' };
function getMeta(name) { return segmentMeta[name] || defaultMeta; }

/* ─── Auto-segments (recalculated from client data) ─── */
const autoSegments = computed(() => {
    const cl = allClients.value;
    const tot = cl.length || 1;
    const byField = (seg) => cl.filter(c => c.segment === seg);
    const vip       = byField('VIP');
    const loyal     = byField('Лояльная');
    const active    = byField('Активная');
    const newbies   = byField('Новичок');
    const sleeping  = byField('Спящая');
    const lost      = byField('Потерянная');
    const problem   = byField('Проблемная');
    const highCheck = cl.filter(c => c.avgCheck >= 5000);
    const promoOnly = cl.filter(c => c.source === 'Акция');
    const reviewed  = cl.filter(c => c.rating && c.rating >= 4);
    const allergic  = cl.filter(c => c.allergies && c.allergies !== 'Нет');
    const bdays     = cl.filter(c => {
        if (!c.birthday) return false;
        const parts = c.birthday.split('.');
        const m = +parts[1], d = +parts[0];
        const now = new Date();
        const diff = new Date(now.getFullYear(), m - 1, d) - new Date(now.getFullYear(), now.getMonth(), now.getDate());
        return diff >= 0 && diff <= 30 * 86400000;
    });

    return [
        { name: 'VIP',          type: 'auto', count: vip.length,       pct: pct(vip.length, tot),       avgSpent: avg(vip, 'totalSpent'),       avgLTV: avg(vip, 'totalSpent'),       updated: '08.04.2026 06:00', description: 'Высокий LTV + частые визиты',              clients: vip },
        { name: 'Лояльная',     type: 'auto', count: loyal.length,     pct: pct(loyal.length, tot),     avgSpent: avg(loyal, 'totalSpent'),     avgLTV: avg(loyal, 'totalSpent'),     updated: '08.04.2026 06:00', description: 'Регулярные визиты, высокий retention',      clients: loyal },
        { name: 'Активная',     type: 'auto', count: active.length,    pct: pct(active.length, tot),    avgSpent: avg(active, 'totalSpent'),    avgLTV: avg(active, 'totalSpent'),    updated: '08.04.2026 06:00', description: 'Стабильная активность и посещения',         clients: active },
        { name: 'Новичок',      type: 'auto', count: newbies.length,   pct: pct(newbies.length, tot),   avgSpent: avg(newbies, 'totalSpent'),   avgLTV: avg(newbies, 'totalSpent'),   updated: '08.04.2026 06:00', description: 'Первый визит за последние 30 дней',         clients: newbies },
        { name: 'Спящая',       type: 'auto', count: sleeping.length,  pct: pct(sleeping.length, tot),  avgSpent: avg(sleeping, 'totalSpent'),  avgLTV: avg(sleeping, 'totalSpent'),  updated: '08.04.2026 06:00', description: 'Не были 60–120 дней',                       clients: sleeping },
        { name: 'Потерянная',   type: 'auto', count: lost.length,      pct: pct(lost.length, tot),      avgSpent: avg(lost, 'totalSpent'),      avgLTV: avg(lost, 'totalSpent'),      updated: '08.04.2026 06:00', description: 'Не были более 180 дней',                    clients: lost },
        { name: 'Проблемная',   type: 'auto', count: problem.length,   pct: pct(problem.length, tot),   avgSpent: avg(problem, 'totalSpent'),   avgLTV: avg(problem, 'totalSpent'),   updated: '08.04.2026 06:00', description: 'Жалобы, низкие оценки, конфликты',          clients: problem },
        { name: 'Высокий чек',  type: 'auto', count: highCheck.length, pct: pct(highCheck.length, tot), avgSpent: avg(highCheck, 'totalSpent'), avgLTV: avg(highCheck, 'totalSpent'), updated: '08.04.2026 06:00', description: 'Средний чек ≥ 5 000 ₽',                     clients: highCheck },
        { name: 'По акции',     type: 'auto', count: promoOnly.length, pct: pct(promoOnly.length, tot), avgSpent: avg(promoOnly, 'totalSpent'), avgLTV: avg(promoOnly, 'totalSpent'), updated: '08.04.2026 06:00', description: 'Пришли только по акции/промокоду',           clients: promoOnly },
        { name: 'С отзывами',   type: 'auto', count: reviewed.length,  pct: pct(reviewed.length, tot),  avgSpent: avg(reviewed, 'totalSpent'),  avgLTV: avg(reviewed, 'totalSpent'),  updated: '08.04.2026 06:00', description: 'Оставляют положительные отзывы (≥4★)',      clients: reviewed },
        { name: 'Аллергии',     type: 'auto', count: allergic.length,  pct: pct(allergic.length, tot),  avgSpent: avg(allergic, 'totalSpent'),  avgLTV: avg(allergic, 'totalSpent'),  updated: '08.04.2026 06:00', description: 'Есть аллергии / противопоказания',           clients: allergic },
        { name: 'Именинники',   type: 'auto', count: bdays.length,     pct: pct(bdays.length, tot),     avgSpent: avg(bdays, 'totalSpent'),     avgLTV: avg(bdays, 'totalSpent'),     updated: '08.04.2026 08:00', description: 'День рождения в ближайшие 30 дней',         clients: bdays },
    ];
});

function avg(arr, field) {
    if (!arr.length) return 0;
    return Math.round(arr.reduce((s, c) => s + (c[field] || 0), 0) / arr.length);
}

/* ─── Manual (custom) segments ─── */
const manualSegments = ref([
    { name: 'Клиенты мастера Анны',  type: 'manual', count: 0, pct: 0, avgSpent: 0, avgLTV: 0, updated: '05.04.2026', description: 'Все клиенты мастера Анны Соколовой', criteria: { master: 'Анна Соколова' }, archived: false, clients: [] },
    { name: 'Только окрашивание',    type: 'manual', count: 0, pct: 0, avgSpent: 0, avgLTV: 0, updated: '03.04.2026', description: 'Клиенты, записывающиеся только на окрашивание', criteria: { service: 'Окрашивание' }, archived: false, clients: [] },
    { name: 'Акция «Весна 2026»',    type: 'manual', count: 0, pct: 0, avgSpent: 0, avgLTV: 0, updated: '01.04.2026', description: 'Участники весенней акции', criteria: { tag: 'Весна2026' }, archived: false, clients: [] },
    { name: 'Филиал Арбат',          type: 'manual', count: 0, pct: 0, avgSpent: 0, avgLTV: 0, updated: '01.04.2026', description: 'Постоянные клиенты филиала на Арбате', criteria: { salon: 'BeautyLab Арбат' }, archived: true, clients: [] },
]);

/* recalculate manual segment counts */
const enrichedManualSegments = computed(() => {
    const cl = allClients.value;
    const tot = cl.length || 1;
    return manualSegments.value.map(seg => {
        let matched = [...cl];
        if (seg.criteria?.master) matched = matched.filter(c => c.favoriteMaster === seg.criteria.master);
        if (seg.criteria?.salon)  matched = matched.filter(c => c.favoriteSalon === seg.criteria.salon);
        if (seg.criteria?.source) matched = matched.filter(c => c.source === seg.criteria.source);
        return { ...seg, count: matched.length, pct: pct(matched.length, tot), avgSpent: avg(matched, 'totalSpent'), clients: matched };
    });
});

/* ─── All segments merged ─── */
const allSegments = computed(() => [...autoSegments.value, ...enrichedManualSegments.value]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  FILTERS & SEARCH                                                   */
/* ═══════════════════════════════════════════════════════════════════ */
const search = ref('');
const filterType = ref('all');      // all | auto | manual
const filterStatus = ref('active'); // active | archived | all
const sortBy = ref('count');        // count | name | pct | avgSpent

const filteredSegments = computed(() => {
    let list = allSegments.value;
    if (filterType.value === 'auto')   list = list.filter(s => s.type === 'auto');
    if (filterType.value === 'manual') list = list.filter(s => s.type === 'manual');
    if (filterStatus.value === 'active')   list = list.filter(s => !s.archived);
    if (filterStatus.value === 'archived') list = list.filter(s => s.archived);
    if (search.value) {
        const q = search.value.toLowerCase();
        list = list.filter(s => s.name.toLowerCase().includes(q) || (s.description || '').toLowerCase().includes(q));
    }
    const sortMap = {
        count:    (a, b) => b.count - a.count,
        name:     (a, b) => a.name.localeCompare(b.name),
        pct:      (a, b) => b.pct - a.pct,
        avgSpent: (a, b) => (b.avgSpent || 0) - (a.avgSpent || 0),
    };
    list = [...list].sort(sortMap[sortBy.value] || sortMap.count);
    return list;
});

/* ═══════════════════════════════════════════════════════════════════ */
/*  OVERVIEW STATS                                                     */
/* ═══════════════════════════════════════════════════════════════════ */
const overviewStats = computed(() => ({
    totalClients: totalClients.value,
    totalSegments: allSegments.value.filter(s => !s.archived).length,
    autoSegments: autoSegments.value.length,
    manualSegments: enrichedManualSegments.value.filter(s => !s.archived).length,
    avgChurnRisk: Math.round(allClients.value.reduce((s, c) => s + (c.churnRisk || 0), 0) / (totalClients.value || 1)),
    topSegment: autoSegments.value.reduce((max, s) => s.count > max.count ? s : max, { count: 0, name: '—' }),
}));

/* ═══════════════════════════════════════════════════════════════════ */
/*  SEGMENT DETAIL VIEW                                                */
/* ═══════════════════════════════════════════════════════════════════ */
const activeSegment = ref(null);
const segmentClientSearch = ref('');
const segmentClientSort = ref('totalSpent');
const selectedSegClients = ref([]);

function openSegment(seg) {
    activeSegment.value = seg;
    segmentClientSearch.value = '';
    selectedSegClients.value = [];
}
function closeSegment() { activeSegment.value = null; }

const segmentClients = computed(() => {
    if (!activeSegment.value) return [];
    let list = activeSegment.value.clients || [];
    if (segmentClientSearch.value) {
        const q = segmentClientSearch.value.toLowerCase();
        list = list.filter(c => c.name.toLowerCase().includes(q) || c.phone.includes(q));
    }
    const sortMap = {
        totalSpent: (a, b) => b.totalSpent - a.totalSpent,
        visits:     (a, b) => b.visits - a.visits,
        name:       (a, b) => a.name.localeCompare(b.name),
        lastVisit:  (a, b) => (b.lastVisit || '').localeCompare(a.lastVisit || ''),
        churnRisk:  (a, b) => (b.churnRisk || 0) - (a.churnRisk || 0),
    };
    return [...list].sort(sortMap[segmentClientSort.value] || sortMap.totalSpent);
});

const segAllSelected = computed(() => segmentClients.value.length > 0 && segmentClients.value.every(c => selectedSegClients.value.includes(c.id)));
function toggleSegSelectAll() {
    if (segAllSelected.value) selectedSegClients.value = [];
    else selectedSegClients.value = segmentClients.value.map(c => c.id);
}
function toggleSegClient(id) {
    const idx = selectedSegClients.value.indexOf(id);
    if (idx >= 0) selectedSegClients.value.splice(idx, 1);
    else selectedSegClients.value.push(id);
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  SEGMENT ACTIONS (MASS)                                             */
/* ═══════════════════════════════════════════════════════════════════ */
const showMassModal = ref(false);
const massActionType = ref('sms');
const massMessageText = ref('');
const massBonusAmount = ref('');
const massPromoValue = ref('');

function openMassAction(type) {
    massActionType.value = type;
    showMassModal.value = true;
}
function executeMassAction() {
    const clients = selectedSegClients.value.length > 0
        ? allClients.value.filter(c => selectedSegClients.value.includes(c.id))
        : activeSegment.value?.clients || [];
    const payload = { type: massActionType.value, clients, segment: activeSegment.value?.name };
    if (massActionType.value === 'sms' || massActionType.value === 'push' || massActionType.value === 'email' || massActionType.value === 'whatsapp') {
        emit('send-message', { ...payload, text: massMessageText.value });
    } else if (massActionType.value === 'bonus') {
        emit('award-bonus', { ...payload, amount: +massBonusAmount.value });
    } else if (massActionType.value === 'promo') {
        emit('create-promo', { ...payload, value: massPromoValue.value });
    } else if (massActionType.value === 'tag') {
        emit('add-tag', payload);
    } else if (massActionType.value === 'export') {
        emit('export', payload);
    }
    showMassModal.value = false;
    massMessageText.value = '';
    massBonusAmount.value = '';
    massPromoValue.value = '';
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  CREATE SEGMENT MODAL                                               */
/* ═══════════════════════════════════════════════════════════════════ */
const showCreateModal = ref(false);
const newSegment = reactive({
    name: '',
    description: '',
    type: 'manual',
    criteria: { master: '', salon: '', service: '', source: '', minSpent: '', maxSpent: '', minVisits: '', maxVisits: '', tag: '' },
});

function createSegment() {
    if (!newSegment.name.trim()) return;
    manualSegments.value.push({
        name: newSegment.name,
        type: newSegment.type,
        description: newSegment.description,
        criteria: { ...newSegment.criteria },
        archived: false,
        count: 0, pct: 0, avgSpent: 0, avgLTV: 0,
        updated: new Date().toLocaleDateString('ru-RU'),
        clients: [],
    });
    showCreateModal.value = false;
    Object.assign(newSegment, { name: '', description: '', type: 'manual', criteria: { master: '', salon: '', service: '', source: '', minSpent: '', maxSpent: '', minVisits: '', maxVisits: '', tag: '' } });
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  TRIGGERS                                                           */
/* ═══════════════════════════════════════════════════════════════════ */
const triggers = ref([
    { id: 1, segment: 'Спящая',    action: 'Реактивационное SMS',   channel: 'sms',   enabled: true,  lastFired: '07.04.2026', fired: 3 },
    { id: 2, segment: 'Именинники', action: 'Поздравление + 1000 ₽', channel: 'push',  enabled: true,  lastFired: '05.04.2026', fired: 12 },
    { id: 3, segment: 'Потерянная', action: 'Скидка 20% на визит',  channel: 'email', enabled: true,  lastFired: '01.04.2026', fired: 5 },
    { id: 4, segment: 'Новичок',   action: 'Приветственный бонус 500 ₽', channel: 'push', enabled: true, lastFired: '06.04.2026', fired: 8 },
    { id: 5, segment: 'VIP',       action: 'Персональное предложение',   channel: 'whatsapp', enabled: false, lastFired: '—', fired: 0 },
]);
const channelIcons = { sms: '📱', push: '🔔', email: '📧', whatsapp: '💬', telegram: '✈️' };

function toggleTrigger(t) { t.enabled = !t.enabled; }

/* ═══════════════════════════════════════════════════════════════════ */
/*  RFM ANALYSIS                                                       */
/* ═══════════════════════════════════════════════════════════════════ */
const rfmData = computed(() => {
    const cl = allClients.value;
    const cells = [
        { r: 'Недавно',    f: 'Часто',  m: 'Много',  label: 'Чемпионы',      color: '#22c55e', count: 0 },
        { r: 'Недавно',    f: 'Часто',  m: 'Мало',   label: 'Лояльные',      color: '#3b82f6', count: 0 },
        { r: 'Недавно',    f: 'Редко',  m: 'Много',  label: 'Потенциальные',  color: '#8b5cf6', count: 0 },
        { r: 'Недавно',    f: 'Редко',  m: 'Мало',   label: 'Новички',        color: '#06b6d4', count: 0 },
        { r: 'Давно',      f: 'Часто',  m: 'Много',  label: 'Под угрозой',    color: '#f59e0b', count: 0 },
        { r: 'Давно',      f: 'Часто',  m: 'Мало',   label: 'Нужно внимание', color: '#f97316', count: 0 },
        { r: 'Давно',      f: 'Редко',  m: 'Много',  label: 'Спящие VIP',     color: '#ef4444', count: 0 },
        { r: 'Давно',      f: 'Редко',  m: 'Мало',   label: 'Потерянные',     color: '#6b7280', count: 0 },
    ];
    for (const c of cl) {
        const r = daysAgo(c.lastVisit) <= 60 ? 'Недавно' : 'Давно';
        const f = (c.visits || 0) >= 10 ? 'Часто' : 'Редко';
        const m = (c.totalSpent || 0) >= 50000 ? 'Много' : 'Мало';
        const cell = cells.find(x => x.r === r && x.f === f && x.m === m);
        if (cell) cell.count++;
    }
    return cells;
});
const maxRfm = computed(() => Math.max(...rfmData.value.map(c => c.count), 1));

/* ═══════════════════════════════════════════════════════════════════ */
/*  VIEW STATE                                                         */
/* ═══════════════════════════════════════════════════════════════════ */
const activeView = ref('segments'); // segments | detail | rfm | triggers
</script>

<template>
<div class="space-y-4">

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- TOP BAR                                                    -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div class="flex justify-between items-center flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <button v-if="activeView !== 'segments'" @click="activeView = 'segments'; closeSegment()"
                    class="p-2 rounded-lg hover:brightness-110 transition"
                    style="background:var(--t-surface);color:var(--t-text-2)">← Назад</button>
            <h2 class="text-lg font-bold" style="color:var(--t-text)">
                {{ activeView === 'segments' ? '📂 Сегментация клиентов' :
                   activeView === 'detail'   ? getMeta(activeSegment?.name).icon + ' ' + (activeSegment?.name || '') :
                   activeView === 'rfm'      ? '📊 RFM-анализ' :
                   '⚡ Автотриггеры' }}
            </h2>
        </div>
        <div class="flex gap-2">
            <VButton size="sm" variant="outline"
                     :style="activeView === 'rfm' ? 'background:var(--t-primary);color:#fff' : ''"
                     @click="activeView = activeView === 'rfm' ? 'segments' : 'rfm'">📊 RFM</VButton>
            <VButton size="sm" variant="outline"
                     :style="activeView === 'triggers' ? 'background:var(--t-primary);color:#fff' : ''"
                     @click="activeView = activeView === 'triggers' ? 'segments' : 'triggers'">⚡ Триггеры</VButton>
            <VButton size="sm" @click="showCreateModal = true">➕ Новый сегмент</VButton>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- OVERVIEW STATS                                             -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div v-if="activeView === 'segments'" class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <VStatCard title="Всего клиентов" :value="String(overviewStats.totalClients)" icon="👥" />
        <VStatCard title="Активных сегментов" :value="String(overviewStats.totalSegments)" icon="📂" />
        <VStatCard title="Авто-сегменты" :value="String(overviewStats.autoSegments)" icon="🤖" />
        <VStatCard title="Ручные сегменты" :value="String(overviewStats.manualSegments)" icon="✋" />
        <VStatCard title="Ср. риск оттока" :value="overviewStats.avgChurnRisk + '%'" icon="⚠️" />
    </div>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- 1. SEGMENTS GRID                                           -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <template v-if="activeView === 'segments'">
        <!-- Filters -->
        <div class="flex flex-wrap gap-2 items-center">
            <VInput v-model="search" placeholder="🔍 Поиск сегмента..." class="w-56" />
            <select v-model="filterType" class="px-3 py-1.5 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option value="all">Все типы</option>
                <option value="auto">🤖 Автоматические</option>
                <option value="manual">✋ Ручные</option>
            </select>
            <select v-model="filterStatus" class="px-3 py-1.5 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option value="active">Активные</option>
                <option value="archived">Архивные</option>
                <option value="all">Все</option>
            </select>
            <select v-model="sortBy" class="px-3 py-1.5 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option value="count">По количеству</option>
                <option value="name">По названию</option>
                <option value="pct">По % базы</option>
                <option value="avgSpent">По ср. тратам</option>
            </select>
        </div>

        <!-- Segment cards grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="seg in filteredSegments" :key="seg.name"
                 class="rounded-2xl border-2 p-5 cursor-pointer hover:shadow-lg transition-all group"
                 :style="`background:${getMeta(seg.name).bg};border-color:${getMeta(seg.name).border}`"
                 @click="openSegment(seg); activeView = 'detail'">
                <!-- Header row -->
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">{{ getMeta(seg.name).icon }}</span>
                        <div>
                            <h3 class="text-sm font-bold" style="color:var(--t-text)">{{ seg.name }}</h3>
                            <VBadge :color="seg.type === 'auto' ? 'blue' : 'purple'" size="sm">
                                {{ seg.type === 'auto' ? '🤖 Авто' : '✋ Ручной' }}
                            </VBadge>
                        </div>
                    </div>
                    <!-- Big count -->
                    <div class="text-right">
                        <div class="text-2xl font-black" :style="`color:${getMeta(seg.name).color}`">{{ seg.count }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ seg.pct }}% базы</div>
                    </div>
                </div>

                <!-- Description -->
                <p class="text-xs mb-3" style="color:var(--t-text-2)">{{ seg.description }}</p>

                <!-- Mini stats -->
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div class="p-2 rounded-lg text-center" style="background:var(--t-surface)">
                        <div class="text-xs font-bold" style="color:var(--t-text)">{{ fmtMoney(seg.avgSpent) }}</div>
                        <div class="text-[9px]" style="color:var(--t-text-3)">Ср. траты</div>
                    </div>
                    <div class="p-2 rounded-lg text-center" style="background:var(--t-surface)">
                        <div class="text-xs font-bold" style="color:var(--t-text)">{{ fmt(seg.count) }}</div>
                        <div class="text-[9px]" style="color:var(--t-text-3)">Клиентов</div>
                    </div>
                </div>

                <!-- Progress bar (% of total) -->
                <div class="h-2 rounded-full mb-2" style="background:var(--t-surface)">
                    <div class="h-full rounded-full transition-all" :style="`width:${Math.min(seg.pct, 100)}%;background:${getMeta(seg.name).color}`"></div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between text-[10px]" style="color:var(--t-text-3)">
                    <span>Обновлено: {{ seg.updated }}</span>
                    <span class="opacity-0 group-hover:opacity-100 transition" :style="`color:${getMeta(seg.name).color}`">Открыть →</span>
                </div>
            </div>

            <!-- "Create" placeholder card -->
            <div class="rounded-2xl border-2 border-dashed p-5 flex flex-col items-center justify-center gap-3 cursor-pointer hover:opacity-80 transition min-h-[200px]"
                 style="border-color:var(--t-border);color:var(--t-text-3)"
                 @click="showCreateModal = true">
                <span class="text-4xl">➕</span>
                <span class="text-sm font-medium">Создать сегмент</span>
            </div>
        </div>
    </template>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- 2. SEGMENT DETAIL VIEW                                     -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <template v-if="activeView === 'detail' && activeSegment">
        <!-- Segment header card -->
        <div class="rounded-2xl border-2 p-5"
             :style="`background:${getMeta(activeSegment.name).bg};border-color:${getMeta(activeSegment.name).border}`">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-4xl">{{ getMeta(activeSegment.name).icon }}</span>
                    <div>
                        <h2 class="text-xl font-bold" style="color:var(--t-text)">{{ activeSegment.name }}</h2>
                        <p class="text-sm" style="color:var(--t-text-2)">{{ activeSegment.description }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <VBadge :color="activeSegment.type === 'auto' ? 'blue' : 'purple'" size="sm">
                                {{ activeSegment.type === 'auto' ? '🤖 Авто' : '✋ Ручной' }}
                            </VBadge>
                            <span class="text-xs" style="color:var(--t-text-3)">Обновлено: {{ activeSegment.updated }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-4 items-center">
                    <div class="text-center">
                        <div class="text-3xl font-black" :style="`color:${getMeta(activeSegment.name).color}`">{{ activeSegment.count }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ activeSegment.pct }}% базы</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-bold" style="color:var(--t-text)">{{ fmtMoney(activeSegment.avgSpent) }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">Ср. траты</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Segment analytics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <VStatCard title="Клиентов" :value="String(activeSegment.count)" icon="👥" />
            <VStatCard title="% от базы" :value="activeSegment.pct + '%'" icon="📊" />
            <VStatCard title="Ср. траты" :value="fmtMoney(activeSegment.avgSpent)" icon="💰" />
            <VStatCard title="Общая выручка" :value="fmtMoney((activeSegment.clients || []).reduce((s, c) => s + (c.totalSpent || 0), 0))" icon="📈" />
        </div>

        <!-- Mass actions bar -->
        <div class="flex flex-wrap gap-2 items-center p-3 rounded-xl border"
             style="background:var(--t-surface);border-color:var(--t-border)">
            <span class="text-sm font-medium mr-2" style="color:var(--t-text)">
                ⚡ Действия{{ selectedSegClients.length > 0 ? ` (выбрано ${selectedSegClients.length})` : ' (весь сегмент)' }}:
            </span>
            <VButton size="sm" @click="openMassAction('sms')">📱 SMS</VButton>
            <VButton size="sm" @click="openMassAction('push')">🔔 Push</VButton>
            <VButton size="sm" @click="openMassAction('whatsapp')">💬 WhatsApp</VButton>
            <VButton size="sm" @click="openMassAction('email')">📧 Email</VButton>
            <VButton size="sm" variant="outline" @click="openMassAction('bonus')">🎁 Бонусы</VButton>
            <VButton size="sm" variant="outline" @click="openMassAction('promo')">🏷️ Промокод</VButton>
            <VButton size="sm" variant="outline" @click="openMassAction('tag')">🏷️ Тег</VButton>
            <VButton size="sm" variant="outline" @click="openMassAction('export')">📤 Экспорт</VButton>
        </div>

        <!-- Clients table -->
        <div class="flex flex-wrap gap-2 items-center">
            <VInput v-model="segmentClientSearch" placeholder="🔍 Имя, телефон..." class="w-56" />
            <select v-model="segmentClientSort" class="px-3 py-1.5 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option value="totalSpent">По тратам</option>
                <option value="visits">По визитам</option>
                <option value="lastVisit">По посл. визиту</option>
                <option value="name">По имени</option>
                <option value="churnRisk">По риску оттока</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border" style="border-color:var(--t-border)">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background:var(--t-surface)">
                        <th class="p-3 text-left">
                            <input type="checkbox" :checked="segAllSelected" @change="toggleSegSelectAll" />
                        </th>
                        <th class="p-3 text-left" style="color:var(--t-text-2)">Клиент</th>
                        <th class="p-3 text-center" style="color:var(--t-text-2)">Визиты</th>
                        <th class="p-3 text-center" style="color:var(--t-text-2)">Ср. чек</th>
                        <th class="p-3 text-center" style="color:var(--t-text-2)">Потрачено</th>
                        <th class="p-3 text-center" style="color:var(--t-text-2)">Посл. визит</th>
                        <th class="p-3 text-center" style="color:var(--t-text-2)">Риск оттока</th>
                        <th class="p-3 text-center" style="color:var(--t-text-2)">Мастер</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in segmentClients" :key="c.id"
                        class="border-t cursor-pointer hover:brightness-105 transition"
                        style="border-color:var(--t-border)"
                        @click="$emit('open-client', c)">
                        <td class="p-3" @click.stop>
                            <input type="checkbox" :checked="selectedSegClients.includes(c.id)" @change="toggleSegClient(c.id)" />
                        </td>
                        <td class="p-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                                     style="background:var(--t-primary-dim);color:var(--t-primary)">{{ c.name.charAt(0) }}</div>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium truncate" style="color:var(--t-text)">{{ c.name }}</div>
                                    <div class="text-[10px]" style="color:var(--t-text-3)">{{ c.phone }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="p-3 text-center" style="color:var(--t-text)">{{ c.visits }}</td>
                        <td class="p-3 text-center" style="color:var(--t-text)">{{ fmtMoney(c.avgCheck) }}</td>
                        <td class="p-3 text-center font-bold" style="color:var(--t-primary)">{{ fmtMoney(c.totalSpent) }}</td>
                        <td class="p-3 text-center" style="color:var(--t-text-2)">{{ c.lastVisit || '—' }}</td>
                        <td class="p-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                                  :style="`color:#fff;background:${(c.churnRisk || 0) > 60 ? '#ef4444' : (c.churnRisk || 0) > 30 ? '#f59e0b' : '#22c55e'}`">
                                {{ c.churnRisk || 0 }}%
                            </span>
                        </td>
                        <td class="p-3 text-center text-xs" style="color:var(--t-text-2)">{{ c.favoriteMaster || '—' }}</td>
                    </tr>
                    <tr v-if="segmentClients.length === 0">
                        <td colspan="8" class="p-8 text-center" style="color:var(--t-text-3)">Нет клиентов в этом сегменте</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </template>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- 3. RFM ANALYSIS VIEW                                       -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <template v-if="activeView === 'rfm'">
        <VCard title="📊 RFM-матрица (Recency × Frequency × Monetary)">
            <p class="text-xs mb-4" style="color:var(--t-text-3)">
                Клиенты сгруппированы по давности визита (R), частоте (F) и сумме трат (M). Кликните на ячейку для перехода к сегменту.
            </p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div v-for="cell in rfmData" :key="cell.label"
                     class="rounded-xl p-4 cursor-pointer hover:shadow-md transition-all border-2"
                     :style="`background:${cell.color}15;border-color:${cell.color}`"
                     @click="/* could filter */;">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-bold" :style="`color:${cell.color}`">{{ cell.label }}</h4>
                        <span class="text-xl font-black" :style="`color:${cell.color}`">{{ cell.count }}</span>
                    </div>
                    <div class="text-[10px] space-y-0.5" style="color:var(--t-text-3)">
                        <div>R: {{ cell.r }} · F: {{ cell.f }} · M: {{ cell.m }}</div>
                    </div>
                    <!-- Bar -->
                    <div class="h-2 rounded-full mt-2" style="background:var(--t-surface)">
                        <div class="h-full rounded-full transition-all"
                             :style="`width:${(cell.count / maxRfm) * 100}%;background:${cell.color}`"></div>
                    </div>
                </div>
            </div>
        </VCard>

        <!-- RFM legend -->
        <VCard title="📖 Расшифровка RFM">
            <div class="grid md:grid-cols-2 gap-3">
                <div class="space-y-2">
                    <h4 class="text-xs font-bold" style="color:var(--t-text-2)">R — Recency (давность)</h4>
                    <div class="text-xs" style="color:var(--t-text-3)">
                        <div>Недавно: последний визит ≤ 60 дней</div>
                        <div>Давно: последний визит > 60 дней</div>
                    </div>
                </div>
                <div class="space-y-2">
                    <h4 class="text-xs font-bold" style="color:var(--t-text-2)">F — Frequency (частота)</h4>
                    <div class="text-xs" style="color:var(--t-text-3)">
                        <div>Часто: ≥ 10 визитов за всё время</div>
                        <div>Редко: &lt; 10 визитов</div>
                    </div>
                </div>
                <div class="space-y-2">
                    <h4 class="text-xs font-bold" style="color:var(--t-text-2)">M — Monetary (деньги)</h4>
                    <div class="text-xs" style="color:var(--t-text-3)">
                        <div>Много: сумма трат ≥ 50 000 ₽</div>
                        <div>Мало: &lt; 50 000 ₽</div>
                    </div>
                </div>
                <div class="space-y-2">
                    <h4 class="text-xs font-bold" style="color:var(--t-text-2)">💡 Как использовать</h4>
                    <div class="text-xs" style="color:var(--t-text-3)">
                        <div>🟢 Чемпионы → удерживать VIP-обслуживанием</div>
                        <div>🟡 Под угрозой → реактивировать скидками</div>
                        <div>🔴 Потерянные → агрессивная реактивация</div>
                    </div>
                </div>
            </div>
        </VCard>
    </template>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- 4. AUTO-TRIGGERS VIEW                                      -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <template v-if="activeView === 'triggers'">
        <VCard title="⚡ Автоматические триггеры по сегментам">
            <p class="text-xs mb-4" style="color:var(--t-text-3)">
                Триггеры автоматически отправляют сообщения / начисляют бонусы при попадании клиента в определённый сегмент.
            </p>
            <div class="space-y-3">
                <div v-for="t in triggers" :key="t.id"
                     class="flex items-center gap-4 p-4 rounded-xl border transition-all"
                     :style="`background:var(--t-bg);border-color:${t.enabled ? getMeta(t.segment).border : 'var(--t-border)'}`">
                    <!-- Toggle -->
                    <button class="w-12 h-6 rounded-full relative transition-colors shrink-0"
                            :style="`background:${t.enabled ? '#22c55e' : '#6b7280'}`"
                            @click="toggleTrigger(t)">
                        <span class="absolute w-5 h-5 bg-white rounded-full top-0.5 transition-all shadow"
                              :style="`left:${t.enabled ? '26px' : '2px'}`"></span>
                    </button>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-bold" style="color:var(--t-text)">{{ t.action }}</span>
                            <VBadge :color="t.enabled ? 'green' : 'gray'" size="sm">{{ t.enabled ? 'Активен' : 'Выключен' }}</VBadge>
                        </div>
                        <div class="text-xs mt-0.5" style="color:var(--t-text-3)">
                            Сегмент: <strong :style="`color:${getMeta(t.segment).color}`">{{ getMeta(t.segment).icon }} {{ t.segment }}</strong>
                            · Канал: {{ channelIcons[t.channel] }} {{ t.channel.toUpperCase() }}
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="text-right shrink-0">
                        <div class="text-sm font-bold" style="color:var(--t-primary)">{{ t.fired }}x</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">Последний: {{ t.lastFired }}</div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <VButton size="sm" variant="outline" class="w-full">➕ Добавить триггер</VButton>
            </div>
        </VCard>

        <!-- Trigger effectiveness -->
        <VCard title="📈 Эффективность триггеров">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <VStatCard title="Всего отправок" :value="String(triggers.reduce((s, t) => s + t.fired, 0))" icon="📤" />
                <VStatCard title="Активных триггеров" :value="String(triggers.filter(t => t.enabled).length)" icon="⚡" />
                <VStatCard title="Реактивировано" :value="'3'" icon="🔄" />
                <VStatCard title="Конверсия" :value="'12%'" icon="🎯" />
            </div>
        </VCard>
    </template>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- MODALS                                                     -->
    <!-- ═══════════════════════════════════════════════════════════ -->

    <!-- Create Segment Modal -->
    <VModal :show="showCreateModal" title="➕ Создать новый сегмент" @close="showCreateModal = false">
        <div class="space-y-3">
            <VInput v-model="newSegment.name" placeholder="Название сегмента" />
            <textarea v-model="newSegment.description" rows="2" placeholder="Описание сегмента..."
                      class="w-full p-3 rounded-xl text-sm border resize-none"
                      style="background:var(--t-bg);color:var(--t-text);border-color:var(--t-border)"></textarea>

            <div class="text-xs font-bold" style="color:var(--t-text-2)">Критерии фильтрации:</div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-[10px] block mb-0.5" style="color:var(--t-text-3)">Мастер</label>
                    <select v-model="newSegment.criteria.master" class="w-full px-3 py-1.5 rounded-lg text-sm border"
                            style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                        <option value="">Любой</option>
                        <option v-for="m in props.masters" :key="m.id || m.name" :value="m.name">{{ m.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] block mb-0.5" style="color:var(--t-text-3)">Филиал</label>
                    <select v-model="newSegment.criteria.salon" class="w-full px-3 py-1.5 rounded-lg text-sm border"
                            style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                        <option value="">Любой</option>
                        <option v-for="s in props.salons" :key="s.id || s.name" :value="s.name">{{ s.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] block mb-0.5" style="color:var(--t-text-3)">Услуга</label>
                    <VInput v-model="newSegment.criteria.service" placeholder="Окрашивание..." />
                </div>
                <div>
                    <label class="text-[10px] block mb-0.5" style="color:var(--t-text-3)">Источник</label>
                    <select v-model="newSegment.criteria.source" class="w-full px-3 py-1.5 rounded-lg text-sm border"
                            style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                        <option value="">Любой</option>
                        <option>Онлайн-запись</option>
                        <option>Instagram</option>
                        <option>Рекомендация</option>
                        <option>Яндекс.Карты</option>
                        <option>Сайт</option>
                        <option>Акция</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] block mb-0.5" style="color:var(--t-text-3)">Мин. трат (₽)</label>
                    <VInput v-model="newSegment.criteria.minSpent" type="number" placeholder="0" />
                </div>
                <div>
                    <label class="text-[10px] block mb-0.5" style="color:var(--t-text-3)">Макс. трат (₽)</label>
                    <VInput v-model="newSegment.criteria.maxSpent" type="number" placeholder="∞" />
                </div>
                <div>
                    <label class="text-[10px] block mb-0.5" style="color:var(--t-text-3)">Мин. визитов</label>
                    <VInput v-model="newSegment.criteria.minVisits" type="number" placeholder="0" />
                </div>
                <div>
                    <label class="text-[10px] block mb-0.5" style="color:var(--t-text-3)">Тег</label>
                    <VInput v-model="newSegment.criteria.tag" placeholder="VIP, Блонд..." />
                </div>
            </div>

            <div class="flex gap-2 justify-end pt-2">
                <VButton size="sm" variant="outline" @click="showCreateModal = false">Отмена</VButton>
                <VButton size="sm" @click="createSegment" :disabled="!newSegment.name.trim()">Создать</VButton>
            </div>
        </div>
    </VModal>

    <!-- Mass Action Modal -->
    <VModal :show="showMassModal" :title="
        massActionType === 'sms' ? '📱 Отправить SMS' :
        massActionType === 'push' ? '🔔 Отправить Push' :
        massActionType === 'whatsapp' ? '💬 Отправить WhatsApp' :
        massActionType === 'email' ? '📧 Отправить Email' :
        massActionType === 'bonus' ? '🎁 Начислить бонусы' :
        massActionType === 'promo' ? '🏷️ Создать промокод' :
        massActionType === 'tag' ? '🏷️ Добавить тег' :
        '📤 Экспорт контактов'
    " @close="showMassModal = false">
        <div class="space-y-3">
            <div class="text-sm" style="color:var(--t-text-2)">
                Сегмент: <strong style="color:var(--t-primary)">{{ activeSegment?.name }}</strong>
                · Клиентов: <strong>{{ selectedSegClients.length > 0 ? selectedSegClients.length : activeSegment?.count }}</strong>
            </div>

            <!-- Channel messages -->
            <template v-if="['sms','push','whatsapp','email'].includes(massActionType)">
                <textarea v-model="massMessageText" rows="4" placeholder="Текст сообщения..."
                          class="w-full p-3 rounded-xl text-sm border resize-none"
                          style="background:var(--t-bg);color:var(--t-text);border-color:var(--t-border)"></textarea>
                <div class="text-[10px]" style="color:var(--t-text-3)">
                    💡 Используйте {name} для подстановки имени клиента
                </div>
            </template>

            <!-- Bonus -->
            <template v-if="massActionType === 'bonus'">
                <VInput v-model="massBonusAmount" type="number" placeholder="Сумма бонусов (₽)" />
            </template>

            <!-- Promo -->
            <template v-if="massActionType === 'promo'">
                <VInput v-model="massPromoValue" placeholder="Промокод или описание скидки" />
            </template>

            <!-- Tag -->
            <template v-if="massActionType === 'tag'">
                <VInput v-model="massMessageText" placeholder="Название тега" />
            </template>

            <!-- Export info -->
            <template v-if="massActionType === 'export'">
                <div class="text-sm" style="color:var(--t-text)">
                    Будет экспортировано {{ selectedSegClients.length > 0 ? selectedSegClients.length : activeSegment?.count }} контактов в CSV.
                </div>
            </template>

            <div class="flex gap-2 justify-end pt-2">
                <VButton size="sm" variant="outline" @click="showMassModal = false">Отмена</VButton>
                <VButton size="sm" @click="executeMassAction">
                    {{ massActionType === 'export' ? '📤 Экспортировать' : '✅ Выполнить' }}
                </VButton>
            </div>
        </div>
    </VModal>

</div>
</template>
