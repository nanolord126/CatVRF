<script setup>
/**
 * BeautyReviews — полное управление отзывами.
 * 6 табов: обзор, все отзывы, модерация, ответы, аналитика, запросы.
 * 5 тем (mint/day/night/sunset/lavender) через CSS custom properties.
 */
import { ref, computed, reactive, inject } from 'vue';
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
});
const emit = defineEmits([
    'open-client', 'open-master', 'reply-review', 'delete-review',
    'request-review', 'export-report', 'flag-review',
]);

const t = inject('theme', {
    bg: 'var(--t-bg)', surface: 'var(--t-surface)', border: 'var(--t-border)',
    primary: 'var(--t-primary)', primaryDim: 'var(--t-primary-dim)',
    accent: 'var(--t-accent)', text: 'var(--t-text)', text2: 'var(--t-text-2)',
    text3: 'var(--t-text-3)', cardHover: 'var(--t-card-hover)',
});

/* ─── Tabs ─── */
const tabs = [
    { key: 'overview',    label: '📊 Обзор' },
    { key: 'all',         label: '📝 Все отзывы' },
    { key: 'moderation',  label: '🔍 Модерация' },
    { key: 'replies',     label: '💬 Ответы' },
    { key: 'analytics',   label: '📈 Аналитика' },
    { key: 'requests',    label: '📧 Запросы' },
];
const activeTab = ref('overview');

/* ─── Stats ─── */
const reviewStats = ref([
    { label: 'Средний рейтинг', value: '4.78', trend: '+0.05', icon: '⭐' },
    { label: 'Всего отзывов', value: '1 842', trend: '+48', icon: '📝' },
    { label: 'На модерации', value: '7', trend: '', icon: '🔍' },
    { label: 'Без ответа', value: '12', trend: '-3', icon: '💬' },
    { label: 'Отзывов за месяц', value: '186', trend: '+22%', icon: '📈' },
    { label: 'Положительных (%)', value: '94%', trend: '+2%', icon: '👍' },
]);

/* ─── Reviews Data ─── */
const reviews = ref([
    { id: 1, client: 'Мария К.', rating: 5, text: 'Отличная стрижка! Анна — лучший мастер. Обязательно вернусь!', master: 'Анна Соколова', salon: 'BeautyLab Центр', service: 'Стрижка + укладка', date: '08.04.2026', status: 'published', hasReply: true, reply: 'Спасибо, Мария! Ждём вас снова ❤️', photos: 2 },
    { id: 2, client: 'Елена П.', rating: 4, text: 'Хороший маникюр, но пришлось подождать 15 минут. В целом довольна.', master: 'Ольга Демидова', salon: 'BeautyLab Центр', service: 'Маникюр гель-лак', date: '08.04.2026', status: 'published', hasReply: false, reply: '', photos: 1 },
    { id: 3, client: 'Татьяна С.', rating: 5, text: 'Лучшее окрашивание в городе! AirTouch получился идеально. Рекомендую всем!', master: 'Анна Соколова', salon: 'BeautyLab Центр', service: 'Окрашивание AirTouch', date: '07.04.2026', status: 'published', hasReply: true, reply: 'Благодарим за тёплые слова, Татьяна! 🌸', photos: 3 },
    { id: 4, client: 'Ирина М.', rating: 3, text: 'Массаж был неплохой, но ожидала большего. Мастер торопился.', master: 'Светлана Романова', salon: 'BeautyLab Центр', service: 'Массаж лица', date: '07.04.2026', status: 'published', hasReply: false, reply: '', photos: 0 },
    { id: 5, client: 'Наталья Б.', rating: 5, text: 'Потрясающие брови! Форма идеальная, ламинирование отлично легло.', master: 'Кристина Лебедева', salon: 'BeautyLab Центр', service: 'Брови + ламинирование', date: '06.04.2026', status: 'published', hasReply: true, reply: 'Рады, что вам понравилось! Ждём на коррекцию 😊', photos: 1 },
    { id: 6, client: 'Ольга Р.', rating: 2, text: 'Разочарована. Цвет получился не таким, как обсуждали. Придётся перекрашиваться.', master: 'Анна Соколова', salon: 'BeautyLab Север', service: 'Окрашивание', date: '05.04.2026', status: 'published', hasReply: false, reply: '', photos: 0 },
    { id: 7, client: 'Спам-аккаунт', rating: 1, text: 'Купите рекламу на нашем сайте! Скидки и акции!', master: '', salon: '', service: '', date: '05.04.2026', status: 'pending', hasReply: false, reply: '', photos: 0 },
    { id: 8, client: 'Алла Ш.', rating: 5, text: 'SPA-процедура была великолепной! Чувствую себя обновлённой.', master: 'Светлана Романова', salon: 'BeautyLab SPA', service: 'SPA-комплекс', date: '04.04.2026', status: 'pending', hasReply: false, reply: '', photos: 2 },
]);

const pendingReviews = computed(() => reviews.value.filter(r => r.status === 'pending'));
const publishedReviews = computed(() => reviews.value.filter(r => r.status === 'published'));
const unrepliedReviews = computed(() => publishedReviews.value.filter(r => !r.hasReply));
const negativeReviews = computed(() => publishedReviews.value.filter(r => r.rating <= 2));

/* ─── Rating distribution ─── */
const ratingDistribution = computed(() => {
    const dist = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
    publishedReviews.value.forEach(r => { dist[r.rating] = (dist[r.rating] || 0) + 1; });
    return dist;
});
const totalPublished = computed(() => publishedReviews.value.length);
const avgRating = computed(() => {
    if (!totalPublished.value) return 0;
    return (publishedReviews.value.reduce((s, r) => s + r.rating, 0) / totalPublished.value).toFixed(2);
});

/* ─── Master ratings ─── */
const masterRatings = computed(() => {
    const map = {};
    publishedReviews.value.forEach(r => {
        if (!r.master) return;
        if (!map[r.master]) map[r.master] = { name: r.master, total: 0, sum: 0, count5: 0, count1: 0 };
        map[r.master].total++;
        map[r.master].sum += r.rating;
        if (r.rating === 5) map[r.master].count5++;
        if (r.rating <= 2) map[r.master].count1++;
    });
    return Object.values(map).map(m => ({ ...m, avg: (m.sum / m.total).toFixed(2) })).sort((a, b) => b.avg - a.avg);
});

/* ─── Filters ─── */
const filterRating = ref(0);
const filterMaster = ref('');
const filterStatus = ref('');
const filteredReviews = computed(() => {
    return reviews.value.filter(r => {
        if (filterRating.value && r.rating !== filterRating.value) return false;
        if (filterMaster.value && r.master !== filterMaster.value) return false;
        if (filterStatus.value && r.status !== filterStatus.value) return false;
        return true;
    });
});

/* ─── Reply ─── */
const showReplyModal = ref(false);
const replyTarget = ref(null);
const replyText = ref('');
function openReply(review) { replyTarget.value = review; replyText.value = review.reply || ''; showReplyModal.value = true; }
function sendReply() {
    if (!replyTarget.value || !replyText.value.trim()) return;
    replyTarget.value.reply = replyText.value.trim();
    replyTarget.value.hasReply = true;
    emit('reply-review', { reviewId: replyTarget.value.id, reply: replyText.value.trim() });
    showReplyModal.value = false;
    toast('Ответ опубликован');
}

/* ─── Moderation ─── */
function approveReview(review) { review.status = 'published'; toast(`Отзыв от «${review.client}» одобрен`); }
function rejectReview(review) { review.status = 'rejected'; toast(`Отзыв от «${review.client}» отклонён`); }
function flagReview(review) { emit('flag-review', review); toast(`Отзыв от «${review.client}» отмечен как подозрительный`); }

/* ─── Request Reviews ─── */
const requestTemplates = ref([
    { id: 1, name: 'После визита (SMS)', channel: 'sms', template: 'Здравствуйте, {name}! Спасибо за визит в {salon}. Будем рады вашему отзыву: {link}', isActive: true, sent: 1240 },
    { id: 2, name: 'После визита (Push)', channel: 'push', template: 'Как вам визит? 🌸 Оставьте отзыв и получите 200 бонусов!', isActive: true, sent: 890 },
    { id: 3, name: 'Напоминание (Email)', channel: 'email', template: 'Добрый день, {name}! Мы заметили, что вы ещё не оставили отзыв о визите {date}...', isActive: false, sent: 420 },
]);
const showRequestModal = ref(false);
function sendReviewRequest() { emit('request-review', { template: 'default' }); showRequestModal.value = false; toast('Запрос отзыва отправлен'); }

/* ─── Monthly Analytics ─── */
const monthlyReviews = ref([
    { month: 'Янв', count: 140, avgRating: 4.6, positive: 88, negative: 8, responseRate: 72 },
    { month: 'Фев', count: 155, avgRating: 4.7, positive: 91, negative: 5, responseRate: 78 },
    { month: 'Мар', count: 172, avgRating: 4.75, positive: 93, negative: 4, responseRate: 85 },
    { month: 'Апр', count: 186, avgRating: 4.78, positive: 94, negative: 3, responseRate: 89 },
]);

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
const ratingStars = (n) => '★'.repeat(n) + '☆'.repeat(5 - n);
const ratingColor = (n) => n >= 4 ? 'green' : n === 3 ? 'yellow' : 'red';
</script>

<template>
<div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-xl font-bold" style="color:var(--t-text)">⭐ Управление отзывами</h1>
        <div class="flex gap-2">
            <VButton size="sm" variant="outline" @click="exportCSV(publishedReviews, 'reviews')">📥 Экспорт</VButton>
            <VButton size="sm" @click="showRequestModal = true">📧 Запросить отзыв</VButton>
        </div>
    </div>

    <VTabs :tabs="tabs" v-model="activeTab" />

    <!-- ═══ OVERVIEW ═══ -->
    <div v-if="activeTab === 'overview'" class="space-y-4">
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <VStatCard v-for="s in reviewStats" :key="s.label" :label="s.label" :value="s.value" :trend="s.trend" :icon="s.icon" />
        </div>
        <div class="grid lg:grid-cols-2 gap-4">
            <VCard title="📊 Распределение оценок">
                <div class="space-y-2">
                    <div v-for="star in [5,4,3,2,1]" :key="star" class="flex items-center gap-3">
                        <span class="w-8 text-sm font-bold" style="color:var(--t-text)">{{ star }}★</span>
                        <div class="flex-1 h-4 rounded-full overflow-hidden" style="background:var(--t-bg)">
                            <div class="h-full rounded-full transition-all" :style="{ width: totalPublished ? ((ratingDistribution[star] / totalPublished) * 100) + '%' : '0%', background: star >= 4 ? 'var(--t-primary)' : star === 3 ? '#eab308' : '#ef4444' }"></div>
                        </div>
                        <span class="w-8 text-right text-sm font-bold" style="color:var(--t-text-2)">{{ ratingDistribution[star] }}</span>
                    </div>
                </div>
            </VCard>
            <VCard title="👤 Рейтинг по мастерам">
                <div class="space-y-2">
                    <div v-for="m in masterRatings" :key="m.name" class="flex items-center gap-3 p-2 rounded-lg cursor-pointer" style="background:var(--t-bg)" @click="emit('open-master', m)">
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm truncate" style="color:var(--t-text)">{{ m.name }}</div>
                            <div class="text-xs" style="color:var(--t-text-3)">{{ m.total }} отзывов</div>
                        </div>
                        <span class="font-bold text-sm" style="color:var(--t-primary)">⭐ {{ m.avg }}</span>
                    </div>
                </div>
            </VCard>
        </div>
        <!-- Recent negative -->
        <VCard v-if="negativeReviews.length" title="⚠️ Негативные отзывы (требуют внимания)">
            <div class="space-y-2">
                <div v-for="nr in negativeReviews" :key="nr.id" class="p-3 rounded-lg border border-red-500/30" style="background:var(--t-bg)">
                    <div class="flex items-start gap-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-sm" style="color:var(--t-text)">{{ nr.client }}</span>
                                <span class="text-red-400 text-xs">{{ ratingStars(nr.rating) }}</span>
                            </div>
                            <div class="text-sm" style="color:var(--t-text-2)">{{ nr.text }}</div>
                            <div class="text-xs mt-1" style="color:var(--t-text-3)">{{ nr.master }} · {{ nr.date }}</div>
                        </div>
                        <VButton v-if="!nr.hasReply" size="sm" @click="openReply(nr)">💬 Ответить</VButton>
                    </div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ ALL REVIEWS ═══ -->
    <div v-if="activeTab === 'all'" class="space-y-4">
        <div class="flex gap-3 flex-wrap">
            <select v-model.number="filterRating" class="rounded-lg px-3 py-2 border text-sm" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                <option :value="0">Все оценки</option>
                <option v-for="s in [5,4,3,2,1]" :key="s" :value="s">{{ s }} ★</option>
            </select>
            <select v-model="filterMaster" class="rounded-lg px-3 py-2 border text-sm" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                <option value="">Все мастера</option>
                <option v-for="m in props.masters" :key="m.id" :value="m.name">{{ m.name }}</option>
            </select>
            <select v-model="filterStatus" class="rounded-lg px-3 py-2 border text-sm" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                <option value="">Все статусы</option>
                <option value="published">Опубликован</option>
                <option value="pending">На модерации</option>
                <option value="rejected">Отклонён</option>
            </select>
        </div>
        <div class="space-y-3">
            <div v-for="rv in filteredReviews" :key="rv.id" class="p-4 rounded-xl border" style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="flex items-start gap-3 flex-wrap">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold" style="background:var(--t-primary-dim);color:var(--t-primary)">{{ rv.client.charAt(0) }}</div>
                    <div class="flex-1 min-w-[200px]">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="font-bold text-sm cursor-pointer" style="color:var(--t-text)" @click="emit('open-client', rv)">{{ rv.client }}</span>
                            <span :class="rv.rating >= 4 ? 'text-yellow-400' : rv.rating === 3 ? 'text-yellow-600' : 'text-red-400'" class="text-sm">{{ ratingStars(rv.rating) }}</span>
                            <VBadge :color="rv.status === 'published' ? 'green' : rv.status === 'pending' ? 'yellow' : 'red'" size="sm">{{ rv.status === 'published' ? 'Опубликован' : rv.status === 'pending' ? 'Модерация' : 'Отклонён' }}</VBadge>
                        </div>
                        <div class="text-sm mb-2" style="color:var(--t-text-2)">{{ rv.text }}</div>
                        <div class="flex items-center gap-3 text-xs" style="color:var(--t-text-3)">
                            <span v-if="rv.master">👤 {{ rv.master }}</span>
                            <span v-if="rv.salon">🏪 {{ rv.salon }}</span>
                            <span v-if="rv.service">✂️ {{ rv.service }}</span>
                            <span>📅 {{ rv.date }}</span>
                            <span v-if="rv.photos">📸 {{ rv.photos }} фото</span>
                        </div>
                        <!-- Reply -->
                        <div v-if="rv.hasReply" class="mt-3 p-3 rounded-lg" style="background:var(--t-bg)">
                            <div class="text-xs font-bold mb-1" style="color:var(--t-primary)">💬 Ответ салона:</div>
                            <div class="text-sm" style="color:var(--t-text-2)">{{ rv.reply }}</div>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <VButton v-if="!rv.hasReply && rv.status === 'published'" size="sm" @click="openReply(rv)">💬</VButton>
                        <VButton size="sm" variant="outline" @click="flagReview(rv)">🚩</VButton>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ MODERATION ═══ -->
    <div v-if="activeTab === 'moderation'" class="space-y-4">
        <h2 class="text-lg font-semibold" style="color:var(--t-text)">На модерации ({{ pendingReviews.length }})</h2>
        <div v-if="!pendingReviews.length" class="p-8 text-center rounded-xl" style="background:var(--t-surface);color:var(--t-text-3)">
            ✅ Все отзывы проверены — нет ожидающих модерации
        </div>
        <div v-for="rv in pendingReviews" :key="rv.id" class="p-4 rounded-xl border" style="background:var(--t-surface);border-color:var(--t-border)">
            <div class="flex items-start gap-3 flex-wrap">
                <div class="flex-1 min-w-[200px]">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-bold text-sm" style="color:var(--t-text)">{{ rv.client }}</span>
                        <span class="text-sm" :class="rv.rating >= 4 ? 'text-yellow-400' : 'text-red-400'">{{ ratingStars(rv.rating) }}</span>
                    </div>
                    <div class="text-sm" style="color:var(--t-text-2)">{{ rv.text }}</div>
                    <div class="text-xs mt-2" style="color:var(--t-text-3)">
                        <span v-if="rv.master">👤 {{ rv.master }}</span>
                        <span v-if="rv.salon"> · 🏪 {{ rv.salon }}</span>
                        <span> · 📅 {{ rv.date }}</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <VButton size="sm" @click="approveReview(rv)">✅ Одобрить</VButton>
                    <VButton size="sm" variant="outline" @click="rejectReview(rv)">❌ Отклонить</VButton>
                    <VButton size="sm" variant="outline" @click="flagReview(rv)">🚩 Спам</VButton>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ REPLIES ═══ -->
    <div v-if="activeTab === 'replies'" class="space-y-4">
        <h2 class="text-lg font-semibold" style="color:var(--t-text)">Без ответа ({{ unrepliedReviews.length }})</h2>
        <div v-if="!unrepliedReviews.length" class="p-8 text-center rounded-xl" style="background:var(--t-surface);color:var(--t-text-3)">
            ✅ Все отзывы имеют ответы
        </div>
        <div v-for="rv in unrepliedReviews" :key="rv.id" class="p-4 rounded-xl border" style="background:var(--t-surface);border-color:var(--t-border)">
            <div class="flex items-start gap-3 flex-wrap">
                <div class="flex-1 min-w-[200px]">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-bold text-sm" style="color:var(--t-text)">{{ rv.client }}</span>
                        <span class="text-sm" :class="rv.rating >= 4 ? 'text-yellow-400' : rv.rating === 3 ? 'text-yellow-600' : 'text-red-400'">{{ ratingStars(rv.rating) }}</span>
                    </div>
                    <div class="text-sm" style="color:var(--t-text-2)">{{ rv.text }}</div>
                    <div class="text-xs mt-1" style="color:var(--t-text-3)">{{ rv.master }} · {{ rv.date }}</div>
                </div>
                <VButton size="sm" @click="openReply(rv)">💬 Ответить</VButton>
            </div>
        </div>
    </div>

    <!-- ═══ ANALYTICS ═══ -->
    <div v-if="activeTab === 'analytics'" class="space-y-4">
        <VCard title="📈 Тренды по месяцам">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="border-bottom:2px solid var(--t-border)">
                            <th class="text-left p-2" style="color:var(--t-text-3)">Месяц</th>
                            <th class="text-right p-2" style="color:var(--t-text-3)">Отзывов</th>
                            <th class="text-right p-2" style="color:var(--t-text-3)">Ср. рейтинг</th>
                            <th class="text-right p-2" style="color:var(--t-text-3)">Положит. (%)</th>
                            <th class="text-right p-2" style="color:var(--t-text-3)">Негатив (%)</th>
                            <th class="text-right p-2" style="color:var(--t-text-3)">Ответы (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="d in monthlyReviews" :key="d.month" :style="{ borderBottom: '1px solid var(--t-border)' }">
                            <td class="p-2 font-medium" style="color:var(--t-text)">{{ d.month }}</td>
                            <td class="p-2 text-right" style="color:var(--t-text-2)">{{ d.count }}</td>
                            <td class="p-2 text-right font-bold" style="color:var(--t-primary)">⭐ {{ d.avgRating }}</td>
                            <td class="p-2 text-right text-green-400">{{ d.positive }}%</td>
                            <td class="p-2 text-right text-red-400">{{ d.negative }}%</td>
                            <td class="p-2 text-right" style="color:var(--t-primary)">{{ d.responseRate }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>
        <div class="flex justify-end">
            <VButton variant="outline" @click="exportCSV(monthlyReviews, 'reviews_analytics')">📥 Экспорт аналитики</VButton>
        </div>
    </div>

    <!-- ═══ REQUESTS ═══ -->
    <div v-if="activeTab === 'requests'" class="space-y-4">
        <h2 class="text-lg font-semibold" style="color:var(--t-text)">Шаблоны запроса отзывов</h2>
        <div class="space-y-3">
            <div v-for="tpl in requestTemplates" :key="tpl.id" class="p-4 rounded-xl border" style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="flex items-start gap-3 flex-wrap">
                    <div class="flex-1 min-w-[200px]">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-bold text-sm" style="color:var(--t-text)">{{ tpl.name }}</span>
                            <VBadge :color="tpl.channel === 'sms' ? 'blue' : tpl.channel === 'push' ? 'green' : 'purple'" size="sm">{{ tpl.channel.toUpperCase() }}</VBadge>
                        </div>
                        <div class="text-sm p-2 rounded-lg mt-1 font-mono" style="background:var(--t-bg);color:var(--t-text-2)">{{ tpl.template }}</div>
                        <div class="text-xs mt-2" style="color:var(--t-text-3)">Отправлено: {{ fmt(tpl.sent) }} раз</div>
                    </div>
                    <div class="flex gap-2 items-center">
                        <label class="flex items-center gap-1 cursor-pointer">
                            <input type="checkbox" v-model="tpl.isActive" class="w-5 h-5 accent-(--t-primary)">
                            <span class="text-xs" :style="{ color: tpl.isActive ? 'var(--t-primary)' : 'var(--t-text-3)' }">{{ tpl.isActive ? 'Активен' : 'Выкл.' }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <VButton @click="showRequestModal = true">➕ Новый шаблон</VButton>
    </div>

    <!-- ═══ MODALS ═══ -->
    <VModal :show="showReplyModal" @close="showReplyModal = false" title="💬 Ответ на отзыв">
        <div v-if="replyTarget" class="space-y-4">
            <div class="p-3 rounded-lg" style="background:var(--t-bg)">
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-bold text-sm" style="color:var(--t-text)">{{ replyTarget.client }}</span>
                    <span class="text-sm text-yellow-400">{{ ratingStars(replyTarget.rating) }}</span>
                </div>
                <div class="text-sm" style="color:var(--t-text-2)">{{ replyTarget.text }}</div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Ваш ответ</label>
                <textarea v-model="replyText" rows="4" class="w-full rounded-lg px-3 py-2 border text-sm resize-none" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)" placeholder="Спасибо за отзыв..."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <VButton variant="outline" @click="showReplyModal = false">Отмена</VButton>
                <VButton @click="sendReply">💬 Опубликовать ответ</VButton>
            </div>
        </div>
    </VModal>

    <VModal :show="showRequestModal" @close="showRequestModal = false" title="📧 Отправить запрос отзыва">
        <div class="space-y-4">
            <VInput label="Клиент" placeholder="Имя или телефон клиента..." />
            <div>
                <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Канал</label>
                <select class="w-full rounded-lg px-3 py-2 border text-sm" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                    <option value="sms">SMS</option>
                    <option value="push">Push</option>
                    <option value="email">Email</option>
                </select>
            </div>
            <div class="flex justify-end gap-3">
                <VButton variant="outline" @click="showRequestModal = false">Отмена</VButton>
                <VButton @click="sendReviewRequest">📧 Отправить</VButton>
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
