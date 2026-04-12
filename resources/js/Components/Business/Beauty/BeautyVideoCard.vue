<script setup>
/**
 * BeautyVideoCard — полная карточка видео / шортса.
 * Детальная аналитика: просмотры, удержание, воронка конверсии,
 * аудитория, география, A/B-тесты, динамика по дням.
 * Получает video через props, эмитит @close наверх.
 */
import { ref, computed } from 'vue';
import VButton from '../../UI/VButton.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import VStatCard from '../../UI/VStatCard.vue';

const props = defineProps({
    video: { type: Object, required: true },
});

const emit = defineEmits(['close', 'boost', 'duplicate', 'archive']);

/* ─── Helpers ─── */
function fmtMoney(n) { return new Intl.NumberFormat('ru-RU').format(n) + ' ₽'; }
function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }

/* ═══════════════ TABS ═══════════════ */
const tabs = [
    { key: 'overview',   label: '📊 Обзор' },
    { key: 'retention',  label: '📉 Удержание' },
    { key: 'funnel',     label: '🔄 Воронка' },
    { key: 'audience',   label: '👥 Аудитория' },
    { key: 'dynamics',   label: '📈 Динамика' },
    { key: 'ab',         label: '🧪 A/B тест' },
];
const activeTab = ref('overview');

/* ═══════════════ KPI ═══════════════ */
const kpis = computed(() => {
    const v = props.video;
    return [
        { label: 'Просмотры',      value: fmt(v.views),            icon: '👁', color: 'var(--t-text)' },
        { label: 'Досмотры',       value: v.completion + '%',      icon: '⏱',  color: v.completion >= 65 ? '#22c55e' : '#f59e0b' },
        { label: 'Клики',          value: fmt(v.clicks),           icon: '🖱',  color: 'var(--t-text)' },
        { label: 'Записи',         value: v.bookings,              icon: '✅', color: '#22c55e' },
        { label: 'Выручка',        value: fmtMoney(v.revenue),     icon: '💰', color: 'var(--t-primary)' },
        { label: 'ROI',            value: v.roi + '%',             icon: '📈', color: v.roi >= 300 ? '#22c55e' : '#f59e0b' },
        { label: 'CTR',            value: v.views > 0 ? ((v.clicks / v.views) * 100).toFixed(2) + '%' : '—', icon: '🎯', color: 'var(--t-primary)' },
        { label: 'CPV',            value: v.views > 0 ? fmtMoney(Math.round(v.revenue / v.views * 100) / 100) : '—', icon: '💳', color: 'var(--t-text-2)' },
        { label: 'Лайки',          value: fmt(v.likes || 0),       icon: '❤️', color: 'var(--t-text)' },
        { label: 'Комментарии',    value: fmt(v.comments || 0),    icon: '💬', color: 'var(--t-text)' },
        { label: 'Шеры',           value: fmt(v.shares || 0),      icon: '📤', color: 'var(--t-text)' },
        { label: 'Сохранения',     value: fmt(v.saves || 0),       icon: '🔖', color: 'var(--t-text)' },
    ];
});

/* ═══════════════ RETENTION CURVE ═══════════════ */
const retentionPoints = ref([
    { second: 0,  pct: 100 }, { second: 3,  pct: 92 },
    { second: 5,  pct: 84 },  { second: 10, pct: 72 },
    { second: 15, pct: 65 },  { second: 20, pct: 58 },
    { second: 25, pct: 48 },  { second: 30, pct: 42 },
    { second: 45, pct: 28 },  { second: 60, pct: 18 },
]);
const retentionInsights = ref({
    dropOff1: { second: 3, reason: 'Первые 3 секунды: потеря 8%' },
    dropOff2: { second: 15, reason: 'Переход на другой контент' },
    avgWatchTime: 22,
    benchmark: 18,
});

/* ═══════════════ CONVERSION FUNNEL ═══════════════ */
const funnel = computed(() => {
    const v = props.video;
    return [
        { step: 'Показы в ленте',   value: Math.round(v.views * 3.2),   pct: 100 },
        { step: 'Просмотры',        value: v.views,                      pct: 31.2 },
        { step: 'Досмотры (50%+)',   value: Math.round(v.views * v.completion / 100), pct: v.completion / 3.2 },
        { step: 'Клики по ссылке',   value: v.clicks,                    pct: (v.clicks / v.views * 100 / 3.2) },
        { step: 'Переход на сайт',   value: Math.round(v.clicks * 0.72), pct: (v.clicks * 0.72 / v.views * 100 / 3.2) },
        { step: 'Начал запись',      value: Math.round(v.bookings * 1.4), pct: (v.bookings * 1.4 / v.views * 100 / 3.2) },
        { step: 'Оформил запись',    value: v.bookings,                   pct: (v.bookings / v.views * 100 / 3.2) },
    ];
});
const maxFunnel = computed(() => funnel.value[0]?.value || 1);

/* ═══════════════ AUDIENCE ═══════════════ */
const videoAudienceGeo = ref([
    { city: 'Москва', pct: 45 }, { city: 'Санкт-Петербург', pct: 18 },
    { city: 'Казань', pct: 8 }, { city: 'Краснодар', pct: 7 },
    { city: 'Другие', pct: 22 },
]);
const videoAudienceAge = ref([
    { group: '13–17', pct: 8 }, { group: '18–24', pct: 28 },
    { group: '25–34', pct: 38 }, { group: '35–44', pct: 18 },
    { group: '45+', pct: 8 },
]);
const videoGender = ref({ female: 82, male: 18 });
const videoTrafficSources = ref([
    { source: 'Рекомендации',    pct: 62, icon: '🔮' },
    { source: 'Подписки',        pct: 18, icon: '👥' },
    { source: 'Хэштеги',        pct: 9,  icon: '#️⃣' },
    { source: 'Внешние ссылки',  pct: 7,  icon: '🔗' },
    { source: 'Поиск',          pct: 4,  icon: '🔍' },
]);

/* ═══════════════ DAILY DYNAMICS ═══════════════ */
const dailyDynamics = ref([
    { date: '10.03', views: 1200, likes: 98,  comments: 12, bookings: 0 },
    { date: '11.03', views: 3400, likes: 280, comments: 34, bookings: 1 },
    { date: '12.03', views: 8200, likes: 620, comments: 78, bookings: 3 },
    { date: '13.03', views: 5600, likes: 440, comments: 52, bookings: 2 },
    { date: '14.03', views: 3800, likes: 310, comments: 38, bookings: 1 },
    { date: '15.03', views: 2200, likes: 180, comments: 22, bookings: 0 },
    { date: '16.03', views: 1600, likes: 120, comments: 16, bookings: 0 },
    { date: '17.03', views: 1100, likes: 90,  comments: 10, bookings: 0 },
]);
const maxViews = computed(() => Math.max(...dailyDynamics.value.map(d => d.views)));

/* ═══════════════ A/B TEST ═══════════════ */
const abVariants = ref([
    { name: 'Вариант A (текущий)',  thumbnail: '🎬', hook: 'Прямой заход — «Хочешь такой маникюр?»', views: 14200, completion: 68, clicks: 420, bookings: 6, winner: true },
    { name: 'Вариант B',           thumbnail: '🎬', hook: 'Сторителлинг — «Моя клиентка хотела...»', views: 12800, completion: 74, clicks: 360, bookings: 4, winner: false },
    { name: 'Вариант C',           thumbnail: '🎬', hook: 'Тренд — звук + быстрая смена кадров',     views: 18400, completion: 55, clicks: 510, bookings: 5, winner: false },
]);

/* ═══════════════ AI INSIGHTS ═══════════════ */
const aiVideoInsight = ref({
    viralScore: 7.2,
    bestMoment: '0:05 — пиковое внимание',
    recommendation: 'Первые 3 секунды удерживают 92% — это отличный результат. Рекомендуется добавить CTA (призыв к действию) на 15-й секунде, когда удержание ещё 65%. Формат "до/после" показывает на 34% лучший ROI.',
    suggestedHashtags: ['#beauty', '#маникюр2026', '#нейлдизайн', '#тренды', '#салонкрасоты'],
});
</script>

<template>
<div class="space-y-4">

    <!-- ═══ HEADER ═══ -->
    <div class="relative rounded-2xl overflow-hidden p-5"
         style="background:linear-gradient(135deg,var(--t-gradient-from),var(--t-gradient-via),var(--t-gradient-to))">
        <div class="absolute inset-0 opacity-10"
             style="background:repeating-linear-gradient(60deg,transparent,transparent 10px,rgba(255,255,255,.04) 10px,rgba(255,255,255,.04) 20px)"></div>
        <div class="relative flex items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center text-3xl"
                     style="background:rgba(255,255,255,.15);backdrop-filter:blur(6px)">🎬</div>
                <div>
                    <h2 class="text-lg font-bold text-white">{{ video.title }}</h2>
                    <div class="flex items-center gap-2 mt-1">
                        <VBadge :color="video.format === 'shorts' ? 'red' : video.format === 'reels' ? 'purple' : 'blue'" size="sm">{{ video.format }}</VBadge>
                        <span class="text-xs text-white/70">{{ fmt(video.views) }} просмотров</span>
                        <span class="text-xs text-white/70">• ROI {{ video.roi }}%</span>
                    </div>
                </div>
            </div>
            <VButton size="sm" variant="outline" style="color:#fff;border-color:rgba(255,255,255,.3)" @click="$emit('close')">✕</VButton>
        </div>
    </div>

    <!-- ═══ ACTION BAR ═══ -->
    <div class="flex flex-wrap gap-2">
        <VButton size="sm" @click="$emit('boost', video)">🚀 Продвинуть</VButton>
        <VButton size="sm" variant="outline" @click="$emit('duplicate', video)">📋 Дублировать</VButton>
        <VButton size="sm" variant="outline">📊 Скачать отчёт</VButton>
        <VButton size="sm" variant="outline">🔗 Копировать ссылку</VButton>
    </div>

    <!-- ═══ TABS ═══ -->
    <div class="flex gap-1 flex-wrap">
        <button v-for="t in tabs" :key="t.key"
                class="px-3 py-1.5 text-xs rounded-full border transition-all"
                :style="activeTab === t.key
                    ? 'background:var(--t-primary);color:#fff;border-color:var(--t-primary)'
                    : 'background:var(--t-surface);color:var(--t-text-2);border-color:var(--t-border)'"
                @click="activeTab = t.key">{{ t.label }}</button>
    </div>

    <!-- ═══ OVERVIEW ═══ -->
    <div v-if="activeTab === 'overview'" class="space-y-4">
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
            <div v-for="k in kpis" :key="k.label"
                 class="p-2.5 rounded-xl border text-center"
                 style="background:var(--t-bg);border-color:var(--t-border)">
                <div class="text-lg mb-0.5">{{ k.icon }}</div>
                <div class="text-[10px] mb-1" style="color:var(--t-text-3)">{{ k.label }}</div>
                <div class="text-sm font-bold" :style="`color:${k.color}`">{{ k.value }}</div>
            </div>
        </div>

        <!-- AI Insight -->
        <VCard title="🤖 AI-аналитика видео">
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="p-2.5 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Вирусный потенциал</div>
                    <div class="text-lg font-bold" style="color:var(--t-primary)">{{ aiVideoInsight.viralScore }}/10</div>
                </div>
                <div class="p-2.5 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Лучший момент</div>
                    <div class="text-sm font-bold" style="color:#22c55e">{{ aiVideoInsight.bestMoment }}</div>
                </div>
            </div>
            <div class="p-3 rounded-lg text-xs mb-3" style="background:var(--t-surface);color:var(--t-text-2)">
                💡 {{ aiVideoInsight.recommendation }}
            </div>
            <div class="flex flex-wrap gap-1">
                <VBadge v-for="tag in aiVideoInsight.suggestedHashtags" :key="tag" color="blue" size="sm">{{ tag }}</VBadge>
            </div>
        </VCard>
    </div>

    <!-- ═══ RETENTION ═══ -->
    <div v-if="activeTab === 'retention'" class="space-y-4">
        <VCard title="📉 Кривая удержания">
            <!-- SVG retention chart -->
            <div class="relative h-48 border rounded-lg overflow-hidden" style="background:var(--t-bg);border-color:var(--t-border)">
                <svg class="w-full h-full" viewBox="0 0 400 180" preserveAspectRatio="none">
                    <!-- Grid -->
                    <line v-for="i in [0,25,50,75,100]" :key="i"
                          :x1="0" :y1="180 - i * 1.8" :x2="400" :y2="180 - i * 1.8"
                          stroke="var(--t-border)" stroke-width="0.5" stroke-dasharray="4"/>
                    <!-- Area -->
                    <polygon :points="`0,180 ${retentionPoints.map((p, i) => `${i * 44},${180 - p.pct * 1.8}`).join(' ')} ${(retentionPoints.length-1)*44},180`"
                             fill="var(--t-primary)" opacity="0.15"/>
                    <!-- Line -->
                    <polyline :points="retentionPoints.map((p, i) => `${i * 44},${180 - p.pct * 1.8}`).join(' ')"
                              fill="none" stroke="var(--t-primary)" stroke-width="2.5"/>
                    <!-- Dots -->
                    <circle v-for="(p, i) in retentionPoints" :key="p.second"
                            :cx="i * 44" :cy="180 - p.pct * 1.8" r="3"
                            fill="var(--t-primary)" stroke="#fff" stroke-width="1"/>
                </svg>
                <!-- Y-axis labels -->
                <div class="absolute left-1 top-0 bottom-0 flex flex-col justify-between text-[9px] py-1" style="color:var(--t-text-3)">
                    <span>100%</span><span>75%</span><span>50%</span><span>25%</span><span>0%</span>
                </div>
            </div>

            <!-- X labels -->
            <div class="flex justify-between text-[9px] mt-1" style="color:var(--t-text-3)">
                <span v-for="p in retentionPoints" :key="p.second">{{ p.second }}с</span>
            </div>

            <!-- Insights -->
            <div class="grid grid-cols-2 gap-2 mt-3">
                <div class="p-2 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Ср. время просмотра</div>
                    <div class="text-lg font-bold" style="color:var(--t-primary)">{{ retentionInsights.avgWatchTime }}с</div>
                    <div class="text-[10px]" style="color:#22c55e">бенчмарк: {{ retentionInsights.benchmark }}с</div>
                </div>
                <div class="p-2 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Первый drop-off</div>
                    <div class="text-lg font-bold" style="color:#f59e0b">{{ retentionInsights.dropOff1.second }}с</div>
                    <div class="text-[10px]" style="color:var(--t-text-3)">{{ retentionInsights.dropOff1.reason }}</div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ FUNNEL ═══ -->
    <div v-if="activeTab === 'funnel'" class="space-y-4">
        <VCard title="🔄 Воронка конверсии видео">
            <div class="space-y-2">
                <div v-for="(f, i) in funnel" :key="f.step"
                     class="flex items-center gap-3">
                    <span class="text-[10px] w-32 truncate" style="color:var(--t-text)">{{ f.step }}</span>
                    <div class="flex-1 h-6 rounded overflow-hidden" style="background:var(--t-bg)">
                        <div class="h-full rounded flex items-center justify-end pr-2"
                             :style="`width:${(f.value / maxFunnel) * 100}%;background:${['var(--t-border)','var(--t-primary-dim)','var(--t-primary)','var(--t-accent)','#22c55e','#16a34a','#15803d'][i]};min-width:2rem`">
                            <span class="text-[9px] font-bold text-white">{{ fmt(f.value) }}</span>
                        </div>
                    </div>
                    <span v-if="i > 0" class="text-[10px] w-10 text-right"
                          :style="`color:${funnel[i].value / funnel[i-1].value > 0.5 ? '#22c55e' : '#f59e0b'}`">
                        {{ ((funnel[i].value / funnel[i-1].value) * 100).toFixed(1) }}%
                    </span>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ AUDIENCE ═══ -->
    <div v-if="activeTab === 'audience'" class="space-y-4">
        <!-- Traffic sources -->
        <VCard title="📡 Откуда пришли зрители">
            <div class="space-y-2">
                <div v-for="s in videoTrafficSources" :key="s.source" class="flex items-center gap-3">
                    <span class="text-lg">{{ s.icon }}</span>
                    <span class="text-xs w-28" style="color:var(--t-text)">{{ s.source }}</span>
                    <div class="flex-1 h-4 rounded-full overflow-hidden" style="background:var(--t-bg)">
                        <div class="h-full rounded-full" :style="`width:${s.pct}%;background:var(--t-primary)`"></div>
                    </div>
                    <span class="text-[10px] w-8 text-right font-bold" style="color:var(--t-text)">{{ s.pct }}%</span>
                </div>
            </div>
        </VCard>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Geo -->
            <VCard title="🌍 География">
                <div class="space-y-2">
                    <div v-for="g in videoAudienceGeo" :key="g.city" class="flex items-center gap-2">
                        <span class="text-xs w-28 truncate" style="color:var(--t-text)">{{ g.city }}</span>
                        <div class="flex-1 h-4 rounded-full overflow-hidden" style="background:var(--t-bg)">
                            <div class="h-full rounded-full" :style="`width:${g.pct}%;background:var(--t-accent)`"></div>
                        </div>
                        <span class="text-[10px] w-8 text-right font-bold" style="color:var(--t-text)">{{ g.pct }}%</span>
                    </div>
                </div>
            </VCard>

            <!-- Demo -->
            <VCard title="👤 Демография">
                <div class="space-y-2 mb-3">
                    <div v-for="a in videoAudienceAge" :key="a.group" class="flex items-center gap-2">
                        <span class="text-xs w-10" style="color:var(--t-text)">{{ a.group }}</span>
                        <div class="flex-1 h-4 rounded-full overflow-hidden" style="background:var(--t-bg)">
                            <div class="h-full rounded-full" :style="`width:${a.pct * 2.5}%;background:var(--t-primary)`"></div>
                        </div>
                        <span class="text-[10px] w-8 text-right font-bold" style="color:var(--t-text)">{{ a.pct }}%</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="flex-1 p-2 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                        <span class="text-lg">♀️</span><div class="text-sm font-bold" style="color:var(--t-primary)">{{ videoGender.female }}%</div>
                    </div>
                    <div class="flex-1 p-2 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                        <span class="text-lg">♂️</span><div class="text-sm font-bold" style="color:var(--t-text)">{{ videoGender.male }}%</div>
                    </div>
                </div>
            </VCard>
        </div>
    </div>

    <!-- ═══ DYNAMICS ═══ -->
    <div v-if="activeTab === 'dynamics'" class="space-y-4">
        <VCard title="📈 Динамика по дням">
            <!-- Bar chart -->
            <div class="flex items-end gap-1 h-28 mb-1">
                <div v-for="d in dailyDynamics" :key="d.date"
                     class="flex-1 rounded-t"
                     :style="`height:${(d.views / maxViews) * 100}%;background:var(--t-primary)`"></div>
            </div>
            <div class="flex gap-1 text-[9px] mb-4" style="color:var(--t-text-3)">
                <div v-for="d in dailyDynamics" :key="d.date" class="flex-1 text-center">{{ d.date }}</div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b" style="border-color:var(--t-border)">
                            <th class="text-left px-2 py-2" style="color:var(--t-text-3)">Дата</th>
                            <th class="text-right px-2 py-2" style="color:var(--t-text-3)">Просмотры</th>
                            <th class="text-right px-2 py-2" style="color:var(--t-text-3)">Лайки</th>
                            <th class="text-right px-2 py-2" style="color:var(--t-text-3)">Комменты</th>
                            <th class="text-right px-2 py-2" style="color:var(--t-text-3)">Записи</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="d in dailyDynamics" :key="d.date" class="border-b" style="border-color:var(--t-border)">
                            <td class="px-2 py-2 font-medium" style="color:var(--t-text)">{{ d.date }}</td>
                            <td class="text-right px-2 py-2 font-bold" style="color:var(--t-text)">{{ fmt(d.views) }}</td>
                            <td class="text-right px-2 py-2" style="color:var(--t-text-2)">{{ d.likes }}</td>
                            <td class="text-right px-2 py-2" style="color:var(--t-text-2)">{{ d.comments }}</td>
                            <td class="text-right px-2 py-2 font-bold" style="color:var(--t-primary)">{{ d.bookings }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>
    </div>

    <!-- ═══ A/B TEST ═══ -->
    <div v-if="activeTab === 'ab'" class="space-y-4">
        <VCard title="🧪 A/B-тест вариантов видео">
            <div class="space-y-3">
                <div v-for="v in abVariants" :key="v.name"
                     class="p-3 rounded-xl border"
                     :style="`background:${v.winner ? 'var(--t-bg)' : 'var(--t-surface)'};border-color:${v.winner ? 'var(--t-primary)' : 'var(--t-border)'}`">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-lg">{{ v.thumbnail }}</span>
                        <span class="text-xs font-bold" :style="`color:${v.winner ? 'var(--t-primary)' : 'var(--t-text)'}`">
                            {{ v.winner ? '🏆 ' : '' }}{{ v.name }}
                        </span>
                    </div>
                    <div class="text-[10px] mb-2" style="color:var(--t-text-3)">Хук: {{ v.hook }}</div>
                    <div class="grid grid-cols-4 gap-2 text-center text-[10px]">
                        <div>
                            <div style="color:var(--t-text-3)">Просмотры</div>
                            <div class="font-bold" style="color:var(--t-text)">{{ fmt(v.views) }}</div>
                        </div>
                        <div>
                            <div style="color:var(--t-text-3)">Досмотры</div>
                            <div class="font-bold" :style="`color:${v.completion >= 65 ? '#22c55e' : 'var(--t-text)'}`">{{ v.completion }}%</div>
                        </div>
                        <div>
                            <div style="color:var(--t-text-3)">Клики</div>
                            <div class="font-bold" style="color:var(--t-text)">{{ fmt(v.clicks) }}</div>
                        </div>
                        <div>
                            <div style="color:var(--t-text-3)">Записи</div>
                            <div class="font-bold" style="color:var(--t-primary)">{{ v.bookings }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </VCard>
    </div>
</div>
</template>
