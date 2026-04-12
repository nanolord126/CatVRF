<script setup lang="ts">
/**
 * TenantPayments.vue — Платежи, кошелёк и финансовые операции B2B Tenant Dashboard
 *
 * Вертикали:
 *   Beauty · Taxi · Food · Hotels · RealEstate · Flowers
 *   Fashion · Furniture · Fitness · Travel · default
 *
 * ───────────────────────────────────────────────────────────────
 *  Функционал:
 *   1.  Баланс кошелька + hold + кредитный лимит (B2B)
 *   2.  KPI-виджеты: баланс, входящие, исходящие, комиссии, hold, средний чек
 *   3.  История транзакций: таблица (desktop) / карточки (mobile)
 *   4.  Фильтры: тип транзакции · статус · период · поиск
 *   5.  График движения средств (placeholder bar chart)
 *   6.  Модал «Пополнить» — выбор шлюза (Tinkoff / Sber / SBP / Tochka)
 *   7.  Модал «Вывести» — реквизиты + fraud-warning
 *   8.  Детальный drawer транзакции
 *   9.  Sidebar: быстрые действия · подключённые шлюзы · настройки
 *  10.  Смена периода · сортировка · full-screen · keyboard (Esc)
 *  11.  Mobile sidebar drawer · ripple-py
 *  12.  Glassmorphism · dark theme · 2026 design
 * ───────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { useAuth, useTenant } from '@/stores'

/* ━━━━━━━━━━━━  TYPES  ━━━━━━━━━━━━ */

type TxType =
  | 'deposit' | 'withdrawal' | 'commission' | 'bonus'
  | 'refund'  | 'payout'     | 'hold'       | 'release_hold'
  | 'payment' | 'invoice'

type TxStatus    = 'pending' | 'completed' | 'failed' | 'cancelled' | 'processing'
type SortKey     = 'date' | 'amount' | 'type' | 'status'
type SortDir     = 'asc' | 'desc'
type FilterType  = TxType | 'all'
type FilterStat  = TxStatus | 'all'
type Gateway     = 'tinkoff' | 'sber' | 'sbp' | 'tochka' | 'card'

interface Transaction {
  id:             number | string
  type:           TxType
  status:         TxStatus
  amount:         number
  balanceAfter:   number
  description:    string
  gateway?:       Gateway
  correlationId:  string
  metadata?:      Record<string, unknown>
  counterparty?:  string
  createdAt:      string
}

interface WalletData {
  currentBalance:  number
  holdAmount:      number
  creditLimit:     number
  creditUsed:      number
  availableCredit: number
  currency:        string
}

interface PeriodStats {
  incoming:   number
  outgoing:   number
  commission: number
  avgCheck:   number
  txCount:    number
  bonuses:    number
}

interface GatewayInfo {
  key:       Gateway
  label:     string
  icon:      string
  connected: boolean
  fee:       string
}

interface VerticalPayCfg {
  label:       string
  icon:        string
  accentColor: string
  payNoun:     string
}

/* ━━━━━━━━━━━━  PROPS / EMITS  ━━━━━━━━━━━━ */

const props = withDefaults(defineProps<{
  vertical?:      string
  wallet?:        WalletData | null
  transactions?:  Transaction[]
  periodStats?:   PeriodStats | null
  gateways?:      GatewayInfo[]
  chartData?:     Array<{ date: string; incoming: number; outgoing: number }>
  loading?:       boolean
  period?:        string
}>(), {
  vertical:     'default',
  wallet:       null,
  transactions: () => [],
  periodStats:  null,
  gateways:     () => [],
  chartData:    () => [],
  loading:      false,
  period:       '30d',
})

const emit = defineEmits<{
  'top-up':          [data: { amount: number; gateway: Gateway }]
  'withdraw':        [data: { amount: number; bankAccount: string; description: string }]
  'open-tx':         [id: number | string]
  'retry-tx':        [id: number | string]
  'cancel-tx':       [id: number | string]
  'export':          [format: 'csv' | 'xlsx' | 'pdf']
  'filter-change':   [filters: Record<string, string>]
  'sort-change':     [key: SortKey, dir: SortDir]
  'period-change':   [period: string]
  'connect-gateway': [gateway: Gateway]
  'refresh':         []
  'toggle-fullscreen': []
}>()

const auth = useAuth()
const biz  = useTenant()

/* ━━━━━━━━━━━━  VERTICAL CONFIG  ━━━━━━━━━━━━ */

const VERTICAL_CFG: Record<string, VerticalPayCfg> = {
  beauty:     { label: 'Салон красоты',   icon: '💄', accentColor: 'pink',    payNoun: 'услуги' },
  taxi:       { label: 'Такси',           icon: '🚕', accentColor: 'yellow',  payNoun: 'поездки' },
  food:       { label: 'Еда и рестораны', icon: '🍽️', accentColor: 'orange',  payNoun: 'заказы' },
  hotel:      { label: 'Отели',           icon: '🏨', accentColor: 'sky',     payNoun: 'бронирования' },
  realEstate: { label: 'Недвижимость',    icon: '🏢', accentColor: 'emerald', payNoun: 'сделки' },
  flowers:    { label: 'Цветы',           icon: '💐', accentColor: 'rose',    payNoun: 'букеты' },
  fashion:    { label: 'Мода и одежда',   icon: '👗', accentColor: 'violet',  payNoun: 'покупки' },
  furniture:  { label: 'Мебель',          icon: '🛋️', accentColor: 'amber',   payNoun: 'покупки' },
  fitness:    { label: 'Фитнес',          icon: '💪', accentColor: 'lime',    payNoun: 'абонементы' },
  travel:     { label: 'Путешествия',     icon: '✈️', accentColor: 'cyan',    payNoun: 'туры' },
  default:    { label: 'Бизнес',          icon: '📊', accentColor: 'indigo',  payNoun: 'платежи' },
}

const vc = computed<VerticalPayCfg>(() => VERTICAL_CFG[props.vertical] ?? VERTICAL_CFG.default)

/* ━━━━━━━━━━━━  CONSTANTS  ━━━━━━━━━━━━ */

const TX_META: Record<TxType, { label: string; icon: string; cls: string; sign: '+' | '-' | '~' }> = {
  deposit:      { label: 'Пополнение',       icon: '💳', cls: 'text-emerald-400', sign: '+' },
  withdrawal:   { label: 'Вывод',            icon: '🏦', cls: 'text-rose-400',    sign: '-' },
  commission:   { label: 'Комиссия',         icon: '📊', cls: 'text-amber-400',   sign: '-' },
  bonus:        { label: 'Бонус',            icon: '🎁', cls: 'text-violet-400',  sign: '+' },
  refund:       { label: 'Возврат',          icon: '↩️', cls: 'text-sky-400',     sign: '+' },
  payout:       { label: 'Выплата',          icon: '💸', cls: 'text-rose-400',    sign: '-' },
  hold:         { label: 'Холд',             icon: '🔒', cls: 'text-amber-400',   sign: '-' },
  release_hold: { label: 'Снятие холда',     icon: '🔓', cls: 'text-emerald-400', sign: '+' },
  payment:      { label: 'Оплата',           icon: '💰', cls: 'text-emerald-400', sign: '+' },
  invoice:      { label: 'Счёт',             icon: '📄', cls: 'text-sky-400',     sign: '~' },
}

const STATUS_META: Record<TxStatus, { label: string; dot: string; cls: string }> = {
  pending:    { label: 'Ожидает',     dot: 'bg-amber-500',   cls: 'bg-amber-500/12 text-amber-400' },
  processing: { label: 'Обработка',  dot: 'bg-sky-500',     cls: 'bg-sky-500/12 text-sky-400' },
  completed:  { label: 'Выполнена',  dot: 'bg-emerald-500', cls: 'bg-emerald-500/12 text-emerald-400' },
  failed:     { label: 'Ошибка',     dot: 'bg-rose-500',    cls: 'bg-rose-500/12 text-rose-400' },
  cancelled:  { label: 'Отменена',   dot: 'bg-zinc-500',    cls: 'bg-zinc-500/12 text-zinc-400' },
}

const GATEWAY_ICONS: Record<Gateway, { label: string; icon: string }> = {
  tinkoff: { label: 'Тинькофф',  icon: '🟡' },
  sber:    { label: 'Сбербанк',  icon: '🟢' },
  sbp:     { label: 'СБП',       icon: '🔵' },
  tochka:  { label: 'Точка',     icon: '🟣' },
  card:    { label: 'Карта',     icon: '💳' },
}

const PERIODS: Array<{ key: string; label: string }> = [
  { key: '7d',  label: '7 дней' },
  { key: '30d', label: '30 дней' },
  { key: '90d', label: '90 дней' },
  { key: '1y',  label: 'Год' },
  { key: 'all', label: 'Всё время' },
]

const TX_FILTER_TYPES: Array<{ key: FilterType; label: string }> = [
  { key: 'all',          label: 'Все' },
  { key: 'deposit',      label: '💳 Пополнения' },
  { key: 'payment',      label: '💰 Оплаты' },
  { key: 'withdrawal',   label: '🏦 Выводы' },
  { key: 'payout',       label: '💸 Выплаты' },
  { key: 'commission',   label: '📊 Комиссии' },
  { key: 'refund',       label: '↩️ Возвраты' },
  { key: 'bonus',        label: '🎁 Бонусы' },
  { key: 'hold',         label: '🔒 Холды' },
  { key: 'release_hold', label: '🔓 Снятия холда' },
  { key: 'invoice',      label: '📄 Счета' },
]

const STATUS_FILTER_OPTIONS: Array<{ key: FilterStat; label: string }> = [
  { key: 'all',        label: 'Все статусы' },
  { key: 'completed',  label: 'Выполнены' },
  { key: 'pending',    label: 'Ожидают' },
  { key: 'processing', label: 'Обработка' },
  { key: 'failed',     label: 'Ошибки' },
  { key: 'cancelled',  label: 'Отменены' },
]

const SORT_COLS: Array<{ key: SortKey; label: string }> = [
  { key: 'date',   label: 'Дата' },
  { key: 'amount', label: 'Сумма' },
  { key: 'type',   label: 'Тип' },
  { key: 'status', label: 'Статус' },
]

/* ━━━━━━━━━━━━  STATE  ━━━━━━━━━━━━ */

const rootEl              = ref<HTMLElement | null>(null)
const isFullscreen        = ref(false)
const searchQuery         = ref('')
const filterType          = ref<FilterType>('all')
const filterStatus        = ref<FilterStat>('all')
const selectedPeriod      = ref(props.period)
const sortKey             = ref<SortKey>('date')
const sortDir             = ref<SortDir>('desc')
const showSidebar         = ref(true)
const showMobileSidebar   = ref(false)
const showTopUpModal      = ref(false)
const showWithdrawModal   = ref(false)
const showDetailDrawer    = ref(false)
const detailTx            = ref<Transaction | null>(null)
const refreshing          = ref(false)
const showExportMenu      = ref(false)

/* ── Top-up form ── */
const topUpAmount        = ref<number>(1000)
const topUpGateway       = ref<Gateway>('tinkoff')

/* ── Withdraw form ── */
const withdrawAmount     = ref<number>(0)
const withdrawAccount    = ref('')
const withdrawDesc       = ref('')
const withdrawConfirm    = ref(false)

/* ━━━━━━━━━━━━  COMPUTED  ━━━━━━━━━━━━ */

const wallet = computed<WalletData>(() =>
  props.wallet ?? {
    currentBalance: 0, holdAmount: 0, creditLimit: 0,
    creditUsed: 0, availableCredit: 0, currency: '₽',
  },
)

const pStats = computed<PeriodStats>(() =>
  props.periodStats ?? {
    incoming: 0, outgoing: 0, commission: 0,
    avgCheck: 0, txCount: 0, bonuses: 0,
  },
)

const filteredTx = computed<Transaction[]>(() => {
  let list = [...props.transactions]

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase()
    list = list.filter(
      (tx) => tx.description.toLowerCase().includes(q)
           || tx.correlationId.toLowerCase().includes(q)
           || (tx.counterparty ?? '').toLowerCase().includes(q),
    )
  }

  if (filterType.value !== 'all') {
    list = list.filter((tx) => tx.type === filterType.value)
  }

  if (filterStatus.value !== 'all') {
    list = list.filter((tx) => tx.status === filterStatus.value)
  }

  list.sort((a, b) => {
    let cmp = 0
    switch (sortKey.value) {
      case 'date':   cmp = new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime(); break
      case 'amount': cmp = a.amount - b.amount; break
      case 'type':   cmp = a.type.localeCompare(b.type); break
      case 'status': cmp = a.status.localeCompare(b.status); break
    }
    return sortDir.value === 'asc' ? cmp : -cmp
  })

  return list
})

const activeFiltersCount = computed(() => {
  let c = 0
  if (filterType.value !== 'all') c++
  if (filterStatus.value !== 'all') c++
  if (searchQuery.value.trim()) c++
  return c
})

const maxChartVal = computed(() => {
  let m = 1
  for (const d of props.chartData) {
    if (d.incoming > m) m = d.incoming
    if (d.outgoing > m) m = d.outgoing
  }
  return m
})

const connectedGateways = computed(() => props.gateways.filter((g) => g.connected))

const maxWithdraw = computed(() =>
  Math.max(0, wallet.value.currentBalance - wallet.value.holdAmount),
)

/* ━━━━━━━━━━━━  HELPERS  ━━━━━━━━━━━━ */

function fmtMoney(n: number): string {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(2)}M ₽`
  if (n >= 1_000)     return `${(n / 1_000).toFixed(1)}K ₽`
  return `${n.toLocaleString('ru-RU')} ₽`
}

function fmtMoneyFull(n: number): string {
  return `${n.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₽`
}

function fmtNum(n: number): string {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M`
  if (n >= 1_000)     return `${(n / 1_000).toFixed(1)}K`
  return String(n)
}

function fmtDate(d: string): string {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('ru-RU', {
    day: '2-digit', month: 'short', year: 'numeric',
  })
}

function fmtTime(d: string): string {
  if (!d) return ''
  return new Date(d).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
}

function fmtDateTime(d: string): string {
  return `${fmtDate(d)} ${fmtTime(d)}`
}

function txSign(tx: Transaction): string {
  const meta = TX_META[tx.type]
  if (meta.sign === '+') return '+'
  if (meta.sign === '-') return '−'
  return ''
}

function txAmountCls(tx: Transaction): string {
  const meta = TX_META[tx.type]
  if (meta.sign === '+') return 'text-emerald-400'
  if (meta.sign === '-') return 'text-rose-400'
  return 'text-(--t-text-2)'
}

/* ━━━━━━━━━━━━  ACTIONS  ━━━━━━━━━━━━ */

function toggleSort(key: SortKey) {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'desc'
  }
  emit('sort-change', sortKey.value, sortDir.value)
}

function setPeriod(p: string) {
  selectedPeriod.value = p
  emit('period-change', p)
}

function clearFilters() {
  searchQuery.value = ''
  filterType.value = 'all'
  filterStatus.value = 'all'
}

function openTopUp() {
  topUpAmount.value = 1000
  topUpGateway.value = 'tinkoff'
  showTopUpModal.value = true
}

function submitTopUp() {
  emit('top-up', { amount: topUpAmount.value, gateway: topUpGateway.value })
  showTopUpModal.value = false
}

function openWithdraw() {
  withdrawAmount.value = 0
  withdrawAccount.value = ''
  withdrawDesc.value = ''
  withdrawConfirm.value = false
  showWithdrawModal.value = true
}

function submitWithdraw() {
  emit('withdraw', {
    amount: withdrawAmount.value,
    bankAccount: withdrawAccount.value,
    description: withdrawDesc.value,
  })
  showWithdrawModal.value = false
}

function openTxDetail(tx: Transaction) {
  detailTx.value = tx
  showDetailDrawer.value = true
}

function closeTxDetail() {
  showDetailDrawer.value = false
  detailTx.value = null
}

function doRefresh() {
  refreshing.value = true
  emit('refresh')
  setTimeout(() => { refreshing.value = false }, 1200)
}

function doExport(fmt: 'csv' | 'xlsx' | 'pdf') {
  emit('export', fmt)
  showExportMenu.value = false
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

/* ━━━━━━━━━━━━  KEYBOARD  ━━━━━━━━━━━━ */

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    if (showTopUpModal.value)      { showTopUpModal.value = false; return }
    if (showWithdrawModal.value)   { showWithdrawModal.value = false; return }
    if (showDetailDrawer.value)    { closeTxDetail(); return }
    if (showMobileSidebar.value)   { showMobileSidebar.value = false; return }
    if (showExportMenu.value)      { showExportMenu.value = false; return }
    if (isFullscreen.value)        { toggleFullscreen(); return }
  }
}

/* ━━━━━━━━━━━━  LIFECYCLE  ━━━━━━━━━━━━ */

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

/* ━━━━━━━━━━━━  RIPPLE  ━━━━━━━━━━━━ */

function ripple(e: MouseEvent) {
  const target = e.currentTarget as HTMLElement
  const rect   = target.getBoundingClientRect()
  const d      = Math.max(rect.width, rect.height) * 2
  const el     = document.createElement('span')
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-py_0.6s_ease-out]'
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

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     TEMPLATE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<template>
  <div
    ref="rootEl"
    :class="[
      'relative flex flex-col bg-(--t-bg) text-(--t-text)',
      isFullscreen ? 'fixed inset-0 z-50 overflow-auto' : 'min-h-screen',
    ]"
  >
    <!-- ══════════════════════════════════════
         HEADER
    ══════════════════════════════════════ -->
    <header class="sticky inset-block-start-0 z-30 bg-(--t-surface)/80 backdrop-blur-xl
                   border-b border-(--t-border)/40">
      <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 sm:px-6 py-3">

        <!-- Title -->
        <div class="flex items-center gap-3 flex-1 min-w-0">
          <span class="text-2xl">💰</span>
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <h1 class="text-base sm:text-lg font-extrabold text-(--t-text) truncate leading-snug">
                Платежи и кошелёк
              </h1>
              <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold
                           bg-emerald-500/15 text-emerald-400 tabular-nums">
                {{ fmtMoney(wallet.currentBalance) }}
              </span>
            </div>
            <p class="text-[10px] text-(--t-text-3) truncate">
              {{ vc.icon }} {{ vc.label }} · {{ vc.payNoun }}
            </p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 flex-wrap">

          <!-- Top Up -->
          <button
            class="relative overflow-hidden flex items-center gap-1.5 px-4 py-2 rounded-xl
                   text-xs font-semibold bg-emerald-500 text-white hover:brightness-110
                   active:scale-95 transition-all"
            @click="openTopUp" @mousedown="ripple"
          >
            <span class="text-sm">↑</span>
            <span>Пополнить</span>
          </button>

          <!-- Withdraw -->
          <button
            class="relative overflow-hidden flex items-center gap-1.5 px-4 py-2 rounded-xl
                   text-xs font-semibold border border-(--t-border)/50 text-(--t-text)
                   hover:bg-(--t-card-hover) active:scale-95 transition-all"
            @click="openWithdraw" @mousedown="ripple"
          >
            <span class="text-sm">↓</span>
            <span>Вывести</span>
          </button>

          <!-- Period pills -->
          <div class="hidden sm:flex items-center rounded-xl border border-(--t-border)/50 overflow-hidden">
            <button
              v-for="p in PERIODS" :key="p.key"
              :class="[
                'relative overflow-hidden px-2.5 py-1.5 text-[10px] font-medium transition-all',
                selectedPeriod === p.key
                  ? 'bg-(--t-primary) text-white'
                  : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
              ]"
              @click="setPeriod(p.key)" @mousedown="ripple"
            >{{ p.label }}</button>
          </div>

          <!-- Refresh -->
          <button
            :class="[
              'relative overflow-hidden shrink-0 w-9 h-9 rounded-xl border border-(--t-border)/50',
              'flex items-center justify-center text-(--t-text-3)',
              'hover:bg-(--t-card-hover) active:scale-95 transition-all',
              refreshing ? 'animate-spin' : '',
            ]"
            @click="doRefresh" @mousedown="ripple" title="Обновить"
          >🔄</button>

          <!-- Export -->
          <div class="relative">
            <button
              class="relative overflow-hidden shrink-0 w-9 h-9 rounded-xl border border-(--t-border)/50
                     flex items-center justify-center text-(--t-text-3)
                     hover:bg-(--t-card-hover) active:scale-95 transition-all"
              @click="showExportMenu = !showExportMenu" @mousedown="ripple" title="Экспорт"
            >📥</button>
            <Transition name="fade-py">
              <div v-if="showExportMenu"
                   class="absolute inset-inline-end-0 inset-block-start-full mt-1 z-20
                          w-36 rounded-xl border border-(--t-border)/50 bg-(--t-surface)
                          shadow-xl p-1 flex flex-col">
                <button
                  v-for="fmt in (['csv', 'xlsx', 'pdf'] as const)" :key="fmt"
                  class="relative overflow-hidden px-3 py-2 text-xs text-(--t-text-2)
                         rounded-lg hover:bg-(--t-card-hover) text-start transition-all"
                  @click="doExport(fmt)" @mousedown="ripple"
                >{{ fmt.toUpperCase() }}</button>
              </div>
            </Transition>
          </div>

          <!-- Mobile sidebar -->
          <button
            class="xl:hidden relative overflow-hidden shrink-0 w-9 h-9 rounded-xl
                   border border-(--t-border)/50 flex items-center justify-center text-(--t-text-3)
                   hover:bg-(--t-card-hover) active:scale-95 transition-all"
            @click="showMobileSidebar = true" @mousedown="ripple"
          >☰</button>

          <!-- Fullscreen -->
          <button
            class="hidden sm:flex relative overflow-hidden shrink-0 w-9 h-9 rounded-xl
                   border border-(--t-border)/50 items-center justify-center text-(--t-text-3)
                   hover:bg-(--t-card-hover) active:scale-95 transition-all"
            @click="toggleFullscreen" @mousedown="ripple"
          >{{ isFullscreen ? '🗗' : '⛶' }}</button>
        </div>
      </div>
    </header>

    <!-- ══════════════════════════════════════
         MAIN
    ══════════════════════════════════════ -->
    <div class="flex-1 flex gap-5 px-4 sm:px-6 py-5 max-w-screen-2xl mx-auto inline-size-full">

      <!-- ═══ CONTENT ═══ -->
      <div class="flex-1 flex flex-col gap-5 min-w-0">

        <!-- ── WALLET HERO ── -->
        <div class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/50
                    backdrop-blur-sm p-5 sm:p-6">
          <div class="flex flex-col sm:flex-row sm:items-end gap-4">

            <!-- Balance primary -->
            <div class="flex-1 min-w-0">
              <p class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-1">
                Доступный баланс
              </p>
              <p class="text-3xl sm:text-4xl font-black text-(--t-text) tabular-nums leading-none">
                {{ fmtMoneyFull(wallet.currentBalance) }}
              </p>
              <div class="flex items-center gap-4 mt-3">
                <div v-if="wallet.holdAmount > 0" class="flex items-center gap-1.5">
                  <span class="w-2 h-2 rounded-full bg-amber-500" />
                  <span class="text-[10px] text-amber-400 tabular-nums">
                    Холд: {{ fmtMoney(wallet.holdAmount) }}
                  </span>
                </div>
                <div v-if="wallet.creditLimit > 0" class="flex items-center gap-1.5">
                  <span class="w-2 h-2 rounded-full bg-violet-500" />
                  <span class="text-[10px] text-violet-400 tabular-nums">
                    Кредит: {{ fmtMoney(wallet.availableCredit) }} / {{ fmtMoney(wallet.creditLimit) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Quick amounts -->
            <div class="flex items-center gap-2">
              <button
                v-for="amt in [1000, 5000, 10000, 50000]" :key="amt"
                class="relative overflow-hidden px-3 py-1.5 rounded-xl text-[10px] font-medium
                       border border-(--t-border)/40 text-(--t-text-3)
                       hover:border-emerald-500/40 hover:text-emerald-400
                       hover:bg-emerald-500/5 active:scale-95 transition-all"
                @click="topUpAmount = amt; openTopUp()" @mousedown="ripple"
              >+ {{ fmtMoney(amt) }}</button>
            </div>
          </div>
        </div>

        <!-- ── KPI GRID ── -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
          <div v-for="kpi in [
            { label: 'Входящие',   value: fmtMoney(pStats.incoming),   icon: '📥', cls: 'text-emerald-400' },
            { label: 'Исходящие',  value: fmtMoney(pStats.outgoing),   icon: '📤', cls: 'text-rose-400' },
            { label: 'Комиссии',   value: fmtMoney(pStats.commission), icon: '📊', cls: 'text-amber-400' },
            { label: 'Ср. чек',    value: fmtMoney(pStats.avgCheck),   icon: '🧾', cls: 'text-sky-400' },
            { label: 'Транзакций', value: fmtNum(pStats.txCount),      icon: '🔄', cls: 'text-violet-400' },
            { label: 'Бонусы',     value: fmtMoney(pStats.bonuses),    icon: '🎁', cls: 'text-pink-400' },
          ]" :key="kpi.label"
             class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                    backdrop-blur-sm p-3.5">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-sm">{{ kpi.icon }}</span>
              <span class="text-[10px] font-medium text-(--t-text-3)">{{ kpi.label }}</span>
            </div>
            <p :class="['text-base sm:text-lg font-extrabold tabular-nums', kpi.cls]">
              {{ kpi.value }}
            </p>
          </div>
        </div>

        <!-- ── CHART (placeholder bar chart) ── -->
        <div v-if="props.chartData.length > 0"
             class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                    backdrop-blur-sm p-4 sm:p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-bold text-(--t-text)">📈 Движение средств</h3>
            <div class="flex items-center gap-4 text-[10px]">
              <span class="flex items-center gap-1.5">
                <span class="w-2.5 h-1.5 rounded-full bg-emerald-500" /> Входящие
              </span>
              <span class="flex items-center gap-1.5">
                <span class="w-2.5 h-1.5 rounded-full bg-rose-500" /> Исходящие
              </span>
            </div>
          </div>
          <div class="flex items-end gap-1.5" style="block-size: 140px">
            <div v-for="(bar, idx) in props.chartData" :key="idx"
                 class="flex-1 flex items-end gap-px" style="min-inline-size: 0">
              <div
                class="flex-1 rounded-t-sm bg-emerald-500/70 transition-all"
                :style="{ blockSize: `${Math.max(2, (bar.incoming / maxChartVal) * 100)}%` }"
                :title="`${fmtDate(bar.date)}: +${fmtMoney(bar.incoming)}`"
              />
              <div
                class="flex-1 rounded-t-sm bg-rose-500/50 transition-all"
                :style="{ blockSize: `${Math.max(2, (bar.outgoing / maxChartVal) * 100)}%` }"
                :title="`${fmtDate(bar.date)}: −${fmtMoney(bar.outgoing)}`"
              />
            </div>
          </div>
          <!-- X-axis labels -->
          <div class="flex items-center justify-between mt-2">
            <span v-for="(bar, idx) in [props.chartData[0], props.chartData[Math.floor(props.chartData.length / 2)], props.chartData[props.chartData.length - 1]]"
                  :key="idx"
                  class="text-[8px] text-(--t-text-3) tabular-nums">
              {{ bar ? fmtDate(bar.date) : '' }}
            </span>
          </div>
        </div>

        <!-- ── TRANSACTIONS — FILTERS ── -->
        <div class="flex flex-col gap-3">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold text-(--t-text)">📋 История транзакций</h3>
            <span class="text-[10px] text-(--t-text-3) tabular-nums">
              {{ filteredTx.length }} из {{ props.transactions.length }}
            </span>
          </div>

          <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
            <!-- Search -->
            <div class="relative shrink-0">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Поиск…"
                class="py-1.5 ps-8 pe-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors
                       inline-size-36 sm:inline-size-44"
              />
              <span class="absolute inset-inline-start-2.5 inset-block-start-1/2 -translate-y-1/2
                           text-xs text-(--t-text-3) pointer-events-none">🔍</span>
            </div>

            <!-- Type filter -->
            <select
              v-model="filterType"
              class="shrink-0 py-1.5 px-2.5 rounded-xl border border-(--t-border)/50
                     bg-(--t-bg)/60 text-xs text-(--t-text) focus:outline-none
                     focus:border-(--t-primary)/50 transition-colors appearance-none cursor-pointer"
            >
              <option v-for="t in TX_FILTER_TYPES" :key="t.key" :value="t.key">{{ t.label }}</option>
            </select>

            <!-- Status filter -->
            <select
              v-model="filterStatus"
              class="shrink-0 py-1.5 px-2.5 rounded-xl border border-(--t-border)/50
                     bg-(--t-bg)/60 text-xs text-(--t-text) focus:outline-none
                     focus:border-(--t-primary)/50 transition-colors appearance-none cursor-pointer"
            >
              <option v-for="s in STATUS_FILTER_OPTIONS" :key="s.key" :value="s.key">{{ s.label }}</option>
            </select>

            <!-- Clear -->
            <button
              v-if="activeFiltersCount > 0"
              class="shrink-0 flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[10px]
                     font-medium text-rose-400 bg-rose-500/10 hover:bg-rose-500/20
                     active:scale-95 transition-all"
              @click="clearFilters"
            >✕ Сбросить ({{ activeFiltersCount }})</button>

            <!-- Mobile period -->
            <select
              v-model="selectedPeriod"
              class="sm:hidden shrink-0 py-1.5 px-2.5 rounded-xl border border-(--t-border)/50
                     bg-(--t-bg)/60 text-xs text-(--t-text) focus:outline-none
                     focus:border-(--t-primary)/50 transition-colors appearance-none cursor-pointer"
              @change="setPeriod(selectedPeriod)"
            >
              <option v-for="p in PERIODS" :key="p.key" :value="p.key">{{ p.label }}</option>
            </select>
          </div>
        </div>

        <!-- ── TRANSACTIONS — LOADING ── -->
        <div v-if="props.loading && filteredTx.length === 0" class="flex flex-col gap-2.5">
          <div v-for="n in 5" :key="n"
               class="flex items-center gap-3 p-4 rounded-2xl border border-(--t-border)/20
                      bg-(--t-surface)/30 animate-pulse">
            <div class="shrink-0 w-10 h-10 rounded-xl bg-(--t-border)/30" />
            <div class="flex-1">
              <div class="h-3 w-40 bg-(--t-border)/30 rounded mb-2" />
              <div class="h-2.5 w-56 bg-(--t-border)/20 rounded" />
            </div>
            <div class="shrink-0 h-4 w-20 bg-(--t-border)/20 rounded-lg" />
          </div>
        </div>

        <!-- ── TRANSACTIONS — EMPTY ── -->
        <div v-else-if="filteredTx.length === 0 && !props.loading"
             class="py-16 text-center">
          <p class="text-5xl mb-3">🧾</p>
          <p class="text-sm font-semibold text-(--t-text-2)">
            {{ activeFiltersCount > 0 ? 'Транзакции не найдены' : 'Нет транзакций' }}
          </p>
          <p class="text-[10px] text-(--t-text-3) mt-1">
            {{ activeFiltersCount > 0 ? 'Попробуйте изменить фильтры' : 'Пополните кошелёк, чтобы начать' }}
          </p>
        </div>

        <!-- ── TRANSACTIONS — TABLE (desktop) ── -->
        <div v-else class="hidden md:block rounded-2xl border border-(--t-border)/30
                          bg-(--t-surface)/40 backdrop-blur-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="inline-size-full text-xs">
              <thead>
                <tr class="border-b border-(--t-border)/30">
                  <th v-for="col in SORT_COLS" :key="col.key"
                      class="px-4 py-3 text-start text-[10px] font-bold text-(--t-text-3)
                             uppercase tracking-wider cursor-pointer select-none
                             hover:text-(--t-text) transition-colors"
                      @click="toggleSort(col.key)">
                    <span class="flex items-center gap-1">
                      {{ col.label }}
                      <span v-if="sortKey === col.key" class="text-[9px]">
                        {{ sortDir === 'asc' ? '▲' : '▼' }}
                      </span>
                    </span>
                  </th>
                  <th class="px-4 py-3 text-start text-[10px] font-bold text-(--t-text-3)
                             uppercase tracking-wider">Описание</th>
                  <th class="px-4 py-3 text-start text-[10px] font-bold text-(--t-text-3)
                             uppercase tracking-wider">Шлюз</th>
                  <th class="px-4 py-3 text-end text-[10px] font-bold text-(--t-text-3)
                             uppercase tracking-wider">Баланс после</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="tx in filteredTx" :key="tx.id"
                    class="group/row border-b border-(--t-border)/15 hover:bg-(--t-card-hover)/40
                           transition-colors cursor-pointer"
                    @click="openTxDetail(tx)">

                  <!-- Date -->
                  <td class="px-4 py-3.5 text-[10px] text-(--t-text-3) tabular-nums">
                    <div>{{ fmtDate(tx.createdAt) }}</div>
                    <div class="text-[9px]">{{ fmtTime(tx.createdAt) }}</div>
                  </td>

                  <!-- Amount -->
                  <td class="px-4 py-3.5">
                    <span :class="['text-xs font-bold tabular-nums', txAmountCls(tx)]">
                      {{ txSign(tx) }}{{ fmtMoneyFull(tx.amount) }}
                    </span>
                  </td>

                  <!-- Type -->
                  <td class="px-4 py-3.5">
                    <span :class="[
                      'inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[10px] font-medium',
                      TX_META[tx.type].cls,
                    ]">
                      {{ TX_META[tx.type].icon }} {{ TX_META[tx.type].label }}
                    </span>
                  </td>

                  <!-- Status -->
                  <td class="px-4 py-3.5">
                    <span :class="[
                      'inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-medium',
                      STATUS_META[tx.status].cls,
                    ]">
                      <span :class="['w-1.5 h-1.5 rounded-full', STATUS_META[tx.status].dot]" />
                      {{ STATUS_META[tx.status].label }}
                    </span>
                  </td>

                  <!-- Description -->
                  <td class="px-4 py-3.5 text-xs text-(--t-text-2) max-w-48 truncate">
                    {{ tx.description }}
                  </td>

                  <!-- Gateway -->
                  <td class="px-4 py-3.5 text-[10px] text-(--t-text-3)">
                    <span v-if="tx.gateway">
                      {{ GATEWAY_ICONS[tx.gateway].icon }} {{ GATEWAY_ICONS[tx.gateway].label }}
                    </span>
                    <span v-else>—</span>
                  </td>

                  <!-- Balance after -->
                  <td class="px-4 py-3.5 text-end text-xs text-(--t-text-2) tabular-nums">
                    {{ fmtMoney(tx.balanceAfter) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ── TRANSACTIONS — CARDS (mobile) ── -->
        <div class="md:hidden flex flex-col gap-2.5">
          <button
            v-for="tx in filteredTx" :key="tx.id"
            class="group/card relative overflow-hidden text-start rounded-2xl
                   border border-(--t-border)/30 bg-(--t-surface)/50 backdrop-blur-sm
                   hover:border-(--t-border)/60 hover:shadow-lg hover:shadow-black/5
                   active:scale-[0.98] transition-all p-4"
            @click="openTxDetail(tx)" @mousedown="ripple"
          >
            <div class="flex items-center gap-3">
              <!-- Icon -->
              <div :class="[
                'shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-base',
                TX_META[tx.type].cls, 'bg-current/10',
              ]">
                {{ TX_META[tx.type].icon }}
              </div>

              <!-- Info -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                  <span class="text-xs font-bold text-(--t-text) truncate">
                    {{ TX_META[tx.type].label }}
                  </span>
                  <span :class="['text-xs font-bold tabular-nums shrink-0', txAmountCls(tx)]">
                    {{ txSign(tx) }}{{ fmtMoney(tx.amount) }}
                  </span>
                </div>
                <p class="text-[10px] text-(--t-text-3) truncate mt-0.5">{{ tx.description }}</p>
                <div class="flex items-center gap-2 mt-1.5">
                  <span :class="[
                    'inline-flex items-center gap-1 px-1.5 py-px rounded-md text-[8px] font-medium',
                    STATUS_META[tx.status].cls,
                  ]">
                    <span :class="['w-1 h-1 rounded-full', STATUS_META[tx.status].dot]" />
                    {{ STATUS_META[tx.status].label }}
                  </span>
                  <span class="text-[9px] text-(--t-text-3) tabular-nums">
                    {{ fmtDate(tx.createdAt) }} {{ fmtTime(tx.createdAt) }}
                  </span>
                </div>
              </div>
            </div>
          </button>
        </div>
      </div>

      <!-- ═══ SIDEBAR (desktop) ═══ -->
      <Transition name="sb-py">
        <aside v-if="showSidebar"
               class="hidden xl:flex shrink-0 flex-col gap-4 w-72">

          <!-- Quick actions -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              Быстрые действия
            </h3>
            <div class="flex flex-col gap-1.5">
              <button
                v-for="act in [
                  { label: '↑  Пополнить',     fn: () => openTopUp(),      color: 'emerald' },
                  { label: '↓  Вывести',        fn: () => openWithdraw(),   color: 'rose' },
                  { label: '📄 Создать счёт',    fn: () => {},              color: 'sky' },
                  { label: '📊 Отчёт за период', fn: () => doExport('pdf'), color: 'violet' },
                ]" :key="act.label"
                class="relative overflow-hidden flex items-center gap-2 px-3 py-2.5 rounded-xl
                       text-xs text-(--t-text-2) hover:text-(--t-text) hover:bg-(--t-card-hover)
                       active:scale-[0.97] transition-all text-start"
                @click="act.fn()" @mousedown="ripple"
              >{{ act.label }}</button>
            </div>
          </div>

          <!-- Connected gateways -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              Платёжные шлюзы
            </h3>
            <div class="flex flex-col gap-2">
              <div v-for="gw in props.gateways" :key="gw.key"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-xl bg-(--t-bg)/40">
                <span class="shrink-0 text-base">{{ GATEWAY_ICONS[gw.key].icon }}</span>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-medium text-(--t-text) truncate">{{ gw.label }}</p>
                  <p class="text-[9px] text-(--t-text-3)">
                    Комиссия {{ gw.fee }}
                  </p>
                </div>
                <span v-if="gw.connected"
                      class="shrink-0 w-5 h-5 rounded-full bg-emerald-500/15
                             flex items-center justify-center text-[10px]">✓</span>
                <button
                  v-else
                  class="shrink-0 px-2 py-0.5 rounded-lg text-[9px] font-medium
                         text-(--t-primary) bg-(--t-primary)/10 hover:bg-(--t-primary)/20
                         active:scale-95 transition-all"
                  @click="emit('connect-gateway', gw.key)"
                >Подключить</button>
              </div>
            </div>
          </div>

          <!-- Credit info (B2B) -->
          <div v-if="wallet.creditLimit > 0"
               class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              🏦 Кредитная линия B2B
            </h3>
            <div class="flex flex-col gap-3">
              <div>
                <div class="flex items-center justify-between text-[10px] text-(--t-text-3) mb-1.5">
                  <span>Использовано</span>
                  <span class="tabular-nums">
                    {{ fmtMoney(wallet.creditUsed) }} / {{ fmtMoney(wallet.creditLimit) }}
                  </span>
                </div>
                <div class="h-2 rounded-full bg-(--t-border)/20 overflow-hidden">
                  <div
                    :class="[
                      'h-full rounded-full transition-all',
                      wallet.creditUsed / wallet.creditLimit > 0.8
                        ? 'bg-rose-500' : wallet.creditUsed / wallet.creditLimit > 0.5
                          ? 'bg-amber-500' : 'bg-violet-500',
                    ]"
                    :style="{ inlineSize: `${Math.min(100, (wallet.creditUsed / wallet.creditLimit) * 100)}%` }"
                  />
                </div>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-[10px] text-(--t-text-3)">Доступно</span>
                <span class="text-xs font-bold text-violet-400 tabular-nums">
                  {{ fmtMoney(wallet.availableCredit) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Wallet breakdown -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              Структура баланса
            </h3>
            <div class="flex flex-col gap-2.5">
              <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-xs text-(--t-text-2)">
                  <span class="w-2.5 h-2.5 rounded-full bg-emerald-500" />
                  Доступно
                </span>
                <span class="text-xs font-bold text-emerald-400 tabular-nums">
                  {{ fmtMoney(Math.max(0, wallet.currentBalance - wallet.holdAmount)) }}
                </span>
              </div>
              <div v-if="wallet.holdAmount > 0" class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-xs text-(--t-text-2)">
                  <span class="w-2.5 h-2.5 rounded-full bg-amber-500" />
                  На холде
                </span>
                <span class="text-xs font-bold text-amber-400 tabular-nums">
                  {{ fmtMoney(wallet.holdAmount) }}
                </span>
              </div>
              <div v-if="wallet.creditUsed > 0" class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-xs text-(--t-text-2)">
                  <span class="w-2.5 h-2.5 rounded-full bg-violet-500" />
                  Кредит
                </span>
                <span class="text-xs font-bold text-violet-400 tabular-nums">
                  {{ fmtMoney(wallet.creditUsed) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Top transactions -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-3">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-2 px-1">
              Крупные операции
            </h3>
            <div class="flex flex-col gap-0.5">
              <button
                v-for="tx in [...props.transactions]
                  .sort((a, b) => b.amount - a.amount)
                  .slice(0, 5)"
                :key="tx.id"
                class="relative overflow-hidden flex items-center gap-2.5 px-3 py-2 rounded-xl
                       text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)
                       active:scale-[0.97] transition-all text-start"
                @click="openTxDetail(tx)" @mousedown="ripple"
              >
                <span class="shrink-0 text-xs">{{ TX_META[tx.type].icon }}</span>
                <span class="flex-1 text-[10px] truncate">{{ tx.description }}</span>
                <span :class="['shrink-0 text-[10px] font-bold tabular-nums', txAmountCls(tx)]">
                  {{ txSign(tx) }}{{ fmtMoney(tx.amount) }}
                </span>
              </button>
            </div>
          </div>
        </aside>
      </Transition>
    </div>

    <!-- ══════════════════════════════════════
         MOBILE SIDEBAR DRAWER
    ══════════════════════════════════════ -->
    <Transition name="dw-py">
      <div v-if="showMobileSidebar"
           class="fixed inset-0 z-50 flex" @click.self="showMobileSidebar = false">
        <div class="absolute inset-0 bg-black/40" @click="showMobileSidebar = false" />

        <div class="relative z-10 ms-auto inline-size-72 max-w-[85vw] h-full bg-(--t-surface)
                    border-s border-(--t-border) overflow-y-auto p-4 flex flex-col gap-4">

          <div class="flex items-center justify-between mb-1">
            <h3 class="text-sm font-bold text-(--t-text)">💰 Кошелёк</h3>
            <button class="text-(--t-text-3) hover:text-(--t-text) transition-colors"
                    @click="showMobileSidebar = false">✕</button>
          </div>

          <!-- Balance summary -->
          <div class="rounded-xl bg-(--t-bg)/50 p-4">
            <p class="text-[10px] text-(--t-text-3) mb-1">Баланс</p>
            <p class="text-xl font-black text-(--t-text) tabular-nums">
              {{ fmtMoneyFull(wallet.currentBalance) }}
            </p>
            <div class="flex gap-2 mt-3">
              <button
                class="relative overflow-hidden flex-1 py-2 rounded-xl text-[10px] font-semibold
                       bg-emerald-500 text-white active:scale-95 transition-all"
                @click="showMobileSidebar = false; openTopUp()" @mousedown="ripple"
              >↑ Пополнить</button>
              <button
                class="relative overflow-hidden flex-1 py-2 rounded-xl text-[10px] font-semibold
                       border border-(--t-border)/50 text-(--t-text)
                       active:scale-95 transition-all"
                @click="showMobileSidebar = false; openWithdraw()" @mousedown="ripple"
              >↓ Вывести</button>
            </div>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-2 gap-2">
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Входящие</p>
              <p class="text-xs font-bold text-emerald-400 tabular-nums">{{ fmtMoney(pStats.incoming) }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Исходящие</p>
              <p class="text-xs font-bold text-rose-400 tabular-nums">{{ fmtMoney(pStats.outgoing) }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Комиссии</p>
              <p class="text-xs font-bold text-amber-400 tabular-nums">{{ fmtMoney(pStats.commission) }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Транзакций</p>
              <p class="text-xs font-bold text-(--t-text) tabular-nums">{{ fmtNum(pStats.txCount) }}</p>
            </div>
          </div>

          <!-- Gateways -->
          <div>
            <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase mb-2">Шлюзы</h4>
            <div class="flex flex-col gap-1.5">
              <div v-for="gw in props.gateways" :key="gw.key"
                   class="flex items-center gap-2 px-3 py-2 rounded-xl bg-(--t-bg)/40">
                <span class="text-sm">{{ GATEWAY_ICONS[gw.key].icon }}</span>
                <span class="flex-1 text-xs text-(--t-text-2)">{{ gw.label }}</span>
                <span v-if="gw.connected" class="text-[10px] text-emerald-400">✓</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════
         TX DETAIL DRAWER
    ══════════════════════════════════════ -->
    <Transition name="detail-py">
      <div v-if="showDetailDrawer && detailTx"
           class="fixed inset-0 z-50 flex" @click.self="closeTxDetail">
        <div class="absolute inset-0 bg-black/40" @click="closeTxDetail" />

        <div class="relative z-10 ms-auto inline-size-full sm:inline-size-96 max-w-full h-full
                    bg-(--t-surface) border-s border-(--t-border) overflow-y-auto flex flex-col">

          <!-- Header -->
          <div class="sticky inset-block-start-0 z-10 flex items-center gap-3 px-5 py-4
                      bg-(--t-surface)/90 backdrop-blur-xl border-b border-(--t-border)/30">
            <div :class="[
              'shrink-0 w-11 h-11 rounded-xl flex items-center justify-center text-lg',
              TX_META[detailTx.type].cls, 'bg-current/10',
            ]">{{ TX_META[detailTx.type].icon }}</div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-bold text-(--t-text) truncate">
                {{ TX_META[detailTx.type].label }}
              </h3>
              <div class="flex items-center gap-2 mt-0.5">
                <span :class="[
                  'inline-flex items-center gap-1 px-1.5 py-px rounded-md text-[8px] font-medium',
                  STATUS_META[detailTx.status].cls,
                ]">
                  <span :class="['w-1.5 h-1.5 rounded-full', STATUS_META[detailTx.status].dot]" />
                  {{ STATUS_META[detailTx.status].label }}
                </span>
              </div>
            </div>
            <button class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="closeTxDetail">✕</button>
          </div>

          <!-- Body -->
          <div class="flex-1 p-5 flex flex-col gap-5">

            <!-- Amount -->
            <div class="text-center py-4 rounded-xl bg-(--t-bg)/50">
              <p :class="['text-3xl font-black tabular-nums', txAmountCls(detailTx)]">
                {{ txSign(detailTx) }}{{ fmtMoneyFull(detailTx.amount) }}
              </p>
              <p class="text-[10px] text-(--t-text-3) mt-1 tabular-nums">
                Баланс после: {{ fmtMoneyFull(detailTx.balanceAfter) }}
              </p>
            </div>

            <!-- Details grid -->
            <div class="rounded-xl bg-(--t-bg)/50 p-4 flex flex-col gap-3">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase">Детали</h4>

              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Описание</span>
                <span class="text-(--t-text) text-end max-w-[60%]">{{ detailTx.description }}</span>
              </div>

              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Дата</span>
                <span class="text-(--t-text) tabular-nums">{{ fmtDateTime(detailTx.createdAt) }}</span>
              </div>

              <div v-if="detailTx.gateway" class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Шлюз</span>
                <span class="text-(--t-text)">
                  {{ GATEWAY_ICONS[detailTx.gateway].icon }} {{ GATEWAY_ICONS[detailTx.gateway].label }}
                </span>
              </div>

              <div v-if="detailTx.counterparty" class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Контрагент</span>
                <span class="text-(--t-text) truncate max-w-[60%]">{{ detailTx.counterparty }}</span>
              </div>

              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Correlation ID</span>
                <span class="text-(--t-text-3) text-[9px] font-mono tabular-nums truncate max-w-[60%]">
                  {{ detailTx.correlationId }}
                </span>
              </div>

              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">ID транзакции</span>
                <span class="text-(--t-text-3) text-[9px] font-mono tabular-nums">
                  #{{ detailTx.id }}
                </span>
              </div>
            </div>

            <!-- Metadata -->
            <div v-if="detailTx.metadata && Object.keys(detailTx.metadata).length > 0"
                 class="rounded-xl bg-(--t-bg)/50 p-4">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase mb-3">Метаданные</h4>
              <div class="flex flex-col gap-2">
                <div v-for="(val, mkey) in detailTx.metadata" :key="String(mkey)"
                     class="flex justify-between text-xs">
                  <span class="text-(--t-text-3)">{{ mkey }}</span>
                  <span class="text-(--t-text-2) tabular-nums truncate max-w-[60%]">
                    {{ val }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer actions -->
          <div class="sticky inset-block-end-0 flex items-center gap-2 px-5 py-3
                      border-t border-(--t-border)/30 bg-(--t-surface)/90 backdrop-blur-xl">
            <button
              v-if="detailTx.status === 'failed'"
              class="relative overflow-hidden flex-1 flex items-center justify-center gap-1.5
                     py-2.5 rounded-xl text-xs font-semibold
                     bg-(--t-primary) text-white hover:brightness-110
                     active:scale-95 transition-all"
              @click="emit('retry-tx', detailTx!.id)" @mousedown="ripple"
            >🔄 Повторить</button>
            <button
              v-if="detailTx.status === 'pending' || detailTx.status === 'processing'"
              class="relative overflow-hidden flex-1 flex items-center justify-center gap-1.5
                     py-2.5 rounded-xl text-xs font-medium
                     border border-rose-500/25 text-rose-400
                     hover:bg-rose-500/10 active:scale-95 transition-all"
              @click="emit('cancel-tx', detailTx!.id)" @mousedown="ripple"
            >✕ Отменить</button>
            <button
              v-if="detailTx.status === 'completed'"
              class="relative overflow-hidden flex-1 flex items-center justify-center gap-1.5
                     py-2.5 rounded-xl text-xs font-medium
                     border border-(--t-border)/50 text-(--t-text-2)
                     hover:bg-(--t-card-hover) active:scale-95 transition-all"
              @click="doExport('pdf')" @mousedown="ripple"
            >📄 Квитанция</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════
         TOP-UP MODAL
    ══════════════════════════════════════ -->
    <Transition name="modal-py">
      <div v-if="showTopUpModal"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="showTopUpModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showTopUpModal = false" />

        <div class="relative z-10 inline-size-full max-w-sm bg-(--t-surface) rounded-2xl
                    border border-(--t-border)/60 shadow-2xl overflow-hidden">

          <!-- Header -->
          <div class="flex items-center justify-between px-5 py-4 border-b border-(--t-border)/30">
            <h3 class="text-sm font-bold text-(--t-text)">↑ Пополнение кошелька</h3>
            <button class="w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="showTopUpModal = false">✕</button>
          </div>

          <div class="p-5 flex flex-col gap-4">
            <!-- Amount -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Сумма пополнения</span>
              <input
                v-model.number="topUpAmount"
                type="number"
                min="100"
                step="100"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-sm font-bold text-(--t-text) tabular-nums
                       focus:outline-none focus:border-emerald-500/50 transition-colors"
              />
            </label>

            <!-- Quick amounts -->
            <div class="flex flex-wrap gap-2">
              <button
                v-for="amt in [500, 1000, 5000, 10000, 25000, 50000]" :key="amt"
                :class="[
                  'relative overflow-hidden px-3 py-1.5 rounded-xl text-[10px] font-medium transition-all active:scale-95',
                  topUpAmount === amt
                    ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30'
                    : 'border border-(--t-border)/40 text-(--t-text-3) hover:border-(--t-border)/60',
                ]"
                @click="topUpAmount = amt" @mousedown="ripple"
              >{{ fmtMoney(amt) }}</button>
            </div>

            <!-- Gateway -->
            <div>
              <p class="text-[10px] text-(--t-text-3) font-medium mb-2">Способ оплаты</p>
              <div class="flex flex-col gap-1.5">
                <button
                  v-for="gw in (['tinkoff', 'sber', 'sbp', 'tochka', 'card'] as Gateway[])" :key="gw"
                  :class="[
                    'relative overflow-hidden flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all',
                    topUpGateway === gw
                      ? 'bg-(--t-primary)/10 border border-(--t-primary)/30'
                      : 'border border-(--t-border)/30 hover:border-(--t-border)/50',
                  ]"
                  @click="topUpGateway = gw" @mousedown="ripple"
                >
                  <span class="shrink-0 text-base">{{ GATEWAY_ICONS[gw].icon }}</span>
                  <span class="flex-1 text-xs font-medium text-(--t-text) text-start">
                    {{ GATEWAY_ICONS[gw].label }}
                  </span>
                  <span v-if="topUpGateway === gw"
                        class="shrink-0 w-5 h-5 rounded-full bg-(--t-primary)/20
                               flex items-center justify-center text-[10px] text-(--t-primary)">✓</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex gap-2 px-5 py-4 border-t border-(--t-border)/30">
            <button
              class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-medium
                     border border-(--t-border)/50 text-(--t-text-3)
                     hover:bg-(--t-card-hover) active:scale-95 transition-all"
              @click="showTopUpModal = false" @mousedown="ripple"
            >Отмена</button>
            <button
              class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-semibold
                     bg-emerald-500 text-white hover:brightness-110 active:scale-95 transition-all"
              :disabled="topUpAmount < 100"
              @click="submitTopUp" @mousedown="ripple"
            >Пополнить {{ fmtMoney(topUpAmount) }}</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════
         WITHDRAW MODAL
    ══════════════════════════════════════ -->
    <Transition name="modal-py">
      <div v-if="showWithdrawModal"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="showWithdrawModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showWithdrawModal = false" />

        <div class="relative z-10 inline-size-full max-w-sm bg-(--t-surface) rounded-2xl
                    border border-(--t-border)/60 shadow-2xl overflow-hidden">

          <!-- Header -->
          <div class="flex items-center justify-between px-5 py-4 border-b border-(--t-border)/30">
            <h3 class="text-sm font-bold text-(--t-text)">↓ Вывод средств</h3>
            <button class="w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="showWithdrawModal = false">✕</button>
          </div>

          <div class="p-5 flex flex-col gap-4">

            <!-- Available -->
            <div class="rounded-xl bg-(--t-bg)/50 p-3 text-center">
              <p class="text-[10px] text-(--t-text-3)">Доступно для вывода</p>
              <p class="text-lg font-bold text-emerald-400 tabular-nums">
                {{ fmtMoneyFull(maxWithdraw) }}
              </p>
            </div>

            <!-- Amount -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Сумма вывода</span>
              <input
                v-model.number="withdrawAmount"
                type="number"
                min="100"
                :max="maxWithdraw"
                step="100"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-sm font-bold text-(--t-text) tabular-nums
                       focus:outline-none focus:border-rose-500/50 transition-colors"
              />
            </label>

            <!-- Bank account -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Расчётный счёт</span>
              <input
                v-model="withdrawAccount"
                type="text"
                placeholder="40702810..."
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>

            <!-- Description -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Назначение (необязательно)</span>
              <input
                v-model="withdrawDesc"
                type="text"
                placeholder="Вывод прибыли"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>

            <!-- Fraud warning -->
            <div class="rounded-xl bg-amber-500/8 border border-amber-500/20 p-3
                        flex items-start gap-2.5">
              <span class="shrink-0 text-sm">⚠️</span>
              <p class="text-[10px] text-amber-400/90 leading-relaxed">
                Вывод проходит проверку FraudControlService.
                Срок зачисления: 4–7 дней (B2C) или 7–14 дней (B2B).
              </p>
            </div>

            <!-- Confirm -->
            <label class="flex items-center gap-2.5 cursor-pointer">
              <input v-model="withdrawConfirm" type="checkbox"
                     class="shrink-0 w-4 h-4 rounded border border-(--t-border)/50
                            bg-(--t-bg)/60 accent-emerald-500" />
              <span class="text-[10px] text-(--t-text-3)">
                Подтверждаю вывод средств на указанный счёт
              </span>
            </label>
          </div>

          <!-- Footer -->
          <div class="flex gap-2 px-5 py-4 border-t border-(--t-border)/30">
            <button
              class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-medium
                     border border-(--t-border)/50 text-(--t-text-3)
                     hover:bg-(--t-card-hover) active:scale-95 transition-all"
              @click="showWithdrawModal = false" @mousedown="ripple"
            >Отмена</button>
            <button
              class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-semibold
                     bg-rose-500 text-white hover:brightness-110 active:scale-95 transition-all
                     disabled:opacity-40 disabled:pointer-events-none"
              :disabled="withdrawAmount < 100 || withdrawAmount > maxWithdraw || !withdrawAccount || !withdrawConfirm"
              @click="submitWithdraw" @mousedown="ripple"
            >Вывести {{ fmtMoney(withdrawAmount) }}</button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     STYLES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<style scoped>
/* Ripple — unique suffix py (Payments) */
@keyframes ripple-py {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* No scrollbar */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Sidebar transition */
.sb-py-enter-active,
.sb-py-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.sb-py-enter-from,
.sb-py-leave-to {
  opacity: 0;
  transform: translateX(12px);
}

/* Drawer transitions */
.dw-py-enter-active,
.dw-py-leave-active,
.detail-py-enter-active,
.detail-py-leave-active {
  transition: opacity 0.3s ease;
}
.dw-py-enter-active > :last-child,
.dw-py-leave-active > :last-child,
.detail-py-enter-active > :last-child,
.detail-py-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.dw-py-enter-from,
.dw-py-leave-to,
.detail-py-enter-from,
.detail-py-leave-to {
  opacity: 0;
}
.dw-py-enter-from > :last-child,
.dw-py-leave-to > :last-child,
.detail-py-enter-from > :last-child,
.detail-py-leave-to > :last-child {
  transform: translateX(100%);
}

/* Modal transition */
.modal-py-enter-active,
.modal-py-leave-active {
  transition: opacity 0.25s ease;
}
.modal-py-enter-active > :nth-child(2),
.modal-py-leave-active > :nth-child(2) {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}
.modal-py-enter-from,
.modal-py-leave-to {
  opacity: 0;
}
.modal-py-enter-from > :nth-child(2),
.modal-py-leave-to > :nth-child(2) {
  transform: scale(0.95) translateY(8px);
  opacity: 0;
}

/* Fade (export menu) */
.fade-py-enter-active,
.fade-py-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.fade-py-enter-from,
.fade-py-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
