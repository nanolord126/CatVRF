<script setup>
/**
 * BeautyMasterCard — полная карточка мастера Beauty.
 * 10 секций: шапка, action bar, инфо, график, услуги,
 * финансы, статистика, отзывы, история, документы.
 * Получает мастера через props, эмитит события наверх.
 */
import { ref, computed, reactive } from 'vue';
import VButton from '../../UI/VButton.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import VModal from '../../UI/VModal.vue';
import VInput from '../../UI/VInput.vue';
import VStatCard from '../../UI/VStatCard.vue';

const props = defineProps({
    master: { type: Object, required: true },
    salons: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    bookings: { type: Array, default: () => [] },
});

const emit = defineEmits([
    'close', 'edit', 'book-client', 'open-calendar',
    'award-bonus', 'send-message', 'archive', 'generate-report',
]);

/* ─── Helpers ─── */
function fmtMoney(n) { return new Intl.NumberFormat('ru-RU').format(n) + ' ₽'; }
function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }

/* ═══════════════ PROFILE TABS ═══════════════ */
const profileTabs = [
    { key: 'info',      label: '📋 Инфо' },
    { key: 'schedule',  label: '📅 График' },
    { key: 'skills',    label: '💇 Услуги' },
    { key: 'finance',   label: '💰 Финансы' },
    { key: 'stats',     label: '📊 Статистика' },
    { key: 'reviews',   label: '⭐ Отзывы' },
    { key: 'history',   label: '🕐 История' },
    { key: 'docs',      label: '📁 Документы' },
];
const activeTab = ref('info');

/* ═══════════════ MASTER DATA (enriched) ═══════════════ */
const masterProfile = computed(() => {
    const m = props.master;
    return {
        ...m,
        nickname: m.nickname || null,
        status: m.status || 'active',
        photo: m.photo || '',
        birthDate: m.birthDate || '15.07.1992',
        age: m.age || 33,
        phone: m.phone || '+7 900 123-45-67',
        telegram: m.telegram || '@anna_sokolova',
        whatsapp: m.whatsapp || '+79001234567',
        email: m.email || 'anna@beautylab.ru',
        hireDate: m.hireDate || '10.03.2022',
        experience: m.experience || '4 года 1 мес.',
        salonIds: m.salonIds || [m.salon],
        schedule: m.schedule || 'Пн–Пт 10:00–20:00',
        level: m.level || 'Мастер',
        commissionType: m.commissionType || 'percent',
        commissionValue: m.commissionValue || (m.commission ? parseInt(m.commission) : 35),
    };
});

/* ─── Status mappings ─── */
const statusColors = { active: 'green', vacation: 'blue', sick: 'yellow', fired: 'red' };
const statusLabels = { active: 'Активен', vacation: 'В отпуске', sick: 'На больничном', fired: 'Уволен' };
const statusIcons = { active: '🟢', vacation: '🏖️', sick: '🤒', fired: '🚫' };
const levelColors = { 'Джуниор': 'gray', 'Мастер': 'blue', 'Топ': 'purple', 'Junior': 'gray', 'Middle': 'blue', 'Senior': 'purple', 'Top Master': 'purple' };

/* ═══════════════ 4. SCHEDULE & AVAILABILITY ═══════════════ */
const scheduleDays = computed(() => {
    const days = [];
    const dayNames = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
    const now = new Date();
    for (let i = 0; i < 14; i++) {
        const d = new Date(now.getTime() + i * 86400000);
        const dayKey = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
        const isWorking = ![0, 6].includes(d.getDay()); // Пн–Пт
        const bookingsCount = masterBookings.value.filter(b => b.date?.startsWith(dayKey) || false).length;
        const load = isWorking ? Math.min(bookingsCount * 12, 100) : 0;
        days.push({
            date: d.getDate(),
            dayName: dayNames[d.getDay()],
            month: d.toLocaleDateString('ru-RU', { month: 'short' }),
            isWorking,
            isToday: i === 0,
            load,
            loadColor: load >= 80 ? '#ef4444' : load >= 50 ? '#f59e0b' : '#22c55e',
            freeSlots: isWorking ? Math.max(8 - bookingsCount, 0) : 0,
            blocked: false,
            blockReason: '',
        });
    }
    return days;
});

const timeBlocks = ref([
    { id: 1, type: 'vacation', from: '15.04.2026', to: '20.04.2026', reason: 'Отпуск' },
    { id: 2, type: 'training', from: '25.04.2026', to: '25.04.2026', reason: "Обучение L'Oréal Professionnel" },
]);
const blockTypeLabels = { vacation: '🏖️ Отпуск', sick: '🤒 Больничный', training: '📚 Обучение', break: '☕ Перерыв' };

/* ═══════════════ 5. SKILLS & SERVICES ═══════════════ */
const masterServices = computed(() => [
    { id: 1, name: 'Стрижка женская',      icon: '✂️', duration: 60,  price: 2500, level: 'expert',   monthlyCount: 48 },
    { id: 2, name: 'Окрашивание AirTouch', icon: '🎨', duration: 180, price: 8500, level: 'expert',   monthlyCount: 22 },
    { id: 3, name: 'Окрашивание балаяж',   icon: '🎨', duration: 150, price: 4500, level: 'expert',   monthlyCount: 18 },
    { id: 4, name: 'Укладка',              icon: '💇', duration: 45,  price: 1800, level: 'main',     monthlyCount: 34 },
    { id: 5, name: 'Тонирование',          icon: '✨', duration: 30,  price: 1000, level: 'main',     monthlyCount: 15 },
    { id: 6, name: 'Уход Olaplex',         icon: '💎', duration: 30,  price: 1500, level: 'additional', monthlyCount: 12 },
    { id: 7, name: 'Кератиновое выпрямл.', icon: '🧴', duration: 120, price: 6000, level: 'additional', monthlyCount: 4 },
]);
const serviceLevelLabels = { expert: '🏆 Эксперт', main: '⭐ Основная', additional: '➕ Дополнительная' };
const serviceLevelColors = { expert: 'purple', main: 'blue', additional: 'gray' };

const certificates = ref([
    { id: 1, name: "Диплом колориста L'Oréal Professionnel", date: '2023', file: 'cert_loreal.pdf' },
    { id: 2, name: 'Сертификат Wella Color Expert',          date: '2024', file: 'cert_wella.pdf' },
    { id: 3, name: 'AirTouch — авторский курс',              date: '2024', file: 'cert_airtouch.pdf' },
    { id: 4, name: 'Диплом парикмахер-стилист',              date: '2018', file: 'cert_diploma.pdf' },
]);

const portfolio = ref([
    { id: 1, label: 'Балаяж блонд',   type: 'before_after' },
    { id: 2, label: 'AirTouch каштан', type: 'before_after' },
    { id: 3, label: 'Стрижка каре',    type: 'after' },
    { id: 4, label: 'Окрашивание рыжий', type: 'before_after' },
    { id: 5, label: 'Мужская стрижка', type: 'after' },
    { id: 6, label: 'Свадебная укладка', type: 'after' },
]);

const specialSkills = ref([
    'Колорист-стилист', 'AirTouch', 'Балаяж', 'Шатуш',
    'Работа с тонкими волосами', 'Кудрявый метод',
    'Свадебные укладки', 'Уходовые процедуры',
]);

/* ═══════════════ 6. FINANCES & MOTIVATION ═══════════════ */
const financeStats = computed(() => ({
    totalPayout: 168000,
    avgCheck: 3390,
    monthlyServices: 124,
    pendingPayout: 42000,
}));

const payoutHistory = ref([
    { id: 1, period: 'Март 2026',   revenue: 420000, commission: 168000, bonus: 5000,  total: 173000, status: 'paid',    paidDate: '05.04.2026' },
    { id: 2, period: 'Февраль 2026', revenue: 385000, commission: 154000, bonus: 3000,  total: 157000, status: 'paid',    paidDate: '05.03.2026' },
    { id: 3, period: 'Январь 2026',  revenue: 310000, commission: 124000, bonus: 10000, total: 134000, status: 'paid',    paidDate: '05.02.2026' },
    { id: 4, period: 'Апрель 2026 (текущий)', revenue: 180000, commission: 72000, bonus: 0, total: 72000, status: 'accruing', paidDate: '—' },
]);
const payoutStatusColors = { paid: 'green', pending: 'yellow', accruing: 'blue', draft: 'gray' };
const payoutStatusLabels = { paid: 'Выплачено', pending: 'К выплате', accruing: 'Начисляется', draft: 'Черновик' };

const bonusProgram = ref([
    { name: 'Перевыполнение плана',    rule: '> 120 записей/мес.',     bonus: '+5 000 ₽',   status: 'active' },
    { name: 'Высокий рейтинг',         rule: '⭐ > 4.8 за месяц',     bonus: '+3 000 ₽',   status: 'active' },
    { name: 'Без отмен по вине мастера', rule: '0 отмен по вине мастера', bonus: '+2 000 ₽', status: 'active' },
    { name: 'Привёл нового клиента',    rule: 'Реферал подтверждён',    bonus: '+1 000 ₽ / клиент', status: 'active' },
]);

/* ═══════════════ 7. STATISTICS & PERFORMANCE ═══════════════ */
const masterBookings = computed(() => props.bookings?.filter(b => b.master?.includes(props.master.name?.split(' ')[0])) || []);

const performanceStats = computed(() => ({
    bookingsThisMonth: 124,
    loadPercent: 92,
    avgServiceTime: 68,
    rating: props.master.rating || 4.9,
    completionRate: 97,
    repeatRate: 74,
    upsellRate: 42,
    noShowRate: 2,
}));

const monthlyPerformance = ref([
    { month: 'Окт', bookings: 98,  revenue: 312000, avgCheck: 3180 },
    { month: 'Ноя', bookings: 105, revenue: 336000, avgCheck: 3200 },
    { month: 'Дек', bookings: 112, revenue: 392000, avgCheck: 3500 },
    { month: 'Янв', bookings: 90,  revenue: 310000, avgCheck: 3444 },
    { month: 'Фев', bookings: 110, revenue: 385000, avgCheck: 3500 },
    { month: 'Мар', bookings: 124, revenue: 420000, avgCheck: 3387 },
]);

const topServices = computed(() =>
    [...masterServices.value]
        .sort((a, b) => b.monthlyCount - a.monthlyCount)
        .slice(0, 5)
);

const comparisonWithOthers = ref([
    { name: props.master.name, bookings: 124, avgCheck: 3390, rating: 4.9, load: 92, highlight: true },
    { name: 'Игорь Волков',      bookings: 98,  avgCheck: 3570, rating: 4.9, load: 88 },
    { name: 'Ольга Демидова',    bookings: 110, avgCheck: 2820, rating: 4.8, load: 81 },
    { name: 'Светлана Романова', bookings: 85,  avgCheck: 3290, rating: 4.7, load: 74 },
    { name: 'Кристина Лебедева', bookings: 72,  avgCheck: 2500, rating: 4.5, load: 65 },
]);

/* ═══════════════ 8. REVIEWS ═══════════════ */
const reviewFilter = reactive({ rating: 0, service: '' });
const allReviews = ref([
    { id: 1, client: 'Мария К.',   rating: 5, text: 'Идеальный цвет! Анна — мастер от бога. Обязательно вернусь.', date: '09.04.2026', service: 'Окрашивание балаяж', reply: null },
    { id: 2, client: 'Елена В.',   rating: 5, text: 'Лучшая стрижка за последние годы! Рекомендую всем!', date: '06.04.2026', service: 'Стрижка женская', reply: 'Спасибо за тёплые слова! Ждём снова 💕' },
    { id: 3, client: 'Ольга Р.',   rating: 4, text: 'Окрашивание AirTouch — супер! Единственный минус — пришлось подождать 15 минут.', date: '03.04.2026', service: 'Окрашивание AirTouch', reply: null },
    { id: 4, client: 'Татьяна С.', rating: 5, text: 'Сделали потрясающую свадебную укладку! Держалась весь день и ночь.', date: '29.03.2026', service: 'Укладка', reply: 'Было приятно работать с вашими волосами! Счастья молодожёнам! 🎉' },
    { id: 5, client: 'Наталья Б.', rating: 5, text: 'Olaplex от Анны — это магия. Волосы как шёлк.', date: '22.03.2026', service: 'Уход Olaplex', reply: null },
    { id: 6, client: 'Дарья В.',   rating: 3, text: 'Тон немного отличается от того, что обсуждали. Но мастер исправила при следующем визите.', date: '15.03.2026', service: 'Тонирование', reply: 'Дарья, спасибо за обратную связь! Рады, что смогли скорректировать.' },
    { id: 7, client: 'Алиса Т.',   rating: 5, text: 'Уже 2 года хожу только к Анне. Ни разу не разочаровала.', date: '10.03.2026', service: 'Стрижка женская', reply: null },
    { id: 8, client: 'Ирина М.',   rating: 4, text: 'Хороший балаяж, но хотелось чуть светлее. В целом очень довольна.', date: '01.03.2026', service: 'Окрашивание балаяж', reply: null },
]);

const filteredReviews = computed(() => {
    let list = [...allReviews.value];
    if (reviewFilter.rating > 0) list = list.filter(r => r.rating === reviewFilter.rating);
    if (reviewFilter.service) list = list.filter(r => r.service === reviewFilter.service);
    return list;
});

const avgRating = computed(() => {
    if (!allReviews.value.length) return 0;
    return (allReviews.value.reduce((s, r) => s + r.rating, 0) / allReviews.value.length).toFixed(1);
});

const ratingDistribution = computed(() => {
    const dist = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
    for (const r of allReviews.value) dist[r.rating]++;
    const max = Math.max(...Object.values(dist), 1);
    return Object.entries(dist).reverse().map(([star, count]) => ({
        star: Number(star), count, pct: Math.round(count / max * 100),
    }));
});

const reviewServicesList = computed(() => {
    const set = new Set(allReviews.value.map(r => r.service));
    return [...set].sort();
});

const showReplyModal = ref(false);
const replyTarget = ref(null);
const replyText = ref('');
function openReplyModal(review) { replyTarget.value = review; replyText.value = review.reply || ''; showReplyModal.value = true; }
function submitReply() {
    if (replyTarget.value && replyText.value.trim()) {
        replyTarget.value.reply = replyText.value.trim();
    }
    showReplyModal.value = false;
}

/* ═══════════════ 9. WORK HISTORY ═══════════════ */
const workHistory = ref([
    {
        id: 1, date: '08.04.2026', time: '10:00', client: 'Мария К.', clientId: 101,
        services: [{ name: 'Окрашивание балаяж', price: 4500 }, { name: 'Стрижка', price: 2000 }],
        total: 6500, status: 'completed',
        adminComment: 'Клиент очень доволен',
    },
    {
        id: 2, date: '07.04.2026', time: '11:00', client: 'Елена В.', clientId: 102,
        services: [{ name: 'Стрижка женская', price: 2500 }],
        total: 2500, status: 'completed', adminComment: null,
    },
    {
        id: 3, date: '07.04.2026', time: '14:00', client: 'Виктория Н.', clientId: 103,
        services: [{ name: 'Окрашивание AirTouch', price: 8500 }],
        total: 8500, status: 'completed', adminComment: null,
    },
    {
        id: 4, date: '06.04.2026', time: '10:00', client: 'Ольга Р.', clientId: 104,
        services: [{ name: 'Укладка', price: 1800 }],
        total: 1800, status: 'completed',
        adminComment: 'Просила перенести, но решила прийти',
    },
    {
        id: 5, date: '06.04.2026', time: '13:00', client: 'Дарья В.', clientId: 105,
        services: [{ name: 'Тонирование', price: 1000 }, { name: 'Стрижка', price: 2500 }],
        total: 3500, status: 'completed', adminComment: null,
    },
    {
        id: 6, date: '05.04.2026', time: '11:00', client: 'Наталья Б.', clientId: 106,
        services: [{ name: 'Уход Olaplex', price: 1500 }],
        total: 1500, status: 'completed', adminComment: null,
    },
    {
        id: 7, date: '05.04.2026', time: '15:00', client: 'Ирина М.', clientId: 107,
        services: [{ name: 'Окрашивание балаяж', price: 4500 }, { name: 'Уход Olaplex', price: 1500 }],
        total: 6000, status: 'completed', adminComment: null,
    },
    {
        id: 8, date: '04.04.2026', time: '10:30', client: 'Регина К.', clientId: 108,
        services: [{ name: 'Стрижка женская', price: 2500 }, { name: 'Укладка', price: 1800 }],
        total: 4300, status: 'cancelled',
        adminComment: 'Отменено клиентом за 1 час — штраф 500 ₽',
    },
]);
const historyStatusColors = { completed: 'green', cancelled: 'red', no_show: 'gray' };
const historyStatusLabels = { completed: 'Выполнено', cancelled: 'Отменено', no_show: 'Неявка' };

/* ═══════════════ 10. DOCUMENTS & SECURITY ═══════════════ */
const documents = ref([
    { id: 1, name: 'Трудовой договор №127',    type: 'contract',  date: '10.03.2022', status: 'active',  file: 'contract_127.pdf' },
    { id: 2, name: 'ДС №1 к ТД №127 (повыш.)', type: 'contract', date: '01.01.2024', status: 'active',  file: 'amendment_1.pdf' },
    { id: 3, name: 'Медицинская книжка',        type: 'medical',  date: '15.06.2025', status: 'active',  file: 'medbook.pdf' },
    { id: 4, name: 'Паспорт (копия)',            type: 'identity', date: '10.03.2022', status: 'active',  file: 'passport.pdf' },
    { id: 5, name: 'ИНН',                        type: 'identity', date: '10.03.2022', status: 'active',  file: 'inn.pdf' },
    { id: 6, name: 'СНИЛС',                      type: 'identity', date: '10.03.2022', status: 'active',  file: 'snils.pdf' },
]);
const docTypeLabels = { contract: '📄 Договор', medical: '🏥 Мед.книжка', identity: '🪪 Документ', certificate: '🎓 Сертификат' };

const accessRights = ref([
    { name: 'Просмотр своего расписания',    granted: true },
    { name: 'Управление своими записями',     granted: true },
    { name: 'Просмотр карточек клиентов',     granted: true },
    { name: 'Просмотр финансов',              granted: false },
    { name: 'Редактирование услуг',           granted: false },
    { name: 'Управление акциями',             granted: false },
    { name: 'Доступ к CRM',                   granted: false },
    { name: 'Просмотр отчётов',              granted: false },
]);

/* ═══════════════ MODALS ═══════════════ */
const showEditModal = ref(false);
const showBonusModal = ref(false);
const showMessageModal = ref(false);
const showArchiveConfirm = ref(false);
const messageText = ref('');
const bonusAmount = ref('');
const bonusReason = ref('');

function editMaster() { showEditModal.value = true; emit('edit', props.master); }
function bookClient() { emit('book-client', props.master); }
function openCalendar() { emit('open-calendar', props.master); }
function awardBonus() {
    if (bonusAmount.value && bonusReason.value) {
        emit('award-bonus', { master: props.master, amount: bonusAmount.value, reason: bonusReason.value });
        bonusAmount.value = '';
        bonusReason.value = '';
        showBonusModal.value = false;
    }
}
function sendMessage() {
    if (messageText.value.trim()) {
        emit('send-message', { master: props.master, text: messageText.value });
        messageText.value = '';
        showMessageModal.value = false;
    }
}
function generateReport() { emit('generate-report', props.master); }
function archiveMaster() { emit('archive', props.master); showArchiveConfirm.value = false; }
</script>

<template>
<div class="space-y-4">

    <!-- ═══ 1. HEADER ═══ -->
    <div class="rounded-2xl border overflow-hidden" style="background:var(--t-surface);border-color:var(--t-border)">
        <!-- Cover gradient -->
        <div class="h-24 relative" style="background:linear-gradient(135deg, var(--t-gradient-from), var(--t-gradient-via), var(--t-gradient-to))">
            <button class="absolute top-3 right-3 p-2 rounded-xl text-xs" style="background:rgba(0,0,0,.3);color:#fff" @click="$emit('close')">✕ Закрыть</button>
        </div>

        <div class="px-6 pb-5 -mt-10 relative">
            <div class="flex flex-col md:flex-row items-start md:items-end gap-4">
                <!-- Photo -->
                <div class="relative group cursor-pointer" @click="editMaster" title="Заменить фото">
                    <div class="w-24 h-24 rounded-2xl border-4 flex items-center justify-center text-3xl font-bold shadow-lg overflow-hidden"
                         :style="`border-color:var(--t-surface);background:var(--t-primary-dim);color:var(--t-primary)`">
                        <img v-if="masterProfile.photo" :src="masterProfile.photo" class="w-full h-full object-cover" />
                        <span v-else>{{ masterProfile.name?.charAt(0) || '?' }}</span>
                    </div>
                    <div class="absolute inset-0 rounded-2xl bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                        <span class="text-white text-lg">📷</span>
                    </div>
                    <!-- Online dot -->
                    <div v-if="masterProfile.isOnline" class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-400 rounded-full border-2" style="border-color:var(--t-surface)"></div>
                </div>

                <!-- Name & info -->
                <div class="flex-1 min-w-0 pt-2 md:pt-0">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-xl font-bold" style="color:var(--t-text)">{{ masterProfile.name }}</h2>
                        <span v-if="masterProfile.nickname" class="text-sm" style="color:var(--t-text-3)">{{ masterProfile.nickname }}</span>
                        <VBadge :color="statusColors[masterProfile.status]" size="sm">
                            {{ statusIcons[masterProfile.status] }} {{ statusLabels[masterProfile.status] }}
                        </VBadge>
                        <VBadge :color="levelColors[masterProfile.level]" size="sm">{{ masterProfile.level }}</VBadge>
                    </div>
                    <div class="text-sm mt-1" style="color:var(--t-text-2)">{{ masterProfile.specialization }}</div>
                    <div class="flex items-center gap-4 mt-2 flex-wrap">
                        <!-- Rating -->
                        <div class="flex items-center gap-1">
                            <span v-for="s in 5" :key="s" class="text-sm">{{ s <= Math.round(masterProfile.rating) ? '⭐' : '☆' }}</span>
                            <span class="text-sm font-bold ml-1" style="color:var(--t-primary)">{{ masterProfile.rating }}</span>
                            <span class="text-xs" style="color:var(--t-text-3)">({{ masterProfile.reviews }} отзывов)</span>
                        </div>
                        <span class="text-xs" style="color:var(--t-text-3)">📍 {{ masterProfile.salonIds?.join(', ') || masterProfile.salon }}</span>
                        <span class="text-xs" style="color:var(--t-text-3)">📅 {{ masterProfile.schedule }}</span>
                    </div>
                </div>

                <!-- Edit button -->
                <VButton @click="editMaster" size="sm" class="shrink-0">✏️ Редактировать профиль</VButton>
            </div>
        </div>
    </div>

    <!-- ═══ 2. ACTION BAR ═══ -->
    <div class="flex flex-wrap gap-2">
        <VButton size="sm" @click="bookClient">📅 Записать клиента</VButton>
        <VButton size="sm" variant="outline" @click="openCalendar">🗓️ Календарь мастера</VButton>
        <VButton size="sm" variant="outline" @click="showBonusModal = true">💎 Начислить бонус</VButton>
        <VButton size="sm" variant="outline" @click="showMessageModal = true">💬 Сообщение</VButton>
        <VButton size="sm" variant="outline" @click="generateReport">📊 Отчёт</VButton>
        <VButton size="sm" variant="outline" @click="showArchiveConfirm = true" class="ml-auto">🗃️ Архив</VButton>
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
        <!-- Personal info grid -->
        <VCard title="📋 Основная информация">
            <div class="grid md:grid-cols-2 gap-x-8 gap-y-3">
                <div v-for="item in [
                    { label: '🎂 Дата рождения',    value: masterProfile.birthDate + ' (' + masterProfile.age + ' лет)' },
                    { label: '📱 Телефон',           value: masterProfile.phone },
                    { label: '✈️ Telegram',          value: masterProfile.telegram },
                    { label: '💬 WhatsApp',          value: masterProfile.whatsapp },
                    { label: '📧 Email',             value: masterProfile.email },
                    { label: '📅 Дата найма',        value: masterProfile.hireDate },
                    { label: '⏳ Стаж в салоне',     value: masterProfile.experience },
                    { label: '🏢 Филиалы',           value: masterProfile.salonIds?.join(', ') || masterProfile.salon },
                    { label: '🕐 График работы',     value: masterProfile.schedule },
                    { label: '🎖️ Уровень',          value: masterProfile.level },
                    { label: '💰 Комиссия',          value: masterProfile.commissionValue + (masterProfile.commissionType === 'percent' ? '%' : ' ₽') },
                ]" :key="item.label" class="flex items-start gap-2 py-1.5 border-b" style="border-color:var(--t-border)">
                    <span class="text-xs font-medium w-40 shrink-0" style="color:var(--t-text-3)">{{ item.label }}</span>
                    <span class="text-sm" style="color:var(--t-text)">{{ item.value }}</span>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ 4. SCHEDULE TAB ═══ -->
    <div v-if="activeTab === 'schedule'" class="space-y-4">
        <VCard title="📅 График и доступность (14 дней)">
            <div class="flex gap-1.5 flex-wrap">
                <div v-for="(d, i) in scheduleDays" :key="i"
                     class="flex flex-col items-center p-2 rounded-xl text-center transition-all cursor-default border"
                     :style="`width:60px;border-color:${d.isToday ? 'var(--t-primary)' : 'var(--t-border)'};background:${d.isWorking ? 'var(--t-surface)' : 'var(--t-bg)'}`">
                    <span class="text-[10px] uppercase font-semibold" :style="`color:${d.isToday ? 'var(--t-primary)' : 'var(--t-text-3)'}`">{{ d.dayName }}</span>
                    <span class="text-lg font-bold leading-tight" :style="`color:${d.isWorking ? 'var(--t-text)' : 'var(--t-text-3)'}`">{{ d.date }}</span>
                    <span class="text-[9px]" style="color:var(--t-text-3)">{{ d.month }}</span>
                    <div v-if="d.isWorking" class="w-full mt-1">
                        <div class="h-1.5 rounded-full" style="background:var(--t-bg)">
                            <div class="h-full rounded-full transition-all" :style="`width:${d.load}%;background:${d.loadColor}`"></div>
                        </div>
                        <span class="text-[9px] mt-0.5 block" :style="`color:${d.loadColor}`">{{ d.freeSlots }} свободно</span>
                    </div>
                    <span v-else class="text-[9px] mt-1" style="color:var(--t-text-3)">Выходной</span>
                </div>
            </div>
        </VCard>

        <!-- Time blocks -->
        <VCard title="🚫 Блокировка времени">
            <div class="space-y-2">
                <div v-for="b in timeBlocks" :key="b.id"
                     class="flex items-center gap-3 p-3 rounded-lg border"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-lg">{{ blockTypeLabels[b.type]?.charAt(0) || '📋' }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ b.reason }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">{{ b.from }}{{ b.from !== b.to ? ' — ' + b.to : '' }}</div>
                    </div>
                    <VBadge color="yellow" size="sm">{{ blockTypeLabels[b.type] }}</VBadge>
                </div>
                <VButton size="sm" variant="outline" class="w-full">➕ Добавить блокировку</VButton>
            </div>
        </VCard>
    </div>

    <!-- ═══ 5. SKILLS & SERVICES TAB ═══ -->
    <div v-if="activeTab === 'skills'" class="space-y-4">
        <!-- Services list -->
        <VCard title="💇 Услуги мастера">
            <div class="space-y-2">
                <div v-for="s in masterServices" :key="s.id"
                     class="flex items-center gap-3 p-3 rounded-xl border"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-xl">{{ s.icon }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium" style="color:var(--t-text)">{{ s.name }}</span>
                            <VBadge :color="serviceLevelColors[s.level]" size="sm">{{ serviceLevelLabels[s.level] }}</VBadge>
                        </div>
                        <div class="text-xs mt-0.5" style="color:var(--t-text-3)">⏱ {{ s.duration }} мин · {{ s.monthlyCount }} за мес.</div>
                    </div>
                    <span class="font-bold text-sm shrink-0" style="color:var(--t-primary)">{{ fmtMoney(s.price) }}</span>
                </div>
            </div>
        </VCard>

        <!-- Special skills -->
        <VCard title="🎯 Специальные навыки">
            <div class="flex flex-wrap gap-2">
                <span v-for="skill in specialSkills" :key="skill"
                      class="px-3 py-1.5 rounded-full text-xs font-medium border"
                      style="background:var(--t-primary-dim);color:var(--t-primary);border-color:var(--t-primary)">
                    {{ skill }}
                </span>
            </div>
        </VCard>

        <!-- Certificates -->
        <VCard title="🎓 Сертификаты и дипломы">
            <div class="grid md:grid-cols-2 gap-2">
                <div v-for="c in certificates" :key="c.id"
                     class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer hover:shadow-md transition-shadow"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-2xl">📜</span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate" style="color:var(--t-text)">{{ c.name }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">{{ c.date }}</div>
                    </div>
                    <span class="text-xs" style="color:var(--t-primary)">📎 Скачать</span>
                </div>
            </div>
        </VCard>

        <!-- Portfolio -->
        <VCard title="📸 Портфолио работ">
            <div class="grid grid-cols-3 md:grid-cols-6 gap-2">
                <div v-for="p in portfolio" :key="p.id"
                     class="aspect-square rounded-xl border flex flex-col items-center justify-center cursor-pointer hover:shadow-md transition-shadow overflow-hidden"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-2xl mb-1">📷</span>
                    <span class="text-[9px] text-center px-1 leading-tight" style="color:var(--t-text-3)">{{ p.label }}</span>
                    <VBadge v-if="p.type === 'before_after'" color="purple" size="sm" class="mt-0.5">До/После</VBadge>
                </div>
                <div class="aspect-square rounded-xl border-2 border-dashed flex items-center justify-center cursor-pointer hover:opacity-80 transition"
                     style="border-color:var(--t-border);color:var(--t-text-3)">
                    <span class="text-2xl">➕</span>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ 6. FINANCES TAB ═══ -->
    <div v-if="activeTab === 'finance'" class="space-y-4">
        <!-- Quick stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <VStatCard title="Выплачено (мес.)" :value="fmtMoney(financeStats.totalPayout)">
                <template #icon><span class="text-xl">💰</span></template>
            </VStatCard>
            <VStatCard title="Средний чек" :value="fmtMoney(financeStats.avgCheck)">
                <template #icon><span class="text-xl">🧾</span></template>
            </VStatCard>
            <VStatCard title="Услуг за мес." :value="String(financeStats.monthlyServices)">
                <template #icon><span class="text-xl">📋</span></template>
            </VStatCard>
            <VStatCard title="К выплате" :value="fmtMoney(financeStats.pendingPayout)">
                <template #icon><span class="text-xl">⏳</span></template>
            </VStatCard>
        </div>

        <!-- Payout history -->
        <VCard title="📊 История выплат">
            <div class="space-y-2">
                <div v-for="p in payoutHistory" :key="p.id"
                     class="flex items-center gap-4 p-3 rounded-xl border flex-wrap"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="min-w-[140px]">
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ p.period }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">Выплата: {{ p.paidDate }}</div>
                    </div>
                    <div class="text-xs text-center" style="color:var(--t-text-3)">
                        <div>Выручка</div>
                        <div class="font-bold" style="color:var(--t-text)">{{ fmtMoney(p.revenue) }}</div>
                    </div>
                    <div class="text-xs text-center" style="color:var(--t-text-3)">
                        <div>Комиссия</div>
                        <div class="font-bold" style="color:var(--t-primary)">{{ fmtMoney(p.commission) }}</div>
                    </div>
                    <div class="text-xs text-center" style="color:var(--t-text-3)">
                        <div>Бонус</div>
                        <div class="font-bold" style="color:var(--t-accent)">{{ fmtMoney(p.bonus) }}</div>
                    </div>
                    <div class="text-right flex-1">
                        <div class="font-bold" style="color:var(--t-text)">{{ fmtMoney(p.total) }}</div>
                        <VBadge :color="payoutStatusColors[p.status]" size="sm">{{ payoutStatusLabels[p.status] }}</VBadge>
                    </div>
                </div>
            </div>
        </VCard>

        <!-- Bonus program -->
        <VCard title="🎁 Бонусная программа">
            <div class="space-y-2">
                <div v-for="b in bonusProgram" :key="b.name"
                     class="flex items-center gap-3 p-3 rounded-lg border"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex-1">
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ b.name }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">{{ b.rule }}</div>
                    </div>
                    <span class="font-bold text-sm" style="color:var(--t-accent)">{{ b.bonus }}</span>
                    <VBadge color="green" size="sm">Активно</VBadge>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ 7. STATISTICS TAB ═══ -->
    <div v-if="activeTab === 'stats'" class="space-y-4">
        <!-- Performance metrics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div v-for="item in [
                { label: 'Записей/мес.', value: performanceStats.bookingsThisMonth, icon: '📋' },
                { label: 'Загрузка',      value: performanceStats.loadPercent + '%',  icon: '⏱️' },
                { label: 'Ср. время услуги', value: performanceStats.avgServiceTime + ' мин', icon: '⏰' },
                { label: 'Повторные клиенты', value: performanceStats.repeatRate + '%', icon: '🔄' },
                { label: 'Рейтинг',       value: performanceStats.rating,            icon: '⭐' },
                { label: 'Завершаемость',  value: performanceStats.completionRate + '%', icon: '✅' },
                { label: 'Допродажи',      value: performanceStats.upsellRate + '%',   icon: '📈' },
                { label: 'Неявки',         value: performanceStats.noShowRate + '%',   icon: '🚫' },
            ]" :key="item.label"
                 class="p-3 rounded-xl border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                <span class="text-xl">{{ item.icon }}</span>
                <div class="text-lg font-bold mt-1" style="color:var(--t-primary)">{{ item.value }}</div>
                <div class="text-[10px] uppercase" style="color:var(--t-text-3)">{{ item.label }}</div>
            </div>
        </div>

        <!-- Monthly chart (bar-like) -->
        <VCard title="📈 Динамика по месяцам">
            <div class="flex items-end gap-2 h-32">
                <div v-for="m in monthlyPerformance" :key="m.month"
                     class="flex-1 flex flex-col items-center gap-1">
                    <span class="text-[9px] font-bold" style="color:var(--t-text-2)">{{ fmt(m.revenue / 1000) }}к</span>
                    <div class="w-full rounded-t-lg transition-all"
                         :style="`height:${Math.round(m.revenue / 4500)};background:var(--t-primary);opacity:${0.4 + m.bookings / 200}`"></div>
                    <span class="text-[10px]" style="color:var(--t-text-3)">{{ m.month }}</span>
                </div>
            </div>
        </VCard>

        <!-- Top services -->
        <VCard title="🏆 Топ-услуги мастера">
            <div class="space-y-2">
                <div v-for="(s, i) in topServices" :key="s.id"
                     class="flex items-center gap-3 p-2 rounded-lg"
                     style="background:var(--t-bg)">
                    <span class="w-6 text-center font-bold text-sm" style="color:var(--t-primary)">#{{ i + 1 }}</span>
                    <span class="text-base">{{ s.icon }}</span>
                    <div class="flex-1">
                        <div class="text-sm" style="color:var(--t-text)">{{ s.name }}</div>
                    </div>
                    <span class="text-xs" style="color:var(--t-text-3)">{{ s.monthlyCount }} за мес.</span>
                    <span class="font-bold text-sm" style="color:var(--t-primary)">{{ fmtMoney(s.price) }}</span>
                </div>
            </div>
        </VCard>

        <!-- Comparison with others -->
        <VCard title="📊 Сравнение с другими мастерами">
            <div class="space-y-2">
                <div v-for="c in comparisonWithOthers" :key="c.name"
                     class="flex items-center gap-4 p-3 rounded-xl border flex-wrap"
                     :style="`background:${c.highlight ? 'var(--t-primary-dim)' : 'var(--t-bg)'};border-color:${c.highlight ? 'var(--t-primary)' : 'var(--t-border)'}`">
                    <div class="flex-1 min-w-[120px]">
                        <div class="text-sm font-medium" :style="`color:${c.highlight ? 'var(--t-primary)' : 'var(--t-text)'}`">
                            {{ c.name }} {{ c.highlight ? '← текущий' : '' }}
                        </div>
                    </div>
                    <div class="text-xs text-center">
                        <div style="color:var(--t-text-3)">Записей</div>
                        <div class="font-bold" style="color:var(--t-text)">{{ c.bookings }}</div>
                    </div>
                    <div class="text-xs text-center">
                        <div style="color:var(--t-text-3)">Ср. чек</div>
                        <div class="font-bold" style="color:var(--t-text)">{{ fmtMoney(c.avgCheck) }}</div>
                    </div>
                    <div class="text-xs text-center">
                        <div style="color:var(--t-text-3)">Рейтинг</div>
                        <div class="font-bold" style="color:var(--t-text)">⭐ {{ c.rating }}</div>
                    </div>
                    <div class="text-xs text-center">
                        <div style="color:var(--t-text-3)">Загрузка</div>
                        <div class="font-bold" :style="`color:${c.load >= 80 ? '#22c55e' : c.load >= 50 ? '#f59e0b' : '#ef4444'}`">{{ c.load }}%</div>
                    </div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ 8. REVIEWS TAB ═══ -->
    <div v-if="activeTab === 'reviews'" class="space-y-4">
        <!-- Rating summary -->
        <div class="flex flex-col md:flex-row gap-4">
            <div class="p-5 rounded-2xl border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="text-4xl font-bold" style="color:var(--t-primary)">{{ avgRating }}</div>
                <div class="flex justify-center gap-0.5 my-1">
                    <span v-for="s in 5" :key="s" class="text-lg">{{ s <= Math.round(Number(avgRating)) ? '⭐' : '☆' }}</span>
                </div>
                <div class="text-xs" style="color:var(--t-text-3)">{{ allReviews.length }} отзывов</div>
            </div>
            <div class="flex-1 space-y-1.5 py-2">
                <div v-for="r in ratingDistribution" :key="r.star" class="flex items-center gap-2">
                    <span class="text-xs w-8 text-right" style="color:var(--t-text-3)">{{ r.star }}⭐</span>
                    <div class="flex-1 h-3 rounded-full overflow-hidden" style="background:var(--t-bg)">
                        <div class="h-full rounded-full transition-all" :style="`width:${r.pct}%;background:var(--t-primary)`"></div>
                    </div>
                    <span class="text-xs w-6" style="color:var(--t-text-3)">{{ r.count }}</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex gap-2 flex-wrap items-center">
            <button v-for="star in [0, 5, 4, 3, 2, 1]" :key="star"
                    @click="reviewFilter.rating = star"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                    :style="reviewFilter.rating === star
                        ? 'background:var(--t-primary);color:#fff'
                        : 'background:var(--t-surface);color:var(--t-text-2)'">
                {{ star === 0 ? 'Все' : star + '⭐' }}
            </button>
            <select v-model="reviewFilter.service" class="px-2 py-1.5 rounded-lg text-xs border"
                    style="background:var(--t-bg);color:var(--t-text);border-color:var(--t-border)">
                <option value="">Все услуги</option>
                <option v-for="s in reviewServicesList" :key="s" :value="s">{{ s }}</option>
            </select>
        </div>

        <!-- Review cards -->
        <div class="space-y-3">
            <div v-for="r in filteredReviews" :key="r.id"
                 class="p-4 rounded-xl border" style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                             style="background:var(--t-primary-dim);color:var(--t-primary)">{{ r.client.charAt(0) }}</div>
                        <div>
                            <div class="text-sm font-medium" style="color:var(--t-text)">{{ r.client }}</div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">{{ r.service }} · {{ r.date }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-0.5">
                        <span v-for="s in 5" :key="s" class="text-sm">{{ s <= r.rating ? '⭐' : '☆' }}</span>
                    </div>
                </div>
                <p class="text-sm" style="color:var(--t-text)">{{ r.text }}</p>

                <!-- Reply -->
                <div v-if="r.reply" class="mt-3 p-3 rounded-lg border-l-2" style="background:var(--t-bg);border-color:var(--t-primary)">
                    <div class="text-[10px] uppercase mb-1" style="color:var(--t-text-3)">💇 Ответ салона</div>
                    <p class="text-sm" style="color:var(--t-text-2)">{{ r.reply }}</p>
                </div>

                <div class="flex gap-2 mt-3">
                    <VButton size="sm" variant="outline" @click="openReplyModal(r)">
                        {{ r.reply ? '✏️ Редактировать ответ' : '💬 Ответить' }}
                    </VButton>
                </div>
            </div>
        </div>

        <div v-if="!filteredReviews.length" class="text-center py-8">
            <div class="text-3xl mb-2">📭</div>
            <div class="text-sm" style="color:var(--t-text-3)">Нет отзывов по выбранным фильтрам</div>
        </div>
    </div>

    <!-- ═══ 9. HISTORY TAB ═══ -->
    <div v-if="activeTab === 'history'" class="space-y-4">
        <VCard title="🕐 История работы мастера">
            <div class="space-y-2">
                <div v-for="h in workHistory" :key="h.id"
                     class="p-3 rounded-xl border flex items-start gap-3 flex-wrap cursor-pointer hover:shadow-md transition-shadow"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <!-- Date -->
                    <div class="text-center min-w-[48px]">
                        <div class="text-sm font-bold" style="color:var(--t-primary)">{{ h.date.split('.')[0] }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ h.date.substring(3) }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">{{ h.time }}</div>
                    </div>
                    <div class="w-px h-12 rounded" style="background:var(--t-border)"></div>
                    <!-- Client & services -->
                    <div class="flex-1 min-w-[160px]">
                        <div class="text-sm font-medium" style="color:var(--t-text)">
                            {{ h.client }}
                            <span class="text-[10px] ml-1" style="color:var(--t-text-3)">#{{ h.clientId }}</span>
                        </div>
                        <div v-for="s in h.services" :key="s.name" class="text-xs mt-0.5" style="color:var(--t-text-2)">
                            · {{ s.name }} — {{ fmtMoney(s.price) }}
                        </div>
                        <div v-if="h.adminComment" class="mt-1 p-1.5 rounded text-[10px] italic border-l-2"
                             style="background:var(--t-surface);color:var(--t-text-3);border-color:var(--t-accent)">
                            🗒️ {{ h.adminComment }}
                        </div>
                    </div>
                    <!-- Total & status -->
                    <div class="text-right shrink-0">
                        <div class="font-bold text-sm" style="color:var(--t-text)">{{ fmtMoney(h.total) }}</div>
                        <VBadge :color="historyStatusColors[h.status]" size="sm">{{ historyStatusLabels[h.status] }}</VBadge>
                    </div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ 10. DOCUMENTS TAB ═══ -->
    <div v-if="activeTab === 'docs'" class="space-y-4">
        <!-- Documents list -->
        <VCard title="📁 Документы">
            <div class="space-y-2">
                <div v-for="d in documents" :key="d.id"
                     class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer hover:shadow-md transition-shadow"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-xl">{{ docTypeLabels[d.type]?.split(' ')[0] || '📄' }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate" style="color:var(--t-text)">{{ d.name }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">Загружен: {{ d.date }}</div>
                    </div>
                    <VBadge :color="d.status === 'active' ? 'green' : 'red'" size="sm">
                        {{ d.status === 'active' ? 'Действует' : 'Истёк' }}
                    </VBadge>
                    <span class="text-xs cursor-pointer" style="color:var(--t-primary)">📥 Скачать</span>
                </div>
                <VButton size="sm" variant="outline" class="w-full">📤 Загрузить документ</VButton>
            </div>
        </VCard>

        <!-- Access rights -->
        <VCard title="🔐 Права доступа в системе">
            <div class="space-y-2">
                <div v-for="a in accessRights" :key="a.name"
                     class="flex items-center justify-between p-2 rounded-lg border"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-sm" style="color:var(--t-text)">{{ a.name }}</span>
                    <div class="flex items-center gap-2">
                        <span :class="a.granted ? 'text-green-400' : 'text-red-400'" class="text-sm">
                            {{ a.granted ? '✅ Разрешено' : '🚫 Запрещено' }}
                        </span>
                        <button class="p-1 rounded hover:opacity-80 transition text-xs"
                                style="background:var(--t-surface);color:var(--t-text-2)"
                                @click="a.granted = !a.granted">🔄</button>
                    </div>
                </div>
            </div>
        </VCard>

        <!-- Certificates (from skills, displayed here too) -->
        <VCard title="🎓 Сертификаты">
            <div class="grid md:grid-cols-2 gap-2">
                <div v-for="c in certificates" :key="c.id"
                     class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer hover:shadow-md transition-shadow"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-2xl">📜</span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate" style="color:var(--t-text)">{{ c.name }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">{{ c.date }}</div>
                    </div>
                    <span class="text-xs" style="color:var(--t-primary)">📎</span>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ MODALS ═══ -->
    <!-- Bonus Modal -->
    <VModal :show="showBonusModal" @close="showBonusModal = false" title="💎 Начислить бонус / комиссию">
        <div class="space-y-3">
            <div class="text-sm" style="color:var(--t-text-2)">Мастер: <strong style="color:var(--t-text)">{{ masterProfile.name }}</strong></div>
            <VInput v-model="bonusAmount" type="number" placeholder="Сумма, ₽" />
            <VInput v-model="bonusReason" placeholder="Причина (обязательно)" />
        </div>
        <template #footer>
            <VButton variant="outline" @click="showBonusModal = false">Отмена</VButton>
            <VButton @click="awardBonus" :disabled="!bonusAmount || !bonusReason">💎 Начислить</VButton>
        </template>
    </VModal>

    <!-- Message Modal -->
    <VModal :show="showMessageModal" @close="showMessageModal = false" title="💬 Сообщение мастеру">
        <div class="space-y-3">
            <div class="text-sm" style="color:var(--t-text-2)">Кому: <strong style="color:var(--t-text)">{{ masterProfile.name }}</strong></div>
            <textarea v-model="messageText" rows="4" placeholder="Текст сообщения..."
                      class="w-full p-3 rounded-xl border text-sm resize-none"
                      style="background:var(--t-bg);color:var(--t-text);border-color:var(--t-border)"></textarea>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showMessageModal = false">Отмена</VButton>
            <VButton @click="sendMessage" :disabled="!messageText.trim()">📤 Отправить</VButton>
        </template>
    </VModal>

    <!-- Reply Modal -->
    <VModal :show="showReplyModal" @close="showReplyModal = false" title="💬 Ответ на отзыв">
        <div class="space-y-3">
            <div v-if="replyTarget" class="p-3 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                <div class="flex items-center gap-1 mb-1">
                    <span v-for="s in 5" :key="s" class="text-sm">{{ s <= replyTarget.rating ? '⭐' : '☆' }}</span>
                    <span class="ml-2 text-xs" style="color:var(--t-text-3)">{{ replyTarget.client }} · {{ replyTarget.date }}</span>
                </div>
                <p class="text-sm" style="color:var(--t-text)">{{ replyTarget.text }}</p>
            </div>
            <textarea v-model="replyText" rows="3" placeholder="Ваш ответ от лица салона..."
                      class="w-full p-3 rounded-xl border text-sm resize-none"
                      style="background:var(--t-bg);color:var(--t-text);border-color:var(--t-border)"></textarea>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showReplyModal = false">Отмена</VButton>
            <VButton @click="submitReply" :disabled="!replyText.trim()">✅ Сохранить ответ</VButton>
        </template>
    </VModal>

    <!-- Archive Confirm -->
    <VModal :show="showArchiveConfirm" @close="showArchiveConfirm = false" title="🗃️ Архивировать мастера?">
        <div class="space-y-3">
            <p class="text-sm" style="color:var(--t-text)">
                Вы собираетесь деактивировать мастера <strong>{{ masterProfile.name }}</strong>.
            </p>
            <p class="text-sm" style="color:var(--t-text-2)">
                Все текущие записи будут перенесены или отменены.
                Мастер не сможет принимать новые записи.
            </p>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showArchiveConfirm = false">Отмена</VButton>
            <VButton @click="archiveMaster" style="background:#ef4444;color:#fff">🚫 Деактивировать</VButton>
        </template>
    </VModal>
</div>
</template>
