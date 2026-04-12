<script setup lang="ts">
/**
 * TenantFinance.vue — Главная страница финансов B2B Tenant Dashboard
 *
 * Поддержка всех 127 вертикалей CatVRF:
 *   Beauty · Taxi · Food · Hotels · RealEstate · Flowers · Fashion · Furniture
 *   Fitness · Travel · Medical · Auto · и т.д.
 *
 * ─────────────────────────────────────────────────────────────
 *  Функционал:
 *   1. Верхняя панель: выбор периода + KPI (выручка, прибыль, средний чек)
 *   2. Grid виджетов (выручка, расходы, чистая прибыль, ROI)
 *   3. Графики (выручка по дням, структура расходов, динамика)
 *   4. Таблица «Структура выручки» (источники, услуги, мастера)
 *   5. Быстрые действия (выплатить, отчёт, экспорт)
 *   6. Full-screen режим
 *   7. Модал деталей транзакции
 * ─────────────────────────────────────────────────────────────
 *  Адаптация под вертикаль:
 *   → props.vertical определяет терминологию и поля
 *   → VERTICAL_FINANCE_CONFIG — маппинг конфигов
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

interface FinanceKpi {
  revenue:       number
  expenses:      number
  profit:        number
  avgCheck:      number
  ordersCount:   number
  commission:    number
  payoutsTotal:  number
  roi:           number
  revenueGrowth: number   // % vs prev period
  expenseGrowth: number
  profitGrowth:  number
}

interface RevenueDayPoint {
  date: string
  revenue: number
  expenses: number
  profit: number
}

interface RevenueSource {
  id:      number | string
  name:    string
  icon?:   string
  amount:  number
  share:   number  // 0-100%
  orders:  number
  trend:   number  // % vs prev period
}

interface FinanceTransaction {
  id:          number | string
  date:        string
  type:        'income' | 'expense' | 'commission' | 'payout' | 'refund' | 'bonus'
  description: string
  amount:      number
  status:      'completed' | 'pending' | 'failed'
  category?:   string
  correlationId?: string
}

interface ExpenseCategory {
  name:    string
  amount:  number
  share:   number
  color:   string
}

interface VerticalFinanceConfig {
  label:           string
  icon:            string
  revenueLabel:    string
  sourcesLabel:    string
  sourceColumns:   Array<{ key: string; label: string }>
  payoutLabel:     string
  quickActions:    Array<{ key: string; label: string; icon: string }>
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// PROPS & EMITS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const props = withDefaults(defineProps<{
  vertical?:          string
  kpi?:               FinanceKpi
  revenueDays?:       RevenueDayPoint[]
  revenueSources?:    RevenueSource[]
  expenseCategories?: ExpenseCategory[]
  transactions?:      FinanceTransaction[]
  loading?:           boolean
}>(), {
  vertical:          'default',
  kpi:               () => ({
    revenue: 0, expenses: 0, profit: 0, avgCheck: 0, ordersCount: 0,
    commission: 0, payoutsTotal: 0, roi: 0,
    revenueGrowth: 0, expenseGrowth: 0, profitGrowth: 0,
  }),
  revenueDays:       () => [],
  revenueSources:    () => [],
  expenseCategories: () => [],
  transactions:      () => [],
  loading:           false,
})

const emit = defineEmits<{
  'period-change':    [period: string]
  'payout-request':   []
  'report-generate':  [type: string]
  'export':           [format: 'csv' | 'xlsx' | 'pdf']
  'transaction-click': [tx: FinanceTransaction]
  'source-click':     [source: RevenueSource]
}>()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// STORES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const auth     = useAuth()
const business = useTenant()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// VERTICAL CONFIG
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const VERTICAL_FINANCE_CONFIG: Record<string, VerticalFinanceConfig> = {
  beauty: {
    label: 'Финансы салона', icon: '💄',
    revenueLabel: 'Выручка с услуг',
    sourcesLabel: 'По мастерам',
    sourceColumns: [
      { key: 'name',   label: 'Мастер / Услуга' },
      { key: 'orders', label: 'Записей' },
    ],
    payoutLabel: 'Выплатить мастерам',
    quickActions: [
      { key: 'payout',       label: 'Выплата мастерам', icon: '💸' },
      { key: 'report-daily', label: 'Отчёт за день',    icon: '📄' },
      { key: 'report-tax',   label: 'Налоговый отчёт',  icon: '🏛️' },
      { key: 'export-xlsx',  label: 'Экспорт XLSX',     icon: '📥' },
    ],
  },
  taxi: {
    label: 'Финансы парка', icon: '🚕',
    revenueLabel: 'Выручка с поездок',
    sourcesLabel: 'По водителям',
    sourceColumns: [
      { key: 'name',   label: 'Водитель / Маршрут' },
      { key: 'orders', label: 'Поездок' },
    ],
    payoutLabel: 'Выплатить водителям',
    quickActions: [
      { key: 'payout',       label: 'Выплата водителям', icon: '💸' },
      { key: 'report-daily', label: 'Дневной отчёт',     icon: '📄' },
      { key: 'report-fuel',  label: 'Отчёт по топливу',  icon: '⛽' },
      { key: 'export-xlsx',  label: 'Экспорт XLSX',      icon: '📥' },
    ],
  },
  food: {
    label: 'Финансы ресторана', icon: '🍽️',
    revenueLabel: 'Выручка с заказов',
    sourcesLabel: 'По категориям меню',
    sourceColumns: [
      { key: 'name',   label: 'Блюдо / Категория' },
      { key: 'orders', label: 'Заказов' },
    ],
    payoutLabel: 'Выплатить поварам',
    quickActions: [
      { key: 'payout',       label: 'Выплата персоналу', icon: '💸' },
      { key: 'report-daily', label: 'Кассовый отчёт',    icon: '📄' },
      { key: 'report-food',  label: 'Себестоимость',     icon: '📊' },
      { key: 'export-xlsx',  label: 'Экспорт XLSX',      icon: '📥' },
    ],
  },
  hotel: {
    label: 'Финансы отеля', icon: '🏨',
    revenueLabel: 'Выручка за проживание',
    sourcesLabel: 'По типам номеров',
    sourceColumns: [
      { key: 'name',   label: 'Тип номера / Услуга' },
      { key: 'orders', label: 'Бронирований' },
    ],
    payoutLabel: 'Выплатить персоналу',
    quickActions: [
      { key: 'payout',          label: 'Выплата персоналу', icon: '💸' },
      { key: 'report-occupancy', label: 'Отчёт загрузки',  icon: '🛏️' },
      { key: 'report-revpar',   label: 'RevPAR отчёт',     icon: '📊' },
      { key: 'export-xlsx',     label: 'Экспорт XLSX',     icon: '📥' },
    ],
  },
  realEstate: {
    label: 'Финансы агентства', icon: '🏢',
    revenueLabel: 'Комиссионная выручка',
    sourcesLabel: 'По агентам',
    sourceColumns: [
      { key: 'name',   label: 'Агент / Объект' },
      { key: 'orders', label: 'Сделок' },
    ],
    payoutLabel: 'Выплатить агентам',
    quickActions: [
      { key: 'payout',       label: 'Выплата агентам',  icon: '💸' },
      { key: 'report-deals', label: 'Отчёт по сделкам', icon: '📄' },
      { key: 'report-tax',   label: 'Налоговый отчёт',  icon: '🏛️' },
      { key: 'export-xlsx',  label: 'Экспорт XLSX',     icon: '📥' },
    ],
  },
  flowers: {
    label: 'Финансы магазина', icon: '💐',
    revenueLabel: 'Выручка с букетов',
    sourcesLabel: 'По категориям',
    sourceColumns: [
      { key: 'name',   label: 'Букет / Категория' },
      { key: 'orders', label: 'Заказов' },
    ],
    payoutLabel: 'Выплатить флористам',
    quickActions: [
      { key: 'payout',       label: 'Выплата флористам', icon: '💸' },
      { key: 'report-daily', label: 'Дневной отчёт',     icon: '📄' },
      { key: 'report-waste', label: 'Списания',          icon: '🗑️' },
      { key: 'export-xlsx',  label: 'Экспорт XLSX',      icon: '📥' },
    ],
  },
  fashion: {
    label: 'Финансы магазина', icon: '👗',
    revenueLabel: 'Выручка с продаж',
    sourcesLabel: 'По брендам',
    sourceColumns: [
      { key: 'name',   label: 'Бренд / Категория' },
      { key: 'orders', label: 'Продаж' },
    ],
    payoutLabel: 'Оплата поставщикам',
    quickActions: [
      { key: 'payout',        label: 'Оплата поставщикам', icon: '💸' },
      { key: 'report-season', label: 'Сезонный отчёт',     icon: '📊' },
      { key: 'report-margin', label: 'Маржинальность',     icon: '📈' },
      { key: 'export-xlsx',   label: 'Экспорт XLSX',       icon: '📥' },
    ],
  },
  furniture: {
    label: 'Финансы мебели', icon: '🛋️',
    revenueLabel: 'Выручка с продаж',
    sourcesLabel: 'По категориям',
    sourceColumns: [
      { key: 'name',   label: 'Категория / Товар' },
      { key: 'orders', label: 'Заказов' },
    ],
    payoutLabel: 'Оплата поставщикам',
    quickActions: [
      { key: 'payout',       label: 'Оплата поставщикам', icon: '💸' },
      { key: 'report-daily', label: 'Дневной отчёт',      icon: '📄' },
      { key: 'report-margin', label: 'Маржинальность',    icon: '📈' },
      { key: 'export-xlsx',  label: 'Экспорт XLSX',       icon: '📥' },
    ],
  },
  fitness: {
    label: 'Финансы клуба', icon: '💪',
    revenueLabel: 'Выручка с абонементов',
    sourcesLabel: 'По тренерам',
    sourceColumns: [
      { key: 'name',   label: 'Тренер / Услуга' },
      { key: 'orders', label: 'Клиентов' },
    ],
    payoutLabel: 'Выплатить тренерам',
    quickActions: [
      { key: 'payout',       label: 'Выплата тренерам', icon: '💸' },
      { key: 'report-daily', label: 'Дневной отчёт',    icon: '📄' },
      { key: 'report-churn', label: 'Отток клиентов',   icon: '📉' },
      { key: 'export-xlsx',  label: 'Экспорт XLSX',     icon: '📥' },
    ],
  },
  travel: {
    label: 'Финансы агентства', icon: '✈️',
    revenueLabel: 'Комиссионная выручка',
    sourcesLabel: 'По направлениям',
    sourceColumns: [
      { key: 'name',   label: 'Направление / Тип' },
      { key: 'orders', label: 'Бронирований' },
    ],
    payoutLabel: 'Оплата операторам',
    quickActions: [
      { key: 'payout',       label: 'Оплата операторам', icon: '💸' },
      { key: 'report-daily', label: 'Дневной отчёт',     icon: '📄' },
      { key: 'report-margin', label: 'Маржинальность',   icon: '📈' },
      { key: 'export-xlsx',  label: 'Экспорт XLSX',      icon: '📥' },
    ],
  },
  default: {
    label: 'Финансы', icon: '💰',
    revenueLabel: 'Общая выручка',
    sourcesLabel: 'Источники дохода',
    sourceColumns: [
      { key: 'name',   label: 'Источник' },
      { key: 'orders', label: 'Операций' },
    ],
    payoutLabel: 'Выплатить',
    quickActions: [
      { key: 'payout',       label: 'Выплата',        icon: '💸' },
      { key: 'report-daily', label: 'Дневной отчёт',  icon: '📄' },
      { key: 'report-tax',   label: 'Налоговый отчёт', icon: '🏛️' },
      { key: 'export-xlsx',  label: 'Экспорт XLSX',   icon: '📥' },
    ],
  },
}

const vf = computed(() => VERTICAL_FINANCE_CONFIG[props.vertical] ?? VERTICAL_FINANCE_CONFIG.default)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// STATE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const rootEl       = ref<HTMLElement | null>(null)
const isFullscreen = ref(false)

// Period
const PERIODS = [
  { key: 'today',   label: 'Сегодня' },
  { key: '7d',      label: '7 дней' },
  { key: '30d',     label: '30 дней' },
  { key: '90d',     label: '90 дней' },
  { key: 'year',    label: 'Год' },
  { key: 'custom',  label: 'Период' },
] as const

const activePeriod = ref<string>('30d')

// Tabs for structure table
const structureTab = ref<'sources' | 'transactions'>('sources')
const structureTabs = computed(() => [
  { key: 'sources',      label: vf.value.sourcesLabel,    icon: '📊' },
  { key: 'transactions', label: 'Последние операции', icon: '📝' },
])

// Transaction detail modal
const showTxModal    = ref(false)
const selectedTx     = ref<FinanceTransaction | null>(null)

// Animated KPI values (counter animation)
const animatedRevenue  = ref(0)
const animatedExpenses = ref(0)
const animatedProfit   = ref(0)
const animatedRoi      = ref(0)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// COMPUTED
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const kpi = computed(() => props.kpi)

// Chart: max value for scaling bars
const maxDayRevenue = computed(() => {
  if (!props.revenueDays.length) return 1
  return Math.max(...props.revenueDays.map(d => Math.max(d.revenue, d.expenses, d.profit))) || 1
})

// Expense donut segments
const donutSegments = computed(() => {
  const total = props.expenseCategories.reduce((s, c) => s + c.amount, 0) || 1
  let offset = 0
  return props.expenseCategories.map(cat => {
    const pct = (cat.amount / total) * 100
    const seg = { ...cat, percentage: pct, dashOffset: offset }
    offset += pct
    return seg
  })
})

// Wallet info from auth store
const walletBalance = computed(() => auth.walletBalance ?? 0)
const creditAvail   = computed(() => auth.creditAvailable ?? 0)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// PERIOD CHANGE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function selectPeriod(key: string) {
  activePeriod.value = key
  emit('period-change', key)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// QUICK ACTIONS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function handleQuickAction(key: string) {
  if (key === 'payout') {
    emit('payout-request')
  } else if (key.startsWith('report-')) {
    emit('report-generate', key.replace('report-', ''))
  } else if (key.startsWith('export-')) {
    emit('export', key.replace('export-', '') as 'csv' | 'xlsx' | 'pdf')
  }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// TRANSACTION DETAIL
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function openTx(tx: FinanceTransaction) {
  selectedTx.value = tx
  showTxModal.value = true
  emit('transaction-click', tx)
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
function onFullscreenChange() { isFullscreen.value = !!document.fullscreenElement }

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// ANIMATED KPI COUNTER
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function animateValue(from: number, to: number, duration: number, setter: (v: number) => void) {
  const start = performance.now()
  function step(ts: number) {
    const progress = Math.min((ts - start) / duration, 1)
    const eased = 1 - Math.pow(1 - progress, 3) // ease-out cubic
    setter(Math.round(from + (to - from) * eased))
    if (progress < 1) requestAnimationFrame(step)
  }
  requestAnimationFrame(step)
}

watch(() => props.kpi, (nv) => {
  animateValue(animatedRevenue.value,  nv.revenue,  800, v => { animatedRevenue.value  = v })
  animateValue(animatedExpenses.value, nv.expenses, 800, v => { animatedExpenses.value = v })
  animateValue(animatedProfit.value,   nv.profit,   800, v => { animatedProfit.value   = v })
  animateValue(animatedRoi.value,      nv.roi,      800, v => { animatedRoi.value      = v })
}, { immediate: true, deep: true })

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// HELPERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function fmtNum(n: number): string     { return n.toLocaleString('ru-RU') }
function fmtMoney(n: number): string   { return `${n.toLocaleString('ru-RU')} ₽` }
function fmtPercent(n: number): string { return `${n > 0 ? '+' : ''}${n.toFixed(1)}%` }
function fmtDate(iso: string): string  { return new Date(iso).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' }) }
function fmtDateFull(iso: string): string {
  return new Date(iso).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', year: 'numeric' })
}
function fmtDatetime(iso: string): string {
  return new Date(iso).toLocaleString('ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

function trendClass(val: number): string {
  if (val > 0) return 'text-emerald-400'
  if (val < 0) return 'text-rose-400'
  return 'text-(--t-text-3)'
}

function trendArrow(val: number): string {
  if (val > 0) return '↑'
  if (val < 0) return '↓'
  return '—'
}

const TX_TYPE_MAP: Record<string, { label: string; icon: string; color: string }> = {
  income:     { label: 'Доход',     icon: '💰', color: 'text-emerald-400' },
  expense:    { label: 'Расход',    icon: '💸', color: 'text-rose-400' },
  commission: { label: 'Комиссия',  icon: '🏦', color: 'text-amber-400' },
  payout:     { label: 'Выплата',   icon: '📤', color: 'text-sky-400' },
  refund:     { label: 'Возврат',   icon: '↩️', color: 'text-orange-400' },
  bonus:      { label: 'Бонус',     icon: '🎁', color: 'text-violet-400' },
}

const TX_STATUS_MAP: Record<string, { label: string; variant: string }> = {
  completed: { label: 'Завершена', variant: 'success' },
  pending:   { label: 'Ожидает',   variant: 'warning' },
  failed:    { label: 'Ошибка',    variant: 'danger' },
}

function barHeight(val: number): string {
  return `${Math.max(4, (val / maxDayRevenue.value) * 100)}%`
}

function ripple(e: MouseEvent) {
  const tgt = e.currentTarget as HTMLElement
  const rect = tgt.getBoundingClientRect()
  const diameter = Math.max(rect.width, rect.height) * 2
  const el = document.createElement('span')
  el.className = 'absolute rounded-full bg-white/10 pointer-events-none animate-[ripple-fn_0.6s_ease-out]'
  el.style.cssText = `inline-size:${diameter}px;block-size:${diameter}px;inset-inline-start:${e.clientX - rect.left - diameter / 2}px;inset-block-start:${e.clientY - rect.top - diameter / 2}px;`
  tgt.appendChild(el)
  setTimeout(() => el.remove(), 650)
}

onMounted(() => document.addEventListener('fullscreenchange', onFullscreenChange))
onBeforeUnmount(() => document.removeEventListener('fullscreenchange', onFullscreenChange))
</script>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     TEMPLATE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<template>
  <div
    ref="rootEl"
    :class="[
      'flex flex-col gap-5',
      isFullscreen ? 'fixed inset-0 z-90 bg-(--t-bg) p-5 overflow-auto' : '',
    ]"
  >
    <!-- ═══════════════════════════════════════════════
         1. HEADER: TITLE + PERIOD + ACTIONS
    ═══════════════════════════════════════════════ -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <!-- Inline-start: Title -->
      <div class="flex items-center gap-3">
        <span class="text-2xl">{{ vf.icon }}</span>
        <div>
          <h1 class="text-xl font-bold text-(--t-text)">{{ vf.label }}</h1>
          <p class="text-xs text-(--t-text-3)">
            {{ auth.tenantName }} · {{ auth.isB2BMode ? 'B2B' : 'B2C' }}
            <span v-if="walletBalance" class="ml-2">💳 {{ fmtMoney(walletBalance) }}</span>
          </p>
        </div>
      </div>

      <!-- Inline-end: Period + Actions -->
      <div class="flex items-center gap-2 flex-wrap">
        <!-- Period pills -->
        <div class="flex items-center rounded-xl border border-(--t-border) overflow-hidden">
          <button
            v-for="p in PERIODS"
            :key="p.key"
            :class="[
              'px-3 py-1.5 text-xs font-medium transition-all duration-200',
              activePeriod === p.key
                ? 'bg-(--t-primary)/15 text-(--t-primary)'
                : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
            ]"
            @click="selectPeriod(p.key)"
          >
            {{ p.label }}
          </button>
        </div>

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
         2. KPI WIDGET GRID
    ═══════════════════════════════════════════════ -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <!-- Revenue -->
      <div
        class="group relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
               backdrop-blur-xl p-4 transition-all duration-300
               hover:border-emerald-500/30 hover:shadow-[0_0_25px_rgba(16,185,129,.08)] cursor-pointer"
        @click="ripple($event)"
      >
        <div class="text-[10px] uppercase tracking-widest text-(--t-text-3) font-semibold">
          {{ vf.revenueLabel }}
        </div>
        <div class="text-2xl font-black text-emerald-400 mt-2 tabular-nums">
          {{ fmtMoney(animatedRevenue) }}
        </div>
        <div class="flex items-center gap-1 mt-1.5">
          <span :class="[trendClass(kpi.revenueGrowth), 'text-xs font-bold']">
            {{ trendArrow(kpi.revenueGrowth) }} {{ fmtPercent(kpi.revenueGrowth) }}
          </span>
          <span class="text-[10px] text-(--t-text-3)">vs пред. период</span>
        </div>
        <!-- Glow accent -->
        <div class="absolute inset-block-start-0 inset-inline-end-0 w-20 h-20 rounded-full bg-emerald-500/5 blur-2xl
                    group-hover:bg-emerald-500/10 transition-all duration-500" />
      </div>

      <!-- Expenses -->
      <div
        class="group relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
               backdrop-blur-xl p-4 transition-all duration-300
               hover:border-rose-500/30 hover:shadow-[0_0_25px_rgba(244,63,94,.08)] cursor-pointer"
        @click="ripple($event)"
      >
        <div class="text-[10px] uppercase tracking-widest text-(--t-text-3) font-semibold">Расходы</div>
        <div class="text-2xl font-black text-rose-400 mt-2 tabular-nums">
          {{ fmtMoney(animatedExpenses) }}
        </div>
        <div class="flex items-center gap-1 mt-1.5">
          <span :class="[trendClass(-kpi.expenseGrowth), 'text-xs font-bold']">
            {{ trendArrow(kpi.expenseGrowth) }} {{ fmtPercent(kpi.expenseGrowth) }}
          </span>
          <span class="text-[10px] text-(--t-text-3)">vs пред. период</span>
        </div>
        <div class="absolute inset-block-start-0 inset-inline-end-0 w-20 h-20 rounded-full bg-rose-500/5 blur-2xl
                    group-hover:bg-rose-500/10 transition-all duration-500" />
      </div>

      <!-- Profit -->
      <div
        class="group relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
               backdrop-blur-xl p-4 transition-all duration-300
               hover:border-sky-500/30 hover:shadow-[0_0_25px_rgba(14,165,233,.08)] cursor-pointer"
        @click="ripple($event)"
      >
        <div class="text-[10px] uppercase tracking-widest text-(--t-text-3) font-semibold">Чистая прибыль</div>
        <div :class="['text-2xl font-black mt-2 tabular-nums', animatedProfit >= 0 ? 'text-sky-400' : 'text-rose-400']">
          {{ fmtMoney(animatedProfit) }}
        </div>
        <div class="flex items-center gap-1 mt-1.5">
          <span :class="[trendClass(kpi.profitGrowth), 'text-xs font-bold']">
            {{ trendArrow(kpi.profitGrowth) }} {{ fmtPercent(kpi.profitGrowth) }}
          </span>
          <span class="text-[10px] text-(--t-text-3)">vs пред. период</span>
        </div>
        <div class="absolute inset-block-start-0 inset-inline-end-0 w-20 h-20 rounded-full bg-sky-500/5 blur-2xl
                    group-hover:bg-sky-500/10 transition-all duration-500" />
      </div>

      <!-- ROI -->
      <div
        class="group relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
               backdrop-blur-xl p-4 transition-all duration-300
               hover:border-violet-500/30 hover:shadow-[0_0_25px_rgba(139,92,246,.08)] cursor-pointer"
        @click="ripple($event)"
      >
        <div class="text-[10px] uppercase tracking-widest text-(--t-text-3) font-semibold">ROI</div>
        <div class="text-2xl font-black text-violet-400 mt-2 tabular-nums">
          {{ animatedRoi }}%
        </div>
        <div class="flex items-center gap-2 mt-1.5 text-[10px] text-(--t-text-3)">
          <span>Заказов: <b class="text-(--t-text)">{{ fmtNum(kpi.ordersCount) }}</b></span>
          <span>Ср. чек: <b class="text-(--t-text)">{{ fmtMoney(kpi.avgCheck) }}</b></span>
        </div>
        <div class="absolute inset-block-start-0 inset-inline-end-0 w-20 h-20 rounded-full bg-violet-500/5 blur-2xl
                    group-hover:bg-violet-500/10 transition-all duration-500" />
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════
         3. SECONDARY KPI ROW
    ═══════════════════════════════════════════════ -->
    <div class="grid grid-cols-3 gap-3">
      <!-- Commission -->
      <div class="rounded-xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-3 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-amber-500/10 text-amber-400 text-lg">🏦</div>
        <div>
          <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Комиссия платформы</div>
          <div class="text-sm font-bold text-amber-400">{{ fmtMoney(kpi.commission) }}</div>
        </div>
      </div>

      <!-- Payouts total -->
      <div class="rounded-xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-3 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-sky-500/10 text-sky-400 text-lg">📤</div>
        <div>
          <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Выплаты</div>
          <div class="text-sm font-bold text-sky-400">{{ fmtMoney(kpi.payoutsTotal) }}</div>
        </div>
      </div>

      <!-- Wallet -->
      <div class="rounded-xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-3 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-emerald-500/10 text-emerald-400 text-lg">💳</div>
        <div>
          <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Баланс Wallet</div>
          <div class="text-sm font-bold text-emerald-400">{{ fmtMoney(walletBalance) }}</div>
          <div v-if="auth.isB2BMode && creditAvail > 0" class="text-[10px] text-(--t-text-3)">
            Кредит: {{ fmtMoney(creditAvail) }}
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════
         4. CHARTS ROW
    ═══════════════════════════════════════════════ -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- 4a. Revenue chart (bar chart, CSS-only) -->
      <VCard title="Динамика выручки" class="lg:col-span-2" glow>
        <div v-if="props.revenueDays.length" class="flex items-end gap-px" style="block-size: 200px">
          <div
            v-for="(day, idx) in props.revenueDays"
            :key="idx"
            class="flex-1 flex flex-col items-center justify-end gap-0.5 group/bar cursor-pointer relative"
            @click="ripple($event)"
          >
            <!-- Revenue bar -->
            <div
              class="w-full rounded-t-sm bg-emerald-500/60 transition-all duration-300
                     group-hover/bar:bg-emerald-400"
              :style="{ blockSize: barHeight(day.revenue) }"
            />
            <!-- Expense bar (stacked behind or overlapping) -->
            <div
              class="w-full rounded-t-sm bg-rose-500/40 -mt-px transition-all duration-300
                     group-hover/bar:bg-rose-400/60"
              :style="{ blockSize: barHeight(day.expenses) }"
            />
            <!-- Tooltip on hover -->
            <div
              class="absolute inset-block-end-full mb-2 inset-inline-start-1/2 -translate-x-1/2 opacity-0
                     group-hover/bar:opacity-100 transition-opacity pointer-events-none z-20
                     bg-(--t-surface) border border-(--t-border) rounded-lg px-2 py-1.5 text-[10px]
                     whitespace-nowrap shadow-lg backdrop-blur-xl"
            >
              <div class="font-bold text-(--t-text)">{{ fmtDate(day.date) }}</div>
              <div class="text-emerald-400">Выручка: {{ fmtMoney(day.revenue) }}</div>
              <div class="text-rose-400">Расход: {{ fmtMoney(day.expenses) }}</div>
              <div :class="day.profit >= 0 ? 'text-sky-400' : 'text-rose-400'">Прибыль: {{ fmtMoney(day.profit) }}</div>
            </div>
            <!-- Date label (show every Nth) -->
            <div
              v-if="idx % Math.max(1, Math.floor(props.revenueDays.length / 7)) === 0"
              class="text-[8px] text-(--t-text-3) mt-1 whitespace-nowrap"
            >
              {{ fmtDate(day.date) }}
            </div>
          </div>
        </div>
        <div v-else class="flex items-center justify-center text-(--t-text-3) text-sm" style="block-size: 200px">
          Нет данных за период
        </div>
        <!-- Legend -->
        <div class="flex items-center gap-4 mt-3 text-[10px] text-(--t-text-3)">
          <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500/60" /> Выручка</span>
          <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-rose-500/40" /> Расходы</span>
        </div>
      </VCard>

      <!-- 4b. Expense structure (donut, CSS-only) -->
      <VCard title="Структура расходов" glow>
        <div class="flex flex-col items-center">
          <!-- SVG donut -->
          <svg viewBox="0 0 36 36" class="w-40 h-40">
            <circle
              v-for="(seg, i) in donutSegments"
              :key="i"
              cx="18" cy="18" r="15.5"
              fill="none"
              :stroke="seg.color"
              stroke-width="3"
              :stroke-dasharray="`${seg.percentage} ${100 - seg.percentage}`"
              :stroke-dashoffset="`${-seg.dashOffset}`"
              class="transition-all duration-500"
            />
            <!-- Center text -->
            <text x="18" y="18.5" text-anchor="middle" class="fill-(--t-text) text-[4px] font-bold">
              {{ fmtNum(kpi.expenses) }}
            </text>
            <text x="18" y="22" text-anchor="middle" class="fill-(--t-text-3) text-[2.5px]">₽</text>
          </svg>

          <!-- Legend -->
          <div class="w-full mt-4 space-y-2">
            <div
              v-for="cat in props.expenseCategories"
              :key="cat.name"
              class="flex items-center gap-2 text-xs"
            >
              <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ background: cat.color }" />
              <span class="flex-1 text-(--t-text-2) truncate">{{ cat.name }}</span>
              <span class="font-semibold text-(--t-text) tabular-nums">{{ fmtMoney(cat.amount) }}</span>
              <span class="text-(--t-text-3) tabular-nums" style="inline-size:36px; text-align:end">{{ cat.share.toFixed(0) }}%</span>
            </div>
          </div>
        </div>
        <div v-if="!props.expenseCategories.length" class="text-center text-sm text-(--t-text-3) py-8">
          Нет данных
        </div>
      </VCard>
    </div>

    <!-- ═══════════════════════════════════════════════
         5. STRUCTURE TABLE / TRANSACTIONS
    ═══════════════════════════════════════════════ -->
    <VCard :title="structureTab === 'sources' ? vf.sourcesLabel : 'Последние операции'" glow>
      <template #header-action>
        <div class="flex items-center rounded-xl border border-(--t-border) overflow-hidden">
          <button
            v-for="tab in structureTabs"
            :key="tab.key"
            :class="[
              'px-3 py-1.5 text-xs font-medium transition-all duration-200',
              structureTab === tab.key
                ? 'bg-(--t-primary)/15 text-(--t-primary)'
                : 'text-(--t-text-3) hover:text-(--t-text)',
            ]"
            @click="structureTab = tab.key as typeof structureTab"
          >
            {{ tab.icon }} {{ tab.label }}
          </button>
        </div>
      </template>

      <!-- 5a. Revenue sources -->
      <div v-if="structureTab === 'sources'" class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-(--t-border)">
              <th class="py-2.5 px-4 text-left text-xs font-semibold uppercase tracking-wider text-(--t-text-3)">
                {{ vf.sourceColumns[0]?.label ?? 'Источник' }}
              </th>
              <th class="py-2.5 px-4 text-right text-xs font-semibold uppercase tracking-wider text-(--t-text-3)">
                {{ vf.sourceColumns[1]?.label ?? 'Операций' }}
              </th>
              <th class="py-2.5 px-4 text-right text-xs font-semibold uppercase tracking-wider text-(--t-text-3)">Сумма</th>
              <th class="py-2.5 px-4 text-right text-xs font-semibold uppercase tracking-wider text-(--t-text-3)">Доля</th>
              <th class="py-2.5 px-4 text-right text-xs font-semibold uppercase tracking-wider text-(--t-text-3)">Тренд</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-(--t-border)/50">
            <tr
              v-for="src in props.revenueSources"
              :key="src.id"
              class="relative overflow-hidden transition-colors duration-150
                     hover:bg-(--t-card-hover) cursor-pointer"
              @click="emit('source-click', src); ripple($event)"
            >
              <td class="py-3 px-4">
                <div class="flex items-center gap-2">
                  <span v-if="src.icon" class="text-base">{{ src.icon }}</span>
                  <span class="font-semibold text-(--t-text)">{{ src.name }}</span>
                </div>
              </td>
              <td class="py-3 px-4 text-right tabular-nums text-(--t-text-2)">{{ fmtNum(src.orders) }}</td>
              <td class="py-3 px-4 text-right tabular-nums font-semibold text-(--t-text)">{{ fmtMoney(src.amount) }}</td>
              <td class="py-3 px-4 text-right">
                <!-- Share bar -->
                <div class="flex items-center gap-2 justify-end">
                  <div class="hidden sm:block" style="inline-size: 60px">
                    <div
                      class="h-1.5 rounded-full bg-emerald-500/40 transition-all duration-500"
                      :style="{ inlineSize: `${src.share}%` }"
                    />
                  </div>
                  <span class="text-xs tabular-nums text-(--t-text-2)">{{ src.share.toFixed(1) }}%</span>
                </div>
              </td>
              <td class="py-3 px-4 text-right">
                <span :class="[trendClass(src.trend), 'text-xs font-bold tabular-nums']">
                  {{ trendArrow(src.trend) }} {{ fmtPercent(src.trend) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!props.revenueSources.length" class="text-center py-10 text-sm text-(--t-text-3)">
          Нет данных за выбранный период
        </div>
      </div>

      <!-- 5b. Transactions list -->
      <div v-if="structureTab === 'transactions'">
        <div class="space-y-1">
          <div
            v-for="tx in props.transactions"
            :key="tx.id"
            class="flex items-center gap-3 px-4 py-3 rounded-xl
                   transition-all duration-150 hover:bg-(--t-card-hover) cursor-pointer
                   relative overflow-hidden"
            @click="openTx(tx); ripple($event)"
          >
            <!-- Icon -->
            <div
              :class="[
                'w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0',
                tx.type === 'income'  ? 'bg-emerald-500/10' :
                tx.type === 'expense' ? 'bg-rose-500/10' :
                tx.type === 'payout'  ? 'bg-sky-500/10' :
                tx.type === 'refund'  ? 'bg-orange-500/10' :
                tx.type === 'bonus'   ? 'bg-violet-500/10' :
                'bg-amber-500/10',
              ]"
            >
              {{ TX_TYPE_MAP[tx.type]?.icon ?? '💰' }}
            </div>

            <!-- Description -->
            <div class="flex-1 min-w-0">
              <div class="text-sm font-semibold text-(--t-text) truncate">{{ tx.description }}</div>
              <div class="flex items-center gap-2 mt-0.5">
                <span class="text-[10px] text-(--t-text-3)">{{ fmtDatetime(tx.date) }}</span>
                <VBadge
                  :text="TX_STATUS_MAP[tx.status]?.label ?? tx.status"
                  :variant="TX_STATUS_MAP[tx.status]?.variant ?? 'neutral'"
                  size="xs"
                />
              </div>
            </div>

            <!-- Amount -->
            <div :class="[
              'text-sm font-bold tabular-nums shrink-0',
              tx.type === 'income' || tx.type === 'bonus' ? 'text-emerald-400' :
              tx.type === 'refund' ? 'text-orange-400' :
              'text-rose-400',
            ]">
              {{ tx.type === 'income' || tx.type === 'bonus' ? '+' : '−' }}{{ fmtMoney(tx.amount) }}
            </div>
          </div>
        </div>
        <div v-if="!props.transactions.length" class="text-center py-10 text-sm text-(--t-text-3)">
          Нет операций за выбранный период
        </div>
      </div>
    </VCard>

    <!-- ═══════════════════════════════════════════════
         6. QUICK ACTIONS
    ═══════════════════════════════════════════════ -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <button
        v-for="action in vf.quickActions"
        :key="action.key"
        class="group relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
               backdrop-blur-xl p-4 text-left
               transition-all duration-300
               hover:border-(--t-primary)/30 hover:shadow-[0_0_20px_var(--t-glow)]
               active:scale-[0.97]"
        @click="handleQuickAction(action.key); ripple($event)"
      >
        <div class="text-2xl mb-2">{{ action.icon }}</div>
        <div class="text-sm font-semibold text-(--t-text) group-hover:text-(--t-primary) transition-colors">
          {{ action.label }}
        </div>
        <div class="absolute inset-block-start-0 inset-inline-end-0 w-16 h-16 rounded-full bg-(--t-primary)/3 blur-2xl
                    group-hover:bg-(--t-primary)/8 transition-all duration-500" />
      </button>
    </div>

    <!-- ═══════════════════════════════════════════════
         7. B2B EXTRA: Credit & Payout info
    ═══════════════════════════════════════════════ -->
    <div v-if="auth.isB2BMode" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <!-- Credit limit -->
      <VCard title="🏦 Кредитный лимит" glow>
        <div class="space-y-3">
          <div class="flex justify-between text-sm">
            <span class="text-(--t-text-3)">Лимит</span>
            <span class="font-bold text-(--t-text)">{{ fmtMoney(auth.creditLimit ?? 0) }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-(--t-text-3)">Использовано</span>
            <span class="font-bold text-amber-400">{{ fmtMoney(auth.creditUsed ?? 0) }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-(--t-text-3)">Доступно</span>
            <span class="font-bold text-emerald-400">{{ fmtMoney(creditAvail) }}</span>
          </div>
          <!-- Progress bar -->
          <div class="h-2 rounded-full bg-(--t-border) overflow-hidden">
            <div
              class="h-full rounded-full bg-linear-to-r from-emerald-500 to-amber-500 transition-all duration-700"
              :style="{ inlineSize: `${auth.creditLimit ? ((auth.creditUsed ?? 0) / auth.creditLimit * 100) : 0}%` }"
            />
          </div>
        </div>
      </VCard>

      <!-- Payout summary -->
      <VCard title="📤 Выплаты за период" glow>
        <div class="space-y-3">
          <div class="flex justify-between text-sm">
            <span class="text-(--t-text-3)">Всего выплачено</span>
            <span class="font-bold text-sky-400">{{ fmtMoney(kpi.payoutsTotal) }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-(--t-text-3)">Комиссия платформы</span>
            <span class="font-bold text-amber-400">{{ fmtMoney(kpi.commission) }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-(--t-text-3)">К выплате</span>
            <span class="font-bold text-emerald-400">
              {{ fmtMoney(Math.max(0, kpi.revenue - kpi.expenses - kpi.commission - kpi.payoutsTotal)) }}
            </span>
          </div>
          <VButton variant="b2b" size="sm" full-width @click="emit('payout-request')">
            💸 {{ vf.payoutLabel }}
          </VButton>
        </div>
      </VCard>
    </div>

    <!-- ═══════════════════════════════════════════════
         TRANSACTION DETAIL MODAL
    ═══════════════════════════════════════════════ -->
    <VModal
      v-model="showTxModal"
      :title="`Операция #${selectedTx?.id ?? ''}`"
      size="md"
    >
      <template v-if="selectedTx">
        <div class="space-y-4">
          <!-- Header -->
          <div class="flex items-center gap-4">
            <div
              :class="[
                'w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shrink-0',
                selectedTx.type === 'income'  ? 'bg-emerald-500/10' :
                selectedTx.type === 'expense' ? 'bg-rose-500/10' :
                selectedTx.type === 'payout'  ? 'bg-sky-500/10' :
                'bg-amber-500/10',
              ]"
            >
              {{ TX_TYPE_MAP[selectedTx.type]?.icon ?? '💰' }}
            </div>
            <div class="min-w-0">
              <h2 class="text-lg font-bold text-(--t-text)">{{ selectedTx.description }}</h2>
              <div class="flex items-center gap-2 mt-0.5">
                <VBadge
                  :text="TX_TYPE_MAP[selectedTx.type]?.label ?? selectedTx.type"
                  :variant="selectedTx.type === 'income' ? 'success' : selectedTx.type === 'expense' ? 'danger' : 'info'"
                  size="xs"
                />
                <VBadge
                  :text="TX_STATUS_MAP[selectedTx.status]?.label ?? selectedTx.status"
                  :variant="TX_STATUS_MAP[selectedTx.status]?.variant ?? 'neutral'"
                  size="xs"
                />
              </div>
            </div>
          </div>

          <!-- Details grid -->
          <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl p-3 bg-(--t-card-hover)">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Сумма</div>
              <div :class="[
                'text-xl font-bold mt-1',
                selectedTx.type === 'income' || selectedTx.type === 'bonus' ? 'text-emerald-400' : 'text-rose-400',
              ]">
                {{ selectedTx.type === 'income' || selectedTx.type === 'bonus' ? '+' : '−' }}{{ fmtMoney(selectedTx.amount) }}
              </div>
            </div>
            <div class="rounded-xl p-3 bg-(--t-card-hover)">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Дата</div>
              <div class="text-sm font-semibold text-(--t-text) mt-1">{{ fmtDateFull(selectedTx.date) }}</div>
            </div>
            <div v-if="selectedTx.category" class="rounded-xl p-3 bg-(--t-card-hover)">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Категория</div>
              <div class="text-sm text-(--t-text) mt-1">{{ selectedTx.category }}</div>
            </div>
            <div v-if="selectedTx.correlationId" class="rounded-xl p-3 bg-(--t-card-hover)">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Correlation ID</div>
              <div class="text-xs text-(--t-text-3) mt-1 font-mono truncate">{{ selectedTx.correlationId }}</div>
            </div>
          </div>
        </div>
      </template>
      <template #footer>
        <VButton variant="ghost" size="sm" @click="showTxModal = false">Закрыть</VButton>
        <VButton variant="secondary" size="sm">📄 Квитанция</VButton>
      </template>
    </VModal>

    <!-- ═══════════════════════════════════════════════
         LOADING OVERLAY
    ═══════════════════════════════════════════════ -->
    <Transition name="fade-fn">
      <div
        v-if="loading"
        class="fixed inset-0 z-80 bg-(--t-bg)/40 backdrop-blur-sm flex items-center justify-center pointer-events-none"
      >
        <div class="flex items-center gap-3 text-(--t-text-2) bg-(--t-surface) border border-(--t-border)
                    rounded-2xl px-6 py-4 shadow-2xl backdrop-blur-xl pointer-events-auto">
          <svg class="animate-spin w-5 h-5 text-(--t-primary)" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <span class="text-sm font-medium">Загрузка финансов...</span>
        </div>
      </div>
    </Transition>
  </div>
</template>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     STYLES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<style scoped>
@keyframes ripple-fn {
  to { transform: scale(3.5); opacity: 0; }
}

.fade-fn-enter-active,
.fade-fn-leave-active {
  transition: opacity 0.3s ease;
}
.fade-fn-enter-from,
.fade-fn-leave-to {
  opacity: 0;
}

.tabular-nums {
  font-variant-numeric: tabular-nums;
}

/* Scrollbar */
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
</style>
