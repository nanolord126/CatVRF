<script setup lang="ts">
/**
 * TenantAnalytics.vue — главная страница аналитики в B2B Tenant Dashboard
 *
 * Универсальная аналитика для всех 127 вертикалей CatVRF:
 *   Beauty  (салоны · мастера)       · Taxi   (тарифы · водители)
 *   Food    (рестораны · доставка)    · Hotels (номера · бронь)
 *   RealEstate (объекты · сделки)    · Flowers (букеты · доставка)
 *   Fashion (одежда · обувь)         · Furniture (мебель · декор)
 *   Fitness (абонементы · тренеры)   · Travel (туры · билеты)
 *   default (универсальный)
 *
 * ─────────────────────────────────────────────────────────────
 *  Функционал:
 *   1.  Period selector (day / week / month / quarter / year)
 *   2.  Сравнение с предыдущим периодом (delta % + sparkline)
 *   3.  8 KPI-виджетов: revenue, orders, clients, conversion,
 *       averageCheck, LTV, retention, NPS
 *   4.  Revenue Timeline Chart (bar + line)
 *   5.  Revenue Breakdown (donut)
 *   6.  Acquisition Sources (horizontal bars)
 *   7.  Activity Heatmap (7×24 grid)
 *   8.  Top-таблицы: staff, services/items, clients
 *   9.  Quick Reports sidebar (PDF/XLSX export)
 *  10.  Full-screen, keyboard (Esc), ripple-an
 * ─────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useAuth, useTenant } from '@/stores'

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  TYPES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

type PeriodKey  = 'day' | 'week' | 'month' | 'quarter' | 'year'
type ChartTab   = 'timeline' | 'breakdown' | 'sources' | 'heatmap'
type TopTab     = 'staff' | 'items' | 'clients'
type TrendDir   = 'up' | 'down' | 'flat'
type ExportFmt  = 'pdf' | 'xlsx' | 'csv'

interface KpiValue {
  key:           string
  label:         string
  value:         number
  formatted:     string
  prevValue:     number
  prevFormatted: string
  delta:         number
  trend:         TrendDir
  icon:          string
  color:         string
  sparkline:     number[]
  suffix?:       string
}

interface TimelinePoint {
  label: string
  revenue: number
  orders: number
  prevRevenue?: number
}

interface BreakdownSlice {
  label:   string
  value:   number
  percent: number
  color:   string
}

interface AcquisitionRow {
  label: string
  value: number
  percent: number
  color: string
  icon: string
}

interface HeatmapCell {
  day:   number
  hour:  number
  value: number
}

interface TopStaffRow {
  id:        number | string
  name:      string
  avatar?:   string
  role:      string
  revenue:   number
  orders:    number
  rating:    number
  delta:     number
}

interface TopItemRow {
  id:        number | string
  name:      string
  image?:    string
  category:  string
  revenue:   number
  orders:    number
  conversion: number
  delta:     number
}

interface TopClientRow {
  id:        number | string
  name:      string
  avatar?:   string
  orders:    number
  revenue:   number
  ltv:       number
  lastVisit: string
  isVip:     boolean
}

interface QuickReport {
  key:    string
  label:  string
  icon:   string
  desc:   string
}

interface VerticalAnalyticsConfig {
  label:            string
  icon:             string
  revenueLabel:     string
  ordersLabel:      string
  clientsLabel:     string
  staffLabel:       string
  itemsLabel:       string
  conversionLabel:  string
  avgCheckLabel:    string
  topStaffTitle:    string
  topItemsTitle:    string
  topClientsTitle:  string
  breakdownCategories: string[]
  acquisitionChannels: Array<{ label: string; icon: string; color: string }>
  kpiExtra?:        Array<{ key: string; label: string; icon: string; color: string }>
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  PROPS & EMITS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const props = withDefaults(defineProps<{
  vertical?:       string
  kpis?:           KpiValue[]
  timeline?:       TimelinePoint[]
  breakdown?:      BreakdownSlice[]
  acquisition?:    AcquisitionRow[]
  heatmap?:        HeatmapCell[]
  topStaff?:       TopStaffRow[]
  topItems?:       TopItemRow[]
  topClients?:     TopClientRow[]
  loading?:        boolean
  lastUpdated?:    string
  currency?:       string
  compareEnabled?: boolean
}>(), {
  vertical:       'default',
  kpis:           () => [],
  timeline:       () => [],
  breakdown:      () => [],
  acquisition:    () => [],
  heatmap:        () => [],
  topStaff:       () => [],
  topItems:       () => [],
  topClients:     () => [],
  loading:        false,
  lastUpdated:    '',
  currency:       'RUB',
  compareEnabled: true,
})

const emit = defineEmits<{
  'period-change':    [period: PeriodKey]
  'compare-toggle':   [enabled: boolean]
  'export-report':    [format: ExportFmt, reportKey: string]
  'kpi-click':        [kpi: KpiValue]
  'staff-click':      [staff: TopStaffRow]
  'item-click':       [item: TopItemRow]
  'client-click':     [client: TopClientRow]
  'chart-zoom':       [chart: ChartTab, range: { from: string; to: string }]
  'refresh':          []
  'toggle-fullscreen': []
}>()

const auth = useAuth()
const biz  = useTenant()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  VERTICAL CONFIG
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const VERTICAL_ANALYTICS_CONFIG: Record<string, VerticalAnalyticsConfig> = {
  beauty: {
    label: 'Салоны красоты', icon: '💄',
    revenueLabel: 'Выручка', ordersLabel: 'Записи', clientsLabel: 'Клиенты',
    staffLabel: 'Мастера', itemsLabel: 'Услуги',
    conversionLabel: 'Конверсия записи', avgCheckLabel: 'Средний чек',
    topStaffTitle: 'Топ-мастера', topItemsTitle: 'Топ-услуги', topClientsTitle: 'Топ-клиенты',
    breakdownCategories: ['Стрижки', 'Окрашивание', 'Маникюр', 'Косметология', 'Массаж', 'Прочее'],
    acquisitionChannels: [
      { label: 'Органический поиск', icon: '🔍', color: '#3b82f6' },
      { label: 'Соцсети',            icon: '📱', color: '#8b5cf6' },
      { label: 'Рекомендации',       icon: '💬', color: '#10b981' },
      { label: 'Реклама',            icon: '📢', color: '#f59e0b' },
      { label: 'Повторные',          icon: '🔄', color: '#ec4899' },
      { label: 'Прочее',             icon: '📎', color: '#6b7280' },
    ],
  },
  taxi: {
    label: 'Такси', icon: '🚕',
    revenueLabel: 'Выручка', ordersLabel: 'Поездки', clientsLabel: 'Пассажиры',
    staffLabel: 'Водители', itemsLabel: 'Тарифы',
    conversionLabel: 'Конверсия заказа', avgCheckLabel: 'Средний чек',
    topStaffTitle: 'Топ-водители', topItemsTitle: 'Топ-тарифы', topClientsTitle: 'Топ-пассажиры',
    breakdownCategories: ['Эконом', 'Комфорт', 'Бизнес', 'Премиум', 'Минивэн', 'Грузовой'],
    acquisitionChannels: [
      { label: 'Приложение',     icon: '📱', color: '#3b82f6' },
      { label: 'Звонки',        icon: '📞', color: '#8b5cf6' },
      { label: 'Агрегаторы',    icon: '🔗', color: '#10b981' },
      { label: 'Корпоративные', icon: '🏢', color: '#f59e0b' },
      { label: 'Повторные',     icon: '🔄', color: '#ec4899' },
      { label: 'Прочее',        icon: '📎', color: '#6b7280' },
    ],
  },
  food: {
    label: 'Еда и рестораны', icon: '🍽️',
    revenueLabel: 'Выручка', ordersLabel: 'Заказы', clientsLabel: 'Клиенты',
    staffLabel: 'Повара', itemsLabel: 'Блюда',
    conversionLabel: 'Конверсия заказа', avgCheckLabel: 'Средний чек',
    topStaffTitle: 'Топ-повара', topItemsTitle: 'Топ-блюда', topClientsTitle: 'Топ-клиенты',
    breakdownCategories: ['Завтраки', 'Супы', 'Горячее', 'Салаты', 'Десерты', 'Напитки'],
    acquisitionChannels: [
      { label: 'Агрегаторы',   icon: '📲', color: '#3b82f6' },
      { label: 'Сайт',         icon: '🌐', color: '#8b5cf6' },
      { label: 'Рекомендации', icon: '💬', color: '#10b981' },
      { label: 'Реклама',      icon: '📢', color: '#f59e0b' },
      { label: 'Повторные',    icon: '🔄', color: '#ec4899' },
      { label: 'Прочее',       icon: '📎', color: '#6b7280' },
    ],
  },
  hotel: {
    label: 'Отели', icon: '🏨',
    revenueLabel: 'Выручка', ordersLabel: 'Бронирования', clientsLabel: 'Гости',
    staffLabel: 'Персонал', itemsLabel: 'Номера',
    conversionLabel: 'Конверсия брони', avgCheckLabel: 'Средний чек',
    topStaffTitle: 'Топ-персонал', topItemsTitle: 'Топ-номера', topClientsTitle: 'Топ-гости',
    breakdownCategories: ['Стандарт', 'Улучшенный', 'Люкс', 'Апартаменты', 'Семейный', 'Премиум'],
    acquisitionChannels: [
      { label: 'Booking',      icon: '🅱️', color: '#3b82f6' },
      { label: 'Прямые',       icon: '🌐', color: '#8b5cf6' },
      { label: 'Турагентства', icon: '✈️', color: '#10b981' },
      { label: 'Корпоративные', icon: '🏢', color: '#f59e0b' },
      { label: 'Повторные',    icon: '🔄', color: '#ec4899' },
      { label: 'Прочее',       icon: '📎', color: '#6b7280' },
    ],
  },
  realEstate: {
    label: 'Недвижимость', icon: '🏢',
    revenueLabel: 'Оборот', ordersLabel: 'Сделки', clientsLabel: 'Клиенты',
    staffLabel: 'Агенты', itemsLabel: 'Объекты',
    conversionLabel: 'Конверсия сделки', avgCheckLabel: 'Средняя сделка',
    topStaffTitle: 'Топ-агенты', topItemsTitle: 'Топ-объекты', topClientsTitle: 'Топ-клиенты',
    breakdownCategories: ['Квартиры', 'Дома', 'Коммерция', 'Новостройки', 'Аренда', 'Земля'],
    acquisitionChannels: [
      { label: 'ЦИАН / Авито', icon: '🏠', color: '#3b82f6' },
      { label: 'Сайт',         icon: '🌐', color: '#8b5cf6' },
      { label: 'Рекомендации', icon: '💬', color: '#10b981' },
      { label: 'Реклама',      icon: '📢', color: '#f59e0b' },
      { label: 'Повторные',    icon: '🔄', color: '#ec4899' },
      { label: 'Прочее',       icon: '📎', color: '#6b7280' },
    ],
  },
  flowers: {
    label: 'Цветы', icon: '💐',
    revenueLabel: 'Выручка', ordersLabel: 'Заказы', clientsLabel: 'Клиенты',
    staffLabel: 'Флористы', itemsLabel: 'Букеты',
    conversionLabel: 'Конверсия заказа', avgCheckLabel: 'Средний чек',
    topStaffTitle: 'Топ-флористы', topItemsTitle: 'Топ-букеты', topClientsTitle: 'Топ-клиенты',
    breakdownCategories: ['Розы', 'Полевые', 'Экзотические', 'Свадебные', 'Траурные', 'Комнатные'],
    acquisitionChannels: [
      { label: 'Маркетплейсы', icon: '🛒', color: '#3b82f6' },
      { label: 'Сайт',         icon: '🌐', color: '#8b5cf6' },
      { label: 'Соцсети',      icon: '📱', color: '#10b981' },
      { label: 'Рекомендации', icon: '💬', color: '#f59e0b' },
      { label: 'Повторные',    icon: '🔄', color: '#ec4899' },
      { label: 'Прочее',       icon: '📎', color: '#6b7280' },
    ],
  },
  fashion: {
    label: 'Одежда и обувь', icon: '👗',
    revenueLabel: 'Выручка', ordersLabel: 'Заказы', clientsLabel: 'Покупатели',
    staffLabel: 'Стилисты', itemsLabel: 'Товары',
    conversionLabel: 'Конверсия покупки', avgCheckLabel: 'Средний чек',
    topStaffTitle: 'Топ-стилисты', topItemsTitle: 'Топ-товары', topClientsTitle: 'Топ-покупатели',
    breakdownCategories: ['Платья', 'Верхняя', 'Обувь', 'Аксессуары', 'Спорт', 'Бельё'],
    acquisitionChannels: [
      { label: 'Маркетплейсы', icon: '🛒', color: '#3b82f6' },
      { label: 'Сайт',         icon: '🌐', color: '#8b5cf6' },
      { label: 'Соцсети',      icon: '📱', color: '#10b981' },
      { label: 'Блогеры',      icon: '📸', color: '#f59e0b' },
      { label: 'Повторные',    icon: '🔄', color: '#ec4899' },
      { label: 'Прочее',       icon: '📎', color: '#6b7280' },
    ],
  },
  furniture: {
    label: 'Мебель', icon: '🛋️',
    revenueLabel: 'Выручка', ordersLabel: 'Заказы', clientsLabel: 'Покупатели',
    staffLabel: 'Дизайнеры', itemsLabel: 'Товары',
    conversionLabel: 'Конверсия заказа', avgCheckLabel: 'Средний чек',
    topStaffTitle: 'Топ-дизайнеры', topItemsTitle: 'Топ-товары', topClientsTitle: 'Топ-покупатели',
    breakdownCategories: ['Диваны', 'Столы', 'Шкафы', 'Кровати', 'Кухни', 'Декор'],
    acquisitionChannels: [
      { label: 'Маркетплейсы', icon: '🛒', color: '#3b82f6' },
      { label: 'Шоурум',       icon: '🏬', color: '#8b5cf6' },
      { label: 'Дизайн-проект', icon: '🎨', color: '#10b981' },
      { label: 'Реклама',      icon: '📢', color: '#f59e0b' },
      { label: 'Повторные',    icon: '🔄', color: '#ec4899' },
      { label: 'Прочее',       icon: '📎', color: '#6b7280' },
    ],
  },
  fitness: {
    label: 'Фитнес', icon: '💪',
    revenueLabel: 'Выручка', ordersLabel: 'Записи', clientsLabel: 'Клиенты',
    staffLabel: 'Тренеры', itemsLabel: 'Абонементы',
    conversionLabel: 'Конверсия записи', avgCheckLabel: 'Средний чек',
    topStaffTitle: 'Топ-тренеры', topItemsTitle: 'Топ-абонементы', topClientsTitle: 'Топ-клиенты',
    breakdownCategories: ['Абонементы', 'Персональные', 'Групповые', 'SPA', 'Питание', 'Товары'],
    acquisitionChannels: [
      { label: 'Сайт',         icon: '🌐', color: '#3b82f6' },
      { label: 'Соцсети',      icon: '📱', color: '#8b5cf6' },
      { label: 'Рекомендации', icon: '💬', color: '#10b981' },
      { label: 'Партнёры',     icon: '🤝', color: '#f59e0b' },
      { label: 'Повторные',    icon: '🔄', color: '#ec4899' },
      { label: 'Прочее',       icon: '📎', color: '#6b7280' },
    ],
  },
  travel: {
    label: 'Путешествия', icon: '✈️',
    revenueLabel: 'Оборот', ordersLabel: 'Бронирования', clientsLabel: 'Туристы',
    staffLabel: 'Менеджеры', itemsLabel: 'Туры',
    conversionLabel: 'Конверсия брони', avgCheckLabel: 'Средний чек',
    topStaffTitle: 'Топ-менеджеры', topItemsTitle: 'Топ-туры', topClientsTitle: 'Топ-туристы',
    breakdownCategories: ['Пляжный', 'Экскурсии', 'Горнолыжный', 'Круизы', 'Авторские', 'Корпоратив'],
    acquisitionChannels: [
      { label: 'Агрегаторы',   icon: '🔗', color: '#3b82f6' },
      { label: 'Сайт',         icon: '🌐', color: '#8b5cf6' },
      { label: 'Рекомендации', icon: '💬', color: '#10b981' },
      { label: 'Реклама',      icon: '📢', color: '#f59e0b' },
      { label: 'Повторные',    icon: '🔄', color: '#ec4899' },
      { label: 'Прочее',       icon: '📎', color: '#6b7280' },
    ],
  },
  default: {
    label: 'Бизнес', icon: '📊',
    revenueLabel: 'Выручка', ordersLabel: 'Заказы', clientsLabel: 'Клиенты',
    staffLabel: 'Сотрудники', itemsLabel: 'Товары/Услуги',
    conversionLabel: 'Конверсия', avgCheckLabel: 'Средний чек',
    topStaffTitle: 'Топ-сотрудники', topItemsTitle: 'Топ-позиции', topClientsTitle: 'Топ-клиенты',
    breakdownCategories: ['Категория A', 'Категория B', 'Категория C', 'Категория D', 'Категория E', 'Прочее'],
    acquisitionChannels: [
      { label: 'Органический',  icon: '🔍', color: '#3b82f6' },
      { label: 'Соцсети',       icon: '📱', color: '#8b5cf6' },
      { label: 'Рекомендации',  icon: '💬', color: '#10b981' },
      { label: 'Реклама',       icon: '📢', color: '#f59e0b' },
      { label: 'Повторные',     icon: '🔄', color: '#ec4899' },
      { label: 'Прочее',        icon: '📎', color: '#6b7280' },
    ],
  },
}

const vc = computed<VerticalAnalyticsConfig>(() =>
  VERTICAL_ANALYTICS_CONFIG[props.vertical] ?? VERTICAL_ANALYTICS_CONFIG.default
)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  MAPS & CONSTANTS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const PERIOD_OPTIONS: Array<{ key: PeriodKey; label: string; shortLabel: string }> = [
  { key: 'day',     label: 'День',     shortLabel: 'Д' },
  { key: 'week',    label: 'Неделя',   shortLabel: 'Н' },
  { key: 'month',   label: 'Месяц',    shortLabel: 'М' },
  { key: 'quarter', label: 'Квартал',  shortLabel: 'Кв' },
  { key: 'year',    label: 'Год',      shortLabel: 'Г' },
]

const CHART_TABS: Array<{ key: ChartTab; label: string; icon: string }> = [
  { key: 'timeline',  label: 'Динамика',   icon: '📈' },
  { key: 'breakdown', label: 'Структура',  icon: '🍩' },
  { key: 'sources',   label: 'Источники',  icon: '🎯' },
  { key: 'heatmap',   label: 'Тепловая карта', icon: '🔥' },
]

const TOP_TABS: Array<{ key: TopTab; label: string; icon: string }> = [
  { key: 'staff',   label: '',  icon: '👤' },
  { key: 'items',   label: '',  icon: '📦' },
  { key: 'clients', label: '',  icon: '🤝' },
]

const DAY_NAMES  = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'] as const
const HOUR_TICKS = [0, 3, 6, 9, 12, 15, 18, 21] as const

const QUICK_REPORTS: QuickReport[] = [
  { key: 'revenue',    label: 'Выручка',            icon: '💰', desc: 'Полный отчёт по выручке за период' },
  { key: 'clients',    label: 'Клиенты',            icon: '👥', desc: 'Новые, вернувшиеся, churn-анализ' },
  { key: 'services',   label: 'Услуги/Товары',      icon: '📦', desc: 'ABC-анализ, маржинальность' },
  { key: 'staff',      label: 'Персонал',           icon: '👤', desc: 'Производительность, KPI сотрудников' },
  { key: 'marketing',  label: 'Маркетинг',          icon: '📢', desc: 'ROI каналов, CAC, LTV/CAC' },
  { key: 'forecast',   label: 'Прогноз',            icon: '🔮', desc: 'AI-прогноз спроса на 30 дней' },
  { key: 'comparison', label: 'Сравнение периодов',  icon: '⚖️', desc: 'Год к году, месяц к месяцу' },
  { key: 'cohort',     label: 'Когортный анализ',    icon: '📊', desc: 'Retention по когортам клиентов' },
]

const DONUT_COLORS = [
  'rgba(59,130,246,0.85)',
  'rgba(139,92,246,0.85)',
  'rgba(16,185,129,0.85)',
  'rgba(245,158,11,0.85)',
  'rgba(236,72,153,0.85)',
  'rgba(107,114,128,0.85)',
] as const

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STATE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const rootEl            = ref<HTMLElement | null>(null)
const isFullscreen      = ref(false)
const activePeriod      = ref<PeriodKey>('month')
const compareOn         = ref(true)
const activeChartTab    = ref<ChartTab>('timeline')
const activeTopTab      = ref<TopTab>('staff')
const showReportDrawer  = ref(false)
const showReportSidebar = ref(true)
const exportingKey      = ref<string | null>(null)
const refreshing        = ref(false)
const hoveredKpi        = ref<string | null>(null)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  COMPUTED
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const topTabsLabeled = computed(() =>
  TOP_TABS.map((t) => ({
    ...t,
    label:
      t.key === 'staff'   ? vc.value.topStaffTitle :
      t.key === 'items'   ? vc.value.topItemsTitle :
      vc.value.topClientsTitle,
  }))
)

const totalRevenue = computed(() =>
  props.kpis.find((k) => k.key === 'revenue')?.value ?? 0
)

const heatmapMax = computed(() => {
  if (props.heatmap.length === 0) return 1
  return Math.max(...props.heatmap.map((c) => c.value), 1)
})

function heatmapIntensity(val: number): string {
  const pct = val / heatmapMax.value
  if (pct === 0)    return 'bg-zinc-800/40'
  if (pct < 0.2)    return 'bg-emerald-900/50'
  if (pct < 0.4)    return 'bg-emerald-700/50'
  if (pct < 0.6)    return 'bg-emerald-500/50'
  if (pct < 0.8)    return 'bg-emerald-400/60'
  return 'bg-emerald-300/70'
}

function heatmapValue(day: number, hour: number): number {
  return props.heatmap.find((c) => c.day === day && c.hour === hour)?.value ?? 0
}

/* Breakdown largest slice for donut hole label */
const breakdownTotal = computed(() =>
  props.breakdown.reduce((s, sl) => s + sl.value, 0)
)

/* Sparkline mini-chart path generator (SVG polyline) */
function sparklinePath(data: number[]): string {
  if (data.length < 2) return ''
  const maxVal = Math.max(...data, 1)
  const minVal = Math.min(...data, 0)
  const range  = maxVal - minVal || 1
  const w = 80
  const h = 24
  const step = w / (data.length - 1)
  return data
    .map((v, i) => `${(i * step).toFixed(1)},${(h - ((v - minVal) / range) * h).toFixed(1)}`)
    .join(' ')
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  FORMATTERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function fmtCurrency(v: number): string {
  if (v >= 1_000_000) return (v / 1_000_000).toFixed(1).replace(/\.0$/, '') + ' млн ₽'
  if (v >= 1_000)     return (v / 1_000).toFixed(1).replace(/\.0$/, '') + ' тыс. ₽'
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency', currency: 'RUB', maximumFractionDigits: 0,
  }).format(v)
}

function fmtNumber(v: number): string {
  if (v >= 1_000_000) return (v / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M'
  if (v >= 1_000)     return (v / 1_000).toFixed(1).replace(/\.0$/, '') + 'K'
  return new Intl.NumberFormat('ru-RU').format(v)
}

function fmtPercent(v: number): string {
  return (v >= 0 ? '+' : '') + v.toFixed(1) + '%'
}

function fmtDelta(d: number): string {
  return (d >= 0 ? '+' : '') + d.toFixed(1) + '%'
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  ACTIONS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function setPeriod(p: PeriodKey) {
  activePeriod.value = p
  emit('period-change', p)
}

function toggleCompare() {
  compareOn.value = !compareOn.value
  emit('compare-toggle', compareOn.value)
}

function doRefresh() {
  refreshing.value = true
  emit('refresh')
  setTimeout(() => { refreshing.value = false }, 1200)
}

function doExport(fmt: ExportFmt, key: string) {
  exportingKey.value = key
  emit('export-report', fmt, key)
  setTimeout(() => { exportingKey.value = null }, 2000)
}

function toggleFullscreen() {
  if (!rootEl.value) return
  if (!isFullscreen.value) rootEl.value.requestFullscreen?.()
  else document.exitFullscreen?.()
}

function handleFullscreenChange() {
  isFullscreen.value = !!document.fullscreenElement
}

function checkViewport() {
  showReportSidebar.value = window.innerWidth >= 1280
}

/* Timeline bar height (max bar = 100%) */
function barHeight(val: number): string {
  const max = Math.max(...props.timeline.map((t) => t.revenue), 1)
  return Math.max((val / max) * 100, 2).toFixed(0) + '%'
}

function barPrevHeight(val: number): string {
  const max = Math.max(...props.timeline.map((t) => Math.max(t.revenue, t.prevRevenue ?? 0)), 1)
  return Math.max((val / max) * 100, 2).toFixed(0) + '%'
}

/* Donut chart — pure CSS conic gradient */
function donutGradient(): string {
  if (props.breakdown.length === 0) return 'conic-gradient(#27272a 0% 100%)'
  let acc = 0
  const stops: string[] = []
  props.breakdown.forEach((sl, i) => {
    const start = acc
    acc += sl.percent
    stops.push(`${DONUT_COLORS[i % DONUT_COLORS.length]} ${start}% ${acc}%`)
  })
  return `conic-gradient(${stops.join(', ')})`
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  KEYBOARD
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    if (showReportDrawer.value) { showReportDrawer.value = false; return }
    if (isFullscreen.value)     { toggleFullscreen(); return }
  }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  LIFECYCLE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

onMounted(() => {
  document.addEventListener('keydown', onKeydown)
  document.addEventListener('fullscreenchange', handleFullscreenChange)
  window.addEventListener('resize', checkViewport)
  checkViewport()
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  document.removeEventListener('fullscreenchange', handleFullscreenChange)
  window.removeEventListener('resize', checkViewport)
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  RIPPLE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function ripple(e: MouseEvent) {
  const target = e.currentTarget as HTMLElement
  const rect   = target.getBoundingClientRect()
  const d      = Math.max(rect.width, rect.height) * 2
  const el     = document.createElement('span')
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-an_0.6s_ease-out]'
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
         HEADER: Title + Period + Compare + Fullscreen
    ══════════════════════════════════════════════ -->
    <header class="sticky inset-block-start-0 z-30 bg-(--t-surface)/80 backdrop-blur-xl
                   border-b border-(--t-border)/40">
      <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 sm:px-6 py-3">

        <!-- Title row -->
        <div class="flex items-center gap-3 flex-1 min-w-0">
          <span class="text-2xl">{{ vc.icon }}</span>
          <div class="min-w-0">
            <h1 class="text-base sm:text-lg font-extrabold text-(--t-text) truncate leading-snug">
              Аналитика
            </h1>
            <p v-if="props.lastUpdated"
               class="text-[10px] text-(--t-text-3) truncate">
              Обновлено: {{ props.lastUpdated }}
            </p>
          </div>
        </div>

        <!-- Controls -->
        <div class="flex items-center gap-2 flex-wrap">

          <!-- Period selector -->
          <div class="flex items-center rounded-xl border border-(--t-border)/50 overflow-hidden">
            <button
              v-for="p in PERIOD_OPTIONS" :key="p.key"
              :class="[
                'relative overflow-hidden px-2.5 sm:px-3 py-1.5 text-[10px] sm:text-xs font-medium transition-all',
                activePeriod === p.key
                  ? 'bg-(--t-primary) text-white'
                  : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
              ]"
              @click="setPeriod(p.key)" @mousedown="ripple"
            >
              <span class="hidden sm:inline">{{ p.label }}</span>
              <span class="sm:hidden">{{ p.shortLabel }}</span>
            </button>
          </div>

          <!-- Compare toggle -->
          <button
            :class="[
              'relative overflow-hidden flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[10px] sm:text-xs font-medium transition-all border',
              compareOn
                ? 'border-(--t-primary)/40 bg-(--t-primary)/10 text-(--t-primary)'
                : 'border-(--t-border)/50 text-(--t-text-3) hover:bg-(--t-card-hover)',
            ]"
            @click="toggleCompare" @mousedown="ripple"
            title="Сравнение с предыдущим периодом"
          >
            ⚖️ <span class="hidden sm:inline">Сравнение</span>
          </button>

          <!-- Refresh -->
          <button
            class="relative overflow-hidden w-9 h-9 rounded-xl border border-(--t-border)/50
                   flex items-center justify-center text-(--t-text-3)
                   hover:bg-(--t-card-hover) active:scale-95 transition-all"
            :class="refreshing ? 'animate-spin' : ''"
            @click="doRefresh" @mousedown="ripple" title="Обновить"
          >
            🔄
          </button>

          <!-- Report drawer (mobile) -->
          <button
            class="xl:hidden relative overflow-hidden w-9 h-9 rounded-xl border border-(--t-border)/50
                   flex items-center justify-center text-(--t-text-3)
                   hover:bg-(--t-card-hover) active:scale-95 transition-all"
            @click="showReportDrawer = true" @mousedown="ripple" title="Отчёты"
          >
            📋
          </button>

          <!-- Fullscreen -->
          <button
            class="hidden sm:flex relative overflow-hidden w-9 h-9 rounded-xl border border-(--t-border)/50
                   items-center justify-center text-(--t-text-3)
                   hover:bg-(--t-card-hover) active:scale-95 transition-all"
            @click="toggleFullscreen" @mousedown="ripple"
          >
            {{ isFullscreen ? '🗗' : '⛶' }}
          </button>
        </div>
      </div>
    </header>

    <!-- ══════════════════════════════════════════════
         MAIN: CONTENT + SIDEBAR
    ══════════════════════════════════════════════ -->
    <div class="flex-1 flex gap-5 px-4 sm:px-6 py-5 max-w-screen-2xl mx-auto inline-size-full">

      <!-- ═══ CONTENT COLUMN ═══ -->
      <div class="flex-1 flex flex-col gap-5 min-w-0">

        <!-- ──────────────────────────
             KPI GRID (8 widgets)
        ────────────────────────── -->
        <section>
          <!-- Loading skeletons -->
          <div v-if="props.loading && props.kpis.length === 0"
               class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div v-for="n in 8" :key="n"
                 class="h-28 rounded-2xl bg-(--t-surface)/60 animate-pulse" />
          </div>

          <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <button
              v-for="kpi in props.kpis" :key="kpi.key"
              :class="[
                'group relative overflow-hidden rounded-2xl border p-3.5 text-start transition-all',
                'hover:shadow-lg hover:shadow-black/8 active:scale-[0.97]',
                'border-(--t-border)/40 bg-(--t-surface)/60 backdrop-blur-sm',
                'hover:border-(--t-border)/80',
              ]"
              @click="emit('kpi-click', kpi)" @mousedown="ripple"
              @mouseenter="hoveredKpi = kpi.key" @mouseleave="hoveredKpi = null"
            >
              <!-- Glow on hover -->
              <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                <div class="absolute -inset-block-start-8 -inset-inline-end-8 w-24 h-24 rounded-full blur-2xl"
                     :class="kpi.color" />
              </div>

              <div class="relative z-10 flex flex-col gap-2">
                <!-- Icon + label -->
                <div class="flex items-center gap-2">
                  <span class="text-lg">{{ kpi.icon }}</span>
                  <span class="text-[10px] sm:text-xs text-(--t-text-3) font-medium truncate">{{ kpi.label }}</span>
                </div>

                <!-- Value -->
                <p class="text-lg sm:text-xl font-extrabold text-(--t-text) leading-none">
                  {{ kpi.formatted }}
                  <span v-if="kpi.suffix" class="text-xs text-(--t-text-3) font-normal">{{ kpi.suffix }}</span>
                </p>

                <!-- Delta + Sparkline -->
                <div class="flex items-center gap-2">
                  <span
                    :class="[
                      'text-[10px] sm:text-xs font-bold px-1.5 py-0.5 rounded-md',
                      kpi.trend === 'up'   ? 'text-emerald-400 bg-emerald-500/12' :
                      kpi.trend === 'down' ? 'text-rose-400 bg-rose-500/12' :
                                             'text-zinc-400 bg-zinc-500/12',
                    ]"
                  >
                    {{ kpi.trend === 'up' ? '↑' : kpi.trend === 'down' ? '↓' : '→' }}
                    {{ fmtDelta(kpi.delta) }}
                  </span>

                  <!-- Sparkline -->
                  <svg v-if="kpi.sparkline.length >= 2"
                       class="flex-1 block-size-6 shrink-0" viewBox="0 0 80 24" fill="none" preserveAspectRatio="none">
                    <polyline
                      :points="sparklinePath(kpi.sparkline)"
                      :class="kpi.trend === 'down' ? 'stroke-rose-400' : 'stroke-emerald-400'"
                      stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </div>

                <!-- Prev value -->
                <p v-if="compareOn" class="text-[10px] text-(--t-text-3)">
                  Пред. период: {{ kpi.prevFormatted }}
                </p>
              </div>
            </button>
          </div>
        </section>

        <!-- ──────────────────────────
             CHARTS SECTION
        ────────────────────────── -->
        <section class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                        backdrop-blur-sm overflow-hidden">
          <!-- Chart tabs -->
          <div class="flex items-center gap-1 px-4 pt-3 overflow-x-auto no-scrollbar">
            <button
              v-for="tab in CHART_TABS" :key="tab.key"
              :class="[
                'relative overflow-hidden shrink-0 px-3 py-1.5 rounded-xl text-xs font-medium transition-all',
                activeChartTab === tab.key
                  ? 'bg-(--t-primary)/12 text-(--t-primary)'
                  : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
              ]"
              @click="activeChartTab = tab.key" @mousedown="ripple"
            >
              {{ tab.icon }} {{ tab.label }}
            </button>
          </div>

          <div class="p-4">

            <!-- ═ TIMELINE CHART ═ -->
            <div v-if="activeChartTab === 'timeline'" class="flex flex-col gap-3">
              <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-(--t-text)">
                  {{ vc.revenueLabel }} по дням
                </h3>
                <span class="text-[10px] text-(--t-text-3)">{{ activePeriod }}</span>
              </div>

              <!-- Loading -->
              <div v-if="props.loading"
                   class="h-52 sm:h-64 rounded-xl bg-(--t-bg)/60 animate-pulse" />

              <!-- Bar chart -->
              <div v-else-if="props.timeline.length > 0"
                   class="relative h-52 sm:h-64 flex items-end gap-0.5 sm:gap-1">
                <div
                  v-for="(pt, idx) in props.timeline" :key="idx"
                  class="flex-1 flex items-end gap-px min-w-0 relative group"
                >
                  <!-- Prev period bar (faded) -->
                  <div
                    v-if="compareOn && pt.prevRevenue != null"
                    class="flex-1 rounded-t-sm bg-zinc-600/25 transition-all duration-500"
                    :style="{ 'block-size': barPrevHeight(pt.prevRevenue) }"
                  />
                  <!-- Current bar -->
                  <div
                    class="flex-1 rounded-t-sm bg-linear-to-t from-(--t-primary)/70 to-(--t-primary)
                           transition-all duration-500 group-hover:brightness-125"
                    :style="{ 'block-size': barHeight(pt.revenue) }"
                  />

                  <!-- Tooltip on hover -->
                  <div class="absolute inset-block-end-full inset-inline-start-1/2 -translate-x-1/2
                              mb-1 px-2 py-1 rounded-lg bg-zinc-900 border border-zinc-700 text-white
                              text-[10px] whitespace-nowrap opacity-0 group-hover:opacity-100
                              pointer-events-none transition-opacity z-20 shadow-lg">
                    <p class="font-bold">{{ fmtCurrency(pt.revenue) }}</p>
                    <p class="text-zinc-400">{{ pt.label }}</p>
                    <p v-if="compareOn && pt.prevRevenue != null" class="text-zinc-500">
                      Пред.: {{ fmtCurrency(pt.prevRevenue) }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Empty -->
              <div v-else class="h-52 flex items-center justify-center text-sm text-(--t-text-3)">
                Нет данных за выбранный период
              </div>

              <!-- X-axis labels -->
              <div v-if="props.timeline.length > 0"
                   class="flex items-center gap-0.5 sm:gap-1">
                <span v-for="(pt, idx) in props.timeline" :key="idx"
                      class="flex-1 text-center text-[8px] sm:text-[10px] text-(--t-text-3) truncate min-w-0">
                  {{ pt.label }}
                </span>
              </div>
            </div>

            <!-- ═ BREAKDOWN (DONUT) ═ -->
            <div v-if="activeChartTab === 'breakdown'" class="flex flex-col gap-3">
              <h3 class="text-xs font-bold text-(--t-text)">
                Структура {{ vc.revenueLabel.toLowerCase() }}
              </h3>

              <div v-if="props.loading"
                   class="h-64 rounded-xl bg-(--t-bg)/60 animate-pulse" />

              <div v-else-if="props.breakdown.length > 0"
                   class="flex flex-col sm:flex-row items-center gap-6">
                <!-- Donut ring (CSS conic-gradient) -->
                <div class="relative shrink-0 w-44 h-44 sm:w-52 sm:h-52 rounded-full"
                     :style="{ background: donutGradient() }">
                  <div class="absolute inset-3 rounded-full bg-(--t-surface) flex flex-col items-center justify-center">
                    <p class="text-lg sm:text-xl font-extrabold text-(--t-text)">
                      {{ fmtCurrency(breakdownTotal) }}
                    </p>
                    <p class="text-[10px] text-(--t-text-3)">Всего</p>
                  </div>
                </div>

                <!-- Legend -->
                <div class="flex-1 flex flex-col gap-2 min-w-0">
                  <div v-for="(sl, idx) in props.breakdown" :key="idx"
                       class="flex items-center gap-3">
                    <span class="shrink-0 w-3 h-3 rounded-sm"
                          :style="{ background: DONUT_COLORS[idx % DONUT_COLORS.length] }" />
                    <span class="flex-1 text-xs text-(--t-text-2) truncate">{{ sl.label }}</span>
                    <span class="shrink-0 text-xs font-bold text-(--t-text)">{{ fmtCurrency(sl.value) }}</span>
                    <span class="shrink-0 text-[10px] text-(--t-text-3)">{{ sl.percent.toFixed(1) }}%</span>
                  </div>
                </div>
              </div>

              <div v-else class="h-52 flex items-center justify-center text-sm text-(--t-text-3)">
                Нет данных
              </div>
            </div>

            <!-- ═ ACQUISITION SOURCES ═ -->
            <div v-if="activeChartTab === 'sources'" class="flex flex-col gap-3">
              <h3 class="text-xs font-bold text-(--t-text)">Источники привлечения</h3>

              <div v-if="props.loading"
                   class="flex flex-col gap-3">
                <div v-for="n in 6" :key="n"
                     class="h-8 rounded-lg bg-(--t-bg)/60 animate-pulse" />
              </div>

              <div v-else-if="props.acquisition.length > 0"
                   class="flex flex-col gap-2.5">
                <div v-for="ch in props.acquisition" :key="ch.label"
                     class="group flex items-center gap-3">
                  <span class="shrink-0 text-sm w-7 text-center">{{ ch.icon }}</span>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                      <span class="text-xs text-(--t-text-2) truncate">{{ ch.label }}</span>
                      <span class="text-xs font-bold text-(--t-text)">{{ ch.percent.toFixed(1) }}%</span>
                    </div>
                    <div class="block-size-2 rounded-full bg-(--t-bg)/60 overflow-hidden">
                      <div class="block-size-full rounded-full transition-all duration-700 group-hover:brightness-125"
                           :style="{ 'inline-size': ch.percent + '%', background: ch.color }" />
                    </div>
                  </div>
                  <span class="shrink-0 text-[10px] text-(--t-text-3) w-16 text-end">
                    {{ fmtNumber(ch.value) }}
                  </span>
                </div>
              </div>

              <div v-else class="h-52 flex items-center justify-center text-sm text-(--t-text-3)">
                Нет данных
              </div>
            </div>

            <!-- ═ HEATMAP ═ -->
            <div v-if="activeChartTab === 'heatmap'" class="flex flex-col gap-3">
              <h3 class="text-xs font-bold text-(--t-text)">Тепловая карта активности</h3>

              <div v-if="props.loading"
                   class="h-52 rounded-xl bg-(--t-bg)/60 animate-pulse" />

              <div v-else class="overflow-x-auto">
                <div class="min-w-120">
                  <!-- Hour labels -->
                  <div class="flex items-center ps-10 gap-px mb-1">
                    <template v-for="h in 24" :key="h">
                      <div class="flex-1 text-center text-[8px] text-(--t-text-3)">
                        {{ (HOUR_TICKS as readonly number[]).includes(h - 1) ? `${h - 1}:00` : '' }}
                      </div>
                    </template>
                  </div>

                  <!-- Grid rows -->
                  <div v-for="(dayName, dayIdx) in DAY_NAMES" :key="dayIdx"
                       class="flex items-center gap-px mb-px">
                    <span class="shrink-0 w-10 text-[10px] text-(--t-text-3) text-end pe-2">
                      {{ dayName }}
                    </span>
                    <div v-for="hour in 24" :key="hour"
                         :class="[
                           'flex-1 aspect-square rounded-xs transition-all cursor-default group relative',
                           heatmapIntensity(heatmapValue(dayIdx, hour - 1)),
                         ]"
                    >
                      <!-- Heatmap tooltip -->
                      <div class="absolute inset-block-end-full inset-inline-start-1/2 -translate-x-1/2
                                  mb-1 px-1.5 py-0.5 rounded bg-zinc-900 border border-zinc-700
                                  text-[9px] text-white whitespace-nowrap
                                  opacity-0 group-hover:opacity-100 pointer-events-none
                                  transition-opacity z-20 shadow-lg">
                        {{ dayName }} {{ (hour - 1).toString().padStart(2, '0') }}:00 · {{ heatmapValue(dayIdx, hour - 1) }}
                      </div>
                    </div>
                  </div>

                  <!-- Legend -->
                  <div class="flex items-center justify-end gap-1 mt-2">
                    <span class="text-[9px] text-(--t-text-3)">Мало</span>
                    <div class="w-3 h-3 rounded-xs bg-zinc-800/40" />
                    <div class="w-3 h-3 rounded-xs bg-emerald-900/50" />
                    <div class="w-3 h-3 rounded-xs bg-emerald-700/50" />
                    <div class="w-3 h-3 rounded-xs bg-emerald-500/50" />
                    <div class="w-3 h-3 rounded-xs bg-emerald-400/60" />
                    <div class="w-3 h-3 rounded-xs bg-emerald-300/70" />
                    <span class="text-[9px] text-(--t-text-3)">Много</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </section>

        <!-- ──────────────────────────
             TOP TABLES
        ────────────────────────── -->
        <section class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                        backdrop-blur-sm overflow-hidden">
          <!-- Top tabs -->
          <div class="flex items-center gap-1 px-4 pt-3 overflow-x-auto no-scrollbar">
            <button
              v-for="tab in topTabsLabeled" :key="tab.key"
              :class="[
                'relative overflow-hidden shrink-0 px-3 py-1.5 rounded-xl text-xs font-medium transition-all',
                activeTopTab === tab.key
                  ? 'bg-(--t-primary)/12 text-(--t-primary)'
                  : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
              ]"
              @click="activeTopTab = tab.key" @mousedown="ripple"
            >
              {{ tab.icon }} {{ tab.label }}
            </button>
          </div>

          <div class="p-4">

            <!-- ═ TOP STAFF ═ -->
            <div v-if="activeTopTab === 'staff'">
              <div v-if="props.loading" class="flex flex-col gap-2">
                <div v-for="n in 5" :key="n" class="h-14 rounded-xl bg-(--t-bg)/60 animate-pulse" />
              </div>

              <div v-else-if="props.topStaff.length === 0"
                   class="py-12 text-center text-sm text-(--t-text-3)">
                Нет данных о {{ vc.staffLabel.toLowerCase() }}
              </div>

              <div v-else class="flex flex-col gap-1.5">
                <button
                  v-for="(s, idx) in props.topStaff" :key="s.id"
                  class="group relative overflow-hidden flex items-center gap-3 px-3 py-2.5
                         rounded-xl hover:bg-(--t-card-hover) active:scale-[0.99] transition-all"
                  @click="emit('staff-click', s)" @mousedown="ripple"
                >
                  <!-- Rank -->
                  <span :class="[
                    'shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-extrabold',
                    idx === 0 ? 'bg-amber-500/15 text-amber-400' :
                    idx === 1 ? 'bg-zinc-400/15 text-zinc-300' :
                    idx === 2 ? 'bg-orange-500/15 text-orange-400' :
                                'bg-(--t-bg)/60 text-(--t-text-3)',
                  ]">
                    {{ idx < 3 ? ['🥇', '🥈', '🥉'][idx] : idx + 1 }}
                  </span>

                  <!-- Avatar -->
                  <div class="shrink-0 w-9 h-9 rounded-full bg-(--t-bg)/80 overflow-hidden
                              flex items-center justify-center text-lg ring-1 ring-(--t-border)/30">
                    <img v-if="s.avatar" :src="s.avatar" :alt="s.name"
                         class="inline-size-full block-size-full object-cover" />
                    <span v-else>{{ s.name.charAt(0) }}</span>
                  </div>

                  <!-- Info -->
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-(--t-text) truncate">{{ s.name }}</p>
                    <p class="text-[10px] text-(--t-text-3)">{{ s.role }} · ⭐ {{ s.rating.toFixed(1) }}</p>
                  </div>

                  <!-- Revenue -->
                  <div class="shrink-0 text-end">
                    <p class="text-xs font-bold text-(--t-text)">{{ fmtCurrency(s.revenue) }}</p>
                    <span :class="[
                      'text-[10px] font-medium',
                      s.delta >= 0 ? 'text-emerald-400' : 'text-rose-400',
                    ]">
                      {{ fmtDelta(s.delta) }}
                    </span>
                  </div>

                  <!-- Orders -->
                  <span class="hidden sm:block shrink-0 text-[10px] text-(--t-text-3) w-14 text-end">
                    {{ fmtNumber(s.orders) }} {{ vc.ordersLabel.toLowerCase().slice(0, 3) }}.
                  </span>
                </button>
              </div>
            </div>

            <!-- ═ TOP ITEMS ═ -->
            <div v-if="activeTopTab === 'items'">
              <div v-if="props.loading" class="flex flex-col gap-2">
                <div v-for="n in 5" :key="n" class="h-14 rounded-xl bg-(--t-bg)/60 animate-pulse" />
              </div>

              <div v-else-if="props.topItems.length === 0"
                   class="py-12 text-center text-sm text-(--t-text-3)">
                Нет данных о {{ vc.itemsLabel.toLowerCase() }}
              </div>

              <div v-else class="flex flex-col gap-1.5">
                <button
                  v-for="(it, idx) in props.topItems" :key="it.id"
                  class="group relative overflow-hidden flex items-center gap-3 px-3 py-2.5
                         rounded-xl hover:bg-(--t-card-hover) active:scale-[0.99] transition-all"
                  @click="emit('item-click', it)" @mousedown="ripple"
                >
                  <!-- Rank -->
                  <span :class="[
                    'shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-extrabold',
                    idx === 0 ? 'bg-amber-500/15 text-amber-400' :
                    idx === 1 ? 'bg-zinc-400/15 text-zinc-300' :
                    idx === 2 ? 'bg-orange-500/15 text-orange-400' :
                                'bg-(--t-bg)/60 text-(--t-text-3)',
                  ]">
                    {{ idx < 3 ? ['🥇', '🥈', '🥉'][idx] : idx + 1 }}
                  </span>

                  <!-- Image -->
                  <div class="shrink-0 w-9 h-9 rounded-lg bg-(--t-bg)/80 overflow-hidden
                              flex items-center justify-center text-lg ring-1 ring-(--t-border)/30">
                    <img v-if="it.image" :src="it.image" :alt="it.name"
                         class="inline-size-full block-size-full object-cover" />
                    <span v-else>{{ vc.icon }}</span>
                  </div>

                  <!-- Info -->
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-(--t-text) truncate">{{ it.name }}</p>
                    <p class="text-[10px] text-(--t-text-3)">{{ it.category }}</p>
                  </div>

                  <!-- Revenue -->
                  <div class="shrink-0 text-end">
                    <p class="text-xs font-bold text-(--t-text)">{{ fmtCurrency(it.revenue) }}</p>
                    <span :class="[
                      'text-[10px] font-medium',
                      it.delta >= 0 ? 'text-emerald-400' : 'text-rose-400',
                    ]">
                      {{ fmtDelta(it.delta) }}
                    </span>
                  </div>

                  <!-- Conversion -->
                  <span class="hidden sm:block shrink-0 text-[10px] text-(--t-text-3) w-12 text-end">
                    {{ it.conversion.toFixed(1) }}%
                  </span>
                </button>
              </div>
            </div>

            <!-- ═ TOP CLIENTS ═ -->
            <div v-if="activeTopTab === 'clients'">
              <div v-if="props.loading" class="flex flex-col gap-2">
                <div v-for="n in 5" :key="n" class="h-14 rounded-xl bg-(--t-bg)/60 animate-pulse" />
              </div>

              <div v-else-if="props.topClients.length === 0"
                   class="py-12 text-center text-sm text-(--t-text-3)">
                Нет данных о клиентах
              </div>

              <div v-else class="flex flex-col gap-1.5">
                <button
                  v-for="(cl, idx) in props.topClients" :key="cl.id"
                  class="group relative overflow-hidden flex items-center gap-3 px-3 py-2.5
                         rounded-xl hover:bg-(--t-card-hover) active:scale-[0.99] transition-all"
                  @click="emit('client-click', cl)" @mousedown="ripple"
                >
                  <!-- Rank -->
                  <span :class="[
                    'shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-extrabold',
                    idx === 0 ? 'bg-amber-500/15 text-amber-400' :
                    idx === 1 ? 'bg-zinc-400/15 text-zinc-300' :
                    idx === 2 ? 'bg-orange-500/15 text-orange-400' :
                                'bg-(--t-bg)/60 text-(--t-text-3)',
                  ]">
                    {{ idx < 3 ? ['🥇', '🥈', '🥉'][idx] : idx + 1 }}
                  </span>

                  <!-- Avatar -->
                  <div class="shrink-0 w-9 h-9 rounded-full bg-(--t-bg)/80 overflow-hidden
                              flex items-center justify-center text-lg ring-1 ring-(--t-border)/30 relative">
                    <img v-if="cl.avatar" :src="cl.avatar" :alt="cl.name"
                         class="inline-size-full block-size-full object-cover" />
                    <span v-else>{{ cl.name.charAt(0) }}</span>
                    <span v-if="cl.isVip"
                          class="absolute -inset-block-start-0.5 -inset-inline-end-0.5 text-[8px]">👑</span>
                  </div>

                  <!-- Info -->
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-(--t-text) truncate">
                      {{ cl.name }}
                      <span v-if="cl.isVip" class="text-amber-400 text-[10px]">VIP</span>
                    </p>
                    <p class="text-[10px] text-(--t-text-3)">
                      {{ cl.orders }} {{ vc.ordersLabel.toLowerCase() }} · {{ cl.lastVisit }}
                    </p>
                  </div>

                  <!-- Revenue -->
                  <div class="shrink-0 text-end">
                    <p class="text-xs font-bold text-(--t-text)">{{ fmtCurrency(cl.revenue) }}</p>
                    <p class="text-[10px] text-(--t-text-3)">LTV {{ fmtCurrency(cl.ltv) }}</p>
                  </div>
                </button>
              </div>
            </div>

          </div>
        </section>
      </div>

      <!-- ═══ SIDEBAR: QUICK REPORTS ═══ -->
      <Transition name="sidebar-an">
        <aside v-if="showReportSidebar"
               class="hidden xl:flex shrink-0 flex-col gap-3 w-60">

          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-xs font-bold text-(--t-text) mb-3 flex items-center gap-2">
              📋 Отчёты
            </h3>

            <div class="flex flex-col gap-1.5">
              <button
                v-for="rep in QUICK_REPORTS" :key="rep.key"
                class="group relative overflow-hidden flex items-center gap-2.5 px-3 py-2.5
                       rounded-xl text-start hover:bg-(--t-card-hover)
                       active:scale-[0.97] transition-all"
                @click="doExport('pdf', rep.key)" @mousedown="ripple"
              >
                <span class="shrink-0 text-base">{{ rep.icon }}</span>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-medium text-(--t-text-2) group-hover:text-(--t-text) truncate transition-colors">
                    {{ rep.label }}
                  </p>
                  <p class="text-[10px] text-(--t-text-3) truncate">{{ rep.desc }}</p>
                </div>
                <span v-if="exportingKey === rep.key"
                      class="shrink-0 text-xs text-emerald-400 animate-pulse">⏳</span>
              </button>
            </div>
          </div>

          <!-- Export buttons -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-xs font-bold text-(--t-text) mb-3">Экспорт данных</h3>
            <div class="flex flex-col gap-1.5">
              <button
                class="relative overflow-hidden inline-size-full py-2 rounded-xl text-xs font-medium
                       border border-(--t-border)/50 text-(--t-text-2) hover:bg-(--t-card-hover)
                       active:scale-[0.97] transition-all flex items-center justify-center gap-2"
                @click="doExport('pdf', 'full')" @mousedown="ripple"
              >
                📄 Полный PDF-отчёт
              </button>
              <button
                class="relative overflow-hidden inline-size-full py-2 rounded-xl text-xs font-medium
                       border border-(--t-border)/50 text-(--t-text-2) hover:bg-(--t-card-hover)
                       active:scale-[0.97] transition-all flex items-center justify-center gap-2"
                @click="doExport('xlsx', 'full')" @mousedown="ripple"
              >
                📊 Скачать XLSX
              </button>
              <button
                class="relative overflow-hidden inline-size-full py-2 rounded-xl text-xs font-medium
                       border border-(--t-border)/50 text-(--t-text-2) hover:bg-(--t-card-hover)
                       active:scale-[0.97] transition-all flex items-center justify-center gap-2"
                @click="doExport('csv', 'full')" @mousedown="ripple"
              >
                📃 Скачать CSV
              </button>
            </div>
          </div>

          <!-- AI Insights (вертикаль-aware) -->
          <div class="rounded-2xl border border-violet-500/20 bg-violet-500/5
                      backdrop-blur-sm p-4">
            <h3 class="text-xs font-bold text-violet-300 mb-2 flex items-center gap-1.5">
              🤖 AI-инсайты
            </h3>
            <div class="flex flex-col gap-2 text-[10px] text-violet-200/80 leading-relaxed">
              <p>
                📈 Конверсия выросла на <span class="font-bold text-emerald-400">+12%</span>
                за последние 7 дней. Основной рост — в категории
                «{{ vc.breakdownCategories[0] }}».
              </p>
              <p>
                ⚠️ Retention клиентов снизился на <span class="font-bold text-amber-400">−3%</span>.
                Рекомендуем запустить email-кампанию для сегмента «Спящие клиенты».
              </p>
              <p>
                💡 Наибольший потенциал роста: канал
                «{{ vc.acquisitionChannels[1]?.label ?? 'Соцсети' }}»
                — <span class="font-bold text-sky-400">+25% прогнозируемый ROI</span>.
              </p>
            </div>
          </div>
        </aside>
      </Transition>
    </div>

    <!-- ══════════════════════════════════════════════
         REPORT DRAWER (mobile / tablet)
    ══════════════════════════════════════════════ -->
    <Transition name="drawer-an">
      <div v-if="showReportDrawer"
           class="fixed inset-0 z-50 flex justify-end"
           @click.self="showReportDrawer = false">
        <div class="absolute inset-0 bg-black/40" @click="showReportDrawer = false" />

        <div class="relative z-10 inline-size-72 max-w-[85vw] bg-(--t-surface)
                    border-s border-(--t-border) h-full overflow-y-auto p-5 flex flex-col gap-4">

          <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-(--t-text)">📋 Отчёты</h3>
            <button class="text-(--t-text-3) hover:text-(--t-text) transition-colors"
                    @click="showReportDrawer = false">✕</button>
          </div>

          <!-- Reports list -->
          <div class="flex flex-col gap-1.5">
            <button
              v-for="rep in QUICK_REPORTS" :key="rep.key"
              class="group relative overflow-hidden flex items-center gap-2.5 px-3 py-3
                     rounded-xl text-start hover:bg-(--t-card-hover)
                     active:scale-[0.97] transition-all"
              @click="doExport('pdf', rep.key)" @mousedown="ripple"
            >
              <span class="shrink-0 text-base">{{ rep.icon }}</span>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-(--t-text-2) truncate">{{ rep.label }}</p>
                <p class="text-[10px] text-(--t-text-3) truncate">{{ rep.desc }}</p>
              </div>
              <span v-if="exportingKey === rep.key"
                    class="shrink-0 text-emerald-400 animate-pulse">⏳</span>
            </button>
          </div>

          <div class="border-t border-(--t-border)/30 pt-3">
            <p class="text-xs font-bold text-(--t-text) mb-2">Экспорт</p>
            <div class="flex flex-col gap-1.5">
              <button
                class="relative overflow-hidden inline-size-full py-2.5 rounded-xl text-xs font-medium
                       border border-(--t-border)/50 text-(--t-text-2) hover:bg-(--t-card-hover)
                       active:scale-[0.97] transition-all"
                @click="doExport('pdf', 'full'); showReportDrawer = false" @mousedown="ripple"
              >
                📄 PDF-отчёт
              </button>
              <button
                class="relative overflow-hidden inline-size-full py-2.5 rounded-xl text-xs font-medium
                       border border-(--t-border)/50 text-(--t-text-2) hover:bg-(--t-card-hover)
                       active:scale-[0.97] transition-all"
                @click="doExport('xlsx', 'full'); showReportDrawer = false" @mousedown="ripple"
              >
                📊 XLSX
              </button>
              <button
                class="relative overflow-hidden inline-size-full py-2.5 rounded-xl text-xs font-medium
                       border border-(--t-border)/50 text-(--t-text-2) hover:bg-(--t-card-hover)
                       active:scale-[0.97] transition-all"
                @click="doExport('csv', 'full'); showReportDrawer = false" @mousedown="ripple"
              >
                📃 CSV
              </button>
            </div>
          </div>

          <!-- AI Insights (mobile) -->
          <div class="rounded-2xl border border-violet-500/20 bg-violet-500/5 p-4 mt-auto">
            <h3 class="text-xs font-bold text-violet-300 mb-2">🤖 AI-инсайты</h3>
            <div class="flex flex-col gap-2 text-[10px] text-violet-200/80 leading-relaxed">
              <p>
                📈 Конверсия <span class="font-bold text-emerald-400">+12%</span>
                за 7 дней в «{{ vc.breakdownCategories[0] }}».
              </p>
              <p>
                ⚠️ Retention <span class="font-bold text-amber-400">−3%</span>.
                Запустите реактивацию.
              </p>
            </div>
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
/* Ripple — unique suffix an (Analytics) */
@keyframes ripple-an {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* No-scrollbar for horizontal tabs */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Sidebar */
.sidebar-an-enter-active,
.sidebar-an-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.sidebar-an-enter-from,
.sidebar-an-leave-to {
  opacity: 0;
  transform: translateX(16px);
}

/* Drawer */
.drawer-an-enter-active,
.drawer-an-leave-active {
  transition: opacity 0.3s ease;
}
.drawer-an-enter-active > :last-child,
.drawer-an-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.drawer-an-enter-from,
.drawer-an-leave-to {
  opacity: 0;
}
.drawer-an-enter-from > :last-child,
.drawer-an-leave-to > :last-child {
  transform: translateX(100%);
}
</style>
