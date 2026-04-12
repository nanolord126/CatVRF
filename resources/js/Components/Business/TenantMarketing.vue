<!--
  ╔═══════════════════════════════════════════════════════════════════════════╗
  ║  TenantMarketing.vue — маркетинг и реклама в B2B Tenant Dashboard       ║
  ║  CatVRF 2026 — 9-слойная архитектура · Tailwind v4 · Vue 3 + TS        ║
  ║  Вертикали: beauty · taxi · food · hotel · realEstate · flowers ·       ║
  ║             fashion · furniture · fitness · travel · default             ║
  ╚═══════════════════════════════════════════════════════════════════════════╝
-->
<script setup lang="ts">
import {
  ref,
  reactive,
  computed,
  watch,
  onMounted,
  onBeforeUnmount,
  nextTick,
  type Ref,
} from 'vue'

import VCard   from '../UI/VCard.vue'
import VButton from '../UI/VButton.vue'
import VBadge  from '../UI/VBadge.vue'
import VTabs   from '../UI/VTabs.vue'
import VModal  from '../UI/VModal.vue'
import VInput  from '../UI/VInput.vue'
import { useAuth, useTenant, useNotifications } from '@/stores'

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   TYPES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

type CampaignStatus = 'active' | 'paused' | 'draft' | 'completed' | 'rejected'
type CampaignType   = 'contextual' | 'banner' | 'push' | 'email' | 'sms' | 'shorts' | 'blogger' | 'social'
type BloggerStatus  = 'pending' | 'active' | 'negotiation' | 'completed' | 'declined'

interface Campaign {
  id:              number | string
  name:            string
  type:            CampaignType
  status:          CampaignStatus
  budget:          number
  spent:           number
  impressions:     number
  clicks:          number
  conversions:     number
  revenue:         number
  ctr:             number
  roas:            number
  cpc:             number
  startDate:       string
  endDate:         string
  targeting:       string
  vertical:        string
  correlationId:   string
  createdAt:       string
  updatedAt:       string
}

interface BloggerCollab {
  id:           number | string
  bloggerName:  string
  platform:     string
  followers:    number
  status:       BloggerStatus
  budget:       number
  reach:        number
  engagement:   number
  conversions:  number
  revenue:      number
  vertical:     string
  avatarUrl:    string | null
  startDate:    string
  endDate:      string
}

interface MarketingStats {
  totalBudget:       number
  totalSpent:        number
  activeCampaigns:   number
  totalCampaigns:    number
  totalImpressions:  number
  totalClicks:       number
  totalConversions:  number
  avgCtr:            number
  avgRoas:           number
  avgCpc:            number
  adRevenue:         number
  adRevenueGrowth:   number
  bloggerSpent:      number
  bloggerRevenue:    number
  budgetGrowth:      number
  impressionGrowth:  number
  conversionGrowth:  number
}

interface AdDayPoint {
  date:        string
  impressions: number
  clicks:      number
  conversions: number
  spent:       number
  revenue:     number
}

interface CampaignFilter {
  search:  string
  status:  CampaignStatus | ''
  type:    CampaignType   | ''
  sortBy:  string
  sortDir: 'asc' | 'desc'
}

interface MarketingChannelStat {
  channel:     string
  icon:        string
  budget:      number
  spent:       number
  impressions: number
  clicks:      number
  conversions: number
  revenue:     number
  ctr:         number
  roas:        number
}

interface VerticalMarketingConfig {
  icon:             string
  label:            string
  accentColor:      string
  kpiLabel:         string
  campaignTypes:    { key: CampaignType; label: string; icon: string }[]
  targetingHints:   string[]
  bloggerPlatforms: string[]
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   PROPS / EMITS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const props = withDefaults(defineProps<{
  vertical:     string
  stats:        MarketingStats
  campaigns:    Campaign[]
  bloggers:     BloggerCollab[]
  adDays:       AdDayPoint[]
  channels:     MarketingChannelStat[]
  loading?:     boolean
  totalCount?:  number
  perPage?:     number
}>(), {
  loading:    false,
  totalCount: 0,
  perPage:    20,
})

const emit = defineEmits<{
  (e: 'campaign-click',    campaign: Campaign): void
  (e: 'campaign-create',   data: Record<string, unknown>): void
  (e: 'campaign-pause',    id: number | string): void
  (e: 'campaign-resume',   id: number | string): void
  (e: 'campaign-delete',   id: number | string): void
  (e: 'campaign-duplicate', id: number | string): void
  (e: 'blogger-click',     collab: BloggerCollab): void
  (e: 'blogger-request',   data: Record<string, unknown>): void
  (e: 'filter-change',     filters: CampaignFilter): void
  (e: 'sort-change',       key: string, dir: string): void
  (e: 'page-change',       page: number): void
  (e: 'period-change',     key: string): void
  (e: 'export',            type: string): void
  (e: 'load-more'): void
}>()

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   STORES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const auth   = useAuth()
const biz    = useTenant()
const notify = useNotifications()

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   VERTICAL CONFIG  (11 вертикалей + default)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const VERTICAL_MARKETING_CONFIG: Record<string, VerticalMarketingConfig> = {
  beauty: {
    icon: '💄', label: 'Маркетинг · Красота', accentColor: 'pink',
    kpiLabel: 'Записей через рекламу',
    campaignTypes: [
      { key: 'contextual', label: 'Контекстная', icon: '🔍' },
      { key: 'banner',     label: 'Баннерная',   icon: '🖼️' },
      { key: 'shorts',     label: 'Шортсы',      icon: '📱' },
      { key: 'blogger',    label: 'Блогеры',     icon: '👩‍🦰' },
      { key: 'social',     label: 'Соцсети',     icon: '📲' },
      { key: 'push',       label: 'Push',        icon: '🔔' },
      { key: 'email',      label: 'Email',       icon: '📧' },
      { key: 'sms',        label: 'SMS',         icon: '💬' },
    ],
    targetingHints: ['цветотип', 'тип кожи', 'возраст', 'район', 'AR-примерка'],
    bloggerPlatforms: ['Instagram', 'TikTok', 'YouTube', 'Telegram'],
  },
  taxi: {
    icon: '🚕', label: 'Маркетинг · Такси', accentColor: 'yellow',
    kpiLabel: 'Поездок через рекламу',
    campaignTypes: [
      { key: 'contextual', label: 'Контекстная', icon: '🔍' },
      { key: 'push',       label: 'Push',        icon: '🔔' },
      { key: 'banner',     label: 'Баннерная',   icon: '🖼️' },
      { key: 'social',     label: 'Соцсети',     icon: '📲' },
      { key: 'email',      label: 'Email',       icon: '📧' },
      { key: 'sms',        label: 'SMS',         icon: '💬' },
    ],
    targetingHints: ['маршрут', 'частота поездок', 'время суток', 'район'],
    bloggerPlatforms: ['TikTok', 'YouTube', 'Telegram'],
  },
  food: {
    icon: '🍕', label: 'Маркетинг · Еда', accentColor: 'orange',
    kpiLabel: 'Заказов через рекламу',
    campaignTypes: [
      { key: 'contextual', label: 'Контекстная', icon: '🔍' },
      { key: 'banner',     label: 'Баннерная',   icon: '🖼️' },
      { key: 'shorts',     label: 'Шортсы',      icon: '📱' },
      { key: 'blogger',    label: 'Блогеры',     icon: '🍳' },
      { key: 'push',       label: 'Push',        icon: '🔔' },
      { key: 'email',      label: 'Email',       icon: '📧' },
      { key: 'sms',        label: 'SMS',         icon: '💬' },
      { key: 'social',     label: 'Соцсети',     icon: '📲' },
    ],
    targetingHints: ['диета', 'кухня', 'район доставки', 'время обеда', 'КБЖУ'],
    bloggerPlatforms: ['Instagram', 'TikTok', 'YouTube', 'Telegram', 'Дзен'],
  },
  hotel: {
    icon: '🏨', label: 'Маркетинг · Отели', accentColor: 'blue',
    kpiLabel: 'Бронирований через рекламу',
    campaignTypes: [
      { key: 'contextual', label: 'Контекстная', icon: '🔍' },
      { key: 'banner',     label: 'Баннерная',   icon: '🖼️' },
      { key: 'shorts',     label: 'Шортсы',      icon: '📱' },
      { key: 'blogger',    label: 'Блогеры',     icon: '✈️' },
      { key: 'social',     label: 'Соцсети',     icon: '📲' },
      { key: 'email',      label: 'Email',       icon: '📧' },
    ],
    targetingHints: ['направление', 'бюджет', 'звёздность', 'тип отдыха', 'даты'],
    bloggerPlatforms: ['Instagram', 'YouTube', 'TikTok', 'Telegram'],
  },
  realEstate: {
    icon: '🏠', label: 'Маркетинг · Недвижимость', accentColor: 'teal',
    kpiLabel: 'Заявок через рекламу',
    campaignTypes: [
      { key: 'contextual', label: 'Контекстная', icon: '🔍' },
      { key: 'banner',     label: 'Баннерная',   icon: '🖼️' },
      { key: 'social',     label: 'Соцсети',     icon: '📲' },
      { key: 'email',      label: 'Email',       icon: '📧' },
      { key: 'blogger',    label: 'Блогеры',     icon: '🏗️' },
    ],
    targetingHints: ['район', 'бюджет', 'площадь', 'тип жилья', 'цель (покупка/аренда)'],
    bloggerPlatforms: ['YouTube', 'Telegram', 'Instagram', 'Дзен'],
  },
  flowers: {
    icon: '💐', label: 'Маркетинг · Цветы', accentColor: 'rose',
    kpiLabel: 'Заказов через рекламу',
    campaignTypes: [
      { key: 'contextual', label: 'Контекстная', icon: '🔍' },
      { key: 'banner',     label: 'Баннерная',   icon: '🖼️' },
      { key: 'shorts',     label: 'Шортсы',      icon: '📱' },
      { key: 'social',     label: 'Соцсети',     icon: '📲' },
      { key: 'push',       label: 'Push',        icon: '🔔' },
      { key: 'sms',        label: 'SMS',         icon: '💬' },
    ],
    targetingHints: ['повод', 'бюджет', 'район доставки', 'предпочтения'],
    bloggerPlatforms: ['Instagram', 'TikTok', 'Telegram'],
  },
  fashion: {
    icon: '👗', label: 'Маркетинг · Мода', accentColor: 'fuchsia',
    kpiLabel: 'Покупок через рекламу',
    campaignTypes: [
      { key: 'contextual', label: 'Контекстная', icon: '🔍' },
      { key: 'banner',     label: 'Баннерная',   icon: '🖼️' },
      { key: 'shorts',     label: 'Шортсы',      icon: '📱' },
      { key: 'blogger',    label: 'Блогеры',     icon: '👗' },
      { key: 'social',     label: 'Соцсети',     icon: '📲' },
      { key: 'email',      label: 'Email',       icon: '📧' },
    ],
    targetingHints: ['цветотип', 'стиль', 'размер', 'бренд', 'бюджет', 'сезон'],
    bloggerPlatforms: ['Instagram', 'TikTok', 'YouTube', 'Pinterest'],
  },
  furniture: {
    icon: '🛋️', label: 'Маркетинг · Мебель', accentColor: 'amber',
    kpiLabel: 'Заказов через рекламу',
    campaignTypes: [
      { key: 'contextual', label: 'Контекстная', icon: '🔍' },
      { key: 'banner',     label: 'Баннерная',   icon: '🖼️' },
      { key: 'social',     label: 'Соцсети',     icon: '📲' },
      { key: 'email',      label: 'Email',       icon: '📧' },
      { key: 'shorts',     label: 'Шортсы',      icon: '📱' },
    ],
    targetingHints: ['стиль интерьера', 'бюджет', 'площадь', 'тип помещения'],
    bloggerPlatforms: ['YouTube', 'Instagram', 'TikTok', 'Дзен'],
  },
  fitness: {
    icon: '💪', label: 'Маркетинг · Фитнес', accentColor: 'lime',
    kpiLabel: 'Абонементов через рекламу',
    campaignTypes: [
      { key: 'contextual', label: 'Контекстная', icon: '🔍' },
      { key: 'shorts',     label: 'Шортсы',      icon: '📱' },
      { key: 'blogger',    label: 'Блогеры',     icon: '🏃' },
      { key: 'social',     label: 'Соцсети',     icon: '📲' },
      { key: 'push',       label: 'Push',        icon: '🔔' },
      { key: 'email',      label: 'Email',       icon: '📧' },
    ],
    targetingHints: ['цель (похудение/масса/выносливость)', 'район', 'бюджет', 'возраст'],
    bloggerPlatforms: ['Instagram', 'TikTok', 'YouTube', 'Telegram'],
  },
  travel: {
    icon: '✈️', label: 'Маркетинг · Путешествия', accentColor: 'cyan',
    kpiLabel: 'Бронирований через рекламу',
    campaignTypes: [
      { key: 'contextual', label: 'Контекстная', icon: '🔍' },
      { key: 'banner',     label: 'Баннерная',   icon: '🖼️' },
      { key: 'shorts',     label: 'Шортсы',      icon: '📱' },
      { key: 'blogger',    label: 'Блогеры',     icon: '🌍' },
      { key: 'social',     label: 'Соцсети',     icon: '📲' },
      { key: 'email',      label: 'Email',       icon: '📧' },
    ],
    targetingHints: ['направление', 'бюджет', 'тип отдыха', 'даты', 'кол-во человек'],
    bloggerPlatforms: ['Instagram', 'YouTube', 'TikTok', 'Telegram', 'Дзен'],
  },
  default: {
    icon: '📢', label: 'Маркетинг', accentColor: 'indigo',
    kpiLabel: 'Конверсий через рекламу',
    campaignTypes: [
      { key: 'contextual', label: 'Контекстная', icon: '🔍' },
      { key: 'banner',     label: 'Баннерная',   icon: '🖼️' },
      { key: 'shorts',     label: 'Шортсы',      icon: '📱' },
      { key: 'blogger',    label: 'Блогеры',     icon: '📣' },
      { key: 'social',     label: 'Соцсети',     icon: '📲' },
      { key: 'push',       label: 'Push',        icon: '🔔' },
      { key: 'email',      label: 'Email',       icon: '📧' },
      { key: 'sms',        label: 'SMS',         icon: '💬' },
    ],
    targetingHints: ['аудитория', 'гео', 'возраст', 'интересы', 'бюджет'],
    bloggerPlatforms: ['Instagram', 'TikTok', 'YouTube', 'Telegram'],
  },
}

const vm = computed<VerticalMarketingConfig>(
  () => VERTICAL_MARKETING_CONFIG[props.vertical] ?? VERTICAL_MARKETING_CONFIG.default,
)

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   STATUS / TYPE MAPS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const CAMPAIGN_STATUS_MAP: Record<CampaignStatus, { label: string; badge: string; dot: string }> = {
  active:    { label: 'Активна',    badge: 'emerald', dot: 'bg-emerald-400' },
  paused:    { label: 'Пауза',     badge: 'amber',   dot: 'bg-amber-400'   },
  draft:     { label: 'Черновик',   badge: 'slate',   dot: 'bg-slate-400'   },
  completed: { label: 'Завершена', badge: 'sky',     dot: 'bg-sky-400'     },
  rejected:  { label: 'Отклонена', badge: 'rose',    dot: 'bg-rose-400'    },
}

const CAMPAIGN_TYPE_MAP: Record<CampaignType, { label: string; icon: string }> = {
  contextual: { label: 'Контекстная', icon: '🔍' },
  banner:     { label: 'Баннерная',   icon: '🖼️' },
  push:       { label: 'Push',        icon: '🔔' },
  email:      { label: 'Email',       icon: '📧' },
  sms:        { label: 'SMS',         icon: '💬' },
  shorts:     { label: 'Шортсы',      icon: '📱' },
  blogger:    { label: 'Блогеры',     icon: '📣' },
  social:     { label: 'Соцсети',     icon: '📲' },
}

const BLOGGER_STATUS_MAP: Record<BloggerStatus, { label: string; badge: string }> = {
  pending:     { label: 'Ожидание',     badge: 'amber'   },
  active:      { label: 'Активна',      badge: 'emerald' },
  negotiation: { label: 'Переговоры',   badge: 'sky'     },
  completed:   { label: 'Завершена',   badge: 'violet'  },
  declined:    { label: 'Отклонена',   badge: 'rose'    },
}

const ALL_STATUSES: CampaignStatus[] = ['active', 'paused', 'draft', 'completed', 'rejected']
const ALL_TYPES: CampaignType[]      = ['contextual', 'banner', 'push', 'email', 'sms', 'shorts', 'blogger', 'social']

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   STATE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const rootEl             = ref<HTMLElement | null>(null)
const scrollSentinel     = ref<HTMLElement | null>(null)
const isFullscreen       = ref(false)

const showCreateModal    = ref(false)
const showCampaignModal  = ref(false)
const showBloggerModal   = ref(false)
const showExportMenu     = ref(false)
const showFilterDrawer   = ref(false)

const selectedCampaign   = ref<Campaign | null>(null)
const selectedBlogger    = ref<BloggerCollab | null>(null)

const currentPage        = ref(1)
const activeTab          = ref<'campaigns' | 'bloggers' | 'channels'>('campaigns')

const animatedBudget     = ref(0)
const animatedSpent      = ref(0)
const animatedRoas       = ref(0)
const animatedRevenue    = ref(0)

const filters = reactive<CampaignFilter>({
  search:  '',
  status:  '',
  type:    '',
  sortBy:  'spent',
  sortDir: 'desc',
})

const PERIODS = [
  { key: 'today', label: 'Сегодня' },
  { key: '7d',    label: '7 дней'  },
  { key: '30d',   label: '30 дней' },
  { key: '90d',   label: '90 дней' },
  { key: 'year',  label: 'Год'     },
] as const

const activePeriod = ref<string>('30d')

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   COMPUTED
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const totalPages = computed(() => Math.max(1, Math.ceil(props.totalCount / props.perPage)))

const hasActiveFilters = computed(() =>
  filters.search !== '' || filters.status !== '' || filters.type !== '',
)

const statusCountMap = computed(() => {
  const m: Record<string, number> = {}
  ALL_STATUSES.forEach(s => { m[s] = props.campaigns.filter(c => c.status === s).length })
  return m
})

const typeCountMap = computed(() => {
  const m: Record<string, number> = {}
  ALL_TYPES.forEach(t => { m[t] = props.campaigns.filter(c => c.type === t).length })
  return m
})

const budgetUsedPercent = computed(() => {
  if (!props.stats.totalBudget) return 0
  return Math.min(100, Math.round((props.stats.totalSpent / props.stats.totalBudget) * 100))
})

const maxAdDaySpent = computed(() =>
  Math.max(1, ...props.adDays.map(d => Math.max(d.spent, d.revenue))),
)

const visiblePages = computed(() => {
  const total = totalPages.value
  const cur   = currentPage.value
  const pages: (number | 'dots')[] = []
  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i)
    return pages
  }
  pages.push(1)
  if (cur > 3) pages.push('dots')
  for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i)
  if (cur < total - 2) pages.push('dots')
  pages.push(total)
  return pages
})

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ANIMATE VALUES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function animateValue(target: Ref<number>, to: number, duration = 800) {
  const start = target.value
  const delta = to - start
  const t0    = performance.now()
  function frame(now: number) {
    const elapsed = now - t0
    const progress = Math.min(elapsed / duration, 1)
    const ease = 1 - Math.pow(1 - progress, 3)
    target.value = Math.round(start + delta * ease)
    if (progress < 1) requestAnimationFrame(frame)
  }
  requestAnimationFrame(frame)
}

watch(() => props.stats, (s) => {
  animateValue(animatedBudget,  s.totalBudget)
  animateValue(animatedSpent,   s.totalSpent)
  animateValue(animatedRoas,    Math.round(s.avgRoas * 100))
  animateValue(animatedRevenue, s.adRevenue)
}, { immediate: true })

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   PERIOD / FILTER / SORT / PAGE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function selectPeriod(key: string) {
  activePeriod.value = key
  emit('period-change', key)
}

function applyFilter(key: keyof CampaignFilter, val: string) {
  ;(filters as Record<string, string>)[key] = val
  currentPage.value = 1
  emitFilters()
}

function clearAllFilters() {
  filters.search  = ''
  filters.status  = ''
  filters.type    = ''
  currentPage.value = 1
  emitFilters()
}

function emitFilters() {
  emit('filter-change', { ...filters })
}

let searchTimer: ReturnType<typeof setTimeout> | null = null
watch(() => filters.search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => { currentPage.value = 1; emitFilters() }, 350)
})

function toggleSort(key: string) {
  if (filters.sortBy === key) {
    filters.sortDir = filters.sortDir === 'asc' ? 'desc' : 'asc'
  } else {
    filters.sortBy  = key
    filters.sortDir = 'desc'
  }
  emit('sort-change', filters.sortBy, filters.sortDir)
}

function sortIcon(key: string): string {
  if (filters.sortBy !== key) return '↕'
  return filters.sortDir === 'asc' ? '↑' : '↓'
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  emit('page-change', page)
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   CAMPAIGN DETAIL MODAL
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function openCampaign(c: Campaign) {
  selectedCampaign.value  = c
  showCampaignModal.value = true
  emit('campaign-click', c)
}

function closeCampaignModal() {
  showCampaignModal.value = false
  selectedCampaign.value  = null
}

function openBlogger(b: BloggerCollab) {
  selectedBlogger.value  = b
  showBloggerModal.value = true
  emit('blogger-click', b)
}

function closeBloggerModal() {
  showBloggerModal.value = false
  selectedBlogger.value  = null
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   FULLSCREEN
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    rootEl.value?.requestFullscreen?.()
    isFullscreen.value = true
  } else {
    document.exitFullscreen?.()
    isFullscreen.value = false
  }
}

function onFullscreenChange() {
  isFullscreen.value = !!document.fullscreenElement
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   CHART HELPERS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function barHeight(val: number): string {
  return `${Math.max(4, (val / maxAdDaySpent.value) * 100)}%`
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   INTERSECTION OBSERVER
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

let observer: IntersectionObserver | null = null

function setupObserver() {
  if (!scrollSentinel.value) return
  observer = new IntersectionObserver(([entry]) => {
    if (entry.isIntersecting && !props.loading && currentPage.value < totalPages.value) {
      emit('load-more')
    }
  }, { rootMargin: '200px' })
  observer.observe(scrollSentinel.value)
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   RIPPLE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function ripple(e: MouseEvent) {
  const target = e.currentTarget as HTMLElement
  const rect   = target.getBoundingClientRect()
  const diameter = Math.max(rect.width, rect.height) * 2
  const el = document.createElement('span')
  el.className = 'absolute rounded-full bg-white/10 pointer-events-none animate-[ripple-mk_0.6s_ease-out]'
  el.style.cssText = `inline-size:${diameter}px;block-size:${diameter}px;inset-inline-start:${e.clientX - rect.left - diameter / 2}px;inset-block-start:${e.clientY - rect.top - diameter / 2}px;`
  target.appendChild(el)
  setTimeout(() => el.remove(), 650)
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   FORMATTERS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function fmtMoney(n: number): string {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M ₽'
  if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K ₽'
  return n.toLocaleString('ru-RU') + ' ₽'
}

function fmtNum(n: number): string {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
  return n.toLocaleString('ru-RU')
}

function fmtPercent(n: number): string {
  return n.toFixed(1) + '%'
}

function fmtDate(iso: string): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })
}

function fmtDateFull(iso: string): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', year: 'numeric' })
}

function trendClass(v: number): string {
  if (v > 0) return 'text-emerald-400'
  if (v < 0) return 'text-rose-400'
  return 'text-(--t-text-3)'
}

function trendArrow(v: number): string {
  if (v > 0) return '↑'
  if (v < 0) return '↓'
  return '→'
}

function budgetBarColor(spent: number, budget: number): string {
  const ratio = budget > 0 ? spent / budget : 0
  if (ratio >= 0.9) return 'bg-rose-500'
  if (ratio >= 0.7) return 'bg-amber-500'
  return 'bg-emerald-500'
}

function followersLabel(n: number): string {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000)     return (n / 1_000).toFixed(0) + 'K'
  return String(n)
}

function avatarInitials(name: string): string {
  return name.split(' ').slice(0, 2).map(w => w.charAt(0)).join('').toUpperCase()
}

const AVATAR_COLORS = [
  '#6366f1', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6',
  '#ec4899', '#14b8a6', '#f97316', '#06b6d4', '#84cc16',
]

function avatarColor(id: number | string): string {
  const idx = typeof id === 'number' ? id : String(id).charCodeAt(0)
  return AVATAR_COLORS[idx % AVATAR_COLORS.length]
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   LIFECYCLE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

onMounted(() => {
  document.addEventListener('fullscreenchange', onFullscreenChange)
  nextTick(() => setupObserver())
})

onBeforeUnmount(() => {
  document.removeEventListener('fullscreenchange', onFullscreenChange)
  observer?.disconnect()
  if (searchTimer) clearTimeout(searchTimer)
})
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
         1. HEADER: TITLE + PERIOD + BUDGET + ACTIONS
    ═══════════════════════════════════════════════ -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <!-- Inline-start: Title -->
      <div class="flex items-center gap-3">
        <span class="text-2xl">{{ vm.icon }}</span>
        <div>
          <h1 class="text-xl font-bold text-(--t-text)">{{ vm.label }}</h1>
          <p class="text-xs text-(--t-text-3)">
            {{ auth.tenantName }} · {{ auth.isB2BMode ? 'B2B' : 'B2C' }}
            <span class="ml-2">💰 Бюджет: <b class="text-(--t-text)">{{ fmtMoney(props.stats.totalBudget) }}</b></span>
          </p>
        </div>
      </div>

      <!-- Inline-end: Period + Create + Fullscreen -->
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

        <!-- Create campaign -->
        <VButton variant="primary" size="sm" @click="showCreateModal = true">
          🚀 Создать кампанию
        </VButton>

        <!-- Export menu -->
        <div class="relative">
          <button
            class="w-9 h-9 rounded-xl flex items-center justify-center
                   bg-(--t-surface) border border-(--t-border) text-(--t-text-2)
                   hover:text-(--t-text) hover:border-(--t-primary)/40
                   transition-all duration-200 active:scale-95"
            @click="showExportMenu = !showExportMenu"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </button>
          <Transition name="dropdown-mk">
            <div
              v-if="showExportMenu"
              class="absolute inset-inline-end-0 mt-2 w-44 rounded-xl border border-(--t-border)
                     bg-(--t-surface) backdrop-blur-xl shadow-2xl z-50 py-1 overflow-hidden"
            >
              <button class="w-full text-start px-3 py-2 text-xs text-(--t-text-2) hover:bg-(--t-card-hover) transition-colors"
                      @click="emit('export', 'xlsx'); showExportMenu = false">📊 Экспорт XLSX</button>
              <button class="w-full text-start px-3 py-2 text-xs text-(--t-text-2) hover:bg-(--t-card-hover) transition-colors"
                      @click="emit('export', 'csv'); showExportMenu = false">📄 Экспорт CSV</button>
              <button class="w-full text-start px-3 py-2 text-xs text-(--t-text-2) hover:bg-(--t-card-hover) transition-colors"
                      @click="emit('export', 'pdf'); showExportMenu = false">🖨️ Отчёт PDF</button>
            </div>
          </Transition>
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
      <!-- Active campaigns -->
      <div
        class="group relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
               backdrop-blur-xl p-4 transition-all duration-300
               hover:border-emerald-500/30 hover:shadow-[0_0_25px_rgba(16,185,129,.08)] cursor-pointer"
        @click="ripple($event)"
      >
        <div class="text-[10px] uppercase tracking-widest text-(--t-text-3) font-semibold">
          Активных кампаний
        </div>
        <div class="text-2xl font-black text-emerald-400 mt-2 tabular-nums">
          {{ props.stats.activeCampaigns }}
        </div>
        <div class="flex items-center gap-1 mt-1.5">
          <span class="text-xs text-(--t-text-3)">
            из {{ props.stats.totalCampaigns }} всего
          </span>
        </div>
        <div class="absolute inset-block-start-0 inset-inline-end-0 w-20 h-20 rounded-full bg-emerald-500/5 blur-2xl
                    group-hover:bg-emerald-500/10 transition-all duration-500" />
      </div>

      <!-- Budget spent -->
      <div
        class="group relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
               backdrop-blur-xl p-4 transition-all duration-300
               hover:border-amber-500/30 hover:shadow-[0_0_25px_rgba(245,158,11,.08)] cursor-pointer"
        @click="ripple($event)"
      >
        <div class="text-[10px] uppercase tracking-widest text-(--t-text-3) font-semibold">
          Потрачено
        </div>
        <div class="text-2xl font-black text-amber-400 mt-2 tabular-nums">
          {{ fmtMoney(animatedSpent) }}
        </div>
        <div class="flex items-center gap-2 mt-1.5">
          <div class="flex-1 h-1.5 rounded-full bg-(--t-border) overflow-hidden">
            <div
              :class="['h-full rounded-full transition-all duration-700', budgetBarColor(props.stats.totalSpent, props.stats.totalBudget)]"
              :style="{ inlineSize: budgetUsedPercent + '%' }"
            />
          </div>
          <span class="text-[10px] text-(--t-text-3) tabular-nums shrink-0">{{ budgetUsedPercent }}%</span>
        </div>
        <div class="absolute inset-block-start-0 inset-inline-end-0 w-20 h-20 rounded-full bg-amber-500/5 blur-2xl
                    group-hover:bg-amber-500/10 transition-all duration-500" />
      </div>

      <!-- ROAS -->
      <div
        class="group relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
               backdrop-blur-xl p-4 transition-all duration-300
               hover:border-violet-500/30 hover:shadow-[0_0_25px_rgba(139,92,246,.08)] cursor-pointer"
        @click="ripple($event)"
      >
        <div class="text-[10px] uppercase tracking-widest text-(--t-text-3) font-semibold">
          ROAS
        </div>
        <div class="text-2xl font-black text-violet-400 mt-2 tabular-nums">
          {{ (animatedRoas / 100).toFixed(2) }}x
        </div>
        <div class="flex items-center gap-1 mt-1.5">
          <span class="text-xs text-(--t-text-3)">
            Доход: <b class="text-violet-300">{{ fmtMoney(props.stats.adRevenue) }}</b>
          </span>
        </div>
        <div class="absolute inset-block-start-0 inset-inline-end-0 w-20 h-20 rounded-full bg-violet-500/5 blur-2xl
                    group-hover:bg-violet-500/10 transition-all duration-500" />
      </div>

      <!-- CTR -->
      <div
        class="group relative overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface)
               backdrop-blur-xl p-4 transition-all duration-300
               hover:border-sky-500/30 hover:shadow-[0_0_25px_rgba(14,165,233,.08)] cursor-pointer"
        @click="ripple($event)"
      >
        <div class="text-[10px] uppercase tracking-widest text-(--t-text-3) font-semibold">
          Средний CTR
        </div>
        <div class="text-2xl font-black text-sky-400 mt-2 tabular-nums">
          {{ fmtPercent(props.stats.avgCtr) }}
        </div>
        <div class="flex items-center gap-2 mt-1.5 text-[10px] text-(--t-text-3)">
          <span>Клики: <b class="text-(--t-text)">{{ fmtNum(props.stats.totalClicks) }}</b></span>
          <span>CPC: <b class="text-(--t-text)">{{ fmtMoney(props.stats.avgCpc) }}</b></span>
        </div>
        <div class="absolute inset-block-start-0 inset-inline-end-0 w-20 h-20 rounded-full bg-sky-500/5 blur-2xl
                    group-hover:bg-sky-500/10 transition-all duration-500" />
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════
         3. SECONDARY KPI ROW
    ═══════════════════════════════════════════════ -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <!-- Impressions -->
      <div class="rounded-xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-3 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-indigo-500/10 text-indigo-400 text-lg shrink-0">👁️</div>
        <div class="min-w-0">
          <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Показы</div>
          <div class="text-sm font-bold text-indigo-400 tabular-nums">{{ fmtNum(props.stats.totalImpressions) }}</div>
          <div class="flex items-center gap-1">
            <span :class="[trendClass(props.stats.impressionGrowth), 'text-[10px] font-bold']">
              {{ trendArrow(props.stats.impressionGrowth) }} {{ fmtPercent(props.stats.impressionGrowth) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Conversions -->
      <div class="rounded-xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-3 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-emerald-500/10 text-emerald-400 text-lg shrink-0">🎯</div>
        <div class="min-w-0">
          <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Конверсии</div>
          <div class="text-sm font-bold text-emerald-400 tabular-nums">{{ fmtNum(props.stats.totalConversions) }}</div>
          <div class="flex items-center gap-1">
            <span :class="[trendClass(props.stats.conversionGrowth), 'text-[10px] font-bold']">
              {{ trendArrow(props.stats.conversionGrowth) }} {{ fmtPercent(props.stats.conversionGrowth) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Ad Revenue -->
      <div class="rounded-xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-3 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-teal-500/10 text-teal-400 text-lg shrink-0">💸</div>
        <div class="min-w-0">
          <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Выручка с рекламы</div>
          <div class="text-sm font-bold text-teal-400 tabular-nums">{{ fmtMoney(animatedRevenue) }}</div>
          <div class="flex items-center gap-1">
            <span :class="[trendClass(props.stats.adRevenueGrowth), 'text-[10px] font-bold']">
              {{ trendArrow(props.stats.adRevenueGrowth) }} {{ fmtPercent(props.stats.adRevenueGrowth) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Blogger Revenue -->
      <div class="rounded-xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-3 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-pink-500/10 text-pink-400 text-lg shrink-0">📣</div>
        <div class="min-w-0">
          <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Блогеры: доход</div>
          <div class="text-sm font-bold text-pink-400 tabular-nums">{{ fmtMoney(props.stats.bloggerRevenue) }}</div>
          <div class="text-[10px] text-(--t-text-3)">
            Потрачено: {{ fmtMoney(props.stats.bloggerSpent) }}
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════
         4. AD PERFORMANCE CHART (CSS bar chart)
    ═══════════════════════════════════════════════ -->
    <VCard title="📈 Динамика рекламы" glow>
      <template v-if="props.adDays.length">
        <div class="flex items-end gap-px" style="block-size: 180px">
          <div
            v-for="(day, idx) in props.adDays"
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
            <!-- Spent bar -->
            <div
              class="w-full rounded-t-sm bg-amber-500/40 -mt-px transition-all duration-300
                     group-hover/bar:bg-amber-400/60"
              :style="{ blockSize: barHeight(day.spent) }"
            />
            <!-- Tooltip -->
            <div
              class="absolute inset-block-end-full mb-2 inset-inline-start-1/2 -translate-x-1/2 opacity-0
                     group-hover/bar:opacity-100 transition-opacity pointer-events-none z-20
                     bg-(--t-surface) border border-(--t-border) rounded-lg px-2 py-1.5 text-[10px]
                     whitespace-nowrap shadow-lg backdrop-blur-xl"
            >
              <div class="font-bold text-(--t-text)">{{ fmtDate(day.date) }}</div>
              <div class="text-emerald-400">Доход: {{ fmtMoney(day.revenue) }}</div>
              <div class="text-amber-400">Расход: {{ fmtMoney(day.spent) }}</div>
              <div class="text-sky-400">Клики: {{ fmtNum(day.clicks) }}</div>
              <div class="text-violet-400">Конверсии: {{ fmtNum(day.conversions) }}</div>
            </div>
            <!-- Date label -->
            <div
              v-if="idx % Math.max(1, Math.floor(props.adDays.length / 7)) === 0"
              class="text-[8px] text-(--t-text-3) mt-1 whitespace-nowrap"
            >
              {{ fmtDate(day.date) }}
            </div>
          </div>
        </div>
        <!-- Legend -->
        <div class="flex items-center gap-4 mt-3 text-[10px] text-(--t-text-3)">
          <span class="flex items-center gap-1"><span class="inline-block w-3 h-2 rounded-xs bg-emerald-500/60" /> Доход</span>
          <span class="flex items-center gap-1"><span class="inline-block w-3 h-2 rounded-xs bg-amber-500/40" /> Расход</span>
        </div>
      </template>
      <div v-else class="text-center py-10 text-(--t-text-3) text-sm">Нет данных за период</div>
    </VCard>

    <!-- ═══════════════════════════════════════════════
         5. TABS: CAMPAIGNS / BLOGGERS / CHANNELS
    ═══════════════════════════════════════════════ -->
    <div class="flex items-center gap-1 rounded-xl border border-(--t-border) overflow-hidden self-start">
      <button
        v-for="tab in [
          { key: 'campaigns', label: '📋 Кампании',   count: props.campaigns.length },
          { key: 'bloggers',  label: '📣 Блогеры',    count: props.bloggers.length  },
          { key: 'channels',  label: '📊 Каналы',     count: props.channels.length  },
        ]"
        :key="tab.key"
        :class="[
          'px-4 py-2 text-xs font-medium transition-all duration-200',
          activeTab === tab.key
            ? 'bg-(--t-primary)/15 text-(--t-primary)'
            : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
        ]"
        @click="activeTab = tab.key as typeof activeTab"
      >
        {{ tab.label }}
        <span class="ml-1 text-[10px] opacity-70">({{ tab.count }})</span>
      </button>
    </div>

    <!-- ═══════════════════════════════════════════════
         5a. TAB: CAMPAIGNS TABLE
    ═══════════════════════════════════════════════ -->
    <template v-if="activeTab === 'campaigns'">
      <!-- Toolbar: Search + Filters -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <!-- Search -->
        <div class="relative flex-1 max-w-md">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Поиск: название кампании..."
            class="w-full h-10 rounded-xl pl-10 pr-4 text-sm bg-(--t-surface) border border-(--t-border)
                   text-(--t-text) placeholder:text-(--t-text-3)
                   focus:outline-none focus:border-(--t-primary)/60
                   focus:shadow-[0_0_20px_var(--t-glow)]
                   transition-all duration-200"
          />
          <svg class="absolute inset-inline-start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-(--t-text-3)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <!-- Filter pills -->
        <div class="flex items-center gap-2 flex-wrap">
          <!-- Status filter -->
          <select
            :value="filters.status"
            class="h-9 rounded-xl px-3 text-xs bg-(--t-surface) border border-(--t-border)
                   text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all"
            @change="applyFilter('status', ($event.target as HTMLSelectElement).value)"
          >
            <option value="">Все статусы</option>
            <option v-for="s in ALL_STATUSES" :key="s" :value="s">
              {{ CAMPAIGN_STATUS_MAP[s].label }} ({{ statusCountMap[s] }})
            </option>
          </select>

          <!-- Type filter -->
          <select
            :value="filters.type"
            class="h-9 rounded-xl px-3 text-xs bg-(--t-surface) border border-(--t-border)
                   text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all"
            @change="applyFilter('type', ($event.target as HTMLSelectElement).value)"
          >
            <option value="">Все типы</option>
            <option v-for="t in ALL_TYPES" :key="t" :value="t">
              {{ CAMPAIGN_TYPE_MAP[t].icon }} {{ CAMPAIGN_TYPE_MAP[t].label }} ({{ typeCountMap[t] }})
            </option>
          </select>

          <!-- Clear filters -->
          <button
            v-if="hasActiveFilters"
            class="h-9 px-3 rounded-xl text-xs text-rose-400 border border-rose-500/20
                   hover:bg-rose-500/10 transition-all"
            @click="clearAllFilters"
          >
            ✕ Сбросить
          </button>
        </div>
      </div>

      <!-- Desktop table -->
      <div class="hidden md:block overflow-x-auto rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-(--t-border) text-(--t-text-3)">
              <th class="text-start py-3 px-4 text-xs font-semibold">Название</th>
              <th class="text-start py-3 px-3 text-xs font-semibold cursor-pointer select-none" @click="toggleSort('type')">
                Тип {{ sortIcon('type') }}
              </th>
              <th class="text-start py-3 px-3 text-xs font-semibold">Статус</th>
              <th class="text-end py-3 px-3 text-xs font-semibold cursor-pointer select-none" @click="toggleSort('budget')">
                Бюджет {{ sortIcon('budget') }}
              </th>
              <th class="text-end py-3 px-3 text-xs font-semibold cursor-pointer select-none" @click="toggleSort('spent')">
                Расход {{ sortIcon('spent') }}
              </th>
              <th class="text-end py-3 px-3 text-xs font-semibold cursor-pointer select-none" @click="toggleSort('clicks')">
                Клики {{ sortIcon('clicks') }}
              </th>
              <th class="text-end py-3 px-3 text-xs font-semibold cursor-pointer select-none" @click="toggleSort('ctr')">
                CTR {{ sortIcon('ctr') }}
              </th>
              <th class="text-end py-3 px-3 text-xs font-semibold cursor-pointer select-none" @click="toggleSort('roas')">
                ROAS {{ sortIcon('roas') }}
              </th>
              <th class="text-end py-3 px-3 text-xs font-semibold">Даты</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="c in props.campaigns"
              :key="c.id"
              class="border-b border-(--t-border)/40 hover:bg-(--t-card-hover) transition-colors cursor-pointer group/row"
              @click="openCampaign(c)"
            >
              <td class="py-3 px-4">
                <div class="font-medium text-(--t-text) group-hover/row:text-(--t-primary) transition-colors truncate max-w-56">
                  {{ c.name }}
                </div>
              </td>
              <td class="py-3 px-3">
                <span class="text-xs">
                  {{ CAMPAIGN_TYPE_MAP[c.type]?.icon }} {{ CAMPAIGN_TYPE_MAP[c.type]?.label }}
                </span>
              </td>
              <td class="py-3 px-3">
                <span class="inline-flex items-center gap-1.5 text-xs">
                  <span :class="['w-2 h-2 rounded-full shrink-0', CAMPAIGN_STATUS_MAP[c.status]?.dot]" />
                  {{ CAMPAIGN_STATUS_MAP[c.status]?.label }}
                </span>
              </td>
              <td class="py-3 px-3 text-end tabular-nums text-(--t-text-2)">{{ fmtMoney(c.budget) }}</td>
              <td class="py-3 px-3 text-end">
                <div class="tabular-nums text-(--t-text-2)">{{ fmtMoney(c.spent) }}</div>
                <div class="w-16 h-1 rounded-full bg-(--t-border) mt-1 ml-auto overflow-hidden">
                  <div
                    :class="['h-full rounded-full', budgetBarColor(c.spent, c.budget)]"
                    :style="{ inlineSize: Math.min(100, Math.round((c.spent / Math.max(c.budget, 1)) * 100)) + '%' }"
                  />
                </div>
              </td>
              <td class="py-3 px-3 text-end tabular-nums text-(--t-text-2)">{{ fmtNum(c.clicks) }}</td>
              <td class="py-3 px-3 text-end tabular-nums text-sky-400">{{ fmtPercent(c.ctr) }}</td>
              <td class="py-3 px-3 text-end tabular-nums" :class="c.roas >= 1 ? 'text-emerald-400' : 'text-rose-400'">
                {{ c.roas.toFixed(2) }}x
              </td>
              <td class="py-3 px-3 text-end text-[10px] text-(--t-text-3) whitespace-nowrap">
                {{ fmtDate(c.startDate) }} — {{ fmtDate(c.endDate) }}
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty state -->
        <div v-if="!props.campaigns.length" class="text-center py-12 text-(--t-text-3)">
          <div class="text-4xl mb-3">📢</div>
          <div class="text-sm font-medium">Кампаний пока нет</div>
          <div class="text-xs mt-1">Создайте первую рекламную кампанию</div>
        </div>
      </div>

      <!-- Mobile cards -->
      <div class="flex flex-col gap-3 md:hidden">
        <div
          v-for="c in props.campaigns"
          :key="c.id"
          class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-4
                 transition-all duration-200 hover:border-(--t-primary)/30 active:scale-[0.99] cursor-pointer"
          @click="openCampaign(c)"
        >
          <div class="flex items-start justify-between gap-2 mb-3">
            <div class="min-w-0">
              <div class="font-medium text-(--t-text) truncate">{{ c.name }}</div>
              <div class="text-xs text-(--t-text-3) mt-0.5">
                {{ CAMPAIGN_TYPE_MAP[c.type]?.icon }} {{ CAMPAIGN_TYPE_MAP[c.type]?.label }}
              </div>
            </div>
            <span class="inline-flex items-center gap-1 text-[10px] font-semibold shrink-0 px-2 py-0.5 rounded-full border"
                  :class="`border-${CAMPAIGN_STATUS_MAP[c.status]?.badge}-500/30 text-${CAMPAIGN_STATUS_MAP[c.status]?.badge}-400 bg-${CAMPAIGN_STATUS_MAP[c.status]?.badge}-500/10`">
              <span :class="['w-1.5 h-1.5 rounded-full', CAMPAIGN_STATUS_MAP[c.status]?.dot]" />
              {{ CAMPAIGN_STATUS_MAP[c.status]?.label }}
            </span>
          </div>

          <!-- Budget bar -->
          <div class="flex items-center gap-2 mb-3">
            <div class="flex-1 h-1.5 rounded-full bg-(--t-border) overflow-hidden">
              <div
                :class="['h-full rounded-full transition-all', budgetBarColor(c.spent, c.budget)]"
                :style="{ inlineSize: Math.min(100, Math.round((c.spent / Math.max(c.budget, 1)) * 100)) + '%' }"
              />
            </div>
            <span class="text-[10px] text-(--t-text-3) tabular-nums shrink-0">
              {{ fmtMoney(c.spent) }} / {{ fmtMoney(c.budget) }}
            </span>
          </div>

          <!-- Metrics row -->
          <div class="grid grid-cols-4 gap-2 text-center">
            <div>
              <div class="text-[10px] text-(--t-text-3)">Клики</div>
              <div class="text-xs font-bold text-(--t-text) tabular-nums">{{ fmtNum(c.clicks) }}</div>
            </div>
            <div>
              <div class="text-[10px] text-(--t-text-3)">CTR</div>
              <div class="text-xs font-bold text-sky-400 tabular-nums">{{ fmtPercent(c.ctr) }}</div>
            </div>
            <div>
              <div class="text-[10px] text-(--t-text-3)">Конверсии</div>
              <div class="text-xs font-bold text-emerald-400 tabular-nums">{{ fmtNum(c.conversions) }}</div>
            </div>
            <div>
              <div class="text-[10px] text-(--t-text-3)">ROAS</div>
              <div class="text-xs font-bold tabular-nums" :class="c.roas >= 1 ? 'text-emerald-400' : 'text-rose-400'">
                {{ c.roas.toFixed(2) }}x
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile empty -->
        <div v-if="!props.campaigns.length" class="text-center py-10 text-(--t-text-3) text-sm">
          📢 Кампаний пока нет
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex items-center justify-center gap-1 mt-2">
        <button
          class="w-8 h-8 rounded-lg flex items-center justify-center text-xs text-(--t-text-3)
                 hover:bg-(--t-card-hover) transition disabled:opacity-30"
          :disabled="currentPage <= 1"
          @click="goToPage(currentPage - 1)"
        >‹</button>
        <template v-for="p in visiblePages" :key="p">
          <button
            v-if="p !== 'dots'"
            :class="[
              'w-8 h-8 rounded-lg flex items-center justify-center text-xs transition',
              p === currentPage
                ? 'bg-(--t-primary)/15 text-(--t-primary) font-bold'
                : 'text-(--t-text-3) hover:bg-(--t-card-hover)',
            ]"
            @click="goToPage(p as number)"
          >{{ p }}</button>
          <span v-else class="w-6 text-center text-(--t-text-3)">…</span>
        </template>
        <button
          class="w-8 h-8 rounded-lg flex items-center justify-center text-xs text-(--t-text-3)
                 hover:bg-(--t-card-hover) transition disabled:opacity-30"
          :disabled="currentPage >= totalPages"
          @click="goToPage(currentPage + 1)"
        >›</button>
      </div>

      <!-- Infinite scroll sentinel -->
      <div ref="scrollSentinel" class="h-1" />
    </template>

    <!-- ═══════════════════════════════════════════════
         5b. TAB: BLOGGERS (Реклама у блогеров через Экосистему)
    ═══════════════════════════════════════════════ -->
    <template v-if="activeTab === 'bloggers'">
      <!-- Blogger header -->
      <div class="rounded-2xl border border-pink-500/20 bg-pink-500/5 backdrop-blur-xl p-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h2 class="text-lg font-bold text-(--t-text) flex items-center gap-2">
              🐱 Реклама у блогеров через Экосистему Кота
            </h2>
            <p class="text-xs text-(--t-text-3) mt-1 max-w-lg">
              Размещайте рекламу у проверенных блогеров напрямую через платформу.
              Автоматический подбор по аудитории, гарантия показов, прозрачная статистика.
            </p>
          </div>
          <VButton variant="primary" size="sm" @click="emit('blogger-request', { vertical: props.vertical })">
            🤝 Найти блогера
          </VButton>
        </div>

        <!-- Blogger stats mini -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
          <div class="rounded-xl bg-(--t-surface)/60 border border-(--t-border) p-3 text-center">
            <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Активных</div>
            <div class="text-lg font-bold text-pink-400 mt-1">
              {{ props.bloggers.filter(b => b.status === 'active').length }}
            </div>
          </div>
          <div class="rounded-xl bg-(--t-surface)/60 border border-(--t-border) p-3 text-center">
            <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Потрачено</div>
            <div class="text-lg font-bold text-amber-400 mt-1">{{ fmtMoney(props.stats.bloggerSpent) }}</div>
          </div>
          <div class="rounded-xl bg-(--t-surface)/60 border border-(--t-border) p-3 text-center">
            <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Доход</div>
            <div class="text-lg font-bold text-emerald-400 mt-1">{{ fmtMoney(props.stats.bloggerRevenue) }}</div>
          </div>
          <div class="rounded-xl bg-(--t-surface)/60 border border-(--t-border) p-3 text-center">
            <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">ROAS блогеров</div>
            <div class="text-lg font-bold text-violet-400 mt-1">
              {{ props.stats.bloggerSpent > 0 ? (props.stats.bloggerRevenue / props.stats.bloggerSpent).toFixed(2) : '0.00' }}x
            </div>
          </div>
        </div>
      </div>

      <!-- Blogger cards grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div
          v-for="b in props.bloggers"
          :key="b.id"
          class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-4
                 transition-all duration-200 hover:border-pink-500/30 hover:shadow-[0_0_25px_rgba(236,72,153,.06)]
                 cursor-pointer group/blog"
          @click="openBlogger(b)"
        >
          <!-- Blogger header -->
          <div class="flex items-center gap-3 mb-3">
            <div
              v-if="b.avatarUrl"
              class="w-11 h-11 rounded-full bg-cover bg-center border-2 border-(--t-border)
                     group-hover/blog:border-pink-500/40 transition-all shrink-0"
              :style="{ backgroundImage: `url(${b.avatarUrl})` }"
            />
            <div
              v-else
              class="w-11 h-11 rounded-full flex items-center justify-center text-sm font-bold text-white shrink-0
                     group-hover/blog:ring-2 group-hover/blog:ring-pink-500/30 transition-all"
              :style="{ backgroundColor: avatarColor(b.id) }"
            >
              {{ avatarInitials(b.bloggerName) }}
            </div>
            <div class="min-w-0 flex-1">
              <div class="font-medium text-(--t-text) truncate group-hover/blog:text-pink-400 transition-colors">
                {{ b.bloggerName }}
              </div>
              <div class="text-[10px] text-(--t-text-3)">
                {{ b.platform }} · {{ followersLabel(b.followers) }} подписчиков
              </div>
            </div>
            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full shrink-0"
                  :class="`bg-${BLOGGER_STATUS_MAP[b.status]?.badge}-500/10 text-${BLOGGER_STATUS_MAP[b.status]?.badge}-400 border border-${BLOGGER_STATUS_MAP[b.status]?.badge}-500/20`">
              {{ BLOGGER_STATUS_MAP[b.status]?.label }}
            </span>
          </div>

          <!-- Metrics -->
          <div class="grid grid-cols-3 gap-2 text-center border-t border-(--t-border)/40 pt-3">
            <div>
              <div class="text-[10px] text-(--t-text-3)">Охват</div>
              <div class="text-xs font-bold text-indigo-400 tabular-nums">{{ fmtNum(b.reach) }}</div>
            </div>
            <div>
              <div class="text-[10px] text-(--t-text-3)">Вовлеч.</div>
              <div class="text-xs font-bold text-pink-400 tabular-nums">{{ fmtPercent(b.engagement) }}</div>
            </div>
            <div>
              <div class="text-[10px] text-(--t-text-3)">Конверсии</div>
              <div class="text-xs font-bold text-emerald-400 tabular-nums">{{ fmtNum(b.conversions) }}</div>
            </div>
          </div>

          <!-- Budget -->
          <div class="mt-3 flex items-center justify-between text-[10px]">
            <span class="text-(--t-text-3)">Бюджет: <b class="text-amber-400">{{ fmtMoney(b.budget) }}</b></span>
            <span class="text-(--t-text-3)">Доход: <b class="text-emerald-400">{{ fmtMoney(b.revenue) }}</b></span>
          </div>
        </div>

        <!-- Empty bloggers -->
        <div v-if="!props.bloggers.length" class="col-span-full text-center py-12 text-(--t-text-3)">
          <div class="text-4xl mb-3">🐱</div>
          <div class="text-sm font-medium">Нет коллабораций с блогерами</div>
          <div class="text-xs mt-1">Найдите блогеров через Экосистему Кота</div>
        </div>
      </div>
    </template>

    <!-- ═══════════════════════════════════════════════
         5c. TAB: CHANNELS BREAKDOWN
    ═══════════════════════════════════════════════ -->
    <template v-if="activeTab === 'channels'">
      <div class="overflow-x-auto rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-(--t-border) text-(--t-text-3)">
              <th class="text-start py-3 px-4 text-xs font-semibold">Канал</th>
              <th class="text-end py-3 px-3 text-xs font-semibold">Бюджет</th>
              <th class="text-end py-3 px-3 text-xs font-semibold">Расход</th>
              <th class="text-end py-3 px-3 text-xs font-semibold">Показы</th>
              <th class="text-end py-3 px-3 text-xs font-semibold">Клики</th>
              <th class="text-end py-3 px-3 text-xs font-semibold">CTR</th>
              <th class="text-end py-3 px-3 text-xs font-semibold">Конверсии</th>
              <th class="text-end py-3 px-3 text-xs font-semibold">Доход</th>
              <th class="text-end py-3 px-3 text-xs font-semibold">ROAS</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="ch in props.channels"
              :key="ch.channel"
              class="border-b border-(--t-border)/40 hover:bg-(--t-card-hover) transition-colors"
            >
              <td class="py-3 px-4">
                <span class="flex items-center gap-2 font-medium text-(--t-text)">
                  <span>{{ ch.icon }}</span>
                  {{ ch.channel }}
                </span>
              </td>
              <td class="py-3 px-3 text-end tabular-nums text-(--t-text-2)">{{ fmtMoney(ch.budget) }}</td>
              <td class="py-3 px-3 text-end tabular-nums text-amber-400">{{ fmtMoney(ch.spent) }}</td>
              <td class="py-3 px-3 text-end tabular-nums text-(--t-text-2)">{{ fmtNum(ch.impressions) }}</td>
              <td class="py-3 px-3 text-end tabular-nums text-(--t-text-2)">{{ fmtNum(ch.clicks) }}</td>
              <td class="py-3 px-3 text-end tabular-nums text-sky-400">{{ fmtPercent(ch.ctr) }}</td>
              <td class="py-3 px-3 text-end tabular-nums text-emerald-400">{{ fmtNum(ch.conversions) }}</td>
              <td class="py-3 px-3 text-end tabular-nums text-teal-400">{{ fmtMoney(ch.revenue) }}</td>
              <td class="py-3 px-3 text-end tabular-nums font-bold"
                  :class="ch.roas >= 1 ? 'text-emerald-400' : 'text-rose-400'">
                {{ ch.roas.toFixed(2) }}x
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty channels -->
        <div v-if="!props.channels.length" class="text-center py-12 text-(--t-text-3)">
          <div class="text-4xl mb-3">📊</div>
          <div class="text-sm font-medium">Нет данных по каналам</div>
        </div>
      </div>
    </template>

    <!-- ═══════════════════════════════════════════════
         6. CAMPAIGN DETAIL MODAL
    ═══════════════════════════════════════════════ -->
    <VModal
      :show="showCampaignModal"
      size="lg"
      @close="closeCampaignModal"
    >
      <template #title>
        <span v-if="selectedCampaign">
          {{ CAMPAIGN_TYPE_MAP[selectedCampaign.type]?.icon }} {{ selectedCampaign.name }}
        </span>
      </template>
      <template #default>
        <div v-if="selectedCampaign" class="flex flex-col gap-4">
          <!-- Status + dates -->
          <div class="flex items-center gap-3 flex-wrap">
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full"
                  :class="`bg-${CAMPAIGN_STATUS_MAP[selectedCampaign.status]?.badge}-500/10 text-${CAMPAIGN_STATUS_MAP[selectedCampaign.status]?.badge}-400 border border-${CAMPAIGN_STATUS_MAP[selectedCampaign.status]?.badge}-500/20`">
              <span :class="['w-2 h-2 rounded-full', CAMPAIGN_STATUS_MAP[selectedCampaign.status]?.dot]" />
              {{ CAMPAIGN_STATUS_MAP[selectedCampaign.status]?.label }}
            </span>
            <span class="text-xs text-(--t-text-3)">
              {{ fmtDateFull(selectedCampaign.startDate) }} — {{ fmtDateFull(selectedCampaign.endDate) }}
            </span>
          </div>

          <!-- KPI grid -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="rounded-xl border border-(--t-border) bg-(--t-surface)/50 p-3 text-center">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Бюджет</div>
              <div class="text-lg font-bold text-(--t-text) mt-1 tabular-nums">{{ fmtMoney(selectedCampaign.budget) }}</div>
            </div>
            <div class="rounded-xl border border-(--t-border) bg-(--t-surface)/50 p-3 text-center">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Потрачено</div>
              <div class="text-lg font-bold text-amber-400 mt-1 tabular-nums">{{ fmtMoney(selectedCampaign.spent) }}</div>
              <div class="w-full h-1.5 rounded-full bg-(--t-border) mt-2 overflow-hidden">
                <div
                  :class="['h-full rounded-full', budgetBarColor(selectedCampaign.spent, selectedCampaign.budget)]"
                  :style="{ inlineSize: Math.min(100, Math.round((selectedCampaign.spent / Math.max(selectedCampaign.budget, 1)) * 100)) + '%' }"
                />
              </div>
            </div>
            <div class="rounded-xl border border-(--t-border) bg-(--t-surface)/50 p-3 text-center">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Показы</div>
              <div class="text-lg font-bold text-indigo-400 mt-1 tabular-nums">{{ fmtNum(selectedCampaign.impressions) }}</div>
            </div>
            <div class="rounded-xl border border-(--t-border) bg-(--t-surface)/50 p-3 text-center">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Доход</div>
              <div class="text-lg font-bold text-emerald-400 mt-1 tabular-nums">{{ fmtMoney(selectedCampaign.revenue) }}</div>
            </div>
          </div>

          <!-- Detailed metrics -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="rounded-xl border border-(--t-border) p-3">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Клики</div>
              <div class="text-sm font-bold text-(--t-text) mt-1 tabular-nums">{{ fmtNum(selectedCampaign.clicks) }}</div>
            </div>
            <div class="rounded-xl border border-(--t-border) p-3">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">CTR</div>
              <div class="text-sm font-bold text-sky-400 mt-1 tabular-nums">{{ fmtPercent(selectedCampaign.ctr) }}</div>
            </div>
            <div class="rounded-xl border border-(--t-border) p-3">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">CPC</div>
              <div class="text-sm font-bold text-(--t-text) mt-1 tabular-nums">{{ fmtMoney(selectedCampaign.cpc) }}</div>
            </div>
            <div class="rounded-xl border border-(--t-border) p-3">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">ROAS</div>
              <div class="text-sm font-bold mt-1 tabular-nums"
                   :class="selectedCampaign.roas >= 1 ? 'text-emerald-400' : 'text-rose-400'">
                {{ selectedCampaign.roas.toFixed(2) }}x
              </div>
            </div>
          </div>

          <!-- Targeting -->
          <div class="rounded-xl border border-(--t-border) p-3">
            <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Таргетинг</div>
            <div class="text-xs text-(--t-text-2) mt-1">{{ selectedCampaign.targeting || '—' }}</div>
          </div>

          <!-- Correlation ID -->
          <div class="rounded-xl border border-(--t-border) p-3">
            <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Correlation ID</div>
            <div class="text-xs text-(--t-text-3) mt-1 font-mono truncate">{{ selectedCampaign.correlationId }}</div>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex items-center gap-2 flex-wrap">
          <VButton variant="ghost" size="sm" @click="closeCampaignModal">Закрыть</VButton>
          <VButton
            v-if="selectedCampaign?.status === 'active'"
            variant="secondary" size="sm"
            @click="emit('campaign-pause', selectedCampaign!.id); closeCampaignModal()"
          >⏸️ Пауза</VButton>
          <VButton
            v-if="selectedCampaign?.status === 'paused'"
            variant="primary" size="sm"
            @click="emit('campaign-resume', selectedCampaign!.id); closeCampaignModal()"
          >▶️ Возобновить</VButton>
          <VButton variant="secondary" size="sm"
                   @click="emit('campaign-duplicate', selectedCampaign!.id); closeCampaignModal()">
            📋 Дублировать
          </VButton>
        </div>
      </template>
    </VModal>

    <!-- ═══════════════════════════════════════════════
         7. BLOGGER DETAIL MODAL
    ═══════════════════════════════════════════════ -->
    <VModal
      :show="showBloggerModal"
      size="md"
      @close="closeBloggerModal"
    >
      <template #title>
        <span v-if="selectedBlogger">📣 {{ selectedBlogger.bloggerName }}</span>
      </template>
      <template #default>
        <div v-if="selectedBlogger" class="flex flex-col gap-4">
          <!-- Avatar + info -->
          <div class="flex items-center gap-4">
            <div
              v-if="selectedBlogger.avatarUrl"
              class="w-16 h-16 rounded-full bg-cover bg-center border-2 border-(--t-border) shrink-0"
              :style="{ backgroundImage: `url(${selectedBlogger.avatarUrl})` }"
            />
            <div
              v-else
              class="w-16 h-16 rounded-full flex items-center justify-center text-xl font-bold text-white shrink-0"
              :style="{ backgroundColor: avatarColor(selectedBlogger.id) }"
            >
              {{ avatarInitials(selectedBlogger.bloggerName) }}
            </div>
            <div>
              <div class="font-bold text-(--t-text) text-lg">{{ selectedBlogger.bloggerName }}</div>
              <div class="text-xs text-(--t-text-3)">
                {{ selectedBlogger.platform }} · {{ followersLabel(selectedBlogger.followers) }} подписчиков
              </div>
              <span class="inline-flex items-center text-[10px] font-semibold mt-1 px-2 py-0.5 rounded-full"
                    :class="`bg-${BLOGGER_STATUS_MAP[selectedBlogger.status]?.badge}-500/10 text-${BLOGGER_STATUS_MAP[selectedBlogger.status]?.badge}-400`">
                {{ BLOGGER_STATUS_MAP[selectedBlogger.status]?.label }}
              </span>
            </div>
          </div>

          <!-- KPI -->
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div class="rounded-xl border border-(--t-border) p-3 text-center">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Бюджет</div>
              <div class="text-lg font-bold text-amber-400 mt-1 tabular-nums">{{ fmtMoney(selectedBlogger.budget) }}</div>
            </div>
            <div class="rounded-xl border border-(--t-border) p-3 text-center">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Охват</div>
              <div class="text-lg font-bold text-indigo-400 mt-1 tabular-nums">{{ fmtNum(selectedBlogger.reach) }}</div>
            </div>
            <div class="rounded-xl border border-(--t-border) p-3 text-center">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Вовлечённость</div>
              <div class="text-lg font-bold text-pink-400 mt-1 tabular-nums">{{ fmtPercent(selectedBlogger.engagement) }}</div>
            </div>
            <div class="rounded-xl border border-(--t-border) p-3 text-center">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Конверсии</div>
              <div class="text-lg font-bold text-emerald-400 mt-1 tabular-nums">{{ fmtNum(selectedBlogger.conversions) }}</div>
            </div>
            <div class="rounded-xl border border-(--t-border) p-3 text-center">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Доход</div>
              <div class="text-lg font-bold text-teal-400 mt-1 tabular-nums">{{ fmtMoney(selectedBlogger.revenue) }}</div>
            </div>
            <div class="rounded-xl border border-(--t-border) p-3 text-center">
              <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">ROAS</div>
              <div class="text-lg font-bold mt-1 tabular-nums"
                   :class="selectedBlogger.budget > 0 && selectedBlogger.revenue / selectedBlogger.budget >= 1 ? 'text-emerald-400' : 'text-rose-400'">
                {{ selectedBlogger.budget > 0 ? (selectedBlogger.revenue / selectedBlogger.budget).toFixed(2) : '0.00' }}x
              </div>
            </div>
          </div>

          <!-- Dates -->
          <div class="rounded-xl border border-(--t-border) p-3">
            <div class="text-[10px] uppercase text-(--t-text-3) tracking-wider">Период</div>
            <div class="text-xs text-(--t-text-2) mt-1">
              {{ fmtDateFull(selectedBlogger.startDate) }} — {{ fmtDateFull(selectedBlogger.endDate) }}
            </div>
          </div>
        </div>
      </template>
      <template #footer>
        <VButton variant="ghost" size="sm" @click="closeBloggerModal">Закрыть</VButton>
      </template>
    </VModal>

    <!-- ═══════════════════════════════════════════════
         8. CREATE CAMPAIGN MODAL
    ═══════════════════════════════════════════════ -->
    <VModal
      :show="showCreateModal"
      size="lg"
      @close="showCreateModal = false"
    >
      <template #title>🚀 Новая рекламная кампания</template>
      <template #default>
        <div class="flex flex-col gap-4">
          <!-- Campaign type -->
          <div>
            <label class="block text-xs font-semibold text-(--t-text-2) mb-2">Тип кампании</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
              <button
                v-for="ct in vm.campaignTypes"
                :key="ct.key"
                class="rounded-xl border border-(--t-border) bg-(--t-surface) p-3 text-center text-xs
                       transition-all duration-200 hover:border-(--t-primary)/40 hover:bg-(--t-primary)/5
                       focus:outline-none focus:ring-2 focus:ring-(--t-primary)/30"
                @click="ripple($event)"
              >
                <div class="text-xl mb-1">{{ ct.icon }}</div>
                <div class="font-medium text-(--t-text)">{{ ct.label }}</div>
              </button>
            </div>
          </div>

          <!-- Name -->
          <div>
            <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Название кампании</label>
            <input
              type="text"
              placeholder="Летняя акция..."
              class="w-full h-10 rounded-xl px-4 text-sm bg-(--t-surface) border border-(--t-border)
                     text-(--t-text) placeholder:text-(--t-text-3)
                     focus:outline-none focus:border-(--t-primary)/60 transition-all"
            />
          </div>

          <!-- Budget -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Бюджет (₽)</label>
              <input
                type="number"
                placeholder="50000"
                class="w-full h-10 rounded-xl px-4 text-sm bg-(--t-surface) border border-(--t-border)
                       text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/60 transition-all"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Дневной лимит (₽)</label>
              <input
                type="number"
                placeholder="5000"
                class="w-full h-10 rounded-xl px-4 text-sm bg-(--t-surface) border border-(--t-border)
                       text-(--t-text) placeholder:text-(--t-text-3)
                       focus:outline-none focus:border-(--t-primary)/60 transition-all"
              />
            </div>
          </div>

          <!-- Dates -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Дата начала</label>
              <input
                type="date"
                class="w-full h-10 rounded-xl px-4 text-sm bg-(--t-surface) border border-(--t-border)
                       text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Дата окончания</label>
              <input
                type="date"
                class="w-full h-10 rounded-xl px-4 text-sm bg-(--t-surface) border border-(--t-border)
                       text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all"
              />
            </div>
          </div>

          <!-- Targeting hints -->
          <div>
            <label class="block text-xs font-semibold text-(--t-text-2) mb-2">Таргетинг</label>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="hint in vm.targetingHints"
                :key="hint"
                class="px-3 py-1.5 rounded-full text-[10px] font-medium
                       border border-(--t-border) bg-(--t-surface)
                       text-(--t-text-2) hover:border-(--t-primary)/40 hover:text-(--t-primary)
                       cursor-pointer transition-all"
              >
                {{ hint }}
              </span>
            </div>
          </div>
        </div>
      </template>
      <template #footer>
        <VButton variant="ghost" size="sm" @click="showCreateModal = false">Отмена</VButton>
        <VButton variant="primary" size="sm" @click="emit('campaign-create', {}); showCreateModal = false">
          🚀 Запустить
        </VButton>
      </template>
    </VModal>

    <!-- ═══════════════════════════════════════════════
         LOADING OVERLAY
    ═══════════════════════════════════════════════ -->
    <Transition name="fade-mk">
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
          <span class="text-sm font-medium">Загрузка маркетинга...</span>
        </div>
      </div>
    </Transition>
  </div>
</template>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     STYLES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<style scoped>
/* Ripple animation */
@keyframes ripple-mk {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* Dropdown transition */
.dropdown-mk-enter-active,
.dropdown-mk-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-mk-enter-from,
.dropdown-mk-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.96);
}

/* Slide transition */
.slide-mk-enter-active,
.slide-mk-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.slide-mk-enter-from,
.slide-mk-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Fade transition */
.fade-mk-enter-active,
.fade-mk-leave-active {
  transition: opacity 0.3s ease;
}
.fade-mk-enter-from,
.fade-mk-leave-to {
  opacity: 0;
}

/* Tabular nums */
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
