<script setup>
/**
 * BeautyTryOn — AI-конструктор образа с виртуальной AR-примеркой.
 * Загрузка фото → анализ (тип лица, тон кожи, цветотип) →
 * рекомендации причёсок, макияжа, ухода → AR-превью → запись к мастеру.
 */
import { ref, computed, reactive, watch } from 'vue';
import VCard from '../../UI/VCard.vue';
import VButton from '../../UI/VButton.vue';
import VBadge from '../../UI/VBadge.vue';
import VModal from '../../UI/VModal.vue';

const props = defineProps({
    masters: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
});
const emit = defineEmits(['book-master', 'save-design', 'share-result']);

function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }

/* ─── Upload & Analyze ─── */
const fileInput = ref(null);
const uploadedPhoto = ref(null);
const uploadedPhotoUrl = ref('');
const isAnalyzing = ref(false);
const analysisComplete = ref(false);
const analysisProgress = ref(0);

function triggerUpload() {
    fileInput.value?.click();
}

function handleFileUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    uploadedPhoto.value = file;
    uploadedPhotoUrl.value = URL.createObjectURL(file);
    analysisComplete.value = false;
    analysisResult.value = null;
}

function handleDrop(e) {
    e.preventDefault();
    const file = e.dataTransfer?.files?.[0];
    if (!file || !file.type.startsWith('image/')) return;
    uploadedPhoto.value = file;
    uploadedPhotoUrl.value = URL.createObjectURL(file);
    analysisComplete.value = false;
    analysisResult.value = null;
}

/* ─── AI Analysis ─── */
const analysisResult = ref(null);
const analysisSteps = [
    'Определение типа лица...',
    'Анализ тона кожи...',
    'Определение цветотипа...',
    'Анализ состояния кожи...',
    'Определение текущей причёски...',
    'Подбор рекомендаций...',
    'Формирование AR-превью...',
    'Генерация образов...',
];
const currentStep = ref(0);

async function startAnalysis() {
    if (!uploadedPhoto.value) return;
    isAnalyzing.value = true;
    analysisProgress.value = 0;
    currentStep.value = 0;

    for (let i = 0; i < analysisSteps.length; i++) {
        currentStep.value = i;
        analysisProgress.value = ((i + 1) / analysisSteps.length) * 100;
        await new Promise(r => setTimeout(r, 400 + Math.random() * 300));
    }

    analysisResult.value = {
        faceType: 'Овальное',
        skinTone: 'Светлый (I-II фототип)',
        colorType: 'Лето',
        skinCondition: 'Хорошее (лёгкая сухость)',
        hairColor: 'Русый',
        hairLength: 'Средние (до плеч)',
        browShape: 'Дугообразные',
        eyeColor: 'Серо-голубые',
        age: '28-32',
        confidence: 0.94,
    };

    recommendedStyles.value = [
        { id: 1, name: 'Балаяж «Солнечный»', type: 'color', image: '🎨', match: 96, price: 8500, duration: '2.5 ч', description: 'Мягкие тёплые блики на русых волосах — идеально для цветотипа Лето', master: 'Анна Иванова' },
        { id: 2, name: 'AirTouch мягкий', type: 'color', image: '✨', match: 93, price: 12000, duration: '3 ч', description: 'Невесомое осветление с плавными переходами, подчеркнёт тон кожи', master: 'Анна Иванова' },
        { id: 3, name: 'Каре с удлинением', type: 'cut', image: '✂️', match: 91, price: 3500, duration: '1 ч', description: 'Классика для овального лица, визуально добавляет объём', master: 'Татьяна Волкова' },
        { id: 4, name: 'Лёгкие локоны', type: 'styling', image: '🌊', match: 89, price: 2500, duration: '40 мин', description: 'Расслабленные волны — универсальная укладка для вашего типа', master: 'Татьяна Волкова' },
        { id: 5, name: 'Нюдовый макияж', type: 'makeup', image: '💄', match: 95, price: 4000, duration: '1 ч', description: 'Подчёркивает естественную красоту, идеально для светлого тона', master: 'Мария Смирнова' },
        { id: 6, name: 'Уход Hydra Glow', type: 'care', image: '💧', match: 88, price: 5500, duration: '1.5 ч', description: 'Глубокое увлажнение для сухой кожи + сияние', master: 'Мария Смирнова' },
        { id: 7, name: 'Оформление бровей «Пушистые»', type: 'brows', image: '🪶', match: 90, price: 2000, duration: '30 мин', description: 'Натуральный объём, подчёркивает дугообразную форму', master: 'Елена Козлова' },
        { id: 8, name: 'Ламинирование ресниц', type: 'lashes', image: '👁️', match: 87, price: 3000, duration: '1 ч', description: 'Эффект распахнутого взгляда без наращивания', master: 'Елена Козлова' },
    ];

    careSuggestions.value = [
        { name: 'Увлажняющий крем SPF 30', reason: 'Для светлого тона кожи обязательна ежедневная защита', price: 2800 },
        { name: 'Сыворотка с гиалуроновой кислотой', reason: 'Восполнит недостаток влаги при сухости', price: 3500 },
        { name: 'Шампунь без сульфатов', reason: 'Сохранит окрашивание и здоровье русых волос', price: 1200 },
        { name: 'Маска для волос с кератином', reason: 'Восстановление после окрашивания', price: 1800 },
    ];

    isAnalyzing.value = false;
    analysisComplete.value = true;
}

/* ─── Recommendations ─── */
const recommendedStyles = ref([]);
const careSuggestions = ref([]);
const activeStyleFilter = ref('all');
const styleFilters = [
    { key: 'all', label: 'Все' },
    { key: 'color', label: '🎨 Окрашивание' },
    { key: 'cut', label: '✂️ Стрижка' },
    { key: 'styling', label: '🌊 Укладка' },
    { key: 'makeup', label: '💄 Макияж' },
    { key: 'care', label: '💧 Уход' },
    { key: 'brows', label: '🪶 Брови' },
    { key: 'lashes', label: '👁️ Ресницы' },
];

const filteredStyles = computed(() => {
    if (activeStyleFilter.value === 'all') return recommendedStyles.value;
    return recommendedStyles.value.filter(s => s.type === activeStyleFilter.value);
});

/* ─── AR preview ─── */
const showARPreview = ref(false);
const activeARStyle = ref(null);

function openARPreview(style) {
    activeARStyle.value = style;
    showARPreview.value = true;
}

/* ─── Saved designs ─── */
const savedDesigns = ref([]);
function saveDesign(style) {
    if (savedDesigns.value.find(d => d.id === style.id)) return;
    savedDesigns.value.push({ ...style, savedAt: new Date().toISOString() });
    emit('save-design', style);
}

/* ─── Book master ─── */
function bookForStyle(style) {
    emit('book-master', { master: style.master, service: style.name, price: style.price });
}

/* ─── Share ─── */
function shareResult() {
    const data = {
        analysis: analysisResult.value,
        recommendations: recommendedStyles.value.slice(0, 3),
    };
    if (navigator.share) {
        navigator.share({
            title: 'Мой AI-образ — CatVRF Beauty',
            text: `AI подобрал мне образ! Цветотип: ${analysisResult.value.colorType}, тип лица: ${analysisResult.value.faceType}`,
        }).catch(() => {
            emit('share-result', data);
        });
        return;
    }
    emit('share-result', data);
}

/* ─── History ─── */
const designHistory = ref([
    { id: 1, date: '05.04.2026', colorType: 'Лето', topStyle: 'Балаяж', stylesCount: 6 },
    { id: 2, date: '12.03.2026', colorType: 'Лето', topStyle: 'AirTouch', stylesCount: 8 },
]);
</script>

<template>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold" style="color:var(--t-text)">🪞 AI-конструктор образа</h2>
            <p class="text-sm mt-1" style="color:var(--t-text-2)">Загрузите фото → получите персональные рекомендации стиля</p>
        </div>
        <VButton v-if="analysisComplete" size="sm" variant="outline" @click="shareResult">📤 Поделиться</VButton>
    </div>

    <!-- Upload Zone -->
    <div v-if="!analysisComplete" class="grid md:grid-cols-2 gap-6">
        <VCard class="p-6">
            <div @drop="handleDrop" @dragover.prevent
                 class="border-2 border-dashed rounded-2xl p-8 text-center cursor-pointer transition-all"
                 :style="`border-color:${uploadedPhotoUrl ? 'var(--t-primary)' : 'var(--t-border)'}`"
                 @click="triggerUpload">
                <template v-if="!uploadedPhotoUrl">
                    <div class="text-6xl mb-4">📸</div>
                    <h3 class="text-lg font-bold mb-2" style="color:var(--t-text)">Загрузите селфи</h3>
                    <p class="text-sm mb-4" style="color:var(--t-text-2)">Перетащите фото или нажмите для выбора</p>
                    <VButton variant="outline">Выбрать фото</VButton>
                </template>
                <template v-else>
                    <img :src="uploadedPhotoUrl" class="w-48 h-48 mx-auto rounded-2xl object-cover mb-4 shadow-lg" />
                    <p class="text-sm font-medium" style="color:var(--t-primary)">{{ uploadedPhoto?.name }}</p>
                    <p class="text-xs mt-1" style="color:var(--t-text-3)">Нажмите для замены</p>
                </template>
                <input ref="fileInput" type="file" class="hidden" accept="image/*" @change="handleFileUpload" />
            </div>

            <VButton v-if="uploadedPhotoUrl && !isAnalyzing" class="w-full mt-4" @click="startAnalysis">
                🔬 Начать анализ
            </VButton>

            <!-- Progress -->
            <div v-if="isAnalyzing" class="mt-4 space-y-3">
                <div class="rounded-full h-2.5 overflow-hidden" style="background:var(--t-bg)">
                    <div class="h-full rounded-full transition-all duration-500" :style="`background:var(--t-primary);width:${analysisProgress}%`"></div>
                </div>
                <div class="space-y-1">
                    <div v-for="(step, i) in analysisSteps" :key="i"
                         class="flex items-center gap-2 text-xs transition-all"
                         :style="`color:${i < currentStep ? '#22c55e' : i === currentStep ? 'var(--t-primary)' : 'var(--t-text-3)'}`">
                        <span>{{ i < currentStep ? '✅' : i === currentStep ? '⏳' : '⬜' }}</span>
                        <span>{{ step }}</span>
                    </div>
                </div>
            </div>
        </VCard>

        <!-- Tips & History -->
        <div class="space-y-6">
            <VCard class="p-6">
                <h3 class="font-bold mb-3" style="color:var(--t-text)">💡 Советы для лучшего результата</h3>
                <div class="space-y-2 text-sm" style="color:var(--t-text-2)">
                    <div class="flex items-start gap-2"><span>📍</span><span>Используйте фронтальное фото при дневном свете</span></div>
                    <div class="flex items-start gap-2"><span>🚫</span><span>Без солнцезащитных очков и головных уборов</span></div>
                    <div class="flex items-start gap-2"><span>💆‍♀️</span><span>Волосы лучше распустить для точного анализа</span></div>
                    <div class="flex items-start gap-2"><span>😊</span><span>Нейтральное выражение лица даёт лучший результат</span></div>
                    <div class="flex items-start gap-2"><span>📱</span><span>Минимальное разрешение: 640×480 пикселей</span></div>
                </div>
            </VCard>

            <VCard v-if="designHistory.length > 0" class="p-6">
                <h3 class="font-bold mb-3" style="color:var(--t-text)">📋 История анализов</h3>
                <div class="space-y-2">
                    <div v-for="h in designHistory" :key="h.id" class="flex items-center justify-between p-3 rounded-lg" style="background:var(--t-bg)">
                        <div>
                            <div class="text-sm font-medium" style="color:var(--t-text)">{{ h.topStyle }}</div>
                            <div class="text-xs" style="color:var(--t-text-3)">{{ h.date }} · Цветотип: {{ h.colorType }} · {{ h.stylesCount }} рекомендаций</div>
                        </div>
                        <VBadge color="blue" size="sm">Открыть</VBadge>
                    </div>
                </div>
            </VCard>
        </div>
    </div>

    <!-- Results -->
    <template v-if="analysisComplete && analysisResult">
        <!-- Face Analysis Card -->
        <div class="grid md:grid-cols-3 gap-6">
            <VCard class="p-6">
                <div class="text-center mb-4">
                    <img v-if="uploadedPhotoUrl" :src="uploadedPhotoUrl" class="w-32 h-32 mx-auto rounded-2xl object-cover shadow-lg" />
                    <div class="mt-3 text-sm font-medium" style="color:var(--t-text)">Ваш анализ</div>
                    <div class="text-xs mt-1" style="color:var(--t-text-3)">Точность: {{ (analysisResult.confidence * 100).toFixed(0) }}%</div>
                </div>
                <VButton variant="outline" class="w-full" @click="uploadedPhotoUrl = ''; analysisComplete = false; analysisResult = null">🔄 Новый анализ</VButton>
            </VCard>

            <VCard class="p-6 md:col-span-2">
                <h3 class="font-bold mb-4" style="color:var(--t-text)">🔬 Результаты анализа</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div v-for="(value, key) in {
                        'Тип лица': analysisResult.faceType,
                        'Тон кожи': analysisResult.skinTone,
                        'Цветотип': analysisResult.colorType,
                        'Состояние кожи': analysisResult.skinCondition,
                        'Цвет волос': analysisResult.hairColor,
                        'Длина волос': analysisResult.hairLength,
                        'Форма бровей': analysisResult.browShape,
                        'Цвет глаз': analysisResult.eyeColor,
                    }" :key="key" class="p-3 rounded-lg" style="background:var(--t-bg)">
                        <div class="text-[10px] uppercase font-semibold mb-1" style="color:var(--t-text-3)">{{ key }}</div>
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ value }}</div>
                    </div>
                </div>
            </VCard>
        </div>

        <!-- Style Recommendations -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold" style="color:var(--t-text)">✨ Рекомендации для вас</h3>
                <div class="flex gap-1 overflow-x-auto">
                    <button v-for="f in styleFilters" :key="f.key" @click="activeStyleFilter = f.key"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-all"
                            :style="activeStyleFilter === f.key ? 'background:var(--t-primary);color:#fff' : 'background:var(--t-surface);color:var(--t-text-2)'">
                        {{ f.label }}
                    </button>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <VCard v-for="style in filteredStyles" :key="style.id" class="overflow-hidden transition-all duration-300"
                       @mouseenter="$event.target.style.transform = 'translateY(-4px)';$event.target.style.boxShadow = '0 8px 30px rgba(0,0,0,0.12)'"
                       @mouseleave="$event.target.style.transform = '';$event.target.style.boxShadow = ''">
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-3xl">{{ style.image }}</span>
                            <VBadge :color="style.match >= 90 ? 'green' : style.match >= 80 ? 'blue' : 'yellow'" size="sm">{{ style.match }}% match</VBadge>
                        </div>
                        <h4 class="font-bold text-sm mb-1" style="color:var(--t-text)">{{ style.name }}</h4>
                        <p class="text-xs mb-3" style="color:var(--t-text-2)">{{ style.description }}</p>
                        <div class="flex items-center justify-between text-xs mb-3" style="color:var(--t-text-3)">
                            <span>👩‍🎨 {{ style.master }}</span>
                            <span>⏱ {{ style.duration }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-lg font-bold" style="color:var(--t-primary)">{{ fmt(style.price) }} ₽</span>
                        </div>
                        <div class="flex gap-2">
                            <VButton size="sm" class="flex-1" @click="bookForStyle(style)">📅 Записаться</VButton>
                            <VButton size="sm" variant="outline" @click="openARPreview(style)">🪞 AR</VButton>
                            <VButton size="sm" variant="outline" @click="saveDesign(style)">
                                {{ savedDesigns.find(d => d.id === style.id) ? '❤️' : '🤍' }}
                            </VButton>
                        </div>
                    </div>
                </VCard>
            </div>
        </div>

        <!-- Care Suggestions -->
        <VCard class="p-6">
            <h3 class="font-bold mb-4" style="color:var(--t-text)">💧 Рекомендации по уходу</h3>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="care in careSuggestions" :key="care.name" class="p-4 rounded-xl" style="background:var(--t-bg)">
                    <div class="font-medium text-sm mb-1" style="color:var(--t-text)">{{ care.name }}</div>
                    <div class="text-xs mb-2" style="color:var(--t-text-2)">{{ care.reason }}</div>
                    <div class="font-bold text-sm" style="color:var(--t-primary)">{{ fmt(care.price) }} ₽</div>
                </div>
            </div>
        </VCard>

        <!-- Saved Designs -->
        <VCard v-if="savedDesigns.length > 0" class="p-6">
            <h3 class="font-bold mb-4" style="color:var(--t-text)">❤️ Сохранённые образы ({{ savedDesigns.length }})</h3>
            <div class="flex flex-wrap gap-3">
                <div v-for="sd in savedDesigns" :key="sd.id" class="flex items-center gap-2 px-3 py-2 rounded-lg" style="background:var(--t-bg)">
                    <span>{{ sd.image }}</span>
                    <span class="text-sm" style="color:var(--t-text)">{{ sd.name }}</span>
                    <span class="text-xs" style="color:var(--t-primary)">{{ fmt(sd.price) }} ₽</span>
                </div>
            </div>
            <div class="mt-3 text-sm font-medium" style="color:var(--t-text-2)">
                Итого: <strong style="color:var(--t-primary)">{{ fmt(savedDesigns.reduce((s, d) => s + d.price, 0)) }} ₽</strong>
            </div>
        </VCard>
    </template>
</div>

<!-- AR Preview Modal -->
<VModal :show="showARPreview" @close="showARPreview = false" :title="`🪞 AR-примерка: ${activeARStyle?.name || ''}`" size="lg">
    <div v-if="activeARStyle" class="space-y-4">
        <div class="flex items-center gap-6">
            <div class="text-center">
                <img v-if="uploadedPhotoUrl" :src="uploadedPhotoUrl" class="w-40 h-40 rounded-2xl object-cover shadow-lg" />
                <div class="text-xs mt-2" style="color:var(--t-text-3)">До</div>
            </div>
            <div class="text-3xl" style="color:var(--t-primary)">→</div>
            <div class="text-center">
                <div class="w-40 h-40 rounded-2xl flex items-center justify-center text-6xl" style="background:var(--t-bg)">
                    {{ activeARStyle.image }}
                </div>
                <div class="text-xs mt-2" style="color:var(--t-primary)">После (AR-превью)</div>
            </div>
        </div>
        <div class="p-4 rounded-xl" style="background:var(--t-bg)">
            <h4 class="font-bold text-sm mb-1" style="color:var(--t-text)">{{ activeARStyle.name }}</h4>
            <p class="text-xs mb-2" style="color:var(--t-text-2)">{{ activeARStyle.description }}</p>
            <div class="flex items-center gap-4 text-xs" style="color:var(--t-text-3)">
                <span>👩‍🎨 {{ activeARStyle.master }}</span>
                <span>⏱ {{ activeARStyle.duration }}</span>
                <span class="font-bold" style="color:var(--t-primary)">{{ fmt(activeARStyle.price) }} ₽</span>
            </div>
        </div>
        <p class="text-xs text-center" style="color:var(--t-text-3)">
            💡 Полноценный AR-просмотр доступен в мобильном приложении CatVRF
        </p>
        <div class="flex justify-end gap-3">
            <VButton variant="outline" @click="saveDesign(activeARStyle)">🤍 Сохранить</VButton>
            <VButton @click="bookForStyle(activeARStyle); showARPreview = false">📅 Записаться к мастеру</VButton>
        </div>
    </div>
</VModal>
</template>
