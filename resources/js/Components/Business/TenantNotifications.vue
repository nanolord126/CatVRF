<script setup lang="ts">
/**
 * TenantNotifications.vue — Центр уведомлений B2B Tenant Dashboard
 *
 * Вертикали:
 *   Beauty · Taxi · Food · Hotels · RealEstate · Flowers
 *   Fashion · Furniture · Fitness · Travel · default
 *
 * ───────────────────────────────────────────────────────────────
 *  Функционал:
 *   1.  Лента уведомлений: заказы, бронирования, платежи, отзывы,
 *       остатки, напоминания, фрод, доставка, AI-конструктор
 *   2.  Фильтры: все / непрочитанные / по вертикали / по типу
 *   3.  Пакетные действия: отметить всё прочитанным, очистить
 *   4.  Sidebar: быстрые категории + счётчики + настройки каналов
 *   5.  Карточки: иконка · заголовок · текст · время · действие
 *   6.  Detail-drawer для одного уведомления (мобильный + десктоп)
 *   7.  Звуковые и Push-настройки для каждой категории
 *   8.  Full-screen · keyboard (Esc, J/K навигация) · ripple-nt
 *   9.  Mobile-first drawer sidebar · адаптивный grid
 *  10.  Glassmorphism · dark theme · 2026 design
 * ───────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useAuth, useTenant } from '@/stores'

/* ━━━━━━━━━━━━  TYPES  ━━━━━━━━━━━━ */

type NotificationType =
  | 'order'        // новый заказ
  | 'booking'      // новое бронирование
  | 'payment'      // платёж / возврат
  | 'review'       // новый отзыв
  | 'stock'        // низкий остаток
  | 'delivery'     // доставка: статус
  | 'reminder'     // напоминание
  | 'fraud'        // фрод-алерт
  | 'ai'           // AI-конструктор
  | 'marketing'    // рассылки / кампании
  | 'system'       // системное

type PriorityLevel = 'critical' | 'high' | 'normal' | 'low'
type FilterMode    = 'all' | 'unread' | 'starred'
type SortKey       = 'newest' | 'priority' | 'type'

interface NotificationAction {
  label:   string
  icon:    string
  variant: 'primary' | 'secondary' | 'danger'
  href?:   string
}

interface NotificationItem {
  id:          number | string
  type:        NotificationType
  priority:    PriorityLevel
  vertical:    string
  title:       string
  body:        string
  icon:        string
  createdAt:   string
  isRead:      boolean
  isStarred:   boolean
  actions:     NotificationAction[]
  metadata?:   Record<string, string | number>
  groupId?:    string
}

interface CategoryStat {
  type:    NotificationType
  label:   string
  icon:    string
  count:   number
  unread:  number
}

interface ChannelSetting {
  type:     NotificationType
  label:    string
  inApp:    boolean
  push:     boolean
  email:    boolean
  telegram: boolean
  sound:    boolean
}

interface VerticalNotifConfig {
  label:       string
  icon:        string
  accentColor: string
  types:       NotificationType[]
}

/* ━━━━━━━━━━━━  PROPS / EMITS  ━━━━━━━━━━━━ */

const props = withDefaults(defineProps<{
  vertical?:       string
  notifications?:  NotificationItem[]
  categories?:     CategoryStat[]
  channels?:       ChannelSetting[]
  loading?:        boolean
  hasMore?:        boolean
  totalUnread?:    number
}>(), {
  vertical:       'default',
  notifications:  () => [],
  categories:     () => [],
  channels:       () => [],
  loading:        false,
  hasMore:        false,
  totalUnread:    0,
})

const emit = defineEmits<{
  'mark-read':      [id: number | string]
  'mark-all-read':  []
  'toggle-star':    [id: number | string]
  'delete-one':     [id: number | string]
  'delete-all':     []
  'action-click':   [notifId: number | string, action: NotificationAction]
  'filter-change':  [filter: FilterMode]
  'type-filter':    [type: NotificationType | null]
  'sort-change':    [sort: SortKey]
  'load-more':      []
  'channel-update': [setting: ChannelSetting]
  'refresh':        []
  'toggle-fullscreen': []
}>()

const auth = useAuth()
const biz  = useTenant()

/* ━━━━━━━━━━━━  VERTICAL CONFIG  ━━━━━━━━━━━━ */

const ALL_TYPES: NotificationType[] = [
  'order', 'booking', 'payment', 'review', 'stock',
  'delivery', 'reminder', 'fraud', 'ai', 'marketing', 'system',
]

const VERTICAL_CFG: Record<string, VerticalNotifConfig> = {
  beauty:     { label: 'Салон красоты',   icon: '💄', accentColor: 'pink',    types: ALL_TYPES },
  taxi:       { label: 'Такси',           icon: '🚕', accentColor: 'yellow',  types: ALL_TYPES },
  food:       { label: 'Еда и рестораны', icon: '🍽️', accentColor: 'orange',  types: ALL_TYPES },
  hotel:      { label: 'Отели',           icon: '🏨', accentColor: 'sky',     types: ALL_TYPES },
  realEstate: { label: 'Недвижимость',    icon: '🏢', accentColor: 'emerald', types: ALL_TYPES },
  flowers:    { label: 'Цветы',           icon: '💐', accentColor: 'rose',    types: ALL_TYPES },
  fashion:    { label: 'Мода и одежда',   icon: '👗', accentColor: 'violet',  types: ALL_TYPES },
  furniture:  { label: 'Мебель',          icon: '🛋️', accentColor: 'amber',   types: ALL_TYPES },
  fitness:    { label: 'Фитнес',          icon: '💪', accentColor: 'lime',    types: ALL_TYPES },
  travel:     { label: 'Путешествия',     icon: '✈️', accentColor: 'cyan',    types: ALL_TYPES },
  default:    { label: 'Бизнес',          icon: '📊', accentColor: 'indigo',  types: ALL_TYPES },
}

const vc = computed<VerticalNotifConfig>(() =>
  VERTICAL_CFG[props.vertical] ?? VERTICAL_CFG.default,
)

/* ━━━━━━━━━━━━  CONSTANTS  ━━━━━━━━━━━━ */

const TYPE_META: Record<NotificationType, { label: string; icon: string; cls: string }> = {
  order:     { label: 'Заказы',       icon: '🛒', cls: 'bg-sky-500/12 text-sky-400' },
  booking:   { label: 'Бронирования', icon: '📅', cls: 'bg-violet-500/12 text-violet-400' },
  payment:   { label: 'Платежи',      icon: '💳', cls: 'bg-emerald-500/12 text-emerald-400' },
  review:    { label: 'Отзывы',       icon: '⭐', cls: 'bg-amber-500/12 text-amber-400' },
  stock:     { label: 'Остатки',      icon: '📦', cls: 'bg-orange-500/12 text-orange-400' },
  delivery:  { label: 'Доставка',     icon: '🚚', cls: 'bg-teal-500/12 text-teal-400' },
  reminder:  { label: 'Напоминания',  icon: '⏰', cls: 'bg-indigo-500/12 text-indigo-400' },
  fraud:     { label: 'Безопасность', icon: '🛡️', cls: 'bg-rose-500/12 text-rose-400' },
  ai:        { label: 'AI',           icon: '🤖', cls: 'bg-purple-500/12 text-purple-400' },
  marketing: { label: 'Маркетинг',    icon: '📣', cls: 'bg-pink-500/12 text-pink-400' },
  system:    { label: 'Система',      icon: '⚙️', cls: 'bg-zinc-500/12 text-zinc-400' },
}

const PRIORITY_META: Record<PriorityLevel, { label: string; dot: string; ring: string }> = {
  critical: { label: 'Критичный', dot: 'bg-rose-500',    ring: 'ring-rose-500/30' },
  high:     { label: 'Высокий',   dot: 'bg-amber-500',   ring: 'ring-amber-500/30' },
  normal:   { label: 'Обычный',   dot: 'bg-sky-500',     ring: 'ring-sky-500/30' },
  low:      { label: 'Низкий',    dot: 'bg-zinc-500',    ring: 'ring-zinc-500/30' },
}

const FILTER_TABS: Array<{ key: FilterMode; label: string; icon: string }> = [
  { key: 'all',     label: 'Все',            icon: '📋' },
  { key: 'unread',  label: 'Непрочитанные',  icon: '🔵' },
  { key: 'starred', label: 'Избранные',      icon: '⭐' },
]

const SORT_OPTIONS: Array<{ key: SortKey; label: string }> = [
  { key: 'newest',   label: 'Новые' },
  { key: 'priority', label: 'Приоритет' },
  { key: 'type',     label: 'По типу' },
]

const PRIORITY_ORDER: Record<PriorityLevel, number> = {
  critical: 0,
  high:     1,
  normal:   2,
  low:      3,
}

/* ━━━━━━━━━━━━  STATE  ━━━━━━━━━━━━ */

const rootEl              = ref<HTMLElement | null>(null)
const isFullscreen        = ref(false)
const activeFilter        = ref<FilterMode>('all')
const activeTypeFilter    = ref<NotificationType | null>(null)
const activeSort          = ref<SortKey>('newest')
const showSidebar         = ref(true)
const showMobileSidebar   = ref(false)
const showDetail          = ref<NotificationItem | null>(null)
const showSettings        = ref(false)
const showDeleteConfirm   = ref(false)
const searchQuery         = ref('')
const focusedIdx          = ref(-1)
const loadingMore         = ref(false)
const refreshing          = ref(false)
const markingAllRead      = ref(false)

/* ━━━━━━━━━━━━  COMPUTED  ━━━━━━━━━━━━ */

const filteredNotifications = computed<NotificationItem[]>(() => {
  let list = [...props.notifications]

  /* filter mode */
  if (activeFilter.value === 'unread') {
    list = list.filter((n) => !n.isRead)
  } else if (activeFilter.value === 'starred') {
    list = list.filter((n) => n.isStarred)
  }

  /* type filter */
  if (activeTypeFilter.value) {
    list = list.filter((n) => n.type === activeTypeFilter.value)
  }

  /* search */
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase()
    list = list.filter(
      (n) => n.title.toLowerCase().includes(q) || n.body.toLowerCase().includes(q),
    )
  }

  /* sort */
  if (activeSort.value === 'priority') {
    list.sort((a, b) => PRIORITY_ORDER[a.priority] - PRIORITY_ORDER[b.priority])
  } else if (activeSort.value === 'type') {
    list.sort((a, b) => a.type.localeCompare(b.type))
  }
  /* newest — default API order */

  return list
})

const unreadCount = computed(() =>
  props.notifications.filter((n) => !n.isRead).length,
)

const starredCount = computed(() =>
  props.notifications.filter((n) => n.isStarred).length,
)

const categoryStats = computed<CategoryStat[]>(() => {
  if (props.categories.length > 0) return props.categories
  /* fallback: build from notifications */
  const map = new Map<NotificationType, { count: number; unread: number }>()
  for (const n of props.notifications) {
    const entry = map.get(n.type) ?? { count: 0, unread: 0 }
    entry.count++
    if (!n.isRead) entry.unread++
    map.set(n.type, entry)
  }
  return Array.from(map.entries()).map(([type, v]) => ({
    type,
    label:  TYPE_META[type].label,
    icon:   TYPE_META[type].icon,
    count:  v.count,
    unread: v.unread,
  }))
})

const criticalCount = computed(() =>
  props.notifications.filter((n) => n.priority === 'critical' && !n.isRead).length,
)

/* ━━━━━━━━━━━━  HELPERS  ━━━━━━━━━━━━ */

function fmtTime(d: string): string {
  if (!d) return '—'
  const dt  = new Date(d)
  const now = new Date()
  const ms  = now.getTime() - dt.getTime()
  const mins  = Math.floor(ms / 60_000)
  const hours = Math.floor(ms / 3_600_000)
  const days  = Math.floor(ms / 86_400_000)

  if (mins < 1)   return 'Сейчас'
  if (mins < 60)  return `${mins} мин`
  if (hours < 24) return `${hours} ч`
  if (days < 7)   return `${days} дн`
  return dt.toLocaleDateString('ru-RU', { day: '2-digit', month: 'short' })
}

function fmtNum(n: number): string {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M`
  if (n >= 1_000)     return `${(n / 1_000).toFixed(1)}K`
  return String(n)
}

function groupByDate(items: NotificationItem[]): Array<{ label: string; items: NotificationItem[] }> {
  const groups: Array<{ label: string; items: NotificationItem[] }> = []
  const bucketMap = new Map<string, NotificationItem[]>()

  for (const item of items) {
    const dt  = new Date(item.createdAt)
    const now = new Date()
    const diffDays = Math.floor((now.getTime() - dt.getTime()) / 86_400_000)

    let label: string
    if (diffDays === 0)      label = 'Сегодня'
    else if (diffDays === 1) label = 'Вчера'
    else if (diffDays < 7)   label = 'На этой неделе'
    else if (diffDays < 30)  label = 'В этом месяце'
    else                     label = 'Ранее'

    const bucket = bucketMap.get(label) ?? []
    bucket.push(item)
    bucketMap.set(label, bucket)
  }

  const ORDER = ['Сегодня', 'Вчера', 'На этой неделе', 'В этом месяце', 'Ранее']
  for (const label of ORDER) {
    const items = bucketMap.get(label)
    if (items && items.length > 0) {
      groups.push({ label, items })
    }
  }
  return groups
}

const groupedNotifications = computed(() => groupByDate(filteredNotifications.value))

/* ━━━━━━━━━━━━  ACTIONS  ━━━━━━━━━━━━ */

function setFilter(f: FilterMode) {
  activeFilter.value = f
  focusedIdx.value = -1
  emit('filter-change', f)
}

function setTypeFilter(t: NotificationType | null) {
  activeTypeFilter.value = activeTypeFilter.value === t ? null : t
  focusedIdx.value = -1
  emit('type-filter', activeTypeFilter.value)
}

function setSort(s: SortKey) {
  activeSort.value = s
  emit('sort-change', s)
}

function markRead(n: NotificationItem) {
  if (!n.isRead) emit('mark-read', n.id)
}

function markAllRead() {
  markingAllRead.value = true
  emit('mark-all-read')
  setTimeout(() => { markingAllRead.value = false }, 1200)
}

function toggleStar(n: NotificationItem) {
  emit('toggle-star', n.id)
}

function deleteOne(n: NotificationItem) {
  emit('delete-one', n.id)
  if (showDetail.value?.id === n.id) showDetail.value = null
}

function handleAction(n: NotificationItem, action: NotificationAction) {
  markRead(n)
  emit('action-click', n.id, action)
}

function openDetail(n: NotificationItem) {
  markRead(n)
  showDetail.value = n
}

function closeDetail() {
  showDetail.value = null
}

function loadMore() {
  if (loadingMore.value || !props.hasMore) return
  loadingMore.value = true
  emit('load-more')
  setTimeout(() => { loadingMore.value = false }, 2000)
}

function doRefresh() {
  refreshing.value = true
  emit('refresh')
  setTimeout(() => { refreshing.value = false }, 1200)
}

function confirmDeleteAll() {
  showDeleteConfirm.value = true
}

function executeDeleteAll() {
  emit('delete-all')
  showDeleteConfirm.value = false
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
  const inInput = e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement

  if (e.key === 'Escape') {
    if (showDeleteConfirm.value)  { showDeleteConfirm.value = false; return }
    if (showSettings.value)       { showSettings.value = false; return }
    if (showDetail.value)         { closeDetail(); return }
    if (showMobileSidebar.value)  { showMobileSidebar.value = false; return }
    if (isFullscreen.value)       { toggleFullscreen(); return }
  }

  if (inInput) return

  if (e.key === 'j' || e.key === 'ArrowDown') {
    e.preventDefault()
    if (focusedIdx.value < filteredNotifications.value.length - 1) focusedIdx.value++
  }
  if (e.key === 'k' || e.key === 'ArrowUp') {
    e.preventDefault()
    if (focusedIdx.value > 0) focusedIdx.value--
  }
  if (e.key === 'Enter' && focusedIdx.value >= 0) {
    const n = filteredNotifications.value[focusedIdx.value]
    if (n) openDetail(n)
  }
  if (e.key === 'r' && !e.ctrlKey && !e.metaKey) {
    doRefresh()
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
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-nt_0.6s_ease-out]'
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
          <span class="text-2xl">🔔</span>
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <h1 class="text-base sm:text-lg font-extrabold text-(--t-text) truncate leading-snug">
                Уведомления
              </h1>
              <span v-if="unreadCount > 0"
                    class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold
                           bg-(--t-primary) text-white tabular-nums">
                {{ fmtNum(unreadCount) }}
              </span>
              <span v-if="criticalCount > 0"
                    class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold
                           bg-rose-500 text-white tabular-nums animate-pulse">
                {{ criticalCount }} 🔴
              </span>
            </div>
            <p class="text-[10px] text-(--t-text-3) truncate">
              {{ vc.icon }} {{ vc.label }} · {{ props.notifications.length }} всего
            </p>
          </div>
        </div>

        <!-- Actions row -->
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
                     inline-size-36 sm:inline-size-44"
            />
            <span class="absolute inset-inline-start-2.5 inset-block-start-1/2 -translate-y-1/2
                         text-xs text-(--t-text-3) pointer-events-none">🔍</span>
          </div>

          <!-- Sort -->
          <div class="flex items-center rounded-xl border border-(--t-border)/50 overflow-hidden">
            <button
              v-for="s in SORT_OPTIONS" :key="s.key"
              :class="[
                'relative overflow-hidden px-2.5 py-1.5 text-[10px] sm:text-xs font-medium transition-all',
                activeSort === s.key
                  ? 'bg-(--t-primary) text-white'
                  : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
              ]"
              @click="setSort(s.key)" @mousedown="ripple"
            >{{ s.label }}</button>
          </div>

          <!-- Mark all read -->
          <button
            :class="[
              'relative overflow-hidden flex items-center gap-1.5 px-3 py-1.5 rounded-xl',
              'text-xs font-semibold transition-all active:scale-95',
              unreadCount > 0
                ? 'bg-(--t-primary) text-white hover:brightness-110'
                : 'bg-zinc-700/50 text-zinc-500 cursor-not-allowed',
            ]"
            :disabled="unreadCount === 0 || markingAllRead"
            @click="markAllRead" @mousedown="ripple"
          >
            {{ markingAllRead ? '⏳' : '✓' }}
            <span class="hidden sm:inline">Прочитать все</span>
          </button>

          <!-- Refresh -->
          <button
            :class="[
              'relative overflow-hidden w-9 h-9 rounded-xl border border-(--t-border)/50',
              'flex items-center justify-center text-(--t-text-3)',
              'hover:bg-(--t-card-hover) active:scale-95 transition-all',
              refreshing ? 'animate-spin' : '',
            ]"
            @click="doRefresh" @mousedown="ripple" title="Обновить (R)"
          >🔄</button>

          <!-- Settings -->
          <button
            class="relative overflow-hidden w-9 h-9 rounded-xl border border-(--t-border)/50
                   flex items-center justify-center text-(--t-text-3)
                   hover:bg-(--t-card-hover) active:scale-95 transition-all"
            @click="showSettings = true" @mousedown="ripple" title="Настройки каналов"
          >⚙️</button>

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

      <!-- Filter tabs -->
      <div class="px-4 sm:px-6 pb-2 flex gap-1.5 overflow-x-auto no-scrollbar">
        <button
          v-for="f in FILTER_TABS" :key="f.key"
          :class="[
            'relative overflow-hidden shrink-0 flex items-center gap-1.5 px-3 py-1.5',
            'rounded-xl text-[10px] sm:text-xs font-medium transition-all',
            activeFilter === f.key
              ? 'bg-(--t-primary)/12 text-(--t-primary) border border-(--t-primary)/25'
              : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover) border border-transparent',
          ]"
          @click="setFilter(f.key)" @mousedown="ripple"
        >
          <span class="text-xs">{{ f.icon }}</span>
          {{ f.label }}
          <span class="text-[9px] opacity-60 tabular-nums">
            {{ f.key === 'all' ? props.notifications.length
             : f.key === 'unread' ? unreadCount
             : starredCount }}
          </span>
        </button>

        <!-- divider -->
        <span class="shrink-0 inline-size-px self-stretch bg-(--t-border)/30 mx-1" />

        <!-- Type chips -->
        <button
          v-for="tm in categoryStats" :key="tm.type"
          :class="[
            'relative overflow-hidden shrink-0 flex items-center gap-1 px-2.5 py-1.5',
            'rounded-xl text-[10px] font-medium transition-all',
            activeTypeFilter === tm.type
              ? TYPE_META[tm.type].cls + ' border border-current/20'
              : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover) border border-transparent',
          ]"
          @click="setTypeFilter(tm.type)" @mousedown="ripple"
        >
          <span class="text-[10px]">{{ tm.icon }}</span>
          <span class="hidden sm:inline">{{ tm.label }}</span>
          <span v-if="tm.unread > 0"
                class="shrink-0 w-4 h-4 rounded-full bg-current/15 flex items-center justify-center
                       text-[8px] font-bold tabular-nums">
            {{ tm.unread }}
          </span>
        </button>
      </div>
    </header>

    <!-- ══════════════════════════════════════
         MAIN: FEED + SIDEBAR
    ══════════════════════════════════════ -->
    <div class="flex-1 flex gap-5 px-4 sm:px-6 py-5 max-w-screen-2xl mx-auto inline-size-full">

      <!-- ═══ NOTIFICATION FEED ═══ -->
      <div class="flex-1 flex flex-col gap-1 min-w-0">

        <!-- Loading skeleton -->
        <div v-if="props.loading && filteredNotifications.length === 0"
             class="flex flex-col gap-2">
          <div v-for="n in 5" :key="n"
               class="flex items-start gap-3 p-4 rounded-2xl border border-(--t-border)/20
                      bg-(--t-surface)/30 animate-pulse">
            <div class="shrink-0 w-10 h-10 rounded-xl bg-(--t-border)/30" />
            <div class="flex-1">
              <div class="h-3 w-44 bg-(--t-border)/30 rounded mb-2" />
              <div class="h-2.5 w-full bg-(--t-border)/20 rounded mb-1.5" />
              <div class="h-2.5 w-2/3 bg-(--t-border)/20 rounded" />
            </div>
            <div class="shrink-0 h-2 w-10 bg-(--t-border)/20 rounded" />
          </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="filteredNotifications.length === 0 && !props.loading"
             class="py-20 text-center">
          <p class="text-5xl mb-3">🔕</p>
          <p class="text-sm font-semibold text-(--t-text-2)">Нет уведомлений</p>
          <p class="text-[10px] text-(--t-text-3) mt-1">
            {{ activeFilter === 'unread' ? 'Все уведомления прочитаны!' : 'Здесь будут появляться новые события' }}
          </p>
        </div>

        <!-- Grouped notifications -->
        <template v-for="group in groupedNotifications" :key="group.label">
          <!-- Date group header -->
          <div class="sticky inset-block-start-28 sm:inset-block-start-32 z-10
                      flex items-center gap-2 py-2">
            <span class="shrink-0 text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider">
              {{ group.label }}
            </span>
            <span class="flex-1 block-size-px bg-(--t-border)/25" />
            <span class="shrink-0 text-[9px] text-(--t-text-3) tabular-nums">
              {{ group.items.length }}
            </span>
          </div>

          <!-- Notification cards -->
          <button
            v-for="(notif, ni) in group.items" :key="notif.id"
            :class="[
              'group/card relative overflow-hidden flex items-start gap-3 px-4 py-3.5',
              'rounded-2xl border transition-all text-start',
              'hover:shadow-lg hover:shadow-black/5 active:scale-[0.995]',
              !notif.isRead
                ? 'bg-(--t-surface)/70 border-(--t-border)/50 hover:border-(--t-border)/80'
                : 'bg-(--t-surface)/30 border-(--t-border)/20 hover:border-(--t-border)/40',
              notif.priority === 'critical' && !notif.isRead
                ? 'ring-1 ' + PRIORITY_META.critical.ring
                : '',
              focusedIdx === props.notifications.indexOf(notif)
                ? 'ring-2 ring-(--t-primary)/40'
                : '',
            ]"
            @click="openDetail(notif)" @mousedown="ripple"
          >
            <!-- Unread dot -->
            <span v-if="!notif.isRead"
                  :class="[
                    'absolute inset-block-start-2 inset-inline-start-2 w-2 h-2 rounded-full',
                    PRIORITY_META[notif.priority].dot,
                  ]" />

            <!-- Icon -->
            <div :class="[
              'shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-base',
              TYPE_META[notif.type].cls,
            ]">
              {{ notif.icon || TYPE_META[notif.type].icon }}
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-0.5">
                <span :class="[
                  'text-xs font-bold truncate',
                  notif.isRead ? 'text-(--t-text-2)' : 'text-(--t-text)',
                ]">{{ notif.title }}</span>
                <span :class="['shrink-0 px-1.5 py-px rounded-md text-[8px] font-medium', TYPE_META[notif.type].cls]">
                  {{ TYPE_META[notif.type].label }}
                </span>
              </div>

              <p :class="[
                'text-[11px] leading-relaxed line-clamp-2',
                notif.isRead ? 'text-(--t-text-3)' : 'text-(--t-text-2)',
              ]">{{ notif.body }}</p>

              <!-- Quick actions (visible on hover) -->
              <div v-if="notif.actions.length > 0"
                   class="flex gap-1.5 mt-2 opacity-0 group-hover/card:opacity-100 transition-opacity">
                <button
                  v-for="(act, ai) in notif.actions.slice(0, 2)" :key="ai"
                  :class="[
                    'relative overflow-hidden px-2.5 py-1 rounded-lg text-[10px] font-medium transition-all active:scale-95',
                    act.variant === 'primary'
                      ? 'bg-(--t-primary)/15 text-(--t-primary) hover:bg-(--t-primary)/25'
                      : act.variant === 'danger'
                        ? 'bg-rose-500/12 text-rose-400 hover:bg-rose-500/20'
                        : 'bg-(--t-card-hover) text-(--t-text-3) hover:text-(--t-text)',
                  ]"
                  @click.stop="handleAction(notif, act)" @mousedown="ripple"
                >{{ act.icon }} {{ act.label }}</button>
              </div>
            </div>

            <!-- Right side: time + star + menu -->
            <div class="shrink-0 flex flex-col items-end gap-1">
              <span class="text-[10px] text-(--t-text-3) tabular-nums whitespace-nowrap">
                {{ fmtTime(notif.createdAt) }}
              </span>

              <!-- Star -->
              <button
                :class="[
                  'w-6 h-6 rounded-lg flex items-center justify-center text-xs transition-all',
                  'opacity-0 group-hover/card:opacity-100',
                  notif.isStarred ? 'opacity-100! text-amber-400' : 'text-(--t-text-3) hover:text-amber-400',
                ]"
                @click.stop="toggleStar(notif)"
                title="В избранное"
              >{{ notif.isStarred ? '★' : '☆' }}</button>

              <!-- Delete -->
              <button
                class="w-6 h-6 rounded-lg flex items-center justify-center text-xs
                       text-(--t-text-3) hover:text-rose-400 hover:bg-rose-500/10
                       opacity-0 group-hover/card:opacity-100 transition-all"
                @click.stop="deleteOne(notif)"
                title="Удалить"
              >🗑️</button>
            </div>
          </button>
        </template>

        <!-- Load more -->
        <div v-if="props.hasMore && filteredNotifications.length > 0" class="flex justify-center py-4">
          <button
            :class="[
              'relative overflow-hidden px-6 py-2.5 rounded-xl text-xs font-semibold transition-all',
              'border border-(--t-border)/50 text-(--t-text-3) hover:text-(--t-text)',
              'hover:bg-(--t-card-hover) active:scale-95',
              loadingMore ? 'animate-pulse pointer-events-none' : '',
            ]"
            @click="loadMore" @mousedown="ripple"
          >{{ loadingMore ? '⏳ Загрузка…' : '↓ Показать ещё' }}</button>
        </div>
      </div>

      <!-- ═══ SIDEBAR (desktop) ═══ -->
      <Transition name="sb-nt">
        <aside v-if="showSidebar"
               class="hidden xl:flex shrink-0 flex-col gap-4 w-72">

          <!-- Summary card -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              Сводка
            </h3>
            <div class="grid grid-cols-2 gap-2.5">
              <div class="rounded-xl bg-(--t-bg)/50 p-3">
                <p class="text-[10px] text-(--t-text-3)">Всего</p>
                <p class="text-base font-extrabold text-(--t-text) tabular-nums">
                  {{ fmtNum(props.notifications.length) }}
                </p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-3">
                <p class="text-[10px] text-(--t-text-3)">Непрочитано</p>
                <p class="text-base font-extrabold text-sky-400 tabular-nums">
                  {{ fmtNum(unreadCount) }}
                </p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-3">
                <p class="text-[10px] text-(--t-text-3)">Критичных</p>
                <p :class="[
                  'text-base font-extrabold tabular-nums',
                  criticalCount > 0 ? 'text-rose-400' : 'text-(--t-text)',
                ]">
                  {{ criticalCount }}
                </p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-3">
                <p class="text-[10px] text-(--t-text-3)">Избранных</p>
                <p class="text-base font-extrabold text-amber-400 tabular-nums">
                  {{ starredCount }}
                </p>
              </div>
            </div>
          </div>

          <!-- Categories -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-3">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-2 px-1">
              Категории
            </h3>
            <div class="flex flex-col gap-0.5">
              <button
                v-for="cat in categoryStats" :key="cat.type"
                :class="[
                  'relative overflow-hidden flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all',
                  activeTypeFilter === cat.type
                    ? 'bg-(--t-primary)/10 text-(--t-primary)'
                    : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
                ]"
                @click="setTypeFilter(cat.type)" @mousedown="ripple"
              >
                <span class="shrink-0 text-sm">{{ cat.icon }}</span>
                <span class="flex-1 text-start text-xs truncate">{{ cat.label }}</span>
                <span class="shrink-0 flex items-center gap-1.5">
                  <span class="text-[10px] tabular-nums opacity-60">{{ cat.count }}</span>
                  <span v-if="cat.unread > 0"
                        class="w-5 h-5 rounded-full bg-(--t-primary)/15 flex items-center justify-center
                               text-[9px] font-bold text-(--t-primary) tabular-nums">
                    {{ cat.unread }}
                  </span>
                </span>
              </button>
            </div>
          </div>

          <!-- Priority breakdown -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              По приоритету
            </h3>
            <div class="flex flex-col gap-2">
              <div v-for="(meta, pkey) in PRIORITY_META" :key="pkey"
                   class="flex items-center gap-2.5">
                <span :class="['shrink-0 w-2.5 h-2.5 rounded-full', meta.dot]" />
                <span class="flex-1 text-xs text-(--t-text-2)">{{ meta.label }}</span>
                <span class="shrink-0 text-[10px] text-(--t-text-3) tabular-nums">
                  {{ props.notifications.filter((n) => n.priority === pkey).length }}
                </span>
              </div>
            </div>
          </div>

          <!-- Bulk actions -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-3">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-2 px-1">
              Действия
            </h3>
            <div class="flex flex-col gap-0.5">
              <button
                class="relative overflow-hidden flex items-center gap-2.5 px-3 py-2 rounded-xl
                       text-xs text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)
                       active:scale-[0.97] transition-all"
                :disabled="unreadCount === 0"
                @click="markAllRead" @mousedown="ripple"
              >
                <span class="text-sm">✓</span>
                <span>Прочитать все</span>
              </button>
              <button
                class="relative overflow-hidden flex items-center gap-2.5 px-3 py-2 rounded-xl
                       text-xs text-(--t-text-3) hover:text-rose-400 hover:bg-rose-500/8
                       active:scale-[0.97] transition-all"
                @click="confirmDeleteAll" @mousedown="ripple"
              >
                <span class="text-sm">🗑️</span>
                <span>Удалить все</span>
              </button>
              <button
                class="relative overflow-hidden flex items-center gap-2.5 px-3 py-2 rounded-xl
                       text-xs text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)
                       active:scale-[0.97] transition-all"
                @click="showSettings = true" @mousedown="ripple"
              >
                <span class="text-sm">⚙️</span>
                <span>Настройки каналов</span>
              </button>
            </div>
          </div>

          <!-- Keyboard hints -->
          <div class="rounded-2xl border border-(--t-border)/20 bg-(--t-surface)/30 p-3">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-2 px-1">
              Горячие клавиши
            </h3>
            <div class="flex flex-col gap-1 text-[10px] text-(--t-text-3)">
              <div class="flex items-center justify-between px-1">
                <span>Навигация</span>
                <div class="flex gap-1">
                  <kbd class="px-1.5 py-0.5 rounded bg-(--t-bg)/70 text-[9px] font-mono">J</kbd>
                  <kbd class="px-1.5 py-0.5 rounded bg-(--t-bg)/70 text-[9px] font-mono">K</kbd>
                </div>
              </div>
              <div class="flex items-center justify-between px-1">
                <span>Открыть</span>
                <kbd class="px-1.5 py-0.5 rounded bg-(--t-bg)/70 text-[9px] font-mono">Enter</kbd>
              </div>
              <div class="flex items-center justify-between px-1">
                <span>Обновить</span>
                <kbd class="px-1.5 py-0.5 rounded bg-(--t-bg)/70 text-[9px] font-mono">R</kbd>
              </div>
              <div class="flex items-center justify-between px-1">
                <span>Закрыть</span>
                <kbd class="px-1.5 py-0.5 rounded bg-(--t-bg)/70 text-[9px] font-mono">Esc</kbd>
              </div>
            </div>
          </div>
        </aside>
      </Transition>
    </div>

    <!-- ══════════════════════════════════════
         MOBILE SIDEBAR DRAWER
    ══════════════════════════════════════ -->
    <Transition name="dw-nt">
      <div v-if="showMobileSidebar"
           class="fixed inset-0 z-50 flex" @click.self="showMobileSidebar = false">
        <div class="absolute inset-0 bg-black/40" @click="showMobileSidebar = false" />

        <div class="relative z-10 ms-auto inline-size-72 max-w-[85vw] h-full bg-(--t-surface)
                    border-s border-(--t-border) overflow-y-auto p-4 flex flex-col gap-4">

          <div class="flex items-center justify-between mb-1">
            <h3 class="text-sm font-bold text-(--t-text)">🔔 Уведомления</h3>
            <button class="text-(--t-text-3) hover:text-(--t-text) transition-colors"
                    @click="showMobileSidebar = false">✕</button>
          </div>

          <!-- Mobile summary -->
          <div class="grid grid-cols-2 gap-2">
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Непрочитано</p>
              <p class="text-sm font-bold text-sky-400 tabular-nums">{{ unreadCount }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Критичных</p>
              <p :class="['text-sm font-bold tabular-nums', criticalCount > 0 ? 'text-rose-400' : 'text-(--t-text)']">
                {{ criticalCount }}
              </p>
            </div>
          </div>

          <!-- Mobile categories -->
          <div>
            <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase mb-1.5">Категории</h4>
            <div class="flex flex-col gap-0.5">
              <button
                v-for="cat in categoryStats" :key="cat.type"
                :class="[
                  'relative overflow-hidden flex items-center gap-2 px-3 py-2.5 rounded-xl transition-all',
                  activeTypeFilter === cat.type
                    ? 'bg-(--t-primary)/10 text-(--t-primary)'
                    : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
                ]"
                @click="setTypeFilter(cat.type); showMobileSidebar = false" @mousedown="ripple"
              >
                <span class="text-sm">{{ cat.icon }}</span>
                <span class="flex-1 text-start text-xs">{{ cat.label }}</span>
                <span v-if="cat.unread > 0"
                      class="w-5 h-5 rounded-full bg-(--t-primary)/15 flex items-center justify-center
                             text-[9px] font-bold text-(--t-primary) tabular-nums">{{ cat.unread }}</span>
              </button>
            </div>
          </div>

          <!-- Mobile bulk actions -->
          <div class="flex flex-col gap-1 mt-auto">
            <button
              class="relative overflow-hidden flex items-center gap-2.5 px-3 py-2.5 rounded-xl
                     text-xs text-(--t-text-3) hover:bg-(--t-card-hover) transition-all"
              @click="markAllRead; showMobileSidebar = false" @mousedown="ripple"
            >✓ Прочитать все</button>
            <button
              class="relative overflow-hidden flex items-center gap-2.5 px-3 py-2.5 rounded-xl
                     text-xs text-(--t-text-3) hover:text-rose-400 hover:bg-rose-500/8 transition-all"
              @click="confirmDeleteAll; showMobileSidebar = false" @mousedown="ripple"
            >🗑️ Удалить все</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════
         NOTIFICATION DETAIL DRAWER
    ══════════════════════════════════════ -->
    <Transition name="detail-nt">
      <div v-if="showDetail"
           class="fixed inset-0 z-50 flex" @click.self="closeDetail">
        <div class="absolute inset-0 bg-black/40" @click="closeDetail" />

        <div class="relative z-10 ms-auto inline-size-full sm:inline-size-96 max-w-full h-full
                    bg-(--t-surface) border-s border-(--t-border) overflow-y-auto flex flex-col">

          <!-- Detail header -->
          <div class="sticky inset-block-start-0 z-10 flex items-center gap-3 px-5 py-4
                      bg-(--t-surface)/90 backdrop-blur-xl border-b border-(--t-border)/30">
            <div :class="[
              'shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-base',
              TYPE_META[showDetail.type].cls,
            ]">
              {{ showDetail.icon || TYPE_META[showDetail.type].icon }}
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-bold text-(--t-text) truncate">{{ showDetail.title }}</h3>
              <div class="flex items-center gap-2 mt-0.5">
                <span :class="['px-1.5 py-px rounded-md text-[8px] font-medium', TYPE_META[showDetail.type].cls]">
                  {{ TYPE_META[showDetail.type].label }}
                </span>
                <span class="text-[10px] text-(--t-text-3)">{{ fmtTime(showDetail.createdAt) }}</span>
                <span :class="['w-2 h-2 rounded-full', PRIORITY_META[showDetail.priority].dot]" />
              </div>
            </div>
            <button class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="closeDetail">✕</button>
          </div>

          <!-- Detail body -->
          <div class="flex-1 p-5 flex flex-col gap-5">

            <!-- Priority badge -->
            <div class="flex items-center gap-2">
              <span :class="['w-3 h-3 rounded-full', PRIORITY_META[showDetail.priority].dot]" />
              <span class="text-xs font-medium text-(--t-text-2)">
                Приоритет: {{ PRIORITY_META[showDetail.priority].label }}
              </span>
            </div>

            <!-- Full text -->
            <div class="rounded-xl bg-(--t-bg)/50 p-4">
              <p class="text-xs text-(--t-text-2) leading-relaxed whitespace-pre-line">
                {{ showDetail.body }}
              </p>
            </div>

            <!-- Metadata -->
            <div v-if="showDetail.metadata && Object.keys(showDetail.metadata).length > 0"
                 class="rounded-xl bg-(--t-bg)/50 p-4">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase mb-2">Данные</h4>
              <div class="flex flex-col gap-1.5">
                <div v-for="(val, key) in showDetail.metadata" :key="String(key)"
                     class="flex items-center justify-between">
                  <span class="text-[10px] text-(--t-text-3)">{{ key }}</span>
                  <span class="text-xs font-medium text-(--t-text) tabular-nums">{{ val }}</span>
                </div>
              </div>
            </div>

            <!-- Vertical & time details -->
            <div class="flex flex-wrap gap-2">
              <span class="px-2.5 py-1 rounded-lg text-[10px] font-medium
                           bg-(--t-card-hover) text-(--t-text-2)">
                {{ vc.icon }} {{ vc.label }}
              </span>
              <span class="px-2.5 py-1 rounded-lg text-[10px] font-medium
                           bg-(--t-card-hover) text-(--t-text-2)">
                🕐 {{ new Date(showDetail.createdAt).toLocaleString('ru-RU') }}
              </span>
              <span v-if="showDetail.isStarred"
                    class="px-2.5 py-1 rounded-lg text-[10px] font-medium
                           bg-amber-500/12 text-amber-400">
                ★ Избранное
              </span>
            </div>

            <!-- Actions -->
            <div v-if="showDetail.actions.length > 0"
                 class="flex flex-col gap-2">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase">Действия</h4>
              <button
                v-for="(act, ai) in showDetail.actions" :key="ai"
                :class="[
                  'relative overflow-hidden flex items-center justify-center gap-2 py-2.5 px-4',
                  'rounded-xl text-xs font-semibold transition-all active:scale-95',
                  act.variant === 'primary'
                    ? 'bg-(--t-primary) text-white hover:brightness-110'
                    : act.variant === 'danger'
                      ? 'bg-rose-500/12 text-rose-400 border border-rose-500/25 hover:bg-rose-500/20'
                      : 'bg-(--t-card-hover) text-(--t-text-2) border border-(--t-border)/40 hover:border-(--t-border)/60',
                ]"
                @click="handleAction(showDetail!, act)" @mousedown="ripple"
              >{{ act.icon }} {{ act.label }}</button>
            </div>
          </div>

          <!-- Detail footer -->
          <div class="sticky inset-block-end-0 flex items-center gap-2 px-5 py-3
                      border-t border-(--t-border)/30 bg-(--t-surface)/90 backdrop-blur-xl">
            <button
              class="relative overflow-hidden flex-1 flex items-center justify-center gap-1.5
                     py-2.5 rounded-xl text-xs font-medium border border-(--t-border)/40
                     text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)
                     active:scale-95 transition-all"
              @click="toggleStar(showDetail!)" @mousedown="ripple"
            >{{ showDetail.isStarred ? '★ Убрать из избранного' : '☆ В избранное' }}</button>
            <button
              class="relative overflow-hidden flex items-center justify-center gap-1.5
                     py-2.5 px-4 rounded-xl text-xs font-medium
                     border border-rose-500/25 text-rose-400
                     hover:bg-rose-500/10 active:scale-95 transition-all"
              @click="deleteOne(showDetail!)" @mousedown="ripple"
            >🗑️</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════
         CHANNEL SETTINGS MODAL
    ══════════════════════════════════════ -->
    <Transition name="modal-nt">
      <div v-if="showSettings"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="showSettings = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showSettings = false" />

        <div class="relative z-10 inline-size-full max-w-lg max-h-[85vh] bg-(--t-surface)
                    rounded-2xl border border-(--t-border)/60 shadow-2xl flex flex-col overflow-hidden">

          <!-- Modal header -->
          <div class="flex items-center justify-between px-5 py-4 border-b border-(--t-border)/30">
            <div>
              <h3 class="text-sm font-bold text-(--t-text)">⚙️ Настройки уведомлений</h3>
              <p class="text-[10px] text-(--t-text-3) mt-0.5">
                Управляйте каналами для каждого типа событий
              </p>
            </div>
            <button class="w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="showSettings = false">✕</button>
          </div>

          <!-- Settings body -->
          <div class="flex-1 overflow-y-auto p-5">

            <!-- Channel legend -->
            <div class="flex items-center gap-4 mb-4 pb-3 border-b border-(--t-border)/20">
              <span class="text-[10px] text-(--t-text-3) flex-1">Тип</span>
              <span class="shrink-0 w-12 text-center text-[9px] text-(--t-text-3)">In-app</span>
              <span class="shrink-0 w-12 text-center text-[9px] text-(--t-text-3)">Push</span>
              <span class="shrink-0 w-12 text-center text-[9px] text-(--t-text-3)">Email</span>
              <span class="shrink-0 w-12 text-center text-[9px] text-(--t-text-3)">TG</span>
              <span class="shrink-0 w-12 text-center text-[9px] text-(--t-text-3)">Звук</span>
            </div>

            <div class="flex flex-col gap-1">
              <div v-for="ch in props.channels" :key="ch.type"
                   class="flex items-center gap-4 py-2.5 px-1 rounded-xl hover:bg-(--t-card-hover)/40
                          transition-colors">
                <div class="flex-1 flex items-center gap-2 min-w-0">
                  <span class="text-sm">{{ TYPE_META[ch.type]?.icon ?? '📌' }}</span>
                  <span class="text-xs text-(--t-text-2) truncate">{{ ch.label }}</span>
                </div>

                <!-- Toggle cells -->
                <button
                  :class="[
                    'shrink-0 w-12 h-7 rounded-full flex items-center justify-center transition-all',
                    ch.inApp ? 'bg-emerald-500/20 text-emerald-400' : 'bg-(--t-bg)/60 text-(--t-text-3)',
                  ]"
                  @click="ch.inApp = !ch.inApp; emit('channel-update', ch)"
                >{{ ch.inApp ? '✓' : '—' }}</button>

                <button
                  :class="[
                    'shrink-0 w-12 h-7 rounded-full flex items-center justify-center transition-all',
                    ch.push ? 'bg-emerald-500/20 text-emerald-400' : 'bg-(--t-bg)/60 text-(--t-text-3)',
                  ]"
                  @click="ch.push = !ch.push; emit('channel-update', ch)"
                >{{ ch.push ? '✓' : '—' }}</button>

                <button
                  :class="[
                    'shrink-0 w-12 h-7 rounded-full flex items-center justify-center transition-all',
                    ch.email ? 'bg-emerald-500/20 text-emerald-400' : 'bg-(--t-bg)/60 text-(--t-text-3)',
                  ]"
                  @click="ch.email = !ch.email; emit('channel-update', ch)"
                >{{ ch.email ? '✓' : '—' }}</button>

                <button
                  :class="[
                    'shrink-0 w-12 h-7 rounded-full flex items-center justify-center transition-all',
                    ch.telegram ? 'bg-emerald-500/20 text-emerald-400' : 'bg-(--t-bg)/60 text-(--t-text-3)',
                  ]"
                  @click="ch.telegram = !ch.telegram; emit('channel-update', ch)"
                >{{ ch.telegram ? '✓' : '—' }}</button>

                <button
                  :class="[
                    'shrink-0 w-12 h-7 rounded-full flex items-center justify-center transition-all',
                    ch.sound ? 'bg-amber-500/20 text-amber-400' : 'bg-(--t-bg)/60 text-(--t-text-3)',
                  ]"
                  @click="ch.sound = !ch.sound; emit('channel-update', ch)"
                >{{ ch.sound ? '🔔' : '🔕' }}</button>
              </div>
            </div>

            <!-- Empty channels state -->
            <div v-if="props.channels.length === 0"
                 class="py-10 text-center text-[10px] text-(--t-text-3)">
              <p class="text-3xl mb-2">⚙️</p>
              <p>Настройки каналов загружаются из API</p>
            </div>
          </div>

          <!-- Modal footer -->
          <div class="flex items-center justify-end px-5 py-3 border-t border-(--t-border)/30">
            <button
              class="relative overflow-hidden px-5 py-2 rounded-xl text-xs font-semibold
                     bg-(--t-primary) text-white hover:brightness-110 active:scale-95 transition-all"
              @click="showSettings = false" @mousedown="ripple"
            >Готово</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════
         DELETE ALL CONFIRMATION
    ══════════════════════════════════════ -->
    <Transition name="modal-nt">
      <div v-if="showDeleteConfirm"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="showDeleteConfirm = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteConfirm = false" />

        <div class="relative z-10 inline-size-full max-w-sm bg-(--t-surface) rounded-2xl
                    border border-(--t-border)/60 shadow-2xl p-6 text-center">
          <p class="text-4xl mb-3">🗑️</p>
          <h3 class="text-sm font-bold text-(--t-text) mb-1">Удалить все уведомления?</h3>
          <p class="text-[10px] text-(--t-text-3) mb-5">
            Это действие нельзя отменить. Все {{ props.notifications.length }} уведомлений будут удалены.
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
              @click="executeDeleteAll" @mousedown="ripple"
            >Удалить все</button>
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
/* Ripple — unique suffix nt (Notifications) */
@keyframes ripple-nt {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* No scrollbar */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* line-clamp utility */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  overflow: hidden;
}

/* Sidebar transition */
.sb-nt-enter-active,
.sb-nt-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.sb-nt-enter-from,
.sb-nt-leave-to {
  opacity: 0;
  transform: translateX(12px);
}

/* Drawer transitions (right side) */
.dw-nt-enter-active,
.dw-nt-leave-active,
.detail-nt-enter-active,
.detail-nt-leave-active {
  transition: opacity 0.3s ease;
}
.dw-nt-enter-active > :last-child,
.dw-nt-leave-active > :last-child,
.detail-nt-enter-active > :last-child,
.detail-nt-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.dw-nt-enter-from,
.dw-nt-leave-to,
.detail-nt-enter-from,
.detail-nt-leave-to {
  opacity: 0;
}
.dw-nt-enter-from > :last-child,
.dw-nt-leave-to > :last-child,
.detail-nt-enter-from > :last-child,
.detail-nt-leave-to > :last-child {
  transform: translateX(100%);
}

/* Modal transition */
.modal-nt-enter-active,
.modal-nt-leave-active {
  transition: opacity 0.25s ease;
}
.modal-nt-enter-active > :nth-child(2),
.modal-nt-leave-active > :nth-child(2) {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}
.modal-nt-enter-from,
.modal-nt-leave-to {
  opacity: 0;
}
.modal-nt-enter-from > :nth-child(2),
.modal-nt-leave-to > :nth-child(2) {
  transform: scale(0.95) translateY(8px);
  opacity: 0;
}
</style>
