<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import UniversalCard from '@/Components/UniversalCard.vue';
import ItemDetailModal from '@/Components/ItemDetailModal.vue';
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { megaCategories, totalVerticals, typeMeta, generateDemoItems } from '@/data/verticals.js';
import { useUserGeo } from '@/composables/useUserGeo';

const props = defineProps({ tab: { type: String, default: '' } });

// ── Состояние ──
const activeCatId = ref(props.tab || megaCategories[0]?.id || '');
const activeSlug  = ref('');
const sortBy      = ref('popular');

const { enrichItemsWithDistance, userLat } = useUserGeo();

const activeCat = computed(() => megaCategories.find(c => c.id === activeCatId.value) || megaCategories[0]);

const activeVertical = computed(() => {
    if (!activeSlug.value) return null;
    return activeCat.value?.verticals.find(v => v.slug === activeSlug.value) || null;
});

const meta = computed(() => {
    if (!activeVertical.value) return null;
    return typeMeta[activeVertical.value.type] || typeMeta.product;
});

// ── Infinite scroll ──
const PAGE_SIZE = 20;
const allDemoItems = computed(() => {
    if (!activeVertical.value) return [];
    return generateDemoItems(activeVertical.value, 200);
});
const visibleCount = ref(PAGE_SIZE);
const demoItems = computed(() => allDemoItems.value.slice(0, visibleCount.value));
const hasMore = computed(() => visibleCount.value < allDemoItems.value.length);
const loadingMore = ref(false);

const loadMore = () => {
    if (loadingMore.value || !hasMore.value) return;
    loadingMore.value = true;
    setTimeout(() => { visibleCount.value += PAGE_SIZE; loadingMore.value = false; }, 300);
};

const onScroll = () => {
    if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 400) loadMore();
};
onMounted(() => window.addEventListener('scroll', onScroll));
onUnmounted(() => window.removeEventListener('scroll', onScroll));

const sortedItems = computed(() => {
    const items = [...filteredItems.value];
    switch (sortBy.value) {
        case 'price_asc':  return items.sort((a, b) => a.price - b.price);
        case 'price_desc': return items.sort((a, b) => b.price - a.price);
        case 'rating':     return items.sort((a, b) => b.rating - a.rating);
        case 'distance':   return items.sort((a, b) => (a.distance ?? 9999) - (b.distance ?? 9999));
        default:           return items;
    }
});

const sortOptions = computed(() => {
    const base = [
        { value: 'popular',    label: 'Популярности' },
        { value: 'price_asc',  label: 'Цена ↑' },
        { value: 'price_desc', label: 'Цена ↓' },
        { value: 'rating',     label: 'Рейтинг' },
    ];
    if (userLat.value != null) {
        base.push({ value: 'distance', label: '📍 Рядом' });
    }
    return base;
});

// ── Действия ──
const selectedItem = ref(null);
const openItem = (item) => { selectedItem.value = item; };
const closeItem = () => { selectedItem.value = null; };

const selectCategory = (catId) => {
    activeCatId.value = catId;
    activeSlug.value = '';
    sortBy.value = 'popular';
    resetFilters();
};

const selectVertical = (slug) => {
    activeSlug.value = activeSlug.value === slug ? '' : slug;
    sortBy.value = 'popular';
    visibleCount.value = PAGE_SIZE;
    resetFilters();
    if (activeSlug.value) {
        nextTick(() => {
            document.getElementById('vertical-content')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
};

const onAction = (item) => {
    // Для бронирований с номерами — переход на полноценную страницу бронирования
    if (activeVertical.value?.type === 'booking' && item.rooms && item.rooms.length) {
        router.visit(`/booking/${activeVertical.value.slug}/${item.id}`);
        return;
    }
    const cta = meta.value?.cta || 'Действие';
    alert(`${cta}: ${item.name} — ${item.price.toLocaleString('ru-RU')} ₽`);
};

// ── Фильтры ──
const showFilters = ref(false);
const filterPriceMin = ref('');
const filterPriceMax = ref('');
const filterRating = ref(0);
const ratingPresets = [
    { value: 0,   label: 'Все' },
    { value: 3,   label: '★ 3+' },
    { value: 4,   label: '★ 4+' },
    { value: 4.5, label: '★ 4.5+' },
];

const hasActiveFilters = computed(() =>
    filterPriceMin.value !== '' || filterPriceMax.value !== '' || filterRating.value > 0
);

const resetFilters = () => {
    filterPriceMin.value = '';
    filterPriceMax.value = '';
    filterRating.value = 0;
};

const priceRange = computed(() => {
    const prices = allDemoItems.value.map(i => i.price);
    if (!prices.length) return { min: 0, max: 99999 };
    return { min: Math.min(...prices), max: Math.max(...prices) };
});

const filteredItems = computed(() => {
    let items = enrichItemsWithDistance([...demoItems.value]);
    if (filterPriceMin.value !== '') {
        const min = Number(filterPriceMin.value);
        if (!isNaN(min)) items = items.filter(i => i.price >= min);
    }
    if (filterPriceMax.value !== '') {
        const max = Number(filterPriceMax.value);
        if (!isNaN(max)) items = items.filter(i => i.price <= max);
    }
    if (filterRating.value > 0) {
        items = items.filter(i => i.rating >= filterRating.value);
    }
    return items;
});
</script>

<template>
    <Head title="Все вертикали — CatVRF" />

    <AppLayout show-back title="Каталог">
        <section>
            <h1 class="text-2xl md:text-3xl font-black mb-1" style="color: var(--t-text);">
                📂 {{ totalVerticals }} вертикалей
            </h1>
            <p class="text-sm mb-5" style="color: var(--t-text-3);">
                {{ megaCategories.length }} категорий · AI-персонализация в каждой
            </p>

            <!-- ═══ ТАБЫ МЕГА-КАТЕГОРИЙ (горизонтальная прокрутка) ═══ -->
            <div class="overflow-x-auto -mx-4 px-4 mb-5 hide-scrollbar">
                <div class="flex gap-1.5 min-w-max">
                    <button v-for="cat in megaCategories" :key="cat.id"
                        @click="selectCategory(cat.id)"
                        class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all duration-300 active:scale-90 hover:scale-105 cursor-pointer border"
                        :style="activeCatId === cat.id
                            ? { background: 'var(--t-primary-dim)', borderColor: 'var(--t-primary)', color: 'var(--t-text)' }
                            : { background: 'var(--t-surface)', borderColor: 'var(--t-border)', color: 'var(--t-text-2)' }">
                        <span>{{ cat.icon }}</span>
                        {{ cat.name }}
                        <span class="text-[10px] opacity-50">{{ cat.verticals.length }}</span>
                    </button>
                </div>
            </div>

            <!-- ═══ ЧИПЫ ВЕРТИКАЛЕЙ (выбранная категория) ═══ -->
            <div class="mb-6">
                <p class="text-xs font-bold mb-2 flex items-center gap-1" style="color: var(--t-text-3);">
                    {{ activeCat.icon }} {{ activeCat.name }}
                    <span class="opacity-50">— выберите вертикаль</span>
                </p>
                <div class="flex flex-wrap gap-1.5">
                    <button v-for="v in activeCat.verticals" :key="v.slug"
                        @click="selectVertical(v.slug)"
                        class="vert-chip flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all duration-300 active:scale-[0.93] hover:scale-105 cursor-pointer border hover:shadow-md"
                        :style="activeSlug === v.slug
                            ? { background: 'var(--t-primary-dim)', borderColor: 'var(--t-primary)', color: 'var(--t-text)' }
                            : { background: 'var(--t-surface)', borderColor: 'var(--t-border)', color: 'var(--t-text)' }">
                        <span>{{ v.icon }}</span>
                        {{ v.name }}
                        <span class="text-[10px] px-1 py-0 rounded ml-0.5" style="background: rgba(128,128,128,.15); color: var(--t-text-3);">
                            {{ (typeMeta[v.type] || typeMeta.product).badge }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- ═══ КОНТЕНТ ВЫБРАННОЙ ВЕРТИКАЛИ (inline, без навигации) ═══ -->
            <Transition enter-from-class="opacity-0 translate-y-3" enter-active-class="transition duration-300" leave-to-class="opacity-0 translate-y-3" leave-active-class="transition duration-200">
                <div v-if="activeVertical" id="vertical-content" class="rounded-2xl border p-4 md:p-6 mb-8" style="background: var(--t-surface); border-color: var(--t-border);">

                    <!-- Заголовок + бейдж + перейти на страницу -->
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-black" style="color: var(--t-text);">
                                {{ activeVertical.icon }} {{ activeVertical.name }}
                            </h2>
                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full" style="background: var(--t-bg); color: var(--t-primary);">
                                {{ meta.badge }}
                            </span>
                        </div>
                        <Link :href="`/category/${activeVertical.slug}`"
                            class="text-xs font-bold transition-all cursor-pointer hover:scale-105 active:scale-95" style="color: var(--t-primary);">
                            Открыть страницу →
                        </Link>
                        <Link href="/business?section=products" class="text-[10px] font-bold px-2 py-1 rounded-md border border-amber-500/30 text-amber-400 hover:bg-amber-500/10 active:scale-95 transition-all cursor-pointer">
                            B2B 🏢
                        </Link>
                    </div>

                    <!-- Сортировка + Фильтры -->
                    <div class="flex items-center justify-between mb-3 gap-2 flex-wrap">
                        <p class="text-xs" style="color: var(--t-text-3);">
                            {{ sortedItems.length }} предложений
                            <span v-if="hasActiveFilters" class="text-[10px] ml-1">(фильтры)</span>
                        </p>
                        <div class="flex items-center gap-2">
                            <button @click="showFilters = !showFilters"
                                class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all cursor-pointer border"
                                :style="showFilters || hasActiveFilters
                                    ? { background: 'var(--t-primary-dim)', borderColor: 'var(--t-primary)', color: 'var(--t-text)' }
                                    : { background: 'var(--t-bg)', borderColor: 'var(--t-border)', color: 'var(--t-text-2)' }">
                                🔍 Фильтры
                                <span v-if="hasActiveFilters" class="w-1.5 h-1.5 rounded-full" style="background: var(--t-primary);"></span>
                            </button>
                            <div class="flex gap-1">
                                <button v-for="opt in sortOptions" :key="opt.value"
                                    @click="sortBy = opt.value"
                                    class="text-[11px] px-2.5 py-1 rounded-lg font-semibold transition-all cursor-pointer"
                                    :style="sortBy === opt.value
                                        ? { background: 'var(--t-primary-dim)', color: 'var(--t-text)' }
                                        : { color: 'var(--t-text-3)' }">
                                    {{ opt.label }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Панель фильтров -->
                    <Transition enter-from-class="opacity-0 -translate-y-2 max-h-0" enter-active-class="transition-all duration-300 ease-out"
                                enter-to-class="opacity-100 translate-y-0 max-h-[300px]"
                                leave-from-class="opacity-100 translate-y-0 max-h-[300px]" leave-active-class="transition-all duration-200 ease-in"
                                leave-to-class="opacity-0 -translate-y-2 max-h-0">
                        <div v-if="showFilters" class="rounded-lg border p-3 mb-3 overflow-hidden"
                             style="background: var(--t-bg); border-color: var(--t-border);">
                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="flex-1">
                                    <label class="text-[10px] font-bold uppercase mb-1.5 block" style="color: var(--t-text-3);">💰 Цена, ₽</label>
                                    <div class="flex items-center gap-2">
                                        <input v-model="filterPriceMin" type="number" :placeholder="`от ${priceRange.min}`"
                                            class="w-full px-2.5 py-1.5 rounded-lg text-xs outline-none"
                                            style="background: var(--t-surface); border: 1px solid var(--t-border); color: var(--t-text);" />
                                        <span class="text-xs" style="color: var(--t-text-3);">—</span>
                                        <input v-model="filterPriceMax" type="number" :placeholder="`до ${priceRange.max}`"
                                            class="w-full px-2.5 py-1.5 rounded-lg text-xs outline-none"
                                            style="background: var(--t-surface); border: 1px solid var(--t-border); color: var(--t-text);" />
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold uppercase mb-1.5 block" style="color: var(--t-text-3);">⭐ Рейтинг</label>
                                    <div class="flex gap-1">
                                        <button v-for="rp in ratingPresets" :key="rp.value"
                                            @click="filterRating = rp.value"
                                            class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all cursor-pointer border"
                                            :style="filterRating === rp.value
                                                ? { background: 'var(--t-primary-dim)', borderColor: 'var(--t-primary)', color: 'var(--t-text)' }
                                                : { background: 'var(--t-surface)', borderColor: 'var(--t-border)', color: 'var(--t-text-2)' }">
                                            {{ rp.label }}
                                        </button>
                                    </div>
                                </div>
                                <div v-if="hasActiveFilters" class="flex items-end">
                                    <button @click="resetFilters" class="text-xs font-bold cursor-pointer" style="color: var(--t-primary);">✕ Сбросить</button>
                                </div>
                            </div>
                        </div>
                    </Transition>

                    <!-- Пусто после фильтрации -->
                    <div v-if="sortedItems.length === 0 && hasActiveFilters"
                         class="text-center py-8 rounded-xl mb-3"
                         style="background: var(--t-bg); border: 1px solid var(--t-border);">
                        <p class="text-3xl mb-2">🔍</p>
                        <p class="text-sm font-bold" style="color: var(--t-text);">Ничего не найдено</p>
                        <button @click="resetFilters" class="text-xs font-bold mt-2 cursor-pointer" style="color: var(--t-primary);">Сбросить фильтры</button>
                    </div>

                    <!-- Карточки -->
                    <div v-if="sortedItems.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                        <UniversalCard
                            v-for="item in sortedItems" :key="item.id"
                            :item="item"
                            :type="activeVertical.type"
                            compact
                            @click="openItem"
                            @action="onAction" />
                    </div>

                    <!-- Infinite scroll -->
                    <div v-if="hasMore" class="text-center py-5">
                        <div v-if="loadingMore" class="inline-flex items-center gap-2 text-sm" style="color: var(--t-text-3);">
                            <span class="animate-spin">⏳</span> Загрузка...
                        </div>
                        <button v-else @click="loadMore" class="px-6 py-2 rounded-xl text-sm font-bold transition-all cursor-pointer border" style="background: var(--t-bg); border-color: var(--t-border); color: var(--t-text-2);">
                            Показать ещё
                        </button>
                    </div>
                    <p v-else class="text-center text-xs py-3" style="color: var(--t-text-3);">Все предложения загружены ✓</p>
                </div>
            </Transition>

            <!-- Подсказка если ничего не выбрано -->
            <div v-if="!activeVertical" class="text-center py-12 rounded-2xl border" style="background: var(--t-surface); border-color: var(--t-border);">
                <p class="text-4xl mb-3">👆</p>
                <p class="text-sm font-bold" style="color: var(--t-text-2);">Выберите вертикаль выше</p>
                <p class="text-xs mt-1" style="color: var(--t-text-3);">Контент отобразится прямо здесь — без перехода на другую страницу</p>
            </div>

        </section>

        <!-- Модальное окно товара -->
        <ItemDetailModal
            :item="selectedItem"
            :type="activeVertical?.type || 'product'"
            @close="closeItem"
            @action="onAction" />
    </AppLayout>
</template>

<style scoped>
.vert-chip:hover { transform: translateY(-2px); box-shadow: 0 4px 12px var(--t-glow); }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.hide-scrollbar::-webkit-scrollbar { display: none; }
</style>
