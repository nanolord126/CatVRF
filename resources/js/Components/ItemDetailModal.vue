<template>
  <Teleport to="body">
    <Transition enter-from-class="opacity-0" enter-active-class="transition duration-300"
                leave-to-class="opacity-0" leave-active-class="transition duration-200">
      <div v-if="item" class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center"
           @click.self="$emit('close')">

        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="$emit('close')"></div>

        <!-- Modal Content -->
        <Transition enter-from-class="translate-y-full sm:translate-y-0 sm:scale-95 opacity-0"
                    enter-active-class="transition duration-350 ease-out"
                    leave-to-class="translate-y-full sm:translate-y-0 sm:scale-95 opacity-0"
                    leave-active-class="transition duration-200 ease-in">
          <div v-if="item"
               class="relative z-10 w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl overflow-hidden shadow-2xl max-h-[92vh] flex flex-col"
               style="background: var(--t-surface);">

            <!-- Close button -->
            <button @click="$emit('close')"
                    class="absolute top-3 right-3 z-30 w-9 h-9 rounded-full flex items-center justify-center backdrop-blur-md transition-all cursor-pointer active:scale-90 hover:scale-110 hover:bg-red-500/80"
                    style="background: rgba(0,0,0,.45); color: white;">
              ✕
            </button>

            <!-- Image section -->
            <div class="relative w-full aspect-[4/3] sm:aspect-[16/10] overflow-hidden shrink-0">
              <img :src="item.image" :alt="item.name"
                   class="w-full h-full object-cover" />

              <!-- Gradient over image -->
              <div class="absolute inset-0 bg-linear-to-t from-black/50 via-transparent to-transparent"></div>

              <!-- Badge on image -->
              <span class="absolute top-3 left-3 text-[10px] uppercase font-extrabold tracking-wider px-2.5 py-1 rounded-lg backdrop-blur-md"
                    style="background: var(--t-primary); color: white;">
                {{ meta.badge }}
              </span>

              <!-- B2B Wholesale badge -->
              <span v-if="item.isB2B || item.b2bPrice"
                    class="absolute top-3 left-[calc(3rem+60px)] text-[10px] uppercase font-extrabold tracking-wider px-2.5 py-1 rounded-lg backdrop-blur-md"
                    style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
                🏢 B2B
              </span>

              <!-- Like button -->
              <button @click.stop="toggleFavorite"
                      class="absolute top-3 right-14 w-9 h-9 rounded-full flex items-center justify-center backdrop-blur-md transition-all cursor-pointer active:scale-90 hover:scale-110"
                      :style="{ background: isFav ? 'rgba(239,68,68,.8)' : 'rgba(0,0,0,.45)' }">
                <span class="text-white text-sm">{{ isFav ? '❤️' : '🤍' }}</span>
              </button>
            </div>

            <!-- Content section -->
            <div class="flex-1 overflow-y-auto p-5 space-y-4">
              <!-- Category -->
              <span class="text-[11px] font-bold uppercase tracking-wider"
                    style="color: var(--t-primary);">
                {{ item.category || meta.badge }}
              </span>

              <!-- Title -->
              <h2 class="text-xl font-black leading-tight" style="color: var(--t-text);">
                {{ item.name }}
              </h2>

              <!-- Star Rating -->
              <div v-if="item.rating" class="flex items-center gap-2">
                <span class="flex">
                  <span v-for="s in 5" :key="s" class="text-lg"
                        :style="{ color: s <= Math.round(item.rating) ? '#facc15' : 'rgba(128,128,128,.25)' }">★</span>
                </span>
                <span class="text-sm font-bold" style="color: var(--t-text);">{{ item.rating }}</span>
                <span class="text-xs" style="color: var(--t-text-3);">
                  · {{ Math.floor(Math.random() * 500 + 50) }} отзывов
                </span>
              </div>

              <!-- Subtitle / meta info -->
              <p v-if="item.subtitle" class="text-sm" style="color: var(--t-text-2);">
                {{ item.subtitle }}
              </p>

              <!-- 📍 Адрес + расстояние + тип доставки -->
              <div v-if="item.address || geo" class="rounded-xl p-3 space-y-2"
                   style="background: var(--t-bg); border: 1px solid var(--t-border);">

                <!-- Адрес -->
                <div v-if="item.address" class="flex items-start gap-2">
                  <span class="text-base mt-0.5">📍</span>
                  <div>
                    <p class="text-sm font-bold" style="color: var(--t-text);">{{ item.address }}</p>
                    <p v-if="distText" class="text-xs" style="color: var(--t-text-3);">{{ distText }} от вас</p>
                  </div>
                </div>

                <!-- Тип доставки -->
                <div v-if="deliveryLabel" class="flex items-center gap-2 pt-1 border-t" style="border-color: var(--t-border);">
                  <span class="text-base">{{ deliveryLabel.icon }}</span>
                  <div>
                    <p class="text-sm font-bold" style="color: var(--t-text);">{{ deliveryLabel.title }}</p>
                    <p class="text-xs" style="color: var(--t-text-3);">{{ deliveryLabel.desc }}</p>
                  </div>
                </div>
              </div>

              <!-- Description (generated) -->
              <div class="rounded-xl p-4" style="background: var(--t-bg); border: 1px solid var(--t-border);">
                <h3 class="text-xs font-bold uppercase mb-2" style="color: var(--t-text-3);">Описание</h3>
                <p class="text-sm leading-relaxed" style="color: var(--t-text-2);">
                  {{ description }}
                </p>
              </div>

              <!-- ════════════ BOOKING: Полная секция бронирования ════════════ -->
              <template v-if="isBookingAccommodation">

                <!-- Тип размещения + звёзды + заезд/выезд -->
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="text-xs font-bold px-2.5 py-1 rounded-lg"
                        style="background: var(--t-primary-dim); color: var(--t-primary);">
                    {{ item.accommodationLabel }}
                  </span>
                  <span v-if="item.hotelStars" class="flex items-center gap-0.5">
                    <span v-for="s in item.hotelStars" :key="s" class="text-sm text-yellow-400">★</span>
                  </span>
                  <span class="text-[11px]" style="color: var(--t-text-3);">
                    Заезд {{ item.checkIn }} · Выезд {{ item.checkOut }}
                  </span>
                </div>

                <!-- Удобства -->
                <div v-if="item.amenities && item.amenities.length" class="rounded-xl p-3"
                     style="background: var(--t-bg); border: 1px solid var(--t-border);">
                  <h3 class="text-xs font-bold uppercase mb-2" style="color: var(--t-text-3);">Удобства</h3>
                  <div class="flex flex-wrap gap-2">
                    <span v-for="a in item.amenities" :key="a"
                          class="flex items-center gap-1 text-xs px-2.5 py-1.5 rounded-lg"
                          style="background: var(--t-surface); border: 1px solid var(--t-border); color: var(--t-text);">
                      <span>{{ (item.amenityIcons && item.amenityIcons[a]) || '✓' }}</span>
                      {{ a }}
                    </span>
                  </div>
                </div>

                <!-- Даты и гости -->
                <div class="rounded-xl p-4 space-y-3"
                     style="background: var(--t-bg); border: 1px solid var(--t-border);">
                  <h3 class="text-xs font-bold uppercase mb-1" style="color: var(--t-text-3);">📅 Даты и гости</h3>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="text-[11px] font-bold mb-1 block" style="color: var(--t-text-3);">Заезд</label>
                      <input v-model="bookingCheckIn" type="date"
                             class="w-full px-3 py-2.5 rounded-lg text-sm outline-none"
                             style="background: var(--t-surface); border: 1px solid var(--t-border); color: var(--t-text);" />
                    </div>
                    <div>
                      <label class="text-[11px] font-bold mb-1 block" style="color: var(--t-text-3);">Выезд</label>
                      <input v-model="bookingCheckOut" type="date"
                             class="w-full px-3 py-2.5 rounded-lg text-sm outline-none"
                             style="background: var(--t-surface); border: 1px solid var(--t-border); color: var(--t-text);" />
                    </div>
                  </div>
                  <div>
                    <label class="text-[11px] font-bold mb-1 block" style="color: var(--t-text-3);">Гости</label>
                    <div class="flex items-center gap-3">
                      <button @click="bookingGuests = Math.max(1, bookingGuests - 1)"
                              class="w-9 h-9 rounded-lg flex items-center justify-center text-lg font-bold cursor-pointer transition-all active:scale-90"
                              style="background: var(--t-surface); border: 1px solid var(--t-border); color: var(--t-text);">−</button>
                      <span class="text-lg font-black w-8 text-center" style="color: var(--t-text);">{{ bookingGuests }}</span>
                      <button @click="bookingGuests = Math.min(12, bookingGuests + 1)"
                              class="w-9 h-9 rounded-lg flex items-center justify-center text-lg font-bold cursor-pointer transition-all active:scale-90"
                              style="background: var(--t-surface); border: 1px solid var(--t-border); color: var(--t-text);">+</button>
                      <span class="text-xs" style="color: var(--t-text-3);">{{ bookingGuests === 1 ? 'гость' : bookingGuests < 5 ? 'гостя' : 'гостей' }}</span>
                    </div>
                  </div>
                  <div v-if="bookingNights > 0" class="text-xs pt-1" style="color: var(--t-text-2);">
                    Проживание: <b>{{ bookingNights }}</b> {{ bookingNights === 1 ? 'ночь' : bookingNights < 5 ? 'ночи' : 'ночей' }}
                  </div>
                </div>

                <!-- Номерной фонд -->
                <div v-if="item.rooms && item.rooms.length" class="space-y-2">
                  <h3 class="text-xs font-bold uppercase" style="color: var(--t-text-3);">🏠 Номерной фонд</h3>
                  <div v-for="(room, ri) in suitableRooms" :key="ri"
                       class="rounded-xl p-3 transition-all cursor-pointer hover:scale-[1.02] hover:shadow-lg"
                       :style="selectedRoom === ri
                           ? { background: 'var(--t-primary-dim)', border: '2px solid var(--t-primary)' }
                           : { background: 'var(--t-bg)', border: '1px solid var(--t-border)' }"
                       @click="selectedRoom = ri">
                    <div class="flex items-center justify-between mb-1">
                      <div class="flex items-center gap-2">
                        <span v-if="selectedRoom === ri" class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] text-white" style="background: var(--t-primary);">✓</span>
                        <span class="text-sm font-bold" style="color: var(--t-text);">{{ room.name }}</span>
                      </div>
                      <div class="text-right">
                        <span class="text-sm font-black" style="color: var(--t-text);">{{ formatPrice(room.pricePerNight) }} ₽</span>
                        <span class="text-[10px] block" style="color: var(--t-text-3);">/ночь</span>
                      </div>
                    </div>
                    <div class="flex items-center gap-3 text-[11px]" style="color: var(--t-text-3);">
                      <span>👥 до {{ room.capacity }} гостей</span>
                      <span>📐 {{ room.area }} м²</span>
                      <span :style="{ color: room.available > 0 ? 'var(--t-primary)' : 'red' }">
                        {{ room.available > 0 ? `✓ Свободно: ${room.available}` : '✕ Нет мест' }}
                      </span>
                    </div>
                    <div v-if="bookingNights > 0 && selectedRoom === ri" class="mt-2 pt-2 border-t flex items-center justify-between"
                         style="border-color: var(--t-border);">
                      <span class="text-xs" style="color: var(--t-text-2);">
                        {{ bookingNights }} {{ bookingNights === 1 ? 'ночь' : bookingNights < 5 ? 'ночи' : 'ночей' }} × {{ formatPrice(room.pricePerNight) }} ₽
                      </span>
                      <span class="text-base font-black" style="color: var(--t-primary);">
                        {{ formatPrice(room.pricePerNight * bookingNights) }} ₽
                      </span>
                    </div>
                  </div>

                  <!-- Предупреждение если нет подходящих номеров -->
                  <div v-if="item.rooms.length && suitableRooms.length === 0"
                       class="rounded-xl p-3 text-center text-sm"
                       style="background: var(--t-bg); border: 1px solid var(--t-border); color: var(--t-text-3);">
                    😔 Нет номеров на {{ bookingGuests }} {{ bookingGuests === 1 ? 'гостя' : bookingGuests < 5 ? 'гостей' : 'гостей' }}
                  </div>
                </div>
              </template>

              <!-- ════════════ NON-BOOKING: стандартные фичи ════════════ -->
              <template v-else>
                <!-- Features list -->
                <div class="grid grid-cols-2 gap-2">
                  <div v-for="(feat, idx) in features" :key="idx"
                       class="flex items-center gap-2 rounded-lg p-2.5 text-xs transition-all hover:scale-105 cursor-default"
                       style="background: var(--t-bg); border: 1px solid var(--t-border);">
                    <span>{{ feat.icon }}</span>
                    <div>
                      <p class="font-bold" style="color: var(--t-text);">{{ feat.label }}</p>
                      <p style="color: var(--t-text-3);">{{ feat.value }}</p>
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <!-- ════════════ BOOKING: Подтверждение ════════════ -->
            <Transition enter-from-class="opacity-0 scale-95" enter-active-class="transition duration-300">
              <div v-if="bookingConfirmed" class="absolute inset-0 z-40 flex items-center justify-center p-6" style="background: rgba(0,0,0,.7); backdrop-filter: blur(8px);">
                <div class="rounded-2xl p-6 max-w-sm w-full text-center space-y-4" style="background: var(--t-surface);">
                  <div class="text-5xl">✅</div>
                  <h3 class="text-xl font-black" style="color: var(--t-text);">Бронь подтверждена!</h3>
                  <p class="text-sm whitespace-pre-line" style="color: var(--t-text-2);">{{ bookingConfirmMsg }}</p>
                  <p class="text-xs" style="color: var(--t-text-3);">Подтверждение отправлено на ваш email</p>
                  <button @click="bookingConfirmed = false; $emit('close');"
                          class="w-full py-3 rounded-xl text-white font-bold text-sm cursor-pointer transition-all active:scale-90 hover:scale-105"
                          :style="{ background: 'var(--t-primary)', boxShadow: '0 4px 20px var(--t-glow)' }">
                    Отлично!
                  </button>
                </div>
              </div>
            </Transition>

            <!-- Booking validation error -->
            <Transition enter-from-class="opacity-0 -translate-y-2" enter-active-class="transition duration-200">
              <div v-if="bookingError" class="shrink-0 px-4 py-2 text-center text-sm font-bold"
                   style="color: #ef4444; background: rgba(239,68,68,.08);">
                ⚠️ {{ bookingError }}
              </div>
            </Transition>

            <!-- Bottom bar: price + CTA -->
            <div class="shrink-0 p-4 flex items-center justify-between gap-4 border-t"
                 style="border-color: var(--t-border); background: var(--t-surface);">
              <div>
                <template v-if="isBookingAccommodation && bookingTotal > 0">
                  <p class="text-[10px] uppercase" style="color: var(--t-text-3);">Итого за {{ bookingNights }} {{ bookingNights === 1 ? 'ночь' : bookingNights < 5 ? 'ночи' : 'ночей' }}</p>
                  <p class="text-2xl font-black" style="color: var(--t-text);">
                    {{ formatPrice(bookingTotal) }}&nbsp;₽
                  </p>
                </template>
                <template v-else-if="isBookingAccommodation">
                  <p class="text-[10px] uppercase" style="color: var(--t-text-3);">от</p>
                  <p class="text-2xl font-black" style="color: var(--t-text);">
                    {{ formatPrice(item.pricePerNight || item.price) }}&nbsp;₽<span class="text-sm font-normal" style="color: var(--t-text-3);">/ночь</span>
                  </p>
                </template>
                <template v-else>
                  <p v-if="meta.pricePrefix" class="text-[10px] uppercase" style="color: var(--t-text-3);">{{ meta.pricePrefix }}</p>
                  <p class="text-2xl font-black" style="color: var(--t-text);">
                    {{ formatPrice(item.price) }}&nbsp;₽
                  </p>
                  <p v-if="item.b2bPrice" class="text-xs font-bold" style="color: #f59e0b;">
                    🏢 Опт: {{ formatPrice(item.b2bPrice) }} ₽
                  </p>
                </template>
              </div>

              <button v-if="item.inStock !== false"
                      @click.stop="onCta"
                      class="px-6 py-3 rounded-xl text-white font-bold text-sm transition-all active:scale-90 hover:scale-105 cursor-pointer flex items-center gap-2"
                      :style="{ background: 'var(--t-primary)', boxShadow: '0 4px 20px var(--t-glow)' }">
                {{ meta.cta }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
              </button>
              <span v-else class="text-sm font-bold italic" style="color: var(--t-text-3);">Нет в наличии</span>
            </div>

          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import { typeMeta } from '@/data/verticals.js';
import { useUserGeo } from '@/composables/useUserGeo';

const props = defineProps({
    item: { type: Object, default: null },
    type: { type: String, default: 'product' },
});

const emit = defineEmits(['close', 'action']);

const meta = computed(() => typeMeta[props.type] || typeMeta.product);

const { distanceToUser, formatDistance, geoLabel } = useUserGeo();

/* ── Гео-данные для модалки ── */
const geo = computed(() => {
    if (!props.item) return null;
    const dist = distanceToUser(props.item.lat, props.item.lng);
    return geoLabel(props.item.deliveryMode, dist, props.type);
});

const distText = computed(() => {
    if (!props.item) return null;
    const d = distanceToUser(props.item.lat, props.item.lng);
    return d != null ? formatDistance(d) : null;
});

const deliveryLabel = computed(() => {
    const mode = props.item?.deliveryMode;
    if (!mode) return null;
    const labels = {
        courier: { icon: '🚚', title: 'Курьерская доставка', desc: 'Доставим к вашей двери' },
        visit:   { icon: '📍', title: 'Посещение', desc: 'Вы приходите к нам' },
        pickup:  { icon: '🚗', title: 'Подача', desc: 'Приедем к вам' },
    };
    return labels[mode] || labels.courier;
});

const isFav = ref(false);
const toggleFavorite = () => { isFav.value = !isFav.value; };

// ── Booking state ──
const bookingCheckIn = ref('');
const bookingCheckOut = ref('');
const bookingGuests = ref(2);
const selectedRoom = ref(0);
const bookingConfirmed = ref(false);
const bookingConfirmMsg = ref('');
const bookingError = ref('');

// Является ли элемент размещением (отели, хостелы, апарты и т.д.)
const isBookingAccommodation = computed(() =>
    props.type === 'booking' && props.item?.rooms && props.item.rooms.length > 0
);

// Кол-во ночей
const bookingNights = computed(() => {
    if (!bookingCheckIn.value || !bookingCheckOut.value) return 0;
    const d1 = new Date(bookingCheckIn.value);
    const d2 = new Date(bookingCheckOut.value);
    const diff = Math.floor((d2 - d1) / 86400000);
    return diff > 0 ? diff : 0;
});

// Фильтруем номера по вместимости
const suitableRooms = computed(() => {
    if (!props.item?.rooms) return [];
    return props.item.rooms.filter(r => r.capacity >= bookingGuests.value && r.available > 0);
});

// Итоговая стоимость
const bookingTotal = computed(() => {
    if (bookingNights.value <= 0 || !suitableRooms.value.length) return 0;
    const room = suitableRooms.value[selectedRoom.value] || suitableRooms.value[0];
    return room ? room.pricePerNight * bookingNights.value : 0;
});

// Устанавливаем даты по умолчанию при открытии
watch(() => props.item, (val) => {
    if (val) {
        document.body.style.overflow = 'hidden';
        bookingConfirmed.value = false;
        bookingConfirmMsg.value = '';
        bookingError.value = '';
        // Default dates: today + 1 day
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        bookingCheckIn.value = today.toISOString().split('T')[0];
        bookingCheckOut.value = tomorrow.toISOString().split('T')[0];
        bookingGuests.value = 2;
        selectedRoom.value = 0;
    } else {
        document.body.style.overflow = '';
    }
});

const formatPrice = (p) => {
    const n = typeof p === 'number' ? p : parseInt(String(p).replace(/\s/g, ''), 10);
    return isNaN(n) ? p : n.toLocaleString('ru-RU');
};

const onCta = () => {
    // Для бронирования — валидация и подтверждение прямо в модалке
    if (isBookingAccommodation.value) {
        bookingError.value = '';
        if (bookingNights.value <= 0) {
            bookingError.value = 'Укажите даты заезда и выезда';
            return;
        }
        if (suitableRooms.value.length === 0) {
            bookingError.value = `Нет свободных номеров на ${bookingGuests.value} гостей`;
            return;
        }
        const room = suitableRooms.value[selectedRoom.value] || suitableRooms.value[0];
        const total = room.pricePerNight * bookingNights.value;
        bookingConfirmMsg.value = `${props.item.name}\n${room.name} · ${bookingGuests.value} ${bookingGuests.value === 1 ? 'гость' : bookingGuests.value < 5 ? 'гостя' : 'гостей'}\n${bookingCheckIn.value} — ${bookingCheckOut.value} (${bookingNights.value} ${bookingNights.value === 1 ? 'ночь' : bookingNights.value < 5 ? 'ночи' : 'ночей'})\nИтого: ${formatPrice(total)} ₽`;
        bookingConfirmed.value = true;
        return;
    }
    emit('action', props.item);
    emit('close');
};

// ── Описание (генерируется по типу вертикали) ──
const descriptions = {
    product:   'Высококачественный товар с сертификатами. Быстрая доставка по всей России. Гарантия качества и возврат 14 дней. AI-персонализация подобрала этот товар специально для вас.',
    service:   'Профессиональная услуга от сертифицированных мастеров. Запись онлайн 24/7 с выбором удобного времени. Гарантия результата и индивидуальный подход.',
    transport: 'Комфортная поездка с опытным водителем. Отслеживание маршрута в реальном времени. Безопасность и пунктуальность гарантированы.',
    booking:   'Премиальное бронирование с подтверждением. Бесплатная отмена до 24 часов. Виртуальный тур и 3D-просмотр перед бронированием.',
    food:      'Свежие ингредиенты, приготовление по заказу. Быстрая доставка с отслеживанием курьера. КБЖУ указан для каждого блюда.',
    event:     'Уникальное мероприятие с ограниченным числом мест. Электронный билет на email. VIP-зона и дополнительные привилегии доступны.',
};

const description = computed(() => descriptions[props.type] || descriptions.product);

// ── Фичи (по типу) ──
const featureSets = {
    product: [
        { icon: '🚚', label: 'Доставка', value: '1–3 дня' },
        { icon: '🔄', label: 'Возврат', value: '14 дней' },
        { icon: '✅', label: 'Гарантия', value: '12 мес' },
        { icon: '🤖', label: 'AI-подбор', value: 'Персонально' },
    ],
    service: [
        { icon: '⏱️', label: 'Длительность', value: '30–90 мин' },
        { icon: '📅', label: 'Запись', value: 'Онлайн 24/7' },
        { icon: '👩‍💼', label: 'Мастер', value: 'Сертификат' },
        { icon: '🤖', label: 'AI-подбор', value: 'Персонально' },
    ],
    transport: [
        { icon: '⏱️', label: 'Подача', value: '3–7 мин' },
        { icon: '📍', label: 'Геотрекинг', value: 'Реал-тайм' },
        { icon: '🛡️', label: 'Страховка', value: 'Включена' },
        { icon: '💳', label: 'Оплата', value: 'Безнал / СБП' },
    ],
    booking: [
        { icon: '📅', label: 'Бронь', value: 'Мгновенная' },
        { icon: '❌', label: 'Отмена', value: 'Бесплатно' },
        { icon: '🏠', label: '3D-тур', value: 'Доступен' },
        { icon: '⭐', label: 'Качество', value: 'Проверено' },
    ],
    food: [
        { icon: '🛵', label: 'Доставка', value: '20–40 мин' },
        { icon: '🥗', label: 'КБЖУ', value: 'Указан' },
        { icon: '🔥', label: 'Свежесть', value: 'По заказу' },
        { icon: '📦', label: 'Упаковка', value: 'Эко' },
    ],
    event: [
        { icon: '📍', label: 'Место', value: 'В городе' },
        { icon: '🎫', label: 'Билет', value: 'Электронный' },
        { icon: '👑', label: 'VIP', value: 'Доступен' },
        { icon: '📸', label: 'Фото', value: 'Включено' },
    ],
};

const features = computed(() => featureSets[props.type] || featureSets.product);

// Закрытие по Escape
const onKeydown = (e) => { if (e.key === 'Escape') emit('close'); };
onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>
