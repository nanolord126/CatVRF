<script setup lang="ts">
/**
 * TenantBookings.vue — Главная страница заказов / бронирований B2B Tenant Dashboard
 *
 * Поддержка всех 127 вертикалей CatVRF:
 *   Beauty (записи к мастерам) · Taxi (поездки) · Food (заказы еды)
 *   Hotels (бронирования) · RealEstate (показы) · Flowers (заказы букетов)
 *   Fashion (заказы одежды) · Furniture (заказы мебели) · Fitness (тренировки)
 *   Travel (бронирования туров) · Medical · Auto · и т.д.
 *
 * ─────────────────────────────────────────────────────────────
 *  Функционал:
 *   1. Верхняя панель: поиск + фильтры (статус, дата, исполнитель, филиал)
 *      + кнопка «Создать заказ» + массовые действия
 *   2. Основной контент: mobile → карточки, desktop → таблица
 *   3. Sidebar с быстрыми фильтрами по статусам + метриками
 *   4. Full-screen режим
 *   5. Детальная модалка заказа (при клике на строку/карточку)
 *   6. Drag & Drop для смены статуса (Kanban-strip)
 *   7. Массовые действия (подтвердить, отменить, экспорт)
 *   8. B2B-специфика: филиалы, корп.клиенты, контрактные заказы
 * ─────────────────────────────────────────────────────────────
 *  Адаптация под вертикаль:
 *   → props.vertical определяет терминологию и колонки
 *   → VERTICAL_BOOKING_CONFIG — маппинг конфигов
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
// TYPES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

type BookingStatus = 'pending' | 'confirmed' | 'in_progress' | 'completed' | 'cancelled' | 'no_show'

interface Booking {
  id:             number | string
  number:         string                   // Номер заказа: #B-12345
  status:         BookingStatus
  clientName:     string
  clientPhone:    string
  clientAvatar?:  string
  isB2B:          boolean
  companyName?:   string
  assigneeName:   string                   // Мастер / Водитель / Курьер
  assigneeAvatar?: string
  branchName?:    string                   // Филиал
  service:        string                   // Стрижка / Эконом / Пицца / Люкс
  datetime:       string                   // ISO — дата и время заказа
  createdAt:      string                   // ISO — дата создания
  amount:         number                   // Сумма ₽
  prepaid:        number                   // Предоплата
  notes?:         string
  tags:           string[]
  rating?:        number
  correlationId?: string
  verticalData?:  Record<string, unknown>  // дополнительные поля для вертикали
}

interface BookingFilter {
  search:     string
  status:     string
  dateFrom:   string
  dateTo:     string
  assignee:   string
  branch:     string
  sortBy:     string
  sortDir:    'asc' | 'desc'
}

interface BookingStats {
  total:       number
  pending:     number
  confirmed:   number
  inProgress:  number
  completed:   number
  cancelled:   number
  noShow:      number
  todayCount:  number
  todayRevenue: number
  avgAmount:   number
}

interface VerticalBookingConfig {
  /** Терминология */
  bookingLabel:       string        // «Запись» / «Поездка» / «Заказ» / «Бронирование»
  bookingLabelPlural: string        // «Записи» / «Поездки» / «Заказы» / «Бронирования»
  icon:               string
  /** Кто исполняет */
  assigneeLabel:      string        // «Мастер» / «Водитель» / «Курьер» / «Менеджер»
  /** Что является услугой */
  serviceLabel:       string        // «Услуга» / «Тариф» / «Блюдо» / «Номер»
  /** Доп. колонки таблицы */
  extraColumns:       Array<{ key: string; label: string }>
  /** Статусы — вертикаль-зависимые метки */
  statusLabels:       Record<BookingStatus, string>
  /** Быстрые действия для тулбара */
  quickCreate:        { label: string; icon: string }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// PROPS & EMITS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const props = withDefaults(defineProps<{
  vertical?:     string
  bookings?:     Booking[]
  stats?:        BookingStats
  assignees?:    Array<{ id: string; name: string }>   // список исполнителей для фильтра
  branches?:     Array<{ id: string; name: string }>   // список филиалов для фильтра
  totalBookings?: number
  loading?:      boolean
  perPage?:      number
}>(), {
  vertical:      'default',
  bookings:      () => [],
  stats:         () => ({
    total: 0, pending: 0, confirmed: 0, inProgress: 0,
    completed: 0, cancelled: 0, noShow: 0,
    todayCount: 0, todayRevenue: 0, avgAmount: 0,
  }),
  assignees:     () => [],
  branches:      () => [],
  totalBookings: 0,
  loading:       false,
  perPage:       25,
})

const emit = defineEmits<{
  'booking-click':    [booking: Booking]
  'booking-create':   []
  'booking-confirm':  [bookingIds: Array<number | string>]
  'booking-cancel':   [bookingIds: Array<number | string>]
  'booking-complete': [bookingIds: Array<number | string>]
  'status-change':    [bookingId: number | string, newStatus: BookingStatus]
  'filter-change':    [filters: BookingFilter]
  'sort-change':      [sortBy: string, sortDir: 'asc' | 'desc']
  'page-change':      [page: number]
  'bulk-action':      [action: string, bookingIds: Array<number | string>]
  'export':           [format: 'xlsx' | 'csv']
  'load-more':        []
}>()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// STORES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const auth     = useAuth()
const business = useTenant()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// VERTICAL CONFIG
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const DEFAULT_STATUS_LABELS: Record<BookingStatus, string> = {
  pending:     '⏳ Ожидает',
  confirmed:   '✅ Подтверждено',
  in_progress: '🔄 В процессе',
  completed:   '✔️ Завершено',
  cancelled:   '❌ Отменено',
  no_show:     '🚫 Не явился',
}

const VERTICAL_BOOKING_CONFIG: Record<string, VerticalBookingConfig> = {
  // ── BEAUTY ──────────────────────────────────
  beauty: {
    bookingLabel: 'Запись', bookingLabelPlural: 'Записи', icon: '💄',
    assigneeLabel: 'Мастер', serviceLabel: 'Услуга',
    extraColumns: [
      { key: 'duration', label: 'Длительность' },
    ],
    statusLabels: {
      pending: '⏳ Ожидает', confirmed: '✅ Подтверждена',
      in_progress: '💇 В процессе', completed: '✔️ Завершена',
      cancelled: '❌ Отменена', no_show: '🚫 Не явился',
    },
    quickCreate: { label: 'Новая запись', icon: '📅' },
  },

  // ── TAXI ────────────────────────────────────
  taxi: {
    bookingLabel: 'Поездка', bookingLabelPlural: 'Поездки', icon: '🚕',
    assigneeLabel: 'Водитель', serviceLabel: 'Тариф',
    extraColumns: [
      { key: 'route', label: 'Маршрут' },
      { key: 'distance', label: 'Расстояние' },
    ],
    statusLabels: {
      pending: '⏳ Поиск авто', confirmed: '✅ Назначен',
      in_progress: '🚗 В пути', completed: '✔️ Завершена',
      cancelled: '❌ Отменена', no_show: '🚫 Не вышел',
    },
    quickCreate: { label: 'Новая поездка', icon: '🚗' },
  },

  // ── FOOD ────────────────────────────────────
  food: {
    bookingLabel: 'Заказ', bookingLabelPlural: 'Заказы', icon: '🍽️',
    assigneeLabel: 'Курьер', serviceLabel: 'Блюдо',
    extraColumns: [
      { key: 'items', label: 'Позиций' },
      { key: 'deliveryTime', label: 'Время доставки' },
    ],
    statusLabels: {
      pending: '⏳ Новый', confirmed: '✅ Принят',
      in_progress: '🍳 Готовится', completed: '✔️ Доставлен',
      cancelled: '❌ Отменён', no_show: '🚫 Не получен',
    },
    quickCreate: { label: 'Новый заказ', icon: '🛒' },
  },

  // ── HOTEL ───────────────────────────────────
  hotel: {
    bookingLabel: 'Бронирование', bookingLabelPlural: 'Бронирования', icon: '🏨',
    assigneeLabel: 'Менеджер', serviceLabel: 'Номер',
    extraColumns: [
      { key: 'checkIn', label: 'Заезд' },
      { key: 'checkOut', label: 'Выезд' },
      { key: 'guests', label: 'Гостей' },
    ],
    statusLabels: {
      pending: '⏳ Ожидает', confirmed: '✅ Подтверждено',
      in_progress: '🏠 Проживает', completed: '✔️ Выехал',
      cancelled: '❌ Отменено', no_show: '🚫 Не заехал',
    },
    quickCreate: { label: 'Новое бронирование', icon: '🛏️' },
  },

  // ── REAL ESTATE ─────────────────────────────
  realEstate: {
    bookingLabel: 'Показ', bookingLabelPlural: 'Показы', icon: '🏢',
    assigneeLabel: 'Агент', serviceLabel: 'Объект',
    extraColumns: [
      { key: 'propertyType', label: 'Тип' },
      { key: 'area', label: 'Площадь' },
    ],
    statusLabels: {
      pending: '⏳ Запланирован', confirmed: '✅ Подтверждён',
      in_progress: '🔑 Идёт показ', completed: '✔️ Проведён',
      cancelled: '❌ Отменён', no_show: '🚫 Не явился',
    },
    quickCreate: { label: 'Новый показ', icon: '🏠' },
  },

  // ── FLOWERS ─────────────────────────────────
  flowers: {
    bookingLabel: 'Заказ', bookingLabelPlural: 'Заказы', icon: '💐',
    assigneeLabel: 'Флорист', serviceLabel: 'Букет',
    extraColumns: [
      { key: 'deliveryAddress', label: 'Адрес доставки' },
    ],
    statusLabels: {
      pending: '⏳ Новый', confirmed: '✅ Принят',
      in_progress: '💐 Собирается', completed: '✔️ Доставлен',
      cancelled: '❌ Отменён', no_show: '🚫 Не получен',
    },
    quickCreate: { label: 'Новый заказ', icon: '💐' },
  },

  // ── FASHION ─────────────────────────────────
  fashion: {
    bookingLabel: 'Заказ', bookingLabelPlural: 'Заказы', icon: '👗',
    assigneeLabel: 'Стилист', serviceLabel: 'Товар',
    extraColumns: [
      { key: 'size', label: 'Размер' },
      { key: 'itemsCount', label: 'Позиций' },
    ],
    statusLabels: {
      pending: '⏳ Новый', confirmed: '✅ Оформлен',
      in_progress: '📦 Комплектуется', completed: '✔️ Доставлен',
      cancelled: '❌ Отменён', no_show: '🚫 Не получен',
    },
    quickCreate: { label: 'Новый заказ', icon: '🛍️' },
  },

  // ── FURNITURE ───────────────────────────────
  furniture: {
    bookingLabel: 'Заказ', bookingLabelPlural: 'Заказы', icon: '🛋️',
    assigneeLabel: 'Менеджер', serviceLabel: 'Товар',
    extraColumns: [
      { key: 'deliveryDate', label: 'Дата доставки' },
      { key: 'assemblyIncluded', label: 'Сборка' },
    ],
    statusLabels: {
      pending: '⏳ Новый', confirmed: '✅ Подтверждён',
      in_progress: '🚚 В доставке', completed: '✔️ Доставлен',
      cancelled: '❌ Отменён', no_show: '🚫 Не получен',
    },
    quickCreate: { label: 'Новый заказ', icon: '📦' },
  },

  // ── FITNESS ─────────────────────────────────
  fitness: {
    bookingLabel: 'Тренировка', bookingLabelPlural: 'Тренировки', icon: '💪',
    assigneeLabel: 'Тренер', serviceLabel: 'Программа',
    extraColumns: [
      { key: 'duration', label: 'Длительность' },
      { key: 'type', label: 'Тип' },
    ],
    statusLabels: {
      pending: '⏳ Запланирована', confirmed: '✅ Подтверждена',
      in_progress: '🏋️ Идёт', completed: '✔️ Завершена',
      cancelled: '❌ Отменена', no_show: '🚫 Не пришёл',
    },
    quickCreate: { label: 'Новая тренировка', icon: '🏋️' },
  },

  // ── TRAVEL ──────────────────────────────────
  travel: {
    bookingLabel: 'Бронирование', bookingLabelPlural: 'Бронирования', icon: '✈️',
    assigneeLabel: 'Менеджер', serviceLabel: 'Тур',
    extraColumns: [
      { key: 'destination', label: 'Направление' },
      { key: 'travelers', label: 'Туристов' },
    ],
    statusLabels: {
      pending: '⏳ Ожидает', confirmed: '✅ Подтверждено',
      in_progress: '✈️ В поездке', completed: '✔️ Завершено',
      cancelled: '❌ Отменено', no_show: '🚫 Не явился',
    },
    quickCreate: { label: 'Новое бронирование', icon: '🗺️' },
  },

  // ── DEFAULT ─────────────────────────────────
  default: {
    bookingLabel: 'Заказ', bookingLabelPlural: 'Заказы', icon: '📋',
    assigneeLabel: 'Исполнитель', serviceLabel: 'Услуга',
    extraColumns: [],
    statusLabels: { ...DEFAULT_STATUS_LABELS },
    quickCreate: { label: 'Новый заказ', icon: '➕' },
  },
}

const vb = computed<VerticalBookingConfig>(() =>
  VERTICAL_BOOKING_CONFIG[props.vertical] ?? VERTICAL_BOOKING_CONFIG.default
)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// STATUS CONFIG
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

interface StatusMeta {
  color:   string   // Tailwind border/bg suffix
  badge:   string   // VBadge variant
  glow:    string   // Shadow glow color
}

const STATUS_META: Record<BookingStatus, StatusMeta> = {
  pending:     { color: 'amber',   badge: 'warning', glow: 'rgba(245,158,11,.12)' },
  confirmed:   { color: 'emerald', badge: 'success', glow: 'rgba(16,185,129,.12)' },
  in_progress: { color: 'sky',     badge: 'info',    glow: 'rgba(14,165,233,.12)' },
  completed:   { color: 'green',   badge: 'success', glow: 'rgba(34,197,94,.12)' },
  cancelled:   { color: 'rose',    badge: 'danger',  glow: 'rgba(244,63,94,.12)' },
  no_show:     { color: 'zinc',    badge: 'neutral', glow: 'rgba(161,161,170,.08)' },
}

const ALL_STATUSES: BookingStatus[] = [
  'pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show',
]

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// STATE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const rootEl          = ref<HTMLElement | null>(null)
const isFullscreen    = ref(false)
const showSidebar     = ref(true)
const showDetailModal = ref(false)
const showCreateHint  = ref(false)
const showExportMenu  = ref(false)
const showFilterDrawer = ref(false)
const selectedBooking = ref<Booking | null>(null)
const currentPage     = ref(1)

// Bulk selection
const selectedIds = reactive<Set<number | string>>(new Set())
const isBulkMode  = ref(false)
const selectAll   = ref(false)

// View mode: table vs cards (auto on mobile)
const viewAs = ref<'table' | 'cards'>('table')

// Kanban strip D&D
const dragBookingId  = ref<number | string | null>(null)
const dragOverStatus = ref<BookingStatus | null>(null)

// Filters
const filters = reactive<BookingFilter>({
  search:   '',
  status:   '',
  dateFrom: '',
  dateTo:   '',
  assignee: '',
  branch:   '',
  sortBy:   'datetime',
  sortDir:  'desc',
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// COMPUTED
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const totalPages = computed(() => Math.ceil(props.totalBookings / props.perPage) || 1)

const hasActiveFilters = computed(() =>
  filters.status !== '' ||
  filters.dateFrom !== '' ||
  filters.dateTo !== '' ||
  filters.assignee !== '' ||
  filters.branch !== ''
)

const statusCountMap = computed<Record<BookingStatus, number>>(() => ({
  pending:     props.stats.pending,
  confirmed:   props.stats.confirmed,
  in_progress: props.stats.inProgress,
  completed:   props.stats.completed,
  cancelled:   props.stats.cancelled,
  no_show:     props.stats.noShow,
}))

// Visible pages for pagination
const visiblePages = computed(() => {
  const total   = totalPages.value
  const current = currentPage.value
  const pages: (number | '...')[] = []
  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 3) pages.push('...')
    const start = Math.max(2, current - 1)
    const end   = Math.min(total - 1, current + 1)
    for (let i = start; i <= end; i++) pages.push(i)
    if (current < total - 2) pages.push('...')
    pages.push(total)
  }
  return pages
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// FILTER / SORT / PAGINATION
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function applyFilter(key: keyof BookingFilter, value: unknown) {
  ;(filters as Record<string, unknown>)[key] = value
  currentPage.value = 1
  emitFilters()
}

function clearAllFilters() {
  filters.search   = ''
  filters.status   = ''
  filters.dateFrom = ''
  filters.dateTo   = ''
  filters.assignee = ''
  filters.branch   = ''
  currentPage.value = 1
  emitFilters()
}

function emitFilters() {
  emit('filter-change', { ...filters })
}

let searchTimer: ReturnType<typeof setTimeout> | null = null
watch(() => filters.search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => { currentPage.value = 1; emitFilters() }, 350)
})

function toggleSort(key: string) {
  if (filters.sortBy === key) {
    filters.sortDir = filters.sortDir === 'asc' ? 'desc' : 'asc'
  } else {
    filters.sortBy = key
    filters.sortDir = 'desc'
  }
  emit('sort-change', filters.sortBy, filters.sortDir)
}

function sortIcon(key: string): string {
  if (filters.sortBy !== key) return '↕'
  return filters.sortDir === 'asc' ? '↑' : '↓'
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  emit('page-change', page)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// SELECTION / BULK
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function toggleSelect(id: number | string) {
  if (selectedIds.has(id)) selectedIds.delete(id)
  else selectedIds.add(id)
}

function toggleSelectAll() {
  if (selectAll.value) {
    selectedIds.clear()
    selectAll.value = false
  } else {
    props.bookings.forEach(b => selectedIds.add(b.id))
    selectAll.value = true
  }
}

function handleBulkAction(action: string) {
  const ids = Array.from(selectedIds)
  if (action === 'confirm')  emit('booking-confirm', ids)
  else if (action === 'cancel') emit('booking-cancel', ids)
  else if (action === 'complete') emit('booking-complete', ids)
  else emit('bulk-action', action, ids)
  selectedIds.clear()
  selectAll.value = false
  isBulkMode.value = false
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// BOOKING DETAIL MODAL
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function openBooking(booking: Booking) {
  if (isBulkMode.value) { toggleSelect(booking.id); return }
  selectedBooking.value = booking
  showDetailModal.value = true
  emit('booking-click', booking)
}

function closeDetailModal() {
  showDetailModal.value = false
  selectedBooking.value = null
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// DRAG & DROP (Kanban strip status change)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function onDragStart(bookingId: number | string) {
  dragBookingId.value = bookingId
}

function onDragOver(e: DragEvent, status: BookingStatus) {
  e.preventDefault()
  dragOverStatus.value = status
}

function onDragLeave() {
  dragOverStatus.value = null
}

function onDrop(status: BookingStatus) {
  if (dragBookingId.value !== null) {
    emit('status-change', dragBookingId.value, status)
  }
  dragBookingId.value  = null
  dragOverStatus.value = null
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// FULLSCREEN
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    rootEl.value?.requestFullscreen?.()
    isFullscreen.value = true
  } else {
    document.exitFullscreen?.()
    isFullscreen.value = false
  }
}

function onFullscreenChange() {
  isFullscreen.value = !!document.fullscreenElement
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// FORMATTERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function fmtMoney(n: number): string {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M ₽'
  if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K ₽'
  return n.toLocaleString('ru-RU') + ' ₽'
}

function fmtNum(n: number): string {
  return n.toLocaleString('ru-RU')
}

function fmtDate(iso: string): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })
}

function fmtDatetime(iso: string): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('ru-RU', {
    day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
  })
}

function fmtDateFull(iso: string): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('ru-RU', {
    day: 'numeric', month: 'long', year: 'numeric',
  })
}

function relativeTime(iso: string): string {
  if (!iso) return '—'
  const diff = Math.floor((new Date(iso).getTime() - Date.now()) / 60_000)
  if (diff > 0 && diff < 60) return `через ${diff} мин`
  if (diff >= 60 && diff < 1440) return `через ${Math.floor(diff / 60)} ч`
  if (diff < 0) {
    const abs = Math.abs(diff)
    if (abs < 60) return `${abs} мин назад`
    if (abs < 1440) return `${Math.floor(abs / 60)} ч назад`
    return fmtDate(iso)
  }
  return fmtDate(iso)
}

function avatarInitials(name: string): string {
  return name.split(' ').slice(0, 2).map(w => w.charAt(0)).join('').toUpperCase()
}

const AVATAR_COLORS = [
  '#6366f1', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6',
  '#ec4899', '#14b8a6', '#f97316', '#06b6d4', '#84cc16',
]

function avatarColor(id: number | string): string {
  const idx = typeof id === 'number' ? id : String(id).charCodeAt(0)
  return AVATAR_COLORS[idx % AVATAR_COLORS.length]
}

function statusLabel(s: BookingStatus): string {
  return vb.value.statusLabels[s] ?? DEFAULT_STATUS_LABELS[s] ?? s
}

function statusBorderClass(s: BookingStatus): string {
  const c = STATUS_META[s]?.color ?? 'zinc'
  return `border-${c}-500/30`
}

function starRating(r?: number): string {
  if (!r) return ''
  return '★'.repeat(Math.round(r)) + '☆'.repeat(5 - Math.round(r))
}

// Ripple effect
function ripple(e: MouseEvent) {
  const target = e.currentTarget as HTMLElement
  const rect = target.getBoundingClientRect()
  const diameter = Math.max(rect.width, rect.height) * 2
  const el = document.createElement('span')
  el.className = 'absolute rounded-full bg-white/10 pointer-events-none animate-[ripple-bk_0.6s_ease-out]'
  el.style.cssText = `inline-size:${diameter}px;block-size:${diameter}px;inset-inline-start:${e.clientX - rect.left - diameter / 2}px;inset-block-start:${e.clientY - rect.top - diameter / 2}px;`
  target.appendChild(el)
  setTimeout(() => el.remove(), 650)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// LIFECYCLE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

onMounted(() => {
  document.addEventListener('fullscreenchange', onFullscreenChange)
})

onBeforeUnmount(() => {
  document.removeEventListener('fullscreenchange', onFullscreenChange)
  if (searchTimer) clearTimeout(searchTimer)
})
</script>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     TEMPLATE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<template>
  <div
    ref="rootEl"
    :class="[
      'flex flex-col gap-4',
      isFullscreen ? 'fixed inset-0 z-90 bg-(--t-bg) p-4 overflow-auto' : '',
    ]"
  >
    <!-- ═══════════════════════════════════════════════
         1. HEADER: TITLE + TODAY STATS
    ═══════════════════════════════════════════════ -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-3">
        <span class="text-2xl">{{ vb.icon }}</span>
        <div>
          <h1 class="text-xl font-bold text-(--t-text)">{{ vb.bookingLabelPlural }}</h1>
          <p class="text-xs text-(--t-text-3)">
            {{ auth.tenantName }} · Сегодня: <b class="text-(--t-text)">{{ fmtNum(props.stats.todayCount) }}</b>
            · Выручка: <b class="text-emerald-400">{{ fmtMoney(props.stats.todayRevenue) }}</b>
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <!-- Create booking -->
        <VButton variant="primary" size="sm" @click="emit('booking-create')">
          {{ vb.quickCreate.icon }} {{ vb.quickCreate.label }}
        </VButton>
        <!-- Fullscreen -->
        <button
          class="w-9 h-9 rounded-xl flex items-center justify-center
                 bg-(--t-surface) border border-(--t-border) text-(--t-text-2)
                 hover:text-(--t-text) hover:border-(--t-primary)/40
                 transition-all duration-200 active:scale-95"
          @click="toggleFullscreen"
        >
          <svg v-if="!isFullscreen" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
          </svg>
          <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M9 9V4m0 5H4m0 0l5-5m6 5h5m0 0l-5-5m0 5V4M9 15v5m0-5H4m0 0l5 5m6-5h5m0 0l-5 5m5-5v5" />
          </svg>
        </button>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════
         2. KANBAN STATUS STRIP (D&D targets)
    ═══════════════════════════════════════════════ -->
    <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
      <button
        v-for="st in ALL_STATUSES"
        :key="st"
        :class="[
          'relative overflow-hidden rounded-xl border p-3 text-center transition-all duration-200',
          'cursor-pointer group/kst',
          filters.status === st
            ? `border-${STATUS_META[st].color}-500/40 bg-${STATUS_META[st].color}-500/10`
            : 'border-(--t-border) bg-(--t-surface) hover:border-(--t-primary)/20',
          dragOverStatus === st ? 'ring-2 ring-(--t-primary) scale-[1.02]' : '',
        ]"
        @click="applyFilter('status', filters.status === st ? '' : st)"
        @dragover="onDragOver($event, st)"
        @dragleave="onDragLeave"
        @drop="onDrop(st)"
      >
        <div class="text-lg leading-none">{{ statusLabel(st).split(' ')[0] }}</div>
        <div class="text-xl font-black text-(--t-text) mt-1">{{ statusCountMap[st] }}</div>
        <div class="text-[10px] text-(--t-text-3) mt-0.5 truncate">
          {{ statusLabel(st).split(' ').slice(1).join(' ') }}
        </div>
      </button>
    </div>

    <!-- ═══════════════════════════════════════════════
         3. TOOLBAR: SEARCH + FILTERS + ACTIONS
    ═══════════════════════════════════════════════ -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <!-- Search -->
      <div class="relative flex-1 max-w-md">
        <input
          v-model="filters.search"
          type="text"
          :placeholder="`Поиск: клиент, номер, ${vb.assigneeLabel.toLowerCase()}...`"
          class="w-full h-10 rounded-xl pl-10 pr-4 text-sm bg-(--t-surface) border border-(--t-border)
                 text-(--t-text) placeholder:text-(--t-text-3)
                 focus:outline-none focus:border-(--t-primary)/60
                 focus:shadow-[0_0_20px_var(--t-glow)]
                 transition-all duration-200"
        />
        <svg class="absolute inset-inline-start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-(--t-text-3)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <button
          v-if="filters.search"
          class="absolute inset-inline-end-3 top-1/2 -translate-y-1/2 w-5 h-5 rounded-full
                 flex items-center justify-center text-xs text-(--t-text-3) hover:text-(--t-text)
                 hover:bg-(--t-card-hover) transition-all"
          @click="filters.search = ''"
        >
          ✕
        </button>
      </div>

      <div class="flex items-center gap-2 flex-wrap">
        <!-- Date range -->
        <div class="flex items-center gap-1">
          <input
            v-model="filters.dateFrom"
            type="date"
            class="h-9 rounded-xl px-2 text-xs bg-(--t-surface) border border-(--t-border)
                   text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
            @change="emitFilters"
          />
          <span class="text-(--t-text-3) text-xs">—</span>
          <input
            v-model="filters.dateTo"
            type="date"
            class="h-9 rounded-xl px-2 text-xs bg-(--t-surface) border border-(--t-border)
                   text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
            @change="emitFilters"
          />
        </div>

        <!-- Assignee filter -->
        <select
          v-if="props.assignees.length"
          v-model="filters.assignee"
          class="h-9 rounded-xl px-2 text-xs bg-(--t-surface) border border-(--t-border)
                 text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
          @change="emitFilters"
        >
          <option value="">{{ vb.assigneeLabel }}: все</option>
          <option v-for="a in props.assignees" :key="a.id" :value="a.id">{{ a.name }}</option>
        </select>

        <!-- Branch filter (B2B) -->
        <select
          v-if="auth.isB2BMode && props.branches.length"
          v-model="filters.branch"
          class="h-9 rounded-xl px-2 text-xs bg-(--t-surface) border border-(--t-border)
                 text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
          @change="emitFilters"
        >
          <option value="">Филиал: все</option>
          <option v-for="br in props.branches" :key="br.id" :value="br.id">{{ br.name }}</option>
        </select>

        <!-- View toggle (desktop) -->
        <div class="hidden sm:flex items-center rounded-xl border border-(--t-border) overflow-hidden">
          <button
            :class="['px-2.5 py-1.5 text-xs transition-all duration-150',
              viewAs === 'table' ? 'bg-(--t-primary)/20 text-(--t-primary)' : 'text-(--t-text-3) hover:text-(--t-text)']"
            @click="viewAs = 'table'"
          >☰</button>
          <button
            :class="['px-2.5 py-1.5 text-xs transition-all duration-150',
              viewAs === 'cards' ? 'bg-(--t-primary)/20 text-(--t-primary)' : 'text-(--t-text-3) hover:text-(--t-text)']"
            @click="viewAs = 'cards'"
          >▦</button>
        </div>

        <!-- Bulk toggle -->
        <VButton
          :variant="isBulkMode ? 'danger' : 'ghost'"
          size="sm"
          @click="isBulkMode = !isBulkMode; if (!isBulkMode) { selectedIds.clear(); selectAll = false }"
        >
          {{ isBulkMode ? `✓ ${selectedIds.size}` : '☑️' }}
        </VButton>

        <!-- Export -->
        <div class="relative">
          <VButton variant="ghost" size="sm" @click="showExportMenu = !showExportMenu">📥</VButton>
          <Transition name="dropdown-bk">
            <div
              v-if="showExportMenu"
              class="absolute inset-inline-end-0 top-full mt-1 z-30 rounded-xl border border-(--t-border)
                     bg-(--t-surface) backdrop-blur-xl shadow-xl overflow-hidden"
              style="min-inline-size: 150px"
            >
              <button
                class="w-full px-4 py-2.5 text-xs text-(--t-text) hover:bg-(--t-card-hover)
                       transition-colors text-start"
                @click="emit('export', 'xlsx'); showExportMenu = false"
              >📊 Excel</button>
              <button
                class="w-full px-4 py-2.5 text-xs text-(--t-text) hover:bg-(--t-card-hover)
                       transition-colors text-start"
                @click="emit('export', 'csv'); showExportMenu = false"
              >📄 CSV</button>
            </div>
          </Transition>
        </div>

        <!-- Clear filters -->
        <VButton v-if="hasActiveFilters" variant="ghost" size="sm" @click="clearAllFilters">
          ✕ Сбросить
        </VButton>

        <!-- Sidebar toggle (desktop) -->
        <button
          class="hidden lg:flex w-9 h-9 rounded-xl items-center justify-center
                 bg-(--t-surface) border border-(--t-border) text-(--t-text-2)
                 hover:text-(--t-text) hover:border-(--t-primary)/40
                 transition-all duration-200 active:scale-95"
          @click="showSidebar = !showSidebar"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Bulk action bar -->
    <Transition name="slide-bk">
      <div
        v-if="isBulkMode && selectedIds.size > 0"
        class="flex items-center gap-3 rounded-xl border border-(--t-primary)/30 bg-(--t-primary)/5
               backdrop-blur-xl p-3"
      >
        <span class="text-sm text-(--t-text)">
          Выбрано: <b class="text-(--t-primary)">{{ selectedIds.size }}</b>
        </span>
        <VButton variant="success" size="xs" @click="handleBulkAction('confirm')">✅ Подтвердить</VButton>
        <VButton variant="danger" size="xs" @click="handleBulkAction('cancel')">❌ Отменить</VButton>
        <VButton variant="ghost" size="xs" @click="handleBulkAction('complete')">✔️ Завершить</VButton>
        <div class="flex-1" />
        <VButton variant="ghost" size="xs" @click="selectedIds.clear(); selectAll = false">Снять выделение</VButton>
      </div>
    </Transition>

    <!-- ═══════════════════════════════════════════════
         4. MAIN CONTENT: SIDEBAR + TABLE/CARDS
    ═══════════════════════════════════════════════ -->
    <div class="flex gap-4">

      <!-- ─── SIDEBAR (desktop) ─── -->
      <Transition name="sidebar-bk">
        <aside
          v-if="showSidebar"
          class="hidden lg:flex flex-col gap-3 shrink-0"
          style="inline-size: 220px"
        >
          <!-- Stats mini-card -->
          <div class="rounded-xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-4">
            <div class="text-xs font-semibold text-(--t-text-2) mb-3">📊 Сводка</div>
            <div class="flex flex-col gap-2 text-xs">
              <div class="flex justify-between">
                <span class="text-(--t-text-3)">Всего</span>
                <span class="font-bold text-(--t-text)">{{ fmtNum(props.stats.total) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-(--t-text-3)">Ср. чек</span>
                <span class="font-bold text-emerald-400">{{ fmtMoney(props.stats.avgAmount) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-(--t-text-3)">Сегодня</span>
                <span class="font-bold text-(--t-text)">{{ fmtNum(props.stats.todayCount) }}</span>
              </div>
            </div>
          </div>

          <!-- Status quick filters -->
          <div class="rounded-xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-4">
            <div class="text-xs font-semibold text-(--t-text-2) mb-3">Статусы</div>
            <div class="flex flex-col gap-1">
              <button
                :class="[
                  'flex items-center justify-between rounded-lg px-3 py-2 text-xs transition-all duration-200',
                  filters.status === ''
                    ? 'bg-(--t-primary)/10 text-(--t-primary)'
                    : 'text-(--t-text-3) hover:bg-(--t-card-hover) hover:text-(--t-text)',
                ]"
                @click="applyFilter('status', '')"
              >
                <span>Все</span>
                <span class="font-bold">{{ fmtNum(props.stats.total) }}</span>
              </button>
              <button
                v-for="st in ALL_STATUSES"
                :key="st"
                :class="[
                  'flex items-center justify-between rounded-lg px-3 py-2 text-xs transition-all duration-200',
                  filters.status === st
                    ? `bg-${STATUS_META[st].color}-500/10 text-${STATUS_META[st].color}-400`
                    : 'text-(--t-text-3) hover:bg-(--t-card-hover) hover:text-(--t-text)',
                ]"
                @click="applyFilter('status', filters.status === st ? '' : st)"
              >
                <span>{{ statusLabel(st) }}</span>
                <span class="font-bold">{{ statusCountMap[st] }}</span>
              </button>
            </div>
          </div>

          <!-- Assignees quick list -->
          <div v-if="props.assignees.length" class="rounded-xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-4">
            <div class="text-xs font-semibold text-(--t-text-2) mb-3">{{ vb.assigneeLabel }}</div>
            <div class="flex flex-col gap-1 max-h-48 overflow-auto">
              <button
                v-for="a in props.assignees"
                :key="a.id"
                :class="[
                  'flex items-center gap-2 rounded-lg px-3 py-2 text-xs transition-all duration-200',
                  filters.assignee === a.id
                    ? 'bg-(--t-primary)/10 text-(--t-primary)'
                    : 'text-(--t-text-3) hover:bg-(--t-card-hover) hover:text-(--t-text)',
                ]"
                @click="applyFilter('assignee', filters.assignee === a.id ? '' : a.id)"
              >
                <div
                  class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0"
                  :style="{ backgroundColor: avatarColor(a.id) }"
                >
                  {{ avatarInitials(a.name) }}
                </div>
                <span class="truncate">{{ a.name }}</span>
              </button>
            </div>
          </div>
        </aside>
      </Transition>

      <!-- ─── MAIN AREA ─── -->
      <div class="flex-1 min-w-0">

        <!-- ═══ DESKTOP TABLE ═══ -->
        <div v-if="viewAs === 'table'" class="hidden sm:block overflow-auto rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-(--t-border)">
                <!-- Checkbox -->
                <th v-if="isBulkMode" class="p-3 w-10">
                  <input type="checkbox" :checked="selectAll" @change="toggleSelectAll"
                    class="accent-(--t-primary)" />
                </th>
                <th class="p-3 text-start text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold cursor-pointer hover:text-(--t-text) transition-colors"
                    @click="toggleSort('number')">
                  № {{ sortIcon('number') }}
                </th>
                <th class="p-3 text-start text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold">Клиент</th>
                <th class="p-3 text-start text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold">{{ vb.serviceLabel }}</th>
                <th class="p-3 text-start text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold">{{ vb.assigneeLabel }}</th>
                <th class="p-3 text-start text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold cursor-pointer hover:text-(--t-text) transition-colors"
                    @click="toggleSort('datetime')">
                  Дата/Время {{ sortIcon('datetime') }}
                </th>
                <th class="p-3 text-end text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold cursor-pointer hover:text-(--t-text) transition-colors"
                    @click="toggleSort('amount')">
                  Сумма {{ sortIcon('amount') }}
                </th>
                <th class="p-3 text-center text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold">Статус</th>
                <!-- Extra columns per vertical -->
                <th
                  v-for="col in vb.extraColumns"
                  :key="col.key"
                  class="p-3 text-start text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold"
                >
                  {{ col.label }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="bk in props.bookings"
                :key="bk.id"
                draggable="true"
                :class="[
                  'border-b border-(--t-border)/50 transition-all duration-150 cursor-pointer',
                  'hover:bg-(--t-card-hover) group/row',
                  selectedIds.has(bk.id) ? 'bg-(--t-primary)/5' : '',
                ]"
                @click="openBooking(bk)"
                @dragstart="onDragStart(bk.id)"
              >
                <!-- Checkbox -->
                <td v-if="isBulkMode" class="p-3" @click.stop>
                  <input type="checkbox" :checked="selectedIds.has(bk.id)" @change="toggleSelect(bk.id)"
                    class="accent-(--t-primary)" />
                </td>
                <!-- Number -->
                <td class="p-3 font-mono text-xs text-(--t-text-2) whitespace-nowrap">{{ bk.number }}</td>
                <!-- Client -->
                <td class="p-3">
                  <div class="flex items-center gap-2">
                    <div
                      class="w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0"
                      :style="{ backgroundColor: avatarColor(bk.id) }"
                    >
                      {{ bk.clientAvatar ? '' : avatarInitials(bk.clientName) }}
                      <img v-if="bk.clientAvatar" :src="bk.clientAvatar" class="w-full h-full rounded-full object-cover" />
                    </div>
                    <div class="min-w-0">
                      <div class="text-sm font-medium text-(--t-text) truncate">{{ bk.clientName }}</div>
                      <div class="text-[10px] text-(--t-text-3)">
                        {{ bk.clientPhone }}
                        <VBadge v-if="bk.isB2B" text="B2B" variant="b2b" size="xs" class="ml-1" />
                      </div>
                    </div>
                  </div>
                </td>
                <!-- Service -->
                <td class="p-3 text-sm text-(--t-text-2) truncate max-w-35">{{ bk.service }}</td>
                <!-- Assignee -->
                <td class="p-3">
                  <div class="flex items-center gap-2">
                    <div
                      class="w-6 h-6 rounded-full flex items-center justify-center text-[9px] font-bold text-white shrink-0"
                      :style="{ backgroundColor: avatarColor(bk.assigneeName) }"
                    >
                      {{ avatarInitials(bk.assigneeName) }}
                    </div>
                    <span class="text-xs text-(--t-text-2) truncate">{{ bk.assigneeName }}</span>
                  </div>
                </td>
                <!-- Datetime -->
                <td class="p-3 text-xs text-(--t-text-2) whitespace-nowrap">
                  {{ fmtDatetime(bk.datetime) }}
                </td>
                <!-- Amount -->
                <td class="p-3 text-end font-semibold text-sm tabular-nums text-emerald-400 whitespace-nowrap">
                  {{ fmtMoney(bk.amount) }}
                </td>
                <!-- Status -->
                <td class="p-3 text-center">
                  <VBadge
                    :text="statusLabel(bk.status).replace(/^\S+\s/, '')"
                    :variant="STATUS_META[bk.status]?.badge ?? 'neutral'"
                    size="xs"
                  />
                </td>
                <!-- Extra columns -->
                <td
                  v-for="col in vb.extraColumns"
                  :key="col.key"
                  class="p-3 text-xs text-(--t-text-3)"
                >
                  {{ bk.verticalData?.[col.key] ?? '—' }}
                </td>
              </tr>

              <!-- Empty state -->
              <tr v-if="!props.bookings.length && !props.loading">
                <td :colspan="8 + (isBulkMode ? 1 : 0) + vb.extraColumns.length" class="p-12 text-center">
                  <div class="text-4xl mb-3">{{ vb.icon }}</div>
                  <div class="text-sm text-(--t-text-3)">{{ vb.bookingLabelPlural }} не найдены</div>
                  <VButton variant="primary" size="sm" class="mt-4" @click="emit('booking-create')">
                    {{ vb.quickCreate.icon }} {{ vb.quickCreate.label }}
                  </VButton>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ═══ MOBILE CARDS (+ cards view on desktop) ═══ -->
        <div
          :class="[
            'flex flex-col gap-3',
            viewAs === 'table' ? 'sm:hidden' : '',
          ]"
        >
          <div
            v-for="bk in props.bookings"
            :key="bk.id"
            draggable="true"
            :class="[
              'relative overflow-hidden rounded-2xl border p-4 transition-all duration-200',
              'bg-(--t-surface) backdrop-blur-xl cursor-pointer',
              'hover:border-(--t-primary)/30 hover:shadow-[0_0_20px_var(--t-glow)]',
              'active:scale-[0.99] group/card',
              selectedIds.has(bk.id) ? 'border-(--t-primary)/40 bg-(--t-primary)/5' : `border-(--t-border) ${statusBorderClass(bk.status)}`,
            ]"
            @click="openBooking(bk)"
            @dragstart="onDragStart(bk.id)"
          >
            <!-- Bulk checkbox -->
            <input
              v-if="isBulkMode"
              type="checkbox"
              :checked="selectedIds.has(bk.id)"
              class="absolute inset-block-start-3 inset-inline-end-3 accent-(--t-primary) z-10"
              @click.stop
              @change="toggleSelect(bk.id)"
            />

            <!-- Row 1: Number + Status -->
            <div class="flex items-center justify-between mb-3">
              <span class="font-mono text-xs text-(--t-text-3)">{{ bk.number }}</span>
              <VBadge
                :text="statusLabel(bk.status)"
                :variant="STATUS_META[bk.status]?.badge ?? 'neutral'"
                size="xs"
              />
            </div>

            <!-- Row 2: Client -->
            <div class="flex items-center gap-3 mb-3">
              <div
                class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0"
                :style="{ backgroundColor: avatarColor(bk.id) }"
              >
                {{ avatarInitials(bk.clientName) }}
              </div>
              <div class="min-w-0 flex-1">
                <div class="font-medium text-sm text-(--t-text) truncate">{{ bk.clientName }}</div>
                <div class="text-[11px] text-(--t-text-3)">
                  {{ bk.clientPhone }}
                  <VBadge v-if="bk.isB2B" text="B2B" variant="b2b" size="xs" class="ml-1" />
                </div>
              </div>
              <div class="text-end shrink-0">
                <div class="text-sm font-bold text-emerald-400">{{ fmtMoney(bk.amount) }}</div>
                <div v-if="bk.prepaid > 0" class="text-[10px] text-(--t-text-3)">
                  Предоплата: {{ fmtMoney(bk.prepaid) }}
                </div>
              </div>
            </div>

            <!-- Row 3: Service + Assignee + Time -->
            <div class="grid grid-cols-3 gap-2 text-xs">
              <div>
                <div class="text-[10px] text-(--t-text-3) uppercase">{{ vb.serviceLabel }}</div>
                <div class="text-(--t-text-2) font-medium truncate">{{ bk.service }}</div>
              </div>
              <div>
                <div class="text-[10px] text-(--t-text-3) uppercase">{{ vb.assigneeLabel }}</div>
                <div class="text-(--t-text-2) font-medium truncate">{{ bk.assigneeName }}</div>
              </div>
              <div class="text-end">
                <div class="text-[10px] text-(--t-text-3) uppercase">Время</div>
                <div class="text-(--t-text-2) font-medium">{{ fmtDatetime(bk.datetime) }}</div>
              </div>
            </div>

            <!-- Tags -->
            <div v-if="bk.tags.length" class="flex flex-wrap gap-1 mt-2">
              <span
                v-for="tag in bk.tags"
                :key="tag"
                class="px-2 py-0.5 rounded-full text-[10px] bg-(--t-primary)/10 text-(--t-primary) border border-(--t-primary)/20"
              >
                {{ tag }}
              </span>
            </div>

            <!-- Branches badge (B2B) -->
            <div v-if="bk.branchName" class="mt-2 text-[10px] text-(--t-text-3)">
              🏢 {{ bk.branchName }}
            </div>

            <!-- Rating (if exists, for completed) -->
            <div v-if="bk.rating" class="mt-1 text-amber-400 text-xs">
              {{ starRating(bk.rating) }}
            </div>
          </div>

          <!-- Empty on mobile -->
          <div v-if="!props.bookings.length && !props.loading" class="text-center py-16">
            <div class="text-5xl mb-4">{{ vb.icon }}</div>
            <div class="text-sm text-(--t-text-3)">{{ vb.bookingLabelPlural }} не найдены</div>
            <VButton variant="primary" size="sm" class="mt-4" @click="emit('booking-create')">
              {{ vb.quickCreate.icon }} {{ vb.quickCreate.label }}
            </VButton>
          </div>
        </div>

        <!-- ═══ PAGINATION ═══ -->
        <div v-if="totalPages > 1" class="flex items-center justify-center gap-1 mt-4">
          <button
            class="w-8 h-8 rounded-lg flex items-center justify-center text-xs text-(--t-text-3)
                   hover:bg-(--t-card-hover) hover:text-(--t-text) transition-all disabled:opacity-30"
            :disabled="currentPage <= 1"
            @click="goToPage(currentPage - 1)"
          >‹</button>
          <template v-for="pg in visiblePages" :key="pg">
            <span v-if="pg === '...'" class="w-8 text-center text-xs text-(--t-text-3)">…</span>
            <button
              v-else
              :class="[
                'w-8 h-8 rounded-lg flex items-center justify-center text-xs font-medium transition-all',
                currentPage === pg
                  ? 'bg-(--t-primary)/20 text-(--t-primary) border border-(--t-primary)/30'
                  : 'text-(--t-text-3) hover:bg-(--t-card-hover) hover:text-(--t-text)',
              ]"
              @click="goToPage(pg as number)"
            >{{ pg }}</button>
          </template>
          <button
            class="w-8 h-8 rounded-lg flex items-center justify-center text-xs text-(--t-text-3)
                   hover:bg-(--t-card-hover) hover:text-(--t-text) transition-all disabled:opacity-30"
            :disabled="currentPage >= totalPages"
            @click="goToPage(currentPage + 1)"
          >›</button>
        </div>

        <!-- Loading -->
        <div v-if="props.loading" class="flex items-center justify-center py-12">
          <div class="w-8 h-8 border-2 border-(--t-primary)/30 border-t-(--t-primary) rounded-full animate-spin" />
          <span class="ml-3 text-sm text-(--t-text-3)">Загрузка...</span>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════
         5. DETAIL MODAL
    ═══════════════════════════════════════════════ -->
    <VModal v-model="showDetailModal" :title="`${vb.bookingLabel} ${selectedBooking?.number ?? ''}`" size="lg" @update:model-value="!$event && closeDetailModal()">
      <template v-if="selectedBooking">
        <div class="flex flex-col gap-5">

          <!-- Status + Amount header -->
          <div class="flex items-center justify-between">
            <VBadge
              :text="statusLabel(selectedBooking.status)"
              :variant="STATUS_META[selectedBooking.status]?.badge ?? 'neutral'"
              size="sm"
            />
            <div class="text-2xl font-black text-emerald-400">{{ fmtMoney(selectedBooking.amount) }}</div>
          </div>

          <!-- Info grid -->
          <div class="grid grid-cols-2 gap-4">
            <!-- Client -->
            <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-2">Клиент</div>
              <div class="flex items-center gap-3">
                <div
                  class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0"
                  :style="{ backgroundColor: avatarColor(selectedBooking.id) }"
                >{{ avatarInitials(selectedBooking.clientName) }}</div>
                <div>
                  <div class="text-sm font-semibold text-(--t-text)">{{ selectedBooking.clientName }}</div>
                  <div class="text-xs text-(--t-text-3)">{{ selectedBooking.clientPhone }}</div>
                  <VBadge v-if="selectedBooking.isB2B" text="B2B" variant="b2b" size="xs" class="mt-1" />
                  <div v-if="selectedBooking.companyName" class="text-[10px] text-(--t-text-3) mt-0.5">
                    🏢 {{ selectedBooking.companyName }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Assignee -->
            <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-2">{{ vb.assigneeLabel }}</div>
              <div class="flex items-center gap-3">
                <div
                  class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0"
                  :style="{ backgroundColor: avatarColor(selectedBooking.assigneeName) }"
                >{{ avatarInitials(selectedBooking.assigneeName) }}</div>
                <div>
                  <div class="text-sm font-semibold text-(--t-text)">{{ selectedBooking.assigneeName }}</div>
                </div>
              </div>
            </div>

            <!-- Service -->
            <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-2">{{ vb.serviceLabel }}</div>
              <div class="text-sm font-medium text-(--t-text)">{{ selectedBooking.service }}</div>
            </div>

            <!-- Date/Time -->
            <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-2">Дата и время</div>
              <div class="text-sm font-medium text-(--t-text)">{{ fmtDateFull(selectedBooking.datetime) }}</div>
              <div class="text-xs text-(--t-text-3) mt-1">{{ relativeTime(selectedBooking.datetime) }}</div>
            </div>
          </div>

          <!-- Financial details -->
          <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
            <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-3">Финансы</div>
            <div class="grid grid-cols-3 gap-4 text-center">
              <div>
                <div class="text-lg font-bold text-emerald-400">{{ fmtMoney(selectedBooking.amount) }}</div>
                <div class="text-[10px] text-(--t-text-3)">Сумма</div>
              </div>
              <div>
                <div class="text-lg font-bold text-sky-400">{{ fmtMoney(selectedBooking.prepaid) }}</div>
                <div class="text-[10px] text-(--t-text-3)">Предоплата</div>
              </div>
              <div>
                <div class="text-lg font-bold text-amber-400">{{ fmtMoney(selectedBooking.amount - selectedBooking.prepaid) }}</div>
                <div class="text-[10px] text-(--t-text-3)">К оплате</div>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="selectedBooking.notes" class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
            <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-2">Заметки</div>
            <p class="text-sm text-(--t-text-2) whitespace-pre-wrap">{{ selectedBooking.notes }}</p>
          </div>

          <!-- Tags -->
          <div v-if="selectedBooking.tags.length" class="flex flex-wrap gap-1.5">
            <span
              v-for="tag in selectedBooking.tags"
              :key="tag"
              class="px-2.5 py-1 rounded-full text-xs bg-(--t-primary)/10 text-(--t-primary) border border-(--t-primary)/20"
            >{{ tag }}</span>
          </div>

          <!-- Rating -->
          <div v-if="selectedBooking.rating" class="text-center">
            <div class="text-2xl text-amber-400">{{ starRating(selectedBooking.rating) }}</div>
            <div class="text-xs text-(--t-text-3) mt-1">Оценка клиента</div>
          </div>

          <!-- Extra vertical data -->
          <div v-if="vb.extraColumns.length && selectedBooking.verticalData" class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
            <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-3">Детали</div>
            <div class="grid grid-cols-2 gap-3">
              <div v-for="col in vb.extraColumns" :key="col.key">
                <div class="text-[10px] text-(--t-text-3)">{{ col.label }}</div>
                <div class="text-sm text-(--t-text)">{{ selectedBooking.verticalData[col.key] ?? '—' }}</div>
              </div>
            </div>
          </div>

          <!-- Meta -->
          <div class="flex items-center justify-between text-[10px] text-(--t-text-3)">
            <span>Создано: {{ fmtDatetime(selectedBooking.createdAt) }}</span>
            <span v-if="selectedBooking.correlationId" class="font-mono">
              ID: {{ selectedBooking.correlationId.slice(0, 8) }}
            </span>
          </div>
        </div>
      </template>

      <template #footer>
        <div class="flex items-center gap-2">
          <VButton
            v-if="selectedBooking?.status === 'pending'"
            variant="success"
            size="sm"
            @click="emit('booking-confirm', [selectedBooking!.id]); closeDetailModal()"
          >✅ Подтвердить</VButton>
          <VButton
            v-if="selectedBooking?.status === 'confirmed' || selectedBooking?.status === 'in_progress'"
            variant="primary"
            size="sm"
            @click="emit('booking-complete', [selectedBooking!.id]); closeDetailModal()"
          >✔️ Завершить</VButton>
          <VButton
            v-if="selectedBooking?.status !== 'completed' && selectedBooking?.status !== 'cancelled'"
            variant="danger"
            size="sm"
            @click="emit('booking-cancel', [selectedBooking!.id]); closeDetailModal()"
          >❌ Отменить</VButton>
          <div class="flex-1" />
          <VButton variant="ghost" size="sm" @click="closeDetailModal">Закрыть</VButton>
        </div>
      </template>
    </VModal>

    <!-- ═══════════════════════════════════════════════
         6. MOBILE FILTER DRAWER
    ═══════════════════════════════════════════════ -->
    <Transition name="drawer-bk">
      <div
        v-if="showFilterDrawer"
        class="fixed inset-0 z-90 sm:hidden"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showFilterDrawer = false" />
        <!-- Drawer -->
        <div class="absolute inset-inline-end-0 inset-block-start-0 inset-block-end-0 bg-(--t-surface)
                    border-s border-(--t-border) p-5 overflow-auto"
             style="inline-size: 85vw; max-inline-size: 320px">
          <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-bold text-(--t-text)">Фильтры</h2>
            <button class="w-8 h-8 rounded-lg flex items-center justify-center text-(--t-text-3) hover:text-(--t-text)"
                    @click="showFilterDrawer = false">✕</button>
          </div>

          <div class="flex flex-col gap-4">
            <!-- Status -->
            <div>
              <div class="text-xs font-semibold text-(--t-text-2) mb-2">Статус</div>
              <div class="flex flex-col gap-1">
                <button
                  v-for="st in ALL_STATUSES"
                  :key="st"
                  :class="[
                    'flex items-center justify-between rounded-lg px-3 py-2 text-xs transition-all',
                    filters.status === st
                      ? `bg-${STATUS_META[st].color}-500/10 text-${STATUS_META[st].color}-400`
                      : 'text-(--t-text-3) hover:bg-(--t-card-hover)',
                  ]"
                  @click="applyFilter('status', filters.status === st ? '' : st)"
                >
                  <span>{{ statusLabel(st) }}</span>
                  <span class="font-bold">{{ statusCountMap[st] }}</span>
                </button>
              </div>
            </div>

            <!-- Date range -->
            <div>
              <div class="text-xs font-semibold text-(--t-text-2) mb-2">Период</div>
              <input v-model="filters.dateFrom" type="date" class="w-full h-9 rounded-xl px-3 text-xs mb-2 bg-(--t-surface) border border-(--t-border) text-(--t-text)" @change="emitFilters" />
              <input v-model="filters.dateTo" type="date" class="w-full h-9 rounded-xl px-3 text-xs bg-(--t-surface) border border-(--t-border) text-(--t-text)" @change="emitFilters" />
            </div>

            <!-- Assignee -->
            <div v-if="props.assignees.length">
              <div class="text-xs font-semibold text-(--t-text-2) mb-2">{{ vb.assigneeLabel }}</div>
              <select v-model="filters.assignee" class="w-full h-9 rounded-xl px-3 text-xs bg-(--t-surface) border border-(--t-border) text-(--t-text)" @change="emitFilters">
                <option value="">Все</option>
                <option v-for="a in props.assignees" :key="a.id" :value="a.id">{{ a.name }}</option>
              </select>
            </div>

            <!-- Branch -->
            <div v-if="auth.isB2BMode && props.branches.length">
              <div class="text-xs font-semibold text-(--t-text-2) mb-2">Филиал</div>
              <select v-model="filters.branch" class="w-full h-9 rounded-xl px-3 text-xs bg-(--t-surface) border border-(--t-border) text-(--t-text)" @change="emitFilters">
                <option value="">Все</option>
                <option v-for="br in props.branches" :key="br.id" :value="br.id">{{ br.name }}</option>
              </select>
            </div>

            <!-- Actions -->
            <VButton variant="ghost" size="sm" @click="clearAllFilters">Сбросить все фильтры</VButton>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     STYLES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<style scoped>
/* Ripple animation */
@keyframes ripple-bk {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* Dropdown transition */
.dropdown-bk-enter-active,
.dropdown-bk-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-bk-enter-from,
.dropdown-bk-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.96);
}

/* Slide transition for bulk bar */
.slide-bk-enter-active,
.slide-bk-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.slide-bk-enter-from,
.slide-bk-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Sidebar transition */
.sidebar-bk-enter-active,
.sidebar-bk-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease, inline-size 0.25s ease;
}
.sidebar-bk-enter-from,
.sidebar-bk-leave-to {
  opacity: 0;
  transform: translateX(-12px);
  inline-size: 0 !important;
}

/* Drawer transition */
.drawer-bk-enter-active,
.drawer-bk-leave-active {
  transition: opacity 0.3s ease;
}
.drawer-bk-enter-active > :last-child,
.drawer-bk-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.drawer-bk-enter-from,
.drawer-bk-leave-to {
  opacity: 0;
}
.drawer-bk-enter-from > :last-child,
.drawer-bk-leave-to > :last-child {
  transform: translateX(100%);
}

/* Custom scrollbar for sidebar */
aside::-webkit-scrollbar { inline-size: 4px; }
aside::-webkit-scrollbar-track { background: transparent; }
aside::-webkit-scrollbar-thumb { background: var(--t-border); border-radius: 999px; }
</style>
