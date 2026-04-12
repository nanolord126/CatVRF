<script setup lang="ts">
/**
 * TenantStaff.vue — Главная страница управления персоналом B2B Tenant Dashboard
 *
 * Поддержка всех 127 вертикалей CatVRF:
 *   Beauty (мастера) · Taxi (водители) · Food (повара, курьеры)
 *   Hotels (горничные, консьержи) · RealEstate (агенты) · Flowers (флористы)
 *   Fashion (стилисты) · Furniture (замерщики, сборщики) · Fitness (тренеры)
 *   Travel (гиды, менеджеры) · Medical (врачи) · Auto (механики) · и т.д.
 *
 * ─────────────────────────────────────────────────────────────
 *  Функционал:
 *   1. Таблица (desktop) / Карточки (mobile) — адаптивное переключение
 *   2. Поиск (по имени, телефону, email, должности)
 *   3. Расширенные фильтры: роль, статус, филиал, смена, рейтинг
 *   4. Массовые действия: изменить статус, назначить смену, экспорт
 *   5. Боковая панель быстрых фильтров (desktop)
 *   6. Full-screen режим
 *   7. Пагинация + бесконечный скролл
 *   8. Карточка сотрудника (модал) со статистикой и KPI
 *   9. B2B/B2C различия (филиалы, контракты, права)
 *  10. Вертикаль-зависимая терминология через VERTICAL_STAFF_CONFIG
 *  11. Графики работы + KPI-метрики + история работы
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

type EmploymentType = 'full_time' | 'part_time' | 'contract' | 'freelance'
type StaffStatus    = 'active' | 'on_leave' | 'suspended' | 'terminated'
type ShiftStatus    = 'online' | 'offline' | 'on_break' | 'busy'

interface StaffMember {
  id:              number | string
  fullName:        string
  phone:           string
  email:           string
  avatar?:         string
  role:            string                   // 'master' | 'driver' | 'courier' | ...
  roleLabel:       string                   // «Мастер-стилист» / «Водитель категории B»
  position:        string                   // Должность
  status:          StaffStatus
  shiftStatus:     ShiftStatus
  employmentType:  EmploymentType
  branchName?:     string                   // Филиал (B2B)
  branchId?:       string
  hireDate:        string                   // ISO
  rating:          number                   // 0–5
  totalOrders:     number                   // Обслужено заказов
  totalRevenue:    number                   // Выручка ₽
  avgRating:       number
  completionRate:  number                   // 0–100 %
  salary:          number                   // Базовая зарплата
  bonusBalance:    number                   // Бонусы за KPI
  todayOrders:     number                   // Заказов сегодня
  isOnline:        boolean
  lastActivity:    string                   // ISO
  tags:            string[]
  schedule?:       string                   // «Пн–Пт 9:00–18:00»
  specializations: string[]                 // «Стрижки», «Окрашивание»
  permissions:     string[]                 // «bookings.manage», «inventory.view»
  notes?:          string
  correlationId?:  string
  verticalData?:   Record<string, unknown>
}

interface StaffFilter {
  search:         string
  role:           string
  status:         string
  shiftStatus:    string
  branch:         string
  employmentType: string
  sortBy:         string
  sortDir:        'asc' | 'desc'
}

interface StaffStats {
  total:          number
  active:         number
  onLeave:        number
  suspended:      number
  terminated:     number
  online:         number
  onBreak:        number
  busy:           number
  avgRating:      number
  totalRevenue:   number
  avgCompletionRate: number
}

interface VerticalStaffConfig {
  /** Терминология */
  staffLabel:       string          // «Сотрудник» / «Мастер» / «Водитель»
  staffLabelPlural: string          // «Сотрудники» / «Мастера» / «Водители»
  icon:             string
  /** Роли в вертикали */
  roles:            Array<{ key: string; label: string; icon: string }>
  /** Доп. колонки таблицы */
  extraColumns:     Array<{ key: string; label: string }>
  /** KPI-метки */
  kpiLabels:        { orders: string; revenue: string; completion: string }
  /** Кнопка создания */
  quickCreate:      { label: string; icon: string }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  PROPS & EMITS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const props = withDefaults(defineProps<{
  vertical?:    string
  staff?:       StaffMember[]
  stats?:       StaffStats
  branches?:    Array<{ id: string; name: string }>
  totalStaff?:  number
  loading?:     boolean
  perPage?:     number
}>(), {
  vertical:   'default',
  staff:      () => [],
  stats:      () => ({
    total: 0, active: 0, onLeave: 0, suspended: 0, terminated: 0,
    online: 0, onBreak: 0, busy: 0,
    avgRating: 0, totalRevenue: 0, avgCompletionRate: 0,
  }),
  branches:   () => [],
  totalStaff: 0,
  loading:    false,
  perPage:    25,
})

const emit = defineEmits<{
  'staff-click':       [member: StaffMember]
  'staff-create':      []
  'staff-edit':        [member: StaffMember]
  'staff-terminate':   [memberIds: Array<number | string>]
  'staff-suspend':     [memberIds: Array<number | string>]
  'staff-activate':    [memberIds: Array<number | string>]
  'status-change':     [memberId: number | string, newStatus: StaffStatus]
  'shift-change':      [memberId: number | string, newShift: ShiftStatus]
  'schedule-assign':   [memberIds: Array<number | string>]
  'permissions-edit':  [memberId: number | string]
  'filter-change':     [filters: StaffFilter]
  'sort-change':       [sortBy: string, sortDir: 'asc' | 'desc']
  'page-change':       [page: number]
  'bulk-action':       [action: string, memberIds: Array<number | string>]
  'export':            [format: 'xlsx' | 'csv']
  'load-more':         []
}>()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STORES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const auth     = useAuth()
const business = useTenant()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  VERTICAL STAFF CONFIG
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const VERTICAL_STAFF_CONFIG: Record<string, VerticalStaffConfig> = {
  // ── BEAUTY ──────────────────────────────────
  beauty: {
    staffLabel: 'Мастер', staffLabelPlural: 'Мастера', icon: '💄',
    roles: [
      { key: 'stylist',     label: 'Стилист',    icon: '💇' },
      { key: 'colorist',    label: 'Колорист',   icon: '🎨' },
      { key: 'nail_master', label: 'Мастер маникюра', icon: '💅' },
      { key: 'cosmetologist', label: 'Косметолог', icon: '🧴' },
      { key: 'admin',       label: 'Администратор', icon: '🗂️' },
    ],
    extraColumns: [
      { key: 'specializations', label: 'Специализация' },
      { key: 'todayClients', label: 'Клиентов сегодня' },
    ],
    kpiLabels: { orders: 'Услуг оказано', revenue: 'Выручка', completion: 'Явка клиентов' },
    quickCreate: { label: 'Добавить мастера', icon: '💇' },
  },

  // ── TAXI ────────────────────────────────────
  taxi: {
    staffLabel: 'Водитель', staffLabelPlural: 'Водители', icon: '🚕',
    roles: [
      { key: 'driver_economy', label: 'Эконом', icon: '🚗' },
      { key: 'driver_comfort', label: 'Комфорт', icon: '🚙' },
      { key: 'driver_business', label: 'Бизнес', icon: '🚘' },
      { key: 'driver_cargo',   label: 'Грузовой', icon: '🚛' },
      { key: 'dispatcher',     label: 'Диспетчер', icon: '📞' },
    ],
    extraColumns: [
      { key: 'vehicleModel', label: 'Автомобиль' },
      { key: 'licensePlate', label: 'Гос. номер' },
      { key: 'todayRides', label: 'Поездок сегодня' },
    ],
    kpiLabels: { orders: 'Поездок', revenue: 'Выручка', completion: 'Выполнение' },
    quickCreate: { label: 'Добавить водителя', icon: '🚗' },
  },

  // ── FOOD ────────────────────────────────────
  food: {
    staffLabel: 'Сотрудник', staffLabelPlural: 'Сотрудники', icon: '🍽️',
    roles: [
      { key: 'chef',      label: 'Шеф-повар',  icon: '👨‍🍳' },
      { key: 'cook',      label: 'Повар',       icon: '🍳' },
      { key: 'courier',   label: 'Курьер',      icon: '🚴' },
      { key: 'waiter',    label: 'Официант',    icon: '🍽️' },
      { key: 'admin',     label: 'Администратор', icon: '🗂️' },
    ],
    extraColumns: [
      { key: 'kitchen', label: 'Кухня' },
      { key: 'todayDeliveries', label: 'Доставок сегодня' },
    ],
    kpiLabels: { orders: 'Заказов', revenue: 'Выручка', completion: 'Доставлено в срок' },
    quickCreate: { label: 'Добавить сотрудника', icon: '👨‍🍳' },
  },

  // ── HOTEL ───────────────────────────────────
  hotel: {
    staffLabel: 'Сотрудник', staffLabelPlural: 'Сотрудники', icon: '🏨',
    roles: [
      { key: 'receptionist', label: 'Ресепшн',     icon: '🛎️' },
      { key: 'concierge',    label: 'Консьерж',    icon: '🎩' },
      { key: 'housekeeper',  label: 'Горничная',   icon: '🧹' },
      { key: 'porter',       label: 'Портье',      icon: '🧳' },
      { key: 'manager',      label: 'Менеджер',    icon: '📋' },
    ],
    extraColumns: [
      { key: 'floor', label: 'Этаж/Зона' },
      { key: 'todayRooms', label: 'Обслужено номеров' },
    ],
    kpiLabels: { orders: 'Бронирований', revenue: 'Выручка', completion: 'Удовлетворённость' },
    quickCreate: { label: 'Добавить сотрудника', icon: '🛎️' },
  },

  // ── REAL ESTATE ─────────────────────────────
  realEstate: {
    staffLabel: 'Агент', staffLabelPlural: 'Агенты', icon: '🏢',
    roles: [
      { key: 'agent',    label: 'Агент',         icon: '🔑' },
      { key: 'appraiser', label: 'Оценщик',      icon: '📐' },
      { key: 'broker',   label: 'Брокер',        icon: '🤝' },
      { key: 'manager',  label: 'Менеджер',      icon: '📋' },
    ],
    extraColumns: [
      { key: 'activeListings', label: 'Активных объектов' },
      { key: 'closedDeals', label: 'Закрытых сделок' },
    ],
    kpiLabels: { orders: 'Показов', revenue: 'Комиссия', completion: 'Конверсия' },
    quickCreate: { label: 'Добавить агента', icon: '🔑' },
  },

  // ── FLOWERS ─────────────────────────────────
  flowers: {
    staffLabel: 'Флорист', staffLabelPlural: 'Флористы', icon: '💐',
    roles: [
      { key: 'florist',  label: 'Флорист',       icon: '💐' },
      { key: 'courier',  label: 'Курьер',        icon: '🚴' },
      { key: 'designer', label: 'Декоратор',     icon: '🎨' },
      { key: 'admin',    label: 'Администратор', icon: '🗂️' },
    ],
    extraColumns: [
      { key: 'todayBouquets', label: 'Букетов сегодня' },
    ],
    kpiLabels: { orders: 'Букетов', revenue: 'Выручка', completion: 'В срок' },
    quickCreate: { label: 'Добавить флориста', icon: '💐' },
  },

  // ── FASHION ─────────────────────────────────
  fashion: {
    staffLabel: 'Стилист', staffLabelPlural: 'Стилисты', icon: '👗',
    roles: [
      { key: 'stylist',     label: 'Стилист',       icon: '👗' },
      { key: 'consultant',  label: 'Консультант',   icon: '🛍️' },
      { key: 'tailor',      label: 'Портной',       icon: '🪡' },
      { key: 'warehouse',   label: 'Кладовщик',     icon: '📦' },
      { key: 'admin',       label: 'Администратор', icon: '🗂️' },
    ],
    extraColumns: [
      { key: 'todayConsults', label: 'Консультаций' },
      { key: 'returnRate', label: 'Возвратов %' },
    ],
    kpiLabels: { orders: 'Консультаций', revenue: 'Выручка', completion: 'Конверсия' },
    quickCreate: { label: 'Добавить стилиста', icon: '👗' },
  },

  // ── FURNITURE ───────────────────────────────
  furniture: {
    staffLabel: 'Сотрудник', staffLabelPlural: 'Сотрудники', icon: '🛋️',
    roles: [
      { key: 'measurer',   label: 'Замерщик',    icon: '📐' },
      { key: 'assembler',  label: 'Сборщик',     icon: '🔧' },
      { key: 'designer',   label: 'Дизайнер',    icon: '🎨' },
      { key: 'courier',    label: 'Доставщик',   icon: '🚚' },
      { key: 'manager',    label: 'Менеджер',    icon: '📋' },
    ],
    extraColumns: [
      { key: 'todayProjects', label: 'Проектов' },
      { key: 'region', label: 'Регион' },
    ],
    kpiLabels: { orders: 'Проектов', revenue: 'Выручка', completion: 'В срок' },
    quickCreate: { label: 'Добавить сотрудника', icon: '🔧' },
  },

  // ── FITNESS ─────────────────────────────────
  fitness: {
    staffLabel: 'Тренер', staffLabelPlural: 'Тренеры', icon: '💪',
    roles: [
      { key: 'personal_trainer', label: 'Персональный тренер', icon: '🏋️' },
      { key: 'group_trainer',    label: 'Групповой тренер',    icon: '👥' },
      { key: 'nutritionist',     label: 'Нутрициолог',         icon: '🥗' },
      { key: 'physio',           label: 'Физиотерапевт',       icon: '🩺' },
      { key: 'admin',            label: 'Администратор',       icon: '🗂️' },
    ],
    extraColumns: [
      { key: 'certifications', label: 'Сертификаты' },
      { key: 'todaySessions', label: 'Тренировок сегодня' },
    ],
    kpiLabels: { orders: 'Тренировок', revenue: 'Выручка', completion: 'Посещаемость' },
    quickCreate: { label: 'Добавить тренера', icon: '🏋️' },
  },

  // ── TRAVEL ──────────────────────────────────
  travel: {
    staffLabel: 'Менеджер', staffLabelPlural: 'Менеджеры', icon: '✈️',
    roles: [
      { key: 'tour_manager', label: 'Тур-менеджер', icon: '🗺️' },
      { key: 'guide',        label: 'Гид',          icon: '🧭' },
      { key: 'transfer',     label: 'Трансфер',     icon: '🚐' },
      { key: 'visa_spec',    label: 'Визовый специалист', icon: '📝' },
      { key: 'admin',        label: 'Администратор', icon: '🗂️' },
    ],
    extraColumns: [
      { key: 'activeTrips', label: 'Активных туров' },
      { key: 'languages', label: 'Языки' },
    ],
    kpiLabels: { orders: 'Бронирований', revenue: 'Выручка', completion: 'Конверсия' },
    quickCreate: { label: 'Добавить менеджера', icon: '🗺️' },
  },

  // ── DEFAULT ─────────────────────────────────
  default: {
    staffLabel: 'Сотрудник', staffLabelPlural: 'Сотрудники', icon: '👥',
    roles: [
      { key: 'employee',  label: 'Сотрудник',     icon: '👤' },
      { key: 'manager',   label: 'Менеджер',      icon: '📋' },
      { key: 'admin',     label: 'Администратор', icon: '🗂️' },
    ],
    extraColumns: [],
    kpiLabels: { orders: 'Заказов', revenue: 'Выручка', completion: 'Выполнение' },
    quickCreate: { label: 'Добавить сотрудника', icon: '➕' },
  },
}

const vs = computed<VerticalStaffConfig>(() =>
  VERTICAL_STAFF_CONFIG[props.vertical] ?? VERTICAL_STAFF_CONFIG.default
)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STATUS MAPS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const STAFF_STATUS_MAP: Record<StaffStatus, { label: string; badge: string; color: string }> = {
  active:     { label: '✅ Активен',     badge: 'success', color: 'emerald' },
  on_leave:   { label: '🏖️ В отпуске',   badge: 'warning', color: 'amber' },
  suspended:  { label: '⏸️ Приостановлен', badge: 'danger',  color: 'rose' },
  terminated: { label: '🚫 Уволен',      badge: 'neutral', color: 'zinc' },
}

const SHIFT_STATUS_MAP: Record<ShiftStatus, { label: string; dot: string }> = {
  online:   { label: 'Онлайн',   dot: 'bg-emerald-400' },
  offline:  { label: 'Офлайн',   dot: 'bg-zinc-500' },
  on_break: { label: 'Перерыв',  dot: 'bg-amber-400' },
  busy:     { label: 'Занят',    dot: 'bg-sky-400' },
}

const EMPLOYMENT_LABELS: Record<EmploymentType, string> = {
  full_time: 'Полная',
  part_time: 'Частичная',
  contract:  'Контракт',
  freelance: 'Фриланс',
}

const ALL_STATUSES: StaffStatus[] = ['active', 'on_leave', 'suspended', 'terminated']
const ALL_SHIFTS: ShiftStatus[]   = ['online', 'offline', 'on_break', 'busy']

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STATE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const rootEl          = ref<HTMLElement | null>(null)
const scrollSentinel  = ref<HTMLElement | null>(null)
const isFullscreen    = ref(false)
const showSidebar     = ref(true)
const showStaffModal  = ref(false)
const showExportMenu  = ref(false)
const showFilterDrawer = ref(false)
const selectedMember  = ref<StaffMember | null>(null)
const currentPage     = ref(1)
const modalTab        = ref<'info' | 'kpi' | 'schedule' | 'permissions'>('info')

// Bulk
const selectedIds = reactive<Set<number | string>>(new Set())
const isBulkMode  = ref(false)
const selectAll   = ref(false)

// View
const viewAs = ref<'table' | 'cards'>('table')

// Filters
const filters = reactive<StaffFilter>({
  search:         '',
  role:           '',
  status:         '',
  shiftStatus:    '',
  branch:         '',
  employmentType: '',
  sortBy:         'fullName',
  sortDir:        'asc',
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  COMPUTED
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const totalPages = computed(() => Math.ceil(props.totalStaff / props.perPage) || 1)

const hasActiveFilters = computed(() =>
  filters.role !== '' ||
  filters.status !== '' ||
  filters.shiftStatus !== '' ||
  filters.branch !== '' ||
  filters.employmentType !== ''
)

const statusCountMap = computed<Record<StaffStatus, number>>(() => ({
  active:     props.stats.active,
  on_leave:   props.stats.onLeave,
  suspended:  props.stats.suspended,
  terminated: props.stats.terminated,
}))

const shiftCountMap = computed<Record<ShiftStatus, number>>(() => ({
  online:   props.stats.online,
  offline:  props.stats.total - props.stats.online - props.stats.onBreak - props.stats.busy,
  on_break: props.stats.onBreak,
  busy:     props.stats.busy,
}))

const roleCounts = computed(() => {
  const map: Record<string, number> = {}
  props.staff.forEach(m => { map[m.role] = (map[m.role] ?? 0) + 1 })
  return map
})

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
//  FILTER / SORT / PAGINATION
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function applyFilter(key: keyof StaffFilter, value: unknown) {
  ;(filters as Record<string, unknown>)[key] = value
  currentPage.value = 1
  emitFilters()
}

function clearAllFilters() {
  filters.search         = ''
  filters.role           = ''
  filters.status         = ''
  filters.shiftStatus    = ''
  filters.branch         = ''
  filters.employmentType = ''
  currentPage.value      = 1
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
    props.staff.forEach(m => selectedIds.add(m.id))
    selectAll.value = true
  }
}

function handleBulkAction(action: string) {
  const ids = Array.from(selectedIds)
  if (action === 'activate')  emit('staff-activate', ids)
  else if (action === 'suspend') emit('staff-suspend', ids)
  else if (action === 'terminate') emit('staff-terminate', ids)
  else if (action === 'schedule') emit('schedule-assign', ids)
  else emit('bulk-action', action, ids)
  selectedIds.clear()
  selectAll.value = false
  isBulkMode.value = false
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STAFF DETAIL MODAL
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function openMember(member: StaffMember) {
  if (isBulkMode.value) { toggleSelect(member.id); return }
  selectedMember.value = member
  modalTab.value = 'info'
  showStaffModal.value = true
  emit('staff-click', member)
}

function closeStaffModal() {
  showStaffModal.value = false
  selectedMember.value = null
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
//  INTERSECTION OBSERVER
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
  el.className = 'absolute rounded-full bg-white/10 pointer-events-none animate-[ripple-sf_0.6s_ease-out]'
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

function fmtPercent(n: number): string {
  return n.toFixed(1) + '%'
}

function fmtDate(iso: string): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', year: 'numeric' })
}

function relativeDate(iso: string): string {
  if (!iso) return '—'
  const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 86_400_000)
  if (diff === 0) return 'Сегодня'
  if (diff === 1) return 'Вчера'
  if (diff < 7) return `${diff} дн. назад`
  if (diff < 30) return `${Math.floor(diff / 7)} нед. назад`
  if (diff < 365) return `${Math.floor(diff / 30)} мес. назад`
  return `${Math.floor(diff / 365)} г. назад`
}

function relativeTime(iso: string): string {
  if (!iso) return '—'
  const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 60_000)
  if (diff < 1) return 'только что'
  if (diff < 60) return `${diff} мин назад`
  if (diff < 1440) return `${Math.floor(diff / 60)} ч назад`
  return relativeDate(iso)
}

function avatarInitials(name: string): string {
  return name.split(' ').slice(0, 2).map(w => w.charAt(0)).join('').toUpperCase()
}

function starRating(r: number): string {
  const full = Math.round(r)
  return '★'.repeat(full) + '☆'.repeat(5 - full)
}

const AVATAR_COLORS = [
  '#6366f1', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6',
  '#ec4899', '#14b8a6', '#f97316', '#06b6d4', '#84cc16',
]

function avatarColor(id: number | string): string {
  const idx = typeof id === 'number' ? id : String(id).charCodeAt(0)
  return AVATAR_COLORS[idx % AVATAR_COLORS.length]
}

function completionBarColor(rate: number): string {
  if (rate >= 90) return 'bg-emerald-500'
  if (rate >= 70) return 'bg-amber-500'
  return 'bg-rose-500'
}

function roleIcon(roleKey: string): string {
  const found = vs.value.roles.find(r => r.key === roleKey)
  return found?.icon ?? '👤'
}

function roleLabel(roleKey: string): string {
  const found = vs.value.roles.find(r => r.key === roleKey)
  return found?.label ?? roleKey
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
         1. HEADER
    ═══════════════════════════════════════════════ -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-3">
        <span class="text-2xl">{{ vs.icon }}</span>
        <div>
          <h1 class="text-xl font-bold text-(--t-text)">{{ vs.staffLabelPlural }}</h1>
          <p class="text-xs text-(--t-text-3)">
            {{ auth.tenantName }}
            · Всего: <b class="text-(--t-text)">{{ fmtNum(props.stats.total) }}</b>
            · Онлайн: <b class="text-emerald-400">{{ fmtNum(props.stats.online) }}</b>
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <VButton variant="primary" size="sm" @click="emit('staff-create')">
          {{ vs.quickCreate.icon }} {{ vs.quickCreate.label }}
        </VButton>
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
         2. STAT CARDS
    ═══════════════════════════════════════════════ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <!-- Total -->
      <div class="relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
                  backdrop-blur-xl p-4 transition-all duration-200 hover:border-(--t-primary)/30
                  group/stat">
        <div class="text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold">
          Всего {{ vs.staffLabelPlural.toLowerCase() }}
        </div>
        <div class="mt-1 text-2xl font-extrabold text-(--t-text)">{{ fmtNum(props.stats.total) }}</div>
        <div class="mt-0.5 text-xs text-emerald-400">{{ props.stats.active }} активных</div>
        <div class="absolute inset-block-end-0 inset-inline-end-0 text-4xl opacity-10 p-2
                    transition-transform duration-300 group-hover/stat:scale-110">{{ vs.icon }}</div>
      </div>

      <!-- Online now -->
      <div class="relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
                  backdrop-blur-xl p-4 transition-all duration-200 hover:border-emerald-500/30
                  group/stat">
        <div class="text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold">Сейчас онлайн</div>
        <div class="mt-1 text-2xl font-extrabold text-emerald-400">{{ fmtNum(props.stats.online) }}</div>
        <div class="mt-0.5 text-xs text-(--t-text-3)">
          {{ props.stats.busy }} занято · {{ props.stats.onBreak }} на перерыве
        </div>
        <div class="absolute inset-block-end-0 inset-inline-end-0 text-4xl opacity-10 p-2
                    transition-transform duration-300 group-hover/stat:scale-110">🟢</div>
      </div>

      <!-- Avg Rating -->
      <div class="relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
                  backdrop-blur-xl p-4 transition-all duration-200 hover:border-amber-500/30
                  group/stat">
        <div class="text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold">Средний рейтинг</div>
        <div class="mt-1 text-2xl font-extrabold text-amber-400">{{ props.stats.avgRating.toFixed(1) }}</div>
        <div class="mt-0.5 text-xs text-amber-400/70">{{ starRating(props.stats.avgRating) }}</div>
        <div class="absolute inset-block-end-0 inset-inline-end-0 text-4xl opacity-10 p-2
                    transition-transform duration-300 group-hover/stat:scale-110">⭐</div>
      </div>

      <!-- Revenue -->
      <div class="relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
                  backdrop-blur-xl p-4 transition-all duration-200 hover:border-violet-500/30
                  group/stat">
        <div class="text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold">{{ vs.kpiLabels.revenue }}</div>
        <div class="mt-1 text-2xl font-extrabold text-violet-400">{{ fmtMoney(props.stats.totalRevenue) }}</div>
        <div class="mt-0.5 text-xs text-(--t-text-3)">
          {{ fmtPercent(props.stats.avgCompletionRate) }} {{ vs.kpiLabels.completion.toLowerCase() }}
        </div>
        <div class="absolute inset-block-end-0 inset-inline-end-0 text-4xl opacity-10 p-2
                    transition-transform duration-300 group-hover/stat:scale-110">💰</div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════
         3. TOOLBAR
    ═══════════════════════════════════════════════ -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <!-- Search -->
      <div class="relative flex-1 max-w-md">
        <input
          v-model="filters.search"
          type="text"
          :placeholder="`Поиск: имя, телефон, должность...`"
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
        >✕</button>
      </div>

      <div class="flex items-center gap-2 flex-wrap">
        <!-- Role filter -->
        <select
          v-model="filters.role"
          class="h-9 rounded-xl px-2 text-xs bg-(--t-surface) border border-(--t-border)
                 text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
          @change="emitFilters"
        >
          <option value="">Роль: все</option>
          <option v-for="r in vs.roles" :key="r.key" :value="r.key">{{ r.icon }} {{ r.label }}</option>
        </select>

        <!-- Status filter -->
        <select
          v-model="filters.status"
          class="h-9 rounded-xl px-2 text-xs bg-(--t-surface) border border-(--t-border)
                 text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
          @change="emitFilters"
        >
          <option value="">Статус: все</option>
          <option v-for="st in ALL_STATUSES" :key="st" :value="st">{{ STAFF_STATUS_MAP[st].label }}</option>
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
        >{{ isBulkMode ? `✓ ${selectedIds.size}` : '☑️' }}</VButton>

        <!-- Export -->
        <div class="relative">
          <VButton variant="ghost" size="sm" @click="showExportMenu = !showExportMenu">📥</VButton>
          <Transition name="dropdown-sf">
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
        <VButton v-if="hasActiveFilters" variant="ghost" size="sm" @click="clearAllFilters">✕ Сбросить</VButton>

        <!-- Mobile filters button -->
        <button
          class="sm:hidden w-9 h-9 rounded-xl flex items-center justify-center
                 bg-(--t-surface) border border-(--t-border) text-(--t-text-2)"
          @click="showFilterDrawer = true"
        >🔽</button>

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
    <Transition name="slide-sf">
      <div
        v-if="isBulkMode && selectedIds.size > 0"
        class="flex items-center gap-3 rounded-xl border border-(--t-primary)/30 bg-(--t-primary)/5
               backdrop-blur-xl p-3"
      >
        <span class="text-sm text-(--t-text)">
          Выбрано: <b class="text-(--t-primary)">{{ selectedIds.size }}</b>
        </span>
        <VButton variant="success" size="xs" @click="handleBulkAction('activate')">✅ Активировать</VButton>
        <VButton variant="warning" size="xs" @click="handleBulkAction('suspend')">⏸️ Приостановить</VButton>
        <VButton variant="ghost" size="xs" @click="handleBulkAction('schedule')">📅 Назначить смену</VButton>
        <VButton variant="danger" size="xs" @click="handleBulkAction('terminate')">🚫 Уволить</VButton>
        <div class="flex-1" />
        <VButton variant="ghost" size="xs" @click="selectedIds.clear(); selectAll = false">Снять выделение</VButton>
      </div>
    </Transition>

    <!-- ═══════════════════════════════════════════════
         4. SHIFT STATUS STRIP (кто online / busy / break)
    ═══════════════════════════════════════════════ -->
    <div class="grid grid-cols-4 gap-2">
      <button
        v-for="sh in ALL_SHIFTS"
        :key="sh"
        :class="[
          'rounded-xl border p-3 text-center transition-all duration-200 cursor-pointer',
          filters.shiftStatus === sh
            ? 'border-(--t-primary)/40 bg-(--t-primary)/10'
            : 'border-(--t-border) bg-(--t-surface) hover:border-(--t-primary)/20',
        ]"
        @click="applyFilter('shiftStatus', filters.shiftStatus === sh ? '' : sh)"
      >
        <div class="flex items-center justify-center gap-1.5">
          <span :class="['w-2 h-2 rounded-full', SHIFT_STATUS_MAP[sh].dot]" />
          <span class="text-xs text-(--t-text-2)">{{ SHIFT_STATUS_MAP[sh].label }}</span>
        </div>
        <div class="text-lg font-black text-(--t-text) mt-1">{{ shiftCountMap[sh] }}</div>
      </button>
    </div>

    <!-- ═══════════════════════════════════════════════
         5. MAIN CONTENT: SIDEBAR + TABLE/CARDS
    ═══════════════════════════════════════════════ -->
    <div class="flex gap-4">
      <!-- ─── SIDEBAR (desktop) ─── -->
      <Transition name="sidebar-sf">
        <aside
          v-if="showSidebar"
          class="hidden lg:flex flex-col gap-3 shrink-0"
          style="inline-size: 220px"
        >
          <!-- Staff status -->
          <div class="rounded-xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-4">
            <div class="text-xs font-semibold text-(--t-text-2) mb-3">📊 Статусы</div>
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
                    ? `bg-${STAFF_STATUS_MAP[st].color}-500/10 text-${STAFF_STATUS_MAP[st].color}-400`
                    : 'text-(--t-text-3) hover:bg-(--t-card-hover) hover:text-(--t-text)',
                ]"
                @click="applyFilter('status', filters.status === st ? '' : st)"
              >
                <span>{{ STAFF_STATUS_MAP[st].label }}</span>
                <span class="font-bold">{{ statusCountMap[st] }}</span>
              </button>
            </div>
          </div>

          <!-- Roles -->
          <div class="rounded-xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-4">
            <div class="text-xs font-semibold text-(--t-text-2) mb-3">🏷️ Роли</div>
            <div class="flex flex-col gap-1 max-h-52 overflow-auto">
              <button
                v-for="r in vs.roles"
                :key="r.key"
                :class="[
                  'flex items-center justify-between rounded-lg px-3 py-2 text-xs transition-all duration-200',
                  filters.role === r.key
                    ? 'bg-(--t-primary)/10 text-(--t-primary)'
                    : 'text-(--t-text-3) hover:bg-(--t-card-hover) hover:text-(--t-text)',
                ]"
                @click="applyFilter('role', filters.role === r.key ? '' : r.key)"
              >
                <span>{{ r.icon }} {{ r.label }}</span>
                <span class="font-bold">{{ roleCounts[r.key] ?? 0 }}</span>
              </button>
            </div>
          </div>

          <!-- Quick stats -->
          <div class="rounded-xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-4">
            <div class="text-xs font-semibold text-(--t-text-2) mb-3">📈 KPI</div>
            <div class="flex flex-col gap-2 text-xs">
              <div class="flex justify-between">
                <span class="text-(--t-text-3)">Ср. рейтинг</span>
                <span class="font-bold text-amber-400">{{ props.stats.avgRating.toFixed(1) }} ⭐</span>
              </div>
              <div class="flex justify-between">
                <span class="text-(--t-text-3)">{{ vs.kpiLabels.completion }}</span>
                <span class="font-bold text-emerald-400">{{ fmtPercent(props.stats.avgCompletionRate) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-(--t-text-3)">Выручка</span>
                <span class="font-bold text-violet-400">{{ fmtMoney(props.stats.totalRevenue) }}</span>
              </div>
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
                <th v-if="isBulkMode" class="p-3 w-10">
                  <input type="checkbox" :checked="selectAll" @change="toggleSelectAll" class="accent-(--t-primary)" />
                </th>
                <th class="p-3 text-start text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold cursor-pointer hover:text-(--t-text) transition-colors"
                    @click="toggleSort('fullName')">
                  {{ vs.staffLabel }} {{ sortIcon('fullName') }}
                </th>
                <th class="p-3 text-start text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold">Роль</th>
                <th class="p-3 text-center text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold">Смена</th>
                <th class="p-3 text-center text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold">Статус</th>
                <th class="p-3 text-center text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold cursor-pointer hover:text-(--t-text) transition-colors"
                    @click="toggleSort('rating')">
                  Рейтинг {{ sortIcon('rating') }}
                </th>
                <th class="p-3 text-end text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold cursor-pointer hover:text-(--t-text) transition-colors"
                    @click="toggleSort('totalOrders')">
                  {{ vs.kpiLabels.orders }} {{ sortIcon('totalOrders') }}
                </th>
                <th class="p-3 text-end text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold cursor-pointer hover:text-(--t-text) transition-colors"
                    @click="toggleSort('totalRevenue')">
                  {{ vs.kpiLabels.revenue }} {{ sortIcon('totalRevenue') }}
                </th>
                <th class="p-3 text-center text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold">
                  {{ vs.kpiLabels.completion }}
                </th>
                <th
                  v-for="col in vs.extraColumns"
                  :key="col.key"
                  class="p-3 text-start text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold"
                >{{ col.label }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="m in props.staff"
                :key="m.id"
                :class="[
                  'border-b border-(--t-border)/50 transition-all duration-150 cursor-pointer',
                  'hover:bg-(--t-card-hover) group/row',
                  selectedIds.has(m.id) ? 'bg-(--t-primary)/5' : '',
                ]"
                @click="openMember(m)"
              >
                <!-- Checkbox -->
                <td v-if="isBulkMode" class="p-3" @click.stop>
                  <input type="checkbox" :checked="selectedIds.has(m.id)" @change="toggleSelect(m.id)" class="accent-(--t-primary)" />
                </td>
                <!-- Name + avatar -->
                <td class="p-3">
                  <div class="flex items-center gap-2.5">
                    <div class="relative shrink-0">
                      <div
                        class="w-9 h-9 rounded-full flex items-center justify-center text-[11px] font-bold text-white"
                        :style="{ backgroundColor: avatarColor(m.id) }"
                      >{{ m.avatar ? '' : avatarInitials(m.fullName) }}
                        <img v-if="m.avatar" :src="m.avatar" class="w-full h-full rounded-full object-cover" />
                      </div>
                      <!-- Online dot -->
                      <span
                        :class="[
                          'absolute -inset-block-end-0.5 -inset-inline-end-0.5 w-3 h-3 rounded-full border-2 border-(--t-surface)',
                          SHIFT_STATUS_MAP[m.shiftStatus].dot,
                        ]"
                      />
                    </div>
                    <div class="min-w-0">
                      <div class="text-sm font-medium text-(--t-text) truncate">{{ m.fullName }}</div>
                      <div class="text-[10px] text-(--t-text-3) truncate">{{ m.phone }}</div>
                    </div>
                  </div>
                </td>
                <!-- Role -->
                <td class="p-3 text-xs text-(--t-text-2)">
                  <span>{{ roleIcon(m.role) }} {{ m.roleLabel }}</span>
                </td>
                <!-- Shift -->
                <td class="p-3 text-center">
                  <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-(--t-card-hover) text-[10px]">
                    <span :class="['w-1.5 h-1.5 rounded-full', SHIFT_STATUS_MAP[m.shiftStatus].dot]" />
                    <span class="text-(--t-text-2)">{{ SHIFT_STATUS_MAP[m.shiftStatus].label }}</span>
                  </div>
                </td>
                <!-- Status -->
                <td class="p-3 text-center">
                  <VBadge
                    :text="STAFF_STATUS_MAP[m.status].label.replace(/^\S+\s/, '')"
                    :variant="STAFF_STATUS_MAP[m.status].badge"
                    size="xs"
                  />
                </td>
                <!-- Rating -->
                <td class="p-3 text-center text-xs text-amber-400 whitespace-nowrap">
                  {{ m.rating.toFixed(1) }} ⭐
                </td>
                <!-- Orders -->
                <td class="p-3 text-end text-sm font-medium text-(--t-text) tabular-nums">
                  {{ fmtNum(m.totalOrders) }}
                </td>
                <!-- Revenue -->
                <td class="p-3 text-end text-sm font-semibold text-emerald-400 tabular-nums whitespace-nowrap">
                  {{ fmtMoney(m.totalRevenue) }}
                </td>
                <!-- Completion rate -->
                <td class="p-3">
                  <div class="flex items-center gap-2">
                    <div class="flex-1 h-1.5 rounded-full bg-(--t-border) overflow-hidden">
                      <div :class="['h-full rounded-full transition-all', completionBarColor(m.completionRate)]"
                           :style="{ inlineSize: `${Math.min(m.completionRate, 100)}%` }" />
                    </div>
                    <span class="text-[10px] text-(--t-text-3) tabular-nums" style="min-inline-size: 36px">
                      {{ fmtPercent(m.completionRate) }}
                    </span>
                  </div>
                </td>
                <!-- Extra columns -->
                <td
                  v-for="col in vs.extraColumns"
                  :key="col.key"
                  class="p-3 text-xs text-(--t-text-3)"
                >{{ m.verticalData?.[col.key] ?? '—' }}</td>
              </tr>

              <!-- Empty -->
              <tr v-if="!props.staff.length && !props.loading">
                <td :colspan="9 + (isBulkMode ? 1 : 0) + vs.extraColumns.length" class="p-12 text-center">
                  <div class="text-4xl mb-3">{{ vs.icon }}</div>
                  <div class="text-sm text-(--t-text-3)">{{ vs.staffLabelPlural }} не найдены</div>
                  <VButton variant="primary" size="sm" class="mt-4" @click="emit('staff-create')">
                    {{ vs.quickCreate.icon }} {{ vs.quickCreate.label }}
                  </VButton>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ═══ MOBILE CARDS ═══ -->
        <div :class="['flex flex-col gap-3', viewAs === 'table' ? 'sm:hidden' : '']">
          <div
            v-for="m in props.staff"
            :key="m.id"
            :class="[
              'relative overflow-hidden rounded-2xl border p-4 transition-all duration-200',
              'bg-(--t-surface) backdrop-blur-xl cursor-pointer',
              'hover:border-(--t-primary)/30 hover:shadow-[0_0_20px_var(--t-glow)]',
              'active:scale-[0.99] group/card',
              selectedIds.has(m.id) ? 'border-(--t-primary)/40 bg-(--t-primary)/5' : 'border-(--t-border)',
            ]"
            @click="openMember(m)"
          >
            <!-- Bulk checkbox -->
            <input
              v-if="isBulkMode"
              type="checkbox"
              :checked="selectedIds.has(m.id)"
              class="absolute inset-block-start-3 inset-inline-end-3 accent-(--t-primary) z-10"
              @click.stop
              @change="toggleSelect(m.id)"
            />

            <!-- Header: avatar + name + status -->
            <div class="flex items-center gap-3 mb-3">
              <div class="relative shrink-0">
                <div
                  class="w-12 h-12 rounded-full flex items-center justify-center text-sm font-bold text-white"
                  :style="{ backgroundColor: avatarColor(m.id) }"
                >{{ avatarInitials(m.fullName) }}</div>
                <span
                  :class="[
                    'absolute -inset-block-end-0.5 -inset-inline-end-0.5 w-3.5 h-3.5 rounded-full border-2 border-(--t-surface)',
                    SHIFT_STATUS_MAP[m.shiftStatus].dot,
                  ]"
                />
              </div>
              <div class="flex-1 min-w-0">
                <div class="font-semibold text-sm text-(--t-text) truncate">{{ m.fullName }}</div>
                <div class="text-[11px] text-(--t-text-3)">{{ roleIcon(m.role) }} {{ m.roleLabel }}</div>
              </div>
              <div class="text-end shrink-0">
                <VBadge
                  :text="STAFF_STATUS_MAP[m.status].label.replace(/^\S+\s/, '')"
                  :variant="STAFF_STATUS_MAP[m.status].badge"
                  size="xs"
                />
                <div class="text-xs text-amber-400 mt-1">{{ m.rating.toFixed(1) }} ⭐</div>
              </div>
            </div>

            <!-- KPI row -->
            <div class="grid grid-cols-3 gap-2 text-xs mb-3">
              <div class="text-center">
                <div class="font-bold text-(--t-text)">{{ fmtNum(m.totalOrders) }}</div>
                <div class="text-[10px] text-(--t-text-3)">{{ vs.kpiLabels.orders }}</div>
              </div>
              <div class="text-center">
                <div class="font-bold text-emerald-400">{{ fmtMoney(m.totalRevenue) }}</div>
                <div class="text-[10px] text-(--t-text-3)">{{ vs.kpiLabels.revenue }}</div>
              </div>
              <div class="text-center">
                <div class="font-bold text-(--t-text)">{{ fmtPercent(m.completionRate) }}</div>
                <div class="text-[10px] text-(--t-text-3)">{{ vs.kpiLabels.completion }}</div>
              </div>
            </div>

            <!-- Completion bar -->
            <div class="h-1.5 rounded-full bg-(--t-border) overflow-hidden mb-2">
              <div :class="['h-full rounded-full transition-all', completionBarColor(m.completionRate)]"
                   :style="{ inlineSize: `${Math.min(m.completionRate, 100)}%` }" />
            </div>

            <!-- inset-block-end: schedule + last activity -->
            <div class="flex items-center justify-between text-[10px] text-(--t-text-3)">
              <span v-if="m.schedule">📅 {{ m.schedule }}</span>
              <span v-else>—</span>
              <span>{{ relativeTime(m.lastActivity) }}</span>
            </div>

            <!-- Tags -->
            <div v-if="m.tags.length" class="flex flex-wrap gap-1 mt-2">
              <span
                v-for="tag in m.tags"
                :key="tag"
                class="px-2 py-0.5 rounded-full text-[10px] bg-(--t-primary)/10 text-(--t-primary) border border-(--t-primary)/20"
              >{{ tag }}</span>
            </div>

            <!-- Branch badge (B2B) -->
            <div v-if="m.branchName" class="mt-2 text-[10px] text-(--t-text-3)">🏢 {{ m.branchName }}</div>
          </div>

          <!-- Empty -->
          <div v-if="!props.staff.length && !props.loading" class="text-center py-16">
            <div class="text-5xl mb-4">{{ vs.icon }}</div>
            <div class="text-sm text-(--t-text-3)">{{ vs.staffLabelPlural }} не найдены</div>
            <VButton variant="primary" size="sm" class="mt-4" @click="emit('staff-create')">
              {{ vs.quickCreate.icon }} {{ vs.quickCreate.label }}
            </VButton>
          </div>
        </div>

        <!-- Infinite scroll sentinel -->
        <div ref="scrollSentinel" class="h-1" />

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
         6. STAFF DETAIL MODAL
    ═══════════════════════════════════════════════ -->
    <VModal v-model="showStaffModal" :title="`${vs.staffLabel}: ${selectedMember?.fullName ?? ''}`" size="lg" @update:model-value="!$event && closeStaffModal()">
      <template v-if="selectedMember">
        <div class="flex flex-col gap-5">

          <!-- Header: avatar + basic info -->
          <div class="flex items-center gap-4">
            <div class="relative shrink-0">
              <div
                class="w-16 h-16 rounded-2xl flex items-center justify-center text-lg font-bold text-white"
                :style="{ backgroundColor: avatarColor(selectedMember.id) }"
              >{{ avatarInitials(selectedMember.fullName) }}</div>
              <span
                :class="[
                  'absolute -inset-block-end-1 -inset-inline-end-1 w-4 h-4 rounded-full border-2 border-(--t-surface)',
                  SHIFT_STATUS_MAP[selectedMember.shiftStatus].dot,
                ]"
              />
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-lg font-bold text-(--t-text)">{{ selectedMember.fullName }}</div>
              <div class="text-sm text-(--t-text-2)">{{ roleIcon(selectedMember.role) }} {{ selectedMember.roleLabel }}</div>
              <div class="text-xs text-(--t-text-3)">{{ selectedMember.phone }} · {{ selectedMember.email }}</div>
            </div>
            <div class="text-end shrink-0">
              <VBadge
                :text="STAFF_STATUS_MAP[selectedMember.status].label"
                :variant="STAFF_STATUS_MAP[selectedMember.status].badge"
                size="sm"
              />
              <div class="text-lg text-amber-400 mt-1">{{ starRating(selectedMember.rating) }}</div>
            </div>
          </div>

          <!-- Tabs -->
          <div class="flex gap-1 rounded-xl bg-(--t-card-hover) p-1">
            <button
              v-for="tab in (['info', 'kpi', 'schedule', 'permissions'] as const)"
              :key="tab"
              :class="[
                'flex-1 px-3 py-2 rounded-lg text-xs font-medium transition-all duration-200',
                modalTab === tab
                  ? 'bg-(--t-surface) text-(--t-text) shadow-sm'
                  : 'text-(--t-text-3) hover:text-(--t-text)',
              ]"
              @click="modalTab = tab"
            >
              {{ { info: '📋 Инфо', kpi: '📈 KPI', schedule: '📅 График', permissions: '🔐 Права' }[tab] }}
            </button>
          </div>

          <!-- Tab: Info -->
          <div v-if="modalTab === 'info'" class="flex flex-col gap-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
                <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-2">Занятость</div>
                <div class="text-sm font-medium text-(--t-text)">{{ EMPLOYMENT_LABELS[selectedMember.employmentType] }}</div>
              </div>
              <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
                <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-2">Дата найма</div>
                <div class="text-sm font-medium text-(--t-text)">{{ fmtDate(selectedMember.hireDate) }}</div>
              </div>
              <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
                <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-2">Зарплата</div>
                <div class="text-sm font-bold text-emerald-400">{{ fmtMoney(selectedMember.salary) }}</div>
              </div>
              <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
                <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-2">Бонус KPI</div>
                <div class="text-sm font-bold text-amber-400">{{ fmtMoney(selectedMember.bonusBalance) }}</div>
              </div>
            </div>

            <!-- Specializations -->
            <div v-if="selectedMember.specializations.length" class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-2">Специализации</div>
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="spec in selectedMember.specializations"
                  :key="spec"
                  class="px-2.5 py-1 rounded-full text-xs bg-(--t-primary)/10 text-(--t-primary) border border-(--t-primary)/20"
                >{{ spec }}</span>
              </div>
            </div>

            <!-- Notes -->
            <div v-if="selectedMember.notes" class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-2">Заметки</div>
              <p class="text-sm text-(--t-text-2) whitespace-pre-wrap">{{ selectedMember.notes }}</p>
            </div>

            <!-- Branch -->
            <div v-if="selectedMember.branchName" class="text-xs text-(--t-text-3)">🏢 Филиал: {{ selectedMember.branchName }}</div>

            <!-- Last activity -->
            <div class="text-[10px] text-(--t-text-3)">
              Последняя активность: {{ relativeTime(selectedMember.lastActivity) }}
              <span v-if="selectedMember.correlationId" class="font-mono ml-2">
                ID: {{ selectedMember.correlationId.slice(0, 8) }}
              </span>
            </div>
          </div>

          <!-- Tab: KPI -->
          <div v-if="modalTab === 'kpi'" class="flex flex-col gap-4">
            <div class="grid grid-cols-3 gap-4">
              <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4 text-center">
                <div class="text-2xl font-black text-(--t-text)">{{ fmtNum(selectedMember.totalOrders) }}</div>
                <div class="text-[10px] text-(--t-text-3) mt-1">{{ vs.kpiLabels.orders }}</div>
              </div>
              <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4 text-center">
                <div class="text-2xl font-black text-emerald-400">{{ fmtMoney(selectedMember.totalRevenue) }}</div>
                <div class="text-[10px] text-(--t-text-3) mt-1">{{ vs.kpiLabels.revenue }}</div>
              </div>
              <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4 text-center">
                <div class="text-2xl font-black text-(--t-text)">{{ selectedMember.avgRating.toFixed(1) }}</div>
                <div class="text-xs text-amber-400">{{ starRating(selectedMember.avgRating) }}</div>
                <div class="text-[10px] text-(--t-text-3) mt-1">Рейтинг</div>
              </div>
            </div>
            <!-- Completion rate -->
            <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs text-(--t-text-2)">{{ vs.kpiLabels.completion }}</span>
                <span class="text-sm font-bold text-(--t-text)">{{ fmtPercent(selectedMember.completionRate) }}</span>
              </div>
              <div class="h-3 rounded-full bg-(--t-border) overflow-hidden">
                <div
                  :class="['h-full rounded-full transition-all', completionBarColor(selectedMember.completionRate)]"
                  :style="{ inlineSize: `${Math.min(selectedMember.completionRate, 100)}%` }"
                />
              </div>
            </div>
            <!-- Today -->
            <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-2">Сегодня</div>
              <div class="text-xl font-bold text-(--t-text)">{{ selectedMember.todayOrders }} {{ vs.kpiLabels.orders.toLowerCase() }}</div>
            </div>
          </div>

          <!-- Tab: Schedule -->
          <div v-if="modalTab === 'schedule'" class="flex flex-col gap-4">
            <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-2">Текущий график</div>
              <div class="text-sm font-medium text-(--t-text)">{{ selectedMember.schedule ?? 'Не назначен' }}</div>
            </div>
            <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-2">Текущая смена</div>
              <div class="flex items-center gap-2">
                <span :class="['w-3 h-3 rounded-full', SHIFT_STATUS_MAP[selectedMember.shiftStatus].dot]" />
                <span class="text-sm font-medium text-(--t-text)">{{ SHIFT_STATUS_MAP[selectedMember.shiftStatus].label }}</span>
              </div>
            </div>
            <VButton variant="primary" size="sm" @click="emit('schedule-assign', [selectedMember.id])">
              📅 Изменить график
            </VButton>
          </div>

          <!-- Tab: Permissions -->
          <div v-if="modalTab === 'permissions'" class="flex flex-col gap-4">
            <div class="rounded-xl border border-(--t-border) bg-(--t-surface) p-4">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider mb-3">Текущие права</div>
              <div v-if="selectedMember.permissions.length" class="flex flex-wrap gap-1.5">
                <span
                  v-for="perm in selectedMember.permissions"
                  :key="perm"
                  class="px-2.5 py-1 rounded-full text-xs bg-sky-500/10 text-sky-400 border border-sky-500/20"
                >🔑 {{ perm }}</span>
              </div>
              <div v-else class="text-sm text-(--t-text-3)">Нет назначенных прав</div>
            </div>
            <VButton variant="ghost" size="sm" @click="emit('permissions-edit', selectedMember.id)">
              🔐 Редактировать права
            </VButton>
          </div>
        </div>
      </template>

      <template #footer>
        <div class="flex items-center gap-2">
          <VButton variant="primary" size="sm" @click="selectedMember && emit('staff-edit', selectedMember); closeStaffModal()">
            ✏️ Редактировать
          </VButton>
          <VButton
            v-if="selectedMember?.status === 'active'"
            variant="warning"
            size="sm"
            @click="selectedMember && emit('staff-suspend', [selectedMember.id]); closeStaffModal()"
          >⏸️ Приостановить</VButton>
          <VButton
            v-if="selectedMember?.status === 'suspended'"
            variant="success"
            size="sm"
            @click="selectedMember && emit('staff-activate', [selectedMember.id]); closeStaffModal()"
          >✅ Активировать</VButton>
          <VButton
            v-if="selectedMember?.status !== 'terminated'"
            variant="danger"
            size="sm"
            @click="selectedMember && emit('staff-terminate', [selectedMember.id]); closeStaffModal()"
          >🚫 Уволить</VButton>
          <div class="flex-1" />
          <VButton variant="ghost" size="sm" @click="closeStaffModal">Закрыть</VButton>
        </div>
      </template>
    </VModal>

    <!-- ═══════════════════════════════════════════════
         7. MOBILE FILTER DRAWER
    ═══════════════════════════════════════════════ -->
    <Transition name="drawer-sf">
      <div v-if="showFilterDrawer" class="fixed inset-0 z-90 sm:hidden">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showFilterDrawer = false" />
        <div
          class="absolute inset-inline-end-0 inset-block-start-0 inset-block-end-0 bg-(--t-surface)
                 border-s border-(--t-border) p-5 overflow-auto"
          style="inline-size: 85vw; max-inline-size: 320px"
        >
          <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-bold text-(--t-text)">Фильтры</h2>
            <button class="w-8 h-8 rounded-lg flex items-center justify-center text-(--t-text-3) hover:text-(--t-text)"
                    @click="showFilterDrawer = false">✕</button>
          </div>

          <div class="flex flex-col gap-4">
            <!-- Role -->
            <div>
              <div class="text-xs font-semibold text-(--t-text-2) mb-2">Роль</div>
              <div class="flex flex-col gap-1">
                <button
                  v-for="r in vs.roles"
                  :key="r.key"
                  :class="[
                    'flex items-center justify-between rounded-lg px-3 py-2 text-xs transition-all',
                    filters.role === r.key
                      ? 'bg-(--t-primary)/10 text-(--t-primary)'
                      : 'text-(--t-text-3) hover:bg-(--t-card-hover)',
                  ]"
                  @click="applyFilter('role', filters.role === r.key ? '' : r.key)"
                >
                  <span>{{ r.icon }} {{ r.label }}</span>
                  <span class="font-bold">{{ roleCounts[r.key] ?? 0 }}</span>
                </button>
              </div>
            </div>

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
                      ? `bg-${STAFF_STATUS_MAP[st].color}-500/10 text-${STAFF_STATUS_MAP[st].color}-400`
                      : 'text-(--t-text-3) hover:bg-(--t-card-hover)',
                  ]"
                  @click="applyFilter('status', filters.status === st ? '' : st)"
                >
                  <span>{{ STAFF_STATUS_MAP[st].label }}</span>
                  <span class="font-bold">{{ statusCountMap[st] }}</span>
                </button>
              </div>
            </div>

            <!-- Branch -->
            <div v-if="auth.isB2BMode && props.branches.length">
              <div class="text-xs font-semibold text-(--t-text-2) mb-2">Филиал</div>
              <select v-model="filters.branch" class="w-full h-9 rounded-xl px-3 text-xs bg-(--t-surface) border border-(--t-border) text-(--t-text)" @change="emitFilters">
                <option value="">Все</option>
                <option v-for="br in props.branches" :key="br.id" :value="br.id">{{ br.name }}</option>
              </select>
            </div>

            <!-- Employment type -->
            <div>
              <div class="text-xs font-semibold text-(--t-text-2) mb-2">Занятость</div>
              <select v-model="filters.employmentType" class="w-full h-9 rounded-xl px-3 text-xs bg-(--t-surface) border border-(--t-border) text-(--t-text)" @change="emitFilters">
                <option value="">Все</option>
                <option value="full_time">Полная</option>
                <option value="part_time">Частичная</option>
                <option value="contract">Контракт</option>
                <option value="freelance">Фриланс</option>
              </select>
            </div>

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
@keyframes ripple-sf {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* Dropdown transition */
.dropdown-sf-enter-active,
.dropdown-sf-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-sf-enter-from,
.dropdown-sf-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.96);
}

/* Slide transition (bulk bar) */
.slide-sf-enter-active,
.slide-sf-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.slide-sf-enter-from,
.slide-sf-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Sidebar transition */
.sidebar-sf-enter-active,
.sidebar-sf-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease, inline-size 0.25s ease;
}
.sidebar-sf-enter-from,
.sidebar-sf-leave-to {
  opacity: 0;
  transform: translateX(-12px);
  inline-size: 0 !important;
}

/* Drawer transition */
.drawer-sf-enter-active,
.drawer-sf-leave-active {
  transition: opacity 0.3s ease;
}
.drawer-sf-enter-active > :last-child,
.drawer-sf-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.drawer-sf-enter-from,
.drawer-sf-leave-to {
  opacity: 0;
}
.drawer-sf-enter-from > :last-child,
.drawer-sf-leave-to > :last-child {
  transform: translateX(100%);
}

/* Custom scrollbar */
aside::-webkit-scrollbar { inline-size: 4px; }
aside::-webkit-scrollbar-track { background: transparent; }
aside::-webkit-scrollbar-thumb { background: var(--t-border); border-radius: 999px; }
</style>
