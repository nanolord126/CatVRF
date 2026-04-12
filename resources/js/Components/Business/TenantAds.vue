<script setup lang="ts">
/**
 * TenantAds.vue — Рекламный движок + реклама у блогеров через Экосистему Кота
 *
 * Вертикали:
 *   Beauty · Taxi · Food · Hotels · RealEstate · Flowers
 *   Fashion · Furniture · Fitness · Travel · default
 *
 * ───────────────────────────────────────────────────────────────
 *  Функционал:
 *   1.  Верхняя панель: период, бюджет, «Создать кампанию»,
 *       «Запустить рекламу у блогеров»
 *   2.  KPI-виджеты: активные кампании, расход, ROAS, CTR,
 *       конверсия, CPC, CPL, блогер-охват
 *   3.  Табы: 📢 Кампании · 🐱 Блогеры Кота · 📊 Аналитика
 *   4.  Таблица / карточки кампаний с бюджетом, статусом, метриками
 *   5.  Блок «Реклама у блогеров через Экосистему Кота»:
 *       список блогеров, ниша, охват, цена, рейтинг, статус
 *   6.  Графики: расход по дням, выручка от рекламы, источники
 *       трафика (Яндекс · Google · Блогеры · Органика · Шортсы)
 *   7.  Модал «Создать кампанию» / «Запустить рекламу у блогера»
 *   8.  Detail Drawer кампании (метрики, креативы, аудитория)
 *   9.  Sidebar: топ кампании, бюджет breakdown, quick actions
 *  10.  Full-screen · mobile drawer · keyboard Esc · ripple-ad
 *  11.  Glassmorphism · dark theme · 2026 design
 * ───────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { useAuth, useTenant } from '@/stores'

/* ━━━━━━━━━━━━  TYPES  ━━━━━━━━━━━━ */

type CampaignStatus  = 'active' | 'paused' | 'draft' | 'completed' | 'rejected'
type CampaignType    = 'cpc' | 'cpm' | 'cpa' | 'retarget' | 'shorts' | 'blogger'
type TrafficSource   = 'yandex' | 'google' | 'bloggers' | 'organic' | 'shorts' | 'social' | 'direct'
type BloggerStatus   = 'available' | 'booked' | 'in_progress' | 'completed' | 'declined'
type TabKey          = 'campaigns' | 'bloggers' | 'analytics'
type SortKey         = 'name' | 'budget' | 'spent' | 'roas' | 'status' | 'date'
type SortDir         = 'asc' | 'desc'

interface AdCampaign {
  id:            number | string
  name:          string
  type:          CampaignType
  status:        CampaignStatus
  budget:        number
  spent:         number
  impressions:   number
  clicks:        number
  conversions:   number
  revenue:       number
  roas:          number
  ctr:           number
  cpc:           number
  startDate:     string
  endDate:       string
  audience:      string
  creatives:     number
  correlationId: string
}

interface CatBlogger {
  id:           number | string
  name:         string
  avatar?:      string
  niche:        string
  followers:    number
  avgReach:     number
  engagementPct: number
  pricePerPost: number
  pricePerStory: number
  rating:       number
  completedAds: number
  status:       BloggerStatus
  vertical:     string
  tags:         string[]
}

interface BloggerDeal {
  id:           number | string
  bloggerId:    number | string
  bloggerName:  string
  type:         'post' | 'story' | 'reels' | 'review' | 'integration'
  status:       BloggerStatus
  price:        number
  reach:        number
  clicks:       number
  conversions:  number
  publishDate:  string
  correlationId: string
}

interface AdStats {
  activeCampaigns: number
  totalBudget:     number
  totalSpent:      number
  avgROAS:         number
  avgCTR:          number
  avgConversion:   number
  avgCPC:          number
  totalLeads:      number
  bloggerReach:    number
  bloggerDeals:    number
}

interface ChartPoint {
  date:    string
  spent:   number
  revenue: number
}

interface TrafficData {
  source: TrafficSource
  visits: number
  pct:    number
}

interface VerticalAdCfg {
  label:     string
  icon:      string
  accent:    string
  adNoun:    string
  audience:  string
}

/* ━━━━━━━━━━━━  PROPS / EMITS  ━━━━━━━━━━━━ */

const props = withDefaults(defineProps<{
  vertical?:      string
  campaigns?:     AdCampaign[]
  bloggers?:      CatBlogger[]
  bloggerDeals?:  BloggerDeal[]
  stats?:         AdStats | null
  chartData?:     ChartPoint[]
  trafficData?:   TrafficData[]
  loading?:       boolean
  period?:        string
}>(), {
  vertical:     'default',
  campaigns:    () => [],
  bloggers:     () => [],
  bloggerDeals: () => [],
  stats:        null,
  chartData:    () => [],
  trafficData:  () => [],
  loading:      false,
  period:       '30d',
})

const emit = defineEmits<{
  'create-campaign':    [data: Record<string, unknown>]
  'edit-campaign':      [id: number | string]
  'pause-campaign':     [id: number | string]
  'resume-campaign':    [id: number | string]
  'delete-campaign':    [id: number | string]
  'book-blogger':       [data: Record<string, unknown>]
  'open-blogger':       [id: number | string]
  'open-campaign':      [id: number | string]
  'export':             [format: 'csv' | 'xlsx' | 'pdf']
  'period-change':      [period: string]
  'refresh':            []
  'toggle-fullscreen':  []
}>()

const auth = useAuth()
const biz  = useTenant()

/* ━━━━━━━━━━━━  VERTICAL CONFIG  ━━━━━━━━━━━━ */

const VERTICAL_CFG: Record<string, VerticalAdCfg> = {
  beauty:     { label: 'Салон красоты',   icon: '💄', accent: 'pink',    adNoun: 'за услуги',       audience: 'Женщины 18-45' },
  taxi:       { label: 'Такси',           icon: '🚕', accent: 'yellow',  adNoun: 'за поездки',      audience: 'Городские 18-55' },
  food:       { label: 'Еда и рестораны', icon: '🍽️', accent: 'orange',  adNoun: 'за заказы',       audience: 'Фудгики 18-40' },
  hotel:      { label: 'Отели',           icon: '🏨', accent: 'sky',     adNoun: 'за бронирования', audience: 'Путешественники 25-55' },
  realEstate: { label: 'Недвижимость',    icon: '🏢', accent: 'emerald', adNoun: 'за сделки',       audience: 'Покупатели 25-55' },
  flowers:    { label: 'Цветы',           icon: '💐', accent: 'rose',    adNoun: 'за букеты',       audience: 'Мужчины 25-50' },
  fashion:    { label: 'Мода и одежда',   icon: '👗', accent: 'violet',  adNoun: 'за покупки',      audience: 'Стиль 18-40' },
  furniture:  { label: 'Мебель',          icon: '🛋️', accent: 'amber',   adNoun: 'за покупки',      audience: 'Семьи 25-55' },
  fitness:    { label: 'Фитнес',          icon: '💪', accent: 'lime',    adNoun: 'за абонементы',   audience: 'Спорт 18-45' },
  travel:     { label: 'Путешествия',     icon: '✈️', accent: 'cyan',    adNoun: 'за туры',         audience: 'Путешественники 20-50' },
  default:    { label: 'Бизнес',          icon: '📊', accent: 'indigo',  adNoun: 'за конверсии',    audience: 'Все 18-60' },
}

const vc = computed<VerticalAdCfg>(() => VERTICAL_CFG[props.vertical] ?? VERTICAL_CFG.default)

/* ━━━━━━━━━━━━  CONSTANTS  ━━━━━━━━━━━━ */

const CAMPAIGN_STATUS_META: Record<CampaignStatus, { label: string; dot: string; cls: string; icon: string }> = {
  active:    { label: 'Активна',    dot: 'bg-emerald-500', cls: 'bg-emerald-500/12 text-emerald-400', icon: '▶' },
  paused:    { label: 'На паузе',   dot: 'bg-amber-500',   cls: 'bg-amber-500/12 text-amber-400',     icon: '⏸' },
  draft:     { label: 'Черновик',   dot: 'bg-zinc-500',    cls: 'bg-zinc-500/12 text-zinc-400',       icon: '📝' },
  completed: { label: 'Завершена',  dot: 'bg-sky-500',     cls: 'bg-sky-500/12 text-sky-400',         icon: '✓' },
  rejected:  { label: 'Отклонена',  dot: 'bg-rose-500',    cls: 'bg-rose-500/12 text-rose-400',       icon: '✕' },
}

const CAMPAIGN_TYPE_META: Record<CampaignType, { label: string; icon: string }> = {
  cpc:      { label: 'CPC',         icon: '🖱️' },
  cpm:      { label: 'CPM',         icon: '👁️' },
  cpa:      { label: 'CPA',         icon: '🎯' },
  retarget: { label: 'Ретаргетинг', icon: '🔄' },
  shorts:   { label: 'Шортсы',      icon: '📱' },
  blogger:  { label: 'Блогер',      icon: '🐱' },
}

const TRAFFIC_META: Record<TrafficSource, { label: string; icon: string; color: string }> = {
  yandex:   { label: 'Яндекс',   icon: '🔍', color: 'bg-amber-500' },
  google:   { label: 'Google',   icon: '🌐', color: 'bg-sky-500' },
  bloggers: { label: 'Блогеры',  icon: '🐱', color: 'bg-violet-500' },
  organic:  { label: 'Органика', icon: '🌿', color: 'bg-emerald-500' },
  shorts:   { label: 'Шортсы',   icon: '📱', color: 'bg-pink-500' },
  social:   { label: 'Соцсети',  icon: '💬', color: 'bg-indigo-500' },
  direct:   { label: 'Прямой',   icon: '🔗', color: 'bg-zinc-500' },
}

const BLOGGER_STATUS_META: Record<BloggerStatus, { label: string; dot: string; cls: string }> = {
  available:   { label: 'Доступен',     dot: 'bg-emerald-500', cls: 'bg-emerald-500/12 text-emerald-400' },
  booked:      { label: 'Забронирован', dot: 'bg-amber-500',   cls: 'bg-amber-500/12 text-amber-400' },
  in_progress: { label: 'В работе',     dot: 'bg-sky-500',     cls: 'bg-sky-500/12 text-sky-400' },
  completed:   { label: 'Завершён',     dot: 'bg-zinc-500',    cls: 'bg-zinc-500/12 text-zinc-400' },
  declined:    { label: 'Отказал',       dot: 'bg-rose-500',    cls: 'bg-rose-500/12 text-rose-400' },
}

const PERIODS: Array<{ key: string; label: string }> = [
  { key: '7d',  label: '7 дней' },
  { key: '30d', label: '30 дней' },
  { key: '90d', label: '90 дней' },
  { key: '1y',  label: 'Год' },
  { key: 'all', label: 'Всё время' },
]

const DEAL_TYPE_META: Record<string, { label: string; icon: string }> = {
  post:        { label: 'Пост',        icon: '📝' },
  story:       { label: 'Сторис',      icon: '📸' },
  reels:       { label: 'Reels/Шортс', icon: '🎬' },
  review:      { label: 'Обзор',       icon: '⭐' },
  integration: { label: 'Интеграция',  icon: '🤝' },
}

const SORT_COLS: Array<{ key: SortKey; label: string }> = [
  { key: 'name',   label: 'Название' },
  { key: 'budget', label: 'Бюджет' },
  { key: 'spent',  label: 'Расход' },
  { key: 'roas',   label: 'ROAS' },
  { key: 'status', label: 'Статус' },
  { key: 'date',   label: 'Дата' },
]

/* ━━━━━━━━━━━━  STATE  ━━━━━━━━━━━━ */

const rootEl              = ref<HTMLElement | null>(null)
const isFullscreen        = ref(false)
const activeTab           = ref<TabKey>('campaigns')
const selectedPeriod      = ref(props.period)
const searchQuery         = ref('')
const bloggerSearch       = ref('')
const sortKey             = ref<SortKey>('spent')
const sortDir             = ref<SortDir>('desc')
const filterStatus        = ref<CampaignStatus | 'all'>('all')
const filterType          = ref<CampaignType | 'all'>('all')
const showSidebar         = ref(true)
const showMobileSidebar   = ref(false)
const showCampaignModal   = ref(false)
const showBloggerModal    = ref(false)
const showCampaignDrawer  = ref(false)
const showExportMenu      = ref(false)
const detailCampaign      = ref<AdCampaign | null>(null)
const selectedBlogger     = ref<CatBlogger | null>(null)
const refreshing          = ref(false)

/* ── Campaign form ── */
const campaignForm = reactive<{
  name: string; type: CampaignType; budget: number
  startDate: string; endDate: string
  audience: string; description: string
}>({
  name: '', type: 'cpc', budget: 10000,
  startDate: '', endDate: '',
  audience: '', description: '',
})

/* ── Blogger deal form ── */
const bloggerDealForm = reactive<{
  type: 'post' | 'story' | 'reels' | 'review' | 'integration'
  message: string
  budget: number
}>({
  type: 'post', message: '', budget: 0,
})

/* ━━━━━━━━━━━━  COMPUTED  ━━━━━━━━━━━━ */

const pStats = computed<AdStats>(() =>
  props.stats ?? {
    activeCampaigns: 0, totalBudget: 0, totalSpent: 0,
    avgROAS: 0, avgCTR: 0, avgConversion: 0, avgCPC: 0,
    totalLeads: 0, bloggerReach: 0, bloggerDeals: 0,
  },
)

const budgetUsedPct = computed(() => {
  if (pStats.value.totalBudget <= 0) return 0
  return Math.min(100, Math.round((pStats.value.totalSpent / pStats.value.totalBudget) * 100))
})

/* ── Filtered + sorted campaigns ── */
const filteredCampaigns = computed<AdCampaign[]>(() => {
  let list = [...props.campaigns]

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase()
    list = list.filter(
      (c) => c.name.toLowerCase().includes(q)
           || c.audience.toLowerCase().includes(q),
    )
  }

  if (filterStatus.value !== 'all') {
    list = list.filter((c) => c.status === filterStatus.value)
  }

  if (filterType.value !== 'all') {
    list = list.filter((c) => c.type === filterType.value)
  }

  const statusOrder: Record<CampaignStatus, number> = {
    active: 1, paused: 2, draft: 3, completed: 4, rejected: 5,
  }

  list.sort((a, b) => {
    let cmp = 0
    switch (sortKey.value) {
      case 'name':   cmp = a.name.localeCompare(b.name); break
      case 'budget': cmp = a.budget - b.budget; break
      case 'spent':  cmp = a.spent - b.spent; break
      case 'roas':   cmp = a.roas - b.roas; break
      case 'status': cmp = statusOrder[a.status] - statusOrder[b.status]; break
      case 'date':   cmp = new Date(a.startDate).getTime() - new Date(b.startDate).getTime(); break
    }
    return sortDir.value === 'asc' ? cmp : -cmp
  })

  return list
})

/* ── Filtered bloggers ── */
const filteredBloggers = computed<CatBlogger[]>(() => {
  let list = [...props.bloggers]

  if (bloggerSearch.value.trim()) {
    const q = bloggerSearch.value.trim().toLowerCase()
    list = list.filter(
      (b) => b.name.toLowerCase().includes(q)
           || b.niche.toLowerCase().includes(q)
           || b.tags.some((t) => t.toLowerCase().includes(q)),
    )
  }

  list.sort((a, b) => b.rating - a.rating)
  return list
})

const activeFiltersCount = computed(() => {
  let c = 0
  if (filterStatus.value !== 'all') c++
  if (filterType.value !== 'all') c++
  if (searchQuery.value.trim()) c++
  return c
})

const maxChartVal = computed(() => {
  let m = 1
  for (const d of props.chartData) {
    if (d.spent > m)   m = d.spent
    if (d.revenue > m) m = d.revenue
  }
  return m
})

const maxTrafficVisits = computed(() =>
  Math.max(1, ...props.trafficData.map((t) => t.visits)),
)

/* ── Sidebar: top campaigns by ROAS ── */
const topByROAS = computed(() =>
  [...props.campaigns]
    .filter((c) => c.status === 'active' || c.status === 'completed')
    .sort((a, b) => b.roas - a.roas)
    .slice(0, 5),
)

const activeBloggerDeals = computed(() =>
  props.bloggerDeals.filter((d) => d.status === 'in_progress' || d.status === 'booked'),
)

/* ━━━━━━━━━━━━  HELPERS  ━━━━━━━━━━━━ */

function fmtMoney(n: number): string {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(2)}M`
  if (n >= 1_000)     return `${(n / 1_000).toFixed(1)}K`
  return n.toLocaleString('ru-RU')
}

function fmtNum(n: number): string {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M`
  if (n >= 1_000)     return `${(n / 1_000).toFixed(1)}K`
  return String(n)
}

function fmtPct(n: number): string { return `${n.toFixed(1)}%` }

function fmtDate(d: string): string {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('ru-RU', { day: '2-digit', month: 'short', year: 'numeric' })
}

function fmtRoas(n: number): string { return `×${n.toFixed(2)}` }

function fmtStars(r: number): string {
  return '★'.repeat(Math.round(r)) + '☆'.repeat(5 - Math.round(r))
}

function bloggerInitials(name: string): string {
  return name.split(' ').slice(0, 2).map((w) => w[0] ?? '').join('').toUpperCase()
}

function campaignProgress(c: AdCampaign): number {
  if (c.budget <= 0) return 0
  return Math.min(100, Math.round((c.spent / c.budget) * 100))
}

/* ━━━━━━━━━━━━  ACTIONS  ━━━━━━━━━━━━ */

function toggleSort(key: SortKey) {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'desc'
  }
}

function setPeriod(p: string) {
  selectedPeriod.value = p
  emit('period-change', p)
}

function clearFilters() {
  searchQuery.value = ''
  filterStatus.value = 'all'
  filterType.value = 'all'
}

function openCampaignModal() {
  campaignForm.name = ''
  campaignForm.type = 'cpc'
  campaignForm.budget = 10000
  campaignForm.startDate = ''
  campaignForm.endDate = ''
  campaignForm.audience = vc.value.audience
  campaignForm.description = ''
  showCampaignModal.value = true
}

function submitCampaign() {
  emit('create-campaign', { ...campaignForm })
  showCampaignModal.value = false
}

function openCampaignDetail(c: AdCampaign) {
  detailCampaign.value = c
  showCampaignDrawer.value = true
}

function closeCampaignDrawer() {
  showCampaignDrawer.value = false
  detailCampaign.value = null
}

function openBloggerDeal(blogger: CatBlogger) {
  selectedBlogger.value = blogger
  bloggerDealForm.type = 'post'
  bloggerDealForm.message = ''
  bloggerDealForm.budget = blogger.pricePerPost
  showBloggerModal.value = true
}

function submitBloggerDeal() {
  if (selectedBlogger.value) {
    emit('book-blogger', {
      bloggerId: selectedBlogger.value.id,
      ...bloggerDealForm,
    })
  }
  showBloggerModal.value = false
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
    if (showCampaignModal.value)   { showCampaignModal.value = false; return }
    if (showBloggerModal.value)    { showBloggerModal.value = false; return }
    if (showCampaignDrawer.value)  { closeCampaignDrawer(); return }
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
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-ad_0.6s_ease-out]'
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
          <span class="text-2xl">📢</span>
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <h1 class="text-base sm:text-lg font-extrabold text-(--t-text) truncate leading-snug">
                Рекламный движок
              </h1>
              <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold
                           bg-emerald-500/15 text-emerald-400 tabular-nums">
                {{ fmtMoney(pStats.totalSpent) }} / {{ fmtMoney(pStats.totalBudget) }} ₽
              </span>
            </div>
            <p class="text-[10px] text-(--t-text-3) truncate">
              {{ vc.icon }} {{ vc.label }} · Реклама {{ vc.adNoun }} · ROAS {{ fmtRoas(pStats.avgROAS) }}
            </p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 flex-wrap">

          <!-- Create campaign -->
          <button
            class="relative overflow-hidden flex items-center gap-1.5 px-4 py-2 rounded-xl
                   text-xs font-semibold bg-(--t-primary) text-white hover:brightness-110
                   active:scale-95 transition-all"
            @click="openCampaignModal" @mousedown="ripple"
          >
            <span class="text-sm">+</span>
            <span class="hidden sm:inline">Создать кампанию</span>
            <span class="sm:hidden">Кампания</span>
          </button>

          <!-- Blogger CTA -->
          <button
            class="relative overflow-hidden flex items-center gap-1.5 px-4 py-2 rounded-xl
                   text-xs font-semibold border border-violet-500/40 text-violet-400
                   hover:bg-violet-500/10 active:scale-95 transition-all"
            @click="activeTab = 'bloggers'" @mousedown="ripple"
          >
            🐱
            <span class="hidden sm:inline">Блогеры Кота</span>
            <span class="sm:hidden">Блогеры</span>
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
            <Transition name="fade-ad">
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

    <!-- ═══════ MAIN LAYOUT ═══════ -->
    <div class="flex-1 flex gap-5 px-4 sm:px-6 py-5 max-w-screen-2xl mx-auto inline-size-full">

      <!-- ═══ CONTENT ═══ -->
      <div class="flex-1 flex flex-col gap-5 min-w-0">

        <!-- ── KPI GRID ── -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div v-for="kpi in [
            { label: 'Кампании',   value: fmtNum(pStats.activeCampaigns),   icon: '📢', cls: 'text-sky-400' },
            { label: 'Расход',     value: fmtMoney(pStats.totalSpent) + ' ₽', icon: '💸', cls: 'text-rose-400' },
            { label: 'ROAS',       value: fmtRoas(pStats.avgROAS),          icon: '📈', cls: 'text-emerald-400' },
            { label: 'CTR',        value: fmtPct(pStats.avgCTR),            icon: '🖱️', cls: 'text-violet-400' },
            { label: 'Конверсия',  value: fmtPct(pStats.avgConversion),     icon: '🎯', cls: 'text-amber-400' },
            { label: 'Ср. CPC',    value: fmtMoney(pStats.avgCPC) + ' ₽',  icon: '💰', cls: 'text-sky-400' },
            { label: 'Лиды',       value: fmtNum(pStats.totalLeads),        icon: '👥', cls: 'text-pink-400' },
            { label: 'Блогер-охват', value: fmtNum(pStats.bloggerReach),    icon: '🐱', cls: 'text-violet-400' },
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

        <!-- ── Budget progress bar ── -->
        <div class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                    backdrop-blur-sm p-4">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-(--t-text)">💰 Бюджет кампаний</span>
            <span class="text-[10px] text-(--t-text-3) tabular-nums">
              {{ budgetUsedPct }}% использовано
            </span>
          </div>
          <div class="h-2.5 rounded-full bg-(--t-border)/20 overflow-hidden">
            <div
              :class="[
                'h-full rounded-full transition-all',
                budgetUsedPct > 90 ? 'bg-rose-500' : budgetUsedPct > 70 ? 'bg-amber-500' : 'bg-emerald-500',
              ]"
              :style="{ inlineSize: `${Math.max(1, budgetUsedPct)}%` }"
            />
          </div>
          <div class="flex items-center justify-between mt-1.5 text-[9px] text-(--t-text-3) tabular-nums">
            <span>{{ fmtMoney(pStats.totalSpent) }} ₽ потрачено</span>
            <span>{{ fmtMoney(Math.max(0, pStats.totalBudget - pStats.totalSpent)) }} ₽ осталось</span>
          </div>
        </div>

        <!-- ── TAB SWITCHER ── -->
        <div class="flex items-center gap-1 p-1 rounded-xl bg-(--t-surface)/40
                    border border-(--t-border)/30 self-start">
          <button
            v-for="tab in ([
              { key: 'campaigns', label: '📢 Кампании',  count: filteredCampaigns.length },
              { key: 'bloggers',  label: '🐱 Блогеры',   count: filteredBloggers.length },
              { key: 'analytics', label: '📊 Аналитика', count: null },
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
            <span v-if="tab.count !== null" class="tabular-nums text-[9px] opacity-60">
              ({{ tab.count }})
            </span>
          </button>
        </div>

        <!-- ══════════ TAB: CAMPAIGNS ══════════ -->
        <template v-if="activeTab === 'campaigns'">

          <!-- Filters -->
          <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
            <div class="relative shrink-0">
              <input
                v-model="searchQuery" type="text" placeholder="Поиск кампании…"
                class="py-1.5 ps-8 pe-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors
                       inline-size-36 sm:inline-size-44"
              />
              <span class="absolute inset-inline-start-2.5 inset-block-start-1/2 -translate-y-1/2
                           text-xs text-(--t-text-3) pointer-events-none">🔍</span>
            </div>

            <select v-model="filterStatus"
              class="shrink-0 py-1.5 px-2.5 rounded-xl border border-(--t-border)/50
                     bg-(--t-bg)/60 text-xs text-(--t-text) focus:outline-none
                     focus:border-(--t-primary)/50 transition-colors appearance-none cursor-pointer"
            >
              <option value="all">Все статусы</option>
              <option v-for="(m, k) in CAMPAIGN_STATUS_META" :key="k" :value="k">
                {{ m.icon }} {{ m.label }}
              </option>
            </select>

            <select v-model="filterType"
              class="shrink-0 py-1.5 px-2.5 rounded-xl border border-(--t-border)/50
                     bg-(--t-bg)/60 text-xs text-(--t-text) focus:outline-none
                     focus:border-(--t-primary)/50 transition-colors appearance-none cursor-pointer"
            >
              <option value="all">Все типы</option>
              <option v-for="(m, k) in CAMPAIGN_TYPE_META" :key="k" :value="k">
                {{ m.icon }} {{ m.label }}
              </option>
            </select>

            <button v-if="activeFiltersCount > 0"
              class="shrink-0 flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[10px]
                     font-medium text-rose-400 bg-rose-500/10 hover:bg-rose-500/20
                     active:scale-95 transition-all"
              @click="clearFilters"
            >✕ Сбросить ({{ activeFiltersCount }})</button>

            <!-- Mobile period -->
            <select v-model="selectedPeriod"
              class="sm:hidden shrink-0 py-1.5 px-2.5 rounded-xl border border-(--t-border)/50
                     bg-(--t-bg)/60 text-xs text-(--t-text) focus:outline-none
                     appearance-none cursor-pointer"
              @change="setPeriod(selectedPeriod)"
            >
              <option v-for="p in PERIODS" :key="p.key" :value="p.key">{{ p.label }}</option>
            </select>
          </div>

          <!-- Loading -->
          <div v-if="props.loading && filteredCampaigns.length === 0" class="flex flex-col gap-2.5">
            <div v-for="n in 4" :key="n"
                 class="flex items-center gap-3 p-4 rounded-2xl border border-(--t-border)/20
                        bg-(--t-surface)/30 animate-pulse">
              <div class="shrink-0 w-10 h-10 rounded-xl bg-(--t-border)/30" />
              <div class="flex-1">
                <div class="h-3 w-36 bg-(--t-border)/30 rounded mb-2" />
                <div class="h-2.5 w-48 bg-(--t-border)/20 rounded" />
              </div>
              <div class="shrink-0 h-4 w-20 bg-(--t-border)/20 rounded-lg" />
            </div>
          </div>

          <!-- Empty -->
          <div v-else-if="filteredCampaigns.length === 0 && !props.loading"
               class="py-16 text-center">
            <p class="text-5xl mb-3">📢</p>
            <p class="text-sm font-semibold text-(--t-text-2)">Нет кампаний</p>
            <p class="text-[10px] text-(--t-text-3) mt-1">Создайте первую рекламную кампанию</p>
            <button class="relative overflow-hidden mt-4 px-5 py-2 rounded-xl text-xs font-semibold
                           bg-(--t-primary) text-white hover:brightness-110
                           active:scale-95 transition-all"
                    @click="openCampaignModal" @mousedown="ripple"
            >+ Создать кампанию</button>
          </div>

          <!-- Table (desktop) -->
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
                               uppercase tracking-wider">ROAS</th>
                    <th class="px-4 py-3 text-end text-[10px] font-bold text-(--t-text-3)
                               uppercase tracking-wider">CTR</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="c in filteredCampaigns" :key="c.id"
                      class="group/row border-b border-(--t-border)/15 hover:bg-(--t-card-hover)/40
                             transition-colors cursor-pointer"
                      @click="openCampaignDetail(c)">

                    <!-- Name -->
                    <td class="px-4 py-3.5">
                      <div class="flex items-center gap-2.5">
                        <span class="shrink-0 text-sm">{{ CAMPAIGN_TYPE_META[c.type].icon }}</span>
                        <div class="min-w-0">
                          <p class="text-xs font-medium text-(--t-text) truncate">{{ c.name }}</p>
                          <p class="text-[9px] text-(--t-text-3)">
                            {{ CAMPAIGN_TYPE_META[c.type].label }}
                          </p>
                        </div>
                      </div>
                    </td>

                    <!-- Budget -->
                    <td class="px-4 py-3.5">
                      <div>
                        <p class="text-xs text-(--t-text) tabular-nums">{{ fmtMoney(c.budget) }} ₽</p>
                        <div class="h-1 w-16 mt-1 rounded-full bg-(--t-border)/20 overflow-hidden">
                          <div class="h-full rounded-full bg-sky-500/60"
                               :style="{ inlineSize: `${campaignProgress(c)}%` }" />
                        </div>
                      </div>
                    </td>

                    <!-- Spent -->
                    <td class="px-4 py-3.5 text-xs text-(--t-text-2) tabular-nums">
                      {{ fmtMoney(c.spent) }} ₽
                    </td>

                    <!-- ROAS (sort col) -->
                    <td class="px-4 py-3.5">
                      <span :class="[
                        'text-xs font-bold tabular-nums',
                        c.roas >= 3 ? 'text-emerald-400' : c.roas >= 1 ? 'text-amber-400' : 'text-rose-400',
                      ]">{{ fmtRoas(c.roas) }}</span>
                    </td>

                    <!-- Status -->
                    <td class="px-4 py-3.5">
                      <span :class="[
                        'inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-medium',
                        CAMPAIGN_STATUS_META[c.status].cls,
                      ]">
                        <span :class="['w-1.5 h-1.5 rounded-full', CAMPAIGN_STATUS_META[c.status].dot]" />
                        {{ CAMPAIGN_STATUS_META[c.status].label }}
                      </span>
                    </td>

                    <!-- Date -->
                    <td class="px-4 py-3.5 text-[10px] text-(--t-text-3) tabular-nums">
                      {{ fmtDate(c.startDate) }}
                    </td>

                    <!-- ROAS display col -->
                    <td class="px-4 py-3.5">
                      <span :class="[
                        'text-xs font-bold tabular-nums',
                        c.roas >= 3 ? 'text-emerald-400' : c.roas >= 1 ? 'text-amber-400' : 'text-rose-400',
                      ]">{{ fmtRoas(c.roas) }}</span>
                    </td>

                    <!-- CTR -->
                    <td class="px-4 py-3.5 text-end text-xs text-violet-400 tabular-nums">
                      {{ fmtPct(c.ctr) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Cards (mobile) -->
          <div class="md:hidden flex flex-col gap-2.5">
            <button v-for="c in filteredCampaigns" :key="c.id"
              class="group/card relative overflow-hidden text-start rounded-2xl
                     border border-(--t-border)/30 bg-(--t-surface)/50 backdrop-blur-sm
                     hover:border-(--t-border)/60 hover:shadow-lg hover:shadow-black/5
                     active:scale-[0.98] transition-all p-4"
              @click="openCampaignDetail(c)" @mousedown="ripple"
            >
              <div class="flex items-center gap-3">
                <div class="shrink-0 w-10 h-10 rounded-xl bg-(--t-primary)/10
                            flex items-center justify-center text-base">
                  {{ CAMPAIGN_TYPE_META[c.type].icon }}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-(--t-text) truncate">{{ c.name }}</span>
                    <span :class="[
                      'shrink-0 inline-flex items-center gap-0.5 px-1.5 py-px rounded-md text-[8px] font-medium',
                      CAMPAIGN_STATUS_META[c.status].cls,
                    ]">
                      <span :class="['w-1 h-1 rounded-full', CAMPAIGN_STATUS_META[c.status].dot]" />
                      {{ CAMPAIGN_STATUS_META[c.status].label }}
                    </span>
                  </div>
                  <div class="flex items-center gap-3 mt-1.5 text-[10px] text-(--t-text-3) tabular-nums">
                    <span>{{ fmtMoney(c.spent) }} / {{ fmtMoney(c.budget) }} ₽</span>
                    <span :class="[
                      'font-bold',
                      c.roas >= 3 ? 'text-emerald-400' : c.roas >= 1 ? 'text-amber-400' : 'text-rose-400',
                    ]">ROAS {{ fmtRoas(c.roas) }}</span>
                  </div>
                </div>
                <div class="shrink-0 text-end">
                  <p class="text-xs font-bold text-violet-400 tabular-nums">{{ fmtPct(c.ctr) }}</p>
                  <p class="text-[9px] text-(--t-text-3)">CTR</p>
                </div>
              </div>
              <!-- Progress -->
              <div class="h-1 mt-3 rounded-full bg-(--t-border)/20 overflow-hidden">
                <div class="h-full rounded-full bg-sky-500/60 transition-all"
                     :style="{ inlineSize: `${campaignProgress(c)}%` }" />
              </div>
            </button>
          </div>
        </template>

        <!-- ══════════ TAB: BLOGGERS ══════════ -->
        <template v-if="activeTab === 'bloggers'">

          <!-- Hero -->
          <div class="rounded-2xl border border-violet-500/20 bg-violet-500/5
                      backdrop-blur-sm p-5 sm:p-6">
            <div class="flex items-center gap-3 mb-3">
              <span class="text-3xl">🐱</span>
              <div>
                <h2 class="text-sm sm:text-base font-extrabold text-(--t-text)">
                  Экосистема Кота — реклама у блогеров
                </h2>
                <p class="text-[10px] text-(--t-text-3)">
                  Лучшие блогеры {{ vc.label.toLowerCase() }} · Прозрачные цены · Гарантия охвата
                </p>
              </div>
            </div>
            <div class="flex items-center gap-4 text-[10px]">
              <span class="text-violet-400 tabular-nums">
                🐱 {{ filteredBloggers.length }} блогеров
              </span>
              <span class="text-emerald-400 tabular-nums">
                📊 {{ fmtNum(pStats.bloggerReach) }} охват
              </span>
              <span class="text-sky-400 tabular-nums">
                🤝 {{ pStats.bloggerDeals }} сделок
              </span>
            </div>
          </div>

          <!-- Search -->
          <div class="flex items-center gap-2">
            <div class="relative flex-1 max-w-xs">
              <input v-model="bloggerSearch" type="text" placeholder="Поиск блогера…"
                class="inline-size-full py-1.5 ps-8 pe-3 rounded-xl border border-(--t-border)/50
                       bg-(--t-bg)/60 text-xs text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
              <span class="absolute inset-inline-start-2.5 inset-block-start-1/2 -translate-y-1/2
                           text-xs text-(--t-text-3) pointer-events-none">🔍</span>
            </div>
            <span class="text-[10px] text-(--t-text-3) tabular-nums">
              {{ filteredBloggers.length }} из {{ props.bloggers.length }}
            </span>
          </div>

          <!-- Empty -->
          <div v-if="filteredBloggers.length === 0 && !props.loading"
               class="py-16 text-center">
            <p class="text-5xl mb-3">🐱</p>
            <p class="text-sm font-semibold text-(--t-text-2)">Блогеры не найдены</p>
            <p class="text-[10px] text-(--t-text-3) mt-1">Попробуйте изменить поиск</p>
          </div>

          <!-- Blogger cards -->
          <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="bl in filteredBloggers" :key="bl.id"
                 class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                        backdrop-blur-sm p-4 hover:border-(--t-border)/60
                        hover:shadow-lg hover:shadow-black/5 transition-all">
              <!-- Header -->
              <div class="flex items-center gap-3 mb-3">
                <div v-if="bl.avatar"
                     class="shrink-0 w-11 h-11 rounded-full overflow-hidden bg-(--t-border)/30">
                  <img :src="bl.avatar" :alt="bl.name" class="w-full h-full object-cover" />
                </div>
                <div v-else
                     class="shrink-0 w-11 h-11 rounded-full bg-violet-500/15
                            flex items-center justify-center text-xs font-bold text-violet-400">
                  {{ bloggerInitials(bl.name) }}
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-bold text-(--t-text) truncate">{{ bl.name }}</p>
                  <p class="text-[9px] text-(--t-text-3)">{{ bl.niche }}</p>
                  <p class="text-[9px] text-amber-400">{{ fmtStars(bl.rating) }}</p>
                </div>
                <span :class="[
                  'shrink-0 inline-flex items-center gap-1 px-1.5 py-px rounded-md text-[8px] font-medium',
                  BLOGGER_STATUS_META[bl.status].cls,
                ]">
                  <span :class="['w-1 h-1 rounded-full', BLOGGER_STATUS_META[bl.status].dot]" />
                  {{ BLOGGER_STATUS_META[bl.status].label }}
                </span>
              </div>

              <!-- Stats -->
              <div class="grid grid-cols-3 gap-2 mb-3">
                <div class="text-center rounded-lg bg-(--t-bg)/40 py-2">
                  <p class="text-[9px] text-(--t-text-3)">Подписчики</p>
                  <p class="text-xs font-bold text-(--t-text) tabular-nums">{{ fmtNum(bl.followers) }}</p>
                </div>
                <div class="text-center rounded-lg bg-(--t-bg)/40 py-2">
                  <p class="text-[9px] text-(--t-text-3)">Охват</p>
                  <p class="text-xs font-bold text-(--t-text) tabular-nums">{{ fmtNum(bl.avgReach) }}</p>
                </div>
                <div class="text-center rounded-lg bg-(--t-bg)/40 py-2">
                  <p class="text-[9px] text-(--t-text-3)">ER</p>
                  <p class="text-xs font-bold text-emerald-400 tabular-nums">{{ fmtPct(bl.engagementPct) }}</p>
                </div>
              </div>

              <!-- Prices -->
              <div class="flex items-center gap-3 mb-3 text-[10px] text-(--t-text-3) tabular-nums">
                <span>📝 Пост: <b class="text-(--t-text)">{{ fmtMoney(bl.pricePerPost) }} ₽</b></span>
                <span>📸 Сторис: <b class="text-(--t-text)">{{ fmtMoney(bl.pricePerStory) }} ₽</b></span>
              </div>

              <!-- Tags -->
              <div v-if="bl.tags.length > 0" class="flex flex-wrap gap-1 mb-3">
                <span v-for="tag in bl.tags.slice(0, 4)" :key="tag"
                      class="px-1.5 py-px rounded text-[7px] font-medium
                             bg-(--t-primary)/10 text-(--t-primary)">{{ tag }}</span>
              </div>

              <!-- CTA -->
              <button
                class="relative overflow-hidden inline-size-full py-2.5 rounded-xl text-xs font-semibold
                       bg-violet-500/15 text-violet-400 hover:bg-violet-500/25
                       active:scale-[0.97] transition-all disabled:opacity-40 disabled:pointer-events-none"
                :disabled="bl.status !== 'available'"
                @click="openBloggerDeal(bl)" @mousedown="ripple"
              >🐱 Заказать рекламу</button>
            </div>
          </div>

          <!-- Active blogger deals -->
          <div v-if="activeBloggerDeals.length > 0"
               class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                      backdrop-blur-sm p-4 sm:p-5">
            <h3 class="text-xs font-bold text-(--t-text) mb-3">
              🤝 Активные сделки с блогерами ({{ activeBloggerDeals.length }})
            </h3>
            <div class="flex flex-col gap-2">
              <div v-for="deal in activeBloggerDeals" :key="deal.id"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-(--t-bg)/40">
                <span class="shrink-0 text-sm">
                  {{ DEAL_TYPE_META[deal.type]?.icon ?? '📋' }}
                </span>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-medium text-(--t-text) truncate">{{ deal.bloggerName }}</p>
                  <p class="text-[9px] text-(--t-text-3)">
                    {{ DEAL_TYPE_META[deal.type]?.label ?? deal.type }} · {{ fmtDate(deal.publishDate) }}
                  </p>
                </div>
                <span :class="[
                  'shrink-0 px-1.5 py-px rounded-md text-[8px] font-medium',
                  BLOGGER_STATUS_META[deal.status].cls,
                ]">{{ BLOGGER_STATUS_META[deal.status].label }}</span>
                <span class="shrink-0 text-[10px] font-bold text-(--t-text) tabular-nums">
                  {{ fmtMoney(deal.price) }} ₽
                </span>
              </div>
            </div>
          </div>
        </template>

        <!-- ══════════ TAB: ANALYTICS ══════════ -->
        <template v-if="activeTab === 'analytics'">

          <!-- Revenue chart -->
          <div v-if="props.chartData.length > 0"
               class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                      backdrop-blur-sm p-4 sm:p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xs font-bold text-(--t-text)">📈 Расход vs Выручка от рекламы</h3>
              <div class="flex items-center gap-4 text-[10px]">
                <span class="flex items-center gap-1.5">
                  <span class="w-2.5 h-1.5 rounded-full bg-rose-500" /> Расход
                </span>
                <span class="flex items-center gap-1.5">
                  <span class="w-2.5 h-1.5 rounded-full bg-emerald-500" /> Выручка
                </span>
              </div>
            </div>
            <div class="flex items-end gap-1.5" style="block-size: 160px">
              <div v-for="(bar, idx) in props.chartData" :key="idx"
                   class="flex-1 flex items-end gap-px" style="min-inline-size: 0">
                <div
                  class="flex-1 rounded-t-sm bg-rose-500/50 transition-all"
                  :style="{ blockSize: `${Math.max(2, (bar.spent / maxChartVal) * 100)}%` }"
                  :title="`${fmtDate(bar.date)}: −${fmtMoney(bar.spent)} ₽`"
                />
                <div
                  class="flex-1 rounded-t-sm bg-emerald-500/70 transition-all"
                  :style="{ blockSize: `${Math.max(2, (bar.revenue / maxChartVal) * 100)}%` }"
                  :title="`${fmtDate(bar.date)}: +${fmtMoney(bar.revenue)} ₽`"
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

          <!-- Traffic sources -->
          <div v-if="props.trafficData.length > 0"
               class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                      backdrop-blur-sm p-4 sm:p-5">
            <h3 class="text-xs font-bold text-(--t-text) mb-4">
              🌐 Источники трафика
            </h3>
            <div class="flex flex-col gap-3">
              <div v-for="src in props.trafficData" :key="src.source"
                   class="flex items-center gap-3">
                <span class="shrink-0 text-sm">
                  {{ TRAFFIC_META[src.source]?.icon ?? '🔗' }}
                </span>
                <span class="shrink-0 w-20 text-xs text-(--t-text-2)">
                  {{ TRAFFIC_META[src.source]?.label ?? src.source }}
                </span>
                <div class="flex-1 h-2 rounded-full bg-(--t-border)/20 overflow-hidden">
                  <div
                    :class="['h-full rounded-full transition-all',
                             TRAFFIC_META[src.source]?.color ?? 'bg-zinc-500']"
                    :style="{ inlineSize: `${Math.max(2, (src.visits / maxTrafficVisits) * 100)}%`,
                              opacity: '0.7' }"
                  />
                </div>
                <span class="shrink-0 w-14 text-end text-[10px] font-bold text-(--t-text) tabular-nums">
                  {{ fmtNum(src.visits) }}
                </span>
                <span class="shrink-0 w-10 text-end text-[10px] text-(--t-text-3) tabular-nums">
                  {{ fmtPct(src.pct) }}
                </span>
              </div>
            </div>
          </div>

          <!-- No data -->
          <div v-if="props.chartData.length === 0 && props.trafficData.length === 0"
               class="py-16 text-center">
            <p class="text-5xl mb-3">📊</p>
            <p class="text-sm font-semibold text-(--t-text-2)">Нет аналитических данных</p>
            <p class="text-[10px] text-(--t-text-3) mt-1">
              Запустите рекламные кампании для получения статистики
            </p>
          </div>
        </template>
      </div>

      <!-- ═══ SIDEBAR (desktop) ═══ -->
      <Transition name="sb-ad">
        <aside v-if="showSidebar"
               class="hidden xl:flex shrink-0 flex-col gap-4 w-72">

          <!-- Top campaigns by ROAS -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-3">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-2 px-1">
              🏆 Топ кампании по ROAS
            </h3>
            <div v-if="topByROAS.length === 0" class="text-center py-3">
              <p class="text-[10px] text-(--t-text-3)">Нет данных</p>
            </div>
            <div v-else class="flex flex-col gap-0.5">
              <button v-for="(c, idx) in topByROAS" :key="c.id"
                class="relative overflow-hidden flex items-center gap-2.5 px-3 py-2 rounded-xl
                       text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)
                       active:scale-[0.97] transition-all text-start"
                @click="openCampaignDetail(c)" @mousedown="ripple"
              >
                <span class="shrink-0 w-5 text-[10px] font-bold text-(--t-text-3) tabular-nums">
                  #{{ idx + 1 }}
                </span>
                <span class="flex-1 text-[10px] truncate text-(--t-text-2)">{{ c.name }}</span>
                <span :class="[
                  'shrink-0 text-[10px] font-bold tabular-nums',
                  c.roas >= 3 ? 'text-emerald-400' : c.roas >= 1 ? 'text-amber-400' : 'text-rose-400',
                ]">{{ fmtRoas(c.roas) }}</span>
              </button>
            </div>
          </div>

          <!-- Budget breakdown -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              💰 Распределение бюджета
            </h3>
            <div class="flex flex-col gap-2.5">
              <div class="flex items-center justify-between text-xs">
                <span class="text-(--t-text-3)">Общий бюджет</span>
                <span class="font-bold text-(--t-text) tabular-nums">
                  {{ fmtMoney(pStats.totalBudget) }} ₽
                </span>
              </div>
              <div class="flex items-center justify-between text-xs">
                <span class="text-(--t-text-3)">Потрачено</span>
                <span class="font-bold text-rose-400 tabular-nums">
                  {{ fmtMoney(pStats.totalSpent) }} ₽
                </span>
              </div>
              <div class="flex items-center justify-between text-xs">
                <span class="text-(--t-text-3)">Остаток</span>
                <span class="font-bold text-emerald-400 tabular-nums">
                  {{ fmtMoney(Math.max(0, pStats.totalBudget - pStats.totalSpent)) }} ₽
                </span>
              </div>
              <div class="h-px bg-(--t-border)/20 my-1" />
              <div class="flex items-center justify-between text-xs">
                <span class="text-(--t-text-3)">🐱 Блогеры</span>
                <span class="font-bold text-violet-400 tabular-nums">
                  {{ pStats.bloggerDeals }} сделок
                </span>
              </div>
            </div>
          </div>

          <!-- Active blogger deals -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              🐱 Активные блогер-сделки ({{ activeBloggerDeals.length }})
            </h3>
            <div v-if="activeBloggerDeals.length === 0" class="text-center py-3">
              <p class="text-[10px] text-(--t-text-3)">Нет активных сделок</p>
            </div>
            <div v-else class="flex flex-col gap-1.5">
              <div v-for="dl in activeBloggerDeals.slice(0, 4)" :key="dl.id"
                   class="flex items-center gap-2 px-2.5 py-2 rounded-xl bg-(--t-bg)/40">
                <span class="shrink-0 text-xs">
                  {{ DEAL_TYPE_META[dl.type]?.icon ?? '📋' }}
                </span>
                <span class="flex-1 text-[10px] text-(--t-text-2) truncate">{{ dl.bloggerName }}</span>
                <span class="shrink-0 text-[9px] text-(--t-text-3) tabular-nums">
                  {{ fmtMoney(dl.price) }} ₽
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
                { label: '📢 Создать кампанию',    fn: () => openCampaignModal() },
                { label: '🐱 Найти блогера',       fn: () => { activeTab = 'bloggers' } },
                { label: '📊 Полный отчёт',        fn: () => doExport('pdf') },
                { label: '📤 Экспорт данных',      fn: () => doExport('xlsx') },
                { label: '🔄 Обновить',            fn: () => doRefresh() },
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
    <Transition name="dw-ad">
      <div v-if="showMobileSidebar"
           class="fixed inset-0 z-50 flex" @click.self="showMobileSidebar = false">
        <div class="absolute inset-0 bg-black/40" @click="showMobileSidebar = false" />
        <div class="relative z-10 ms-auto inline-size-72 max-w-[85vw] h-full bg-(--t-surface)
                    border-s border-(--t-border) overflow-y-auto p-4 flex flex-col gap-4">

          <div class="flex items-center justify-between mb-1">
            <h3 class="text-sm font-bold text-(--t-text)">📢 Реклама</h3>
            <button class="text-(--t-text-3) hover:text-(--t-text) transition-colors"
                    @click="showMobileSidebar = false">✕</button>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-2 gap-2">
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Кампании</p>
              <p class="text-xs font-bold text-sky-400 tabular-nums">{{ fmtNum(pStats.activeCampaigns) }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">ROAS</p>
              <p class="text-xs font-bold text-emerald-400 tabular-nums">{{ fmtRoas(pStats.avgROAS) }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Расход</p>
              <p class="text-xs font-bold text-rose-400 tabular-nums">{{ fmtMoney(pStats.totalSpent) }} ₽</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">🐱 Блогеры</p>
              <p class="text-xs font-bold text-violet-400 tabular-nums">{{ pStats.bloggerDeals }}</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col gap-1.5 mt-2">
            <button class="relative overflow-hidden py-2.5 rounded-xl text-[10px] font-semibold
                           bg-(--t-primary) text-white active:scale-95 transition-all"
                    @click="showMobileSidebar = false; openCampaignModal()" @mousedown="ripple"
            >📢 Создать кампанию</button>
            <button class="relative overflow-hidden py-2.5 rounded-xl text-[10px] font-semibold
                           border border-violet-500/40 text-violet-400
                           active:scale-95 transition-all"
                    @click="showMobileSidebar = false; activeTab = 'bloggers'" @mousedown="ripple"
            >🐱 Найти блогера</button>
            <button class="relative overflow-hidden py-2.5 rounded-xl text-[10px] font-semibold
                           border border-(--t-border)/50 text-(--t-text)
                           active:scale-95 transition-all"
                    @click="showMobileSidebar = false; doExport('pdf')" @mousedown="ripple"
            >📊 Полный отчёт</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ═══════ CAMPAIGN DETAIL DRAWER ═══════ -->
    <Transition name="detail-ad">
      <div v-if="showCampaignDrawer && detailCampaign"
           class="fixed inset-0 z-50 flex" @click.self="closeCampaignDrawer">
        <div class="absolute inset-0 bg-black/40" @click="closeCampaignDrawer" />
        <div class="relative z-10 ms-auto inline-size-full sm:inline-size-96 max-w-full h-full
                    bg-(--t-surface) border-s border-(--t-border) overflow-y-auto flex flex-col">

          <!-- Header -->
          <div class="sticky inset-block-start-0 z-10 flex items-center gap-3 px-5 py-4
                      bg-(--t-surface)/90 backdrop-blur-xl border-b border-(--t-border)/30">
            <div class="shrink-0 w-10 h-10 rounded-xl bg-(--t-primary)/10
                        flex items-center justify-center text-lg">
              {{ CAMPAIGN_TYPE_META[detailCampaign.type].icon }}
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-bold text-(--t-text) truncate">{{ detailCampaign.name }}</h3>
              <div class="flex items-center gap-2 mt-0.5">
                <span :class="[
                  'inline-flex items-center gap-1 px-1.5 py-px rounded-md text-[8px] font-medium',
                  CAMPAIGN_STATUS_META[detailCampaign.status].cls,
                ]">
                  <span :class="['w-1.5 h-1.5 rounded-full', CAMPAIGN_STATUS_META[detailCampaign.status].dot]" />
                  {{ CAMPAIGN_STATUS_META[detailCampaign.status].label }}
                </span>
                <span class="text-[9px] text-(--t-text-3)">
                  {{ CAMPAIGN_TYPE_META[detailCampaign.type].label }}
                </span>
              </div>
            </div>
            <button class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="closeCampaignDrawer">✕</button>
          </div>

          <!-- Body -->
          <div class="flex-1 p-5 flex flex-col gap-5">

            <!-- Budget / ROAS highlight -->
            <div class="grid grid-cols-2 gap-3">
              <div class="text-center py-4 rounded-xl bg-(--t-bg)/50">
                <p class="text-[9px] text-(--t-text-3) mb-1">Бюджет / Расход</p>
                <p class="text-lg font-black text-(--t-text) tabular-nums">
                  {{ fmtMoney(detailCampaign.spent) }} ₽
                </p>
                <p class="text-[9px] text-(--t-text-3) mt-0.5 tabular-nums">
                  из {{ fmtMoney(detailCampaign.budget) }} ₽
                </p>
                <div class="h-1.5 w-24 mx-auto mt-2 rounded-full bg-(--t-border)/20 overflow-hidden">
                  <div class="h-full rounded-full bg-sky-500/60"
                       :style="{ inlineSize: `${campaignProgress(detailCampaign)}%` }" />
                </div>
              </div>
              <div class="text-center py-4 rounded-xl bg-(--t-bg)/50">
                <p class="text-[9px] text-(--t-text-3) mb-1">ROAS</p>
                <p :class="[
                  'text-2xl font-black tabular-nums',
                  detailCampaign.roas >= 3 ? 'text-emerald-400' :
                  detailCampaign.roas >= 1 ? 'text-amber-400' : 'text-rose-400',
                ]">{{ fmtRoas(detailCampaign.roas) }}</p>
                <p class="text-[9px] text-(--t-text-3) mt-0.5 tabular-nums">
                  Выручка: {{ fmtMoney(detailCampaign.revenue) }} ₽
                </p>
              </div>
            </div>

            <!-- Metrics -->
            <div class="rounded-xl bg-(--t-bg)/50 p-4 flex flex-col gap-3">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase">Метрики</h4>
              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Показы</span>
                <span class="text-(--t-text) tabular-nums">{{ fmtNum(detailCampaign.impressions) }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Клики</span>
                <span class="text-(--t-text) tabular-nums">{{ fmtNum(detailCampaign.clicks) }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">CTR</span>
                <span class="text-violet-400 tabular-nums font-medium">{{ fmtPct(detailCampaign.ctr) }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Конверсии</span>
                <span class="text-(--t-text) tabular-nums">{{ fmtNum(detailCampaign.conversions) }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">CPC</span>
                <span class="text-(--t-text) tabular-nums">{{ fmtMoney(detailCampaign.cpc) }} ₽</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Креативов</span>
                <span class="text-(--t-text) tabular-nums">{{ detailCampaign.creatives }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Период</span>
                <span class="text-(--t-text) tabular-nums">
                  {{ fmtDate(detailCampaign.startDate) }} – {{ fmtDate(detailCampaign.endDate) }}
                </span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-(--t-text-3)">Аудитория</span>
                <span class="text-(--t-text)">{{ detailCampaign.audience }}</span>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="sticky inset-block-end-0 flex items-center gap-2 px-5 py-3
                      border-t border-(--t-border)/30 bg-(--t-surface)/90 backdrop-blur-xl">
            <button v-if="detailCampaign.status === 'active'"
              class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-medium
                     border border-amber-500/40 text-amber-400 hover:bg-amber-500/10
                     active:scale-95 transition-all"
              @click="emit('pause-campaign', detailCampaign!.id)" @mousedown="ripple"
            >⏸ Пауза</button>
            <button v-if="detailCampaign.status === 'paused'"
              class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-medium
                     border border-emerald-500/40 text-emerald-400 hover:bg-emerald-500/10
                     active:scale-95 transition-all"
              @click="emit('resume-campaign', detailCampaign!.id)" @mousedown="ripple"
            >▶ Запустить</button>
            <button
              class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-semibold
                     bg-(--t-primary) text-white hover:brightness-110
                     active:scale-95 transition-all"
              @click="emit('edit-campaign', detailCampaign!.id); closeCampaignDrawer()"
              @mousedown="ripple"
            >✏️ Редактировать</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ═══════ CREATE CAMPAIGN MODAL ═══════ -->
    <Transition name="modal-ad">
      <div v-if="showCampaignModal"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="showCampaignModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showCampaignModal = false" />
        <div class="relative z-10 inline-size-full max-w-md bg-(--t-surface) rounded-2xl
                    border border-(--t-border)/60 shadow-2xl overflow-hidden
                    max-block-size-[85vh] overflow-y-auto">

          <div class="sticky inset-block-start-0 z-10 flex items-center justify-between px-5 py-4
                      border-b border-(--t-border)/30 bg-(--t-surface)/90 backdrop-blur-xl">
            <h3 class="text-sm font-bold text-(--t-text)">📢 Создать кампанию</h3>
            <button class="w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="showCampaignModal = false">✕</button>
          </div>

          <div class="p-5 flex flex-col gap-4">
            <!-- Name -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Название</span>
              <input v-model="campaignForm.name" type="text"
                placeholder="Летняя кампания 2026"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>

            <!-- Type -->
            <div>
              <p class="text-[10px] text-(--t-text-3) font-medium mb-2">Тип кампании</p>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="ct in (['cpc', 'cpm', 'cpa', 'retarget', 'shorts', 'blogger'] as CampaignType[])"
                  :key="ct"
                  :class="[
                    'relative overflow-hidden flex items-center gap-1.5 px-3 py-2 rounded-xl',
                    'text-[10px] font-medium transition-all active:scale-95',
                    campaignForm.type === ct
                      ? 'bg-(--t-primary)/15 text-(--t-primary) border border-(--t-primary)/30'
                      : 'border border-(--t-border)/30 text-(--t-text-3) hover:border-(--t-border)/50',
                  ]"
                  @click="campaignForm.type = ct" @mousedown="ripple"
                >{{ CAMPAIGN_TYPE_META[ct].icon }} {{ CAMPAIGN_TYPE_META[ct].label }}</button>
              </div>
            </div>

            <!-- Budget -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Бюджет (₽)</span>
              <input v-model.number="campaignForm.budget" type="number" min="1000"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-sm font-bold text-(--t-text) tabular-nums
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>

            <!-- Dates -->
            <div class="grid grid-cols-2 gap-3">
              <label class="flex flex-col gap-1.5">
                <span class="text-[10px] text-(--t-text-3) font-medium">Начало</span>
                <input v-model="campaignForm.startDate" type="date"
                  class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                         text-xs text-(--t-text)
                         focus:outline-none focus:border-(--t-primary)/50 transition-colors"
                />
              </label>
              <label class="flex flex-col gap-1.5">
                <span class="text-[10px] text-(--t-text-3) font-medium">Конец</span>
                <input v-model="campaignForm.endDate" type="date"
                  class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                         text-xs text-(--t-text)
                         focus:outline-none focus:border-(--t-primary)/50 transition-colors"
                />
              </label>
            </div>

            <!-- Audience -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Аудитория</span>
              <input v-model="campaignForm.audience" type="text"
                :placeholder="vc.audience"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>

            <!-- Description -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Описание</span>
              <textarea v-model="campaignForm.description" rows="2"
                placeholder="Цели и комментарии…"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3) resize-none
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>

            <!-- Fraud note -->
            <div class="rounded-xl bg-amber-500/8 border border-amber-500/20 p-3
                        flex items-start gap-2.5">
              <span class="shrink-0 text-sm">⚠️</span>
              <p class="text-[10px] text-amber-400/90 leading-relaxed">
                Бюджет списывается из WalletService · FraudControlService::check() · correlation_id
              </p>
            </div>
          </div>

          <div class="sticky inset-block-end-0 flex gap-2 px-5 py-4
                      border-t border-(--t-border)/30 bg-(--t-surface)/90 backdrop-blur-xl">
            <button class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-medium
                           border border-(--t-border)/50 text-(--t-text-3)
                           hover:bg-(--t-card-hover) active:scale-95 transition-all"
                    @click="showCampaignModal = false" @mousedown="ripple"
            >Отмена</button>
            <button class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-semibold
                           bg-(--t-primary) text-white hover:brightness-110 active:scale-95 transition-all
                           disabled:opacity-40 disabled:pointer-events-none"
                    :disabled="!campaignForm.name.trim() || campaignForm.budget < 1000"
                    @click="submitCampaign" @mousedown="ripple"
            >📢 Запустить кампанию</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ═══════ BOOK BLOGGER MODAL ═══════ -->
    <Transition name="modal-ad">
      <div v-if="showBloggerModal && selectedBlogger"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="showBloggerModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showBloggerModal = false" />
        <div class="relative z-10 inline-size-full max-w-sm bg-(--t-surface) rounded-2xl
                    border border-(--t-border)/60 shadow-2xl overflow-hidden">

          <div class="flex items-center justify-between px-5 py-4 border-b border-(--t-border)/30">
            <h3 class="text-sm font-bold text-(--t-text)">🐱 Заказать рекламу</h3>
            <button class="w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="showBloggerModal = false">✕</button>
          </div>

          <div class="p-5 flex flex-col gap-4">
            <!-- Blogger info -->
            <div class="rounded-xl bg-(--t-bg)/50 p-3 flex items-center gap-3">
              <div v-if="selectedBlogger.avatar"
                   class="shrink-0 w-10 h-10 rounded-full overflow-hidden bg-(--t-border)/30">
                <img :src="selectedBlogger.avatar" :alt="selectedBlogger.name"
                     class="w-full h-full object-cover" />
              </div>
              <div v-else
                   class="shrink-0 w-10 h-10 rounded-full bg-violet-500/15
                          flex items-center justify-center text-xs font-bold text-violet-400">
                {{ bloggerInitials(selectedBlogger.name) }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-(--t-text) truncate">{{ selectedBlogger.name }}</p>
                <p class="text-[9px] text-(--t-text-3)">
                  {{ fmtNum(selectedBlogger.followers) }} подписчиков · {{ selectedBlogger.niche }}
                </p>
              </div>
            </div>

            <!-- Deal type -->
            <div>
              <p class="text-[10px] text-(--t-text-3) font-medium mb-2">Формат</p>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="dt in (['post', 'story', 'reels', 'review', 'integration'] as const)"
                  :key="dt"
                  :class="[
                    'relative overflow-hidden flex items-center gap-1 px-3 py-2 rounded-xl',
                    'text-[10px] font-medium transition-all active:scale-95',
                    bloggerDealForm.type === dt
                      ? 'bg-violet-500/15 text-violet-400 border border-violet-500/30'
                      : 'border border-(--t-border)/30 text-(--t-text-3) hover:border-(--t-border)/50',
                  ]"
                  @click="bloggerDealForm.type = dt;
                          bloggerDealForm.budget = dt === 'story'
                            ? selectedBlogger!.pricePerStory
                            : selectedBlogger!.pricePerPost"
                  @mousedown="ripple"
                >{{ DEAL_TYPE_META[dt].icon }} {{ DEAL_TYPE_META[dt].label }}</button>
              </div>
            </div>

            <!-- Budget -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Бюджет (₽)</span>
              <input v-model.number="bloggerDealForm.budget" type="number" min="100"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-sm font-bold text-(--t-text) tabular-nums
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>

            <!-- Message -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Сообщение блогеру</span>
              <textarea v-model="bloggerDealForm.message" rows="2"
                placeholder="Описание задачи…"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3) resize-none
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </label>
          </div>

          <div class="flex gap-2 px-5 py-4 border-t border-(--t-border)/30">
            <button class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-medium
                           border border-(--t-border)/50 text-(--t-text-3)
                           hover:bg-(--t-card-hover) active:scale-95 transition-all"
                    @click="showBloggerModal = false" @mousedown="ripple"
            >Отмена</button>
            <button class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-semibold
                           bg-violet-500 text-white hover:brightness-110 active:scale-95 transition-all
                           disabled:opacity-40 disabled:pointer-events-none"
                    :disabled="bloggerDealForm.budget < 100"
                    @click="submitBloggerDeal" @mousedown="ripple"
            >🐱 Забронировать · {{ fmtMoney(bloggerDealForm.budget) }} ₽</button>
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
/* Ripple — unique suffix ad (Ads) */
@keyframes ripple-ad {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* No scrollbar */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Sidebar transition */
.sb-ad-enter-active,
.sb-ad-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.sb-ad-enter-from,
.sb-ad-leave-to {
  opacity: 0;
  transform: translateX(12px);
}

/* Drawer transitions */
.dw-ad-enter-active,
.dw-ad-leave-active,
.detail-ad-enter-active,
.detail-ad-leave-active {
  transition: opacity 0.3s ease;
}
.dw-ad-enter-active > :last-child,
.dw-ad-leave-active > :last-child,
.detail-ad-enter-active > :last-child,
.detail-ad-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.dw-ad-enter-from,
.dw-ad-leave-to,
.detail-ad-enter-from,
.detail-ad-leave-to {
  opacity: 0;
}
.dw-ad-enter-from > :last-child,
.dw-ad-leave-to > :last-child,
.detail-ad-enter-from > :last-child,
.detail-ad-leave-to > :last-child {
  transform: translateX(100%);
}

/* Modal transition */
.modal-ad-enter-active,
.modal-ad-leave-active {
  transition: opacity 0.25s ease;
}
.modal-ad-enter-active > :nth-child(2),
.modal-ad-leave-active > :nth-child(2) {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}
.modal-ad-enter-from,
.modal-ad-leave-to {
  opacity: 0;
}
.modal-ad-enter-from > :nth-child(2),
.modal-ad-leave-to > :nth-child(2) {
  transform: scale(0.95) translateY(8px);
  opacity: 0;
}

/* Fade (export menu) */
.fade-ad-enter-active,
.fade-ad-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.fade-ad-enter-from,
.fade-ad-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
