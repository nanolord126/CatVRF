<script setup lang="ts">
/**
 * TenantLoyalty.vue — Программа лояльности, бонусы и кэшбэк в B2B Tenant Dashboard
 *
 * Вертикали:
 *   Beauty · Taxi · Food · Hotels · RealEstate · Flowers
 *   Fashion · Furniture · Fitness · Travel · default
 *
 * ───────────────────────────────────────────────────────────────
 *  Функционал:
 *   1.  Суммарный баланс бонусов бизнеса + кнопка «Создать акцию»
 *   2.  KPI-виджеты: активные клиенты, ср. LTV, конверсия бонусов,
 *       всего начислено, всего списано, кэшбэк %
 *   3.  Уровни лояльности (tier-карточки с прогрессом)
 *   4.  Таблица клиентов (desktop) / карточки (mobile) с tier,
 *       балансом, LTV, последним визитом
 *   5.  История операций (начисление / списание / сгорание / cashback)
 *       с фильтрами (тип · статус · клиент · поиск)
 *   6.  График динамики бонусной программы (placeholder bar chart)
 *   7.  Модал «Создать / Редактировать акцию»
 *   8.  Detail Drawer клиента (tier, история, рекомендации)
 *   9.  Sidebar: статистика по tier, top-клиенты, quick actions
 *  10.  Full-screen · mobile drawer · keyboard Esc · ripple-ly
 *  11.  Glassmorphism · dark theme · 2026 design
 * ───────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { useAuth, useTenant } from '@/stores'

/* ━━━━━━━━━━━━  TYPES  ━━━━━━━━━━━━ */

type TierKey   = 'bronze' | 'silver' | 'gold' | 'platinum' | 'vip'
type OpType    = 'accrual' | 'spending' | 'cashback' | 'expiry' | 'referral' | 'promo' | 'manual'
type OpStatus  = 'completed' | 'pending' | 'cancelled' | 'expired'
type SortKey   = 'name' | 'tier' | 'balance' | 'ltv' | 'lastVisit'
type SortDir   = 'asc' | 'desc'
type FilterOp  = OpType | 'all'
type FilterSt  = OpStatus | 'all'
type PromoType = 'cashback' | 'multiplier' | 'fixed_bonus' | 'free_item' | 'discount'

interface LoyaltyTier {
  key:          TierKey
  label:        string
  icon:         string
  minPoints:    number
  maxPoints:    number | null
  cashbackPct:  number
  color:        string
  dotCls:       string
  badgeCls:     string
  clientCount:  number
}

interface LoyaltyClient {
  id:           number | string
  name:         string
  avatar?:      string
  phone?:       string
  email?:       string
  tier:         TierKey
  bonusBalance: number
  totalEarned:  number
  totalSpent:   number
  ltv:          number
  visitsCount:  number
  lastVisit:    string
  registeredAt: string
  isB2B:        boolean
}

interface BonusOperation {
  id:            number | string
  type:          OpType
  status:        OpStatus
  amount:        number
  description:   string
  clientId:      number | string
  clientName:    string
  correlationId: string
  createdAt:     string
}

interface PromoAction {
  id:          number | string
  name:        string
  type:        PromoType
  value:       number
  startDate:   string
  endDate:     string
  isActive:    boolean
  usageCount:  number
  maxUsage:    number | null
  tierFilter:  TierKey[] | null
  description: string
}

interface ProgramStats {
  activeClients:      number
  avgLTV:             number
  bonusConversionPct: number
  totalIssued:        number
  totalRedeemed:      number
  avgCashbackPct:     number
  churnRiskCount:     number
  newThisMonth:       number
}

interface ChartPoint {
  date:     string
  accrued:  number
  redeemed: number
}

interface VerticalLoyaltyCfg {
  label:      string
  icon:       string
  accent:     string
  rewardNoun: string
  visitNoun:  string
}

/* ━━━━━━━━━━━━  PROPS / EMITS  ━━━━━━━━━━━━ */

const props = withDefaults(defineProps<{
  vertical?:    string
  tiers?:       LoyaltyTier[]
  clients?:     LoyaltyClient[]
  operations?:  BonusOperation[]
  promos?:      PromoAction[]
  stats?:       ProgramStats | null
  chartData?:   ChartPoint[]
  loading?:     boolean
  period?:      string
}>(), {
  vertical:   'default',
  tiers:      () => [],
  clients:    () => [],
  operations: () => [],
  promos:     () => [],
  stats:      null,
  chartData:  () => [],
  loading:    false,
  period:     '30d',
})

const emit = defineEmits<{
  'create-promo':     [data: Record<string, unknown>]
  'edit-promo':       [id: number | string]
  'toggle-promo':     [id: number | string, active: boolean]
  'delete-promo':     [id: number | string]
  'open-client':      [id: number | string]
  'adjust-balance':   [clientId: number | string, amount: number, reason: string]
  'change-tier':      [clientId: number | string, tier: TierKey]
  'export':           [format: 'csv' | 'xlsx' | 'pdf']
  'filter-change':    [filters: Record<string, string>]
  'sort-change':      [key: SortKey, dir: SortDir]
  'period-change':    [period: string]
  'refresh':          []
  'toggle-fullscreen': []
}>()

const auth = useAuth()
const biz  = useTenant()

/* ━━━━━━━━━━━━  VERTICAL CONFIG  ━━━━━━━━━━━━ */

const VERTICAL_CFG: Record<string, VerticalLoyaltyCfg> = {
  beauty:     { label: 'Салон красоты',   icon: '💄', accent: 'pink',    rewardNoun: 'за услуги',      visitNoun: 'визитов' },
  taxi:       { label: 'Такси',           icon: '🚕', accent: 'yellow',  rewardNoun: 'за поездки',     visitNoun: 'поездок' },
  food:       { label: 'Еда и рестораны', icon: '🍽️', accent: 'orange',  rewardNoun: 'за заказы',      visitNoun: 'заказов' },
  hotel:      { label: 'Отели',           icon: '🏨', accent: 'sky',     rewardNoun: 'за бронирования', visitNoun: 'ночей' },
  realEstate: { label: 'Недвижимость',    icon: '🏢', accent: 'emerald', rewardNoun: 'за сделки',      visitNoun: 'сделок' },
  flowers:    { label: 'Цветы',           icon: '💐', accent: 'rose',    rewardNoun: 'за букеты',      visitNoun: 'заказов' },
  fashion:    { label: 'Мода и одежда',   icon: '👗', accent: 'violet',  rewardNoun: 'за покупки',     visitNoun: 'покупок' },
  furniture:  { label: 'Мебель',          icon: '🛋️', accent: 'amber',   rewardNoun: 'за покупки',     visitNoun: 'заказов' },
  fitness:    { label: 'Фитнес',          icon: '💪', accent: 'lime',    rewardNoun: 'за тренировки',  visitNoun: 'тренировок' },
  travel:     { label: 'Путешествия',     icon: '✈️', accent: 'cyan',    rewardNoun: 'за туры',        visitNoun: 'туров' },
  default:    { label: 'Бизнес',          icon: '📊', accent: 'indigo',  rewardNoun: 'за операции',    visitNoun: 'операций' },
}

const vc = computed<VerticalLoyaltyCfg>(() => VERTICAL_CFG[props.vertical] ?? VERTICAL_CFG.default)

/* ━━━━━━━━━━━━  CONSTANTS  ━━━━━━━━━━━━ */

const TIER_META: Record<TierKey, { label: string; icon: string; color: string; badgeCls: string }> = {
  bronze:   { label: 'Бронза',   icon: '🥉', color: 'amber',   badgeCls: 'bg-amber-700/15 text-amber-500' },
  silver:   { label: 'Серебро',  icon: '🥈', color: 'zinc',    badgeCls: 'bg-zinc-400/15 text-zinc-300' },
  gold:     { label: 'Золото',   icon: '🥇', color: 'yellow',  badgeCls: 'bg-yellow-500/15 text-yellow-400' },
  platinum: { label: 'Платина',  icon: '💎', color: 'sky',     badgeCls: 'bg-sky-500/15 text-sky-400' },
  vip:      { label: 'VIP',      icon: '👑', color: 'violet',  badgeCls: 'bg-violet-500/15 text-violet-400' },
}

const OP_META: Record<OpType, { label: string; icon: string; cls: string; sign: '+' | '-' | '~' }> = {
  accrual:  { label: 'Начисление', icon: '💰', cls: 'text-emerald-400', sign: '+' },
  spending: { label: 'Списание',   icon: '🛒', cls: 'text-rose-400',    sign: '-' },
  cashback: { label: 'Кэшбэк',    icon: '💸', cls: 'text-sky-400',     sign: '+' },
  expiry:   { label: 'Сгорание',   icon: '🔥', cls: 'text-amber-400',   sign: '-' },
  referral: { label: 'Реферал',    icon: '🤝', cls: 'text-violet-400',  sign: '+' },
  promo:    { label: 'Акция',      icon: '🎉', cls: 'text-pink-400',    sign: '+' },
  manual:   { label: 'Ручное',     icon: '✏️', cls: 'text-zinc-400',    sign: '~' },
}

const OP_STATUS_META: Record<OpStatus, { label: string; dot: string; cls: string }> = {
  completed: { label: 'Выполнена', dot: 'bg-emerald-500', cls: 'bg-emerald-500/12 text-emerald-400' },
  pending:   { label: 'Ожидает',   dot: 'bg-amber-500',   cls: 'bg-amber-500/12 text-amber-400' },
  cancelled: { label: 'Отменена',  dot: 'bg-zinc-500',    cls: 'bg-zinc-500/12 text-zinc-400' },
  expired:   { label: 'Истекла',   dot: 'bg-rose-500',    cls: 'bg-rose-500/12 text-rose-400' },
}

const PROMO_TYPE_META: Record<PromoType, { label: string; icon: string }> = {
  cashback:    { label: 'Кэшбэк',        icon: '💸' },
  multiplier:  { label: 'Множитель',      icon: '✖️' },
  fixed_bonus: { label: 'Фикс. бонус',   icon: '🎁' },
  free_item:   { label: 'Бесплатный',     icon: '🆓' },
  discount:    { label: 'Скидка',         icon: '🏷️' },
}

const PERIODS: Array<{ key: string; label: string }> = [
  { key: '7d',  label: '7 дней' },
  { key: '30d', label: '30 дней' },
  { key: '90d', label: '90 дней' },
  { key: '1y',  label: 'Год' },
  { key: 'all', label: 'Всё время' },
]

const OP_FILTER_TYPES: Array<{ key: FilterOp; label: string }> = [
  { key: 'all',      label: 'Все' },
  { key: 'accrual',  label: '💰 Начисления' },
  { key: 'spending', label: '🛒 Списания' },
  { key: 'cashback', label: '💸 Кэшбэк' },
  { key: 'expiry',   label: '🔥 Сгорания' },
  { key: 'referral', label: '🤝 Рефералы' },
  { key: 'promo',    label: '🎉 Акции' },
  { key: 'manual',   label: '✏️ Ручные' },
]

const STATUS_FILTER_OPTIONS: Array<{ key: FilterSt; label: string }> = [
  { key: 'all',       label: 'Все статусы' },
  { key: 'completed', label: 'Выполнены' },
  { key: 'pending',   label: 'Ожидают' },
  { key: 'cancelled', label: 'Отменены' },
  { key: 'expired',   label: 'Истекли' },
]

const CLIENT_SORT_COLS: Array<{ key: SortKey; label: string }> = [
  { key: 'name',      label: 'Имя' },
  { key: 'tier',      label: 'Уровень' },
  { key: 'balance',   label: 'Баланс' },
  { key: 'ltv',       label: 'LTV' },
  { key: 'lastVisit', label: 'Последний визит' },
]

const TIER_ORDER: Record<TierKey, number> = { bronze: 1, silver: 2, gold: 3, platinum: 4, vip: 5 }

/* ━━━━━━━━━━━━  STATE  ━━━━━━━━━━━━ */

const rootEl              = ref<HTMLElement | null>(null)
const isFullscreen        = ref(false)
const searchQuery         = ref('')
const clientSearch        = ref('')
const filterOpType        = ref<FilterOp>('all')
const filterOpStatus      = ref<FilterSt>('all')
const selectedPeriod      = ref(props.period)
const sortKey             = ref<SortKey>('balance')
const sortDir             = ref<SortDir>('desc')
const activeTab           = ref<'clients' | 'operations' | 'promos'>('clients')
const showSidebar         = ref(true)
const showMobileSidebar   = ref(false)
const showPromoModal      = ref(false)
const showClientDrawer    = ref(false)
const showAdjustModal     = ref(false)
const showExportMenu      = ref(false)
const detailClient        = ref<LoyaltyClient | null>(null)
const refreshing          = ref(false)

/* ── Promo form ── */
const promoForm = reactive<{
  name: string; type: PromoType; value: number
  startDate: string; endDate: string
  description: string; tierFilter: TierKey[]
  maxUsage: number | null
}>({
  name: '', type: 'cashback', value: 5,
  startDate: '', endDate: '', description: '',
  tierFilter: [], maxUsage: null,
})

/* ── Adjust form ── */
const adjustAmount = ref(0)
const adjustReason = ref('')

/* ━━━━━━━━━━━━  COMPUTED  ━━━━━━━━━━━━ */

const pStats = computed<ProgramStats>(() =>
  props.stats ?? {
    activeClients: 0, avgLTV: 0, bonusConversionPct: 0,
    totalIssued: 0, totalRedeemed: 0, avgCashbackPct: 0,
    churnRiskCount: 0, newThisMonth: 0,
  },
)

const totalBonusPool = computed(() =>
  props.clients.reduce((s, c) => s + c.bonusBalance, 0),
)

/* ── Filtered + sorted clients ── */
const filteredClients = computed<LoyaltyClient[]>(() => {
  let list = [...props.clients]

  if (clientSearch.value.trim()) {
    const q = clientSearch.value.trim().toLowerCase()
    list = list.filter(
      (c) => c.name.toLowerCase().includes(q)
           || (c.phone ?? '').includes(q)
           || (c.email ?? '').toLowerCase().includes(q),
    )
  }

  list.sort((a, b) => {
    let cmp = 0
    switch (sortKey.value) {
      case 'name':      cmp = a.name.localeCompare(b.name); break
      case 'tier':      cmp = TIER_ORDER[a.tier] - TIER_ORDER[b.tier]; break
      case 'balance':   cmp = a.bonusBalance - b.bonusBalance; break
      case 'ltv':       cmp = a.ltv - b.ltv; break
      case 'lastVisit': cmp = new Date(a.lastVisit).getTime() - new Date(b.lastVisit).getTime(); break
    }
    return sortDir.value === 'asc' ? cmp : -cmp
  })

  return list
})

/* ── Filtered operations ── */
const filteredOps = computed<BonusOperation[]>(() => {
  let list = [...props.operations]

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase()
    list = list.filter(
      (op) => op.description.toLowerCase().includes(q)
            || op.clientName.toLowerCase().includes(q)
            || op.correlationId.toLowerCase().includes(q),
    )
  }

  if (filterOpType.value !== 'all') {
    list = list.filter((op) => op.type === filterOpType.value)
  }

  if (filterOpStatus.value !== 'all') {
    list = list.filter((op) => op.status === filterOpStatus.value)
  }

  list.sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())

  return list
})

const activePromos = computed(() => props.promos.filter((p) => p.isActive))

const activeFiltersCount = computed(() => {
  let c = 0
  if (filterOpType.value !== 'all') c++
  if (filterOpStatus.value !== 'all') c++
  if (searchQuery.value.trim()) c++
  return c
})

const maxChartVal = computed(() => {
  let m = 1
  for (const d of props.chartData) {
    if (d.accrued > m)  m = d.accrued
    if (d.redeemed > m) m = d.redeemed
  }
  return m
})

/* ── Tier distribution for sidebar ── */
const tierDistribution = computed(() => {
  const map: Record<TierKey, number> = { bronze: 0, silver: 0, gold: 0, platinum: 0, vip: 0 }
  for (const c of props.clients) {
    map[c.tier] = (map[c.tier] || 0) + 1
  }
  const total = Math.max(1, props.clients.length)
  return (Object.entries(map) as [TierKey, number][]).map(([key, count]) => ({
    key,
    count,
    pct: Math.round((count / total) * 100),
    ...TIER_META[key],
  }))
})

const topClients = computed(() =>
  [...props.clients].sort((a, b) => b.ltv - a.ltv).slice(0, 5),
)

/* ━━━━━━━━━━━━  HELPERS  ━━━━━━━━━━━━ */

function fmtMoney(n: number): string {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(2)}M`
  if (n >= 1_000)     return `${(n / 1_000).toFixed(1)}K`
  return n.toLocaleString('ru-RU')
}

function fmtPts(n: number): string {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M б.`
  if (n >= 1_000)     return `${(n / 1_000).toFixed(1)}K б.`
  return `${n.toLocaleString('ru-RU')} б.`
}

function fmtNum(n: number): string {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M`
  if (n >= 1_000)     return `${(n / 1_000).toFixed(1)}K`
  return String(n)
}

function fmtPct(n: number): string {
  return `${n.toFixed(1)}%`
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

function opSign(op: BonusOperation): string {
  const meta = OP_META[op.type]
  if (meta.sign === '+') return '+'
  if (meta.sign === '-') return '−'
  return ''
}

function opAmountCls(op: BonusOperation): string {
  const meta = OP_META[op.type]
  if (meta.sign === '+') return 'text-emerald-400'
  if (meta.sign === '-') return 'text-rose-400'
  return 'text-(--t-text-2)'
}

function clientInitials(name: string): string {
  return name.split(' ').slice(0, 2).map((w) => w[0] ?? '').join('').toUpperCase()
}

function daysAgo(d: string): string {
  if (!d) return '—'
  const diff = Math.floor((Date.now() - new Date(d).getTime()) / 86400000)
  if (diff === 0) return 'сегодня'
  if (diff === 1) return 'вчера'
  if (diff < 7)   return `${diff} дн. назад`
  if (diff < 30)  return `${Math.floor(diff / 7)} нед. назад`
  return fmtDate(d)
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
  filterOpType.value = 'all'
  filterOpStatus.value = 'all'
}

function openPromoModal() {
  promoForm.name = ''
  promoForm.type = 'cashback'
  promoForm.value = 5
  promoForm.startDate = ''
  promoForm.endDate = ''
  promoForm.description = ''
  promoForm.tierFilter = []
  promoForm.maxUsage = null
  showPromoModal.value = true
}

function submitPromo() {
  emit('create-promo', { ...promoForm })
  showPromoModal.value = false
}

function openClientDetail(client: LoyaltyClient) {
  detailClient.value = client
  showClientDrawer.value = true
}

function closeClientDrawer() {
  showClientDrawer.value = false
  detailClient.value = null
}

function openAdjust(client: LoyaltyClient) {
  detailClient.value = client
  adjustAmount.value = 0
  adjustReason.value = ''
  showAdjustModal.value = true
}

function submitAdjust() {
  if (detailClient.value) {
    emit('adjust-balance', detailClient.value.id, adjustAmount.value, adjustReason.value)
  }
  showAdjustModal.value = false
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
    if (showPromoModal.value)     { showPromoModal.value = false; return }
    if (showAdjustModal.value)    { showAdjustModal.value = false; return }
    if (showClientDrawer.value)   { closeClientDrawer(); return }
    if (showMobileSidebar.value)  { showMobileSidebar.value = false; return }
    if (showExportMenu.value)     { showExportMenu.value = false; return }
    if (isFullscreen.value)       { toggleFullscreen(); return }
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
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-ly_0.6s_ease-out]'
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
          <span class="text-2xl">🎖️</span>
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <h1 class="text-base sm:text-lg font-extrabold text-(--t-text) truncate leading-snug">
                Программа лояльности
              </h1>
              <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold
                           bg-violet-500/15 text-violet-400 tabular-nums">
                {{ fmtPts(totalBonusPool) }} в обороте
              </span>
            </div>
            <p class="text-[10px] text-(--t-text-3) truncate">
              {{ vc.icon }} {{ vc.label }} · Бонусы {{ vc.rewardNoun }}
            </p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 flex-wrap">

          <!-- Create promo -->
          <button
            class="relative overflow-hidden flex items-center gap-1.5 px-4 py-2 rounded-xl
                   text-xs font-semibold bg-(--t-primary) text-white hover:brightness-110
                   active:scale-95 transition-all"
            @click="openPromoModal" @mousedown="ripple"
          >
            <span class="text-sm">+</span>
            <span>Создать акцию</span>
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
            <Transition name="fade-ly">
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

        <!-- ── KPI GRID ── -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
          <div v-for="kpi in [
            { label: 'Участники',    value: fmtNum(pStats.activeClients),     icon: '👥', cls: 'text-sky-400' },
            { label: 'Ср. LTV',      value: fmtMoney(pStats.avgLTV) + ' ₽',  icon: '💎', cls: 'text-violet-400' },
            { label: 'Конверсия',    value: fmtPct(pStats.bonusConversionPct), icon: '📈', cls: 'text-emerald-400' },
            { label: 'Начислено',    value: fmtPts(pStats.totalIssued),       icon: '💰', cls: 'text-emerald-400' },
            { label: 'Списано',      value: fmtPts(pStats.totalRedeemed),     icon: '🛒', cls: 'text-rose-400' },
            { label: 'Ср. кэшбэк',  value: fmtPct(pStats.avgCashbackPct),    icon: '💸', cls: 'text-sky-400' },
            { label: 'Риск оттока',  value: fmtNum(pStats.churnRiskCount),    icon: '⚠️', cls: 'text-amber-400' },
            { label: 'Новых за мес.', value: fmtNum(pStats.newThisMonth),     icon: '🆕', cls: 'text-pink-400' },
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

        <!-- ── TIER CARDS ── -->
        <div class="flex flex-col gap-2">
          <h3 class="text-xs font-bold text-(--t-text)">🎖️ Уровни лояльности</h3>
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5">
            <div v-for="tier in props.tiers" :key="tier.key"
                 class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                        backdrop-blur-sm p-3.5 hover:border-(--t-border)/60
                        hover:shadow-lg hover:shadow-black/5 transition-all">
              <div class="flex items-center gap-2 mb-2.5">
                <span class="text-lg">{{ TIER_META[tier.key].icon }}</span>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-bold text-(--t-text) truncate">
                    {{ TIER_META[tier.key].label }}
                  </p>
                  <p class="text-[9px] text-(--t-text-3)">
                    {{ tier.cashbackPct }}% кэшбэк
                  </p>
                </div>
              </div>
              <div class="flex items-center justify-between mb-1.5">
                <span class="text-[9px] text-(--t-text-3)">Клиентов</span>
                <span class="text-xs font-bold text-(--t-text) tabular-nums">
                  {{ tier.clientCount }}
                </span>
              </div>
              <div class="h-1.5 rounded-full bg-(--t-border)/20 overflow-hidden">
                <div
                  class="h-full rounded-full bg-violet-500/70 transition-all"
                  :style="{
                    inlineSize: `${Math.min(100, Math.max(3,
                      (tier.clientCount / Math.max(1, props.clients.length)) * 100
                    ))}%`,
                  }"
                />
              </div>
              <p class="text-[8px] text-(--t-text-3) mt-1.5 tabular-nums">
                {{ fmtPts(tier.minPoints) }}
                {{ tier.maxPoints ? '– ' + fmtPts(tier.maxPoints) : '+' }}
              </p>
            </div>
          </div>
        </div>

        <!-- ── TAB SWITCHER ── -->
        <div class="flex items-center gap-1 p-1 rounded-xl bg-(--t-surface)/40
                    border border-(--t-border)/30 self-start">
          <button
            v-for="tab in ([
              { key: 'clients',    label: '👥 Клиенты',   count: filteredClients.length },
              { key: 'operations', label: '📋 Операции',   count: filteredOps.length },
              { key: 'promos',     label: '🎉 Акции',      count: props.promos.length },
            ] as const)" :key="tab.key"
            :class="[
              'relative overflow-hidden flex items-center gap-1.5 px-3 py-2 rounded-lg',
              'text-xs font-medium transition-all',
              activeTab === tab.key
                ? 'bg-(--t-primary)/15 text-(--t-primary)'
                : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
            ]"
            @click="activeTab = tab.key" @mousedown="ripple"
          >
            {{ tab.label }}
            <span class="tabular-nums text-[9px] opacity-60">({{ tab.count }})</span>
          </button>
        </div>

        <!-- ──────────────── TAB: CLIENTS ──────────────── -->
        <template v-if="activeTab === 'clients'">

          <!-- Search + sort -->
          <div class="flex items-center gap-2">
            <div class="relative flex-1 max-w-xs">
              <input
                v-model="clientSearch"
                type="text"
                placeholder="Поиск клиента…"
                class="inline-size-full py-1.5 ps-8 pe-3 rounded-xl border border-(--t-border)/50
                       bg-(--t-bg)/60 text-xs text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
              <span class="absolute inset-inline-start-2.5 inset-block-start-1/2 -translate-y-1/2
                           text-xs text-(--t-text-3) pointer-events-none">🔍</span>
            </div>
            <span class="text-[10px] text-(--t-text-3) tabular-nums">
              {{ filteredClients.length }} из {{ props.clients.length }}
            </span>
          </div>

          <!-- Loading -->
          <div v-if="props.loading && filteredClients.length === 0" class="flex flex-col gap-2.5">
            <div v-for="n in 4" :key="n"
                 class="flex items-center gap-3 p-4 rounded-2xl border border-(--t-border)/20
                        bg-(--t-surface)/30 animate-pulse">
              <div class="shrink-0 w-10 h-10 rounded-full bg-(--t-border)/30" />
              <div class="flex-1">
                <div class="h-3 w-36 bg-(--t-border)/30 rounded mb-2" />
                <div class="h-2.5 w-48 bg-(--t-border)/20 rounded" />
              </div>
              <div class="shrink-0 h-4 w-16 bg-(--t-border)/20 rounded-lg" />
            </div>
          </div>

          <!-- Empty -->
          <div v-else-if="filteredClients.length === 0 && !props.loading"
               class="py-16 text-center">
            <p class="text-5xl mb-3">👥</p>
            <p class="text-sm font-semibold text-(--t-text-2)">Клиенты не найдены</p>
            <p class="text-[10px] text-(--t-text-3) mt-1">Попробуйте изменить поиск</p>
          </div>

          <!-- Table (desktop) -->
          <div v-else class="hidden md:block rounded-2xl border border-(--t-border)/30
                            bg-(--t-surface)/40 backdrop-blur-sm overflow-hidden">
            <div class="overflow-x-auto">
              <table class="inline-size-full text-xs">
                <thead>
                  <tr class="border-b border-(--t-border)/30">
                    <th v-for="col in CLIENT_SORT_COLS" :key="col.key"
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
                               uppercase tracking-wider">{{ vc.visitNoun }}</th>
                    <th class="px-4 py-3 text-end text-[10px] font-bold text-(--t-text-3)
                               uppercase tracking-wider">B2B</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="cl in filteredClients" :key="cl.id"
                      class="group/row border-b border-(--t-border)/15 hover:bg-(--t-card-hover)/40
                             transition-colors cursor-pointer"
                      @click="openClientDetail(cl)">

                    <!-- Name -->
                    <td class="px-4 py-3.5">
                      <div class="flex items-center gap-2.5">
                        <div v-if="cl.avatar"
                             class="shrink-0 w-8 h-8 rounded-full bg-(--t-border)/30 overflow-hidden">
                          <img :src="cl.avatar" :alt="cl.name"
                               class="w-full h-full object-cover" />
                        </div>
                        <div v-else
                             class="shrink-0 w-8 h-8 rounded-full bg-(--t-primary)/15
                                    flex items-center justify-center text-[10px] font-bold
                                    text-(--t-primary)">
                          {{ clientInitials(cl.name) }}
                        </div>
                        <div class="min-w-0">
                          <p class="text-xs font-medium text-(--t-text) truncate">{{ cl.name }}</p>
                          <p v-if="cl.phone" class="text-[9px] text-(--t-text-3)">{{ cl.phone }}</p>
                        </div>
                      </div>
                    </td>

                    <!-- Tier -->
                    <td class="px-4 py-3.5">
                      <span :class="[
                        'inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-medium',
                        TIER_META[cl.tier].badgeCls,
                      ]">
                        {{ TIER_META[cl.tier].icon }} {{ TIER_META[cl.tier].label }}
                      </span>
                    </td>

                    <!-- Balance -->
                    <td class="px-4 py-3.5 text-xs font-bold text-violet-400 tabular-nums">
                      {{ fmtPts(cl.bonusBalance) }}
                    </td>

                    <!-- LTV -->
                    <td class="px-4 py-3.5 text-xs text-(--t-text-2) tabular-nums">
                      {{ fmtMoney(cl.ltv) }} ₽
                    </td>

                    <!-- Last visit -->
                    <td class="px-4 py-3.5 text-[10px] text-(--t-text-3) tabular-nums">
                      {{ daysAgo(cl.lastVisit) }}
                    </td>

                    <!-- Visits -->
                    <td class="px-4 py-3.5 text-[10px] text-(--t-text-3) tabular-nums">
                      {{ cl.visitsCount }}
                    </td>

                    <!-- B2B badge -->
                    <td class="px-4 py-3.5 text-end">
                      <span v-if="cl.isB2B"
                            class="px-1.5 py-0.5 rounded text-[8px] font-bold
                                   bg-sky-500/12 text-sky-400">B2B</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Cards (mobile) -->
          <div class="md:hidden flex flex-col gap-2.5">
            <button
              v-for="cl in filteredClients" :key="cl.id"
              class="group/card relative overflow-hidden text-start rounded-2xl
                     border border-(--t-border)/30 bg-(--t-surface)/50 backdrop-blur-sm
                     hover:border-(--t-border)/60 hover:shadow-lg hover:shadow-black/5
                     active:scale-[0.98] transition-all p-4"
              @click="openClientDetail(cl)" @mousedown="ripple"
            >
              <div class="flex items-center gap-3">
                <div v-if="cl.avatar"
                     class="shrink-0 w-10 h-10 rounded-full overflow-hidden bg-(--t-border)/30">
                  <img :src="cl.avatar" :alt="cl.name" class="w-full h-full object-cover" />
                </div>
                <div v-else
                     class="shrink-0 w-10 h-10 rounded-full bg-(--t-primary)/15
                            flex items-center justify-center text-xs font-bold text-(--t-primary)">
                  {{ clientInitials(cl.name) }}
                </div>

                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-(--t-text) truncate">{{ cl.name }}</span>
                    <span v-if="cl.isB2B"
                          class="shrink-0 px-1 py-px rounded text-[7px] font-bold
                                 bg-sky-500/12 text-sky-400">B2B</span>
                  </div>
                  <div class="flex items-center gap-2 mt-1">
                    <span :class="[
                      'inline-flex items-center gap-0.5 px-1.5 py-px rounded-md text-[8px] font-medium',
                      TIER_META[cl.tier].badgeCls,
                    ]">
                      {{ TIER_META[cl.tier].icon }} {{ TIER_META[cl.tier].label }}
                    </span>
                    <span class="text-[9px] text-(--t-text-3) tabular-nums">
                      {{ daysAgo(cl.lastVisit) }}
                    </span>
                  </div>
                </div>

                <div class="shrink-0 text-end">
                  <p class="text-xs font-bold text-violet-400 tabular-nums">{{ fmtPts(cl.bonusBalance) }}</p>
                  <p class="text-[9px] text-(--t-text-3) tabular-nums">LTV {{ fmtMoney(cl.ltv) }} ₽</p>
                </div>
              </div>
            </button>
          </div>
        </template>

        <!-- ──────────────── TAB: OPERATIONS ──────────────── -->
        <template v-if="activeTab === 'operations'">

          <!-- Filters -->
          <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
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

            <select
              v-model="filterOpType"
              class="shrink-0 py-1.5 px-2.5 rounded-xl border border-(--t-border)/50
                     bg-(--t-bg)/60 text-xs text-(--t-text) focus:outline-none
                     focus:border-(--t-primary)/50 transition-colors appearance-none cursor-pointer"
            >
              <option v-for="t in OP_FILTER_TYPES" :key="t.key" :value="t.key">{{ t.label }}</option>
            </select>

            <select
              v-model="filterOpStatus"
              class="shrink-0 py-1.5 px-2.5 rounded-xl border border-(--t-border)/50
                     bg-(--t-bg)/60 text-xs text-(--t-text) focus:outline-none
                     focus:border-(--t-primary)/50 transition-colors appearance-none cursor-pointer"
            >
              <option v-for="s in STATUS_FILTER_OPTIONS" :key="s.key" :value="s.key">{{ s.label }}</option>
            </select>

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

          <!-- Operations list -->
          <div v-if="props.loading && filteredOps.length === 0" class="flex flex-col gap-2.5">
            <div v-for="n in 4" :key="n"
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

          <div v-else-if="filteredOps.length === 0 && !props.loading"
               class="py-16 text-center">
            <p class="text-5xl mb-3">📋</p>
            <p class="text-sm font-semibold text-(--t-text-2)">
              {{ activeFiltersCount > 0 ? 'Операции не найдены' : 'Нет операций' }}
            </p>
            <p class="text-[10px] text-(--t-text-3) mt-1">
              {{ activeFiltersCount > 0 ? 'Попробуйте изменить фильтры' : 'Создайте акцию для начала' }}
            </p>
          </div>

          <div v-else class="flex flex-col gap-2">
            <button
              v-for="op in filteredOps" :key="op.id"
              class="group/op relative overflow-hidden text-start rounded-2xl
                     border border-(--t-border)/25 bg-(--t-surface)/40 backdrop-blur-sm
                     hover:border-(--t-border)/50 hover:shadow-lg hover:shadow-black/5
                     active:scale-[0.99] transition-all p-4"
              @mousedown="ripple"
            >
              <div class="flex items-center gap-3">
                <div :class="[
                  'shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-base',
                  OP_META[op.type].cls, 'bg-current/10',
                ]">{{ OP_META[op.type].icon }}</div>

                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-(--t-text) truncate">
                      {{ OP_META[op.type].label }}
                    </span>
                    <span :class="['text-xs font-bold tabular-nums shrink-0', opAmountCls(op)]">
                      {{ opSign(op) }}{{ fmtPts(op.amount) }}
                    </span>
                  </div>
                  <p class="text-[10px] text-(--t-text-3) truncate mt-0.5">
                    {{ op.clientName }} · {{ op.description }}
                  </p>
                  <div class="flex items-center gap-2 mt-1.5">
                    <span :class="[
                      'inline-flex items-center gap-1 px-1.5 py-px rounded-md text-[8px] font-medium',
                      OP_STATUS_META[op.status].cls,
                    ]">
                      <span :class="['w-1 h-1 rounded-full', OP_STATUS_META[op.status].dot]" />
                      {{ OP_STATUS_META[op.status].label }}
                    </span>
                    <span class="text-[9px] text-(--t-text-3) tabular-nums">
                      {{ fmtDate(op.createdAt) }} {{ fmtTime(op.createdAt) }}
                    </span>
                  </div>
                </div>
              </div>
            </button>
          </div>
        </template>

        <!-- ──────────────── TAB: PROMOS ──────────────── -->
        <template v-if="activeTab === 'promos'">

          <div v-if="props.promos.length === 0" class="py-16 text-center">
            <p class="text-5xl mb-3">🎉</p>
            <p class="text-sm font-semibold text-(--t-text-2)">Нет акций</p>
            <p class="text-[10px] text-(--t-text-3) mt-1">Создайте первую бонусную акцию</p>
            <button
              class="relative overflow-hidden mt-4 px-5 py-2 rounded-xl text-xs font-semibold
                     bg-(--t-primary) text-white hover:brightness-110
                     active:scale-95 transition-all"
              @click="openPromoModal" @mousedown="ripple"
            >+ Создать акцию</button>
          </div>

          <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="promo in props.promos" :key="promo.id"
                 :class="[
                   'rounded-2xl border bg-(--t-surface)/40 backdrop-blur-sm p-4',
                   'hover:shadow-lg hover:shadow-black/5 transition-all',
                   promo.isActive
                     ? 'border-emerald-500/30'
                     : 'border-(--t-border)/25 opacity-60',
                 ]">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                  <span class="text-base">{{ PROMO_TYPE_META[promo.type].icon }}</span>
                  <span class="text-xs font-bold text-(--t-text) truncate">{{ promo.name }}</span>
                </div>
                <button
                  :class="[
                    'shrink-0 w-9 h-5 rounded-full transition-colors relative',
                    promo.isActive ? 'bg-emerald-500' : 'bg-(--t-border)/40',
                  ]"
                  @click="emit('toggle-promo', promo.id, !promo.isActive)"
                >
                  <span
                    :class="[
                      'absolute inset-block-start-0.5 w-4 h-4 rounded-full bg-white transition-all',
                      promo.isActive ? 'inset-inline-start-[18px]' : 'inset-inline-start-0.5',
                    ]"
                  />
                </button>
              </div>

              <p class="text-[10px] text-(--t-text-3) mb-3 line-clamp-2">{{ promo.description }}</p>

              <div class="flex items-center gap-3 mb-2">
                <span class="text-[10px] text-(--t-text-3)">
                  {{ PROMO_TYPE_META[promo.type].label }}:
                  <span class="font-bold text-(--t-text) tabular-nums">
                    {{ promo.type === 'cashback' || promo.type === 'discount' || promo.type === 'multiplier'
                         ? promo.value + '%'
                         : fmtPts(promo.value) }}
                  </span>
                </span>
              </div>

              <div class="flex items-center justify-between text-[9px] text-(--t-text-3)">
                <span class="tabular-nums">
                  {{ fmtDate(promo.startDate) }} – {{ fmtDate(promo.endDate) }}
                </span>
                <span class="tabular-nums">
                  {{ promo.usageCount }}{{ promo.maxUsage ? ' / ' + promo.maxUsage : '' }} исп.
                </span>
              </div>

              <div v-if="promo.tierFilter && promo.tierFilter.length > 0"
                   class="flex items-center gap-1 mt-2.5 flex-wrap">
                <span v-for="t in promo.tierFilter" :key="t"
                      :class="[
                        'px-1.5 py-px rounded text-[7px] font-medium',
                        TIER_META[t].badgeCls,
                      ]">{{ TIER_META[t].icon }} {{ TIER_META[t].label }}</span>
              </div>
            </div>
          </div>
        </template>

        <!-- ── CHART ── -->
        <div v-if="props.chartData.length > 0"
             class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                    backdrop-blur-sm p-4 sm:p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-bold text-(--t-text)">📈 Динамика бонусной программы</h3>
            <div class="flex items-center gap-4 text-[10px]">
              <span class="flex items-center gap-1.5">
                <span class="w-2.5 h-1.5 rounded-full bg-emerald-500" /> Начислено
              </span>
              <span class="flex items-center gap-1.5">
                <span class="w-2.5 h-1.5 rounded-full bg-rose-500" /> Списано
              </span>
            </div>
          </div>
          <div class="flex items-end gap-1.5" style="block-size: 140px">
            <div v-for="(bar, idx) in props.chartData" :key="idx"
                 class="flex-1 flex items-end gap-px" style="min-inline-size: 0">
              <div
                class="flex-1 rounded-t-sm bg-emerald-500/70 transition-all"
                :style="{ blockSize: `${Math.max(2, (bar.accrued / maxChartVal) * 100)}%` }"
                :title="`${fmtDate(bar.date)}: +${fmtPts(bar.accrued)}`"
              />
              <div
                class="flex-1 rounded-t-sm bg-rose-500/50 transition-all"
                :style="{ blockSize: `${Math.max(2, (bar.redeemed / maxChartVal) * 100)}%` }"
                :title="`${fmtDate(bar.date)}: −${fmtPts(bar.redeemed)}`"
              />
            </div>
          </div>
          <div class="flex items-center justify-between mt-2">
            <span v-for="(bar, idx) in [
              props.chartData[0],
              props.chartData[Math.floor(props.chartData.length / 2)],
              props.chartData[props.chartData.length - 1],
            ]" :key="idx"
                 class="text-[8px] text-(--t-text-3) tabular-nums">
              {{ bar ? fmtDate(bar.date) : '' }}
            </span>
          </div>
        </div>
      </div>

      <!-- ═══ SIDEBAR (desktop) ═══ -->
      <Transition name="sb-ly">
        <aside v-if="showSidebar"
               class="hidden xl:flex shrink-0 flex-col gap-4 w-72">

          <!-- Tier distribution -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              Распределение по уровням
            </h3>
            <div class="flex flex-col gap-2.5">
              <div v-for="td in tierDistribution" :key="td.key"
                   class="flex items-center gap-2.5">
                <span class="shrink-0 text-sm">{{ td.icon }}</span>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] font-medium text-(--t-text-2)">{{ td.label }}</span>
                    <span class="text-[10px] text-(--t-text-3) tabular-nums">
                      {{ td.count }} ({{ td.pct }}%)
                    </span>
                  </div>
                  <div class="h-1.5 rounded-full bg-(--t-border)/20 overflow-hidden">
                    <div
                      class="h-full rounded-full bg-violet-500/60 transition-all"
                      :style="{ inlineSize: `${Math.max(2, td.pct)}%` }"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Active promos summary -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              🎉 Активные акции ({{ activePromos.length }})
            </h3>
            <div v-if="activePromos.length === 0"
                 class="text-center py-4">
              <p class="text-[10px] text-(--t-text-3)">Нет активных акций</p>
            </div>
            <div v-else class="flex flex-col gap-1.5">
              <div v-for="ap in activePromos.slice(0, 4)" :key="ap.id"
                   class="flex items-center gap-2 px-2.5 py-2 rounded-xl bg-(--t-bg)/40">
                <span class="shrink-0 text-xs">{{ PROMO_TYPE_META[ap.type].icon }}</span>
                <span class="flex-1 text-[10px] text-(--t-text-2) truncate">{{ ap.name }}</span>
                <span class="shrink-0 text-[9px] text-(--t-text-3) tabular-nums">
                  {{ ap.usageCount }} исп.
                </span>
              </div>
            </div>
          </div>

          <!-- Top clients -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-3">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-2 px-1">
              👑 Топ клиенты по LTV
            </h3>
            <div class="flex flex-col gap-0.5">
              <button
                v-for="(cl, idx) in topClients" :key="cl.id"
                class="relative overflow-hidden flex items-center gap-2.5 px-3 py-2 rounded-xl
                       text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)
                       active:scale-[0.97] transition-all text-start"
                @click="openClientDetail(cl)" @mousedown="ripple"
              >
                <span class="shrink-0 w-5 text-[10px] font-bold text-(--t-text-3) tabular-nums">
                  #{{ idx + 1 }}
                </span>
                <span class="flex-1 text-[10px] truncate text-(--t-text-2)">{{ cl.name }}</span>
                <span class="shrink-0 text-[10px] font-bold text-violet-400 tabular-nums">
                  {{ fmtMoney(cl.ltv) }} ₽
                </span>
              </button>
            </div>
          </div>

          <!-- Quick actions -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              Быстрые действия
            </h3>
            <div class="flex flex-col gap-1.5">
              <button
                v-for="act in [
                  { label: '🎉 Создать акцию',        fn: () => openPromoModal() },
                  { label: '📊 Отчёт по лояльности',  fn: () => doExport('pdf') },
                  { label: '📤 Экспорт клиентов',     fn: () => doExport('xlsx') },
                  { label: '🔄 Обновить данные',       fn: () => doRefresh() },
                ]" :key="act.label"
                class="relative overflow-hidden flex items-center gap-2 px-3 py-2.5 rounded-xl
                       text-xs text-(--t-text-2) hover:text-(--t-text) hover:bg-(--t-card-hover)
                       active:scale-[0.97] transition-all text-start"
                @click="act.fn()" @mousedown="ripple"
              >{{ act.label }}</button>
            </div>
          </div>
        </aside>
      </Transition>
    </div>

    <!-- ══════════════════════════════════════
         MOBILE SIDEBAR DRAWER
    ══════════════════════════════════════ -->
    <Transition name="dw-ly">
      <div v-if="showMobileSidebar"
           class="fixed inset-0 z-50 flex" @click.self="showMobileSidebar = false">
        <div class="absolute inset-0 bg-black/40" @click="showMobileSidebar = false" />

        <div class="relative z-10 ms-auto inline-size-72 max-w-[85vw] h-full bg-(--t-surface)
                    border-s border-(--t-border) overflow-y-auto p-4 flex flex-col gap-4">

          <div class="flex items-center justify-between mb-1">
            <h3 class="text-sm font-bold text-(--t-text)">🎖️ Лояльность</h3>
            <button class="text-(--t-text-3) hover:text-(--t-text) transition-colors"
                    @click="showMobileSidebar = false">✕</button>
          </div>

          <!-- Stats grid -->
          <div class="grid grid-cols-2 gap-2">
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Участники</p>
              <p class="text-xs font-bold text-sky-400 tabular-nums">{{ fmtNum(pStats.activeClients) }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Ср. LTV</p>
              <p class="text-xs font-bold text-violet-400 tabular-nums">{{ fmtMoney(pStats.avgLTV) }} ₽</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Начислено</p>
              <p class="text-xs font-bold text-emerald-400 tabular-nums">{{ fmtPts(pStats.totalIssued) }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Списано</p>
              <p class="text-xs font-bold text-rose-400 tabular-nums">{{ fmtPts(pStats.totalRedeemed) }}</p>
            </div>
          </div>

          <!-- Tier distribution -->
          <div>
            <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase mb-2">Уровни</h4>
            <div class="flex flex-col gap-2">
              <div v-for="td in tierDistribution" :key="td.key"
                   class="flex items-center gap-2 px-3 py-2 rounded-xl bg-(--t-bg)/40">
                <span class="text-sm">{{ td.icon }}</span>
                <span class="flex-1 text-xs text-(--t-text-2)">{{ td.label }}</span>
                <span class="text-[10px] text-(--t-text-3) tabular-nums">{{ td.count }}</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col gap-1.5 mt-2">
            <button
              class="relative overflow-hidden py-2.5 rounded-xl text-[10px] font-semibold
                     bg-(--t-primary) text-white active:scale-95 transition-all"
              @click="showMobileSidebar = false; openPromoModal()" @mousedown="ripple"
            >🎉 Создать акцию</button>
            <button
              class="relative overflow-hidden py-2.5 rounded-xl text-[10px] font-semibold
                     border border-(--t-border)/50 text-(--t-text)
                     active:scale-95 transition-all"
              @click="showMobileSidebar = false; doExport('pdf')" @mousedown="ripple"
            >📊 Отчёт по лояльности</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════
         CLIENT DETAIL DRAWER
    ══════════════════════════════════════ -->
    <Transition name="detail-ly">
      <div v-if="showClientDrawer && detailClient"
           class="fixed inset-0 z-50 flex" @click.self="closeClientDrawer">
        <div class="absolute inset-0 bg-black/40" @click="closeClientDrawer" />

        <div class="relative z-10 ms-auto inline-size-full sm:inline-size-96 max-w-full h-full
                    bg-(--t-surface) border-s border-(--t-border) overflow-y-auto flex flex-col">

          <!-- Header -->
          <div class="sticky inset-block-start-0 z-10 flex items-center gap-3 px-5 py-4
                      bg-(--t-surface)/90 backdrop-blur-xl border-b border-(--t-border)/30">
            <div v-if="detailClient.avatar"
                 class="shrink-0 w-11 h-11 rounded-full overflow-hidden bg-(--t-border)/30">
              <img :src="detailClient.avatar" :alt="detailClient.name"
                   class="w-full h-full object-cover" />
            </div>
            <div v-else
                 class="shrink-0 w-11 h-11 rounded-full bg-(--t-primary)/15
                        flex items-center justify-center text-sm font-bold text-(--t-primary)">
              {{ clientInitials(detailClient.name) }}
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-bold text-(--t-text) truncate">{{ detailClient.name }}</h3>
              <div class="flex items-center gap-2 mt-0.5">
                <span :class="[
                  'inline-flex items-center gap-1 px-1.5 py-px rounded-md text-[8px] font-medium',
                  TIER_META[detailClient.tier].badgeCls,
                ]">
                  {{ TIER_META[detailClient.tier].icon }} {{ TIER_META[detailClient.tier].label }}
                </span>
                <span v-if="detailClient.isB2B"
                      class="px-1 py-px rounded text-[7px] font-bold
                             bg-sky-500/12 text-sky-400">B2B</span>
              </div>
            </div>
            <button class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="closeClientDrawer">✕</button>
          </div>

          <!-- Body -->
          <div class="flex-1 p-5 flex flex-col gap-5">

            <!-- Balance highlight -->
            <div class="text-center py-5 rounded-xl bg-(--t-bg)/50">
              <p class="text-[10px] text-(--t-text-3) mb-1">Бонусный баланс</p>
              <p class="text-3xl font-black text-violet-400 tabular-nums">
                {{ fmtPts(detailClient.bonusBalance) }}
              </p>
              <div class="flex items-center justify-center gap-4 mt-3 text-[10px]">
                <span class="text-emerald-400 tabular-nums">
                  + {{ fmtPts(detailClient.totalEarned) }} начислено
                </span>
                <span class="text-rose-400 tabular-nums">
                  − {{ fmtPts(detailClient.totalSpent) }} списано
                </span>
              </div>
            </div>

            <!-- Detail grid -->
            <div class="rounded-xl bg-(--t-bg)/50 p-4 flex flex-col gap-3">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase">Детали</h4>

              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">LTV</span>
                <span class="text-(--t-text) tabular-nums font-medium">
                  {{ fmtMoney(detailClient.ltv) }} ₽
                </span>
              </div>

              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">{{ vc.visitNoun }}</span>
                <span class="text-(--t-text) tabular-nums">{{ detailClient.visitsCount }}</span>
              </div>

              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Последний визит</span>
                <span class="text-(--t-text) tabular-nums">{{ daysAgo(detailClient.lastVisit) }}</span>
              </div>

              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Регистрация</span>
                <span class="text-(--t-text) tabular-nums">{{ fmtDate(detailClient.registeredAt) }}</span>
              </div>

              <div v-if="detailClient.phone" class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Телефон</span>
                <span class="text-(--t-text)">{{ detailClient.phone }}</span>
              </div>

              <div v-if="detailClient.email" class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Email</span>
                <span class="text-(--t-text) truncate max-w-[60%]">{{ detailClient.email }}</span>
              </div>
            </div>

            <!-- Recent ops for this client -->
            <div class="rounded-xl bg-(--t-bg)/50 p-4">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase mb-3">
                Последние операции
              </h4>
              <div v-if="props.operations.filter(o => o.clientId === detailClient!.id).length === 0"
                   class="text-center py-3">
                <p class="text-[10px] text-(--t-text-3)">Нет операций</p>
              </div>
              <div v-else class="flex flex-col gap-2">
                <div v-for="op in props.operations
                       .filter(o => o.clientId === detailClient!.id)
                       .slice(0, 6)"
                     :key="op.id"
                     class="flex items-center gap-2.5">
                  <span class="shrink-0 text-xs">{{ OP_META[op.type].icon }}</span>
                  <span class="flex-1 text-[10px] text-(--t-text-3) truncate">
                    {{ op.description }}
                  </span>
                  <span :class="['shrink-0 text-[10px] font-bold tabular-nums', opAmountCls(op)]">
                    {{ opSign(op) }}{{ fmtPts(op.amount) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer actions -->
          <div class="sticky inset-block-end-0 flex items-center gap-2 px-5 py-3
                      border-t border-(--t-border)/30 bg-(--t-surface)/90 backdrop-blur-xl">
            <button
              class="relative overflow-hidden flex-1 flex items-center justify-center gap-1.5
                     py-2.5 rounded-xl text-xs font-semibold
                     bg-(--t-primary) text-white hover:brightness-110
                     active:scale-95 transition-all"
              @click="openAdjust(detailClient!)" @mousedown="ripple"
            >✏️ Корректировка баланса</button>
            <button
              class="relative overflow-hidden flex-1 flex items-center justify-center gap-1.5
                     py-2.5 rounded-xl text-xs font-medium
                     border border-(--t-border)/50 text-(--t-text-2)
                     hover:bg-(--t-card-hover) active:scale-95 transition-all"
              @click="emit('open-client', detailClient!.id)" @mousedown="ripple"
            >👤 Полный профиль</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════
         CREATE PROMO MODAL
    ══════════════════════════════════════ -->
    <Transition name="modal-ly">
      <div v-if="showPromoModal"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="showPromoModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showPromoModal = false" />

        <div class="relative z-10 inline-size-full max-w-md bg-(--t-surface) rounded-2xl
                    border border-(--t-border)/60 shadow-2xl overflow-hidden
                    max-block-size-[85vh] overflow-y-auto">

          <!-- Header -->
          <div class="sticky inset-block-start-0 z-10 flex items-center justify-between px-5 py-4
                      border-b border-(--t-border)/30 bg-(--t-surface)/90 backdrop-blur-xl">
            <h3 class="text-sm font-bold text-(--t-text)">🎉 Создать акцию</h3>
            <button class="w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="showPromoModal = false">✕</button>
          </div>

          <div class="p-5 flex flex-col gap-4">

            <!-- Name -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Название акции</span>
              <input
                v-model="promoForm.name"
                type="text"
                placeholder="Двойной кэшбэк в выходные"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>

            <!-- Type -->
            <div>
              <p class="text-[10px] text-(--t-text-3) font-medium mb-2">Тип акции</p>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="pt in (['cashback', 'multiplier', 'fixed_bonus', 'free_item', 'discount'] as PromoType[])"
                  :key="pt"
                  :class="[
                    'relative overflow-hidden flex items-center gap-1.5 px-3 py-2 rounded-xl',
                    'text-[10px] font-medium transition-all active:scale-95',
                    promoForm.type === pt
                      ? 'bg-(--t-primary)/15 text-(--t-primary) border border-(--t-primary)/30'
                      : 'border border-(--t-border)/30 text-(--t-text-3) hover:border-(--t-border)/50',
                  ]"
                  @click="promoForm.type = pt" @mousedown="ripple"
                >{{ PROMO_TYPE_META[pt].icon }} {{ PROMO_TYPE_META[pt].label }}</button>
              </div>
            </div>

            <!-- Value -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">
                {{ promoForm.type === 'cashback' || promoForm.type === 'discount' || promoForm.type === 'multiplier'
                     ? 'Процент (%)' : 'Бонусов' }}
              </span>
              <input
                v-model.number="promoForm.value"
                type="number"
                min="1"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-sm font-bold text-(--t-text) tabular-nums
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>

            <!-- Dates -->
            <div class="grid grid-cols-2 gap-3">
              <label class="flex flex-col gap-1.5">
                <span class="text-[10px] text-(--t-text-3) font-medium">Начало</span>
                <input
                  v-model="promoForm.startDate"
                  type="date"
                  class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                         text-xs text-(--t-text)
                         focus:outline-none focus:border-(--t-primary)/50 transition-colors"
                />
              </label>
              <label class="flex flex-col gap-1.5">
                <span class="text-[10px] text-(--t-text-3) font-medium">Конец</span>
                <input
                  v-model="promoForm.endDate"
                  type="date"
                  class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                         text-xs text-(--t-text)
                         focus:outline-none focus:border-(--t-primary)/50 transition-colors"
                />
              </label>
            </div>

            <!-- Tier filter -->
            <div>
              <p class="text-[10px] text-(--t-text-3) font-medium mb-2">
                Для уровней (пусто = все)
              </p>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="tk in (['bronze', 'silver', 'gold', 'platinum', 'vip'] as TierKey[])"
                  :key="tk"
                  :class="[
                    'relative overflow-hidden flex items-center gap-1 px-2.5 py-1.5 rounded-xl',
                    'text-[10px] font-medium transition-all active:scale-95',
                    promoForm.tierFilter.includes(tk)
                      ? TIER_META[tk].badgeCls + ' border border-current/30'
                      : 'border border-(--t-border)/30 text-(--t-text-3) hover:border-(--t-border)/50',
                  ]"
                  @click="
                    promoForm.tierFilter.includes(tk)
                      ? promoForm.tierFilter = promoForm.tierFilter.filter(t => t !== tk)
                      : promoForm.tierFilter.push(tk)
                  "
                  @mousedown="ripple"
                >{{ TIER_META[tk].icon }} {{ TIER_META[tk].label }}</button>
              </div>
            </div>

            <!-- Description -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Описание</span>
              <textarea
                v-model="promoForm.description"
                rows="2"
                placeholder="Описание акции для клиентов…"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3) resize-none
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>
          </div>

          <!-- Footer -->
          <div class="sticky inset-block-end-0 flex gap-2 px-5 py-4
                      border-t border-(--t-border)/30 bg-(--t-surface)/90 backdrop-blur-xl">
            <button
              class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-medium
                     border border-(--t-border)/50 text-(--t-text-3)
                     hover:bg-(--t-card-hover) active:scale-95 transition-all"
              @click="showPromoModal = false" @mousedown="ripple"
            >Отмена</button>
            <button
              class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-semibold
                     bg-(--t-primary) text-white hover:brightness-110 active:scale-95 transition-all
                     disabled:opacity-40 disabled:pointer-events-none"
              :disabled="!promoForm.name.trim() || promoForm.value <= 0"
              @click="submitPromo" @mousedown="ripple"
            >🎉 Создать акцию</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════
         ADJUST BALANCE MODAL
    ══════════════════════════════════════ -->
    <Transition name="modal-ly">
      <div v-if="showAdjustModal && detailClient"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="showAdjustModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showAdjustModal = false" />

        <div class="relative z-10 inline-size-full max-w-sm bg-(--t-surface) rounded-2xl
                    border border-(--t-border)/60 shadow-2xl overflow-hidden">

          <div class="flex items-center justify-between px-5 py-4 border-b border-(--t-border)/30">
            <h3 class="text-sm font-bold text-(--t-text)">✏️ Корректировка баланса</h3>
            <button class="w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="showAdjustModal = false">✕</button>
          </div>

          <div class="p-5 flex flex-col gap-4">

            <!-- Client info -->
            <div class="rounded-xl bg-(--t-bg)/50 p-3 flex items-center gap-3">
              <div class="shrink-0 w-9 h-9 rounded-full bg-(--t-primary)/15
                          flex items-center justify-center text-xs font-bold text-(--t-primary)">
                {{ clientInitials(detailClient.name) }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-(--t-text) truncate">{{ detailClient.name }}</p>
                <p class="text-[10px] text-violet-400 tabular-nums">
                  Баланс: {{ fmtPts(detailClient.bonusBalance) }}
                </p>
              </div>
            </div>

            <!-- Amount -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">
                Количество бонусов (+ начислить / − списать)
              </span>
              <input
                v-model.number="adjustAmount"
                type="number"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-sm font-bold text-(--t-text) tabular-nums
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>

            <!-- Reason -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Причина</span>
              <input
                v-model="adjustReason"
                type="text"
                placeholder="Компенсация за неудобства"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>

            <!-- Fraud note -->
            <div class="rounded-xl bg-amber-500/8 border border-amber-500/20 p-3
                        flex items-start gap-2.5">
              <span class="shrink-0 text-sm">⚠️</span>
              <p class="text-[10px] text-amber-400/90 leading-relaxed">
                Ручная корректировка проходит FraudControlService и логируется
                в audit с correlation_id.
              </p>
            </div>
          </div>

          <div class="flex gap-2 px-5 py-4 border-t border-(--t-border)/30">
            <button
              class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-medium
                     border border-(--t-border)/50 text-(--t-text-3)
                     hover:bg-(--t-card-hover) active:scale-95 transition-all"
              @click="showAdjustModal = false" @mousedown="ripple"
            >Отмена</button>
            <button
              class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-semibold
                     bg-(--t-primary) text-white hover:brightness-110 active:scale-95 transition-all
                     disabled:opacity-40 disabled:pointer-events-none"
              :disabled="adjustAmount === 0 || !adjustReason.trim()"
              @click="submitAdjust" @mousedown="ripple"
            >{{ adjustAmount >= 0 ? '+ Начислить' : '− Списать' }} {{ fmtPts(Math.abs(adjustAmount)) }}</button>
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
/* Ripple — unique suffix ly (Loyalty) */
@keyframes ripple-ly {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* No scrollbar */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Line clamp fallback */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Sidebar transition */
.sb-ly-enter-active,
.sb-ly-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.sb-ly-enter-from,
.sb-ly-leave-to {
  opacity: 0;
  transform: translateX(12px);
}

/* Drawer transitions */
.dw-ly-enter-active,
.dw-ly-leave-active,
.detail-ly-enter-active,
.detail-ly-leave-active {
  transition: opacity 0.3s ease;
}
.dw-ly-enter-active > :last-child,
.dw-ly-leave-active > :last-child,
.detail-ly-enter-active > :last-child,
.detail-ly-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.dw-ly-enter-from,
.dw-ly-leave-to,
.detail-ly-enter-from,
.detail-ly-leave-to {
  opacity: 0;
}
.dw-ly-enter-from > :last-child,
.dw-ly-leave-to > :last-child,
.detail-ly-enter-from > :last-child,
.detail-ly-leave-to > :last-child {
  transform: translateX(100%);
}

/* Modal transition */
.modal-ly-enter-active,
.modal-ly-leave-active {
  transition: opacity 0.25s ease;
}
.modal-ly-enter-active > :nth-child(2),
.modal-ly-leave-active > :nth-child(2) {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}
.modal-ly-enter-from,
.modal-ly-leave-to {
  opacity: 0;
}
.modal-ly-enter-from > :nth-child(2),
.modal-ly-leave-to > :nth-child(2) {
  transform: scale(0.95) translateY(8px);
  opacity: 0;
}

/* Fade (export menu) */
.fade-ly-enter-active,
.fade-ly-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.fade-ly-enter-from,
.fade-ly-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
