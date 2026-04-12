<script setup>
/**
 * BeautySalonCard — полная карточка филиала / салона Beauty.
 * 10 секций: шапка, action bar, основная инфо, настройки,
 * сотрудники, финансы, загрузка, аналитика, документы, B2B.
 * Получает салон через props, эмитит события наверх.
 */
import { ref, computed, reactive } from 'vue';
import VButton from '../../UI/VButton.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import VModal from '../../UI/VModal.vue';
import VInput from '../../UI/VInput.vue';
import VStatCard from '../../UI/VStatCard.vue';
import BeautyCampaignCard from './BeautyCampaignCard.vue';
import BeautyBloggerCard from './BeautyBloggerCard.vue';
import BeautySocialCard from './BeautySocialCard.vue';
import BeautyVideoCard from './BeautyVideoCard.vue';
import BeautySourceDetailCard from './BeautySourceDetailCard.vue';

const props = defineProps({
    salon: { type: Object, required: true },
    masters: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    bookings: { type: Array, default: () => [] },
    salons: { type: Array, default: () => [] },
});

const emit = defineEmits([
    'close', 'edit', 'open-calendar', 'create-booking',
    'add-employee', 'run-promo', 'generate-report',
    'export-data', 'deactivate',
]);

/* ─── Helpers ─── */
function fmtMoney(n) { return new Intl.NumberFormat('ru-RU').format(n) + ' ₽'; }
function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }

/* ═══════════════ PROFILE TABS ═══════════════ */
const profileTabs = [
    { key: 'info',       label: '📋 Инфо' },
    { key: 'settings',   label: '⚙️ Настройки' },
    { key: 'staff',      label: '👥 Сотрудники' },
    { key: 'finance',    label: '💰 Финансы' },
    { key: 'load',       label: '📊 Загрузка' },
    { key: 'analytics',  label: '📈 Аналитика' },
    { key: 'sources',    label: '📡 Источники' },
    { key: 'docs',       label: '📁 Документы' },
    { key: 'b2b',        label: '🏢 B2B' },
];
const activeTab = ref('info');

/* ═══════════════ ENRICHED SALON DATA ═══════════════ */
const salonProfile = computed(() => {
    const s = props.salon;
    return {
        ...s,
        status: s.status || 'active',
        fullAddress: s.fullAddress || s.address || 'Новосибирск, ул. Ленина 12, офис 3',
        lat: s.lat || 55.0302,
        lon: s.lon || 82.9204,
        phone: s.phone || '+7 383 200-00-01',
        whatsapp: s.whatsapp || '+73832000001',
        email: s.email || 'center@beautylab.ru',
        rating: s.rating || 4.8,
        reviewsCount: s.reviewsCount || 342,
        photos: s.photos || [],
        openedDate: s.openedDate || '15.06.2020',
        area: s.area || 180,
        rooms: s.rooms || 6,
        workstations: s.workstations || 10,
        type: s.type || 'flagship',
        inn: s.inn || '5401234567',
        ogrn: s.ogrn || '1145400012345',
        bankAccount: s.bankAccount || '40702810200000001234',
        admin: s.admin || { name: 'Ирина Павлова', phone: '+7 913 111-22-33', role: 'Старший администратор' },
        hours: s.hours || '09:00–21:00',
        zones: s.zones || ['Парикмахерская', 'Маникюр', 'Косметология'],
    };
});

/* ─── Status mappings ─── */
const statusColors = { active: 'green', renovation: 'yellow', temp_closed: 'red', closed: 'gray' };
const statusLabels = { active: 'Работает', renovation: 'На реконструкции', temp_closed: 'Временно закрыт', closed: 'Закрыт' };
const statusIcons = { active: '🟢', renovation: '🔧', temp_closed: '🔴', closed: '⛔' };
const typeLabels = { flagship: '🏆 Флагманский', standard: '📍 Стандартный', mini: '💅 Мини-салон' };
const typeColors = { flagship: 'purple', standard: 'blue', mini: 'gray' };

/* ═══════════════ 3. SCHEDULE DATA ═══════════════ */
const weekSchedule = ref([
    { day: 'Понедельник', from: '09:00', to: '21:00', isWorking: true },
    { day: 'Вторник',     from: '09:00', to: '21:00', isWorking: true },
    { day: 'Среда',       from: '09:00', to: '21:00', isWorking: true },
    { day: 'Четверг',     from: '09:00', to: '21:00', isWorking: true },
    { day: 'Пятница',     from: '09:00', to: '21:00', isWorking: true },
    { day: 'Суббота',     from: '10:00', to: '20:00', isWorking: true },
    { day: 'Воскресенье', from: '10:00', to: '18:00', isWorking: true },
]);
const holidays = ref([
    { date: '01.01.2026', name: 'Новый Год', isWorking: false },
    { date: '08.03.2026', name: '8 Марта',   isWorking: true, note: 'Сокращённый 10:00–18:00' },
    { date: '01.05.2026', name: '1 Мая',     isWorking: false },
]);

/* ═══════════════ 4. SETTINGS ═══════════════ */
const salonSettings = reactive({
    onlineBooking: true,
    bookingAdvanceDays: 30,
    minBookingHours: 2,
    clientNotifications: true,
    smsNotifications: true,
    emailNotifications: true,
    pushNotifications: true,
    whatsappIntegration: true,
    jivosite: false,
    yandexMaps: true,
    twoGis: true,
    paymentMethods: ['Наличные', 'Карта', 'СБП', 'Рассрочка'],
    acquiring: 'Тинькофф',
    cancelPolicy: '24 часа до визита — бесплатно, менее 24 часов — 50% стоимости',
    returnPolicy: 'В течение 14 дней при наличии чека',
});

/* ═══════════════ 5. STAFF ═══════════════ */
const salonStaff = computed(() => {
    const salonName = props.salon.name;
    const matched = (props.masters || []).filter(m => m.salon === salonName);
    if (matched.length > 0) return matched.map(m => ({ ...m, role: 'Мастер', loadPct: Math.round(50 + Math.random() * 45) }));
    return [
        { id: 101, name: 'Анна Соколова',    specialization: 'Стрижки, Окрашивание', role: 'Мастер',        level: 'Топ',     rating: 4.9, isOnline: true,  loadPct: 92 },
        { id: 102, name: 'Ольга Демидова',   specialization: 'Маникюр, Педикюр',     role: 'Мастер',        level: 'Мастер',  rating: 4.8, isOnline: true,  loadPct: 81 },
        { id: 103, name: 'Светлана Романова', specialization: 'Косметология',         role: 'Мастер',        level: 'Мастер',  rating: 4.7, isOnline: false, loadPct: 74 },
        { id: 104, name: 'Кристина Лебедева', specialization: 'Брови, Ресницы',       role: 'Мастер',        level: 'Джуниор', rating: 4.5, isOnline: true,  loadPct: 65 },
        { id: 105, name: 'Ирина Павлова',     specialization: 'Администрирование',    role: 'Администратор', level: '—',       rating: 0,   isOnline: true,  loadPct: 0 },
    ];
});

const levelColors = { 'Топ': 'purple', 'Мастер': 'blue', 'Джуниор': 'gray', '—': 'gray' };

/* ═══════════════ 6. FINANCES ═══════════════ */
const financeStats = computed(() => ({
    currentBalance: 284000,
    revenueToday: 42000,
    revenueWeek: 218000,
    revenueMonth: 920000,
    planMonth: 1000000,
    avgCheck: 3250,
    commissions: 368000,
    rent: 150000,
    utilities: 28000,
    supplies: 45000,
    otherExpenses: 12000,
}));

const profitLoss = computed(() => {
    const f = financeStats.value;
    const totalExpenses = f.commissions + f.rent + f.utilities + f.supplies + f.otherExpenses;
    const profit = f.revenueMonth - totalExpenses;
    return { revenue: f.revenueMonth, expenses: totalExpenses, profit, margin: Math.round(profit / f.revenueMonth * 100) };
});

const monthlyRevenue = ref([
    { month: 'Окт', revenue: 780000, expenses: 620000 },
    { month: 'Ноя', revenue: 840000, expenses: 650000 },
    { month: 'Дек', revenue: 1050000, expenses: 710000 },
    { month: 'Янв', revenue: 650000, expenses: 580000 },
    { month: 'Фев', revenue: 870000, expenses: 660000 },
    { month: 'Мар', revenue: 920000, expenses: 690000 },
]);

/* ═══════════════ 6a. REVENUE BREAKDOWN (Структура выручки) ═══════════════ */
const revPeriod = ref('month');
const revPeriodOptions = [
    { key: 'today',   label: 'Сегодня' },
    { key: '7d',      label: '7 дней' },
    { key: '30d',     label: '30 дней' },
    { key: 'month',   label: 'Месяц' },
    { key: 'quarter', label: 'Квартал' },
    { key: 'year',    label: 'Год' },
    { key: 'custom',  label: 'Произвольный' },
];
const revCustomFrom = ref('');
const revCustomTo = ref('');
const revShowCompare = ref(false);
const revDrillCategory = ref(null);
const revActiveSlice = ref('source');

const revTotalByPeriod = computed(() => {
    const map = {
        today:   { total: 42000,   prev: 38000,   delta: 10.5 },
        '7d':    { total: 218000,  prev: 195000,  delta: 11.8 },
        '30d':   { total: 920000,  prev: 840000,  delta: 9.5  },
        month:   { total: 920000,  prev: 840000,  delta: 9.5  },
        quarter: { total: 2440000, prev: 2270000, delta: 7.5  },
        year:    { total: 9650000, prev: 8800000, delta: 9.7  },
        custom:  { total: 920000,  prev: 840000,  delta: 9.5  },
    };
    return map[revPeriod.value] || map.month;
});

/* 2.1 Revenue by service categories (pie / donut) */
const revByCategory = computed(() => [
    { name: 'Парикмахерские услуги', revenue: 322000, count: 68, pct: 35.0, color: '#6366f1' },
    { name: 'Окрашивание',            revenue: 184000, count: 23, pct: 20.0, color: '#f59e0b' },
    { name: 'Маникюр / Педикюр',      revenue: 147200, count: 82, pct: 16.0, color: '#ec4899' },
    { name: 'Косметология',           revenue: 119600, count: 18, pct: 13.0, color: '#14b8a6' },
    { name: 'Ресницы / Брови',        revenue: 73600,  count: 46, pct: 8.0,  color: '#8b5cf6' },
    { name: 'Массаж / SPA',           revenue: 46000,  count: 14, pct: 5.0,  color: '#22c55e' },
    { name: 'Прочее',                 revenue: 27600,  count: 12, pct: 3.0,  color: '#94a3b8' },
]);

const revDonutGradient = computed(() => {
    let css = '', start = 0;
    revByCategory.value.forEach((c, i) => {
        if (i > 0) css += ', ';
        css += `${c.color} ${start}% ${start + c.pct}%`;
        start += c.pct;
    });
    return `conic-gradient(${css})`;
});

/* 2.2 Revenue by masters (bar chart top-10) */
const revByMasters = computed(() => [
    { name: 'Анна Соколова',      revenue: 184000, pct: 20.0, avgCheck: 4600, services: 40 },
    { name: 'Ольга Демидова',     revenue: 147200, pct: 16.0, avgCheck: 1800, services: 82 },
    { name: 'Светлана Романова',  revenue: 119600, pct: 13.0, avgCheck: 6640, services: 18 },
    { name: 'Кристина Лебедева',  revenue: 101200, pct: 11.0, avgCheck: 2200, services: 46 },
    { name: 'Мария Козлова',      revenue: 92000,  pct: 10.0, avgCheck: 3830, services: 24 },
    { name: 'Елена Иванова',      revenue: 73600,  pct: 8.0,  avgCheck: 5260, services: 14 },
    { name: 'Дарья Петрова',      revenue: 55200,  pct: 6.0,  avgCheck: 2760, services: 20 },
    { name: 'Наталья Волкова',    revenue: 46000,  pct: 5.0,  avgCheck: 4180, services: 11 },
    { name: 'Виктория Сидорова',  revenue: 36800,  pct: 4.0,  avgCheck: 3070, services: 12 },
    { name: 'Юлия Миронова',      revenue: 27600,  pct: 3.0,  avgCheck: 2300, services: 12 },
]);
const revMastersMax = computed(() => Math.max(...revByMasters.value.map(m => m.revenue)));

/* 2.3 Revenue heatmap (days × hours) */
const revHeatmapData = computed(() => {
    const days = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
    const hours = ['09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20'];
    const base = [
        [3200, 4800, 6200, 7100, 5800, 6400, 7200, 8100, 7500, 6800, 4200, 2800],
        [2800, 4200, 5800, 6800, 6200, 7000, 7800, 8400, 7200, 6400, 3800, 2400],
        [3500, 5200, 6800, 7500, 6800, 7200, 8000, 8800, 7800, 7000, 4500, 3000],
        [3000, 4500, 6000, 7000, 6500, 7100, 7500, 8200, 7400, 6600, 4000, 2600],
        [4200, 5800, 7200, 8000, 7500, 8200, 9000, 9800, 8800, 7800, 5500, 3800],
        [5500, 7000, 8500, 9200, 8800, 9500, 10200, 10800, 9500, 8500, 6200, 4200],
        [3800, 5000, 6000, 6500, 6200, 5800, 5200, 4800, 4200, 3500, 2800, 2000],
    ];
    const maxVal = Math.max(...base.flat());
    return days.map((day, di) => ({
        day,
        hours: hours.map((h, hi) => ({
            hour: h,
            revenue: base[di][hi],
            intensity: Math.round(base[di][hi] / maxVal * 100),
        })),
    }));
});
function revHeatColor(intensity) {
    if (intensity >= 70) return '#22c55e';
    if (intensity >= 40) return '#f59e0b';
    return '#94a3b8';
}

/* 2.4 Detail table (per-service) */
const revDetailTable = computed(() => [
    { service: 'Стрижка женская',        cat: 'Парикмахерские услуги', count: 42, revenue: 168000, pct: 18.3, avgCheck: 4000, topMaster: 'Анна Соколова',     delta: 12.5 },
    { service: 'Окрашивание корней',     cat: 'Окрашивание',           count: 15, revenue: 112500, pct: 12.2, avgCheck: 7500, topMaster: 'Анна Соколова',     delta: 8.0  },
    { service: 'Маникюр с покрытием',    cat: 'Маникюр / Педикюр',     count: 58, revenue: 104400, pct: 11.3, avgCheck: 1800, topMaster: 'Ольга Демидова',    delta: 15.2 },
    { service: 'Чистка лица ультразвук', cat: 'Косметология',          count: 12, revenue: 84000,  pct: 9.1,  avgCheck: 7000, topMaster: 'Светлана Романова', delta: -2.3 },
    { service: 'Ламинирование ресниц',   cat: 'Ресницы / Брови',       count: 28, revenue: 72800,  pct: 7.9,  avgCheck: 2600, topMaster: 'Кристина Лебедева', delta: 22.0 },
    { service: 'Полное окрашивание',     cat: 'Окрашивание',           count: 8,  revenue: 71200,  pct: 7.7,  avgCheck: 8900, topMaster: 'Анна Соколова',     delta: 5.1  },
    { service: 'Стрижка мужская',        cat: 'Парикмахерские услуги', count: 26, revenue: 52000,  pct: 5.7,  avgCheck: 2000, topMaster: 'Мария Козлова',     delta: 3.4  },
    { service: 'Массаж лица / тела',     cat: 'Массаж / SPA',          count: 14, revenue: 46000,  pct: 5.0,  avgCheck: 3286, topMaster: 'Елена Иванова',     delta: 10.0 },
    { service: 'Педикюр аппаратный',     cat: 'Маникюр / Педикюр',     count: 24, revenue: 42800,  pct: 4.7,  avgCheck: 1783, topMaster: 'Ольга Демидова',    delta: -1.8 },
    { service: 'Мезотерапия',            cat: 'Косметология',          count: 6,  revenue: 35400,  pct: 3.8,  avgCheck: 5900, topMaster: 'Светлана Романова', delta: -5.2 },
    { service: 'Коррекция бровей',       cat: 'Ресницы / Брови',       count: 18, revenue: 28800,  pct: 3.1,  avgCheck: 1600, topMaster: 'Кристина Лебедева', delta: 7.8  },
    { service: 'Прочие услуги',          cat: 'Прочее',                count: 8,  revenue: 22100,  pct: 2.4,  avgCheck: 2763, topMaster: '—',                 delta: 1.2  },
]);

const revFilteredDetail = computed(() => {
    if (!revDrillCategory.value) return revDetailTable.value;
    return revDetailTable.value.filter(r => r.cat === revDrillCategory.value);
});

/* Additional breakdowns */
const revBySource = computed(() => [
    { name: 'Прямые записи (сайт / приложение)', revenue: 368000, pct: 40, color: '#6366f1' },
    { name: 'Повторные визиты',                   revenue: 276000, pct: 30, color: '#22c55e' },
    { name: 'Яндекс.Карты / 2ГИС',               revenue: 119600, pct: 13, color: '#f59e0b' },
    { name: 'Сарафанное радио',                    revenue: 92000,  pct: 10, color: '#ec4899' },
    { name: 'Промоакции / соцсети',                revenue: 64400,  pct: 7,  color: '#8b5cf6' },
]);

const revByPayment = computed(() => [
    { name: 'Банковская карта',    revenue: 506000, pct: 55, icon: '💳' },
    { name: 'СБП',                  revenue: 184000, pct: 20, icon: '📲' },
    { name: 'Наличные',             revenue: 110400, pct: 12, icon: '💵' },
    { name: 'Рассрочка',            revenue: 73600,  pct: 8,  icon: '📄' },
    { name: 'Бонусы / подарочные',  revenue: 46000,  pct: 5,  icon: '🎁' },
]);

const revByTimeOfDay = computed(() => [
    { name: 'Утро (09–12)',   revenue: 184000, pct: 20, icon: '🌅' },
    { name: 'День (12–16)',   revenue: 322000, pct: 35, icon: '☀️' },
    { name: 'Вечер (16–20)',  revenue: 331200, pct: 36, icon: '🌆' },
    { name: 'Позднее (20+)', revenue: 82800,  pct: 9,  icon: '🌙' },
]);

const revByClientType = computed(() => [
    { name: 'Постоянные',          revenue: 552000, pct: 60, icon: '👑' },
    { name: 'Новые',                revenue: 276000, pct: 30, icon: '✨' },
    { name: 'B2B / корпоративные', revenue: 92000,  pct: 10, icon: '🏢' },
]);

const revByPromo = computed(() => [
    { code: 'WELCOME20', revenue: 36800, uses: 18, avgCheck: 2044, discount: '20%', active: true  },
    { code: 'SPRING15',  revenue: 27600, uses: 12, avgCheck: 2300, discount: '15%', active: true  },
    { code: 'VIP10',     revenue: 55200, uses: 24, avgCheck: 2300, discount: '10%', active: true  },
    { code: 'BIRTHDAY',  revenue: 18400, uses: 8,  avgCheck: 2300, discount: '25%', active: true  },
    { code: 'FLASH50',   revenue: 9200,  uses: 6,  avgCheck: 1533, discount: '50%', active: false },
]);

const revSliceOptions = [
    { key: 'source',  label: 'Источник', icon: '🔗' },
    { key: 'payment', label: 'Оплата',   icon: '💳' },
    { key: 'time',    label: 'Время',    icon: '🕐' },
    { key: 'client',  label: 'Клиент',   icon: '👤' },
    { key: 'promo',   label: 'Промо',    icon: '🎟️' },
];

function exportRevenue(format) {
    const header = '\uFEFFПериод;Выручка;Комиссия;Заказов\n';
    const rows = 'Апрель 2026;1250000;175000;342\n';
    const content = header + rows;
    const ext = format === 'pdf' ? 'pdf' : 'csv';
    const mime = format === 'pdf' ? 'application/pdf' : 'text/csv;charset=utf-8;';
    const blob = new Blob([content], { type: mime });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `revenue_${Date.now()}.${ext}`;
    a.click();
    URL.revokeObjectURL(url);
}
function saveAsMyReport() {
    const report = { id: Date.now(), name: 'Мой отчёт', createdAt: new Date().toLocaleDateString('ru-RU') };
    const saved = JSON.parse(localStorage.getItem('beauty_saved_reports') || '[]');
    saved.push(report);
    localStorage.setItem('beauty_saved_reports', JSON.stringify(saved));
}

/* ═══════════════ 7. LOAD & PERFORMANCE ═══════════════ */
const heatmapData = computed(() => {
    const days = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
    const hours = ['09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20'];
    return days.map(day => ({
        day,
        hours: hours.map(h => {
            const base = day === 'Сб' || day === 'Вс' ? 40 : 60;
            const peak = (h >= '11' && h <= '14') || (h >= '17' && h <= '19') ? 30 : 0;
            const val = Math.min(Math.round(base + peak + Math.random() * 20), 100);
            return { hour: h, load: val, color: val >= 80 ? '#ef4444' : val >= 50 ? '#f59e0b' : '#22c55e' };
        }),
    }));
});

const avgLoad = computed(() => 78);

const topSalonServices = ref([
    { name: 'Окрашивание AirTouch', count: 86, revenue: 731000 },
    { name: 'Стрижка женская',      count: 145, revenue: 362500 },
    { name: 'Укладка',              count: 102, revenue: 183600 },
    { name: 'Маникюр + покрытие',   count: 134, revenue: 267000 },
    { name: 'Уход Olaplex',         count: 67,  revenue: 100500 },
]);

const topMastersByRevenue = computed(() => {
    return [...salonStaff.value]
        .filter(s => s.role === 'Мастер')
        .sort((a, b) => (b.loadPct || 0) - (a.loadPct || 0))
        .slice(0, 5);
});

const roomOccupancy = ref([
    { room: 'Кабинет 1 (Барбер)',      pct: 92 },
    { room: 'Кабинет 2 (Колористика)', pct: 88 },
    { room: 'Кабинет 3 (Маникюр)',     pct: 85 },
    { room: 'Кабинет 4 (Маникюр)',     pct: 79 },
    { room: 'Кабинет 5 (Косметолог)',  pct: 72 },
    { room: 'Кабинет 6 (SPA)',         pct: 58 },
]);

/* ═══════════════ 8. ANALYTICS ═══════════════ */
const branchComparison = computed(() => {
    return (props.salons || [props.salon]).map(s => ({
        name: s.name,
        revenue: s.name === props.salon.name ? financeStats.value.revenueMonth : Math.round(500000 + Math.random() * 500000),
        avgCheck: s.name === props.salon.name ? financeStats.value.avgCheck : Math.round(2500 + Math.random() * 1500),
        rating: s.rating || 4.5,
        load: s.name === props.salon.name ? avgLoad.value : Math.round(50 + Math.random() * 40),
        highlight: s.name === props.salon.name,
    }));
});

const clientSources = ref([
    { source: 'Онлайн-запись', pct: 42, count: 580 },
    { source: 'Яндекс.Карты', pct: 22, count: 304 },
    { source: '2ГИС',         pct: 14, count: 193 },
    { source: 'Рекомендации',  pct: 12, count: 166 },
    { source: 'Instagram',    pct: 6,  count: 83 },
    { source: 'Прямые звонки', pct: 4,  count: 55 },
]);

const retentionRate = ref(68);

const salonReviews = ref([
    { id: 1, client: 'Мария К.',    rating: 5, text: 'Лучший салон в городе! Обстановка, мастера — всё на высоте.', date: '07.04.2026', service: 'Окрашивание' },
    { id: 2, client: 'Елена В.',    rating: 5, text: 'Уютно, чисто, мастера профессионалы. Рекомендую!', date: '05.04.2026', service: 'Стрижка' },
    { id: 3, client: 'Ольга Р.',    rating: 4, text: 'Хороший салон, но пришлось немного подождать администратора.', date: '03.04.2026', service: 'Маникюр' },
    { id: 4, client: 'Татьяна С.',  rating: 5, text: 'Потрясающая атмосфера! Приду ещё 100 раз.', date: '01.04.2026', service: 'SPA' },
    { id: 5, client: 'Наталья Б.',  rating: 4, text: 'Всё понравилось, кроме парковки. Негде встать.', date: '28.03.2026', service: 'Укладка' },
]);

const avgSalonRating = computed(() => {
    if (!salonReviews.value.length) return 0;
    return (salonReviews.value.reduce((s, r) => s + r.rating, 0) / salonReviews.value.length).toFixed(1);
});

const ratingDistribution = computed(() => {
    const dist = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
    for (const r of salonReviews.value) dist[r.rating]++;
    const max = Math.max(...Object.values(dist), 1);
    return Object.entries(dist).reverse().map(([star, count]) => ({
        star: Number(star), count, pct: Math.round(count / max * 100),
    }));
});

/* ═══════════════ 9. DOCUMENTS ═══════════════ */
const documents = ref([
    { id: 1, name: 'Договор аренды №А-2020/06',                type: 'lease',    date: '15.06.2020', expires: '14.06.2027', status: 'active' },
    { id: 2, name: 'Заключение СЭС №12345',                    type: 'sanitary', date: '20.08.2024', expires: '19.08.2026', status: 'active' },
    { id: 3, name: 'Акт пожарной безопасности',                 type: 'fire',     date: '10.01.2025', expires: '09.01.2027', status: 'active' },
    { id: 4, name: 'Лицензия на мед. деятельность ЛО-0012345', type: 'license',  date: '01.03.2023', expires: '28.02.2028', status: 'active' },
    { id: 5, name: 'Страховой полис помещения',                 type: 'insurance', date: '01.01.2026', expires: '31.12.2026', status: 'active' },
    { id: 6, name: 'Договор эквайринга Тинькофф',               type: 'contract', date: '10.06.2020', expires: '—',          status: 'active' },
]);
const docTypeLabels = { lease: '🏠 Аренда', sanitary: '🧪 СЭС', fire: '🔥 Пожарная', license: '📋 Лицензия', insurance: '🛡️ Страховка', contract: '📄 Договор' };

const gallery = ref([
    { id: 1, label: 'Фасад салона',       type: 'photo' },
    { id: 2, label: 'Зона ресепшн',       type: 'photo' },
    { id: 3, label: 'Кабинет колористики', type: 'photo' },
    { id: 4, label: 'Кабинет маникюра',   type: 'photo' },
    { id: 5, label: 'SPA-зона',           type: 'photo' },
    { id: 6, label: 'Зона ожидания',      type: 'photo' },
    { id: 7, label: 'Видео-тур 3D',       type: 'video' },
    { id: 8, label: 'Барбершоп',          type: 'photo' },
]);

/* ═══════════════ 10. B2B TOOLS ═══════════════ */
const salonPriceList = ref([
    { service: 'Стрижка женская',      standardPrice: 2500, salonPrice: 2500, diff: 0 },
    { service: 'Окрашивание AirTouch', standardPrice: 8500, salonPrice: 8000, diff: -500 },
    { service: 'Укладка',              standardPrice: 1800, salonPrice: 1800, diff: 0 },
    { service: 'Маникюр + покрытие',   standardPrice: 2000, salonPrice: 2200, diff: 200 },
    { service: 'Уход Olaplex',         standardPrice: 1500, salonPrice: 1500, diff: 0 },
]);

const salonPromos = ref([
    { id: 1, name: 'Весенняя скидка 15%',     code: 'SPRING15',  validUntil: '30.04.2026', usedCount: 87,  status: 'active' },
    { id: 2, name: 'Приведи подругу',          code: 'FRIEND500', validUntil: '31.12.2026', usedCount: 134, status: 'active' },
    { id: 3, name: 'Первый визит -20%',        code: 'FIRST20',   validUntil: '31.12.2026', usedCount: 215, status: 'active' },
]);

const stockItems = ref([
    { id: 1, name: 'Краска Wella Koleston',    qty: 34, minQty: 10, unit: 'шт', lastOrder: '02.04.2026' },
    { id: 2, name: 'Оксид 6%',                 qty: 12, minQty: 8,  unit: 'л',  lastOrder: '02.04.2026' },
    { id: 3, name: 'Гель-лак CND Pink',        qty: 1,  minQty: 3,  unit: 'шт', lastOrder: '25.03.2026' },
    { id: 4, name: 'Маска для волос Olaplex',   qty: 3,  minQty: 5,  unit: 'шт', lastOrder: '20.03.2026' },
    { id: 5, name: 'Фольга 100м',              qty: 8,  minQty: 5,  unit: 'рул', lastOrder: '28.03.2026' },
    { id: 6, name: 'Шампунь Matrix 1000мл',    qty: 6,  minQty: 4,  unit: 'шт', lastOrder: '01.04.2026' },
]);

const auditLog = ref([
    { id: 1, date: '08.04.2026 14:30', user: 'Ирина Павлова',    action: 'Перенесла запись клиента Мария К. на 10.04',        severity: 'info' },
    { id: 2, date: '08.04.2026 11:15', user: 'Анна Соколова',    action: 'Добавила фото в портфолио (3 шт.)',                 severity: 'info' },
    { id: 3, date: '07.04.2026 18:00', user: 'Система',          action: 'Автоотмена записи: клиент Виктория Н. (неявка)',    severity: 'warning' },
    { id: 4, date: '07.04.2026 09:30', user: 'Ирина Павлова',    action: 'Изменила график Ольги Демидовой на 08–14.04',       severity: 'info' },
    { id: 5, date: '06.04.2026 16:45', user: 'Система',          action: 'Низкий остаток: Гель-лак CND Pink (1 шт. из мин. 3)', severity: 'critical' },
    { id: 6, date: '06.04.2026 10:00', user: 'Кристина Лебедева', action: 'Отменила свою смену на 07.04 (болезнь)',            severity: 'warning' },
]);
const severityColors = { info: 'blue', warning: 'yellow', critical: 'red' };
const severityLabels = { info: 'ℹ️', warning: '⚠️', critical: '🚨' };

/* ═══════════════ 11. CLIENT ACQUISITION SOURCES ═══════════════ */
const srcPeriod = ref('month');
const srcPeriodOptions = [
    { key: 'week',    label: 'Неделя' },
    { key: 'month',   label: 'Месяц' },
    { key: 'quarter', label: 'Квартал' },
    { key: 'year',    label: 'Год' },
    { key: 'custom',  label: 'Свой период' },
];

const srcColors = [
    '#6366f1', '#f59e0b', '#22c55e', '#ec4899', '#3b82f6',
    '#8b5cf6', '#14b8a6', '#f97316', '#64748b', '#a855f7',
];

const srcMainTable = ref([
    { id: 1,  name: 'Онлайн-запись через Экосистему',                     icon: '🌐', clients: 142, pctClients: 38, revenue: 1248000, pctRevenue: 42, avgCheck: 8790,  dynamics: 18,  roi: null },
    { id: 2,  name: 'Реклама Экосистема Кота',                            icon: '🐱', clients: 67,  pctClients: 18, revenue: 684000,  pctRevenue: 23, avgCheck: 10209, dynamics: 9,   roi: 4.8 },
    { id: 3,  name: 'Переходы из поисковиков',                            icon: '🔍', clients: 58,  pctClients: 15, revenue: 521000,  pctRevenue: 17, avgCheck: 8983,  dynamics: 29,  roi: null },
    { id: 4,  name: 'Реклама у блогеров через Экосистему',                icon: '📸', clients: 44,  pctClients: 12, revenue: 461000,  pctRevenue: 15, avgCheck: 10477, dynamics: 34,  roi: 5.9 },
    { id: 5,  name: 'Социальные сети',                                    icon: '💬', clients: 37,  pctClients: 10, revenue: 298000,  pctRevenue: 10, avgCheck: 8054,  dynamics: -4,  roi: 2.8 },
    { id: 6,  name: 'Переходы из ленты новостей Экосистемы',              icon: '📰', clients: 29,  pctClients: 8,  revenue: 214000,  pctRevenue: 7,  avgCheck: 7379,  dynamics: 41,  roi: null },
    { id: 7,  name: 'Переходы из видео и шортс',                          icon: '🎬', clients: 26,  pctClients: 7,  revenue: 187000,  pctRevenue: 6,  avgCheck: 7192,  dynamics: 52,  roi: null },
    { id: 8,  name: 'Рекомендации знакомых / сарафан',                    icon: '🗣️', clients: 45,  pctClients: 12, revenue: 310000,  pctRevenue: 10, avgCheck: 6889,  dynamics: 27,  roi: null },
    { id: 9,  name: 'Запись администратором по телефону',                  icon: '📞', clients: 33,  pctClients: 9,  revenue: 218000,  pctRevenue: 7,  avgCheck: 6606,  dynamics: -12, roi: null },
    { id: 10, name: 'Партнёрские интеграции',                             icon: '🤝', clients: 12,  pctClients: 3,  revenue: 87000,   pctRevenue: 3,  avgCheck: 7250,  dynamics: 41,  roi: 6.1 },
]);

const srcTotalClients = computed(() => srcMainTable.value.reduce((s, r) => s + r.clients, 0));
const srcTotalRevenue = computed(() => srcMainTable.value.reduce((s, r) => s + r.revenue, 0));
const srcDonutGradient = computed(() => {
    let angle = 0;
    const segments = srcMainTable.value.map((r, i) => {
        const start = angle;
        const size = (r.revenue / srcTotalRevenue.value) * 360;
        angle += size;
        return `${srcColors[i % srcColors.length]} ${start}deg ${start + size}deg`;
    });
    return `conic-gradient(${segments.join(', ')})`;
});

/* drill-down state */
const srcExpandedRow = ref(null);
function toggleSrcDrill(id) { srcExpandedRow.value = srcExpandedRow.value === id ? null : id; }

/* ─── Drill-down: Реклама Экосистема Кота ─── */
const srcEcoCatCampaigns = ref([
    { id: 1, name: 'Весна красоты 2026',       status: 'active',   budget: 42000,  spent: 38400,  impressions: 148000, clicks: 4120, ctr: 2.78, leads: 312, bookings: 67, cpo: 573,  roas: 4.8, startDate: '01.03.2026', endDate: '30.04.2026' },
    { id: 2, name: 'Окрашивание балаяж',        status: 'active',   budget: 18000,  spent: 14200,  impressions: 62000,  clicks: 2100, ctr: 3.39, leads: 178, bookings: 38, cpo: 374,  roas: 6.1, startDate: '15.03.2026', endDate: '15.04.2026' },
    { id: 3, name: 'Маникюр GelX -20%',         status: 'paused',   budget: 12000,  spent: 11800,  impressions: 51000,  clicks: 1640, ctr: 3.22, leads: 89,  bookings: 24, cpo: 492,  roas: 3.9, startDate: '01.02.2026', endDate: '28.02.2026' },
    { id: 4, name: 'SPA-ритуал выходного дня',  status: 'completed', budget: 25000, spent: 24900,  impressions: 93000,  clicks: 2800, ctr: 3.01, leads: 201, bookings: 42, cpo: 593,  roas: 5.2, startDate: '10.01.2026', endDate: '10.02.2026' },
]);
const srcEcoCatFunnel = ref({ impressions: 354000, clicks: 10660, ctr: 3.01, leads: 780, bookings: 171, convLeadToBook: 21.9 });
const srcEcoCatCreatives = ref([
    { id: 1, name: 'Видео "Преображение за 3 часа"', type: 'video',  impressions: 89000, clicks: 3200, ctr: 3.60, bookings: 42, roas: 6.8 },
    { id: 2, name: 'Карусель до/после',               type: 'carousel', impressions: 72000, clicks: 2400, ctr: 3.33, bookings: 31, roas: 5.1 },
    { id: 3, name: 'Баннер "Весенний образ"',         type: 'banner', impressions: 104000, clicks: 2800, ctr: 2.69, bookings: 28, roas: 3.2 },
    { id: 4, name: 'Шортс "До и после окрашивания"',  type: 'shorts', impressions: 89000, clicks: 2260, ctr: 2.54, bookings: 70, roas: 8.4 },
]);

/* ─── Drill-down: Социальные сети ─── */
const srcSocialPlatforms = ref([
    { id: 1, name: 'VK',        icon: '🟦', followers: 8400,  reach: 42000, clicks: 1640, clients: 14, revenue: 112000, avgCheck: 8000,  dynamics: 6,   er: 4.2 },
    { id: 2, name: 'Instagram',  icon: '📷', followers: 12800, reach: 68000, clicks: 3100, clients: 11, revenue: 94000,  avgCheck: 8545,  dynamics: -8,  er: 3.8 },
    { id: 3, name: 'Telegram',   icon: '✈️', followers: 3200,  reach: 9800,  clicks: 820,  clients: 8,  revenue: 61000,  avgCheck: 7625,  dynamics: 22,  er: 6.1 },
    { id: 4, name: 'TikTok',     icon: '🎵', followers: 5600,  reach: 120000, clicks: 4200, clients: 4, revenue: 31000,  avgCheck: 7750,  dynamics: 48,  er: 8.4 },
]);

/* ─── Drill-down: Реклама у блогеров ─── */
const srcBloggerDetails = ref([
    { id: 1, name: '@krasotka_msk',        platform: 'Instagram', subscribers: 84000,  placements: 3, clicks: 2400, leads: 180, bookings: 18, revenue: 188000, cpo: 2778, roas: 7.5, avgCheck: 10444 },
    { id: 2, name: '@beauty_journal',       platform: 'Telegram',  subscribers: 22000,  placements: 2, clicks: 980,  leads: 112, bookings: 12, revenue: 134000, cpo: 2500, roas: 6.7, avgCheck: 11167 },
    { id: 3, name: '@nails_art_studio',     platform: 'VK',        subscribers: 45000,  placements: 2, clicks: 1200, leads: 78,  bookings: 8,  revenue: 82000,  cpo: 3125, roas: 4.1, avgCheck: 10250 },
    { id: 4, name: '@mama_bloger_spb',      platform: 'TikTok',    subscribers: 310000, placements: 1, clicks: 8200, leads: 420, bookings: 6,  revenue: 57000,  cpo: 5000, roas: 3.8, avgCheck: 9500  },
]);

/* ─── Drill-down: Органический трафик ─── */
const srcOrganicChannels = ref([
    { id: 1, name: 'Яндекс',        icon: '🔴', sessions: 3200, clients: 28, revenue: 248000, convRate: 0.88 },
    { id: 2, name: 'Google',         icon: '🟢', sessions: 1800, clients: 14, revenue: 124000, convRate: 0.78 },
    { id: 3, name: 'Прямые заходы',  icon: '🔗', sessions: 4100, clients: 9,  revenue: 82000,  convRate: 0.22 },
    { id: 4, name: 'Яндекс.Карты',   icon: '📍', sessions: 2400, clients: 4,  revenue: 38000,  convRate: 0.17 },
    { id: 5, name: '2ГИС',           icon: '🗺️', sessions: 1100, clients: 3,  revenue: 29000,  convRate: 0.27 },
]);
const srcTopKeywords = ref([
    { keyword: 'салон красоты москва',          impressions: 14200, clicks: 840, position: 3.2 },
    { keyword: 'окрашивание балаяж цена',       impressions: 8400,  clicks: 620, position: 4.1 },
    { keyword: 'маникюр гель-лак рядом',        impressions: 6100,  clicks: 410, position: 5.8 },
    { keyword: 'spa массаж москва',             impressions: 5200,  clicks: 380, position: 6.4 },
    { keyword: 'стрижка женская недорого',       impressions: 4800,  clicks: 290, position: 7.1 },
    { keyword: 'beauty salon near me',          impressions: 3100,  clicks: 210, position: 8.2 },
    { keyword: 'уход за волосами салон',         impressions: 2900,  clicks: 180, position: 4.7 },
    { keyword: 'ботокс для волос цена',         impressions: 2400,  clicks: 160, position: 5.3 },
]);

/* ─── Drill-down: Переходы из поисковиков (детально) ─── */
const srcSearchLandingPages = ref([
    { page: '/services/coloring',    sessions: 1420, bookings: 28, convRate: 1.97, avgTimeOnPage: '2:48' },
    { page: '/services/manicure',    sessions: 1180, bookings: 14, convRate: 1.19, avgTimeOnPage: '2:12' },
    { page: '/masters',              sessions: 980,  bookings: 8,  convRate: 0.82, avgTimeOnPage: '3:21' },
    { page: '/price',                sessions: 820,  bookings: 5,  convRate: 0.61, avgTimeOnPage: '1:58' },
    { page: '/blog/spring-hair',     sessions: 600,  bookings: 3,  convRate: 0.50, avgTimeOnPage: '4:12' },
]);
const srcSearchFunnel = ref({ sessions: 5000, viewed_service: 3200, started_booking: 980, completed_booking: 58, convRate: 1.16 });

/* ─── Drill-down: Переходы из карт ─── */
const srcMapMetrics = ref({
    yandexMaps: { views: 8400, routes: 1200, calls: 340, bookings: 4, convViewToBook: 0.05 },
    twoGis:     { views: 4200, routes: 620,  calls: 180, bookings: 3, convViewToBook: 0.07 },
    googleMaps: { views: 2100, routes: 310,  calls: 90,  bookings: 1, convViewToBook: 0.05 },
});
const srcMapReviews = ref({ yandex: { rating: 4.8, count: 214 }, twoGis: { rating: 4.7, count: 89 }, google: { rating: 4.9, count: 52 } });

/* ─── Drill-down: Лента новостей ─── */
const srcNewsFeedPosts = ref([
    { id: 1, title: '5 трендов окрашивания весна 2026',            views: 12400, clicks: 840,  bookings: 11, revenue: 96000,  ctr: 6.77 },
    { id: 2, title: 'Как выбрать мастера маникюра — гайд',        views: 8200,  clicks: 620,  bookings: 8,  revenue: 54000,  ctr: 7.56 },
    { id: 3, title: 'SPA-ритуал: что включено и стоит ли?',       views: 6400,  clicks: 410,  bookings: 6,  revenue: 42000,  ctr: 6.41 },
    { id: 4, title: 'Реальные отзывы наших клиентов за март',      views: 4800,  clicks: 280,  bookings: 4,  revenue: 22000,  ctr: 5.83 },
]);
const srcNewsFeedAvgTimeToBooking = ref('2ч 14мин');

/* ─── Drill-down: Видео и шортс (наиболее детальный) ─── */
const srcVideoMainMetrics = ref([
    { metric: 'Просмотры',                  value: 184000,       formatted: '184 000' },
    { metric: 'Уникальные просмотры',       value: 142000,       formatted: '142 000' },
    { metric: 'Среднее время просмотра',     value: 18,           formatted: '18 сек' },
    { metric: 'Досмотры до конца',           value: 42,           formatted: '42%' },
    { metric: 'CTR (клик по ссылке)',        value: 3.8,          formatted: '3.8%' },
    { metric: 'Переходы на профиль',         value: 6992,         formatted: '6 992' },
    { metric: 'Начали запись',               value: 410,          formatted: '410' },
    { metric: 'Завершили запись',            value: 26,           formatted: '26' },
    { metric: 'Конверсия просмотр→запись',   value: 0.014,        formatted: '0.014%' },
    { metric: 'Выручка',                     value: 187000,       formatted: '187 000 ₽' },
    { metric: 'ROAS',                        value: null,         formatted: '—' },
    { metric: 'CPO',                         value: null,         formatted: '—' },
]);

const srcVideoTopVideos = ref([
    { id: 1, title: 'Преображение блондинки за 4 часа',    format: 'shorts', views: 48000,  completion: 54, clicks: 1840, bookings: 8,  revenue: 72000,  roi: 9.2 },
    { id: 2, title: 'Маникюр "Северное сияние"',          format: 'shorts', views: 36000,  completion: 61, clicks: 1420, bookings: 6,  revenue: 42000,  roi: 7.8 },
    { id: 3, title: 'Уход за волосами: 3 шага',           format: 'horizontal', views: 28000, completion: 38, clicks: 980, bookings: 5, revenue: 34000, roi: 5.4 },
    { id: 4, title: 'Мастер-класс от Ольги: стрижка боб', format: 'horizontal', views: 22000, completion: 44, clicks: 640, bookings: 3, revenue: 21000, roi: 4.1 },
    { id: 5, title: 'SPA-ритуал: полный процесс',         format: 'shorts', views: 19000,  completion: 48, clicks: 520,  bookings: 2,  revenue: 11000,  roi: 3.6 },
    { id: 6, title: 'Что нового в весенней коллекции',     format: 'shorts', views: 31000,  completion: 57, clicks: 592,  bookings: 2,  revenue: 7000,   roi: 2.1 },
]);

const srcVideoFormatEfficiency = ref([
    { format: 'Вертикальные шортс (<60с)',    videos: 14, views: 128000, avgCompletion: 52, avgCtr: 4.2, bookings: 18, revenueShare: 62 },
    { format: 'Горизонтальные (1–3 мин)',     videos: 8,  views: 42000,  avgCompletion: 36, avgCtr: 3.1, bookings: 6,  revenueShare: 28 },
    { format: 'С субтитрами',                 videos: 11, views: 98000,  avgCompletion: 58, avgCtr: 4.8, bookings: 16, revenueShare: 71 },
    { format: 'С лицом мастера',              videos: 9,  views: 86000,  avgCompletion: 55, avgCtr: 4.4, bookings: 14, revenueShare: 64 },
    { format: 'Без лица (руки/процесс)',      videos: 13, views: 98000,  avgCompletion: 44, avgCtr: 3.2, bookings: 10, revenueShare: 36 },
]);

const srcVideoGeo = ref([
    { city: 'Москва',          views: 92000, pct: 50, bookings: 18 },
    { city: 'Санкт-Петербург', views: 28000, pct: 15, bookings: 4 },
    { city: 'Казань',          views: 14000, pct: 8,  bookings: 2 },
    { city: 'Краснодар',       views: 11000, pct: 6,  bookings: 1 },
    { city: 'Другие',          views: 39000, pct: 21, bookings: 1 },
]);
const srcVideoDemographics = ref([
    { group: 'Ж 18–24', pct: 28 }, { group: 'Ж 25–34', pct: 38 },
    { group: 'Ж 35–44', pct: 18 }, { group: 'Ж 45+',   pct: 8 },
    { group: 'М 18–34', pct: 5 },  { group: 'М 35+',   pct: 3 },
]);

const srcVideoDailyDynamics = ref([
    { date: '01.04', views: 6200, bookings: 1 }, { date: '02.04', views: 5800, bookings: 0 },
    { date: '03.04', views: 7400, bookings: 2 }, { date: '04.04', views: 8100, bookings: 1 },
    { date: '05.04', views: 9200, bookings: 3 }, { date: '06.04', views: 7800, bookings: 1 },
    { date: '07.04', views: 6600, bookings: 1 }, { date: '08.04', views: 8400, bookings: 2 },
]);

/* ═══════════════ MODALS ═══════════════ */
const showDeactivateConfirm = ref(false);
const showPromoModal = ref(false);
const showStockOrderModal = ref(false);
const orderItem = ref(null);
const orderQty = ref(0);

function editSalon() { emit('edit', props.salon); }
function openCalendar() { emit('open-calendar', props.salon); }
function createBooking() { emit('create-booking', props.salon); }
function addEmployee() { emit('add-employee', props.salon); }
function runPromo() { showPromoModal.value = true; }
function generateReport() { emit('generate-report', props.salon); }
function exportData() { emit('export-data', props.salon); }
function deactivateSalon() { emit('deactivate', props.salon); showDeactivateConfirm.value = false; }
function openStockOrder(item) { orderItem.value = item; orderQty.value = item.minQty * 2; showStockOrderModal.value = true; }

/* ═══════════════ SOURCE CARD NAVIGATION ═══════════════ */
const selectedCampaign = ref(null);
const selectedBlogger = ref(null);
const selectedSocial = ref(null);
const selectedVideo = ref(null);
const selectedSourceDetail = ref(null);
const selectedSourceType = ref(null);

function openCampaignCard(c) { selectedCampaign.value = c; }
function closeCampaignCard() { selectedCampaign.value = null; }
function openBloggerCard(b) { selectedBlogger.value = b; }
function closeBloggerCard() { selectedBlogger.value = null; }
function openSocialCard(p) { selectedSocial.value = p; }
function closeSocialCard() { selectedSocial.value = null; }
function openVideoCard(v) { selectedVideo.value = v; }
function closeVideoCard() { selectedVideo.value = null; }
function openSourceDetail(row, type) { selectedSourceDetail.value = row; selectedSourceType.value = type; }
function closeSourceDetail() { selectedSourceDetail.value = null; selectedSourceType.value = null; }

const srcTypeMap = { 1: 'online_booking', 3: 'search', 6: 'news_feed', 8: 'recommendations', 9: 'admin', 10: 'partners' };
</script>

<template>
<div class="space-y-4">

    <!-- ═══ 1. HEADER ═══ -->
    <div class="rounded-2xl border overflow-hidden" style="background:var(--t-surface);border-color:var(--t-border)">
        <!-- Cover -->
        <div class="h-28 relative" style="background:linear-gradient(135deg, var(--t-gradient-from), var(--t-gradient-via), var(--t-gradient-to))">
            <!-- Gallery thumbnails -->
            <div class="absolute bottom-2 left-4 flex gap-1">
                <div v-for="i in Math.min(gallery.length, 5)" :key="i"
                     class="w-8 h-8 rounded-lg border-2 flex items-center justify-center text-[9px] cursor-pointer hover:scale-110 transition"
                     style="border-color:rgba(255,255,255,.6);background:rgba(0,0,0,.3);color:#fff">
                    📷
                </div>
                <div v-if="gallery.length > 5" class="w-8 h-8 rounded-lg flex items-center justify-center text-[9px]"
                     style="background:rgba(0,0,0,.4);color:#fff">
                    +{{ gallery.length - 5 }}
                </div>
            </div>
            <button class="absolute top-3 right-3 p-2 rounded-xl text-xs" style="background:rgba(0,0,0,.3);color:#fff" @click="$emit('close')">✕ Закрыть</button>
        </div>

        <div class="px-6 pb-5 -mt-8 relative">
            <div class="flex flex-col md:flex-row items-start md:items-end gap-4">
                <!-- Logo / icon -->
                <div class="w-20 h-20 rounded-2xl border-4 flex items-center justify-center text-3xl shadow-lg"
                     :style="`border-color:var(--t-surface);background:var(--t-primary-dim);color:var(--t-primary)`">
                    🏠
                </div>

                <!-- Name & info -->
                <div class="flex-1 min-w-0 pt-1">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-xl font-bold" style="color:var(--t-text)">{{ salonProfile.name }}</h2>
                        <VBadge :color="statusColors[salonProfile.status]" size="sm">
                            {{ statusIcons[salonProfile.status] }} {{ statusLabels[salonProfile.status] }}
                        </VBadge>
                        <VBadge :color="typeColors[salonProfile.type]" size="sm">{{ typeLabels[salonProfile.type] }}</VBadge>
                    </div>
                    <div class="text-sm mt-0.5" style="color:var(--t-text-2)">📍 {{ salonProfile.fullAddress }}</div>
                    <div class="flex items-center gap-4 mt-1.5 flex-wrap">
                        <!-- Rating -->
                        <div class="flex items-center gap-1">
                            <span v-for="st in 5" :key="st" class="text-sm">{{ st <= Math.round(salonProfile.rating) ? '⭐' : '☆' }}</span>
                            <span class="text-sm font-bold ml-1" style="color:var(--t-primary)">{{ salonProfile.rating }}</span>
                            <span class="text-xs" style="color:var(--t-text-3)">({{ salonProfile.reviewsCount }} отзывов)</span>
                        </div>
                        <span class="text-xs" style="color:var(--t-text-3)">📞 {{ salonProfile.phone }}</span>
                        <span class="text-xs" style="color:var(--t-text-3)">🕐 {{ salonProfile.hours }}</span>
                    </div>
                </div>

                <VButton @click="editSalon" size="sm" class="shrink-0">✏️ Редактировать филиал</VButton>
            </div>
        </div>
    </div>

    <!-- ═══ 2. ACTION BAR ═══ -->
    <div class="flex flex-wrap gap-2">
        <VButton size="sm" @click="openCalendar">📅 Календарь</VButton>
        <VButton size="sm" @click="createBooking">➕ Новая запись</VButton>
        <VButton size="sm" variant="outline" @click="addEmployee">👤 Добавить сотрудника</VButton>
        <VButton size="sm" variant="outline" @click="runPromo">🎉 Запустить акцию</VButton>
        <VButton size="sm" variant="outline" @click="generateReport">📊 Отчёт</VButton>
        <VButton size="sm" variant="outline" @click="exportData">📤 Экспорт</VButton>
        <VButton size="sm" variant="outline" @click="showDeactivateConfirm = true" class="ml-auto">🔴 Закрыть филиал</VButton>
    </div>

    <!-- ═══ PROFILE TABS ═══ -->
    <div class="flex gap-1 flex-wrap">
        <button v-for="t in profileTabs" :key="t.key"
                @click="activeTab = t.key"
                class="px-3 py-1.5 rounded-xl text-xs font-medium transition-colors"
                :style="activeTab === t.key
                    ? 'background:var(--t-primary);color:#fff'
                    : 'background:var(--t-surface);color:var(--t-text-2)'">
            {{ t.label }}
        </button>
    </div>

    <!-- ═══ 3. INFO TAB ═══ -->
    <div v-if="activeTab === 'info'" class="space-y-4">
        <VCard title="📋 Основная информация">
            <div class="grid md:grid-cols-2 gap-x-8 gap-y-3">
                <div v-for="item in [
                    { label: '📍 Полный адрес',    value: salonProfile.fullAddress },
                    { label: '📞 Телефон',          value: salonProfile.phone },
                    { label: '💬 WhatsApp',          value: salonProfile.whatsapp },
                    { label: '📧 Email',             value: salonProfile.email },
                    { label: '📅 Дата открытия',     value: salonProfile.openedDate },
                    { label: '📐 Площадь',           value: salonProfile.area + ' м²' },
                    { label: '🚪 Кабинетов',         value: salonProfile.rooms },
                    { label: '💺 Рабочих мест',      value: salonProfile.workstations },
                    { label: '🏷️ Тип филиала',      value: typeLabels[salonProfile.type] },
                    { label: '🔢 ИНН',              value: salonProfile.inn },
                    { label: '🔢 ОГРН',             value: salonProfile.ogrn },
                    { label: '🏦 Р/сч',             value: salonProfile.bankAccount },
                ]" :key="item.label" class="flex items-start gap-2 py-1.5 border-b" style="border-color:var(--t-border)">
                    <span class="text-xs font-medium w-40 shrink-0" style="color:var(--t-text-3)">{{ item.label }}</span>
                    <span class="text-sm" style="color:var(--t-text)">{{ item.value }}</span>
                </div>
            </div>
        </VCard>

        <!-- Schedule -->
        <VCard title="🕐 График работы">
            <div class="space-y-1.5">
                <div v-for="d in weekSchedule" :key="d.day"
                     class="flex items-center justify-between py-1.5 border-b" style="border-color:var(--t-border)">
                    <span class="text-sm w-32" style="color:var(--t-text)">{{ d.day }}</span>
                    <span v-if="d.isWorking" class="text-sm font-medium" style="color:var(--t-primary)">{{ d.from }} — {{ d.to }}</span>
                    <span v-else class="text-sm" style="color:var(--t-text-3)">Выходной</span>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-xs font-semibold mb-2" style="color:var(--t-text-2)">🎄 Праздничные дни</div>
                <div class="flex flex-wrap gap-2">
                    <div v-for="h in holidays" :key="h.date"
                         class="px-3 py-1.5 rounded-lg text-xs border"
                         :style="`background:${h.isWorking ? 'var(--t-primary-dim)' : 'var(--t-bg)'};border-color:var(--t-border);color:var(--t-text)`">
                        {{ h.date }} · {{ h.name }} {{ h.note ? '(' + h.note + ')' : h.isWorking ? '' : '— выходной' }}
                    </div>
                </div>
            </div>
        </VCard>

        <!-- Contact person -->
        <VCard title="👤 Контактное лицо">
            <div class="flex items-center gap-4 p-3 rounded-xl" style="background:var(--t-bg)">
                <div class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold"
                     style="background:var(--t-primary-dim);color:var(--t-primary)">
                    {{ salonProfile.admin.name?.charAt(0) || 'A' }}
                </div>
                <div>
                    <div class="text-sm font-bold" style="color:var(--t-text)">{{ salonProfile.admin.name }}</div>
                    <div class="text-xs" style="color:var(--t-text-2)">{{ salonProfile.admin.role }}</div>
                    <div class="text-xs" style="color:var(--t-text-3)">📞 {{ salonProfile.admin.phone }}</div>
                </div>
            </div>
        </VCard>

        <!-- Zones -->
        <VCard title="🏷️ Зоны филиала">
            <div class="flex flex-wrap gap-2">
                <span v-for="z in salonProfile.zones" :key="z"
                      class="px-3 py-1.5 rounded-full text-xs font-medium border"
                      style="background:var(--t-primary-dim);color:var(--t-primary);border-color:var(--t-primary)">
                    {{ z }}
                </span>
            </div>
        </VCard>
    </div>

    <!-- ═══ 4. SETTINGS TAB ═══ -->
    <div v-if="activeTab === 'settings'" class="space-y-4">
        <VCard title="📅 Онлайн-запись">
            <div class="space-y-3">
                <div class="flex items-center justify-between p-2 rounded-lg" style="background:var(--t-bg)">
                    <span class="text-sm" style="color:var(--t-text)">Онлайн-запись включена</span>
                    <button @click="salonSettings.onlineBooking = !salonSettings.onlineBooking"
                            class="w-12 h-6 rounded-full transition-colors flex items-center px-0.5"
                            :style="`background:${salonSettings.onlineBooking ? 'var(--t-primary)' : 'var(--t-border)'}`">
                        <div class="w-5 h-5 rounded-full bg-white transition-transform shadow"
                             :style="`transform:translateX(${salonSettings.onlineBooking ? '24px' : '0'})`"></div>
                    </button>
                </div>
                <div class="grid md:grid-cols-2 gap-3">
                    <div class="p-3 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <div class="text-xs mb-1" style="color:var(--t-text-3)">Запись заранее, дней</div>
                        <div class="text-sm font-bold" style="color:var(--t-text)">{{ salonSettings.bookingAdvanceDays }}</div>
                    </div>
                    <div class="p-3 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <div class="text-xs mb-1" style="color:var(--t-text-3)">Мин. часов до записи</div>
                        <div class="text-sm font-bold" style="color:var(--t-text)">{{ salonSettings.minBookingHours }}</div>
                    </div>
                </div>
            </div>
        </VCard>

        <VCard title="🔔 Уведомления клиентов">
            <div class="space-y-2">
                <div v-for="ch in [
                    { key: 'smsNotifications',   label: '📱 SMS-уведомления' },
                    { key: 'emailNotifications',  label: '📧 Email-уведомления' },
                    { key: 'pushNotifications',   label: '🔔 Push-уведомления' },
                ]" :key="ch.key" class="flex items-center justify-between p-2 rounded-lg" style="background:var(--t-bg)">
                    <span class="text-sm" style="color:var(--t-text)">{{ ch.label }}</span>
                    <button @click="salonSettings[ch.key] = !salonSettings[ch.key]"
                            class="w-12 h-6 rounded-full transition-colors flex items-center px-0.5"
                            :style="`background:${salonSettings[ch.key] ? 'var(--t-primary)' : 'var(--t-border)'}`">
                        <div class="w-5 h-5 rounded-full bg-white transition-transform shadow"
                             :style="`transform:translateX(${salonSettings[ch.key] ? '24px' : '0'})`"></div>
                    </button>
                </div>
            </div>
        </VCard>

        <VCard title="🔗 Интеграции">
            <div class="space-y-2">
                <div v-for="int in [
                    { key: 'whatsappIntegration', label: '💬 WhatsApp Business', icon: '💬' },
                    { key: 'jivosite',            label: '💭 JivoSite',          icon: '💭' },
                    { key: 'yandexMaps',          label: '🗺️ Яндекс.Карты',     icon: '🗺️' },
                    { key: 'twoGis',              label: '🌐 2GIS',              icon: '🌐' },
                ]" :key="int.key" class="flex items-center justify-between p-3 rounded-lg border"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">{{ int.icon }}</span>
                        <span class="text-sm" style="color:var(--t-text)">{{ int.label }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <VBadge :color="salonSettings[int.key] ? 'green' : 'gray'" size="sm">
                            {{ salonSettings[int.key] ? 'Подключено' : 'Не подключено' }}
                        </VBadge>
                        <button @click="salonSettings[int.key] = !salonSettings[int.key]"
                                class="w-12 h-6 rounded-full transition-colors flex items-center px-0.5"
                                :style="`background:${salonSettings[int.key] ? 'var(--t-primary)' : 'var(--t-border)'}`">
                            <div class="w-5 h-5 rounded-full bg-white transition-transform shadow"
                                 :style="`transform:translateX(${salonSettings[int.key] ? '24px' : '0'})`"></div>
                        </button>
                    </div>
                </div>
            </div>
        </VCard>

        <VCard title="💳 Оплата">
            <div class="flex flex-wrap gap-2 mb-3">
                <span v-for="pm in salonSettings.paymentMethods" :key="pm"
                      class="px-3 py-1.5 rounded-full text-xs font-medium border"
                      style="background:var(--t-primary-dim);color:var(--t-primary);border-color:var(--t-primary)">{{ pm }}</span>
            </div>
            <div class="text-xs" style="color:var(--t-text-3)">Эквайринг: <strong style="color:var(--t-text)">{{ salonSettings.acquiring }}</strong></div>
        </VCard>

        <VCard title="🔄 Политика отмены и возврата">
            <div class="space-y-2">
                <div class="p-3 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-xs font-semibold mb-1" style="color:var(--t-text-3)">Политика отмены</div>
                    <div class="text-sm" style="color:var(--t-text)">{{ salonSettings.cancelPolicy }}</div>
                </div>
                <div class="p-3 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-xs font-semibold mb-1" style="color:var(--t-text-3)">Политика возврата</div>
                    <div class="text-sm" style="color:var(--t-text)">{{ salonSettings.returnPolicy }}</div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ 5. STAFF TAB ═══ -->
    <div v-if="activeTab === 'staff'" class="space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-base font-semibold" style="color:var(--t-text)">👥 Сотрудники филиала ({{ salonStaff.length }})</h3>
            <div class="flex gap-2">
                <VButton size="sm" variant="outline">🔄 Перенести между филиалами</VButton>
                <VButton size="sm" @click="addEmployee">👤 Добавить</VButton>
            </div>
        </div>

        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-3">
            <div v-for="s in salonStaff" :key="s.id"
                 class="p-4 rounded-xl border cursor-pointer hover:shadow-lg transition-shadow"
                 style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="flex items-start gap-3">
                    <div class="relative">
                        <div class="w-11 h-11 rounded-full flex items-center justify-center text-base font-bold"
                             style="background:var(--t-primary-dim);color:var(--t-primary)">{{ s.name?.charAt(0) }}</div>
                        <div v-if="s.isOnline" class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-green-400 rounded-full border-2" style="border-color:var(--t-surface)"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-sm truncate" style="color:var(--t-text)">{{ s.name }}</div>
                        <div class="text-xs" style="color:var(--t-text-2)">{{ s.specialization }}</div>
                        <div class="flex items-center gap-2 mt-1">
                            <VBadge :color="s.role === 'Администратор' ? 'yellow' : levelColors[s.level]" size="sm">{{ s.role === 'Администратор' ? '👑 Админ' : s.level }}</VBadge>
                            <span v-if="s.rating" class="text-xs" style="color:var(--t-text-3)">⭐ {{ s.rating }}</span>
                        </div>
                    </div>
                </div>
                <!-- Load bar -->
                <div v-if="s.role === 'Мастер'" class="mt-3">
                    <div class="flex justify-between text-[10px] mb-0.5" style="color:var(--t-text-3)">
                        <span>Загрузка</span><span :style="`color:${s.loadPct >= 80 ? '#22c55e' : s.loadPct >= 50 ? '#f59e0b' : '#ef4444'}`">{{ s.loadPct }}%</span>
                    </div>
                    <div class="h-1.5 rounded-full" style="background:var(--t-bg)">
                        <div class="h-full rounded-full transition-all"
                             :style="`width:${s.loadPct}%;background:${s.loadPct >= 80 ? '#22c55e' : s.loadPct >= 50 ? '#f59e0b' : '#ef4444'}`"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ 6. FINANCES TAB ═══ -->
    <div v-if="activeTab === 'finance'" class="space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <VStatCard title="Баланс филиала" :value="fmtMoney(financeStats.currentBalance)"><template #icon><span class="text-xl">💰</span></template></VStatCard>
            <VStatCard title="Выручка (мес.)" :value="fmtMoney(financeStats.revenueMonth)"><template #icon><span class="text-xl">📈</span></template></VStatCard>
            <VStatCard title="Средний чек" :value="fmtMoney(financeStats.avgCheck)"><template #icon><span class="text-xl">🧾</span></template></VStatCard>
            <VStatCard title="Прибыль" :value="fmtMoney(profitLoss.profit)"><template #icon><span class="text-xl">💎</span></template></VStatCard>
        </div>

        <!-- Revenue vs Plan -->
        <VCard title="📊 Выполнение плана">
            <div class="flex items-center gap-3 mb-2">
                <div class="flex-1 h-4 rounded-full overflow-hidden" style="background:var(--t-bg)">
                    <div class="h-full rounded-full transition-all" :style="`width:${Math.min(Math.round(financeStats.revenueMonth / financeStats.planMonth * 100), 100)}%;background:var(--t-primary)`"></div>
                </div>
                <span class="text-sm font-bold" style="color:var(--t-primary)">{{ Math.round(financeStats.revenueMonth / financeStats.planMonth * 100) }}%</span>
            </div>
            <div class="flex justify-between text-xs" style="color:var(--t-text-3)">
                <span>Факт: {{ fmtMoney(financeStats.revenueMonth) }}</span>
                <span>План: {{ fmtMoney(financeStats.planMonth) }}</span>
            </div>
        </VCard>

        <!-- Revenue by period -->
        <div class="grid grid-cols-3 gap-3">
            <div v-for="item in [
                { label: 'Сегодня', value: financeStats.revenueToday },
                { label: 'Неделя', value: financeStats.revenueWeek },
                { label: 'Месяц', value: financeStats.revenueMonth },
            ]" :key="item.label" class="p-3 rounded-xl border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="text-[10px] uppercase" style="color:var(--t-text-3)">{{ item.label }}</div>
                <div class="text-base font-bold" style="color:var(--t-primary)">{{ fmtMoney(item.value) }}</div>
            </div>
        </div>

        <!-- P&L -->
        <VCard title="📋 Прибыли и убытки (P&L)">
            <div class="space-y-2">
                <div v-for="item in [
                    { label: '💰 Выручка', value: profitLoss.revenue, color: 'var(--t-primary)' },
                    { label: '👩‍💼 Комиссии мастерам', value: -financeStats.commissions, color: '#f59e0b' },
                    { label: '🏠 Аренда', value: -financeStats.rent, color: '#ef4444' },
                    { label: '💡 Коммуналка', value: -financeStats.utilities, color: '#ef4444' },
                    { label: '🧴 Расходники', value: -financeStats.supplies, color: '#ef4444' },
                    { label: '📦 Прочие расходы', value: -financeStats.otherExpenses, color: '#ef4444' },
                ]" :key="item.label" class="flex items-center justify-between py-1.5 border-b" style="border-color:var(--t-border)">
                    <span class="text-sm" style="color:var(--t-text)">{{ item.label }}</span>
                    <span class="text-sm font-bold" :style="`color:${item.color}`">{{ item.value >= 0 ? '+' : '' }}{{ fmtMoney(Math.abs(item.value)) }}</span>
                </div>
                <div class="flex items-center justify-between pt-2 font-bold">
                    <span class="text-sm" style="color:var(--t-text)">📊 Чистая прибыль ({{ profitLoss.margin }}%)</span>
                    <span class="text-base" :style="`color:${profitLoss.profit >= 0 ? '#22c55e' : '#ef4444'}`">{{ fmtMoney(profitLoss.profit) }}</span>
                </div>
            </div>
        </VCard>

        <!-- Monthly chart -->
        <VCard title="📈 Динамика за 6 месяцев">
            <div class="flex items-end gap-2 h-28">
                <div v-for="m in monthlyRevenue" :key="m.month" class="flex-1 flex flex-col items-center gap-0.5">
                    <span class="text-[8px] font-bold" style="color:var(--t-text-2)">{{ fmt(m.revenue / 1000) }}к</span>
                    <div class="w-full rounded-t-lg" :style="`height:${Math.round(m.revenue / 12000)}px;background:var(--t-primary);opacity:.8`"></div>
                    <div class="w-full rounded-t-lg" :style="`height:${Math.round(m.expenses / 12000)}px;background:#ef4444;opacity:.4`"></div>
                    <span class="text-[10px]" style="color:var(--t-text-3)">{{ m.month }}</span>
                </div>
            </div>
            <div class="flex gap-4 mt-2 text-[10px]" style="color:var(--t-text-3)">
                <span><span class="inline-block w-3 h-2 rounded" style="background:var(--t-primary);opacity:.8"></span> Выручка</span>
                <span><span class="inline-block w-3 h-2 rounded" style="background:#ef4444;opacity:.4"></span> Расходы</span>
            </div>
        </VCard>

        <!-- ═══ 6a. СТРУКТУРА ВЫРУЧКИ ═══ -->
        <div class="mt-2 pt-4 border-t" style="border-color:var(--t-border)">
            <!-- Header + Export -->
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold flex items-center gap-2" style="color:var(--t-text)">
                    📊 Структура выручки
                </h3>
                <div class="flex gap-1">
                    <button @click="exportRevenue('excel')" class="px-2 py-1 rounded-lg text-[10px] border transition hover:scale-105"
                            style="border-color:var(--t-border);color:var(--t-text-2);background:var(--t-surface)">📥 Excel</button>
                    <button @click="exportRevenue('pdf')" class="px-2 py-1 rounded-lg text-[10px] border transition hover:scale-105"
                            style="border-color:var(--t-border);color:var(--t-text-2);background:var(--t-surface)">📄 PDF</button>
                    <button @click="saveAsMyReport" class="px-2 py-1 rounded-lg text-[10px] font-medium transition hover:scale-105"
                            style="background:var(--t-primary);color:#fff">💾 Мой отчёт</button>
                </div>
            </div>

            <!-- Period selector -->
            <div class="flex flex-wrap gap-1.5 mb-4">
                <button v-for="opt in revPeriodOptions" :key="opt.key"
                        @click="revPeriod = opt.key"
                        class="px-2.5 py-1 rounded-lg text-[11px] font-medium transition"
                        :style="revPeriod === opt.key
                            ? 'background:var(--t-primary);color:#fff'
                            : 'background:var(--t-bg);color:var(--t-text-2)'">
                    {{ opt.label }}
                </button>
                <button @click="revShowCompare = !revShowCompare"
                        class="px-2.5 py-1 rounded-lg text-[11px] font-medium transition ml-auto"
                        :style="revShowCompare
                            ? 'background:var(--t-accent);color:#fff'
                            : 'background:var(--t-bg);color:var(--t-text-3)'">
                    ⚖️ Сравнить
                </button>
            </div>

            <!-- Custom date range -->
            <div v-if="revPeriod === 'custom'" class="flex gap-2 mb-4">
                <VInput v-model="revCustomFrom" type="date" label="С" class="flex-1" />
                <VInput v-model="revCustomTo" type="date" label="По" class="flex-1" />
            </div>

            <!-- Total revenue + dynamics -->
            <div class="p-4 rounded-xl border mb-4" style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="text-[10px] uppercase tracking-wider mb-1" style="color:var(--t-text-3)">Общая выручка за период</div>
                <div class="flex items-end gap-3">
                    <span class="text-2xl font-black" style="color:var(--t-primary)">{{ fmtMoney(revTotalByPeriod.total) }}</span>
                    <span class="text-sm font-bold mb-0.5"
                          :style="`color:${revTotalByPeriod.delta >= 0 ? '#22c55e' : '#ef4444'}`">
                        {{ revTotalByPeriod.delta >= 0 ? '▲' : '▼' }} {{ Math.abs(revTotalByPeriod.delta) }}%
                    </span>
                </div>
                <div v-if="revShowCompare" class="mt-1 text-xs" style="color:var(--t-text-3)">
                    Пред. период: {{ fmtMoney(revTotalByPeriod.prev) }}
                </div>
            </div>

            <!-- 2.1 PIE: Revenue by categories -->
            <VCard title="🍩 Выручка по категориям услуг" class="mb-4">
                <div class="flex gap-4 items-start">
                    <!-- Donut chart -->
                    <div class="relative w-32 h-32 shrink-0">
                        <div class="w-32 h-32 rounded-full" :style="`background:${revDonutGradient}`"></div>
                        <div class="absolute inset-[18px] rounded-full flex items-center justify-center"
                             style="background:var(--t-surface)">
                            <div class="text-center">
                                <div class="text-sm font-black" style="color:var(--t-text)">{{ revByCategory.length }}</div>
                                <div class="text-[8px]" style="color:var(--t-text-3)">категорий</div>
                            </div>
                        </div>
                    </div>
                    <!-- Legend -->
                    <div class="flex-1 space-y-1">
                        <div v-for="c in revByCategory" :key="c.name"
                             class="flex items-center gap-2 cursor-pointer rounded-lg px-1.5 py-0.5 transition"
                             :style="revDrillCategory === c.name ? 'background:var(--t-bg)' : ''"
                             @click="revDrillCategory = revDrillCategory === c.name ? null : c.name">
                            <span class="w-2.5 h-2.5 rounded-sm shrink-0" :style="`background:${c.color}`"></span>
                            <span class="text-[11px] flex-1 truncate" style="color:var(--t-text)">{{ c.name }}</span>
                            <span class="text-[11px] font-bold" style="color:var(--t-primary)">{{ c.pct }}%</span>
                            <span class="text-[10px]" style="color:var(--t-text-3)">{{ fmtMoney(c.revenue) }}</span>
                        </div>
                    </div>
                </div>
                <!-- Drill-down by category -->
                <div v-if="revDrillCategory" class="mt-3 pt-3 border-t space-y-1" style="border-color:var(--t-border)">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold" style="color:var(--t-text)">🔎 {{ revDrillCategory }}</span>
                        <button @click="revDrillCategory = null" class="text-[10px] hover:opacity-70 transition" style="color:var(--t-text-3)">✕ Закрыть</button>
                    </div>
                    <div v-for="row in revFilteredDetail" :key="row.service"
                         class="flex items-center gap-2 text-[11px] py-1.5 border-b" style="border-color:var(--t-border)">
                        <span class="flex-1 truncate" style="color:var(--t-text)">{{ row.service }}</span>
                        <span style="color:var(--t-text-3)">{{ row.count }} раз</span>
                        <span class="font-bold" style="color:var(--t-primary)">{{ fmtMoney(row.revenue) }}</span>
                        <span class="w-12 text-right text-[10px] font-bold"
                              :style="`color:${row.delta >= 0 ? '#22c55e' : '#ef4444'}`">
                            {{ row.delta >= 0 ? '+' : '' }}{{ row.delta }}%
                        </span>
                    </div>
                </div>
            </VCard>

            <!-- 2.2 BAR: Revenue by masters (top-10) -->
            <VCard title="👩‍💼 Выручка по мастерам (ТОП-10)" class="mb-4">
                <div class="space-y-2.5">
                    <div v-for="(m, i) in revByMasters" :key="m.name" class="flex items-center gap-2">
                        <span class="w-5 text-center text-[10px] font-bold" style="color:var(--t-text-3)">#{{ i + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-0.5">
                                <span class="text-[11px] truncate" style="color:var(--t-text)">{{ m.name }}</span>
                                <span class="text-[11px] font-bold shrink-0 ml-2" style="color:var(--t-primary)">{{ fmtMoney(m.revenue) }}</span>
                            </div>
                            <div class="h-2 rounded-full" style="background:var(--t-bg)">
                                <div class="h-full rounded-full transition-all"
                                     :style="`width:${Math.round(m.revenue / revMastersMax * 100)}%;background:var(--t-primary);opacity:${0.5 + (1 - i / 10) * 0.5}`"></div>
                            </div>
                            <div class="flex gap-3 mt-0.5 text-[9px]" style="color:var(--t-text-3)">
                                <span>{{ m.pct }}% от филиала</span>
                                <span>Ср. чек: {{ fmtMoney(m.avgCheck) }}</span>
                                <span>{{ m.services }} усл.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </VCard>

            <!-- 2.3 HEATMAP: Revenue by day × hour -->
            <VCard title="🔥 Тепловая карта выручки (дни × часы)" class="mb-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-[10px]">
                        <thead>
                            <tr>
                                <th class="text-left py-1 w-8" style="color:var(--t-text-3)"></th>
                                <th v-for="h in ['09','10','11','12','13','14','15','16','17','18','19','20']" :key="h"
                                    class="text-center py-1 w-7" style="color:var(--t-text-3)">{{ h }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in revHeatmapData" :key="row.day">
                                <td class="py-0.5 font-medium" style="color:var(--t-text-2)">{{ row.day }}</td>
                                <td v-for="cell in row.hours" :key="cell.hour" class="py-0.5 text-center">
                                    <div class="w-6 h-6 mx-auto rounded flex items-center justify-center text-[8px] font-bold cursor-default"
                                         :style="`background:${revHeatColor(cell.intensity)}20;color:${revHeatColor(cell.intensity)}`"
                                         :title="`${row.day} ${cell.hour}:00 — ${fmtMoney(cell.revenue)}`">
                                        {{ Math.round(cell.revenue / 1000) }}к
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex gap-3 mt-2 text-[9px]" style="color:var(--t-text-3)">
                    <span><span class="inline-block w-3 h-2 rounded" style="background:#94a3b8;opacity:.3"></span> Мало</span>
                    <span><span class="inline-block w-3 h-2 rounded" style="background:#f59e0b;opacity:.4"></span> Средне</span>
                    <span><span class="inline-block w-3 h-2 rounded" style="background:#22c55e;opacity:.5"></span> Много</span>
                </div>
            </VCard>

            <!-- 2.4 Detail table -->
            <VCard title="📋 Детальная таблица выручки" class="mb-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-[10px]">
                        <thead>
                            <tr class="border-b" style="border-color:var(--t-border)">
                                <th class="text-left py-2 pr-2 font-medium" style="color:var(--t-text-3)">Услуга</th>
                                <th class="text-right py-2 px-1 font-medium" style="color:var(--t-text-3)">Кол-во</th>
                                <th class="text-right py-2 px-1 font-medium" style="color:var(--t-text-3)">Выручка</th>
                                <th class="text-right py-2 px-1 font-medium" style="color:var(--t-text-3)">%</th>
                                <th class="text-right py-2 px-1 font-medium" style="color:var(--t-text-3)">Ср. чек</th>
                                <th class="text-left py-2 px-1 font-medium" style="color:var(--t-text-3)">Топ-мастер</th>
                                <th class="text-right py-2 pl-1 font-medium" style="color:var(--t-text-3)">±%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in revFilteredDetail" :key="row.service"
                                class="border-b transition" style="border-color:var(--t-border)">
                                <td class="py-1.5 pr-2 truncate max-w-[140px]" style="color:var(--t-text)">{{ row.service }}</td>
                                <td class="py-1.5 px-1 text-right" style="color:var(--t-text-2)">{{ row.count }}</td>
                                <td class="py-1.5 px-1 text-right font-bold" style="color:var(--t-primary)">{{ fmtMoney(row.revenue) }}</td>
                                <td class="py-1.5 px-1 text-right" style="color:var(--t-text-3)">{{ row.pct }}%</td>
                                <td class="py-1.5 px-1 text-right" style="color:var(--t-text-2)">{{ fmtMoney(row.avgCheck) }}</td>
                                <td class="py-1.5 px-1 truncate max-w-[100px]" style="color:var(--t-text-2)">{{ row.topMaster }}</td>
                                <td class="py-1.5 pl-1 text-right font-bold"
                                    :style="`color:${row.delta >= 0 ? '#22c55e' : '#ef4444'}`">
                                    {{ row.delta >= 0 ? '+' : '' }}{{ row.delta }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </VCard>

            <!-- Additional breakdowns (5 slicers) -->
            <div class="mb-2">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-xs font-bold" style="color:var(--t-text)">📊 Дополнительные срезы</span>
                    <div class="flex gap-1 ml-auto flex-wrap">
                        <button v-for="opt in revSliceOptions" :key="opt.key"
                                @click="revActiveSlice = opt.key"
                                class="px-2 py-1 rounded-lg text-[10px] font-medium transition"
                                :style="revActiveSlice === opt.key
                                    ? 'background:var(--t-primary);color:#fff'
                                    : 'background:var(--t-bg);color:var(--t-text-3)'">
                            {{ opt.icon }} {{ opt.label }}
                        </button>
                    </div>
                </div>

                <!-- Slice: Source -->
                <div v-if="revActiveSlice === 'source'" class="space-y-1.5">
                    <div v-for="s in revBySource" :key="s.name"
                         class="flex items-center gap-2 p-2 rounded-lg" style="background:var(--t-bg)">
                        <span class="w-2.5 h-2.5 rounded-sm shrink-0" :style="`background:${s.color}`"></span>
                        <span class="text-[11px] flex-1 truncate" style="color:var(--t-text)">{{ s.name }}</span>
                        <span class="text-[10px]" style="color:var(--t-text-3)">{{ s.pct }}%</span>
                        <span class="text-[11px] font-bold" style="color:var(--t-primary)">{{ fmtMoney(s.revenue) }}</span>
                    </div>
                </div>

                <!-- Slice: Payment method -->
                <div v-if="revActiveSlice === 'payment'" class="space-y-1.5">
                    <div v-for="p in revByPayment" :key="p.name"
                         class="flex items-center gap-2 p-2 rounded-lg" style="background:var(--t-bg)">
                        <span class="text-sm">{{ p.icon }}</span>
                        <span class="text-[11px] flex-1" style="color:var(--t-text)">{{ p.name }}</span>
                        <div class="w-20 h-1.5 rounded-full" style="background:var(--t-border)">
                            <div class="h-full rounded-full" :style="`width:${p.pct}%;background:var(--t-primary)`"></div>
                        </div>
                        <span class="text-[10px] w-8 text-right" style="color:var(--t-text-3)">{{ p.pct }}%</span>
                        <span class="text-[11px] font-bold w-16 text-right" style="color:var(--t-primary)">{{ fmtMoney(p.revenue) }}</span>
                    </div>
                </div>

                <!-- Slice: Time of day -->
                <div v-if="revActiveSlice === 'time'" class="grid grid-cols-2 gap-2">
                    <div v-for="t in revByTimeOfDay" :key="t.name"
                         class="p-3 rounded-xl border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                        <div class="text-xl mb-1">{{ t.icon }}</div>
                        <div class="text-[10px] mb-0.5" style="color:var(--t-text-3)">{{ t.name }}</div>
                        <div class="text-sm font-bold" style="color:var(--t-primary)">{{ fmtMoney(t.revenue) }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ t.pct }}%</div>
                    </div>
                </div>

                <!-- Slice: Client type -->
                <div v-if="revActiveSlice === 'client'" class="space-y-1.5">
                    <div v-for="c in revByClientType" :key="c.name"
                         class="flex items-center gap-3 p-3 rounded-xl border" style="background:var(--t-surface);border-color:var(--t-border)">
                        <span class="text-2xl">{{ c.icon }}</span>
                        <div class="flex-1">
                            <div class="text-xs font-medium" style="color:var(--t-text)">{{ c.name }}</div>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="flex-1 h-2 rounded-full" style="background:var(--t-bg)">
                                    <div class="h-full rounded-full" :style="`width:${c.pct}%;background:var(--t-primary)`"></div>
                                </div>
                                <span class="text-[10px] font-bold" style="color:var(--t-text-3)">{{ c.pct }}%</span>
                            </div>
                        </div>
                        <span class="text-sm font-bold" style="color:var(--t-primary)">{{ fmtMoney(c.revenue) }}</span>
                    </div>
                </div>

                <!-- Slice: Promo codes -->
                <div v-if="revActiveSlice === 'promo'" class="overflow-x-auto">
                    <table class="w-full text-[10px]">
                        <thead>
                            <tr class="border-b" style="border-color:var(--t-border)">
                                <th class="text-left py-1.5 font-medium" style="color:var(--t-text-3)">Промокод</th>
                                <th class="text-right py-1.5 font-medium" style="color:var(--t-text-3)">Скидка</th>
                                <th class="text-right py-1.5 font-medium" style="color:var(--t-text-3)">Исп.</th>
                                <th class="text-right py-1.5 font-medium" style="color:var(--t-text-3)">Выручка</th>
                                <th class="text-right py-1.5 font-medium" style="color:var(--t-text-3)">Ср. чек</th>
                                <th class="text-center py-1.5 font-medium" style="color:var(--t-text-3)">Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in revByPromo" :key="p.code"
                                class="border-b" style="border-color:var(--t-border)">
                                <td class="py-1.5 font-mono font-bold" style="color:var(--t-text)">{{ p.code }}</td>
                                <td class="py-1.5 text-right" style="color:var(--t-text-2)">{{ p.discount }}</td>
                                <td class="py-1.5 text-right" style="color:var(--t-text-2)">{{ p.uses }}</td>
                                <td class="py-1.5 text-right font-bold" style="color:var(--t-primary)">{{ fmtMoney(p.revenue) }}</td>
                                <td class="py-1.5 text-right" style="color:var(--t-text-2)">{{ fmtMoney(p.avgCheck) }}</td>
                                <td class="py-1.5 text-center">
                                    <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold"
                                          :style="p.active
                                              ? 'background:#22c55e20;color:#22c55e'
                                              : 'background:#94a3b820;color:#94a3b8'">
                                        {{ p.active ? 'Актив' : 'Истёк' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ 7. LOAD TAB ═══ -->
    <div v-if="activeTab === 'load'" class="space-y-4">
        <!-- Avg load -->
        <div class="flex items-center gap-4 p-4 rounded-xl border" style="background:var(--t-surface);border-color:var(--t-border)">
            <div class="relative w-16 h-16">
                <svg viewBox="0 0 36 36" class="w-16 h-16 -rotate-90">
                    <circle cx="18" cy="18" r="15.5" fill="none" stroke-width="3" style="stroke:var(--t-bg)" />
                    <circle cx="18" cy="18" r="15.5" fill="none" stroke-width="3"
                            :stroke-dasharray="`${avgLoad * 0.975} 100`"
                            :style="`stroke:${avgLoad >= 80 ? '#22c55e' : avgLoad >= 50 ? '#f59e0b' : '#ef4444'}`" />
                </svg>
                <div class="absolute inset-0 flex items-center justify-center text-sm font-bold" style="color:var(--t-text)">{{ avgLoad }}%</div>
            </div>
            <div>
                <div class="text-base font-bold" style="color:var(--t-text)">Средняя загрузка филиала</div>
                <div class="text-xs" style="color:var(--t-text-3)">За последние 30 дней</div>
            </div>
        </div>

        <!-- Heatmap -->
        <VCard title="🔥 Тепловая карта загрузки">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr>
                            <th class="text-left py-1 w-10" style="color:var(--t-text-3)"></th>
                            <th v-for="h in ['09','10','11','12','13','14','15','16','17','18','19','20']" :key="h"
                                class="text-center py-1 w-8" style="color:var(--t-text-3)">{{ h }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in heatmapData" :key="row.day">
                            <td class="py-0.5 font-medium" style="color:var(--t-text-2)">{{ row.day }}</td>
                            <td v-for="cell in row.hours" :key="cell.hour" class="py-0.5 text-center">
                                <div class="w-7 h-7 mx-auto rounded-md flex items-center justify-center text-[9px] font-bold"
                                     :style="`background:${cell.color}20;color:${cell.color}`">
                                    {{ cell.load }}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>

        <!-- Top services -->
        <VCard title="🏆 Топ-услуги филиала">
            <div class="space-y-2">
                <div v-for="(s, i) in topSalonServices" :key="s.name"
                     class="flex items-center gap-3 p-2 rounded-lg" style="background:var(--t-bg)">
                    <span class="w-6 text-center font-bold text-sm" style="color:var(--t-primary)">#{{ i + 1 }}</span>
                    <div class="flex-1"><div class="text-sm" style="color:var(--t-text)">{{ s.name }}</div></div>
                    <span class="text-xs" style="color:var(--t-text-3)">{{ s.count }} раз</span>
                    <span class="font-bold text-sm" style="color:var(--t-primary)">{{ fmtMoney(s.revenue) }}</span>
                </div>
            </div>
        </VCard>

        <!-- Room occupancy -->
        <VCard title="🚪 Заполняемость кабинетов">
            <div class="space-y-2">
                <div v-for="r in roomOccupancy" :key="r.room" class="flex items-center gap-3">
                    <span class="text-xs w-44 truncate" style="color:var(--t-text)">{{ r.room }}</span>
                    <div class="flex-1 h-3 rounded-full" style="background:var(--t-bg)">
                        <div class="h-full rounded-full transition-all"
                             :style="`width:${r.pct}%;background:${r.pct >= 80 ? '#22c55e' : r.pct >= 50 ? '#f59e0b' : '#ef4444'}`"></div>
                    </div>
                    <span class="text-xs font-bold w-10 text-right" :style="`color:${r.pct >= 80 ? '#22c55e' : r.pct >= 50 ? '#f59e0b' : '#ef4444'}`">{{ r.pct }}%</span>
                </div>
            </div>
        </VCard>

        <!-- Top masters by revenue -->
        <VCard title="💸 Топ-мастера по загрузке">
            <div class="space-y-2">
                <div v-for="(m, i) in topMastersByRevenue" :key="m.id"
                     class="flex items-center gap-3 p-2 rounded-lg" style="background:var(--t-bg)">
                    <span class="w-6 text-center font-bold text-sm" style="color:var(--t-primary)">#{{ i + 1 }}</span>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                         style="background:var(--t-primary-dim);color:var(--t-primary)">{{ m.name?.charAt(0) }}</div>
                    <div class="flex-1">
                        <div class="text-sm" style="color:var(--t-text)">{{ m.name }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ m.specialization }}</div>
                    </div>
                    <span class="font-bold text-sm" :style="`color:${m.loadPct >= 80 ? '#22c55e' : '#f59e0b'}`">{{ m.loadPct }}%</span>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ 8. ANALYTICS TAB ═══ -->
    <div v-if="activeTab === 'analytics'" class="space-y-4">
        <!-- Branch comparison -->
        <VCard title="📊 Сравнение филиалов">
            <div class="space-y-2">
                <div v-for="c in branchComparison" :key="c.name"
                     class="flex items-center gap-4 p-3 rounded-xl border flex-wrap"
                     :style="`background:${c.highlight ? 'var(--t-primary-dim)' : 'var(--t-bg)'};border-color:${c.highlight ? 'var(--t-primary)' : 'var(--t-border)'}`">
                    <div class="flex-1 min-w-[130px]">
                        <div class="text-sm font-medium" :style="`color:${c.highlight ? 'var(--t-primary)' : 'var(--t-text)'}`">
                            {{ c.name }} {{ c.highlight ? '← текущий' : '' }}
                        </div>
                    </div>
                    <div class="text-xs text-center"><div style="color:var(--t-text-3)">Выручка</div><div class="font-bold" style="color:var(--t-text)">{{ fmtMoney(c.revenue) }}</div></div>
                    <div class="text-xs text-center"><div style="color:var(--t-text-3)">Ср. чек</div><div class="font-bold" style="color:var(--t-text)">{{ fmtMoney(c.avgCheck) }}</div></div>
                    <div class="text-xs text-center"><div style="color:var(--t-text-3)">Рейтинг</div><div class="font-bold" style="color:var(--t-text)">⭐ {{ c.rating }}</div></div>
                    <div class="text-xs text-center"><div style="color:var(--t-text-3)">Загрузка</div><div class="font-bold" :style="`color:${c.load >= 70 ? '#22c55e' : '#f59e0b'}`">{{ c.load }}%</div></div>
                </div>
            </div>
        </VCard>

        <!-- Client sources -->
        <VCard title="📡 Источники клиентов">
            <div class="space-y-2">
                <div v-for="s in clientSources" :key="s.source" class="flex items-center gap-3">
                    <span class="text-xs w-32 truncate" style="color:var(--t-text)">{{ s.source }}</span>
                    <div class="flex-1 h-3 rounded-full" style="background:var(--t-bg)">
                        <div class="h-full rounded-full" :style="`width:${s.pct * 2}%;background:var(--t-primary)`"></div>
                    </div>
                    <span class="text-xs font-bold w-10 text-right" style="color:var(--t-primary)">{{ s.pct }}%</span>
                    <span class="text-xs w-12 text-right" style="color:var(--t-text-3)">{{ s.count }}</span>
                </div>
            </div>
        </VCard>

        <!-- Retention -->
        <VCard title="🔄 Retention клиентов">
            <div class="flex items-center gap-4">
                <div class="relative w-20 h-20">
                    <svg viewBox="0 0 36 36" class="w-20 h-20 -rotate-90">
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke-width="3" style="stroke:var(--t-bg)" />
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke-width="3"
                                :stroke-dasharray="`${retentionRate * 0.975} 100`" style="stroke:var(--t-primary)" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center text-lg font-bold" style="color:var(--t-primary)">{{ retentionRate }}%</div>
                </div>
                <div>
                    <div class="text-sm font-bold" style="color:var(--t-text)">Клиенты возвращаются</div>
                    <div class="text-xs" style="color:var(--t-text-3)">{{ retentionRate }}% клиентов приходят повторно в течение 90 дней</div>
                </div>
            </div>
        </VCard>

        <!-- Reviews -->
        <VCard title="⭐ Отзывы филиала">
            <div class="flex gap-4 mb-4">
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color:var(--t-primary)">{{ avgSalonRating }}</div>
                    <div class="flex gap-0.5 justify-center">
                        <span v-for="st in 5" :key="st" class="text-sm">{{ st <= Math.round(Number(avgSalonRating)) ? '⭐' : '☆' }}</span>
                    </div>
                    <div class="text-xs" style="color:var(--t-text-3)">{{ salonReviews.length }} отзывов</div>
                </div>
                <div class="flex-1 space-y-1">
                    <div v-for="r in ratingDistribution" :key="r.star" class="flex items-center gap-2">
                        <span class="text-xs w-6 text-right" style="color:var(--t-text-3)">{{ r.star }}⭐</span>
                        <div class="flex-1 h-2.5 rounded-full" style="background:var(--t-bg)">
                            <div class="h-full rounded-full" :style="`width:${r.pct}%;background:var(--t-primary)`"></div>
                        </div>
                        <span class="text-xs w-4" style="color:var(--t-text-3)">{{ r.count }}</span>
                    </div>
                </div>
            </div>
            <div class="space-y-2">
                <div v-for="r in salonReviews.slice(0, 3)" :key="r.id"
                     class="p-3 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium" style="color:var(--t-text)">{{ r.client }}</span>
                            <span class="text-[10px]" style="color:var(--t-text-3)">{{ r.service }} · {{ r.date }}</span>
                        </div>
                        <div class="flex gap-0.5">
                            <span v-for="st in 5" :key="st" class="text-xs">{{ st <= r.rating ? '⭐' : '☆' }}</span>
                        </div>
                    </div>
                    <p class="text-sm" style="color:var(--t-text-2)">{{ r.text }}</p>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ 9. SOURCES TAB — Источники привлечения клиентов ═══ -->
    <div v-if="activeTab === 'sources'" class="space-y-4">

        <!-- ─── CARD OVERLAYS ─── -->
        <BeautyCampaignCard v-if="selectedCampaign" :campaign="selectedCampaign"
            @close="closeCampaignCard" @pause="closeCampaignCard" @edit="closeCampaignCard"
            @duplicate="closeCampaignCard" @archive="closeCampaignCard" />
        <BeautyBloggerCard v-else-if="selectedBlogger" :blogger="selectedBlogger"
            @close="closeBloggerCard" @new-placement="closeBloggerCard"
            @send-message="closeBloggerCard" @archive="closeBloggerCard" />
        <BeautySocialCard v-else-if="selectedSocial" :platform="selectedSocial"
            @close="closeSocialCard" @create-post="closeSocialCard"
            @schedule="closeSocialCard" @export="closeSocialCard" />
        <BeautyVideoCard v-else-if="selectedVideo" :video="selectedVideo"
            @close="closeVideoCard" @boost="closeVideoCard"
            @duplicate="closeVideoCard" @archive="closeVideoCard" />
        <BeautySourceDetailCard v-else-if="selectedSourceDetail" :source="selectedSourceDetail" :source-type="selectedSourceType"
            @close="closeSourceDetail" @export="closeSourceDetail" />

        <!-- ─── MAIN SOURCES CONTENT ─── -->
        <template v-else>

        <!-- ─── Header: period + summary ─── -->
        <VCard>
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h3 class="text-base font-bold" style="color:var(--t-text)">📡 Источники привлечения клиентов</h3>
                <div class="flex gap-1">
                    <button v-for="p in srcPeriodOptions" :key="p.key"
                            class="px-3 py-1 text-xs rounded-full border transition-all"
                            :style="srcPeriod === p.key
                                ? 'background:var(--t-primary);color:#fff;border-color:var(--t-primary)'
                                : 'background:var(--t-bg);color:var(--t-text-2);border-color:var(--t-border)'"
                            @click="srcPeriod = p.key">
                        {{ p.label }}
                    </button>
                </div>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="p-3 rounded-xl border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px] uppercase mb-1" style="color:var(--t-text-3)">Всего клиентов</div>
                    <div class="text-xl font-bold" style="color:var(--t-text)">{{ fmt(srcTotalClients) }}</div>
                </div>
                <div class="p-3 rounded-xl border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px] uppercase mb-1" style="color:var(--t-text-3)">Общая выручка</div>
                    <div class="text-xl font-bold" style="color:var(--t-primary)">{{ fmtMoney(srcTotalRevenue) }}</div>
                </div>
                <div class="p-3 rounded-xl border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px] uppercase mb-1" style="color:var(--t-text-3)">Средний чек</div>
                    <div class="text-xl font-bold" style="color:var(--t-text)">{{ fmtMoney(Math.round(srcTotalRevenue / srcTotalClients)) }}</div>
                </div>
                <div class="p-3 rounded-xl border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px] uppercase mb-1" style="color:var(--t-text-3)">Источников</div>
                    <div class="text-xl font-bold" style="color:var(--t-text)">{{ srcMainTable.length }}</div>
                </div>
            </div>
        </VCard>

        <!-- ─── Donut + Legend ─── -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <VCard title="🍩 Доля выручки по источникам">
                <div class="flex items-center justify-center">
                    <div class="relative w-48 h-48">
                        <div class="w-full h-full rounded-full" :style="`background:${srcDonutGradient}`"></div>
                        <div class="absolute inset-6 rounded-full flex items-center justify-center" style="background:var(--t-surface)">
                            <div class="text-center">
                                <div class="text-lg font-bold" style="color:var(--t-text)">{{ fmt(srcTotalClients) }}</div>
                                <div class="text-[10px]" style="color:var(--t-text-3)">клиентов</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Legend -->
                <div class="mt-4 space-y-1.5">
                    <div v-for="(r, i) in srcMainTable" :key="r.id" class="flex items-center gap-2 text-xs">
                        <span class="w-3 h-3 rounded-full shrink-0" :style="`background:${srcColors[i % srcColors.length]}`"></span>
                        <span class="flex-1 truncate" style="color:var(--t-text-2)">{{ r.name }}</span>
                        <span class="font-bold" style="color:var(--t-text)">{{ r.pctRevenue }}%</span>
                    </div>
                </div>
            </VCard>

            <!-- Bar chart by clients -->
            <VCard title="📊 Клиенты по источникам">
                <div class="space-y-2">
                    <div v-for="(r, i) in srcMainTable" :key="r.id" class="space-y-0.5">
                        <div class="flex justify-between text-xs">
                            <span style="color:var(--t-text-2)">{{ r.icon }} {{ r.name.length > 30 ? r.name.slice(0, 30) + '…' : r.name }}</span>
                            <span class="font-bold" style="color:var(--t-text)">{{ r.clients }}</span>
                        </div>
                        <div class="h-4 rounded-full overflow-hidden" style="background:var(--t-bg)">
                            <div class="h-full rounded-full transition-all"
                                 :style="`width:${Math.round(r.clients / srcTotalClients * 100)}%;background:${srcColors[i % srcColors.length]}`"></div>
                        </div>
                    </div>
                </div>
            </VCard>
        </div>

        <!-- ─── Main detailed table (10 rows × 8 columns) ─── -->
        <VCard title="📋 Детальная таблица источников">
            <div class="overflow-x-auto -mx-3">
                <table class="w-full text-xs" style="min-width:800px">
                    <thead>
                        <tr class="border-b" style="border-color:var(--t-border)">
                            <th class="text-left px-3 py-2 font-semibold" style="color:var(--t-text-3)">Источник</th>
                            <th class="text-right px-2 py-2 font-semibold" style="color:var(--t-text-3)">Клиенты</th>
                            <th class="text-right px-2 py-2 font-semibold" style="color:var(--t-text-3)">% кл.</th>
                            <th class="text-right px-2 py-2 font-semibold" style="color:var(--t-text-3)">Выручка</th>
                            <th class="text-right px-2 py-2 font-semibold" style="color:var(--t-text-3)">% выр.</th>
                            <th class="text-right px-2 py-2 font-semibold" style="color:var(--t-text-3)">Ср. чек</th>
                            <th class="text-right px-2 py-2 font-semibold" style="color:var(--t-text-3)">Дин. ±%</th>
                            <th class="text-right px-2 py-2 font-semibold" style="color:var(--t-text-3)">ROI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(r, i) in srcMainTable" :key="r.id">
                            <tr class="border-b cursor-pointer hover:brightness-110 transition-all"
                                :style="`border-color:var(--t-border);background:${srcExpandedRow === r.id ? 'var(--t-bg)' : 'transparent'}`"
                                @click="toggleSrcDrill(r.id)">
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="`background:${srcColors[i % srcColors.length]}`"></span>
                                        <span class="font-medium" style="color:var(--t-text)">{{ r.icon }} {{ r.name }}</span>
                                        <span class="text-[10px] ml-auto" style="color:var(--t-text-3)">{{ srcExpandedRow === r.id ? '▲' : '▼' }}</span>
                                    </div>
                                </td>
                                <td class="text-right px-2 py-2.5 font-bold" style="color:var(--t-text)">{{ fmt(r.clients) }}</td>
                                <td class="text-right px-2 py-2.5" style="color:var(--t-text-2)">{{ r.pctClients }}%</td>
                                <td class="text-right px-2 py-2.5 font-bold" style="color:var(--t-primary)">{{ fmtMoney(r.revenue) }}</td>
                                <td class="text-right px-2 py-2.5" style="color:var(--t-text-2)">{{ r.pctRevenue }}%</td>
                                <td class="text-right px-2 py-2.5" style="color:var(--t-text)">{{ fmtMoney(r.avgCheck) }}</td>
                                <td class="text-right px-2 py-2.5 font-bold"
                                    :style="`color:${r.dynamics > 0 ? '#22c55e' : r.dynamics < 0 ? '#ef4444' : 'var(--t-text-3)'}`">
                                    {{ r.dynamics > 0 ? '+' : '' }}{{ r.dynamics }}%
                                </td>
                                <td class="text-right px-2 py-2.5 font-bold"
                                    :style="`color:${r.roi ? (r.roi >= 5 ? '#22c55e' : r.roi >= 3 ? '#f59e0b' : '#ef4444') : 'var(--t-text-3)'}`">
                                    {{ r.roi ? r.roi.toFixed(1) : '—' }}
                                </td>
                            </tr>

                            <!-- ════ DRILL-DOWN PANELS ════ -->

                            <!-- Drill-down: Реклама Экосистема Кота (id=2) -->
                            <tr v-if="srcExpandedRow === 2 && r.id === 2">
                                <td colspan="8" class="p-0">
                                    <div class="p-4 space-y-4" style="background:var(--t-bg)">
                                        <h4 class="text-sm font-bold" style="color:var(--t-text)">🐱 Рекламные кампании Экосистемы Кота</h4>

                                        <!-- Campaigns table -->
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-[11px]" style="min-width:700px">
                                                <thead>
                                                    <tr class="border-b" style="border-color:var(--t-border)">
                                                        <th class="text-left px-2 py-1.5" style="color:var(--t-text-3)">Кампания</th>
                                                        <th class="text-center px-1 py-1.5" style="color:var(--t-text-3)">Статус</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">Бюджет</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">Потрачено</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">Показы</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">Клики</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">CTR</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">Лиды</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">Записи</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">CPO</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">ROAS</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="c in srcEcoCatCampaigns" :key="c.id" class="border-b cursor-pointer hover:brightness-110 transition-all" style="border-color:var(--t-border)" @click="openCampaignCard(c)">
                                                        <td class="px-2 py-1.5 font-medium" style="color:var(--t-primary)">{{ c.name }} <span class="text-[9px]" style="color:var(--t-text-3)">→</span></td>
                                                        <td class="text-center px-1 py-1.5">
                                                            <VBadge :color="c.status === 'active' ? 'green' : c.status === 'paused' ? 'yellow' : 'gray'" size="sm">
                                                                {{ c.status === 'active' ? '▶ Активна' : c.status === 'paused' ? '⏸ Пауза' : '✅ Завершена' }}
                                                            </VBadge>
                                                        </td>
                                                        <td class="text-right px-1 py-1.5" style="color:var(--t-text-2)">{{ fmtMoney(c.budget) }}</td>
                                                        <td class="text-right px-1 py-1.5" style="color:var(--t-text)">{{ fmtMoney(c.spent) }}</td>
                                                        <td class="text-right px-1 py-1.5" style="color:var(--t-text-2)">{{ fmt(c.impressions) }}</td>
                                                        <td class="text-right px-1 py-1.5" style="color:var(--t-text-2)">{{ fmt(c.clicks) }}</td>
                                                        <td class="text-right px-1 py-1.5" style="color:var(--t-text)">{{ c.ctr }}%</td>
                                                        <td class="text-right px-1 py-1.5" style="color:var(--t-text-2)">{{ fmt(c.leads) }}</td>
                                                        <td class="text-right px-1 py-1.5 font-bold" style="color:var(--t-primary)">{{ c.bookings }}</td>
                                                        <td class="text-right px-1 py-1.5" style="color:var(--t-text)">{{ fmtMoney(c.cpo) }}</td>
                                                        <td class="text-right px-1 py-1.5 font-bold" :style="`color:${c.roas >= 5 ? '#22c55e' : '#f59e0b'}`">{{ c.roas }}x</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Conversion funnel -->
                                        <div class="p-3 rounded-xl border" style="background:var(--t-surface);border-color:var(--t-border)">
                                            <div class="text-xs font-semibold mb-2" style="color:var(--t-text-3)">Воронка конверсии</div>
                                            <div class="flex items-end gap-2 h-24">
                                                <div v-for="(step, si) in [
                                                    { label: 'Показы', value: srcEcoCatFunnel.impressions },
                                                    { label: 'Клики', value: srcEcoCatFunnel.clicks },
                                                    { label: 'Лиды', value: srcEcoCatFunnel.leads },
                                                    { label: 'Записи', value: srcEcoCatFunnel.bookings },
                                                ]" :key="si" class="flex-1 text-center">
                                                    <div class="mx-auto rounded-t-lg transition-all"
                                                         :style="`width:80%;height:${Math.max(Math.round(step.value / srcEcoCatFunnel.impressions * 96), 4)}px;background:var(--t-primary);opacity:${1 - si * 0.2}`"></div>
                                                    <div class="text-[10px] mt-1 font-bold" style="color:var(--t-text)">{{ fmt(step.value) }}</div>
                                                    <div class="text-[9px]" style="color:var(--t-text-3)">{{ step.label }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Creatives comparison -->
                                        <div>
                                            <div class="text-xs font-semibold mb-2" style="color:var(--t-text-3)">Эффективность креативов</div>
                                            <div class="space-y-1.5">
                                                <div v-for="cr in srcEcoCatCreatives" :key="cr.id"
                                                     class="flex items-center gap-3 p-2 rounded-lg border"
                                                     style="background:var(--t-surface);border-color:var(--t-border)">
                                                    <VBadge :color="cr.type === 'video' ? 'blue' : cr.type === 'shorts' ? 'purple' : cr.type === 'carousel' ? 'green' : 'gray'" size="sm">{{ cr.type }}</VBadge>
                                                    <span class="flex-1 text-xs font-medium truncate" style="color:var(--t-text)">{{ cr.name }}</span>
                                                    <span class="text-[10px]" style="color:var(--t-text-3)">{{ fmt(cr.impressions) }} показов</span>
                                                    <span class="text-[10px]" style="color:var(--t-text-2)">CTR {{ cr.ctr }}%</span>
                                                    <span class="text-xs font-bold" :style="`color:${cr.roas >= 6 ? '#22c55e' : '#f59e0b'}`">ROAS {{ cr.roas }}x</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Drill-down: Социальные сети (id=5) -->
                            <tr v-if="srcExpandedRow === 5 && r.id === 5">
                                <td colspan="8" class="p-0">
                                    <div class="p-4 space-y-3" style="background:var(--t-bg)">
                                        <h4 class="text-sm font-bold" style="color:var(--t-text)">💬 Разбивка по соцсетям</h4>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div v-for="sp in srcSocialPlatforms" :key="sp.id"
                                                 class="p-3 rounded-xl border cursor-pointer hover:shadow-md transition-shadow" style="background:var(--t-surface);border-color:var(--t-border)" @click="openSocialCard(sp)">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <span class="text-lg">{{ sp.icon }}</span>
                                                    <span class="text-sm font-bold" style="color:var(--t-text)">{{ sp.name }}</span>
                                                    <span class="ml-auto text-xs font-bold"
                                                          :style="`color:${sp.dynamics > 0 ? '#22c55e' : '#ef4444'}`">
                                                        {{ sp.dynamics > 0 ? '+' : '' }}{{ sp.dynamics }}%
                                                    </span>
                                                </div>
                                                <div class="grid grid-cols-2 gap-1 text-[10px]">
                                                    <div><span style="color:var(--t-text-3)">Подписчики:</span> <span class="font-bold" style="color:var(--t-text)">{{ fmt(sp.followers) }}</span></div>
                                                    <div><span style="color:var(--t-text-3)">Охват:</span> <span class="font-bold" style="color:var(--t-text)">{{ fmt(sp.reach) }}</span></div>
                                                    <div><span style="color:var(--t-text-3)">Клики:</span> <span class="font-bold" style="color:var(--t-text)">{{ fmt(sp.clicks) }}</span></div>
                                                    <div><span style="color:var(--t-text-3)">Клиенты:</span> <span class="font-bold" style="color:var(--t-primary)">{{ sp.clients }}</span></div>
                                                    <div><span style="color:var(--t-text-3)">Выручка:</span> <span class="font-bold" style="color:var(--t-primary)">{{ fmtMoney(sp.revenue) }}</span></div>
                                                    <div><span style="color:var(--t-text-3)">ER:</span> <span class="font-bold" style="color:var(--t-text)">{{ sp.er }}%</span></div>
                                                </div>
                                                <div class="mt-2 text-[10px] text-right font-medium" style="color:var(--t-primary)">Подробнее →</div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Drill-down: Реклама у блогеров (id=4) -->
                            <tr v-if="srcExpandedRow === 4 && r.id === 4">
                                <td colspan="8" class="p-0">
                                    <div class="p-4 space-y-3" style="background:var(--t-bg)">
                                        <h4 class="text-sm font-bold" style="color:var(--t-text)">📸 Блогеры — детальная статистика</h4>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-[11px]" style="min-width:650px">
                                                <thead>
                                                    <tr class="border-b" style="border-color:var(--t-border)">
                                                        <th class="text-left px-2 py-1.5" style="color:var(--t-text-3)">Блогер</th>
                                                        <th class="text-left px-1 py-1.5" style="color:var(--t-text-3)">Площадка</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">Подпис.</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">Размещ.</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">Клики</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">Лиды</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">Записи</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">Выручка</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">CPO</th>
                                                        <th class="text-right px-1 py-1.5" style="color:var(--t-text-3)">ROAS</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="b in srcBloggerDetails" :key="b.id" class="border-b cursor-pointer hover:brightness-110 transition-all" style="border-color:var(--t-border)" @click="openBloggerCard(b)">
                                                        <td class="px-2 py-1.5 font-medium" style="color:var(--t-primary)">{{ b.name }} <span class="text-[9px]" style="color:var(--t-text-3)">→</span></td>
                                                        <td class="px-1 py-1.5" style="color:var(--t-text-2)">{{ b.platform }}</td>
                                                        <td class="text-right px-1 py-1.5" style="color:var(--t-text-2)">{{ fmt(b.subscribers) }}</td>
                                                        <td class="text-right px-1 py-1.5" style="color:var(--t-text)">{{ b.placements }}</td>
                                                        <td class="text-right px-1 py-1.5" style="color:var(--t-text-2)">{{ fmt(b.clicks) }}</td>
                                                        <td class="text-right px-1 py-1.5" style="color:var(--t-text-2)">{{ fmt(b.leads) }}</td>
                                                        <td class="text-right px-1 py-1.5 font-bold" style="color:var(--t-primary)">{{ b.bookings }}</td>
                                                        <td class="text-right px-1 py-1.5 font-bold" style="color:var(--t-primary)">{{ fmtMoney(b.revenue) }}</td>
                                                        <td class="text-right px-1 py-1.5" style="color:var(--t-text)">{{ fmtMoney(b.cpo) }}</td>
                                                        <td class="text-right px-1 py-1.5 font-bold" :style="`color:${b.roas >= 6 ? '#22c55e' : '#f59e0b'}`">{{ b.roas }}x</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Drill-down: Переходы из поисковиков (id=3) -->
                            <tr v-if="srcExpandedRow === 3 && r.id === 3">
                                <td colspan="8" class="p-0">
                                    <div class="p-4 space-y-4" style="background:var(--t-bg)">
                                        <h4 class="text-sm font-bold" style="color:var(--t-text)">🔍 Органика и поисковики</h4>

                                        <!-- Channels -->
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                            <div v-for="ch in srcOrganicChannels" :key="ch.id"
                                                 class="p-2.5 rounded-lg border text-center"
                                                 style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-lg mb-1">{{ ch.icon }}</div>
                                                <div class="text-xs font-bold" style="color:var(--t-text)">{{ ch.name }}</div>
                                                <div class="text-[10px]" style="color:var(--t-text-3)">{{ fmt(ch.sessions) }} сессий → {{ ch.clients }} кл.</div>
                                                <div class="text-xs font-bold mt-1" style="color:var(--t-primary)">{{ fmtMoney(ch.revenue) }}</div>
                                            </div>
                                        </div>

                                        <!-- Top keywords -->
                                        <div>
                                            <div class="text-xs font-semibold mb-2" style="color:var(--t-text-3)">🔑 Топ-8 поисковых запросов</div>
                                            <div class="space-y-1">
                                                <div v-for="(kw, ki) in srcTopKeywords" :key="ki"
                                                     class="flex items-center gap-2 p-1.5 rounded-lg text-[11px]"
                                                     style="background:var(--t-surface)">
                                                    <span class="w-5 text-center font-bold" style="color:var(--t-text-3)">{{ ki + 1 }}</span>
                                                    <span class="flex-1 font-medium" style="color:var(--t-text)">{{ kw.keyword }}</span>
                                                    <span style="color:var(--t-text-3)">{{ fmt(kw.impressions) }} показов</span>
                                                    <span style="color:var(--t-text-2)">{{ fmt(kw.clicks) }} кликов</span>
                                                    <span class="font-bold" style="color:var(--t-primary)">позиция {{ kw.position }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Landing pages -->
                                        <div>
                                            <div class="text-xs font-semibold mb-2" style="color:var(--t-text-3)">📄 Посадочные страницы</div>
                                            <div class="space-y-1">
                                                <div v-for="lp in srcSearchLandingPages" :key="lp.page"
                                                     class="flex items-center gap-3 p-1.5 rounded-lg text-[11px]"
                                                     style="background:var(--t-surface)">
                                                    <code class="text-xs" style="color:var(--t-primary)">{{ lp.page }}</code>
                                                    <span class="ml-auto" style="color:var(--t-text-3)">{{ fmt(lp.sessions) }} сессий</span>
                                                    <span style="color:var(--t-text-2)">{{ lp.bookings }} записей</span>
                                                    <span class="font-bold" style="color:var(--t-text)">CR {{ lp.convRate }}%</span>
                                                    <span style="color:var(--t-text-3)">⏱ {{ lp.avgTimeOnPage }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Search funnel -->
                                        <div class="p-3 rounded-xl border" style="background:var(--t-surface);border-color:var(--t-border)">
                                            <div class="text-xs font-semibold mb-2" style="color:var(--t-text-3)">Воронка из поиска</div>
                                            <div class="flex items-center gap-1 text-[10px] text-center">
                                                <div class="flex-1 p-2 rounded-lg" style="background:var(--t-primary);color:#fff">
                                                    <div class="font-bold">{{ fmt(srcSearchFunnel.sessions) }}</div>
                                                    <div>Сессий</div>
                                                </div>
                                                <span style="color:var(--t-text-3)">→</span>
                                                <div class="flex-1 p-2 rounded-lg" style="background:var(--t-primary);color:#fff;opacity:.8">
                                                    <div class="font-bold">{{ fmt(srcSearchFunnel.viewed_service) }}</div>
                                                    <div>Смотрели услугу</div>
                                                </div>
                                                <span style="color:var(--t-text-3)">→</span>
                                                <div class="flex-1 p-2 rounded-lg" style="background:var(--t-primary);color:#fff;opacity:.6">
                                                    <div class="font-bold">{{ fmt(srcSearchFunnel.started_booking) }}</div>
                                                    <div>Начали запись</div>
                                                </div>
                                                <span style="color:var(--t-text-3)">→</span>
                                                <div class="flex-1 p-2 rounded-lg" style="background:#22c55e;color:#fff">
                                                    <div class="font-bold">{{ srcSearchFunnel.completed_booking }}</div>
                                                    <div>Записались</div>
                                                </div>
                                            </div>
                                        </div>
                                        <button class="w-full mt-2 py-2 text-xs font-semibold rounded-lg border transition-all hover:shadow-md"
                                                style="background:var(--t-primary);color:#fff;border-color:var(--t-primary)"
                                                @click.stop="openSourceDetail(r, 'search')">
                                            🔍 Подробнее →
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Drill-down: Переходы из ленты новостей (id=6) -->
                            <tr v-if="srcExpandedRow === 6 && r.id === 6">
                                <td colspan="8" class="p-0">
                                    <div class="p-4 space-y-3" style="background:var(--t-bg)">
                                        <h4 class="text-sm font-bold" style="color:var(--t-text)">📰 Лента новостей — лучшие публикации</h4>
                                        <div class="text-xs mb-2" style="color:var(--t-text-2)">
                                            Среднее время до записи после прочтения: <strong style="color:var(--t-primary)">{{ srcNewsFeedAvgTimeToBooking }}</strong>
                                        </div>
                                        <div class="space-y-2">
                                            <div v-for="post in srcNewsFeedPosts" :key="post.id"
                                                 class="flex items-center gap-3 p-2.5 rounded-lg border cursor-pointer hover:shadow-md transition-shadow"
                                                 style="background:var(--t-surface);border-color:var(--t-border)" @click="openSourceDetail({...post, icon:'📰', clients: post.bookings, revenue: post.revenue, dynamics: 0}, 'news_feed')">
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-xs font-medium truncate" style="color:var(--t-text)">{{ post.title }}</div>
                                                    <div class="text-[10px] mt-0.5" style="color:var(--t-text-3)">
                                                        👁 {{ fmt(post.views) }} · 🖱 {{ fmt(post.clicks) }} · CTR {{ post.ctr }}%
                                                    </div>
                                                </div>
                                                <div class="text-right shrink-0">
                                                    <div class="text-xs font-bold" style="color:var(--t-primary)">{{ post.bookings }} записей</div>
                                                    <div class="text-[10px]" style="color:var(--t-text-2)">{{ fmtMoney(post.revenue) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <button class="w-full mt-2 py-2 text-xs font-semibold rounded-lg border transition-all hover:shadow-md"
                                                style="background:var(--t-primary);color:#fff;border-color:var(--t-primary)"
                                                @click.stop="openSourceDetail(r, 'news_feed')">
                                            🔍 Подробнее →
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Drill-down: Переходы из карт (id=3 handled above; this is for a generic map row if separate) -->

                            <!-- Drill-down: Онлайн-запись (id=1) — summary -->
                            <tr v-if="srcExpandedRow === 1 && r.id === 1">
                                <td colspan="8" class="p-0">
                                    <div class="p-4 space-y-3" style="background:var(--t-bg)">
                                        <h4 class="text-sm font-bold" style="color:var(--t-text)">🌐 Онлайн-запись через Экосистему</h4>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                            <div class="p-2 rounded-lg border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-[10px]" style="color:var(--t-text-3)">Записей в месяц</div>
                                                <div class="text-lg font-bold" style="color:var(--t-primary)">142</div>
                                            </div>
                                            <div class="p-2 rounded-lg border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-[10px]" style="color:var(--t-text-3)">Ср. время до записи</div>
                                                <div class="text-lg font-bold" style="color:var(--t-text)">4 мин</div>
                                            </div>
                                            <div class="p-2 rounded-lg border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-[10px]" style="color:var(--t-text-3)">Повторные записи</div>
                                                <div class="text-lg font-bold" style="color:#22c55e">68%</div>
                                            </div>
                                            <div class="p-2 rounded-lg border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-[10px]" style="color:var(--t-text-3)">Оценка удобства</div>
                                                <div class="text-lg font-bold" style="color:var(--t-text)">4.9 ⭐</div>
                                            </div>
                                        </div>
                                        <div class="text-xs p-2 rounded-lg" style="background:var(--t-surface);color:var(--t-text-2)">
                                            📊 Основной источник трафика. 38% клиентов приходят через онлайн-запись. Наибольшая конверсия из всех каналов. Рекомендация: увеличить видимость формы записи в каталоге.
                                        </div>
                                        <button class="w-full mt-2 py-2 text-xs font-semibold rounded-lg border transition-all hover:shadow-md"
                                                style="background:var(--t-primary);color:#fff;border-color:var(--t-primary)"
                                                @click.stop="openSourceDetail(r, 'online_booking')">
                                            🔍 Подробнее →
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Drill-down: Рекомендации (id=8) -->
                            <tr v-if="srcExpandedRow === 8 && r.id === 8">
                                <td colspan="8" class="p-0">
                                    <div class="p-4 space-y-3" style="background:var(--t-bg)">
                                        <h4 class="text-sm font-bold" style="color:var(--t-text)">🗣️ Рекомендации — сарафанное радио</h4>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="p-2 rounded-lg border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-[10px]" style="color:var(--t-text-3)">По промокоду друга</div>
                                                <div class="text-lg font-bold" style="color:var(--t-primary)">28</div>
                                            </div>
                                            <div class="p-2 rounded-lg border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-[10px]" style="color:var(--t-text-3)">Спросили при записи</div>
                                                <div class="text-lg font-bold" style="color:var(--t-text)">12</div>
                                            </div>
                                            <div class="p-2 rounded-lg border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-[10px]" style="color:var(--t-text-3)">Привели лично</div>
                                                <div class="text-lg font-bold" style="color:var(--t-text)">5</div>
                                            </div>
                                        </div>
                                        <div class="text-xs p-2 rounded-lg" style="background:var(--t-surface);color:var(--t-text-2)">
                                            💡 Самый «дешёвый» канал привлечения: нулевые затраты + высокий LTV. Рекомендация: усилить реферальную программу (бонус за приведённого друга).
                                        </div>
                                        <button class="w-full mt-2 py-2 text-xs font-semibold rounded-lg border transition-all hover:shadow-md"
                                                style="background:var(--t-primary);color:#fff;border-color:var(--t-primary)"
                                                @click.stop="openSourceDetail(r, 'recommendations')">
                                            🔍 Подробнее →
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Drill-down: Администратор (id=9) -->
                            <tr v-if="srcExpandedRow === 9 && r.id === 9">
                                <td colspan="8" class="p-0">
                                    <div class="p-4 space-y-3" style="background:var(--t-bg)">
                                        <h4 class="text-sm font-bold" style="color:var(--t-text)">📞 Запись администратором</h4>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                            <div class="p-2 rounded-lg border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-[10px]" style="color:var(--t-text-3)">Звонков / мес</div>
                                                <div class="text-lg font-bold" style="color:var(--t-text)">112</div>
                                            </div>
                                            <div class="p-2 rounded-lg border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-[10px]" style="color:var(--t-text-3)">Конверсия звонок→запись</div>
                                                <div class="text-lg font-bold" style="color:var(--t-primary)">29%</div>
                                            </div>
                                            <div class="p-2 rounded-lg border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-[10px]" style="color:var(--t-text-3)">Ср. время разговора</div>
                                                <div class="text-lg font-bold" style="color:var(--t-text)">3:40</div>
                                            </div>
                                            <div class="p-2 rounded-lg border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-[10px]" style="color:var(--t-text-3)">Тренд</div>
                                                <div class="text-lg font-bold" style="color:#ef4444">-12%</div>
                                            </div>
                                        </div>
                                        <div class="text-xs p-2 rounded-lg" style="background:var(--t-surface);color:var(--t-text-2)">
                                            ⚠️ Канал снижается — клиенты мигрируют на онлайн-запись. Это нормально и снижает нагрузку на персонал.
                                        </div>
                                        <button class="w-full mt-2 py-2 text-xs font-semibold rounded-lg border transition-all hover:shadow-md"
                                                style="background:var(--t-primary);color:#fff;border-color:var(--t-primary)"
                                                @click.stop="openSourceDetail(r, 'admin')">
                                            🔍 Подробнее →
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Drill-down: Партнёрские (id=10) -->
                            <tr v-if="srcExpandedRow === 10 && r.id === 10">
                                <td colspan="8" class="p-0">
                                    <div class="p-4 space-y-3" style="background:var(--t-bg)">
                                        <h4 class="text-sm font-bold" style="color:var(--t-text)">🤝 Партнёрские интеграции</h4>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="p-2 rounded-lg border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-[10px]" style="color:var(--t-text-3)">Активных партнёров</div>
                                                <div class="text-lg font-bold" style="color:var(--t-primary)">4</div>
                                            </div>
                                            <div class="p-2 rounded-lg border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-[10px]" style="color:var(--t-text-3)">Клиентов от партнёров</div>
                                                <div class="text-lg font-bold" style="color:var(--t-text)">12</div>
                                            </div>
                                            <div class="p-2 rounded-lg border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-[10px]" style="color:var(--t-text-3)">ROAS</div>
                                                <div class="text-lg font-bold" style="color:#22c55e">6.1x</div>
                                            </div>
                                        </div>
                                        <div class="text-xs p-2 rounded-lg" style="background:var(--t-surface);color:var(--t-text-2)">
                                            🚀 Самый высокий ROI из платных каналов. Рекомендация: масштабировать — найти ещё 3–5 партнёров в смежных нишах (фитнес, свадебные агентства).
                                        </div>
                                        <button class="w-full mt-2 py-2 text-xs font-semibold rounded-lg border transition-all hover:shadow-md"
                                                style="background:var(--t-primary);color:#fff;border-color:var(--t-primary)"
                                                @click.stop="openSourceDetail(r, 'partners')">
                                            🔍 Подробнее →
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- ════ DRILL-DOWN: ВИДЕО И ШОРТС (id=7) — самый детальный ════ -->
                            <tr v-if="srcExpandedRow === 7 && r.id === 7">
                                <td colspan="8" class="p-0">
                                    <div class="p-4 space-y-4" style="background:var(--t-bg)">
                                        <h4 class="text-sm font-bold" style="color:var(--t-text)">🎬 Видео и шортс — расширенная аналитика</h4>

                                        <!-- 12 main metrics grid -->
                                        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                                            <div v-for="m in srcVideoMainMetrics" :key="m.metric"
                                                 class="p-2 rounded-lg border text-center"
                                                 style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-[9px] leading-tight mb-1" style="color:var(--t-text-3)">{{ m.metric }}</div>
                                                <div class="text-sm font-bold" style="color:var(--t-primary)">{{ m.formatted }}</div>
                                            </div>
                                        </div>

                                        <!-- Top videos by revenue -->
                                        <div>
                                            <div class="text-xs font-semibold mb-2" style="color:var(--t-text-3)">🏆 Топ видео по выручке</div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-[11px]" style="min-width:600px">
                                                    <thead>
                                                        <tr class="border-b" style="border-color:var(--t-border)">
                                                            <th class="text-left px-2 py-1" style="color:var(--t-text-3)">Видео</th>
                                                            <th class="text-center px-1 py-1" style="color:var(--t-text-3)">Формат</th>
                                                            <th class="text-right px-1 py-1" style="color:var(--t-text-3)">Просмотры</th>
                                                            <th class="text-right px-1 py-1" style="color:var(--t-text-3)">Досмотры</th>
                                                            <th class="text-right px-1 py-1" style="color:var(--t-text-3)">Клики</th>
                                                            <th class="text-right px-1 py-1" style="color:var(--t-text-3)">Записи</th>
                                                            <th class="text-right px-1 py-1" style="color:var(--t-text-3)">Выручка</th>
                                                            <th class="text-right px-1 py-1" style="color:var(--t-text-3)">ROI</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="v in srcVideoTopVideos" :key="v.id" class="border-b cursor-pointer hover:brightness-110 transition-all" style="border-color:var(--t-border)" @click="openVideoCard(v)">
                                                            <td class="px-2 py-1.5 font-medium" style="color:var(--t-primary)">{{ v.title }} <span class="text-[9px]" style="color:var(--t-text-3)">→</span></td>
                                                            <td class="text-center px-1 py-1.5">
                                                                <VBadge :color="v.format === 'shorts' ? 'purple' : 'blue'" size="sm">{{ v.format }}</VBadge>
                                                            </td>
                                                            <td class="text-right px-1 py-1.5" style="color:var(--t-text-2)">{{ fmt(v.views) }}</td>
                                                            <td class="text-right px-1 py-1.5" style="color:var(--t-text)">{{ v.completion }}%</td>
                                                            <td class="text-right px-1 py-1.5" style="color:var(--t-text-2)">{{ fmt(v.clicks) }}</td>
                                                            <td class="text-right px-1 py-1.5 font-bold" style="color:var(--t-primary)">{{ v.bookings }}</td>
                                                            <td class="text-right px-1 py-1.5 font-bold" style="color:var(--t-primary)">{{ fmtMoney(v.revenue) }}</td>
                                                            <td class="text-right px-1 py-1.5 font-bold" :style="`color:${v.roi >= 7 ? '#22c55e' : v.roi >= 4 ? '#f59e0b' : '#ef4444'}`">{{ v.roi }}x</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Format effectiveness -->
                                        <div>
                                            <div class="text-xs font-semibold mb-2" style="color:var(--t-text-3)">📐 Эффективность форматов</div>
                                            <div class="space-y-1.5">
                                                <div v-for="f in srcVideoFormatEfficiency" :key="f.format"
                                                     class="flex items-center gap-3 p-2 rounded-lg border"
                                                     style="background:var(--t-surface);border-color:var(--t-border)">
                                                    <span class="text-xs font-medium w-40 shrink-0" style="color:var(--t-text)">{{ f.format }}</span>
                                                    <div class="flex-1 grid grid-cols-3 sm:grid-cols-6 gap-1 text-[10px] text-center">
                                                        <div>
                                                            <div style="color:var(--t-text-3)">Видео</div>
                                                            <div class="font-bold" style="color:var(--t-text)">{{ f.videos }}</div>
                                                        </div>
                                                        <div>
                                                            <div style="color:var(--t-text-3)">Просмотры</div>
                                                            <div class="font-bold" style="color:var(--t-text)">{{ fmt(f.views) }}</div>
                                                        </div>
                                                        <div>
                                                            <div style="color:var(--t-text-3)">Досмотры</div>
                                                            <div class="font-bold" style="color:var(--t-text)">{{ f.avgCompletion }}%</div>
                                                        </div>
                                                        <div>
                                                            <div style="color:var(--t-text-3)">CTR</div>
                                                            <div class="font-bold" style="color:var(--t-text)">{{ f.avgCtr }}%</div>
                                                        </div>
                                                        <div>
                                                            <div style="color:var(--t-text-3)">Записи</div>
                                                            <div class="font-bold" style="color:var(--t-primary)">{{ f.bookings }}</div>
                                                        </div>
                                                        <div>
                                                            <div style="color:var(--t-text-3)">Доля выручки</div>
                                                            <div class="font-bold" style="color:var(--t-primary)">{{ f.revenueShare }}%</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Geography + Demographics side by side -->
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <!-- Geography -->
                                            <div class="p-3 rounded-xl border" style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-xs font-semibold mb-2" style="color:var(--t-text-3)">🌍 География просмотров</div>
                                                <div class="space-y-1.5">
                                                    <div v-for="g in srcVideoGeo" :key="g.city" class="flex items-center gap-2">
                                                        <span class="text-xs w-28 truncate" style="color:var(--t-text)">{{ g.city }}</span>
                                                        <div class="flex-1 h-3 rounded-full overflow-hidden" style="background:var(--t-bg)">
                                                            <div class="h-full rounded-full" :style="`width:${g.pct}%;background:var(--t-primary)`"></div>
                                                        </div>
                                                        <span class="text-[10px] w-8 text-right font-bold" style="color:var(--t-text)">{{ g.pct }}%</span>
                                                        <span class="text-[10px] w-10 text-right" style="color:var(--t-text-3)">{{ g.bookings }} зап.</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Demographics -->
                                            <div class="p-3 rounded-xl border" style="background:var(--t-surface);border-color:var(--t-border)">
                                                <div class="text-xs font-semibold mb-2" style="color:var(--t-text-3)">👥 Демография аудитории</div>
                                                <div class="space-y-1.5">
                                                    <div v-for="d in srcVideoDemographics" :key="d.group" class="flex items-center gap-2">
                                                        <span class="text-xs w-16" style="color:var(--t-text)">{{ d.group }}</span>
                                                        <div class="flex-1 h-3 rounded-full overflow-hidden" style="background:var(--t-bg)">
                                                            <div class="h-full rounded-full" :style="`width:${d.pct * 2}%;background:var(--t-accent)`"></div>
                                                        </div>
                                                        <span class="text-[10px] w-8 text-right font-bold" style="color:var(--t-text)">{{ d.pct }}%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Daily dynamics mini chart -->
                                        <div class="p-3 rounded-xl border" style="background:var(--t-surface);border-color:var(--t-border)">
                                            <div class="text-xs font-semibold mb-2" style="color:var(--t-text-3)">📈 Динамика по дням</div>
                                            <div class="flex items-end gap-1.5 h-20">
                                                <div v-for="day in srcVideoDailyDynamics" :key="day.date"
                                                     class="flex-1 flex flex-col items-center gap-0.5">
                                                    <div class="text-[8px] font-bold" :style="`color:${day.bookings > 0 ? 'var(--t-primary)' : 'var(--t-text-3)'}`">
                                                        {{ day.bookings > 0 ? day.bookings : '' }}
                                                    </div>
                                                    <div class="w-full rounded-t-sm"
                                                         :style="`height:${Math.round(day.views / 100)}px;background:var(--t-primary);opacity:${0.4 + day.bookings * 0.2}`"></div>
                                                    <div class="text-[7px]" style="color:var(--t-text-3)">{{ day.date.slice(0, 5) }}</div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <!-- Totals row -->
                    <tfoot>
                        <tr class="border-t-2 font-bold" style="border-color:var(--t-primary)">
                            <td class="px-3 py-2.5" style="color:var(--t-text)">ИТОГО</td>
                            <td class="text-right px-2 py-2.5" style="color:var(--t-text)">{{ fmt(srcTotalClients) }}</td>
                            <td class="text-right px-2 py-2.5" style="color:var(--t-text)">100%</td>
                            <td class="text-right px-2 py-2.5" style="color:var(--t-primary)">{{ fmtMoney(srcTotalRevenue) }}</td>
                            <td class="text-right px-2 py-2.5" style="color:var(--t-text)">100%</td>
                            <td class="text-right px-2 py-2.5" style="color:var(--t-text)">{{ fmtMoney(Math.round(srcTotalRevenue / srcTotalClients)) }}</td>
                            <td class="text-right px-2 py-2.5" style="color:#22c55e">+19%</td>
                            <td class="text-right px-2 py-2.5" style="color:var(--t-text-3)">—</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </VCard>

        <!-- ─── Map sources card ─── -->
        <VCard title="📍 Переходы из карт — детализация">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div v-for="(data, key) in srcMapMetrics" :key="key"
                     class="p-3 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-sm font-bold mb-2" style="color:var(--t-text)">
                        {{ key === 'yandexMaps' ? '🔴 Яндекс.Карты' : key === 'twoGis' ? '🗺️ 2ГИС' : '🟢 Google Maps' }}
                    </div>
                    <div class="grid grid-cols-2 gap-1 text-[10px]">
                        <div><span style="color:var(--t-text-3)">Просмотры:</span> <span class="font-bold" style="color:var(--t-text)">{{ fmt(data.views) }}</span></div>
                        <div><span style="color:var(--t-text-3)">Маршрутов:</span> <span class="font-bold" style="color:var(--t-text)">{{ fmt(data.routes) }}</span></div>
                        <div><span style="color:var(--t-text-3)">Звонков:</span> <span class="font-bold" style="color:var(--t-text)">{{ fmt(data.calls) }}</span></div>
                        <div><span style="color:var(--t-text-3)">Записей:</span> <span class="font-bold" style="color:var(--t-primary)">{{ data.bookings }}</span></div>
                    </div>
                    <div class="mt-2 text-[10px]" style="color:var(--t-text-3)">
                        Конверсия просмотр→запись: <strong style="color:var(--t-text)">{{ (data.convViewToBook * 100).toFixed(2) }}%</strong>
                    </div>
                    <div class="mt-1 text-[10px]" style="color:var(--t-text-3)">
                        Рейтинг: <strong style="color:var(--t-primary)">
                            {{ key === 'yandexMaps' ? srcMapReviews.yandex.rating : key === 'twoGis' ? srcMapReviews.twoGis.rating : srcMapReviews.google.rating }} ⭐
                        </strong>
                        ({{ key === 'yandexMaps' ? srcMapReviews.yandex.count : key === 'twoGis' ? srcMapReviews.twoGis.count : srcMapReviews.google.count }} отзывов)
                    </div>
                </div>
            </div>
        </VCard>

        <!-- ─── Export / Report actions ─── -->
        <div class="flex gap-2">
            <VButton size="sm" variant="outline" class="flex-1">📥 Экспорт в Excel</VButton>
            <VButton size="sm" variant="outline" class="flex-1">📊 Сохранить как отчёт</VButton>
            <VButton size="sm" variant="outline" class="flex-1">📧 Отправить на почту</VButton>
        </div>

        </template><!-- /v-else main sources content -->
    </div>

    <!-- ═══ 10. DOCUMENTS TAB ═══ -->
    <div v-if="activeTab === 'docs'" class="space-y-4">
        <VCard title="📁 Документы и лицензии">
            <div class="space-y-2">
                <div v-for="d in documents" :key="d.id"
                     class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer hover:shadow-md transition-shadow"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-xl">{{ docTypeLabels[d.type]?.split(' ')[0] || '📄' }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate" style="color:var(--t-text)">{{ d.name }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">Загружен: {{ d.date }} · Истекает: {{ d.expires }}</div>
                    </div>
                    <VBadge :color="d.status === 'active' ? 'green' : 'red'" size="sm">
                        {{ d.status === 'active' ? 'Действует' : 'Истёк' }}
                    </VBadge>
                    <span class="text-xs" style="color:var(--t-primary)">📥 Скачать</span>
                </div>
                <VButton size="sm" variant="outline" class="w-full">📤 Загрузить документ</VButton>
            </div>
        </VCard>

        <!-- Gallery -->
        <VCard title="📸 Фотогалерея помещения">
            <div class="grid grid-cols-4 md:grid-cols-8 gap-2">
                <div v-for="p in gallery" :key="p.id"
                     class="aspect-square rounded-xl border flex flex-col items-center justify-center cursor-pointer hover:shadow-md transition-shadow overflow-hidden"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-xl mb-0.5">{{ p.type === 'video' ? '🎬' : '📷' }}</span>
                    <span class="text-[8px] text-center px-0.5 leading-tight" style="color:var(--t-text-3)">{{ p.label }}</span>
                </div>
                <div class="aspect-square rounded-xl border-2 border-dashed flex items-center justify-center cursor-pointer hover:opacity-80 transition"
                     style="border-color:var(--t-border);color:var(--t-text-3)">
                    <span class="text-xl">➕</span>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ 10. B2B TAB ═══ -->
    <div v-if="activeTab === 'b2b'" class="space-y-4">
        <!-- Price list -->
        <VCard title="💲 Прайс-лист филиала">
            <div class="space-y-2">
                <div class="flex items-center gap-2 px-2 py-1 text-[10px] font-semibold uppercase" style="color:var(--t-text-3)">
                    <span class="flex-1">Услуга</span>
                    <span class="w-20 text-right">Стандарт</span>
                    <span class="w-20 text-right">Филиал</span>
                    <span class="w-16 text-right">Разница</span>
                </div>
                <div v-for="p in salonPriceList" :key="p.service"
                     class="flex items-center gap-2 p-2 rounded-lg border"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="flex-1 text-sm" style="color:var(--t-text)">{{ p.service }}</span>
                    <span class="w-20 text-right text-xs" style="color:var(--t-text-3)">{{ fmtMoney(p.standardPrice) }}</span>
                    <span class="w-20 text-right text-sm font-bold" style="color:var(--t-primary)">{{ fmtMoney(p.salonPrice) }}</span>
                    <span class="w-16 text-right text-xs font-bold"
                          :style="`color:${p.diff > 0 ? '#22c55e' : p.diff < 0 ? '#ef4444' : 'var(--t-text-3)'}`">
                        {{ p.diff > 0 ? '+' : '' }}{{ p.diff !== 0 ? fmtMoney(p.diff) : '—' }}
                    </span>
                </div>
            </div>
        </VCard>

        <!-- Promos -->
        <VCard title="🎉 Акции и промокоды филиала">
            <div class="space-y-2">
                <div v-for="p in salonPromos" :key="p.id"
                     class="flex items-center gap-3 p-3 rounded-lg border"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex-1">
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ p.name }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">Код: <code style="color:var(--t-primary)">{{ p.code }}</code> · До: {{ p.validUntil }} · Использований: {{ p.usedCount }}</div>
                    </div>
                    <VBadge :color="p.status === 'active' ? 'green' : 'gray'" size="sm">{{ p.status === 'active' ? 'Активна' : 'Завершена' }}</VBadge>
                </div>
                <VButton size="sm" variant="outline" class="w-full">➕ Создать акцию</VButton>
            </div>
        </VCard>

        <!-- Stock management -->
        <VCard title="📦 Склад и расходники">
            <div class="space-y-2">
                <div v-for="s in stockItems" :key="s.id"
                     class="flex items-center gap-3 p-3 rounded-lg border"
                     :style="`background:${s.qty <= s.minQty ? '#fef2f210' : 'var(--t-bg)'};border-color:${s.qty <= s.minQty ? '#ef4444' : 'var(--t-border)'}`">
                    <span class="text-lg">{{ s.qty <= s.minQty ? '⚠️' : '📦' }}</span>
                    <div class="flex-1">
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ s.name }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">Последний заказ: {{ s.lastOrder }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-bold" :style="`color:${s.qty <= s.minQty ? '#ef4444' : 'var(--t-text)'}`">{{ s.qty }} {{ s.unit }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">мин: {{ s.minQty }}</div>
                    </div>
                    <VButton size="sm" variant="outline" @click="openStockOrder(s)">🛒 Заказать</VButton>
                </div>
            </div>
        </VCard>

        <!-- Audit log -->
        <VCard title="🔍 Аудит действий сотрудников">
            <div class="space-y-1.5">
                <div v-for="a in auditLog" :key="a.id"
                     class="flex items-start gap-2 p-2 rounded-lg border"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-sm mt-0.5">{{ severityLabels[a.severity] }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm" style="color:var(--t-text)">{{ a.action }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ a.user }} · {{ a.date }}</div>
                    </div>
                    <VBadge :color="severityColors[a.severity]" size="sm">{{ a.severity }}</VBadge>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ MODALS ═══ -->
    <!-- Deactivate confirm -->
    <VModal :show="showDeactivateConfirm" @close="showDeactivateConfirm = false" title="🔴 Закрыть филиал?">
        <div class="space-y-3">
            <p class="text-sm" style="color:var(--t-text)">
                Вы собираетесь деактивировать филиал <strong>{{ salonProfile.name }}</strong>.
            </p>
            <p class="text-sm" style="color:var(--t-text-2)">
                Все текущие записи будут перенесены или отменены.
                Мастера потеряют привязку к этому филиалу.
                Онлайн-запись будет отключена.
            </p>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showDeactivateConfirm = false">Отмена</VButton>
            <VButton @click="deactivateSalon" style="background:#ef4444;color:#fff">⛔ Деактивировать</VButton>
        </template>
    </VModal>

    <!-- Stock order modal -->
    <VModal :show="showStockOrderModal" @close="showStockOrderModal = false" title="🛒 Заказ расходника">
        <div v-if="orderItem" class="space-y-3">
            <div class="text-sm" style="color:var(--t-text-2)">Товар: <strong style="color:var(--t-text)">{{ orderItem.name }}</strong></div>
            <div class="text-xs" style="color:var(--t-text-3)">Текущий остаток: {{ orderItem.qty }} {{ orderItem.unit }} (мин: {{ orderItem.minQty }})</div>
            <VInput v-model="orderQty" type="number" placeholder="Количество" />
        </div>
        <template #footer>
            <VButton variant="outline" @click="showStockOrderModal = false">Отмена</VButton>
            <VButton @click="showStockOrderModal = false">📦 Оформить заказ</VButton>
        </template>
    </VModal>

    <!-- Promo modal -->
    <VModal :show="showPromoModal" @close="showPromoModal = false" title="🎉 Запустить акцию для филиала">
        <div class="space-y-3">
            <div class="text-sm" style="color:var(--t-text-2)">Филиал: <strong style="color:var(--t-text)">{{ salonProfile.name }}</strong></div>
            <VInput placeholder="Название акции" />
            <VInput placeholder="Промокод (например: SPRING15)" />
            <div class="grid grid-cols-2 gap-2">
                <VInput placeholder="Скидка, %" type="number" />
                <VInput placeholder="Действует до" type="text" />
            </div>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showPromoModal = false">Отмена</VButton>
            <VButton @click="showPromoModal = false">🚀 Запустить</VButton>
        </template>
    </VModal>
</div>
</template>
