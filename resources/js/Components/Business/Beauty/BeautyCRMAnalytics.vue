<template>
<div class="space-y-4">
    <!-- ═══ HEADER ═══ -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold" style="color:var(--t-text)">📊 Аналитика клиентов</h2>
        <div class="flex items-center gap-2">
            <select v-model="period" class="px-3 py-2 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option value="7d">7 дней</option>
                <option value="30d">30 дней</option>
                <option value="90d">90 дней</option>
                <option value="year">Год</option>
            </select>
            <VButton size="sm" variant="outline" @click="exportReport">📤 Отчёт</VButton>
        </div>
    </div>

    <!-- ═══ KPI METRICS ═══ -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <VStatCard title="Retention Rate" :value="retentionRate + '%'" icon="🔁">
            <template #trend>
                <span class="text-green-400 text-xs">+2.3% за месяц</span>
            </template>
        </VStatCard>
        <VStatCard title="Churn Rate" :value="churnRate + '%'" icon="📉">
            <template #trend>
                <span class="text-red-400 text-xs">{{ churnRate }}% в зоне оттока</span>
            </template>
        </VStatCard>
        <VStatCard title="NPS Score" :value="npsScore" icon="💯">
            <template #trend>
                <span class="text-green-400 text-xs">Отлично</span>
            </template>
        </VStatCard>
        <VStatCard title="Средний LTV" :value="fmtMoney(avgLTV)" icon="💰">
            <template #trend>
                <span class="text-green-400 text-xs">+12% к пред. периоду</span>
            </template>
        </VStatCard>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <VStatCard title="Средний чек" :value="fmtMoney(avgCheck)" icon="🧾" />
        <VStatCard title="Визитов/мес (ср.)" :value="'3.4'" icon="📅" />
        <VStatCard title="Новых за период" :value="String(newClientsInPeriod)" icon="🆕" />
        <VStatCard title="Потерянных за период" :value="String(lostClientsInPeriod)" icon="😔" />
    </div>

    <!-- ═══ RFM ANALYSIS ═══ -->
    <VCard title="🎯 RFM-анализ клиентской базы">
        <div class="mb-3 text-xs" style="color:var(--t-text-3)">
            Recency (давность) × Frequency (частота) × Monetary (сумма) — 3D-скоринг клиентов
        </div>
        <div class="grid md:grid-cols-3 gap-4 mb-4">
            <div v-for="dim in rfmDimensions" :key="dim.key"
                 class="p-3 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg">{{ dim.icon }}</span>
                    <span class="text-sm font-bold" style="color:var(--t-text)">{{ dim.label }}</span>
                </div>
                <div class="text-xs mb-2" style="color:var(--t-text-3)">{{ dim.description }}</div>
                <div class="space-y-1.5">
                    <div v-for="tier in dim.tiers" :key="tier.label" class="flex items-center gap-2">
                        <div class="w-20 text-[10px] font-medium" :style="`color:${tier.color}`">{{ tier.label }}</div>
                        <div class="flex-1 h-4 rounded-full overflow-hidden" style="background:var(--t-border)">
                            <div class="h-full rounded-full transition-all"
                                 :style="`width:${tier.pct}%;background:${tier.color}`"></div>
                        </div>
                        <span class="text-[10px] w-8 text-right" style="color:var(--t-text-3)">{{ tier.count }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RFM matrix -->
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr>
                        <th class="p-2 text-left" style="color:var(--t-text-2)">RFM-сегмент</th>
                        <th class="p-2 text-center" style="color:var(--t-text-2)">R</th>
                        <th class="p-2 text-center" style="color:var(--t-text-2)">F</th>
                        <th class="p-2 text-center" style="color:var(--t-text-2)">M</th>
                        <th class="p-2 text-right" style="color:var(--t-text-2)">Клиентов</th>
                        <th class="p-2 text-right" style="color:var(--t-text-2)">Ср. LTV</th>
                        <th class="p-2 text-center" style="color:var(--t-text-2)">Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="seg in rfmSegments" :key="seg.name"
                        class="border-t hover:brightness-110 transition"
                        style="border-color:var(--t-border)">
                        <td class="p-2">
                            <div class="flex items-center gap-2">
                                <span>{{ seg.icon }}</span>
                                <span class="font-medium" style="color:var(--t-text)">{{ seg.name }}</span>
                            </div>
                        </td>
                        <td class="p-2 text-center">
                            <span class="inline-block w-6 h-6 rounded text-[10px] font-bold flex items-center justify-center"
                                  :style="`background:${rfmColor(seg.r)};color:#fff`">{{ seg.r }}</span>
                        </td>
                        <td class="p-2 text-center">
                            <span class="inline-block w-6 h-6 rounded text-[10px] font-bold flex items-center justify-center"
                                  :style="`background:${rfmColor(seg.f)};color:#fff`">{{ seg.f }}</span>
                        </td>
                        <td class="p-2 text-center">
                            <span class="inline-block w-6 h-6 rounded text-[10px] font-bold flex items-center justify-center"
                                  :style="`background:${rfmColor(seg.m)};color:#fff`">{{ seg.m }}</span>
                        </td>
                        <td class="p-2 text-right font-medium" style="color:var(--t-text)">{{ seg.count }}</td>
                        <td class="p-2 text-right font-medium" style="color:var(--t-primary)">{{ fmtMoney(seg.avgLtv) }}</td>
                        <td class="p-2 text-center">
                            <VButton size="sm" variant="outline" @click="emit('filter-segment', seg.name)">📣</VButton>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </VCard>

    <!-- ═══ RETENTION & CHURN ═══ -->
    <div class="grid md:grid-cols-2 gap-4">
        <!-- Retention cohorts -->
        <VCard title="🔁 Retention по когортам">
            <div class="overflow-x-auto">
                <table class="w-full text-[10px]">
                    <thead>
                        <tr>
                            <th class="p-1.5 text-left" style="color:var(--t-text-2)">Когорта</th>
                            <th v-for="m in cohortMonths" :key="m" class="p-1.5 text-center" style="color:var(--t-text-2)">M{{ m }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="cohort in retentionCohorts" :key="cohort.name"
                            class="border-t" style="border-color:var(--t-border)">
                            <td class="p-1.5 font-medium whitespace-nowrap" style="color:var(--t-text)">{{ cohort.name }}</td>
                            <td v-for="(val, mi) in cohort.values" :key="mi" class="p-1">
                                <div class="w-full h-6 rounded flex items-center justify-center text-[9px] font-bold"
                                     :style="`background:${retentionColor(val)};color:#fff`">
                                    {{ val }}%
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>

        <!-- Churn risk distribution -->
        <VCard title="⚠️ Распределение риска оттока">
            <div class="space-y-3">
                <div v-for="bucket in churnBuckets" :key="bucket.label"
                     class="flex items-center gap-3">
                    <div class="w-28 text-xs font-medium" :style="`color:${bucket.color}`">{{ bucket.label }}</div>
                    <div class="flex-1 h-6 rounded-full overflow-hidden" style="background:var(--t-border)">
                        <div class="h-full rounded-full transition-all flex items-center justify-end pr-2 text-[10px] font-bold text-white"
                             :style="`width:${bucket.pct}%;background:${bucket.color}`">
                            {{ bucket.count }}
                        </div>
                    </div>
                    <span class="text-xs w-12 text-right" style="color:var(--t-text-3)">{{ bucket.pct }}%</span>
                </div>
            </div>
            <div class="mt-4 p-3 rounded-lg" style="background:var(--t-bg)">
                <div class="text-xs font-medium mb-2" style="color:var(--t-text)">💡 Рекомендации AI:</div>
                <ul class="text-[11px] space-y-1" style="color:var(--t-text-2)">
                    <li>• {{ churnRecommendations[0] }}</li>
                    <li>• {{ churnRecommendations[1] }}</li>
                    <li>• {{ churnRecommendations[2] }}</li>
                </ul>
            </div>
        </VCard>
    </div>

    <!-- ═══ LTV BY SEGMENTS ═══ -->
    <VCard title="💎 LTV по сегментам">
        <div class="grid md:grid-cols-4 gap-3 mb-4">
            <div v-for="seg in ltvBySegment" :key="seg.name"
                 class="p-4 rounded-xl border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                <div class="text-2xl mb-1">{{ seg.icon }}</div>
                <div class="text-sm font-bold" style="color:var(--t-text)">{{ seg.name }}</div>
                <div class="text-xl font-bold mt-1" style="color:var(--t-primary)">{{ fmtMoney(seg.avgLtv) }}</div>
                <div class="text-[10px] mt-1" style="color:var(--t-text-3)">{{ seg.count }} клиентов</div>
                <div class="text-[10px] mt-0.5" :class="seg.trend > 0 ? 'text-green-400' : 'text-red-400'">
                    {{ seg.trend > 0 ? '+' : '' }}{{ seg.trend }}% к пред. периоду
                </div>
            </div>
        </div>

        <!-- LTV distribution bars -->
        <div class="space-y-2">
            <div class="text-xs font-medium mb-1" style="color:var(--t-text-2)">Распределение LTV (все клиенты)</div>
            <div v-for="range in ltvDistribution" :key="range.label" class="flex items-center gap-3">
                <div class="w-32 text-xs" style="color:var(--t-text-2)">{{ range.label }}</div>
                <div class="flex-1 h-5 rounded-full overflow-hidden" style="background:var(--t-border)">
                    <div class="h-full rounded-full transition-all"
                         :style="`width:${range.pct}%;background:var(--t-primary)`"></div>
                </div>
                <span class="text-xs w-8 text-right font-medium" style="color:var(--t-text)">{{ range.count }}</span>
            </div>
        </div>
    </VCard>

    <!-- ═══ MARKETING EFFECTIVENESS ═══ -->
    <VCard title="📣 Эффективность маркетинга">
        <div class="grid md:grid-cols-4 gap-3 mb-4">
            <VStatCard title="Кампаний" :value="String(marketingStats.totalCampaigns)" icon="📣" />
            <VStatCard title="Отправлено" :value="String(marketingStats.totalSent)" icon="📩" />
            <VStatCard title="Open Rate" :value="marketingStats.openRate + '%'" icon="👁️" />
            <VStatCard title="Конверсия" :value="marketingStats.conversionRate + '%'" icon="🎯" />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr>
                        <th class="p-2 text-left" style="color:var(--t-text-2)">Кампания</th>
                        <th class="p-2 text-center" style="color:var(--t-text-2)">Канал</th>
                        <th class="p-2 text-center" style="color:var(--t-text-2)">Сегмент</th>
                        <th class="p-2 text-right" style="color:var(--t-text-2)">Отправлено</th>
                        <th class="p-2 text-right" style="color:var(--t-text-2)">Открыто</th>
                        <th class="p-2 text-right" style="color:var(--t-text-2)">Кликов</th>
                        <th class="p-2 text-right" style="color:var(--t-text-2)">Конверсия</th>
                        <th class="p-2 text-right" style="color:var(--t-text-2)">Выручка</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="camp in campaignStats" :key="camp.id"
                        class="border-t hover:brightness-110 transition"
                        style="border-color:var(--t-border)">
                        <td class="p-2 font-medium" style="color:var(--t-text)">{{ camp.name }}</td>
                        <td class="p-2 text-center">
                            <VBadge :color="camp.channel === 'SMS' ? 'blue' : camp.channel === 'Push' ? 'green' : 'purple'" size="sm">
                                {{ camp.channel }}
                            </VBadge>
                        </td>
                        <td class="p-2 text-center" style="color:var(--t-text-2)">{{ camp.segment }}</td>
                        <td class="p-2 text-right" style="color:var(--t-text)">{{ camp.sent }}</td>
                        <td class="p-2 text-right" style="color:var(--t-text)">{{ camp.opened }} ({{ Math.round(camp.opened / camp.sent * 100) }}%)</td>
                        <td class="p-2 text-right" style="color:var(--t-text)">{{ camp.clicks }}</td>
                        <td class="p-2 text-right font-bold" style="color:var(--t-primary)">{{ camp.conversion }}%</td>
                        <td class="p-2 text-right font-bold" style="color:var(--t-primary)">{{ fmtMoney(camp.revenue) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </VCard>

    <!-- ═══ CLIENT BASE DYNAMICS ═══ -->
    <div class="grid md:grid-cols-2 gap-4">
        <!-- Segment dynamics -->
        <VCard title="📈 Динамика сегментов">
            <div class="grid grid-cols-2 gap-3">
                <div v-for="dyn in segmentDynamics" :key="dyn.segment"
                     class="p-3 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-sm font-medium" style="color:var(--t-text)">{{ dyn.segment }}</div>
                    <div class="text-2xl font-bold" style="color:var(--t-primary)">{{ dyn.current }}</div>
                    <div class="text-xs" :class="dyn.change >= 0 ? 'text-green-400' : 'text-red-400'">
                        {{ dyn.change >= 0 ? '+' : '' }}{{ dyn.change }} за 30 дней
                    </div>
                    <!-- mini bar chart -->
                    <div class="flex items-end justify-center gap-0.5 h-8 mt-2">
                        <div v-for="(val, i) in dyn.history" :key="i"
                             class="w-2 rounded-t transition-all"
                             :style="`height:${val}%;background:var(--t-primary);opacity:${0.3 + i * 0.1}`"></div>
                    </div>
                </div>
            </div>
        </VCard>

        <!-- Source analytics -->
        <VCard title="🔗 Источники клиентов">
            <div class="space-y-2">
                <div v-for="src in sourceStats" :key="src.name"
                     class="flex items-center gap-3 p-2 rounded-lg" style="background:var(--t-bg)">
                    <span class="text-lg">{{ src.icon }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium" style="color:var(--t-text)">{{ src.name }}</span>
                            <span class="text-sm font-bold" style="color:var(--t-primary)">{{ src.count }}</span>
                        </div>
                        <div class="mt-1 h-2 rounded-full overflow-hidden" style="background:var(--t-border)">
                            <div class="h-full rounded-full transition-all" :style="`width:${src.pct}%;background:${src.color}`"></div>
                        </div>
                    </div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ VISIT PREDICTIONS ═══ -->
    <VCard title="🔮 AI-прогноз визитов">
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3">
            <div v-for="pred in visitPredictions" :key="pred.clientId"
                 class="p-3 rounded-lg border flex items-center gap-3 cursor-pointer hover:shadow transition"
                 style="background:var(--t-bg);border-color:var(--t-border)"
                 @click="emit('open-client', pred.clientId)">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold"
                     style="background:var(--t-primary-dim);color:var(--t-primary)">{{ pred.name.charAt(0) }}</div>
                <div class="flex-1">
                    <div class="text-sm font-medium" style="color:var(--t-text)">{{ pred.name }}</div>
                    <div class="text-[10px]" style="color:var(--t-text-3)">
                        Обычная частота: раз в {{ pred.avgInterval }} дней
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-bold" style="color:var(--t-primary)">{{ pred.predictedDate }}</div>
                    <div class="text-[10px]" style="color:var(--t-text-3)">точность {{ pred.confidence }}%</div>
                </div>
            </div>
        </div>
    </VCard>

    <!-- ═══ CHURN RISK CLIENTS ═══ -->
    <VCard title="⚠️ Клиенты в зоне оттока (AI-скоринг)">
        <div class="space-y-2 max-h-72 overflow-y-auto">
            <div v-for="c in churnRiskClients" :key="c.id"
                 class="p-3 rounded-lg border flex items-center gap-3 cursor-pointer hover:shadow transition"
                 style="background:var(--t-bg);border-color:var(--t-border)"
                 @click="emit('open-client', c.id)">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold"
                     style="background:var(--t-primary-dim);color:var(--t-primary)">{{ c.name.charAt(0) }}</div>
                <div class="flex-1">
                    <div class="text-sm font-medium" style="color:var(--t-text)">{{ c.name }}</div>
                    <div class="text-[10px]" style="color:var(--t-text-3)">
                        Посл. визит: {{ c.lastVisit || '—' }} · Потрачено: {{ fmtMoney(c.totalSpent) }}
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-bold" :style="`color:${c.churnRisk > 70 ? '#ef4444' : c.churnRisk > 40 ? '#f59e0b' : '#22c55e'}`">
                        {{ c.churnRisk }}%
                    </div>
                    <div class="text-[10px]" style="color:var(--t-text-3)">риск оттока</div>
                </div>
                <div class="w-20 h-2 rounded-full overflow-hidden" style="background:var(--t-border)">
                    <div class="h-full rounded-full transition-all"
                         :style="`width:${c.churnRisk}%;background:${c.churnRisk > 70 ? '#ef4444' : c.churnRisk > 40 ? '#f59e0b' : '#22c55e'}`"></div>
                </div>
            </div>
        </div>
    </VCard>
</div>
</template>

<script setup>
import { ref, computed } from 'vue';
import VButton from '../../UI/VButton.vue';
import VStatCard from '../../UI/VStatCard.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';

/* ═══════════════════════════════════════════════════════════════════ */
/*  PROPS & EMITS                                                      */
/* ═══════════════════════════════════════════════════════════════════ */
const props = defineProps({
    clients: { type: Array, default: () => [] },
});

const emit = defineEmits(['open-client', 'filter-segment']);

/* ═══════════════════════════════════════════════════════════════════ */
/*  STATE                                                              */
/* ═══════════════════════════════════════════════════════════════════ */
const period = ref('30d');

/* ═══════════════════════════════════════════════════════════════════ */
/*  HELPERS                                                            */
/* ═══════════════════════════════════════════════════════════════════ */
function fmtMoney(v) {
    if (v == null) return '0 ₽';
    return Number(v).toLocaleString('ru-RU') + ' ₽';
}

function rfmColor(score) {
    if (score >= 4) return '#22c55e';
    if (score >= 3) return '#84cc16';
    if (score >= 2) return '#f59e0b';
    return '#ef4444';
}

function retentionColor(pct) {
    if (pct >= 70) return '#22c55e';
    if (pct >= 50) return '#84cc16';
    if (pct >= 30) return '#f59e0b';
    return '#ef4444';
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  KPI METRICS                                                        */
/* ═══════════════════════════════════════════════════════════════════ */
const retentionRate = computed(() => {
    const returning = props.clients.filter(c => c.visits >= 2).length;
    return props.clients.length > 0 ? Math.round(returning / props.clients.length * 100) : 0;
});

const churnRate = computed(() => {
    const lost = props.clients.filter(c => c.churnRisk > 60).length;
    return props.clients.length > 0 ? Math.round(lost / props.clients.length * 100) : 0;
});

const npsScore = ref('72');

const avgLTV = computed(() => {
    const total = props.clients.reduce((s, c) => s + (c.ltvPredicted || c.totalSpent || 0), 0);
    return props.clients.length > 0 ? Math.round(total / props.clients.length) : 0;
});

const avgCheck = computed(() => {
    const withVisits = props.clients.filter(c => c.visits > 0);
    if (!withVisits.length) return 0;
    const total = withVisits.reduce((s, c) => s + Math.round(c.totalSpent / c.visits), 0);
    return Math.round(total / withVisits.length);
});

const newClientsInPeriod = computed(() => props.clients.filter(c => c.segment === 'Новичок').length);
const lostClientsInPeriod = computed(() => props.clients.filter(c => c.segment === 'Потерянная').length);

/* ═══════════════════════════════════════════════════════════════════ */
/*  RFM ANALYSIS                                                       */
/* ═══════════════════════════════════════════════════════════════════ */
const rfmDimensions = computed(() => [
    {
        key: 'recency', icon: '🕒', label: 'Recency (Давность)',
        description: 'Сколько дней прошло с последнего визита',
        tiers: [
            { label: '0–7 дней',  count: 4, pct: 33, color: '#22c55e' },
            { label: '8–30 дней', count: 4, pct: 33, color: '#84cc16' },
            { label: '31–60 дней', count: 2, pct: 17, color: '#f59e0b' },
            { label: '60+ дней',  count: 2, pct: 17, color: '#ef4444' },
        ],
    },
    {
        key: 'frequency', icon: '🔄', label: 'Frequency (Частота)',
        description: 'Количество визитов за всё время',
        tiers: [
            { label: '15+ визитов', count: 3, pct: 25, color: '#22c55e' },
            { label: '5–14',        count: 4, pct: 33, color: '#84cc16' },
            { label: '2–4',         count: 3, pct: 25, color: '#f59e0b' },
            { label: '0–1',         count: 2, pct: 17, color: '#ef4444' },
        ],
    },
    {
        key: 'monetary', icon: '💰', label: 'Monetary (Сумма)',
        description: 'Общая сумма покупок за всё время',
        tiers: [
            { label: '> 50 000 ₽',     count: 3, pct: 25, color: '#22c55e' },
            { label: '20 000–50 000',  count: 4, pct: 33, color: '#84cc16' },
            { label: '5 000–20 000',   count: 3, pct: 25, color: '#f59e0b' },
            { label: '< 5 000 ₽',      count: 2, pct: 17, color: '#ef4444' },
        ],
    },
]);

const rfmSegments = computed(() => [
    { name: 'Чемпионы',         icon: '🏆', r: 5, f: 5, m: 5, count: 2, avgLtv: 146000, action: 'Удержать и радовать' },
    { name: 'Лояльные клиенты', icon: '💚', r: 4, f: 4, m: 4, count: 3, avgLtv: 72000,  action: 'Персональные предложения' },
    { name: 'Перспективные',    icon: '🌱', r: 5, f: 2, m: 2, count: 2, avgLtv: 28000,  action: 'Конвертировать в постоянных' },
    { name: 'Под угрозой',      icon: '⚠️', r: 2, f: 3, m: 3, count: 2, avgLtv: 33000,  action: 'Реактивировать' },
    { name: 'Спящие',           icon: '😴', r: 1, f: 2, m: 2, count: 2, avgLtv: 14000,  action: 'Win-back кампания' },
    { name: 'Потерянные',       icon: '💔', r: 1, f: 1, m: 1, count: 1, avgLtv: 8000,   action: 'Последняя попытка' },
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  RETENTION COHORTS                                                  */
/* ═══════════════════════════════════════════════════════════════════ */
const cohortMonths = [1, 2, 3, 4, 5, 6];

const retentionCohorts = computed(() => [
    { name: 'Окт 2025', values: [85, 72, 65, 58, 52, 48] },
    { name: 'Ноя 2025', values: [88, 75, 68, 60, 55, null] },
    { name: 'Дек 2025', values: [82, 70, 62, 56, null, null] },
    { name: 'Янв 2026', values: [90, 78, 70, null, null, null] },
    { name: 'Фев 2026', values: [86, 74, null, null, null, null] },
    { name: 'Мар 2026', values: [92, null, null, null, null, null] },
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  CHURN ANALYSIS                                                     */
/* ═══════════════════════════════════════════════════════════════════ */
const churnBuckets = computed(() => {
    const total = props.clients.length || 1;
    const low = props.clients.filter(c => c.churnRisk <= 20).length;
    const med = props.clients.filter(c => c.churnRisk > 20 && c.churnRisk <= 50).length;
    const high = props.clients.filter(c => c.churnRisk > 50 && c.churnRisk <= 75).length;
    const crit = props.clients.filter(c => c.churnRisk > 75).length;

    return [
        { label: '🟢 Низкий (0–20%)',    count: low,  pct: Math.round(low / total * 100),  color: '#22c55e' },
        { label: '🟡 Средний (21–50%)',   count: med,  pct: Math.round(med / total * 100),  color: '#f59e0b' },
        { label: '🟠 Высокий (51–75%)',   count: high, pct: Math.round(high / total * 100), color: '#f97316' },
        { label: '🔴 Критичный (76–100%)', count: crit, pct: Math.round(crit / total * 100), color: '#ef4444' },
    ];
});

const churnRecommendations = [
    'Запустите win-back кампанию для 2 клиентов с риском > 75% (Регина Карпова, Полина Зайцева)',
    'Предложите персональную скидку 15% клиентам с риском 50–75% для возврата',
    'Увеличьте частоту коммуникаций с "Потерянными" — автоматические напоминания каждые 14 дней',
];

const churnRiskClients = computed(() => {
    return [...props.clients]
        .filter(c => c.churnRisk > 25)
        .sort((a, b) => b.churnRisk - a.churnRisk)
        .slice(0, 10);
});

/* ═══════════════════════════════════════════════════════════════════ */
/*  LTV BY SEGMENTS                                                    */
/* ═══════════════════════════════════════════════════════════════════ */
const ltvBySegment = computed(() => {
    const segments = ['VIP', 'Лояльная', 'Новичок', 'Потерянная'];
    const icons = { VIP: '👑', 'Лояльная': '💚', 'Новичок': '🆕', 'Потерянная': '😔' };
    const trends = { VIP: 15, 'Лояльная': 8, 'Новичок': 22, 'Потерянная': -5 };

    return segments.map(seg => {
        const clients = props.clients.filter(c => c.segment === seg);
        const totalLtv = clients.reduce((s, c) => s + (c.ltvPredicted || 0), 0);
        return {
            name: seg,
            icon: icons[seg],
            count: clients.length,
            avgLtv: clients.length > 0 ? Math.round(totalLtv / clients.length) : 0,
            trend: trends[seg],
        };
    });
});

const ltvDistribution = computed(() => [
    { label: '< 10 000 ₽',       count: props.clients.filter(c => (c.ltvPredicted || 0) < 10000).length,                                      pct: 15 },
    { label: '10 000 – 30 000',  count: props.clients.filter(c => (c.ltvPredicted || 0) >= 10000 && (c.ltvPredicted || 0) < 30000).length,  pct: 25 },
    { label: '30 000 – 70 000',  count: props.clients.filter(c => (c.ltvPredicted || 0) >= 30000 && (c.ltvPredicted || 0) < 70000).length,  pct: 33 },
    { label: '70 000 – 100 000', count: props.clients.filter(c => (c.ltvPredicted || 0) >= 70000 && (c.ltvPredicted || 0) < 100000).length, pct: 17 },
    { label: '> 100 000 ₽',      count: props.clients.filter(c => (c.ltvPredicted || 0) >= 100000).length,                                    pct: 10 },
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  MARKETING STATS                                                    */
/* ═══════════════════════════════════════════════════════════════════ */
const marketingStats = computed(() => ({
    totalCampaigns: 5,
    totalSent: 187,
    openRate: 68,
    conversionRate: 14,
}));

const campaignStats = computed(() => [
    { id: 1, name: 'Весенняя акция -20%',     channel: 'SMS',      segment: 'Лояльная',   sent: 45, opened: 32, clicks: 18, conversion: 22, revenue: 86400 },
    { id: 2, name: 'Новинки косметики',        channel: 'Push',     segment: 'VIP',        sent: 12, opened: 10, clicks: 8,  conversion: 42, revenue: 54200 },
    { id: 3, name: 'Промо дня рождения',       channel: 'WhatsApp', segment: 'Именинники', sent: 8,  opened: 7,  clicks: 5,  conversion: 38, revenue: 28600 },
    { id: 4, name: 'Вернись со скидкой',        channel: 'SMS',      segment: 'Потерянная', sent: 22, opened: 14, clicks: 6,  conversion: 9,  revenue: 12400 },
    { id: 5, name: 'Новый мастер — пробный визит', channel: 'Push', segment: 'Все',        sent: 100, opened: 62, clicks: 28, conversion: 8, revenue: 34800 },
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  SEGMENT DYNAMICS                                                   */
/* ═══════════════════════════════════════════════════════════════════ */
const segmentDynamics = computed(() => [
    { segment: 'VIP',        current: props.clients.filter(c => c.segment === 'VIP').length,        change: 1,  history: [40, 50, 55, 60, 65, 75, 80] },
    { segment: 'Лояльная',   current: props.clients.filter(c => c.segment === 'Лояльная').length,  change: 2,  history: [50, 55, 60, 65, 70, 75, 85] },
    { segment: 'Новичок',    current: props.clients.filter(c => c.segment === 'Новичок').length,   change: 3,  history: [30, 35, 40, 50, 60, 70, 90] },
    { segment: 'Потерянная', current: props.clients.filter(c => c.segment === 'Потерянная').length, change: -1, history: [60, 55, 50, 45, 40, 35, 30] },
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  SOURCE ANALYTICS                                                   */
/* ═══════════════════════════════════════════════════════════════════ */
const sourceStats = computed(() => [
    { name: 'Рекомендация',  icon: '🗣️', count: 3, pct: 75, color: '#22c55e' },
    { name: 'Instagram',     icon: '📸', count: 3, pct: 75, color: '#e1306c' },
    { name: 'Яндекс',        icon: '🔍', count: 2, pct: 50, color: '#ff0000' },
    { name: 'Сайт',          icon: '🌐', count: 1, pct: 25, color: '#3b82f6' },
    { name: 'Реклама ВК',    icon: '📣', count: 1, pct: 25, color: '#4680c2' },
    { name: 'Авито',         icon: '🏷️', count: 1, pct: 25, color: '#00aaff' },
    { name: 'Самостоятельно', icon: '🚶', count: 1, pct: 25, color: '#64748b' },
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  VISIT PREDICTIONS                                                  */
/* ═══════════════════════════════════════════════════════════════════ */
const visitPredictions = computed(() => [
    { clientId: 9,  name: 'Виктория Соловьёва', avgInterval: 12, predictedDate: '21.04.2026', confidence: 95 },
    { clientId: 11, name: 'Алина Фёдорова',     avgInterval: 17, predictedDate: '20.04.2026', confidence: 90 },
    { clientId: 1,  name: 'Мария Королёва',     avgInterval: 14, predictedDate: '22.04.2026', confidence: 92 },
    { clientId: 6,  name: 'Анастасия Кузнецова', avgInterval: 18, predictedDate: '22.04.2026', confidence: 88 },
    { clientId: 2,  name: 'Елена Петрова',      avgInterval: 21, predictedDate: '28.04.2026', confidence: 85 },
    { clientId: 4,  name: 'Ирина Морозова',     avgInterval: 25, predictedDate: '30.04.2026', confidence: 78 },
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  ACTIONS                                                            */
/* ═══════════════════════════════════════════════════════════════════ */
function exportReport() {
    const header = '\uFEFFМетрика;Значение;Период\n';
    const rows = 'LTV;Высокий;' + period.value + '\nChurn Rate;5.2%;' + period.value + '\nНовых клиентов;23;' + period.value + '\n';
    const blob = new Blob([header + rows], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `crm_analytics_${period.value}_${Date.now()}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}
</script>
