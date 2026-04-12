<script setup>
/**
 * AIConstructors — панель AI-конструкторов для всех вертикалей.
 * Запуск AI-анализа, загрузка фото, результаты, сохранённые дизайны.
 */
import { ref } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VTabs from '../UI/VTabs.vue';
import VModal from '../UI/VModal.vue';
import VInput from '../UI/VInput.vue';
import VStatCard from '../UI/VStatCard.vue';

const activeTab = ref('constructors');
const tabs = [
    { key: 'constructors', label: 'AI-конструкторы' },
    { key: 'saved', label: 'Мои дизайны', badge: 12 },
    { key: 'history', label: 'История' },
];

const showConstructorModal = ref(false);
const selectedConstructor = ref(null);
const isAnalyzing = ref(false);
const analysisResult = ref(null);

const constructors = [
    {
        id: 'beauty', name: 'Конструктор Образа', icon: '💄', color: 'from-pink-500/20 to-rose-500/10',
        description: 'Загрузите селфи — AI определит тип лица, цветотип и порекомендует причёски, макияж, уход.',
        features: ['Анализ лица', 'AR-примерка', 'Подбор мастера', 'Расчёт стоимости'],
        uses: 245, rating: 4.9
    },
    {
        id: 'interior', name: 'Конструктор Интерьера', icon: '🛋️', color: 'from-amber-500/20 to-orange-500/10',
        description: 'Фото комнаты → AI создаст 3D-визуализацию, подберёт мебель и рассчитает смету ремонта.',
        features: ['3D-визуализация', 'Подбор мебели', 'Расчёт ремонта', 'Планировки'],
        uses: 189, rating: 4.8
    },
    {
        id: 'food', name: 'Конструктор Меню', icon: '🍕', color: 'from-emerald-500/20 to-green-500/10',
        description: 'Выберите ингредиенты, диету, калории — AI создаст персональные рецепты с точным КБЖУ.',
        features: ['Генерация рецептов', 'Расчёт КБЖУ', 'Подбор ресторана', 'Доставка ингредиентов'],
        uses: 567, rating: 4.7
    },
    {
        id: 'fashion', name: 'Подбор Стиля', icon: '👗', color: 'from-violet-500/20 to-purple-500/10',
        description: 'Фото → определение цветотипа, стиля. Капсульный гардероб и виртуальная примерка (AR).',
        features: ['Цветотип', 'Капсульный гардероб', 'AR-примерка', 'Подбор образа'],
        uses: 312, rating: 4.6
    },
    {
        id: 'fitness', name: 'Фитнес-Конструктор', icon: '💪', color: 'from-blue-500/20 to-cyan-500/10',
        description: 'Анализ тела → персональный план тренировок, питания и подбор спортивных добавок.',
        features: ['Анализ тела', 'План тренировок', 'План питания', 'Виртуальный тренер'],
        uses: 198, rating: 4.8
    },
    {
        id: 'realestate', name: 'Дизайн Квартиры', icon: '🏠', color: 'from-teal-500/20 to-emerald-500/10',
        description: 'План квартиры или фото → генерация планировок, дизайна интерьера, 3D-тур и смета.',
        features: ['3D-тур', 'Планировки', 'Дизайн интерьера', 'Смета ремонта'],
        uses: 87, rating: 4.9
    },
    {
        id: 'auto', name: 'Авто-Конструктор', icon: '🚗', color: 'from-slate-500/20 to-zinc-500/10',
        description: 'Визуализация тюнинга, дефектовка по фото, подбор запчастей и запись на сервис.',
        features: ['Визуализация тюнинга', 'Дефектовка', 'Подбор запчастей', 'Запись на СТО'],
        uses: 134, rating: 4.5
    },
    {
        id: 'travel', name: 'Планировщик Путешествий', icon: '✈️', color: 'from-sky-500/20 to-blue-500/10',
        description: 'AI построит полный маршрут путешествия: билеты, отели, экскурсии, страховки.',
        features: ['Генерация маршрута', 'Бронирование', 'Виртуальные туры', 'Бюджет поездки'],
        uses: 156, rating: 4.7
    },
];

const savedDesigns = [
    { id: 1, vertical: 'beauty', name: 'Мой стиль — весна 2026', date: '2026-04-06', preview: '💄', confidence: 0.95 },
    { id: 2, vertical: 'interior', name: 'Дизайн гостиной', date: '2026-04-03', preview: '🛋️', confidence: 0.94 },
    { id: 3, vertical: 'food', name: 'Меню на неделю — кето', date: '2026-04-01', preview: '🥗', confidence: 0.97 },
    { id: 4, vertical: 'fashion', name: 'Капсулярный гардероб', date: '2026-03-28', preview: '👗', confidence: 0.92 },
];

function openConstructor(constructor) {
    selectedConstructor.value = constructor;
    showConstructorModal.value = true;
    analysisResult.value = null;
    isAnalyzing.value = false;
}

function runAnalysis() {
    isAnalyzing.value = true;
    setTimeout(() => {
        isAnalyzing.value = false;
        analysisResult.value = {
            success: true,
            confidence: 0.95,
            recommendations: 12,
            arAvailable: true,
        };
    }, 3000);
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-linear-to-br from-violet-500/20 to-purple-500/10 border border-violet-500/20 flex items-center justify-center text-2xl shadow-lg shadow-violet-500/10">
                    🤖
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-bold text-(--t-text)">AI-Конструкторы</h1>
                    <p class="text-xs text-(--t-text-3)">Визуальный AI-анализ для всех вертикалей</p>
                </div>
            </div>
        </div>

        <!-- Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <VStatCard title="Всего анализов" value="1,888" icon="🤖" :trend="28.5" color="indigo" clickable />
            <VStatCard title="Точность AI" value="94.2%" icon="🎯" :trend="2.1" color="emerald" clickable />
            <VStatCard title="Сохранённых дизайнов" value="12" icon="💾" color="amber" clickable />
            <VStatCard title="AR-примерок" value="342" icon="📱" :trend="45.2" color="primary" clickable />
        </div>

        <!-- Tabs -->
        <VTabs :tabs="tabs" v-model="activeTab" variant="underline" />

        <!-- Constructors Grid -->
        <template v-if="activeTab === 'constructors'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div v-for="constructor in constructors" :key="constructor.id"
                     class="group relative rounded-2xl border border-(--t-border) bg-(--t-surface) overflow-hidden cursor-pointer hover:-translate-y-1 hover:shadow-xl hover:shadow-(--t-primary)/10 transition-all duration-300 active:scale-[0.98]"
                     @click="openConstructor(constructor)"
                >
                    <!-- Gradient header -->
                    <div :class="`h-28 bg-linear-to-br ${constructor.color} flex items-center justify-center relative overflow-hidden`">
                        <span class="text-5xl group-hover:scale-110 transition-transform duration-300">{{ constructor.icon }}</span>
                        <div class="absolute inset-0 bg-linear-to-t from-(--t-surface) to-transparent opacity-50" />
                    </div>

                    <!-- Content -->
                    <div class="p-4 space-y-3">
                        <div>
                            <h3 class="text-sm font-bold text-(--t-text) group-hover:text-(--t-primary) transition-colors">{{ constructor.name }}</h3>
                            <p class="text-xs text-(--t-text-3) mt-1 line-clamp-2 leading-relaxed">{{ constructor.description }}</p>
                        </div>

                        <!-- Features -->
                        <div class="flex flex-wrap gap-1">
                            <span v-for="feature in constructor.features" :key="feature"
                                  class="px-1.5 py-0.5 rounded text-[9px] bg-(--t-card-hover) text-(--t-text-3)"
                            >{{ feature }}</span>
                        </div>

                        <!-- Stats -->
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-(--t-text-3)">{{ constructor.uses }} использований</span>
                            <span class="text-amber-400">★ {{ constructor.rating }}</span>
                        </div>

                        <VButton variant="primary" size="sm" full-width>Запустить AI →</VButton>
                    </div>
                </div>
            </div>
        </template>

        <!-- Saved Designs -->
        <template v-if="activeTab === 'saved'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="design in savedDesigns" :key="design.id"
                     class="p-4 rounded-xl border border-(--t-border) hover:border-(--t-primary)/20 bg-(--t-surface) cursor-pointer hover:shadow-lg transition-all active:scale-[0.98]"
                >
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-xl bg-(--t-card-hover) flex items-center justify-center text-2xl">{{ design.preview }}</div>
                        <div>
                            <div class="text-sm font-semibold text-(--t-text)">{{ design.name }}</div>
                            <div class="text-[10px] text-(--t-text-3)">{{ design.date }}</div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <VBadge :text="`AI ${(design.confidence * 100).toFixed(0)}%`" variant="success" size="xs" />
                        <div class="flex gap-1">
                            <VButton variant="ghost" size="xs">👁️ Просмотр</VButton>
                            <VButton variant="ghost" size="xs">📥 Скачать</VButton>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- History -->
        <template v-if="activeTab === 'history'">
            <VCard title="📋 История AI-анализов">
                <div class="space-y-2">
                    <div v-for="h in [
                        {vertical:'beauty',action:'Анализ лица',date:'2026-04-06 14:20',confidence:0.95,result:'Успех'},
                        {vertical:'interior',action:'3D-визуализация',date:'2026-04-03 10:15',confidence:0.94,result:'Успех'},
                        {vertical:'food',action:'Генерация меню',date:'2026-04-01 18:30',confidence:0.97,result:'Успех'},
                        {vertical:'fashion',action:'Капсульный гардероб',date:'2026-03-28 12:45',confidence:0.92,result:'Успех'},
                    ]" :key="h.date"
                       class="flex items-center gap-3 p-3 rounded-xl hover:bg-(--t-card-hover) transition-colors cursor-pointer active:scale-[0.99]"
                    >
                        <div class="w-8 h-8 rounded-lg bg-(--t-primary-dim) flex items-center justify-center text-sm">🤖</div>
                        <div class="flex-1">
                            <div class="text-sm text-(--t-text)">{{ h.action }} ({{ h.vertical }})</div>
                            <div class="text-[10px] text-(--t-text-3)">{{ h.date }}</div>
                        </div>
                        <VBadge :text="`${(h.confidence * 100).toFixed(0)}%`" variant="success" size="xs" />
                    </div>
                </div>
            </VCard>
        </template>

        <!-- Constructor Modal -->
        <VModal v-model="showConstructorModal" :title="selectedConstructor?.name" size="lg">
            <template v-if="selectedConstructor">
                <div class="space-y-4">
                    <!-- Info -->
                    <div class="p-4 rounded-xl bg-linear-to-br from-(--t-card-hover) to-(--t-surface) border border-(--t-border)">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-3xl">{{ selectedConstructor.icon }}</span>
                            <p class="text-sm text-(--t-text-2)">{{ selectedConstructor.description }}</p>
                        </div>
                    </div>

                    <!-- Upload area -->
                    <div v-if="!analysisResult"
                         class="h-40 border-2 border-dashed border-(--t-border) rounded-xl flex flex-col items-center justify-center gap-2 cursor-pointer hover:border-(--t-primary) hover:bg-(--t-primary-dim) transition-all active:scale-[0.99]"
                    >
                        <span class="text-4xl">📷</span>
                        <span class="text-sm text-(--t-text-2)">Нажмите для загрузки фото</span>
                        <span class="text-[10px] text-(--t-text-3)">JPG, PNG до 10 МБ</span>
                    </div>

                    <!-- Loading state -->
                    <div v-if="isAnalyzing" class="h-40 rounded-xl bg-(--t-card-hover) flex flex-col items-center justify-center gap-3">
                        <div class="w-12 h-12 border-3 border-(--t-primary)/20 border-t-(--t-primary) rounded-full animate-spin" />
                        <span class="text-sm text-(--t-text-2)">AI анализирует...</span>
                        <div class="w-48 h-1.5 rounded-full bg-(--t-border) overflow-hidden">
                            <div class="h-full rounded-full bg-(--t-primary) animate-pulse" style="width:60%" />
                        </div>
                    </div>

                    <!-- Result -->
                    <div v-if="analysisResult" class="space-y-3">
                        <div class="p-4 rounded-xl bg-emerald-500/5 border border-emerald-500/20">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">✅</span>
                                <span class="text-sm font-semibold text-emerald-400">Анализ завершён</span>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="text-center">
                                    <div class="text-lg font-bold text-(--t-text)">{{ (analysisResult.confidence * 100).toFixed(0) }}%</div>
                                    <div class="text-[10px] text-(--t-text-3)">Точность</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-(--t-text)">{{ analysisResult.recommendations }}</div>
                                    <div class="text-[10px] text-(--t-text-3)">Рекомендаций</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-emerald-400">{{ analysisResult.arAvailable ? 'Да' : 'Нет' }}</div>
                                    <div class="text-[10px] text-(--t-text-3)">AR доступен</div>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <VButton variant="primary" size="sm">📱 AR-примерка</VButton>
                            <VButton variant="secondary" size="sm">💾 Сохранить</VButton>
                            <VButton variant="ghost" size="sm">📥 Скачать PDF</VButton>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <VButton variant="secondary" @click="showConstructorModal = false">Закрыть</VButton>
                <VButton v-if="!analysisResult && !isAnalyzing" variant="primary" @click="runAnalysis" :loading="isAnalyzing">
                    🤖 Запустить AI-анализ
                </VButton>
            </template>
        </VModal>
    </div>
</template>
