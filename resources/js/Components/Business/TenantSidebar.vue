<script setup lang="ts">
/**
 * TenantSidebar.vue — Боковая навигация B2B Tenant Dashboard
 *
 * Вертикали:
 *   Beauty · Taxi · Food · Hotels · RealEstate · Flowers
 *   Fashion · Furniture · Fitness · Travel · default
 *
 * ───────────────────────────────────────────────────────────────
 *  Функционал:
 *   1.  Header: логотип 🐱 + название тенанта + кнопка collapse
 *   2.  Навигационные группы:
 *       — Главная (Dashboard · Календарь · Уведомления)
 *       — Клиенты (Клиенты · CRM · Записи)
 *       — Каталог (Каталог · Склад)
 *       — Финансы (Финансы · Платежи · Лояльность)
 *       — Маркетинг (Маркетинг · Рассылки · Реклама · SEO)
 *       — Команда (Персонал)
 *       — Филиалы (Филиалы)
 *       — Аналитика (Аналитика · Отчёты)
 *       — Публичное (Паблик · Интеграции)
 *       — Система (Настройки · Помощь)
 *   3.  Active item — emerald-500 highlight
 *   4.  Collapsed: иконки + tooltip
 *   5.  Expanded: иконки + label
 *   6.  Persist collapsed/expanded → localStorage
 *   7.  lg+ фиксированная, на mobile скрыта
 *   8.  Ripple-sb, keyboard, smooth transitions
 *   9.  Tenant vertical badge + online indicator
 *  10.  Unread counters на Уведомления, CRM, Рассылки
 * ───────────────────────────────────────────────────────────────
 */

import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useAuth, useTenant } from '@/stores'

/* ━━━━━━━━━━━━  TYPES  ━━━━━━━━━━━━ */

interface NavItem {
  key:       string
  label:     string
  icon:      string
  route:     string
  badge?:    number | null
  /** Видим только при указанных вертикалях (null = всегда) */
  verticals: string[] | null
}

interface NavGroup {
  key:       string
  label:     string
  items:     NavItem[]
  /** Видимость целой группы по вертикалям (null = всегда) */
  verticals: string[] | null
}

interface VerticalCfg {
  label: string
  icon:  string
  color: string
}

/* ━━━━━━━━━━━━  PROPS / EMITS  ━━━━━━━━━━━━ */

const props = withDefaults(defineProps<{
  /** Текущий активный route key (напр. 'dashboard', 'clients', 'seo') */
  activeRoute?:    string
  /** Вертикаль текущего тенанта */
  vertical?:       string
  /** Название тенанта / бизнеса */
  tenantName?:     string
  /** Аватар или логотип тенанта */
  tenantAvatar?:   string
  /** Подписка / тариф */
  tenantPlan?:     string
  /** Активные вертикали тенанта (для фильтрации меню) */
  activeVerticals?: string[]
  /** Бейджи — непрочитанные */
  unreadNotifications?: number
  unreadCrm?:          number
  unreadMailings?:     number
}>(), {
  activeRoute:         'dashboard',
  vertical:            'default',
  tenantName:          'Мой бизнес',
  tenantAvatar:        '',
  tenantPlan:          'Pro',
  activeVerticals:     () => [],
  unreadNotifications: 0,
  unreadCrm:           0,
  unreadMailings:      0,
})

const emit = defineEmits<{
  'navigate':  [route: string]
  'collapse':  [collapsed: boolean]
  'logout':    []
}>()

const auth = useAuth()
const biz  = useTenant()

/* ━━━━━━━━━━━━  VERTICAL CONFIG  ━━━━━━━━━━━━ */

const VERTICAL_MAP: Record<string, VerticalCfg> = {
  beauty:     { label: 'Салон красоты',   icon: '💄', color: 'pink' },
  taxi:       { label: 'Такси',           icon: '🚕', color: 'yellow' },
  food:       { label: 'Еда и рестораны', icon: '🍽️', color: 'orange' },
  hotel:      { label: 'Отели',           icon: '🏨', color: 'sky' },
  realEstate: { label: 'Недвижимость',    icon: '🏢', color: 'emerald' },
  flowers:    { label: 'Цветы',           icon: '💐', color: 'rose' },
  fashion:    { label: 'Мода',            icon: '👗', color: 'violet' },
  furniture:  { label: 'Мебель',          icon: '🛋️', color: 'amber' },
  fitness:    { label: 'Фитнес',          icon: '💪', color: 'lime' },
  travel:     { label: 'Путешествия',     icon: '✈️', color: 'cyan' },
  default:    { label: 'Бизнес',          icon: '📊', color: 'indigo' },
}

const vc = computed<VerticalCfg>(() => VERTICAL_MAP[props.vertical] ?? VERTICAL_MAP.default)

/* ━━━━━━━━━━━━  COLLAPSE  ━━━━━━━━━━━━ */

const LS_KEY = 'catvrf_sidebar_collapsed'

const collapsed = ref(false)

function initCollapsed() {
  try {
    const stored = localStorage.getItem(LS_KEY)
    if (stored !== null) collapsed.value = stored === '1'
  } catch { /* ssr / privacy */ }
}

function toggleCollapse() {
  collapsed.value = !collapsed.value
  try { localStorage.setItem(LS_KEY, collapsed.value ? '1' : '0') } catch {}
  emit('collapse', collapsed.value)
}

/* ━━━━━━━━━━━━  NAVIGATION DATA  ━━━━━━━━━━━━ */

const NAV_GROUPS = computed<NavGroup[]>(() => [
  {
    key: 'main', label: 'Главная', verticals: null,
    items: [
      { key: 'dashboard',     label: 'Дашборд',       icon: '📊', route: 'dashboard',     verticals: null },
      { key: 'calendar',      label: 'Календарь',     icon: '📅', route: 'calendar',      verticals: null },
      { key: 'notifications', label: 'Уведомления',   icon: '🔔', route: 'notifications', verticals: null,
        badge: props.unreadNotifications || null },
    ],
  },
  {
    key: 'clients', label: 'Клиенты', verticals: null,
    items: [
      { key: 'clients',  label: 'Клиенты',   icon: '👥', route: 'clients',  verticals: null },
      { key: 'crm',      label: 'CRM',        icon: '💼', route: 'crm',      verticals: null,
        badge: props.unreadCrm || null },
      { key: 'bookings', label: 'Записи',     icon: '📋', route: 'bookings',
        verticals: ['beauty', 'fitness', 'hotel', 'food', 'taxi', 'travel'] },
    ],
  },
  {
    key: 'catalog', label: 'Каталог и склад', verticals: null,
    items: [
      { key: 'catalog',   label: 'Каталог',        icon: '🏷️', route: 'catalog',   verticals: null },
      { key: 'inventory', label: 'Склад',          icon: '📦', route: 'inventory',
        verticals: ['food', 'flowers', 'fashion', 'furniture', 'beauty'] },
    ],
  },
  {
    key: 'finance', label: 'Финансы', verticals: null,
    items: [
      { key: 'finance',  label: 'Финансы',    icon: '💰', route: 'finance',  verticals: null },
      { key: 'payments', label: 'Платежи',    icon: '💳', route: 'payments', verticals: null },
      { key: 'loyalty',  label: 'Лояльность', icon: '⭐', route: 'loyalty',  verticals: null },
    ],
  },
  {
    key: 'marketing', label: 'Маркетинг', verticals: null,
    items: [
      { key: 'marketing', label: 'Маркетинг', icon: '📣', route: 'marketing', verticals: null },
      { key: 'mailings',  label: 'Рассылки',  icon: '📩', route: 'mailings',  verticals: null,
        badge: props.unreadMailings || null },
      { key: 'ads',       label: 'Реклама',   icon: '📺', route: 'ads',       verticals: null },
      { key: 'seo',       label: 'SEO',       icon: '🔎', route: 'seo',       verticals: null },
    ],
  },
  {
    key: 'team', label: 'Команда', verticals: null,
    items: [
      { key: 'staff', label: 'Персонал', icon: '👤', route: 'staff', verticals: null },
    ],
  },
  {
    key: 'branches', label: 'Филиалы', verticals: null,
    items: [
      { key: 'branches', label: 'Филиалы', icon: '🏬', route: 'branches', verticals: null },
    ],
  },
  {
    key: 'analytics', label: 'Аналитика', verticals: null,
    items: [
      { key: 'analytics', label: 'Аналитика', icon: '📈', route: 'analytics', verticals: null },
      { key: 'reports',   label: 'Отчёты',    icon: '📑', route: 'reports',   verticals: null },
    ],
  },
  {
    key: 'public', label: 'Публичное', verticals: null,
    items: [
      { key: 'public',       label: 'Паблик',       icon: '🌐', route: 'public',       verticals: null },
      { key: 'integrations', label: 'Интеграции',   icon: '🔗', route: 'integrations', verticals: null },
    ],
  },
  {
    key: 'system', label: 'Система', verticals: null,
    items: [
      { key: 'settings', label: 'Настройки', icon: '⚙️', route: 'settings', verticals: null },
      { key: 'help',     label: 'Помощь',    icon: '❓', route: 'help',     verticals: null },
    ],
  },
])

/* ── Filter by active verticals ── */
const filteredGroups = computed<NavGroup[]>(() => {
  const av = props.activeVerticals
  const current = props.vertical

  return NAV_GROUPS.value
    .filter((g) => {
      if (!g.verticals) return true
      return g.verticals.includes(current) || g.verticals.some((v) => av.includes(v))
    })
    .map((g) => ({
      ...g,
      items: g.items.filter((item) => {
        if (!item.verticals) return true
        return item.verticals.includes(current) || item.verticals.some((v) => av.includes(v))
      }),
    }))
    .filter((g) => g.items.length > 0)
})

/* ━━━━━━━━━━━━  COLLAPSED GROUPS (accordion) ━━━━━━━━━━━━ */

const expandedGroups = ref<Set<string>>(new Set())

function toggleGroup(key: string) {
  if (expandedGroups.value.has(key)) expandedGroups.value.delete(key)
  else expandedGroups.value.add(key)
}

function isGroupExpanded(key: string): boolean {
  /* В expanded-sidebar группы всегда открыты */
  if (!collapsed.value) return true
  return expandedGroups.value.has(key)
}

/* ━━━━━━━━━━━━  TOOLTIP  ━━━━━━━━━━━━ */

const tooltipItem = ref<string | null>(null)
const tooltipPos  = ref({ x: 0, y: 0 })

function showTooltip(e: MouseEvent, label: string) {
  if (!collapsed.value) return
  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
  tooltipPos.value = { x: rect.right + 10, y: rect.top + rect.height / 2 }
  tooltipItem.value = label
}

function hideTooltip() {
  tooltipItem.value = null
}

/* ━━━━━━━━━━━━  NAVIGATE  ━━━━━━━━━━━━ */

function navigate(route: string) {
  emit('navigate', route)
}

/* ━━━━━━━━━━━━  RIPPLE  ━━━━━━━━━━━━ */

function ripple(e: MouseEvent) {
  const target = e.currentTarget as HTMLElement
  const rect   = target.getBoundingClientRect()
  const d      = Math.max(rect.width, rect.height) * 2
  const el     = document.createElement('span')
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-sb_0.55s_ease-out]'
  el.style.cssText = [
    `inline-size:${d}px`,
    `block-size:${d}px`,
    `inset-inline-start:${e.clientX - rect.left - d / 2}px`,
    `inset-block-start:${e.clientY - rect.top - d / 2}px`,
  ].join(';')
  target.appendChild(el)
  setTimeout(() => el.remove(), 600)
}

/* ━━━━━━━━━━━━  LIFECYCLE  ━━━━━━━━━━━━ */

onMounted(() => {
  initCollapsed()
})
</script>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     TEMPLATE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<template>
  <!-- Sidebar — hidden below lg, fixed on lg+ -->
  <aside
    :class="[
      'hidden lg:flex flex-col fixed inset-block-start-0 inset-inline-start-0',
      'h-screen z-40 border-e border-(--t-border)/30',
      'bg-(--t-surface)/70 backdrop-blur-2xl',
      'transition-[inline-size] duration-300 ease-out',
      collapsed ? 'w-17' : 'w-62',
    ]"
  >
    <!-- ═══════ HEADER ═══════ -->
    <div :class="[
      'shrink-0 flex items-center gap-3 border-b border-(--t-border)/30',
      'transition-all duration-300 px-4 py-4',
      collapsed ? 'justify-center' : '',
    ]">
      <!-- Logo -->
      <div class="relative shrink-0 w-9 h-9 rounded-xl bg-emerald-500/15
                  flex items-center justify-center select-none">
        <span v-if="props.tenantAvatar" class="text-lg">{{ props.tenantAvatar }}</span>
        <span v-else class="text-lg">🐱</span>
        <!-- Online dot -->
        <span class="absolute -inset-block-end-0.5 -inset-inline-end-0.5 w-2.5 h-2.5
                     rounded-full bg-emerald-500 border-2 border-(--t-surface)" />
      </div>

      <!-- Tenant info (expanded only) -->
      <div v-if="!collapsed" class="flex-1 min-w-0 transition-opacity duration-200">
        <p class="text-xs font-extrabold text-(--t-text) truncate leading-tight">
          {{ props.tenantName }}
        </p>
        <div class="flex items-center gap-1.5 mt-0.5">
          <span class="text-[9px]">{{ vc.icon }}</span>
          <span class="text-[9px] text-(--t-text-3) truncate">{{ vc.label }}</span>
          <span v-if="props.tenantPlan"
                class="shrink-0 ms-auto px-1.5 py-px rounded text-[7px] font-bold
                       bg-emerald-500/12 text-emerald-400">
            {{ props.tenantPlan }}
          </span>
        </div>
      </div>

      <!-- Collapse toggle -->
      <button
        :class="[
          'relative overflow-hidden shrink-0 w-7 h-7 rounded-lg',
          'flex items-center justify-center text-(--t-text-3)',
          'hover:bg-(--t-card-hover) active:scale-90 transition-all',
          collapsed ? '' : '',
        ]"
        :title="collapsed ? 'Развернуть' : 'Свернуть'"
        @click="toggleCollapse" @mousedown="ripple"
      >
        <span class="text-xs transition-transform duration-300"
              :style="{ transform: collapsed ? 'rotate(180deg)' : '' }">«</span>
      </button>
    </div>

    <!-- ═══════ NAVIGATION ═══════ -->
    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-3 px-2 no-scrollbar">
      <template v-for="group in filteredGroups" :key="group.key">

        <!-- Group label (expanded only) -->
        <div v-if="!collapsed"
             class="px-2.5 pt-4 pb-1.5 first:pt-0">
          <p class="text-[9px] font-bold text-(--t-text-3)/60 uppercase tracking-widest select-none">
            {{ group.label }}
          </p>
        </div>

        <!-- Separator (collapsed) -->
        <div v-else class="mx-2 my-2 h-px bg-(--t-border)/20 first:hidden" />

        <!-- Items -->
        <ul class="flex flex-col gap-0.5">
          <li v-for="item in group.items" :key="item.key">
            <button
              :class="[
                'relative overflow-hidden group/nav flex items-center gap-3',
                'rounded-xl transition-all duration-200 cursor-pointer select-none',
                collapsed ? 'w-11 h-11 mx-auto justify-center' : 'w-full px-3 py-2.5',
                activeRoute === item.key
                  ? 'bg-emerald-500/12 text-emerald-400 shadow-sm shadow-emerald-500/5'
                  : 'text-(--t-text-2) hover:text-(--t-text) hover:bg-(--t-card-hover)',
              ]"
              @click="navigate(item.route)"
              @mousedown="ripple"
              @mouseenter="(e: MouseEvent) => showTooltip(e, item.label)"
              @mouseleave="hideTooltip"
            >
              <!-- Icon -->
              <span :class="[
                'shrink-0 text-sm leading-none transition-transform duration-200',
                'group-hover/nav:scale-110',
                activeRoute === item.key ? 'drop-shadow-[0_0_6px_rgba(16,185,129,0.35)]' : '',
              ]">{{ item.icon }}</span>

              <!-- Label (expanded only) -->
              <span v-if="!collapsed"
                    :class="[
                      'flex-1 text-[11px] font-semibold truncate text-start transition-colors',
                      activeRoute === item.key ? 'text-emerald-400' : '',
                    ]">
                {{ item.label }}
              </span>

              <!-- Badge -->
              <span v-if="item.badge && item.badge > 0"
                    :class="[
                      'shrink-0 min-w-4.5 h-4.5 rounded-full',
                      'flex items-center justify-center',
                      'text-[8px] font-black text-white bg-rose-500 tabular-nums',
                      collapsed ? 'absolute -inset-block-start-0.5 -inset-inline-end-0.5 min-w-3.5 h-3.5 text-[7px]' : '',
                    ]">
                {{ item.badge > 99 ? '99+' : item.badge }}
              </span>

              <!-- Active bar (expanded) -->
              <span v-if="activeRoute === item.key && !collapsed"
                    class="absolute inset-inline-start-0 inset-block-start-1/2 -translate-y-1/2
                           inline-size-[3px] block-size-5 rounded-e-full bg-emerald-500" />

              <!-- Active ring (collapsed) -->
              <span v-if="activeRoute === item.key && collapsed"
                    class="absolute inset-0 rounded-xl ring-1 ring-emerald-500/30
                           pointer-events-none" />
            </button>
          </li>
        </ul>
      </template>
    </nav>

    <!-- ═══════ FOOTER ═══════ -->
    <div :class="[
      'shrink-0 border-t border-(--t-border)/30 px-2 py-3',
      'flex flex-col gap-1.5',
    ]">
      <!-- User quick-row -->
      <div :class="[
        'flex items-center gap-2.5 px-2 py-2 rounded-xl',
        collapsed ? 'justify-center' : '',
      ]">
        <!-- Avatar -->
        <div class="shrink-0 w-8 h-8 rounded-full bg-emerald-500/15
                    flex items-center justify-center text-xs font-black text-emerald-400 select-none">
          {{ (props.tenantName || 'B')[0].toUpperCase() }}
        </div>

        <template v-if="!collapsed">
          <div class="flex-1 min-w-0">
            <p class="text-[10px] font-bold text-(--t-text) truncate">
              {{ props.tenantName }}
            </p>
            <p class="text-[8px] text-(--t-text-3)">B2B Кабинет</p>
          </div>

          <!-- Logout -->
          <button
            class="relative overflow-hidden shrink-0 w-7 h-7 rounded-lg
                   flex items-center justify-center text-(--t-text-3)
                   hover:text-rose-400 hover:bg-rose-500/10 active:scale-90 transition-all"
            title="Выйти"
            @click="emit('logout')" @mousedown="ripple"
            @mouseenter="(e: MouseEvent) => showTooltip(e, 'Выйти')"
            @mouseleave="hideTooltip"
          >
            <span class="text-xs">🚪</span>
          </button>
        </template>
      </div>

      <!-- Version (expanded only) -->
      <p v-if="!collapsed" class="text-center text-[8px] text-(--t-text-3)/40 select-none">
        CatVRF v2.6 · 2026
      </p>
    </div>
  </aside>

  <!-- ═══════ TOOLTIP (collapsed mode) ═══════ -->
  <Teleport to="body">
    <Transition name="tt-sb">
      <div v-if="tooltipItem"
           class="fixed z-9999 pointer-events-none px-3 py-1.5 rounded-lg
                  bg-zinc-900/95 border border-(--t-border)/40 shadow-xl
                  text-[10px] font-semibold text-white whitespace-nowrap"
           :style="{
             insetInlineStart: `${tooltipPos.x}px`,
             insetBlockStart:  `${tooltipPos.y}px`,
             transform: 'translateY(-50%)',
           }">
        {{ tooltipItem }}
      </div>
    </Transition>
  </Teleport>
</template>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     STYLES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<style scoped>
/* Ripple — unique suffix sb (Sidebar) */
@keyframes ripple-sb {
  from { transform: scale(0); opacity: 0.35; }
  to   { transform: scale(1); opacity: 0; }
}

/* No scrollbar */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Tooltip transition */
.tt-sb-enter-active,
.tt-sb-leave-active {
  transition: opacity 0.12s ease, transform 0.12s ease;
}
.tt-sb-enter-from,
.tt-sb-leave-to {
  opacity: 0;
  transform: translateY(-50%) translateX(-4px);
}
</style>
