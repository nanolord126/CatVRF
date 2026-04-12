<script setup>
/**
 * BeautyBloggerCard — полная карточка блогера / инфлюенсера.
 * Детальная аналитика сотрудничества: публикации, охват, ROI,
 * аудитория блогера, история размещений, прогнозы.
 * Получает blogger через props, эмитит @close наверх.
 */
import { ref, computed } from 'vue';
import VButton from '../../UI/VButton.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import VStatCard from '../../UI/VStatCard.vue';

const props = defineProps({
    blogger: { type: Object, required: true },
});

const emit = defineEmits(['close', 'new-placement', 'send-message', 'archive']);

/* ─── Helpers ─── */
function fmtMoney(n) { return new Intl.NumberFormat('ru-RU').format(n) + ' ₽'; }
function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }

/* ═══════════════ TABS ═══════════════ */
const tabs = [
    { key: 'overview',    label: '📊 Обзор' },
    { key: 'placements',  label: '📝 Размещения' },
    { key: 'audience',    label: '👥 Аудитория' },
    { key: 'comparison',  label: '⚖️ Сравнение' },
];
const activeTab = ref('overview');

/* ═══════════════ ENRICHED DATA ═══════════════ */
const blog = computed(() => {
    const b = props.blogger;
    return {
        ...b,
        convRate: b.clicks > 0 ? ((b.bookings / b.clicks) * 100).toFixed(2) : 0,
        costPerPlacement: b.placements > 0 ? Math.round((b.cpo * b.bookings) / b.placements) : 0,
        avgLeadsPerPlacement: b.placements > 0 ? Math.round(b.leads / b.placements) : 0,
        avgBookingsPerPlacement: b.placements > 0 ? (b.bookings / b.placements).toFixed(1) : 0,
    };
});

/* ═══════════════ KPI CARDS ═══════════════ */
const kpis = computed(() => [
    { label: 'Подписчики',        value: fmt(blog.value.subscribers), icon: '👥', color: 'var(--t-text)' },
    { label: 'Площадка',          value: blog.value.platform,         icon: '📱', color: 'var(--t-primary)' },
    { label: 'Размещений',        value: blog.value.placements,       icon: '📝', color: 'var(--t-text)' },
    { label: 'Клики',             value: fmt(blog.value.clicks),      icon: '🖱',  color: 'var(--t-text)' },
    { label: 'Лиды',              value: fmt(blog.value.leads),       icon: '📋', color: 'var(--t-text-2)' },
    { label: 'Записи',            value: blog.value.bookings,         icon: '✅', color: '#22c55e' },
    { label: 'Выручка',           value: fmtMoney(blog.value.revenue), icon: '💰', color: 'var(--t-primary)' },
    { label: 'CPO',               value: fmtMoney(blog.value.cpo),    icon: '💳', color: blog.value.cpo < 3000 ? '#22c55e' : '#f59e0b' },
    { label: 'ROAS',              value: blog.value.roas + 'x',       icon: '📈', color: blog.value.roas >= 6 ? '#22c55e' : '#f59e0b' },
    { label: 'Ср. чек',           value: fmtMoney(blog.value.avgCheck), icon: '🧾', color: 'var(--t-text)' },
    { label: 'Conv. клик→запись', value: blog.value.convRate + '%',    icon: '🎯', color: 'var(--t-primary)' },
    { label: 'Ср. лидов / размещ.', value: blog.value.avgLeadsPerPlacement, icon: '📊', color: 'var(--t-text)' },
]);

/* ═══════════════ PLACEMENTS HISTORY ═══════════════ */
const placements = ref([
    { id: 1, date: '15.03.2026', type: 'stories',  topic: 'Окрашивание AirTouch — процесс',             reach: 42000, clicks: 1200, bookings: 8,  revenue: 84000,  cost: 15000, roas: 5.6 },
    { id: 2, date: '28.02.2026', type: 'reels',     topic: 'Сравнение до/после стрижки',                  reach: 68000, clicks: 840,  bookings: 6,  revenue: 62000,  cost: 12000, roas: 5.2 },
    { id: 3, date: '10.02.2026', type: 'post',      topic: 'Обзор на салон — 10 причин записаться',       reach: 28000, clicks: 360,  bookings: 4,  revenue: 42000,  cost: 8000,  roas: 5.3 },
]);

/* ═══════════════ BLOGGER AUDIENCE ═══════════════ */
const blogAudienceGeo = ref([
    { city: 'Москва',           pct: 48 },
    { city: 'Санкт-Петербург',  pct: 16 },
    { city: 'Казань',           pct: 8 },
    { city: 'Краснодар',        pct: 6 },
    { city: 'Другие',           pct: 22 },
]);
const blogAudienceAge = ref([
    { group: '18–24', pct: 22 }, { group: '25–34', pct: 44 },
    { group: '35–44', pct: 22 }, { group: '45+', pct: 12 },
]);
const blogAudienceGender = ref({ female: 88, male: 12 });
const blogEngagement = ref({ er: 4.8, avgLikes: 2400, avgComments: 180, avgShares: 94 });

/* ═══════════════ COMPARISON WITH OTHERS ═══════════════ */
const comparisonTable = ref([
    { name: props.blogger.name, subscribers: props.blogger.subscribers, bookings: props.blogger.bookings, cpo: props.blogger.cpo, roas: props.blogger.roas, isCurrent: true },
    { name: '@beauty_journal',   subscribers: 22000,  bookings: 12, cpo: 2500, roas: 6.7, isCurrent: false },
    { name: '@nails_art_studio', subscribers: 45000,  bookings: 8,  cpo: 3125, roas: 4.1, isCurrent: false },
    { name: '@mama_bloger_spb',  subscribers: 310000, bookings: 6,  cpo: 5000, roas: 3.8, isCurrent: false },
]);

/* ═══════════════ AI INSIGHTS ═══════════════ */
const aiInsights = ref({
    predictedRoas: 6.2,
    bestContentType: 'reels',
    bestPostingDay: 'Среда',
    bestPostingTime: '19:00',
    recommendation: 'Увеличить частоту размещений до 2 раз в месяц. Формат reels показывает лучший ROAS. Аудитория блогера совпадает с ЦА салона на 78%.',
    audienceOverlap: 78,
});
</script>

<template>
<div class="space-y-4">

    <!-- ═══ HEADER ═══ -->
    <div class="relative rounded-2xl overflow-hidden p-5"
         style="background:linear-gradient(135deg,var(--t-gradient-from),var(--t-gradient-via),var(--t-gradient-to))">
        <div class="absolute inset-0 opacity-10" style="background:repeating-linear-gradient(-45deg,transparent,transparent 8px,rgba(255,255,255,.05) 8px,rgba(255,255,255,.05) 16px)"></div>
        <div class="relative flex items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full flex items-center justify-center text-2xl"
                     style="background:rgba(255,255,255,.15);backdrop-filter:blur(6px)">📸</div>
                <div>
                    <h2 class="text-lg font-bold text-white">{{ blog.name }}</h2>
                    <div class="flex items-center gap-2 mt-1">
                        <VBadge color="blue" size="sm">{{ blog.platform }}</VBadge>
                        <span class="text-xs text-white/70">{{ fmt(blog.subscribers) }} подписчиков</span>
                    </div>
                </div>
            </div>
            <VButton size="sm" variant="outline" style="color:#fff;border-color:rgba(255,255,255,.3)" @click="$emit('close')">✕ Закрыть</VButton>
        </div>
    </div>

    <!-- ═══ ACTION BAR ═══ -->
    <div class="flex flex-wrap gap-2">
        <VButton size="sm" @click="$emit('new-placement', blogger)">📝 Новое размещение</VButton>
        <VButton size="sm" variant="outline" @click="$emit('send-message', blogger)">💬 Написать</VButton>
        <VButton size="sm" variant="outline">📊 Скачать отчёт</VButton>
        <VButton size="sm" variant="outline">📋 Копировать ссылку</VButton>
    </div>

    <!-- ═══ TABS ═══ -->
    <div class="flex gap-1 flex-wrap">
        <button v-for="t in tabs" :key="t.key"
                class="px-3 py-1.5 text-xs rounded-full border transition-all"
                :style="activeTab === t.key
                    ? 'background:var(--t-primary);color:#fff;border-color:var(--t-primary)'
                    : 'background:var(--t-surface);color:var(--t-text-2);border-color:var(--t-border)'"
                @click="activeTab = t.key">
            {{ t.label }}
        </button>
    </div>

    <!-- ═══ OVERVIEW TAB ═══ -->
    <div v-if="activeTab === 'overview'" class="space-y-4">
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
            <div v-for="k in kpis" :key="k.label"
                 class="p-2.5 rounded-xl border text-center"
                 style="background:var(--t-bg);border-color:var(--t-border)">
                <div class="text-lg mb-0.5">{{ k.icon }}</div>
                <div class="text-[10px] leading-tight mb-1" style="color:var(--t-text-3)">{{ k.label }}</div>
                <div class="text-sm font-bold" :style="`color:${k.color}`">{{ k.value }}</div>
            </div>
        </div>

        <!-- AI Insights -->
        <VCard title="🤖 AI-аналитика блогера">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                <div class="p-2.5 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Прогноз ROAS</div>
                    <div class="text-lg font-bold" style="color:#22c55e">{{ aiInsights.predictedRoas }}x</div>
                </div>
                <div class="p-2.5 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Лучший формат</div>
                    <div class="text-lg font-bold" style="color:var(--t-primary)">{{ aiInsights.bestContentType }}</div>
                </div>
                <div class="p-2.5 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Лучший день</div>
                    <div class="text-lg font-bold" style="color:var(--t-text)">{{ aiInsights.bestPostingDay }}</div>
                </div>
                <div class="p-2.5 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Совпадение ЦА</div>
                    <div class="text-lg font-bold" style="color:#22c55e">{{ aiInsights.audienceOverlap }}%</div>
                </div>
            </div>
            <div class="p-3 rounded-lg text-xs" style="background:var(--t-surface);color:var(--t-text-2)">
                💡 {{ aiInsights.recommendation }}
            </div>
        </VCard>

        <!-- Engagement -->
        <VCard title="💬 Вовлечённость аудитории блогера">
            <div class="grid grid-cols-4 gap-3">
                <div class="p-2 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">ER</div>
                    <div class="text-lg font-bold" style="color:var(--t-primary)">{{ blogEngagement.er }}%</div>
                </div>
                <div class="p-2 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Ср. лайки</div>
                    <div class="text-lg font-bold" style="color:var(--t-text)">{{ fmt(blogEngagement.avgLikes) }}</div>
                </div>
                <div class="p-2 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Ср. комментарии</div>
                    <div class="text-lg font-bold" style="color:var(--t-text)">{{ fmt(blogEngagement.avgComments) }}</div>
                </div>
                <div class="p-2 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Ср. шеры</div>
                    <div class="text-lg font-bold" style="color:var(--t-text)">{{ fmt(blogEngagement.avgShares) }}</div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ PLACEMENTS TAB ═══ -->
    <div v-if="activeTab === 'placements'" class="space-y-4">
        <VCard title="📝 История размещений">
            <div class="space-y-3">
                <div v-for="p in placements" :key="p.id"
                     class="p-3 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-xs font-mono" style="color:var(--t-text-3)">{{ p.date }}</span>
                        <VBadge :color="p.type === 'reels' ? 'purple' : p.type === 'stories' ? 'blue' : 'gray'" size="sm">{{ p.type }}</VBadge>
                        <span class="flex-1 text-sm font-medium truncate" style="color:var(--t-text)">{{ p.topic }}</span>
                    </div>
                    <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 text-center text-[10px]">
                        <div>
                            <div style="color:var(--t-text-3)">Охват</div>
                            <div class="font-bold" style="color:var(--t-text)">{{ fmt(p.reach) }}</div>
                        </div>
                        <div>
                            <div style="color:var(--t-text-3)">Клики</div>
                            <div class="font-bold" style="color:var(--t-text)">{{ fmt(p.clicks) }}</div>
                        </div>
                        <div>
                            <div style="color:var(--t-text-3)">Записи</div>
                            <div class="font-bold" style="color:var(--t-primary)">{{ p.bookings }}</div>
                        </div>
                        <div>
                            <div style="color:var(--t-text-3)">Выручка</div>
                            <div class="font-bold" style="color:var(--t-primary)">{{ fmtMoney(p.revenue) }}</div>
                        </div>
                        <div>
                            <div style="color:var(--t-text-3)">Затраты</div>
                            <div class="font-bold" style="color:#ef4444">{{ fmtMoney(p.cost) }}</div>
                        </div>
                        <div>
                            <div style="color:var(--t-text-3)">ROAS</div>
                            <div class="font-bold" :style="`color:${p.roas >= 5 ? '#22c55e' : '#f59e0b'}`">{{ p.roas }}x</div>
                        </div>
                    </div>
                </div>
            </div>
        </VCard>

        <VButton size="sm" class="w-full">📝 Создать новое размещение</VButton>
    </div>

    <!-- ═══ AUDIENCE TAB ═══ -->
    <div v-if="activeTab === 'audience'" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <VCard title="🌍 География подписчиков">
                <div class="space-y-2">
                    <div v-for="g in blogAudienceGeo" :key="g.city" class="flex items-center gap-2">
                        <span class="text-xs w-28 truncate" style="color:var(--t-text)">{{ g.city }}</span>
                        <div class="flex-1 h-4 rounded-full overflow-hidden" style="background:var(--t-bg)">
                            <div class="h-full rounded-full" :style="`width:${g.pct}%;background:var(--t-primary)`"></div>
                        </div>
                        <span class="text-[10px] w-8 text-right font-bold" style="color:var(--t-text)">{{ g.pct }}%</span>
                    </div>
                </div>
            </VCard>

            <VCard title="👥 Возраст">
                <div class="space-y-2">
                    <div v-for="a in blogAudienceAge" :key="a.group" class="flex items-center gap-2">
                        <span class="text-xs w-12" style="color:var(--t-text)">{{ a.group }}</span>
                        <div class="flex-1 h-4 rounded-full overflow-hidden" style="background:var(--t-bg)">
                            <div class="h-full rounded-full" :style="`width:${a.pct * 2}%;background:var(--t-accent)`"></div>
                        </div>
                        <span class="text-[10px] w-8 text-right font-bold" style="color:var(--t-text)">{{ a.pct }}%</span>
                    </div>
                </div>
                <div class="mt-3 flex gap-3 text-center">
                    <div class="flex-1 p-2 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <div class="text-lg">♀️</div>
                        <div class="text-sm font-bold" style="color:var(--t-primary)">{{ blogAudienceGender.female }}%</div>
                    </div>
                    <div class="flex-1 p-2 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <div class="text-lg">♂️</div>
                        <div class="text-sm font-bold" style="color:var(--t-text)">{{ blogAudienceGender.male }}%</div>
                    </div>
                </div>
            </VCard>
        </div>
    </div>

    <!-- ═══ COMPARISON TAB ═══ -->
    <div v-if="activeTab === 'comparison'" class="space-y-4">
        <VCard title="⚖️ Сравнение с другими блогерами">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b" style="border-color:var(--t-border)">
                            <th class="text-left px-2 py-2" style="color:var(--t-text-3)">Блогер</th>
                            <th class="text-right px-2 py-2" style="color:var(--t-text-3)">Подписчики</th>
                            <th class="text-right px-2 py-2" style="color:var(--t-text-3)">Записи</th>
                            <th class="text-right px-2 py-2" style="color:var(--t-text-3)">CPO</th>
                            <th class="text-right px-2 py-2" style="color:var(--t-text-3)">ROAS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in comparisonTable" :key="c.name"
                            class="border-b" :style="`border-color:var(--t-border);background:${c.isCurrent ? 'var(--t-bg)' : 'transparent'}`">
                            <td class="px-2 py-2 font-medium" :style="`color:${c.isCurrent ? 'var(--t-primary)' : 'var(--t-text)'}`">
                                {{ c.isCurrent ? '⭐ ' : '' }}{{ c.name }}
                            </td>
                            <td class="text-right px-2 py-2" style="color:var(--t-text-2)">{{ fmt(c.subscribers) }}</td>
                            <td class="text-right px-2 py-2 font-bold" style="color:var(--t-primary)">{{ c.bookings }}</td>
                            <td class="text-right px-2 py-2" style="color:var(--t-text)">{{ fmtMoney(c.cpo) }}</td>
                            <td class="text-right px-2 py-2 font-bold" :style="`color:${c.roas >= 6 ? '#22c55e' : '#f59e0b'}`">{{ c.roas }}x</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>
    </div>
</div>
</template>
