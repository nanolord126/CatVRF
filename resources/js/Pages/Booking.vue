<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import UniversalCard from '@/Components/UniversalCard.vue';
import { computed, ref, watch, onMounted } from 'vue';
import { findBySlug, typeMeta, generateDemoItems, megaCategories } from '@/data/verticals.js';
import { useUserGeo } from '@/composables/useUserGeo';

const props = defineProps({ slug: String, itemId: [String, Number] });

/* ── Данные вертикали и товара ── */
const match = computed(() => findBySlug(props.slug));
const vertical = computed(() => match.value?.vertical || { name: props.slug, icon: '📦', slug: props.slug, type: 'booking' });
const parentCat = computed(() => match.value?.category || null);
const meta = computed(() => typeMeta.booking || typeMeta.product);

// Генерируем демо-товары и ищем нужный по id
const allItems = computed(() => generateDemoItems(vertical.value, 200));
const item = computed(() => {
    const id = Number(props.itemId);
    return allItems.value.find(i => i.id === id) || allItems.value[0] || null;
});

const { distanceToUser, formatDistance, geoLabel } = useUserGeo();

const distText = computed(() => {
    if (!item.value) return null;
    const d = distanceToUser(item.value.lat, item.value.lng);
    return d != null ? formatDistance(d) : null;
});

/* ── Состояние бронирования ── */
const bookingCheckIn = ref('');
const bookingCheckOut = ref('');
const bookingAdults = ref(2);
const bookingChildren = ref(0);
const totalGuests = computed(() => bookingAdults.value + bookingChildren.value);
const selectedRoom = ref(0);
const bookingConfirmed = ref(false);
const bookingError = ref('');
const activeTab = ref('rooms'); // rooms | amenities | info

// Инициализация дат
onMounted(() => {
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    bookingCheckIn.value = today.toISOString().split('T')[0];
    bookingCheckOut.value = tomorrow.toISOString().split('T')[0];
});

// Минимальная дата — сегодня
const minDate = computed(() => new Date().toISOString().split('T')[0]);
const minCheckOut = computed(() => {
    if (!bookingCheckIn.value) return minDate.value;
    const d = new Date(bookingCheckIn.value);
    d.setDate(d.getDate() + 1);
    return d.toISOString().split('T')[0];
});

// Автоматическая коррекция: выезд не раньше заезда +1
watch(bookingCheckIn, (val) => {
    if (val && bookingCheckOut.value && bookingCheckOut.value <= val) {
        const d = new Date(val);
        d.setDate(d.getDate() + 1);
        bookingCheckOut.value = d.toISOString().split('T')[0];
    }
});

// Кол-во ночей
const bookingNights = computed(() => {
    if (!bookingCheckIn.value || !bookingCheckOut.value) return 0;
    const d1 = new Date(bookingCheckIn.value);
    const d2 = new Date(bookingCheckOut.value);
    const diff = Math.floor((d2 - d1) / 86400000);
    return diff > 0 ? diff : 0;
});

// Фильтруем номера по вместимости (взрослые + дети)
const suitableRooms = computed(() => {
    if (!item.value?.rooms) return [];
    return item.value.rooms.filter(r => r.capacity >= totalGuests.value && r.available > 0);
});

// Итоговая стоимость
const bookingTotal = computed(() => {
    if (bookingNights.value <= 0 || !suitableRooms.value.length) return 0;
    const room = suitableRooms.value[selectedRoom.value] || suitableRooms.value[0];
    return room ? room.pricePerNight * bookingNights.value : 0;
});

// Форматирование цены
const formatPrice = (p) => {
    const n = typeof p === 'number' ? p : parseInt(String(p).replace(/\s/g, ''), 10);
    return isNaN(n) ? p : n.toLocaleString('ru-RU');
};

// Форматирование даты
const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
};

// Склонение слов
const plural = (n, one, few, many) => {
    const mod10 = n % 10;
    const mod100 = n % 100;
    if (mod100 >= 11 && mod100 <= 14) return many;
    if (mod10 === 1) return one;
    if (mod10 >= 2 && mod10 <= 4) return few;
    return many;
};

/* ── Бронирование ── */
const confirmBooking = () => {
    bookingError.value = '';

    if (bookingNights.value <= 0) {
        bookingError.value = 'Укажите корректные даты заезда и выезда';
        return;
    }
    if (totalGuests.value < 1) {
        bookingError.value = 'Укажите хотя бы 1 гостя';
        return;
    }
    if (suitableRooms.value.length === 0) {
        bookingError.value = `Нет свободных номеров на ${totalGuests.value} ${plural(totalGuests.value, 'гостя', 'гостей', 'гостей')}`;
        return;
    }

    bookingConfirmed.value = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

/* ══════════════════════════════════════════════════════════════
   ML-РЕКОМЕНДАЦИИ ПОСЛЕ БРОНИРОВАНИЯ
   Алгоритм:
   1. Находим вертикали смежных категорий (цветы, еда, мероприятия, транспорт)
   2. Генерируем демо-товары из каждой
   3. Сортируем по "расстоянию" от отеля + рейтинг (имитация ML-скоринга)
   4. Показываем Top-N по каждой категории
   ══════════════════════════════════════════════════════════════ */

// Все вертикали в плоском виде
const allVerticals = computed(() => {
    const result = [];
    megaCategories.forEach(cat => {
        cat.verticals.forEach(v => {
            result.push({ ...v, categoryName: cat.name, categoryIcon: cat.icon });
        });
    });
    return result;
});

// Определяем «рекомендательные» группы
const recommendationGroups = computed(() => {
    if (!bookingConfirmed.value || !item.value) return [];

    const hotelLat = item.value.lat || 55.75;
    const hotelLng = item.value.lng || 37.62;

    const groups = [
        {
            title: '🌸 Цветы и подарки',
            desc: 'Порадуйте близких по приезду',
            slugs: ['flowers', 'gifts', 'souvenirs', 'perfumery', 'jewelry'],
            type: 'product',
        },
        {
            title: '🍽️ Рестораны и еда рядом',
            desc: 'Лучшие заведения рядом с вашим отелем',
            slugs: ['restaurants', 'cafes', 'fastfood', 'sushi', 'pizza', 'burgers', 'bakeries', 'bars'],
            type: 'food',
        },
        {
            title: '🎭 Мероприятия в ваши даты',
            desc: `${formatDate(bookingCheckIn.value)} — ${formatDate(bookingCheckOut.value)}`,
            slugs: ['concerts', 'theater', 'exhibitions', 'festivals', 'cinema', 'quests', 'amusement', 'bowling'],
            type: 'event',
        },
        {
            title: '🚕 Транспорт',
            desc: 'Добраться до отеля с комфортом',
            slugs: ['taxi', 'car-rental', 'transfer', 'carsharing'],
            type: 'transport',
        },
        {
            title: '💆 SPA и красота',
            desc: 'Расслабьтесь во время отдыха',
            slugs: ['spa', 'massage', 'cosmetology', 'barbershop', 'nails'],
            type: 'service',
        },
    ];

    const result = [];

    for (const group of groups) {
        // Ищем реальные совпадения среди существующих вертикалей
        const matched = allVerticals.value.filter(v => group.slugs.includes(v.slug));
        if (matched.length === 0) continue;

        // Выбираем до 2-х вертикалей и генерируем товары
        const picked = matched.slice(0, 2);
        let items = [];
        for (const vert of picked) {
            const generated = generateDemoItems(vert, 6);
            items.push(...generated.map(gi => ({
                ...gi,
                _verticalSlug: vert.slug,
                _verticalType: vert.type || group.type,
            })));
        }

        // ML-скоринг: расстояние от отеля × рейтинг × random вкусовой коэффициент
        items = items.map(gi => {
            const dLat = (gi.lat || 55.75) - hotelLat;
            const dLng = (gi.lng || 37.62) - hotelLng;
            const distKm = Math.sqrt(dLat * dLat + dLng * dLng) * 111;
            const tasteAffinity = 0.5 + Math.random() * 0.5; // имитация ML taste profile
            const recencyBoost = 1.0 + Math.random() * 0.3;
            const mlScore = (gi.rating || 4.0) * tasteAffinity * recencyBoost / (1 + distKm * 0.2);
            return { ...gi, _mlScore: mlScore, _distKm: distKm };
        });

        // Сортируем по ML-скору и берём Top-6
        items.sort((a, b) => b._mlScore - a._mlScore);
        items = items.slice(0, 6);

        if (items.length > 0) {
            result.push({ ...group, items });
        }
    }

    return result;
});

// Действие на рекомендации
const onRecommendationAction = (recItem) => {
    // Для booking-type → переход на отдельную страницу бронирования
    if (recItem._verticalType === 'booking' && recItem.rooms?.length) {
        router.visit(`/booking/${recItem._verticalSlug}/${recItem.id}`);
        return;
    }
    alert(`${typeMeta[recItem._verticalType]?.cta || 'Добавить'}: ${recItem.name} — ${recItem.price?.toLocaleString('ru-RU')} ₽`);
};
</script>

<template>
    <Head :title="`Бронирование — ${item?.name || 'Загрузка...'}`" />

    <AppLayout show-back :back-href="`/category/${slug}`" title="Бронирование">
        <div v-if="!item" class="text-center py-20">
            <p class="text-4xl mb-4">😔</p>
            <p class="text-lg font-bold" style="color: var(--t-text);">Объект не найден</p>
            <Link :href="`/category/${slug}`" class="mt-4 inline-block text-sm font-bold" style="color: var(--t-primary);">
                ← Вернуться к списку
            </Link>
        </div>

        <template v-else>

            <!-- ════════════ ПОДТВЕРЖДЕНИЕ БРОНИ ════════════ -->
            <Transition enter-from-class="opacity-0 scale-95" enter-active-class="transition duration-500">
                <div v-if="bookingConfirmed" class="mb-8">
                    <!-- Confirmation card -->
                    <div class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-primary);">
                        <div class="p-6 md:p-8 text-center space-y-4">
                            <div class="text-6xl animate-bounce">✅</div>
                            <h2 class="text-2xl md:text-3xl font-black" style="color: var(--t-text);">Бронь подтверждена!</h2>
                            <div class="max-w-md mx-auto space-y-2 text-sm" style="color: var(--t-text-2);">
                                <p class="font-bold text-lg" style="color: var(--t-text);">{{ item.name }}</p>
                                <p>{{ suitableRooms[selectedRoom]?.name || suitableRooms[0]?.name }}</p>
                                <p>{{ formatDate(bookingCheckIn) }} — {{ formatDate(bookingCheckOut) }}
                                    <span class="font-bold">({{ bookingNights }} {{ plural(bookingNights, 'ночь', 'ночи', 'ночей') }})</span>
                                </p>
                                <p>👥 {{ bookingAdults }} {{ plural(bookingAdults, 'взрослый', 'взрослых', 'взрослых') }}
                                    <template v-if="bookingChildren > 0">, {{ bookingChildren }} {{ plural(bookingChildren, 'ребёнок', 'ребёнка', 'детей') }}</template>
                                </p>
                                <p class="text-xl font-black pt-2" style="color: var(--t-primary);">
                                    {{ formatPrice(bookingTotal) }} ₽
                                </p>
                            </div>
                            <p class="text-xs" style="color: var(--t-text-3);">📧 Подтверждение отправлено на ваш email</p>

                            <div class="flex justify-center gap-3 pt-4">
                                <button @click="bookingConfirmed = false"
                                    class="px-5 py-2.5 rounded-xl text-sm font-bold border cursor-pointer transition-all active:scale-95"
                                    style="border-color: var(--t-border); color: var(--t-text-2);">
                                    Изменить бронь
                                </button>
                                <Link :href="`/category/${slug}`"
                                    class="px-5 py-2.5 rounded-xl text-sm font-bold text-white cursor-pointer transition-all active:scale-95"
                                    style="background: var(--t-primary);">
                                    К списку
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- ════════════ ML-РЕКОМЕНДАЦИИ ════════════ -->
                    <div v-if="recommendationGroups.length" class="mt-10 space-y-10">
                        <div class="text-center">
                            <h2 class="text-xl md:text-2xl font-black" style="color: var(--t-text);">
                                🤖 Рекомендации для вашей поездки
                            </h2>
                            <p class="text-sm mt-1" style="color: var(--t-text-3);">
                                AI подобрал лучшие предложения рядом с «{{ item.name }}»
                            </p>
                        </div>

                        <div v-for="(group, gi) in recommendationGroups" :key="gi" class="space-y-3">
                            <div>
                                <h3 class="text-lg font-bold" style="color: var(--t-text);">{{ group.title }}</h3>
                                <p class="text-xs" style="color: var(--t-text-3);">{{ group.desc }}</p>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-3">
                                <UniversalCard
                                    v-for="ri in group.items" :key="`${gi}-${ri.id}`"
                                    :item="ri"
                                    :type="ri._verticalType || group.type"
                                    compact
                                    @action="onRecommendationAction"
                                    @click="onRecommendationAction" />
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- ════════════ ОСНОВНАЯ ФОРМА БРОНИРОВАНИЯ ════════════ -->
            <div v-if="!bookingConfirmed" class="space-y-6">

                <!-- Хлебные крошки -->
                <div class="flex items-center gap-1 text-xs flex-wrap" style="color: var(--t-text-3);">
                    <Link href="/" class="hover:underline" style="color: var(--t-primary);">Главная</Link>
                    <span>›</span>
                    <Link v-if="parentCat" href="/categories" class="hover:underline" style="color: var(--t-primary);">{{ parentCat.name }}</Link>
                    <span v-if="parentCat">›</span>
                    <Link :href="`/category/${slug}`" class="hover:underline" style="color: var(--t-primary);">{{ vertical.name }}</Link>
                    <span>›</span>
                    <span>{{ item.name }}</span>
                </div>

                <!-- ═══ Герой: фото + основная информация ═══ -->
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                    <!-- Левая часть: фото + инфо -->
                    <div class="lg:col-span-3 space-y-5">
                        <!-- Фото -->
                        <div class="relative rounded-2xl overflow-hidden aspect-[16/9]">
                            <img :src="item.image" :alt="item.name" class="w-full h-full object-cover" />
                            <div class="absolute inset-0 bg-linear-to-t from-black/40 via-transparent to-transparent"></div>
                            <span class="absolute top-4 left-4 text-xs uppercase font-extrabold tracking-wider px-3 py-1.5 rounded-lg backdrop-blur-md text-white"
                                  style="background: var(--t-primary);">
                                {{ item.accommodationLabel || meta.badge }}
                            </span>
                            <span v-if="item.hotelStars" class="absolute top-4 right-4 flex items-center gap-0.5 px-2.5 py-1 rounded-lg backdrop-blur-md bg-black/40">
                                <span v-for="s in item.hotelStars" :key="s" class="text-yellow-400 text-sm">★</span>
                            </span>
                        </div>

                        <!-- Название + рейтинг -->
                        <div>
                            <h1 class="text-2xl md:text-3xl font-black" style="color: var(--t-text);">{{ item.name }}</h1>
                            <div class="flex items-center gap-3 mt-2">
                                <div v-if="item.rating" class="flex items-center gap-1.5">
                                    <span class="flex">
                                        <span v-for="s in 5" :key="s" class="text-lg"
                                              :style="{ color: s <= Math.round(item.rating) ? '#facc15' : 'rgba(128,128,128,.25)' }">★</span>
                                    </span>
                                    <span class="text-sm font-bold" style="color: var(--t-text);">{{ item.rating }}</span>
                                </div>
                                <span v-if="distText" class="text-xs px-2 py-0.5 rounded-full" style="background: var(--t-surface); color: var(--t-text-3);">
                                    📍 {{ distText }} от вас
                                </span>
                            </div>
                            <p v-if="item.address" class="text-sm mt-2" style="color: var(--t-text-2);">📍 {{ item.address }}</p>
                        </div>

                        <!-- Tabs: Номера / Удобства / Информация -->
                        <div class="flex gap-1 border-b" style="border-color: var(--t-border);">
                            <button v-for="tab in [
                                { id: 'rooms', label: '🏠 Номера', count: item.rooms?.length },
                                { id: 'amenities', label: '✨ Удобства', count: item.amenities?.length },
                                { id: 'info', label: 'ℹ️ Информация' },
                            ]" :key="tab.id"
                                @click="activeTab = tab.id"
                                class="px-4 py-3 text-sm font-bold transition-all cursor-pointer border-b-2 -mb-px"
                                :style="activeTab === tab.id
                                    ? { borderColor: 'var(--t-primary)', color: 'var(--t-primary)' }
                                    : { borderColor: 'transparent', color: 'var(--t-text-3)' }">
                                {{ tab.label }}
                                <span v-if="tab.count" class="ml-1 text-xs opacity-60">({{ tab.count }})</span>
                            </button>
                        </div>

                        <!-- Tab: Номера -->
                        <div v-if="activeTab === 'rooms'" class="space-y-3">
                            <div v-if="suitableRooms.length === 0 && item.rooms?.length"
                                 class="rounded-xl p-4 text-center"
                                 style="background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.2);">
                                <p class="text-sm font-bold" style="color: #ef4444;">
                                    😔 Нет свободных номеров на {{ totalGuests }} {{ plural(totalGuests, 'гостя', 'гостей', 'гостей') }}
                                </p>
                                <p class="text-xs mt-1" style="color: var(--t-text-3);">Попробуйте уменьшить число гостей или выбрать другие даты</p>
                            </div>

                            <div v-for="(room, ri) in suitableRooms" :key="ri"
                                 class="rounded-xl p-4 transition-all cursor-pointer"
                                 :style="selectedRoom === ri
                                     ? { background: 'var(--t-primary-dim)', border: '2px solid var(--t-primary)' }
                                     : { background: 'var(--t-surface)', border: '1px solid var(--t-border)' }"
                                 @click="selectedRoom = ri">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-3">
                                        <span v-if="selectedRoom === ri"
                                              class="w-6 h-6 rounded-full flex items-center justify-center text-xs text-white"
                                              style="background: var(--t-primary);">✓</span>
                                        <span v-else class="w-6 h-6 rounded-full border-2" style="border-color: var(--t-border);"></span>
                                        <span class="text-base font-bold" style="color: var(--t-text);">{{ room.name }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-black" style="color: var(--t-text);">{{ formatPrice(room.pricePerNight) }} ₽</span>
                                        <span class="text-[11px] block" style="color: var(--t-text-3);">/ночь</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 text-xs" style="color: var(--t-text-3);">
                                    <span>👥 до {{ room.capacity }} гостей</span>
                                    <span>📐 {{ room.area }} м²</span>
                                    <span :style="{ color: room.available > 0 ? 'var(--t-primary)' : '#ef4444' }">
                                        {{ room.available > 0 ? `✓ Свободно: ${room.available}` : '✕ Нет мест' }}
                                    </span>
                                </div>
                                <!-- Расчёт за выбранный период -->
                                <div v-if="bookingNights > 0 && selectedRoom === ri"
                                     class="mt-3 pt-3 border-t flex items-center justify-between"
                                     style="border-color: var(--t-border);">
                                    <span class="text-sm" style="color: var(--t-text-2);">
                                        {{ bookingNights }} {{ plural(bookingNights, 'ночь', 'ночи', 'ночей') }} × {{ formatPrice(room.pricePerNight) }} ₽
                                    </span>
                                    <span class="text-xl font-black" style="color: var(--t-primary);">
                                        {{ formatPrice(room.pricePerNight * bookingNights) }} ₽
                                    </span>
                                </div>
                            </div>

                            <!-- Все номера (недоступные — серые) -->
                            <div v-if="item.rooms?.length && suitableRooms.length < item.rooms.length" class="pt-4">
                                <p class="text-xs font-bold uppercase mb-2" style="color: var(--t-text-3);">Недоступные номера</p>
                                <div v-for="(room, ri) in item.rooms.filter(r => r.capacity < totalGuests || r.available === 0)" :key="`na-${ri}`"
                                     class="rounded-xl p-3 mb-2 opacity-50"
                                     style="background: var(--t-bg); border: 1px solid var(--t-border);">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm" style="color: var(--t-text-3);">{{ room.name }}</span>
                                        <span class="text-sm" style="color: var(--t-text-3);">{{ formatPrice(room.pricePerNight) }} ₽/ночь</span>
                                    </div>
                                    <div class="flex gap-3 text-[11px] mt-1" style="color: var(--t-text-3);">
                                        <span>👥 до {{ room.capacity }}</span>
                                        <span>📐 {{ room.area }} м²</span>
                                        <span v-if="room.available === 0" style="color: #ef4444;">✕ Нет мест</span>
                                        <span v-else-if="room.capacity < totalGuests" style="color: #f59e0b;">⚠ Мало мест</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab: Удобства -->
                        <div v-if="activeTab === 'amenities'" class="space-y-3">
                            <div v-if="item.amenities?.length" class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <div v-for="a in item.amenities" :key="a"
                                     class="flex items-center gap-2 p-3 rounded-xl"
                                     style="background: var(--t-surface); border: 1px solid var(--t-border);">
                                    <span class="text-lg">{{ (item.amenityIcons && item.amenityIcons[a]) || '✓' }}</span>
                                    <span class="text-sm font-semibold" style="color: var(--t-text);">{{ a }}</span>
                                </div>
                            </div>
                            <p v-else class="text-sm text-center py-8" style="color: var(--t-text-3);">Информация об удобствах не указана</p>
                        </div>

                        <!-- Tab: Информация -->
                        <div v-if="activeTab === 'info'" class="space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-xl p-4" style="background: var(--t-surface); border: 1px solid var(--t-border);">
                                    <p class="text-xs font-bold uppercase mb-1" style="color: var(--t-text-3);">Заезд</p>
                                    <p class="text-lg font-bold" style="color: var(--t-text);">{{ item.checkIn || '14:00' }}</p>
                                </div>
                                <div class="rounded-xl p-4" style="background: var(--t-surface); border: 1px solid var(--t-border);">
                                    <p class="text-xs font-bold uppercase mb-1" style="color: var(--t-text-3);">Выезд</p>
                                    <p class="text-lg font-bold" style="color: var(--t-text);">{{ item.checkOut || '12:00' }}</p>
                                </div>
                            </div>
                            <div class="rounded-xl p-4" style="background: var(--t-surface); border: 1px solid var(--t-border);">
                                <h3 class="text-xs font-bold uppercase mb-2" style="color: var(--t-text-3);">Описание</h3>
                                <p class="text-sm leading-relaxed" style="color: var(--t-text-2);">
                                    Премиальное размещение с подтверждением в реальном времени.
                                    Бесплатная отмена бронирования за 24 часа до заезда.
                                    Виртуальный 3D-тур по номерам доступен. AI-персонализация
                                    подобрала этот объект специально для вас на основе ваших предпочтений.
                                </p>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div v-for="feat in [
                                    { icon: '📅', label: 'Бронь', value: 'Мгновенная' },
                                    { icon: '❌', label: 'Отмена', value: 'Бесплатно' },
                                    { icon: '🏠', label: '3D-тур', value: 'Доступен' },
                                    { icon: '⭐', label: 'Качество', value: 'Проверено' },
                                ]" :key="feat.label"
                                     class="flex items-center gap-2 rounded-lg p-3 text-xs"
                                     style="background: var(--t-bg); border: 1px solid var(--t-border);">
                                    <span class="text-base">{{ feat.icon }}</span>
                                    <div>
                                        <p class="font-bold" style="color: var(--t-text);">{{ feat.label }}</p>
                                        <p style="color: var(--t-text-3);">{{ feat.value }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═══ Правая часть: форма бронирования (sticky) ═══ -->
                    <div class="lg:col-span-2">
                        <div class="lg:sticky lg:top-4 space-y-4">

                            <!-- Карточка бронирования -->
                            <div class="rounded-2xl border p-5 space-y-5"
                                 style="background: var(--t-surface); border-color: var(--t-border);">

                                <div class="flex items-baseline gap-2">
                                    <span class="text-[11px] uppercase" style="color: var(--t-text-3);">от</span>
                                    <span class="text-2xl font-black" style="color: var(--t-text);">
                                        {{ formatPrice(item.pricePerNight || item.price) }} ₽
                                    </span>
                                    <span class="text-sm" style="color: var(--t-text-3);">/ночь</span>
                                </div>

                                <!-- Даты: заезд — выезд -->
                                <div>
                                    <label class="text-[11px] font-bold uppercase mb-1.5 block" style="color: var(--t-text-3);">📅 Даты проживания</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[10px] mb-0.5 block" style="color: var(--t-text-3);">Заезд</label>
                                            <input v-model="bookingCheckIn" type="date" :min="minDate"
                                                   class="w-full px-3 py-2.5 rounded-lg text-sm outline-none"
                                                   style="background: var(--t-bg); border: 1px solid var(--t-border); color: var(--t-text);" />
                                        </div>
                                        <div>
                                            <label class="text-[10px] mb-0.5 block" style="color: var(--t-text-3);">Выезд</label>
                                            <input v-model="bookingCheckOut" type="date" :min="minCheckOut"
                                                   class="w-full px-3 py-2.5 rounded-lg text-sm outline-none"
                                                   style="background: var(--t-bg); border: 1px solid var(--t-border); color: var(--t-text);" />
                                        </div>
                                    </div>
                                    <p v-if="bookingNights > 0" class="text-xs mt-1.5" style="color: var(--t-text-2);">
                                        📆 {{ bookingNights }} {{ plural(bookingNights, 'ночь', 'ночи', 'ночей') }}
                                    </p>
                                </div>

                                <!-- Взрослые -->
                                <div>
                                    <label class="text-[11px] font-bold uppercase mb-1.5 block" style="color: var(--t-text-3);">👤 Взрослые</label>
                                    <div class="flex items-center gap-3">
                                        <button @click="bookingAdults = Math.max(1, bookingAdults - 1)"
                                                class="w-10 h-10 rounded-xl flex items-center justify-center text-lg font-bold cursor-pointer transition-all active:scale-90"
                                                style="background: var(--t-bg); border: 1px solid var(--t-border); color: var(--t-text);">−</button>
                                        <span class="text-xl font-black w-8 text-center" style="color: var(--t-text);">{{ bookingAdults }}</span>
                                        <button @click="bookingAdults = Math.min(10, bookingAdults + 1)"
                                                class="w-10 h-10 rounded-xl flex items-center justify-center text-lg font-bold cursor-pointer transition-all active:scale-90"
                                                style="background: var(--t-bg); border: 1px solid var(--t-border); color: var(--t-text);">+</button>
                                    </div>
                                </div>

                                <!-- Дети -->
                                <div>
                                    <label class="text-[11px] font-bold uppercase mb-1.5 block" style="color: var(--t-text-3);">🧒 Дети (0–12 лет)</label>
                                    <div class="flex items-center gap-3">
                                        <button @click="bookingChildren = Math.max(0, bookingChildren - 1)"
                                                class="w-10 h-10 rounded-xl flex items-center justify-center text-lg font-bold cursor-pointer transition-all active:scale-90"
                                                style="background: var(--t-bg); border: 1px solid var(--t-border); color: var(--t-text);">−</button>
                                        <span class="text-xl font-black w-8 text-center" style="color: var(--t-text);">{{ bookingChildren }}</span>
                                        <button @click="bookingChildren = Math.min(6, bookingChildren + 1)"
                                                class="w-10 h-10 rounded-xl flex items-center justify-center text-lg font-bold cursor-pointer transition-all active:scale-90"
                                                style="background: var(--t-bg); border: 1px solid var(--t-border); color: var(--t-text);">+</button>
                                    </div>
                                </div>

                                <!-- Итого гостей -->
                                <div class="rounded-lg p-3 text-center" style="background: var(--t-bg); border: 1px solid var(--t-border);">
                                    <p class="text-xs" style="color: var(--t-text-3);">
                                        Всего: <b style="color: var(--t-text);">{{ totalGuests }}</b>
                                        {{ plural(totalGuests, 'гость', 'гостя', 'гостей') }}
                                        <template v-if="bookingChildren > 0">
                                            ({{ bookingAdults }} взр. + {{ bookingChildren }} дет.)
                                        </template>
                                    </p>
                                </div>

                                <!-- Итоговая стоимость -->
                                <div v-if="bookingTotal > 0" class="pt-2 border-t" style="border-color: var(--t-border);">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm" style="color: var(--t-text-2);">
                                            {{ suitableRooms[selectedRoom]?.name || suitableRooms[0]?.name }} × {{ bookingNights }} {{ plural(bookingNights, 'ночь', 'ночи', 'ночей') }}
                                        </span>
                                    </div>
                                    <div class="flex items-baseline justify-between">
                                        <span class="text-xs uppercase" style="color: var(--t-text-3);">Итого</span>
                                        <span class="text-3xl font-black" style="color: var(--t-primary);">
                                            {{ formatPrice(bookingTotal) }} ₽
                                        </span>
                                    </div>
                                </div>

                                <!-- Ошибка -->
                                <Transition enter-from-class="opacity-0 -translate-y-1" enter-active-class="transition duration-200">
                                    <p v-if="bookingError" class="text-sm font-bold text-center px-3 py-2 rounded-lg"
                                       style="color: #ef4444; background: rgba(239,68,68,.08);">
                                        ⚠️ {{ bookingError }}
                                    </p>
                                </Transition>

                                <!-- Кнопка бронирования -->
                                <button @click="confirmBooking"
                                    class="w-full py-4 rounded-xl text-white font-bold text-base transition-all active:scale-[0.95] hover:scale-[1.02] hover:shadow-2xl cursor-pointer flex items-center justify-center gap-2"
                                    style="background: var(--t-btn); box-shadow: 0 4px 20px var(--t-glow);">
                                    Забронировать
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </button>

                                <p class="text-[10px] text-center" style="color: var(--t-text-3);">
                                    Бесплатная отмена за 24 часа · Мгновенное подтверждение
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </template>
    </AppLayout>
</template>

<style scoped>
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
.animate-bounce { animation: bounce 1s ease-in-out 2; }
</style>
