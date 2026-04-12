<script setup lang="ts">
/**
 * TenantSEO.vue — SEO и продвижение · B2B Tenant Dashboard
 *
 * Вертикали:
 *   Beauty · Taxi · Food · Hotels · RealEstate · Flowers
 *   Fashion · Furniture · Fitness · Travel · default
 *
 * ───────────────────────────────────────────────────────────────
 *  Функционал:
 *   1.  Header: SEO-score (0-100), «Обновить из поисковиков»,
 *       period pills, refresh, export, fullscreen
 *   2.  8 KPI-виджетов: SEO Score · Органика · Ср. позиция
 *       · Индексация · CTR · Backlinks · Ключевые слова · DA
 *   3.  Score-прогресс + рекомендации по улучшению
 *   4.  4 таба: 📊 Обзор (трафик + позиции графики)
 *               🔍 Ключевые слова (таблица + динамика)
 *               🏷️ Мета-теги (редактор страниц)
 *               🗺️ Технический SEO (sitemap, robots, Core Web)
 *   5.  Таблица запросов: запрос, позиция, Δ, трафик, CTR, URL
 *   6.  Meta-tag Editor modal (title, description, og:, canonical)
 *   7.  Detail Drawer: полная аналитика ключевого слова / страницы
 *   8.  Sidebar: Яндекс.Вебмастер + Google SC статусы,
 *       быстрые действия, top-5 страниц, проблемы
 *   9.  Mobile drawer · fullscreen · keyboard Esc · ripple-se
 * ───────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { useAuth, useTenant } from '@/stores'

/* ━━━━━━━━━━━━  TYPES  ━━━━━━━━━━━━ */

type TabKey      = 'overview' | 'keywords' | 'meta' | 'technical'
type Period      = '7d' | '30d' | '90d' | '6m' | '1y'
type SortKey     = 'query' | 'position' | 'delta' | 'traffic' | 'ctr' | 'impressions'
type SortDir     = 'asc' | 'desc'
type CrawlStatus = 'ok' | 'warning' | 'error' | 'pending'
type PageStatus  = 'indexed' | 'not_indexed' | 'excluded' | 'error'

interface KeywordRow {
  id:          number | string
  query:       string
  position:    number
  prevPosition: number
  delta:       number
  traffic:     number
  impressions: number
  ctr:         number
  url:         string
  isBranded:   boolean
  trend:       number[]
}

interface PageMeta {
  id:          number | string
  url:         string
  title:       string
  description: string
  ogTitle:     string
  ogDescription: string
  ogImage:     string
  canonical:   string
  status:      PageStatus
  indexDate:    string
  issues:      string[]
}

interface SeoIssue {
  id:          number | string
  severity:    'critical' | 'warning' | 'info'
  title:       string
  description: string
  page?:       string
  fixUrl?:     string
}

interface TrafficPoint {
  date:        string
  organic:     number
  direct:      number
  referral:    number
  social:      number
}

interface PositionPoint {
  date:        string
  avg:         number
  top3:        number
  top10:       number
  top30:       number
}

interface CoreWebVital {
  name:        string
  value:       number
  unit:        string
  rating:      'good' | 'needs-improvement' | 'poor'
  threshold:   number
}

interface SearchEngine {
  name:        string
  icon:        string
  status:      'connected' | 'disconnected' | 'error'
  lastSync:    string
  siteUrl:     string
}

interface SeoStats {
  score:           number
  organicTraffic:  number
  organicDelta:    number
  avgPosition:     number
  positionDelta:   number
  indexedPages:    number
  indexedDelta:    number
  ctr:             number
  ctrDelta:        number
  backlinks:       number
  backlinksDelta:  number
  keywords:        number
  keywordsDelta:   number
  domainAuthority: number
  daDelta:         number
  crawlErrors:     number
}

interface VerticalSeoCfg {
  label:            string
  icon:             string
  accent:           string
  topKeywords:      string[]
  schemaTypes:      string[]
}

/* ━━━━━━━━━━━━  PROPS / EMITS  ━━━━━━━━━━━━ */

const props = withDefaults(defineProps<{
  vertical?:       string
  stats?:          SeoStats | null
  keywords?:       KeywordRow[]
  pages?:          PageMeta[]
  issues?:         SeoIssue[]
  trafficChart?:   TrafficPoint[]
  positionChart?:  PositionPoint[]
  coreWebVitals?:  CoreWebVital[]
  searchEngines?:  SearchEngine[]
  loading?:        boolean
}>(), {
  vertical:       'default',
  stats:          null,
  keywords:       () => [],
  pages:          () => [],
  issues:         () => [],
  trafficChart:   () => [],
  positionChart:  () => [],
  coreWebVitals:  () => [],
  searchEngines:  () => [],
  loading:        false,
})

const emit = defineEmits<{
  'refresh':            []
  'sync-engines':       []
  'update-meta':        [pageId: number | string, meta: Partial<PageMeta>]
  'submit-sitemap':     []
  'update-robots':      [content: string]
  'export':             [format: 'csv' | 'json' | 'pdf']
  'open-keyword':       [id: number | string]
  'open-page':          [id: number | string]
  'fix-issue':          [id: number | string]
  'toggle-fullscreen':  []
}>()

const auth = useAuth()
const biz  = useTenant()

/* ━━━━━━━━━━━━  VERTICAL CONFIG  ━━━━━━━━━━━━ */

const VERTICAL_CFG: Record<string, VerticalSeoCfg> = {
  beauty:     { label: 'Салон красоты',   icon: '💄', accent: 'pink',    topKeywords: ['салон красоты', 'маникюр', 'стрижка', 'окрашивание'],       schemaTypes: ['LocalBusiness', 'HealthAndBeautyBusiness'] },
  taxi:       { label: 'Такси',           icon: '🚕', accent: 'yellow',  topKeywords: ['такси', 'заказать такси', 'трансфер', 'бизнес-класс'],      schemaTypes: ['LocalBusiness', 'TaxiService'] },
  food:       { label: 'Еда и рестораны', icon: '🍽️', accent: 'orange',  topKeywords: ['доставка еды', 'ресторан', 'меню', 'заказ онлайн'],        schemaTypes: ['Restaurant', 'FoodEstablishment'] },
  hotel:      { label: 'Отели',           icon: '🏨', accent: 'sky',     topKeywords: ['отель', 'бронирование', 'номера', 'гостиница'],             schemaTypes: ['Hotel', 'LodgingBusiness'] },
  realEstate: { label: 'Недвижимость',    icon: '🏢', accent: 'emerald', topKeywords: ['квартира купить', 'аренда', 'новостройки', 'ипотека'],      schemaTypes: ['RealEstateAgent', 'Apartment'] },
  flowers:    { label: 'Цветы',           icon: '💐', accent: 'rose',    topKeywords: ['доставка цветов', 'букет', 'розы', 'цветы на заказ'],       schemaTypes: ['Florist', 'LocalBusiness'] },
  fashion:    { label: 'Мода и одежда',   icon: '👗', accent: 'violet',  topKeywords: ['одежда', 'мода', 'платья', 'бренды'],                       schemaTypes: ['ClothingStore', 'Product'] },
  furniture:  { label: 'Мебель',          icon: '🛋️', accent: 'amber',   topKeywords: ['мебель', 'диваны', 'кухни', 'мебель на заказ'],             schemaTypes: ['FurnitureStore', 'Product'] },
  fitness:    { label: 'Фитнес',          icon: '💪', accent: 'lime',    topKeywords: ['фитнес-клуб', 'тренировки', 'абонемент', 'спортзал'],       schemaTypes: ['ExerciseGym', 'SportsActivityLocation'] },
  travel:     { label: 'Путешествия',     icon: '✈️', accent: 'cyan',    topKeywords: ['туры', 'путешествия', 'авиабилеты', 'отдых'],               schemaTypes: ['TravelAgency', 'TouristTrip'] },
  default:    { label: 'Бизнес',          icon: '📊', accent: 'indigo',  topKeywords: ['компания', 'услуги', 'каталог', 'заказать'],                schemaTypes: ['LocalBusiness', 'Organization'] },
}

const vc = computed<VerticalSeoCfg>(() => VERTICAL_CFG[props.vertical] ?? VERTICAL_CFG.default)

/* ━━━━━━━━━━━━  CONSTANTS  ━━━━━━━━━━━━ */

const TABS: Array<{ key: TabKey; label: string; icon: string }> = [
  { key: 'overview',  label: 'Обзор',          icon: '📊' },
  { key: 'keywords',  label: 'Ключевые слова', icon: '🔍' },
  { key: 'meta',      label: 'Мета-теги',      icon: '🏷️' },
  { key: 'technical', label: 'Технический SEO', icon: '🗺️' },
]

const PERIODS: Array<{ key: Period; label: string }> = [
  { key: '7d',  label: '7 дн' },
  { key: '30d', label: '30 дн' },
  { key: '90d', label: '90 дн' },
  { key: '6m',  label: '6 мес' },
  { key: '1y',  label: '1 год' },
]

const SEVERITY_CLS: Record<string, { cls: string; dot: string; label: string }> = {
  critical: { cls: 'bg-rose-500/12 text-rose-400',    dot: 'bg-rose-500',    label: 'Критично' },
  warning:  { cls: 'bg-amber-500/12 text-amber-400',  dot: 'bg-amber-500',   label: 'Внимание' },
  info:     { cls: 'bg-sky-500/12 text-sky-400',      dot: 'bg-sky-500',     label: 'Инфо' },
}

const PAGE_STATUS_CLS: Record<PageStatus, { cls: string; label: string }> = {
  indexed:      { cls: 'text-emerald-400', label: 'Индексировано' },
  not_indexed:  { cls: 'text-zinc-400',    label: 'Не индексировано' },
  excluded:     { cls: 'text-amber-400',   label: 'Исключено' },
  error:        { cls: 'text-rose-400',    label: 'Ошибка' },
}

const CWV_RATING_CLS: Record<string, string> = {
  good:                'text-emerald-400',
  'needs-improvement': 'text-amber-400',
  poor:                'text-rose-400',
}

/* ━━━━━━━━━━━━  STATE  ━━━━━━━━━━━━ */

const rootEl              = ref<HTMLElement | null>(null)
const isFullscreen        = ref(false)
const activeTab           = ref<TabKey>('overview')
const activePeriod        = ref<Period>('30d')
const searchQuery         = ref('')
const sortKey             = ref<SortKey>('position')
const sortDir             = ref<SortDir>('asc')
const showSidebar         = ref(true)
const showMobileSidebar   = ref(false)
const showDetailDrawer    = ref(false)
const showMetaModal       = ref(false)
const showExportMenu      = ref(false)
const refreshing          = ref(false)
const syncing             = ref(false)

const detailKeyword       = ref<KeywordRow | null>(null)
const editPage            = ref<PageMeta | null>(null)

/* ── Meta editor form ── */
const metaForm = reactive({
  title: '', description: '', ogTitle: '', ogDescription: '', ogImage: '', canonical: '',
})

/* ━━━━━━━━━━━━  COMPUTED  ━━━━━━━━━━━━ */

const pStats = computed<SeoStats>(() =>
  props.stats ?? {
    score: 0, organicTraffic: 0, organicDelta: 0, avgPosition: 0,
    positionDelta: 0, indexedPages: 0, indexedDelta: 0, ctr: 0, ctrDelta: 0,
    backlinks: 0, backlinksDelta: 0, keywords: 0, keywordsDelta: 0,
    domainAuthority: 0, daDelta: 0, crawlErrors: 0,
  },
)

/* ── Score color ── */
const scoreColor = computed(() => {
  const s = pStats.value.score
  if (s >= 80) return 'text-emerald-400'
  if (s >= 60) return 'text-amber-400'
  if (s >= 40) return 'text-orange-400'
  return 'text-rose-400'
})

const scoreBg = computed(() => {
  const s = pStats.value.score
  if (s >= 80) return 'bg-emerald-500'
  if (s >= 60) return 'bg-amber-500'
  if (s >= 40) return 'bg-orange-500'
  return 'bg-rose-500'
})

/* ── Traffic chart max for bars ── */
const trafficMax = computed(() => {
  if (props.trafficChart.length === 0) return 1
  return Math.max(...props.trafficChart.map((p) => p.organic + p.direct + p.referral + p.social), 1)
})

/* ── Sorted + filtered keywords ── */
const filteredKeywords = computed<KeywordRow[]>(() => {
  let list = [...props.keywords]

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase()
    list = list.filter(
      (k) => k.query.toLowerCase().includes(q) || k.url.toLowerCase().includes(q),
    )
  }

  list.sort((a, b) => {
    let cmp = 0
    const ak = a[sortKey.value as keyof KeywordRow]
    const bk = b[sortKey.value as keyof KeywordRow]
    if (typeof ak === 'number' && typeof bk === 'number') cmp = ak - bk
    else if (typeof ak === 'string' && typeof bk === 'string') cmp = ak.localeCompare(bk)
    return sortDir.value === 'asc' ? cmp : -cmp
  })

  return list
})

/* ── Issues by severity ── */
const criticalIssues = computed(() => props.issues.filter((i) => i.severity === 'critical'))
const warningIssues  = computed(() => props.issues.filter((i) => i.severity === 'warning'))

/* ── Connected engines ── */
const connectedEngines = computed(() => props.searchEngines.filter((e) => e.status === 'connected'))

/* ━━━━━━━━━━━━  HELPERS  ━━━━━━━━━━━━ */

function fmtNum(n: number): string {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M`
  if (n >= 1_000)     return `${(n / 1_000).toFixed(1)}K`
  return String(n)
}

function fmtPct(n: number): string { return `${n.toFixed(1)}%` }

function fmtDelta(d: number): string {
  if (d > 0) return `+${d}`
  return String(d)
}

function deltaCls(d: number, inverse = false): string {
  if (d === 0) return 'text-(--t-text-3)'
  const positive = inverse ? d < 0 : d > 0
  return positive ? 'text-emerald-400' : 'text-rose-400'
}

function fmtDate(d: string): string {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('ru-RU', {
    day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit',
  })
}

function toggleSort(key: SortKey) {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = 'asc' }
}

function sortIndicator(key: SortKey): string {
  if (sortKey.value !== key) return ''
  return sortDir.value === 'asc' ? ' ↑' : ' ↓'
}

/* ━━━━━━━━━━━━  ACTIONS  ━━━━━━━━━━━━ */

function doRefresh() {
  refreshing.value = true
  emit('refresh')
  setTimeout(() => { refreshing.value = false }, 1200)
}

function doSyncEngines() {
  syncing.value = true
  emit('sync-engines')
  setTimeout(() => { syncing.value = false }, 2500)
}

function doExport(fmt: 'csv' | 'json' | 'pdf') {
  emit('export', fmt)
  showExportMenu.value = false
}

function openKeywordDetail(kw: KeywordRow) {
  detailKeyword.value = kw
  showDetailDrawer.value = true
}

function closeDetail() {
  showDetailDrawer.value = false
  detailKeyword.value = null
}

function openMetaEditor(page: PageMeta) {
  editPage.value = page
  metaForm.title = page.title
  metaForm.description = page.description
  metaForm.ogTitle = page.ogTitle
  metaForm.ogDescription = page.ogDescription
  metaForm.ogImage = page.ogImage
  metaForm.canonical = page.canonical
  showMetaModal.value = true
}

function submitMeta() {
  if (editPage.value) {
    emit('update-meta', editPage.value.id, { ...metaForm })
  }
  showMetaModal.value = false
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
    if (showMetaModal.value)       { showMetaModal.value = false; return }
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
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-se_0.6s_ease-out]'
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

        <!-- Title + score -->
        <div class="flex items-center gap-3 flex-1 min-w-0">
          <span class="text-2xl">🔎</span>
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <h1 class="text-base sm:text-lg font-extrabold text-(--t-text) truncate leading-snug">
                SEO и продвижение
              </h1>
              <span :class="[
                'shrink-0 px-2 py-0.5 rounded-full text-[10px] font-black tabular-nums',
                pStats.score >= 80 ? 'bg-emerald-500/15 text-emerald-400'
                : pStats.score >= 60 ? 'bg-amber-500/15 text-amber-400'
                : pStats.score >= 40 ? 'bg-orange-500/15 text-orange-400'
                : 'bg-rose-500/15 text-rose-400',
              ]">
                {{ pStats.score }}/100
              </span>
            </div>
            <p class="text-[10px] text-(--t-text-3) truncate">
              {{ vc.icon }} {{ vc.label }} · Позиции · Трафик · Мета-теги · Индексация
            </p>
          </div>
        </div>

        <!-- Actions row -->
        <div class="flex items-center gap-2 flex-wrap">

          <!-- Sync engines -->
          <button
            :class="[
              'relative overflow-hidden flex items-center gap-1.5 px-4 py-2 rounded-xl',
              'text-xs font-semibold bg-(--t-primary) text-white hover:brightness-110',
              'active:scale-95 transition-all',
              syncing ? 'animate-pulse pointer-events-none' : '',
            ]"
            @click="doSyncEngines" @mousedown="ripple"
          >
            <span class="text-sm">{{ syncing ? '⏳' : '🔄' }}</span>
            <span class="hidden sm:inline">{{ syncing ? 'Синхронизация…' : 'Обновить данные' }}</span>
          </button>

          <!-- Period pills -->
          <div class="hidden md:flex items-center gap-0.5 p-0.5 rounded-lg border border-(--t-border)/30
                      bg-(--t-surface)/40">
            <button
              v-for="p in PERIODS" :key="p.key"
              :class="[
                'relative overflow-hidden px-2.5 py-1.5 rounded-md text-[10px] font-medium transition-all',
                activePeriod === p.key
                  ? 'bg-(--t-primary)/15 text-(--t-primary)'
                  : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
              ]"
              @click="activePeriod = p.key" @mousedown="ripple"
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
            <Transition name="fade-se">
              <div v-if="showExportMenu"
                   class="absolute inset-inline-end-0 inset-block-start-full mt-1 z-20
                          w-32 rounded-xl border border-(--t-border)/50 bg-(--t-surface)
                          shadow-xl p-1 flex flex-col">
                <button
                  v-for="fmt in (['csv', 'json', 'pdf'] as const)" :key="fmt"
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
            { label: 'SEO Score',     value: String(pStats.score) + '/100', delta: null,                  icon: '🏆', cls: scoreColor },
            { label: 'Органика',      value: fmtNum(pStats.organicTraffic), delta: pStats.organicDelta,   icon: '👁️', cls: 'text-sky-400' },
            { label: 'Ср. позиция',   value: String(pStats.avgPosition),    delta: pStats.positionDelta,  icon: '📍', cls: 'text-violet-400', inverseDelta: true },
            { label: 'Индексация',    value: fmtNum(pStats.indexedPages),   delta: pStats.indexedDelta,   icon: '📄', cls: 'text-emerald-400' },
            { label: 'CTR',           value: fmtPct(pStats.ctr),            delta: pStats.ctrDelta,       icon: '🖱️', cls: 'text-amber-400' },
            { label: 'Backlinks',     value: fmtNum(pStats.backlinks),      delta: pStats.backlinksDelta, icon: '🔗', cls: 'text-pink-400' },
            { label: 'Ключевые',      value: fmtNum(pStats.keywords),       delta: pStats.keywordsDelta,  icon: '🔍', cls: 'text-cyan-400' },
            { label: 'DA',            value: String(pStats.domainAuthority), delta: pStats.daDelta,        icon: '⚡', cls: 'text-lime-400' },
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
            <p v-if="kpi.delta != null"
               :class="['text-[9px] font-bold tabular-nums mt-0.5',
                        deltaCls(kpi.delta, (kpi as any).inverseDelta)]">
              {{ fmtDelta(kpi.delta) }}
            </p>
          </div>
        </div>

        <!-- ── SCORE PROGRESS ── -->
        <div class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                    backdrop-blur-sm p-4">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-(--t-text)">🏆 SEO Score</span>
            <span :class="['text-sm font-black tabular-nums', scoreColor]">{{ pStats.score }}</span>
          </div>
          <div class="h-3 rounded-full bg-(--t-border)/20 overflow-hidden">
            <div
              :class="['h-full rounded-full transition-all', scoreBg]"
              :style="{ inlineSize: `${Math.max(1, pStats.score)}%` }"
            />
          </div>
          <div class="flex items-center justify-between mt-1.5 text-[9px] text-(--t-text-3)">
            <span>0 — Нет оптимизации</span>
            <span>100 — Идеально</span>
          </div>
        </div>

        <!-- ── TABS ── -->
        <div class="flex items-center gap-1 overflow-x-auto no-scrollbar p-0.5">
          <button
            v-for="tab in TABS" :key="tab.key"
            :class="[
              'relative overflow-hidden shrink-0 flex items-center gap-1.5 px-3.5 py-2.5',
              'rounded-xl text-xs font-medium transition-all',
              activeTab === tab.key
                ? 'bg-(--t-primary)/15 text-(--t-primary) border border-(--t-primary)/30 shadow-sm'
                : 'border border-transparent text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
            ]"
            @click="activeTab = tab.key" @mousedown="ripple"
          >{{ tab.icon }} {{ tab.label }}</button>
        </div>

        <!-- ── TAB: OVERVIEW ── -->
        <template v-if="activeTab === 'overview'">

          <!-- Organic traffic chart (bar) -->
          <div class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                      backdrop-blur-sm p-4 sm:p-5">
            <h3 class="text-xs font-bold text-(--t-text) mb-4">📊 Органический трафик</h3>

            <div v-if="props.trafficChart.length === 0"
                 class="flex items-center justify-center py-10 text-xs text-(--t-text-3)">
              Нет данных
            </div>
            <div v-else class="flex items-end gap-px sm:gap-1 h-40">
              <div v-for="(pt, i) in props.trafficChart" :key="i"
                   class="group/bar relative flex-1 flex flex-col justify-end
                          rounded-t-md overflow-hidden cursor-default"
                   :title="`${pt.date}: ${pt.organic + pt.direct + pt.referral + pt.social}`"
              >
                <!-- stacked bar -->
                <div class="bg-sky-500/70 transition-all rounded-t-sm"
                     :style="{ blockSize: `${(pt.organic / trafficMax) * 100}%` }" />
                <div class="bg-violet-500/50 transition-all"
                     :style="{ blockSize: `${(pt.direct / trafficMax) * 100}%` }" />
                <div class="bg-amber-500/40 transition-all"
                     :style="{ blockSize: `${(pt.referral / trafficMax) * 100}%` }" />
                <div class="bg-pink-500/30 transition-all rounded-b-sm"
                     :style="{ blockSize: `${(pt.social / trafficMax) * 100}%` }" />
              </div>
            </div>

            <!-- Legend -->
            <div class="flex items-center gap-4 mt-3 text-[9px] text-(--t-text-3)">
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-sky-500/70" /> Органика</span>
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-violet-500/50" /> Прямой</span>
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-amber-500/40" /> Реферал</span>
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-pink-500/30" /> Соц. сети</span>
            </div>
          </div>

          <!-- Positions chart (horizontal) -->
          <div class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                      backdrop-blur-sm p-4 sm:p-5">
            <h3 class="text-xs font-bold text-(--t-text) mb-4">📍 Позиции в поиске</h3>

            <div v-if="props.positionChart.length === 0"
                 class="flex items-center justify-center py-10 text-xs text-(--t-text-3)">
              Нет данных
            </div>
            <div v-else class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div v-for="bucket in [
                { label: 'TOP-3',  key: 'top3'  as const, cls: 'text-emerald-400 bg-emerald-500/12' },
                { label: 'TOP-10', key: 'top10' as const, cls: 'text-sky-400 bg-sky-500/12' },
                { label: 'TOP-30', key: 'top30' as const, cls: 'text-amber-400 bg-amber-500/12' },
                { label: 'Средняя', key: 'avg'  as const, cls: 'text-violet-400 bg-violet-500/12' },
              ]" :key="bucket.label"
                 class="rounded-xl p-3 text-center"
                 :class="bucket.cls.split(' ').slice(1).join(' ')"
              >
                <p class="text-[10px] text-(--t-text-3) mb-1">{{ bucket.label }}</p>
                <p :class="['text-xl font-black tabular-nums', bucket.cls.split(' ')[0]]">
                  {{ props.positionChart.length > 0
                       ? props.positionChart[props.positionChart.length - 1][bucket.key]
                       : '—' }}
                </p>
              </div>
            </div>
          </div>

          <!-- Issues snapshot -->
          <div v-if="props.issues.length > 0"
               class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                      backdrop-blur-sm p-4 sm:p-5">
            <h3 class="text-xs font-bold text-(--t-text) mb-3">
              ⚠️ Проблемы ({{ props.issues.length }})
            </h3>
            <div class="flex flex-col gap-2">
              <button v-for="issue in props.issues.slice(0, 5)" :key="issue.id"
                class="relative overflow-hidden flex items-start gap-3 px-3 py-2.5 rounded-xl
                       text-start hover:bg-(--t-card-hover) active:scale-[0.98] transition-all"
                @click="emit('fix-issue', issue.id)" @mousedown="ripple"
              >
                <span :class="[
                  'shrink-0 w-2 h-2 rounded-full mt-1',
                  SEVERITY_CLS[issue.severity].dot,
                ]" />
                <div class="flex-1 min-w-0">
                  <p class="text-[11px] font-bold text-(--t-text) truncate">{{ issue.title }}</p>
                  <p class="text-[9px] text-(--t-text-3) line-clamp-1">{{ issue.description }}</p>
                </div>
                <span :class="[
                  'shrink-0 px-1.5 py-px rounded text-[7px] font-bold',
                  SEVERITY_CLS[issue.severity].cls,
                ]">{{ SEVERITY_CLS[issue.severity].label }}</span>
              </button>
            </div>
          </div>
        </template>

        <!-- ── TAB: KEYWORDS ── -->
        <template v-if="activeTab === 'keywords'">

          <!-- Search -->
          <div class="relative">
            <input
              v-model="searchQuery" type="text"
              placeholder="Поиск по запросу или URL…"
              class="inline-size-full py-2.5 ps-9 pe-3 rounded-xl border border-(--t-border)/50
                     bg-(--t-bg)/60 text-xs text-(--t-text) placeholder:text-(--t-text-3)
                     focus:outline-none focus:border-(--t-primary)/50 transition-colors"
            />
            <span class="absolute inset-inline-start-3 inset-block-start-1/2 -translate-y-1/2
                         text-sm text-(--t-text-3) pointer-events-none">🔍</span>
          </div>

          <!-- Loading -->
          <div v-if="props.loading && filteredKeywords.length === 0"
               class="space-y-2">
            <div v-for="n in 6" :key="n"
                 class="h-12 rounded-xl bg-(--t-surface)/30 animate-pulse" />
          </div>

          <!-- Empty -->
          <div v-else-if="filteredKeywords.length === 0" class="py-14 text-center">
            <p class="text-4xl mb-2">🔍</p>
            <p class="text-sm font-semibold text-(--t-text-2)">Ключевые слова не найдены</p>
            <p class="text-[10px] text-(--t-text-3) mt-1">Синхронизируйте данные из поисковиков</p>
          </div>

          <!-- Table (desktop) -->
          <div v-else class="hidden sm:block rounded-2xl border border-(--t-border)/30
                            bg-(--t-surface)/40 backdrop-blur-sm overflow-hidden">
            <div class="overflow-x-auto">
              <table class="inline-size-full text-xs">
                <thead>
                  <tr class="border-b border-(--t-border)/30 text-[10px] text-(--t-text-3) font-bold uppercase">
                    <th class="text-start px-4 py-3 cursor-pointer hover:text-(--t-text) transition-colors"
                        @click="toggleSort('query')">Запрос{{ sortIndicator('query') }}</th>
                    <th class="text-end px-3 py-3 cursor-pointer hover:text-(--t-text) transition-colors"
                        @click="toggleSort('position')">Позиция{{ sortIndicator('position') }}</th>
                    <th class="text-end px-3 py-3 cursor-pointer hover:text-(--t-text) transition-colors"
                        @click="toggleSort('delta')">Δ{{ sortIndicator('delta') }}</th>
                    <th class="text-end px-3 py-3 cursor-pointer hover:text-(--t-text) transition-colors"
                        @click="toggleSort('traffic')">Трафик{{ sortIndicator('traffic') }}</th>
                    <th class="text-end px-3 py-3 cursor-pointer hover:text-(--t-text) transition-colors"
                        @click="toggleSort('impressions')">Показы{{ sortIndicator('impressions') }}</th>
                    <th class="text-end px-3 py-3 cursor-pointer hover:text-(--t-text) transition-colors"
                        @click="toggleSort('ctr')">CTR{{ sortIndicator('ctr') }}</th>
                    <th class="text-start px-3 py-3">URL</th>
                    <th class="px-3 py-3" />
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="kw in filteredKeywords" :key="kw.id"
                      class="border-b border-(--t-border)/15 hover:bg-(--t-card-hover)/40 transition-colors">
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-2">
                        <span class="text-(--t-text) font-medium">{{ kw.query }}</span>
                        <span v-if="kw.isBranded"
                              class="shrink-0 px-1 py-px rounded text-[7px] font-bold
                                     bg-violet-500/12 text-violet-400">brand</span>
                      </div>
                    </td>
                    <td class="text-end px-3 py-3 tabular-nums">
                      <span :class="[
                        'font-bold',
                        kw.position <= 3 ? 'text-emerald-400' :
                        kw.position <= 10 ? 'text-sky-400' :
                        kw.position <= 30 ? 'text-amber-400' : 'text-(--t-text-3)',
                      ]">{{ kw.position }}</span>
                    </td>
                    <td :class="['text-end px-3 py-3 font-bold tabular-nums',
                                deltaCls(kw.delta, true)]">
                      {{ kw.delta === 0 ? '—' : fmtDelta(kw.delta) }}
                    </td>
                    <td class="text-end px-3 py-3 tabular-nums text-(--t-text-2)">{{ fmtNum(kw.traffic) }}</td>
                    <td class="text-end px-3 py-3 tabular-nums text-(--t-text-3)">{{ fmtNum(kw.impressions) }}</td>
                    <td class="text-end px-3 py-3 tabular-nums text-(--t-text-2)">{{ fmtPct(kw.ctr) }}</td>
                    <td class="px-3 py-3">
                      <span class="text-[9px] text-(--t-text-3) truncate block max-w-32">{{ kw.url }}</span>
                    </td>
                    <td class="px-3 py-3">
                      <button
                        class="relative overflow-hidden px-2.5 py-1.5 rounded-lg text-[10px]
                               text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)
                               active:scale-95 transition-all"
                        @click="openKeywordDetail(kw)" @mousedown="ripple"
                      >📊</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Cards (mobile) -->
          <div class="sm:hidden flex flex-col gap-2.5">
            <button v-for="kw in filteredKeywords" :key="kw.id"
              class="relative overflow-hidden rounded-xl border border-(--t-border)/30
                     bg-(--t-surface)/40 p-3.5 text-start
                     hover:border-(--t-border)/60 active:scale-[0.98] transition-all"
              @click="openKeywordDetail(kw)" @mousedown="ripple"
            >
              <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-(--t-text) truncate">{{ kw.query }}</span>
                <span :class="[
                  'shrink-0 text-sm font-black tabular-nums',
                  kw.position <= 3 ? 'text-emerald-400' :
                  kw.position <= 10 ? 'text-sky-400' :
                  kw.position <= 30 ? 'text-amber-400' : 'text-(--t-text-3)',
                ]">{{ kw.position }}</span>
              </div>
              <div class="flex items-center gap-3 text-[9px] text-(--t-text-3) tabular-nums">
                <span :class="deltaCls(kw.delta, true)">Δ {{ fmtDelta(kw.delta) }}</span>
                <span>👁️ {{ fmtNum(kw.traffic) }}</span>
                <span>CTR {{ fmtPct(kw.ctr) }}</span>
              </div>
            </button>
          </div>
        </template>

        <!-- ── TAB: META TAGS ── -->
        <template v-if="activeTab === 'meta'">

          <div v-if="props.pages.length === 0" class="py-14 text-center">
            <p class="text-4xl mb-2">🏷️</p>
            <p class="text-sm font-semibold text-(--t-text-2)">Страницы не загружены</p>
          </div>

          <div v-else class="flex flex-col gap-3">
            <div v-for="page in props.pages" :key="page.id"
                 class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                        backdrop-blur-sm p-4">

              <div class="flex items-start justify-between gap-3 mb-3">
                <div class="min-w-0 flex-1">
                  <p class="text-[11px] font-bold text-(--t-text) truncate">{{ page.url }}</p>
                  <span :class="['text-[9px] font-medium', PAGE_STATUS_CLS[page.status].cls]">
                    {{ PAGE_STATUS_CLS[page.status].label }}
                  </span>
                </div>
                <button
                  class="relative overflow-hidden shrink-0 px-3 py-1.5 rounded-lg text-[10px] font-medium
                         border border-(--t-border)/40 text-(--t-text-3) hover:text-(--t-text)
                         hover:bg-(--t-card-hover) active:scale-95 transition-all"
                  @click="openMetaEditor(page)" @mousedown="ripple"
                >✏️ Редактировать</button>
              </div>

              <!-- Title -->
              <div class="mb-2">
                <p class="text-[9px] text-(--t-text-3) mb-0.5">Title</p>
                <p :class="[
                  'text-xs',
                  page.title ? 'text-(--t-text)' : 'text-rose-400 italic',
                ]">{{ page.title || 'Не задан' }}</p>
                <div v-if="page.title" class="mt-0.5 h-1 rounded-full bg-(--t-border)/20 overflow-hidden">
                  <div :class="[
                    'h-full rounded-full transition-all',
                    page.title.length <= 60 ? 'bg-emerald-500' :
                    page.title.length <= 70 ? 'bg-amber-500' : 'bg-rose-500',
                  ]" :style="{ inlineSize: `${Math.min(100, (page.title.length / 70) * 100)}%` }" />
                </div>
                <p v-if="page.title" class="text-[8px] text-(--t-text-3) mt-0.5 tabular-nums">
                  {{ page.title.length }}/60 символов
                </p>
              </div>

              <!-- Description -->
              <div>
                <p class="text-[9px] text-(--t-text-3) mb-0.5">Description</p>
                <p :class="[
                  'text-[10px] leading-relaxed',
                  page.description ? 'text-(--t-text-2)' : 'text-rose-400 italic',
                ]">{{ page.description || 'Не задан' }}</p>
                <div v-if="page.description" class="mt-0.5 h-1 rounded-full bg-(--t-border)/20 overflow-hidden">
                  <div :class="[
                    'h-full rounded-full transition-all',
                    page.description.length <= 155 ? 'bg-emerald-500' :
                    page.description.length <= 170 ? 'bg-amber-500' : 'bg-rose-500',
                  ]" :style="{ inlineSize: `${Math.min(100, (page.description.length / 170) * 100)}%` }" />
                </div>
                <p v-if="page.description" class="text-[8px] text-(--t-text-3) mt-0.5 tabular-nums">
                  {{ page.description.length }}/155 символов
                </p>
              </div>

              <!-- Issues -->
              <div v-if="page.issues.length > 0" class="mt-3 flex flex-wrap gap-1.5">
                <span v-for="iss in page.issues" :key="iss"
                      class="px-1.5 py-px rounded text-[8px] font-medium bg-rose-500/10 text-rose-400">
                  {{ iss }}
                </span>
              </div>
            </div>
          </div>
        </template>

        <!-- ── TAB: TECHNICAL SEO ── -->
        <template v-if="activeTab === 'technical'">

          <!-- Core Web Vitals -->
          <div class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                      backdrop-blur-sm p-4 sm:p-5">
            <h3 class="text-xs font-bold text-(--t-text) mb-4">⚡ Core Web Vitals</h3>

            <div v-if="props.coreWebVitals.length === 0"
                 class="text-center py-6 text-xs text-(--t-text-3)">Нет данных</div>
            <div v-else class="grid gap-3 sm:grid-cols-3">
              <div v-for="cwv in props.coreWebVitals" :key="cwv.name"
                   class="rounded-xl bg-(--t-bg)/50 p-4 text-center">
                <p class="text-[10px] text-(--t-text-3) mb-1">{{ cwv.name }}</p>
                <p :class="['text-2xl font-black tabular-nums', CWV_RATING_CLS[cwv.rating] ?? 'text-(--t-text)']">
                  {{ cwv.value }}{{ cwv.unit }}
                </p>
                <p class="text-[8px] mt-1" :class="CWV_RATING_CLS[cwv.rating]">
                  {{ cwv.rating === 'good' ? '✓ Хорошо' : cwv.rating === 'needs-improvement' ? '⚠ Улучшить' : '✕ Плохо' }}
                </p>
              </div>
            </div>
          </div>

          <!-- Sitemap + Robots -->
          <div class="grid gap-3 sm:grid-cols-2">

            <!-- Sitemap -->
            <div class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                        backdrop-blur-sm p-4">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
                🗺️ Карта сайта (sitemap.xml)
              </h4>
              <div class="flex flex-col gap-2 text-xs text-(--t-text-2)">
                <p>Страниц в карте: <span class="font-bold text-(--t-text) tabular-nums">{{ pStats.indexedPages }}</span></p>
                <p>Ошибок краулинга: <span :class="[
                  'font-bold tabular-nums',
                  pStats.crawlErrors > 0 ? 'text-rose-400' : 'text-emerald-400',
                ]">{{ pStats.crawlErrors }}</span></p>
              </div>
              <button
                class="relative overflow-hidden inline-size-full mt-3 py-2.5 rounded-xl text-[10px]
                       font-medium border border-(--t-border)/40 text-(--t-text-2)
                       hover:bg-(--t-card-hover) active:scale-95 transition-all"
                @click="emit('submit-sitemap')" @mousedown="ripple"
              >📤 Отправить в поисковики</button>
            </div>

            <!-- Schema -->
            <div class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40
                        backdrop-blur-sm p-4">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
                🧩 Schema.org ({{ vc.label }})
              </h4>
              <div class="flex flex-wrap gap-1.5 mb-3">
                <span v-for="schema in vc.schemaTypes" :key="schema"
                      class="px-2 py-0.5 rounded-lg text-[9px] font-medium
                             bg-(--t-primary)/10 text-(--t-primary)">{{ schema }}</span>
              </div>
              <p class="text-[10px] text-(--t-text-3) leading-relaxed">
                Рекомендуемые типы разметки для вертикали «{{ vc.label }}».
                Добавьте JSON-LD на все ключевые страницы.
              </p>
            </div>
          </div>

          <!-- Recommended keywords for vertical -->
          <div class="rounded-2xl border border-(--t-primary)/20 bg-(--t-primary)/4
                      backdrop-blur-sm p-4">
            <div class="flex items-center gap-2 mb-3">
              <span class="text-lg">{{ vc.icon }}</span>
              <h4 class="text-xs font-bold text-(--t-text)">
                Ключевые слова для «{{ vc.label }}»
              </h4>
            </div>
            <div class="flex flex-wrap gap-2">
              <span v-for="kw in vc.topKeywords" :key="kw"
                    class="px-2.5 py-1 rounded-lg text-[10px] font-medium
                           bg-(--t-surface)/60 border border-(--t-border)/30 text-(--t-text-2)">
                {{ kw }}
              </span>
            </div>
          </div>
        </template>
      </div>

      <!-- ═══ SIDEBAR (desktop) ═══ -->
      <Transition name="sb-se">
        <aside v-if="showSidebar"
               class="hidden xl:flex shrink-0 flex-col gap-4 w-72">

          <!-- Search engines status -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              🌐 Поисковые системы
            </h3>
            <div v-if="props.searchEngines.length === 0"
                 class="text-center py-3 text-[10px] text-(--t-text-3)">Не подключены</div>
            <div v-else class="flex flex-col gap-2.5">
              <div v-for="eng in props.searchEngines" :key="eng.name"
                   class="flex items-center gap-2.5">
                <span class="shrink-0 text-sm">{{ eng.icon }}</span>
                <div class="flex-1 min-w-0">
                  <p class="text-[11px] font-bold text-(--t-text) truncate">{{ eng.name }}</p>
                  <p class="text-[8px] text-(--t-text-3)">
                    {{ eng.status === 'connected' ? '✓ Подключено' :
                       eng.status === 'error' ? '✕ Ошибка' : '○ Не подключено' }}
                    <span v-if="eng.lastSync"> · {{ fmtDate(eng.lastSync) }}</span>
                  </p>
                </div>
                <span :class="[
                  'shrink-0 w-2 h-2 rounded-full',
                  eng.status === 'connected' ? 'bg-emerald-500' :
                  eng.status === 'error' ? 'bg-rose-500' : 'bg-zinc-500',
                ]" />
              </div>
            </div>
          </div>

          <!-- Issues summary -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              ⚠️ Проблемы
            </h3>
            <div class="flex flex-col gap-2">
              <div class="flex items-center justify-between text-xs">
                <span class="text-rose-400">Критичные</span>
                <span class="font-bold text-rose-400 tabular-nums">{{ criticalIssues.length }}</span>
              </div>
              <div class="flex items-center justify-between text-xs">
                <span class="text-amber-400">Предупреждения</span>
                <span class="font-bold text-amber-400 tabular-nums">{{ warningIssues.length }}</span>
              </div>
              <div class="flex items-center justify-between text-xs">
                <span class="text-(--t-text-3)">Всего</span>
                <span class="font-bold text-(--t-text-2) tabular-nums">{{ props.issues.length }}</span>
              </div>
            </div>
          </div>

          <!-- Top-5 keywords -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              🏅 Лучшие позиции
            </h3>
            <div v-if="props.keywords.length === 0" class="text-center py-3 text-[10px] text-(--t-text-3)">
              Нет данных
            </div>
            <div v-else class="flex flex-col gap-1.5">
              <button v-for="kw in [...props.keywords]
                .sort((a, b) => a.position - b.position).slice(0, 5)"
                :key="kw.id"
                class="relative overflow-hidden flex items-center gap-2.5 px-3 py-2 rounded-xl
                       text-start hover:bg-(--t-card-hover) active:scale-[0.97] transition-all"
                @click="openKeywordDetail(kw)" @mousedown="ripple"
              >
                <span :class="[
                  'shrink-0 w-6 text-center text-xs font-black tabular-nums',
                  kw.position <= 3 ? 'text-emerald-400' :
                  kw.position <= 10 ? 'text-sky-400' : 'text-amber-400',
                ]">{{ kw.position }}</span>
                <span class="flex-1 text-[10px] text-(--t-text-2) truncate">{{ kw.query }}</span>
                <span :class="['text-[9px] font-bold tabular-nums', deltaCls(kw.delta, true)]">
                  {{ kw.delta === 0 ? '—' : fmtDelta(kw.delta) }}
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
              <button v-for="act in [
                { label: '🔄 Синхронизировать',       fn: () => doSyncEngines() },
                { label: '🏷️ Редактор мета-тегов',   fn: () => { activeTab = 'meta' } },
                { label: '🗺️ Отправить sitemap',      fn: () => emit('submit-sitemap') },
                { label: '📥 Экспорт ключевых слов', fn: () => doExport('csv') },
                { label: '⚡ Core Web Vitals',        fn: () => { activeTab = 'technical' } },
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
    <Transition name="dw-se">
      <div v-if="showMobileSidebar"
           class="fixed inset-0 z-50 flex" @click.self="showMobileSidebar = false">
        <div class="absolute inset-0 bg-black/40" @click="showMobileSidebar = false" />
        <div class="relative z-10 ms-auto inline-size-72 max-w-[85vw] h-full bg-(--t-surface)
                    border-s border-(--t-border) overflow-y-auto p-4 flex flex-col gap-4">

          <div class="flex items-center justify-between mb-1">
            <h3 class="text-sm font-bold text-(--t-text)">🔎 SEO</h3>
            <button class="text-(--t-text-3) hover:text-(--t-text) transition-colors"
                    @click="showMobileSidebar = false">✕</button>
          </div>

          <!-- Score -->
          <div class="text-center py-4 rounded-xl bg-(--t-bg)/50">
            <p class="text-[9px] text-(--t-text-3) mb-1">SEO Score</p>
            <p :class="['text-3xl font-black tabular-nums', scoreColor]">{{ pStats.score }}</p>
          </div>

          <!-- KPIs -->
          <div class="grid grid-cols-2 gap-2">
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Органика</p>
              <p class="text-xs font-bold text-sky-400 tabular-nums">{{ fmtNum(pStats.organicTraffic) }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Позиция</p>
              <p class="text-xs font-bold text-violet-400 tabular-nums">{{ pStats.avgPosition }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Индексация</p>
              <p class="text-xs font-bold text-emerald-400 tabular-nums">{{ fmtNum(pStats.indexedPages) }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Ошибки</p>
              <p class="text-xs font-bold text-rose-400 tabular-nums">{{ props.issues.length }}</p>
            </div>
          </div>

          <!-- Engines -->
          <div v-if="props.searchEngines.length > 0">
            <p class="text-[10px] font-bold text-(--t-text-3) uppercase mb-2">🌐 Поисковики</p>
            <div class="flex flex-col gap-1.5">
              <div v-for="eng in props.searchEngines" :key="eng.name"
                   class="flex items-center gap-2 px-3 py-2 rounded-xl bg-(--t-bg)/30">
                <span class="text-sm">{{ eng.icon }}</span>
                <span class="flex-1 text-[10px] text-(--t-text-2) truncate">{{ eng.name }}</span>
                <span :class="[
                  'w-2 h-2 rounded-full',
                  eng.status === 'connected' ? 'bg-emerald-500' :
                  eng.status === 'error' ? 'bg-rose-500' : 'bg-zinc-500',
                ]" />
              </div>
            </div>
          </div>

          <!-- Period -->
          <div class="flex flex-wrap gap-1">
            <button v-for="p in PERIODS" :key="p.key"
              :class="[
                'relative overflow-hidden px-3 py-1.5 rounded-lg text-[10px] font-medium transition-all',
                activePeriod === p.key
                  ? 'bg-(--t-primary)/15 text-(--t-primary)'
                  : 'text-(--t-text-3) hover:text-(--t-text)',
              ]"
              @click="activePeriod = p.key" @mousedown="ripple"
            >{{ p.label }}</button>
          </div>

          <div class="flex flex-col gap-1.5 mt-2">
            <button class="relative overflow-hidden py-2.5 rounded-xl text-[10px] font-semibold
                           bg-(--t-primary) text-white active:scale-95 transition-all"
                    @click="showMobileSidebar = false; doSyncEngines()" @mousedown="ripple"
            >🔄 Синхронизировать</button>
            <button class="relative overflow-hidden py-2.5 rounded-xl text-[10px] font-semibold
                           border border-(--t-border)/50 text-(--t-text)
                           active:scale-95 transition-all"
                    @click="showMobileSidebar = false; doRefresh()" @mousedown="ripple"
            >♻️ Обновить</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ═══════ KEYWORD DETAIL DRAWER ═══════ -->
    <Transition name="detail-se">
      <div v-if="showDetailDrawer && detailKeyword"
           class="fixed inset-0 z-50 flex" @click.self="closeDetail">
        <div class="absolute inset-0 bg-black/40" @click="closeDetail" />
        <div class="relative z-10 ms-auto inline-size-full sm:inline-size-[26rem] max-w-full h-full
                    bg-(--t-surface) border-s border-(--t-border) overflow-y-auto flex flex-col">

          <!-- Header -->
          <div class="sticky inset-block-start-0 z-10 flex items-center gap-3 px-5 py-4
                      bg-(--t-surface)/90 backdrop-blur-xl border-b border-(--t-border)/30">
            <span class="shrink-0 text-lg">🔍</span>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-bold text-(--t-text) truncate">{{ detailKeyword.query }}</h3>
              <p class="text-[9px] text-(--t-text-3) truncate">{{ detailKeyword.url }}</p>
            </div>
            <button class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="closeDetail">✕</button>
          </div>

          <div class="flex-1 p-5 flex flex-col gap-5">

            <!-- Position highlight -->
            <div class="flex items-center justify-center gap-6 py-4 rounded-xl bg-(--t-bg)/50">
              <div class="text-center">
                <p class="text-[9px] text-(--t-text-3) mb-1">Позиция</p>
                <p :class="[
                  'text-3xl font-black tabular-nums',
                  detailKeyword.position <= 3 ? 'text-emerald-400' :
                  detailKeyword.position <= 10 ? 'text-sky-400' :
                  detailKeyword.position <= 30 ? 'text-amber-400' : 'text-(--t-text-3)',
                ]">{{ detailKeyword.position }}</p>
              </div>
              <div class="text-center">
                <p class="text-[9px] text-(--t-text-3) mb-1">Было</p>
                <p class="text-xl font-bold text-(--t-text-2) tabular-nums">{{ detailKeyword.prevPosition }}</p>
              </div>
              <div class="text-center">
                <p class="text-[9px] text-(--t-text-3) mb-1">Δ</p>
                <p :class="['text-xl font-bold tabular-nums', deltaCls(detailKeyword.delta, true)]">
                  {{ fmtDelta(detailKeyword.delta) }}
                </p>
              </div>
            </div>

            <!-- Metrics grid -->
            <div class="grid grid-cols-2 gap-3">
              <div class="rounded-xl bg-(--t-bg)/50 p-3.5 text-center">
                <p class="text-[9px] text-(--t-text-3) mb-1">Трафик</p>
                <p class="text-lg font-black text-sky-400 tabular-nums">{{ fmtNum(detailKeyword.traffic) }}</p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-3.5 text-center">
                <p class="text-[9px] text-(--t-text-3) mb-1">Показы</p>
                <p class="text-lg font-black text-violet-400 tabular-nums">{{ fmtNum(detailKeyword.impressions) }}</p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-3.5 text-center">
                <p class="text-[9px] text-(--t-text-3) mb-1">CTR</p>
                <p class="text-lg font-black text-amber-400 tabular-nums">{{ fmtPct(detailKeyword.ctr) }}</p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-3.5 text-center">
                <p class="text-[9px] text-(--t-text-3) mb-1">Тип</p>
                <p class="text-xs font-bold text-(--t-text-2)">
                  {{ detailKeyword.isBranded ? '🏷️ Брендовый' : '🔍 Небрендовый' }}
                </p>
              </div>
            </div>

            <!-- Mini trend -->
            <div v-if="detailKeyword.trend.length > 0"
                 class="rounded-xl bg-(--t-bg)/50 p-4">
              <p class="text-[10px] font-bold text-(--t-text-3) uppercase mb-3">📈 Тренд позиции</p>
              <div class="flex items-end gap-px h-16">
                <div v-for="(val, i) in detailKeyword.trend" :key="i"
                     class="flex-1 rounded-t-sm bg-sky-500/60 transition-all"
                     :style="{
                       blockSize: `${Math.max(4, (1 - val / Math.max(...detailKeyword.trend, 1)) * 100)}%`
                     }" />
              </div>
              <div class="flex items-center justify-between mt-1 text-[8px] text-(--t-text-3)">
                <span>{{ activePeriod }} назад</span>
                <span>Сейчас</span>
              </div>
            </div>

            <!-- URL -->
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3) mb-1">URL</p>
              <code class="text-[10px] text-sky-400 break-all select-all leading-relaxed">
                {{ detailKeyword.url }}
              </code>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ═══════ META EDITOR MODAL ═══════ -->
    <Transition name="modal-se">
      <div v-if="showMetaModal && editPage"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="showMetaModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showMetaModal = false" />
        <div class="relative z-10 inline-size-full max-w-lg bg-(--t-surface) rounded-2xl
                    border border-(--t-border)/60 shadow-2xl overflow-hidden
                    max-block-size-[85vh] overflow-y-auto">

          <!-- Header -->
          <div class="sticky inset-block-start-0 z-10 flex items-center justify-between px-5 py-4
                      border-b border-(--t-border)/30 bg-(--t-surface)/90 backdrop-blur-xl">
            <div>
              <h3 class="text-sm font-bold text-(--t-text)">🏷️ Редактор мета-тегов</h3>
              <p class="text-[9px] text-(--t-text-3) truncate max-w-64">{{ editPage.url }}</p>
            </div>
            <button class="w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="showMetaModal = false">✕</button>
          </div>

          <div class="p-5 flex flex-col gap-4">

            <!-- Title -->
            <label class="flex flex-col gap-1.5">
              <div class="flex items-center justify-between">
                <span class="text-[10px] text-(--t-text-3) font-medium">Title</span>
                <span :class="[
                  'text-[9px] tabular-nums font-medium',
                  metaForm.title.length <= 60 ? 'text-emerald-400' :
                  metaForm.title.length <= 70 ? 'text-amber-400' : 'text-rose-400',
                ]">{{ metaForm.title.length }}/60</span>
              </div>
              <input v-model="metaForm.title" type="text"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
                placeholder="Заголовок страницы…"
              />
            </label>

            <!-- Description -->
            <label class="flex flex-col gap-1.5">
              <div class="flex items-center justify-between">
                <span class="text-[10px] text-(--t-text-3) font-medium">Description</span>
                <span :class="[
                  'text-[9px] tabular-nums font-medium',
                  metaForm.description.length <= 155 ? 'text-emerald-400' :
                  metaForm.description.length <= 170 ? 'text-amber-400' : 'text-rose-400',
                ]">{{ metaForm.description.length }}/155</span>
              </div>
              <textarea v-model="metaForm.description" rows="3"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3) resize-none
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
                placeholder="Описание страницы…"
              />
            </label>

            <!-- OG Title -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">og:title</span>
              <input v-model="metaForm.ogTitle" type="text"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
                placeholder="Open Graph заголовок…"
              />
            </label>

            <!-- OG Description -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">og:description</span>
              <textarea v-model="metaForm.ogDescription" rows="2"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3) resize-none
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
                placeholder="Open Graph описание…"
              />
            </label>

            <!-- OG Image -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">og:image</span>
              <input v-model="metaForm.ogImage" type="url"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3) font-mono
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
                placeholder="https://example.com/image.jpg"
              />
            </label>

            <!-- Canonical -->
            <label class="flex flex-col gap-1.5">
              <span class="text-[10px] text-(--t-text-3) font-medium">Canonical URL</span>
              <input v-model="metaForm.canonical" type="url"
                class="py-2.5 px-3 rounded-xl border border-(--t-border)/50 bg-(--t-bg)/60
                       text-xs text-(--t-text) placeholder:text-(--t-text-3) font-mono
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
                placeholder="https://example.com/page"
              />
            </label>

            <!-- SERP Preview -->
            <div class="rounded-xl bg-(--t-bg)/50 border border-(--t-border)/30 p-4">
              <p class="text-[9px] text-(--t-text-3) uppercase font-bold mb-2">Превью в поиске</p>
              <p class="text-sm font-medium text-sky-400 leading-snug truncate">
                {{ metaForm.title || 'Заголовок страницы' }}
              </p>
              <p class="text-[10px] text-emerald-500 truncate mt-0.5">
                {{ editPage.url }}
              </p>
              <p class="text-[10px] text-(--t-text-3) line-clamp-2 mt-1 leading-relaxed">
                {{ metaForm.description || 'Описание страницы будет отображаться здесь…' }}
              </p>
            </div>
          </div>

          <!-- Footer -->
          <div class="sticky inset-block-end-0 flex gap-2 px-5 py-4
                      border-t border-(--t-border)/30 bg-(--t-surface)/90 backdrop-blur-xl">
            <button
              class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-medium
                     border border-(--t-border)/50 text-(--t-text-3)
                     hover:bg-(--t-card-hover) active:scale-95 transition-all"
              @click="showMetaModal = false" @mousedown="ripple"
            >Отмена</button>
            <button
              class="relative overflow-hidden flex-1 py-2.5 rounded-xl text-xs font-semibold
                     bg-(--t-primary) text-white hover:brightness-110 active:scale-95 transition-all"
              @click="submitMeta" @mousedown="ripple"
            >💾 Сохранить</button>
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
/* Ripple — unique suffix se (SEO) */
@keyframes ripple-se {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* line-clamp */
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

/* Sidebar transition */
.sb-se-enter-active,
.sb-se-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.sb-se-enter-from,
.sb-se-leave-to {
  opacity: 0;
  transform: translateX(12px);
}

/* Drawer transitions */
.dw-se-enter-active,
.dw-se-leave-active,
.detail-se-enter-active,
.detail-se-leave-active {
  transition: opacity 0.3s ease;
}
.dw-se-enter-active > :last-child,
.dw-se-leave-active > :last-child,
.detail-se-enter-active > :last-child,
.detail-se-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.dw-se-enter-from,
.dw-se-leave-to,
.detail-se-enter-from,
.detail-se-leave-to {
  opacity: 0;
}
.dw-se-enter-from > :last-child,
.dw-se-leave-to > :last-child,
.detail-se-enter-from > :last-child,
.detail-se-leave-to > :last-child {
  transform: translateX(100%);
}

/* Modal */
.modal-se-enter-active,
.modal-se-leave-active {
  transition: opacity 0.25s ease;
}
.modal-se-enter-active > :nth-child(2),
.modal-se-leave-active > :nth-child(2) {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}
.modal-se-enter-from,
.modal-se-leave-to {
  opacity: 0;
}
.modal-se-enter-from > :nth-child(2),
.modal-se-leave-to > :nth-child(2) {
  transform: scale(0.95) translateY(8px);
  opacity: 0;
}

/* Fade (export menu) */
.fade-se-enter-active,
.fade-se-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.fade-se-enter-from,
.fade-se-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
