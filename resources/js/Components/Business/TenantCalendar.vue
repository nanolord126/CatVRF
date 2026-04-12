<script setup lang="ts">
/**
 * TenantCalendar.vue — Универсальный B2B-календарь для Tenant Dashboard
 *
 * Поддержка всех 127 вертикалей CatVRF:
 *   Beauty · Taxi · Food · Hotels · RealEstate · Flowers · Fashion · Furniture
 *   Fitness · Travel · Medical · Auto · и т.д.
 *
 * ─────────────────────────────────────────────────────────────
 *  Функционал:
 *   1. 5 видов: Day · Week · Month · Timeline · List
 *   2. Drag & Drop записей (перемещение по времени и между сотрудниками)
 *   3. Full-screen режим
 *   4. Боковая панель загрузки мастеров/сотрудников
 *   5. Массовые операции: блокировка слотов, перенос, удаление
 *   6. Реал-тайм обновления (polling / Echo-ready)
 *   7. Фильтрация по мастеру/сотруднику, услуге, статусу
 *   8. Адаптивность: mobile-first → tablet → desktop → fullscreen
 * ─────────────────────────────────────────────────────────────
 *  Как адаптировать под вертикаль:
 *   → props.vertical: 'beauty' | 'taxi' | 'food' | 'hotel' | ...
 *   → VERTICAL_CALENDAR_CONFIG определяет терминологию и слоты
 * ─────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'

import VCard    from '../UI/VCard.vue'
import VButton  from '../UI/VButton.vue'
import VBadge   from '../UI/VBadge.vue'
import VTabs    from '../UI/VTabs.vue'
import VModal   from '../UI/VModal.vue'
import VInput   from '../UI/VInput.vue'
import { useAuth } from '@/stores'

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// TYPES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

type ViewMode = 'day' | 'week' | 'month' | 'timeline' | 'list'

interface CalendarEvent {
  id: number | string
  title: string
  start: string              // ISO datetime
  end: string
  resourceId?: number | string  // мастер / водитель / номер и т.д.
  status: 'confirmed' | 'pending' | 'cancelled' | 'completed' | 'no_show' | 'blocked'
  color?: string
  client?: string
  phone?: string
  price?: number
  notes?: string
  service?: string
  [key: string]: unknown
}

interface Resource {
  id: number | string
  name: string
  avatar?: string
  role?: string
  color: string
  loadPercent: number        // 0–100 загрузка за текущий день
  isOnline?: boolean
}

interface VerticalCalendarConfig {
  resourceLabel: string      // «Мастер» / «Водитель» / «Номер» / «Стол»
  resourceLabelPlural: string
  eventLabel: string         // «Запись» / «Поездка» / «Бронь» / «Заказ»
  eventLabelPlural: string
  slotDuration: number       // минуты: 15, 30, 60
  workStart: number          // час начала рабочего дня
  workEnd: number            // час конца
  icon: string
  statusMap: Record<string, { text: string; variant: string; icon: string }>
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// PROPS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const props = withDefaults(defineProps<{
  vertical?: string
  events?: CalendarEvent[]
  resources?: Resource[]
  loading?: boolean
}>(), {
  vertical: 'default',
  events: () => [],
  resources: () => [],
  loading: false,
})

const emit = defineEmits<{
  'event-click': [event: CalendarEvent]
  'event-move': [event: CalendarEvent, newStart: string, newEnd: string, newResourceId?: number | string]
  'slot-click': [date: string, hour: number, resourceId?: number | string]
  'slot-block': [slots: Array<{ date: string; hour: number; resourceId?: number | string }>]
  'date-change': [date: string]
  'view-change': [view: ViewMode]
  'bulk-action': [action: string, eventIds: Array<number | string>]
}>()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// STORES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const auth = useAuth()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// VERTICAL CONFIG
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// АДАПТАЦИЯ: добавляйте новые вертикали сюда

const VERTICAL_CALENDAR_CONFIG: Record<string, VerticalCalendarConfig> = {
  beauty: {
    resourceLabel: 'Мастер', resourceLabelPlural: 'Мастера',
    eventLabel: 'Запись', eventLabelPlural: 'Записи',
    slotDuration: 30, workStart: 9, workEnd: 21, icon: '💄',
    statusMap: {
      confirmed: { text: 'Подтверждена', variant: 'success', icon: '✅' },
      pending:   { text: 'Ожидает',      variant: 'warning', icon: '⏳' },
      cancelled: { text: 'Отменена',     variant: 'danger',  icon: '❌' },
      completed: { text: 'Завершена',    variant: 'info',    icon: '✨' },
      no_show:   { text: 'Неявка',       variant: 'danger',  icon: '👻' },
      blocked:   { text: 'Заблокирован', variant: 'neutral', icon: '🔒' },
    },
  },
  taxi: {
    resourceLabel: 'Водитель', resourceLabelPlural: 'Водители',
    eventLabel: 'Смена', eventLabelPlural: 'Смены',
    slotDuration: 60, workStart: 0, workEnd: 24, icon: '🚕',
    statusMap: {
      confirmed: { text: 'На линии',   variant: 'success', icon: '🟢' },
      pending:   { text: 'Планируется', variant: 'warning', icon: '⏳' },
      cancelled: { text: 'Отменена',    variant: 'danger',  icon: '❌' },
      completed: { text: 'Завершена',   variant: 'info',    icon: '✅' },
      no_show:   { text: 'Не вышел',    variant: 'danger',  icon: '👻' },
      blocked:   { text: 'Заблокирован',variant: 'neutral', icon: '🔒' },
    },
  },
  food: {
    resourceLabel: 'Стол', resourceLabelPlural: 'Столы',
    eventLabel: 'Бронь', eventLabelPlural: 'Брони',
    slotDuration: 30, workStart: 10, workEnd: 23, icon: '🍽️',
    statusMap: {
      confirmed: { text: 'Подтверждена', variant: 'success', icon: '✅' },
      pending:   { text: 'Ожидает',      variant: 'warning', icon: '⏳' },
      cancelled: { text: 'Отменена',     variant: 'danger',  icon: '❌' },
      completed: { text: 'Завершена',    variant: 'info',    icon: '🍽️' },
      no_show:   { text: 'Неявка',       variant: 'danger',  icon: '👻' },
      blocked:   { text: 'Недоступен',   variant: 'neutral', icon: '🔒' },
    },
  },
  hotel: {
    resourceLabel: 'Номер', resourceLabelPlural: 'Номера',
    eventLabel: 'Бронирование', eventLabelPlural: 'Бронирования',
    slotDuration: 60, workStart: 0, workEnd: 24, icon: '🏨',
    statusMap: {
      confirmed: { text: 'Подтверждено', variant: 'success', icon: '✅' },
      pending:   { text: 'Ожидает',      variant: 'warning', icon: '⏳' },
      cancelled: { text: 'Отменено',     variant: 'danger',  icon: '❌' },
      completed: { text: 'Выехал',       variant: 'info',    icon: '🏁' },
      no_show:   { text: 'Неявка',       variant: 'danger',  icon: '👻' },
      blocked:   { text: 'Закрыт',       variant: 'neutral', icon: '🔒' },
    },
  },
  realEstate: {
    resourceLabel: 'Агент', resourceLabelPlural: 'Агенты',
    eventLabel: 'Показ', eventLabelPlural: 'Показы',
    slotDuration: 60, workStart: 9, workEnd: 20, icon: '🏢',
    statusMap: {
      confirmed: { text: 'Подтверждён',  variant: 'success', icon: '✅' },
      pending:   { text: 'Ожидает',      variant: 'warning', icon: '⏳' },
      cancelled: { text: 'Отменён',      variant: 'danger',  icon: '❌' },
      completed: { text: 'Проведён',     variant: 'info',    icon: '🏠' },
      no_show:   { text: 'Неявка',       variant: 'danger',  icon: '👻' },
      blocked:   { text: 'Заблокирован', variant: 'neutral', icon: '🔒' },
    },
  },
  flowers: {
    resourceLabel: 'Флорист', resourceLabelPlural: 'Флористы',
    eventLabel: 'Заказ', eventLabelPlural: 'Заказы',
    slotDuration: 30, workStart: 7, workEnd: 21, icon: '💐',
    statusMap: {
      confirmed: { text: 'В работе',    variant: 'success', icon: '🌸' },
      pending:   { text: 'Ожидает',     variant: 'warning', icon: '⏳' },
      cancelled: { text: 'Отменён',     variant: 'danger',  icon: '❌' },
      completed: { text: 'Доставлен',   variant: 'info',    icon: '✅' },
      no_show:   { text: 'Не забрал',   variant: 'danger',  icon: '👻' },
      blocked:   { text: 'Заблокирован',variant: 'neutral', icon: '🔒' },
    },
  },
  fashion: {
    resourceLabel: 'Стилист', resourceLabelPlural: 'Стилисты',
    eventLabel: 'Консультация', eventLabelPlural: 'Консультации',
    slotDuration: 60, workStart: 10, workEnd: 20, icon: '👗',
    statusMap: {
      confirmed: { text: 'Подтверждена', variant: 'success', icon: '✅' },
      pending:   { text: 'Ожидает',      variant: 'warning', icon: '⏳' },
      cancelled: { text: 'Отменена',     variant: 'danger',  icon: '❌' },
      completed: { text: 'Завершена',    variant: 'info',    icon: '✨' },
      no_show:   { text: 'Неявка',       variant: 'danger',  icon: '👻' },
      blocked:   { text: 'Заблокирован', variant: 'neutral', icon: '🔒' },
    },
  },
  furniture: {
    resourceLabel: 'Дизайнер', resourceLabelPlural: 'Дизайнеры',
    eventLabel: 'Замер', eventLabelPlural: 'Замеры',
    slotDuration: 60, workStart: 9, workEnd: 19, icon: '🛋️',
    statusMap: {
      confirmed: { text: 'Подтверждён', variant: 'success', icon: '✅' },
      pending:   { text: 'Ожидает',     variant: 'warning', icon: '⏳' },
      cancelled: { text: 'Отменён',     variant: 'danger',  icon: '❌' },
      completed: { text: 'Завершён',    variant: 'info',    icon: '📐' },
      no_show:   { text: 'Неявка',      variant: 'danger',  icon: '👻' },
      blocked:   { text: 'Заблокирован',variant: 'neutral', icon: '🔒' },
    },
  },
  fitness: {
    resourceLabel: 'Тренер', resourceLabelPlural: 'Тренеры',
    eventLabel: 'Тренировка', eventLabelPlural: 'Тренировки',
    slotDuration: 60, workStart: 6, workEnd: 22, icon: '💪',
    statusMap: {
      confirmed: { text: 'Подтверждена', variant: 'success', icon: '✅' },
      pending:   { text: 'Ожидает',      variant: 'warning', icon: '⏳' },
      cancelled: { text: 'Отменена',     variant: 'danger',  icon: '❌' },
      completed: { text: 'Завершена',    variant: 'info',    icon: '🏋️' },
      no_show:   { text: 'Неявка',       variant: 'danger',  icon: '👻' },
      blocked:   { text: 'Заблокирован', variant: 'neutral', icon: '🔒' },
    },
  },
  default: {
    resourceLabel: 'Сотрудник', resourceLabelPlural: 'Сотрудники',
    eventLabel: 'Событие', eventLabelPlural: 'События',
    slotDuration: 30, workStart: 8, workEnd: 20, icon: '📅',
    statusMap: {
      confirmed: { text: 'Подтверждено', variant: 'success', icon: '✅' },
      pending:   { text: 'Ожидает',      variant: 'warning', icon: '⏳' },
      cancelled: { text: 'Отменено',     variant: 'danger',  icon: '❌' },
      completed: { text: 'Завершено',    variant: 'info',    icon: '✅' },
      no_show:   { text: 'Неявка',       variant: 'danger',  icon: '👻' },
      blocked:   { text: 'Заблокирован', variant: 'neutral', icon: '🔒' },
    },
  },
}

const vc = computed(() => VERTICAL_CALENDAR_CONFIG[props.vertical] ?? VERTICAL_CALENDAR_CONFIG.default)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// STATE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const viewMode  = ref<ViewMode>('day')
const currentDate = ref(new Date())
const isFullscreen = ref(false)
const showSidebar  = ref(true)
const showEventModal  = ref(false)
const showBlockModal  = ref(false)
const selectedEvent   = ref<CalendarEvent | null>(null)
const sidebarMobile   = ref(false)

// Filters
const filterResource = ref<string>('')
const filterService  = ref<string>('')
const filterStatus   = ref<string>('')
const searchQuery    = ref<string>('')

// Bulk selection
const selectedEventIds = reactive<Set<number | string>>(new Set())
const isBulkMode = ref(false)

// D&D state
const dragState = reactive({
  active: false,
  eventId: null as number | string | null,
  ghostBlockStart: 0,
  ghostInlineStart: 0,
  originHour: 0,
  originResourceId: null as number | string | null,
})

// Now-line refresh
const nowMinute = ref(new Date().getHours() * 60 + new Date().getMinutes())
let nowTimer: ReturnType<typeof setInterval> | null = null
let pollTimer: ReturnType<typeof setInterval> | null = null

// Container ref for fullscreen
const calendarRoot = ref<HTMLElement | null>(null)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// VIEW TABS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const viewTabs = [
  { key: 'day',      label: 'День',     icon: '📅' },
  { key: 'week',     label: 'Неделя',   icon: '📆' },
  { key: 'month',    label: 'Месяц',    icon: '🗓️' },
  { key: 'timeline', label: 'Timeline', icon: '⏱️' },
  { key: 'list',     label: 'Список',   icon: '📋' },
]

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// DATE HELPERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const MONTHS_RU = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
                   'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь']
const DAYS_SHORT = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']
const DAYS_FULL  = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье']

function isoDate(d: Date): string {
  return d.toISOString().slice(0, 10)
}
function isoDatetime(d: Date): string {
  return d.toISOString().slice(0, 16)
}
function addDays(d: Date, n: number): Date {
  const r = new Date(d); r.setDate(r.getDate() + n); return r
}
function startOfWeek(d: Date): Date {
  const r = new Date(d)
  const day = r.getDay()
  const diff = (day === 0 ? -6 : 1) - day
  r.setDate(r.getDate() + diff)
  return r
}
function sameDay(a: Date, b: Date): boolean {
  return isoDate(a) === isoDate(b)
}
function isToday(d: Date): boolean {
  return sameDay(d, new Date())
}
function formatHour(h: number): string {
  return `${String(h).padStart(2, '0')}:00`
}
function parseTime(iso: string): { hour: number; minute: number } {
  const d = new Date(iso)
  return { hour: d.getHours(), minute: d.getMinutes() }
}
function durationMinutes(start: string, end: string): number {
  return (new Date(end).getTime() - new Date(start).getTime()) / 60000
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// COMPUTED: TITLE & DATE RANGE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const dateTitle = computed(() => {
  const d = currentDate.value
  if (viewMode.value === 'day') {
    return `${d.getDate()} ${MONTHS_RU[d.getMonth()]} ${d.getFullYear()}, ${DAYS_FULL[(d.getDay() + 6) % 7]}`
  }
  if (viewMode.value === 'week') {
    const s = startOfWeek(d)
    const e = addDays(s, 6)
    return `${s.getDate()}–${e.getDate()} ${MONTHS_RU[s.getMonth()]} ${s.getFullYear()}`
  }
  if (viewMode.value === 'month') {
    return `${MONTHS_RU[d.getMonth()]} ${d.getFullYear()}`
  }
  return `${MONTHS_RU[d.getMonth()]} ${d.getFullYear()}`
})

const todayStr = computed(() => isoDate(new Date()))

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// COMPUTED: HOURS ARRAY
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const hours = computed(() => {
  const arr: number[] = []
  for (let h = vc.value.workStart; h < vc.value.workEnd; h++) arr.push(h)
  return arr
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// COMPUTED: WEEK DAYS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const weekDays = computed(() => {
  const s = startOfWeek(currentDate.value)
  return Array.from({ length: 7 }, (_, i) => addDays(s, i))
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// COMPUTED: MONTH GRID (6×7)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const monthGrid = computed(() => {
  const d = currentDate.value
  const first = new Date(d.getFullYear(), d.getMonth(), 1)
  const startOffset = (first.getDay() + 6) % 7 // Monday = 0
  const gridStart = addDays(first, -startOffset)
  return Array.from({ length: 42 }, (_, i) => addDays(gridStart, i))
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// COMPUTED: FILTERED RESOURCES & EVENTS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const filteredResources = computed(() => {
  let list = props.resources
  if (filterResource.value) {
    list = list.filter(r => String(r.id) === filterResource.value)
  }
  return list
})

const filteredEvents = computed(() => {
  let list = props.events
  if (filterResource.value) {
    list = list.filter(e => String(e.resourceId) === filterResource.value)
  }
  if (filterService.value) {
    list = list.filter(e => e.service === filterService.value)
  }
  if (filterStatus.value) {
    list = list.filter(e => e.status === filterStatus.value)
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(e =>
      (e.title?.toLowerCase().includes(q)) ||
      (e.client?.toLowerCase().includes(q)) ||
      (e.service?.toLowerCase().includes(q))
    )
  }
  return list
})

// Events for a specific day
function eventsForDay(d: Date): CalendarEvent[] {
  const ds = isoDate(d)
  return filteredEvents.value.filter(e => e.start.startsWith(ds))
}

// Events for a specific hour+resource (Day View cell)
function eventsForSlot(d: Date, hour: number, resourceId?: number | string): CalendarEvent[] {
  return eventsForDay(d).filter(e => {
    const t = parseTime(e.start)
    const inHour = t.hour === hour
    const matchRes = resourceId == null || String(e.resourceId) === String(resourceId)
    return inHour && matchRes
  })
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// COMPUTED: AVAILABLE SERVICES (for filter)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const availableServices = computed(() => {
  const set = new Set<string>()
  props.events.forEach(e => { if (e.service) set.add(e.service) })
  return Array.from(set).sort()
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// COMPUTED: STATS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const todayEvents = computed(() => eventsForDay(currentDate.value))
const confirmedCount = computed(() => todayEvents.value.filter(e => e.status === 'confirmed').length)
const pendingCount   = computed(() => todayEvents.value.filter(e => e.status === 'pending').length)
const cancelledCount = computed(() => todayEvents.value.filter(e => e.status === 'cancelled').length)
const completedCount = computed(() => todayEvents.value.filter(e => e.status === 'completed').length)
const todayRevenue   = computed(() => todayEvents.value.reduce((s, e) => s + (e.price ?? 0), 0))

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// NAVIGATION
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function navigatePrev() {
  const d = currentDate.value
  if (viewMode.value === 'day')   currentDate.value = addDays(d, -1)
  if (viewMode.value === 'week')  currentDate.value = addDays(d, -7)
  if (viewMode.value === 'month') currentDate.value = new Date(d.getFullYear(), d.getMonth() - 1, 1)
  if (viewMode.value === 'timeline') currentDate.value = addDays(d, -1)
  if (viewMode.value === 'list')  currentDate.value = addDays(d, -7)
  emit('date-change', isoDate(currentDate.value))
}
function navigateNext() {
  const d = currentDate.value
  if (viewMode.value === 'day')   currentDate.value = addDays(d, 1)
  if (viewMode.value === 'week')  currentDate.value = addDays(d, 7)
  if (viewMode.value === 'month') currentDate.value = new Date(d.getFullYear(), d.getMonth() + 1, 1)
  if (viewMode.value === 'timeline') currentDate.value = addDays(d, 1)
  if (viewMode.value === 'list')  currentDate.value = addDays(d, 7)
  emit('date-change', isoDate(currentDate.value))
}
function goToday() {
  currentDate.value = new Date()
  emit('date-change', isoDate(currentDate.value))
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// FULLSCREEN
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    calendarRoot.value?.requestFullscreen?.()
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
// EVENT DETAIL MODAL
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function openEvent(ev: CalendarEvent) {
  if (isBulkMode.value) {
    toggleBulkSelect(ev.id)
    return
  }
  selectedEvent.value = ev
  showEventModal.value = true
  emit('event-click', ev)
}

function closeEventModal() {
  showEventModal.value = false
  selectedEvent.value = null
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// SLOT CLICK
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function onSlotClick(date: Date, hour: number, resourceId?: number | string) {
  emit('slot-click', isoDate(date), hour, resourceId)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// DRAG & DROP
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function onDragStart(ev: CalendarEvent, e: DragEvent) {
  if (!e.dataTransfer) return
  dragState.active = true
  dragState.eventId = ev.id
  dragState.originHour = parseTime(ev.start).hour
  dragState.originResourceId = ev.resourceId ?? null
  e.dataTransfer.effectAllowed = 'move'
  e.dataTransfer.setData('text/plain', String(ev.id))
}

function onDragOver(e: DragEvent) {
  e.preventDefault()
  if (e.dataTransfer) e.dataTransfer.dropEffect = 'move'
}

function onDrop(date: Date, hour: number, resourceId: number | string | undefined, e: DragEvent) {
  e.preventDefault()
  dragState.active = false
  const evId = dragState.eventId
  if (evId == null) return
  const ev = props.events.find(x => x.id === evId)
  if (!ev) return
  const dur = durationMinutes(ev.start, ev.end)
  const newStart = new Date(date)
  newStart.setHours(hour, 0, 0, 0)
  const newEnd = new Date(newStart.getTime() + dur * 60000)
  emit('event-move', ev, isoDatetime(newStart), isoDatetime(newEnd), resourceId)
  dragState.eventId = null
}

function onDragEnd() {
  dragState.active = false
  dragState.eventId = null
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// BULK OPERATIONS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function toggleBulkMode() {
  isBulkMode.value = !isBulkMode.value
  if (!isBulkMode.value) selectedEventIds.clear()
}

function toggleBulkSelect(id: number | string) {
  if (selectedEventIds.has(id)) selectedEventIds.delete(id)
  else selectedEventIds.add(id)
}

function selectAllVisible() {
  todayEvents.value.forEach(e => selectedEventIds.add(e.id))
}

function deselectAll() {
  selectedEventIds.clear()
}

function bulkAction(action: string) {
  emit('bulk-action', action, Array.from(selectedEventIds))
  selectedEventIds.clear()
  isBulkMode.value = false
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// BLOCK SLOTS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const blockRange = reactive({ startHour: 12, endHour: 14, resourceId: '' as string })

function openBlockModal() {
  showBlockModal.value = true
}

function confirmBlock() {
  const slots: Array<{ date: string; hour: number; resourceId?: number | string }> = []
  for (let h = blockRange.startHour; h < blockRange.endHour; h++) {
    slots.push({
      date: isoDate(currentDate.value),
      hour: h,
      resourceId: blockRange.resourceId || undefined,
    })
  }
  emit('slot-block', slots)
  showBlockModal.value = false
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// RIPPLE EFFECT
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function ripple(e: MouseEvent) {
  const target = e.currentTarget as HTMLElement
  const rect = target.getBoundingClientRect()
  const diameter = Math.max(rect.width, rect.height) * 2
  const el = document.createElement('span')
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-cal_0.6s_ease-out]'
  el.style.cssText = `inline-size:${diameter}px;block-size:${diameter}px;inset-inline-start:${e.clientX - rect.left - diameter / 2}px;inset-block-start:${e.clientY - rect.top - diameter / 2}px;`
  target.appendChild(el)
  setTimeout(() => el.remove(), 650)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// EVENT COLOR HELPERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const STATUS_COLORS: Record<string, string> = {
  confirmed: 'bg-emerald-500/20 border-emerald-500/40 text-emerald-300',
  pending:   'bg-amber-500/20 border-amber-500/40 text-amber-300',
  cancelled: 'bg-red-500/15 border-red-500/30 text-red-400/60 line-through opacity-60',
  completed: 'bg-sky-500/15 border-sky-500/30 text-sky-300',
  no_show:   'bg-red-500/10 border-red-500/20 text-red-400/50 opacity-50',
  blocked:   'bg-(--t-surface) border-(--t-border) text-(--t-text-3) opacity-70',
}

function eventClasses(ev: CalendarEvent): string {
  return STATUS_COLORS[ev.status] ?? STATUS_COLORS.confirmed
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// NOW-LINE POSITION (pixels from top)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const SLOT_HEIGHT = 64 // px per hour-row
const nowLineTop = computed(() => {
  const minutesSinceStart = nowMinute.value - vc.value.workStart * 60
  return (minutesSinceStart / 60) * SLOT_HEIGHT
})
const showNowLine = computed(() => {
  const h = new Date().getHours()
  return (viewMode.value === 'day' || viewMode.value === 'timeline')
    && h >= vc.value.workStart && h < vc.value.workEnd
    && sameDay(currentDate.value, new Date())
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// LIFECYCLE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

watch(viewMode, (v) => emit('view-change', v))

onMounted(() => {
  nowTimer = setInterval(() => {
    nowMinute.value = new Date().getHours() * 60 + new Date().getMinutes()
  }, 15_000)
  document.addEventListener('fullscreenchange', onFullscreenChange)
})

onBeforeUnmount(() => {
  if (nowTimer) clearInterval(nowTimer)
  if (pollTimer) clearInterval(pollTimer)
  document.removeEventListener('fullscreenchange', onFullscreenChange)
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// UTILITY
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function fmtNum(n: number): string {
  return n.toLocaleString('ru-RU')
}

function fmtTime(iso: string): string {
  const d = new Date(iso)
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}

function resourceById(id: number | string | undefined): Resource | undefined {
  if (id == null) return undefined
  return props.resources.find(r => String(r.id) === String(id))
}
</script>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     TEMPLATE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<template>
  <div
    ref="calendarRoot"
    :class="[
      'flex flex-col gap-4',
      isFullscreen ? 'fixed inset-0 z-90 bg-(--t-bg) p-4 overflow-auto' : '',
    ]"
  >
    <!-- ═══════════════════════════════════════════════════
         1. TOOLBAR
    ══════════════════════════════════════════════════════ -->
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

      <!-- Inline-start: Navigation + Date -->
      <div class="flex items-center gap-2">
        <!-- Prev -->
        <button
          class="relative overflow-hidden w-9 h-9 rounded-xl flex items-center justify-center
                 bg-(--t-surface) border border-(--t-border)
                 text-(--t-text-2) hover:text-(--t-text) hover:border-(--t-primary)/40
                 transition-all duration-200 active:scale-95"
          @click="navigatePrev(); ripple($event)"
          aria-label="Назад"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <!-- Today -->
        <button
          class="relative overflow-hidden px-3 h-9 rounded-xl text-xs font-semibold
                 bg-(--t-surface) border border-(--t-border) text-(--t-text-2)
                 hover:text-(--t-text) hover:border-(--t-primary)/40
                 transition-all duration-200 active:scale-95"
          @click="goToday(); ripple($event)"
        >
          Сегодня
        </button>

        <!-- Next -->
        <button
          class="relative overflow-hidden w-9 h-9 rounded-xl flex items-center justify-center
                 bg-(--t-surface) border border-(--t-border)
                 text-(--t-text-2) hover:text-(--t-text) hover:border-(--t-primary)/40
                 transition-all duration-200 active:scale-95"
          @click="navigateNext(); ripple($event)"
          aria-label="Вперёд"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <!-- Date title -->
        <h2 class="ml-2 text-lg font-bold text-(--t-text) truncate">
          {{ dateTitle }}
        </h2>
        <VBadge v-if="isToday(currentDate)" text="Сегодня" variant="success" size="xs" :dot="true" />
      </div>

      <!-- Center: View tabs (desktop) -->
      <div class="hidden md:block">
        <VTabs :tabs="viewTabs" v-model="viewMode" variant="segment" size="sm" />
      </div>

      <!-- Inline-end: Actions -->
      <div class="flex items-center gap-2 flex-wrap">
        <!-- Mobile view selector -->
        <select
          v-model="viewMode"
          class="md:hidden h-9 rounded-xl px-2 text-xs bg-(--t-surface) border border-(--t-border)
                 text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
        >
          <option v-for="tab in viewTabs" :key="tab.key" :value="tab.key">{{ tab.icon }} {{ tab.label }}</option>
        </select>

        <!-- Search -->
        <div class="relative hidden sm:block">
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="`Поиск ${vc.eventLabelPlural.toLowerCase()}...`"
            class="h-9 w-48 rounded-xl pl-8 pr-3 text-xs bg-(--t-surface) border border-(--t-border)
                   text-(--t-text) placeholder:text-(--t-text-3)
                   focus:outline-none focus:border-(--t-primary)/60
                   transition-all duration-200"
          />
          <svg class="absolute inset-inline-start-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-(--t-text-3)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <!-- Bulk mode toggle -->
        <VButton
          :variant="isBulkMode ? 'danger' : 'ghost'"
          size="sm"
          @click="toggleBulkMode"
        >
          {{ isBulkMode ? `✓ ${selectedEventIds.size}` : '☑️' }}
        </VButton>

        <!-- Block slots -->
        <VButton variant="ghost" size="sm" @click="openBlockModal">🔒 Блок</VButton>

        <!-- Sidebar toggle (desktop) -->
        <button
          class="hidden lg:flex w-9 h-9 rounded-xl items-center justify-center
                 bg-(--t-surface) border border-(--t-border) text-(--t-text-2)
                 hover:text-(--t-text) hover:border-(--t-primary)/40
                 transition-all duration-200 active:scale-95"
          @click="showSidebar = !showSidebar"
          :title="showSidebar ? 'Скрыть панель' : 'Показать панель'"
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
          :title="isFullscreen ? 'Выход из полноэкранного' : 'На весь экран'"
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

    <!-- ═══════════════════════════════════════════════════
         2. FILTER BAR
    ══════════════════════════════════════════════════════ -->
    <div class="flex flex-wrap items-center gap-2">
      <!-- Resource filter -->
      <select
        v-model="filterResource"
        class="h-8 rounded-lg px-2 text-xs bg-(--t-surface) border border-(--t-border)
               text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
      >
        <option value="">Все {{ vc.resourceLabelPlural.toLowerCase() }}</option>
        <option v-for="r in resources" :key="r.id" :value="String(r.id)">
          {{ r.name }}
        </option>
      </select>

      <!-- Service filter -->
      <select
        v-if="availableServices.length > 0"
        v-model="filterService"
        class="h-8 rounded-lg px-2 text-xs bg-(--t-surface) border border-(--t-border)
               text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
      >
        <option value="">Все услуги</option>
        <option v-for="s in availableServices" :key="s" :value="s">{{ s }}</option>
      </select>

      <!-- Status filter -->
      <select
        v-model="filterStatus"
        class="h-8 rounded-lg px-2 text-xs bg-(--t-surface) border border-(--t-border)
               text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
      >
        <option value="">Все статусы</option>
        <option v-for="(cfg, key) in vc.statusMap" :key="key" :value="key">
          {{ cfg.icon }} {{ cfg.text }}
        </option>
      </select>

      <!-- Clear filters -->
      <button
        v-if="filterResource || filterService || filterStatus || searchQuery"
        class="h-8 px-2 rounded-lg text-xs text-(--t-text-3) hover:text-(--t-text)
               hover:bg-(--t-card-hover) transition-all duration-150 active:scale-95"
        @click="filterResource = ''; filterService = ''; filterStatus = ''; searchQuery = ''"
      >
        ✕ Сбросить
      </button>

      <!-- Spacer -->
      <div class="flex-1" />

      <!-- Stats mini row -->
      <div class="hidden sm:flex items-center gap-3 text-xs">
        <span class="text-emerald-400 font-semibold">✅ {{ confirmedCount }}</span>
        <span class="text-amber-400 font-semibold">⏳ {{ pendingCount }}</span>
        <span class="text-red-400 font-semibold">❌ {{ cancelledCount }}</span>
        <span class="text-sky-400 font-semibold">✨ {{ completedCount }}</span>
        <span class="border-l border-(--t-border) pl-3 text-(--t-text-2) font-semibold">
          💰 {{ fmtNum(todayRevenue) }} ₽
        </span>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         3. BULK ACTION BAR
    ══════════════════════════════════════════════════════ -->
    <Transition name="slide-down-cal">
      <div
        v-if="isBulkMode && selectedEventIds.size > 0"
        class="flex flex-wrap items-center gap-2 p-3 rounded-xl
               bg-amber-500/10 border border-amber-500/20"
      >
        <span class="text-sm font-semibold text-amber-200">
          Выбрано: {{ selectedEventIds.size }}
        </span>
        <div class="flex-1" />
        <VButton variant="ghost" size="xs" @click="selectAllVisible">Выбрать все</VButton>
        <VButton variant="ghost" size="xs" @click="deselectAll">Снять</VButton>
        <VButton variant="secondary" size="xs" @click="bulkAction('reschedule')">📅 Перенести</VButton>
        <VButton variant="secondary" size="xs" @click="bulkAction('confirm')">✅ Подтвердить</VButton>
        <VButton variant="danger" size="xs" @click="bulkAction('cancel')">❌ Отменить</VButton>
      </div>
    </Transition>

    <!-- ═══════════════════════════════════════════════════
         4. MAIN CONTENT (calendar + sidebar)
    ══════════════════════════════════════════════════════ -->
    <div class="flex gap-4 min-h-0 flex-1">

      <!-- ─── CALENDAR AREA ─── -->
      <div class="flex-1 min-w-0">

        <!-- ═══ LOADING OVERLAY ═══ -->
        <div v-if="loading" class="relative">
          <div class="absolute inset-0 z-10 bg-(--t-bg)/60 backdrop-blur-sm rounded-2xl
                      flex items-center justify-center">
            <div class="flex items-center gap-3 text-(--t-text-2)">
              <svg class="animate-spin w-5 h-5 text-(--t-primary)" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              <span class="text-sm">Загрузка...</span>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════
             VIEW: DAY
        ═══════════════════════════════════════════ -->
        <div
          v-if="viewMode === 'day'"
          class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl overflow-hidden"
        >
          <!-- Resource header (if multiple resources) -->
          <div
            v-if="filteredResources.length > 0"
            class="flex border-b border-(--t-border)"
          >
            <!-- Time gutter label -->
            <div class="shrink-0 w-16 border-r border-(--t-border) bg-(--t-bg)/50" />
            <!-- Resource columns -->
            <div
              v-for="res in filteredResources"
              :key="res.id"
              class="flex-1 min-w-28 px-2 py-2.5 text-center border-r border-(--t-border) last:border-r-0
                     bg-(--t-bg)/30"
            >
              <div class="flex items-center justify-center gap-1.5">
                <!-- Avatar circle -->
                <div
                  class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0"
                  :style="{ background: res.color }"
                >
                  {{ res.avatar ?? res.name.charAt(0) }}
                </div>
                <div class="min-w-0 text-left">
                  <div class="text-xs font-semibold text-(--t-text) truncate">{{ res.name }}</div>
                  <div class="text-[9px] text-(--t-text-3)">
                    {{ res.loadPercent }}% загрузка
                  </div>
                </div>
                <!-- Online dot -->
                <span
                  v-if="res.isOnline"
                  class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"
                />
              </div>
            </div>
          </div>

          <!-- Time grid -->
          <div class="relative overflow-y-auto" style="max-block-size: calc(100vh - 280px)">
            <!-- Now line -->
            <div
              v-if="showNowLine"
              class="absolute inset-x-0 z-20 pointer-events-none"
              :style="{ insetBlockStart: nowLineTop + 'px' }"
            >
              <div class="flex items-center">
                <div class="w-2 h-2 rounded-full bg-rose-500 -ml-1 shrink-0" />
                <div class="flex-1 h-px bg-rose-500/70" />
              </div>
            </div>

            <!-- Hour rows -->
            <div
              v-for="hour in hours"
              :key="hour"
              class="flex border-b border-(--t-border)/50 group/row"
              style="block-size: 64px"
            >
              <!-- Time label -->
              <div
                class="shrink-0 w-16 flex items-start justify-end pr-2 pt-1
                       text-[11px] text-(--t-text-3) border-r border-(--t-border)/50 select-none"
              >
                {{ formatHour(hour) }}
              </div>

              <!-- Resource cells (or single column if no resources) -->
              <template v-if="filteredResources.length > 0">
                <div
                  v-for="res in filteredResources"
                  :key="res.id"
                  class="flex-1 min-w-28 relative border-r border-(--t-border)/30 last:border-r-0
                         hover:bg-(--t-card-hover)/40 transition-colors duration-100 cursor-pointer"
                  @click="onSlotClick(currentDate, hour, res.id)"
                  @dragover="onDragOver"
                  @drop="onDrop(currentDate, hour, res.id, $event)"
                >
                  <!-- Events in this cell -->
                  <div
                    v-for="ev in eventsForSlot(currentDate, hour, res.id)"
                    :key="ev.id"
                    :class="[
                      'absolute inset-x-0.5 mx-0.5 rounded-lg border px-2 py-1 cursor-grab',
                      'transition-all duration-200 hover:-translate-y-px hover:shadow-lg',
                      'active:cursor-grabbing select-none z-10',
                      eventClasses(ev),
                      isBulkMode && selectedEventIds.has(ev.id) ? 'ring-2 ring-amber-400' : '',
                    ]"
                    :style="{ insetBlockStart: (parseTime(ev.start).minute / 60) * 64 + 'px', blockSize: Math.max((durationMinutes(ev.start, ev.end) / 60) * 64 - 2, 20) + 'px' }"
                    :draggable="ev.status !== 'blocked'"
                    @dragstart="onDragStart(ev, $event)"
                    @dragend="onDragEnd"
                    @click.stop="openEvent(ev)"
                  >
                    <div class="text-[11px] font-semibold truncate leading-tight">
                      {{ ev.title }}
                    </div>
                    <div v-if="durationMinutes(ev.start, ev.end) >= 30" class="text-[10px] opacity-70 truncate">
                      {{ fmtTime(ev.start) }}–{{ fmtTime(ev.end) }}
                      <template v-if="ev.client"> · {{ ev.client }}</template>
                    </div>
                  </div>
                </div>
              </template>

              <!-- Single column (no resources) -->
              <div
                v-else
                class="flex-1 relative hover:bg-(--t-card-hover)/40 transition-colors duration-100 cursor-pointer"
                @click="onSlotClick(currentDate, hour)"
                @dragover="onDragOver"
                @drop="onDrop(currentDate, hour, undefined, $event)"
              >
                <div
                  v-for="ev in eventsForSlot(currentDate, hour)"
                  :key="ev.id"
                  :class="[
                    'absolute inset-x-1 rounded-lg border px-2 py-1 cursor-grab',
                    'transition-all duration-200 hover:-translate-y-px hover:shadow-lg',
                    'active:cursor-grabbing select-none z-10',
                    eventClasses(ev),
                    isBulkMode && selectedEventIds.has(ev.id) ? 'ring-2 ring-amber-400' : '',
                  ]"
                  :style="{ insetBlockStart: (parseTime(ev.start).minute / 60) * 64 + 'px', blockSize: Math.max((durationMinutes(ev.start, ev.end) / 60) * 64 - 2, 20) + 'px' }"
                  :draggable="ev.status !== 'blocked'"
                  @dragstart="onDragStart(ev, $event)"
                  @dragend="onDragEnd"
                  @click.stop="openEvent(ev)"
                >
                  <div class="text-[11px] font-semibold truncate leading-tight">{{ ev.title }}</div>
                  <div class="text-[10px] opacity-70 truncate">
                    {{ fmtTime(ev.start) }}–{{ fmtTime(ev.end) }}
                    <template v-if="ev.client"> · {{ ev.client }}</template>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════
             VIEW: WEEK
        ═══════════════════════════════════════════ -->
        <div
          v-else-if="viewMode === 'week'"
          class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl overflow-hidden"
        >
          <!-- Week day headers -->
          <div class="flex border-b border-(--t-border)">
            <div class="shrink-0 w-16 border-r border-(--t-border) bg-(--t-bg)/50" />
            <div
              v-for="day in weekDays"
              :key="isoDate(day)"
              :class="[
                'flex-1 min-w-20 text-center py-2 border-r border-(--t-border)/50 last:border-r-0',
                isToday(day) ? 'bg-(--t-primary)/5' : 'bg-(--t-bg)/30',
              ]"
            >
              <div class="text-[10px] uppercase tracking-wider text-(--t-text-3)">
                {{ DAYS_SHORT[(day.getDay() + 6) % 7] }}
              </div>
              <div
                :class="[
                  'text-sm font-bold mt-0.5',
                  isToday(day) ? 'text-(--t-primary)' : 'text-(--t-text)',
                ]"
              >
                {{ day.getDate() }}
              </div>
              <!-- Event count badge -->
              <div v-if="eventsForDay(day).length > 0" class="mt-0.5">
                <span class="inline-flex items-center justify-center min-w-4 h-4 px-1 text-[9px] font-bold rounded-full bg-(--t-primary)/20 text-(--t-primary)">
                  {{ eventsForDay(day).length }}
                </span>
              </div>
            </div>
          </div>

          <!-- Time grid -->
          <div class="relative overflow-y-auto" style="max-block-size: calc(100vh - 280px)">
            <div
              v-for="hour in hours"
              :key="hour"
              class="flex border-b border-(--t-border)/50"
              style="block-size: 48px"
            >
              <div class="shrink-0 w-16 flex items-start justify-end pr-2 pt-1 text-[10px] text-(--t-text-3) border-r border-(--t-border)/50 select-none">
                {{ formatHour(hour) }}
              </div>
              <div
                v-for="day in weekDays"
                :key="isoDate(day)"
                :class="[
                  'flex-1 min-w-20 relative border-r border-(--t-border)/30 last:border-r-0',
                  'hover:bg-(--t-card-hover)/30 transition-colors duration-100 cursor-pointer',
                  isToday(day) ? 'bg-(--t-primary)/2' : '',
                ]"
                @click="onSlotClick(day, hour)"
                @dragover="onDragOver"
                @drop="onDrop(day, hour, undefined, $event)"
              >
                <div
                  v-for="ev in eventsForSlot(day, hour)"
                  :key="ev.id"
                  :class="[
                    'absolute inset-x-0.5 rounded border px-1 py-0.5 z-10',
                    'text-[9px] font-semibold truncate cursor-grab active:cursor-grabbing',
                    'transition-all duration-150 hover:shadow-md',
                    eventClasses(ev),
                  ]"
                  :style="{ insetBlockStart: (parseTime(ev.start).minute / 60) * 48 + 'px', blockSize: Math.max((durationMinutes(ev.start, ev.end) / 60) * 48 - 1, 14) + 'px' }"
                  :draggable="ev.status !== 'blocked'"
                  @dragstart="onDragStart(ev, $event)"
                  @dragend="onDragEnd"
                  @click.stop="openEvent(ev)"
                >
                  {{ ev.title }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════
             VIEW: MONTH
        ═══════════════════════════════════════════ -->
        <div
          v-else-if="viewMode === 'month'"
          class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl overflow-hidden"
        >
          <!-- Weekday headers -->
          <div class="grid grid-cols-7 border-b border-(--t-border)">
            <div
              v-for="d in DAYS_SHORT"
              :key="d"
              class="py-2 text-center text-[10px] uppercase tracking-wider text-(--t-text-3) font-semibold"
            >
              {{ d }}
            </div>
          </div>

          <!-- Grid -->
          <div class="grid grid-cols-7">
            <div
              v-for="(day, idx) in monthGrid"
              :key="idx"
              :class="[
                'relative min-h-20 p-1.5 border-b border-r border-(--t-border)/50 cursor-pointer',
                'transition-colors duration-100 hover:bg-(--t-card-hover)/40',
                day.getMonth() !== currentDate.getMonth() ? 'opacity-30' : '',
                isToday(day) ? 'bg-(--t-primary)/5' : '',
              ]"
              @click="currentDate = day; viewMode = 'day'"
            >
              <div
                :class="[
                  'text-xs font-semibold mb-1',
                  isToday(day) ? 'w-6 h-6 rounded-full bg-(--t-primary) text-white flex items-center justify-center' : 'text-(--t-text)',
                ]"
              >
                {{ day.getDate() }}
              </div>
              <!-- Event dots -->
              <div class="flex flex-wrap gap-0.5">
                <div
                  v-for="ev in eventsForDay(day).slice(0, 3)"
                  :key="ev.id"
                  class="w-1.5 h-1.5 rounded-full"
                  :class="ev.status === 'confirmed' ? 'bg-emerald-400' : ev.status === 'pending' ? 'bg-amber-400' : 'bg-red-400'"
                />
                <span
                  v-if="eventsForDay(day).length > 3"
                  class="text-[8px] text-(--t-text-3) leading-none"
                >
                  +{{ eventsForDay(day).length - 3 }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════
             VIEW: TIMELINE
        ═══════════════════════════════════════════ -->
        <div
          v-else-if="viewMode === 'timeline'"
          class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl overflow-hidden"
        >
          <!-- Resource rows (horizontal timeline) -->
          <div class="overflow-x-auto">
            <!-- Hour header -->
            <div class="flex border-b border-(--t-border) sticky top-0 z-10 bg-(--t-surface)">
              <div class="shrink-0 w-36 border-r border-(--t-border) bg-(--t-bg)/50 px-3 py-2">
                <span class="text-xs font-semibold text-(--t-text-2)">{{ vc.resourceLabel }}</span>
              </div>
              <div class="flex">
                <div
                  v-for="hour in hours"
                  :key="hour"
                  class="shrink-0 text-center py-2 border-r border-(--t-border)/50 text-[10px] text-(--t-text-3)"
                  style="inline-size: 80px"
                >
                  {{ formatHour(hour) }}
                </div>
              </div>
            </div>

            <!-- Resource rows -->
            <div
              v-for="res in filteredResources"
              :key="res.id"
              class="flex border-b border-(--t-border)/50 hover:bg-(--t-card-hover)/20 transition-colors"
            >
              <!-- Resource label -->
              <div class="shrink-0 w-36 border-r border-(--t-border)/50 px-3 py-2 flex items-center gap-2">
                <div
                  class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0"
                  :style="{ background: res.color }"
                >
                  {{ res.avatar ?? res.name.charAt(0) }}
                </div>
                <div class="min-w-0">
                  <div class="text-xs font-semibold text-(--t-text) truncate">{{ res.name }}</div>
                  <div class="text-[9px] text-(--t-text-3)">{{ res.loadPercent }}%</div>
                </div>
              </div>
              <!-- Hours -->
              <div class="flex relative" :style="{ inlineSize: hours.length * 80 + 'px' }">
                <div
                  v-for="hour in hours"
                  :key="hour"
                  class="shrink-0 border-r border-(--t-border)/30 hover:bg-(--t-card-hover)/40
                         transition-colors cursor-pointer"
                  style="inline-size: 80px; block-size: 48px"
                  @click="onSlotClick(currentDate, hour, res.id)"
                  @dragover="onDragOver"
                  @drop="onDrop(currentDate, hour, res.id, $event)"
                />
                <!-- Events overlay -->
                <div
                  v-for="ev in eventsForDay(currentDate).filter(e => String(e.resourceId) === String(res.id))"
                  :key="ev.id"
                  :class="[
                    'absolute top-1 bottom-1 rounded-lg border px-1.5 py-0.5 z-10',
                    'text-[10px] font-semibold truncate cursor-grab active:cursor-grabbing',
                    'transition-all duration-150 hover:shadow-lg',
                    eventClasses(ev),
                  ]"
                  :style="{
                    insetInlineStart: ((parseTime(ev.start).hour - vc.workStart) * 80 + (parseTime(ev.start).minute / 60) * 80) + 'px',
                    inlineSize: Math.max((durationMinutes(ev.start, ev.end) / 60) * 80 - 2, 30) + 'px',
                  }"
                  :draggable="ev.status !== 'blocked'"
                  @dragstart="onDragStart(ev, $event)"
                  @dragend="onDragEnd"
                  @click.stop="openEvent(ev)"
                >
                  {{ ev.title }}
                </div>
              </div>
            </div>

            <!-- Empty state -->
            <div
              v-if="filteredResources.length === 0"
              class="flex items-center justify-center py-12 text-(--t-text-3)"
            >
              <span class="text-3xl mr-3">📋</span>
              <span class="text-sm">Добавьте {{ vc.resourceLabelPlural.toLowerCase() }} для отображения timeline</span>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════
             VIEW: LIST
        ═══════════════════════════════════════════ -->
        <VCard
          v-else-if="viewMode === 'list'"
          :title="`${vc.eventLabelPlural} — ${dateTitle}`"
          :loading="loading"
          :no-padding="true"
        >
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-(--t-border)">
                  <th v-if="isBulkMode" class="py-3 px-3 w-10" />
                  <th class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wider text-(--t-text-3)">Время</th>
                  <th class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wider text-(--t-text-3)">{{ vc.eventLabel }}</th>
                  <th class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wider text-(--t-text-3)">Клиент</th>
                  <th class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wider text-(--t-text-3)">{{ vc.resourceLabel }}</th>
                  <th class="py-3 px-4 text-right text-xs font-semibold uppercase tracking-wider text-(--t-text-3)">Сумма</th>
                  <th class="py-3 px-4 text-center text-xs font-semibold uppercase tracking-wider text-(--t-text-3)">Статус</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-(--t-border)/50">
                <tr
                  v-for="ev in todayEvents"
                  :key="ev.id"
                  class="group transition-colors duration-150 hover:bg-(--t-card-hover) cursor-pointer"
                  @click="openEvent(ev)"
                >
                  <td v-if="isBulkMode" class="py-3 px-3">
                    <input
                      type="checkbox"
                      :checked="selectedEventIds.has(ev.id)"
                      @click.stop="toggleBulkSelect(ev.id)"
                      class="rounded border-(--t-border) text-(--t-primary) focus:ring-(--t-primary)"
                    />
                  </td>
                  <td class="py-3 px-4 text-(--t-text-2) font-mono text-xs whitespace-nowrap">
                    {{ fmtTime(ev.start) }}–{{ fmtTime(ev.end) }}
                  </td>
                  <td class="py-3 px-4 text-(--t-text) font-medium">{{ ev.title }}</td>
                  <td class="py-3 px-4 text-(--t-text-2)">{{ ev.client ?? '—' }}</td>
                  <td class="py-3 px-4 text-(--t-text-2)">{{ resourceById(ev.resourceId)?.name ?? '—' }}</td>
                  <td class="py-3 px-4 text-right font-semibold text-(--t-text)">
                    {{ ev.price ? fmtNum(ev.price) + ' ₽' : '—' }}
                  </td>
                  <td class="py-3 px-4 text-center">
                    <VBadge
                      :text="vc.statusMap[ev.status]?.text ?? ev.status"
                      :variant="vc.statusMap[ev.status]?.variant ?? 'neutral'"
                      :dot="true"
                      size="xs"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Empty state -->
          <div
            v-if="todayEvents.length === 0"
            class="flex flex-col items-center py-12 text-(--t-text-3)"
          >
            <span class="text-4xl mb-3">{{ vc.icon }}</span>
            <p class="text-sm">{{ vc.eventLabelPlural }} не найдены</p>
          </div>
        </VCard>
      </div>

      <!-- ─── SIDEBAR (desktop) ─── -->
      <Transition name="sidebar-slide">
        <aside
          v-if="showSidebar && (viewMode === 'day' || viewMode === 'timeline')"
          class="hidden lg:flex flex-col gap-4 shrink-0"
          style="inline-size: 280px"
        >
          <!-- Resource Load Panel -->
          <VCard :title="`Загрузка ${vc.resourceLabelPlural.toLowerCase()}`" glow>
            <div class="space-y-3">
              <div
                v-for="res in filteredResources"
                :key="res.id"
                class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-(--t-card-hover)
                       transition-all duration-150 cursor-pointer group/res"
                @click="filterResource = filterResource === String(res.id) ? '' : String(res.id)"
              >
                <div
                  class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0
                         transition-transform duration-200 group-hover/res:scale-110"
                  :style="{ background: res.color }"
                >
                  {{ res.avatar ?? res.name.charAt(0) }}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-(--t-text) truncate">{{ res.name }}</span>
                    <span
                      :class="[
                        'text-[10px] font-bold',
                        res.loadPercent > 80 ? 'text-rose-400' : res.loadPercent > 50 ? 'text-amber-400' : 'text-emerald-400',
                      ]"
                    >
                      {{ res.loadPercent }}%
                    </span>
                  </div>
                  <!-- Load bar -->
                  <div class="mt-1 h-1.5 rounded-full bg-(--t-border) overflow-hidden">
                    <div
                      :class="[
                        'h-full rounded-full transition-all duration-700',
                        res.loadPercent > 80 ? 'bg-rose-500' : res.loadPercent > 50 ? 'bg-amber-500' : 'bg-emerald-500',
                      ]"
                      :style="{ inlineSize: res.loadPercent + '%' }"
                    />
                  </div>
                </div>
                <!-- Online indicator -->
                <span
                  v-if="res.isOnline"
                  class="w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-(--t-bg) shrink-0"
                />
              </div>

              <!-- Empty -->
              <div v-if="filteredResources.length === 0" class="text-center py-4 text-(--t-text-3) text-xs">
                Нет {{ vc.resourceLabelPlural.toLowerCase() }}
              </div>
            </div>
          </VCard>

          <!-- Day Stats -->
          <VCard title="Статистика дня">
            <div class="space-y-2.5">
              <div class="flex items-center justify-between">
                <span class="text-xs text-(--t-text-3)">Всего</span>
                <span class="text-sm font-bold text-(--t-text)">{{ todayEvents.length }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-xs text-emerald-400">✅ Подтверждено</span>
                <span class="text-sm font-bold text-emerald-400">{{ confirmedCount }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-xs text-amber-400">⏳ Ожидает</span>
                <span class="text-sm font-bold text-amber-400">{{ pendingCount }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-xs text-sky-400">✨ Завершено</span>
                <span class="text-sm font-bold text-sky-400">{{ completedCount }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-xs text-red-400">❌ Отмены</span>
                <span class="text-sm font-bold text-red-400">{{ cancelledCount }}</span>
              </div>
              <div class="border-t border-(--t-border) pt-2 mt-2 flex items-center justify-between">
                <span class="text-xs text-(--t-text-2)">💰 Выручка</span>
                <span class="text-sm font-bold text-(--t-text)">{{ fmtNum(todayRevenue) }} ₽</span>
              </div>
            </div>
          </VCard>

          <!-- Quick add -->
          <VButton variant="primary" full-width @click="onSlotClick(currentDate, new Date().getHours())">
            ➕ {{ vc.eventLabel }}
          </VButton>
        </aside>
      </Transition>
    </div>

    <!-- ═══════════════════════════════════════════════════
         MOBILE SIDEBAR (bottom sheet trigger)
    ══════════════════════════════════════════════════════ -->
    <div class="lg:hidden flex gap-2">
      <VButton variant="secondary" full-width @click="sidebarMobile = true">
        📊 Загрузка {{ vc.resourceLabelPlural.toLowerCase() }}
      </VButton>
      <VButton variant="primary" full-width @click="onSlotClick(currentDate, new Date().getHours())">
        ➕ {{ vc.eventLabel }}
      </VButton>
    </div>

    <!-- ═══════════════════════════════════════════════════
         EVENT DETAIL MODAL
    ══════════════════════════════════════════════════════ -->
    <VModal v-model="showEventModal" :title="`${vc.eventLabel} #${selectedEvent?.id ?? ''}`" size="md" @close="closeEventModal">
      <template v-if="selectedEvent">
        <div class="space-y-4">
          <!-- Status -->
          <div class="flex items-center gap-2">
            <VBadge
              :text="vc.statusMap[selectedEvent.status]?.text ?? selectedEvent.status"
              :variant="vc.statusMap[selectedEvent.status]?.variant ?? 'neutral'"
              :dot="true"
            />
            <span class="text-sm text-(--t-text-3)">
              {{ vc.statusMap[selectedEvent.status]?.icon }} {{ fmtTime(selectedEvent.start) }} – {{ fmtTime(selectedEvent.end) }}
            </span>
          </div>

          <!-- Info grid -->
          <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl p-3 bg-(--t-card-hover)">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">{{ vc.eventLabel }}</div>
              <div class="text-sm font-semibold text-(--t-text) mt-0.5">{{ selectedEvent.title }}</div>
            </div>
            <div class="rounded-xl p-3 bg-(--t-card-hover)">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Клиент</div>
              <div class="text-sm font-semibold text-(--t-text) mt-0.5">{{ selectedEvent.client ?? '—' }}</div>
            </div>
            <div class="rounded-xl p-3 bg-(--t-card-hover)">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">{{ vc.resourceLabel }}</div>
              <div class="text-sm font-semibold text-(--t-text) mt-0.5">
                {{ resourceById(selectedEvent.resourceId)?.name ?? '—' }}
              </div>
            </div>
            <div class="rounded-xl p-3 bg-(--t-card-hover)">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Стоимость</div>
              <div class="text-sm font-bold text-emerald-400 mt-0.5">
                {{ selectedEvent.price ? fmtNum(selectedEvent.price) + ' ₽' : '—' }}
              </div>
            </div>
          </div>

          <!-- Service -->
          <div v-if="selectedEvent.service" class="rounded-xl p-3 bg-(--t-card-hover)">
            <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Услуга</div>
            <div class="text-sm text-(--t-text) mt-0.5">{{ selectedEvent.service }}</div>
          </div>

          <!-- Notes -->
          <div v-if="selectedEvent.notes" class="rounded-xl p-3 bg-(--t-card-hover)">
            <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Заметки</div>
            <div class="text-sm text-(--t-text-2) mt-0.5">{{ selectedEvent.notes }}</div>
          </div>

          <!-- Phone -->
          <div v-if="selectedEvent.phone" class="flex items-center gap-2">
            <span class="text-lg">📱</span>
            <a :href="`tel:${selectedEvent.phone}`" class="text-sm text-(--t-primary) hover:underline">
              {{ selectedEvent.phone }}
            </a>
          </div>
        </div>
      </template>
      <template #footer>
        <VButton variant="ghost" size="sm" @click="closeEventModal">Закрыть</VButton>
        <VButton variant="secondary" size="sm">✏️ Редактировать</VButton>
        <VButton variant="danger" size="sm">❌ Отменить</VButton>
      </template>
    </VModal>

    <!-- ═══════════════════════════════════════════════════
         BLOCK SLOTS MODAL
    ══════════════════════════════════════════════════════ -->
    <VModal v-model="showBlockModal" title="🔒 Заблокировать слоты" size="sm">
      <div class="space-y-4">
        <VInput v-model.number="blockRange.startHour" label="Начало (час)" type="number" />
        <VInput v-model.number="blockRange.endHour"   label="Конец (час)"  type="number" />
        <div>
          <label class="block mb-1.5 text-xs font-medium text-(--t-text-2)">{{ vc.resourceLabel }}</label>
          <select
            v-model="blockRange.resourceId"
            class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-surface) border border-(--t-border)
                   text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
          >
            <option value="">Все</option>
            <option v-for="r in resources" :key="r.id" :value="String(r.id)">{{ r.name }}</option>
          </select>
        </div>
      </div>
      <template #footer>
        <VButton variant="ghost" size="sm" @click="showBlockModal = false">Отмена</VButton>
        <VButton variant="danger" size="sm" @click="confirmBlock">🔒 Заблокировать</VButton>
      </template>
    </VModal>

    <!-- ═══════════════════════════════════════════════════
         MOBILE SIDEBAR MODAL
    ══════════════════════════════════════════════════════ -->
    <VModal v-model="sidebarMobile" :title="`Загрузка ${vc.resourceLabelPlural.toLowerCase()}`" size="md">
      <div class="space-y-3">
        <div
          v-for="res in filteredResources"
          :key="res.id"
          class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-(--t-card-hover) transition-all"
        >
          <div
            class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white shrink-0"
            :style="{ background: res.color }"
          >
            {{ res.avatar ?? res.name.charAt(0) }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <span class="text-sm font-semibold text-(--t-text) truncate">{{ res.name }}</span>
              <span
                :class="[
                  'text-xs font-bold',
                  res.loadPercent > 80 ? 'text-rose-400' : res.loadPercent > 50 ? 'text-amber-400' : 'text-emerald-400',
                ]"
              >
                {{ res.loadPercent }}%
              </span>
            </div>
            <div class="mt-1.5 h-2 rounded-full bg-(--t-border) overflow-hidden">
              <div
                :class="[
                  'h-full rounded-full transition-all duration-700',
                  res.loadPercent > 80 ? 'bg-rose-500' : res.loadPercent > 50 ? 'bg-amber-500' : 'bg-emerald-500',
                ]"
                :style="{ inlineSize: res.loadPercent + '%' }"
              />
            </div>
          </div>
        </div>
      </div>
    </VModal>
  </div>
</template>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     STYLES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<style scoped>
/* ── Ripple ─────────────────────────────── */
@keyframes ripple-cal {
  to { transform: scale(3.5); opacity: 0; }
}

/* ── Slide-down (bulk bar) ──────────────── */
.slide-down-cal-enter-active,
.slide-down-cal-leave-active {
  transition: all 0.3s cubic-bezier(.4,0,.2,1);
}
.slide-down-cal-enter-from,
.slide-down-cal-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* ── Sidebar slide ──────────────────────── */
.sidebar-slide-enter-active {
  transition: all 0.35s cubic-bezier(.4,0,.2,1);
}
.sidebar-slide-leave-active {
  transition: all 0.25s cubic-bezier(.4,0,.2,1);
}
.sidebar-slide-enter-from,
.sidebar-slide-leave-to {
  opacity: 0;
  transform: translateX(16px);
}

/* ── Scrollbar ──────────────────────────── */
.overflow-y-auto::-webkit-scrollbar,
.overflow-x-auto::-webkit-scrollbar {
  inline-size: 5px;
  block-size: 5px;
}
.overflow-y-auto::-webkit-scrollbar-track,
.overflow-x-auto::-webkit-scrollbar-track {
  background: transparent;
}
.overflow-y-auto::-webkit-scrollbar-thumb,
.overflow-x-auto::-webkit-scrollbar-thumb {
  background: var(--t-border);
  border-radius: 9999px;
}

/* ── Drag active state ──────────────────── */
[draggable="true"]:active {
  opacity: 0.7;
  cursor: grabbing;
}
</style>
