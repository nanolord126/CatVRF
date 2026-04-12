<script setup>
/**
 * BeautyPanel — полный B2B-кабинет вертикали Beauty.
 * 11 секций: дашборд, салоны, мастера, услуги, календарь,
 * бронирования, CRM, финансы, маркетинг, аналитика, настройки.
 */
import { ref, computed, reactive, onMounted } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VStatCard from '../../UI/VStatCard.vue';
import VButton from '../../UI/VButton.vue';
import VBadge from '../../UI/VBadge.vue';
import VModal from '../../UI/VModal.vue';
import VInput from '../../UI/VInput.vue';
import VTable from '../../UI/VTable.vue';
import BeautyCalendar from './BeautyCalendar.vue';
import BeautySalonCard from './BeautySalonCard.vue';
import BeautyCRM from './BeautyCRM.vue';
import BeautyMasterCard from './BeautyMasterCard.vue';
import BeautyClientCard from './BeautyClientCard.vue';
import BeautyInteractions from './BeautyInteractions.vue';
import BeautyChat from './BeautyChat.vue';
import BeautyFinances from './BeautyFinances.vue';
import BeautyReports from './BeautyReports.vue';
import BeautyTryOn from './BeautyTryOn.vue';
import BeautyStaff from './BeautyStaff.vue';
import BeautyInventory from './BeautyInventory.vue';
import BeautyPublicPages from './BeautyPublicPages.vue';
import BeautyPageStats from './BeautyPageStats.vue';
import BeautyLoyalty from './BeautyLoyalty.vue';
import BeautyReviews from './BeautyReviews.vue';
import BeautyNotifications from './BeautyNotifications.vue';
import { useBeautyApi } from '../../../Composables/useBeautyApi';

/* ─── API Layer ─── */
const api = useBeautyApi();
const dataLoaded = ref(false);
const dataError = ref('');

async function loadDashboardData() {
    try {
        const [salonsData, mastersData, servicesData, bookingsData, dashboard] = await Promise.all([
            api.fetchSalons(),
            api.fetchMasters(),
            api.fetchServices(),
            api.fetchAppointments({ status: 'confirmed,pending', upcoming: true }),
            api.fetchDashboard(),
        ]);
        if (salonsData.length > 0) { salons.value = salonsData; }
        if (mastersData.length > 0) { masters.value = mastersData; }
        if (servicesData.length > 0) { services.value = servicesData; }
        if (bookingsData.length > 0) { bookings.value = bookingsData; }
        if (dashboard) {
            dashStats.value = [
                { label: 'Выручка сегодня', value: fmt(dashboard.revenue_today) + ' ₽', trend: '', icon: '💰' },
                { label: 'Выручка за неделю', value: fmt(dashboard.revenue_week) + ' ₽', trend: '', icon: '📈' },
                { label: 'Активных записей', value: String(dashboard.active_bookings), trend: '', icon: '📅' },
                { label: 'Загрузка мастеров', value: dashboard.masters_load + '%', trend: '', icon: '⏱️' },
                { label: 'Средний чек', value: fmt(dashboard.avg_check) + ' ₽', trend: '', icon: '🧾' },
                { label: 'Конверсия', value: (dashboard.conversion || 0) + '%', trend: '', icon: '🎯' },
            ];
        }
        dataLoaded.value = true;
    } catch (e) {
        dataError.value = 'Ошибка загрузки данных. Используются локальные данные.';
        dataLoaded.value = true;
    }
}

onMounted(() => { loadDashboardData(); });

/* ─── Tabs ─── */
const tabs = [
    { key: 'dashboard', label: 'Дашборд' },
    { key: 'salons',    label: 'Салоны', badge: 3 },
    { key: 'masters',   label: 'Мастера', badge: 14 },
    { key: 'staff',     label: '👥 Персонал' },
    { key: 'inventory', label: '📦 Инвентарь' },
    { key: 'services',  label: 'Услуги' },
    { key: 'calendar',  label: 'Календарь' },
    { key: 'bookings',  label: 'Записи', badge: 8 },
    { key: 'clients',   label: 'Клиенты' },
    { key: 'chat',      label: '💬 Чат' },
    { key: 'finances',  label: 'Финансы' },
    { key: 'promo',     label: 'Акции' },
    { key: 'reports',   label: 'Отчёты' },
    { key: 'tryon',     label: '🪞 AI-образ' },
    { key: 'pages',     label: '📄 Паблики' },
    { key: 'page-stats', label: '📈 Стат. страниц' },
    { key: 'loyalty',    label: '🎁 Лояльность' },
    { key: 'reviews',    label: '⭐ Отзывы' },
    { key: 'notifications', label: '🔔 Уведомления' },
    { key: 'config',    label: 'Настройки' },
];
const activeTab = ref('dashboard');

/* ─── 1. Dashboard ─── */
const dashStats = ref([
    { label: 'Выручка сегодня',     value: '127 400 ₽', trend: '+18%', icon: '💰' },
    { label: 'Выручка за неделю',   value: '843 200 ₽', trend: '+12%', icon: '📈' },
    { label: 'Активных записей',    value: '24',        trend: '',     icon: '📅' },
    { label: 'Загрузка мастеров',   value: '78%',       trend: '+5%',  icon: '⏱️' },
    { label: 'Средний чек',         value: '3 420 ₽',   trend: '+8%',  icon: '🧾' },
    { label: 'Конверсия',           value: '64%',       trend: '+3%',  icon: '🎯' },
]);
const upcomingBookings = ref([
    { time: '10:00', client: 'Мария К.', service: 'Стрижка + укладка', master: 'Анна С.', status: 'confirmed' },
    { time: '10:30', client: 'Елена П.', service: 'Маникюр гель-лак', master: 'Ольга Д.', status: 'confirmed' },
    { time: '11:00', client: 'Дарья В.', service: 'Окрашивание AirTouch', master: 'Анна С.', status: 'pending' },
    { time: '11:30', client: 'Ирина М.', service: 'Массаж лица', master: 'Светлана Р.', status: 'confirmed' },
    { time: '12:00', client: 'Наталья Б.', service: 'Брови + ресницы', master: 'Кристина Л.', status: 'pending' },
]);
const recentReviews = ref([
    { client: 'Мария К.', rating: 5, text: 'Отличная стрижка, как всегда!', date: '08.04.2026' },
    { client: 'Ольга Р.', rating: 4, text: 'Хороший маникюр, немного подождала.', date: '07.04.2026' },
    { client: 'Татьяна С.', rating: 5, text: 'Лучшее окрашивание в городе.', date: '07.04.2026' },
]);
const lowStockAlerts = ref([
    { name: 'Краска Wella 7/0', remaining: 2, min: 5 },
    { name: 'Гель-лак CND Pink', remaining: 1, min: 3 },
    { name: 'Маска для волос Olaplex', remaining: 3, min: 5 },
]);

/* ─── 2. Салоны ─── */
const salons = ref([
    { id: 1, name: 'BeautyLab Центр', address: 'ул. Ленина 12', status: 'active', rooms: 6, zones: ['Барбер', 'Маникюр', 'Косметология'], hours: '09:00–21:00', photo: '', rating: 4.8, bookingsToday: 18 },
    { id: 2, name: 'BeautyLab Север', address: 'пр. Мира 45',   status: 'active', rooms: 4, zones: ['Парикмахерская', 'Маникюр'], hours: '10:00–20:00', photo: '', rating: 4.6, bookingsToday: 11 },
    { id: 3, name: 'BeautyLab SPA',   address: 'ул. Речная 8',  status: 'archived', rooms: 8, zones: ['SPA', 'Массаж', 'Косметология'], hours: '10:00–22:00', photo: '', rating: 4.9, bookingsToday: 0 },
]);
const showAddSalon = ref(false);
const newSalon = reactive({ name: '', address: '', hours: '09:00–21:00', zones: '' });
const salonStatusColors = { active: 'green', archived: 'gray' };
const salonStatusLabels = { active: 'Активен', archived: 'Архив' };
const selectedSalon = ref(null);
function openSalonCard(s) { selectedSalon.value = s; }
function closeSalonCard() { selectedSalon.value = null; }

/* ─── 3. Мастера ─── */
const masters = ref([
    { id: 1, name: 'Анна Соколова',    salon: 'BeautyLab Центр', specialization: 'Стрижки, Окрашивание', level: 'Топ',    commission: '40%', rating: 4.9, reviews: 214, schedule: 'Пн–Пт', isOnline: true },
    { id: 2, name: 'Ольга Демидова',   salon: 'BeautyLab Центр', specialization: 'Маникюр, Педикюр',     level: 'Мастер', commission: '35%', rating: 4.8, reviews: 189, schedule: 'Пн–Сб', isOnline: true },
    { id: 3, name: 'Светлана Романова', salon: 'BeautyLab Центр', specialization: 'Косметология, Массаж', level: 'Мастер', commission: '35%', rating: 4.7, reviews: 156, schedule: 'Вт–Сб', isOnline: false },
    { id: 4, name: 'Кристина Лебедева',salon: 'BeautyLab Центр', specialization: 'Брови, Ресницы',       level: 'Джуниор',commission: '30%', rating: 4.5, reviews: 87,  schedule: 'Ср–Вс', isOnline: true },
    { id: 5, name: 'Игорь Волков',     salon: 'BeautyLab Север', specialization: 'Барбер',               level: 'Топ',    commission: '40%', rating: 4.9, reviews: 302, schedule: 'Пн–Пт', isOnline: true },
    { id: 6, name: 'Марина Козлова',   salon: 'BeautyLab Север', specialization: 'Маникюр, Педикюр',     level: 'Мастер', commission: '35%', rating: 4.6, reviews: 134, schedule: 'Пн–Пт', isOnline: false },
]);
const showAddMaster = ref(false);
const newMaster = reactive({ name: '', salon: '', specialization: '', level: 'Мастер', commission: '35%' });
const masterLevelColors = { 'Топ': 'purple', 'Мастер': 'blue', 'Джуниор': 'gray' };
const selectedMaster = ref(null);
function openMasterCard(m) { selectedMaster.value = m; }
function closeMasterCard() { selectedMaster.value = null; }

/* ─── 4. Услуги ─── */
const serviceCategories = ref([
    { key: 'hair',   label: 'Волосы',       count: 12 },
    { key: 'nails',  label: 'Ногти',        count: 8 },
    { key: 'face',   label: 'Лицо',         count: 6 },
    { key: 'body',   label: 'Тело / SPA',   count: 5 },
    { key: 'brows',  label: 'Брови / Ресницы', count: 4 },
]);
const activeServiceCategory = ref('hair');
const services = ref([
    { id: 1, cat: 'hair',  name: 'Стрижка женская',   duration: 60, buffer: 10, price: 2500, promoPrice: 2000, vipPrice: 2200, modifiers: ['Короткие', 'Средние', 'Длинные'], cost: 350, inStock: true },
    { id: 2, cat: 'hair',  name: 'Окрашивание AirTouch', duration: 180, buffer: 15, price: 8500, promoPrice: null, vipPrice: 7500, modifiers: ['До плеч', 'Длинные'], cost: 2400, inStock: true },
    { id: 3, cat: 'hair',  name: 'Укладка',           duration: 45, buffer: 5,  price: 1800, promoPrice: 1500, vipPrice: 1600, modifiers: [], cost: 200, inStock: true },
    { id: 4, cat: 'nails', name: 'Маникюр гель-лак',  duration: 90, buffer: 10, price: 2800, promoPrice: 2500, vipPrice: 2500, modifiers: ['Короткие', 'Длинные', 'Дизайн'], cost: 450, inStock: true },
    { id: 5, cat: 'nails', name: 'Педикюр аппаратный', duration: 75, buffer: 10, price: 3200, promoPrice: null, vipPrice: 2900, modifiers: [], cost: 380, inStock: true },
    { id: 6, cat: 'face',  name: 'Чистка лица',       duration: 60, buffer: 10, price: 3500, promoPrice: 3000, vipPrice: 3000, modifiers: ['Комбинированная', 'Ультразвуковая'], cost: 600, inStock: true },
    { id: 7, cat: 'face',  name: 'Массаж лица',       duration: 40, buffer: 5,  price: 2000, promoPrice: null, vipPrice: 1800, modifiers: [], cost: 150, inStock: true },
    { id: 8, cat: 'body',  name: 'Массаж спины',      duration: 60, buffer: 10, price: 3000, promoPrice: 2700, vipPrice: 2700, modifiers: [], cost: 200, inStock: true },
    { id: 9, cat: 'brows', name: 'Коррекция бровей',  duration: 30, buffer: 5,  price: 1200, promoPrice: null, vipPrice: 1000, modifiers: [], cost: 100, inStock: true },
    { id: 10,cat: 'brows', name: 'Ламинирование ресниц', duration: 60, buffer: 10, price: 2500, promoPrice: 2200, vipPrice: 2200, modifiers: [], cost: 350, inStock: false },
]);
const filteredServices = computed(() => services.value.filter(s => s.cat === activeServiceCategory.value));
const showAddService = ref(false);
const showPackages = ref(false);
const packages = ref([
    { id: 1, name: 'Полный образ',    services: ['Стрижка', 'Укладка', 'Маникюр'], price: 6200, discount: 15 },
    { id: 2, name: 'SPA-день',        services: ['Массаж спины', 'Массаж лица', 'Чистка лица'], price: 7500, discount: 12 },
    { id: 3, name: 'Абонемент 5 стрижек', services: ['Стрижка женская x5'], price: 10000, discount: 20 },
]);

/* ─── 5. Календарь — делегирован BeautyCalendar.vue ─── */
const showQuickBook = ref(false);

/* ─── 6. Бронирования ─── */
const bookings = ref([
    { id: 1001, client: 'Мария К.',   phone: '+7 900 111-22-33', service: 'Стрижка + укладка',      master: 'Анна С.',      salon: 'Центр', date: '08.04.2026 10:00', status: 'confirmed',  prepaid: 1000, total: 4300 },
    { id: 1002, client: 'Елена П.',   phone: '+7 900 222-33-44', service: 'Маникюр гель-лак',       master: 'Ольга Д.',     salon: 'Центр', date: '08.04.2026 10:30', status: 'confirmed',  prepaid: 0,    total: 2800 },
    { id: 1003, client: 'Дарья В.',   phone: '+7 900 333-44-55', service: 'Окрашивание AirTouch',    master: 'Анна С.',      salon: 'Центр', date: '08.04.2026 11:00', status: 'pending',    prepaid: 3000, total: 8500 },
    { id: 1004, client: 'Ирина М.',   phone: '+7 900 444-55-66', service: 'Массаж лица',             master: 'Светлана Р.',  salon: 'Центр', date: '08.04.2026 11:30', status: 'confirmed',  prepaid: 2000, total: 2000 },
    { id: 1005, client: 'Наталья Б.', phone: '+7 900 555-66-77', service: 'Коррекция бровей',        master: 'Кристина Л.',  salon: 'Центр', date: '08.04.2026 12:00', status: 'pending',    prepaid: 0,    total: 1200 },
    { id: 1006, client: 'Виктория Н.',phone: '+7 900 666-77-88', service: 'Стрижка женская',         master: 'Анна С.',      salon: 'Центр', date: '08.04.2026 14:00', status: 'confirmed',  prepaid: 0,    total: 2500 },
    { id: 1007, client: 'Регина К.',  phone: '+7 900 777-88-99', service: 'Маникюр + педикюр',       master: 'Ольга Д.',     salon: 'Центр', date: '08.04.2026 14:00', status: 'completed',  prepaid: 0,    total: 5600 },
    { id: 1008, client: 'Алиса Т.',   phone: '+7 900 888-99-00', service: 'Чистка лица',             master: 'Светлана Р.',  salon: 'Центр', date: '08.04.2026 13:00', status: 'cancelled',  prepaid: 0,    total: 3500 },
]);
const bookingStatusColors = { confirmed: 'green', pending: 'yellow', completed: 'blue', cancelled: 'red', no_show: 'gray' };
const bookingStatusLabels = { confirmed: 'Подтверждена', pending: 'Ожидает', completed: 'Завершена', cancelled: 'Отменена', no_show: 'Не пришёл' };
const selectedBookings = ref([]);
const showBookingDetail = ref(false);
const activeBooking = ref(null);
function openBooking(b) { activeBooking.value = b; showBookingDetail.value = true; }

/* ─── 7. CRM — вынесен в BeautyCRM.vue ─── */

/* ─── Client Card (from CRM or bookings) ─── */
const showClientCard = ref(false);
const activeClient = ref(null);
const segmentColors = { VIP: 'purple', 'Постоянный': 'blue', 'Новый': 'green', 'Новичок': 'green', 'Потерянный': 'red', 'Спящий': 'yellow', 'Лояльный': 'blue' };
function openClientCard(client) { activeClient.value = client; showClientCard.value = true; }

/* ─── 8. Финансы ─── */
const financeStats = ref([
    { label: 'Выручка (месяц)',   value: '3 240 800 ₽', trend: '+14%' },
    { label: 'Комиссии мастерам', value: '1 134 280 ₽', trend: '' },
    { label: 'Себестоимость',     value: '486 120 ₽',   trend: '-3%' },
    { label: 'Чистая прибыль',    value: '1 620 400 ₽', trend: '+18%' },
]);
const masterPayouts = ref([
    { master: 'Анна Соколова',     revenue: 420000, commission: 168000, status: 'paid' },
    { master: 'Ольга Демидова',    revenue: 310000, commission: 108500, status: 'paid' },
    { master: 'Светлана Романова', revenue: 280000, commission: 98000,  status: 'pending' },
    { master: 'Кристина Лебедева', revenue: 180000, commission: 54000,  status: 'pending' },
    { master: 'Игорь Волков',      revenue: 350000, commission: 140000, status: 'paid' },
    { master: 'Марина Козлова',    revenue: 220000, commission: 77000,  status: 'draft' },
]);
const payoutStatusColors = { paid: 'green', pending: 'yellow', draft: 'gray' };
const payoutStatusLabels = { paid: 'Выплачено', pending: 'К выплате', draft: 'Черновик' };

/* ─── 9. Акции ─── */
const promos = ref([
    { id: 1, name: 'Весенняя скидка 20%',    type: 'discount', value: 20,  services: ['Стрижка', 'Укладка'],  status: 'active',   validUntil: '30.04.2026', uses: 45 },
    { id: 2, name: 'Приведи подругу — 500 ₽', type: 'referral', value: 500, services: ['Все'],                 status: 'active',   validUntil: '31.05.2026', uses: 12 },
    { id: 3, name: 'Абонемент -30%',          type: 'package',  value: 30,  services: ['Маникюр x5'],          status: 'active',   validUntil: '30.06.2026', uses: 8  },
    { id: 4, name: 'Новогодний образ',        type: 'bundle',   value: 25,  services: ['Полный образ'],        status: 'expired',  validUntil: '15.01.2026', uses: 134 },
]);
const promoStatusColors = { active: 'green', paused: 'yellow', expired: 'gray' };
const showAddPromo = ref(false);

/* ─── 10. Отчёты ─── */
const reportPeriod = ref('month');
const masterLoadData = ref([
    { master: 'Анна Соколова',     load: 92, bookings: 124, avgCheck: 3390 },
    { master: 'Игорь Волков',      load: 88, bookings: 98,  avgCheck: 3570 },
    { master: 'Ольга Демидова',    load: 81, bookings: 110, avgCheck: 2820 },
    { master: 'Светлана Романова', load: 74, bookings: 85,  avgCheck: 3290 },
    { master: 'Кристина Лебедева', load: 65, bookings: 72,  avgCheck: 2500 },
    { master: 'Марина Козлова',    load: 60, bookings: 64,  avgCheck: 3440 },
]);
const popularServices = ref([
    { name: 'Маникюр гель-лак', count: 312, revenue: 873600 },
    { name: 'Стрижка женская',  count: 284, revenue: 710000 },
    { name: 'Окрашивание',      count: 156, revenue: 1326000 },
    { name: 'Массаж лица',      count: 142, revenue: 284000 },
    { name: 'Чистка лица',      count: 128, revenue: 448000 },
]);
const retentionRate = ref(72);
const salonComparison = ref([
    { salon: 'BeautyLab Центр', revenue: 2180000, bookings: 640, avgCheck: 3406, retention: 74 },
    { salon: 'BeautyLab Север', revenue: 1060800, bookings: 320, avgCheck: 3315, retention: 68 },
]);

/* ─── 11. Настройки ─── */
const onlineBookingEnabled = ref(true);
const autoConfirm = ref(false);
const bookingAdvanceDays = ref(30);
const minCancelHours = ref(2);
const showIntegrations = ref(false);
const integrations = ref([
    { name: 'WhatsApp Business', connected: true,  icon: '💬' },
    { name: 'Telegram Bot',     connected: true,  icon: '🤖' },
    { name: 'Instagram DM',     connected: false, icon: '📸' },
    { name: 'Яндекс Карты',    connected: true,  icon: '🗺️' },
    { name: '2ГИС',            connected: false, icon: '📍' },
    { name: 'YCLIENTS',        connected: false, icon: '📋' },
]);
const auditLog = ref([
    { date: '08.04 10:12', user: 'Админ', action: 'Создал акцию «Весенняя скидка 20%»' },
    { date: '08.04 09:45', user: 'Ольга Д.', action: 'Отменила запись #1008' },
    { date: '07.04 18:30', user: 'Админ', action: 'Добавил мастера «Марина Козлова»' },
    { date: '07.04 14:20', user: 'Админ', action: 'Изменил график работы филиала «Север»' },
    { date: '06.04 11:05', user: 'Анна С.', action: 'Подтвердила запись #1003' },
]);

/* ─── Staff / Inventory handlers ─── */
async function handleStaffPayout(payoutData) {
    const name = payoutData?.masterName || payoutData?.name || 'Мастер';
    const amount = payoutData?.amount || 0;
    try {
        await api.processStaffPayout(payoutData?.masterId || 0, { amount, reason: payoutData?.reason || 'manual_payout' });
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Выплата ${fmt(amount)} ₽ мастеру «${name}»` });
    } catch (e) {
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка выплаты мастеру «${name}»: ${e?.message || 'Неизвестная ошибка'}` });
    }
}
async function handleStaffExportReport(data) {
    try {
        const blob = await api.exportReport(data?.format || 'csv', 'staff');
        api.downloadBlob(blob, `staff_report.${data?.format || 'csv'}`);
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Экспорт отчёта персонала (${data?.format || 'csv'})` });
    } catch (e) {
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка экспорта: ${e?.message || 'Неизвестная ошибка'}` });
    }
}
async function handleInventoryExportReport(data) {
    try {
        const blob = await api.exportReport(data?.format || 'csv', 'inventory');
        api.downloadBlob(blob, `inventory_report.${data?.format || 'csv'}`);
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Экспорт отчёта инвентаря (${data?.format || 'csv'})` });
    } catch (e) {
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка экспорта: ${e?.message || 'Неизвестная ошибка'}` });
    }
}

/* ─── Loyalty handlers ─── */
async function handleLoyaltyAwardBonus(data) {
    try {
        await api.awardBonus({ client_id: data?.clientId, amount: data?.amount || 0, reason: data?.reason || '' });
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Начислил бонусы клиенту «${data?.clientName || 'Клиент'}» — ${data?.amount || 0}` });
    } catch (e) {
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка начисления бонусов: ${e?.message || 'Неизвестная ошибка'}` });
    }
}
async function handleLoyaltyDeductBonus(data) {
    try {
        await api.deductBonus({ client_id: data?.clientId, amount: data?.amount || 0, reason: data?.reason || '' });
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Списал бонусы у клиента «${data?.clientName || 'Клиент'}» — ${data?.amount || 0}` });
    } catch (e) {
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка списания бонусов: ${e?.message || 'Неизвестная ошибка'}` });
    }
}
async function handleLoyaltyExport(data) {
    try {
        const blob = await api.exportReport(data?.format || 'csv', 'loyalty');
        api.downloadBlob(blob, `loyalty_report.${data?.format || 'csv'}`);
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Экспорт отчёта лояльности (${data?.format || 'csv'})` });
    } catch (e) {
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка экспорта: ${e?.message || 'Неизвестная ошибка'}` });
    }
}
async function handleLoyaltySaveSettings(data) {
    try {
        await api.updateLoyaltyConfig(data);
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: 'Сохранил настройки лояльности' });
    } catch (e) {
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка сохранения настроек: ${e?.message || 'Неизвестная ошибка'}` });
    }
}
function handleLoyaltyCreateTier(data) { auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Создал уровень лояльности «${data?.name || ''}»` }); }
function handleLoyaltyCreateRule(data) { auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Создал бонусное правило «${data?.name || ''}»` }); }

/* ─── Reviews handlers ─── */
async function handleReviewReply(data) {
    try {
        await api.replyToReview(data?.reviewId, { message: data?.message || '' });
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Ответил на отзыв #${data?.reviewId || ''}` });
    } catch (e) {
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка ответа на отзыв: ${e?.message || 'Неизвестная ошибка'}` });
    }
}
async function handleReviewFlag(data) {
    try {
        await api.flagReview(data?.reviewId, { reason: data?.reason || 'suspicious' });
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Пометил отзыв «${data?.client || ''}» как подозрительный` });
    } catch (e) {
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка пометки отзыва: ${e?.message || 'Неизвестная ошибка'}` });
    }
}
function handleReviewRequest(data) { auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Отправил запрос на отзыв` }); }
async function handleReviewExport(data) {
    try {
        const blob = await api.exportReport(data?.format || 'csv', 'reviews');
        api.downloadBlob(blob, `reviews_report.${data?.format || 'csv'}`);
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Экспорт отзывов (${data?.format || 'csv'})` });
    } catch (e) {
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка экспорта: ${e?.message || 'Неизвестная ошибка'}` });
    }
}

/* ─── Notifications handlers ─── */
async function handleNotifSend(data) {
    try {
        await api.sendNotification(data);
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Отправил уведомление` });
    } catch (e) {
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка отправки уведомления: ${e?.message || 'Неизвестная ошибка'}` });
    }
}
function handleNotifSaveTemplate(data) { auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Сохранил шаблон уведомления «${data?.name || ''}»` }); }
function handleNotifDeleteTemplate(data) { auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Удалил шаблон уведомления «${data?.name || ''}»` }); }
function handleNotifToggleReminder(data) { auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Переключил напоминание #${data?.id || ''} — ${data?.isActive ? 'вкл' : 'выкл'}` }); }
async function handleNotifExport(data) {
    try {
        const blob = await api.exportReport(data?.format || 'csv', 'notifications');
        api.downloadBlob(blob, `notifications_report.${data?.format || 'csv'}`);
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Экспорт истории уведомлений (${data?.format || 'csv'})` });
    } catch (e) {
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка экспорта: ${e?.message || 'Неизвестная ошибка'}` });
    }
}
async function handleNotifBulk(data) {
    try {
        await api.sendBulkNotification(data);
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Массовая рассылка (${data?.channel || ''}) — аудитория: ${data?.audience || 'all'}` });
    } catch (e) {
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка массовой рассылки: ${e?.message || 'Неизвестная ошибка'}` });
    }
}

/* ─── Saved AI Designs ─── */
const savedDesigns = ref([]);
function handleSaveDesign(design) {
    savedDesigns.value.push({ ...design, id: Date.now(), savedAt: new Date().toISOString() });
    auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Клиент', action: `Сохранил AI-дизайн «${design.topStyle || design.colorType || 'Образ'}»` });
}

/* ─── Public Pages handlers ─── */
const publicPagesData = ref([]);
const publicPostsData = ref([]);
async function handleCreatePage(page) {
    try {
        const created = await api.createPublicPage(page);
        publicPagesData.value.push(created || { ...page, id: Date.now(), createdAt: new Date().toISOString() });
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Создал публичную страницу «${page.name || 'Без названия'}»` });
    } catch (e) {
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка создания страницы: ${e?.message || 'Неизвестная ошибка'}` });
    }
}
async function handleEditPage(page) {
    try {
        await api.updatePublicPage(page.id, page);
        const idx = publicPagesData.value.findIndex(p => p.id === page.id);
        if (idx !== -1) { publicPagesData.value[idx] = { ...publicPagesData.value[idx], ...page }; }
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Отредактировал страницу «${page.name || 'ID:' + page.id}»` });
    } catch (e) {
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка редактирования страницы: ${e?.message || 'Неизвестная ошибка'}` });
    }
}
async function handleDeletePage(pageId) {
    try {
        await api.deletePublicPage(pageId);
        publicPagesData.value = publicPagesData.value.filter(p => p.id !== pageId);
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Удалил публичную страницу #${pageId}` });
    } catch (e) {
        auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка удаления страницы: ${e?.message || 'Неизвестная ошибка'}` });
    }
}
function handlePublishPost(post) {
    publicPostsData.value.push({ ...post, id: Date.now(), publishedAt: new Date().toISOString() });
    auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Опубликовал пост «${post.title || 'Без заголовка'}»` });
}
function handleSchedulePost(post) {
    publicPostsData.value.push({ ...post, id: Date.now(), status: 'scheduled', scheduledAt: post.scheduledAt });
    auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Запланировал пост «${post.title || 'Без заголовка'}» на ${post.scheduledAt}` });
}
function handleExportPageReport(data) {
    auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Экспортировал отчёт страниц (${data.format || 'csv'})` });
}
function handleOpenPost(postId) {
    activeTab.value = 'pages';
}

/* ─── Client bonus handler ─── */
async function handleAwardClientBonus(bonusData) {
    if (activeClient.value) {
        const amount = bonusData?.amount || bonusData;
        try {
            await api.awardBonus({ client_id: activeClient.value.id, amount: Number(amount), reason: bonusData?.reason || 'manual' });
            activeClient.value.bonusBalance = (activeClient.value.bonusBalance || 0) + Number(amount);
            auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Начислил ${amount} бонусов клиенту «${activeClient.value.name}»` });
        } catch (e) {
            auditLog.value.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Система', action: `Ошибка начисления бонусов: ${e?.message || 'Неизвестная ошибка'}` });
        }
    }
}

/* ─── Helpers ─── */
function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }
</script>

<template>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold" style="color:var(--t-text)">💇 Beauty — B2B Кабинет</h1>
            <p class="text-sm mt-1" style="color:var(--t-text-2)">Салоны красоты, мастера, услуги, записи</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <VButton size="sm" @click="showQuickBook = true">📅 Записать клиента</VButton>
            <VButton size="sm" variant="outline" @click="showAddMaster = true">👤 Добавить мастера</VButton>
            <VButton size="sm" variant="outline" @click="showAddPromo = true">🎁 Создать акцию</VButton>
        </div>
    </div>

    <!-- Tabs -->
    <VTabs :tabs="tabs" v-model="activeTab" />

    <!-- ═══ 1. DASHBOARD ═══ -->
    <div v-if="activeTab === 'dashboard'" class="space-y-6">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <VStatCard v-for="s in dashStats" :key="s.label" :title="s.label" :value="s.value">
                <template #icon><span class="text-xl">{{ s.icon }}</span></template>
                <template #trend v-if="s.trend"><span class="text-green-400 text-xs">{{ s.trend }}</span></template>
            </VStatCard>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Ближайшие записи -->
            <VCard title="📅 Ближайшие записи" class="lg:col-span-2">
                <div class="space-y-2">
                    <div v-for="b in upcomingBookings" :key="b.time"
                         class="flex items-center gap-3 p-3 rounded-xl border"
                         style="background:var(--t-surface);border-color:var(--t-border)">
                        <span class="font-mono text-sm font-bold" style="color:var(--t-primary)">{{ b.time }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium truncate" style="color:var(--t-text)">{{ b.client }}</div>
                            <div class="text-xs truncate" style="color:var(--t-text-2)">{{ b.service }} · {{ b.master }}</div>
                        </div>
                        <VBadge :color="b.status === 'confirmed' ? 'green' : 'yellow'" size="sm">
                            {{ b.status === 'confirmed' ? 'Подтв.' : 'Ожидает' }}
                        </VBadge>
                    </div>
                </div>
            </VCard>

            <!-- Sidebar widgets -->
            <div class="space-y-4">
                <!-- Отзывы -->
                <VCard title="⭐ Новые отзывы">
                    <div class="space-y-3">
                        <div v-for="r in recentReviews" :key="r.client" class="text-sm">
                            <div class="flex justify-between">
                                <span class="font-medium" style="color:var(--t-text)">{{ r.client }}</span>
                                <span>{{ '★'.repeat(r.rating) }}</span>
                            </div>
                            <p class="text-xs mt-1" style="color:var(--t-text-2)">{{ r.text }}</p>
                        </div>
                    </div>
                </VCard>
                <!-- Расходники -->
                <VCard title="⚠️ Низкий остаток">
                    <div class="space-y-2">
                        <div v-for="a in lowStockAlerts" :key="a.name" class="flex justify-between items-center text-sm">
                            <span style="color:var(--t-text)">{{ a.name }}</span>
                            <VBadge color="red" size="sm">{{ a.remaining }} / {{ a.min }}</VBadge>
                        </div>
                    </div>
                </VCard>
            </div>
        </div>
    </div>

    <!-- ═══ 2. САЛОНЫ ═══ -->
    <div v-if="activeTab === 'salons'" class="space-y-4">
        <!-- Salon Card (detail view) -->
        <template v-if="selectedSalon">
            <BeautySalonCard
                :salon="selectedSalon"
                :masters="masters"
                :salons="salons"
                :services="services"
                :bookings="bookings"
                @close="closeSalonCard"
                @edit="closeSalonCard"
                @open-calendar="closeSalonCard"
                @create-booking="closeSalonCard"
                @add-employee="showAddMaster = true"
                @run-promo="showAddPromo = true"
                @generate-report="() => { activeTab = 'reports'; closeSalonCard(); }"
                @export-data="handleStaffExportReport"
                @deactivate="(s) => { s.status = 'archived'; closeSalonCard(); }"
            />
        </template>
        <!-- Salon list -->
        <template v-else>
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold" style="color:var(--t-text)">Филиалы салонов</h2>
            <VButton size="sm" @click="showAddSalon = true">➕ Добавить салон</VButton>
        </div>
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
            <VCard v-for="s in salons" :key="s.id" @click="openSalonCard(s)" class="cursor-pointer">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="font-bold text-lg" style="color:var(--t-text)">{{ s.name }}</h3>
                        <p class="text-xs" style="color:var(--t-text-2)">{{ s.address }}</p>
                    </div>
                    <VBadge :color="salonStatusColors[s.status]" size="sm">{{ salonStatusLabels[s.status] }}</VBadge>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center text-sm mb-3">
                    <div class="p-2 rounded-lg" style="background:var(--t-bg)">
                        <div class="font-bold" style="color:var(--t-primary)">{{ s.rooms }}</div><div class="text-xs" style="color:var(--t-text-3)">Кабинетов</div>
                    </div>
                    <div class="p-2 rounded-lg" style="background:var(--t-bg)">
                        <div class="font-bold" style="color:var(--t-primary)">⭐ {{ s.rating }}</div><div class="text-xs" style="color:var(--t-text-3)">Рейтинг</div>
                    </div>
                    <div class="p-2 rounded-lg" style="background:var(--t-bg)">
                        <div class="font-bold" style="color:var(--t-primary)">{{ s.bookingsToday }}</div><div class="text-xs" style="color:var(--t-text-3)">Записей</div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1 mb-3">
                    <VBadge v-for="z in s.zones" :key="z" color="blue" size="sm">{{ z }}</VBadge>
                </div>
                <div class="text-xs" style="color:var(--t-text-3)">🕐 {{ s.hours }}</div>
                <div class="flex gap-2 mt-3">
                    <VButton size="sm" variant="outline" class="flex-1">✏️ Редактировать</VButton>
                    <VButton size="sm" variant="outline">🏠 3D-тур</VButton>
                </div>
            </VCard>
        </div>
        </template>
    </div>

    <!-- ═══ 3. МАСТЕРА ═══ -->
    <div v-if="activeTab === 'masters'" class="space-y-4">
        <!-- Master Card (detail view) -->
        <template v-if="selectedMaster">
            <BeautyMasterCard
                :master="selectedMaster"
                :salons="salons"
                :services="services"
                :bookings="bookings"
                @close="closeMasterCard"
                @edit="closeMasterCard"
                @book-client="closeMasterCard"
                @open-calendar="activeTab = 'calendar'; closeMasterCard()"
                @award-bonus="handleAwardClientBonus"
                @send-message="() => { activeTab = 'chat'; closeMasterCard(); }"
                @generate-report="() => { activeTab = 'reports'; closeMasterCard(); }"
            />
        </template>

        <!-- Masters grid (list view) -->
        <template v-else>
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold" style="color:var(--t-text)">Мастера и специалисты</h2>
                <VButton size="sm" @click="showAddMaster = true">➕ Добавить мастера</VButton>
            </div>
            <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                <VCard v-for="m in masters" :key="m.id" class="cursor-pointer hover:shadow-lg transition-shadow" @click="openMasterCard(m)">
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold"
                             :style="`background:var(--t-primary-dim);color:var(--t-primary)`">
                            {{ m.name.charAt(0) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-bold truncate" style="color:var(--t-text)">{{ m.name }}</span>
                                <span v-if="m.isOnline" class="w-2 h-2 bg-green-400 rounded-full"></span>
                            </div>
                            <div class="text-xs" style="color:var(--t-text-2)">{{ m.salon }}</div>
                        </div>
                        <VBadge :color="masterLevelColors[m.level]" size="sm">{{ m.level }}</VBadge>
                    </div>
                    <div class="mt-3 text-sm" style="color:var(--t-text-2)">{{ m.specialization }}</div>
                    <div class="grid grid-cols-3 gap-2 text-center text-xs mt-3">
                        <div class="p-2 rounded-lg" style="background:var(--t-bg)">
                            <div class="font-bold" style="color:var(--t-primary)">⭐ {{ m.rating }}</div>Рейтинг
                        </div>
                        <div class="p-2 rounded-lg" style="background:var(--t-bg)">
                            <div class="font-bold" style="color:var(--t-primary)">{{ m.reviews }}</div>Отзывов
                        </div>
                        <div class="p-2 rounded-lg" style="background:var(--t-bg)">
                            <div class="font-bold" style="color:var(--t-primary)">{{ m.commission }}</div>Комиссия
                        </div>
                    </div>
                    <div class="text-xs mt-2" style="color:var(--t-text-3)">📅 {{ m.schedule }}</div>
                    <div class="flex gap-2 mt-3">
                        <VButton size="sm" variant="outline" class="flex-1" @click.stop="openMasterCard(m)">📋 Портфолио</VButton>
                        <VButton size="sm" variant="outline" class="flex-1" @click.stop="openMasterCard(m)">✏️ Редакт.</VButton>
                    </div>
                </VCard>
            </div>
        </template>
    </div>

    <!-- ═══ 3½. ПЕРСОНАЛ (STAFF) ═══ -->
    <div v-if="activeTab === 'staff'">
        <BeautyStaff
            :masters="masters"
            :salons="salons"
            @open-master="openMasterCard"
            @add-master="showAddMaster = true"
            @edit-master="openMasterCard"
            @fire-master="(m) => { const idx = masters.findIndex(x => x.id === m.id); if (idx !== -1) masters.splice(idx, 1); auditLog.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Уволен: ${m?.name || 'мастер'}` }); }"
            @create-shift="(s) => { auditLog.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Смена создана: ${s?.date || 'N/A'}` }); }"
            @payout="handleStaffPayout"
            @export-report="handleStaffExportReport"
        />
    </div>

    <!-- ═══ 3¾. ИНВЕНТАРЬ ═══ -->
    <div v-if="activeTab === 'inventory'">
        <BeautyInventory
            :masters="masters"
            :salons="salons"
            :services="services"
            @open-master="openMasterCard"
            @open-client="openClientCard"
            @export-report="handleInventoryExportReport"
            @writeoff="(d) => { auditLog.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Списание: ${d?.name || d?.productName || 'товар'}` }); }"
            @restock="(d) => { auditLog.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Пополнение: ${d?.name || d?.productName || 'товар'}` }); }"
            @low-stock-alert="(d) => { lowStockAlerts.push(d); }"
        />
    </div>

    <!-- ═══ 4. УСЛУГИ ═══ -->
    <div v-if="activeTab === 'services'" class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <h2 class="text-lg font-semibold" style="color:var(--t-text)">Каталог услуг</h2>
            <div class="flex gap-2">
                <VButton size="sm" variant="outline" @click="showPackages = true">📦 Пакеты / Абонементы</VButton>
                <VButton size="sm" @click="showAddService = true">➕ Добавить услугу</VButton>
            </div>
        </div>
        <!-- Category chips -->
        <div class="flex gap-2 flex-wrap">
            <button v-for="c in serviceCategories" :key="c.key"
                    @click="activeServiceCategory = c.key"
                    class="px-4 py-2 rounded-full text-sm font-medium transition-all border"
                    :style="activeServiceCategory === c.key ? 'background:var(--t-primary);color:#fff;border-color:var(--t-primary)' : 'background:var(--t-surface);color:var(--t-text-2);border-color:var(--t-border)'">
                {{ c.label }} ({{ c.count }})
            </button>
        </div>
        <!-- Services list -->
        <div class="space-y-2">
            <div v-for="s in filteredServices" :key="s.id"
                 class="p-4 rounded-xl border flex items-center gap-4 flex-wrap"
                 style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="flex-1 min-w-[200px]">
                    <div class="font-bold" style="color:var(--t-text)">{{ s.name }}</div>
                    <div class="text-xs mt-1" style="color:var(--t-text-3)">⏱ {{ s.duration }} мин + {{ s.buffer }} мин буфер</div>
                    <div v-if="s.modifiers.length" class="flex gap-1 mt-1 flex-wrap">
                        <VBadge v-for="mod in s.modifiers" :key="mod" color="blue" size="sm">{{ mod }}</VBadge>
                    </div>
                </div>
                <div class="text-right space-y-1">
                    <div class="font-bold" style="color:var(--t-primary)">{{ fmt(s.price) }} ₽</div>
                    <div v-if="s.promoPrice" class="text-xs text-green-400">Акция: {{ fmt(s.promoPrice) }} ₽</div>
                    <div class="text-xs" style="color:var(--t-text-3)">VIP: {{ fmt(s.vipPrice) }} ₽</div>
                    <div class="text-xs" style="color:var(--t-text-3)">Себест.: {{ fmt(s.cost) }} ₽</div>
                </div>
                <div class="flex gap-1">
                    <VButton size="sm" variant="outline">✏️</VButton>
                    <VButton size="sm" variant="outline" v-if="!s.inStock" class="opacity-50">🚫</VButton>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ 5. КАЛЕНДАРЬ ═══ -->
    <div v-if="activeTab === 'calendar'">
        <BeautyCalendar
            :masters="masters"
            :salons="salons"
            :services="services"
            :bookings="bookings"
            @create-booking="showQuickBook = true"
            @update-booking="(d) => { const b = bookings.find(x => x.id === d.id); if (b) b.status = d.status; }"
            @cancel-booking="(d) => { const b = bookings.find(x => x.id === d.id); if (b) b.status = 'cancelled'; }"
            @move-booking="(d) => { const b = bookings.find(x => x.id === d.id); if (b) { b.date = d.date; b.time = d.time; } }"
            @resize-booking="(d) => { const b = bookings.find(x => x.id === d.id); if (b) b.duration = d.duration; }"
            @quick-book="showQuickBook = true"
        />
    </div>

    <!-- ═══ 6. БРОНИРОВАНИЯ ═══ -->
    <div v-if="activeTab === 'bookings'" class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <h2 class="text-lg font-semibold" style="color:var(--t-text)">Записи и бронирования</h2>
            <div class="flex gap-2">
                <VButton v-if="selectedBookings.length" size="sm" variant="outline">✅ Подтвердить ({{ selectedBookings.length }})</VButton>
                <VButton v-if="selectedBookings.length" size="sm" variant="outline">❌ Отменить ({{ selectedBookings.length }})</VButton>
                <VButton size="sm" @click="showQuickBook = true">📅 Новая запись</VButton>
            </div>
        </div>

        <div class="space-y-2">
            <div v-for="b in bookings" :key="b.id"
                 class="p-4 rounded-xl border flex items-center gap-4 flex-wrap cursor-pointer hover:shadow-lg transition-shadow"
                 style="background:var(--t-surface);border-color:var(--t-border)"
                 @click="openBooking(b)">
                <input type="checkbox" v-model="selectedBookings" :value="b.id" class="w-4 h-4" @click.stop>
                <div class="w-16 text-center">
                    <div class="text-xs" style="color:var(--t-text-3)">{{ b.date.split(' ')[0] }}</div>
                    <div class="font-bold text-sm" style="color:var(--t-primary)">{{ b.date.split(' ')[1] }}</div>
                </div>
                <div class="flex-1 min-w-[180px]">
                    <div class="font-medium" style="color:var(--t-text)">{{ b.client }}</div>
                    <div class="text-xs" style="color:var(--t-text-2)">{{ b.service }} · {{ b.master }}</div>
                </div>
                <div class="text-right text-sm">
                    <div class="font-bold" style="color:var(--t-text)">{{ fmt(b.total) }} ₽</div>
                    <div v-if="b.prepaid" class="text-xs text-green-400">Предоплата {{ fmt(b.prepaid) }} ₽</div>
                </div>
                <VBadge :color="bookingStatusColors[b.status]" size="sm">{{ bookingStatusLabels[b.status] }}</VBadge>
            </div>
        </div>
    </div>

    <!-- ═══ 7. КЛИЕНТЫ / CRM ═══ -->
    <div v-if="activeTab === 'clients'" class="space-y-6">
        <BeautyCRM :masters="masters" :salons="salons" :services="services" :bookings="bookings"
                   @open-client="openClientCard"
                   @book-master="showQuickBook = true"
                   @settings-saved="(d) => { auditLog.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: 'CRM-настройки сохранены' }); }" />
        <BeautyInteractions :masters="masters" :services="services"
                            @open-client="openClientCard"
                            @reply-message="(m) => { activeTab = 'chat'; }" />
    </div>

    <!-- ═══ 7.5. ЧАТ ═══ -->
    <div v-if="activeTab === 'chat'">
        <BeautyChat :masters="masters" :clients="[]"
                    @open-client="openClientCard"
                    @book-client="showQuickBook = true"
                    @award-bonus="openClientCard" />
    </div>

    <!-- ═══ 8. ФИНАНСЫ ═══ -->
    <div v-if="activeTab === 'finances'">
        <BeautyFinances :masters="masters" :salons="salons"
                        @payout="handleStaffPayout"
                        @export-report="handleStaffExportReport" />
    </div>

    <!-- ═══ 9. АКЦИИ ═══ -->
    <div v-if="activeTab === 'promo'" class="space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold" style="color:var(--t-text)">Маркетинг и акции</h2>
            <VButton size="sm" @click="showAddPromo = true">➕ Создать акцию</VButton>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <VCard v-for="p in promos" :key="p.id">
                <div class="flex items-start justify-between mb-2">
                    <h3 class="font-bold" style="color:var(--t-text)">{{ p.name }}</h3>
                    <VBadge :color="promoStatusColors[p.status]" size="sm">{{ p.status === 'active' ? 'Активна' : 'Истекла' }}</VBadge>
                </div>
                <div class="text-sm space-y-1" style="color:var(--t-text-2)">
                    <div>Скидка: <strong style="color:var(--t-primary)">{{ p.value }}{{ p.type === 'referral' ? ' ₽' : '%' }}</strong></div>
                    <div>Услуги: {{ p.services.join(', ') }}</div>
                    <div>Использований: <strong>{{ p.uses }}</strong></div>
                    <div>Действует до: {{ p.validUntil }}</div>
                </div>
                <div class="flex gap-2 mt-3">
                    <VButton size="sm" variant="outline" class="flex-1">✏️ Изменить</VButton>
                    <VButton size="sm" variant="outline">📊 Статистика</VButton>
                </div>
            </VCard>
        </div>

        <VCard title="📣 Быстрые рассылки">
            <div class="flex gap-3 flex-wrap">
                <VButton size="sm" variant="outline">💬 SMS-рассылка клиентам</VButton>
                <VButton size="sm" variant="outline">📲 Push-уведомление</VButton>
                <VButton size="sm" variant="outline">📧 Email-рассылка</VButton>
            </div>
        </VCard>
    </div>

    <!-- ═══ 10. ОТЧЁТЫ ═══ -->
    <div v-if="activeTab === 'reports'">
        <BeautyReports :salons="salons" :masters="masters"
                       @export-report="handleStaffExportReport"
                       @open-salon="(s) => { selectedSalon = s; activeTab = 'salons'; }"
                       @open-master="(m) => { selectedMaster = m; activeTab = 'masters'; }" />
    </div>

    <!-- ═══ 10.5. AI-КОНСТРУКТОР ОБРАЗА ═══ -->
    <div v-if="activeTab === 'tryon'">
        <BeautyTryOn :masters="masters" :services="services"
                     @book-master="showQuickBook = true"
                     @save-design="handleSaveDesign"
                     @share-result="(d) => { auditLog.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Клиент', action: 'Поделился AI-образом' }); }" />
    </div>

    <!-- ═══ 10.6. ПУБЛИЧНЫЕ СТРАНИЦЫ (ПАБЛИКИ) ═══ -->
    <div v-if="activeTab === 'pages'">
        <BeautyPublicPages :salons="salons" :masters="masters"
                           @create-page="handleCreatePage" @edit-page="handleEditPage"
                           @delete-page="handleDeletePage" @publish-post="handlePublishPost"
                           @schedule-post="handleSchedulePost" @export-report="handleExportPageReport" />
    </div>

    <!-- ═══ 10.7. СТАТИСТИКА СТРАНИЦ ═══ -->
    <div v-if="activeTab === 'page-stats'">
        <BeautyPageStats :pages="publicPagesData" :posts="publicPostsData"
                         @export-report="handleExportPageReport"
                         @open-page="() => { activeTab = 'pages'; }"
                         @open-post="handleOpenPost" />
    </div>

    <!-- ═══ 11. ЛОЯЛЬНОСТЬ ═══ -->
    <div v-if="activeTab === 'loyalty'">
        <BeautyLoyalty :masters="masters" :salons="salons" :clients="[]"
                       @open-client="openClientCard"
                       @award-bonus="handleLoyaltyAwardBonus"
                       @deduct-bonus="handleLoyaltyDeductBonus"
                       @export-report="handleLoyaltyExport"
                       @save-settings="handleLoyaltySaveSettings"
                       @create-tier="handleLoyaltyCreateTier"
                       @edit-tier="handleLoyaltyCreateTier"
                       @create-rule="handleLoyaltyCreateRule" />
    </div>

    <!-- ═══ 12. ОТЗЫВЫ ═══ -->
    <div v-if="activeTab === 'reviews'">
        <BeautyReviews :masters="masters" :salons="salons"
                       @open-client="openClientCard"
                       @open-master="openMasterCard"
                       @reply-review="handleReviewReply"
                       @delete-review="(r) => { auditLog.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: `Удалил отзыв #${r?.id || ''}` }); }"
                       @request-review="handleReviewRequest"
                       @export-report="handleReviewExport"
                       @flag-review="handleReviewFlag" />
    </div>

    <!-- ═══ 13. УВЕДОМЛЕНИЯ ═══ -->
    <div v-if="activeTab === 'notifications'">
        <BeautyNotifications :masters="masters" :salons="salons" :clients="[]"
                             @send-notification="handleNotifSend"
                             @save-template="handleNotifSaveTemplate"
                             @delete-template="handleNotifDeleteTemplate"
                             @toggle-reminder="handleNotifToggleReminder"
                             @export-report="handleNotifExport"
                             @send-bulk="handleNotifBulk"
                             @open-client="openClientCard" />
    </div>

    <!-- ═══ 14. НАСТРОЙКИ ═══ -->
    <div v-if="activeTab === 'config'" class="space-y-4">
        <h2 class="text-lg font-semibold" style="color:var(--t-text)">Настройки Beauty</h2>

        <div class="grid lg:grid-cols-2 gap-4">
            <!-- Онлайн-запись -->
            <VCard title="📅 Настройка онлайн-записи">
                <div class="space-y-4">
                    <label class="flex items-center justify-between">
                        <span style="color:var(--t-text)">Онлайн-запись включена</span>
                        <input type="checkbox" v-model="onlineBookingEnabled" class="w-5 h-5 accent-(--t-primary)">
                    </label>
                    <label class="flex items-center justify-between">
                        <span style="color:var(--t-text)">Автоподтверждение</span>
                        <input type="checkbox" v-model="autoConfirm" class="w-5 h-5 accent-(--t-primary)">
                    </label>
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color:var(--t-text)">Бронирование заранее (дней)</span>
                        <input type="number" v-model="bookingAdvanceDays" min="1" max="90" class="w-20 rounded-lg px-3 py-2 text-sm border" style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text)">
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color:var(--t-text)">Мин. часов для отмены</span>
                        <input type="number" v-model="minCancelHours" min="0" max="48" class="w-20 rounded-lg px-3 py-2 text-sm border" style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text)">
                    </div>
                </div>
            </VCard>

            <!-- Интеграции -->
            <VCard title="🔌 Интеграции">
                <div class="space-y-3">
                    <div v-for="ig in integrations" :key="ig.name"
                         class="flex items-center justify-between p-3 rounded-lg border"
                         style="background:var(--t-bg);border-color:var(--t-border)">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">{{ ig.icon }}</span>
                            <span class="text-sm font-medium" style="color:var(--t-text)">{{ ig.name }}</span>
                        </div>
                        <VBadge :color="ig.connected ? 'green' : 'gray'" size="sm">{{ ig.connected ? 'Подключено' : 'Не подключено' }}</VBadge>
                    </div>
                </div>
            </VCard>
        </div>

        <!-- Аудит лог -->
        <VCard title="📋 Журнал аудита">
            <div class="space-y-2">
                <div v-for="a in auditLog" :key="a.date + a.action"
                     class="flex items-center gap-3 text-sm p-2 rounded-lg" style="background:var(--t-bg)">
                    <span class="font-mono text-xs" style="color:var(--t-text-3)">{{ a.date }}</span>
                    <VBadge color="blue" size="sm">{{ a.user }}</VBadge>
                    <span style="color:var(--t-text-2)">{{ a.action }}</span>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ MODALS ═══ -->

    <!-- Quick Book Modal -->
    <VModal v-model="showQuickBook" title="📅 Быстрая запись клиента" size="lg">
        <div class="space-y-4">
            <VInput label="Имя клиента" placeholder="Введите имя или телефон..." />
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Филиал</label>
                    <select class="w-full rounded-lg px-3 py-2 border text-sm" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                        <option v-for="s in salons.filter(x=>x.status==='active')" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Мастер</label>
                    <select class="w-full rounded-lg px-3 py-2 border text-sm" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                        <option v-for="m in masters" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Услуга</label>
                <select class="w-full rounded-lg px-3 py-2 border text-sm" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                    <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }} — {{ fmt(s.price) }} ₽</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <VInput label="Дата" type="date" />
                <VInput label="Время" type="time" />
            </div>
            <VInput label="Комментарий" placeholder="Примечание к записи..." />
            <div class="flex justify-end gap-3">
                <VButton variant="outline" @click="showQuickBook = false">Отмена</VButton>
                <VButton @click="showQuickBook = false">✅ Записать</VButton>
            </div>
        </div>
    </VModal>

    <!-- Add Salon Modal -->
    <VModal v-model="showAddSalon" title="🏪 Новый салон">
        <div class="space-y-4">
            <VInput label="Название" v-model="newSalon.name" placeholder="BeautyLab ..." />
            <VInput label="Адрес" v-model="newSalon.address" placeholder="ул. ..." />
            <VInput label="Режим работы" v-model="newSalon.hours" />
            <VInput label="Зоны (через запятую)" v-model="newSalon.zones" placeholder="Барбер, Маникюр, Косметология" />
            <div class="flex justify-end gap-3">
                <VButton variant="outline" @click="showAddSalon = false">Отмена</VButton>
                <VButton @click="showAddSalon = false">✅ Создать</VButton>
            </div>
        </div>
    </VModal>

    <!-- Add Master Modal -->
    <VModal v-model="showAddMaster" title="👤 Новый мастер">
        <div class="space-y-4">
            <VInput label="ФИО" v-model="newMaster.name" placeholder="Имя Фамилия" />
            <div>
                <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Филиал</label>
                <select v-model="newMaster.salon" class="w-full rounded-lg px-3 py-2 border text-sm" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                    <option v-for="s in salons.filter(x=>x.status==='active')" :key="s.id" :value="s.name">{{ s.name }}</option>
                </select>
            </div>
            <VInput label="Специализация" v-model="newMaster.specialization" placeholder="Стрижки, Окрашивание..." />
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Уровень</label>
                    <select v-model="newMaster.level" class="w-full rounded-lg px-3 py-2 border text-sm" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                        <option>Джуниор</option><option>Мастер</option><option>Топ</option>
                    </select>
                </div>
                <VInput label="Комиссия (%)" v-model="newMaster.commission" type="text" />
            </div>
            <div class="flex justify-end gap-3">
                <VButton variant="outline" @click="showAddMaster = false">Отмена</VButton>
                <VButton @click="showAddMaster = false">✅ Добавить</VButton>
            </div>
        </div>
    </VModal>

    <!-- Packages Modal -->
    <VModal v-model="showPackages" title="📦 Пакеты и абонементы" size="lg">
        <div class="space-y-3">
            <div v-for="pkg in packages" :key="pkg.id"
                 class="p-4 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="font-bold" style="color:var(--t-text)">{{ pkg.name }}</h3>
                    <VBadge color="green" size="sm">-{{ pkg.discount }}%</VBadge>
                </div>
                <div class="text-sm" style="color:var(--t-text-2)">{{ pkg.services.join(', ') }}</div>
                <div class="font-bold mt-2" style="color:var(--t-primary)">{{ fmt(pkg.price) }} ₽</div>
            </div>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showPackages = false">Закрыть</VButton>
            <VButton>➕ Новый пакет</VButton>
        </template>
    </VModal>

    <!-- Add Promo Modal -->
    <VModal v-model="showAddPromo" title="🎁 Новая акция">
        <div class="space-y-4">
            <VInput label="Название" placeholder="Весенняя скидка..." />
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Тип</label>
                    <select class="w-full rounded-lg px-3 py-2 border text-sm" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                        <option value="discount">Скидка %</option>
                        <option value="referral">Реферальный бонус</option>
                        <option value="package">Абонемент</option>
                        <option value="bundle">Пакет</option>
                    </select>
                </div>
                <VInput label="Значение" type="number" placeholder="20" />
            </div>
            <VInput label="Действует до" type="date" />
            <VInput label="Услуги (через запятую)" placeholder="Все или конкретные..." />
            <div class="flex justify-end gap-3">
                <VButton variant="outline" @click="showAddPromo = false">Отмена</VButton>
                <VButton @click="showAddPromo = false">✅ Создать</VButton>
            </div>
        </div>
    </VModal>

    <!-- Booking Detail Modal -->
    <VModal v-model="showBookingDetail" :title="`Запись #${activeBooking?.id || ''}`" size="lg">
        <div v-if="activeBooking" class="space-y-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="block text-xs" style="color:var(--t-text-3)">Клиент</span>
                    <span class="font-bold" style="color:var(--t-text)">{{ activeBooking.client }}</span>
                </div>
                <div>
                    <span class="block text-xs" style="color:var(--t-text-3)">Телефон</span>
                    <span style="color:var(--t-text)">{{ activeBooking.phone }}</span>
                </div>
                <div>
                    <span class="block text-xs" style="color:var(--t-text-3)">Услуга</span>
                    <span style="color:var(--t-text)">{{ activeBooking.service }}</span>
                </div>
                <div>
                    <span class="block text-xs" style="color:var(--t-text-3)">Мастер</span>
                    <span style="color:var(--t-text)">{{ activeBooking.master }}</span>
                </div>
                <div>
                    <span class="block text-xs" style="color:var(--t-text-3)">Дата / время</span>
                    <span style="color:var(--t-text)">{{ activeBooking.date }}</span>
                </div>
                <div>
                    <span class="block text-xs" style="color:var(--t-text-3)">Статус</span>
                    <VBadge :color="bookingStatusColors[activeBooking.status]" size="sm">{{ bookingStatusLabels[activeBooking.status] }}</VBadge>
                </div>
                <div>
                    <span class="block text-xs" style="color:var(--t-text-3)">Итого</span>
                    <span class="font-bold" style="color:var(--t-primary)">{{ fmt(activeBooking.total) }} ₽</span>
                </div>
                <div>
                    <span class="block text-xs" style="color:var(--t-text-3)">Предоплата</span>
                    <span style="color:var(--t-text)">{{ activeBooking.prepaid ? fmt(activeBooking.prepaid) + ' ₽' : '—' }}</span>
                </div>
            </div>
            <!-- Timeline -->
            <div class="flex items-center gap-1 mt-4">
                <template v-for="(step, idx) in ['Создана', 'Подтверждена', 'Клиент пришёл', 'В процессе', 'Завершена']" :key="step">
                    <div class="flex flex-col items-center">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs"
                             :style="idx <= 1 ? 'background:var(--t-primary);color:#fff' : 'background:var(--t-bg);color:var(--t-text-3)'">{{ idx+1 }}</div>
                        <span class="text-[10px] mt-1" style="color:var(--t-text-3)">{{ step }}</span>
                    </div>
                    <div v-if="idx < 4" class="flex-1 h-0.5" :style="idx < 1 ? 'background:var(--t-primary)' : 'background:var(--t-border)'"></div>
                </template>
            </div>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showBookingDetail = false">Закрыть</VButton>
            <VButton v-if="activeBooking?.status === 'pending'" @click="showBookingDetail = false">✅ Подтвердить</VButton>
            <VButton v-if="activeBooking?.status === 'confirmed'" variant="outline" @click="showBookingDetail = false">🔄 Перенести</VButton>
            <VButton v-if="activeBooking?.status !== 'cancelled' && activeBooking?.status !== 'completed'" variant="outline" @click="showBookingDetail = false">❌ Отменить</VButton>
        </template>
    </VModal>

    <!-- Client Card Modal (full component) -->
    <VModal v-model="showClientCard" :title="activeClient?.name || 'Клиент'" size="xl">
        <BeautyClientCard v-if="activeClient" :client="activeClient" :masters="masters" :services="services"
            @close="showClientCard = false"
            @book="showQuickBook = true; showClientCard = false"
            @award-bonus="handleAwardClientBonus"
            @send-message="(d) => { activeTab = 'chat'; showClientCard = false; }"
            @open-calendar="activeTab = 'calendar'; showClientCard = false"
            @export="(d) => { auditLog.unshift({ date: new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }), user: 'Админ', action: 'Экспорт карты клиента' }); }" />
    </VModal>

</div>
</template>
