<script setup lang="ts">
/**
 * TenantCatalog.vue — B2B-витрина товаров и услуг
 * в Tenant Dashboard.
 *
 * Универсальный каталог для всех 127 вертикалей CatVRF:
 *   Beauty  (услуги салона)      · Taxi   (тарифы / классы авто)
 *   Food    (блюда / ингредиенты) · Hotels (номера / тарифы)
 *   RealEstate (объекты)         · Flowers (букеты / композиции)
 *   Fashion (одежда / обувь)     · Furniture (мебель / декор)
 *   Fitness (абонементы / добавки)· Travel (туры / билеты)
 *   default (универсальный)
 *
 * ─────────────────────────────────────────────────────────────
 *  Функционал:
 *   1.  Верхняя панель: поиск + «Добавить товар» + вид grid/list
 *   2.  Sidebar с деревом категорий и фильтрами
 *   3.  Grid / list карточек товаров с фото + overlay-статусы
 *   4.  Детальная модалка товара (превью, цена, остаток, SKU)
 *   5.  Quick-edit inline: цена, остаток, статус
 *   6.  Массовые действия: скрыть / опубликовать / удалить / акция
 *   7.  KPI-виджеты: всего товаров, в наличии, акции, средний чек
 *   8.  Full-screen, filter-drawer (mobile), пагинация
 *   9.  VERTICAL_CATALOG_CONFIG — терминология по вертикалям
 *  10.  Акционные предложения и прайс-лист B2B
 * ─────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'

import VCard   from '../UI/VCard.vue'
import VButton from '../UI/VButton.vue'
import VBadge  from '../UI/VBadge.vue'
import VTabs   from '../UI/VTabs.vue'
import VModal  from '../UI/VModal.vue'
import VInput  from '../UI/VInput.vue'
import { useAuth, useTenant } from '@/stores'

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  TYPES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

type ItemStatus       = 'active' | 'draft' | 'hidden' | 'out_of_stock' | 'archived' | 'promo'
type StockLevel       = 'in_stock' | 'low' | 'out' | 'unlimited' | 'preorder'
type PriceType        = 'fixed' | 'range' | 'from' | 'negotiable' | 'free'
type ViewMode         = 'grid' | 'list'

interface CatalogItem {
  id:              number | string
  name:            string
  slug:            string
  description?:    string
  imageUrl?:       string
  images?:         string[]
  categoryId:      number | string
  categoryName?:   string
  status:          ItemStatus
  stockLevel:      StockLevel
  quantity:        number
  reserved:        number
  priceType:       PriceType
  price:           number            // base price ₽
  priceB2B?:       number            // wholesale price
  priceOld?:       number            // crossed-out price
  promoLabel?:     string
  sku?:            string
  rating?:         number            // 0-5
  reviewsCount?:   number
  salesCount:      number
  views:           number
  tags:            string[]
  isPromo:         boolean
  isFeatured:      boolean
  unit?:           string            // шт / час / ночь / км
  createdAt:       string
  updatedAt:       string
  correlationId?:  string
}

interface CatalogCategory {
  id:          number | string
  name:        string
  icon:        string
  count:       number
  parentId?:   number | string
  children?:   CatalogCategory[]
}

interface CatalogStats {
  totalItems:     number
  activeItems:    number
  inStockItems:   number
  outOfStock:     number
  promoItems:     number
  avgPrice:       number
  totalViews:     number
  totalSales:     number
  lowStockCount:  number
  draftCount:     number
}

interface CatalogFilter {
  search:      string
  categoryId:  string
  status:      string
  stockLevel:  string
  priceMin:    string
  priceMax:    string
  isPromo:     boolean | null
  sortBy:      string
  sortDir:     'asc' | 'desc'
}

interface VerticalCatalogConfig {
  label:           string
  icon:            string
  itemLabel:       string          // «Товар» / «Услуга» / «Объект»
  itemLabelPlural: string
  addLabel:        string          // «Добавить товар» / «Добавить услугу»
  priceLabel:      string          // «Цена» / «Стоимость» / «Тариф»
  stockLabel:      string          // «Остаток» / «Мест» / «Слотов»
  unitDefault:     string
  categories:      Array<{ name: string; icon: string }>
  promoHint:       string
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  PROPS & EMITS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const props = withDefaults(defineProps<{
  vertical?:       string
  items?:          CatalogItem[]
  categories?:     CatalogCategory[]
  stats?:          CatalogStats
  totalItems?:     number
  loading?:        boolean
  perPage?:        number
}>(), {
  vertical:     'default',
  items:        () => [],
  categories:   () => [],
  stats:        () => ({
    totalItems: 0, activeItems: 0, inStockItems: 0, outOfStock: 0,
    promoItems: 0, avgPrice: 0, totalViews: 0, totalSales: 0,
    lowStockCount: 0, draftCount: 0,
  }),
  totalItems:   0,
  loading:      false,
  perPage:      24,
})

const emit = defineEmits<{
  'item-click':       [item: CatalogItem]
  'item-create':      []
  'item-edit':        [item: CatalogItem]
  'item-duplicate':   [item: CatalogItem]
  'item-delete':      [ids: Array<number | string>]
  'item-toggle':      [item: CatalogItem, status: ItemStatus]
  'item-price-update':[item: CatalogItem, price: number]
  'item-stock-update':[item: CatalogItem, qty: number]
  'item-promo':       [item: CatalogItem]
  'category-click':   [cat: CatalogCategory]
  'filter-change':    [filters: CatalogFilter]
  'sort-change':      [sortBy: string, sortDir: 'asc' | 'desc']
  'page-change':      [page: number]
  'bulk-action':      [action: string, ids: Array<number | string>]
  'export':           [format: 'xlsx' | 'csv']
  'load-more':        []
}>()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STORES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const auth     = useAuth()
const business = useTenant()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  VERTICAL CATALOG CONFIG
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const VERTICAL_CATALOG_CONFIG: Record<string, VerticalCatalogConfig> = {
  beauty: {
    label: 'Каталог услуг', icon: '💄',
    itemLabel: 'Услуга', itemLabelPlural: 'Услуги',
    addLabel: 'Добавить услугу', priceLabel: 'Стоимость',
    stockLabel: 'Слотов', unitDefault: 'час',
    categories: [
      { name: 'Стрижки',      icon: '✂️' },
      { name: 'Окрашивание',   icon: '🎨' },
      { name: 'Маникюр',      icon: '💅' },
      { name: 'Косметология',  icon: '💆' },
      { name: 'Массаж',       icon: '🧖' },
      { name: 'Макияж',       icon: '💋' },
    ],
    promoHint: 'Акция: скидка для новых клиентов или комплексные услуги',
  },

  taxi: {
    label: 'Тарифы и классы', icon: '🚕',
    itemLabel: 'Тариф', itemLabelPlural: 'Тарифы',
    addLabel: 'Добавить тариф', priceLabel: 'Тариф',
    stockLabel: 'Машин', unitDefault: 'км',
    categories: [
      { name: 'Эконом',   icon: '🚗' },
      { name: 'Комфорт',  icon: '🚙' },
      { name: 'Бизнес',   icon: '🚘' },
      { name: 'Премиум',  icon: '🏎️' },
      { name: 'Минивэн',  icon: '🚐' },
      { name: 'Грузовой',  icon: '🚛' },
    ],
    promoHint: 'Промокод: скидка на первую поездку или ночной тариф',
  },

  food: {
    label: 'Меню и товары', icon: '🍽️',
    itemLabel: 'Блюдо', itemLabelPlural: 'Блюда',
    addLabel: 'Добавить блюдо', priceLabel: 'Цена',
    stockLabel: 'Порций', unitDefault: 'шт',
    categories: [
      { name: 'Завтраки',   icon: '🥐' },
      { name: 'Супы',       icon: '🍜' },
      { name: 'Горячее',    icon: '🥩' },
      { name: 'Салаты',     icon: '🥗' },
      { name: 'Десерты',    icon: '🍰' },
      { name: 'Напитки',    icon: '🍹' },
    ],
    promoHint: 'Комбо-предложение или блюдо дня',
  },

  hotel: {
    label: 'Номера и тарифы', icon: '🏨',
    itemLabel: 'Номер', itemLabelPlural: 'Номера',
    addLabel: 'Добавить номер', priceLabel: 'Тариф/ночь',
    stockLabel: 'Свободно', unitDefault: 'ночь',
    categories: [
      { name: 'Стандарт',   icon: '🛏️' },
      { name: 'Улучшенный', icon: '🛋️' },
      { name: 'Люкс',       icon: '👑' },
      { name: 'Апартаменты', icon: '🏠' },
      { name: 'Семейный',   icon: '👨‍👩‍👧‍👦' },
      { name: 'Президентский', icon: '🌟' },
    ],
    promoHint: 'Early booking -20% или пакет «Завтрак + SPA»',
  },

  realEstate: {
    label: 'Объекты недвижимости', icon: '🏢',
    itemLabel: 'Объект', itemLabelPlural: 'Объекты',
    addLabel: 'Добавить объект', priceLabel: 'Цена',
    stockLabel: 'Доступно', unitDefault: 'м²',
    categories: [
      { name: 'Квартиры',     icon: '🏠' },
      { name: 'Дома',         icon: '🏡' },
      { name: 'Коммерция',    icon: '🏪' },
      { name: 'Новостройки',  icon: '🏗️' },
      { name: 'Аренда',       icon: '🔑' },
      { name: 'Земля',        icon: '🌾' },
    ],
    promoHint: 'Акция: первый месяц аренды -50% или ипотека 0.1%',
  },

  flowers: {
    label: 'Каталог букетов', icon: '💐',
    itemLabel: 'Букет', itemLabelPlural: 'Букеты',
    addLabel: 'Добавить букет', priceLabel: 'Цена',
    stockLabel: 'В наличии', unitDefault: 'шт',
    categories: [
      { name: 'Розы',          icon: '🌹' },
      { name: 'Полевые',       icon: '🌻' },
      { name: 'Экзотические',  icon: '🌺' },
      { name: 'Свадебные',     icon: '💒' },
      { name: 'Траурные',      icon: '🕊️' },
      { name: 'Комнатные',     icon: '🪴' },
    ],
    promoHint: 'Доставка бесплатно от 3 000 ₽ или бонус открытка',
  },

  fashion: {
    label: 'Каталог одежды', icon: '👗',
    itemLabel: 'Товар', itemLabelPlural: 'Товары',
    addLabel: 'Добавить товар', priceLabel: 'Цена',
    stockLabel: 'Остаток', unitDefault: 'шт',
    categories: [
      { name: 'Платья',     icon: '👗' },
      { name: 'Верхняя',    icon: '🧥' },
      { name: 'Обувь',      icon: '👠' },
      { name: 'Аксессуары', icon: '👜' },
      { name: 'Спорт',      icon: '🏃' },
      { name: 'Бельё',      icon: '🩱' },
    ],
    promoHint: 'Sale -30% на прошлую коллекцию или капсула дня',
  },

  furniture: {
    label: 'Каталог мебели', icon: '🛋️',
    itemLabel: 'Товар', itemLabelPlural: 'Товары',
    addLabel: 'Добавить товар', priceLabel: 'Цена',
    stockLabel: 'На складе', unitDefault: 'шт',
    categories: [
      { name: 'Диваны',      icon: '🛋️' },
      { name: 'Столы',       icon: '🪑' },
      { name: 'Шкафы',       icon: '🗄️' },
      { name: 'Кровати',     icon: '🛏️' },
      { name: 'Кухни',       icon: '🍳' },
      { name: 'Декор',       icon: '🖼️' },
    ],
    promoHint: 'Бесплатная доставка + сборка или скидка на комплект',
  },

  fitness: {
    label: 'Абонементы и товары', icon: '💪',
    itemLabel: 'Позиция', itemLabelPlural: 'Позиции',
    addLabel: 'Добавить позицию', priceLabel: 'Стоимость',
    stockLabel: 'Доступно', unitDefault: 'шт',
    categories: [
      { name: 'Абонементы',  icon: '🎫' },
      { name: 'Тренировки',  icon: '🏋️' },
      { name: 'Питание',     icon: '🥤' },
      { name: 'Одежда',      icon: '👟' },
      { name: 'Аксессуары',  icon: '🧴' },
      { name: 'Групповые',   icon: '👥' },
    ],
    promoHint: 'Пробная тренировка бесплатно или -20% на годовой',
  },

  travel: {
    label: 'Туры и предложения', icon: '✈️',
    itemLabel: 'Тур', itemLabelPlural: 'Туры',
    addLabel: 'Добавить тур', priceLabel: 'Цена',
    stockLabel: 'Мест', unitDefault: 'чел.',
    categories: [
      { name: 'Пляжный',    icon: '🏖️' },
      { name: 'Экскурсии',  icon: '🗺️' },
      { name: 'Горнолыжный', icon: '⛷️' },
      { name: 'Круизы',     icon: '🚢' },
      { name: 'Авторские',  icon: '🎒' },
      { name: 'Корпоратив',  icon: '🏢' },
    ],
    promoHint: 'Горящий тур -40% или ранее бронирование -15%',
  },

  default: {
    label: 'Каталог', icon: '📦',
    itemLabel: 'Товар', itemLabelPlural: 'Товары',
    addLabel: 'Добавить товар', priceLabel: 'Цена',
    stockLabel: 'Остаток', unitDefault: 'шт',
    categories: [
      { name: 'Основные',    icon: '📦' },
      { name: 'Услуги',      icon: '🔧' },
      { name: 'Подписки',    icon: '🔄' },
      { name: 'Цифровые',    icon: '💾' },
      { name: 'Расходники',  icon: '🧻' },
      { name: 'Прочее',      icon: '📎' },
    ],
    promoHint: 'Скидка новым клиентам или бесплатная доставка',
  },
}

const vc = computed<VerticalCatalogConfig>(() =>
  VERTICAL_CATALOG_CONFIG[props.vertical] ?? VERTICAL_CATALOG_CONFIG.default
)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  MAPS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const STATUS_MAP: Record<ItemStatus, { label: string; icon: string; color: string; dot: string; bg: string }> = {
  active:       { label: 'Активен',      icon: '✅', color: 'text-emerald-400', dot: 'bg-emerald-400', bg: 'bg-emerald-500/12' },
  draft:        { label: 'Черновик',     icon: '📝', color: 'text-zinc-400',    dot: 'bg-zinc-400',    bg: 'bg-zinc-500/12' },
  hidden:       { label: 'Скрыт',       icon: '👁️', color: 'text-amber-400',   dot: 'bg-amber-400',   bg: 'bg-amber-500/12' },
  out_of_stock: { label: 'Нет в наличии', icon: '🚫', color: 'text-rose-400',   dot: 'bg-rose-400',    bg: 'bg-rose-500/12' },
  archived:     { label: 'Архив',        icon: '🗃️', color: 'text-slate-400',   dot: 'bg-slate-400',   bg: 'bg-slate-500/12' },
  promo:        { label: 'Акция',        icon: '🔥', color: 'text-orange-400',  dot: 'bg-orange-400',  bg: 'bg-orange-500/12' },
}

const STOCK_MAP: Record<StockLevel, { label: string; icon: string; color: string }> = {
  in_stock:  { label: 'В наличии',     icon: '🟢', color: 'text-emerald-400' },
  low:       { label: 'Мало',          icon: '🟡', color: 'text-amber-400' },
  out:       { label: 'Нет в наличии', icon: '🔴', color: 'text-rose-400' },
  unlimited: { label: 'Безлимит',      icon: '♾️', color: 'text-sky-400' },
  preorder:  { label: 'Предзаказ',     icon: '📋', color: 'text-violet-400' },
}

const PRICE_TYPE_MAP: Record<PriceType, { label: string; prefix: string }> = {
  fixed:      { label: 'Фиксированная', prefix: '' },
  range:      { label: 'Диапазон',      prefix: '' },
  from:       { label: 'От',            prefix: 'от ' },
  negotiable: { label: 'Договорная',    prefix: '' },
  free:       { label: 'Бесплатно',     prefix: '' },
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STATE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const rootEl             = ref<HTMLElement | null>(null)
const scrollSentinel     = ref<HTMLElement | null>(null)
const isFullscreen       = ref(false)
const showItemModal      = ref(false)
const showFilterDrawer   = ref(false)
const showSidebar        = ref(true)
const selectedItem       = ref<CatalogItem | null>(null)
const currentPage        = ref(1)
const viewMode           = ref<ViewMode>('grid')

// Quick-edit
const quickEditId        = ref<number | string | null>(null)
const quickEditPrice     = ref('')
const quickEditStock     = ref('')

// Bulk
const selectedIds = reactive<Set<number | string>>(new Set())

// Filters
const filters = reactive<CatalogFilter>({
  search:     '',
  categoryId: '',
  status:     '',
  stockLevel: '',
  priceMin:   '',
  priceMax:   '',
  isPromo:    null,
  sortBy:     'updatedAt',
  sortDir:    'desc',
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  COMPUTED
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const totalPages = computed(() => Math.ceil(props.totalItems / props.perPage) || 1)

const hasActiveFilters = computed(() =>
  filters.categoryId !== '' || filters.status !== '' ||
  filters.stockLevel !== '' || filters.priceMin !== '' ||
  filters.priceMax !== '' || filters.isPromo !== null
)

const filteredItems = computed(() => {
  let result = [...props.items]
  const q = filters.search.toLowerCase().trim()

  if (q) {
    result = result.filter(
      (it) =>
        it.name.toLowerCase().includes(q) ||
        (it.description && it.description.toLowerCase().includes(q)) ||
        (it.sku && it.sku.toLowerCase().includes(q)) ||
        it.tags.some((t) => t.toLowerCase().includes(q))
    )
  }
  if (filters.categoryId)  result = result.filter((it) => String(it.categoryId) === filters.categoryId)
  if (filters.status)      result = result.filter((it) => it.status === filters.status)
  if (filters.stockLevel)  result = result.filter((it) => it.stockLevel === filters.stockLevel)
  if (filters.priceMin)    result = result.filter((it) => it.price >= Number(filters.priceMin))
  if (filters.priceMax)    result = result.filter((it) => it.price <= Number(filters.priceMax))
  if (filters.isPromo === true)  result = result.filter((it) => it.isPromo)
  if (filters.isPromo === false) result = result.filter((it) => !it.isPromo)

  const dir = filters.sortDir === 'asc' ? 1 : -1
  result.sort((a, b) => {
    const av = Object(a)[filters.sortBy]
    const bv = Object(b)[filters.sortBy]
    if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir
    return String(av ?? '').localeCompare(String(bv ?? ''), 'ru') * dir
  })
  return result
})

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * props.perPage
  return filteredItems.value.slice(start, start + props.perPage)
})

const isAllSelected = computed(() =>
  filteredItems.value.length > 0 &&
  filteredItems.value.every((it) => selectedIds.has(it.id))
)

const statusCounts = computed(() => {
  const m: Record<string, number> = {}
  for (const it of props.items) {
    m[it.status] = (m[it.status] || 0) + 1
  }
  return m
})

const categoryCounts = computed(() => {
  const m: Record<string, number> = {}
  for (const it of props.items) {
    m[String(it.categoryId)] = (m[String(it.categoryId)] || 0) + 1
  }
  return m
})

const sortOptions: Array<{ key: string; label: string }> = [
  { key: 'updatedAt', label: 'Дата обновления' },
  { key: 'name',      label: 'Название' },
  { key: 'price',     label: 'Цена' },
  { key: 'quantity',  label: 'Остаток' },
  { key: 'salesCount', label: 'Продажи' },
  { key: 'views',     label: 'Просмотры' },
]

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

function fmtPercent(v: number): string {
  return v.toFixed(1) + '%'
}

function fmtDate(iso: string): string {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('ru-RU', {
    day: 'numeric', month: 'short', year: 'numeric',
  }).format(new Date(iso))
}

function fmtDateShort(iso: string): string {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('ru-RU', {
    day: 'numeric', month: 'short',
  }).format(new Date(iso))
}

function fmtPriceDisplay(item: CatalogItem): string {
  if (item.priceType === 'free')       return 'Бесплатно'
  if (item.priceType === 'negotiable') return 'Договорная'
  const prefix = PRICE_TYPE_MAP[item.priceType].prefix
  return prefix + fmtCurrency(item.price)
}

function stockAvailable(item: CatalogItem): number {
  return Math.max(0, item.quantity - item.reserved)
}

function discountPercent(item: CatalogItem): number {
  if (!item.priceOld || item.priceOld <= item.price) return 0
  return Math.round(((item.priceOld - item.price) / item.priceOld) * 100)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  ACTIONS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function openItem(item: CatalogItem) {
  selectedItem.value  = item
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
  filters.status     = ''
  filters.stockLevel = ''
  filters.priceMin   = ''
  filters.priceMax   = ''
  filters.isPromo    = null
  currentPage.value  = 1
  emit('filter-change', { ...filters })
}

function toggleSort(col: string) {
  if (filters.sortBy === col) filters.sortDir = filters.sortDir === 'asc' ? 'desc' : 'asc'
  else { filters.sortBy = col; filters.sortDir = 'desc' }
  emit('sort-change', filters.sortBy, filters.sortDir)
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
}

// ── Quick Edit ──
function startQuickEdit(item: CatalogItem, e: MouseEvent) {
  e.stopPropagation()
  quickEditId.value    = item.id
  quickEditPrice.value = String(item.price)
  quickEditStock.value = String(item.quantity)
}

function saveQuickEdit(item: CatalogItem) {
  const newPrice = Number(quickEditPrice.value)
  const newStock = Number(quickEditStock.value)
  if (!isNaN(newPrice) && newPrice !== item.price)  emit('item-price-update', item, newPrice)
  if (!isNaN(newStock) && newStock !== item.quantity) emit('item-stock-update', item, newStock)
  quickEditId.value = null
}

function cancelQuickEdit() {
  quickEditId.value = null
}

// ── Bulk ──
function toggleSelectAll() {
  if (isAllSelected.value) selectedIds.clear()
  else filteredItems.value.forEach((it) => selectedIds.add(it.id))
}

function toggleItemSelect(id: number | string) {
  if (selectedIds.has(id)) selectedIds.delete(id)
  else selectedIds.add(id)
}

function executeBulkAction(action: string) {
  emit('bulk-action', action, Array.from(selectedIds))
  selectedIds.clear()
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  KEYBOARD
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    if (quickEditId.value)     { cancelQuickEdit(); return }
    if (showItemModal.value)   { closeItemModal(); return }
    if (showFilterDrawer.value){ showFilterDrawer.value = false; return }
    if (isFullscreen.value)    { toggleFullscreen(); return }
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

function checkViewport() {
  if (window.innerWidth < 768)  { viewMode.value = 'grid'; showSidebar.value = false }
  else if (window.innerWidth < 1024) { showSidebar.value = false }
  else { showSidebar.value = true }
}

// ── Infinite scroll ──
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
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-ct_0.6s_ease-out]'
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
         TOP BAR
    ══════════════════════════════════════════════ -->
    <header class="sticky inset-block-start-0 z-30 bg-(--t-surface)/80 backdrop-blur-xl
                   border-b border-(--t-border)/60">
      <!-- Row 1: title + controls -->
      <div class="flex items-center gap-3 px-4 pt-4 pb-2 sm:px-6">
        <div class="flex items-center gap-2 flex-1 min-w-0">
          <span class="text-xl">{{ vc.icon }}</span>
          <h1 class="text-base sm:text-lg font-bold text-(--t-text) truncate">{{ vc.label }}</h1>
          <span v-if="props.stats.promoItems > 0"
                class="hidden sm:inline-flex text-[10px] px-2 py-0.5 rounded-full
                       bg-orange-500/12 text-orange-400 font-semibold">
            🔥 {{ props.stats.promoItems }} акций
          </span>
        </div>

        <!-- Toggle sidebar (desktop) -->
        <button
          class="hidden lg:flex relative overflow-hidden shrink-0 w-9 h-9 rounded-lg
                 border border-(--t-border)/50 bg-(--t-surface)
                 items-center justify-center text-(--t-text-2)
                 hover:bg-(--t-card-hover) active:scale-95 transition-all"
          @click="showSidebar = !showSidebar" @mousedown="ripple"
          :title="showSidebar ? 'Скрыть панель' : 'Показать панель'"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
          </svg>
        </button>

        <!-- Fullscreen -->
        <button
          class="relative overflow-hidden shrink-0 w-9 h-9 rounded-lg
                 border border-(--t-border)/50 bg-(--t-surface)
                 flex items-center justify-center text-(--t-text-2)
                 hover:bg-(--t-card-hover) active:scale-95 transition-all"
          @click="toggleFullscreen" @mousedown="ripple"
          :title="isFullscreen ? 'Свернуть' : 'На весь экран'"
        >
          <svg v-if="!isFullscreen" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
          </svg>
          <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5m-4.5 0v4.5m0-4.5 5.25 5.25" />
          </svg>
        </button>
      </div>

      <!-- Row 2: search + actions -->
      <div class="flex flex-wrap items-center gap-2 px-4 pb-3 sm:px-6">
        <!-- Search -->
        <div class="relative flex-1 min-w-50 max-w-md">
          <svg class="absolute inset-inline-start-3 inset-block-start-1/2 -translate-y-1/2
                      w-4 h-4 text-(--t-text-3) pointer-events-none"
               fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
          </svg>
          <input
            v-model="filters.search"
            type="text"
            :placeholder="`Поиск ${vc.itemLabelPlural.toLowerCase()}…`"
            class="inline-size-full py-2 ps-9 pe-3 text-sm rounded-xl
                   bg-(--t-bg)/60 border border-(--t-border)/50 text-(--t-text)
                   placeholder:text-(--t-text-3)
                   focus:border-(--t-primary)/60 focus:ring-1 focus:ring-(--t-primary)/30
                   outline-none transition-all"
          />
        </div>

        <!-- Sort selector -->
        <div class="hidden sm:flex items-center gap-1">
          <select
            v-model="filters.sortBy"
            class="text-xs py-2 px-2.5 rounded-lg bg-(--t-bg)/60 border border-(--t-border)/50
                   text-(--t-text-2) focus:outline-none focus:border-(--t-primary)/50"
            @change="emit('sort-change', filters.sortBy, filters.sortDir)"
          >
            <option v-for="opt in sortOptions" :key="opt.key" :value="opt.key">
              {{ opt.label }}
            </option>
          </select>
          <button
            class="w-8 h-8 rounded-lg border border-(--t-border)/50 flex items-center justify-center
                   text-(--t-text-3) hover:bg-(--t-card-hover) transition-all"
            @click="filters.sortDir = filters.sortDir === 'asc' ? 'desc' : 'asc'; emit('sort-change', filters.sortBy, filters.sortDir)"
          >
            <svg class="w-3.5 h-3.5 transition-transform" :class="filters.sortDir === 'asc' ? '' : 'rotate-180'"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
            </svg>
          </button>
        </div>

        <!-- View toggle -->
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

        <!-- Clear filters -->
        <button
          v-if="hasActiveFilters"
          class="text-xs text-(--t-text-3) hover:text-(--t-text) transition-colors
                 underline underline-offset-2 decoration-dotted"
          @click="clearAllFilters"
        >
          Сбросить
        </button>

        <div class="flex-1" />

        <!-- Filter drawer (mobile) -->
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

        <!-- Add item -->
        <button
          class="relative overflow-hidden inline-flex items-center gap-1.5 px-3 py-2
                 rounded-xl text-xs font-semibold bg-(--t-primary)/12 text-(--t-primary)
                 border border-(--t-primary)/20 hover:bg-(--t-primary)/20
                 active:scale-[0.97] transition-all"
          @click="emit('item-create')" @mousedown="ripple"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          <span class="hidden sm:inline">{{ vc.addLabel }}</span>
          <span class="sm:hidden">Добавить</span>
        </button>

        <!-- Export -->
        <button
          class="relative overflow-hidden w-9 h-9 rounded-lg border border-(--t-border)/50
                 bg-(--t-surface) flex items-center justify-center text-(--t-text-2)
                 hover:bg-(--t-card-hover) active:scale-95 transition-all"
          @click="emit('export', 'xlsx')" @mousedown="ripple" title="Экспорт"
        >
          📥
        </button>
      </div>
    </header>

    <!-- ══════════════════════════════════════════════
         KPI WIDGETS
    ══════════════════════════════════════════════ -->
    <section class="px-4 sm:px-6 pt-4 grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-3">
      <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60
                  backdrop-blur-sm p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">Всего {{ vc.itemLabelPlural.toLowerCase() }}</span>
        <span class="text-lg font-bold text-(--t-text)">{{ fmtNumber(props.stats.totalItems) }}</span>
        <span class="text-[10px] text-(--t-text-3)">
          ✅ активных: {{ fmtNumber(props.stats.activeItems) }}
        </span>
      </div>
      <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60
                  backdrop-blur-sm p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">В наличии</span>
        <span class="text-lg font-bold text-emerald-400">{{ fmtNumber(props.stats.inStockItems) }}</span>
        <span v-if="props.stats.lowStockCount > 0" class="text-[10px] text-amber-400">
          ⚠️ мало: {{ props.stats.lowStockCount }}
        </span>
        <span v-else class="text-[10px] text-(--t-text-3)">📦 полный {{ vc.stockLabel.toLowerCase() }}</span>
      </div>
      <div class="rounded-xl border border-rose-500/20 bg-rose-500/5
                  backdrop-blur-sm p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">Нет в наличии</span>
        <span :class="['text-lg font-bold', props.stats.outOfStock > 0 ? 'text-rose-400' : 'text-emerald-400']">
          {{ fmtNumber(props.stats.outOfStock) }}
        </span>
        <span class="text-[10px] text-(--t-text-3)">🚫 требуют пополнения</span>
      </div>
      <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5
                  backdrop-blur-sm p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">Средний чек</span>
        <span class="text-lg font-bold text-emerald-400">{{ fmtCurrency(props.stats.avgPrice) }}</span>
        <span class="text-[10px] text-(--t-text-3)">💰 {{ vc.priceLabel.toLowerCase() }}</span>
      </div>
      <div class="hidden lg:flex rounded-xl border border-orange-500/20 bg-orange-500/5
                  backdrop-blur-sm p-3 flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">Акции</span>
        <span class="text-lg font-bold text-orange-400">{{ fmtNumber(props.stats.promoItems) }}</span>
        <span class="text-[10px] text-(--t-text-3)">🔥 акционных предложений</span>
      </div>
    </section>

    <!-- ══════════════════════════════════════════════
         BULK BAR
    ══════════════════════════════════════════════ -->
    <Transition name="slide-ct">
      <div v-if="selectedIds.size > 0"
           class="mx-4 sm:mx-6 mt-3 flex items-center gap-2 rounded-xl
                  border border-(--t-primary)/30 bg-(--t-primary)/8 px-4 py-2.5">
        <input type="checkbox" :checked="isAllSelected"
               class="w-3.5 h-3.5 rounded border-zinc-600 bg-zinc-800 accent-(--t-primary)"
               @change="toggleSelectAll" />
        <span class="text-xs font-medium text-(--t-text)">Выбрано: {{ selectedIds.size }}</span>
        <div class="flex-1" />
        <button
          class="relative overflow-hidden text-xs px-2.5 py-1.5 rounded-lg
                 bg-emerald-500/12 text-emerald-400 border border-emerald-500/20
                 hover:bg-emerald-500/20 active:scale-95 transition-all"
          @click="executeBulkAction('publish')" @mousedown="ripple"
        >
          ✅ Опубликовать
        </button>
        <button
          class="relative overflow-hidden text-xs px-2.5 py-1.5 rounded-lg
                 bg-amber-500/12 text-amber-400 border border-amber-500/20
                 hover:bg-amber-500/20 active:scale-95 transition-all"
          @click="executeBulkAction('hide')" @mousedown="ripple"
        >
          👁️ Скрыть
        </button>
        <button
          class="relative overflow-hidden text-xs px-2.5 py-1.5 rounded-lg
                 bg-orange-500/12 text-orange-400 border border-orange-500/20
                 hover:bg-orange-500/20 active:scale-95 transition-all"
          @click="executeBulkAction('promo')" @mousedown="ripple"
        >
          🔥 Акция
        </button>
        <button
          class="relative overflow-hidden text-xs px-2.5 py-1.5 rounded-lg
                 bg-rose-500/12 text-rose-400 border border-rose-500/20
                 hover:bg-rose-500/20 active:scale-95 transition-all"
          @click="executeBulkAction('delete')" @mousedown="ripple"
        >
          🗑️ Удалить
        </button>
        <button class="text-xs text-(--t-text-3) hover:text-(--t-text) transition-colors"
                @click="selectedIds.clear()">
          Отменить
        </button>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════════════
         MAIN: SIDEBAR + CONTENT
    ══════════════════════════════════════════════ -->
    <div class="flex-1 flex gap-4 px-4 sm:px-6 py-4">

      <!-- ═══ SIDEBAR (categories + filters) ═══ -->
      <Transition name="sidebar-ct">
        <aside v-if="showSidebar"
               class="hidden lg:flex shrink-0 flex-col gap-4 w-56">

          <!-- Categories tree -->
          <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60
                      backdrop-blur-sm p-3">
            <h3 class="text-xs font-bold text-(--t-text) mb-2.5">Категории</h3>
            <button
              :class="[
                'relative overflow-hidden inline-size-full text-start px-2.5 py-2 rounded-lg text-xs transition-all mb-1',
                filters.categoryId === '' ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
              ]"
              @click="setCategory('')" @mousedown="ripple"
            >
              📦 Все {{ vc.itemLabelPlural.toLowerCase() }}
              <span class="float-end text-(--t-text-3)">{{ props.stats.totalItems }}</span>
            </button>

            <button
              v-for="cat in props.categories" :key="cat.id"
              :class="[
                'relative overflow-hidden inline-size-full text-start px-2.5 py-2 rounded-lg text-xs transition-all mb-0.5',
                filters.categoryId === String(cat.id) ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
              ]"
              @click="setCategory(String(cat.id))" @mousedown="ripple"
            >
              {{ cat.icon }} {{ cat.name }}
              <span class="float-end text-(--t-text-3)">{{ cat.count }}</span>
            </button>
          </div>

          <!-- Status filter -->
          <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60
                      backdrop-blur-sm p-3">
            <h3 class="text-xs font-bold text-(--t-text) mb-2.5">Статус</h3>
            <button
              v-for="(st, stKey) in STATUS_MAP" :key="stKey"
              :class="[
                'relative overflow-hidden inline-size-full text-start px-2.5 py-1.5 rounded-lg text-xs transition-all mb-0.5',
                'flex items-center gap-2',
                filters.status === (stKey as string) ? `${st.bg} ${st.color} font-semibold` : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
              ]"
              @click="filters.status = filters.status === (stKey as string) ? '' : (stKey as string); currentPage = 1; emit('filter-change', { ...filters })"
              @mousedown="ripple"
            >
              <span :class="['w-2 h-2 rounded-full shrink-0', st.dot]" />
              {{ st.label }}
              <span v-if="statusCounts[stKey as string]" class="ms-auto text-(--t-text-3)">{{ statusCounts[stKey as string] }}</span>
            </button>
          </div>

          <!-- Stock filter -->
          <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60
                      backdrop-blur-sm p-3">
            <h3 class="text-xs font-bold text-(--t-text) mb-2.5">{{ vc.stockLabel }}</h3>
            <button
              v-for="(sk, skKey) in STOCK_MAP" :key="skKey"
              :class="[
                'relative overflow-hidden inline-size-full text-start px-2.5 py-1.5 rounded-lg text-xs transition-all mb-0.5',
                'flex items-center gap-2',
                filters.stockLevel === (skKey as string) ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
              ]"
              @click="filters.stockLevel = filters.stockLevel === (skKey as string) ? '' : (skKey as string); currentPage = 1; emit('filter-change', { ...filters })"
              @mousedown="ripple"
            >
              {{ sk.icon }} {{ sk.label }}
            </button>
          </div>

          <!-- Price range -->
          <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60
                      backdrop-blur-sm p-3">
            <h3 class="text-xs font-bold text-(--t-text) mb-2.5">{{ vc.priceLabel }}, ₽</h3>
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

          <!-- Promo only -->
          <button
            :class="[
              'relative overflow-hidden rounded-xl border p-3 text-xs font-medium transition-all text-start',
              filters.isPromo === true
                ? 'border-orange-500/30 bg-orange-500/10 text-orange-400'
                : 'border-(--t-border)/50 bg-(--t-surface)/60 text-(--t-text-2) hover:bg-(--t-card-hover)',
            ]"
            @click="filters.isPromo = filters.isPromo === true ? null : true; currentPage = 1; emit('filter-change', { ...filters })"
            @mousedown="ripple"
          >
            🔥 Только акции
          </button>
        </aside>
      </Transition>

      <!-- ═══ CONTENT ═══ -->
      <div class="flex-1 flex flex-col gap-4 min-w-0">

        <!-- Loading -->
        <div v-if="props.loading && props.items.length === 0"
             :class="viewMode === 'grid' ? 'grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3' : 'flex flex-col gap-2'">
          <div v-for="n in 8" :key="n"
               :class="viewMode === 'grid' ? 'aspect-square rounded-xl bg-(--t-surface)/60 animate-pulse' : 'h-20 rounded-xl bg-(--t-surface)/60 animate-pulse'" />
        </div>

        <!-- Empty -->
        <div v-else-if="filteredItems.length === 0 && !props.loading"
             class="flex flex-col items-center justify-center py-20 text-center">
          <span class="text-5xl mb-4">{{ vc.icon }}</span>
          <p class="text-sm font-medium text-(--t-text-2)">
            {{ filters.search || hasActiveFilters ? 'Ничего не найдено' : `${vc.itemLabelPlural} ещё не добавлены` }}
          </p>
          <p class="text-xs text-(--t-text-3) mt-1 max-w-xs">
            {{ filters.search || hasActiveFilters
              ? 'Попробуйте изменить фильтры или поиск'
              : `Добавьте ${vc.itemLabel.toLowerCase()} в каталог — и ваши клиенты увидят его на витрине`
            }}
          </p>
          <button v-if="!filters.search && !hasActiveFilters"
                  class="relative overflow-hidden mt-4 px-4 py-2 rounded-xl text-xs font-semibold
                         bg-(--t-primary)/12 text-(--t-primary) border border-(--t-primary)/20
                         hover:bg-(--t-primary)/20 active:scale-[0.97] transition-all"
                  @click="emit('item-create')" @mousedown="ripple">
            + {{ vc.addLabel }}
          </button>
          <button v-else class="mt-3 text-xs text-(--t-primary) hover:underline"
                  @click="clearAllFilters">
            Сбросить фильтры
          </button>
        </div>

        <!-- ═══ GRID VIEW ═══ -->
        <div v-else-if="viewMode === 'grid'"
             class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
          <div v-for="item in paginatedItems" :key="item.id"
               :class="[
                 'group relative rounded-xl border overflow-hidden transition-all',
                 'cursor-pointer hover:shadow-lg hover:shadow-black/10',
                 'active:scale-[0.98]',
                 selectedIds.has(item.id)
                   ? 'border-(--t-primary)/40 ring-1 ring-(--t-primary)/30'
                   : 'border-(--t-border)/50 hover:border-(--t-border)',
                 item.status === 'out_of_stock' || item.stockLevel === 'out'
                   ? 'grayscale-40 opacity-80' : '',
               ]"
               @click="openItem(item)">

            <!-- Image area -->
            <div class="relative aspect-square bg-(--t-bg)/80 overflow-hidden">
              <img v-if="item.imageUrl"
                   :src="item.imageUrl" :alt="item.name"
                   class="inline-size-full block-size-full object-cover
                          group-hover:scale-105 transition-transform duration-500" />
              <div v-else class="inline-size-full block-size-full flex items-center justify-center text-3xl
                               bg-linear-to-br from-zinc-800 to-zinc-900">
                {{ vc.icon }}
              </div>

              <!-- Overlays -->
              <div class="absolute inset-block-start-2 inset-inline-start-2 flex flex-col gap-1">
                <!-- Promo badge -->
                <span v-if="item.isPromo"
                      class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md
                             bg-orange-500/90 text-white text-[10px] font-bold shadow-sm">
                  🔥 {{ item.promoLabel ?? 'Акция' }}
                </span>
                <!-- Discount -->
                <span v-if="discountPercent(item) > 0"
                      class="inline-flex items-center px-1.5 py-0.5 rounded-md
                             bg-rose-500/90 text-white text-[10px] font-bold shadow-sm">
                  -{{ discountPercent(item) }}%
                </span>
                <!-- Featured -->
                <span v-if="item.isFeatured"
                      class="inline-flex items-center px-1.5 py-0.5 rounded-md
                             bg-violet-500/90 text-white text-[10px] font-bold shadow-sm">
                  ⭐ Топ
                </span>
              </div>

              <!-- Status chip (top-right) -->
              <span v-if="item.status !== 'active'"
                    :class="['absolute inset-block-start-2 inset-inline-end-2 inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-semibold shadow-sm', STATUS_MAP[item.status].bg, STATUS_MAP[item.status].color]">
                <span :class="['w-1.5 h-1.5 rounded-full', STATUS_MAP[item.status].dot]" />
                {{ STATUS_MAP[item.status].label }}
              </span>

              <!-- Checkbox overlay -->
              <div class="absolute inset-block-end-2 inset-inline-start-2" @click.stop>
                <input type="checkbox" :checked="selectedIds.has(item.id)"
                       class="w-4 h-4 rounded border-zinc-600 bg-zinc-800/80 accent-(--t-primary)
                              opacity-0 group-hover:opacity-100 transition-opacity
                              checked:opacity-100"
                       @change="toggleItemSelect(item.id)" />
              </div>

              <!-- Quick-edit button overlay -->
              <button
                class="absolute inset-block-end-2 inset-inline-end-2 w-7 h-7 rounded-lg
                       bg-black/50 backdrop-blur-sm flex items-center justify-center
                       text-white/80 hover:text-white opacity-0 group-hover:opacity-100
                       transition-all active:scale-90"
                @click.stop="startQuickEdit(item, $event)" title="Быстрое редактирование"
              >
                ✏️
              </button>
            </div>

            <!-- Card body -->
            <div class="p-2.5 bg-(--t-surface)/60 backdrop-blur-sm">
              <p class="text-xs font-semibold text-(--t-text) truncate leading-tight">{{ item.name }}</p>
              <p v-if="item.categoryName" class="text-[10px] text-(--t-text-3) truncate mt-0.5">{{ item.categoryName }}</p>

              <!-- Price row -->
              <div class="flex items-baseline gap-1.5 mt-1.5">
                <template v-if="item.priceType === 'free'">
                  <span class="text-sm font-bold text-emerald-400">Бесплатно</span>
                </template>
                <template v-else-if="item.priceType === 'negotiable'">
                  <span class="text-xs font-medium text-(--t-text-2)">Договорная</span>
                </template>
                <template v-else>
                  <span class="text-sm font-bold text-(--t-text)">{{ fmtPriceDisplay(item) }}</span>
                  <span v-if="item.priceOld && item.priceOld > item.price"
                        class="text-[10px] text-(--t-text-3) line-through">{{ fmtCurrency(item.priceOld) }}</span>
                  <span v-if="item.unit" class="text-[10px] text-(--t-text-3)">/{{ item.unit }}</span>
                </template>
              </div>

              <!-- B2B price -->
              <p v-if="item.priceB2B && item.priceB2B < item.price"
                 class="text-[10px] text-sky-400 mt-0.5 font-medium">
                B2B: {{ fmtCurrency(item.priceB2B) }}
              </p>

              <!-- Stock row -->
              <div class="flex items-center justify-between mt-1.5">
                <span :class="['text-[10px] font-medium', STOCK_MAP[item.stockLevel].color]">
                  {{ STOCK_MAP[item.stockLevel].icon }} {{ item.stockLevel === 'unlimited' ? 'Безлимит' : stockAvailable(item) + ' ' + (item.unit ?? vc.unitDefault) }}
                </span>
                <span v-if="item.rating != null" class="text-[10px] text-amber-400">
                  ⭐ {{ item.rating.toFixed(1) }}
                </span>
              </div>
            </div>

            <!-- Quick edit overlay -->
            <div v-if="quickEditId === item.id"
                 class="absolute inset-0 z-10 bg-black/70 backdrop-blur-sm
                        flex flex-col items-center justify-center gap-3 p-4"
                 @click.stop>
              <p class="text-xs font-semibold text-white">Быстрое редактирование</p>
              <div class="inline-size-full max-w-48 flex flex-col gap-2">
                <label class="text-[10px] text-zinc-300">{{ vc.priceLabel }}, ₽</label>
                <input v-model="quickEditPrice" type="number"
                       class="inline-size-full py-1.5 px-2.5 text-xs rounded-lg bg-zinc-800 border border-zinc-600
                              text-white focus:border-(--t-primary)/50 focus:outline-none" />
                <label class="text-[10px] text-zinc-300">{{ vc.stockLabel }}</label>
                <input v-model="quickEditStock" type="number"
                       class="inline-size-full py-1.5 px-2.5 text-xs rounded-lg bg-zinc-800 border border-zinc-600
                              text-white focus:border-(--t-primary)/50 focus:outline-none" />
              </div>
              <div class="flex items-center gap-2 mt-1">
                <button class="relative overflow-hidden px-3 py-1.5 text-xs font-semibold rounded-lg
                               bg-emerald-500/20 text-emerald-400 border border-emerald-500/30
                               hover:bg-emerald-500/30 active:scale-95 transition-all"
                        @click.stop="saveQuickEdit(item)" @mousedown="ripple">
                  💾 Сохранить
                </button>
                <button class="px-3 py-1.5 text-xs text-zinc-400 hover:text-white transition-colors"
                        @click.stop="cancelQuickEdit">
                  Отмена
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══ LIST VIEW ═══ -->
        <div v-else class="flex flex-col gap-2">
          <div v-for="item in paginatedItems" :key="item.id"
               :class="[
                 'group flex items-center gap-3 rounded-xl border px-3 py-2.5',
                 'transition-all cursor-pointer hover:shadow-md hover:shadow-black/5',
                 'active:scale-[0.995]',
                 selectedIds.has(item.id)
                   ? 'border-(--t-primary)/40 bg-(--t-primary)/5'
                   : 'border-(--t-border)/50 bg-(--t-surface)/60 hover:border-(--t-border)',
                 item.status === 'out_of_stock' || item.stockLevel === 'out'
                   ? 'grayscale-40 opacity-80' : '',
               ]"
               @click="openItem(item)">

            <!-- Checkbox -->
            <div @click.stop>
              <input type="checkbox" :checked="selectedIds.has(item.id)"
                     class="w-3.5 h-3.5 rounded border-zinc-600 bg-zinc-800 accent-(--t-primary)"
                     @change="toggleItemSelect(item.id)" />
            </div>

            <!-- Thumbnail -->
            <div class="shrink-0 w-12 h-12 rounded-lg overflow-hidden bg-(--t-bg)/80">
              <img v-if="item.imageUrl" :src="item.imageUrl" :alt="item.name"
                   class="inline-size-full block-size-full object-cover" />
              <span v-else class="inline-size-full block-size-full flex items-center justify-center text-lg">
                {{ vc.icon }}
              </span>
            </div>

            <!-- Name + meta -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-1.5">
                <p class="text-xs font-semibold text-(--t-text) truncate">{{ item.name }}</p>
                <span v-if="item.isPromo" class="shrink-0 text-[9px] px-1 py-0.5 rounded bg-orange-500/15 text-orange-400 font-bold">🔥</span>
                <span v-if="item.isFeatured" class="shrink-0 text-[9px] px-1 py-0.5 rounded bg-violet-500/15 text-violet-400 font-bold">⭐</span>
              </div>
              <p class="text-[10px] text-(--t-text-3) truncate">
                {{ item.categoryName ?? '' }}
                <template v-if="item.sku"> · SKU: {{ item.sku }}</template>
              </p>
            </div>

            <!-- Price -->
            <div class="hidden sm:flex flex-col items-end shrink-0 min-w-20">
              <span class="text-xs font-bold text-(--t-text)">{{ fmtPriceDisplay(item) }}</span>
              <span v-if="item.priceB2B && item.priceB2B < item.price"
                    class="text-[10px] text-sky-400 font-medium">B2B: {{ fmtCurrency(item.priceB2B) }}</span>
            </div>

            <!-- Stock -->
            <div class="hidden md:flex flex-col items-end shrink-0 min-w-16">
              <span :class="['text-xs font-medium', STOCK_MAP[item.stockLevel].color]">
                {{ item.stockLevel === 'unlimited' ? '♾️' : stockAvailable(item) }}
              </span>
              <span class="text-[10px] text-(--t-text-3)">{{ STOCK_MAP[item.stockLevel].label }}</span>
            </div>

            <!-- Status badge -->
            <span :class="['hidden lg:inline-flex shrink-0 items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium', STATUS_MAP[item.status].bg, STATUS_MAP[item.status].color]">
              <span :class="['w-1.5 h-1.5 rounded-full', STATUS_MAP[item.status].dot]" />
              {{ STATUS_MAP[item.status].label }}
            </span>

            <!-- Sales -->
            <div class="hidden xl:flex flex-col items-end shrink-0 min-w-14">
              <span class="text-xs font-medium text-(--t-text)">{{ fmtNumber(item.salesCount) }}</span>
              <span class="text-[10px] text-(--t-text-3)">продаж</span>
            </div>

            <!-- Edit -->
            <button class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover) transition-colors"
                    @click.stop="emit('item-edit', item)">
              ✏️
            </button>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1 && !props.loading"
             class="flex items-center justify-center gap-1.5 mt-2">
          <button :disabled="currentPage <= 1"
                  class="w-8 h-8 rounded-lg border border-(--t-border)/50 flex items-center justify-center
                         text-(--t-text-3) hover:bg-(--t-card-hover) disabled:opacity-30
                         disabled:cursor-not-allowed transition-all"
                  @click="goPage(currentPage - 1)">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
          </button>
          <template v-for="p in totalPages" :key="p">
            <button v-if="p <= 3 || p > totalPages - 2 || Math.abs(p - currentPage) <= 1"
                    :class="[
                      'w-8 h-8 rounded-lg text-xs font-medium transition-all',
                      p === currentPage ? 'bg-(--t-primary) text-white shadow-sm' : 'text-(--t-text-3) hover:bg-(--t-card-hover)',
                    ]"
                    @click="goPage(p)">
              {{ p }}
            </button>
            <span v-else-if="p === 4 || p === totalPages - 2"
                  class="w-8 h-8 flex items-center justify-center text-(--t-text-3) text-xs">…</span>
          </template>
          <button :disabled="currentPage >= totalPages"
                  class="w-8 h-8 rounded-lg border border-(--t-border)/50 flex items-center justify-center
                         text-(--t-text-3) hover:bg-(--t-card-hover) disabled:opacity-30
                         disabled:cursor-not-allowed transition-all"
                  @click="goPage(currentPage + 1)">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
          </button>
        </div>

        <div ref="scrollSentinel" class="h-1" />
        <div v-if="props.loading && props.items.length > 0" class="flex justify-center py-4">
          <div class="w-5 h-5 border-2 border-(--t-primary)/30 border-t-(--t-primary) rounded-full animate-spin" />
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════
         ITEM DETAIL MODAL
    ══════════════════════════════════════════════ -->
    <Transition name="modal-ct">
      <div v-if="showItemModal && selectedItem"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="closeItemModal">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeItemModal" />
        <div class="relative z-10 inline-size-full max-w-lg rounded-2xl border border-(--t-border)
                    bg-(--t-surface)/90 backdrop-blur-xl shadow-2xl overflow-hidden
                    max-h-[90vh] flex flex-col">

          <!-- Image header -->
          <div class="relative shrink-0 aspect-video bg-(--t-bg)/80 overflow-hidden">
            <img v-if="selectedItem.imageUrl"
                 :src="selectedItem.imageUrl" :alt="selectedItem.name"
                 class="inline-size-full block-size-full object-cover" />
            <div v-else class="inline-size-full block-size-full flex items-center justify-center text-5xl
                             bg-linear-to-br from-zinc-800 to-zinc-900">
              {{ vc.icon }}
            </div>
            <!-- Close -->
            <button class="absolute inset-block-start-3 inset-inline-end-3 w-8 h-8 rounded-full
                           bg-black/40 backdrop-blur-sm flex items-center justify-center
                           text-white/80 hover:text-white transition-colors"
                    @click="closeItemModal">✕</button>
            <!-- Badges -->
            <div class="absolute inset-block-end-3 inset-inline-start-3 flex items-center gap-2">
              <span v-if="selectedItem.isPromo"
                    class="px-2 py-0.5 rounded-md bg-orange-500/90 text-white text-[10px] font-bold">
                🔥 {{ selectedItem.promoLabel ?? 'Акция' }}
              </span>
              <span v-if="discountPercent(selectedItem) > 0"
                    class="px-2 py-0.5 rounded-md bg-rose-500/90 text-white text-[10px] font-bold">
                -{{ discountPercent(selectedItem) }}%
              </span>
              <span :class="['px-2 py-0.5 rounded-md text-[10px] font-semibold', STATUS_MAP[selectedItem.status].bg, STATUS_MAP[selectedItem.status].color]">
                {{ STATUS_MAP[selectedItem.status].label }}
              </span>
            </div>
          </div>

          <!-- Scrollable body -->
          <div class="flex-1 overflow-y-auto px-5 py-4 flex flex-col gap-3">

            <!-- Title -->
            <div>
              <h3 class="text-sm font-bold text-(--t-text)">{{ selectedItem.name }}</h3>
              <p class="text-[10px] text-(--t-text-3) mt-0.5">
                {{ selectedItem.categoryName }}
                <template v-if="selectedItem.sku"> · SKU: {{ selectedItem.sku }}</template>
              </p>
            </div>

            <!-- Price block -->
            <div class="rounded-xl border border-(--t-border)/40 bg-(--t-bg)/40 p-3">
              <div class="flex items-baseline gap-2">
                <span class="text-lg font-bold text-(--t-text)">{{ fmtPriceDisplay(selectedItem) }}</span>
                <span v-if="selectedItem.priceOld && selectedItem.priceOld > selectedItem.price"
                      class="text-xs text-(--t-text-3) line-through">{{ fmtCurrency(selectedItem.priceOld) }}</span>
                <span v-if="selectedItem.unit" class="text-xs text-(--t-text-3)">/{{ selectedItem.unit }}</span>
              </div>
              <p v-if="selectedItem.priceB2B && selectedItem.priceB2B < selectedItem.price"
                 class="text-xs text-sky-400 font-medium mt-1">
                💼 Оптовая B2B: {{ fmtCurrency(selectedItem.priceB2B) }}
              </p>
            </div>

            <!-- Stats grid -->
            <div class="grid grid-cols-3 gap-2">
              <div class="rounded-lg bg-(--t-bg)/40 border border-(--t-border)/30 p-2 text-center">
                <span class="text-[10px] text-(--t-text-3)">{{ vc.stockLabel }}</span>
                <p :class="['text-sm font-bold', STOCK_MAP[selectedItem.stockLevel].color]">
                  {{ selectedItem.stockLevel === 'unlimited' ? '♾️' : stockAvailable(selectedItem) }}
                </p>
                <span class="text-[9px] text-(--t-text-3)">{{ STOCK_MAP[selectedItem.stockLevel].label }}</span>
              </div>
              <div class="rounded-lg bg-(--t-bg)/40 border border-(--t-border)/30 p-2 text-center">
                <span class="text-[10px] text-(--t-text-3)">Продажи</span>
                <p class="text-sm font-bold text-emerald-400">{{ fmtNumber(selectedItem.salesCount) }}</p>
              </div>
              <div class="rounded-lg bg-(--t-bg)/40 border border-(--t-border)/30 p-2 text-center">
                <span class="text-[10px] text-(--t-text-3)">Просмотры</span>
                <p class="text-sm font-bold text-sky-400">{{ fmtNumber(selectedItem.views) }}</p>
              </div>
            </div>

            <!-- Rating -->
            <div v-if="selectedItem.rating != null" class="flex items-center gap-2">
              <div class="flex items-center gap-0.5">
                <span v-for="n in 5" :key="n" class="text-sm">
                  {{ n <= Math.round(selectedItem.rating!) ? '⭐' : '☆' }}
                </span>
              </div>
              <span class="text-xs text-(--t-text-2) font-medium">{{ selectedItem.rating!.toFixed(1) }}</span>
              <span v-if="selectedItem.reviewsCount" class="text-[10px] text-(--t-text-3)">
                ({{ selectedItem.reviewsCount }} отзывов)
              </span>
            </div>

            <!-- Description -->
            <div v-if="selectedItem.description">
              <p class="text-[10px] text-(--t-text-3) mb-0.5">Описание</p>
              <p class="text-xs text-(--t-text-2) leading-relaxed">{{ selectedItem.description }}</p>
            </div>

            <!-- Tags -->
            <div v-if="selectedItem.tags.length > 0" class="flex flex-wrap gap-1.5">
              <span v-for="tag in selectedItem.tags" :key="tag"
                    class="text-[10px] px-1.5 py-0.5 rounded-md bg-(--t-bg)/60
                           text-(--t-text-3) border border-(--t-border)/30">
                {{ tag }}
              </span>
            </div>

            <!-- Dates -->
            <div class="flex items-center justify-between text-[10px] text-(--t-text-3)">
              <span>Создано: {{ fmtDate(selectedItem.createdAt) }}</span>
              <span>Обновлено: {{ fmtDate(selectedItem.updatedAt) }}</span>
            </div>
          </div>

          <!-- Footer -->
          <div class="shrink-0 px-5 pb-4 pt-3 border-t border-(--t-border)/30
                      flex flex-col-reverse sm:flex-row items-stretch sm:items-center gap-2 sm:justify-end">
            <button class="relative overflow-hidden px-4 py-2 rounded-xl text-xs font-medium
                           border border-(--t-border) text-(--t-text-2)
                           hover:bg-(--t-surface) hover:text-(--t-text) active:scale-[0.97] transition-all"
                    @click="closeItemModal" @mousedown="ripple">
              Закрыть
            </button>
            <button class="relative overflow-hidden px-4 py-2 rounded-xl text-xs font-medium
                           bg-sky-500/12 text-sky-400 border border-sky-500/20
                           hover:bg-sky-500/20 active:scale-[0.97] transition-all"
                    @click="emit('item-duplicate', selectedItem!); closeItemModal()" @mousedown="ripple">
              📋 Дублировать
            </button>
            <button v-if="selectedItem.status !== 'active'"
                    class="relative overflow-hidden px-4 py-2 rounded-xl text-xs font-semibold
                           bg-emerald-500/12 text-emerald-400 border border-emerald-500/20
                           hover:bg-emerald-500/20 active:scale-[0.97] transition-all"
                    @click="emit('item-toggle', selectedItem!, 'active'); closeItemModal()" @mousedown="ripple">
              ✅ Опубликовать
            </button>
            <button v-if="selectedItem.status === 'active'"
                    class="relative overflow-hidden px-4 py-2 rounded-xl text-xs font-medium
                           bg-amber-500/12 text-amber-400 border border-amber-500/20
                           hover:bg-amber-500/20 active:scale-[0.97] transition-all"
                    @click="emit('item-toggle', selectedItem!, 'hidden'); closeItemModal()" @mousedown="ripple">
              👁️ Скрыть
            </button>
            <button class="relative overflow-hidden px-4 py-2 rounded-xl text-xs font-semibold
                           bg-(--t-primary) text-white hover:brightness-110 active:scale-[0.97]
                           transition-all shadow-sm"
                    @click="emit('item-edit', selectedItem!); closeItemModal()" @mousedown="ripple">
              ✏️ Редактировать
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════════════
         FILTER DRAWER (mobile)
    ══════════════════════════════════════════════ -->
    <Transition name="drawer-ct">
      <div v-if="showFilterDrawer" class="fixed inset-0 z-50 flex justify-end"
           @click.self="showFilterDrawer = false">
        <div class="absolute inset-0 bg-black/40" @click="showFilterDrawer = false" />
        <div class="relative z-10 inline-size-72 max-w-[85vw] bg-(--t-surface) border-s
                    border-(--t-border) h-full overflow-y-auto p-5 flex flex-col gap-4">
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
                  'relative overflow-hidden text-start px-3 py-2 rounded-lg text-xs transition-all',
                  filters.categoryId === '' ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                ]"
                @click="setCategory('')" @mousedown="ripple"
              >
                📦 Все
              </button>
              <button v-for="cat in props.categories" :key="cat.id"
                      :class="[
                        'relative overflow-hidden text-start px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2',
                        filters.categoryId === String(cat.id) ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                      ]"
                      @click="setCategory(String(cat.id))" @mousedown="ripple">
                {{ cat.icon }} {{ cat.name }}
                <span class="ms-auto text-[10px] opacity-60">{{ cat.count }}</span>
              </button>
            </div>
          </div>

          <!-- Statuses -->
          <div>
            <p class="text-xs font-medium text-(--t-text-2) mb-2">Статус</p>
            <div class="flex flex-col gap-1">
              <button v-for="(st, stKey) in STATUS_MAP" :key="stKey"
                      :class="[
                        'relative overflow-hidden flex items-center gap-2 px-3 py-2 rounded-lg text-xs transition-all',
                        filters.status === (stKey as string) ? `${st.bg} ${st.color} font-semibold` : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                      ]"
                      @click="filters.status = filters.status === (stKey as string) ? '' : (stKey as string); emit('filter-change', { ...filters })"
                      @mousedown="ripple">
                <span :class="['w-2 h-2 rounded-full', st.dot]" />
                {{ st.label }}
              </button>
            </div>
          </div>

          <!-- Stock levels -->
          <div>
            <p class="text-xs font-medium text-(--t-text-2) mb-2">{{ vc.stockLabel }}</p>
            <div class="flex flex-col gap-1">
              <button v-for="(sk, skKey) in STOCK_MAP" :key="skKey"
                      :class="[
                        'relative overflow-hidden flex items-center gap-2 px-3 py-2 rounded-lg text-xs transition-all',
                        filters.stockLevel === (skKey as string) ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold' : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                      ]"
                      @click="filters.stockLevel = filters.stockLevel === (skKey as string) ? '' : (skKey as string); emit('filter-change', { ...filters })"
                      @mousedown="ripple">
                {{ sk.icon }} {{ sk.label }}
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

          <!-- Promo toggle -->
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
/* Ripple — unique suffix ct */
@keyframes ripple-ct {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* Slide (bulk bar) */
.slide-ct-enter-active,
.slide-ct-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.slide-ct-enter-from,
.slide-ct-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Sidebar */
.sidebar-ct-enter-active,
.sidebar-ct-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.sidebar-ct-enter-from,
.sidebar-ct-leave-to {
  opacity: 0;
  transform: translateX(-16px);
}

/* Modal */
.modal-ct-enter-active {
  transition: opacity 0.25s ease;
}
.modal-ct-enter-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}
.modal-ct-leave-active {
  transition: opacity 0.2s ease;
}
.modal-ct-leave-active > :last-child {
  transition: transform 0.2s ease-in, opacity 0.2s ease;
}
.modal-ct-enter-from,
.modal-ct-leave-to {
  opacity: 0;
}
.modal-ct-enter-from > :last-child {
  opacity: 0;
  transform: scale(0.92) translateY(12px);
}
.modal-ct-leave-to > :last-child {
  opacity: 0;
  transform: scale(0.95) translateY(6px);
}

/* Drawer */
.drawer-ct-enter-active,
.drawer-ct-leave-active {
  transition: opacity 0.3s ease;
}
.drawer-ct-enter-active > :last-child,
.drawer-ct-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.drawer-ct-enter-from,
.drawer-ct-leave-to {
  opacity: 0;
}
.drawer-ct-enter-from > :last-child,
.drawer-ct-leave-to > :last-child {
  transform: translateX(100%);
}
</style>
