<script setup lang="ts">
/**
 * TenantBranches.vue — Управление филиалами и локациями B2B Tenant Dashboard
 *
 * Вертикали:
 *   Beauty · Taxi · Food · Hotels · RealEstate · Flowers
 *   Fashion · Furniture · Fitness · Travel · default
 *
 * ───────────────────────────────────────────────────────────────
 *  Функционал:
 *   1.  Таблица филиалов (desktop) / карточки (mobile)
 *   2.  Карта всех филиалов (Yandex Maps placeholder)
 *   3.  Фильтры: город · статус · загрузка · поиск
 *   4.  Sidebar: общая статистика по сети филиалов
 *   5.  Drawer создания / редактирования филиала
 *   6.  Детальная карточка филиала: финансы, загрузка, персонал, графики
 *   7.  Смена статуса (активен/приостановлен/закрыт)
 *   8.  B2B: ИНН / КПП / банк. реквизиты / кредитный лимит
 *   9.  Full-screen · keyboard (Esc) · ripple-br
 *  10.  Mobile-first drawer sidebar · адаптивный grid
 *  11.  Glassmorphism · dark theme · 2026 design
 * ───────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useAuth, useTenant } from '@/stores'

/* ━━━━━━━━━━━━  TYPES  ━━━━━━━━━━━━ */

type BranchStatus   = 'active' | 'paused' | 'closed' | 'opening'
type LoadLevel      = 'low' | 'medium' | 'high' | 'overloaded'
type ViewMode       = 'table' | 'cards' | 'map'
type SortKey        = 'name' | 'revenue' | 'load' | 'status' | 'created'
type SortDir        = 'asc' | 'desc'
type FilterStatus   = BranchStatus | 'all'
type FilterLoad     = LoadLevel | 'all'

interface BranchContact {
  phone:   string
  email:   string
  manager: string
}

interface BranchFinance {
  revenue:       number
  expenses:      number
  profit:        number
  avgCheck:      number
  ordersCount:   number
  creditLimit:   number
  creditUsed:    number
}

interface BranchSchedule {
  mon: string
  tue: string
  wed: string
  thu: string
  fri: string
  sat: string
  sun: string
}

interface BranchItem {
  id:             number | string
  name:           string
  address:        string
  city:           string
  lat:            number
  lon:            number
  vertical:       string
  status:         BranchStatus
  load:           LoadLevel
  loadPercent:    number
  contact:        BranchContact
  finance:        BranchFinance
  schedule:       BranchSchedule
  staffCount:     number
  rating:         number
  reviewsCount:   number
  inn?:           string
  kpp?:           string
  bankAccount?:   string
  legalName?:     string
  createdAt:      string
  imageUrl?:      string
  tags:           string[]
}

interface VerticalBranchCfg {
  label:        string
  icon:         string
  accentColor:  string
  branchNoun:   string
  branchNounPl: string
}

interface NetworkStats {
  totalBranches:   number
  activeBranches:  number
  totalRevenue:    number
  totalOrders:     number
  avgLoad:         number
  avgRating:       number
  totalStaff:      number
  topCity:         string
}

/* ━━━━━━━━━━━━  PROPS / EMITS  ━━━━━━━━━━━━ */

const props = withDefaults(defineProps<{
  vertical?:     string
  branches?:     BranchItem[]
  networkStats?: NetworkStats | null
  cities?:       string[]
  loading?:      boolean
  period?:       string
}>(), {
  vertical:     'default',
  branches:     () => [],
  networkStats: null,
  cities:       () => [],
  loading:      false,
  period:       '30d',
})

const emit = defineEmits<{
  'create-branch':    [data: Record<string, unknown>]
  'update-branch':    [id: number | string, data: Record<string, unknown>]
  'delete-branch':    [id: number | string]
  'change-status':    [id: number | string, status: BranchStatus]
  'open-branch':      [id: number | string]
  'filter-change':    [filters: Record<string, string>]
  'sort-change':      [key: SortKey, dir: SortDir]
  'view-change':      [mode: ViewMode]
  'period-change':    [period: string]
  'refresh':          []
  'toggle-fullscreen':[]
}>()

const auth = useAuth()
const biz  = useTenant()

/* ━━━━━━━━━━━━  VERTICAL CONFIG  ━━━━━━━━━━━━ */

const VERTICAL_CFG: Record<string, VerticalBranchCfg> = {
  beauty:     { label: 'Салон красоты',   icon: '💄', accentColor: 'pink',    branchNoun: 'салон',     branchNounPl: 'салонов' },
  taxi:       { label: 'Такси',           icon: '🚕', accentColor: 'yellow',  branchNoun: 'парк',      branchNounPl: 'парков' },
  food:       { label: 'Еда и рестораны', icon: '🍽️', accentColor: 'orange',  branchNoun: 'точка',     branchNounPl: 'точек' },
  hotel:      { label: 'Отели',           icon: '🏨', accentColor: 'sky',     branchNoun: 'отель',     branchNounPl: 'отелей' },
  realEstate: { label: 'Недвижимость',    icon: '🏢', accentColor: 'emerald', branchNoun: 'офис',      branchNounPl: 'офисов' },
  flowers:    { label: 'Цветы',           icon: '💐', accentColor: 'rose',    branchNoun: 'магазин',   branchNounPl: 'магазинов' },
  fashion:    { label: 'Мода и одежда',   icon: '👗', accentColor: 'violet',  branchNoun: 'бутик',     branchNounPl: 'бутиков' },
  furniture:  { label: 'Мебель',          icon: '🛋️', accentColor: 'amber',   branchNoun: 'шоурум',    branchNounPl: 'шоурумов' },
  fitness:    { label: 'Фитнес',          icon: '💪', accentColor: 'lime',    branchNoun: 'зал',       branchNounPl: 'залов' },
  travel:     { label: 'Путешествия',     icon: '✈️', accentColor: 'cyan',    branchNoun: 'офис',      branchNounPl: 'офисов' },
  default:    { label: 'Бизнес',          icon: '📊', accentColor: 'indigo',  branchNoun: 'филиал',    branchNounPl: 'филиалов' },
}

const vc = computed<VerticalBranchCfg>(() =>
  VERTICAL_CFG[props.vertical] ?? VERTICAL_CFG.default,
)

/* ━━━━━━━━━━━━  CONSTANTS  ━━━━━━━━━━━━ */

const STATUS_META: Record<BranchStatus, { label: string; icon: string; cls: string; dot: string }> = {
  active:  { label: 'Активен',       icon: '✅', cls: 'bg-emerald-500/12 text-emerald-400', dot: 'bg-emerald-500' },
  paused:  { label: 'Приостановлен', icon: '⏸️', cls: 'bg-amber-500/12 text-amber-400',     dot: 'bg-amber-500' },
  closed:  { label: 'Закрыт',        icon: '🔒', cls: 'bg-zinc-500/12 text-zinc-400',       dot: 'bg-zinc-500' },
  opening: { label: 'Открывается',   icon: '🚧', cls: 'bg-sky-500/12 text-sky-400',         dot: 'bg-sky-500' },
}

const LOAD_META: Record<LoadLevel, { label: string; cls: string; barCls: string; pct: string }> = {
  low:        { label: 'Низкая',       cls: 'text-sky-400',     barCls: 'bg-sky-500',     pct: '0–30%' },
  medium:     { label: 'Средняя',      cls: 'text-emerald-400', barCls: 'bg-emerald-500', pct: '30–60%' },
  high:       { label: 'Высокая',      cls: 'text-amber-400',   barCls: 'bg-amber-500',   pct: '60–85%' },
  overloaded: { label: 'Перегрузка',   cls: 'text-rose-400',    barCls: 'bg-rose-500',    pct: '85–100%' },
}

const PERIODS: Array<{ key: string; label: string }> = [
  { key: '7d',  label: '7 дней' },
  { key: '30d', label: '30 дней' },
  { key: '90d', label: '90 дней' },
  { key: '1y',  label: 'Год' },
]

const SORT_COLS: Array<{ key: SortKey; label: string; icon: string }> = [
  { key: 'name',    label: 'Название', icon: '🏢' },
  { key: 'revenue', label: 'Выручка',  icon: '💰' },
  { key: 'load',    label: 'Загрузка', icon: '📊' },
  { key: 'status',  label: 'Статус',   icon: '🔘' },
  { key: 'created', label: 'Дата',     icon: '📅' },
]

const STATUS_OPTIONS: Array<{ key: FilterStatus; label: string }> = [
  { key: 'all',     label: 'Все статусы' },
  { key: 'active',  label: 'Активные' },
  { key: 'paused',  label: 'Приостановлены' },
  { key: 'closed',  label: 'Закрыты' },
  { key: 'opening', label: 'Открываются' },
]

const LOAD_OPTIONS: Array<{ key: FilterLoad; label: string }> = [
  { key: 'all',        label: 'Любая' },
  { key: 'low',        label: 'Низкая' },
  { key: 'medium',     label: 'Средняя' },
  { key: 'high',       label: 'Высокая' },
  { key: 'overloaded', label: 'Перегрузка' },
]

const LOAD_ORDER: Record<LoadLevel, number> = { low: 0, medium: 1, high: 2, overloaded: 3 }
const STATUS_ORDER: Record<BranchStatus, number> = { active: 0, opening: 1, paused: 2, closed: 3 }

/* ━━━━━━━━━━━━  STATE  ━━━━━━━━━━━━ */

const rootEl              = ref<HTMLElement | null>(null)
const isFullscreen        = ref(false)
const viewMode            = ref<ViewMode>('table')
const searchQuery         = ref('')
const filterCity          = ref<string>('all')
const filterStatus        = ref<FilterStatus>('all')
const filterLoad          = ref<FilterLoad>('all')
const sortKey             = ref<SortKey>('name')
const sortDir             = ref<SortDir>('asc')
const selectedPeriod      = ref(props.period)
const showSidebar         = ref(true)
const showMobileSidebar   = ref(false)
const showCreateDrawer    = ref(false)
const showDetailDrawer    = ref(false)
const detailBranch        = ref<BranchItem | null>(null)
const showDeleteConfirm   = ref(false)
const deleteTarget        = ref<BranchItem | null>(null)
const showStatusMenu      = ref<number | string | null>(null)
const refreshing          = ref(false)

/* ── Form ── */
const formData = reactive<{
  id:          number | string | null
  name:        string
  address:     string
  city:        string
  phone:       string
  email:       string
  manager:     string
  inn:         string
  kpp:         string
  bankAccount: string
  legalName:   string
  tags:        string
}>({
  id: null, name: '', address: '', city: '', phone: '', email: '',
  manager: '', inn: '', kpp: '', bankAccount: '', legalName: '', tags: '',
})

function resetForm() {
  formData.id = null
  formData.name = ''
  formData.address = ''
  formData.city = ''
  formData.phone = ''
  formData.email = ''
  formData.manager = ''
  formData.inn = ''
  formData.kpp = ''
  formData.bankAccount = ''
  formData.legalName = ''
  formData.tags = ''
}

/* ━━━━━━━━━━━━  COMPUTED  ━━━━━━━━━━━━ */

const cityOptions = computed(() => {
  const cities = props.cities.length > 0
    ? props.cities
    : [...new Set(props.branches.map((b) => b.city))].sort()
  return [{ key: 'all', label: 'Все города' }, ...cities.map((c) => ({ key: c, label: c }))]
})

const filteredBranches = computed<BranchItem[]>(() => {
  let list = [...props.branches]

  /* search */
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase()
    list = list.filter(
      (b) => b.name.toLowerCase().includes(q)
           || b.address.toLowerCase().includes(q)
           || b.city.toLowerCase().includes(q)
           || (b.contact.manager ?? '').toLowerCase().includes(q),
    )
  }

  /* city */
  if (filterCity.value !== 'all') {
    list = list.filter((b) => b.city === filterCity.value)
  }

  /* status */
  if (filterStatus.value !== 'all') {
    list = list.filter((b) => b.status === filterStatus.value)
  }

  /* load */
  if (filterLoad.value !== 'all') {
    list = list.filter((b) => b.load === filterLoad.value)
  }

  /* sort */
  list.sort((a, b) => {
    let cmp = 0
    switch (sortKey.value) {
      case 'name':    cmp = a.name.localeCompare(b.name); break
      case 'revenue': cmp = a.finance.revenue - b.finance.revenue; break
      case 'load':    cmp = LOAD_ORDER[a.load] - LOAD_ORDER[b.load]; break
      case 'status':  cmp = STATUS_ORDER[a.status] - STATUS_ORDER[b.status]; break
      case 'created': cmp = new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime(); break
    }
    return sortDir.value === 'asc' ? cmp : -cmp
  })

  return list
})

const stats = computed<NetworkStats>(() => {
  if (props.networkStats) return props.networkStats
  const all = props.branches
  const active = all.filter((b) => b.status === 'active')
  const totalRevenue = all.reduce((s, b) => s + b.finance.revenue, 0)
  const totalOrders  = all.reduce((s, b) => s + b.finance.ordersCount, 0)
  const avgLoad      = all.length > 0 ? Math.round(all.reduce((s, b) => s + b.loadPercent, 0) / all.length) : 0
  const avgRating    = all.length > 0 ? +(all.reduce((s, b) => s + b.rating, 0) / all.length).toFixed(1) : 0
  const totalStaff   = all.reduce((s, b) => s + b.staffCount, 0)
  const cityMap      = new Map<string, number>()
  for (const b of all) cityMap.set(b.city, (cityMap.get(b.city) ?? 0) + 1)
  const topCity      = cityMap.size > 0 ? [...cityMap.entries()].sort((a, b) => b[1] - a[1])[0][0] : '—'

  return {
    totalBranches: all.length, activeBranches: active.length,
    totalRevenue, totalOrders, avgLoad, avgRating, totalStaff, topCity,
  }
})

const activeFiltersCount = computed(() => {
  let c = 0
  if (filterCity.value !== 'all') c++
  if (filterStatus.value !== 'all') c++
  if (filterLoad.value !== 'all') c++
  if (searchQuery.value.trim()) c++
  return c
})

/* ━━━━━━━━━━━━  HELPERS  ━━━━━━━━━━━━ */

function fmtMoney(n: number): string {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M ₽`
  if (n >= 1_000)     return `${(n / 1_000).toFixed(0)}K ₽`
  return `${n} ₽`
}

function fmtNum(n: number): string {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M`
  if (n >= 1_000)     return `${(n / 1_000).toFixed(1)}K`
  return String(n)
}

function fmtDate(d: string): string {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('ru-RU', { day: '2-digit', month: 'short', year: 'numeric' })
}

function fmtPct(n: number): string {
  return `${Math.round(n)}%`
}

function loadLevelFromPct(pct: number): LoadLevel {
  if (pct < 30) return 'low'
  if (pct < 60) return 'medium'
  if (pct < 85) return 'high'
  return 'overloaded'
}

/* ━━━━━━━━━━━━  ACTIONS  ━━━━━━━━━━━━ */

function toggleSort(key: SortKey) {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
  emit('sort-change', sortKey.value, sortDir.value)
}

function setView(mode: ViewMode) {
  viewMode.value = mode
  emit('view-change', mode)
}

function setPeriod(p: string) {
  selectedPeriod.value = p
  emit('period-change', p)
}

function clearFilters() {
  searchQuery.value = ''
  filterCity.value = 'all'
  filterStatus.value = 'all'
  filterLoad.value = 'all'
}

function openCreate() {
  resetForm()
  showCreateDrawer.value = true
}

function openEdit(branch: BranchItem) {
  formData.id = branch.id
  formData.name = branch.name
  formData.address = branch.address
  formData.city = branch.city
  formData.phone = branch.contact.phone
  formData.email = branch.contact.email
  formData.manager = branch.contact.manager
  formData.inn = branch.inn ?? ''
  formData.kpp = branch.kpp ?? ''
  formData.bankAccount = branch.bankAccount ?? ''
  formData.legalName = branch.legalName ?? ''
  formData.tags = branch.tags.join(', ')
  showCreateDrawer.value = true
}

function submitForm() {
  const payload: Record<string, unknown> = { ...formData, tags: formData.tags.split(',').map((t) => t.trim()).filter(Boolean) }
  if (formData.id) {
    emit('update-branch', formData.id, payload)
  } else {
    emit('create-branch', payload)
  }
  showCreateDrawer.value = false
  resetForm()
}

function openDetail(branch: BranchItem) {
  detailBranch.value = branch
  showDetailDrawer.value = true
}

function closeDetail() {
  showDetailDrawer.value = false
  detailBranch.value = null
}

function changeStatus(branch: BranchItem, status: BranchStatus) {
  emit('change-status', branch.id, status)
  showStatusMenu.value = null
}

function confirmDelete(branch: BranchItem) {
  deleteTarget.value = branch
  showDeleteConfirm.value = true
}

function executeDelete() {
  if (deleteTarget.value) {
    emit('delete-branch', deleteTarget.value.id)
  }
  showDeleteConfirm.value = false
  deleteTarget.value = null
  closeDetail()
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
  const w = window.innerWidth
  showSidebar.value = w >= 1280
  if (w < 768) viewMode.value = 'cards'
}

/* ━━━━━━━━━━━━  KEYBOARD  ━━━━━━━━━━━━ */

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    if (showDeleteConfirm.value)  { showDeleteConfirm.value = false; return }
    if (showCreateDrawer.value)   { showCreateDrawer.value = false; return }
    if (showDetailDrawer.value)   { closeDetail(); return }
    if (showMobileSidebar.value)  { showMobileSidebar.value = false; return }
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
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-br_0.6s_ease-out]'
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
          <span class="text-2xl">🏢</span>
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <h1 class="text-base sm:text-lg font-extrabold text-(--t-text) truncate leading-snug">
                Филиалы и локации
              </h1>
              <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold
                           bg-(--t-primary)/15 text-(--t-primary) tabular-nums">
                {{ filteredBranches.length }}
              </span>
            </div>
            <p class="text-[10px] text-(--t-text-3) truncate">
              {{ vc.icon }} {{ vc.label }} · {{ stats.activeBranches }} активных {{ vc.branchNounPl }}
            </p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 flex-wrap">

          <!-- Search -->
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Поиск…"
              class="py-1.5 ps-8 pe-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                     text-xs text-(--t-text) placeholder:text-(--t-text-3)
                     focus:outline-none focus:border-(--t-primary)/50 transition-colors
                     inline-size-36 sm:inline-size-48"
            />
            <span class="absolute inset-inline-start-2.5 inset-block-start-1/2 -translate-y-1/2
                         text-xs text-(--t-text-3) pointer-events-none">🔍</span>
          </div>

          <!-- Period -->
          <div class="flex items-center rounded-xl border border-(--t-border)/50 overflow-hidden">
            <button
              v-for="p in PERIODS" :key="p.key"
              :class="[
                'relative overflow-hidden px-2.5 py-1.5 text-[10px] sm:text-xs font-medium transition-all',
                selectedPeriod === p.key
                  ? 'bg-(--t-primary) text-white'
                  : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
              ]"
              @click="setPeriod(p.key)" @mousedown="ripple"
            >{{ p.label }}</button>
          </div>

          <!-- Add branch -->
          <button
            class="relative overflow-hidden flex items-center gap-1.5 px-3 py-1.5 rounded-xl
                   text-xs font-semibold bg-(--t-primary) text-white hover:brightness-110
                   active:scale-95 transition-all"
            @click="openCreate" @mousedown="ripple"
          >
            <span class="text-sm">+</span>
            <span class="hidden sm:inline">Добавить {{ vc.branchNoun }}</span>
          </button>

          <!-- Refresh -->
          <button
            :class="[
              'relative overflow-hidden w-9 h-9 rounded-xl border border-(--t-border)/50',
              'flex items-center justify-center text-(--t-text-3)',
              'hover:bg-(--t-card-hover) active:scale-95 transition-all',
              refreshing ? 'animate-spin' : '',
            ]"
            @click="doRefresh" @mousedown="ripple" title="Обновить"
          >🔄</button>

          <!-- Mobile sidebar -->
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

      <!-- Filters row -->
      <div class="px-4 sm:px-6 pb-2 flex items-center gap-2 overflow-x-auto no-scrollbar">

        <!-- View mode (desktop) -->
        <div class="hidden md:flex items-center rounded-xl border border-(--t-border)/50 overflow-hidden">
          <button
            v-for="vm in ([
              { key: 'table' as ViewMode, icon: '📋' },
              { key: 'cards' as ViewMode, icon: '🃏' },
              { key: 'map'   as ViewMode, icon: '🗺️' },
            ])" :key="vm.key"
            :class="[
              'relative overflow-hidden px-2.5 py-1.5 text-xs transition-all',
              viewMode === vm.key
                ? 'bg-(--t-primary)/15 text-(--t-primary)'
                : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
            ]"
            @click="setView(vm.key)" @mousedown="ripple"
          >{{ vm.icon }}</button>
        </div>

        <!-- City -->
        <select
          v-model="filterCity"
          class="shrink-0 py-1.5 px-2.5 rounded-xl border border-(--t-border)/50
                 bg-(--t-bg)/60 text-xs text-(--t-text) focus:outline-none
                 focus:border-(--t-primary)/50 transition-colors appearance-none
                 cursor-pointer"
        >
          <option v-for="c in cityOptions" :key="c.key" :value="c.key">
            {{ c.label }}
          </option>
        </select>

        <!-- Status -->
        <select
          v-model="filterStatus"
          class="shrink-0 py-1.5 px-2.5 rounded-xl border border-(--t-border)/50
                 bg-(--t-bg)/60 text-xs text-(--t-text) focus:outline-none
                 focus:border-(--t-primary)/50 transition-colors appearance-none
                 cursor-pointer"
        >
          <option v-for="s in STATUS_OPTIONS" :key="s.key" :value="s.key">
            {{ s.label }}
          </option>
        </select>

        <!-- Load -->
        <select
          v-model="filterLoad"
          class="shrink-0 py-1.5 px-2.5 rounded-xl border border-(--t-border)/50
                 bg-(--t-bg)/60 text-xs text-(--t-text) focus:outline-none
                 focus:border-(--t-primary)/50 transition-colors appearance-none
                 cursor-pointer"
        >
          <option v-for="l in LOAD_OPTIONS" :key="l.key" :value="l.key">
            {{ l.label }}
          </option>
        </select>

        <!-- Active filters badge + clear -->
        <button
          v-if="activeFiltersCount > 0"
          class="shrink-0 flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[10px]
                 font-medium text-rose-400 bg-rose-500/10 hover:bg-rose-500/20
                 active:scale-95 transition-all"
          @click="clearFilters"
        >✕ Сбросить ({{ activeFiltersCount }})</button>

        <span class="flex-1" />

        <!-- Result count -->
        <span class="shrink-0 text-[10px] text-(--t-text-3) tabular-nums hidden sm:inline">
          {{ filteredBranches.length }} из {{ props.branches.length }}
        </span>
      </div>
    </header>

    <!-- ══════════════════════════════════════
         MAIN: CONTENT + SIDEBAR
    ══════════════════════════════════════ -->
    <div class="flex-1 flex gap-5 px-4 sm:px-6 py-5 max-w-screen-2xl mx-auto inline-size-full">

      <!-- ═══ MAIN CONTENT ═══ -->
      <div class="flex-1 flex flex-col gap-4 min-w-0">

        <!-- Loading skeleton -->
        <div v-if="props.loading && filteredBranches.length === 0" class="flex flex-col gap-3">
          <div v-for="n in 4" :key="n"
               class="flex items-center gap-3 p-4 rounded-2xl border border-(--t-border)/20
                      bg-(--t-surface)/30 animate-pulse">
            <div class="shrink-0 w-12 h-12 rounded-xl bg-(--t-border)/30" />
            <div class="flex-1">
              <div class="h-3 w-40 bg-(--t-border)/30 rounded mb-2" />
              <div class="h-2.5 w-64 bg-(--t-border)/20 rounded" />
            </div>
            <div class="shrink-0 h-6 w-16 bg-(--t-border)/20 rounded-lg" />
          </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="filteredBranches.length === 0 && !props.loading"
             class="py-20 text-center">
          <p class="text-5xl mb-3">🏗️</p>
          <p class="text-sm font-semibold text-(--t-text-2)">
            {{ activeFiltersCount > 0 ? 'Ничего не найдено' : `Нет ${vc.branchNounPl}` }}
          </p>
          <p class="text-[10px] text-(--t-text-3) mt-1">
            {{ activeFiltersCount > 0 ? 'Попробуйте изменить фильтры' : `Добавьте первый ${vc.branchNoun}` }}
          </p>
          <button
            v-if="activeFiltersCount === 0"
            class="relative overflow-hidden mt-4 px-5 py-2 rounded-xl text-xs font-semibold
                   bg-(--t-primary) text-white hover:brightness-110 active:scale-95 transition-all"
            @click="openCreate" @mousedown="ripple"
          >+ Добавить {{ vc.branchNoun }}</button>
        </div>

        <!-- ═══ TABLE VIEW (desktop) ═══ -->
        <div v-else-if="viewMode === 'table'"
             class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                    backdrop-blur-sm overflow-hidden">
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
                      {{ col.icon }} {{ col.label }}
                      <span v-if="sortKey === col.key" class="text-[9px]">
                        {{ sortDir === 'asc' ? '▲' : '▼' }}
                      </span>
                    </span>
                  </th>
                  <th class="px-4 py-3 text-start text-[10px] font-bold text-(--t-text-3)
                             uppercase tracking-wider">👥 Персонал</th>
                  <th class="px-4 py-3 text-start text-[10px] font-bold text-(--t-text-3)
                             uppercase tracking-wider">⭐ Рейтинг</th>
                  <th class="px-4 py-3 text-end text-[10px] font-bold text-(--t-text-3)
                             uppercase tracking-wider">Действия</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="branch in filteredBranches" :key="branch.id"
                    class="group/row border-b border-(--t-border)/15 hover:bg-(--t-card-hover)/40
                           transition-colors cursor-pointer"
                    @click="openDetail(branch)">

                  <!-- Name + address -->
                  <td class="px-4 py-3.5">
                    <div class="flex items-center gap-3">
                      <div class="shrink-0 w-10 h-10 rounded-xl bg-(--t-primary)/10
                                  flex items-center justify-center text-base">
                        {{ vc.icon }}
                      </div>
                      <div class="min-w-0">
                        <p class="text-xs font-bold text-(--t-text) truncate">{{ branch.name }}</p>
                        <p class="text-[10px] text-(--t-text-3) truncate">
                          📍 {{ branch.address }}, {{ branch.city }}
                        </p>
                      </div>
                    </div>
                  </td>

                  <!-- Revenue -->
                  <td class="px-4 py-3.5">
                    <p class="text-xs font-bold text-(--t-text) tabular-nums">
                      {{ fmtMoney(branch.finance.revenue) }}
                    </p>
                    <p class="text-[10px] text-(--t-text-3) tabular-nums">
                      {{ fmtNum(branch.finance.ordersCount) }} заказов
                    </p>
                  </td>

                  <!-- Load -->
                  <td class="px-4 py-3.5">
                    <div class="flex items-center gap-2">
                      <div class="flex-1 h-1.5 rounded-full bg-(--t-border)/20 overflow-hidden
                                  max-w-20">
                        <div
                          :class="['h-full rounded-full transition-all', LOAD_META[branch.load].barCls]"
                          :style="{ inlineSize: `${branch.loadPercent}%` }"
                        />
                      </div>
                      <span :class="['text-[10px] font-medium tabular-nums', LOAD_META[branch.load].cls]">
                        {{ fmtPct(branch.loadPercent) }}
                      </span>
                    </div>
                  </td>

                  <!-- Status -->
                  <td class="px-4 py-3.5">
                    <span :class="[
                      'inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-medium',
                      STATUS_META[branch.status].cls,
                    ]">
                      <span :class="['w-1.5 h-1.5 rounded-full', STATUS_META[branch.status].dot]" />
                      {{ STATUS_META[branch.status].label }}
                    </span>
                  </td>

                  <!-- Created -->
                  <td class="px-4 py-3.5 text-[10px] text-(--t-text-3) tabular-nums">
                    {{ fmtDate(branch.createdAt) }}
                  </td>

                  <!-- Staff -->
                  <td class="px-4 py-3.5 text-xs text-(--t-text-2) tabular-nums">
                    {{ branch.staffCount }}
                  </td>

                  <!-- Rating -->
                  <td class="px-4 py-3.5">
                    <span class="text-xs text-amber-400 tabular-nums">
                      ⭐ {{ branch.rating.toFixed(1) }}
                    </span>
                    <span class="text-[10px] text-(--t-text-3) ms-1">
                      ({{ branch.reviewsCount }})
                    </span>
                  </td>

                  <!-- Actions -->
                  <td class="px-4 py-3.5 text-end">
                    <div class="flex items-center justify-end gap-1
                                opacity-0 group-hover/row:opacity-100 transition-opacity">
                      <button
                        class="relative overflow-hidden w-7 h-7 rounded-lg flex items-center justify-center
                               text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)
                               active:scale-90 transition-all"
                        @click.stop="openEdit(branch)" @mousedown="ripple" title="Редактировать"
                      >✏️</button>
                      <button
                        class="relative overflow-hidden w-7 h-7 rounded-lg flex items-center justify-center
                               text-(--t-text-3) hover:text-rose-400 hover:bg-rose-500/10
                               active:scale-90 transition-all"
                        @click.stop="confirmDelete(branch)" @mousedown="ripple" title="Удалить"
                      >🗑️</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ═══ CARDS VIEW (mobile & toggle) ═══ -->
        <div v-else-if="viewMode === 'cards'"
             class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <button
            v-for="branch in filteredBranches" :key="branch.id"
            class="group/card relative overflow-hidden text-start rounded-2xl
                   border border-(--t-border)/30 bg-(--t-surface)/50 backdrop-blur-sm
                   hover:border-(--t-border)/60 hover:shadow-lg hover:shadow-black/5
                   active:scale-[0.98] transition-all p-4"
            @click="openDetail(branch)" @mousedown="ripple"
          >
            <!-- Status dot -->
            <span :class="[
              'absolute inset-block-start-3 inset-inline-end-3 w-2.5 h-2.5 rounded-full',
              STATUS_META[branch.status].dot,
            ]" />

            <!-- Header -->
            <div class="flex items-center gap-3 mb-3">
              <div class="shrink-0 w-11 h-11 rounded-xl bg-(--t-primary)/10
                          flex items-center justify-center text-lg">
                {{ vc.icon }}
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-(--t-text) truncate">{{ branch.name }}</p>
                <p class="text-[10px] text-(--t-text-3) truncate">
                  📍 {{ branch.city }}
                </p>
              </div>
            </div>

            <!-- Address -->
            <p class="text-[10px] text-(--t-text-3) mb-3 truncate">{{ branch.address }}</p>

            <!-- Metrics row -->
            <div class="grid grid-cols-3 gap-2 mb-3">
              <div class="rounded-xl bg-(--t-bg)/50 p-2 text-center">
                <p class="text-[9px] text-(--t-text-3)">Выручка</p>
                <p class="text-[11px] font-bold text-(--t-text) tabular-nums">
                  {{ fmtMoney(branch.finance.revenue) }}
                </p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-2 text-center">
                <p class="text-[9px] text-(--t-text-3)">Загрузка</p>
                <p :class="['text-[11px] font-bold tabular-nums', LOAD_META[branch.load].cls]">
                  {{ fmtPct(branch.loadPercent) }}
                </p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-2 text-center">
                <p class="text-[9px] text-(--t-text-3)">Рейтинг</p>
                <p class="text-[11px] font-bold text-amber-400 tabular-nums">
                  ⭐ {{ branch.rating.toFixed(1) }}
                </p>
              </div>
            </div>

            <!-- Load bar -->
            <div class="h-1.5 rounded-full bg-(--t-border)/20 overflow-hidden">
              <div
                :class="['h-full rounded-full transition-all', LOAD_META[branch.load].barCls]"
                :style="{ inlineSize: `${branch.loadPercent}%` }"
              />
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-(--t-border)/15">
              <span :class="[
                'inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-medium',
                STATUS_META[branch.status].cls,
              ]">
                <span :class="['w-1.5 h-1.5 rounded-full', STATUS_META[branch.status].dot]" />
                {{ STATUS_META[branch.status].label }}
              </span>
              <span class="text-[10px] text-(--t-text-3)">
                👥 {{ branch.staffCount }} · 📞 {{ branch.contact.phone }}
              </span>
            </div>
          </button>
        </div>

        <!-- ═══ MAP VIEW ═══ -->
        <div v-else-if="viewMode === 'map'"
             class="flex-1 rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                    backdrop-blur-sm overflow-hidden relative"
             style="min-block-size: 500px">
          <!-- Placeholder: integrate Yandex Maps / Leaflet here -->
          <div class="absolute inset-0 flex flex-col items-center justify-center">
            <p class="text-6xl mb-4">🗺️</p>
            <p class="text-sm font-semibold text-(--t-text-2)">Карта филиалов</p>
            <p class="text-[10px] text-(--t-text-3) mt-1 text-center max-w-xs">
              Подключите Yandex Maps API или Leaflet для отображения {{ filteredBranches.length }} {{ vc.branchNounPl }}
            </p>
            <!-- Mini branch list below map placeholder -->
            <div class="mt-6 inline-size-full max-w-md px-4 flex flex-col gap-2">
              <div v-for="branch in filteredBranches.slice(0, 5)" :key="branch.id"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-xl bg-(--t-bg)/40
                          border border-(--t-border)/20 cursor-pointer hover:border-(--t-border)/40
                          transition-all"
                   @click="openDetail(branch)">
                <span :class="['w-2.5 h-2.5 rounded-full shrink-0', STATUS_META[branch.status].dot]" />
                <span class="text-xs text-(--t-text) truncate flex-1">{{ branch.name }}</span>
                <span class="text-[10px] text-(--t-text-3) shrink-0">{{ branch.city }}</span>
              </div>
              <p v-if="filteredBranches.length > 5"
                 class="text-center text-[10px] text-(--t-text-3)">
                +{{ filteredBranches.length - 5 }} ещё
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ SIDEBAR (desktop) ═══ -->
      <Transition name="sb-br">
        <aside v-if="showSidebar"
               class="hidden xl:flex shrink-0 flex-col gap-4 w-72">

          <!-- Network stats -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              Сеть {{ vc.branchNounPl }}
            </h3>
            <div class="grid grid-cols-2 gap-2.5">
              <div class="rounded-xl bg-(--t-bg)/50 p-3">
                <p class="text-[10px] text-(--t-text-3)">Всего</p>
                <p class="text-lg font-extrabold text-(--t-text) tabular-nums">
                  {{ stats.totalBranches }}
                </p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-3">
                <p class="text-[10px] text-(--t-text-3)">Активных</p>
                <p class="text-lg font-extrabold text-emerald-400 tabular-nums">
                  {{ stats.activeBranches }}
                </p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-3">
                <p class="text-[10px] text-(--t-text-3)">Выручка</p>
                <p class="text-sm font-extrabold text-(--t-text) tabular-nums">
                  {{ fmtMoney(stats.totalRevenue) }}
                </p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-3">
                <p class="text-[10px] text-(--t-text-3)">Заказов</p>
                <p class="text-sm font-extrabold text-(--t-text) tabular-nums">
                  {{ fmtNum(stats.totalOrders) }}
                </p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-3">
                <p class="text-[10px] text-(--t-text-3)">Ср. загрузка</p>
                <p :class="[
                  'text-sm font-extrabold tabular-nums',
                  LOAD_META[loadLevelFromPct(stats.avgLoad)].cls,
                ]">{{ fmtPct(stats.avgLoad) }}</p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-3">
                <p class="text-[10px] text-(--t-text-3)">Ср. рейтинг</p>
                <p class="text-sm font-extrabold text-amber-400 tabular-nums">
                  ⭐ {{ stats.avgRating }}
                </p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-3">
                <p class="text-[10px] text-(--t-text-3)">Персонал</p>
                <p class="text-sm font-extrabold text-(--t-text) tabular-nums">
                  {{ fmtNum(stats.totalStaff) }}
                </p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-3">
                <p class="text-[10px] text-(--t-text-3)">Топ город</p>
                <p class="text-xs font-extrabold text-(--t-text) truncate">
                  {{ stats.topCity }}
                </p>
              </div>
            </div>
          </div>

          <!-- Status breakdown -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              По статусам
            </h3>
            <div class="flex flex-col gap-2">
              <div v-for="(meta, skey) in STATUS_META" :key="skey"
                   class="flex items-center gap-2.5">
                <span :class="['shrink-0 w-2.5 h-2.5 rounded-full', meta.dot]" />
                <span class="flex-1 text-xs text-(--t-text-2)">{{ meta.label }}</span>
                <span class="shrink-0 text-[10px] text-(--t-text-3) tabular-nums font-medium">
                  {{ props.branches.filter((b) => b.status === skey).length }}
                </span>
              </div>
            </div>
          </div>

          <!-- Load breakdown -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              По загрузке
            </h3>
            <div class="flex flex-col gap-2">
              <div v-for="(meta, lkey) in LOAD_META" :key="lkey"
                   class="flex items-center gap-2.5">
                <span :class="['shrink-0 w-3 h-1.5 rounded-full', meta.barCls]" />
                <span class="flex-1 text-xs text-(--t-text-2)">{{ meta.label }}</span>
                <span class="shrink-0 text-[10px] text-(--t-text-3) tabular-nums font-medium">
                  {{ props.branches.filter((b) => b.load === lkey).length }}
                </span>
              </div>
            </div>
          </div>

          <!-- Top branches -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-3">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-2 px-1">
              Лидеры по выручке
            </h3>
            <div class="flex flex-col gap-0.5">
              <button
                v-for="(branch, idx) in [...props.branches]
                  .sort((a, b) => b.finance.revenue - a.finance.revenue)
                  .slice(0, 5)"
                :key="branch.id"
                class="relative overflow-hidden flex items-center gap-2.5 px-3 py-2 rounded-xl
                       text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)
                       active:scale-[0.97] transition-all"
                @click="openDetail(branch)" @mousedown="ripple"
              >
                <span class="shrink-0 w-5 h-5 rounded-full bg-(--t-primary)/10
                             flex items-center justify-center text-[9px] font-bold
                             text-(--t-primary) tabular-nums">{{ idx + 1 }}</span>
                <span class="flex-1 text-xs text-start truncate">{{ branch.name }}</span>
                <span class="shrink-0 text-[10px] font-medium text-(--t-text) tabular-nums">
                  {{ fmtMoney(branch.finance.revenue) }}
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
    <Transition name="dw-br">
      <div v-if="showMobileSidebar"
           class="fixed inset-0 z-50 flex" @click.self="showMobileSidebar = false">
        <div class="absolute inset-0 bg-black/40" @click="showMobileSidebar = false" />

        <div class="relative z-10 ms-auto inline-size-72 max-w-[85vw] h-full bg-(--t-surface)
                    border-s border-(--t-border) overflow-y-auto p-4 flex flex-col gap-4">

          <div class="flex items-center justify-between mb-1">
            <h3 class="text-sm font-bold text-(--t-text)">🏢 Статистика</h3>
            <button class="text-(--t-text-3) hover:text-(--t-text) transition-colors"
                    @click="showMobileSidebar = false">✕</button>
          </div>

          <!-- Mobile stats grid -->
          <div class="grid grid-cols-2 gap-2">
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Всего</p>
              <p class="text-sm font-bold text-(--t-text) tabular-nums">{{ stats.totalBranches }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Активных</p>
              <p class="text-sm font-bold text-emerald-400 tabular-nums">{{ stats.activeBranches }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Выручка</p>
              <p class="text-xs font-bold text-(--t-text) tabular-nums">{{ fmtMoney(stats.totalRevenue) }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Загрузка</p>
              <p :class="['text-xs font-bold tabular-nums', LOAD_META[loadLevelFromPct(stats.avgLoad)].cls]">
                {{ fmtPct(stats.avgLoad) }}
              </p>
            </div>
          </div>

          <!-- Mobile status breakdown -->
          <div>
            <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase mb-2">Статусы</h4>
            <div class="flex flex-col gap-1.5">
              <div v-for="(meta, skey) in STATUS_META" :key="skey" class="flex items-center gap-2">
                <span :class="['w-2 h-2 rounded-full shrink-0', meta.dot]" />
                <span class="flex-1 text-xs text-(--t-text-2)">{{ meta.label }}</span>
                <span class="text-[10px] text-(--t-text-3) tabular-nums">
                  {{ props.branches.filter((b) => b.status === skey).length }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════
         BRANCH DETAIL DRAWER
    ══════════════════════════════════════ -->
    <Transition name="detail-br">
      <div v-if="showDetailDrawer && detailBranch"
           class="fixed inset-0 z-50 flex" @click.self="closeDetail">
        <div class="absolute inset-0 bg-black/40" @click="closeDetail" />

        <div class="relative z-10 ms-auto inline-size-full sm:inline-size-[28rem] max-w-full h-full
                    bg-(--t-surface) border-s border-(--t-border) overflow-y-auto flex flex-col">

          <!-- Detail header -->
          <div class="sticky inset-block-start-0 z-10 flex items-center gap-3 px-5 py-4
                      bg-(--t-surface)/90 backdrop-blur-xl border-b border-(--t-border)/30">
            <div class="shrink-0 w-11 h-11 rounded-xl bg-(--t-primary)/10
                        flex items-center justify-center text-lg">
              {{ vc.icon }}
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-bold text-(--t-text) truncate">{{ detailBranch.name }}</h3>
              <div class="flex items-center gap-2 mt-0.5">
                <span :class="[
                  'inline-flex items-center gap-1 px-1.5 py-px rounded-md text-[8px] font-medium',
                  STATUS_META[detailBranch.status].cls,
                ]">
                  <span :class="['w-1.5 h-1.5 rounded-full', STATUS_META[detailBranch.status].dot]" />
                  {{ STATUS_META[detailBranch.status].label }}
                </span>
                <span class="text-[10px] text-(--t-text-3)">{{ detailBranch.city }}</span>
              </div>
            </div>
            <button class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="closeDetail">✕</button>
          </div>

          <!-- Detail body -->
          <div class="flex-1 p-5 flex flex-col gap-5">

            <!-- Address & contact -->
            <div class="rounded-xl bg-(--t-bg)/50 p-4 flex flex-col gap-2.5">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase">Контакты</h4>
              <div class="flex items-center gap-2 text-xs text-(--t-text-2)">
                <span class="shrink-0">📍</span>
                <span>{{ detailBranch.address }}, {{ detailBranch.city }}</span>
              </div>
              <div class="flex items-center gap-2 text-xs text-(--t-text-2)">
                <span class="shrink-0">📞</span>
                <span>{{ detailBranch.contact.phone }}</span>
              </div>
              <div class="flex items-center gap-2 text-xs text-(--t-text-2)">
                <span class="shrink-0">✉️</span>
                <span>{{ detailBranch.contact.email }}</span>
              </div>
              <div class="flex items-center gap-2 text-xs text-(--t-text-2)">
                <span class="shrink-0">👤</span>
                <span>{{ detailBranch.contact.manager }}</span>
              </div>
            </div>

            <!-- Finance metrics -->
            <div class="rounded-xl bg-(--t-bg)/50 p-4">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase mb-3">Финансы</h4>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <p class="text-[9px] text-(--t-text-3)">Выручка</p>
                  <p class="text-sm font-extrabold text-(--t-text) tabular-nums">
                    {{ fmtMoney(detailBranch.finance.revenue) }}
                  </p>
                </div>
                <div>
                  <p class="text-[9px] text-(--t-text-3)">Расходы</p>
                  <p class="text-sm font-extrabold text-rose-400 tabular-nums">
                    {{ fmtMoney(detailBranch.finance.expenses) }}
                  </p>
                </div>
                <div>
                  <p class="text-[9px] text-(--t-text-3)">Прибыль</p>
                  <p :class="[
                    'text-sm font-extrabold tabular-nums',
                    detailBranch.finance.profit >= 0 ? 'text-emerald-400' : 'text-rose-400',
                  ]">{{ fmtMoney(detailBranch.finance.profit) }}</p>
                </div>
                <div>
                  <p class="text-[9px] text-(--t-text-3)">Ср. чек</p>
                  <p class="text-sm font-extrabold text-(--t-text) tabular-nums">
                    {{ fmtMoney(detailBranch.finance.avgCheck) }}
                  </p>
                </div>
                <div>
                  <p class="text-[9px] text-(--t-text-3)">Заказов</p>
                  <p class="text-sm font-extrabold text-(--t-text) tabular-nums">
                    {{ fmtNum(detailBranch.finance.ordersCount) }}
                  </p>
                </div>
                <div>
                  <p class="text-[9px] text-(--t-text-3)">Рейтинг</p>
                  <p class="text-sm font-extrabold text-amber-400 tabular-nums">
                    ⭐ {{ detailBranch.rating.toFixed(1) }}
                    <span class="text-[10px] text-(--t-text-3) font-normal">
                      ({{ detailBranch.reviewsCount }})
                    </span>
                  </p>
                </div>
              </div>
            </div>

            <!-- Load & staff -->
            <div class="rounded-xl bg-(--t-bg)/50 p-4">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase mb-3">Загрузка</h4>
              <div class="flex items-center gap-3 mb-3">
                <div class="flex-1 h-2 rounded-full bg-(--t-border)/20 overflow-hidden">
                  <div
                    :class="['h-full rounded-full transition-all', LOAD_META[detailBranch.load].barCls]"
                    :style="{ inlineSize: `${detailBranch.loadPercent}%` }"
                  />
                </div>
                <span :class="['text-sm font-bold tabular-nums', LOAD_META[detailBranch.load].cls]">
                  {{ fmtPct(detailBranch.loadPercent) }}
                </span>
              </div>
              <div class="flex items-center justify-between text-xs text-(--t-text-2)">
                <span>👥 Персонал: {{ detailBranch.staffCount }}</span>
                <span>📅 С {{ fmtDate(detailBranch.createdAt) }}</span>
              </div>
            </div>

            <!-- B2B legal details -->
            <div v-if="detailBranch.inn || detailBranch.legalName"
                 class="rounded-xl bg-(--t-bg)/50 p-4">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase mb-3">
                Юридические данные (B2B)
              </h4>
              <div class="flex flex-col gap-2 text-xs text-(--t-text-2)">
                <div v-if="detailBranch.legalName" class="flex justify-between">
                  <span class="text-(--t-text-3)">Юр. лицо</span>
                  <span>{{ detailBranch.legalName }}</span>
                </div>
                <div v-if="detailBranch.inn" class="flex justify-between">
                  <span class="text-(--t-text-3)">ИНН</span>
                  <span class="tabular-nums">{{ detailBranch.inn }}</span>
                </div>
                <div v-if="detailBranch.kpp" class="flex justify-between">
                  <span class="text-(--t-text-3)">КПП</span>
                  <span class="tabular-nums">{{ detailBranch.kpp }}</span>
                </div>
                <div v-if="detailBranch.bankAccount" class="flex justify-between">
                  <span class="text-(--t-text-3)">Р/С</span>
                  <span class="tabular-nums">{{ detailBranch.bankAccount }}</span>
                </div>
                <div v-if="detailBranch.finance.creditLimit > 0" class="flex justify-between">
                  <span class="text-(--t-text-3)">Кредитный лимит</span>
                  <span class="tabular-nums">{{ fmtMoney(detailBranch.finance.creditLimit) }}</span>
                </div>
                <div v-if="detailBranch.finance.creditUsed > 0" class="flex justify-between">
                  <span class="text-(--t-text-3)">Использовано</span>
                  <span class="tabular-nums text-amber-400">{{ fmtMoney(detailBranch.finance.creditUsed) }}</span>
                </div>
              </div>
            </div>

            <!-- Tags -->
            <div v-if="detailBranch.tags.length > 0" class="flex flex-wrap gap-1.5">
              <span v-for="tag in detailBranch.tags" :key="tag"
                    class="px-2 py-0.5 rounded-lg text-[10px] font-medium
                           bg-(--t-card-hover) text-(--t-text-3)">
                {{ tag }}
              </span>
            </div>

            <!-- Status change -->
            <div class="rounded-xl bg-(--t-bg)/50 p-4">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase mb-3">Изменить статус</h4>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="(meta, skey) in STATUS_META" :key="skey"
                  :class="[
                    'relative overflow-hidden flex items-center gap-1.5 px-3 py-1.5 rounded-xl',
                    'text-[10px] font-medium transition-all active:scale-95',
                    detailBranch.status === skey
                      ? meta.cls + ' ring-1 ring-current/25'
                      : 'text-(--t-text-3) border border-(--t-border)/30 hover:border-(--t-border)/50',
                  ]"
                  :disabled="detailBranch.status === skey"
                  @click="changeStatus(detailBranch!, skey as BranchStatus)" @mousedown="ripple"
                >
                  <span :class="['w-1.5 h-1.5 rounded-full', meta.dot]" />
                  {{ meta.label }}
                </button>
              </div>
            </div>
          </div>

          <!-- Detail footer -->
          <div class="sticky inset-block-end-0 flex items-center gap-2 px-5 py-3
                      border-t border-(--t-border)/30 bg-(--t-surface)/90 backdrop-blur-xl">
            <button
              class="relative overflow-hidden flex-1 flex items-center justify-center gap-1.5
                     py-2.5 rounded-xl text-xs font-semibold
                     bg-(--t-primary) text-white hover:brightness-110
                     active:scale-95 transition-all"
              @click="openEdit(detailBranch!)" @mousedown="ripple"
            >✏️ Редактировать</button>
            <button
              class="relative overflow-hidden flex items-center justify-center gap-1.5
                     py-2.5 px-4 rounded-xl text-xs font-medium
                     border border-rose-500/25 text-rose-400
                     hover:bg-rose-500/10 active:scale-95 transition-all"
              @click="confirmDelete(detailBranch!)" @mousedown="ripple"
            >🗑️</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════
         CREATE / EDIT DRAWER
    ══════════════════════════════════════ -->
    <Transition name="detail-br">
      <div v-if="showCreateDrawer"
           class="fixed inset-0 z-50 flex" @click.self="showCreateDrawer = false">
        <div class="absolute inset-0 bg-black/40" @click="showCreateDrawer = false" />

        <div class="relative z-10 ms-auto inline-size-full sm:inline-size-96 max-w-full h-full
                    bg-(--t-surface) border-s border-(--t-border) overflow-y-auto flex flex-col">

          <!-- Form header -->
          <div class="sticky inset-block-start-0 z-10 flex items-center justify-between px-5 py-4
                      bg-(--t-surface)/90 backdrop-blur-xl border-b border-(--t-border)/30">
            <h3 class="text-sm font-bold text-(--t-text)">
              {{ formData.id ? `✏️ Редактировать ${vc.branchNoun}` : `+ Новый ${vc.branchNoun}` }}
            </h3>
            <button class="w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="showCreateDrawer = false">✕</button>
          </div>

          <!-- Form body -->
          <div class="flex-1 p-5 flex flex-col gap-4">

            <!-- Basic info -->
            <div class="flex flex-col gap-3">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase">Основная информация</h4>

              <label class="flex flex-col gap-1">
                <span class="text-[10px] text-(--t-text-3)">Название *</span>
                <input v-model="formData.name" type="text"
                       placeholder="Центральный салон"
                       class="py-2 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                              text-xs text-(--t-text) placeholder:text-(--t-text-3)
                              focus:outline-none focus:border-(--t-primary)/50 transition-colors" />
              </label>

              <label class="flex flex-col gap-1">
                <span class="text-[10px] text-(--t-text-3)">Адрес *</span>
                <input v-model="formData.address" type="text"
                       placeholder="ул. Пушкина, д. 10"
                       class="py-2 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                              text-xs text-(--t-text) placeholder:text-(--t-text-3)
                              focus:outline-none focus:border-(--t-primary)/50 transition-colors" />
              </label>

              <label class="flex flex-col gap-1">
                <span class="text-[10px] text-(--t-text-3)">Город *</span>
                <input v-model="formData.city" type="text"
                       placeholder="Москва"
                       class="py-2 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                              text-xs text-(--t-text) placeholder:text-(--t-text-3)
                              focus:outline-none focus:border-(--t-primary)/50 transition-colors" />
              </label>
            </div>

            <!-- Contact -->
            <div class="flex flex-col gap-3">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase">Контакты</h4>

              <label class="flex flex-col gap-1">
                <span class="text-[10px] text-(--t-text-3)">Телефон</span>
                <input v-model="formData.phone" type="tel"
                       placeholder="+7 (999) 123-45-67"
                       class="py-2 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                              text-xs text-(--t-text) placeholder:text-(--t-text-3)
                              focus:outline-none focus:border-(--t-primary)/50 transition-colors" />
              </label>

              <label class="flex flex-col gap-1">
                <span class="text-[10px] text-(--t-text-3)">Email</span>
                <input v-model="formData.email" type="email"
                       placeholder="salon@example.com"
                       class="py-2 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                              text-xs text-(--t-text) placeholder:text-(--t-text-3)
                              focus:outline-none focus:border-(--t-primary)/50 transition-colors" />
              </label>

              <label class="flex flex-col gap-1">
                <span class="text-[10px] text-(--t-text-3)">Менеджер</span>
                <input v-model="formData.manager" type="text"
                       placeholder="Иванов Иван"
                       class="py-2 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                              text-xs text-(--t-text) placeholder:text-(--t-text-3)
                              focus:outline-none focus:border-(--t-primary)/50 transition-colors" />
              </label>
            </div>

            <!-- B2B Legal -->
            <div class="flex flex-col gap-3">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase">Юридические данные (B2B)</h4>

              <label class="flex flex-col gap-1">
                <span class="text-[10px] text-(--t-text-3)">Юр. лицо</span>
                <input v-model="formData.legalName" type="text"
                       placeholder="ООО «Ромашка»"
                       class="py-2 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                              text-xs text-(--t-text) placeholder:text-(--t-text-3)
                              focus:outline-none focus:border-(--t-primary)/50 transition-colors" />
              </label>

              <div class="grid grid-cols-2 gap-3">
                <label class="flex flex-col gap-1">
                  <span class="text-[10px] text-(--t-text-3)">ИНН</span>
                  <input v-model="formData.inn" type="text"
                         placeholder="7701234567"
                         class="py-2 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                                text-xs text-(--t-text) placeholder:text-(--t-text-3)
                                focus:outline-none focus:border-(--t-primary)/50 transition-colors" />
                </label>
                <label class="flex flex-col gap-1">
                  <span class="text-[10px] text-(--t-text-3)">КПП</span>
                  <input v-model="formData.kpp" type="text"
                         placeholder="770101001"
                         class="py-2 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                                text-xs text-(--t-text) placeholder:text-(--t-text-3)
                                focus:outline-none focus:border-(--t-primary)/50 transition-colors" />
                </label>
              </div>

              <label class="flex flex-col gap-1">
                <span class="text-[10px] text-(--t-text-3)">Расчётный счёт</span>
                <input v-model="formData.bankAccount" type="text"
                       placeholder="40702810..."
                       class="py-2 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                              text-xs text-(--t-text) placeholder:text-(--t-text-3)
                              focus:outline-none focus:border-(--t-primary)/50 transition-colors" />
              </label>
            </div>

            <!-- Tags -->
            <label class="flex flex-col gap-1">
              <span class="text-[10px] text-(--t-text-3)">Теги (через запятую)</span>
              <input v-model="formData.tags" type="text"
                     placeholder="центр, премиум, VIP"
                     class="py-2 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                            text-xs text-(--t-text) placeholder:text-(--t-text-3)
                            focus:outline-none focus:border-(--t-primary)/50 transition-colors" />
            </label>
          </div>

          <!-- Form footer -->
          <div class="sticky inset-block-end-0 flex items-center gap-2 px-5 py-3
                      border-t border-(--t-border)/30 bg-(--t-surface)/90 backdrop-blur-xl">
            <button
              class="relative overflow-hidden flex-1 flex items-center justify-center gap-1.5
                     py-2.5 rounded-xl text-xs font-medium border border-(--t-border)/50
                     text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)
                     active:scale-95 transition-all"
              @click="showCreateDrawer = false" @mousedown="ripple"
            >Отмена</button>
            <button
              class="relative overflow-hidden flex-1 flex items-center justify-center gap-1.5
                     py-2.5 rounded-xl text-xs font-semibold
                     bg-(--t-primary) text-white hover:brightness-110
                     active:scale-95 transition-all"
              :disabled="!formData.name || !formData.address || !formData.city"
              @click="submitForm" @mousedown="ripple"
            >{{ formData.id ? '💾 Сохранить' : '+ Создать' }}</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════
         DELETE CONFIRMATION
    ══════════════════════════════════════ -->
    <Transition name="modal-br">
      <div v-if="showDeleteConfirm"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="showDeleteConfirm = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteConfirm = false" />

        <div class="relative z-10 inline-size-full max-w-sm bg-(--t-surface) rounded-2xl
                    border border-(--t-border)/60 shadow-2xl p-6 text-center">
          <p class="text-4xl mb-3">🗑️</p>
          <h3 class="text-sm font-bold text-(--t-text) mb-1">Удалить {{ vc.branchNoun }}?</h3>
          <p v-if="deleteTarget" class="text-[10px] text-(--t-text-3) mb-5">
            «{{ deleteTarget.name }}» ({{ deleteTarget.city }}) будет удалён. Это действие нельзя отменить.
          </p>
          <div class="flex gap-2 justify-center">
            <button
              class="relative overflow-hidden px-5 py-2 rounded-xl text-xs font-medium
                     border border-(--t-border)/50 text-(--t-text-3) hover:bg-(--t-card-hover)
                     active:scale-95 transition-all"
              @click="showDeleteConfirm = false" @mousedown="ripple"
            >Отмена</button>
            <button
              class="relative overflow-hidden px-5 py-2 rounded-xl text-xs font-semibold
                     bg-rose-500 text-white hover:brightness-110 active:scale-95 transition-all"
              @click="executeDelete" @mousedown="ripple"
            >Удалить</button>
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
/* Ripple — unique suffix br (Branches) */
@keyframes ripple-br {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* No scrollbar */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Sidebar transition */
.sb-br-enter-active,
.sb-br-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.sb-br-enter-from,
.sb-br-leave-to {
  opacity: 0;
  transform: translateX(12px);
}

/* Drawer transitions (right side) */
.dw-br-enter-active,
.dw-br-leave-active,
.detail-br-enter-active,
.detail-br-leave-active {
  transition: opacity 0.3s ease;
}
.dw-br-enter-active > :last-child,
.dw-br-leave-active > :last-child,
.detail-br-enter-active > :last-child,
.detail-br-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.dw-br-enter-from,
.dw-br-leave-to,
.detail-br-enter-from,
.detail-br-leave-to {
  opacity: 0;
}
.dw-br-enter-from > :last-child,
.dw-br-leave-to > :last-child,
.detail-br-enter-from > :last-child,
.detail-br-leave-to > :last-child {
  transform: translateX(100%);
}

/* Modal transition */
.modal-br-enter-active,
.modal-br-leave-active {
  transition: opacity 0.25s ease;
}
.modal-br-enter-active > :nth-child(2),
.modal-br-leave-active > :nth-child(2) {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}
.modal-br-enter-from,
.modal-br-leave-to {
  opacity: 0;
}
.modal-br-enter-from > :nth-child(2),
.modal-br-leave-to > :nth-child(2) {
  transform: scale(0.95) translateY(8px);
  opacity: 0;
}
</style>
