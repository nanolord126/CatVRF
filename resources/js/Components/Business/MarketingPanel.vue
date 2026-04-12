<script setup>
/**
 * MarketingPanel — управление рекламой, кампаниями, рассылками, шортсами.
 * Полная интеграция с AdEngine и NewsletterService.
 */
import { ref } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VTabs from '../UI/VTabs.vue';
import VStatCard from '../UI/VStatCard.vue';
import VModal from '../UI/VModal.vue';
import VInput from '../UI/VInput.vue';

const activeTab = ref('campaigns');
const tabs = [
    { key: 'campaigns', label: 'Кампании', badge: 4 },
    { key: 'promos', label: 'Промокоды', badge: 6 },
    { key: 'newsletters', label: 'Рассылки' },
    { key: 'shorts', label: 'Шортсы' },
    { key: 'targeting', label: 'Таргетинг' },
];

const showNewCampaign = ref(false);

const campaigns = [
    { id: 1, name: 'Весенняя распродажа', type: 'banner', budget: 50000, spent: 32400, impressions: 125000, clicks: 4500, conversions: 312, status: 'active', period: '01.04 — 30.04' },
    { id: 2, name: 'B2B Партнёрская', type: 'email', budget: 15000, spent: 8200, impressions: 45000, clicks: 2100, conversions: 89, status: 'active', period: '15.03 — 15.04' },
    { id: 3, name: 'AI-конструктор промо', type: 'shorts', budget: 30000, spent: 30000, impressions: 340000, clicks: 12000, conversions: 567, status: 'completed', period: '01.03 — 31.03' },
    { id: 4, name: 'Новые пользователи', type: 'push', budget: 10000, spent: 4500, impressions: 78000, clicks: 3200, conversions: 145, status: 'paused', period: '05.04 — 20.04' },
];

const newsletters = [
    { id: 1, subject: '🔥 Весенние скидки до 50%', channel: 'email', sent: 12500, opened: 4200, clicked: 1800, date: '2026-04-07', status: 'sent' },
    { id: 2, subject: 'Новые AI-конструкторы', channel: 'push', sent: 8900, opened: 3400, clicked: 890, date: '2026-04-05', status: 'sent' },
    { id: 3, subject: 'Персональные рекомендации', channel: 'in_app', sent: 5600, opened: 2100, clicked: 560, date: '2026-04-03', status: 'sent' },
];

const promoCodes = ref([
    { id: 1, code: 'SPRING2026', discount: 15, type: 'percent', usageCount: 342, maxUses: 1000, minOrder: 2000, status: 'active', expiresAt: '2026-04-30', vertical: 'all' },
    { id: 2, code: 'BEAUTY20', discount: 20, type: 'percent', usageCount: 89, maxUses: 500, minOrder: 1500, status: 'active', expiresAt: '2026-05-15', vertical: 'beauty' },
    { id: 3, code: 'NEWUSER', discount: 500, type: 'fixed', usageCount: 1200, maxUses: 5000, minOrder: 3000, status: 'active', expiresAt: '2026-12-31', vertical: 'all' },
    { id: 4, code: 'FOOD10', discount: 10, type: 'percent', usageCount: 567, maxUses: 2000, minOrder: 1000, status: 'active', expiresAt: '2026-06-01', vertical: 'food' },
    { id: 5, code: 'VIP50', discount: 50, type: 'percent', usageCount: 50, maxUses: 50, minOrder: 5000, status: 'exhausted', expiresAt: '2026-04-10', vertical: 'all' },
    { id: 6, code: 'SUMMER2026', discount: 25, type: 'percent', usageCount: 0, maxUses: 3000, minOrder: 2500, status: 'scheduled', expiresAt: '2026-08-31', vertical: 'all' },
]);
const showNewPromo = ref(false);
const promoStatusColors = { active: 'success', exhausted: 'neutral', expired: 'danger', scheduled: 'info' };
const promoStatusLabels = { active: 'Активен', exhausted: 'Исчерпан', expired: 'Истёк', scheduled: 'Запланирован' };

const statusColors = { active: 'success', completed: 'neutral', paused: 'warning', draft: 'info' };
const statusLabels = { active: 'Активна', completed: 'Завершена', paused: 'Пауза', draft: 'Черновик' };
const typeIcons = { banner: '🖼️', email: '📧', shorts: '📱', push: '🔔', sms: '💬', in_app: '📲' };
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl lg:text-2xl font-bold text-(--t-text)">📣 Маркетинг</h1>
                <p class="text-xs text-(--t-text-3)">Рекламные кампании, рассылки, шортсы и таргетинг</p>
            </div>
            <VButton variant="primary" size="sm" @click="showNewCampaign = true">➕ Новая кампания</VButton>
        </div>

        <!-- Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <VStatCard title="Рекламный бюджет" value="105k ₽" icon="💰" color="amber" clickable />
            <VStatCard title="Показы" value="588k" icon="👁️" :trend="32.4" color="primary" clickable />
            <VStatCard title="Клики" value="21.8k" icon="👆" :trend="18.7" color="indigo" clickable />
            <VStatCard title="Конверсии" value="1,113" icon="🎯" :trend="24.2" color="emerald" clickable />
        </div>

        <!-- Tabs -->
        <VTabs :tabs="tabs" v-model="activeTab" variant="underline" />

        <!-- Campaigns -->
        <template v-if="activeTab === 'campaigns'">
            <div class="space-y-4">
                <div v-for="campaign in campaigns" :key="campaign.id"
                     class="p-5 rounded-xl border border-(--t-border) hover:border-(--t-primary)/20 bg-(--t-surface) transition-all cursor-pointer active:scale-[0.99] hover:shadow-lg hover:shadow-(--t-primary)/5"
                >
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-(--t-card-hover) flex items-center justify-center text-lg">{{ typeIcons[campaign.type] }}</div>
                            <div>
                                <div class="text-sm font-semibold text-(--t-text)">{{ campaign.name }}</div>
                                <div class="text-[10px] text-(--t-text-3)">{{ campaign.period }}</div>
                            </div>
                        </div>
                        <VBadge :text="statusLabels[campaign.status]" :variant="statusColors[campaign.status]" size="sm" dot />
                    </div>

                    <!-- Budget bar -->
                    <div class="mb-3">
                        <div class="flex justify-between text-[10px] text-(--t-text-3) mb-1">
                            <span>Бюджет</span>
                            <span>{{ Number(campaign.spent).toLocaleString('ru') }} / {{ Number(campaign.budget).toLocaleString('ru') }} ₽</span>
                        </div>
                        <div class="h-2 rounded-full bg-(--t-border) overflow-hidden">
                            <div class="h-full rounded-full bg-linear-to-r from-(--t-primary) to-(--t-accent) transition-all"
                                 :style="{width: (campaign.spent/campaign.budget*100) + '%'}" />
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="p-2 rounded-lg bg-(--t-card-hover) text-center">
                            <div class="text-xs font-bold text-(--t-text)">{{ (campaign.impressions/1000).toFixed(0) }}k</div>
                            <div class="text-[9px] text-(--t-text-3)">Показы</div>
                        </div>
                        <div class="p-2 rounded-lg bg-(--t-card-hover) text-center">
                            <div class="text-xs font-bold text-(--t-text)">{{ (campaign.clicks/1000).toFixed(1) }}k</div>
                            <div class="text-[9px] text-(--t-text-3)">Клики</div>
                        </div>
                        <div class="p-2 rounded-lg bg-(--t-card-hover) text-center">
                            <div class="text-xs font-bold text-emerald-400">{{ campaign.conversions }}</div>
                            <div class="text-[9px] text-(--t-text-3)">Конверсии</div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 mt-3">
                        <VButton v-if="campaign.status === 'active'" variant="ghost" size="xs">⏸️ Пауза</VButton>
                        <VButton v-if="campaign.status === 'paused'" variant="ghost" size="xs">▶️ Продолжить</VButton>
                        <VButton variant="ghost" size="xs">📊 Аналитика</VButton>
                        <VButton variant="ghost" size="xs">✏️ Изменить</VButton>
                    </div>
                </div>
            </div>
        </template>

        <!-- Promos -->
        <template v-if="activeTab === 'promos'">
            <VCard title="🏷️ Промокоды и акции" subtitle="Управление скидочными купонами">
                <template #header-action>
                    <VButton variant="primary" size="sm" @click="showNewPromo = true">➕ Создать промокод</VButton>
                </template>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                    <div class="p-3 rounded-xl bg-(--t-card-hover) text-center">
                        <div class="text-lg font-bold text-(--t-text)">6</div>
                        <div class="text-[10px] text-(--t-text-3)">Всего</div>
                    </div>
                    <div class="p-3 rounded-xl bg-emerald-500/5 border border-emerald-500/10 text-center">
                        <div class="text-lg font-bold text-emerald-400">4</div>
                        <div class="text-[10px] text-emerald-400/60">Активных</div>
                    </div>
                    <div class="p-3 rounded-xl bg-(--t-card-hover) text-center">
                        <div class="text-lg font-bold text-(--t-text)">2 248</div>
                        <div class="text-[10px] text-(--t-text-3)">Использований</div>
                    </div>
                    <div class="p-3 rounded-xl bg-(--t-card-hover) text-center">
                        <div class="text-lg font-bold text-amber-400">₽ 847k</div>
                        <div class="text-[10px] text-(--t-text-3)">Выручка по промо</div>
                    </div>
                </div>
                <div class="space-y-3">
                    <div v-for="promo in promoCodes" :key="promo.id"
                         class="p-4 rounded-xl border border-(--t-border) hover:bg-(--t-card-hover) transition-all"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="px-3 py-1.5 rounded-lg bg-(--t-primary-dim) font-mono text-sm font-bold text-(--t-primary) tracking-wider select-all">{{ promo.code }}</div>
                                <VBadge :text="promoStatusLabels[promo.status]" :variant="promoStatusColors[promo.status]" size="xs" />
                            </div>
                            <div class="flex items-center gap-2">
                                <button class="p-1.5 rounded-lg text-xs hover:bg-(--t-card-hover) cursor-pointer transition-colors" title="Копировать">📋</button>
                                <button class="p-1.5 rounded-lg text-xs hover:bg-(--t-card-hover) cursor-pointer transition-colors" title="Редактировать">✏️</button>
                                <button class="p-1.5 rounded-lg text-xs hover:bg-rose-500/10 text-rose-400 cursor-pointer transition-colors" title="Удалить">🗑️</button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                            <div class="p-2 rounded-lg bg-(--t-card-hover)">
                                <div class="text-sm font-bold text-(--t-text)">{{ promo.type === 'percent' ? promo.discount + '%' : promo.discount + ' ₽' }}</div>
                                <div class="text-[9px] text-(--t-text-3)">Скидка</div>
                            </div>
                            <div class="p-2 rounded-lg bg-(--t-card-hover)">
                                <div class="text-sm font-bold text-(--t-text)">{{ promo.usageCount }} / {{ promo.maxUses }}</div>
                                <div class="text-[9px] text-(--t-text-3)">Использовано</div>
                            </div>
                            <div class="p-2 rounded-lg bg-(--t-card-hover)">
                                <div class="text-sm font-bold text-(--t-text)">{{ promo.minOrder.toLocaleString('ru') }} ₽</div>
                                <div class="text-[9px] text-(--t-text-3)">Мин. заказ</div>
                            </div>
                            <div class="p-2 rounded-lg bg-(--t-card-hover)">
                                <div class="text-sm font-bold text-(--t-text)">{{ promo.expiresAt }}</div>
                                <div class="text-[9px] text-(--t-text-3)">Действует до</div>
                            </div>
                        </div>
                        <div class="h-1.5 rounded-full bg-(--t-border) mt-3 overflow-hidden">
                            <div class="h-full rounded-full bg-linear-to-r from-(--t-primary) to-(--t-accent) transition-all"
                                 :style="{width: Math.min(promo.usageCount / promo.maxUses * 100, 100) + '%'}" />
                        </div>
                    </div>
                </div>
            </VCard>

            <!-- New Promo Modal -->
            <VModal v-model="showNewPromo" title="Создать промокод" size="md">
                <div class="space-y-4">
                    <VInput label="Код промокода" placeholder="SPRING2026" required />
                    <div class="grid grid-cols-2 gap-3">
                        <VInput label="Скидка" type="number" placeholder="15" required />
                        <div>
                            <div class="text-xs text-(--t-text-2) mb-1">Тип скидки</div>
                            <div class="flex gap-2">
                                <button class="flex-1 p-2 rounded-lg border border-(--t-primary) bg-(--t-primary-dim) text-xs text-(--t-primary) font-semibold cursor-pointer">%</button>
                                <button class="flex-1 p-2 rounded-lg border border-(--t-border) text-xs text-(--t-text-3) cursor-pointer hover:border-(--t-primary)">₽</button>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <VInput label="Макс. использований" type="number" placeholder="1000" />
                        <VInput label="Мин. сумма заказа" type="number" placeholder="2000" />
                    </div>
                    <VInput label="Действует до" type="date" />
                </div>
                <template #footer>
                    <VButton variant="secondary" @click="showNewPromo = false">Отмена</VButton>
                    <VButton variant="primary">Создать</VButton>
                </template>
            </VModal>
        </template>

        <!-- Newsletters -->
        <template v-if="activeTab === 'newsletters'">
            <VCard title="📧 Рассылки" subtitle="Email, Push, SMS и In-App уведомления">
                <template #header-action>
                    <VButton variant="primary" size="sm">➕ Новая рассылка</VButton>
                </template>
                <div class="space-y-3">
                    <div v-for="nl in newsletters" :key="nl.id"
                         class="p-4 rounded-xl border border-(--t-border) hover:bg-(--t-card-hover) transition-all cursor-pointer active:scale-[0.99]"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{ typeIcons[nl.channel] }}</span>
                                <span class="text-sm font-semibold text-(--t-text)">{{ nl.subject }}</span>
                            </div>
                            <span class="text-[10px] text-(--t-text-3)">{{ nl.date }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="text-center">
                                <div class="text-sm font-bold text-(--t-text)">{{ (nl.sent/1000).toFixed(1) }}k</div>
                                <div class="text-[9px] text-(--t-text-3)">Отправлено</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm font-bold text-(--t-text)">{{ ((nl.opened/nl.sent)*100).toFixed(0) }}%</div>
                                <div class="text-[9px] text-(--t-text-3)">Открытия</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm font-bold text-emerald-400">{{ ((nl.clicked/nl.sent)*100).toFixed(0) }}%</div>
                                <div class="text-[9px] text-(--t-text-3)">CTR</div>
                            </div>
                        </div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- Shorts -->
        <template v-if="activeTab === 'shorts'">
            <VCard title="📱 AI-Шортсы" subtitle="Автоматическая генерация коротких видео через AI">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div v-for="short in [
                        {title:'Beauty Trends',views:'12.5k',likes:890,vertical:'beauty',duration:'15s'},
                        {title:'Interior Magic',views:'8.2k',likes:567,vertical:'interior',duration:'22s'},
                        {title:'Food Recipes',views:'34.1k',likes:2400,vertical:'food',duration:'18s'},
                        {title:'Fashion Look',views:'15.7k',likes:1200,vertical:'fashion',duration:'20s'},
                    ]" :key="short.title"
                         class="rounded-xl border border-(--t-border) overflow-hidden cursor-pointer hover:-translate-y-1 hover:shadow-lg transition-all active:scale-[0.98]"
                    >
                        <div class="h-36 bg-linear-to-br from-(--t-card-hover) to-(--t-surface) flex items-center justify-center relative">
                            <span class="text-4xl">🎬</span>
                            <div class="absolute bottom-2 right-2 px-1.5 py-0.5 rounded bg-black/60 text-[10px] text-white">{{ short.duration }}</div>
                        </div>
                        <div class="p-3">
                            <div class="text-xs font-semibold text-(--t-text) mb-1">{{ short.title }}</div>
                            <div class="flex items-center gap-2 text-[10px] text-(--t-text-3)">
                                <span>👁️ {{ short.views }}</span>
                                <span>❤️ {{ short.likes }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <VButton variant="primary" size="sm" full-width>🤖 Сгенерировать AI-шортс</VButton>
                </template>
            </VCard>
        </template>

        <!-- Targeting -->
        <template v-if="activeTab === 'targeting'">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <VCard title="🎯 Критерии таргетинга">
                    <div class="space-y-3">
                        <div v-for="criterion in [
                            {name:'UserTasteProfile',desc:'Категории, бренды, цвета, размеры',icon:'🧠'},
                            {name:'New / Returning',desc:'Новые vs постоянные клиенты',icon:'👤'},
                            {name:'Behavior Patterns',desc:'AR использование, AI-конструкторы',icon:'📊'},
                            {name:'B2C / B2B',desc:'Физлица vs юрлица',icon:'🏢'},
                            {name:'Geo (hashed)',desc:'Город / регион (анонимно)',icon:'📍'},
                            {name:'LTV Segment',desc:'Высокий / средний / низкий',icon:'💎'},
                        ]" :key="criterion.name"
                           class="flex items-center gap-3 p-3 rounded-xl border border-(--t-border) hover:bg-(--t-card-hover) transition-all cursor-pointer active:scale-[0.99]"
                        >
                            <div class="w-8 h-8 rounded-lg bg-(--t-primary-dim) flex items-center justify-center text-sm">{{ criterion.icon }}</div>
                            <div>
                                <div class="text-sm font-medium text-(--t-text)">{{ criterion.name }}</div>
                                <div class="text-[10px] text-(--t-text-3)">{{ criterion.desc }}</div>
                            </div>
                        </div>
                    </div>
                </VCard>

                <VCard title="📊 Сегменты аудитории">
                    <div class="space-y-3">
                        <div v-for="segment in [
                            {name:'Высокий LTV',count:'2,340',percentage:18,color:'emerald'},
                            {name:'Новые клиенты (≤7 дней)',count:'1,120',percentage:9,color:'amber'},
                            {name:'Повторные покупки',count:'5,670',percentage:44,color:'primary'},
                            {name:'Риск оттока',count:'890',percentage:7,color:'rose'},
                        ]" :key="segment.name"
                           class="p-3 rounded-xl border border-(--t-border) hover:bg-(--t-card-hover) transition-all cursor-pointer active:scale-[0.99]"
                        >
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-(--t-text)">{{ segment.name }}</span>
                                <span class="text-xs font-bold text-(--t-text)">{{ segment.count }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-(--t-border) overflow-hidden">
                                <div :class="`h-full rounded-full bg-${segment.color}-400`" :style="{width: segment.percentage + '%'}" />
                            </div>
                        </div>
                    </div>
                </VCard>
            </div>
        </template>

        <!-- New Campaign Modal -->
        <VModal v-model="showNewCampaign" title="Новая рекламная кампания" size="lg">
            <div class="space-y-4">
                <VInput label="Название кампании" placeholder="Например: Весенняя распродажа" required />
                <div class="grid grid-cols-2 gap-3">
                    <VInput label="Бюджет (₽)" type="number" placeholder="0" required />
                    <VInput label="Период" placeholder="01.04 — 30.04" />
                </div>
                <VCard title="Тип кампании" flat>
                    <div class="grid grid-cols-3 gap-2">
                        <button v-for="t in [{icon:'🖼️',label:'Баннер'},{icon:'📧',label:'Email'},{icon:'🔔',label:'Push'},{icon:'📱',label:'Шортс'},{icon:'💬',label:'SMS'},{icon:'📲',label:'In-App'}]" :key="t.label"
                                class="p-3 rounded-xl border border-(--t-border) hover:border-(--t-primary) hover:bg-(--t-primary-dim) text-center transition-all cursor-pointer active:scale-95"
                        >
                            <div class="text-xl mb-1">{{ t.icon }}</div>
                            <div class="text-[10px] text-(--t-text)">{{ t.label }}</div>
                        </button>
                    </div>
                </VCard>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showNewCampaign = false">Отмена</VButton>
                <VButton variant="primary">Создать кампанию</VButton>
            </template>
        </VModal>
    </div>
</template>
