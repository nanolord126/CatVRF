<script setup lang="ts">
/**
 * TenantIntegrations.vue — Интеграции и подключения B2B Tenant Dashboard
 *
 * Вертикали:
 *   Beauty · Taxi · Food · Hotels · RealEstate · Flowers
 *   Fashion · Furniture · Fitness · Travel · default
 *
 * ───────────────────────────────────────────────────────────────
 *  Функционал:
 *   1.  Верхняя панель: поиск, фильтры (статус, категория),
 *       «Маркетплейс», refresh, fullscreen
 *   2.  KPI-виджеты: всего интеграций, подключено, ошибок,
 *       API-вызовов, webhooks, Экосистема Кота
 *   3.  Рекомендуемые интеграции для текущей вертикали
 *   4.  Grid карточек интеграций по категориям:
 *       💳 Платежи · 🚚 Доставка · 📩 SMS/Email
 *       📊 Аналитика · 🐱 Экосистема Кота · 🗺️ Карты
 *       🤖 AI/ML · 🔗 CRM · 📦 Склад · ☁️ Облако
 *   5.  Каждая карточка: иконка/лого, название, описание,
 *       статус (connected / available / error / pending),
 *       кнопка «Подключить» / «Настроить»
 *   6.  Detail Drawer: конфигурация, API-ключи, webhooks,
 *       логи вызовов, toggle вкл/выкл
 *   7.  Sidebar: статистика, быстрые действия, здоровье API
 *   8.  Full-screen · mobile drawer · keyboard Esc · ripple-ig
 * ───────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { useAuth, useTenant } from '@/stores'

/* ━━━━━━━━━━━━  TYPES  ━━━━━━━━━━━━ */

type IntegrationStatus   = 'connected' | 'available' | 'error' | 'pending' | 'disabled'
type IntegrationCategory = 'payments' | 'delivery' | 'messaging' | 'analytics'
                         | 'ecosystem' | 'maps' | 'ai' | 'crm' | 'warehouse' | 'cloud'
type FilterStatus        = 'all' | IntegrationStatus
type TabKey              = 'all' | IntegrationCategory
type SortKey             = 'name' | 'status' | 'category' | 'calls'
type SortDir             = 'asc' | 'desc'

interface Integration {
  id:           number | string
  name:        string
  slug:        string
  description: string
  icon:        string
  category:    IntegrationCategory
  status:      IntegrationStatus
  version:     string
  apiCalls24h: number
  errorRate:   number
  lastSync:    string
  webhookUrl?: string
  config?:     Record<string, unknown>
  recommended: boolean
  popular:     boolean
  verticals:   string[]
  tags:        string[]
  docsUrl?:    string
}

interface IntegrationStats {
  total:           number
  connected:       number
  errors:          number
  apiCalls24h:     number
  webhooksActive:  number
  ecosystemScore:  number
  uptime:          number
  avgResponseMs:   number
}

interface ApiLogEntry {
  id:        number | string
  method:    string
  endpoint:  string
  status:    number
  duration:  number
  timestamp: string
}

interface VerticalIntCfg {
  label:        string
  icon:         string
  accent:       string
  recommended:  string[]
}

/* ━━━━━━━━━━━━  PROPS / EMITS  ━━━━━━━━━━━━ */

const props = withDefaults(defineProps<{
  vertical?:      string
  integrations?:  Integration[]
  stats?:         IntegrationStats | null
  apiLogs?:       ApiLogEntry[]
  loading?:       boolean
}>(), {
  vertical:      'default',
  integrations:  () => [],
  stats:         null,
  apiLogs:       () => [],
  loading:       false,
})

const emit = defineEmits<{
  'connect':            [id: number | string]
  'disconnect':         [id: number | string]
  'configure':          [id: number | string, config: Record<string, unknown>]
  'toggle':             [id: number | string, enabled: boolean]
  'test-connection':    [id: number | string]
  'refresh':            []
  'open-marketplace':   []
  'export':             [format: 'csv' | 'json']
  'toggle-fullscreen':  []
}>()

const auth = useAuth()
const biz  = useTenant()

/* ━━━━━━━━━━━━  VERTICAL CONFIG  ━━━━━━━━━━━━ */

const VERTICAL_CFG: Record<string, VerticalIntCfg> = {
  beauty:     { label: 'Салон красоты',   icon: '💄', accent: 'pink',    recommended: ['tinkoff', 'yandex-maps', 'sms-ru', 'cat-eco', 'yclients'] },
  taxi:       { label: 'Такси',           icon: '🚕', accent: 'yellow',  recommended: ['tinkoff', 'yandex-maps', 'cat-eco', 'firebase', 'osrm'] },
  food:       { label: 'Еда и рестораны', icon: '🍽️', accent: 'orange',  recommended: ['tinkoff', 'yandex-delivery', 'iiko', 'cat-eco', 'sms-ru'] },
  hotel:      { label: 'Отели',           icon: '🏨', accent: 'sky',     recommended: ['tinkoff', 'booking-api', 'yandex-maps', 'cat-eco', 'travelline'] },
  realEstate: { label: 'Недвижимость',    icon: '🏢', accent: 'emerald', recommended: ['tinkoff', 'yandex-maps', 'cian-api', 'cat-eco', 'amoCRM'] },
  flowers:    { label: 'Цветы',           icon: '💐', accent: 'rose',    recommended: ['tinkoff', 'cdek', 'sms-ru', 'cat-eco', 'yandex-delivery'] },
  fashion:    { label: 'Мода и одежда',   icon: '👗', accent: 'violet',  recommended: ['tinkoff', 'cdek', 'cat-eco', 'retailCRM', 'google-analytics'] },
  furniture:  { label: 'Мебель',          icon: '🛋️', accent: 'amber',   recommended: ['tinkoff', 'cdek', 'yandex-maps', 'cat-eco', '1c'] },
  fitness:    { label: 'Фитнес',          icon: '💪', accent: 'lime',    recommended: ['tinkoff', 'cat-eco', 'sms-ru', 'google-analytics', 'yclients'] },
  travel:     { label: 'Путешествия',     icon: '✈️', accent: 'cyan',    recommended: ['tinkoff', 'aviasales', 'yandex-maps', 'cat-eco', 'travelline'] },
  default:    { label: 'Бизнес',          icon: '📊', accent: 'indigo',  recommended: ['tinkoff', 'yandex-maps', 'cat-eco', 'sms-ru', 'google-analytics'] },
}

const vc = computed<VerticalIntCfg>(() => VERTICAL_CFG[props.vertical] ?? VERTICAL_CFG.default)

/* ━━━━━━━━━━━━  CONSTANTS  ━━━━━━━━━━━━ */

const STATUS_META: Record<IntegrationStatus, { label: string; dot: string; cls: string; icon: string }> = {
  connected: { label: 'Подключено',     dot: 'bg-emerald-500', cls: 'bg-emerald-500/12 text-emerald-400', icon: '✓' },
  available: { label: 'Доступно',       dot: 'bg-zinc-500',    cls: 'bg-zinc-500/12 text-zinc-400',       icon: '○' },
  error:     { label: 'Ошибка',         dot: 'bg-rose-500',    cls: 'bg-rose-500/12 text-rose-400',       icon: '!' },
  pending:   { label: 'Ожидание',       dot: 'bg-amber-500',   cls: 'bg-amber-500/12 text-amber-400',     icon: '⏳' },
  disabled:  { label: 'Отключено',      dot: 'bg-zinc-600',    cls: 'bg-zinc-600/12 text-zinc-500',       icon: '—' },
}

const CATEGORY_META: Record<IntegrationCategory, { label: string; icon: string }> = {
  payments:   { label: 'Платежи',       icon: '💳' },
  delivery:   { label: 'Доставка',      icon: '🚚' },
  messaging:  { label: 'SMS / Email',   icon: '📩' },
  analytics:  { label: 'Аналитика',     icon: '📊' },
  ecosystem:  { label: 'Экосистема 🐱', icon: '🐱' },
  maps:       { label: 'Карты',         icon: '🗺️' },
  ai:         { label: 'AI / ML',       icon: '🤖' },
  crm:        { label: 'CRM',           icon: '🔗' },
  warehouse:  { label: 'Склад / 1С',    icon: '📦' },
  cloud:      { label: 'Облако',        icon: '☁️' },
}

const CATEGORY_KEYS = Object.keys(CATEGORY_META) as IntegrationCategory[]

const STATUS_FILTERS: Array<{ key: FilterStatus; label: string }> = [
  { key: 'all',       label: 'Все' },
  { key: 'connected', label: 'Подключено' },
  { key: 'available', label: 'Доступно' },
  { key: 'error',     label: 'Ошибки' },
  { key: 'pending',   label: 'Ожидание' },
]

/* ━━━━━━━━━━━━  STATE  ━━━━━━━━━━━━ */

const rootEl              = ref<HTMLElement | null>(null)
const isFullscreen        = ref(false)
const searchQuery         = ref('')
const filterStatus        = ref<FilterStatus>('all')
const activeCategory      = ref<TabKey>('all')
const sortKey             = ref<SortKey>('status')
const sortDir             = ref<SortDir>('asc')
const showSidebar         = ref(true)
const showMobileSidebar   = ref(false)
const showDetailDrawer    = ref(false)
const showConnectModal    = ref(false)
const showExportMenu      = ref(false)
const detailIntegration   = ref<Integration | null>(null)
const connectTarget       = ref<Integration | null>(null)
const refreshing          = ref(false)
const testingId           = ref<number | string | null>(null)

/* ── Connect form ── */
const connectForm = reactive<{
  apiKey: string; secretKey: string; webhookUrl: string; notes: string
}>({
  apiKey: '', secretKey: '', webhookUrl: '', notes: '',
})

/* ━━━━━━━━━━━━  COMPUTED  ━━━━━━━━━━━━ */

const pStats = computed<IntegrationStats>(() =>
  props.stats ?? {
    total: 0, connected: 0, errors: 0, apiCalls24h: 0,
    webhooksActive: 0, ecosystemScore: 0, uptime: 0, avgResponseMs: 0,
  },
)

const connectedPct = computed(() => {
  if (pStats.value.total <= 0) return 0
  return Math.round((pStats.value.connected / pStats.value.total) * 100)
})

/* ── Recommended for this vertical ── */
const recommendedIntegrations = computed<Integration[]>(() => {
  const slugs = vc.value.recommended
  return props.integrations
    .filter((i) => slugs.includes(i.slug) || i.recommended)
    .sort((a, b) => {
      const aIdx = slugs.indexOf(a.slug)
      const bIdx = slugs.indexOf(b.slug)
      if (aIdx >= 0 && bIdx >= 0) return aIdx - bIdx
      if (aIdx >= 0) return -1
      if (bIdx >= 0) return 1
      return 0
    })
    .slice(0, 6)
})

/* ── Filtered integrations ── */
const filteredIntegrations = computed<Integration[]>(() => {
  let list = [...props.integrations]

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase()
    list = list.filter(
      (i) => i.name.toLowerCase().includes(q)
           || i.description.toLowerCase().includes(q)
           || i.tags.some((t) => t.toLowerCase().includes(q)),
    )
  }

  if (filterStatus.value !== 'all') {
    list = list.filter((i) => i.status === filterStatus.value)
  }

  if (activeCategory.value !== 'all') {
    list = list.filter((i) => i.category === activeCategory.value)
  }

  const statusOrder: Record<IntegrationStatus, number> = {
    error: 0, connected: 1, pending: 2, available: 3, disabled: 4,
  }

  list.sort((a, b) => {
    let cmp = 0
    switch (sortKey.value) {
      case 'name':     cmp = a.name.localeCompare(b.name); break
      case 'status':   cmp = statusOrder[a.status] - statusOrder[b.status]; break
      case 'category': cmp = a.category.localeCompare(b.category); break
      case 'calls':    cmp = a.apiCalls24h - b.apiCalls24h; break
    }
    return sortDir.value === 'asc' ? cmp : -cmp
  })

  return list
})

/* ── Grouped by category ── */
const groupedIntegrations = computed<Record<string, Integration[]>>(() => {
  const groups: Record<string, Integration[]> = {}
  for (const item of filteredIntegrations.value) {
    if (!groups[item.category]) groups[item.category] = []
    groups[item.category].push(item)
  }
  return groups
})

const activeGroupKeys = computed(() =>
  CATEGORY_KEYS.filter((k) => groupedIntegrations.value[k]?.length),
)

const activeFiltersCount = computed(() => {
  let c = 0
  if (filterStatus.value !== 'all') c++
  if (activeCategory.value !== 'all') c++
  if (searchQuery.value.trim()) c++
  return c
})

/* ── Sidebar: error integrations ── */
const errorIntegrations = computed(() =>
  props.integrations.filter((i) => i.status === 'error'),
)

const recentApiLogs = computed(() =>
  [...props.apiLogs].sort((a, b) =>
    new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime(),
  ).slice(0, 8),
)

/* ━━━━━━━━━━━━  HELPERS  ━━━━━━━━━━━━ */

function fmtNum(n: number): string {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M`
  if (n >= 1_000)     return `${(n / 1_000).toFixed(1)}K`
  return String(n)
}

function fmtPct(n: number): string { return `${n.toFixed(1)}%` }

function fmtDate(d: string): string {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('ru-RU', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
}

function fmtMs(ms: number): string {
  if (ms >= 1000) return `${(ms / 1000).toFixed(1)}s`
  return `${Math.round(ms)}ms`
}

function httpStatusCls(status: number): string {
  if (status >= 200 && status < 300) return 'text-emerald-400'
  if (status >= 400 && status < 500) return 'text-amber-400'
  return 'text-rose-400'
}

function methodCls(method: string): string {
  switch (method.toUpperCase()) {
    case 'GET':    return 'text-sky-400'
    case 'POST':   return 'text-emerald-400'
    case 'PUT':    return 'text-amber-400'
    case 'DELETE': return 'text-rose-400'
    default:       return 'text-(--t-text-3)'
  }
}

/* ━━━━━━━━━━━━  ACTIONS  ━━━━━━━━━━━━ */

function clearFilters() {
  searchQuery.value = ''
  filterStatus.value = 'all'
  activeCategory.value = 'all'
}

function openDetail(item: Integration) {
  detailIntegration.value = item
  showDetailDrawer.value = true
}

function closeDetail() {
  showDetailDrawer.value = false
  detailIntegration.value = null
}

function openConnect(item: Integration) {
  connectTarget.value = item
  connectForm.apiKey = ''
  connectForm.secretKey = ''
  connectForm.webhookUrl = ''
  connectForm.notes = ''
  showConnectModal.value = true
}

function submitConnect() {
  if (connectTarget.value) {
    emit('configure', connectTarget.value.id, { ...connectForm })
  }
  showConnectModal.value = false
}

function doTestConnection(item: Integration) {
  testingId.value = item.id
  emit('test-connection', item.id)
  setTimeout(() => { testingId.value = null }, 2500)
}

function doRefresh() {
  refreshing.value = true
  emit('refresh')
  setTimeout(() => { refreshing.value = false }, 1200)
}

function doExport(fmt: 'csv' | 'json') {
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
    if (showConnectModal.value)    { showConnectModal.value = false; return }
    if (showDetailDrawer.value)    { closeDetail(); return }
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
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-ig_0.6s_ease-out]'
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

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     TEMPLATE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<template>
  <div
    ref="rootEl"
    :class="[
      'relative flex flex-col bg-(--t-bg) text-(--t-text)',
      isFullscreen ? 'fixed inset-0 z-50 overflow-auto' : 'min-h-screen',
    ]"
  >
    <!-- ═══════ HEADER ═══════ -->
    <header class="sticky inset-block-start-0 z-30 bg-(--t-surface)/80 backdrop-blur-xl
                   border-b border-(--t-border)/40">
      <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 sm:px-6 py-3">

        <!-- Title -->
        <div class="flex items-center gap-3 flex-1 min-w-0">
          <span class="text-2xl">🔌</span>
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <h1 class="text-base sm:text-lg font-extrabold text-(--t-text) truncate leading-snug">
                Интеграции
              </h1>
              <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold
                           bg-emerald-500/15 text-emerald-400 tabular-nums">
                {{ pStats.connected }}/{{ pStats.total }} подключено
              </span>
            </div>
            <p class="text-[10px] text-(--t-text-3) truncate">
              {{ vc.icon }} {{ vc.label }} · Платежи · Доставка · SMS · Аналитика · 🐱 Экосистема
            </p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 flex-wrap">

          <!-- Marketplace -->
          <button
            class="relative overflow-hidden flex items-center gap-1.5 px-4 py-2 rounded-xl
                   text-xs font-semibold bg-(--t-primary) text-white hover:brightness-110
                   active:scale-95 transition-all"
            @click="emit('open-marketplace')" @mousedown="ripple"
          >
            <span class="text-sm">🏪</span>
            <span class="hidden sm:inline">Маркетплейс</span>
          </button>

          <!-- Search -->
          <div class="relative hidden sm:block">
            <input
              v-model="searchQuery" type="text" placeholder="Поиск…"
              class="py-1.5 ps-8 pe-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                     text-xs text-(--t-text) placeholder:text-(--t-text-3)
                     focus:outline-none focus:border-(--t-primary)/50 transition-colors
                     inline-size-44"
            />
            <span class="absolute inset-inline-start-2.5 inset-block-start-1/2 -translate-y-1/2
                         text-xs text-(--t-text-3) pointer-events-none">🔍</span>
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
            <Transition name="fade-ig">
              <div v-if="showExportMenu"
                   class="absolute inset-inline-end-0 inset-block-start-full mt-1 z-20
                          w-32 rounded-xl border border-(--t-border)/50 bg-(--t-surface)
                          shadow-xl p-1 flex flex-col">
                <button
                  v-for="fmt in (['csv', 'json'] as const)" :key="fmt"
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

    <!-- ═══════ MAIN ═══════ -->
    <div class="flex-1 flex gap-5 px-4 sm:px-6 py-5 max-w-screen-2xl mx-auto inline-size-full">

      <!-- ═══ CONTENT ═══ -->
      <div class="flex-1 flex flex-col gap-5 min-w-0">

        <!-- ── KPI GRID ── -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div v-for="kpi in [
            { label: 'Всего',       value: fmtNum(pStats.total),          icon: '🔌', cls: 'text-sky-400' },
            { label: 'Подключено',  value: fmtNum(pStats.connected),      icon: '✓',  cls: 'text-emerald-400' },
            { label: 'Ошибок',      value: fmtNum(pStats.errors),         icon: '⚠️', cls: 'text-rose-400' },
            { label: 'API / 24 ч',  value: fmtNum(pStats.apiCalls24h),    icon: '📡', cls: 'text-violet-400' },
            { label: 'Webhooks',    value: fmtNum(pStats.webhooksActive),  icon: '🔔', cls: 'text-amber-400' },
            { label: '🐱 Экосистема', value: fmtPct(pStats.ecosystemScore), icon: '🐱', cls: 'text-pink-400' },
            { label: 'Uptime',      value: fmtPct(pStats.uptime),          icon: '🟢', cls: 'text-emerald-400' },
            { label: 'Ср. ответ',   value: fmtMs(pStats.avgResponseMs),   icon: '⚡', cls: 'text-sky-400' },
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

        <!-- ── Connected progress ── -->
        <div class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                    backdrop-blur-sm p-4">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-(--t-text)">🔌 Подключение интеграций</span>
            <span class="text-[10px] text-(--t-text-3) tabular-nums">{{ connectedPct }}%</span>
          </div>
          <div class="h-2.5 rounded-full bg-(--t-border)/20 overflow-hidden">
            <div
              class="h-full rounded-full bg-emerald-500 transition-all"
              :style="{ inlineSize: `${Math.max(1, connectedPct)}%` }"
            />
          </div>
          <div class="flex items-center justify-between mt-1.5 text-[9px] text-(--t-text-3) tabular-nums">
            <span>{{ pStats.connected }} подключено</span>
            <span>{{ Math.max(0, pStats.total - pStats.connected) }} доступно</span>
          </div>
        </div>

        <!-- ── RECOMMENDED FOR VERTICAL ── -->
        <div v-if="recommendedIntegrations.length > 0"
             class="rounded-2xl border border-(--t-primary)/20 bg-(--t-primary)/4
                    backdrop-blur-sm p-4 sm:p-5">
          <div class="flex items-center gap-2 mb-3">
            <span class="text-lg">{{ vc.icon }}</span>
            <h2 class="text-xs font-bold text-(--t-text)">
              Рекомендуемые для {{ vc.label.toLowerCase() }}
            </h2>
          </div>
          <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
            <button v-for="rec in recommendedIntegrations" :key="rec.id"
              class="group/rec relative overflow-hidden flex items-center gap-3
                     rounded-xl border border-(--t-border)/30 bg-(--t-surface)/60
                     hover:border-(--t-border)/60 hover:shadow-lg hover:shadow-black/5
                     active:scale-[0.97] transition-all p-3 text-start"
              @click="openDetail(rec)" @mousedown="ripple"
            >
              <span class="shrink-0 text-xl">{{ rec.icon }}</span>
              <div class="flex-1 min-w-0">
                <p class="text-[11px] font-bold text-(--t-text) truncate">{{ rec.name }}</p>
                <p class="text-[9px] text-(--t-text-3) line-clamp-1">{{ rec.description }}</p>
              </div>
              <span :class="[
                'shrink-0 w-2 h-2 rounded-full',
                STATUS_META[rec.status].dot,
              ]" />
            </button>
          </div>
        </div>

        <!-- ── FILTERS ── -->
        <div class="flex flex-col gap-2.5">
          <!-- Mobile search -->
          <div class="sm:hidden relative">
            <input
              v-model="searchQuery" type="text" placeholder="Поиск интеграции…"
              class="inline-size-full py-2 ps-8 pe-3 rounded-xl border border-(--t-border)/50
                     bg-(--t-bg)/60 text-xs text-(--t-text) placeholder:text-(--t-text-3)
                     focus:outline-none focus:border-(--t-primary)/50 transition-colors"
            />
            <span class="absolute inset-inline-start-2.5 inset-block-start-1/2 -translate-y-1/2
                         text-xs text-(--t-text-3) pointer-events-none">🔍</span>
          </div>

          <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
            <!-- Status -->
            <div class="flex items-center gap-0.5 p-0.5 rounded-lg border border-(--t-border)/30
                        bg-(--t-surface)/40 shrink-0">
              <button
                v-for="sf in STATUS_FILTERS" :key="sf.key"
                :class="[
                  'relative overflow-hidden px-2.5 py-1.5 rounded-md text-[10px] font-medium transition-all',
                  filterStatus === sf.key
                    ? 'bg-(--t-primary)/15 text-(--t-primary)'
                    : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
                ]"
                @click="filterStatus = sf.key" @mousedown="ripple"
              >{{ sf.label }}</button>
            </div>

            <!-- Category pills -->
            <div class="flex items-center gap-1 overflow-x-auto no-scrollbar">
              <button
                :class="[
                  'relative overflow-hidden shrink-0 px-2.5 py-1.5 rounded-lg text-[10px] font-medium transition-all',
                  activeCategory === 'all'
                    ? 'bg-(--t-primary)/15 text-(--t-primary) border border-(--t-primary)/30'
                    : 'border border-(--t-border)/30 text-(--t-text-3) hover:text-(--t-text)',
                ]"
                @click="activeCategory = 'all'" @mousedown="ripple"
              >Все</button>
              <button
                v-for="ck in CATEGORY_KEYS" :key="ck"
                :class="[
                  'relative overflow-hidden shrink-0 flex items-center gap-1 px-2.5 py-1.5',
                  'rounded-lg text-[10px] font-medium transition-all',
                  activeCategory === ck
                    ? 'bg-(--t-primary)/15 text-(--t-primary) border border-(--t-primary)/30'
                    : 'border border-(--t-border)/30 text-(--t-text-3) hover:text-(--t-text)',
                ]"
                @click="activeCategory = ck" @mousedown="ripple"
              >{{ CATEGORY_META[ck].icon }} {{ CATEGORY_META[ck].label }}</button>
            </div>

            <button v-if="activeFiltersCount > 0"
              class="shrink-0 flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[10px]
                     font-medium text-rose-400 bg-rose-500/10 hover:bg-rose-500/20
                     active:scale-95 transition-all"
              @click="clearFilters"
            >✕ Сбросить ({{ activeFiltersCount }})</button>
          </div>
        </div>

        <!-- ── LOADING ── -->
        <div v-if="props.loading && filteredIntegrations.length === 0"
             class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <div v-for="n in 6" :key="n"
               class="rounded-2xl border border-(--t-border)/20 bg-(--t-surface)/30
                      animate-pulse p-5">
            <div class="flex items-center gap-3 mb-3">
              <div class="shrink-0 w-10 h-10 rounded-xl bg-(--t-border)/30" />
              <div class="flex-1">
                <div class="h-3 w-24 bg-(--t-border)/30 rounded mb-2" />
                <div class="h-2.5 w-32 bg-(--t-border)/20 rounded" />
              </div>
            </div>
            <div class="h-8 w-full bg-(--t-border)/15 rounded-lg" />
          </div>
        </div>

        <!-- ── EMPTY ── -->
        <div v-else-if="filteredIntegrations.length === 0 && !props.loading"
             class="py-16 text-center">
          <p class="text-5xl mb-3">🔌</p>
          <p class="text-sm font-semibold text-(--t-text-2)">Интеграции не найдены</p>
          <p class="text-[10px] text-(--t-text-3) mt-1">
            {{ activeFiltersCount > 0 ? 'Попробуйте изменить фильтры' : 'Откройте маркетплейс для подключения' }}
          </p>
          <button v-if="activeFiltersCount > 0"
            class="relative overflow-hidden mt-4 px-5 py-2 rounded-xl text-xs font-semibold
                   border border-(--t-border)/50 text-(--t-text) hover:bg-(--t-card-hover)
                   active:scale-95 transition-all"
            @click="clearFilters" @mousedown="ripple"
          >Сбросить фильтры</button>
        </div>

        <!-- ── INTEGRATION CARDS BY CATEGORY ── -->
        <template v-else>
          <div v-for="catKey in (activeCategory === 'all' ? activeGroupKeys : [activeCategory])"
               :key="catKey" class="flex flex-col gap-3">

            <!-- Category heading -->
            <div v-if="activeCategory === 'all'" class="flex items-center gap-2 mt-1">
              <span class="text-sm">{{ CATEGORY_META[catKey as IntegrationCategory]?.icon }}</span>
              <h3 class="text-xs font-bold text-(--t-text-2) uppercase tracking-wider">
                {{ CATEGORY_META[catKey as IntegrationCategory]?.label }}
              </h3>
              <span class="text-[9px] text-(--t-text-3) tabular-nums">
                ({{ groupedIntegrations[catKey]?.length ?? 0 }})
              </span>
            </div>

            <!-- Cards grid -->
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
              <div v-for="item in (activeCategory === 'all' ? (groupedIntegrations[catKey] ?? []) : filteredIntegrations)"
                   :key="item.id"
                   class="group/card rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                          backdrop-blur-sm hover:border-(--t-border)/60
                          hover:shadow-lg hover:shadow-black/5 transition-all overflow-hidden">

                <!-- Card body -->
                <button
                  class="relative overflow-hidden inline-size-full text-start p-4 transition-all
                         active:scale-[0.98]"
                  @click="openDetail(item)" @mousedown="ripple"
                >
                  <div class="flex items-start gap-3">
                    <!-- Icon -->
                    <div class="shrink-0 w-11 h-11 rounded-xl bg-(--t-bg)/60
                                flex items-center justify-center text-xl
                                group-hover/card:scale-105 transition-transform">
                      {{ item.icon }}
                    </div>

                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2">
                        <p class="text-xs font-bold text-(--t-text) truncate">{{ item.name }}</p>
                        <span v-if="item.popular"
                              class="shrink-0 px-1 py-px rounded text-[7px] font-bold
                                     bg-amber-500/12 text-amber-400">🔥</span>
                      </div>
                      <p class="text-[9px] text-(--t-text-3) line-clamp-2 mt-0.5 leading-relaxed">
                        {{ item.description }}
                      </p>
                    </div>
                  </div>

                  <!-- Meta row -->
                  <div class="flex items-center gap-3 mt-3 text-[9px] text-(--t-text-3) tabular-nums">
                    <span v-if="item.apiCalls24h > 0">
                      📡 {{ fmtNum(item.apiCalls24h) }} / 24ч
                    </span>
                    <span v-if="item.version">v{{ item.version }}</span>
                    <span v-if="item.lastSync">🕐 {{ fmtDate(item.lastSync) }}</span>
                  </div>

                  <!-- Tags -->
                  <div v-if="item.tags.length > 0" class="flex flex-wrap gap-1 mt-2">
                    <span v-for="tag in item.tags.slice(0, 3)" :key="tag"
                          class="px-1.5 py-px rounded text-[7px] font-medium
                                 bg-(--t-primary)/8 text-(--t-primary)/70">{{ tag }}</span>
                  </div>
                </button>

                <!-- Footer -->
                <div class="flex items-center justify-between px-4 py-2.5
                            border-t border-(--t-border)/20 bg-(--t-bg)/20">
                  <!-- Status badge -->
                  <span :class="[
                    'inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[9px] font-medium',
                    STATUS_META[item.status].cls,
                  ]">
                    <span :class="['w-1.5 h-1.5 rounded-full', STATUS_META[item.status].dot]" />
                    {{ STATUS_META[item.status].label }}
                  </span>

                  <!-- Action button -->
                  <button v-if="item.status === 'available' || item.status === 'disabled'"
                    class="relative overflow-hidden px-3 py-1.5 rounded-lg text-[10px] font-semibold
                           bg-(--t-primary)/15 text-(--t-primary) hover:bg-(--t-primary)/25
                           active:scale-95 transition-all"
                    @click.stop="openConnect(item)" @mousedown="ripple"
                  >Подключить</button>
                  <button v-else-if="item.status === 'connected'"
                    class="relative overflow-hidden px-3 py-1.5 rounded-lg text-[10px] font-medium
                           text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)
                           active:scale-95 transition-all"
                    @click.stop="openDetail(item)" @mousedown="ripple"
                  >⚙️ Настроить</button>
                  <button v-else-if="item.status === 'error'"
                    class="relative overflow-hidden px-3 py-1.5 rounded-lg text-[10px] font-semibold
                           bg-rose-500/12 text-rose-400 hover:bg-rose-500/20
                           active:scale-95 transition-all"
                    @click.stop="openDetail(item)" @mousedown="ripple"
                  >⚠️ Исправить</button>
                  <button v-else-if="item.status === 'pending'"
                    class="relative overflow-hidden px-3 py-1.5 rounded-lg text-[10px] font-medium
                           text-amber-400 animate-pulse"
                    @click.stop="openDetail(item)"
                  >⏳ Ожидание…</button>
                </div>
              </div>
            </div>

            <!-- break grouped loop for non-all tab -->
            <template v-if="activeCategory !== 'all'">
              <!-- single iteration — break -->
            </template>
          </div>
        </template>
      </div>

      <!-- ═══ SIDEBAR (desktop) ═══ -->
      <Transition name="sb-ig">
        <aside v-if="showSidebar"
               class="hidden xl:flex shrink-0 flex-col gap-4 w-72">

          <!-- Health -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              🩺 Здоровье API
            </h3>
            <div class="flex flex-col gap-2.5">
              <div class="flex items-center justify-between text-xs">
                <span class="text-(--t-text-3)">Uptime</span>
                <span :class="[
                  'font-bold tabular-nums',
                  pStats.uptime >= 99.5 ? 'text-emerald-400' : pStats.uptime >= 95 ? 'text-amber-400' : 'text-rose-400',
                ]">{{ fmtPct(pStats.uptime) }}</span>
              </div>
              <div class="flex items-center justify-between text-xs">
                <span class="text-(--t-text-3)">Ср. ответ</span>
                <span :class="[
                  'font-bold tabular-nums',
                  pStats.avgResponseMs < 200 ? 'text-emerald-400' :
                  pStats.avgResponseMs < 500 ? 'text-amber-400' : 'text-rose-400',
                ]">{{ fmtMs(pStats.avgResponseMs) }}</span>
              </div>
              <div class="flex items-center justify-between text-xs">
                <span class="text-(--t-text-3)">API / 24 ч</span>
                <span class="font-bold text-sky-400 tabular-nums">{{ fmtNum(pStats.apiCalls24h) }}</span>
              </div>
              <div class="flex items-center justify-between text-xs">
                <span class="text-(--t-text-3)">Webhooks</span>
                <span class="font-bold text-violet-400 tabular-nums">{{ pStats.webhooksActive }}</span>
              </div>
            </div>
          </div>

          <!-- Errors -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              ⚠️ Ошибки ({{ errorIntegrations.length }})
            </h3>
            <div v-if="errorIntegrations.length === 0" class="text-center py-3">
              <p class="text-[10px] text-emerald-400">✓ Всё в порядке</p>
            </div>
            <div v-else class="flex flex-col gap-1.5">
              <button v-for="ei in errorIntegrations.slice(0, 5)" :key="ei.id"
                class="relative overflow-hidden flex items-center gap-2.5 px-3 py-2 rounded-xl
                       text-start hover:bg-(--t-card-hover) active:scale-[0.97] transition-all"
                @click="openDetail(ei)" @mousedown="ripple"
              >
                <span class="shrink-0 text-sm">{{ ei.icon }}</span>
                <span class="flex-1 text-[10px] text-rose-400 truncate">{{ ei.name }}</span>
                <span class="shrink-0 w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse" />
              </button>
            </div>
          </div>

          <!-- Recent API logs -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              📡 Последние API вызовы
            </h3>
            <div v-if="recentApiLogs.length === 0" class="text-center py-3">
              <p class="text-[10px] text-(--t-text-3)">Нет данных</p>
            </div>
            <div v-else class="flex flex-col gap-1">
              <div v-for="log in recentApiLogs" :key="log.id"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-[9px] tabular-nums">
                <span :class="['shrink-0 font-bold w-8', methodCls(log.method)]">
                  {{ log.method }}
                </span>
                <span class="flex-1 text-(--t-text-3) truncate">{{ log.endpoint }}</span>
                <span :class="['shrink-0 font-bold', httpStatusCls(log.status)]">
                  {{ log.status }}
                </span>
                <span class="shrink-0 text-(--t-text-3) w-10 text-end">
                  {{ fmtMs(log.duration) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Quick actions -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              Быстрые действия
            </h3>
            <div class="flex flex-col gap-1.5">
              <button v-for="act in [
                { label: '🏪 Маркетплейс интеграций', fn: () => emit('open-marketplace') },
                { label: '🔄 Обновить статусы',       fn: () => doRefresh() },
                { label: '📥 Экспорт конфигурации',   fn: () => doExport('json') },
                { label: '🐱 Экосистема Кота',        fn: () => { activeCategory = 'ecosystem' } },
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

    <!-- ═══════ MOBILE SIDEBAR DRAWER ═══════ -->
    <Transition name="dw-ig">
      <div v-if="showMobileSidebar"
           class="fixed inset-0 z-50 flex" @click.self="showMobileSidebar = false">
        <div class="absolute inset-0 bg-black/40" @click="showMobileSidebar = false" />
        <div class="relative z-10 ms-auto inline-size-72 max-w-[85vw] h-full bg-(--t-surface)
                    border-s border-(--t-border) overflow-y-auto p-4 flex flex-col gap-4">

          <div class="flex items-center justify-between mb-1">
            <h3 class="text-sm font-bold text-(--t-text)">🔌 Интеграции</h3>
            <button class="text-(--t-text-3) hover:text-(--t-text) transition-colors"
                    @click="showMobileSidebar = false">✕</button>
          </div>

          <div class="grid grid-cols-2 gap-2">
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Подключено</p>
              <p class="text-xs font-bold text-emerald-400 tabular-nums">{{ pStats.connected }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Ошибок</p>
              <p class="text-xs font-bold text-rose-400 tabular-nums">{{ pStats.errors }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">API / 24 ч</p>
              <p class="text-xs font-bold text-sky-400 tabular-nums">{{ fmtNum(pStats.apiCalls24h) }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Uptime</p>
              <p class="text-xs font-bold text-emerald-400 tabular-nums">{{ fmtPct(pStats.uptime) }}</p>
            </div>
          </div>

          <!-- Error list -->
          <div v-if="errorIntegrations.length > 0">
            <p class="text-[10px] font-bold text-(--t-text-3) uppercase mb-2">⚠️ Ошибки</p>
            <div class="flex flex-col gap-1">
              <button v-for="ei in errorIntegrations.slice(0, 4)" :key="ei.id"
                class="relative overflow-hidden flex items-center gap-2 px-3 py-2 rounded-xl
                       bg-rose-500/8 text-start active:scale-95 transition-all"
                @click="showMobileSidebar = false; openDetail(ei)" @mousedown="ripple"
              >
                <span class="text-sm">{{ ei.icon }}</span>
                <span class="text-[10px] text-rose-400 truncate">{{ ei.name }}</span>
              </button>
            </div>
          </div>

          <div class="flex flex-col gap-1.5 mt-2">
            <button class="relative overflow-hidden py-2.5 rounded-xl text-[10px] font-semibold
                           bg-(--t-primary) text-white active:scale-95 transition-all"
                    @click="showMobileSidebar = false; emit('open-marketplace')" @mousedown="ripple"
            >🏪 Маркетплейс</button>
            <button class="relative overflow-hidden py-2.5 rounded-xl text-[10px] font-semibold
                           border border-(--t-border)/50 text-(--t-text)
                           active:scale-95 transition-all"
                    @click="showMobileSidebar = false; doRefresh()" @mousedown="ripple"
            >🔄 Обновить</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ═══════ DETAIL DRAWER ═══════ -->
    <Transition name="detail-ig">
      <div v-if="showDetailDrawer && detailIntegration"
           class="fixed inset-0 z-50 flex" @click.self="closeDetail">
        <div class="absolute inset-0 bg-black/40" @click="closeDetail" />
        <div class="relative z-10 ms-auto inline-size-full sm:inline-size-[26rem] max-w-full h-full
                    bg-(--t-surface) border-s border-(--t-border) overflow-y-auto flex flex-col">

          <!-- Header -->
          <div class="sticky inset-block-start-0 z-10 flex items-center gap-3 px-5 py-4
                      bg-(--t-surface)/90 backdrop-blur-xl border-b border-(--t-border)/30">
            <div class="shrink-0 w-11 h-11 rounded-xl bg-(--t-bg)/60
                        flex items-center justify-center text-xl">
              {{ detailIntegration.icon }}
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-bold text-(--t-text) truncate">{{ detailIntegration.name }}</h3>
              <div class="flex items-center gap-2 mt-0.5">
                <span :class="[
                  'inline-flex items-center gap-1 px-1.5 py-px rounded-md text-[8px] font-medium',
                  STATUS_META[detailIntegration.status].cls,
                ]">
                  <span :class="['w-1.5 h-1.5 rounded-full', STATUS_META[detailIntegration.status].dot]" />
                  {{ STATUS_META[detailIntegration.status].label }}
                </span>
                <span v-if="detailIntegration.version"
                      class="text-[9px] text-(--t-text-3)">v{{ detailIntegration.version }}</span>
              </div>
            </div>
            <button class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="closeDetail">✕</button>
          </div>

          <!-- Body -->
          <div class="flex-1 p-5 flex flex-col gap-5">

            <p class="text-xs text-(--t-text-2) leading-relaxed">
              {{ detailIntegration.description }}
            </p>

            <!-- Status + metrics -->
            <div class="grid grid-cols-2 gap-3">
              <div class="text-center py-3.5 rounded-xl bg-(--t-bg)/50">
                <p class="text-[9px] text-(--t-text-3) mb-1">API / 24 ч</p>
                <p class="text-lg font-black text-sky-400 tabular-nums">
                  {{ fmtNum(detailIntegration.apiCalls24h) }}
                </p>
              </div>
              <div class="text-center py-3.5 rounded-xl bg-(--t-bg)/50">
                <p class="text-[9px] text-(--t-text-3) mb-1">Error rate</p>
                <p :class="[
                  'text-lg font-black tabular-nums',
                  detailIntegration.errorRate > 5 ? 'text-rose-400' :
                  detailIntegration.errorRate > 1 ? 'text-amber-400' : 'text-emerald-400',
                ]">{{ fmtPct(detailIntegration.errorRate) }}</p>
              </div>
            </div>

            <!-- Info list -->
            <div class="rounded-xl bg-(--t-bg)/50 p-4 flex flex-col gap-3">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase">Детали</h4>
              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Категория</span>
                <span class="text-(--t-text)">
                  {{ CATEGORY_META[detailIntegration.category]?.icon }}
                  {{ CATEGORY_META[detailIntegration.category]?.label }}
                </span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Версия</span>
                <span class="text-(--t-text) tabular-nums">{{ detailIntegration.version || '—' }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Последняя синхр.</span>
                <span class="text-(--t-text) tabular-nums">{{ fmtDate(detailIntegration.lastSync) }}</span>
              </div>
              <div v-if="detailIntegration.webhookUrl" class="flex flex-col gap-1">
                <span class="text-[10px] text-(--t-text-3)">Webhook</span>
                <code class="text-[9px] text-sky-400 bg-(--t-bg)/40 px-2 py-1.5 rounded-lg
                             break-all select-all">
                  {{ detailIntegration.webhookUrl }}
                </code>
              </div>
              <div v-if="detailIntegration.docsUrl" class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Документация</span>
                <a :href="detailIntegration.docsUrl" target="_blank"
                   class="text-(--t-primary) hover:underline">Открыть →</a>
              </div>
            </div>

            <!-- Tags -->
            <div v-if="detailIntegration.tags.length > 0" class="flex flex-wrap gap-1.5">
              <span v-for="tag in detailIntegration.tags" :key="tag"
                    class="px-2 py-0.5 rounded-lg text-[9px] font-medium
                           bg-(--t-primary)/8 text-(--t-primary)/70">{{ tag }}</span>
            </div>

            <!-- Test connection -->
            <button v-if="detailIntegration.status === 'connected' || detailIntegration.status === 'error'"
              :class="[
                'relative overflow-hidden inline-size-full py-2.5 rounded-xl text-xs font-medium',
                'border border-(--t-border)/50 text-(--t-text-2)',
                'hover:bg-(--t-card-hover) active:scale-95 transition-all',
                testingId === detailIntegration.id ? 'animate-pulse pointer-events-none' : '',
              ]"
              @click="doTestConnection(detailIntegration)" @mousedown="ripple"
            >
              {{ testingId === detailIntegration.id ? '⏳ Тестирование…' : '🧪 Тест соединения' }}
            </button>
          </div>

          <!-- Footer -->
          <div class="sticky inset-block-end-0 flex items-center gap-2 px-5 py-3
                      border-t border-(--t-border)/30 bg-(--t-surface)/90 backdrop-blur-xl">
            <template v-if="detailIntegration.status === 'connected'">
              <button
                class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-medium
                       border border-rose-500/40 text-rose-400 hover:bg-rose-500/10
                       active:scale-95 transition-all"
                @click="emit('disconnect', detailIntegration!.id); closeDetail()"
                @mousedown="ripple"
              >Отключить</button>
              <button
                class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-semibold
                       bg-(--t-primary) text-white hover:brightness-110
                       active:scale-95 transition-all"
                @click="openConnect(detailIntegration!); closeDetail()" @mousedown="ripple"
              >⚙️ Настроить</button>
            </template>
            <template v-else-if="detailIntegration.status === 'available' || detailIntegration.status === 'disabled'">
              <button
                class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-semibold
                       bg-(--t-primary) text-white hover:brightness-110
                       active:scale-95 transition-all"
                @click="openConnect(detailIntegration!); closeDetail()" @mousedown="ripple"
              >🔌 Подключить</button>
            </template>
            <template v-else-if="detailIntegration.status === 'error'">
              <button
                class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-semibold
                       bg-rose-500 text-white hover:brightness-110
                       active:scale-95 transition-all"
                @click="openConnect(detailIntegration!); closeDetail()" @mousedown="ripple"
              >⚠️ Переподключить</button>
            </template>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ═══════ CONNECT MODAL ═══════ -->
    <Transition name="modal-ig">
      <div v-if="showConnectModal && connectTarget"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="showConnectModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showConnectModal = false" />
        <div class="relative z-10 inline-size-full max-w-md bg-(--t-surface) rounded-2xl
                    border border-(--t-border)/60 shadow-2xl overflow-hidden
                    max-block-size-[85vh] overflow-y-auto">

          <div class="sticky inset-block-start-0 z-10 flex items-center justify-between px-5 py-4
                      border-b border-(--t-border)/30 bg-(--t-surface)/90 backdrop-blur-xl">
            <div class="flex items-center gap-3">
              <span class="text-xl">{{ connectTarget.icon }}</span>
              <div>
                <h3 class="text-sm font-bold text-(--t-text)">
                  {{ connectTarget.status === 'connected' ? '⚙️ Настройка' : '🔌 Подключение' }}
                </h3>
                <p class="text-[10px] text-(--t-text-3)">{{ connectTarget.name }}</p>
              </div>
            </div>
            <button class="w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="showConnectModal = false">✕</button>
          </div>

          <div class="p-5 flex flex-col gap-4">
            <!-- API Key -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">API Key</span>
              <input v-model="connectForm.apiKey" type="text"
                placeholder="sk_live_..."
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3) font-mono
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>

            <!-- Secret Key -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Secret Key</span>
              <input v-model="connectForm.secretKey" type="password"
                placeholder="••••••••"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3) font-mono
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>

            <!-- Webhook -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Webhook URL (опционально)</span>
              <input v-model="connectForm.webhookUrl" type="url"
                placeholder="https://your-domain.com/webhook/..."
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3) font-mono
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>

            <!-- Notes -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Заметки</span>
              <textarea v-model="connectForm.notes" rows="2"
                placeholder="Комментарии к подключению…"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3) resize-none
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>

            <!-- Security note -->
            <div class="rounded-xl bg-emerald-500/8 border border-emerald-500/20 p-3
                        flex items-start gap-2.5">
              <span class="shrink-0 text-sm">🔒</span>
              <p class="text-[10px] text-emerald-400/90 leading-relaxed">
                Ключи хранятся зашифрованно · AES-256 · tenant-isolated · audit log
              </p>
            </div>
          </div>

          <div class="sticky inset-block-end-0 flex gap-2 px-5 py-4
                      border-t border-(--t-border)/30 bg-(--t-surface)/90 backdrop-blur-xl">
            <button class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-medium
                           border border-(--t-border)/50 text-(--t-text-3)
                           hover:bg-(--t-card-hover) active:scale-95 transition-all"
                    @click="showConnectModal = false" @mousedown="ripple"
            >Отмена</button>
            <button class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-semibold
                           bg-(--t-primary) text-white hover:brightness-110 active:scale-95 transition-all
                           disabled:opacity-40 disabled:pointer-events-none"
                    :disabled="!connectForm.apiKey.trim()"
                    @click="submitConnect" @mousedown="ripple"
            >{{ connectTarget.status === 'connected' ? '💾 Сохранить' : '🔌 Подключить' }}</button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     STYLES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<style scoped>
/* Ripple — unique suffix ig (Integrations) */
@keyframes ripple-ig {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* line-clamp helper */
.line-clamp-1 {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 1;
  line-clamp: 1;
  overflow: hidden;
}
.line-clamp-2 {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  overflow: hidden;
}

/* No scrollbar */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Sidebar */
.sb-ig-enter-active,
.sb-ig-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.sb-ig-enter-from,
.sb-ig-leave-to {
  opacity: 0;
  transform: translateX(12px);
}

/* Drawers */
.dw-ig-enter-active,
.dw-ig-leave-active,
.detail-ig-enter-active,
.detail-ig-leave-active {
  transition: opacity 0.3s ease;
}
.dw-ig-enter-active > :last-child,
.dw-ig-leave-active > :last-child,
.detail-ig-enter-active > :last-child,
.detail-ig-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.dw-ig-enter-from,
.dw-ig-leave-to,
.detail-ig-enter-from,
.detail-ig-leave-to {
  opacity: 0;
}
.dw-ig-enter-from > :last-child,
.dw-ig-leave-to > :last-child,
.detail-ig-enter-from > :last-child,
.detail-ig-leave-to > :last-child {
  transform: translateX(100%);
}

/* Modal */
.modal-ig-enter-active,
.modal-ig-leave-active {
  transition: opacity 0.25s ease;
}
.modal-ig-enter-active > :nth-child(2),
.modal-ig-leave-active > :nth-child(2) {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}
.modal-ig-enter-from,
.modal-ig-leave-to {
  opacity: 0;
}
.modal-ig-enter-from > :nth-child(2),
.modal-ig-leave-to > :nth-child(2) {
  transform: scale(0.95) translateY(8px);
  opacity: 0;
}

/* Fade (export) */
.fade-ig-enter-active,
.fade-ig-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.fade-ig-enter-from,
.fade-ig-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
