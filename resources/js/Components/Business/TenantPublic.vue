<script setup lang="ts">
/**
 * TenantPublic.vue — публичная стена / паблик / сообщество бизнеса
 *
 * B2B Tenant Dashboard — управление публичным присутствием бренда.
 *
 * Вертикали:
 *   Beauty · Taxi · Food · Hotels · RealEstate · Flowers
 *   Fashion · Furniture · Fitness · Travel · default
 *
 * ───────────────────────────────────────────────────────────────
 *  Функционал:
 *   1.  Лента публикаций (посты / акции / отзывы / объявления)
 *   2.  Создание и редактирование постов (текст + фото + тип)
 *   3.  Реакции, комментарии, расшаривание
 *   4.  Sidebar: охват · вовлечённость · подписчики · группы · вишлисты
 *   5.  Фильтры: все / акции / отзывы / объявления / закреплённые
 *   6.  Интеграция с блогерами (Экосистема Кота)
 *   7.  Управление группами клиентов и вишлистами
 *   8.  Full-screen · keyboard (Esc, N) · ripple-pb
 *   9.  Mobile drawer sidebar · адаптивный grid
 *  10.  Glassmorphism · dark theme · 2026 design
 * ───────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useAuth, useTenant } from '@/stores'

/* ━━━━━━━━━━━━  TYPES  ━━━━━━━━━━━━ */

type PostType     = 'post' | 'promo' | 'review' | 'announcement'
type FilterKey    = 'all' | PostType | 'pinned'
type ReactionKey  = 'like' | 'fire' | 'heart' | 'clap' | 'wow'
type SortKey      = 'newest' | 'popular' | 'discussed'

interface PostImage {
  url:       string
  thumb:     string
  alt?:      string
}

interface PostReaction {
  key:    ReactionKey
  count:  number
  myPick: boolean
}

interface PostComment {
  id:        number | string
  authorName: string
  authorAvatar: string
  text:      string
  createdAt: string
  isOwner:   boolean
}

interface FeedPost {
  id:            number | string
  type:          PostType
  title?:        string
  body:          string
  images:        PostImage[]
  reactions:     PostReaction[]
  commentsCount: number
  comments:      PostComment[]
  sharesCount:   number
  viewsCount:    number
  isPinned:      boolean
  isScheduled:   boolean
  scheduledAt?:  string
  createdAt:     string
  authorName:    string
  authorAvatar:  string
  tags?:         string[]
  promoEndsAt?:  string
  reviewRating?: number
}

interface PublicStat {
  key:    string
  label:  string
  value:  string
  delta?: string
  trend?: 'up' | 'down' | 'flat'
  icon:   string
}

interface ClientGroup {
  id:    number | string
  name:  string
  count: number
  color: string
}

interface WishlistItem {
  id:    number | string
  title: string
  count: number
  icon:  string
}

interface BloggerSlot {
  id:     number | string
  name:   string
  avatar: string
  reach:  string
  status: 'active' | 'pending' | 'completed'
  vertical: string
}

interface VerticalPublicConfig {
  label:          string
  icon:           string
  postTypes:      Array<{ key: PostType; label: string; icon: string }>
  placeholderText: string
  defaultTags:    string[]
}

/* ━━━━━━━━━━━━  PROPS / EMITS  ━━━━━━━━━━━━ */

const props = withDefaults(defineProps<{
  vertical?:      string
  posts?:         FeedPost[]
  stats?:         PublicStat[]
  groups?:        ClientGroup[]
  wishlists?:     WishlistItem[]
  bloggers?:      BloggerSlot[]
  loading?:       boolean
  hasMore?:       boolean
  followersCount?: number
  pageViews?:     string
  engagementRate?: string
}>(), {
  vertical:       'default',
  posts:          () => [],
  stats:          () => [],
  groups:         () => [],
  wishlists:      () => [],
  bloggers:       () => [],
  loading:        false,
  hasMore:        false,
  followersCount: 0,
  pageViews:      '0',
  engagementRate: '0%',
})

const emit = defineEmits<{
  'create-post':    [data: { type: PostType; body: string; title?: string; images: File[]; tags: string[]; scheduled?: string }]
  'edit-post':      [post: FeedPost]
  'delete-post':    [post: FeedPost]
  'pin-post':       [post: FeedPost]
  'react':          [postId: number | string, reaction: ReactionKey]
  'comment':        [postId: number | string, text: string]
  'share-post':     [post: FeedPost]
  'load-more':      []
  'filter-change':  [filter: FilterKey]
  'sort-change':    [sort: SortKey]
  'manage-group':   [group: ClientGroup]
  'manage-wishlist': [item: WishlistItem]
  'invite-blogger': []
  'toggle-fullscreen': []
  'refresh':        []
}>()

const auth = useAuth()
const biz  = useTenant()

/* ━━━━━━━━━━━━  VERTICAL CONFIG  ━━━━━━━━━━━━ */

const POST_TYPES_BASE: Array<{ key: PostType; label: string; icon: string }> = [
  { key: 'post',         label: 'Пост',        icon: '📝' },
  { key: 'promo',        label: 'Акция',       icon: '🏷️' },
  { key: 'review',       label: 'Отзыв',       icon: '⭐' },
  { key: 'announcement', label: 'Объявление',  icon: '📢' },
]

const VERTICAL_CFG: Record<string, VerticalPublicConfig> = {
  beauty: {
    label: 'Салон красоты', icon: '💄',
    postTypes: POST_TYPES_BASE,
    placeholderText: 'Расскажите о новой услуге, мастере или акции…',
    defaultTags: ['красота', 'уход', 'причёски', 'макияж'],
  },
  taxi: {
    label: 'Такси', icon: '🚕',
    postTypes: POST_TYPES_BASE,
    placeholderText: 'Новые тарифы, маршруты, акции для пассажиров…',
    defaultTags: ['поездка', 'тариф', 'комфорт', 'экономия'],
  },
  food: {
    label: 'Еда и рестораны', icon: '🍽️',
    postTypes: POST_TYPES_BASE,
    placeholderText: 'Новое блюдо, сезонное меню, специальное предложение…',
    defaultTags: ['кухня', 'рецепт', 'доставка', 'скидка'],
  },
  hotel: {
    label: 'Отели', icon: '🏨',
    postTypes: POST_TYPES_BASE,
    placeholderText: 'Специальные предложения для гостей, мероприятия…',
    defaultTags: ['отель', 'номера', 'бронь', 'отдых'],
  },
  realEstate: {
    label: 'Недвижимость', icon: '🏢',
    postTypes: POST_TYPES_BASE,
    placeholderText: 'Новые объекты, изменения цен, открытые показы…',
    defaultTags: ['квартира', 'аренда', 'новостройка', 'ипотека'],
  },
  flowers: {
    label: 'Цветы', icon: '💐',
    postTypes: POST_TYPES_BASE,
    placeholderText: 'Новые букеты, цветочные композиции, сезонные предложения…',
    defaultTags: ['букет', 'доставка', 'подарок', 'праздник'],
  },
  fashion: {
    label: 'Одежда и обувь', icon: '👗',
    postTypes: POST_TYPES_BASE,
    placeholderText: 'Новая коллекция, стильные образы, распродажа…',
    defaultTags: ['мода', 'стиль', 'коллекция', 'тренд'],
  },
  furniture: {
    label: 'Мебель', icon: '🛋️',
    postTypes: POST_TYPES_BASE,
    placeholderText: 'Новинки мебели, дизайн-проекты, специальные цены…',
    defaultTags: ['интерьер', 'дизайн', 'мебель', 'ремонт'],
  },
  fitness: {
    label: 'Фитнес', icon: '💪',
    postTypes: POST_TYPES_BASE,
    placeholderText: 'Новые программы, тренеры, фитнес-марафоны…',
    defaultTags: ['фитнес', 'тренировка', 'здоровье', 'спорт'],
  },
  travel: {
    label: 'Путешествия', icon: '✈️',
    postTypes: POST_TYPES_BASE,
    placeholderText: 'Горячие туры, направления, лайфхаки для путешественников…',
    defaultTags: ['тур', 'путешествие', 'отдых', 'билеты'],
  },
  default: {
    label: 'Бизнес', icon: '📊',
    postTypes: POST_TYPES_BASE,
    placeholderText: 'Новости, акции, обновления для клиентов…',
    defaultTags: ['бизнес', 'акция', 'новинка', 'событие'],
  },
}

const vc = computed<VerticalPublicConfig>(() =>
  VERTICAL_CFG[props.vertical] ?? VERTICAL_CFG.default
)

/* ━━━━━━━━━━━━  CONSTANTS  ━━━━━━━━━━━━ */

const FILTER_TABS: Array<{ key: FilterKey; label: string; icon: string }> = [
  { key: 'all',          label: 'Все',          icon: '📋' },
  { key: 'post',         label: 'Посты',        icon: '📝' },
  { key: 'promo',        label: 'Акции',        icon: '🏷️' },
  { key: 'review',       label: 'Отзывы',       icon: '⭐' },
  { key: 'announcement', label: 'Объявления',   icon: '📢' },
  { key: 'pinned',       label: 'Закреплённые', icon: '📌' },
]

const SORT_OPTIONS: Array<{ key: SortKey; label: string }> = [
  { key: 'newest',    label: 'Новые' },
  { key: 'popular',   label: 'Популярные' },
  { key: 'discussed', label: 'Обсуждаемые' },
]

const REACTIONS: Array<{ key: ReactionKey; emoji: string; label: string }> = [
  { key: 'like',  emoji: '👍', label: 'Нравится' },
  { key: 'fire',  emoji: '🔥', label: 'Огонь' },
  { key: 'heart', emoji: '❤️', label: 'Люблю' },
  { key: 'clap',  emoji: '👏', label: 'Браво' },
  { key: 'wow',   emoji: '😮', label: 'Вау' },
]

const TYPE_BADGE: Record<PostType, { label: string; cls: string }> = {
  post:         { label: 'Пост',        cls: 'bg-sky-500/12 text-sky-400' },
  promo:        { label: 'Акция',       cls: 'bg-amber-500/12 text-amber-400' },
  review:       { label: 'Отзыв',       cls: 'bg-violet-500/12 text-violet-400' },
  announcement: { label: 'Объявление',  cls: 'bg-emerald-500/12 text-emerald-400' },
}

const BLOGGER_STATUS: Record<string, { label: string; cls: string }> = {
  active:    { label: 'Активен',    cls: 'bg-emerald-500/12 text-emerald-400' },
  pending:   { label: 'Ожидание',   cls: 'bg-amber-500/12 text-amber-400' },
  completed: { label: 'Завершён',   cls: 'bg-zinc-500/12 text-zinc-400' },
}

/* ━━━━━━━━━━━━  STATE  ━━━━━━━━━━━━ */

const rootEl            = ref<HTMLElement | null>(null)
const isFullscreen      = ref(false)
const activeFilter      = ref<FilterKey>('all')
const activeSort        = ref<SortKey>('newest')
const showSidebar       = ref(true)
const showMobileSidebar = ref(false)
const showCreateModal   = ref(false)
const showReactPicker   = ref<number | string | null>(null)
const expandedComments  = ref<Set<number | string>>(new Set())
const commentDrafts     = ref<Record<string, string>>({})
const loadingMore       = ref(false)
const refreshing        = ref(false)

/* Create-post form */
const newPostType       = ref<PostType>('post')
const newPostTitle      = ref('')
const newPostBody       = ref('')
const newPostTags       = ref<string[]>([])
const newPostTagInput   = ref('')
const newPostScheduled  = ref('')
const newPostImages     = ref<File[]>([])
const newPostImagePreviews = ref<string[]>([])
const isPublishing      = ref(false)

/* ━━━━━━━━━━━━  COMPUTED  ━━━━━━━━━━━━ */

const filteredPosts = computed<FeedPost[]>(() => {
  let list = [...props.posts]

  if (activeFilter.value === 'pinned') {
    list = list.filter((p) => p.isPinned)
  } else if (activeFilter.value !== 'all') {
    list = list.filter((p) => p.type === activeFilter.value)
  }

  if (activeSort.value === 'popular') {
    list.sort((a, b) => totalReactions(b) - totalReactions(a))
  } else if (activeSort.value === 'discussed') {
    list.sort((a, b) => b.commentsCount - a.commentsCount)
  }
  /* newest — уже в порядке от API */

  return list
})

const filterCounts = computed(() => {
  const map: Record<FilterKey, number> = {
    all: props.posts.length,
    post: 0, promo: 0, review: 0, announcement: 0,
    pinned: 0,
  }
  for (const p of props.posts) {
    const k = p.type as PostType
    if (map[k] !== undefined) map[k]++
    if (p.isPinned) map.pinned++
  }
  return map
})

const totalFollowersFormatted = computed(() => {
  const n = props.followersCount
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M`
  if (n >= 1_000)     return `${(n / 1_000).toFixed(1)}K`
  return String(n)
})

/* ━━━━━━━━━━━━  HELPERS  ━━━━━━━━━━━━ */

function totalReactions(p: FeedPost): number {
  return p.reactions.reduce((s, r) => s + r.count, 0)
}

function fmtDate(d: string): string {
  if (!d) return '—'
  const dt  = new Date(d)
  const now = new Date()
  const diffMs = now.getTime() - dt.getTime()
  const mins  = Math.floor(diffMs / 60_000)
  const hours = Math.floor(diffMs / 3_600_000)
  const days  = Math.floor(diffMs / 86_400_000)

  if (mins < 1)  return 'Только что'
  if (mins < 60) return `${mins} мин назад`
  if (hours < 24) return `${hours} ч назад`
  if (days < 7)  return `${days} дн назад`
  return dt.toLocaleDateString('ru-RU', { day: '2-digit', month: 'short' })
}

function fmtNum(n: number): string {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M`
  if (n >= 1_000)     return `${(n / 1_000).toFixed(1)}K`
  return String(n)
}

function starsArray(rating: number): Array<'full' | 'half' | 'empty'> {
  const arr: Array<'full' | 'half' | 'empty'> = []
  for (let i = 1; i <= 5; i++) {
    if (rating >= i)           arr.push('full')
    else if (rating >= i - 0.5) arr.push('half')
    else                        arr.push('empty')
  }
  return arr
}

/* ━━━━━━━━━━━━  ACTIONS  ━━━━━━━━━━━━ */

function setFilter(f: FilterKey) {
  activeFilter.value = f
  emit('filter-change', f)
}

function setSort(s: SortKey) {
  activeSort.value = s
  emit('sort-change', s)
}

function toggleReactPicker(postId: number | string) {
  showReactPicker.value = showReactPicker.value === postId ? null : postId
}

function doReact(postId: number | string, key: ReactionKey) {
  emit('react', postId, key)
  showReactPicker.value = null
}

function toggleComments(postId: number | string) {
  const s = new Set(expandedComments.value)
  if (s.has(postId)) s.delete(postId); else s.add(postId)
  expandedComments.value = s
}

function submitComment(postId: number | string) {
  const text = (commentDrafts.value[String(postId)] ?? '').trim()
  if (!text) return
  emit('comment', postId, text)
  commentDrafts.value[String(postId)] = ''
}

function openCreate(type: PostType = 'post') {
  newPostType.value  = type
  newPostTitle.value = ''
  newPostBody.value  = ''
  newPostTags.value  = []
  newPostTagInput.value = ''
  newPostScheduled.value = ''
  newPostImages.value = []
  newPostImagePreviews.value = []
  showCreateModal.value = true
}

function addTag() {
  const tag = newPostTagInput.value.trim().replace(/^#/, '')
  if (tag && !newPostTags.value.includes(tag) && newPostTags.value.length < 10) {
    newPostTags.value.push(tag)
  }
  newPostTagInput.value = ''
}

function removeTag(idx: number) {
  newPostTags.value.splice(idx, 1)
}

function addSuggestedTag(tag: string) {
  if (!newPostTags.value.includes(tag) && newPostTags.value.length < 10) {
    newPostTags.value.push(tag)
  }
}

function onImageSelect(e: Event) {
  const input = e.target as HTMLInputElement
  if (!input.files) return
  const files = Array.from(input.files).slice(0, 6 - newPostImages.value.length)
  newPostImages.value.push(...files)
  for (const file of files) {
    const reader = new FileReader()
    reader.onload = (ev) => {
      newPostImagePreviews.value.push(ev.target?.result as string)
    }
    reader.readAsDataURL(file)
  }
  input.value = ''
}

function removeImage(idx: number) {
  newPostImages.value.splice(idx, 1)
  newPostImagePreviews.value.splice(idx, 1)
}

function publishPost() {
  if (!newPostBody.value.trim()) return
  isPublishing.value = true
  emit('create-post', {
    type:      newPostType.value,
    body:      newPostBody.value.trim(),
    title:     newPostTitle.value.trim() || undefined,
    images:    newPostImages.value,
    tags:      newPostTags.value,
    scheduled: newPostScheduled.value || undefined,
  })
  setTimeout(() => {
    isPublishing.value = false
    showCreateModal.value = false
  }, 1500)
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
    if (showCreateModal.value)   { showCreateModal.value = false; return }
    if (showMobileSidebar.value) { showMobileSidebar.value = false; return }
    if (showReactPicker.value)   { showReactPicker.value = null; return }
    if (isFullscreen.value)      { toggleFullscreen(); return }
  }
  if (e.key === 'n' && !e.ctrlKey && !e.metaKey && !(e.target instanceof HTMLInputElement) && !(e.target instanceof HTMLTextAreaElement)) {
    if (!showCreateModal.value) { openCreate(); e.preventDefault() }
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
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-pb_0.6s_ease-out]'
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

        <!-- Title block -->
        <div class="flex items-center gap-3 flex-1 min-w-0">
          <span class="text-2xl">{{ vc.icon }}</span>
          <div class="min-w-0">
            <h1 class="text-base sm:text-lg font-extrabold text-(--t-text) truncate leading-snug">
              Паблик
            </h1>
            <p class="text-[10px] text-(--t-text-3) truncate">
              {{ totalFollowersFormatted }} подписчиков · {{ props.pageViews }} просмотров
            </p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 flex-wrap">

          <!-- Sort -->
          <div class="flex items-center rounded-xl border border-(--t-border)/50 overflow-hidden">
            <button
              v-for="s in SORT_OPTIONS" :key="s.key"
              :class="[
                'relative overflow-hidden px-2.5 sm:px-3 py-1.5 text-[10px] sm:text-xs font-medium transition-all',
                activeSort === s.key
                  ? 'bg-(--t-primary) text-white'
                  : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
              ]"
              @click="setSort(s.key)" @mousedown="ripple"
            >{{ s.label }}</button>
          </div>

          <!-- Create post -->
          <button
            class="relative overflow-hidden flex items-center gap-1.5 px-3 py-1.5 rounded-xl
                   text-xs font-semibold bg-(--t-primary) text-white
                   hover:brightness-110 active:scale-95 transition-all"
            @click="openCreate()" @mousedown="ripple"
          >
            ＋ <span class="hidden sm:inline">Создать пост</span>
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

      <!-- Filter tabs -->
      <div class="px-4 sm:px-6 pb-2 flex gap-1 overflow-x-auto no-scrollbar">
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
          <span class="text-[9px] opacity-60 tabular-nums">{{ filterCounts[f.key] }}</span>
        </button>
      </div>
    </header>

    <!-- ══════════════════════════════════════
         MAIN: FEED + SIDEBAR
    ══════════════════════════════════════ -->
    <div class="flex-1 flex gap-5 px-4 sm:px-6 py-5 max-w-screen-2xl mx-auto inline-size-full">

      <!-- ═══ FEED ═══ -->
      <div class="flex-1 flex flex-col gap-4 min-w-0">

        <!-- Quick-compose bar -->
        <button
          class="group relative overflow-hidden flex items-center gap-3 px-4 py-3.5
                 rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                 backdrop-blur-sm hover:border-(--t-border)/70 hover:bg-(--t-card-hover)/40
                 active:scale-[0.99] transition-all"
          @click="openCreate()" @mousedown="ripple"
        >
          <div class="shrink-0 w-9 h-9 rounded-full bg-(--t-primary)/15 flex items-center justify-center
                      text-sm">
            {{ vc.icon }}
          </div>
          <span class="flex-1 text-start text-xs text-(--t-text-3) truncate">
            {{ vc.placeholderText }}
          </span>
          <div class="flex gap-1.5 shrink-0 opacity-60 group-hover:opacity-100 transition-opacity">
            <span title="Фото" class="text-sm">📷</span>
            <span title="Акция" class="text-sm">🏷️</span>
            <span title="Объявление" class="text-sm">📢</span>
          </div>
        </button>

        <!-- Loading skeleton -->
        <div v-if="props.loading && filteredPosts.length === 0"
             class="flex flex-col gap-4">
          <div v-for="n in 3" :key="n"
               class="rounded-2xl border border-(--t-border)/30 bg-(--t-surface)/40 p-5 animate-pulse">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-10 h-10 rounded-full bg-(--t-border)/30" />
              <div class="flex-1">
                <div class="h-3 w-28 bg-(--t-border)/30 rounded mb-1.5" />
                <div class="h-2 w-16 bg-(--t-border)/20 rounded" />
              </div>
            </div>
            <div class="h-3 w-full bg-(--t-border)/20 rounded mb-2" />
            <div class="h-3 w-3/4 bg-(--t-border)/20 rounded mb-4" />
            <div class="h-44 rounded-xl bg-(--t-border)/20" />
          </div>
        </div>

        <!-- Empty -->
        <div v-else-if="filteredPosts.length === 0 && !props.loading"
             class="py-20 text-center">
          <p class="text-4xl mb-3">📭</p>
          <p class="text-sm font-semibold text-(--t-text-2)">Лента пуста</p>
          <p class="text-[10px] text-(--t-text-3) mt-1 mb-4">
            Создайте первый пост, чтобы привлечь клиентов
          </p>
          <button
            class="relative overflow-hidden px-5 py-2 rounded-xl text-xs font-semibold
                   bg-(--t-primary) text-white hover:brightness-110 active:scale-95 transition-all"
            @click="openCreate()" @mousedown="ripple"
          >Создать пост</button>
        </div>

        <!-- Feed posts -->
        <article
          v-for="post in filteredPosts" :key="post.id"
          class="group/post rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                 backdrop-blur-sm overflow-hidden transition-all
                 hover:border-(--t-border)/70 hover:shadow-lg hover:shadow-black/5"
        >
          <!-- Post header -->
          <div class="flex items-center gap-3 px-4 sm:px-5 pt-4 pb-2">
            <div class="shrink-0 w-10 h-10 rounded-full overflow-hidden bg-(--t-border)/30">
              <img v-if="post.authorAvatar" :src="post.authorAvatar" :alt="post.authorName"
                   class="inline-size-full block-size-full object-cover" />
              <span v-else class="inline-size-full block-size-full flex items-center justify-center text-sm">
                {{ post.authorName.charAt(0) }}
              </span>
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-(--t-text) truncate">{{ post.authorName }}</span>
                <span :class="['px-1.5 py-px rounded-md text-[9px] font-medium', TYPE_BADGE[post.type].cls]">
                  {{ TYPE_BADGE[post.type].label }}
                </span>
                <span v-if="post.isPinned" class="text-[9px]" title="Закреплён">📌</span>
              </div>
              <p class="text-[10px] text-(--t-text-3)">{{ fmtDate(post.createdAt) }}</p>
            </div>

            <!-- Post menu -->
            <div class="flex gap-0.5 shrink-0 opacity-0 group-hover/post:opacity-100 transition-opacity">
              <button
                class="relative overflow-hidden w-7 h-7 rounded-lg flex items-center justify-center
                       text-(--t-text-3) hover:bg-(--t-card-hover) active:scale-90 transition-all"
                @click="emit('pin-post', post)" @mousedown="ripple" title="Закрепить"
              >📌</button>
              <button
                class="relative overflow-hidden w-7 h-7 rounded-lg flex items-center justify-center
                       text-(--t-text-3) hover:bg-(--t-card-hover) active:scale-90 transition-all"
                @click="emit('edit-post', post)" @mousedown="ripple" title="Редактировать"
              >✏️</button>
              <button
                class="relative overflow-hidden w-7 h-7 rounded-lg flex items-center justify-center
                       text-(--t-text-3) hover:bg-rose-500/12 hover:text-rose-400
                       active:scale-90 transition-all"
                @click="emit('delete-post', post)" @mousedown="ripple" title="Удалить"
              >🗑️</button>
            </div>
          </div>

          <!-- Review stars -->
          <div v-if="post.type === 'review' && post.reviewRating"
               class="px-4 sm:px-5 pb-1 flex items-center gap-0.5">
            <span v-for="(star, si) in starsArray(post.reviewRating)" :key="si"
                  class="text-sm">
              {{ star === 'full' ? '★' : star === 'half' ? '☆' : '☆' }}
            </span>
            <span class="text-[10px] text-(--t-text-3) ms-1">{{ post.reviewRating.toFixed(1) }}</span>
          </div>

          <!-- Title -->
          <h3 v-if="post.title"
              class="px-4 sm:px-5 text-sm font-bold text-(--t-text) leading-snug mb-1">
            {{ post.title }}
          </h3>

          <!-- Body text -->
          <div class="px-4 sm:px-5 pb-3">
            <p class="text-xs text-(--t-text-2) leading-relaxed whitespace-pre-line">{{ post.body }}</p>
          </div>

          <!-- Tags -->
          <div v-if="post.tags && post.tags.length > 0"
               class="px-4 sm:px-5 pb-3 flex flex-wrap gap-1">
            <span v-for="tag in post.tags" :key="tag"
                  class="px-1.5 py-0.5 rounded-md text-[9px] font-medium bg-(--t-primary)/8 text-(--t-primary)/70">
              #{{ tag }}
            </span>
          </div>

          <!-- Promo countdown -->
          <div v-if="post.type === 'promo' && post.promoEndsAt"
               class="mx-4 sm:mx-5 mb-3 px-3 py-2 rounded-xl bg-amber-500/8 border border-amber-500/15">
            <p class="text-[10px] text-amber-400 font-medium">
              🏷️ Акция до {{ fmtDate(post.promoEndsAt) }}
            </p>
          </div>

          <!-- Images -->
          <div v-if="post.images.length > 0" class="px-4 sm:px-5 pb-3">
            <div :class="[
              'grid gap-1.5 rounded-xl overflow-hidden',
              post.images.length === 1 ? 'grid-cols-1' : '',
              post.images.length === 2 ? 'grid-cols-2' : '',
              post.images.length >= 3  ? 'grid-cols-2 sm:grid-cols-3' : '',
            ]">
              <div v-for="(img, ii) in post.images.slice(0, 4)" :key="ii"
                   :class="[
                     'relative overflow-hidden bg-(--t-border)/20',
                     post.images.length === 1 ? 'aspect-video' : 'aspect-square',
                   ]">
                <img :src="img.thumb || img.url" :alt="img.alt || ''"
                     class="inline-size-full block-size-full object-cover" loading="lazy" />
                <div v-if="ii === 3 && post.images.length > 4"
                     class="absolute inset-0 bg-black/50 flex items-center justify-center">
                  <span class="text-white text-lg font-bold">+{{ post.images.length - 4 }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Engagement metrics bar -->
          <div class="px-4 sm:px-5 pb-2 flex items-center gap-4 text-[10px] text-(--t-text-3)">
            <span v-if="totalReactions(post) > 0">
              {{ REACTIONS.find((r) => post.reactions.find((pr) => pr.count > 0)?.key === r.key)?.emoji ?? '👍' }}
              {{ fmtNum(totalReactions(post)) }}
            </span>
            <span v-if="post.commentsCount > 0">{{ fmtNum(post.commentsCount) }} комм.</span>
            <span v-if="post.sharesCount > 0">{{ fmtNum(post.sharesCount) }} репостов</span>
            <span class="ms-auto">{{ fmtNum(post.viewsCount) }} 👁️</span>
          </div>

          <!-- Action bar -->
          <div class="flex items-center border-t border-(--t-border)/25 divide-x divide-(--t-border)/25">
            <!-- Reactions -->
            <div class="relative flex-1">
              <button
                class="relative overflow-hidden inline-size-full flex items-center justify-center gap-1.5
                       py-2.5 text-xs text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)/40
                       active:scale-95 transition-all"
                @click="toggleReactPicker(post.id)" @mousedown="ripple"
              >
                {{ post.reactions.find((r) => r.myPick)
                  ? REACTIONS.find((rx) => rx.key === post.reactions.find((r) => r.myPick)?.key)?.emoji ?? '👍'
                  : '👍' }}
                <span class="hidden sm:inline">Реакция</span>
              </button>
              <!-- Reaction picker popup -->
              <Transition name="pop-pb">
                <div v-if="showReactPicker === post.id"
                     class="absolute inset-block-end-full inset-inline-start-1/2 -translate-x-1/2 mb-1.5
                            flex gap-0.5 px-2 py-1.5 rounded-xl bg-(--t-surface) border border-(--t-border)/50
                            shadow-xl z-10">
                  <button
                    v-for="rx in REACTIONS" :key="rx.key"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-base
                           hover:bg-(--t-card-hover) hover:scale-125 active:scale-90 transition-all"
                    :title="rx.label"
                    @click="doReact(post.id, rx.key)"
                  >{{ rx.emoji }}</button>
                </div>
              </Transition>
            </div>

            <!-- Comments toggle -->
            <button
              class="relative overflow-hidden flex-1 flex items-center justify-center gap-1.5
                     py-2.5 text-xs text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)/40
                     active:scale-95 transition-all"
              @click="toggleComments(post.id)" @mousedown="ripple"
            >
              💬 <span class="hidden sm:inline">Комментарии</span>
              <span v-if="post.commentsCount > 0" class="text-[10px] opacity-60">{{ post.commentsCount }}</span>
            </button>

            <!-- Share -->
            <button
              class="relative overflow-hidden flex-1 flex items-center justify-center gap-1.5
                     py-2.5 text-xs text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)/40
                     active:scale-95 transition-all"
              @click="emit('share-post', post)" @mousedown="ripple"
            >
              🔗 <span class="hidden sm:inline">Поделиться</span>
            </button>
          </div>

          <!-- Comments section (expanded) -->
          <Transition name="slide-pb">
            <div v-if="expandedComments.has(post.id)"
                 class="border-t border-(--t-border)/25 bg-(--t-bg)/30">

              <!-- Existing comments -->
              <div v-if="post.comments.length > 0"
                   class="px-4 sm:px-5 pt-3 flex flex-col gap-2.5 max-h-64 overflow-y-auto">
                <div v-for="c in post.comments" :key="c.id"
                     class="flex gap-2.5">
                  <div class="shrink-0 w-7 h-7 rounded-full overflow-hidden bg-(--t-border)/30">
                    <img v-if="c.authorAvatar" :src="c.authorAvatar" :alt="c.authorName"
                         class="inline-size-full block-size-full object-cover" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                      <span class="text-[10px] font-bold text-(--t-text)">{{ c.authorName }}</span>
                      <span v-if="c.isOwner" class="text-[8px] px-1 py-px rounded bg-(--t-primary)/15 text-(--t-primary)">автор</span>
                      <span class="text-[9px] text-(--t-text-3)">{{ fmtDate(c.createdAt) }}</span>
                    </div>
                    <p class="text-[11px] text-(--t-text-2) leading-relaxed mt-0.5">{{ c.text }}</p>
                  </div>
                </div>
              </div>

              <!-- Comment input -->
              <div class="flex items-center gap-2 px-4 sm:px-5 py-3">
                <input
                  :value="commentDrafts[String(post.id)] ?? ''"
                  @input="(e: Event) => commentDrafts[String(post.id)] = (e.target as HTMLInputElement).value"
                  @keydown.enter="submitComment(post.id)"
                  type="text"
                  placeholder="Написать комментарий…"
                  class="flex-1 py-2 px-3 rounded-xl border border-(--t-border)/40 bg-(--t-bg)/60
                         text-xs text-(--t-text) placeholder:text-(--t-text-3)
                         focus:outline-none focus:border-(--t-primary)/40 transition-colors"
                />
                <button
                  class="relative overflow-hidden shrink-0 w-8 h-8 rounded-xl
                         flex items-center justify-center bg-(--t-primary) text-white text-sm
                         hover:brightness-110 active:scale-90 transition-all"
                  :disabled="!(commentDrafts[String(post.id)] ?? '').trim()"
                  @click="submitComment(post.id)" @mousedown="ripple"
                >➤</button>
              </div>
            </div>
          </Transition>
        </article>

        <!-- Load more -->
        <div v-if="props.hasMore && filteredPosts.length > 0" class="flex justify-center py-4">
          <button
            :class="[
              'relative overflow-hidden px-6 py-2.5 rounded-xl text-xs font-semibold transition-all',
              'border border-(--t-border)/50 text-(--t-text-3) hover:text-(--t-text)',
              'hover:bg-(--t-card-hover) active:scale-95',
              loadingMore ? 'animate-pulse pointer-events-none' : '',
            ]"
            @click="loadMore" @mousedown="ripple"
          >
            {{ loadingMore ? 'Загрузка…' : 'Показать ещё' }}
          </button>
        </div>
      </div>

      <!-- ═══ SIDEBAR (desktop) ═══ -->
      <Transition name="sb-pb">
        <aside v-if="showSidebar"
               class="hidden xl:flex shrink-0 flex-col gap-4 w-72">

          <!-- Public stats -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              Статистика паблика
            </h3>
            <div class="grid grid-cols-2 gap-2.5">
              <div class="rounded-xl bg-(--t-bg)/50 p-3">
                <p class="text-[10px] text-(--t-text-3)">Подписчики</p>
                <p class="text-base font-extrabold text-(--t-text)">{{ totalFollowersFormatted }}</p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-3">
                <p class="text-[10px] text-(--t-text-3)">Просмотры</p>
                <p class="text-base font-extrabold text-(--t-text)">{{ props.pageViews }}</p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-3">
                <p class="text-[10px] text-(--t-text-3)">Вовлечённость</p>
                <p class="text-base font-extrabold text-emerald-400">{{ props.engagementRate }}</p>
              </div>
              <div class="rounded-xl bg-(--t-bg)/50 p-3">
                <p class="text-[10px] text-(--t-text-3)">Постов</p>
                <p class="text-base font-extrabold text-(--t-text)">{{ props.posts.length }}</p>
              </div>
            </div>

            <!-- Dynamic stats from prop -->
            <div v-if="props.stats.length > 0" class="mt-3 flex flex-col gap-2">
              <div v-for="st in props.stats" :key="st.key"
                   class="flex items-center justify-between">
                <span class="text-[10px] text-(--t-text-3) flex items-center gap-1.5">
                  <span class="text-xs">{{ st.icon }}</span> {{ st.label }}
                </span>
                <div class="text-end">
                  <span class="text-xs font-bold text-(--t-text)">{{ st.value }}</span>
                  <span v-if="st.delta"
                        :class="[
                          'text-[9px] font-medium ms-1',
                          st.trend === 'up' ? 'text-emerald-400' : st.trend === 'down' ? 'text-rose-400' : 'text-zinc-400',
                        ]">
                    {{ st.trend === 'up' ? '↑' : st.trend === 'down' ? '↓' : '→' }}{{ st.delta }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick actions -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-3">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-2 px-1">
              Быстрые действия
            </h3>
            <div class="flex flex-col gap-0.5">
              <button
                v-for="pt in vc.postTypes" :key="pt.key"
                class="relative overflow-hidden flex items-center gap-2.5 px-3 py-2 rounded-xl
                       text-xs text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)
                       active:scale-[0.97] transition-all"
                @click="openCreate(pt.key)" @mousedown="ripple"
              >
                <span class="text-sm">{{ pt.icon }}</span>
                <span>Создать {{ pt.label.toLowerCase() }}</span>
              </button>
            </div>
          </div>

          <!-- Client groups -->
          <div v-if="props.groups.length > 0"
               class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              👥 Группы клиентов
            </h3>
            <div class="flex flex-col gap-1.5">
              <button
                v-for="g in props.groups" :key="g.id"
                class="relative overflow-hidden flex items-center gap-2.5 px-3 py-2 rounded-xl
                       hover:bg-(--t-card-hover) active:scale-[0.97] transition-all"
                @click="emit('manage-group', g)" @mousedown="ripple"
              >
                <span class="shrink-0 w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: g.color }" />
                <span class="flex-1 text-start text-xs text-(--t-text-2) truncate">{{ g.name }}</span>
                <span class="shrink-0 text-[10px] text-(--t-text-3) tabular-nums">{{ g.count }}</span>
              </button>
            </div>
          </div>

          <!-- Wishlists -->
          <div v-if="props.wishlists.length > 0"
               class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider mb-3">
              💝 Вишлисты
            </h3>
            <div class="flex flex-col gap-1.5">
              <button
                v-for="w in props.wishlists" :key="w.id"
                class="relative overflow-hidden flex items-center gap-2.5 px-3 py-2 rounded-xl
                       hover:bg-(--t-card-hover) active:scale-[0.97] transition-all"
                @click="emit('manage-wishlist', w)" @mousedown="ripple"
              >
                <span class="shrink-0 text-sm">{{ w.icon }}</span>
                <span class="flex-1 text-start text-xs text-(--t-text-2) truncate">{{ w.title }}</span>
                <span class="shrink-0 text-[10px] text-(--t-text-3) tabular-nums">{{ w.count }}</span>
              </button>
            </div>
          </div>

          <!-- Bloggers (Экосистема Кота) -->
          <div class="rounded-2xl border border-(--t-border)/40 bg-(--t-surface)/60
                      backdrop-blur-sm p-4">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-[10px] font-bold text-(--t-text-3) uppercase tracking-wider">
                🐱 Блогеры · Экосистема Кота
              </h3>
              <button
                class="relative overflow-hidden px-2 py-0.5 rounded-lg text-[9px] font-medium
                       bg-(--t-primary)/12 text-(--t-primary)
                       hover:bg-(--t-primary)/20 active:scale-95 transition-all"
                @click="emit('invite-blogger')" @mousedown="ripple"
              >＋ Найти</button>
            </div>

            <div v-if="props.bloggers.length === 0"
                 class="py-4 text-center text-[10px] text-(--t-text-3)">
              <p class="text-xl mb-1">🐱</p>
              <p>Нет подключённых блогеров</p>
            </div>

            <div v-else class="flex flex-col gap-2">
              <div v-for="bl in props.bloggers" :key="bl.id"
                   class="flex items-center gap-2.5 p-2 rounded-xl bg-(--t-bg)/40">
                <div class="shrink-0 w-8 h-8 rounded-full overflow-hidden bg-(--t-border)/30">
                  <img v-if="bl.avatar" :src="bl.avatar" :alt="bl.name"
                       class="inline-size-full block-size-full object-cover" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-[10px] font-bold text-(--t-text) truncate">{{ bl.name }}</p>
                  <p class="text-[9px] text-(--t-text-3)">{{ bl.reach }} охват</p>
                </div>
                <span :class="['shrink-0 px-1.5 py-0.5 rounded-md text-[8px] font-medium',
                       BLOGGER_STATUS[bl.status]?.cls ?? 'bg-zinc-500/12 text-zinc-400']">
                  {{ BLOGGER_STATUS[bl.status]?.label ?? bl.status }}
                </span>
              </div>
            </div>
          </div>
        </aside>
      </Transition>
    </div>

    <!-- ══════════════════════════════════════
         MOBILE SIDEBAR DRAWER
    ══════════════════════════════════════ -->
    <Transition name="dw-pb">
      <div v-if="showMobileSidebar"
           class="fixed inset-0 z-50 flex" @click.self="showMobileSidebar = false">
        <div class="absolute inset-0 bg-black/40" @click="showMobileSidebar = false" />

        <div class="relative z-10 ms-auto inline-size-72 max-w-[85vw] h-full bg-(--t-surface)
                    border-s border-(--t-border) overflow-y-auto p-4 flex flex-col gap-4">

          <div class="flex items-center justify-between mb-1">
            <h3 class="text-sm font-bold text-(--t-text)">{{ vc.icon }} Паблик</h3>
            <button class="text-(--t-text-3) hover:text-(--t-text) transition-colors"
                    @click="showMobileSidebar = false">✕</button>
          </div>

          <!-- Mobile stats -->
          <div class="grid grid-cols-2 gap-2">
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Подписчики</p>
              <p class="text-sm font-bold text-(--t-text)">{{ totalFollowersFormatted }}</p>
            </div>
            <div class="rounded-xl bg-(--t-bg)/50 p-3">
              <p class="text-[9px] text-(--t-text-3)">Вовлечённость</p>
              <p class="text-sm font-bold text-emerald-400">{{ props.engagementRate }}</p>
            </div>
          </div>

          <!-- Mobile quick actions -->
          <div class="flex flex-col gap-0.5">
            <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase mb-1">Быстрые действия</h4>
            <button
              v-for="pt in vc.postTypes" :key="pt.key"
              class="relative overflow-hidden flex items-center gap-2.5 px-3 py-2.5 rounded-xl
                     text-xs text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)
                     active:scale-[0.97] transition-all"
              @click="openCreate(pt.key); showMobileSidebar = false" @mousedown="ripple"
            >
              <span class="text-sm">{{ pt.icon }}</span>
              <span>Создать {{ pt.label.toLowerCase() }}</span>
            </button>
          </div>

          <!-- Mobile groups -->
          <div v-if="props.groups.length > 0">
            <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase mb-1.5">👥 Группы</h4>
            <div class="flex flex-col gap-1">
              <button
                v-for="g in props.groups" :key="g.id"
                class="relative overflow-hidden flex items-center gap-2 px-3 py-2 rounded-xl
                       hover:bg-(--t-card-hover) active:scale-[0.97] transition-all"
                @click="emit('manage-group', g); showMobileSidebar = false" @mousedown="ripple"
              >
                <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: g.color }" />
                <span class="flex-1 text-start text-xs text-(--t-text-2) truncate">{{ g.name }}</span>
                <span class="text-[10px] text-(--t-text-3)">{{ g.count }}</span>
              </button>
            </div>
          </div>

          <!-- Mobile bloggers -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <h4 class="text-[10px] font-bold text-(--t-text-3) uppercase">🐱 Блогеры</h4>
              <button
                class="relative overflow-hidden px-2 py-0.5 rounded-lg text-[9px]
                       bg-(--t-primary)/12 text-(--t-primary) hover:bg-(--t-primary)/20
                       active:scale-95 transition-all"
                @click="emit('invite-blogger'); showMobileSidebar = false" @mousedown="ripple"
              >＋</button>
            </div>
            <div v-if="props.bloggers.length > 0" class="flex flex-col gap-1.5">
              <div v-for="bl in props.bloggers" :key="bl.id"
                   class="flex items-center gap-2 p-2 rounded-xl bg-(--t-bg)/40">
                <div class="shrink-0 w-7 h-7 rounded-full overflow-hidden bg-(--t-border)/30">
                  <img v-if="bl.avatar" :src="bl.avatar" :alt="bl.name"
                       class="inline-size-full block-size-full object-cover" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-[10px] font-bold text-(--t-text) truncate">{{ bl.name }}</p>
                  <p class="text-[9px] text-(--t-text-3)">{{ bl.reach }}</p>
                </div>
              </div>
            </div>
            <p v-else class="text-[10px] text-(--t-text-3) text-center py-3">Нет блогеров</p>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════
         CREATE POST MODAL
    ══════════════════════════════════════ -->
    <Transition name="modal-pb">
      <div v-if="showCreateModal"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="showCreateModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showCreateModal = false" />

        <div class="relative z-10 inline-size-full max-w-xl max-h-[90vh] bg-(--t-surface)
                    rounded-2xl border border-(--t-border)/60 shadow-2xl flex flex-col overflow-hidden">

          <!-- Modal header -->
          <div class="flex items-center justify-between px-5 py-4 border-b border-(--t-border)/30">
            <div>
              <h3 class="text-sm font-bold text-(--t-text)">
                {{ vc.postTypes.find((t) => t.key === newPostType)?.icon }}
                Создать {{ vc.postTypes.find((t) => t.key === newPostType)?.label.toLowerCase() ?? 'пост' }}
              </h3>
              <p class="text-[10px] text-(--t-text-3) mt-0.5">Опубликуйте от имени {{ vc.label }}</p>
            </div>
            <button class="w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) transition-colors"
                    @click="showCreateModal = false">✕</button>
          </div>

          <!-- Modal body -->
          <div class="flex-1 overflow-y-auto p-5 flex flex-col gap-4">

            <!-- Post type selector -->
            <div class="flex gap-1.5">
              <button
                v-for="pt in vc.postTypes" :key="pt.key"
                :class="[
                  'relative overflow-hidden flex-1 py-2 rounded-xl text-xs font-medium text-center transition-all border',
                  newPostType === pt.key
                    ? 'border-(--t-primary)/40 bg-(--t-primary)/10 text-(--t-primary)'
                    : 'border-(--t-border)/40 text-(--t-text-3) hover:bg-(--t-card-hover)',
                ]"
                @click="newPostType = pt.key" @mousedown="ripple"
              >{{ pt.icon }} {{ pt.label }}</button>
            </div>

            <!-- Title (optional) -->
            <div>
              <label class="block text-[10px] font-medium text-(--t-text-3) mb-1.5">
                Заголовок <span class="opacity-50">(необязательно)</span>
              </label>
              <input
                v-model="newPostTitle"
                type="text"
                placeholder="Заголовок поста…"
                class="inline-size-full py-2 px-3 rounded-xl border border-(--t-border)/50
                       bg-(--t-bg)/60 text-xs text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </div>

            <!-- Body -->
            <div>
              <label class="block text-[10px] font-medium text-(--t-text-3) mb-1.5">Текст поста</label>
              <textarea
                v-model="newPostBody"
                rows="5"
                :placeholder="vc.placeholderText"
                class="inline-size-full py-2.5 px-3 rounded-xl border border-(--t-border)/50
                       bg-(--t-bg)/60 text-xs text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors resize-none"
              />
            </div>

            <!-- Images -->
            <div>
              <label class="block text-[10px] font-medium text-(--t-text-3) mb-1.5">
                Фото <span class="opacity-50">(до 6)</span>
              </label>
              <div class="flex flex-wrap gap-2">
                <div v-for="(preview, pi) in newPostImagePreviews" :key="pi"
                     class="relative w-16 h-16 rounded-xl overflow-hidden bg-(--t-border)/20">
                  <img :src="preview" alt="" class="inline-size-full block-size-full object-cover" />
                  <button
                    class="absolute inset-block-start-0.5 inset-inline-end-0.5 w-5 h-5 rounded-full
                           bg-black/60 text-white text-[10px] flex items-center justify-center
                           hover:bg-rose-500 transition-colors"
                    @click="removeImage(pi)"
                  >✕</button>
                </div>
                <label v-if="newPostImages.length < 6"
                       class="flex items-center justify-center w-16 h-16 rounded-xl
                              border-2 border-dashed border-(--t-border)/50 cursor-pointer
                              text-(--t-text-3) hover:border-(--t-primary)/40 hover:text-(--t-primary)
                              transition-colors">
                  <span class="text-xl">＋</span>
                  <input type="file" accept="image/*" multiple class="hidden" @change="onImageSelect" />
                </label>
              </div>
            </div>

            <!-- Tags -->
            <div>
              <label class="block text-[10px] font-medium text-(--t-text-3) mb-1.5">
                Теги <span class="opacity-50">(до 10)</span>
              </label>
              <div class="flex flex-wrap gap-1.5 mb-2">
                <span v-for="(tag, ti) in newPostTags" :key="ti"
                      class="flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-medium
                             bg-(--t-primary)/10 text-(--t-primary)">
                  #{{ tag }}
                  <button class="hover:text-rose-400 transition-colors" @click="removeTag(ti)">✕</button>
                </span>
              </div>
              <div class="flex gap-1.5">
                <input
                  v-model="newPostTagInput"
                  type="text"
                  placeholder="Добавить тег…"
                  class="flex-1 py-1.5 px-3 rounded-lg border border-(--t-border)/40 bg-(--t-bg)/60
                         text-[10px] text-(--t-text) placeholder:text-(--t-text-3)
                         focus:outline-none focus:border-(--t-primary)/40 transition-colors"
                  @keydown.enter.prevent="addTag"
                />
              </div>
              <!-- Suggested tags -->
              <div class="flex flex-wrap gap-1 mt-2">
                <button
                  v-for="st in vc.defaultTags.filter((t) => !newPostTags.includes(t))" :key="st"
                  class="px-1.5 py-0.5 rounded-md text-[9px] text-(--t-text-3)
                         border border-(--t-border)/30 hover:border-(--t-primary)/30
                         hover:text-(--t-primary) transition-colors"
                  @click="addSuggestedTag(st)"
                >#{{ st }}</button>
              </div>
            </div>

            <!-- Schedule (optional) -->
            <div>
              <label class="block text-[10px] font-medium text-(--t-text-3) mb-1.5">
                Отложенная публикация <span class="opacity-50">(необязательно)</span>
              </label>
              <input
                v-model="newPostScheduled"
                type="datetime-local"
                class="inline-size-full py-2 px-3 rounded-xl border border-(--t-border)/50
                       bg-(--t-bg)/60 text-xs text-(--t-text)
                       focus:outline-none focus:border-(--t-primary)/50 transition-colors"
              />
            </div>
          </div>

          <!-- Modal footer -->
          <div class="flex items-center justify-between px-5 py-3 border-t border-(--t-border)/30">
            <span class="text-[10px] text-(--t-text-3)">
              {{ newPostBody.length }} символов
              <span v-if="newPostImages.length > 0"> · {{ newPostImages.length }} фото</span>
              <span v-if="newPostTags.length > 0"> · {{ newPostTags.length }} тегов</span>
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
                  'relative overflow-hidden px-5 py-2 rounded-xl text-xs font-semibold transition-all active:scale-95',
                  newPostBody.trim()
                    ? 'bg-(--t-primary) text-white hover:brightness-110'
                    : 'bg-zinc-700 text-zinc-500 cursor-not-allowed',
                ]"
                :disabled="!newPostBody.trim() || isPublishing"
                @click="publishPost" @mousedown="ripple"
              >
                {{ isPublishing ? '⏳ Публикация…' : newPostScheduled ? '🕐 Запланировать' : '📤 Опубликовать' }}
              </button>
            </div>
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
/* Ripple — unique suffix pb (Public) */
@keyframes ripple-pb {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* No-scrollbar */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Sidebar transition */
.sb-pb-enter-active,
.sb-pb-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.sb-pb-enter-from,
.sb-pb-leave-to {
  opacity: 0;
  transform: translateX(12px);
}

/* Drawer transition (right side) */
.dw-pb-enter-active,
.dw-pb-leave-active {
  transition: opacity 0.3s ease;
}
.dw-pb-enter-active > :last-child,
.dw-pb-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.dw-pb-enter-from,
.dw-pb-leave-to {
  opacity: 0;
}
.dw-pb-enter-from > :last-child,
.dw-pb-leave-to > :last-child {
  transform: translateX(100%);
}

/* Modal transition */
.modal-pb-enter-active,
.modal-pb-leave-active {
  transition: opacity 0.25s ease;
}
.modal-pb-enter-active > :nth-child(2),
.modal-pb-leave-active > :nth-child(2) {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}
.modal-pb-enter-from,
.modal-pb-leave-to {
  opacity: 0;
}
.modal-pb-enter-from > :nth-child(2),
.modal-pb-leave-to > :nth-child(2) {
  transform: scale(0.95) translateY(8px);
  opacity: 0;
}

/* Comments slide transition */
.slide-pb-enter-active,
.slide-pb-leave-active {
  transition: max-height 0.3s ease, opacity 0.25s ease;
  overflow: hidden;
}
.slide-pb-enter-from,
.slide-pb-leave-to {
  max-block-size: 0;
  opacity: 0;
}
.slide-pb-enter-to,
.slide-pb-leave-from {
  max-block-size: 500px;
  opacity: 1;
}

/* Reaction picker popup */
.pop-pb-enter-active,
.pop-pb-leave-active {
  transition: opacity 0.15s ease, transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.pop-pb-enter-from,
.pop-pb-leave-to {
  opacity: 0;
  transform: translateX(-50%) scale(0.9) translateY(4px);
}
.pop-pb-enter-to,
.pop-pb-leave-from {
  transform: translateX(-50%) scale(1) translateY(0);
}
</style>
