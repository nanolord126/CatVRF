<script setup lang="ts">
/**
 * PublicCatalog.vue — публичная B2C-витрина товаров и услуг
 * для конечных клиентов (без авторизации).
 *
 * Универсальный каталог для всех 127 вертикалей CatVRF:
 *   Beauty  (салоны · мастера)       · Taxi   (тарифы · классы)
 *   Food    (рестораны · доставка)    · Hotels (номера · бронь)
 *   RealEstate (квартиры · аренда)   · Flowers (букеты · доставка)
 *   Fashion (одежда · обувь)         · Furniture (мебель · декор)
 *   Fitness (абонементы · тренеры)   · Travel (туры · билеты)
 *   default (универсальный)
 *
 * ─────────────────────────────────────────────────────────────
 *  Функционал:
 *   1.  Hero-секция с поисковой строкой
 *   2.  Sidebar: дерево категорий + фильтры (цена, рейтинг, филиал)
 *   3.  Адаптивный grid карточек (mobile 2 col → desktop 4 col)
 *   4.  Карточка: фото, название, цена, рейтинг, «В корзину» / «Записаться»
 *   5.  Wishlist (❤️) toggle на каждой карточке
 *   6.  Полноэкранная модалка товара: галерея, описание, отзывы, CTA
 *   7.  Мини-превью корзины (floating badge)
 *   8.  Filter-drawer (mobile) + sidebar (desktop)
 *   9.  Infinite scroll + пагинация
 *  10.  Full-screen режим, keyboard (Esc)
 *  11.  VERTICAL_PUBLIC_CONFIG — терминология и CTA по вертикалям
 *  12.  Акции, скидки, промо-бейджи, grayscale для «нет в наличии»
 * ─────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  TYPES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

type ItemStatus     = 'available' | 'limited' | 'out_of_stock' | 'coming_soon' | 'promo'
type PriceType      = 'fixed' | 'from' | 'range' | 'free' | 'negotiable'
type ViewMode       = 'grid' | 'list'
type CtaType        = 'cart' | 'book' | 'contact' | 'subscribe' | 'order'

interface PublicItem {
  id:             number | string
  name:           string
  slug:           string
  shortDesc?:     string
  description?:   string
  imageUrl?:      string
  images?:        string[]
  categoryId:     number | string
  categoryName?:  string
  status:         ItemStatus
  priceType:      PriceType
  price:          number
  priceOld?:      number
  promoLabel?:    string
  unit?:          string
  rating:         number
  reviewsCount:   number
  salesCount:     number
  isPromo:        boolean
  isFeatured:     boolean
  isNew:          boolean
  inStock:        boolean
  tags:           string[]
  branchId?:      number | string
  branchName?:    string
  deliveryTime?:  string
  badge?:         string
  arUrl?:         string
}

interface PublicCategory {
  id:        number | string
  name:      string
  icon:      string
  count:     number
  slug:      string
  parentId?: number | string
  children?: PublicCategory[]
}

interface PublicBranch {
  id:       number | string
  name:     string
  address?: string
  distance?: number
}

interface PublicFilter {
  search:     string
  categoryId: string
  priceMin:   string
  priceMax:   string
  ratingMin:  number
  branchId:   string
  status:     string
  sortBy:     string
  sortDir:    'asc' | 'desc'
  isPromo:    boolean | null
}

interface CartPreview {
  count:   number
  total:   number
}

interface VerticalPublicConfig {
  label:           string
  icon:            string
  heroTitle:       string
  heroSubtitle:    string
  searchPlaceholder: string
  itemLabel:       string
  itemLabelPlural: string
  priceLabel:      string
  unitDefault:     string
  ctaType:         CtaType
  ctaLabel:        string
  ctaIcon:         string
  categories:      Array<{ name: string; icon: string }>
  promoBanner?:    string
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  PROPS & EMITS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const props = withDefaults(defineProps<{
  vertical?:     string
  items?:        PublicItem[]
  categories?:   PublicCategory[]
  branches?:     PublicBranch[]
  cart?:         CartPreview
  totalItems?:   number
  loading?:      boolean
  perPage?:      number
  wishlistIds?:  Set<number | string>
}>(), {
  vertical:    'default',
  items:       () => [],
  categories:  () => [],
  branches:    () => [],
  cart:        () => ({ count: 0, total: 0 }),
  totalItems:  0,
  loading:     false,
  perPage:     24,
  wishlistIds: () => new Set(),
})

const emit = defineEmits<{
  'item-click':       [item: PublicItem]
  'add-to-cart':      [item: PublicItem, qty: number]
  'book-service':     [item: PublicItem]
  'toggle-wishlist':  [item: PublicItem]
  'filter-change':    [filters: PublicFilter]
  'sort-change':      [sortBy: string, sortDir: 'asc' | 'desc']
  'page-change':      [page: number]
  'load-more':        []
  'open-cart':        []
  'share-item':       [item: PublicItem]
  'view-reviews':     [item: PublicItem]
  'ar-preview':       [item: PublicItem]
  'category-click':   [cat: PublicCategory]
}>()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  VERTICAL PUBLIC CONFIG
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const VERTICAL_PUBLIC_CONFIG: Record<string, VerticalPublicConfig> = {
  beauty: {
    label: 'Салоны красоты', icon: '💄',
    heroTitle: 'Найдите идеального мастера',
    heroSubtitle: 'Стрижки, окрашивание, маникюр, макияж — рядом с вами',
    searchPlaceholder: 'Поиск услуги или мастера…',
    itemLabel: 'Услуга', itemLabelPlural: 'Услуги',
    priceLabel: 'Стоимость', unitDefault: 'час',
    ctaType: 'book', ctaLabel: 'Записаться', ctaIcon: '📅',
    categories: [
      { name: 'Стрижки',      icon: '✂️' },
      { name: 'Окрашивание',   icon: '🎨' },
      { name: 'Маникюр',      icon: '💅' },
      { name: 'Косметология',  icon: '💆' },
      { name: 'Массаж',       icon: '🧖' },
      { name: 'Макияж',       icon: '💋' },
    ],
    promoBanner: 'Первый визит — скидка 20%!',
  },

  taxi: {
    label: 'Такси', icon: '🚕',
    heroTitle: 'Закажите поездку',
    heroSubtitle: 'Эконом, комфорт, бизнес — от 5 минут подачи',
    searchPlaceholder: 'Откуда и куда…',
    itemLabel: 'Тариф', itemLabelPlural: 'Тарифы',
    priceLabel: 'Тариф', unitDefault: 'км',
    ctaType: 'order', ctaLabel: 'Заказать', ctaIcon: '🚗',
    categories: [
      { name: 'Эконом',   icon: '🚗' },
      { name: 'Комфорт',  icon: '🚙' },
      { name: 'Бизнес',   icon: '🚘' },
      { name: 'Премиум',  icon: '🏎️' },
      { name: 'Минивэн',  icon: '🚐' },
      { name: 'Грузовой',  icon: '🚛' },
    ],
    promoBanner: 'Первая поездка — бесплатно!',
  },

  food: {
    label: 'Еда и рестораны', icon: '🍽️',
    heroTitle: 'Вкусная еда с доставкой',
    heroSubtitle: 'Рестораны, кафе и кулинарии — доставим за 30 минут',
    searchPlaceholder: 'Пицца, суши, бургеры…',
    itemLabel: 'Блюдо', itemLabelPlural: 'Блюда',
    priceLabel: 'Цена', unitDefault: 'шт',
    ctaType: 'cart', ctaLabel: 'В корзину', ctaIcon: '🛒',
    categories: [
      { name: 'Завтраки',   icon: '🥐' },
      { name: 'Супы',       icon: '🍜' },
      { name: 'Горячее',    icon: '🥩' },
      { name: 'Салаты',     icon: '🥗' },
      { name: 'Десерты',    icon: '🍰' },
      { name: 'Напитки',    icon: '🍹' },
    ],
    promoBanner: 'Бесплатная доставка от 1 000 ₽',
  },

  hotel: {
    label: 'Отели', icon: '🏨',
    heroTitle: 'Забронируйте номер мечты',
    heroSubtitle: 'Отели, хостелы, апартаменты — лучшие цены онлайн',
    searchPlaceholder: 'Город, отель или район…',
    itemLabel: 'Номер', itemLabelPlural: 'Номера',
    priceLabel: 'За ночь', unitDefault: 'ночь',
    ctaType: 'book', ctaLabel: 'Забронировать', ctaIcon: '🛏️',
    categories: [
      { name: 'Стандарт',     icon: '🛏️' },
      { name: 'Улучшенный',   icon: '🛋️' },
      { name: 'Люкс',         icon: '👑' },
      { name: 'Апартаменты',  icon: '🏠' },
      { name: 'Семейный',     icon: '👨‍👩‍👧‍👦' },
      { name: 'Президентский', icon: '🌟' },
    ],
    promoBanner: 'Early booking — скидка до 30%!',
  },

  realEstate: {
    label: 'Недвижимость', icon: '🏢',
    heroTitle: 'Найдите своё жильё',
    heroSubtitle: 'Квартиры, дома, аренда — без посредников',
    searchPlaceholder: 'Район, улица, метро…',
    itemLabel: 'Объект', itemLabelPlural: 'Объекты',
    priceLabel: 'Цена', unitDefault: 'м²',
    ctaType: 'contact', ctaLabel: 'Связаться', ctaIcon: '📞',
    categories: [
      { name: 'Квартиры',     icon: '🏠' },
      { name: 'Дома',         icon: '🏡' },
      { name: 'Коммерция',    icon: '🏪' },
      { name: 'Новостройки',  icon: '🏗️' },
      { name: 'Аренда',       icon: '🔑' },
      { name: 'Земля',        icon: '🌾' },
    ],
  },

  flowers: {
    label: 'Цветы', icon: '💐',
    heroTitle: 'Букет для любого повода',
    heroSubtitle: 'Свежие цветы с доставкой за 1 час',
    searchPlaceholder: 'Розы, тюльпаны, букеты…',
    itemLabel: 'Букет', itemLabelPlural: 'Букеты',
    priceLabel: 'Цена', unitDefault: 'шт',
    ctaType: 'cart', ctaLabel: 'В корзину', ctaIcon: '🛒',
    categories: [
      { name: 'Розы',          icon: '🌹' },
      { name: 'Полевые',       icon: '🌻' },
      { name: 'Экзотические',  icon: '🌺' },
      { name: 'Свадебные',     icon: '💒' },
      { name: 'Траурные',      icon: '🕊️' },
      { name: 'Комнатные',     icon: '🪴' },
    ],
    promoBanner: 'Доставка бесплатно от 3 000 ₽',
  },

  fashion: {
    label: 'Одежда и обувь', icon: '👗',
    heroTitle: 'Стиль, который вам идёт',
    heroSubtitle: 'Модная одежда, обувь и аксессуары с примеркой',
    searchPlaceholder: 'Платье, кроссовки, сумка…',
    itemLabel: 'Товар', itemLabelPlural: 'Товары',
    priceLabel: 'Цена', unitDefault: 'шт',
    ctaType: 'cart', ctaLabel: 'В корзину', ctaIcon: '🛒',
    categories: [
      { name: 'Платья',     icon: '👗' },
      { name: 'Верхняя',    icon: '🧥' },
      { name: 'Обувь',      icon: '👠' },
      { name: 'Аксессуары', icon: '👜' },
      { name: 'Спорт',      icon: '🏃' },
      { name: 'Бельё',      icon: '🩱' },
    ],
    promoBanner: 'Sale: скидки до 50%!',
  },

  furniture: {
    label: 'Мебель', icon: '🛋️',
    heroTitle: 'Мебель для вашего дома',
    heroSubtitle: 'Диваны, столы, кровати — с доставкой и сборкой',
    searchPlaceholder: 'Диван, стол, кровать…',
    itemLabel: 'Товар', itemLabelPlural: 'Товары',
    priceLabel: 'Цена', unitDefault: 'шт',
    ctaType: 'cart', ctaLabel: 'В корзину', ctaIcon: '🛒',
    categories: [
      { name: 'Диваны',  icon: '🛋️' },
      { name: 'Столы',   icon: '🪑' },
      { name: 'Шкафы',   icon: '🗄️' },
      { name: 'Кровати', icon: '🛏️' },
      { name: 'Кухни',   icon: '🍳' },
      { name: 'Декор',   icon: '🖼️' },
    ],
    promoBanner: 'Бесплатная доставка + сборка!',
  },

  fitness: {
    label: 'Фитнес', icon: '💪',
    heroTitle: 'Тренировки рядом с домом',
    heroSubtitle: 'Абонементы, тренеры, групповые занятия',
    searchPlaceholder: 'Йога, бокс, тренажёрный зал…',
    itemLabel: 'Абонемент', itemLabelPlural: 'Абонементы',
    priceLabel: 'Стоимость', unitDefault: 'мес.',
    ctaType: 'book', ctaLabel: 'Записаться', ctaIcon: '🏋️',
    categories: [
      { name: 'Абонементы',  icon: '🎫' },
      { name: 'Тренировки',  icon: '🏋️' },
      { name: 'Питание',     icon: '🥤' },
      { name: 'Одежда',      icon: '👟' },
      { name: 'Аксессуары',  icon: '🧴' },
      { name: 'Групповые',   icon: '👥' },
    ],
    promoBanner: 'Пробная тренировка — бесплатно!',
  },

  travel: {
    label: 'Путешествия', icon: '✈️',
    heroTitle: 'Ваш идеальный отпуск',
    heroSubtitle: 'Туры, авиабилеты, экскурсии — всё в одном месте',
    searchPlaceholder: 'Турция, Мальдивы, Сочи…',
    itemLabel: 'Тур', itemLabelPlural: 'Туры',
    priceLabel: 'Цена', unitDefault: 'чел.',
    ctaType: 'book', ctaLabel: 'Забронировать', ctaIcon: '✈️',
    categories: [
      { name: 'Пляжный',     icon: '🏖️' },
      { name: 'Экскурсии',   icon: '🗺️' },
      { name: 'Горнолыжный', icon: '⛷️' },
      { name: 'Круизы',      icon: '🚢' },
      { name: 'Авторские',   icon: '🎒' },
      { name: 'Корпоратив',  icon: '🏢' },
    ],
    promoBanner: 'Горящие туры — скидка до 40%!',
  },

  default: {
    label: 'Каталог', icon: '📦',
    heroTitle: 'Откройте для себя лучшее',
    heroSubtitle: 'Товары и услуги с доставкой и гарантией',
    searchPlaceholder: 'Поиск товаров и услуг…',
    itemLabel: 'Товар', itemLabelPlural: 'Товары',
    priceLabel: 'Цена', unitDefault: 'шт',
    ctaType: 'cart', ctaLabel: 'В корзину', ctaIcon: '🛒',
    categories: [
      { name: 'Популярное',  icon: '🔥' },
      { name: 'Новинки',     icon: '✨' },
      { name: 'Услуги',      icon: '🔧' },
      { name: 'Цифровые',    icon: '💾' },
      { name: 'Подписки',    icon: '🔄' },
      { name: 'Прочее',      icon: '📎' },
    ],
  },
}

const vc = computed<VerticalPublicConfig>(() =>
  VERTICAL_PUBLIC_CONFIG[props.vertical] ?? VERTICAL_PUBLIC_CONFIG.default
)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  MAPS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const STATUS_MAP: Record<ItemStatus, { label: string; icon: string; color: string; bg: string }> = {
  available:    { label: 'В наличии',        icon: '✅', color: 'text-emerald-400', bg: 'bg-emerald-500/12' },
  limited:      { label: 'Осталось мало',    icon: '⏳', color: 'text-amber-400',   bg: 'bg-amber-500/12' },
  out_of_stock: { label: 'Нет в наличии',    icon: '🚫', color: 'text-rose-400',    bg: 'bg-rose-500/12' },
  coming_soon:  { label: 'Скоро в продаже',  icon: '🔜', color: 'text-sky-400',     bg: 'bg-sky-500/12' },
  promo:        { label: 'Акция',            icon: '🔥', color: 'text-orange-400',  bg: 'bg-orange-500/12' },
}

const RATING_STARS = [1, 2, 3, 4, 5] as const

const sortOptions: Array<{ key: string; label: string; icon: string }> = [
  { key: 'popular',  label: 'Популярные',   icon: '🔥' },
  { key: 'rating',   label: 'По рейтингу',  icon: '⭐' },
  { key: 'priceAsc', label: 'Сначала дешёвые', icon: '💰' },
  { key: 'priceDesc', label: 'Сначала дорогие', icon: '💎' },
  { key: 'new',      label: 'Новинки',      icon: '✨' },
  { key: 'reviews',  label: 'По отзывам',   icon: '💬' },
]

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STATE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const rootEl           = ref<HTMLElement | null>(null)
const scrollSentinel   = ref<HTMLElement | null>(null)
const isFullscreen     = ref(false)
const showItemModal    = ref(false)
const showFilterDrawer = ref(false)
const showSidebar      = ref(true)
const selectedItem     = ref<PublicItem | null>(null)
const currentPage      = ref(1)
const viewMode         = ref<ViewMode>('grid')
const galleryIdx       = ref(0)
const addedToCartId    = ref<number | string | null>(null)
const itemQty          = ref(1)

const filters = reactive<PublicFilter>({
  search:     '',
  categoryId: '',
  priceMin:   '',
  priceMax:   '',
  ratingMin:  0,
  branchId:   '',
  status:     '',
  sortBy:     'popular',
  sortDir:    'desc',
  isPromo:    null,
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  COMPUTED
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const totalPages = computed(() => Math.ceil(props.totalItems / props.perPage) || 1)

const hasActiveFilters = computed(() =>
  filters.categoryId !== '' || filters.priceMin !== '' ||
  filters.priceMax !== '' || filters.ratingMin > 0 ||
  filters.branchId !== '' || filters.status !== '' ||
  filters.isPromo === true
)

const filteredItems = computed(() => {
  let result = [...props.items]
  const q = filters.search.toLowerCase().trim()

  if (q) {
    result = result.filter(
      (it) =>
        it.name.toLowerCase().includes(q) ||
        (it.shortDesc && it.shortDesc.toLowerCase().includes(q)) ||
        (it.description && it.description.toLowerCase().includes(q)) ||
        it.tags.some((t) => t.toLowerCase().includes(q)) ||
        (it.branchName && it.branchName.toLowerCase().includes(q))
    )
  }
  if (filters.categoryId)  result = result.filter((it) => String(it.categoryId) === filters.categoryId)
  if (filters.priceMin)    result = result.filter((it) => it.price >= Number(filters.priceMin))
  if (filters.priceMax)    result = result.filter((it) => it.price <= Number(filters.priceMax))
  if (filters.ratingMin)   result = result.filter((it) => it.rating >= filters.ratingMin)
  if (filters.branchId)    result = result.filter((it) => String(it.branchId) === filters.branchId)
  if (filters.status)      result = result.filter((it) => it.status === filters.status)
  if (filters.isPromo)     result = result.filter((it) => it.isPromo)

  /* Sorting */
  switch (filters.sortBy) {
    case 'popular':   result.sort((a, b) => b.salesCount - a.salesCount); break
    case 'rating':    result.sort((a, b) => b.rating - a.rating); break
    case 'priceAsc':  result.sort((a, b) => a.price - b.price); break
    case 'priceDesc': result.sort((a, b) => b.price - a.price); break
    case 'new':       result.sort((a, b) => (a.isNew === b.isNew ? 0 : a.isNew ? -1 : 1)); break
    case 'reviews':   result.sort((a, b) => b.reviewsCount - a.reviewsCount); break
  }
  return result
})

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * props.perPage
  return filteredItems.value.slice(start, start + props.perPage)
})

const categoryCounts = computed(() => {
  const m: Record<string, number> = {}
  for (const it of props.items) m[String(it.categoryId)] = (m[String(it.categoryId)] || 0) + 1
  return m
})

const selectedImages = computed<string[]>(() => {
  if (!selectedItem.value) return []
  const imgs = selectedItem.value.images ?? []
  if (selectedItem.value.imageUrl && !imgs.includes(selectedItem.value.imageUrl)) {
    return [selectedItem.value.imageUrl, ...imgs]
  }
  return imgs.length > 0 ? imgs : []
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  FORMATTERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function fmtCurrency(v: number): string {
  if (v === 0) return 'Бесплатно'
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency', currency: 'RUB', maximumFractionDigits: 0,
  }).format(v)
}

function fmtNumber(v: number): string {
  return new Intl.NumberFormat('ru-RU').format(v)
}

function fmtPriceDisplay(item: PublicItem): string {
  if (item.priceType === 'free')       return 'Бесплатно'
  if (item.priceType === 'negotiable') return 'Договорная'
  if (item.priceType === 'from')       return 'от ' + fmtCurrency(item.price)
  return fmtCurrency(item.price)
}

function discountPercent(item: PublicItem): number {
  if (!item.priceOld || item.priceOld <= item.price) return 0
  return Math.round(((item.priceOld - item.price) / item.priceOld) * 100)
}

function starsText(r: number): string {
  const full  = Math.floor(r)
  const frac  = r - full
  let s = '★'.repeat(full)
  if (frac >= 0.5) s += '½'
  return s.padEnd(5, '☆').substring(0, 5)
}

function reviewWord(n: number): string {
  const mod10  = n % 10
  const mod100 = n % 100
  if (mod100 >= 11 && mod100 <= 14) return 'отзывов'
  if (mod10 === 1) return 'отзыв'
  if (mod10 >= 2 && mod10 <= 4) return 'отзыва'
  return 'отзывов'
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  ACTIONS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function openItem(item: PublicItem) {
  selectedItem.value  = item
  galleryIdx.value    = 0
  itemQty.value       = 1
  showItemModal.value = true
  emit('item-click', item)
}

function closeItemModal() {
  showItemModal.value = false
  selectedItem.value  = null
}

function toggleFullscreen() {
  if (!rootEl.value) return
  if (!isFullscreen.value) rootEl.value.requestFullscreen?.()
  else document.exitFullscreen?.()
}

function handleFullscreenChange() {
  isFullscreen.value = !!document.fullscreenElement
}

function clearAllFilters() {
  filters.search     = ''
  filters.categoryId = ''
  filters.priceMin   = ''
  filters.priceMax   = ''
  filters.ratingMin  = 0
  filters.branchId   = ''
  filters.status     = ''
  filters.isPromo    = null
  currentPage.value  = 1
  emit('filter-change', { ...filters })
}

function setCategory(catId: string) {
  filters.categoryId = filters.categoryId === catId ? '' : catId
  currentPage.value  = 1
  emit('filter-change', { ...filters })
}

function goPage(p: number) {
  if (p < 1 || p > totalPages.value) return
  currentPage.value = p
  emit('page-change', p)
  window.scrollTo(0, 0)
}

function handleCta(item: PublicItem) {
  if (!item.inStock && item.status !== 'coming_soon') return
  switch (vc.value.ctaType) {
    case 'cart':
    case 'order':
      emit('add-to-cart', item, 1)
      addedToCartId.value = item.id
      setTimeout(() => { if (addedToCartId.value === item.id) addedToCartId.value = null }, 1800)
      break
    case 'book':
    case 'subscribe':
      emit('book-service', item)
      break
    case 'contact':
      emit('book-service', item)
      break
  }
}

function handleModalCta() {
  if (!selectedItem.value) return
  const item = selectedItem.value
  if (vc.value.ctaType === 'cart' || vc.value.ctaType === 'order') {
    emit('add-to-cart', item, itemQty.value)
    addedToCartId.value = item.id
    setTimeout(() => { if (addedToCartId.value === item.id) addedToCartId.value = null }, 1800)
  } else {
    emit('book-service', item)
  }
  closeItemModal()
}

function nextImage() {
  if (selectedImages.value.length <= 1) return
  galleryIdx.value = (galleryIdx.value + 1) % selectedImages.value.length
}

function prevImage() {
  if (selectedImages.value.length <= 1) return
  galleryIdx.value = (galleryIdx.value - 1 + selectedImages.value.length) % selectedImages.value.length
}

function checkViewport() {
  if (window.innerWidth < 1024) showSidebar.value = false
  else showSidebar.value = true
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  KEYBOARD
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    if (showItemModal.value)    { closeItemModal(); return }
    if (showFilterDrawer.value) { showFilterDrawer.value = false; return }
    if (isFullscreen.value)     { toggleFullscreen(); return }
  }
  if (showItemModal.value) {
    if (e.key === 'ArrowRight') nextImage()
    if (e.key === 'ArrowLeft')  prevImage()
  }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  WATCHERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(() => filters.search, () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    currentPage.value = 1
    emit('filter-change', { ...filters })
  }, 300)
})

/* Infinite scroll */
let io: IntersectionObserver | null = null

function setupInfiniteScroll() {
  if (!scrollSentinel.value) return
  io = new IntersectionObserver(
    (entries) => {
      if (entries[0]?.isIntersecting && !props.loading && currentPage.value < totalPages.value) {
        emit('load-more')
      }
    },
    { threshold: 0.1 }
  )
  io.observe(scrollSentinel.value)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  LIFECYCLE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

onMounted(() => {
  document.addEventListener('keydown', onKeydown)
  document.addEventListener('fullscreenchange', handleFullscreenChange)
  window.addEventListener('resize', checkViewport)
  checkViewport()
  nextTick(() => setupInfiniteScroll())
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  document.removeEventListener('fullscreenchange', handleFullscreenChange)
  window.removeEventListener('resize', checkViewport)
  if (debounceTimer) clearTimeout(debounceTimer)
  io?.disconnect()
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  RIPPLE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function ripple(e: MouseEvent) {
  const target = e.currentTarget as HTMLElement
  const rect   = target.getBoundingClientRect()
  const d      = Math.max(rect.width, rect.height) * 2
  const el     = document.createElement('span')
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-pc_0.6s_ease-out]'
  el.style.cssText = [
    `inline-size:${d}px`,
    `block-size:${d}px`,
    `inset-inline-start:${e.clientX - rect.left - d / 2}px`,
    `inset-block-start:${e.clientY - rect.top - d / 2}px`,
  ].join(';')
  target.appendChild(el)
  setTimeout(() => el.remove(), 650)
}
</script>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     TEMPLATE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<template>
  <div
    ref="rootEl"
    :class="[
      'relative flex flex-col bg-(--t-bg) text-(--t-text)',
      isFullscreen ? 'fixed inset-0 z-50 overflow-auto' : 'min-h-screen',
    ]"
  >
    <!-- ══════════════════════════════════════════════
         HERO / SEARCH BAR
    ══════════════════════════════════════════════ -->
    <header class="relative overflow-hidden bg-linear-to-br from-zinc-900 via-zinc-800 to-zinc-900
                   border-b border-(--t-border)/40">
      <!-- Decorative glow -->
      <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -inset-block-start-24 inset-inline-start-1/4 w-96 h-96
                    bg-(--t-primary)/8 rounded-full blur-3xl" />
        <div class="absolute -inset-block-end-20 inset-inline-end-1/4 w-80 h-80
                    bg-violet-500/6 rounded-full blur-3xl" />
      </div>

      <div class="relative z-10 px-4 sm:px-6 py-8 sm:py-12 max-w-6xl mx-auto text-center">
        <span class="text-3xl sm:text-4xl mb-3 block">{{ vc.icon }}</span>
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white leading-tight">
          {{ vc.heroTitle }}
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 mt-2 max-w-lg mx-auto">
          {{ vc.heroSubtitle }}
        </p>

        <!-- Big search -->
        <div class="relative mt-6 sm:mt-8 max-w-xl mx-auto">
          <svg class="absolute inset-inline-start-4 inset-block-start-1/2 -translate-y-1/2
                      w-5 h-5 text-zinc-400 pointer-events-none"
               fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
          </svg>
          <input
            v-model="filters.search"
            type="text"
            :placeholder="vc.searchPlaceholder"
            class="inline-size-full py-3.5 ps-12 pe-4 text-sm sm:text-base rounded-2xl
                   bg-white/8 border border-white/12 text-white
                   placeholder:text-zinc-500
                   focus:border-(--t-primary)/50 focus:ring-2 focus:ring-(--t-primary)/20
                   outline-none transition-all backdrop-blur-sm"
          />
          <button v-if="filters.search"
                  class="absolute inset-inline-end-3 inset-block-start-1/2 -translate-y-1/2
                         w-7 h-7 rounded-full flex items-center justify-center
                         text-zinc-400 hover:text-white hover:bg-white/10 transition-all"
                  @click="filters.search = ''">
            ✕
          </button>
        </div>

        <!-- Promo banner -->
        <div v-if="vc.promoBanner"
             class="mt-4 inline-flex items-center gap-2 px-4 py-1.5 rounded-full
                    bg-orange-500/12 border border-orange-500/20 text-orange-300 text-xs sm:text-sm font-medium">
          🔥 {{ vc.promoBanner }}
        </div>
      </div>
    </header>

    <!-- ══════════════════════════════════════════════
         TOOLBAR (sort + view + filter toggle)
    ══════════════════════════════════════════════ -->
    <div class="sticky inset-block-start-0 z-30 bg-(--t-surface)/80 backdrop-blur-xl
                border-b border-(--t-border)/40">
      <div class="flex items-center gap-2 px-4 sm:px-6 py-2.5 max-w-6xl mx-auto">

        <!-- Category chips (horizontal scroll) -->
        <div class="flex-1 flex items-center gap-1.5 overflow-x-auto no-scrollbar min-w-0">
          <button
            :class="[
              'relative overflow-hidden shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-all',
              'whitespace-nowrap',
              filters.categoryId === ''
                ? 'bg-(--t-primary) text-white shadow-sm shadow-(--t-primary)/30'
                : 'bg-(--t-surface) text-(--t-text-2) border border-(--t-border)/50 hover:bg-(--t-card-hover)',
            ]"
            @click="setCategory('')" @mousedown="ripple"
          >
            Все
          </button>
          <button
            v-for="cat in props.categories" :key="cat.id"
            :class="[
              'relative overflow-hidden shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-all',
              'whitespace-nowrap',
              filters.categoryId === String(cat.id)
                ? 'bg-(--t-primary) text-white shadow-sm shadow-(--t-primary)/30'
                : 'bg-(--t-surface) text-(--t-text-2) border border-(--t-border)/50 hover:bg-(--t-card-hover)',
            ]"
            @click="setCategory(String(cat.id))" @mousedown="ripple"
          >
            {{ cat.icon }} {{ cat.name }}
          </button>
        </div>

        <!-- Sort -->
        <select
          v-model="filters.sortBy"
          class="hidden sm:block shrink-0 text-xs py-1.5 px-2.5 rounded-lg bg-(--t-bg)/60
                 border border-(--t-border)/50 text-(--t-text-2)
                 focus:outline-none focus:border-(--t-primary)/50"
          @change="emit('sort-change', filters.sortBy, filters.sortDir)"
        >
          <option v-for="opt in sortOptions" :key="opt.key" :value="opt.key">
            {{ opt.icon }} {{ opt.label }}
          </option>
        </select>

        <!-- View toggle (desktop) -->
        <div class="hidden sm:flex items-center rounded-lg border border-(--t-border)/50 overflow-hidden">
          <button
            :class="[
              'w-8 h-8 flex items-center justify-center text-xs transition-all',
              viewMode === 'grid' ? 'bg-(--t-primary)/12 text-(--t-primary)' : 'text-(--t-text-3) hover:text-(--t-text)',
            ]"
            @click="viewMode = 'grid'" title="Сетка"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" />
            </svg>
          </button>
          <button
            :class="[
              'w-8 h-8 flex items-center justify-center text-xs transition-all',
              viewMode === 'list' ? 'bg-(--t-primary)/12 text-(--t-primary)' : 'text-(--t-text-3) hover:text-(--t-text)',
            ]"
            @click="viewMode = 'list'" title="Список"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
            </svg>
          </button>
        </div>

        <!-- Filter drawer toggle (mobile) -->
        <button
          class="lg:hidden relative overflow-hidden shrink-0 w-9 h-9 rounded-lg
                 border border-(--t-border)/50 bg-(--t-surface)
                 flex items-center justify-center text-(--t-text-2)
                 hover:bg-(--t-card-hover) active:scale-95 transition-all"
          @click="showFilterDrawer = true" @mousedown="ripple"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
          </svg>
          <span v-if="hasActiveFilters"
                class="absolute inset-block-start-0 inset-inline-end-0 w-2 h-2
                       bg-(--t-primary) rounded-full ring-2 ring-(--t-surface)" />
        </button>

        <!-- Clear -->
        <button
          v-if="hasActiveFilters"
          class="shrink-0 text-xs text-(--t-primary) hover:underline transition-colors"
          @click="clearAllFilters"
        >
          Сбросить
        </button>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════
         MAIN: SIDEBAR + CONTENT
    ══════════════════════════════════════════════ -->
    <div class="flex-1 flex gap-5 px-4 sm:px-6 py-5 max-w-6xl mx-auto inline-size-full">

      <!-- ═══ SIDEBAR ═══ -->
      <Transition name="sidebar-pc">
        <aside v-if="showSidebar"
               class="hidden lg:flex shrink-0 flex-col gap-4 w-56">

          <!-- Categories -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-xs font-bold text-(--t-text) mb-3">Категории</h3>
            <button
              :class="[
                'relative overflow-hidden inline-size-full text-start px-3 py-2 rounded-xl text-xs transition-all mb-1 flex items-center gap-2',
                filters.categoryId === '' ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
              ]"
              @click="setCategory('')" @mousedown="ripple"
            >
              📦 Все {{ vc.itemLabelPlural.toLowerCase() }}
              <span class="ms-auto text-(--t-text-3) text-[10px]">{{ props.totalItems }}</span>
            </button>

            <button
              v-for="cat in props.categories" :key="cat.id"
              :class="[
                'relative overflow-hidden inline-size-full text-start px-3 py-2 rounded-xl text-xs transition-all mb-0.5 flex items-center gap-2',
                filters.categoryId === String(cat.id) ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
              ]"
              @click="setCategory(String(cat.id))" @mousedown="ripple"
            >
              {{ cat.icon }} {{ cat.name }}
              <span class="ms-auto text-(--t-text-3) text-[10px]">{{ cat.count }}</span>
            </button>
          </div>

          <!-- Price range -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-xs font-bold text-(--t-text) mb-3">{{ vc.priceLabel }}, ₽</h3>
            <div class="flex items-center gap-2">
              <input v-model="filters.priceMin" type="number" placeholder="от"
                     class="inline-size-full py-1.5 px-2 text-xs rounded-lg bg-(--t-bg)/60
                            border border-(--t-border)/50 text-(--t-text) placeholder:text-(--t-text-3)
                            focus:border-(--t-primary)/50 focus:outline-none"
                     @change="currentPage = 1; emit('filter-change', { ...filters })" />
              <span class="text-(--t-text-3) text-xs">—</span>
              <input v-model="filters.priceMax" type="number" placeholder="до"
                     class="inline-size-full py-1.5 px-2 text-xs rounded-lg bg-(--t-bg)/60
                            border border-(--t-border)/50 text-(--t-text) placeholder:text-(--t-text-3)
                            focus:border-(--t-primary)/50 focus:outline-none"
                     @change="currentPage = 1; emit('filter-change', { ...filters })" />
            </div>
          </div>

          <!-- Rating -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-xs font-bold text-(--t-text) mb-3">Рейтинг</h3>
            <div class="flex flex-col gap-1">
              <button
                v-for="r in RATING_STARS" :key="r"
                :class="[
                  'relative overflow-hidden flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs transition-all',
                  filters.ratingMin === r ? 'bg-amber-500/12 text-amber-400 font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                ]"
                @click="filters.ratingMin = filters.ratingMin === r ? 0 : r; currentPage = 1; emit('filter-change', { ...filters })"
                @mousedown="ripple"
              >
                <span class="text-amber-400">{{ '★'.repeat(r) }}{{ '☆'.repeat(5 - r) }}</span>
                <span>от {{ r }}</span>
              </button>
            </div>
          </div>

          <!-- Branch filter -->
          <div v-if="props.branches.length > 0"
               class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-xs font-bold text-(--t-text) mb-3">Филиал</h3>
            <button
              :class="[
                'relative overflow-hidden inline-size-full text-start px-3 py-2 rounded-lg text-xs transition-all mb-0.5',
                filters.branchId === '' ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
              ]"
              @click="filters.branchId = ''; emit('filter-change', { ...filters })"
              @mousedown="ripple"
            >
              Все филиалы
            </button>
            <button
              v-for="br in props.branches" :key="br.id"
              :class="[
                'relative overflow-hidden inline-size-full text-start px-3 py-2 rounded-lg text-xs transition-all mb-0.5',
                filters.branchId === String(br.id) ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
              ]"
              @click="filters.branchId = filters.branchId === String(br.id) ? '' : String(br.id); emit('filter-change', { ...filters })"
              @mousedown="ripple"
            >
              📍 {{ br.name }}
              <span v-if="br.distance != null" class="text-[10px] text-(--t-text-3) ms-1">{{ br.distance }} км</span>
            </button>
          </div>

          <!-- Promo toggle -->
          <button
            :class="[
              'relative overflow-hidden rounded-2xl border p-3 text-xs font-medium transition-all text-start',
              filters.isPromo === true
                ? 'border-orange-500/30 bg-orange-500/10 text-orange-400'
                : 'border-(--t-border)/40 bg-(--t-surface)/60 text-(--t-text-2) hover:bg-(--t-card-hover)',
            ]"
            @click="filters.isPromo = filters.isPromo === true ? null : true; currentPage = 1; emit('filter-change', { ...filters })"
            @mousedown="ripple"
          >
            🔥 Только акции и скидки
          </button>
        </aside>
      </Transition>

      <!-- ═══ CONTENT ═══ -->
      <div class="flex-1 flex flex-col gap-4 min-w-0">

        <!-- Results count -->
        <div class="flex items-center justify-between">
          <p class="text-xs text-(--t-text-3)">
            <template v-if="!props.loading">
              Найдено: <span class="font-semibold text-(--t-text)">{{ fmtNumber(filteredItems.length) }}</span>
              {{ vc.itemLabelPlural.toLowerCase() }}
            </template>
            <template v-else>Загрузка…</template>
          </p>

          <!-- Mobile sort -->
          <select
            v-model="filters.sortBy"
            class="sm:hidden text-[10px] py-1 px-2 rounded-lg bg-(--t-bg)/60
                   border border-(--t-border)/50 text-(--t-text-2) focus:outline-none"
            @change="emit('sort-change', filters.sortBy, filters.sortDir)"
          >
            <option v-for="opt in sortOptions" :key="opt.key" :value="opt.key">
              {{ opt.label }}
            </option>
          </select>
        </div>

        <!-- Loading skeletons -->
        <div v-if="props.loading && props.items.length === 0"
             :class="viewMode === 'grid'
               ? 'grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3'
               : 'flex flex-col gap-2'">
          <div v-for="n in 8" :key="n"
               :class="[
                 'animate-pulse rounded-2xl bg-(--t-surface)/60',
                 viewMode === 'grid' ? 'aspect-3/4' : 'h-24',
               ]" />
        </div>

        <!-- Empty state -->
        <div v-else-if="filteredItems.length === 0 && !props.loading"
             class="flex flex-col items-center justify-center py-20 text-center">
          <span class="text-5xl mb-4">{{ vc.icon }}</span>
          <p class="text-sm font-semibold text-(--t-text-2)">
            {{ filters.search || hasActiveFilters ? 'Ничего не найдено' : `${vc.itemLabelPlural} скоро появятся` }}
          </p>
          <p class="text-xs text-(--t-text-3) mt-1.5 max-w-xs">
            {{ filters.search || hasActiveFilters
              ? 'Попробуйте изменить фильтры или поисковый запрос'
              : 'Мы добавляем новые предложения каждый день — загляните позже!'
            }}
          </p>
          <button v-if="hasActiveFilters"
                  class="mt-4 text-xs text-(--t-primary) hover:underline"
                  @click="clearAllFilters">
            Сбросить все фильтры
          </button>
        </div>

        <!-- ═══ GRID VIEW ═══ -->
        <div v-else-if="viewMode === 'grid'"
             class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">

          <div v-for="item in paginatedItems" :key="item.id"
               :class="[
                 'group relative rounded-2xl border overflow-hidden transition-all',
                 'cursor-pointer hover:shadow-xl hover:shadow-black/12',
                 'active:scale-[0.98]',
                 'border-(--t-border)/40 hover:border-(--t-border)/80',
                 !item.inStock && item.status !== 'coming_soon' ? 'grayscale-40 opacity-75' : '',
               ]"
               @click="openItem(item)">

            <!-- Image -->
            <div class="relative aspect-square bg-(--t-bg)/80 overflow-hidden">
              <img v-if="item.imageUrl"
                   :src="item.imageUrl" :alt="item.name"
                   class="inline-size-full block-size-full object-cover
                          group-hover:scale-105 transition-transform duration-500" />
              <div v-else
                   class="inline-size-full block-size-full flex items-center justify-center text-4xl
                          bg-linear-to-br from-zinc-800 to-zinc-900">
                {{ vc.icon }}
              </div>

              <!-- Overlay badges (top-left) -->
              <div class="absolute inset-block-start-2 inset-inline-start-2 flex flex-col gap-1">
                <span v-if="item.isPromo"
                      class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-lg
                             bg-orange-500/90 text-white text-[10px] font-bold shadow-md">
                  🔥 {{ item.promoLabel ?? 'Акция' }}
                </span>
                <span v-if="discountPercent(item) > 0"
                      class="inline-flex items-center px-2 py-0.5 rounded-lg
                             bg-rose-500/90 text-white text-[10px] font-bold shadow-md">
                  −{{ discountPercent(item) }}%
                </span>
                <span v-if="item.isNew"
                      class="inline-flex items-center px-2 py-0.5 rounded-lg
                             bg-sky-500/90 text-white text-[10px] font-bold shadow-md">
                  ✨ Новинка
                </span>
                <span v-if="item.isFeatured && !item.isNew && !item.isPromo"
                      class="inline-flex items-center px-2 py-0.5 rounded-lg
                             bg-violet-500/90 text-white text-[10px] font-bold shadow-md">
                  ⭐ Хит
                </span>
              </div>

              <!-- Wishlist heart (top-right) -->
              <button
                class="absolute inset-block-start-2 inset-inline-end-2 w-8 h-8 rounded-full
                       bg-black/30 backdrop-blur-sm flex items-center justify-center
                       transition-all active:scale-90 hover:bg-black/50"
                :class="props.wishlistIds.has(item.id) ? 'text-rose-400' : 'text-white/70 hover:text-white'"
                @click.stop="emit('toggle-wishlist', item)"
                :title="props.wishlistIds.has(item.id) ? 'Убрать из избранного' : 'В избранное'"
              >
                <svg class="w-4 h-4" :fill="props.wishlistIds.has(item.id) ? 'currentColor' : 'none'"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
              </button>

              <!-- Out-of-stock overlay -->
              <div v-if="!item.inStock && item.status !== 'coming_soon'"
                   class="absolute inset-0 bg-black/30 flex items-center justify-center">
                <span class="px-3 py-1 rounded-lg bg-black/60 text-white text-xs font-semibold">
                  Нет в наличии
                </span>
              </div>

              <!-- AR badge -->
              <button v-if="item.arUrl"
                      class="absolute inset-block-end-2 inset-inline-start-2 px-2 py-1 rounded-lg
                             bg-black/50 backdrop-blur-sm text-white/80 text-[10px] font-medium
                             hover:text-white transition-all flex items-center gap-1"
                      @click.stop="emit('ar-preview', item)">
                🔮 AR-примерка
              </button>

              <!-- Delivery info badge -->
              <span v-if="item.deliveryTime"
                    class="absolute inset-block-end-2 inset-inline-end-2 px-2 py-0.5 rounded-lg
                           bg-black/40 backdrop-blur-sm text-white/80 text-[10px]">
                🚚 {{ item.deliveryTime }}
              </span>
            </div>

            <!-- Card body -->
            <div class="p-3 bg-(--t-surface)/60 backdrop-blur-sm flex flex-col gap-1.5">
              <!-- Name -->
              <p class="text-xs sm:text-sm font-semibold text-(--t-text) truncate leading-snug">
                {{ item.name }}
              </p>

              <!-- Short desc -->
              <p v-if="item.shortDesc"
                 class="text-[10px] text-(--t-text-3) line-clamp-2 leading-relaxed">
                {{ item.shortDesc }}
              </p>

              <!-- Rating -->
              <div class="flex items-center gap-1.5">
                <span class="text-amber-400 text-[11px]">{{ starsText(item.rating) }}</span>
                <span class="text-[10px] font-medium text-(--t-text-2)">{{ item.rating.toFixed(1) }}</span>
                <span class="text-[10px] text-(--t-text-3)">({{ item.reviewsCount }})</span>
              </div>

              <!-- Price row -->
              <div class="flex items-baseline gap-1.5 mt-0.5">
                <template v-if="item.priceType === 'free'">
                  <span class="text-sm font-bold text-emerald-400">Бесплатно</span>
                </template>
                <template v-else-if="item.priceType === 'negotiable'">
                  <span class="text-xs text-(--t-text-2)">Договорная</span>
                </template>
                <template v-else>
                  <span class="text-sm font-extrabold text-(--t-text)">{{ fmtPriceDisplay(item) }}</span>
                  <span v-if="item.priceOld && item.priceOld > item.price"
                        class="text-[10px] text-(--t-text-3) line-through">{{ fmtCurrency(item.priceOld) }}</span>
                  <span v-if="item.unit" class="text-[10px] text-(--t-text-3)">/{{ item.unit }}</span>
                </template>
              </div>

              <!-- Branch -->
              <p v-if="item.branchName" class="text-[10px] text-(--t-text-3) truncate">
                📍 {{ item.branchName }}
              </p>

              <!-- CTA button -->
              <button
                :class="[
                  'relative overflow-hidden mt-1.5 inline-size-full py-2.5 rounded-xl text-xs font-bold',
                  'transition-all active:scale-[0.96]',
                  !item.inStock && item.status !== 'coming_soon'
                    ? 'bg-zinc-700/40 text-zinc-500 cursor-not-allowed'
                    : addedToCartId === item.id
                      ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'
                      : 'bg-(--t-primary) text-white hover:brightness-110 shadow-sm shadow-(--t-primary)/20',
                ]"
                :disabled="!item.inStock && item.status !== 'coming_soon'"
                @click.stop="handleCta(item)" @mousedown="item.inStock ? ripple($event) : undefined"
              >
                <template v-if="addedToCartId === item.id">
                  ✅ Добавлено!
                </template>
                <template v-else-if="!item.inStock && item.status !== 'coming_soon'">
                  Нет в наличии
                </template>
                <template v-else-if="item.status === 'coming_soon'">
                  🔔 Уведомить о поступлении
                </template>
                <template v-else>
                  {{ vc.ctaIcon }} {{ vc.ctaLabel }}
                </template>
              </button>
            </div>
          </div>
        </div>

        <!-- ═══ LIST VIEW ═══ -->
        <div v-else class="flex flex-col gap-2.5">
          <div v-for="item in paginatedItems" :key="item.id"
               :class="[
                 'group flex items-center gap-3 sm:gap-4 rounded-2xl border px-3 py-3',
                 'transition-all cursor-pointer hover:shadow-lg hover:shadow-black/8',
                 'active:scale-[0.995]',
                 'border-(--t-border)/40 bg-(--t-surface)/60 hover:border-(--t-border)/80',
                 !item.inStock && item.status !== 'coming_soon' ? 'grayscale-40 opacity-75' : '',
               ]"
               @click="openItem(item)">

            <!-- Thumbnail -->
            <div class="shrink-0 w-20 h-20 sm:w-24 sm:h-24 rounded-xl overflow-hidden bg-(--t-bg)/80 relative">
              <img v-if="item.imageUrl" :src="item.imageUrl" :alt="item.name"
                   class="inline-size-full block-size-full object-cover" />
              <span v-else class="inline-size-full block-size-full flex items-center justify-center text-2xl">
                {{ vc.icon }}
              </span>
              <span v-if="item.isPromo"
                    class="absolute inset-block-start-1 inset-inline-start-1 px-1.5 py-0.5
                           rounded-md bg-orange-500/90 text-white text-[9px] font-bold">🔥</span>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0 flex flex-col gap-1">
              <div class="flex items-start gap-2">
                <p class="text-xs sm:text-sm font-semibold text-(--t-text) truncate flex-1">{{ item.name }}</p>
                <!-- Heart -->
                <button
                  class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center
                         transition-all active:scale-90"
                  :class="props.wishlistIds.has(item.id) ? 'text-rose-400' : 'text-(--t-text-3) hover:text-rose-400'"
                  @click.stop="emit('toggle-wishlist', item)"
                >
                  <svg class="w-3.5 h-3.5" :fill="props.wishlistIds.has(item.id) ? 'currentColor' : 'none'"
                       viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                  </svg>
                </button>
              </div>

              <!-- Rating -->
              <div class="flex items-center gap-1.5">
                <span class="text-amber-400 text-[10px]">{{ starsText(item.rating) }}</span>
                <span class="text-[10px] text-(--t-text-2)">{{ item.rating.toFixed(1) }}</span>
                <span class="text-[10px] text-(--t-text-3)">({{ item.reviewsCount }} {{ reviewWord(item.reviewsCount) }})</span>
              </div>

              <p v-if="item.shortDesc" class="hidden sm:block text-[10px] text-(--t-text-3) truncate">{{ item.shortDesc }}</p>
              <p v-if="item.branchName" class="text-[10px] text-(--t-text-3)">📍 {{ item.branchName }}</p>
            </div>

            <!-- Price + CTA -->
            <div class="shrink-0 flex flex-col items-end gap-1.5">
              <div class="text-end">
                <p class="text-sm font-extrabold text-(--t-text)">{{ fmtPriceDisplay(item) }}</p>
                <p v-if="item.priceOld && item.priceOld > item.price"
                   class="text-[10px] text-(--t-text-3) line-through">{{ fmtCurrency(item.priceOld) }}</p>
              </div>
              <button
                :class="[
                  'relative overflow-hidden px-3 py-2 rounded-xl text-[10px] sm:text-xs font-bold whitespace-nowrap',
                  'transition-all active:scale-95',
                  !item.inStock && item.status !== 'coming_soon'
                    ? 'bg-zinc-700/40 text-zinc-500 cursor-not-allowed'
                    : addedToCartId === item.id
                      ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'
                      : 'bg-(--t-primary) text-white hover:brightness-110 shadow-sm',
                ]"
                :disabled="!item.inStock && item.status !== 'coming_soon'"
                @click.stop="handleCta(item)" @mousedown="item.inStock ? ripple($event) : undefined"
              >
                {{ addedToCartId === item.id ? '✅' : vc.ctaIcon }} {{ addedToCartId === item.id ? 'Добавлено' : vc.ctaLabel }}
              </button>
            </div>
          </div>
        </div>

        <!-- ═══ PAGINATION ═══ -->
        <div v-if="totalPages > 1 && !props.loading"
             class="flex items-center justify-center gap-1.5 mt-3">
          <button :disabled="currentPage <= 1"
                  class="w-9 h-9 rounded-xl border border-(--t-border)/50 flex items-center justify-center
                         text-(--t-text-3) hover:bg-(--t-card-hover) disabled:opacity-30
                         disabled:cursor-not-allowed transition-all"
                  @click="goPage(currentPage - 1)">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
          </button>
          <template v-for="p in totalPages" :key="p">
            <button v-if="p <= 3 || p > totalPages - 2 || Math.abs(p - currentPage) <= 1"
                    :class="[
                      'w-9 h-9 rounded-xl text-xs font-medium transition-all',
                      p === currentPage
                        ? 'bg-(--t-primary) text-white shadow-sm shadow-(--t-primary)/30'
                        : 'text-(--t-text-3) hover:bg-(--t-card-hover)',
                    ]"
                    @click="goPage(p)">
              {{ p }}
            </button>
            <span v-else-if="p === 4 || p === totalPages - 2"
                  class="w-9 h-9 flex items-center justify-center text-(--t-text-3) text-xs">…</span>
          </template>
          <button :disabled="currentPage >= totalPages"
                  class="w-9 h-9 rounded-xl border border-(--t-border)/50 flex items-center justify-center
                         text-(--t-text-3) hover:bg-(--t-card-hover) disabled:opacity-30
                         disabled:cursor-not-allowed transition-all"
                  @click="goPage(currentPage + 1)">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
          </button>
        </div>

        <!-- Infinite scroll sentinel -->
        <div ref="scrollSentinel" class="h-1" />
        <div v-if="props.loading && props.items.length > 0"
             class="flex justify-center py-6">
          <div class="w-6 h-6 border-2 border-(--t-primary)/30 border-t-(--t-primary)
                      rounded-full animate-spin" />
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════
         FLOATING CART BADGE
    ══════════════════════════════════════════════ -->
    <Transition name="cart-pc">
      <button
        v-if="props.cart.count > 0"
        class="fixed inset-block-end-6 inset-inline-end-6 z-40
               w-14 h-14 rounded-2xl bg-(--t-primary) text-white
               flex items-center justify-center shadow-xl shadow-(--t-primary)/30
               hover:brightness-110 active:scale-90 transition-all"
        @click="emit('open-cart')"
      >
        <span class="text-lg">🛒</span>
        <span class="absolute -inset-block-start-1.5 -inset-inline-end-1.5
                     w-6 h-6 rounded-full bg-rose-500 text-white text-[10px]
                     font-bold flex items-center justify-center ring-2 ring-(--t-bg)
                     animate-bounce">
          {{ props.cart.count > 99 ? '99+' : props.cart.count }}
        </span>
      </button>
    </Transition>

    <!-- ══════════════════════════════════════════════
         ITEM DETAIL MODAL
    ══════════════════════════════════════════════ -->
    <Transition name="modal-pc">
      <div v-if="showItemModal && selectedItem"
           class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
           @click.self="closeItemModal">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeItemModal" />

        <div class="relative z-10 inline-size-full sm:max-w-lg border-t sm:border
                    border-(--t-border)/60 bg-(--t-surface)/95 backdrop-blur-xl shadow-2xl
                    overflow-hidden max-h-[95vh] sm:max-h-[90vh] flex flex-col
                    rounded-t-2xl sm:rounded-2xl">

          <!-- Gallery -->
          <div class="relative shrink-0 aspect-video sm:aspect-4/3 bg-(--t-bg)/80 overflow-hidden">
            <template v-if="selectedImages.length > 0">
              <img :src="selectedImages[galleryIdx]" :alt="selectedItem.name"
                   class="inline-size-full block-size-full object-cover transition-opacity duration-300" />
              <!-- Arrows -->
              <button v-if="selectedImages.length > 1"
                      class="absolute inset-inline-start-2 inset-block-start-1/2 -translate-y-1/2
                             w-8 h-8 rounded-full bg-black/40 backdrop-blur-sm flex items-center
                             justify-center text-white/80 hover:text-white transition-all"
                      @click.stop="prevImage">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
              </button>
              <button v-if="selectedImages.length > 1"
                      class="absolute inset-inline-end-2 inset-block-start-1/2 -translate-y-1/2
                             w-8 h-8 rounded-full bg-black/40 backdrop-blur-sm flex items-center
                             justify-center text-white/80 hover:text-white transition-all"
                      @click.stop="nextImage">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
              </button>
              <!-- Dots -->
              <div v-if="selectedImages.length > 1"
                   class="absolute inset-block-end-3 inset-inline-start-1/2 -translate-x-1/2
                          flex items-center gap-1.5">
                <button v-for="(_, idx) in selectedImages" :key="idx"
                        :class="[
                          'w-2 h-2 rounded-full transition-all',
                          idx === galleryIdx ? 'bg-white w-4' : 'bg-white/40 hover:bg-white/60',
                        ]"
                        @click.stop="galleryIdx = idx" />
              </div>
            </template>
            <div v-else class="inline-size-full block-size-full flex items-center justify-center text-5xl
                             bg-linear-to-br from-zinc-800 to-zinc-900">
              {{ vc.icon }}
            </div>

            <!-- Close -->
            <button class="absolute inset-block-start-3 inset-inline-end-3 w-9 h-9 rounded-full
                           bg-black/40 backdrop-blur-sm flex items-center justify-center
                           text-white/80 hover:text-white transition-colors active:scale-90"
                    @click="closeItemModal">
              ✕
            </button>

            <!-- Badges -->
            <div class="absolute inset-block-start-3 inset-inline-start-3 flex flex-col gap-1">
              <span v-if="selectedItem.isPromo"
                    class="px-2 py-0.5 rounded-lg bg-orange-500/90 text-white text-[10px] font-bold shadow-md">
                🔥 {{ selectedItem.promoLabel ?? 'Акция' }}
              </span>
              <span v-if="discountPercent(selectedItem) > 0"
                    class="px-2 py-0.5 rounded-lg bg-rose-500/90 text-white text-[10px] font-bold shadow-md">
                −{{ discountPercent(selectedItem) }}%
              </span>
            </div>

            <!-- Share + Wishlist -->
            <div class="absolute inset-block-end-3 inset-inline-end-3 flex items-center gap-2">
              <button
                class="w-8 h-8 rounded-full bg-black/40 backdrop-blur-sm flex items-center
                       justify-center text-white/70 hover:text-white transition-all active:scale-90"
                @click.stop="emit('share-item', selectedItem!)" title="Поделиться"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round"
                        d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                </svg>
              </button>
              <button
                class="w-8 h-8 rounded-full bg-black/40 backdrop-blur-sm flex items-center
                       justify-center transition-all active:scale-90"
                :class="props.wishlistIds.has(selectedItem.id) ? 'text-rose-400' : 'text-white/70 hover:text-rose-400'"
                @click.stop="emit('toggle-wishlist', selectedItem!)"
              >
                <svg class="w-4 h-4" :fill="props.wishlistIds.has(selectedItem.id) ? 'currentColor' : 'none'"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Scrollable body -->
          <div class="flex-1 overflow-y-auto px-5 py-4 flex flex-col gap-3">
            <!-- Name -->
            <div>
              <h3 class="text-base sm:text-lg font-extrabold text-(--t-text) leading-snug">
                {{ selectedItem.name }}
              </h3>
              <p v-if="selectedItem.categoryName" class="text-xs text-(--t-text-3) mt-0.5">
                {{ selectedItem.categoryName }}
                <template v-if="selectedItem.branchName"> · 📍 {{ selectedItem.branchName }}</template>
              </p>
            </div>

            <!-- Rating bar -->
            <div class="flex items-center gap-2">
              <div class="flex items-center gap-0.5">
                <span v-for="n in 5" :key="n" class="text-base sm:text-lg">
                  {{ n <= Math.round(selectedItem.rating) ? '⭐' : '☆' }}
                </span>
              </div>
              <span class="text-sm font-bold text-(--t-text)">{{ selectedItem.rating.toFixed(1) }}</span>
              <button class="text-xs text-(--t-primary) hover:underline"
                      @click="emit('view-reviews', selectedItem!)">
                {{ selectedItem.reviewsCount }} {{ reviewWord(selectedItem.reviewsCount) }}
              </button>
            </div>

            <!-- Price block -->
            <div class="rounded-xl bg-linear-to-r from-(--t-primary)/8 to-transparent
                        border border-(--t-primary)/15 p-4">
              <div class="flex items-baseline gap-2">
                <span class="text-xl sm:text-2xl font-extrabold text-(--t-text)">
                  {{ fmtPriceDisplay(selectedItem) }}
                </span>
                <span v-if="selectedItem.priceOld && selectedItem.priceOld > selectedItem.price"
                      class="text-sm text-(--t-text-3) line-through">{{ fmtCurrency(selectedItem.priceOld) }}</span>
                <span v-if="selectedItem.unit" class="text-sm text-(--t-text-3)">/{{ selectedItem.unit }}</span>
              </div>
              <p v-if="discountPercent(selectedItem) > 0"
                 class="text-xs text-emerald-400 font-medium mt-1">
                💰 Вы экономите {{ fmtCurrency(selectedItem.priceOld! - selectedItem.price) }}
              </p>
            </div>

            <!-- Delivery info -->
            <div v-if="selectedItem.deliveryTime"
                 class="flex items-center gap-2 text-xs text-(--t-text-2)">
              <span class="shrink-0">🚚</span>
              <span>Доставка: {{ selectedItem.deliveryTime }}</span>
            </div>

            <!-- AR Preview -->
            <button v-if="selectedItem.arUrl"
                    class="relative overflow-hidden inline-flex items-center gap-2 px-4 py-2.5
                           rounded-xl bg-violet-500/10 border border-violet-500/20 text-violet-400
                           text-xs font-medium hover:bg-violet-500/20 active:scale-[0.97] transition-all"
                    @click="emit('ar-preview', selectedItem!)" @mousedown="ripple">
              🔮 Попробуйте AR-примерку
            </button>

            <!-- Description -->
            <div v-if="selectedItem.description">
              <p class="text-xs font-semibold text-(--t-text) mb-1">Описание</p>
              <p class="text-xs text-(--t-text-2) leading-relaxed whitespace-pre-line">
                {{ selectedItem.description }}
              </p>
            </div>

            <!-- Tags -->
            <div v-if="selectedItem.tags.length > 0" class="flex flex-wrap gap-1.5">
              <span v-for="tag in selectedItem.tags" :key="tag"
                    class="text-[10px] px-2 py-0.5 rounded-full bg-(--t-bg)/60
                           text-(--t-text-3) border border-(--t-border)/30">
                {{ tag }}
              </span>
            </div>
          </div>

          <!-- Footer: qty + CTA -->
          <div class="shrink-0 px-5 pb-5 pt-3 border-t border-(--t-border)/30
                      flex items-center gap-3">

            <!-- Qty selector (for cart-type verticals) -->
            <div v-if="vc.ctaType === 'cart' || vc.ctaType === 'order'"
                 class="flex items-center rounded-xl border border-(--t-border)/50 overflow-hidden shrink-0">
              <button class="w-9 h-10 flex items-center justify-center text-(--t-text-2)
                             hover:bg-(--t-card-hover) transition-colors text-sm"
                      @click="if (itemQty > 1) itemQty--">−</button>
              <span class="w-10 text-center text-sm font-bold text-(--t-text)">{{ itemQty }}</span>
              <button class="w-9 h-10 flex items-center justify-center text-(--t-text-2)
                             hover:bg-(--t-card-hover) transition-colors text-sm"
                      @click="if (itemQty < 99) itemQty++">+</button>
            </div>

            <!-- Main CTA -->
            <button
              :class="[
                'relative overflow-hidden flex-1 py-3 rounded-xl text-sm font-bold',
                'transition-all active:scale-[0.97]',
                !selectedItem.inStock && selectedItem.status !== 'coming_soon'
                  ? 'bg-zinc-700/40 text-zinc-500 cursor-not-allowed'
                  : 'bg-(--t-primary) text-white hover:brightness-110 shadow-lg shadow-(--t-primary)/25',
              ]"
              :disabled="!selectedItem.inStock && selectedItem.status !== 'coming_soon'"
              @click="handleModalCta" @mousedown="selectedItem.inStock ? ripple($event) : undefined"
            >
              <template v-if="!selectedItem.inStock && selectedItem.status !== 'coming_soon'">
                Нет в наличии
              </template>
              <template v-else>
                {{ vc.ctaIcon }} {{ vc.ctaLabel }}
                <template v-if="(vc.ctaType === 'cart' || vc.ctaType === 'order') && selectedItem.priceType !== 'free'">
                   · {{ fmtCurrency(selectedItem.price * itemQty) }}
                </template>
              </template>
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════════════
         FILTER DRAWER (mobile)
    ══════════════════════════════════════════════ -->
    <Transition name="drawer-pc">
      <div v-if="showFilterDrawer" class="fixed inset-0 z-50 flex justify-end"
           @click.self="showFilterDrawer = false">
        <div class="absolute inset-0 bg-black/40" @click="showFilterDrawer = false" />
        <div class="relative z-10 inline-size-72 max-w-[85vw] bg-(--t-surface)
                    border-s border-(--t-border) h-full overflow-y-auto p-5 flex flex-col gap-4">

          <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-(--t-text)">Фильтры</h3>
            <button class="text-(--t-text-3) hover:text-(--t-text) transition-colors"
                    @click="showFilterDrawer = false">✕</button>
          </div>

          <!-- Categories -->
          <div>
            <p class="text-xs font-medium text-(--t-text-2) mb-2">Категории</p>
            <div class="flex flex-col gap-1">
              <button
                :class="[
                  'relative overflow-hidden text-start px-3 py-2 rounded-xl text-xs transition-all',
                  filters.categoryId === '' ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                ]"
                @click="setCategory('')" @mousedown="ripple"
              >
                📦 Все
              </button>
              <button v-for="cat in props.categories" :key="cat.id"
                      :class="[
                        'relative overflow-hidden text-start px-3 py-2 rounded-xl text-xs transition-all flex items-center gap-2',
                        filters.categoryId === String(cat.id) ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                      ]"
                      @click="setCategory(String(cat.id))" @mousedown="ripple">
                {{ cat.icon }} {{ cat.name }}
                <span class="ms-auto text-[10px] opacity-60">{{ cat.count }}</span>
              </button>
            </div>
          </div>

          <!-- Price range -->
          <div>
            <p class="text-xs font-medium text-(--t-text-2) mb-2">{{ vc.priceLabel }}, ₽</p>
            <div class="flex items-center gap-2">
              <input v-model="filters.priceMin" type="number" placeholder="от"
                     class="inline-size-full py-1.5 px-2.5 text-xs rounded-lg bg-(--t-bg)/60
                            border border-(--t-border)/50 text-(--t-text) placeholder:text-(--t-text-3)
                            focus:border-(--t-primary)/50 focus:outline-none" />
              <span class="text-(--t-text-3)">—</span>
              <input v-model="filters.priceMax" type="number" placeholder="до"
                     class="inline-size-full py-1.5 px-2.5 text-xs rounded-lg bg-(--t-bg)/60
                            border border-(--t-border)/50 text-(--t-text) placeholder:text-(--t-text-3)
                            focus:border-(--t-primary)/50 focus:outline-none" />
            </div>
          </div>

          <!-- Rating -->
          <div>
            <p class="text-xs font-medium text-(--t-text-2) mb-2">Рейтинг</p>
            <div class="flex flex-col gap-1">
              <button
                v-for="r in RATING_STARS" :key="r"
                :class="[
                  'relative overflow-hidden flex items-center gap-2 px-3 py-2 rounded-lg text-xs transition-all',
                  filters.ratingMin === r ? 'bg-amber-500/12 text-amber-400 font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                ]"
                @click="filters.ratingMin = filters.ratingMin === r ? 0 : r; emit('filter-change', { ...filters })"
                @mousedown="ripple"
              >
                <span class="text-amber-400">{{ '★'.repeat(r) }}{{ '☆'.repeat(5 - r) }}</span>
                от {{ r }}
              </button>
            </div>
          </div>

          <!-- Branches -->
          <div v-if="props.branches.length > 0">
            <p class="text-xs font-medium text-(--t-text-2) mb-2">Филиал</p>
            <div class="flex flex-col gap-1">
              <button
                :class="[
                  'relative overflow-hidden text-start px-3 py-2 rounded-lg text-xs transition-all',
                  filters.branchId === '' ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                ]"
                @click="filters.branchId = ''; emit('filter-change', { ...filters })"
                @mousedown="ripple"
              >
                Все филиалы
              </button>
              <button v-for="br in props.branches" :key="br.id"
                      :class="[
                        'relative overflow-hidden text-start px-3 py-2 rounded-lg text-xs transition-all',
                        filters.branchId === String(br.id) ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                      ]"
                      @click="filters.branchId = filters.branchId === String(br.id) ? '' : String(br.id); emit('filter-change', { ...filters })"
                      @mousedown="ripple">
                📍 {{ br.name }}
              </button>
            </div>
          </div>

          <!-- Promo -->
          <button
            :class="[
              'relative overflow-hidden rounded-xl border p-3 text-xs font-medium transition-all text-start',
              filters.isPromo === true
                ? 'border-orange-500/30 bg-orange-500/10 text-orange-400'
                : 'border-(--t-border)/50 bg-(--t-surface)/60 text-(--t-text-2) hover:bg-(--t-card-hover)',
            ]"
            @click="filters.isPromo = filters.isPromo === true ? null : true; emit('filter-change', { ...filters })"
            @mousedown="ripple"
          >
            🔥 Только акции
          </button>

          <!-- Sort (mobile) -->
          <div>
            <p class="text-xs font-medium text-(--t-text-2) mb-2">Сортировка</p>
            <div class="flex flex-col gap-1">
              <button v-for="opt in sortOptions" :key="opt.key"
                      :class="[
                        'relative overflow-hidden flex items-center gap-2 px-3 py-2 rounded-lg text-xs transition-all',
                        filters.sortBy === opt.key ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                      ]"
                      @click="filters.sortBy = opt.key; emit('sort-change', filters.sortBy, filters.sortDir)"
                      @mousedown="ripple">
                {{ opt.icon }} {{ opt.label }}
              </button>
            </div>
          </div>

          <!-- Clear all -->
          <button v-if="hasActiveFilters"
                  class="relative overflow-hidden mt-auto px-4 py-2.5 rounded-xl text-xs font-medium
                         border border-(--t-border) text-(--t-text-2) hover:bg-(--t-card-hover)
                         active:scale-[0.97] transition-all"
                  @click="clearAllFilters(); showFilterDrawer = false" @mousedown="ripple">
            Сбросить все фильтры
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     STYLES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<style scoped>
/* Ripple — unique suffix pc (PublicCatalog) */
@keyframes ripple-pc {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* No-scrollbar for category chips */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Line clamp */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Sidebar */
.sidebar-pc-enter-active,
.sidebar-pc-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.sidebar-pc-enter-from,
.sidebar-pc-leave-to {
  opacity: 0;
  transform: translateX(-16px);
}

/* Modal (slides up on mobile, scales on desktop) */
.modal-pc-enter-active {
  transition: opacity 0.25s ease;
}
.modal-pc-enter-active > :last-child {
  transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}
.modal-pc-leave-active {
  transition: opacity 0.2s ease;
}
.modal-pc-leave-active > :last-child {
  transition: transform 0.2s ease-in, opacity 0.2s ease;
}
.modal-pc-enter-from,
.modal-pc-leave-to {
  opacity: 0;
}
.modal-pc-enter-from > :last-child {
  opacity: 0;
  transform: translateY(40px);
}
.modal-pc-leave-to > :last-child {
  opacity: 0;
  transform: translateY(20px);
}

/* Drawer */
.drawer-pc-enter-active,
.drawer-pc-leave-active {
  transition: opacity 0.3s ease;
}
.drawer-pc-enter-active > :last-child,
.drawer-pc-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.drawer-pc-enter-from,
.drawer-pc-leave-to {
  opacity: 0;
}
.drawer-pc-enter-from > :last-child,
.drawer-pc-leave-to > :last-child {
  transform: translateX(100%);
}

/* Cart badge */
.cart-pc-enter-active {
  transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.cart-pc-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease-in;
}
.cart-pc-enter-from {
  opacity: 0;
  transform: scale(0.5) translateY(20px);
}
.cart-pc-leave-to {
  opacity: 0;
  transform: scale(0.5) translateY(20px);
}
</style>
