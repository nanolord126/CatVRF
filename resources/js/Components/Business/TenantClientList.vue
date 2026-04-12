<script setup lang="ts">
/**
 * TenantClientList.vue — Главная страница списка клиентов B2B Tenant Dashboard
 *
 * Поддержка всех 127 вертикалей CatVRF:
 *   Beauty · Taxi · Food · Hotels · RealEstate · Flowers · Fashion · Furniture
 *   Fitness · Travel · Medical · Auto · и т.д.
 *
 * ─────────────────────────────────────────────────────────────
 *  Функционал:
 *   1. Таблица (desktop) / Карточки (mobile) — адаптивное переключение
 *   2. Поиск (по имени, телефону, email)
 *   3. Расширенные фильтры: сегмент, статус, источник, дата, LTV
 *   4. Массовые действия: рассылка, тег, экспорт, удаление
 *   5. Боковая панель быстрых фильтров (desktop)
 *   6. Full-screen режим
 *   7. Пагинация / бесконечный скролл
 *   8. Карточка клиента (модал)
 *   9. B2B/B2C различия в колонках и метриках
 *  10. Вертикаль-зависимая терминология через VERTICAL_CLIENT_CONFIG
 * ─────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'

import VCard   from '../UI/VCard.vue'
import VButton from '../UI/VButton.vue'
import VBadge  from '../UI/VBadge.vue'
import VTabs   from '../UI/VTabs.vue'
import VModal  from '../UI/VModal.vue'
import VInput  from '../UI/VInput.vue'
import { useAuth } from '@/stores'

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  TYPES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

interface Client {
  id: number | string
  fullName: string
  phone: string
  email: string
  avatar?: string
  segment: 'vip' | 'loyal' | 'new' | 'at_risk' | 'lost' | 'b2b'
  status: 'active' | 'inactive' | 'blocked'
  source: 'organic' | 'referral' | 'ads' | 'social' | 'b2b_api' | 'import' | 'ai'
  tags: string[]
  totalOrders: number
  totalSpent: number
  avgCheck: number
  ltv: number
  lastVisit: string            // ISO date
  firstVisit: string           // ISO date
  rating?: number              // 1–5
  notes?: string
  isB2B: boolean
  companyName?: string
  inn?: string
  bonusBalance: number
  verticalData?: Record<string, unknown>   // данные, специфичные для вертикали
}

interface ClientFilter {
  search: string
  segment: string
  status: string
  source: string
  tag: string
  dateFrom: string
  dateTo: string
  ltvMin: number | null
  ltvMax: number | null
  sortBy: string
  sortDir: 'asc' | 'desc'
}

interface VerticalClientConfig {
  clientLabel: string
  clientLabelPlural: string
  icon: string
  extraColumns: Array<{ key: string; label: string }>
  segmentLabels: Record<string, string>
  sourceLabels: Record<string, string>
  quickActions: Array<{ key: string; label: string; icon: string }>
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  PROPS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const props = withDefaults(defineProps<{
  vertical?: string
  clients?: Client[]
  totalClients?: number
  loading?: boolean
  perPage?: number
}>(), {
  vertical: 'default',
  clients: () => [],
  totalClients: 0,
  loading: false,
  perPage: 25,
})

const emit = defineEmits<{
  'client-click': [client: Client]
  'client-create': []
  'client-edit': [client: Client]
  'client-delete': [clientIds: Array<number | string>]
  'filter-change': [filters: ClientFilter]
  'page-change': [page: number]
  'sort-change': [sortBy: string, sortDir: 'asc' | 'desc']
  'bulk-action': [action: string, clientIds: Array<number | string>]
  'export': [format: 'xlsx' | 'csv']
  'load-more': []
}>()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STORES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const auth = useAuth()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  VERTICAL CONFIG
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const VERTICAL_CLIENT_CONFIG: Record<string, VerticalClientConfig> = {
  beauty: {
    clientLabel: 'Клиент', clientLabelPlural: 'Клиенты', icon: '💄',
    extraColumns: [
      { key: 'lastService', label: 'Последняя услуга' },
      { key: 'favoriteMaster', label: 'Любимый мастер' },
    ],
    segmentLabels: { vip: '👑 VIP', loyal: '💎 Постоянный', new: '🌟 Новый', at_risk: '⚠️ Под угрозой', lost: '💤 Потерянный', b2b: '🏢 B2B' },
    sourceLabels: { organic: '🔍 Органика', referral: '👥 Реферал', ads: '📢 Реклама', social: '📱 Соцсети', b2b_api: '🔗 API', import: '📥 Импорт', ai: '🤖 AI' },
    quickActions: [
      { key: 'book', label: 'Записать', icon: '📅' },
      { key: 'message', label: 'Написать', icon: '💬' },
      { key: 'bonus', label: 'Начислить бонус', icon: '🎁' },
    ],
  },
  taxi: {
    clientLabel: 'Пассажир', clientLabelPlural: 'Пассажиры', icon: '🚕',
    extraColumns: [
      { key: 'totalRides', label: 'Поездок' },
      { key: 'favoriteRoute', label: 'Частый маршрут' },
    ],
    segmentLabels: { vip: '👑 VIP', loyal: '💎 Постоянный', new: '🌟 Новый', at_risk: '⚠️ Под угрозой', lost: '💤 Потерянный', b2b: '🏢 Корпоративный' },
    sourceLabels: { organic: '🔍 Органика', referral: '👥 Реферал', ads: '📢 Реклама', social: '📱 Соцсети', b2b_api: '🔗 API', import: '📥 Импорт', ai: '🤖 AI' },
    quickActions: [
      { key: 'order', label: 'Заказать', icon: '🚗' },
      { key: 'message', label: 'Написать', icon: '💬' },
      { key: 'bonus', label: 'Промокод', icon: '🎫' },
    ],
  },
  food: {
    clientLabel: 'Гость', clientLabelPlural: 'Гости', icon: '🍽️',
    extraColumns: [
      { key: 'favoriteDish', label: 'Любимое блюдо' },
      { key: 'dietaryPrefs', label: 'Диета' },
    ],
    segmentLabels: { vip: '👑 VIP', loyal: '💎 Завсегдатай', new: '🌟 Новый', at_risk: '⚠️ Под угрозой', lost: '💤 Потерянный', b2b: '🏢 Корпоратив' },
    sourceLabels: { organic: '🔍 Органика', referral: '👥 Реферал', ads: '📢 Реклама', social: '📱 Соцсети', b2b_api: '🔗 API', import: '📥 Импорт', ai: '🤖 AI' },
    quickActions: [
      { key: 'reserve', label: 'Бронь', icon: '🍽️' },
      { key: 'message', label: 'Написать', icon: '💬' },
      { key: 'bonus', label: 'Скидка', icon: '🏷️' },
    ],
  },
  hotel: {
    clientLabel: 'Гость', clientLabelPlural: 'Гости', icon: '🏨',
    extraColumns: [
      { key: 'lastStay', label: 'Последнее проживание' },
      { key: 'roomPreference', label: 'Предпочитаемый номер' },
    ],
    segmentLabels: { vip: '👑 VIP', loyal: '💎 Постоянный', new: '🌟 Новый', at_risk: '⚠️ Под угрозой', lost: '💤 Потерянный', b2b: '🏢 Корпоратив' },
    sourceLabels: { organic: '🔍 Органика', referral: '👥 Реферал', ads: '📢 Реклама', social: '📱 Соцсети', b2b_api: '🔗 API', import: '📥 Импорт', ai: '🤖 AI' },
    quickActions: [
      { key: 'book', label: 'Забронировать', icon: '🛏️' },
      { key: 'message', label: 'Написать', icon: '💬' },
      { key: 'upgrade', label: 'Апгрейд', icon: '⬆️' },
    ],
  },
  realEstate: {
    clientLabel: 'Клиент', clientLabelPlural: 'Клиенты', icon: '🏢',
    extraColumns: [
      { key: 'budget', label: 'Бюджет' },
      { key: 'interestedType', label: 'Тип объекта' },
    ],
    segmentLabels: { vip: '👑 VIP', loyal: '💎 Постоянный', new: '🌟 Новый', at_risk: '⚠️ Без активности', lost: '💤 Потерянный', b2b: '🏢 Агентство' },
    sourceLabels: { organic: '🔍 Органика', referral: '👥 Реферал', ads: '📢 Реклама', social: '📱 Соцсети', b2b_api: '🔗 API', import: '📥 Импорт', ai: '🤖 AI' },
    quickActions: [
      { key: 'showing', label: 'Назначить показ', icon: '🏠' },
      { key: 'message', label: 'Написать', icon: '💬' },
      { key: 'offer', label: 'Предложение', icon: '📄' },
    ],
  },
  flowers: {
    clientLabel: 'Клиент', clientLabelPlural: 'Клиенты', icon: '💐',
    extraColumns: [
      { key: 'lastBouquet', label: 'Последний букет' },
      { key: 'favoriteFlowers', label: 'Любимые цветы' },
    ],
    segmentLabels: { vip: '👑 VIP', loyal: '💎 Постоянный', new: '🌟 Новый', at_risk: '⚠️ Под угрозой', lost: '💤 Потерянный', b2b: '🏢 Корпоратив' },
    sourceLabels: { organic: '🔍 Органика', referral: '👥 Реферал', ads: '📢 Реклама', social: '📱 Соцсети', b2b_api: '🔗 API', import: '📥 Импорт', ai: '🤖 AI' },
    quickActions: [
      { key: 'order', label: 'Заказ', icon: '💐' },
      { key: 'message', label: 'Написать', icon: '💬' },
      { key: 'remind', label: 'Напоминание', icon: '🔔' },
    ],
  },
  fashion: {
    clientLabel: 'Клиент', clientLabelPlural: 'Клиенты', icon: '👗',
    extraColumns: [
      { key: 'sizeProfile', label: 'Размеры' },
      { key: 'styleType', label: 'Стиль' },
    ],
    segmentLabels: { vip: '👑 VIP', loyal: '💎 Постоянный', new: '🌟 Новый', at_risk: '⚠️ Под угрозой', lost: '💤 Потерянный', b2b: '🏢 Оптовик' },
    sourceLabels: { organic: '🔍 Органика', referral: '👥 Реферал', ads: '📢 Реклама', social: '📱 Соцсети', b2b_api: '🔗 API', import: '📥 Импорт', ai: '🤖 AI' },
    quickActions: [
      { key: 'consult', label: 'Консультация', icon: '👗' },
      { key: 'message', label: 'Написать', icon: '💬' },
      { key: 'lookbook', label: 'Подборка', icon: '📸' },
    ],
  },
  furniture: {
    clientLabel: 'Клиент', clientLabelPlural: 'Клиенты', icon: '🛋️',
    extraColumns: [
      { key: 'lastProject', label: 'Последний проект' },
      { key: 'interiorStyle', label: 'Стиль интерьера' },
    ],
    segmentLabels: { vip: '👑 VIP', loyal: '💎 Постоянный', new: '🌟 Новый', at_risk: '⚠️ Под угрозой', lost: '💤 Потерянный', b2b: '🏢 Дизайнер' },
    sourceLabels: { organic: '🔍 Органика', referral: '👥 Реферал', ads: '📢 Реклама', social: '📱 Соцсети', b2b_api: '🔗 API', import: '📥 Импорт', ai: '🤖 AI' },
    quickActions: [
      { key: 'measure', label: 'Замер', icon: '📐' },
      { key: 'message', label: 'Написать', icon: '💬' },
      { key: 'project', label: 'AI-дизайн', icon: '🎨' },
    ],
  },
  fitness: {
    clientLabel: 'Клиент', clientLabelPlural: 'Клиенты', icon: '💪',
    extraColumns: [
      { key: 'membership', label: 'Абонемент' },
      { key: 'trainer', label: 'Тренер' },
    ],
    segmentLabels: { vip: '👑 VIP', loyal: '💎 Постоянный', new: '🌟 Новый', at_risk: '⚠️ Под угрозой', lost: '💤 Потерянный', b2b: '🏢 Корпоратив' },
    sourceLabels: { organic: '🔍 Органика', referral: '👥 Реферал', ads: '📢 Реклама', social: '📱 Соцсети', b2b_api: '🔗 API', import: '📥 Импорт', ai: '🤖 AI' },
    quickActions: [
      { key: 'session', label: 'Тренировка', icon: '🏋️' },
      { key: 'message', label: 'Написать', icon: '💬' },
      { key: 'plan', label: 'AI-план', icon: '📋' },
    ],
  },
  travel: {
    clientLabel: 'Путешественник', clientLabelPlural: 'Путешественники', icon: '✈️',
    extraColumns: [
      { key: 'lastTrip', label: 'Последнее путешествие' },
      { key: 'travelStyle', label: 'Стиль отдыха' },
    ],
    segmentLabels: { vip: '👑 VIP', loyal: '💎 Постоянный', new: '🌟 Новый', at_risk: '⚠️ Под угрозой', lost: '💤 Потерянный', b2b: '🏢 Турагентство' },
    sourceLabels: { organic: '🔍 Органика', referral: '👥 Реферал', ads: '📢 Реклама', social: '📱 Соцсети', b2b_api: '🔗 API', import: '📥 Импорт', ai: '🤖 AI' },
    quickActions: [
      { key: 'trip', label: 'Спланировать', icon: '🗺️' },
      { key: 'message', label: 'Написать', icon: '💬' },
      { key: 'offer', label: 'Спецпредложение', icon: '🎁' },
    ],
  },
  default: {
    clientLabel: 'Клиент', clientLabelPlural: 'Клиенты', icon: '👤',
    extraColumns: [],
    segmentLabels: { vip: '👑 VIP', loyal: '💎 Постоянный', new: '🌟 Новый', at_risk: '⚠️ Под угрозой', lost: '💤 Потерянный', b2b: '🏢 B2B' },
    sourceLabels: { organic: '🔍 Органика', referral: '👥 Реферал', ads: '📢 Реклама', social: '📱 Соцсети', b2b_api: '🔗 API', import: '📥 Импорт', ai: '🤖 AI' },
    quickActions: [
      { key: 'message', label: 'Написать', icon: '💬' },
      { key: 'bonus', label: 'Бонус', icon: '🎁' },
    ],
  },
}

const vc = computed<VerticalClientConfig>(() => VERTICAL_CLIENT_CONFIG[props.vertical] ?? VERTICAL_CLIENT_CONFIG.default)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STATE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const isFullscreen    = ref(false)
const showSidebar     = ref(true)
const showClientModal = ref(false)
const showCreateModal = ref(false)
const showExportMenu  = ref(false)
const showFiltersDrawer = ref(false)
const selectedClient  = ref<Client | null>(null)
const currentPage     = ref(1)

// Bulk
const selectedIds = reactive<Set<number | string>>(new Set())
const isBulkMode  = ref(false)
const selectAll   = ref(false)

// View mode: table vs cards
const viewAs = ref<'table' | 'cards'>('table')

// Filters
const filters = reactive<ClientFilter>({
  search: '',
  segment: '',
  status: '',
  source: '',
  tag: '',
  dateFrom: '',
  dateTo: '',
  ltvMin: null,
  ltvMax: null,
  sortBy: 'lastVisit',
  sortDir: 'desc',
})

// Refs
const rootEl  = ref<HTMLElement | null>(null)
const scrollSentinel = ref<HTMLElement | null>(null)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  SEGMENT COLORS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const SEGMENT_BADGE: Record<string, { variant: string; dot: boolean }> = {
  vip:     { variant: 'warning', dot: true },
  loyal:   { variant: 'success', dot: true },
  new:     { variant: 'info',    dot: true },
  at_risk: { variant: 'danger',  dot: true },
  lost:    { variant: 'neutral', dot: false },
  b2b:     { variant: 'b2b',     dot: true },
}

const STATUS_BADGE: Record<string, { variant: string }> = {
  active:   { variant: 'success' },
  inactive: { variant: 'neutral' },
  blocked:  { variant: 'danger' },
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  SORT OPTIONS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const sortOptions = [
  { key: 'lastVisit',   label: 'Последний визит' },
  { key: 'totalSpent',  label: 'Потрачено' },
  { key: 'ltv',         label: 'LTV' },
  { key: 'totalOrders', label: 'Заказов' },
  { key: 'fullName',    label: 'Имя' },
  { key: 'firstVisit',  label: 'Дата регистрации' },
]

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  COMPUTED
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const totalPages = computed(() => Math.ceil(props.totalClients / props.perPage) || 1)

const hasActiveFilters = computed(() =>
  filters.segment !== '' ||
  filters.status !== '' ||
  filters.source !== '' ||
  filters.tag !== '' ||
  filters.dateFrom !== '' ||
  filters.dateTo !== '' ||
  filters.ltvMin !== null ||
  filters.ltvMax !== null
)

// Client-level computed — available tags (collect from all clients)
const availableTags = computed(() => {
  const tagSet = new Set<string>()
  props.clients.forEach(c => c.tags.forEach(t => tagSet.add(t)))
  return Array.from(tagSet).sort()
})

// Segment counts for sidebar badges
const segmentCounts = computed(() => {
  const map: Record<string, number> = { vip: 0, loyal: 0, new: 0, at_risk: 0, lost: 0, b2b: 0, all: props.totalClients }
  props.clients.forEach(c => { if (map[c.segment] !== undefined) map[c.segment]++ })
  return map
})

const sourceCounts = computed(() => {
  const map: Record<string, number> = {}
  props.clients.forEach(c => { map[c.source] = (map[c.source] ?? 0) + 1 })
  return map
})

// Stats
const avgLTV = computed(() => {
  if (props.clients.length === 0) return 0
  return Math.round(props.clients.reduce((s, c) => s + c.ltv, 0) / props.clients.length)
})
const totalRevenue = computed(() => props.clients.reduce((s, c) => s + c.totalSpent, 0))
const activeCount  = computed(() => props.clients.filter(c => c.status === 'active').length)
const b2bCount     = computed(() => props.clients.filter(c => c.isB2B).length)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  PAGINATION
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  emit('page-change', page)
}

const visiblePages = computed(() => {
  const total = totalPages.value
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
//  SORT
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

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

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  FILTER ACTIONS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function applyFilter(key: keyof ClientFilter, value: unknown) {
  ;(filters as Record<string, unknown>)[key] = value
  currentPage.value = 1
  emitFilters()
}

function clearAllFilters() {
  filters.search   = ''
  filters.segment   = ''
  filters.status    = ''
  filters.source    = ''
  filters.tag       = ''
  filters.dateFrom  = ''
  filters.dateTo    = ''
  filters.ltvMin    = null
  filters.ltvMax    = null
  currentPage.value = 1
  emitFilters()
}

function emitFilters() {
  emit('filter-change', { ...filters })
}

// Debounced search
let searchTimer: ReturnType<typeof setTimeout> | null = null
watch(() => filters.search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    currentPage.value = 1
    emitFilters()
  }, 350)
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  SELECTION / BULK
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
    props.clients.forEach(c => selectedIds.add(c.id))
    selectAll.value = true
  }
}

function handleBulkAction(action: string) {
  emit('bulk-action', action, Array.from(selectedIds))
  selectedIds.clear()
  selectAll.value = false
  isBulkMode.value = false
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  CLIENT MODAL
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function openClient(client: Client) {
  if (isBulkMode.value) {
    toggleSelect(client.id)
    return
  }
  selectedClient.value = client
  showClientModal.value = true
  emit('client-click', client)
}

function closeClientModal() {
  showClientModal.value = false
  selectedClient.value = null
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  FULLSCREEN
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
//  INTERSECTION OBSERVER (infinite scroll)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

let observer: IntersectionObserver | null = null

function setupObserver() {
  if (!scrollSentinel.value) return
  observer = new IntersectionObserver(([entry]) => {
    if (entry.isIntersecting && !props.loading && currentPage.value < totalPages.value) {
      emit('load-more')
    }
  }, { rootMargin: '200px' })
  observer.observe(scrollSentinel.value)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  RIPPLE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function ripple(e: MouseEvent) {
  const target = e.currentTarget as HTMLElement
  const rect = target.getBoundingClientRect()
  const diameter = Math.max(rect.width, rect.height) * 2
  const el = document.createElement('span')
  el.className = 'absolute rounded-full bg-white/10 pointer-events-none animate-[ripple-cl_0.6s_ease-out]'
  el.style.cssText = `inline-size:${diameter}px;block-size:${diameter}px;inset-inline-start:${e.clientX - rect.left - diameter / 2}px;inset-block-start:${e.clientY - rect.top - diameter / 2}px;`
  target.appendChild(el)
  setTimeout(() => el.remove(), 650)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  FORMATTERS
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
  const d = new Date(iso)
  return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', year: 'numeric' })
}

function relativeDate(iso: string): string {
  if (!iso) return '—'
  const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 86_400_000)
  if (diff === 0) return 'Сегодня'
  if (diff === 1) return 'Вчера'
  if (diff < 7)  return `${diff} дн. назад`
  if (diff < 30) return `${Math.floor(diff / 7)} нед. назад`
  if (diff < 365) return `${Math.floor(diff / 30)} мес. назад`
  return `${Math.floor(diff / 365)} г. назад`
}

function avatarInitials(name: string): string {
  return name.split(' ').slice(0, 2).map(w => w.charAt(0)).join('').toUpperCase()
}

function starRating(r?: number): string {
  if (!r) return ''
  return '★'.repeat(Math.round(r)) + '☆'.repeat(5 - Math.round(r))
}

const AVATAR_COLORS = [
  '#6366f1', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6',
  '#ec4899', '#14b8a6', '#f97316', '#06b6d4', '#84cc16',
]

function avatarColor(id: number | string): string {
  const idx = typeof id === 'number' ? id : String(id).charCodeAt(0)
  return AVATAR_COLORS[idx % AVATAR_COLORS.length]
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  LIFECYCLE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

onMounted(() => {
  document.addEventListener('fullscreenchange', onFullscreenChange)
  nextTick(() => setupObserver())
})

onBeforeUnmount(() => {
  document.removeEventListener('fullscreenchange', onFullscreenChange)
  observer?.disconnect()
})
</script>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     TEMPLATE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<template>
  <div
    ref="rootEl"
    :class="[
      'flex flex-col gap-4',
      isFullscreen ? 'fixed inset-0 z-90 bg-(--t-bg) p-4 overflow-auto' : '',
    ]"
  >
    <!-- ═══════════════════════════════════════════════════
         SECTION 1 · STAT CARDS
    ══════════════════════════════════════════════════════ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <!-- Total clients -->
      <div class="relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
                  backdrop-blur-xl p-4 transition-all duration-200 hover:border-(--t-primary)/30
                  group/stat">
        <div class="text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold">
          Всего {{ vc.clientLabelPlural.toLowerCase() }}
        </div>
        <div class="mt-1 text-2xl font-extrabold text-(--t-text)">{{ fmtNum(props.totalClients) }}</div>
        <div class="mt-0.5 text-xs text-emerald-400">{{ activeCount }} активных</div>
        <div class="absolute inset-block-end-0 inset-inline-end-0 text-4xl opacity-10 p-2 transition-transform
                    duration-300 group-hover/stat:scale-110">
          {{ vc.icon }}
        </div>
      </div>

      <!-- Revenue -->
      <div class="relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
                  backdrop-blur-xl p-4 transition-all duration-200 hover:border-emerald-500/30
                  group/stat">
        <div class="text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold">Выручка</div>
        <div class="mt-1 text-2xl font-extrabold text-emerald-400">{{ fmtMoney(totalRevenue) }}</div>
        <div class="mt-0.5 text-xs text-(--t-text-3)">от {{ fmtNum(props.clients.length) }} чел.</div>
        <div class="absolute inset-block-end-0 inset-inline-end-0 text-4xl opacity-10 p-2 transition-transform
                    duration-300 group-hover/stat:scale-110">
          💰
        </div>
      </div>

      <!-- Avg LTV -->
      <div class="relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
                  backdrop-blur-xl p-4 transition-all duration-200 hover:border-violet-500/30
                  group/stat">
        <div class="text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold">Средний LTV</div>
        <div class="mt-1 text-2xl font-extrabold text-violet-400">{{ fmtMoney(avgLTV) }}</div>
        <div class="mt-0.5 text-xs text-(--t-text-3)">на клиента</div>
        <div class="absolute inset-block-end-0 inset-inline-end-0 text-4xl opacity-10 p-2 transition-transform
                    duration-300 group-hover/stat:scale-110">
          📈
        </div>
      </div>

      <!-- B2B -->
      <div class="relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
                  backdrop-blur-xl p-4 transition-all duration-200 hover:border-sky-500/30
                  group/stat">
        <div class="text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold">B2B</div>
        <div class="mt-1 text-2xl font-extrabold text-sky-400">{{ fmtNum(b2bCount) }}</div>
        <div class="mt-0.5 text-xs text-(--t-text-3)">юрлица</div>
        <div class="absolute inset-block-end-0 inset-inline-end-0 text-4xl opacity-10 p-2 transition-transform
                    duration-300 group-hover/stat:scale-110">
          🏢
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         SECTION 2 · TOOLBAR
    ══════════════════════════════════════════════════════ -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

      <!-- Search -->
      <div class="relative flex-1 max-w-md">
        <input
          v-model="filters.search"
          type="text"
          :placeholder="`Поиск ${vc.clientLabelPlural.toLowerCase()} по имени, телефону, email...`"
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

      <!-- Actions -->
      <div class="flex items-center gap-2 flex-wrap">

        <!-- View toggle -->
        <div class="hidden sm:flex items-center rounded-xl border border-(--t-border) overflow-hidden">
          <button
            :class="[
              'px-2.5 py-1.5 text-xs transition-all duration-150',
              viewAs === 'table' ? 'bg-(--t-primary)/20 text-(--t-primary)' : 'text-(--t-text-3) hover:text-(--t-text)',
            ]"
            @click="viewAs = 'table'"
          >
            ☰
          </button>
          <button
            :class="[
              'px-2.5 py-1.5 text-xs transition-all duration-150',
              viewAs === 'cards' ? 'bg-(--t-primary)/20 text-(--t-primary)' : 'text-(--t-text-3) hover:text-(--t-text)',
            ]"
            @click="viewAs = 'cards'"
          >
            ▦
          </button>
        </div>

        <!-- Filters toggle (mobile) -->
        <VButton variant="ghost" size="sm" class="sm:hidden" @click="showFiltersDrawer = true">
          🔍 Фильтры
          <VBadge v-if="hasActiveFilters" text="!" variant="danger" size="xs" class="ml-1" />
        </VButton>

        <!-- Sort -->
        <select
          v-model="filters.sortBy"
          class="h-9 rounded-xl px-2 text-xs bg-(--t-surface) border border-(--t-border)
                 text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
          @change="emit('sort-change', filters.sortBy, filters.sortDir)"
        >
          <option v-for="opt in sortOptions" :key="opt.key" :value="opt.key">{{ opt.label }}</option>
        </select>

        <!-- Bulk toggle -->
        <VButton
          :variant="isBulkMode ? 'danger' : 'ghost'"
          size="sm"
          @click="isBulkMode = !isBulkMode; if (!isBulkMode) { selectedIds.clear(); selectAll = false; }"
        >
          {{ isBulkMode ? `✓ ${selectedIds.size}` : '☑️' }}
        </VButton>

        <!-- Export -->
        <div class="relative">
          <VButton variant="ghost" size="sm" @click="showExportMenu = !showExportMenu">📥 Экспорт</VButton>
          <Transition name="dropdown-cl">
            <div
              v-if="showExportMenu"
              class="absolute inset-inline-end-0 top-full mt-1 z-30 rounded-xl border border-(--t-border)
                     bg-(--t-surface) backdrop-blur-xl shadow-xl overflow-hidden"
              style="min-inline-size: 160px"
            >
              <button
                class="w-full px-4 py-2.5 text-xs text-(--t-text) hover:bg-(--t-card-hover)
                       transition-colors text-start"
                @click="emit('export', 'xlsx'); showExportMenu = false"
              >
                📊 Excel (.xlsx)
              </button>
              <button
                class="w-full px-4 py-2.5 text-xs text-(--t-text) hover:bg-(--t-card-hover)
                       transition-colors text-start"
                @click="emit('export', 'csv'); showExportMenu = false"
              >
                📄 CSV
              </button>
            </div>
          </Transition>
        </div>

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

        <!-- Add client -->
        <VButton variant="primary" size="sm" @click="emit('client-create')">
          ➕ {{ vc.clientLabel }}
        </VButton>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         SECTION 3 · BULK ACTIONS BAR
    ══════════════════════════════════════════════════════ -->
    <Transition name="slide-down-cl">
      <div
        v-if="isBulkMode && selectedIds.size > 0"
        class="flex flex-wrap items-center gap-2 p-3 rounded-xl
               bg-amber-500/10 border border-amber-500/20"
      >
        <span class="text-sm font-semibold text-amber-200">
          Выбрано: {{ selectedIds.size }}
        </span>
        <div class="flex-1" />
        <VButton variant="ghost" size="xs" @click="toggleSelectAll">
          {{ selectAll ? 'Снять все' : 'Выбрать все' }}
        </VButton>
        <VButton variant="secondary" size="xs" @click="handleBulkAction('tag')">🏷️ Тег</VButton>
        <VButton variant="secondary" size="xs" @click="handleBulkAction('message')">💬 Рассылка</VButton>
        <VButton variant="secondary" size="xs" @click="handleBulkAction('bonus')">🎁 Бонус</VButton>
        <VButton variant="danger" size="xs" @click="handleBulkAction('delete')">🗑️ Удалить</VButton>
      </div>
    </Transition>

    <!-- ═══════════════════════════════════════════════════
         SECTION 4 · ACTIVE FILTER PILLS
    ══════════════════════════════════════════════════════ -->
    <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-1.5">
      <span class="text-xs text-(--t-text-3) mr-1">Фильтры:</span>
      <VBadge v-if="filters.segment" :text="vc.segmentLabels[filters.segment] ?? filters.segment" variant="info" size="xs" removable @remove="applyFilter('segment', '')" />
      <VBadge v-if="filters.status" :text="filters.status === 'active' ? 'Активные' : filters.status === 'inactive' ? 'Неактивные' : 'Заблокированные'" variant="info" size="xs" removable @remove="applyFilter('status', '')" />
      <VBadge v-if="filters.source" :text="vc.sourceLabels[filters.source] ?? filters.source" variant="info" size="xs" removable @remove="applyFilter('source', '')" />
      <VBadge v-if="filters.tag" :text="'#' + filters.tag" variant="info" size="xs" removable @remove="applyFilter('tag', '')" />
      <button
        class="ml-1 px-2 py-0.5 rounded-lg text-[10px] text-(--t-text-3) hover:text-rose-400
               hover:bg-rose-500/10 transition-all"
        @click="clearAllFilters"
      >
        ✕ Сбросить все
      </button>
    </div>

    <!-- ═══════════════════════════════════════════════════
         SECTION 5 · MAIN CONTENT (table/cards + sidebar)
    ══════════════════════════════════════════════════════ -->
    <div class="flex gap-4 min-h-0 flex-1">

      <!-- ─── Content area ─── -->
      <div class="flex-1 min-w-0">

        <!-- Loading overlay -->
        <div v-if="loading && clients.length === 0" class="flex items-center justify-center py-24">
          <div class="flex items-center gap-3 text-(--t-text-2)">
            <svg class="animate-spin w-5 h-5 text-(--t-primary)" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <span class="text-sm">Загрузка {{ vc.clientLabelPlural.toLowerCase() }}...</span>
          </div>
        </div>

        <!-- ═══ TABLE VIEW (desktop) ═══ -->
        <div
          v-else-if="viewAs === 'table'"
          class="hidden sm:block rounded-2xl border border-(--t-border) bg-(--t-surface)
                 backdrop-blur-xl overflow-hidden"
        >
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-(--t-border)">
                  <!-- Checkbox -->
                  <th v-if="isBulkMode" class="py-3 px-3" style="inline-size: 40px">
                    <input
                      type="checkbox"
                      :checked="selectAll"
                      @change="toggleSelectAll"
                      class="rounded border-(--t-border) text-(--t-primary) focus:ring-(--t-primary)"
                    />
                  </th>
                  <!-- Name -->
                  <th
                    class="py-3 px-4 text-start text-xs font-semibold uppercase tracking-wider
                           text-(--t-text-3) cursor-pointer hover:text-(--t-text) transition-colors select-none"
                    @click="toggleSort('fullName')"
                  >
                    {{ vc.clientLabel }} {{ sortIcon('fullName') }}
                  </th>
                  <!-- Segment -->
                  <th class="py-3 px-4 text-start text-xs font-semibold uppercase tracking-wider text-(--t-text-3)">
                    Сегмент
                  </th>
                  <!-- Orders -->
                  <th
                    class="py-3 px-4 text-end text-xs font-semibold uppercase tracking-wider
                           text-(--t-text-3) cursor-pointer hover:text-(--t-text) transition-colors select-none"
                    @click="toggleSort('totalOrders')"
                  >
                    Заказов {{ sortIcon('totalOrders') }}
                  </th>
                  <!-- Total spent -->
                  <th
                    class="py-3 px-4 text-end text-xs font-semibold uppercase tracking-wider
                           text-(--t-text-3) cursor-pointer hover:text-(--t-text) transition-colors select-none"
                    @click="toggleSort('totalSpent')"
                  >
                    Потрачено {{ sortIcon('totalSpent') }}
                  </th>
                  <!-- LTV -->
                  <th
                    class="hidden xl:table-cell py-3 px-4 text-end text-xs font-semibold uppercase tracking-wider
                           text-(--t-text-3) cursor-pointer hover:text-(--t-text) transition-colors select-none"
                    @click="toggleSort('ltv')"
                  >
                    LTV {{ sortIcon('ltv') }}
                  </th>
                  <!-- Последний визит -->
                  <th
                    class="hidden lg:table-cell py-3 px-4 text-start text-xs font-semibold uppercase tracking-wider
                           text-(--t-text-3) cursor-pointer hover:text-(--t-text) transition-colors select-none"
                    @click="toggleSort('lastVisit')"
                  >
                    Последний визит {{ sortIcon('lastVisit') }}
                  </th>
                  <!-- Extra vertical columns -->
                  <th
                    v-for="col in vc.extraColumns"
                    :key="col.key"
                    class="hidden 2xl:table-cell py-3 px-4 text-start text-xs font-semibold uppercase tracking-wider text-(--t-text-3)"
                  >
                    {{ col.label }}
                  </th>
                  <!-- Status -->
                  <th class="py-3 px-4 text-center text-xs font-semibold uppercase tracking-wider text-(--t-text-3)">
                    Статус
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-(--t-border)/50">
                <tr
                  v-for="client in clients"
                  :key="client.id"
                  :class="[
                    'group transition-colors duration-150 cursor-pointer',
                    'hover:bg-(--t-card-hover)',
                    selectedIds.has(client.id) ? 'bg-(--t-primary)/5' : '',
                  ]"
                  @click="openClient(client)"
                >
                  <!-- Checkbox -->
                  <td v-if="isBulkMode" class="py-3 px-3">
                    <input
                      type="checkbox"
                      :checked="selectedIds.has(client.id)"
                      @click.stop="toggleSelect(client.id)"
                      class="rounded border-(--t-border) text-(--t-primary) focus:ring-(--t-primary)"
                    />
                  </td>

                  <!-- Name + avatar -->
                  <td class="py-3 px-4">
                    <div class="flex items-center gap-3">
                      <div
                        class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold
                               text-white shrink-0 transition-transform duration-200
                               group-hover:scale-110"
                        :style="{ background: avatarColor(client.id) }"
                      >
                        {{ client.avatar ?? avatarInitials(client.fullName) }}
                      </div>
                      <div class="min-w-0">
                        <div class="text-sm font-semibold text-(--t-text) truncate group-hover:text-(--t-primary) transition-colors">
                          {{ client.fullName }}
                        </div>
                        <div class="text-[11px] text-(--t-text-3) truncate">
                          {{ client.phone }}
                          <template v-if="client.isB2B && client.companyName">
                            · 🏢 {{ client.companyName }}
                          </template>
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Segment -->
                  <td class="py-3 px-4">
                    <VBadge
                      :text="vc.segmentLabels[client.segment] ?? client.segment"
                      :variant="SEGMENT_BADGE[client.segment]?.variant ?? 'neutral'"
                      :dot="SEGMENT_BADGE[client.segment]?.dot ?? false"
                      size="xs"
                    />
                  </td>

                  <!-- Orders -->
                  <td class="py-3 px-4 text-end font-mono text-xs text-(--t-text-2)">
                    {{ fmtNum(client.totalOrders) }}
                  </td>

                  <!-- Total spent -->
                  <td class="py-3 px-4 text-end font-semibold text-(--t-text)">
                    {{ fmtMoney(client.totalSpent) }}
                  </td>

                  <!-- LTV -->
                  <td class="hidden xl:table-cell py-3 px-4 text-end font-semibold text-violet-400">
                    {{ fmtMoney(client.ltv) }}
                  </td>

                  <!-- Последний визит -->
                  <td class="hidden lg:table-cell py-3 px-4 text-(--t-text-3) text-xs">
                    {{ relativeDate(client.lastVisit) }}
                  </td>

                  <!-- Extra columns -->
                  <td
                    v-for="col in vc.extraColumns"
                    :key="col.key"
                    class="hidden 2xl:table-cell py-3 px-4 text-(--t-text-3) text-xs"
                  >
                    {{ (client.verticalData as Record<string, unknown>)?.[col.key] ?? '—' }}
                  </td>

                  <!-- Status -->
                  <td class="py-3 px-4 text-center">
                    <VBadge
                      :text="client.status === 'active' ? 'Активен' : client.status === 'inactive' ? 'Неактивен' : 'Заблокирован'"
                      :variant="STATUS_BADGE[client.status]?.variant ?? 'neutral'"
                      :dot="client.status === 'active'"
                      size="xs"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty -->
          <div
            v-if="clients.length === 0 && !loading"
            class="flex flex-col items-center py-16 text-(--t-text-3)"
          >
            <span class="text-5xl mb-4">{{ vc.icon }}</span>
            <p class="text-sm font-medium">{{ vc.clientLabelPlural }} не найдены</p>
            <p class="text-xs mt-1 opacity-60">Попробуйте изменить фильтры или добавьте нового клиента</p>
          </div>
        </div>

        <!-- ═══ CARD VIEW (mobile + explicit) ═══ -->
        <div
          v-if="viewAs === 'cards' || true"
          :class="[viewAs === 'table' ? 'sm:hidden' : '', 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3']"
          :style="viewAs === 'table' ? {} : {}"
        >
          <div
            v-for="client in clients"
            :key="client.id"
            :class="[
              viewAs === 'table' ? 'sm:hidden' : '',
              'relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)',
              'backdrop-blur-xl p-4 cursor-pointer transition-all duration-200',
              'hover:border-(--t-primary)/30 hover:shadow-lg hover:shadow-(--t-glow)/5',
              'active:scale-[0.98]',
              selectedIds.has(client.id) ? 'ring-2 ring-(--t-primary)/60' : '',
            ]"
            @click="openClient(client)"
          >
            <!-- Bulk checkbox overlay -->
            <div
              v-if="isBulkMode"
              class="absolute inset-block-start-3 inset-inline-end-3 z-10"
            >
              <input
                type="checkbox"
                :checked="selectedIds.has(client.id)"
                @click.stop="toggleSelect(client.id)"
                class="w-5 h-5 rounded border-(--t-border) text-(--t-primary) focus:ring-(--t-primary)"
              />
            </div>

            <!-- Header row -->
            <div class="flex items-start gap-3">
              <div
                class="w-11 h-11 rounded-full flex items-center justify-center text-sm font-bold
                       text-white shrink-0"
                :style="{ background: avatarColor(client.id) }"
              >
                {{ client.avatar ?? avatarInitials(client.fullName) }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <h3 class="text-sm font-bold text-(--t-text) truncate">{{ client.fullName }}</h3>
                  <VBadge
                    :text="vc.segmentLabels[client.segment] ?? client.segment"
                    :variant="SEGMENT_BADGE[client.segment]?.variant ?? 'neutral'"
                    size="xs"
                  />
                </div>
                <div class="text-xs text-(--t-text-3) mt-0.5 truncate">
                  {{ client.phone }} · {{ client.email }}
                </div>
                <div v-if="client.isB2B && client.companyName" class="text-[11px] text-sky-400 mt-0.5 truncate">
                  🏢 {{ client.companyName }}
                </div>
              </div>
            </div>

            <!-- Metrics row -->
            <div class="grid grid-cols-3 gap-2 mt-3 pt-3 border-t border-(--t-border)/50">
              <div>
                <div class="text-[10px] uppercase tracking-wider text-(--t-text-3)">Заказов</div>
                <div class="text-sm font-bold text-(--t-text)">{{ fmtNum(client.totalOrders) }}</div>
              </div>
              <div>
                <div class="text-[10px] uppercase tracking-wider text-(--t-text-3)">Потрачено</div>
                <div class="text-sm font-bold text-emerald-400">{{ fmtMoney(client.totalSpent) }}</div>
              </div>
              <div>
                <div class="text-[10px] uppercase tracking-wider text-(--t-text-3)">LTV</div>
                <div class="text-sm font-bold text-violet-400">{{ fmtMoney(client.ltv) }}</div>
              </div>
            </div>

            <!-- Footer row -->
            <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-(--t-border)/50">
              <div class="flex items-center gap-1.5">
                <VBadge
                  :text="client.status === 'active' ? 'Активен' : client.status === 'inactive' ? 'Неактивен' : 'Заблокирован'"
                  :variant="STATUS_BADGE[client.status]?.variant ?? 'neutral'"
                  :dot="client.status === 'active'"
                  size="xs"
                />
                <span v-if="client.rating" class="text-[10px] text-amber-400">{{ starRating(client.rating) }}</span>
              </div>
              <span class="text-[10px] text-(--t-text-3)">{{ relativeDate(client.lastVisit) }}</span>
            </div>

            <!-- Tags -->
            <div v-if="client.tags.length > 0" class="flex flex-wrap gap-1 mt-2">
              <span
                v-for="tag in client.tags.slice(0, 4)"
                :key="tag"
                class="inline-flex px-1.5 py-0.5 rounded text-[9px] bg-(--t-primary)/10 text-(--t-primary)/80"
              >
                #{{ tag }}
              </span>
              <span v-if="client.tags.length > 4" class="text-[9px] text-(--t-text-3)">
                +{{ client.tags.length - 4 }}
              </span>
            </div>

            <!-- Bonus -->
            <div v-if="client.bonusBalance > 0" class="mt-2 flex items-center gap-1 text-[10px] text-amber-400">
              🎁 {{ fmtNum(client.bonusBalance) }} бонусов
            </div>
          </div>
        </div>

        <!-- ═══ INFINITE SCROLL SENTINEL ═══ -->
        <div ref="scrollSentinel" class="h-1" />

        <!-- Loading more indicator -->
        <div v-if="loading && clients.length > 0" class="flex items-center justify-center py-6">
          <svg class="animate-spin w-5 h-5 text-(--t-primary)" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
        </div>

        <!-- ═══ PAGINATION ═══ -->
        <div
          v-if="totalPages > 1"
          class="flex items-center justify-center gap-1.5 mt-4"
        >
          <button
            :disabled="currentPage === 1"
            class="w-8 h-8 rounded-lg flex items-center justify-center text-xs
                   bg-(--t-surface) border border-(--t-border) text-(--t-text-2)
                   hover:text-(--t-text) hover:border-(--t-primary)/40
                   disabled:opacity-30 disabled:cursor-not-allowed
                   transition-all duration-150 active:scale-95"
            @click="goToPage(currentPage - 1)"
          >
            ‹
          </button>
          <template v-for="p in visiblePages" :key="p">
            <span v-if="p === '...'" class="w-8 h-8 flex items-center justify-center text-xs text-(--t-text-3)">…</span>
            <button
              v-else
              :class="[
                'w-8 h-8 rounded-lg flex items-center justify-center text-xs font-semibold',
                'transition-all duration-150 active:scale-95',
                p === currentPage
                  ? 'bg-(--t-primary) text-white shadow-md shadow-(--t-glow)/20'
                  : 'bg-(--t-surface) border border-(--t-border) text-(--t-text-2) hover:text-(--t-text) hover:border-(--t-primary)/40',
              ]"
              @click="goToPage(p)"
            >
              {{ p }}
            </button>
          </template>
          <button
            :disabled="currentPage === totalPages"
            class="w-8 h-8 rounded-lg flex items-center justify-center text-xs
                   bg-(--t-surface) border border-(--t-border) text-(--t-text-2)
                   hover:text-(--t-text) hover:border-(--t-primary)/40
                   disabled:opacity-30 disabled:cursor-not-allowed
                   transition-all duration-150 active:scale-95"
            @click="goToPage(currentPage + 1)"
          >
            ›
          </button>
          <span class="ml-3 text-xs text-(--t-text-3)">
            {{ fmtNum(props.totalClients) }} {{ vc.clientLabelPlural.toLowerCase() }}
          </span>
        </div>
      </div>

      <!-- ─── SIDEBAR (desktop) ─── -->
      <Transition name="sidebar-slide-cl">
        <aside
          v-if="showSidebar"
          class="hidden lg:flex flex-col gap-4 shrink-0"
          style="inline-size: 260px"
        >
          <!-- Segments -->
          <VCard title="Сегменты" glow>
            <div class="space-y-1">
              <button
                :class="[
                  'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-all duration-150',
                  filters.segment === '' ? 'bg-(--t-primary)/10 text-(--t-primary)' : 'text-(--t-text-2) hover:bg-(--t-card-hover) hover:text-(--t-text)',
                ]"
                @click="applyFilter('segment', '')"
              >
                <span>📋 Все</span>
                <span class="font-bold">{{ fmtNum(segmentCounts.all) }}</span>
              </button>
              <button
                v-for="(label, segKey) in vc.segmentLabels"
                :key="segKey"
                :class="[
                  'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-all duration-150',
                  filters.segment === segKey ? 'bg-(--t-primary)/10 text-(--t-primary)' : 'text-(--t-text-2) hover:bg-(--t-card-hover) hover:text-(--t-text)',
                ]"
                @click="applyFilter('segment', filters.segment === segKey ? '' : segKey)"
              >
                <span>{{ label }}</span>
                <span class="font-bold">{{ segmentCounts[segKey] ?? 0 }}</span>
              </button>
            </div>
          </VCard>

          <!-- Status -->
          <VCard title="Статус">
            <div class="space-y-1">
              <button
                v-for="(cfg, stKey) in { active: 'Активные', inactive: 'Неактивные', blocked: 'Заблокированные' } as Record<string, string>"
                :key="stKey"
                :class="[
                  'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-all duration-150',
                  filters.status === stKey ? 'bg-(--t-primary)/10 text-(--t-primary)' : 'text-(--t-text-2) hover:bg-(--t-card-hover) hover:text-(--t-text)',
                ]"
                @click="applyFilter('status', filters.status === stKey ? '' : stKey)"
              >
                <span>{{ cfg }}</span>
              </button>
            </div>
          </VCard>

          <!-- Sources -->
          <VCard title="Источник">
            <div class="space-y-1">
              <button
                v-for="(label, srcKey) in vc.sourceLabels"
                :key="srcKey"
                :class="[
                  'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-all duration-150',
                  filters.source === srcKey ? 'bg-(--t-primary)/10 text-(--t-primary)' : 'text-(--t-text-2) hover:bg-(--t-card-hover) hover:text-(--t-text)',
                ]"
                @click="applyFilter('source', filters.source === srcKey ? '' : srcKey)"
              >
                <span>{{ label }}</span>
                <span class="font-bold text-(--t-text-3)">{{ sourceCounts[srcKey] ?? 0 }}</span>
              </button>
            </div>
          </VCard>

          <!-- Tags -->
          <VCard v-if="availableTags.length > 0" title="Теги">
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="tag in availableTags.slice(0, 20)"
                :key="tag"
                :class="[
                  'px-2 py-1 rounded-lg text-[10px] font-medium transition-all duration-150',
                  filters.tag === tag
                    ? 'bg-(--t-primary)/20 text-(--t-primary) border border-(--t-primary)/30'
                    : 'bg-(--t-card-hover) text-(--t-text-3) hover:text-(--t-text) border border-transparent',
                ]"
                @click="applyFilter('tag', filters.tag === tag ? '' : tag)"
              >
                #{{ tag }}
              </button>
            </div>
          </VCard>

          <!-- LTV filter -->
          <VCard title="Фильтр по LTV">
            <div class="space-y-3">
              <VInput
                :model-value="String(filters.ltvMin ?? '')"
                label="Мин. LTV (₽)"
                type="number"
                size="sm"
                @update:model-value="applyFilter('ltvMin', $event ? Number($event) : null)"
              />
              <VInput
                :model-value="String(filters.ltvMax ?? '')"
                label="Макс. LTV (₽)"
                type="number"
                size="sm"
                @update:model-value="applyFilter('ltvMax', $event ? Number($event) : null)"
              />
            </div>
          </VCard>
        </aside>
      </Transition>
    </div>

    <!-- ═══════════════════════════════════════════════════
         CLIENT DETAIL MODAL
    ══════════════════════════════════════════════════════ -->
    <VModal v-model="showClientModal" :title="`${vc.clientLabel} #${selectedClient?.id ?? ''}`" size="lg" @close="closeClientModal">
      <template v-if="selectedClient">
        <div class="space-y-5">

          <!-- Profile header -->
          <div class="flex items-start gap-4">
            <div
              class="w-16 h-16 rounded-2xl flex items-center justify-center text-2xl font-bold text-white shrink-0"
              :style="{ background: avatarColor(selectedClient.id) }"
            >
              {{ selectedClient.avatar ?? avatarInitials(selectedClient.fullName) }}
            </div>
            <div class="flex-1 min-w-0">
              <h2 class="text-lg font-bold text-(--t-text)">{{ selectedClient.fullName }}</h2>
              <div class="flex items-center gap-2 mt-1">
                <VBadge
                  :text="vc.segmentLabels[selectedClient.segment] ?? selectedClient.segment"
                  :variant="SEGMENT_BADGE[selectedClient.segment]?.variant ?? 'neutral'"
                  :dot="true"
                />
                <VBadge
                  :text="selectedClient.status === 'active' ? 'Активен' : selectedClient.status === 'inactive' ? 'Неактивен' : 'Заблокирован'"
                  :variant="STATUS_BADGE[selectedClient.status]?.variant ?? 'neutral'"
                  :dot="selectedClient.status === 'active'"
                />
                <span v-if="selectedClient.rating" class="text-sm text-amber-400">
                  {{ starRating(selectedClient.rating) }}
                </span>
              </div>
              <div class="flex items-center gap-4 mt-2 text-xs text-(--t-text-3)">
                <a :href="`tel:${selectedClient.phone}`" class="hover:text-(--t-primary) transition-colors">
                  📱 {{ selectedClient.phone }}
                </a>
                <a :href="`mailto:${selectedClient.email}`" class="hover:text-(--t-primary) transition-colors">
                  ✉️ {{ selectedClient.email }}
                </a>
              </div>
              <div v-if="selectedClient.isB2B" class="mt-1.5 text-xs text-sky-400">
                🏢 {{ selectedClient.companyName }} · ИНН {{ selectedClient.inn }}
              </div>
            </div>
          </div>

          <!-- Metrics grid -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="rounded-xl p-3 bg-(--t-card-hover)">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Заказов</div>
              <div class="text-lg font-bold text-(--t-text) mt-0.5">{{ fmtNum(selectedClient.totalOrders) }}</div>
            </div>
            <div class="rounded-xl p-3 bg-(--t-card-hover)">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Потрачено</div>
              <div class="text-lg font-bold text-emerald-400 mt-0.5">{{ fmtMoney(selectedClient.totalSpent) }}</div>
            </div>
            <div class="rounded-xl p-3 bg-(--t-card-hover)">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Средний чек</div>
              <div class="text-lg font-bold text-(--t-text) mt-0.5">{{ fmtMoney(selectedClient.avgCheck) }}</div>
            </div>
            <div class="rounded-xl p-3 bg-(--t-card-hover)">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">LTV</div>
              <div class="text-lg font-bold text-violet-400 mt-0.5">{{ fmtMoney(selectedClient.ltv) }}</div>
            </div>
          </div>

          <!-- Info rows -->
          <div class="grid grid-cols-2 gap-3 text-xs">
            <div class="rounded-xl p-3 bg-(--t-card-hover)">
              <span class="text-(--t-text-3)">Первый визит</span>
              <div class="text-(--t-text) font-medium mt-0.5">{{ fmtDate(selectedClient.firstVisit) }}</div>
            </div>
            <div class="rounded-xl p-3 bg-(--t-card-hover)">
              <span class="text-(--t-text-3)">Последний визит</span>
              <div class="text-(--t-text) font-medium mt-0.5">{{ relativeDate(selectedClient.lastVisit) }}</div>
            </div>
          </div>

          <!-- Bonus -->
          <div v-if="selectedClient.bonusBalance > 0" class="rounded-xl p-3 bg-amber-500/10 border border-amber-500/20">
            <div class="flex items-center justify-between">
              <span class="text-sm font-semibold text-amber-300">🎁 Бонусный баланс</span>
              <span class="text-lg font-bold text-amber-400">{{ fmtNum(selectedClient.bonusBalance) }} ₽</span>
            </div>
          </div>

          <!-- Source + tags -->
          <div class="flex flex-wrap items-center gap-2">
            <VBadge :text="vc.sourceLabels[selectedClient.source] ?? selectedClient.source" variant="info" size="xs" />
            <span
              v-for="tag in selectedClient.tags"
              :key="tag"
              class="inline-flex px-2 py-0.5 rounded-lg text-[10px] font-medium
                     bg-(--t-primary)/10 text-(--t-primary)/80"
            >
              #{{ tag }}
            </span>
          </div>

          <!-- Notes -->
          <div v-if="selectedClient.notes" class="rounded-xl p-3 bg-(--t-card-hover)">
            <div class="text-[10px] uppercase tracking-wider text-(--t-text-3) mb-1">Заметки</div>
            <p class="text-sm text-(--t-text-2) whitespace-pre-line">{{ selectedClient.notes }}</p>
          </div>

          <!-- Quick actions (vertical-specific) -->
          <div class="flex flex-wrap gap-2">
            <VButton
              v-for="action in vc.quickActions"
              :key="action.key"
              variant="secondary"
              size="sm"
            >
              {{ action.icon }} {{ action.label }}
            </VButton>
          </div>
        </div>
      </template>
      <template #footer>
        <VButton variant="ghost" size="sm" @click="closeClientModal">Закрыть</VButton>
        <VButton variant="secondary" size="sm" @click="emit('client-edit', selectedClient!); closeClientModal()">
          ✏️ Редактировать
        </VButton>
        <VButton variant="danger" size="sm" @click="emit('client-delete', [selectedClient!.id]); closeClientModal()">
          🗑️ Удалить
        </VButton>
      </template>
    </VModal>

    <!-- ═══════════════════════════════════════════════════
         MOBILE FILTERS DRAWER
    ══════════════════════════════════════════════════════ -->
    <VModal v-model="showFiltersDrawer" title="🔍 Фильтры" size="md">
      <div class="space-y-5">

        <!-- Segment -->
        <div>
          <h4 class="text-xs font-semibold text-(--t-text-2) mb-2">Сегмент</h4>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="(label, segKey) in vc.segmentLabels"
              :key="segKey"
              :class="[
                'px-3 py-1.5 rounded-xl text-xs font-medium transition-all duration-150',
                filters.segment === segKey
                  ? 'bg-(--t-primary)/20 text-(--t-primary) border border-(--t-primary)/30'
                  : 'bg-(--t-card-hover) text-(--t-text-3) border border-transparent hover:text-(--t-text)',
              ]"
              @click="applyFilter('segment', filters.segment === segKey ? '' : segKey)"
            >
              {{ label }}
            </button>
          </div>
        </div>

        <!-- Status -->
        <div>
          <h4 class="text-xs font-semibold text-(--t-text-2) mb-2">Статус</h4>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="(label, stKey) in { active: '🟢 Активные', inactive: '⚪ Неактивные', blocked: '🔴 Заблокированные' } as Record<string, string>"
              :key="stKey"
              :class="[
                'px-3 py-1.5 rounded-xl text-xs font-medium transition-all duration-150',
                filters.status === stKey
                  ? 'bg-(--t-primary)/20 text-(--t-primary) border border-(--t-primary)/30'
                  : 'bg-(--t-card-hover) text-(--t-text-3) border border-transparent hover:text-(--t-text)',
              ]"
              @click="applyFilter('status', filters.status === stKey ? '' : stKey)"
            >
              {{ label }}
            </button>
          </div>
        </div>

        <!-- Source -->
        <div>
          <h4 class="text-xs font-semibold text-(--t-text-2) mb-2">Источник</h4>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="(label, srcKey) in vc.sourceLabels"
              :key="srcKey"
              :class="[
                'px-3 py-1.5 rounded-xl text-xs font-medium transition-all duration-150',
                filters.source === srcKey
                  ? 'bg-(--t-primary)/20 text-(--t-primary) border border-(--t-primary)/30'
                  : 'bg-(--t-card-hover) text-(--t-text-3) border border-transparent hover:text-(--t-text)',
              ]"
              @click="applyFilter('source', filters.source === srcKey ? '' : srcKey)"
            >
              {{ label }}
            </button>
          </div>
        </div>

        <!-- Date range -->
        <div class="grid grid-cols-2 gap-3">
          <VInput v-model="filters.dateFrom" label="Дата от" type="date" size="sm" />
          <VInput v-model="filters.dateTo" label="Дата до" type="date" size="sm" />
        </div>

        <!-- LTV -->
        <div class="grid grid-cols-2 gap-3">
          <VInput
            :model-value="String(filters.ltvMin ?? '')"
            label="Мин. LTV"
            type="number"
            size="sm"
            @update:model-value="filters.ltvMin = $event ? Number($event) : null"
          />
          <VInput
            :model-value="String(filters.ltvMax ?? '')"
            label="Макс. LTV"
            type="number"
            size="sm"
            @update:model-value="filters.ltvMax = $event ? Number($event) : null"
          />
        </div>
      </div>
      <template #footer>
        <VButton variant="ghost" size="sm" @click="clearAllFilters(); showFiltersDrawer = false">Сбросить</VButton>
        <VButton variant="primary" size="sm" @click="emitFilters(); showFiltersDrawer = false">Применить</VButton>
      </template>
    </VModal>
  </div>
</template>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     STYLES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<style scoped>
/* ── Ripple ─────────────────────────────── */
@keyframes ripple-cl {
  to { transform: scale(3.5); opacity: 0; }
}

/* ── Slide-down (bulk bar) ──────────────── */
.slide-down-cl-enter-active,
.slide-down-cl-leave-active {
  transition: all 0.3s cubic-bezier(.4, 0, .2, 1);
}
.slide-down-cl-enter-from,
.slide-down-cl-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* ── Sidebar slide ──────────────────────── */
.sidebar-slide-cl-enter-active {
  transition: all 0.35s cubic-bezier(.4, 0, .2, 1);
}
.sidebar-slide-cl-leave-active {
  transition: all 0.25s cubic-bezier(.4, 0, .2, 1);
}
.sidebar-slide-cl-enter-from,
.sidebar-slide-cl-leave-to {
  opacity: 0;
  transform: translateX(16px);
}

/* ── Dropdown ───────────────────────────── */
.dropdown-cl-enter-active {
  transition: all 0.2s cubic-bezier(.4, 0, .2, 1);
}
.dropdown-cl-leave-active {
  transition: all 0.15s cubic-bezier(.4, 0, .2, 1);
}
.dropdown-cl-enter-from,
.dropdown-cl-leave-to {
  opacity: 0;
  transform: translateY(-4px) scale(0.97);
}

/* ── Scrollbar ──────────────────────────── */
.overflow-x-auto::-webkit-scrollbar {
  block-size: 5px;
}
.overflow-x-auto::-webkit-scrollbar-track {
  background: transparent;
}
.overflow-x-auto::-webkit-scrollbar-thumb {
  background: var(--t-border);
  border-radius: 9999px;
}

/* ── Table row hover ripple feel ────────── */
tbody tr {
  position: relative;
  overflow: hidden;
}
</style>
