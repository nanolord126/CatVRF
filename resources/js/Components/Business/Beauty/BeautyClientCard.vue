<script setup>
/**
 * BeautyClientCard — полная карточка клиента Beauty B2B.
 * 9 вкладок: история, бонусы, предпочтения, аналитика,
 * медкарта, документы, коммуникации, заметки, инфо.
 * Шапка + Action Bar + Медкарта-алерт.
 */
import { ref, computed, reactive } from 'vue';
import VButton from '../../UI/VButton.vue';
import VCard   from '../../UI/VCard.vue';
import VBadge  from '../../UI/VBadge.vue';
import VModal  from '../../UI/VModal.vue';
import VInput  from '../../UI/VInput.vue';
import VStatCard from '../../UI/VStatCard.vue';

const props = defineProps({
    client:   { type: Object,  required: true },
    salon:    { type: Object,  default: () => ({}) },
    masters:  { type: Array,   default: () => [] },
    services: { type: Array,   default: () => [] },
});

const emit = defineEmits([
    'close', 'edit', 'book-service', 'send-message',
    'award-bonus', 'deduct-bonus', 'create-promo',
    'add-note', 'export', 'print', 'archive',
    'open-calendar', 'call', 'whatsapp',
]);

/* ─── Helpers ─── */
function fmtMoney(n) { return new Intl.NumberFormat('ru-RU').format(n ?? 0) + ' ₽'; }
function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n ?? 0); }
function plural(n, one, few, many) {
    const abs = Math.abs(n) % 100;
    if (abs >= 11 && abs <= 19) return many;
    const last = abs % 10;
    if (last === 1) return one;
    if (last >= 2 && last <= 4) return few;
    return many;
}

/* ═══════════════ TABS ═══════════════ */
const profileTabs = [
    { key: 'history',   label: '📋 История' },
    { key: 'bonuses',   label: '🎁 Бонусы' },
    { key: 'prefs',     label: '❤️ Предпочтения' },
    { key: 'analytics', label: '📊 Аналитика' },
    { key: 'medical',   label: '🏥 Медкарта' },
    { key: 'docs',      label: '📁 Документы' },
    { key: 'comms',     label: '💬 Коммуникации' },
    { key: 'notes',     label: '📝 Заметки' },
    { key: 'info',      label: '👤 Инфо' },
];
const activeTab = ref('history');

/* ═══════════════ CLIENT ENRICHED ═══════════════ */
const cl = computed(() => {
    const c = props.client;
    return {
        ...c,
        photo: c.photo || '',
        name: c.name || 'Клиент',
        phone: c.phone || '+7 900 000-00-00',
        whatsapp: c.whatsapp || c.phone || '+79000000000',
        telegram: c.telegram || '',
        email: c.email || '',
        birthday: c.birthday || '15.05.1990',
        age: c.age || 35,
        zodiac: c.zodiac || '♉ Телец',
        gender: c.gender || 'Жен.',
        language: c.language || 'Русский',
        source: c.source || 'Самостоятельно',
        segment: c.segment || 'Лояльный',
        loyaltyLevel: c.loyaltyLevel || 'Gold',
        status: c.status || 'loyal',
        createdAt: c.createdAt || '10.01.2024',
        daysInBase: c.daysInBase || 820,
        totalSpent: c.totalSpent ?? 248500,
        visits: c.visits ?? 38,
        avgCheck: c.avgCheck ?? 6540,
        bonusBalance: c.bonusBalance ?? 3200,
        ltv: c.ltv ?? 312000,
        ltvPredicted: c.ltvPredicted ?? 420000,
        tags: c.tags || ['VIP', 'Блонд', 'AirTouch', 'Свадебные укладки'],
        allergies: c.allergies || 'Аммиачные красители',
        skinType: c.skinType || 'Нормальная',
        hairType: c.hairType || 'Тонкие, окрашенные',
        nailType: c.nailType || 'Нормальные',
        medicalNotes: c.medicalNotes || 'Без особенностей',
        preferredBrands: c.preferredBrands || ['L\'Oréal Professionnel', 'Olaplex', 'Kerastase'],
    };
});

/* ─── Status mappings ─── */
const statusColors = { vip: 'purple', loyal: 'green', new: 'blue', sleeping: 'yellow', problem: 'red', blacklist: 'gray' };
const statusLabels = { vip: '👑 VIP', loyal: '💚 Лояльный', new: '🆕 Новичок', sleeping: '😴 Спящий', problem: '⚠️ Проблемный', blacklist: '🚫 Чёрный список' };
const loyaltyColors = { Bronze: 'yellow', Silver: 'gray', Gold: 'yellow', Platinum: 'purple', Diamond: 'blue' };
const loyaltyIcons  = { Bronze: '🥉', Silver: '🥈', Gold: '🥇', Platinum: '💎', Diamond: '💠' };

/* ═══════════════ VISIT HISTORY ═══════════════ */
const historyFilter = reactive({ period: 'all', search: '', master: '' });
const historyExpanded = ref(null);

const visitHistory = computed(() => [
    { id: 1, date: '02.04.2026 14:00', month: 'Апрель 2026', master: 'Анна Соколова', salon: 'BeautyLab Центр',
      services: [{ name: 'Окрашивание AirTouch', price: 8500 }, { name: 'Уход Olaplex', price: 1500 }],
      total: 10000, paid: true, payMethod: 'Карта', bonusUsed: 500, bonusEarned: 200,
      rating: 5, reviewText: 'Идеальное окрашивание, как всегда! Анна — лучшая!',
      photos: [{ type: 'before_after', label: 'AirTouch блонд' }],
      masterComment: 'Использовала 9% оксид + тонировка. Следующий визит через 6 недель.',
      status: 'completed' },
    { id: 2, date: '15.03.2026 11:30', month: 'Март 2026', master: 'Анна Соколова', salon: 'BeautyLab Центр',
      services: [{ name: 'Стрижка женская', price: 2500 }, { name: 'Укладка', price: 1800 }],
      total: 4300, paid: true, payMethod: 'Карта + бонусы', bonusUsed: 300, bonusEarned: 86,
      rating: 5, reviewText: 'Стрижка супер!', photos: [], masterComment: 'Каре удлинённое, филировка кончиков.',
      status: 'completed' },
    { id: 3, date: '20.02.2026 15:00', month: 'Февраль 2026', master: 'Игорь Волков', salon: 'BeautyLab Центр',
      services: [{ name: 'Окрашивание балаяж', price: 4500 }, { name: 'Тонирование', price: 1000 }, { name: 'Уход Olaplex', price: 1500 }],
      total: 7000, paid: true, payMethod: 'Карта', bonusUsed: 0, bonusEarned: 140,
      rating: 4, reviewText: 'Хорошо, но немного длиннее ожидала.', photos: [{ type: 'before_after', label: 'Балаяж' }],
      masterComment: 'Клиентка просила более тёплый тон. Учтём в следующий раз.', status: 'completed' },
    { id: 4, date: '05.02.2026 10:00', month: 'Февраль 2026', master: 'Анна Соколова', salon: 'BeautyLab Арбат',
      services: [{ name: 'Кератиновое выпрямление', price: 6000 }],
      total: 6000, paid: true, payMethod: 'Карта', bonusUsed: 0, bonusEarned: 120,
      rating: 5, reviewText: 'Волосы как шёлк!', photos: [], masterComment: 'Состав Cadiveu. Эффект 3-4 месяца.',
      status: 'completed' },
    { id: 5, date: '10.01.2026 16:00', month: 'Январь 2026', master: 'Анна Соколова', salon: 'BeautyLab Центр',
      services: [{ name: 'Стрижка женская', price: 2500 }, { name: 'Окрашивание AirTouch', price: 8500 }, { name: 'Укладка', price: 1800 }],
      total: 12800, paid: true, payMethod: 'Карта', bonusUsed: 1000, bonusEarned: 236,
      rating: 5, reviewText: '', photos: [{ type: 'before_after', label: 'AirTouch зимний' }],
      masterComment: 'Новогодний образ. Холодный блонд + укладка Hollywood waves.', status: 'completed' },
    { id: 6, date: '18.12.2025 13:00', month: 'Декабрь 2025', master: 'Светлана Романова', salon: 'BeautyLab Центр',
      services: [{ name: 'Маникюр', price: 2000 }, { name: 'Педикюр', price: 2500 }],
      total: 4500, paid: true, payMethod: 'Карта', bonusUsed: 0, bonusEarned: 90,
      rating: 4, reviewText: 'Нормально.', photos: [], masterComment: 'Гель-лак Luxio, оттенок 287.',
      status: 'completed' },
    { id: 7, date: '25.11.2025 10:30', month: 'Ноябрь 2025', master: 'Анна Соколова', salon: 'BeautyLab Центр',
      services: [{ name: 'Тонирование', price: 1000 }, { name: 'Укладка', price: 1800 }],
      total: 2800, paid: true, payMethod: 'Бонусы', bonusUsed: 2800, bonusEarned: 0,
      rating: 5, reviewText: 'Быстро и красиво!', photos: [], masterComment: '', status: 'completed' },
    { id: 8, date: '01.04.2026 11:00', month: 'Апрель 2026', master: 'Анна Соколова', salon: 'BeautyLab Центр',
      services: [{ name: 'Стрижка женская', price: 2500 }],
      total: 2500, paid: false, payMethod: '—', bonusUsed: 0, bonusEarned: 0,
      rating: null, reviewText: '', photos: [], masterComment: '', status: 'cancelled' },
]);

const visitStatusColors = { completed: 'green', cancelled: 'red', no_show: 'gray', pending: 'yellow', in_progress: 'blue' };
const visitStatusLabels = { completed: 'Выполнено', cancelled: 'Отменено', no_show: 'Неявка', pending: 'Ожидает', in_progress: 'В процессе' };

const groupedHistory = computed(() => {
    const groups = {};
    for (const v of visitHistory.value) {
        const mFilter = historyFilter.master;
        const sFilter = historyFilter.search.toLowerCase();
        if (mFilter && !v.master.toLowerCase().includes(mFilter.toLowerCase())) continue;
        if (sFilter && !v.services.some(s => s.name.toLowerCase().includes(sFilter)) && !v.master.toLowerCase().includes(sFilter)) continue;
        if (!groups[v.month]) groups[v.month] = [];
        groups[v.month].push(v);
    }
    return groups;
});

/* ═══════════════ BONUSES & LOYALTY ═══════════════ */
const loyaltyLevels = [
    { name: 'Bronze', min: 0,      max: 30000,  cashback: 3 },
    { name: 'Silver', min: 30000,  max: 100000, cashback: 5 },
    { name: 'Gold',   min: 100000, max: 300000, cashback: 7 },
    { name: 'Platinum', min: 300000, max: 500000, cashback: 10 },
    { name: 'Diamond', min: 500000, max: Infinity, cashback: 15 },
];

const currentLoyaltyLevel = computed(() => loyaltyLevels.find(l => cl.value.totalSpent >= l.min && cl.value.totalSpent < l.max) || loyaltyLevels[0]);
const nextLoyaltyLevel    = computed(() => loyaltyLevels[loyaltyLevels.indexOf(currentLoyaltyLevel.value) + 1] || null);
const loyaltyProgress     = computed(() => {
    const curr = currentLoyaltyLevel.value;
    if (curr.max === Infinity) return 100;
    return Math.round(((cl.value.totalSpent - curr.min) / (curr.max - curr.min)) * 100);
});
const untilNextLevel = computed(() => nextLoyaltyLevel.value ? nextLoyaltyLevel.value.min - cl.value.totalSpent : 0);

const bonusHistory = ref([
    { id: 1, date: '02.04.2026', type: 'earn',   amount: 200,  reason: 'Кэшбэк 7% за визит', balance: 3200 },
    { id: 2, date: '15.03.2026', type: 'earn',   amount: 86,   reason: 'Кэшбэк 7% за визит', balance: 3000 },
    { id: 3, date: '15.03.2026', type: 'spend',  amount: 300,  reason: 'Списание при оплате', balance: 2914 },
    { id: 4, date: '20.02.2026', type: 'earn',   amount: 140,  reason: 'Кэшбэк 7% за визит', balance: 3214 },
    { id: 5, date: '05.02.2026', type: 'earn',   amount: 120,  reason: 'Кэшбэк 7% за визит', balance: 3074 },
    { id: 6, date: '10.01.2026', type: 'earn',   amount: 236,  reason: 'Кэшбэк 7% за визит', balance: 2954 },
    { id: 7, date: '10.01.2026', type: 'spend',  amount: 1000, reason: 'Списание при оплате', balance: 2718 },
    { id: 8, date: '25.11.2025', type: 'spend',  amount: 2800, reason: 'Полная оплата бонусами', balance: 3718 },
    { id: 9, date: '01.01.2026', type: 'promo',  amount: 500,  reason: '🎄 Новогодний подарок', balance: 6518 },
    { id: 10, date: '15.05.2025', type: 'promo', amount: 1000, reason: '🎂 День рождения', balance: 6018 },
]);

const monthlySpending = ref([
    { month: 'Окт', amount: 8200 },
    { month: 'Ноя', amount: 2800 },
    { month: 'Дек', amount: 4500 },
    { month: 'Янв', amount: 12800 },
    { month: 'Фев', amount: 13000 },
    { month: 'Мар', amount: 4300 },
    { month: 'Апр', amount: 10000 },
]);
const maxMonthly = computed(() => Math.max(...monthlySpending.value.map(m => m.amount), 1));

/* ═══════════════ PREFERENCES ═══════════════ */
const topServicesPrefs = computed(() => [
    { name: 'Окрашивание AirTouch', count: 12, pct: 32, icon: '🎨' },
    { name: 'Стрижка женская',      count: 9,  pct: 24, icon: '✂️' },
    { name: 'Укладка',              count: 8,  pct: 21, icon: '💇' },
    { name: 'Уход Olaplex',         count: 6,  pct: 16, icon: '💎' },
    { name: 'Тонирование',          count: 3,  pct: 8,  icon: '✨' },
]);

const preferredMasters = computed(() => [
    { name: 'Анна Соколова',    visits: 28, pct: 74, rating: 4.95, isFavorite: true },
    { name: 'Игорь Волков',     visits: 6,  pct: 16, rating: 4.3 },
    { name: 'Светлана Романова', visits: 4, pct: 10, rating: 4.2 },
]);

const preferredTimes = computed(() => [
    { slot: '10:00–12:00', count: 14, pct: 37 },
    { slot: '14:00–16:00', count: 12, pct: 32 },
    { slot: '16:00–18:00', count: 8,  pct: 21 },
    { slot: '12:00–14:00', count: 4,  pct: 10 },
]);

const preferredSalons = computed(() => [
    { name: 'BeautyLab Центр', visits: 32, pct: 84 },
    { name: 'BeautyLab Арбат', visits: 6,  pct: 16 },
]);

const weekdayFrequency = computed(() => [
    { day: 'Пн', count: 4 }, { day: 'Вт', count: 7 }, { day: 'Ср', count: 10 },
    { day: 'Чт', count: 5 }, { day: 'Пт', count: 8 }, { day: 'Сб', count: 3 }, { day: 'Вс', count: 1 },
]);
const maxWeekday = computed(() => Math.max(...weekdayFrequency.value.map(d => d.count), 1));

/* ═══════════════ ANALYTICS ═══════════════ */
const ltvMonthly = ref([
    { month: 'Окт', ltv: 180000, visits: 3 },
    { month: 'Ноя', ltv: 196000, visits: 2 },
    { month: 'Дек', ltv: 214000, visits: 2 },
    { month: 'Янв', ltv: 232000, visits: 3 },
    { month: 'Фев', ltv: 248500, visits: 3 },
    { month: 'Мар', ltv: 262500, visits: 2 },
    { month: 'Апр', ltv: 272500, visits: 2 },
]);
const maxLtv = computed(() => Math.max(...ltvMonthly.value.map(m => m.ltv), 1));

const aiPrediction = reactive({
    nextVisitDate: '18.04.2026',
    nextVisitDays: 16,
    predictedService: 'Окрашивание AirTouch + Уход Olaplex',
    churnRisk: 8,
    churnReasons: [],
    avgInterval: 22,
    recommendations: [
        '🎯 Предложить новый оттенок — тренд весна 2026',
        '🎁 Начислить 500 бонусов за реферала',
        '📅 Напомнить о визите за 5 дней',
    ],
});

const salonComparison = reactive({
    avgSpent: 6540,
    salonAvgSpent: 4200,
    avgFrequency: 22,
    salonAvgFrequency: 35,
    avgRating: 4.75,
    salonAvgRating: 4.5,
    loyaltyLevel: 'Gold',
    salonTopPercent: 8,
});

/* ═══════════════ MEDICAL CARD ═══════════════ */
const medicalEntries = ref([
    { id: 1, date: '10.01.2024', type: 'allergy', title: 'Аллергия на аммиачные красители',
      description: 'Покраснение кожи головы при использовании аммиачных составов. Использовать только безаммиачные красители.',
      severity: 'high', author: 'Анна Соколова' },
    { id: 2, date: '05.02.2026', type: 'procedure', title: 'Кератиновое выпрямление — Cadiveu',
      description: 'Процедура прошла штатно. Без побочных реакций. Эффект 3-4 мес. Повторить не ранее июня 2026.',
      severity: 'info', author: 'Анна Соколова' },
    { id: 3, date: '20.02.2026', type: 'preference', title: 'Предпочтение по брендам',
      description: 'Клиентка предпочитает L\'Oréal Professionnel и Olaplex. Kerastase для домашнего ухода.',
      severity: 'info', author: 'Игорь Волков' },
    { id: 4, date: '02.04.2026', type: 'note', title: 'Заметка по окрашиванию',
      description: 'Последний раз использовали 9% оксид. В следующий раз попробовать 6% для более мягкого результата.',
      severity: 'info', author: 'Анна Соколова' },
]);
const medSeverityColors = { high: 'red', medium: 'yellow', info: 'blue' };
const medTypeIcons = { allergy: '⚠️', procedure: '💉', preference: '❤️', note: '📝', contraindication: '🚫' };

/* ═══════════════ DOCUMENTS & PHOTOS ═══════════════ */
const documents = ref([
    { id: 1, name: 'Согласие на обработку данных', type: 'consent', date: '10.01.2024', file: 'consent.pdf' },
    { id: 2, name: 'Информированное согласие (инъекции)', type: 'medical', date: '—', file: null },
    { id: 3, name: 'Фото до/после — AirTouch 02.04.2026', type: 'photo', date: '02.04.2026', file: 'airtouch_apr.jpg' },
    { id: 4, name: 'Фото до/после — Балаяж 20.02.2026', type: 'photo', date: '20.02.2026', file: 'balayage_feb.jpg' },
    { id: 5, name: 'Фото до/после — AirTouch 10.01.2026', type: 'photo', date: '10.01.2026', file: 'airtouch_jan.jpg' },
]);
const docTypeIcons = { consent: '📄', medical: '🏥', photo: '📸', contract: '📋', other: '📎' };

/* ═══════════════ COMMUNICATIONS ═══════════════ */
const communications = ref([
    { id: 1, date: '05.04.2026 10:15', channel: 'push', direction: 'out', title: 'Напоминание о визите',
      preview: 'Добрый день! Напоминаем о записи 08.04 в 14:00', status: 'delivered', readAt: '05.04.2026 10:22' },
    { id: 2, date: '01.04.2026 09:00', channel: 'sms', direction: 'out', title: 'Акция ко дню рождения',
      preview: 'Скоро ваш день рождения! Дарим 1000 бонусов 🎂', status: 'delivered', readAt: null },
    { id: 3, date: '28.03.2026 14:30', channel: 'whatsapp', direction: 'in', title: 'Запрос на перезапись',
      preview: 'Здравствуйте! Можно перенести на следующую неделю?', status: 'read', readAt: '28.03.2026 14:32' },
    { id: 4, date: '15.03.2026 18:00', channel: 'push', direction: 'out', title: 'Оценка визита',
      preview: 'Спасибо за визит! Оцените качество обслуживания ⭐', status: 'delivered', readAt: '15.03.2026 18:05' },
    { id: 5, date: '10.03.2026 11:00', channel: 'email', direction: 'out', title: 'Персональная подборка',
      preview: 'Новинки ухода для окрашенных волос — специально для вас', status: 'delivered', readAt: '10.03.2026 15:40' },
    { id: 6, date: '01.03.2026 09:00', channel: 'sms', direction: 'out', title: 'Напоминание',
      preview: 'Запись 02.03 в 15:00 к Анне Соколовой', status: 'delivered', readAt: null },
]);
const channelIcons = { push: '🔔', sms: '📱', whatsapp: '💬', telegram: '✈️', email: '📧', call: '📞' };
const channelLabels = { push: 'Push', sms: 'SMS', whatsapp: 'WhatsApp', telegram: 'Telegram', email: 'Email', call: 'Звонок' };

/* ═══════════════ NOTES ═══════════════ */
const notes = ref([
    { id: 1, date: '02.04.2026', author: 'Анна Соколова', text: 'Клиентка хочет попробовать розовый оттенок в следующий раз. Обсудили варианты — пастельный розовый на обесцвеченную базу.', pinned: true },
    { id: 2, date: '15.03.2026', author: 'Администратор Мария', text: 'Предпочитает утренние слоты, приходит точно в срок. Всегда берёт кофе с молоком.', pinned: false },
    { id: 3, date: '20.02.2026', author: 'Игорь Волков', text: 'При окрашивании — обязательно использовать безаммиачный состав! Аллергия на аммиак подтверждена.', pinned: true },
    { id: 4, date: '10.01.2026', author: 'Анна Соколова', text: 'Порекомендовала домашний уход: Kerastase Blond Absolu + Olaplex №3 раз в неделю.', pinned: false },
    { id: 5, date: '18.12.2025', author: 'Администратор Мария', text: 'Спрашивала про подарочные сертификаты для подруг. Предложить к НГ.', pinned: false },
]);
const newNoteText = ref('');

/* ═══════════════ MODALS ═══════════════ */
const showBonusModal   = ref(false);
const showDeductModal  = ref(false);
const showMessageModal = ref(false);
const showPromoModal   = ref(false);
const showPhotoViewer  = ref(false);
const bonusAmount = ref('');
const bonusReason = ref('');
const deductAmount = ref('');
const deductReason = ref('');
const messageChannel = ref('whatsapp');
const messageText = ref('');
const promoType = ref('discount');
const promoValue = ref('');

function awardBonus() {
    if (bonusAmount.value && bonusReason.value) {
        emit('award-bonus', { client: props.client, amount: +bonusAmount.value, reason: bonusReason.value });
        bonusAmount.value = ''; bonusReason.value = ''; showBonusModal.value = false;
    }
}
function deductBonus() {
    if (deductAmount.value && deductReason.value) {
        emit('deduct-bonus', { client: props.client, amount: +deductAmount.value, reason: deductReason.value });
        deductAmount.value = ''; deductReason.value = ''; showDeductModal.value = false;
    }
}
function sendMessage() {
    if (messageText.value.trim()) {
        emit('send-message', { client: props.client, channel: messageChannel.value, text: messageText.value });
        messageText.value = ''; showMessageModal.value = false;
    }
}
function createPromo() {
    emit('create-promo', { client: props.client, type: promoType.value, value: promoValue.value });
    promoValue.value = ''; showPromoModal.value = false;
}
function addNote() {
    if (newNoteText.value.trim()) {
        notes.value.unshift({ id: Date.now(), date: new Date().toLocaleDateString('ru-RU'), author: 'Вы', text: newNoteText.value, pinned: false });
        emit('add-note', { client: props.client, text: newNoteText.value });
        newNoteText.value = '';
    }
}
function togglePin(note) { note.pinned = !note.pinned; }
function repeatVisit(visit) { emit('book-service', { client: props.client, services: visit.services, master: visit.master }); }
function rescheduleVisit(visit) { emit('open-calendar', { client: props.client, visit }); }
</script>

<template>
<div class="space-y-4">

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- 1. HEADER                                                  -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div class="rounded-2xl border overflow-hidden" style="background:var(--t-surface);border-color:var(--t-border)">
        <!-- Cover gradient -->
        <div class="h-24 relative" style="background:linear-gradient(135deg, var(--t-gradient-from), var(--t-gradient-via), var(--t-gradient-to))">
            <button class="absolute top-3 right-3 p-2 rounded-xl text-xs font-medium"
                    style="background:rgba(0,0,0,.3);color:#fff" @click="$emit('close')">✕ Закрыть</button>
        </div>

        <div class="px-6 pb-5 -mt-10 relative">
            <div class="flex flex-col md:flex-row items-start md:items-end gap-4">
                <!-- Photo (круглое, кликабельное) -->
                <div class="relative group cursor-pointer" @click="$emit('edit', cl)" title="Заменить фото">
                    <div class="w-24 h-24 rounded-full border-4 flex items-center justify-center text-3xl font-bold shadow-lg overflow-hidden"
                         style="border-color:var(--t-surface);background:var(--t-primary-dim);color:var(--t-primary)">
                        <img v-if="cl.photo" :src="cl.photo" class="w-full h-full object-cover" alt="" />
                        <span v-else>{{ cl.name.charAt(0) }}</span>
                    </div>
                    <div class="absolute inset-0 rounded-full bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                        <span class="text-white text-lg">📷</span>
                    </div>
                </div>

                <!-- Name, contacts, status -->
                <div class="flex-1 min-w-0 pt-2 md:pt-0">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-xl font-bold cursor-pointer hover:underline" style="color:var(--t-text)"
                            @click="$emit('edit', cl)" title="Редактировать">{{ cl.name }}</h2>
                        <VBadge :color="statusColors[cl.status]" size="sm">{{ statusLabels[cl.status] || cl.status }}</VBadge>
                        <VBadge :color="loyaltyColors[cl.loyaltyLevel]" size="sm">
                            {{ loyaltyIcons[cl.loyaltyLevel] }} {{ cl.loyaltyLevel }}
                        </VBadge>
                    </div>

                    <!-- Contacts row -->
                    <div class="flex items-center gap-4 mt-1.5 flex-wrap text-sm">
                        <a :href="'tel:' + cl.phone" class="hover:underline" style="color:var(--t-text)">
                            📱 {{ cl.phone }}
                        </a>
                        <button class="hover:opacity-80 transition" title="WhatsApp" @click="$emit('whatsapp', cl)">💬 WA</button>
                        <a v-if="cl.email" :href="'mailto:' + cl.email" class="hover:underline" style="color:var(--t-primary)">
                            📧 {{ cl.email }}
                        </a>
                    </div>

                    <!-- Meta row -->
                    <div class="flex items-center gap-4 mt-1 flex-wrap text-xs" style="color:var(--t-text-3)">
                        <span>📅 В базе с {{ cl.createdAt }} ({{ cl.daysInBase }} {{ plural(cl.daysInBase, 'день', 'дня', 'дней') }})</span>
                        <span>📋 Посл. визит: {{ visitHistory[0]?.date?.split(' ')[0] || '—' }}</span>
                        <span style="color:var(--t-primary)">🔮 След. визит: {{ aiPrediction.nextVisitDate }}</span>
                        <span>💬 Предпочтения: WhatsApp</span>
                    </div>
                </div>

                <!-- Big stats -->
                <div class="flex gap-4 items-center shrink-0">
                    <div class="text-center">
                        <div class="text-lg font-black" style="color:var(--t-primary)">{{ cl.visits }}</div>
                        <div class="text-[10px] uppercase tracking-wide" style="color:var(--t-text-3)">Визитов</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-black" style="color:var(--t-primary)">{{ fmtMoney(cl.totalSpent) }}</div>
                        <div class="text-[10px] uppercase tracking-wide" style="color:var(--t-text-3)">Потрачено</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-black" style="color:var(--t-text)">{{ fmtMoney(cl.avgCheck) }}</div>
                        <div class="text-[10px] uppercase tracking-wide" style="color:var(--t-text-3)">Ср. чек</div>
                    </div>
                    <div class="text-center cursor-pointer" @click="showBonusModal = true" title="Начислить бонусы">
                        <div class="text-lg font-black" style="color:var(--t-accent, var(--t-primary))">{{ fmt(cl.bonusBalance) }} ₽</div>
                        <div class="text-[10px] uppercase tracking-wide" style="color:var(--t-text-3)">Бонусы ➕</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ ALLERGY ALERT (всегда на виду) ═══ -->
    <div v-if="cl.allergies && cl.allergies !== 'Нет'" class="p-3 rounded-xl border-2 border-red-500/60 flex items-center gap-3"
         style="background:rgba(239,68,68,.08)">
        <span class="text-2xl">⚠️</span>
        <div>
            <div class="text-sm font-bold text-red-500">Аллергии и противопоказания</div>
            <div class="text-sm text-red-400">{{ cl.allergies }}</div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- 2. ACTION BAR                                              -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div class="flex flex-wrap gap-2">
        <VButton size="sm" @click="$emit('book-service', { client: cl })">📅 Записать на услугу</VButton>
        <VButton size="sm" variant="outline" @click="showMessageModal = true">💬 Отправить сообщение</VButton>
        <VButton size="sm" variant="outline" @click="showBonusModal = true">🎁 Начислить бонусы</VButton>
        <VButton size="sm" variant="outline" @click="showPromoModal = true">🏷️ Персональная акция</VButton>
        <VButton size="sm" variant="outline" @click="activeTab = 'notes'">📝 Заметка</VButton>
        <VButton size="sm" variant="outline" @click="$emit('export', cl)">📤 Экспорт</VButton>
        <VButton size="sm" variant="outline" @click="$emit('print', cl)">🖨️ Печать</VButton>
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

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- 3. HISTORY TAB (Timeline)                                  -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'history'" class="space-y-4">
        <!-- Filters -->
        <div class="flex flex-wrap gap-2 items-center">
            <VInput v-model="historyFilter.search" placeholder="🔍 Услуга или мастер..." class="w-56" />
            <select v-model="historyFilter.master" class="px-3 py-1.5 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option value="">Все мастера</option>
                <option v-for="m in preferredMasters" :key="m.name" :value="m.name">{{ m.name }}</option>
            </select>
        </div>

        <!-- Quick stats row -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <VStatCard title="Всего визитов" :value="String(cl.visits)" icon="📋" />
            <VStatCard title="Средний чек" :value="fmtMoney(cl.avgCheck)" icon="🧾" />
            <VStatCard title="Последний визит" :value="visitHistory[0]?.date?.split(' ')[0] || '—'" icon="📅" />
            <VStatCard title="Средний интервал" :value="aiPrediction.avgInterval + ' дн.'" icon="⏱️" />
        </div>

        <!-- Timeline grouped by month -->
        <div v-for="(visits, monthLabel) in groupedHistory" :key="monthLabel" class="space-y-2">
            <h3 class="text-sm font-bold sticky top-0 py-1 z-10" style="color:var(--t-text-2);background:var(--t-bg)">
                📅 {{ monthLabel }}
            </h3>

            <div v-for="v in visits" :key="v.id"
                 class="rounded-xl border transition-all"
                 style="background:var(--t-surface);border-color:var(--t-border)">

                <!-- Visit row (collapsed) -->
                <div class="p-4 cursor-pointer flex items-start gap-4" @click="historyExpanded = historyExpanded === v.id ? null : v.id">
                    <!-- Date column -->
                    <div class="shrink-0 text-center w-16">
                        <div class="text-lg font-black leading-tight" style="color:var(--t-primary)">
                            {{ v.date.split(' ')[0].split('.')[0] }}
                        </div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">
                            {{ v.date.split(' ')[0].split('.').slice(1).join('.') }}
                        </div>
                        <div class="text-xs mt-0.5" style="color:var(--t-text-2)">{{ v.date.split(' ')[1] }}</div>
                    </div>

                    <!-- Visit info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-medium" style="color:var(--t-text)">
                                {{ v.services.map(s => s.name).join(' + ') }}
                            </span>
                            <VBadge :color="visitStatusColors[v.status]" size="sm">{{ visitStatusLabels[v.status] }}</VBadge>
                        </div>
                        <div class="text-xs mt-1" style="color:var(--t-text-3)">
                            👩‍🎨 {{ v.master }} · 📍 {{ v.salon }}
                        </div>
                        <!-- Rating -->
                        <div v-if="v.rating" class="flex items-center gap-1 mt-1">
                            <span v-for="s in 5" :key="s" class="text-xs">{{ s <= v.rating ? '⭐' : '☆' }}</span>
                            <span v-if="v.reviewText" class="text-xs ml-1 truncate max-w-[200px]" style="color:var(--t-text-3)">
                                «{{ v.reviewText }}»
                            </span>
                        </div>
                    </div>

                    <!-- Price -->
                    <div class="shrink-0 text-right">
                        <div class="text-sm font-bold" style="color:var(--t-primary)">{{ fmtMoney(v.total) }}</div>
                        <div v-if="v.bonusUsed > 0" class="text-[10px]" style="color:var(--t-accent, #f59e0b)">
                            -{{ fmt(v.bonusUsed) }} бонусов
                        </div>
                        <div v-if="v.bonusEarned > 0" class="text-[10px] text-green-400">
                            +{{ fmt(v.bonusEarned) }} бонусов
                        </div>
                    </div>

                    <!-- Expand icon -->
                    <span class="text-xs transition-transform" :class="historyExpanded === v.id ? 'rotate-180' : ''"
                          style="color:var(--t-text-3)">▼</span>
                </div>

                <!-- Expanded details -->
                <div v-if="historyExpanded === v.id" class="px-4 pb-4 space-y-3 border-t"
                     style="border-color:var(--t-border)">
                    <!-- Services breakdown -->
                    <div class="space-y-1">
                        <div class="text-xs font-bold" style="color:var(--t-text-2)">Детализация услуг:</div>
                        <div v-for="(s, si) in v.services" :key="si" class="flex justify-between text-sm px-2 py-1 rounded"
                             style="background:var(--t-bg)">
                            <span style="color:var(--t-text)">{{ s.name }}</span>
                            <span class="font-medium" style="color:var(--t-primary)">{{ fmtMoney(s.price) }}</span>
                        </div>
                    </div>

                    <!-- Payment info -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                        <div class="p-2 rounded-lg" style="background:var(--t-bg)">
                            <div style="color:var(--t-text-3)">Оплата</div>
                            <div class="font-medium" style="color:var(--t-text)">{{ v.payMethod }}</div>
                        </div>
                        <div class="p-2 rounded-lg" style="background:var(--t-bg)">
                            <div style="color:var(--t-text-3)">Статус</div>
                            <div class="font-medium" :style="`color:${v.paid ? '#22c55e' : '#ef4444'}`">{{ v.paid ? 'Оплачено' : 'Не оплачено' }}</div>
                        </div>
                        <div class="p-2 rounded-lg" style="background:var(--t-bg)">
                            <div style="color:var(--t-text-3)">Бонусы списано</div>
                            <div class="font-medium" style="color:var(--t-text)">{{ fmt(v.bonusUsed) }} ₽</div>
                        </div>
                        <div class="p-2 rounded-lg" style="background:var(--t-bg)">
                            <div style="color:var(--t-text-3)">Бонусы начислено</div>
                            <div class="font-medium text-green-400">+{{ fmt(v.bonusEarned) }} ₽</div>
                        </div>
                    </div>

                    <!-- Review -->
                    <div v-if="v.reviewText" class="p-3 rounded-lg" style="background:var(--t-bg)">
                        <div class="text-xs font-bold mb-1" style="color:var(--t-text-2)">💬 Отзыв клиента:</div>
                        <div class="text-sm" style="color:var(--t-text)">{{ v.reviewText }}</div>
                    </div>

                    <!-- Master comment -->
                    <div v-if="v.masterComment" class="p-3 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <div class="text-xs font-bold mb-1" style="color:var(--t-text-2)">👩‍🎨 Комментарий мастера:</div>
                        <div class="text-sm" style="color:var(--t-text)">{{ v.masterComment }}</div>
                    </div>

                    <!-- Photos -->
                    <div v-if="v.photos.length > 0" class="flex gap-2">
                        <div v-for="(p, pi) in v.photos" :key="pi"
                             class="w-20 h-20 rounded-lg flex items-center justify-center text-xs cursor-pointer hover:opacity-80"
                             style="background:var(--t-primary-dim);color:var(--t-primary)">
                            📸 {{ p.label }}
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 pt-2">
                        <VButton size="sm" variant="outline" @click.stop="repeatVisit(v)">🔄 Повторить</VButton>
                        <VButton size="sm" variant="outline" @click.stop="rescheduleVisit(v)">📅 Перенести</VButton>
                        <VButton size="sm" variant="outline" @click.stop="showBonusModal = true">🎁 Начислить бонусы</VButton>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="Object.keys(groupedHistory).length === 0"
             class="text-center py-12 text-sm" style="color:var(--t-text-3)">
            Нет записей по выбранным фильтрам
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- 4. BONUSES & LOYALTY TAB                                   -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div v-else-if="activeTab === 'bonuses'" class="space-y-4">
        <!-- Balance big card -->
        <div class="p-5 rounded-2xl border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
            <div class="text-3xl font-black" style="color:var(--t-primary)">{{ fmt(cl.bonusBalance) }} ₽</div>
            <div class="text-sm mt-1" style="color:var(--t-text-3)">Текущий баланс бонусов</div>
            <div class="flex justify-center gap-3 mt-3">
                <VButton size="sm" @click="showBonusModal = true">➕ Начислить</VButton>
                <VButton size="sm" variant="outline" @click="showDeductModal = true">➖ Списать</VButton>
            </div>
        </div>

        <!-- Loyalty level -->
        <VCard title="🏆 Уровень лояльности">
            <div class="flex items-center gap-4 mb-3">
                <span class="text-4xl">{{ loyaltyIcons[currentLoyaltyLevel.name] }}</span>
                <div>
                    <div class="text-lg font-bold" style="color:var(--t-text)">{{ currentLoyaltyLevel.name }}</div>
                    <div class="text-sm" style="color:var(--t-text-2)">Кэшбэк {{ currentLoyaltyLevel.cashback }}%</div>
                </div>
            </div>
            <!-- Progress bar -->
            <div class="space-y-1">
                <div class="flex justify-between text-xs" style="color:var(--t-text-3)">
                    <span>{{ fmtMoney(currentLoyaltyLevel.min) }}</span>
                    <span v-if="nextLoyaltyLevel">{{ fmtMoney(nextLoyaltyLevel.min) }} ({{ nextLoyaltyLevel.name }})</span>
                    <span v-else>Максимум</span>
                </div>
                <div class="h-3 rounded-full overflow-hidden" style="background:var(--t-bg)">
                    <div class="h-full rounded-full transition-all" style="background:var(--t-primary)"
                         :style="`width:${loyaltyProgress}%`"></div>
                </div>
                <div v-if="nextLoyaltyLevel" class="text-xs text-center" style="color:var(--t-text-3)">
                    До {{ nextLoyaltyLevel.name }}: ещё {{ fmtMoney(untilNextLevel) }}
                </div>
            </div>
            <!-- All levels -->
            <div class="grid grid-cols-5 gap-2 mt-4">
                <div v-for="l in loyaltyLevels" :key="l.name"
                     class="text-center p-2 rounded-lg border"
                     :style="`background:${l.name === currentLoyaltyLevel.name ? 'var(--t-primary-dim)' : 'var(--t-bg)'};border-color:${l.name === currentLoyaltyLevel.name ? 'var(--t-primary)' : 'var(--t-border)'}`">
                    <div class="text-xl">{{ loyaltyIcons[l.name] }}</div>
                    <div class="text-[10px] font-bold" :style="`color:${l.name === currentLoyaltyLevel.name ? 'var(--t-primary)' : 'var(--t-text-2)'}`">{{ l.name }}</div>
                    <div class="text-[10px]" style="color:var(--t-text-3)">{{ l.cashback }}%</div>
                </div>
            </div>
        </VCard>

        <!-- Financial metrics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <VStatCard title="Потрачено всего" :value="fmtMoney(cl.totalSpent)" icon="💰" />
            <VStatCard title="Средний чек" :value="fmtMoney(cl.avgCheck)" icon="🧾" />
            <VStatCard title="LTV (факт)" :value="fmtMoney(cl.ltv)" icon="📈" />
            <VStatCard title="LTV (прогноз)" :value="fmtMoney(cl.ltvPredicted)" icon="🔮" />
        </div>

        <!-- Spending chart -->
        <VCard title="📊 Траты по месяцам">
            <div class="flex items-end gap-1.5 h-40">
                <div v-for="m in monthlySpending" :key="m.month" class="flex-1 flex flex-col items-center">
                    <span class="text-[10px] font-bold mb-1" style="color:var(--t-primary)">{{ fmtMoney(m.amount) }}</span>
                    <div class="w-full rounded-t-lg transition-all" style="background:var(--t-primary)"
                         :style="`height:${(m.amount / maxMonthly) * 100}%`"></div>
                    <span class="text-[10px] mt-1" style="color:var(--t-text-3)">{{ m.month }}</span>
                </div>
            </div>
        </VCard>

        <!-- Bonus history -->
        <VCard title="📜 История бонусов">
            <div class="space-y-1 max-h-64 overflow-y-auto">
                <div v-for="b in bonusHistory" :key="b.id"
                     class="flex items-center gap-3 p-2 rounded-lg text-sm"
                     style="background:var(--t-bg)">
                    <span class="text-lg">{{ b.type === 'earn' ? '💚' : b.type === 'spend' ? '🔴' : '🎁' }}</span>
                    <div class="flex-1 min-w-0">
                        <div style="color:var(--t-text)">{{ b.reason }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ b.date }}</div>
                    </div>
                    <span class="font-bold shrink-0"
                          :style="`color:${b.type === 'earn' || b.type === 'promo' ? '#22c55e' : '#ef4444'}`">
                        {{ b.type === 'spend' ? '-' : '+' }}{{ fmt(b.amount) }} ₽
                    </span>
                    <span class="text-xs shrink-0" style="color:var(--t-text-3)">Баланс: {{ fmt(b.balance) }}</span>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- 5. PREFERENCES TAB                                         -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div v-else-if="activeTab === 'prefs'" class="space-y-4">
        <!-- Top services -->
        <VCard title="⭐ Любимые услуги (Топ-5)">
            <div class="space-y-2">
                <div v-for="s in topServicesPrefs" :key="s.name"
                     class="flex items-center gap-3">
                    <span class="text-lg">{{ s.icon }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium" style="color:var(--t-text)">{{ s.name }}</span>
                            <span class="text-xs" style="color:var(--t-text-3)">{{ s.count }} раз ({{ s.pct }}%)</span>
                        </div>
                        <div class="h-2 rounded-full mt-1" style="background:var(--t-bg)">
                            <div class="h-full rounded-full" style="background:var(--t-primary)"
                                 :style="`width:${s.pct}%`"></div>
                        </div>
                    </div>
                </div>
            </div>
        </VCard>

        <!-- Preferred masters -->
        <VCard title="👩‍🎨 Предпочитаемые мастера">
            <div class="space-y-2">
                <div v-for="m in preferredMasters" :key="m.name"
                     class="flex items-center gap-3 p-3 rounded-xl border"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold"
                         style="background:var(--t-primary-dim);color:var(--t-primary)">{{ m.name.charAt(0) }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium" style="color:var(--t-text)">{{ m.name }}</span>
                            <span v-if="m.isFavorite" class="text-xs">❤️ Любимый</span>
                        </div>
                        <div class="text-xs" style="color:var(--t-text-3)">{{ m.visits }} визитов ({{ m.pct }}%) · ⭐ {{ m.rating }}</div>
                    </div>
                    <div class="h-2 w-24 rounded-full" style="background:var(--t-bg)">
                        <div class="h-full rounded-full" style="background:var(--t-primary)" :style="`width:${m.pct}%`"></div>
                    </div>
                </div>
            </div>
        </VCard>

        <div class="grid md:grid-cols-2 gap-4">
            <!-- Preferred times -->
            <VCard title="🕐 Любимое время записи">
                <div class="space-y-2">
                    <div v-for="t in preferredTimes" :key="t.slot" class="flex items-center gap-3 text-sm">
                        <span class="w-28 shrink-0" style="color:var(--t-text)">{{ t.slot }}</span>
                        <div class="flex-1 h-2 rounded-full" style="background:var(--t-bg)">
                            <div class="h-full rounded-full" style="background:var(--t-primary)" :style="`width:${t.pct}%`"></div>
                        </div>
                        <span class="text-xs w-12 text-right" style="color:var(--t-text-3)">{{ t.count }}x</span>
                    </div>
                </div>
            </VCard>

            <!-- Preferred salons -->
            <VCard title="📍 Предпочитаемые филиалы">
                <div class="space-y-2">
                    <div v-for="s in preferredSalons" :key="s.name" class="flex items-center gap-3 text-sm">
                        <span class="flex-1" style="color:var(--t-text)">{{ s.name }}</span>
                        <div class="w-32 h-2 rounded-full" style="background:var(--t-bg)">
                            <div class="h-full rounded-full" style="background:var(--t-primary)" :style="`width:${s.pct}%`"></div>
                        </div>
                        <span class="text-xs w-14 text-right" style="color:var(--t-text-3)">{{ s.visits }}x ({{ s.pct }}%)</span>
                    </div>
                </div>
            </VCard>
        </div>

        <!-- Weekday frequency -->
        <VCard title="📅 Частота по дням недели">
            <div class="flex items-end gap-2 h-28">
                <div v-for="d in weekdayFrequency" :key="d.day" class="flex-1 flex flex-col items-center">
                    <span class="text-[10px] font-bold mb-1" style="color:var(--t-primary)">{{ d.count }}</span>
                    <div class="w-full rounded-t-lg" style="background:var(--t-primary)"
                         :style="`height:${(d.count / maxWeekday) * 100}%`"></div>
                    <span class="text-[10px] mt-1" style="color:var(--t-text-3)">{{ d.day }}</span>
                </div>
            </div>
        </VCard>

        <!-- Preferred brands -->
        <VCard title="💄 Предпочтения по брендам">
            <div class="flex flex-wrap gap-2">
                <span v-for="b in cl.preferredBrands" :key="b"
                      class="px-3 py-1.5 rounded-full text-xs font-medium"
                      style="background:var(--t-primary-dim);color:var(--t-primary)">{{ b }}</span>
            </div>
        </VCard>
    </div>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- 6. ANALYTICS TAB                                           -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div v-else-if="activeTab === 'analytics'" class="space-y-4">
        <!-- LTV chart -->
        <VCard title="📈 Динамика LTV и частоты визитов">
            <div class="flex items-end gap-1.5 h-40">
                <div v-for="m in ltvMonthly" :key="m.month" class="flex-1 flex flex-col items-center">
                    <span class="text-[9px] font-bold mb-0.5" style="color:var(--t-primary)">{{ fmtMoney(m.ltv) }}</span>
                    <div class="w-full rounded-t-lg" style="background:var(--t-primary)"
                         :style="`height:${(m.ltv / maxLtv) * 100}%`"></div>
                    <span class="text-[10px] mt-0.5 font-bold" style="color:var(--t-text-2)">{{ m.visits }}v</span>
                    <span class="text-[10px]" style="color:var(--t-text-3)">{{ m.month }}</span>
                </div>
            </div>
        </VCard>

        <!-- AI Prediction -->
        <VCard title="🤖 AI-прогноз">
            <div class="grid md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <div class="p-3 rounded-xl" style="background:var(--t-bg)">
                        <div class="text-xs" style="color:var(--t-text-3)">📅 Прогноз следующего визита</div>
                        <div class="text-lg font-bold" style="color:var(--t-primary)">{{ aiPrediction.nextVisitDate }}</div>
                        <div class="text-xs" style="color:var(--t-text-2)">через {{ aiPrediction.nextVisitDays }} дней</div>
                    </div>
                    <div class="p-3 rounded-xl" style="background:var(--t-bg)">
                        <div class="text-xs" style="color:var(--t-text-3)">💇 Предполагаемая услуга</div>
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ aiPrediction.predictedService }}</div>
                    </div>
                    <div class="p-3 rounded-xl" style="background:var(--t-bg)">
                        <div class="text-xs" style="color:var(--t-text-3)">⚠️ Вероятность оттока</div>
                        <div class="flex items-center gap-2">
                            <div class="text-lg font-bold" :style="`color:${aiPrediction.churnRisk > 30 ? '#ef4444' : aiPrediction.churnRisk > 15 ? '#f59e0b' : '#22c55e'}`">
                                {{ aiPrediction.churnRisk }}%
                            </div>
                            <span class="text-xs" style="color:var(--t-text-3)">
                                {{ aiPrediction.churnRisk > 30 ? 'Высокий риск!' : aiPrediction.churnRisk > 15 ? 'Средний' : 'Низкий' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="text-xs font-bold" style="color:var(--t-text-2)">🎯 AI-рекомендации:</div>
                    <div v-for="(rec, ri) in aiPrediction.recommendations" :key="ri"
                         class="p-3 rounded-lg text-sm" style="background:var(--t-bg);color:var(--t-text)">
                        {{ rec }}
                    </div>
                </div>
            </div>
        </VCard>

        <!-- Comparison with salon average -->
        <VCard title="⚖️ Сравнение со средними показателями салона">
            <div class="space-y-3">
                <div v-for="item in [
                    { label: 'Средний чек', client: salonComparison.avgSpent, salon: salonComparison.salonAvgSpent, suffix: ' ₽', better: salonComparison.avgSpent > salonComparison.salonAvgSpent },
                    { label: 'Частота визитов (дни)', client: salonComparison.avgFrequency, salon: salonComparison.salonAvgFrequency, suffix: ' дн.', better: salonComparison.avgFrequency < salonComparison.salonAvgFrequency },
                    { label: 'Средняя оценка', client: salonComparison.avgRating, salon: salonComparison.salonAvgRating, suffix: '', better: salonComparison.avgRating >= salonComparison.salonAvgRating },
                ]" :key="item.label"
                   class="flex items-center gap-4 p-3 rounded-lg" style="background:var(--t-bg)">
                    <div class="flex-1">
                        <div class="text-xs" style="color:var(--t-text-3)">{{ item.label }}</div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-sm font-bold" style="color:var(--t-primary)">
                                {{ typeof item.client === 'number' && item.suffix === ' ₽' ? fmtMoney(item.client) : item.client + item.suffix }}
                            </span>
                            <span class="text-xs" :style="`color:${item.better ? '#22c55e' : '#ef4444'}`">
                                {{ item.better ? '▲ лучше' : '▼ ниже' }} среднего
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-[10px]" style="color:var(--t-text-3)">Среднее по салону</div>
                        <div class="text-sm" style="color:var(--t-text-2)">
                            {{ typeof item.salon === 'number' && item.suffix === ' ₽' ? fmtMoney(item.salon) : item.salon + item.suffix }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 p-3 rounded-xl text-center" style="background:var(--t-primary-dim)">
                <span class="text-sm font-bold" style="color:var(--t-primary)">
                    🏆 Клиент входит в топ {{ salonComparison.salonTopPercent }}% по выручке
                </span>
            </div>
        </VCard>
    </div>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- 7. MEDICAL CARD TAB                                        -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div v-else-if="activeTab === 'medical'" class="space-y-4">
        <!-- Quick info -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <VStatCard title="Тип кожи" :value="cl.skinType" icon="🧴" />
            <VStatCard title="Тип волос" :value="cl.hairType" icon="💇" />
            <VStatCard title="Ногти" :value="cl.nailType" icon="💅" />
            <VStatCard title="Мед. данные" :value="cl.medicalNotes" icon="🏥" />
        </div>

        <!-- Allergies (prominent) -->
        <div v-if="cl.allergies && cl.allergies !== 'Нет'"
             class="p-4 rounded-xl border-2 border-red-500/60"
             style="background:rgba(239,68,68,.08)">
            <div class="text-sm font-bold text-red-500 mb-1">⚠️ Аллергии и противопоказания</div>
            <div class="text-sm text-red-400">{{ cl.allergies }}</div>
            <div class="text-xs text-red-400/70 mt-1">Обязательно учитывать при оказании всех услуг!</div>
        </div>

        <!-- Preferred brands -->
        <VCard title="💄 Предпочитаемые бренды косметики">
            <div class="flex flex-wrap gap-2">
                <span v-for="b in cl.preferredBrands" :key="b"
                      class="px-3 py-1.5 rounded-full text-xs font-medium"
                      style="background:var(--t-primary-dim);color:var(--t-primary)">{{ b }}</span>
            </div>
        </VCard>

        <!-- Medical entries timeline -->
        <VCard title="📋 Записи медкарты">
            <div class="space-y-3">
                <div v-for="e in medicalEntries" :key="e.id"
                     class="p-3 rounded-xl border-l-4"
                     :style="`background:var(--t-bg);border-color:${e.severity === 'high' ? '#ef4444' : e.severity === 'medium' ? '#f59e0b' : 'var(--t-primary)'}`">
                    <div class="flex items-center gap-2 mb-1">
                        <span>{{ medTypeIcons[e.type] }}</span>
                        <span class="text-sm font-bold" style="color:var(--t-text)">{{ e.title }}</span>
                        <span class="text-[10px] ml-auto" style="color:var(--t-text-3)">{{ e.date }}</span>
                    </div>
                    <div class="text-sm" style="color:var(--t-text-2)">{{ e.description }}</div>
                    <div class="text-[10px] mt-1" style="color:var(--t-text-3)">Автор: {{ e.author }}</div>
                </div>
                <VButton size="sm" variant="outline" class="w-full">➕ Добавить запись в медкарту</VButton>
            </div>
        </VCard>
    </div>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- 8. DOCUMENTS TAB                                           -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div v-else-if="activeTab === 'docs'" class="space-y-4">
        <!-- Photo gallery (before/after) -->
        <VCard title="📸 Фото до/после">
            <div class="grid grid-cols-3 md:grid-cols-5 gap-2">
                <div v-for="d in documents.filter(dd => dd.type === 'photo')" :key="d.id"
                     class="aspect-square rounded-xl flex flex-col items-center justify-center text-xs cursor-pointer hover:opacity-80 transition"
                     style="background:var(--t-primary-dim);color:var(--t-primary)">
                    <span class="text-2xl mb-1">📸</span>
                    <span class="text-center px-1">{{ d.name.replace('Фото до/после — ', '') }}</span>
                    <span class="text-[9px] mt-0.5" style="color:var(--t-text-3)">{{ d.date }}</span>
                </div>
                <div class="aspect-square rounded-xl flex items-center justify-center border-2 border-dashed cursor-pointer hover:opacity-80"
                     style="border-color:var(--t-border);color:var(--t-text-3)">
                    <span class="text-2xl">➕</span>
                </div>
            </div>
        </VCard>

        <!-- Other documents -->
        <VCard title="📄 Документы">
            <div class="space-y-2">
                <div v-for="d in documents.filter(dd => dd.type !== 'photo')" :key="d.id"
                     class="flex items-center gap-3 p-3 rounded-lg border"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-lg">{{ docTypeIcons[d.type] }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ d.name }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ d.date }}</div>
                    </div>
                    <VButton v-if="d.file" size="sm" variant="outline">📥 Скачать</VButton>
                    <VButton v-else size="sm" variant="outline">📤 Загрузить</VButton>
                </div>
                <VButton size="sm" variant="outline" class="w-full">➕ Добавить документ</VButton>
            </div>
        </VCard>
    </div>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- 9. COMMUNICATIONS TAB                                      -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div v-else-if="activeTab === 'comms'" class="space-y-4">
        <!-- Quick send -->
        <div class="flex gap-2">
            <VButton size="sm" @click="showMessageModal = true">💬 Новое сообщение</VButton>
            <VButton size="sm" variant="outline" @click="$emit('call', cl)">📞 Позвонить</VButton>
            <VButton size="sm" variant="outline" @click="$emit('whatsapp', cl)">💬 WhatsApp</VButton>
        </div>

        <!-- Communication stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <VStatCard title="Всего сообщений" :value="String(communications.length)" icon="💬" />
            <VStatCard title="Прочитано" :value="String(communications.filter(c => c.readAt).length)" icon="✅" />
            <VStatCard title="Входящие" :value="String(communications.filter(c => c.direction === 'in').length)" icon="📥" />
            <VStatCard title="Исходящие" :value="String(communications.filter(c => c.direction === 'out').length)" icon="📤" />
        </div>

        <!-- Timeline -->
        <VCard title="📨 История коммуникаций">
            <div class="space-y-2 max-h-96 overflow-y-auto">
                <div v-for="c in communications" :key="c.id"
                     class="flex items-start gap-3 p-3 rounded-xl border"
                     :style="`background:var(--t-bg);border-color:var(--t-border);border-left:3px solid ${c.direction === 'in' ? 'var(--t-primary)' : 'var(--t-border)'}`">
                    <span class="text-xl shrink-0">{{ channelIcons[c.channel] }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium" style="color:var(--t-text)">{{ c.title }}</span>
                            <VBadge :color="c.direction === 'in' ? 'blue' : 'gray'" size="sm">
                                {{ c.direction === 'in' ? '📥 Входящее' : '📤 Исходящее' }}
                            </VBadge>
                            <VBadge color="gray" size="sm">{{ channelLabels[c.channel] }}</VBadge>
                        </div>
                        <div class="text-sm mt-1" style="color:var(--t-text-2)">{{ c.preview }}</div>
                        <div class="flex items-center gap-3 mt-1 text-[10px]" style="color:var(--t-text-3)">
                            <span>📅 {{ c.date }}</span>
                            <span v-if="c.readAt">👁️ Прочитано {{ c.readAt }}</span>
                            <span v-else style="color:#f59e0b">⏳ Не прочитано</span>
                        </div>
                    </div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- 10. NOTES TAB                                              -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div v-else-if="activeTab === 'notes'" class="space-y-4">
        <!-- Add note -->
        <VCard title="📝 Добавить заметку">
            <div class="space-y-2">
                <textarea v-model="newNoteText" rows="3" placeholder="Введите заметку о клиенте..."
                          class="w-full p-3 rounded-xl text-sm border resize-none"
                          style="background:var(--t-bg);color:var(--t-text);border-color:var(--t-border)"></textarea>
                <VButton size="sm" @click="addNote" :disabled="!newNoteText.trim()">💾 Сохранить заметку</VButton>
            </div>
        </VCard>

        <!-- Pinned notes -->
        <div v-if="notes.filter(n => n.pinned).length > 0">
            <h3 class="text-sm font-bold mb-2" style="color:var(--t-text-2)">📌 Закреплённые</h3>
            <div class="space-y-2">
                <div v-for="n in notes.filter(nn => nn.pinned)" :key="n.id"
                     class="p-4 rounded-xl border-l-4"
                     style="background:var(--t-surface);border-color:var(--t-primary)">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold" style="color:var(--t-primary)">📌 {{ n.author }}</span>
                            <span class="text-[10px]" style="color:var(--t-text-3)">{{ n.date }}</span>
                        </div>
                        <button class="text-xs hover:opacity-70" @click="togglePin(n)" title="Открепить">📌</button>
                    </div>
                    <div class="text-sm" style="color:var(--t-text)">{{ n.text }}</div>
                </div>
            </div>
        </div>

        <!-- All notes -->
        <VCard title="📋 Все заметки">
            <div class="space-y-2 max-h-96 overflow-y-auto">
                <div v-for="n in notes.filter(nn => !nn.pinned)" :key="n.id"
                     class="p-3 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold" style="color:var(--t-text-2)">{{ n.author }}</span>
                            <span class="text-[10px]" style="color:var(--t-text-3)">{{ n.date }}</span>
                        </div>
                        <button class="text-xs hover:opacity-70" @click="togglePin(n)" title="Закрепить">📍</button>
                    </div>
                    <div class="text-sm" style="color:var(--t-text)">{{ n.text }}</div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- 11. INFO TAB (Personal data)                               -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div v-else-if="activeTab === 'info'" class="space-y-4">
        <VCard title="👤 Основная информация">
            <div class="grid md:grid-cols-2 gap-x-8 gap-y-3">
                <div v-for="item in [
                    { label: '👤 ФИО',               value: cl.name },
                    { label: '📱 Телефон',           value: cl.phone },
                    { label: '💬 WhatsApp',          value: cl.whatsapp },
                    { label: '✈️ Telegram',          value: cl.telegram || '—' },
                    { label: '📧 Email',             value: cl.email || '—' },
                    { label: '🎂 Дата рождения',    value: cl.birthday + ' (' + cl.age + ' лет)' },
                    { label: '♊ Знак зодиака',      value: cl.zodiac },
                    { label: '⚧ Пол',               value: cl.gender },
                    { label: '🌍 Язык общения',     value: cl.language },
                    { label: '📣 Источник привлечения', value: cl.source },
                    { label: '📅 Дата регистрации', value: cl.createdAt },
                    { label: '⏳ Дней в базе',       value: cl.daysInBase + ' ' + plural(cl.daysInBase, 'день', 'дня', 'дней') },
                ]" :key="item.label" class="flex items-start gap-2 py-1.5 border-b" style="border-color:var(--t-border)">
                    <span class="text-xs font-medium w-44 shrink-0" style="color:var(--t-text-3)">{{ item.label }}</span>
                    <span class="text-sm" style="color:var(--t-text)">{{ item.value }}</span>
                </div>
            </div>
        </VCard>

        <!-- Tags -->
        <VCard title="🏷️ Теги и сегменты">
            <div class="flex flex-wrap gap-2">
                <span v-for="tag in cl.tags" :key="tag"
                      class="px-3 py-1.5 rounded-full text-xs font-medium cursor-pointer hover:opacity-80 transition"
                      style="background:var(--t-primary-dim);color:var(--t-primary)">{{ tag }}</span>
                <button class="px-3 py-1.5 rounded-full text-xs border-2 border-dashed hover:opacity-80"
                        style="border-color:var(--t-border);color:var(--t-text-3)">+ Добавить тег</button>
            </div>
        </VCard>

        <!-- Internal notes summary -->
        <VCard title="📝 Последние заметки">
            <div class="space-y-2">
                <div v-for="n in notes.slice(0, 3)" :key="n.id"
                     class="p-2 rounded-lg text-sm" style="background:var(--t-bg)">
                    <div class="flex items-center gap-2">
                        <span v-if="n.pinned" class="text-xs">📌</span>
                        <span class="text-xs font-bold" style="color:var(--t-text-2)">{{ n.author }}</span>
                        <span class="text-[10px]" style="color:var(--t-text-3)">{{ n.date }}</span>
                    </div>
                    <div class="text-sm truncate mt-0.5" style="color:var(--t-text)">{{ n.text }}</div>
                </div>
                <VButton size="sm" variant="outline" class="w-full" @click="activeTab = 'notes'">
                    Все заметки →
                </VButton>
            </div>
        </VCard>
    </div>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- MODALS                                                     -->
    <!-- ═══════════════════════════════════════════════════════════ -->

    <!-- Award Bonus Modal -->
    <VModal :show="showBonusModal" title="🎁 Начислить бонусы" @close="showBonusModal = false">
        <div class="space-y-3">
            <div class="text-sm" style="color:var(--t-text-2)">Клиент: <strong>{{ cl.name }}</strong></div>
            <div class="text-sm" style="color:var(--t-text-3)">Текущий баланс: {{ fmt(cl.bonusBalance) }} ₽</div>
            <VInput v-model="bonusAmount" type="number" placeholder="Сумма бонусов" />
            <VInput v-model="bonusReason" placeholder="Причина начисления" />
            <div class="flex gap-2 justify-end">
                <VButton size="sm" variant="outline" @click="showBonusModal = false">Отмена</VButton>
                <VButton size="sm" @click="awardBonus">Начислить</VButton>
            </div>
        </div>
    </VModal>

    <!-- Deduct Bonus Modal -->
    <VModal :show="showDeductModal" title="➖ Списать бонусы" @close="showDeductModal = false">
        <div class="space-y-3">
            <div class="text-sm" style="color:var(--t-text-2)">Клиент: <strong>{{ cl.name }}</strong></div>
            <div class="text-sm" style="color:var(--t-text-3)">Текущий баланс: {{ fmt(cl.bonusBalance) }} ₽</div>
            <VInput v-model="deductAmount" type="number" placeholder="Сумма списания" />
            <VInput v-model="deductReason" placeholder="Причина списания" />
            <div class="flex gap-2 justify-end">
                <VButton size="sm" variant="outline" @click="showDeductModal = false">Отмена</VButton>
                <VButton size="sm" @click="deductBonus">Списать</VButton>
            </div>
        </div>
    </VModal>

    <!-- Send Message Modal -->
    <VModal :show="showMessageModal" title="💬 Отправить сообщение" @close="showMessageModal = false">
        <div class="space-y-3">
            <div class="text-sm" style="color:var(--t-text-2)">Клиент: <strong>{{ cl.name }}</strong></div>
            <div class="flex gap-2">
                <button v-for="ch in ['whatsapp','sms','push','telegram','email']" :key="ch"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
                        :style="messageChannel === ch
                            ? 'background:var(--t-primary);color:#fff'
                            : 'background:var(--t-surface);color:var(--t-text-2)'"
                        @click="messageChannel = ch">
                    {{ channelIcons[ch] }} {{ channelLabels[ch] }}
                </button>
            </div>
            <textarea v-model="messageText" rows="4" placeholder="Текст сообщения..."
                      class="w-full p-3 rounded-xl text-sm border resize-none"
                      style="background:var(--t-bg);color:var(--t-text);border-color:var(--t-border)"></textarea>
            <div class="flex gap-2 justify-end">
                <VButton size="sm" variant="outline" @click="showMessageModal = false">Отмена</VButton>
                <VButton size="sm" @click="sendMessage">Отправить</VButton>
            </div>
        </div>
    </VModal>

    <!-- Create Promo Modal -->
    <VModal :show="showPromoModal" title="🏷️ Персональная акция" @close="showPromoModal = false">
        <div class="space-y-3">
            <div class="text-sm" style="color:var(--t-text-2)">Клиент: <strong>{{ cl.name }}</strong></div>
            <div class="flex gap-2">
                <button v-for="pt in [{ k: 'discount', label: '🏷️ Скидка %' }, { k: 'fixed', label: '💰 Скидка ₽' }, { k: 'gift', label: '🎁 Подарок' }, { k: 'bundle', label: '📦 Пакет' }]"
                        :key="pt.k"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
                        :style="promoType === pt.k
                            ? 'background:var(--t-primary);color:#fff'
                            : 'background:var(--t-surface);color:var(--t-text-2)'"
                        @click="promoType = pt.k">
                    {{ pt.label }}
                </button>
            </div>
            <VInput v-model="promoValue" :placeholder="promoType === 'discount' ? 'Размер скидки (%)' : promoType === 'fixed' ? 'Сумма скидки (₽)' : 'Описание'" />
            <div class="flex gap-2 justify-end">
                <VButton size="sm" variant="outline" @click="showPromoModal = false">Отмена</VButton>
                <VButton size="sm" @click="createPromo">Создать</VButton>
            </div>
        </div>
    </VModal>

</div>
</template>
