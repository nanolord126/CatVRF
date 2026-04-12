<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import UniversalCard from '@/Components/UniversalCard.vue';
import ItemDetailModal from '@/Components/ItemDetailModal.vue';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { findBySlug, typeMeta, generateDemoItems } from '@/data/verticals.js';
import { useUserGeo } from '@/composables/useUserGeo';

const props = defineProps({ slug: String });

const match = computed(() => findBySlug(props.slug));
const vertical = computed(() => match.value?.vertical || { name: props.slug, icon: '📦', slug: props.slug, type: 'product' });
const parentCat = computed(() => match.value?.category || null);
const vType = computed(() => vertical.value.type || 'product');
const meta = computed(() => typeMeta[vType.value] || typeMeta.product);

// Соседние вертикали из той же категории
const siblings = computed(() => {
    if (!parentCat.value) return [];
    return parentCat.value.verticals.filter((v) => v.slug !== props.slug);
});

// ── Модальное окно товара ──
const selectedItem = ref(null);
const openItem = (item) => {
    // Для бронирований с номерами — переход на страницу бронирования
    if (vType.value === 'booking' && item.rooms && item.rooms.length) {
        router.visit(`/booking/${props.slug}/${item.id}`);
        return;
    }
    selectedItem.value = item;
};
const closeItem = () => { selectedItem.value = null; };

// ── Infinite scroll ──
const PAGE_SIZE = 20;
const allItems = computed(() => generateDemoItems(vertical.value, 200));
const visibleCount = ref(PAGE_SIZE);
const rawItems = computed(() => allItems.value.slice(0, visibleCount.value));
const hasMore = computed(() => visibleCount.value < allItems.value.length);
const loadingMore = ref(false);

const loadMore = () => {
    if (loadingMore.value || !hasMore.value) return;
    loadingMore.value = true;
    setTimeout(() => {
        visibleCount.value += PAGE_SIZE;
        loadingMore.value = false;
    }, 300);
};

const onScroll = () => {
    const scrollBottom = window.innerHeight + window.scrollY;
    if (scrollBottom >= document.documentElement.scrollHeight - 400) loadMore();
};

onMounted(() => window.addEventListener('scroll', onScroll));
onUnmounted(() => window.removeEventListener('scroll', onScroll));

// ── Сортировка ──
const sortBy = ref('popular');

const { enrichItemsWithDistance, userLat, geoStatus } = useUserGeo();

const sortOptions = computed(() => {
    const base = [
        { value: 'popular',    label: 'По популярности' },
        { value: 'price_asc',  label: 'Сначала дешёвые' },
        { value: 'price_desc', label: 'Сначала дорогие' },
        { value: 'rating',     label: 'По рейтингу' },
    ];
    // Добавляем сортировку по расстоянию если гео доступно
    if (userLat.value != null) {
        base.push({ value: 'distance', label: '📍 Рядом' });
    }
    return base;
});

// ── Фильтры ──
const showFilters = ref(false);
const filterPriceMin = ref('');
const filterPriceMax = ref('');
const filterRating = ref(0); // 0 = все, 3/4/4.5
const ratingPresets = [
    { value: 0,   label: 'Все' },
    { value: 3,   label: '★ 3+' },
    { value: 4,   label: '★ 4+' },
    { value: 4.5, label: '★ 4.5+' },
];

// ── Фильтр типа размещения (booking only) ──
const filterAccomType = ref('all');
const accommodationFilters = [
    { value: 'all',        label: '🏠 Все' },
    { value: 'hotel',      label: '🏨 Гостиницы' },
    { value: 'hostel',     label: '🛏️ Хостелы' },
    { value: 'apartment',  label: '🏘️ Апартаменты' },
    { value: 'resort',     label: '🏡 Пансионаты' },
    { value: 'recreation', label: '🌲 Базы отдыха' },
    { value: 'cottage',    label: '🏕️ Загородные дома' },
];

// ── Фильтр по удобствам (booking only) ──
const filterAmenities = ref([]);
const amenityOptions = ['Wi-Fi', 'Бассейн', 'Парковка', 'Кухня', 'Баня', 'Фитнес', 'SPA', 'Мангал'];
const toggleAmenity = (a) => {
    const idx = filterAmenities.value.indexOf(a);
    if (idx >= 0) filterAmenities.value.splice(idx, 1);
    else filterAmenities.value.push(a);
};

const hasActiveFilters = computed(() =>
    filterPriceMin.value !== '' || filterPriceMax.value !== '' || filterRating.value > 0
    || filterAccomType.value !== 'all' || filterAmenities.value.length > 0
);

const resetFilters = () => {
    filterPriceMin.value = '';
    filterPriceMax.value = '';
    filterRating.value = 0;
    filterAccomType.value = 'all';
    filterAmenities.value = [];
};

// Рассчитываем мин/макс цены для подсказок
const priceRange = computed(() => {
    const prices = allItems.value.map(i => i.price);
    return { min: Math.min(...prices), max: Math.max(...prices) };
});

// ── Фильтрация + Сортировка ──
const filteredItems = computed(() => {
    let items = enrichItemsWithDistance([...rawItems.value]);

    // Фильтр по цене (мин)
    if (filterPriceMin.value !== '') {
        const min = Number(filterPriceMin.value);
        if (!isNaN(min)) items = items.filter(i => i.price >= min);
    }
    // Фильтр по цене (макс)
    if (filterPriceMax.value !== '') {
        const max = Number(filterPriceMax.value);
        if (!isNaN(max)) items = items.filter(i => i.price <= max);
    }
    // Фильтр по рейтингу
    if (filterRating.value > 0) {
        items = items.filter(i => i.rating >= filterRating.value);
    }
    // Фильтр по типу размещения (booking)
    if (filterAccomType.value !== 'all') {
        items = items.filter(i => i.accommodationType === filterAccomType.value);
    }
    // Фильтр по удобствам (booking)
    if (filterAmenities.value.length > 0) {
        items = items.filter(i =>
            i.amenities && filterAmenities.value.every(a => i.amenities.includes(a))
        );
    }

    return items;
});

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

// ── Такси/транспорт: форма заказа ──
const taxiFrom = ref('');
const taxiTo = ref('');
const taxiTariff = ref('Комфорт');
const taxiTariffs = [
    { name: 'Эконом',   price: 299, eta: '3 мин', icon: '🚗' },
    { name: 'Комфорт',  price: 499, eta: '5 мин', icon: '🚘' },
    { name: 'Бизнес',   price: 899, eta: '7 мин', icon: '🚙' },
    { name: 'Премиум',  price: 1500, eta: '10 мин', icon: '💎' },
    { name: 'Минивэн',  price: 699, eta: '4 мин', icon: '🚐' },
];

const onAction = (item) => {
    // Для бронирований с номерами — переход на полноценную страницу бронирования
    if (vType.value === 'booking' && item.rooms && item.rooms.length) {
        router.visit(`/booking/${props.slug}/${item.id}`);
        return;
    }
    alert(`${meta.value.cta}: ${item.name} — ${item.price.toLocaleString('ru-RU')} ₽`);
};

const orderTaxi = () => {
    if (!taxiFrom.value || !taxiTo.value) { alert('Укажите адреса «Откуда» и «Куда»'); return; }
    const t = taxiTariffs.find(t => t.name === taxiTariff.value);
    alert(`🚕 Такси «${taxiTariff.value}» заказано!\n${taxiFrom.value} → ${taxiTo.value}\nОжидание: ${t?.eta}\nСтоимость: от ${t?.price} ₽`);
};
</script>

<template>
    <Head :title="`${vertical.name} — CatVRF`" />

    <AppLayout show-back :back-href="parentCat ? `/categories` : '/'" :title="`${vertical.icon} ${vertical.name}`">
        <section>
            <!-- Хлебные крошки -->
            <div v-if="parentCat" class="flex items-center gap-1 text-xs mb-4 flex-wrap" style="color: var(--t-text-3);">
                <Link href="/" class="hover:underline cursor-pointer hover:scale-105 active:scale-95 transition-all" style="color: var(--t-primary);">Главная</Link>
                <span>›</span>
                <Link href="/categories" class="hover:underline cursor-pointer hover:scale-105 active:scale-95 transition-all" style="color: var(--t-primary);">{{ parentCat.name }}</Link>
                <span>›</span>
                <span style="color: var(--t-text);">{{ vertical.name }}</span>
            </div>

            <!-- Заголовок + бейдж типа -->
            <div class="flex items-center gap-3 mb-4">
                <h1 class="text-2xl md:text-3xl font-black" style="color: var(--t-text);">
                    {{ vertical.icon }} {{ vertical.name }}
                </h1>
                <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full" style="background: var(--t-surface); color: var(--t-primary);">
                    {{ meta.badge }}
                </span>
                <Link href="/business?section=products" class="ml-auto text-[10px] font-bold px-2.5 py-1 rounded-lg border border-amber-500/30 text-amber-400 hover:bg-amber-500/10 active:scale-95 transition-all cursor-pointer">
                    🏢 B2B-опт
                </Link>
            </div>

            <!-- ════════════ TRANSPORT: Форма заказа такси ════════════ -->
            <template v-if="vType === 'transport'">
                <div class="rounded-2xl border p-5 md:p-8 mb-8 space-y-5" style="background: var(--t-surface); border-color: var(--t-border);">
                    <h2 class="text-lg font-bold flex items-center gap-2" style="color: var(--t-text);">
                        🚕 Заказать поездку
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color: var(--t-text-3);">Откуда</label>
                            <input v-model="taxiFrom" type="text" placeholder="Адрес отправления"
                                class="w-full px-4 py-3 rounded-xl text-sm outline-none transition-colors"
                                style="background: var(--t-bg); border: 1px solid var(--t-border); color: var(--t-text);" />
                        </div>
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color: var(--t-text-3);">Куда</label>
                            <input v-model="taxiTo" type="text" placeholder="Адрес назначения"
                                class="w-full px-4 py-3 rounded-xl text-sm outline-none transition-colors"
                                style="background: var(--t-bg); border: 1px solid var(--t-border); color: var(--t-text);" />
                        </div>
                    </div>

                    <!-- Тарифы -->
                    <div>
                        <label class="text-xs font-bold mb-2 block" style="color: var(--t-text-3);">Тариф</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2">
                            <button v-for="t in taxiTariffs" :key="t.name"
                                @click="taxiTariff = t.name"
                                class="flex flex-col items-center p-3 rounded-xl border transition-all duration-200 active:scale-95 cursor-pointer"
                                :style="taxiTariff === t.name
                                    ? { background: 'var(--t-primary-dim)', borderColor: 'var(--t-primary)', color: 'var(--t-text)' }
                                    : { background: 'var(--t-bg)', borderColor: 'var(--t-border)', color: 'var(--t-text-2)' }">
                                <span class="text-lg mb-1">{{ t.icon }}</span>
                                <span class="text-xs font-bold">{{ t.name }}</span>
                                <span class="text-[10px] mt-0.5" style="color: var(--t-text-3);">от {{ t.price }} ₽</span>
                                <span class="text-[10px]" style="color: var(--t-text-3);">{{ t.eta }}</span>
                            </button>
                        </div>
                    </div>

                    <button @click="orderTaxi"
                        class="w-full py-3.5 rounded-xl text-white font-bold text-sm transition-all active:scale-[0.97] hover:scale-[1.02] hover:shadow-lg cursor-pointer"
                        style="background: var(--t-btn); box-shadow: 0 4px 20px var(--t-glow);">
                        🚕 Заказать такси
                    </button>
                </div>
            </template>

            <!-- ════════════ ОСТАЛЬНЫЕ ТИПЫ: карточки ════════════ -->
            <template v-else>
                <!-- Панель сортировки + кнопка фильтров -->
                <div class="flex items-center justify-between mb-3 gap-3 flex-wrap">
                    <p class="text-sm" style="color: var(--t-text-3);">
                        {{ sortedItems.length }} предложений
                        <span v-if="hasActiveFilters" class="text-[10px] ml-1">(фильтры активны)</span>
                    </p>
                    <div class="flex items-center gap-2">
                        <button @click="showFilters = !showFilters"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all cursor-pointer border hover:scale-105 active:scale-95"
                            :style="showFilters || hasActiveFilters
                                ? { background: 'var(--t-primary-dim)', borderColor: 'var(--t-primary)', color: 'var(--t-text)' }
                                : { background: 'var(--t-surface)', borderColor: 'var(--t-border)', color: 'var(--t-text-2)' }">
                            <span>🔍</span> Фильтры
                            <span v-if="hasActiveFilters" class="w-2 h-2 rounded-full" style="background: var(--t-primary);"></span>
                        </button>
                        <select v-model="sortBy"
                            class="text-xs font-semibold px-3 py-2 rounded-lg outline-none cursor-pointer transition-colors"
                            style="background: var(--t-surface); border: 1px solid var(--t-border); color: var(--t-text);">
                            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                </div>

                <!-- ═══ ПАНЕЛЬ ФИЛЬТРОВ ═══ -->
                <Transition enter-from-class="opacity-0 -translate-y-2 max-h-0" enter-active-class="transition-all duration-300 ease-out"
                            enter-to-class="opacity-100 translate-y-0 max-h-[300px]"
                            leave-from-class="opacity-100 translate-y-0 max-h-[300px]" leave-active-class="transition-all duration-200 ease-in"
                            leave-to-class="opacity-0 -translate-y-2 max-h-0">
                    <div v-if="showFilters" class="rounded-xl border p-4 mb-4 overflow-hidden"
                         style="background: var(--t-surface); border-color: var(--t-border);">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <!-- Цена -->
                            <div class="flex-1">
                                <label class="text-[11px] font-bold uppercase mb-2 block" style="color: var(--t-text-3);">
                                    💰 Цена, ₽
                                </label>
                                <div class="flex items-center gap-2">
                                    <input v-model="filterPriceMin" type="number" :placeholder="`от ${priceRange.min}`"
                                        class="w-full px-3 py-2 rounded-lg text-xs outline-none"
                                        style="background: var(--t-bg); border: 1px solid var(--t-border); color: var(--t-text);" />
                                    <span class="text-xs" style="color: var(--t-text-3);">—</span>
                                    <input v-model="filterPriceMax" type="number" :placeholder="`до ${priceRange.max}`"
                                        class="w-full px-3 py-2 rounded-lg text-xs outline-none"
                                        style="background: var(--t-bg); border: 1px solid var(--t-border); color: var(--t-text);" />
                                </div>
                            </div>

                            <!-- Рейтинг -->
                            <div>
                                <label class="text-[11px] font-bold uppercase mb-2 block" style="color: var(--t-text-3);">
                                    ⭐ Рейтинг
                                </label>
                                <div class="flex gap-1.5">
                                    <button v-for="rp in ratingPresets" :key="rp.value"
                                        @click="filterRating = rp.value"
                                        class="px-3 py-2 rounded-lg text-xs font-semibold transition-all cursor-pointer border hover:scale-105 active:scale-90"
                                        :style="filterRating === rp.value
                                            ? { background: 'var(--t-primary-dim)', borderColor: 'var(--t-primary)', color: 'var(--t-text)' }
                                            : { background: 'var(--t-bg)', borderColor: 'var(--t-border)', color: 'var(--t-text-2)' }">
                                        {{ rp.label }}
                                    </button>
                                </div>
                            </div>

                            <!-- Сброс -->
                            <div class="flex items-end">
                                <button v-if="hasActiveFilters" @click="resetFilters"
                                    class="px-4 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                    style="color: var(--t-primary);">
                                    ✕ Сбросить
                                </button>
                            </div>
                        </div>

                        <!-- Фильтр типа размещения (только для booking) -->
                        <div v-if="vType === 'booking'" class="mt-3 pt-3 border-t" style="border-color: var(--t-border);">
                            <label class="text-[11px] font-bold uppercase mb-2 block" style="color: var(--t-text-3);">
                                🏠 Тип размещения
                            </label>
                            <div class="flex flex-wrap gap-1.5">
                                <button v-for="af in accommodationFilters" :key="af.value"
                                    @click="filterAccomType = af.value"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer border"
                                    :style="filterAccomType === af.value
                                        ? { background: 'var(--t-primary-dim)', borderColor: 'var(--t-primary)', color: 'var(--t-text)' }
                                        : { background: 'var(--t-bg)', borderColor: 'var(--t-border)', color: 'var(--t-text-2)' }">
                                    {{ af.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Фильтр по удобствам (только для booking) -->
                        <div v-if="vType === 'booking'" class="mt-3 pt-3 border-t" style="border-color: var(--t-border);">
                            <label class="text-[11px] font-bold uppercase mb-2 block" style="color: var(--t-text-3);">
                                ✨ Удобства
                            </label>
                            <div class="flex flex-wrap gap-1.5">
                                <button v-for="ao in amenityOptions" :key="ao"
                                    @click="toggleAmenity(ao)"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer border"
                                    :style="filterAmenities.includes(ao)
                                        ? { background: 'var(--t-primary-dim)', borderColor: 'var(--t-primary)', color: 'var(--t-text)' }
                                        : { background: 'var(--t-bg)', borderColor: 'var(--t-border)', color: 'var(--t-text-2)' }">
                                    {{ ao }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Пусто после фильтрации -->
                <div v-if="sortedItems.length === 0 && hasActiveFilters"
                     class="text-center py-12 rounded-xl border mb-4"
                     style="background: var(--t-surface); border-color: var(--t-border);">
                    <p class="text-4xl mb-3">🔍</p>
                    <p class="font-bold" style="color: var(--t-text);">Ничего не найдено</p>
                    <p class="text-sm mb-4" style="color: var(--t-text-3);">Попробуйте изменить фильтры</p>
                    <button @click="resetFilters"
                        class="px-5 py-2 rounded-xl text-sm font-bold cursor-pointer transition-all"
                        style="background: var(--t-primary); color: white;">
                        Сбросить фильтры
                    </button>
                </div>

                <!-- Карточки (UniversalCard) -->
                <div v-if="sortedItems.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3 mb-4">
                    <UniversalCard
                        v-for="item in sortedItems" :key="item.id"
                        :item="item"
                        :type="vType"
                        compact
                        @click="openItem"
                        @action="onAction" />
                </div>

                <!-- Infinite scroll indicator -->
                <div v-if="hasMore" class="text-center py-6">
                    <div v-if="loadingMore" class="inline-flex items-center gap-2 text-sm" style="color: var(--t-text-3);">
                        <span class="animate-spin">⏳</span> Загрузка...
                    </div>
                    <button v-else @click="loadMore" class="px-6 py-2 rounded-xl text-sm font-bold transition-all cursor-pointer border hover:scale-105 active:scale-95 hover:shadow-lg" style="background: var(--t-surface); border-color: var(--t-border); color: var(--t-text-2);">
                        Показать ещё
                    </button>
                </div>
                <p v-else class="text-center text-xs py-4" style="color: var(--t-text-3);">Вы просмотрели все предложения ✓</p>
            </template>

            <!-- Соседние вертикали -->
            <div v-if="siblings.length" class="mt-8">
                <h2 class="text-base font-bold mb-3" style="color: var(--t-text);">
                    Ещё в «{{ parentCat.name }}»
                </h2>
                <div class="flex flex-wrap gap-2">
                    <Link v-for="s in siblings" :key="s.slug" :href="`/category/${s.slug}`"
                        class="flex items-center gap-1.5 px-3 py-2 rounded-lg border text-xs font-semibold transition-all duration-300 active:scale-[0.95] hover:scale-105 cursor-pointer hover:shadow-md sibling-link"
                        style="background: var(--t-surface); border-color: var(--t-border); color: var(--t-text);">
                        <span class="transition-transform hover:scale-125">{{ s.icon }}</span>
                        {{ s.name }}
                    </Link>
                </div>
            </div>
        </section>

        <!-- Модальное окно товара -->
        <ItemDetailModal :item="selectedItem" :type="vType" @close="closeItem" @action="onAction" />
    </AppLayout>
</template>
