<script setup lang="ts">
/**
 * TenantReports.vue — главная страница отчётов B2B Tenant Dashboard
 *
 * Универсальный модуль отчётности для всех 127 вертикалей CatVRF:
 *   Beauty  (салоны · мастера)       · Taxi   (тарифы · водители)
 *   Food    (рестораны · доставка)    · Hotels (номера · бронь)
 *   RealEstate (объекты · сделки)    · Flowers (букеты · доставка)
 *   Fashion (одежда · обувь)         · Furniture (мебель · декор)
 *   Fitness (абонементы · тренеры)   · Travel (туры · билеты)
 *   default (универсальный)
 *
 * ───────────────────────────────────────────────────────────────
 *  Функционал:
 *   1.  Period selector (day / week / month / quarter / year / custom)
 *   2.  Готовые шаблоны отчётов (revenue, clients, staff, marketing…)
 *   3.  Конструктор кастомных отчётов
 *   4.  Таблица всех сохранённых / сгенерированных отчётов
 *   5.  Sidebar с категориями + быстрые фильтры
 *   6.  Экспорт: PDF / XLSX / CSV
 *   7.  Сравнение периодов (год к году, месяц к месяцу)
 *   8.  Предпросмотр (preview modal)
 *   9.  Full-screen + keyboard (Esc, ↑/↓)
 *  10.  Ripple-rp
 * ───────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useAuth, useTenant } from '@/stores'

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  TYPES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

type PeriodKey       = 'day' | 'week' | 'month' | 'quarter' | 'year' | 'custom'
type ReportCategory  = 'all' | 'revenue' | 'clients' | 'staff' | 'marketing' | 'inventory' | 'delivery' | 'ai' | 'custom'
type ReportStatus    = 'ready' | 'generating' | 'scheduled' | 'error'
type ExportFmt       = 'pdf' | 'xlsx' | 'csv'
type SortField       = 'name' | 'created' | 'period' | 'status'
type SortDir         = 'asc' | 'desc'
type ViewMode        = 'grid' | 'list'

interface TemplateReport {
  key:         string
  category:    ReportCategory
  icon:        string
  title:       string
  description: string
  metrics:     string[]
  popular:     boolean
  isNew?:      boolean
}

interface SavedReport {
  id:           number | string
  name:         string
  category:     ReportCategory
  period:       string
  periodLabel:  string
  status:       ReportStatus
  format:       ExportFmt
  createdAt:    string
  fileSize?:    string
  downloadUrl?: string
  previewData?: ReportPreviewData
  isScheduled?: boolean
  scheduleFreq?: string
  tags?:        string[]
}

interface ReportPreviewData {
  title:    string
  summary:  string
  kpis:     Array<{ label: string; value: string; delta?: string; trend?: 'up' | 'down' | 'flat' }>
  rows?:    Array<Record<string, string | number>>
  columns?: string[]
}

interface ReportCategoryCfg {
  key:   ReportCategory
  label: string
  icon:  string
  count: number
}

interface VerticalReportsConfig {
  label:      string
  icon:       string
  templates:  TemplateReport[]
  categories: ReportCategoryCfg[]
}

interface CustomReportField {
  key:      string
  label:    string
  checked:  boolean
  group:    string
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  PROPS & EMITS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const props = withDefaults(defineProps<{
  vertical?:      string
  savedReports?:  SavedReport[]
  loading?:       boolean
  lastUpdated?:   string
  totalReports?:  number
  storageUsed?:   string
}>(), {
  vertical:     'default',
  savedReports: () => [],
  loading:      false,
  lastUpdated:  '',
  totalReports: 0,
  storageUsed:  '0 MB',
})

const emit = defineEmits<{
  'period-change':     [period: PeriodKey, dateRange?: { from: string; to: string }]
  'generate-report':   [templateKey: string, period: PeriodKey, format: ExportFmt]
  'create-custom':     [fields: string[], period: PeriodKey, format: ExportFmt, name: string]
  'download-report':   [report: SavedReport]
  'delete-report':     [report: SavedReport]
  'preview-report':    [report: SavedReport]
  'schedule-report':   [templateKey: string, frequency: string]
  'export-all':        [format: ExportFmt]
  'refresh':           []
  'toggle-fullscreen': []
}>()

const auth = useAuth()
const biz  = useTenant()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  VERTICAL CONFIG: 11 вертикалей
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function buildTemplates(overrides: Partial<Record<string, Partial<TemplateReport>>>): TemplateReport[] {
  const base: TemplateReport[] = [
    { key: 'revenue',      category: 'revenue',    icon: '💰', title: 'Выручка',              description: 'Полный отчёт по выручке, среднему чеку и динамике',        metrics: ['GMV', 'Средний чек', 'Динамика'],   popular: true },
    { key: 'clients',      category: 'clients',    icon: '👥', title: 'Клиенты',              description: 'Новые, вернувшиеся, churn, когортный анализ',             metrics: ['Новые', 'Retention', 'Churn'],       popular: true },
    { key: 'staff-perf',   category: 'staff',      icon: '👤', title: 'Персонал',             description: 'KPI сотрудников, производительность, рейтинги',           metrics: ['Выработка', 'Рейтинг', 'KPI'],      popular: true },
    { key: 'marketing',    category: 'marketing',  icon: '📢', title: 'Маркетинг',            description: 'ROI каналов, CAC, LTV/CAC, конверсия воронки',            metrics: ['ROI', 'CAC', 'LTV/CAC'],            popular: true },
    { key: 'abc',          category: 'revenue',    icon: '📊', title: 'ABC-анализ',           description: 'Классификация товаров/услуг по вкладу в выручку',         metrics: ['A-группа', 'B-группа', 'C-группа'], popular: false },
    { key: 'inventory',    category: 'inventory',  icon: '📦', title: 'Инвентарь',            description: 'Остатки, оборачиваемость, low-stock прогноз',             metrics: ['Остатки', 'Оборот', 'Дефицит'],     popular: false },
    { key: 'delivery',     category: 'delivery',   icon: '🚚', title: 'Доставка',             description: 'Среднее время, стоимость, качество доставки',             metrics: ['Время', 'Стоимость', 'SLA'],        popular: false },
    { key: 'ai-usage',     category: 'ai',         icon: '🤖', title: 'AI-конструкторы',      description: 'Использование AI, конверсия рекомендаций, ROI',           metrics: ['Запросы', 'Конверсия', 'ROI'],       popular: false, isNew: true },
    { key: 'forecast',     category: 'revenue',    icon: '🔮', title: 'Прогноз',              description: 'AI-прогноз спроса, выручки и загрузки на 30 дней',        metrics: ['Прогноз', 'Точность', 'Тренд'],     popular: false, isNew: true },
    { key: 'comparison',   category: 'revenue',    icon: '⚖️', title: 'Сравнение периодов',   description: 'Год к году, месяц к месяцу, любые два периода',           metrics: ['Δ Выручка', 'Δ Клиенты', 'Δ Чек'], popular: true },
    { key: 'cohort',       category: 'clients',    icon: '🧬', title: 'Когортный анализ',     description: 'Retention по когортам, LTV по месяцам привлечения',       metrics: ['Когорты', 'Retention', 'LTV'],      popular: false },
    { key: 'funnel',       category: 'marketing',  icon: '🔻', title: 'Воронка продаж',       description: 'Этапы воронки, конверсия между шагами, bottlenecks',      metrics: ['Этапы', 'Конверсия', 'Drop-off'],   popular: false },
  ]
  return base.map((t) => {
    const ov = overrides[t.key]
    return ov ? { ...t, ...ov } : t
  })
}

function buildCategories(templates: TemplateReport[]): ReportCategoryCfg[] {
  const cats: ReportCategoryCfg[] = [
    { key: 'all',       label: 'Все отчёты',  icon: '📋', count: templates.length },
    { key: 'revenue',   label: 'Выручка',     icon: '💰', count: 0 },
    { key: 'clients',   label: 'Клиенты',     icon: '👥', count: 0 },
    { key: 'staff',     label: 'Персонал',    icon: '👤', count: 0 },
    { key: 'marketing', label: 'Маркетинг',   icon: '📢', count: 0 },
    { key: 'inventory', label: 'Инвентарь',   icon: '📦', count: 0 },
    { key: 'delivery',  label: 'Доставка',    icon: '🚚', count: 0 },
    { key: 'ai',        label: 'AI',          icon: '🤖', count: 0 },
    { key: 'custom',    label: 'Кастомные',   icon: '⚙️', count: 0 },
  ]
  for (const t of templates) {
    const c = cats.find((cc) => cc.key === t.category)
    if (c) c.count++
  }
  return cats.filter((c) => c.key === 'all' || c.key === 'custom' || c.count > 0)
}

const VERTICAL_CFG: Record<string, VerticalReportsConfig> = (() => {
  const map: Record<string, VerticalReportsConfig> = {}

  /* beauty */
  const beautyTpl = buildTemplates({
    'staff-perf': { title: 'Мастера', description: 'KPI мастеров, загрузка, рейтинг, возвраты' },
    'inventory':  { title: 'Косметика и расходники', description: 'Остатки материалов, оборачиваемость' },
  })
  map.beauty = { label: 'Салоны красоты', icon: '💄', templates: beautyTpl, categories: buildCategories(beautyTpl) }

  /* taxi */
  const taxiTpl = buildTemplates({
    'staff-perf': { title: 'Водители', description: 'KPI водителей, рейтинг, средний чек поездки' },
    'inventory':  { title: 'Автопарк', description: 'Состояние машин, ТО, пробег, расход' },
    'delivery':   { title: 'Поездки', description: 'Среднее время, расстояние, подача' },
  })
  map.taxi = { label: 'Такси', icon: '🚕', templates: taxiTpl, categories: buildCategories(taxiTpl) }

  /* food */
  const foodTpl = buildTemplates({
    'staff-perf': { title: 'Повара и курьеры', description: 'Производительность кухни, скорость доставки' },
    'inventory':  { title: 'Продукты и ингредиенты', description: 'Остатки, списания, сроки годности' },
  })
  map.food = { label: 'Еда и рестораны', icon: '🍽️', templates: foodTpl, categories: buildCategories(foodTpl) }

  /* hotel */
  const hotelTpl = buildTemplates({
    'staff-perf': { title: 'Персонал отеля', description: 'Загрузка, отзывы гостей, KPI' },
    'inventory':  { title: 'Номерной фонд', description: 'Загрузка номеров, RevPAR, ADR' },
    'delivery':   { title: 'Сервис и обслуживание', description: 'Время реакции, качество сервиса' },
  })
  map.hotel = { label: 'Отели', icon: '🏨', templates: hotelTpl, categories: buildCategories(hotelTpl) }

  /* realEstate */
  const reTpl = buildTemplates({
    'staff-perf': { title: 'Агенты', description: 'Конверсия показов, KPI, средняя сделка' },
    'inventory':  { title: 'Объекты', description: 'Активные листинги, время экспозиции' },
    'delivery':   { title: 'Показы и сделки', description: 'Воронка: запросы → показы → сделки' },
  })
  map.realEstate = { label: 'Недвижимость', icon: '🏢', templates: reTpl, categories: buildCategories(reTpl) }

  /* flowers */
  const flTpl = buildTemplates({
    'staff-perf': { title: 'Флористы', description: 'Скорость сборки, качество, рейтинг' },
    'inventory':  { title: 'Цветы и материалы', description: 'Свежесть, остатки, списания' },
  })
  map.flowers = { label: 'Цветы', icon: '💐', templates: flTpl, categories: buildCategories(flTpl) }

  /* fashion */
  const faTpl = buildTemplates({
    'staff-perf': { title: 'Стилисты', description: 'Конверсия подбора, отзывы, KPI' },
    'inventory':  { title: 'Склад одежды', description: 'Размерная сетка, остатки, возвраты' },
  })
  map.fashion = { label: 'Одежда и обувь', icon: '👗', templates: faTpl, categories: buildCategories(faTpl) }

  /* furniture */
  const fuTpl = buildTemplates({
    'staff-perf': { title: 'Дизайнеры', description: 'Конверсия проектов, средний чек, рейтинг' },
    'inventory':  { title: 'Мебель на складе', description: 'Остатки, оборачиваемость, дефицит' },
    'delivery':   { title: 'Доставка и сборка', description: 'Время, рекламации, возвраты' },
  })
  map.furniture = { label: 'Мебель', icon: '🛋️', templates: fuTpl, categories: buildCategories(fuTpl) }

  /* fitness */
  const fitTpl = buildTemplates({
    'staff-perf': { title: 'Тренеры', description: 'Загрузка, retention клиентов, рейтинг' },
    'inventory':  { title: 'Абонементы и товары', description: 'Активные, замороженные, доп. продажи' },
  })
  map.fitness = { label: 'Фитнес', icon: '💪', templates: fitTpl, categories: buildCategories(fitTpl) }

  /* travel */
  const trTpl = buildTemplates({
    'staff-perf': { title: 'Менеджеры', description: 'Конверсия бронирований, KPI, отзывы' },
    'inventory':  { title: 'Туры и билеты', description: 'Доступность, загрузка, оценки' },
  })
  map.travel = { label: 'Путешествия', icon: '✈️', templates: trTpl, categories: buildCategories(trTpl) }

  /* default */
  const defTpl = buildTemplates({})
  map.default = { label: 'Бизнес', icon: '📊', templates: defTpl, categories: buildCategories(defTpl) }

  return map
})()

const vc = computed<VerticalReportsConfig>(() =>
  VERTICAL_CFG[props.vertical] ?? VERTICAL_CFG.default
)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  CONSTANTS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const PERIOD_OPTIONS: Array<{ key: PeriodKey; label: string; short: string }> = [
  { key: 'day',     label: 'День',      short: 'Д' },
  { key: 'week',    label: 'Неделя',    short: 'Н' },
  { key: 'month',   label: 'Месяц',     short: 'М' },
  { key: 'quarter', label: 'Квартал',   short: 'Кв' },
  { key: 'year',    label: 'Год',       short: 'Г' },
  { key: 'custom',  label: 'Произвольный', short: '…' },
]

const FORMAT_OPTIONS: Array<{ key: ExportFmt; label: string; icon: string }> = [
  { key: 'pdf',  label: 'PDF',  icon: '📄' },
  { key: 'xlsx', label: 'XLSX', icon: '📊' },
  { key: 'csv',  label: 'CSV',  icon: '📃' },
]

const STATUS_MAP: Record<ReportStatus, { label: string; dot: string; bg: string }> = {
  ready:      { label: 'Готов',       dot: 'bg-emerald-400', bg: 'bg-emerald-500/10 text-emerald-400' },
  generating: { label: 'Генерация…',  dot: 'bg-amber-400',   bg: 'bg-amber-500/10 text-amber-400' },
  scheduled:  { label: 'По расписанию', dot: 'bg-sky-400',     bg: 'bg-sky-500/10 text-sky-400' },
  error:      { label: 'Ошибка',      dot: 'bg-rose-400',    bg: 'bg-rose-500/10 text-rose-400' },
}

const CUSTOM_FIELD_GROUPS: Array<{ group: string; fields: Array<{ key: string; label: string }> }> = [
  { group: 'Финансы',   fields: [
    { key: 'revenue',   label: 'Выручка' },
    { key: 'avgCheck',  label: 'Средний чек' },
    { key: 'margin',    label: 'Маржинальность' },
    { key: 'refunds',   label: 'Возвраты' },
    { key: 'commission', label: 'Комиссии' },
  ]},
  { group: 'Клиенты',   fields: [
    { key: 'newClients',   label: 'Новые клиенты' },
    { key: 'returnClients', label: 'Вернувшиеся' },
    { key: 'churn',        label: 'Churn rate' },
    { key: 'ltv',          label: 'LTV' },
    { key: 'nps',          label: 'NPS' },
  ]},
  { group: 'Персонал',  fields: [
    { key: 'staffCount',    label: 'Кол-во сотрудников' },
    { key: 'staffRevenue',  label: 'Выработка' },
    { key: 'staffRating',   label: 'Рейтинг' },
    { key: 'staffUtilization', label: 'Загрузка' },
  ]},
  { group: 'Маркетинг', fields: [
    { key: 'cac',       label: 'CAC' },
    { key: 'roi',       label: 'ROI' },
    { key: 'ltvCac',    label: 'LTV/CAC' },
    { key: 'channels',  label: 'Каналы привлечения' },
    { key: 'conversion', label: 'Конверсия воронки' },
  ]},
]

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STATE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const rootEl              = ref<HTMLElement | null>(null)
const isFullscreen        = ref(false)
const activePeriod        = ref<PeriodKey>('month')
const activeCategory      = ref<ReportCategory>('all')
const searchQuery         = ref('')
const sortField           = ref<SortField>('created')
const sortDir             = ref<SortDir>('desc')
const viewMode            = ref<ViewMode>('grid')
const showSidebar         = ref(true)
const showMobileSidebar   = ref(false)
const showCreateModal     = ref(false)
const showPreviewModal    = ref(false)
const previewReport       = ref<SavedReport | null>(null)
const generatingTemplate  = ref<string | null>(null)
const selectedFmt         = ref<ExportFmt>('pdf')
const refreshing          = ref(false)
const selectedReports     = ref<Set<number | string>>(new Set())
const selectAll           = ref(false)

/* Custom report builder */
const customName          = ref('')
const customFields        = ref<CustomReportField[]>(
  CUSTOM_FIELD_GROUPS.flatMap((g) =>
    g.fields.map((f) => ({ key: f.key, label: f.label, checked: false, group: g.group }))
  )
)
const customFmt           = ref<ExportFmt>('pdf')

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  COMPUTED
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const filteredTemplates = computed(() => {
  let list = vc.value.templates
  if (activeCategory.value !== 'all') {
    list = list.filter((t) => t.category === activeCategory.value)
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter((t) =>
      t.title.toLowerCase().includes(q) || t.description.toLowerCase().includes(q)
    )
  }
  return list
})

const filteredSavedReports = computed(() => {
  let list = [...props.savedReports]
  if (activeCategory.value !== 'all') {
    list = list.filter((r) => r.category === activeCategory.value)
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter((r) =>
      r.name.toLowerCase().includes(q) || r.periodLabel.toLowerCase().includes(q)
    )
  }
  list.sort((a, b) => {
    const mul = sortDir.value === 'asc' ? 1 : -1
    if (sortField.value === 'name')    return mul * a.name.localeCompare(b.name)
    if (sortField.value === 'period')  return mul * a.period.localeCompare(b.period)
    if (sortField.value === 'status')  return mul * a.status.localeCompare(b.status)
    return mul * (new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime())
  })
  return list
})

const categoryCounts = computed(() => {
  const map: Record<string, number> = {}
  for (const c of vc.value.categories) {
    map[c.key] = c.key === 'all'
      ? vc.value.templates.length
      : vc.value.templates.filter((t) => t.category === c.key).length
  }
  return map
})

const customSelectedCount = computed(() => customFields.value.filter((f) => f.checked).length)
const hasSelection        = computed(() => selectedReports.value.size > 0)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  FORMATTERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function fmtDate(d: string): string {
  if (!d) return '—'
  const dt = new Date(d)
  return dt.toLocaleDateString('ru-RU', { day: '2-digit', month: 'short', year: 'numeric' })
}

function fmtTime(d: string): string {
  if (!d) return ''
  return new Date(d).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  ACTIONS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function setPeriod(p: PeriodKey) {
  activePeriod.value = p
  emit('period-change', p)
}

function setCategory(c: ReportCategory) {
  activeCategory.value = c
  showMobileSidebar.value = false
}

function toggleSort(field: SortField) {
  if (sortField.value === field) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDir.value = 'desc'
  }
}

function generateReport(tpl: TemplateReport) {
  generatingTemplate.value = tpl.key
  emit('generate-report', tpl.key, activePeriod.value, selectedFmt.value)
  setTimeout(() => { generatingTemplate.value = null }, 3000)
}

function downloadReport(r: SavedReport) {
  emit('download-report', r)
}

function deleteReport(r: SavedReport) {
  emit('delete-report', r)
}

function openPreview(r: SavedReport) {
  previewReport.value = r
  showPreviewModal.value = true
  emit('preview-report', r)
}

function closePreview() {
  showPreviewModal.value = false
  previewReport.value = null
}

function submitCustomReport() {
  const fields = customFields.value.filter((f) => f.checked).map((f) => f.key)
  if (fields.length === 0 || !customName.value.trim()) return
  emit('create-custom', fields, activePeriod.value, customFmt.value, customName.value.trim())
  showCreateModal.value = false
  customName.value = ''
  customFields.value.forEach((f) => { f.checked = false })
}

function toggleSelectReport(id: number | string) {
  const s = new Set(selectedReports.value)
  if (s.has(id)) s.delete(id); else s.add(id)
  selectedReports.value = s
}

function toggleSelectAll() {
  if (selectAll.value) {
    selectedReports.value = new Set()
    selectAll.value = false
  } else {
    selectedReports.value = new Set(filteredSavedReports.value.map((r) => r.id))
    selectAll.value = true
  }
}

function bulkExport(fmt: ExportFmt) {
  emit('export-all', fmt)
}

function doRefresh() {
  refreshing.value = true
  emit('refresh')
  setTimeout(() => { refreshing.value = false }, 1200)
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
  showSidebar.value = window.innerWidth >= 1280
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  KEYBOARD
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    if (showPreviewModal.value)  { closePreview(); return }
    if (showCreateModal.value)   { showCreateModal.value = false; return }
    if (showMobileSidebar.value) { showMobileSidebar.value = false; return }
    if (isFullscreen.value)      { toggleFullscreen(); return }
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
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-rp_0.6s_ease-out]'
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
         HEADER
    ══════════════════════════════════════════════ -->
    <header class="sticky inset-block-start-0 z-30 bg-(--t-surface)/80 backdrop-blur-xl
                   border-b border-(--t-border)/40">
      <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 sm:px-6 py-3">

        <!-- Title -->
        <div class="flex items-center gap-3 flex-1 min-w-0">
          <span class="text-2xl">{{ vc.icon }}</span>
          <div class="min-w-0">
            <h1 class="text-base sm:text-lg font-extrabold text-(--t-text) truncate leading-snug">
              Отчёты
            </h1>
            <p class="text-[10px] text-(--t-text-3) truncate">
              {{ props.totalReports }} отчётов · {{ props.storageUsed }}
              <span v-if="props.lastUpdated"> · {{ props.lastUpdated }}</span>
            </p>
          </div>
        </div>

        <!-- Controls row -->
        <div class="flex items-center gap-2 flex-wrap">

          <!-- Period selector -->
          <div class="flex items-center rounded-xl border border-(--t-border)/50 overflow-hidden">
            <button
              v-for="p in PERIOD_OPTIONS" :key="p.key"
              :class="[
                'relative overflow-hidden px-2 sm:px-3 py-1.5 text-[10px] sm:text-xs font-medium transition-all',
                activePeriod === p.key
                  ? 'bg-(--t-primary) text-white'
                  : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
              ]"
              @click="setPeriod(p.key)" @mousedown="ripple"
            >
              <span class="hidden sm:inline">{{ p.label }}</span>
              <span class="sm:hidden">{{ p.short }}</span>
            </button>
          </div>

          <!-- Format selector -->
          <div class="flex items-center gap-0.5 rounded-xl border border-(--t-border)/50 overflow-hidden">
            <button
              v-for="f in FORMAT_OPTIONS" :key="f.key"
              :class="[
                'relative overflow-hidden px-2 py-1.5 text-[10px] sm:text-xs font-medium transition-all',
                selectedFmt === f.key
                  ? 'bg-(--t-primary)/15 text-(--t-primary)'
                  : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
              ]"
              @click="selectedFmt = f.key" @mousedown="ripple"
              :title="f.label"
            >
              {{ f.icon }}
            </button>
          </div>

          <!-- Create custom report -->
          <button
            class="relative overflow-hidden flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold
                   bg-(--t-primary) text-white hover:brightness-110 active:scale-95 transition-all"
            @click="showCreateModal = true" @mousedown="ripple"
          >
            ＋ <span class="hidden sm:inline">Создать отчёт</span>
          </button>

          <!-- Refresh -->
          <button
            class="relative overflow-hidden w-9 h-9 rounded-xl border border-(--t-border)/50
                   flex items-center justify-center text-(--t-text-3)
                   hover:bg-(--t-card-hover) active:scale-95 transition-all"
            :class="refreshing ? 'animate-spin' : ''"
            @click="doRefresh" @mousedown="ripple" title="Обновить"
          >🔄</button>

          <!-- Sidebar toggle (mobile) -->
          <button
            class="xl:hidden relative overflow-hidden w-9 h-9 rounded-xl border border-(--t-border)/50
                   flex items-center justify-center text-(--t-text-3)
                   hover:bg-(--t-card-hover) active:scale-95 transition-all"
            @click="showMobileSidebar = true" @mousedown="ripple"
          >☰</button>

          <!-- Fullscreen -->
          <button
            class="hidden sm:flex relative overflow-hidden w-9 h-9 rounded-xl border border-(--t-border)/50
                   items-center justify-center text-(--t-text-3)
                   hover:bg-(--t-card-hover) active:scale-95 transition-all"
            @click="toggleFullscreen" @mousedown="ripple"
          >{{ isFullscreen ? '🗗' : '⛶' }}</button>
        </div>
      </div>

      <!-- Search bar -->
      <div class="px-4 sm:px-6 pb-3">
        <div class="relative">
          <span class="absolute inset-block-start-1/2 -translate-y-1/2 inset-inline-start-3 text-sm text-(--t-text-3) pointer-events-none">🔍</span>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Поиск по отчётам…"
            class="inline-size-full py-2 ps-9 pe-4 rounded-xl border border-(--t-border)/50
                   bg-(--t-bg)/60 text-xs text-(--t-text) placeholder:text-(--t-text-3)
                   focus:outline-none focus:border-(--t-primary)/50 transition-colors"
          />
        </div>
      </div>
    </header>

    <!-- ══════════════════════════════════════════════
         MAIN: SIDEBAR + CONTENT
    ══════════════════════════════════════════════ -->
    <div class="flex-1 flex gap-5 px-4 sm:px-6 py-5 max-w-screen-2xl mx-auto inline-size-full">

      <!-- ═══ SIDEBAR (desktop) ═══ -->
      <Transition name="sb-rp">
        <aside v-if="showSidebar"
               class="hidden xl:flex shrink-0 flex-col gap-3 w-56">

          <!-- Categories -->
          <nav class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-3">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-2 px-2">
              Категории
            </h3>
            <div class="flex flex-col gap-0.5">
              <button
                v-for="cat in vc.categories" :key="cat.key"
                :class="[
                  'relative overflow-hidden flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs transition-all',
                  activeCategory === cat.key
                    ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold'
                    : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
                ]"
                @click="setCategory(cat.key)" @mousedown="ripple"
              >
                <span class="shrink-0 text-sm">{{ cat.icon }}</span>
                <span class="flex-1 text-start truncate">{{ cat.label }}</span>
                <span class="shrink-0 text-[10px] tabular-nums opacity-60">{{ categoryCounts[cat.key] ?? 0 }}</span>
              </button>
            </div>
          </nav>

          <!-- Quick stats -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              Статистика
            </h3>
            <div class="flex flex-col gap-2.5">
              <div class="flex items-center justify-between">
                <span class="text-[10px] text-(--t-text-3)">Всего отчётов</span>
                <span class="text-xs font-bold text-(--t-text)">{{ props.totalReports }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-[10px] text-(--t-text-3)">Хранилище</span>
                <span class="text-xs font-bold text-(--t-text)">{{ props.storageUsed }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-[10px] text-(--t-text-3)">По расписанию</span>
                <span class="text-xs font-bold text-sky-400">
                  {{ props.savedReports.filter((r) => r.isScheduled).length }}
                </span>
              </div>
            </div>
          </div>

          <!-- View toggle -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-3 flex gap-1">
            <button
              :class="[
                'relative overflow-hidden flex-1 py-1.5 rounded-lg text-[10px] font-medium text-center transition-all',
                viewMode === 'grid'
                  ? 'bg-(--t-primary)/12 text-(--t-primary)'
                  : 'text-(--t-text-3) hover:bg-(--t-card-hover)',
              ]"
              @click="viewMode = 'grid'" @mousedown="ripple"
            >▦ Сетка</button>
            <button
              :class="[
                'relative overflow-hidden flex-1 py-1.5 rounded-lg text-[10px] font-medium text-center transition-all',
                viewMode === 'list'
                  ? 'bg-(--t-primary)/12 text-(--t-primary)'
                  : 'text-(--t-text-3) hover:bg-(--t-card-hover)',
              ]"
              @click="viewMode = 'list'" @mousedown="ripple"
            >☰ Список</button>
          </div>
        </aside>
      </Transition>

      <!-- ═══ CONTENT ═══ -->
      <div class="flex-1 flex flex-col gap-6 min-w-0">

        <!-- ──────────────────────────
             READY-MADE TEMPLATES
        ────────────────────────── -->
        <section>
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-bold text-(--t-text) flex items-center gap-2">
              📋 Шаблоны отчётов
              <span class="text-[10px] text-(--t-text-3) font-normal">
                {{ filteredTemplates.length }} {{ activeCategory === 'all' ? '' : '/ ' + vc.templates.length }}
              </span>
            </h2>
            <!-- Mobile view toggle -->
            <div class="flex xl:hidden gap-0.5 rounded-lg border border-(--t-border)/40 overflow-hidden">
              <button
                :class="['px-2 py-1 text-[10px] transition-all', viewMode === 'grid' ? 'bg-(--t-primary)/12 text-(--t-primary)' : 'text-(--t-text-3)']"
                @click="viewMode = 'grid'" @mousedown="ripple"
              >▦</button>
              <button
                :class="['px-2 py-1 text-[10px] transition-all', viewMode === 'list' ? 'bg-(--t-primary)/12 text-(--t-primary)' : 'text-(--t-text-3)']"
                @click="viewMode = 'list'" @mousedown="ripple"
              >☰</button>
            </div>
          </div>

          <!-- Loading -->
          <div v-if="props.loading && filteredTemplates.length === 0"
               :class="viewMode === 'grid' ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3' : 'flex flex-col gap-2'">
            <div v-for="n in 6" :key="n"
                 :class="viewMode === 'grid' ? 'h-36 rounded-2xl bg-(--t-surface)/60 animate-pulse' : 'h-16 rounded-xl bg-(--t-surface)/60 animate-pulse'" />
          </div>

          <!-- GRID VIEW -->
          <div v-else-if="viewMode === 'grid'"
               class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <button
              v-for="tpl in filteredTemplates" :key="tpl.key"
              class="group relative overflow-hidden rounded-2xl border border-(--t-border)/40
                     bg-(--t-surface)/60 backdrop-blur-sm p-4 text-start
                     hover:border-(--t-border)/80 hover:shadow-lg hover:shadow-black/8
                     active:scale-[0.97] transition-all"
              @click="generateReport(tpl)" @mousedown="ripple"
            >
              <!-- Glow -->
              <div class="absolute -inset-block-start-6 -inset-inline-end-6 w-20 h-20 rounded-full blur-2xl
                          bg-(--t-primary)/10 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" />

              <!-- Badges -->
              <div class="absolute inset-block-start-3 inset-inline-end-3 flex gap-1">
                <span v-if="tpl.popular"
                      class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-amber-500/15 text-amber-400">
                  ⭐ Популярный
                </span>
                <span v-if="tpl.isNew"
                      class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-violet-500/15 text-violet-400">
                  🆕 Новый
                </span>
              </div>

              <!-- Generating spinner -->
              <div v-if="generatingTemplate === tpl.key"
                   class="absolute inset-0 bg-(--t-surface)/80 flex items-center justify-center rounded-2xl z-10">
                <span class="text-2xl animate-spin">⏳</span>
              </div>

              <div class="relative z-1 flex flex-col gap-2.5">
                <span class="text-2xl">{{ tpl.icon }}</span>
                <p class="text-sm font-bold text-(--t-text) leading-snug">{{ tpl.title }}</p>
                <p class="text-[10px] text-(--t-text-3) leading-relaxed line-clamp-2"
                   style="-webkit-line-clamp: 2; display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden; line-clamp: 2">
                  {{ tpl.description }}
                </p>
                <div class="flex flex-wrap gap-1 mt-1">
                  <span v-for="m in tpl.metrics" :key="m"
                        class="px-1.5 py-0.5 rounded-md text-[9px] font-medium bg-(--t-bg)/80 text-(--t-text-3)">
                    {{ m }}
                  </span>
                </div>
              </div>
            </button>
          </div>

          <!-- LIST VIEW -->
          <div v-else class="flex flex-col gap-1.5">
            <button
              v-for="tpl in filteredTemplates" :key="tpl.key"
              class="group relative overflow-hidden flex items-center gap-3 px-4 py-3
                     rounded-xl border border-(--t-border)/40 bg-(--t-surface)/60
                     hover:bg-(--t-card-hover) active:scale-[0.99] transition-all"
              @click="generateReport(tpl)" @mousedown="ripple"
            >
              <span class="shrink-0 text-xl">{{ tpl.icon }}</span>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-(--t-text) truncate">
                  {{ tpl.title }}
                  <span v-if="tpl.popular" class="text-amber-400 text-[9px]">⭐</span>
                  <span v-if="tpl.isNew" class="text-violet-400 text-[9px]">🆕</span>
                </p>
                <p class="text-[10px] text-(--t-text-3) truncate">{{ tpl.description }}</p>
              </div>
              <div class="hidden sm:flex gap-1 shrink-0">
                <span v-for="m in tpl.metrics.slice(0, 3)" :key="m"
                      class="px-1.5 py-0.5 rounded-md text-[9px] bg-(--t-bg)/80 text-(--t-text-3)">
                  {{ m }}
                </span>
              </div>
              <span class="shrink-0 text-xs text-(--t-primary) font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                {{ selectedFmt === 'pdf' ? '📄' : selectedFmt === 'xlsx' ? '📊' : '📃' }} Создать →
              </span>
              <div v-if="generatingTemplate === tpl.key"
                   class="absolute inset-0 bg-(--t-surface)/80 flex items-center justify-center z-10">
                <span class="animate-spin">⏳</span>
              </div>
            </button>
          </div>

          <!-- Empty -->
          <div v-if="!props.loading && filteredTemplates.length === 0"
               class="py-12 text-center text-sm text-(--t-text-3)">
            Нет шаблонов для выбранной категории
          </div>
        </section>

        <!-- ──────────────────────────
             SAVED REPORTS TABLE
        ────────────────────────── -->
        <section>
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-bold text-(--t-text) flex items-center gap-2">
              📁 Сохранённые отчёты
              <span class="text-[10px] text-(--t-text-3) font-normal">
                {{ filteredSavedReports.length }}
              </span>
            </h2>

            <!-- Bulk actions -->
            <div v-if="hasSelection" class="flex items-center gap-1.5">
              <span class="text-[10px] text-(--t-text-3)">{{ selectedReports.size }} выбрано</span>
              <button
                v-for="f in FORMAT_OPTIONS" :key="f.key"
                class="relative overflow-hidden px-2 py-1 rounded-lg text-[10px] font-medium
                       border border-(--t-border)/40 text-(--t-text-3) hover:bg-(--t-card-hover) transition-all"
                @click="bulkExport(f.key)" @mousedown="ripple"
                :title="`Экспорт ${f.label}`"
              >{{ f.icon }}</button>
            </div>
          </div>

          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm overflow-hidden">

            <!-- Table header -->
            <div class="hidden sm:grid grid-cols-[2rem_1fr_8rem_7rem_5rem_6rem] items-center gap-3
                        px-4 py-2.5 border-b border-(--t-border)/30 text-[10px] font-bold
                        text-(--t-text-3) uppercase tracking-wider">
              <label class="flex items-center justify-center cursor-pointer">
                <input type="checkbox" :checked="selectAll"
                       class="accent-(--t-primary)" @change="toggleSelectAll" />
              </label>
              <button class="text-start flex items-center gap-1 hover:text-(--t-text) transition-colors"
                      @click="toggleSort('name')">
                Название
                <span v-if="sortField === 'name'">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
              </button>
              <button class="text-start flex items-center gap-1 hover:text-(--t-text) transition-colors"
                      @click="toggleSort('period')">
                Период
                <span v-if="sortField === 'period'">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
              </button>
              <button class="text-start flex items-center gap-1 hover:text-(--t-text) transition-colors"
                      @click="toggleSort('created')">
                Создан
                <span v-if="sortField === 'created'">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
              </button>
              <button class="text-start flex items-center gap-1 hover:text-(--t-text) transition-colors"
                      @click="toggleSort('status')">
                Статус
                <span v-if="sortField === 'status'">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
              </button>
              <span class="text-end">Действия</span>
            </div>

            <!-- Loading -->
            <div v-if="props.loading && filteredSavedReports.length === 0" class="p-4 flex flex-col gap-2">
              <div v-for="n in 5" :key="n" class="h-14 rounded-xl bg-(--t-bg)/60 animate-pulse" />
            </div>

            <!-- Empty -->
            <div v-else-if="filteredSavedReports.length === 0"
                 class="py-16 text-center text-sm text-(--t-text-3)">
              <p class="text-3xl mb-2">📁</p>
              <p>Нет сохранённых отчётов</p>
              <p class="text-[10px] mt-1">Выберите шаблон или создайте кастомный отчёт</p>
            </div>

            <!-- Rows -->
            <div v-else class="divide-y divide-(--t-border)/20">
              <div
                v-for="r in filteredSavedReports" :key="r.id"
                class="group flex flex-col sm:grid sm:grid-cols-[2rem_1fr_8rem_7rem_5rem_6rem] items-start sm:items-center
                       gap-2 sm:gap-3 px-4 py-3 hover:bg-(--t-card-hover)/40 transition-colors"
              >
                <!-- Checkbox -->
                <label class="hidden sm:flex items-center justify-center cursor-pointer">
                  <input type="checkbox" :checked="selectedReports.has(r.id)"
                         class="accent-(--t-primary)" @change="toggleSelectReport(r.id)" />
                </label>

                <!-- Name + tags -->
                <div class="flex items-center gap-2 min-w-0 inline-size-full sm:inline-size-auto">
                  <span class="shrink-0 text-base">
                    {{ r.format === 'pdf' ? '📄' : r.format === 'xlsx' ? '📊' : '📃' }}
                  </span>
                  <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-(--t-text) truncate">{{ r.name }}</p>
                    <div v-if="r.tags && r.tags.length > 0"
                         class="flex gap-1 mt-0.5">
                      <span v-for="tag in r.tags.slice(0, 3)" :key="tag"
                            class="px-1 py-px rounded text-[8px] bg-(--t-bg)/80 text-(--t-text-3)">
                        {{ tag }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Period -->
                <span class="text-[10px] sm:text-xs text-(--t-text-2)">{{ r.periodLabel }}</span>

                <!-- Created -->
                <div class="text-[10px] sm:text-xs text-(--t-text-3)">
                  <p>{{ fmtDate(r.createdAt) }}</p>
                  <p class="text-[9px] opacity-60">{{ fmtTime(r.createdAt) }}</p>
                </div>

                <!-- Status -->
                <div class="flex items-center gap-1.5">
                  <span :class="['shrink-0 w-1.5 h-1.5 rounded-full', STATUS_MAP[r.status].dot]" />
                  <span :class="['px-1.5 py-0.5 rounded-md text-[9px] font-medium', STATUS_MAP[r.status].bg]">
                    {{ STATUS_MAP[r.status].label }}
                  </span>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-1 justify-end inline-size-full sm:inline-size-auto">
                  <button v-if="r.status === 'ready'"
                          class="relative overflow-hidden w-7 h-7 rounded-lg flex items-center justify-center
                                 text-(--t-text-3) hover:bg-(--t-primary)/12 hover:text-(--t-primary)
                                 active:scale-90 transition-all"
                          @click.stop="openPreview(r)" @mousedown="ripple" title="Предпросмотр"
                  >👁️</button>
                  <button v-if="r.status === 'ready'"
                          class="relative overflow-hidden w-7 h-7 rounded-lg flex items-center justify-center
                                 text-(--t-text-3) hover:bg-emerald-500/12 hover:text-emerald-400
                                 active:scale-90 transition-all"
                          @click.stop="downloadReport(r)" @mousedown="ripple" title="Скачать"
                  >⬇️</button>
                  <button class="relative overflow-hidden w-7 h-7 rounded-lg flex items-center justify-center
                                 text-(--t-text-3) hover:bg-rose-500/12 hover:text-rose-400
                                 active:scale-90 transition-all"
                          @click.stop="deleteReport(r)" @mousedown="ripple" title="Удалить"
                  >🗑️</button>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════
         MOBILE SIDEBAR DRAWER
    ══════════════════════════════════════════════ -->
    <Transition name="dw-rp">
      <div v-if="showMobileSidebar"
           class="fixed inset-0 z-50 flex" @click.self="showMobileSidebar = false">
        <div class="absolute inset-0 bg-black/40" @click="showMobileSidebar = false" />

        <div class="relative z-10 inline-size-64 max-w-[80vw] bg-(--t-surface)
                    border-e border-(--t-border) h-full overflow-y-auto p-4 flex flex-col gap-4">

          <div class="flex items-center justify-between mb-1">
            <h3 class="text-sm font-bold text-(--t-text)">Категории</h3>
            <button class="text-(--t-text-3) hover:text-(--t-text) transition-colors"
                    @click="showMobileSidebar = false">✕</button>
          </div>

          <div class="flex flex-col gap-0.5">
            <button
              v-for="cat in vc.categories" :key="cat.key"
              :class="[
                'relative overflow-hidden flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs transition-all',
                activeCategory === cat.key
                  ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold'
                  : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
              ]"
              @click="setCategory(cat.key)" @mousedown="ripple"
            >
              <span class="shrink-0 text-sm">{{ cat.icon }}</span>
              <span class="flex-1 text-start truncate">{{ cat.label }}</span>
              <span class="shrink-0 text-[10px] opacity-60">{{ categoryCounts[cat.key] ?? 0 }}</span>
            </button>
          </div>

          <div class="border-t border-(--t-border)/30 pt-3 mt-auto">
            <div class="flex gap-1">
              <button
                :class="['relative overflow-hidden flex-1 py-2 rounded-lg text-[10px] font-medium text-center transition-all',
                  viewMode === 'grid' ? 'bg-(--t-primary)/12 text-(--t-primary)' : 'text-(--t-text-3) hover:bg-(--t-card-hover)']"
                @click="viewMode = 'grid'" @mousedown="ripple"
              >▦ Сетка</button>
              <button
                :class="['relative overflow-hidden flex-1 py-2 rounded-lg text-[10px] font-medium text-center transition-all',
                  viewMode === 'list' ? 'bg-(--t-primary)/12 text-(--t-primary)' : 'text-(--t-text-3) hover:bg-(--t-card-hover)']"
                @click="viewMode = 'list'" @mousedown="ripple"
              >☰ Список</button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════════════
         CREATE CUSTOM REPORT MODAL
    ══════════════════════════════════════════════ -->
    <Transition name="modal-rp">
      <div v-if="showCreateModal"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="showCreateModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showCreateModal = false" />

        <div class="relative z-10 inline-size-full max-w-lg max-h-[85vh] bg-(--t-surface)
                    rounded-2xl border border-(--t-border)/60 shadow-2xl
                    flex flex-col overflow-hidden">

          <!-- Modal header -->
          <div class="flex items-center justify-between px-5 py-4 border-b border-(--t-border)/30">
            <div>
              <h3 class="text-sm font-bold text-(--t-text)">⚙️ Конструктор отчёта</h3>
              <p class="text-[10px] text-(--t-text-3) mt-0.5">Выберите метрики для кастомного отчёта</p>
            </div>
            <button class="w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="showCreateModal = false">✕</button>
          </div>

          <!-- Modal body -->
          <div class="flex-1 overflow-y-auto p-5 flex flex-col gap-4">

            <!-- Report name -->
            <div>
              <label class="block text-[10px] font-medium text-(--t-text-3) mb-1.5">Название отчёта</label>
              <input
                v-model="customName"
                type="text"
                placeholder="Например: Выручка Q1 2026"
                class="inline-size-full py-2 px-3 rounded-xl border border-(--t-border)/50
                       bg-(--t-bg)/60 text-xs text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </div>

            <!-- Metric groups -->
            <div v-for="group in CUSTOM_FIELD_GROUPS" :key="group.group">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-2">
                {{ group.group }}
              </h4>
              <div class="grid grid-cols-2 gap-1.5">
                <label
                  v-for="field in group.fields" :key="field.key"
                  :class="[
                    'flex items-center gap-2 px-3 py-2 rounded-xl cursor-pointer transition-all text-xs',
                    customFields.find((f) => f.key === field.key)?.checked
                      ? 'bg-(--t-primary)/10 border border-(--t-primary)/30 text-(--t-primary)'
                      : 'border border-(--t-border)/30 text-(--t-text-3) hover:bg-(--t-card-hover)',
                  ]"
                >
                  <input
                    type="checkbox"
                    :checked="customFields.find((f) => f.key === field.key)?.checked"
                    class="accent-(--t-primary)"
                    @change="(() => { const cf = customFields.find((f) => f.key === field.key); if (cf) cf.checked = !cf.checked })()"
                  />
                  <span class="truncate">{{ field.label }}</span>
                </label>
              </div>
            </div>

            <!-- Format -->
            <div>
              <label class="block text-[10px] font-medium text-(--t-text-3) mb-1.5">Формат</label>
              <div class="flex gap-1.5">
                <button
                  v-for="f in FORMAT_OPTIONS" :key="f.key"
                  :class="[
                    'relative overflow-hidden flex-1 py-2 rounded-xl text-xs font-medium text-center transition-all border',
                    customFmt === f.key
                      ? 'border-(--t-primary)/40 bg-(--t-primary)/10 text-(--t-primary)'
                      : 'border-(--t-border)/40 text-(--t-text-3) hover:bg-(--t-card-hover)',
                  ]"
                  @click="customFmt = f.key" @mousedown="ripple"
                >{{ f.icon }} {{ f.label }}</button>
              </div>
            </div>
          </div>

          <!-- Modal footer -->
          <div class="flex items-center justify-between px-5 py-3 border-t border-(--t-border)/30">
            <span class="text-[10px] text-(--t-text-3)">
              Выбрано метрик: <strong class="text-(--t-text)">{{ customSelectedCount }}</strong>
            </span>
            <div class="flex gap-2">
              <button
                class="relative overflow-hidden px-4 py-2 rounded-xl text-xs font-medium
                       border border-(--t-border)/50 text-(--t-text-3) hover:bg-(--t-card-hover)
                       active:scale-95 transition-all"
                @click="showCreateModal = false" @mousedown="ripple"
              >Отмена</button>
              <button
                :class="[
                  'relative overflow-hidden px-4 py-2 rounded-xl text-xs font-semibold transition-all active:scale-95',
                  customSelectedCount > 0 && customName.trim()
                    ? 'bg-(--t-primary) text-white hover:brightness-110'
                    : 'bg-zinc-700 text-zinc-500 cursor-not-allowed',
                ]"
                :disabled="customSelectedCount === 0 || !customName.trim()"
                @click="submitCustomReport" @mousedown="ripple"
              >
                Создать отчёт
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════════════
         PREVIEW MODAL
    ══════════════════════════════════════════════ -->
    <Transition name="modal-rp">
      <div v-if="showPreviewModal && previewReport"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="closePreview">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closePreview" />

        <div class="relative z-10 inline-size-full max-w-2xl max-h-[85vh] bg-(--t-surface)
                    rounded-2xl border border-(--t-border)/60 shadow-2xl
                    flex flex-col overflow-hidden">

          <!-- Preview header -->
          <div class="flex items-center justify-between px-5 py-4 border-b border-(--t-border)/30">
            <div class="flex items-center gap-3 min-w-0">
              <span class="text-xl">
                {{ previewReport.format === 'pdf' ? '📄' : previewReport.format === 'xlsx' ? '📊' : '📃' }}
              </span>
              <div class="min-w-0">
                <h3 class="text-sm font-bold text-(--t-text) truncate">{{ previewReport.name }}</h3>
                <p class="text-[10px] text-(--t-text-3)">
                  {{ previewReport.periodLabel }} · {{ fmtDate(previewReport.createdAt) }}
                  <span v-if="previewReport.fileSize"> · {{ previewReport.fileSize }}</span>
                </p>
              </div>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
              <button
                class="relative overflow-hidden px-3 py-1.5 rounded-lg text-xs font-medium
                       bg-emerald-500/12 text-emerald-400 hover:bg-emerald-500/20
                       active:scale-95 transition-all"
                @click="downloadReport(previewReport)" @mousedown="ripple"
              >⬇️ Скачать</button>
              <button class="w-8 h-8 rounded-lg flex items-center justify-center
                             text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                      @click="closePreview">✕</button>
            </div>
          </div>

          <!-- Preview body -->
          <div class="flex-1 overflow-y-auto p-5">

            <!-- KPIs preview -->
            <div v-if="previewReport.previewData?.kpis"
                 class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-5">
              <div v-for="(kpi, idx) in previewReport.previewData.kpis" :key="idx"
                   class="rounded-xl border border-(--t-border)/30 bg-(--t-bg)/40 p-3">
                <p class="text-[10px] text-(--t-text-3) mb-1">{{ kpi.label }}</p>
                <p class="text-sm font-extrabold text-(--t-text)">{{ kpi.value }}</p>
                <span v-if="kpi.delta"
                      :class="[
                        'text-[10px] font-medium',
                        kpi.trend === 'up' ? 'text-emerald-400' : kpi.trend === 'down' ? 'text-rose-400' : 'text-zinc-400',
                      ]">
                  {{ kpi.trend === 'up' ? '↑' : kpi.trend === 'down' ? '↓' : '→' }} {{ kpi.delta }}
                </span>
              </div>
            </div>

            <!-- Summary text -->
            <div v-if="previewReport.previewData?.summary"
                 class="rounded-xl bg-(--t-bg)/40 border border-(--t-border)/30 p-4 mb-5">
              <p class="text-xs text-(--t-text-2) leading-relaxed">
                {{ previewReport.previewData.summary }}
              </p>
            </div>

            <!-- Table preview -->
            <div v-if="previewReport.previewData?.columns && previewReport.previewData?.rows"
                 class="overflow-x-auto rounded-xl border border-(--t-border)/30">
              <table class="inline-size-full text-xs">
                <thead>
                  <tr class="bg-(--t-bg)/60">
                    <th v-for="col in previewReport.previewData.columns" :key="col"
                        class="px-3 py-2 text-start text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider">
                      {{ col }}
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-(--t-border)/15">
                  <tr v-for="(row, ri) in previewReport.previewData.rows.slice(0, 10)" :key="ri"
                      class="hover:bg-(--t-card-hover)/30">
                    <td v-for="col in previewReport.previewData.columns" :key="col"
                        class="px-3 py-2 text-(--t-text-2)">
                      {{ Object(row)[col] ?? '—' }}
                    </td>
                  </tr>
                </tbody>
              </table>
              <p v-if="(previewReport.previewData.rows?.length ?? 0) > 10"
                 class="px-3 py-2 text-center text-[10px] text-(--t-text-3) border-t border-(--t-border)/15">
                Показаны 10 из {{ previewReport.previewData.rows?.length }} строк
              </p>
            </div>

            <!-- No preview -->
            <div v-if="!previewReport.previewData"
                 class="py-16 text-center text-sm text-(--t-text-3)">
              <p class="text-3xl mb-2">👁️</p>
              <p>Предпросмотр недоступен</p>
              <p class="text-[10px] mt-1">Скачайте файл для просмотра</p>
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
/* Ripple — unique suffix rp (Reports) */
@keyframes ripple-rp {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* No-scrollbar */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Sidebar transition */
.sb-rp-enter-active,
.sb-rp-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.sb-rp-enter-from,
.sb-rp-leave-to {
  opacity: 0;
  transform: translateX(-12px);
}

/* Drawer transition */
.dw-rp-enter-active,
.dw-rp-leave-active {
  transition: opacity 0.3s ease;
}
.dw-rp-enter-active > :last-child,
.dw-rp-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.dw-rp-enter-from,
.dw-rp-leave-to {
  opacity: 0;
}
.dw-rp-enter-from > :last-child,
.dw-rp-leave-to > :last-child {
  transform: translateX(-100%);
}

/* Modal transition */
.modal-rp-enter-active,
.modal-rp-leave-active {
  transition: opacity 0.25s ease;
}
.modal-rp-enter-active > :nth-child(2),
.modal-rp-leave-active > :nth-child(2) {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}
.modal-rp-enter-from,
.modal-rp-leave-to {
  opacity: 0;
}
.modal-rp-enter-from > :nth-child(2),
.modal-rp-leave-to > :nth-child(2) {
  transform: scale(0.95) translateY(8px);
  opacity: 0;
}
</style>
