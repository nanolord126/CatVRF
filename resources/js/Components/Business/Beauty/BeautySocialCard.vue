<script setup>
/**
 * BeautySocialCard — полная карточка социальной платформы.
 * Детальная аналитика соц. сети: контент-план, топ-посты, аудитория,
 * динамика подписчиков, вовлечённость, конверсия в записи.
 * Получает platform через props, эмитит @close наверх.
 */
import { ref, computed } from 'vue';
import VButton from '../../UI/VButton.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import VStatCard from '../../UI/VStatCard.vue';

const props = defineProps({
    platform: { type: Object, required: true },
});

const emit = defineEmits(['close', 'create-post', 'schedule', 'export']);

/* ─── Helpers ─── */
function fmtMoney(n) { return new Intl.NumberFormat('ru-RU').format(n) + ' ₽'; }
function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }

/* ═══════════════ TABS ═══════════════ */
const tabs = [
    { key: 'overview',  label: '📊 Обзор' },
    { key: 'content',   label: '📝 Контент' },
    { key: 'audience',  label: '👥 Аудитория' },
    { key: 'dynamics',  label: '📈 Динамика' },
    { key: 'best',      label: '🏆 Лучший контент' },
];
const activeTab = ref('overview');

/* ═══════════════ KPI ═══════════════ */
const kpis = computed(() => {
    const p = props.platform;
    return [
        { label: 'Подписчики',    value: fmt(p.followers),     icon: '👥', color: 'var(--t-primary)' },
        { label: 'Охват',         value: fmt(p.reach),         icon: '📡', color: 'var(--t-text)' },
        { label: 'Клики',         value: fmt(p.clicks),        icon: '🖱',  color: 'var(--t-text)' },
        { label: 'Клиенты',       value: p.clients,            icon: '✅', color: '#22c55e' },
        { label: 'Выручка',       value: fmtMoney(p.revenue),  icon: '💰', color: 'var(--t-primary)' },
        { label: 'Ср. чек',       value: fmtMoney(p.avgCheck), icon: '🧾', color: 'var(--t-text)' },
        { label: 'Динамика',      value: p.dynamics,           icon: '📈', color: p.dynamics?.startsWith('+') ? '#22c55e' : '#ef4444' },
        { label: 'ER',            value: (p.er || 0) + '%',    icon: '💬', color: 'var(--t-accent)' },
    ];
});

/* ═══════════════ CONTENT CALENDAR ═══════════════ */
const contentCalendar = ref([
    { id: 1, date: '17.03', time: '10:00', type: 'reels',   topic: 'Окрашивание AirTouch — до/после', status: 'published', reach: 12000, likes: 840, comments: 92 },
    { id: 2, date: '16.03', time: '19:00', type: 'stories', topic: 'Бэкстейдж — день мастера',          status: 'published', reach: 8400,  likes: 320, comments: 45 },
    { id: 3, date: '15.03', time: '12:00', type: 'post',    topic: 'Весенние скидки на SPA-программы',  status: 'published', reach: 6200,  likes: 280, comments: 38 },
    { id: 4, date: '14.03', time: '18:00', type: 'reels',   topic: 'Маникюр-тренды весна 2026',         status: 'published', reach: 15400, likes: 1100, comments: 156 },
    { id: 5, date: '13.03', time: '09:00', type: 'carousel', topic: '5 причин выбрать наш салон',       status: 'published', reach: 9200,  likes: 620, comments: 84 },
    { id: 6, date: '18.03', time: '12:00', type: 'reels',   topic: 'Стрижка боб — трансформация',       status: 'scheduled', reach: null,  likes: null, comments: null },
    { id: 7, date: '19.03', time: '18:00', type: 'stories', topic: 'Q&A с мастером маникюра',           status: 'scheduled', reach: null,  likes: null, comments: null },
]);
const publishedPosts = computed(() => contentCalendar.value.filter(p => p.status === 'published'));
const scheduledPosts = computed(() => contentCalendar.value.filter(p => p.status === 'scheduled'));

/* ═══════════════ BEST CONTENT ═══════════════ */
const bestPosts = ref([
    { id: 1, type: 'reels',    topic: 'Маникюр-тренды весна 2026',         reach: 15400, likes: 1100, comments: 156, saves: 420, bookings: 5, date: '14.03' },
    { id: 2, type: 'reels',    topic: 'Окрашивание AirTouch — до/после',   reach: 12000, likes: 840,  comments: 92,  saves: 310, bookings: 3, date: '17.03' },
    { id: 3, type: 'carousel', topic: '5 причин выбрать наш салон',         reach: 9200,  likes: 620,  comments: 84,  saves: 190, bookings: 2, date: '13.03' },
]);

/* ═══════════════ AUDIENCE ═══════════════ */
const audienceGrowth = ref([
    { date: '10.03', followers: 3380 }, { date: '11.03', followers: 3395 },
    { date: '12.03', followers: 3410 }, { date: '13.03', followers: 3442 },
    { date: '14.03', followers: 3490 }, { date: '15.03', followers: 3510 },
    { date: '16.03', followers: 3525 }, { date: '17.03', followers: 3560 },
]);
const audienceTopCities = ref([
    { city: 'Москва', pct: 52 }, { city: 'Санкт-Петербург', pct: 18 },
    { city: 'Екатеринбург', pct: 6 }, { city: 'Казань', pct: 5 }, { city: 'Другие', pct: 19 },
]);
const audienceAgeGroups = ref([
    { group: '18–24', pct: 18 }, { group: '25–34', pct: 42 },
    { group: '35–44', pct: 28 }, { group: '45+', pct: 12 },
]);
const genderSplit = ref({ female: 86, male: 14 });

/* ═══════════════ DYNAMICS ═══════════════ */
const weeklyDynamics = ref([
    { week: '24 фев – 02 мар', posts: 4, reach: 28000, engagements: 3200, newFollowers: 45, clicks: 320, bookings: 3 },
    { week: '03 мар – 09 мар', posts: 5, reach: 34000, engagements: 4100, newFollowers: 62, clicks: 410, bookings: 4 },
    { week: '10 мар – 16 мар', posts: 5, reach: 42000, engagements: 5400, newFollowers: 85, clicks: 520, bookings: 6 },
]);
const maxReach = computed(() => Math.max(...weeklyDynamics.value.map(d => d.reach)));

/* ═══════════════ POSTING TIME ANALYSIS ═══════════════ */
const bestPostingTimes = ref([
    { time: '09:00–11:00', avgReach: 8200,  avgER: 3.4 },
    { time: '12:00–14:00', avgReach: 10400, avgER: 4.1 },
    { time: '18:00–20:00', avgReach: 14600, avgER: 5.2 },
    { time: '20:00–22:00', avgReach: 11800, avgER: 4.5 },
]);
const maxPostReach = computed(() => Math.max(...bestPostingTimes.value.map(t => t.avgReach)));

const typeIcons = { reels: '🎬', stories: '📱', post: '📸', carousel: '🖼' };
</script>

<template>
<div class="space-y-4">

    <!-- ═══ HEADER ═══ -->
    <div class="relative rounded-2xl overflow-hidden p-5"
         style="background:linear-gradient(135deg,var(--t-gradient-from),var(--t-gradient-via),var(--t-gradient-to))">
        <div class="absolute inset-0 opacity-10"
             style="background:repeating-linear-gradient(45deg,transparent,transparent 6px,rgba(255,255,255,.06) 6px,rgba(255,255,255,.06) 12px)"></div>
        <div class="relative flex items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center text-3xl"
                     style="background:rgba(255,255,255,.15);backdrop-filter:blur(6px)">
                    {{ platform.icon || '📱' }}
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white">{{ platform.name }}</h2>
                    <div class="flex items-center gap-3 mt-1 text-xs text-white/70">
                        <span>{{ fmt(platform.followers) }} подписчиков</span>
                        <span>•</span>
                        <span class="font-medium" :class="platform.dynamics?.startsWith('+') ? 'text-emerald-300' : 'text-red-300'">{{ platform.dynamics }}</span>
                    </div>
                </div>
            </div>
            <VButton size="sm" variant="outline" style="color:#fff;border-color:rgba(255,255,255,.3)" @click="$emit('close')">✕</VButton>
        </div>
    </div>

    <!-- ═══ ACTION BAR ═══ -->
    <div class="flex flex-wrap gap-2">
        <VButton size="sm" @click="$emit('create-post', platform)">📝 Создать пост</VButton>
        <VButton size="sm" variant="outline" @click="$emit('schedule', platform)">📅 Запланировать</VButton>
        <VButton size="sm" variant="outline">📊 Скачать отчёт</VButton>
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
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <div v-for="k in kpis" :key="k.label"
                 class="p-2.5 rounded-xl border text-center"
                 style="background:var(--t-bg);border-color:var(--t-border)">
                <div class="text-lg mb-0.5">{{ k.icon }}</div>
                <div class="text-[10px] mb-1" style="color:var(--t-text-3)">{{ k.label }}</div>
                <div class="text-sm font-bold" :style="`color:${k.color}`">{{ k.value }}</div>
            </div>
        </div>

        <!-- Best posting time -->
        <VCard title="⏰ Лучшее время постинга">
            <div class="space-y-2">
                <div v-for="t in bestPostingTimes" :key="t.time" class="flex items-center gap-3">
                    <span class="text-xs w-24" style="color:var(--t-text)">{{ t.time }}</span>
                    <div class="flex-1 h-5 rounded-full overflow-hidden" style="background:var(--t-bg)">
                        <div class="h-full rounded-full flex items-center justify-end pr-2"
                             :style="`width:${(t.avgReach / maxPostReach) * 100}%;background:var(--t-primary)`">
                            <span class="text-[9px] text-white font-bold">{{ fmt(t.avgReach) }}</span>
                        </div>
                    </div>
                    <span class="text-[10px] w-12 text-right font-bold" style="color:var(--t-accent)">ER {{ t.avgER }}%</span>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ CONTENT ═══ -->
    <div v-if="activeTab === 'content'" class="space-y-4">
        <VCard :title="`📝 Опубликовано (${publishedPosts.length})`">
            <div class="space-y-2">
                <div v-for="p in publishedPosts" :key="p.id"
                     class="flex items-center gap-3 p-2.5 rounded-lg border"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-lg">{{ typeIcons[p.type] || '📄' }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-medium truncate" style="color:var(--t-text)">{{ p.topic }}</div>
                        <div class="flex gap-2 mt-0.5 text-[10px]" style="color:var(--t-text-3)">
                            <span>{{ p.date }} {{ p.time }}</span>
                            <VBadge color="green" size="sm">{{ p.type }}</VBadge>
                        </div>
                    </div>
                    <div class="flex gap-3 text-center text-[10px]">
                        <div><div style="color:var(--t-text-3)">Охват</div><div class="font-bold" style="color:var(--t-text)">{{ fmt(p.reach) }}</div></div>
                        <div><div style="color:var(--t-text-3)">❤️</div><div class="font-bold" style="color:var(--t-text)">{{ fmt(p.likes) }}</div></div>
                        <div><div style="color:var(--t-text-3)">💬</div><div class="font-bold" style="color:var(--t-text)">{{ p.comments }}</div></div>
                    </div>
                </div>
            </div>
        </VCard>

        <VCard :title="`📅 Запланировано (${scheduledPosts.length})`">
            <div class="space-y-2">
                <div v-for="p in scheduledPosts" :key="p.id"
                     class="flex items-center gap-3 p-2.5 rounded-lg border border-dashed"
                     style="background:var(--t-surface);border-color:var(--t-border)">
                    <span class="text-lg">{{ typeIcons[p.type] || '📄' }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-medium truncate" style="color:var(--t-text)">{{ p.topic }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ p.date }} {{ p.time }}</div>
                    </div>
                    <VBadge color="yellow" size="sm">план</VBadge>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ AUDIENCE ═══ -->
    <div v-if="activeTab === 'audience'" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <VCard title="🌍 География">
                <div class="space-y-2">
                    <div v-for="c in audienceTopCities" :key="c.city" class="flex items-center gap-2">
                        <span class="text-xs w-28 truncate" style="color:var(--t-text)">{{ c.city }}</span>
                        <div class="flex-1 h-4 rounded-full overflow-hidden" style="background:var(--t-bg)">
                            <div class="h-full rounded-full" :style="`width:${c.pct}%;background:var(--t-primary)`"></div>
                        </div>
                        <span class="text-[10px] w-8 text-right font-bold" style="color:var(--t-text)">{{ c.pct }}%</span>
                    </div>
                </div>
            </VCard>

            <VCard title="👤 Демография">
                <div class="space-y-2 mb-3">
                    <div v-for="a in audienceAgeGroups" :key="a.group" class="flex items-center gap-2">
                        <span class="text-xs w-12" style="color:var(--t-text)">{{ a.group }}</span>
                        <div class="flex-1 h-4 rounded-full overflow-hidden" style="background:var(--t-bg)">
                            <div class="h-full rounded-full" :style="`width:${a.pct * 2}%;background:var(--t-accent)`"></div>
                        </div>
                        <span class="text-[10px] w-8 text-right font-bold" style="color:var(--t-text)">{{ a.pct }}%</span>
                    </div>
                </div>
                <div class="flex gap-3 text-center">
                    <div class="flex-1 p-2 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <span class="text-lg">♀️</span>
                        <div class="text-sm font-bold" style="color:var(--t-primary)">{{ genderSplit.female }}%</div>
                    </div>
                    <div class="flex-1 p-2 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <span class="text-lg">♂️</span>
                        <div class="text-sm font-bold" style="color:var(--t-text)">{{ genderSplit.male }}%</div>
                    </div>
                </div>
            </VCard>
        </div>

        <!-- Growth mini-chart -->
        <VCard title="📈 Рост подписчиков (неделя)">
            <div class="flex items-end gap-1 h-24">
                <div v-for="(d, i) in audienceGrowth" :key="d.date"
                     class="flex-1 rounded-t"
                     :style="`height:${((d.followers - 3370) / 200) * 100}%;background:${i === audienceGrowth.length - 1 ? 'var(--t-primary)' : 'var(--t-border)'}`">
                </div>
            </div>
            <div class="flex gap-1 mt-1 text-[9px]" style="color:var(--t-text-3)">
                <div v-for="d in audienceGrowth" :key="d.date" class="flex-1 text-center">{{ d.date }}</div>
            </div>
        </VCard>
    </div>

    <!-- ═══ DYNAMICS ═══ -->
    <div v-if="activeTab === 'dynamics'" class="space-y-4">
        <VCard title="📈 Еженедельная динамика">
            <!-- Bar chart -->
            <div class="flex items-end gap-2 h-24 mb-2">
                <div v-for="w in weeklyDynamics" :key="w.week"
                     class="flex-1 rounded-t cursor-pointer hover:opacity-80"
                     :style="`height:${(w.reach / maxReach) * 100}%;background:var(--t-primary)`">
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b" style="border-color:var(--t-border)">
                            <th class="text-left px-2 py-2" style="color:var(--t-text-3)">Неделя</th>
                            <th class="text-right px-2 py-2" style="color:var(--t-text-3)">Посты</th>
                            <th class="text-right px-2 py-2" style="color:var(--t-text-3)">Охват</th>
                            <th class="text-right px-2 py-2" style="color:var(--t-text-3)">Вовлечённость</th>
                            <th class="text-right px-2 py-2" style="color:var(--t-text-3)">Новые подп.</th>
                            <th class="text-right px-2 py-2" style="color:var(--t-text-3)">Клики</th>
                            <th class="text-right px-2 py-2" style="color:var(--t-text-3)">Записи</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="w in weeklyDynamics" :key="w.week" class="border-b" style="border-color:var(--t-border)">
                            <td class="px-2 py-2 font-medium" style="color:var(--t-text)">{{ w.week }}</td>
                            <td class="text-right px-2 py-2" style="color:var(--t-text-2)">{{ w.posts }}</td>
                            <td class="text-right px-2 py-2 font-bold" style="color:var(--t-text)">{{ fmt(w.reach) }}</td>
                            <td class="text-right px-2 py-2" style="color:var(--t-text-2)">{{ fmt(w.engagements) }}</td>
                            <td class="text-right px-2 py-2" style="color:#22c55e">+{{ w.newFollowers }}</td>
                            <td class="text-right px-2 py-2" style="color:var(--t-text)">{{ fmt(w.clicks) }}</td>
                            <td class="text-right px-2 py-2 font-bold" style="color:var(--t-primary)">{{ w.bookings }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>
    </div>

    <!-- ═══ BEST CONTENT ═══ -->
    <div v-if="activeTab === 'best'" class="space-y-4">
        <VCard title="🏆 Топ-посты по эффективности">
            <div class="space-y-3">
                <div v-for="(bp, i) in bestPosts" :key="bp.id"
                     class="p-3 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-lg">{{ i === 0 ? '🥇' : i === 1 ? '🥈' : '🥉' }}</span>
                        <VBadge :color="bp.type === 'reels' ? 'purple' : bp.type === 'carousel' ? 'blue' : 'gray'" size="sm">{{ bp.type }}</VBadge>
                        <span class="flex-1 text-xs font-medium truncate" style="color:var(--t-text)">{{ bp.topic }}</span>
                        <span class="text-[10px]" style="color:var(--t-text-3)">{{ bp.date }}</span>
                    </div>
                    <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 text-center text-[10px]">
                        <div><div style="color:var(--t-text-3)">Охват</div><div class="font-bold" style="color:var(--t-text)">{{ fmt(bp.reach) }}</div></div>
                        <div><div style="color:var(--t-text-3)">❤️ Лайки</div><div class="font-bold" style="color:var(--t-text)">{{ fmt(bp.likes) }}</div></div>
                        <div><div style="color:var(--t-text-3)">💬 Комменты</div><div class="font-bold" style="color:var(--t-text)">{{ bp.comments }}</div></div>
                        <div><div style="color:var(--t-text-3)">🔖 Сохранения</div><div class="font-bold" style="color:var(--t-text)">{{ bp.saves }}</div></div>
                        <div><div style="color:var(--t-text-3)">📋 Записи</div><div class="font-bold" style="color:var(--t-primary)">{{ bp.bookings }}</div></div>
                        <div><div style="color:var(--t-text-3)">Conv.</div><div class="font-bold" style="color:#22c55e">{{ bp.reach > 0 ? ((bp.bookings / bp.reach) * 100).toFixed(3) + '%' : '—' }}</div></div>
                    </div>
                </div>
            </div>
        </VCard>
    </div>
</div>
</template>
