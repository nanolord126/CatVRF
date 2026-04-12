<script setup lang="ts">
/**
 * TenantDashboard.vue — Главная страница B2B Tenant Dashboard
 *
 * Универсальный компонент для всех 127 вертикалей CatVRF:
 *   Beauty · Taxi · Food · Hotels · RealEstate · Flowers · Fashion · Furniture
 *   Fitness · Travel · Medical · Auto · и т.д.
 *
 * ─────────────────────────────────────────────────────────────
 *  Структура:
 *   1. Header         — название бизнеса, B2B-badge, wallet, notifications, period
 *   2. B2B Credit Bar — кредитная линия (только для B2B)
 *   3. Stat Grid      — 8 виджетов (GMV, заказы, клиенты, конверсия, AI, доставка, склад, выручка)
 *   4. Revenue Row    — sparkline-график + breakdown по дням + тепловая карта активности
 *   5. Quick Actions  — быстрые действия, адаптированные под текущую вертикаль
 *   6. Vertical Strip — стрип с метриками по под-вертикалям текущего тенанта
 *   7. Recent List    — последние заказы/записи/поездки (зависит от вертикали)
 *   8. Alerts Panel   — live fraud-alerts, low-stock, delivery, AI
 * ─────────────────────────────────────────────────────────────
 *  Как адаптировать под конкретную вертикаль:
 *   → props.vertical: 'beauty' | 'taxi' | 'food' | 'hotel' | 'realEstate' | ...
 *   → Конфиг вертикали берётся из VERTICAL_CONFIG (см. ниже)
 *   → Колонки recent-таблицы, quick-actions, метрики — всё автоматически
 * ─────────────────────────────────────────────────────────────
 */

import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'

import VStatCard   from '../UI/VStatCard.vue'
import VCard       from '../UI/VCard.vue'
import VButton     from '../UI/VButton.vue'
import VBadge      from '../UI/VBadge.vue'
import VTabs       from '../UI/VTabs.vue'
import { useAuth, useTenant, useNotifications } from '@/stores'

// ─────────────────────────────────────────
// TYPES
// ─────────────────────────────────────────

interface QuickAction {
  key: string
  label: string
  icon: string
  color: string       // Tailwind gradient classes
  shortcut?: string
  badge?: string
}

interface RecentRow {
  id: number | string
  [key: string]: unknown
}

interface VerticalConfig {
  icon: string
  label: string
  accentColor: string     // CSS color for glows / accents
  accentClass: string     // Tailwind from-*/to-* classes
  kpiLabel: string        // Главная метрика (Записи / Поездки / Заказы / ...)
  kpiIcon: string
  quickActions: QuickAction[]
  recentColumns: Array<{ key: string; label: string; align?: 'left' | 'center' | 'right' }>
  recentTitle: string
  heatmapLabel: string    // Что отображается на тепловой карте
}

// ─────────────────────────────────────────
// PROPS
// ─────────────────────────────────────────

const props = withDefaults(defineProps<{
  /**
   * Ключ вертикали. Определяет quick-actions, колонки таблицы, метрики.
   * Допустимые значения: 'beauty' | 'taxi' | 'food' | 'hotel' | 'realEstate'
   *   | 'flowers' | 'fashion' | 'furniture' | 'fitness' | 'travel'
   *   | 'medical' | 'auto' | 'default'
   * При отсутствии — используется 'default'.
   */
  vertical?: string
  /** Разрешить переключение периода (по умолчанию true) */
  showPeriodSelector?: boolean
  /** Компактный режим для встройки внутри другого layout */
  compact?: boolean
}>(), {
  vertical: 'default',
  showPeriodSelector: true,
  compact: false,
})

// ─────────────────────────────────────────
// STORES
// ─────────────────────────────────────────

const biz   = useTenant()
const auth  = useAuth()
const notif = useNotifications()

// ─────────────────────────────────────────
// VERTICAL CONFIG MAP
// ─────────────────────────────────────────
// АДАПТАЦИЯ ПОД ВЕРТИКАЛЬ: добавьте новую запись в этот объект.
// ─────────────────────────────────────────

const VERTICAL_CONFIG: Record<string, VerticalConfig> = {
  // ── BEAUTY ─────────────────────────────────────────────────
  beauty: {
    icon: '💄', label: 'Красота',
    accentColor: 'rgba(236,72,153,0.15)',
    accentClass: 'from-pink-500/20 to-rose-500/10 border-pink-500/25',
    kpiLabel: 'Записи', kpiIcon: '📅',
    quickActions: [
      { key: 'slot',    label: 'Добавить слот',        icon: '📅', color: 'from-pink-500/15 to-rose-500/10 border-pink-500/20 hover:border-pink-400/40',    shortcut: 'N' },
      { key: 'master',  label: 'Новый мастер',         icon: '👩‍🎨', color: 'from-violet-500/15 to-purple-500/10 border-violet-500/20 hover:border-violet-400/40' },
      { key: 'promo',   label: 'Промо-акция',          icon: '🎁', color: 'from-amber-500/15 to-orange-500/10 border-amber-500/20 hover:border-amber-400/40' },
      { key: 'ai',      label: 'AI-образ клиента',     icon: '🤖', color: 'from-sky-500/15 to-blue-500/10 border-sky-500/20 hover:border-sky-400/40',       badge: 'AI' },
      { key: 'review',  label: 'Ответить на отзывы',   icon: '⭐', color: 'from-emerald-500/15 to-teal-500/10 border-emerald-500/20 hover:border-emerald-400/40' },
      { key: 'stock',   label: 'Остатки косметики',    icon: '🧴', color: 'from-indigo-500/15 to-blue-500/10 border-indigo-500/20 hover:border-indigo-400/40' },
    ],
    recentTitle: 'Последние записи',
    recentColumns: [
      { key: 'id',       label: '#',       align: 'center' },
      { key: 'client',   label: 'Клиент',  align: 'left'   },
      { key: 'master',   label: 'Мастер',  align: 'left'   },
      { key: 'service',  label: 'Услуга',  align: 'left'   },
      { key: 'slot',     label: 'Время',   align: 'center' },
      { key: 'total',    label: 'Сумма',   align: 'right'  },
      { key: 'status',   label: 'Статус',  align: 'center' },
    ],
    heatmapLabel: 'Загрузка мастеров по часам',
  },

  // ── TAXI ───────────────────────────────────────────────────
  taxi: {
    icon: '🚕', label: 'Такси',
    accentColor: 'rgba(245,158,11,0.15)',
    accentClass: 'from-amber-500/20 to-yellow-500/10 border-amber-500/25',
    kpiLabel: 'Поездки', kpiIcon: '🛰️',
    quickActions: [
      { key: 'dispatch', label: 'Диспетчер',         icon: '🎯', color: 'from-amber-500/15 to-yellow-500/10 border-amber-500/20 hover:border-amber-400/40',   badge: 'LIVE' },
      { key: 'driver',   label: 'Добавить водителя', icon: '🧑‍✈️', color: 'from-sky-500/15 to-blue-500/10 border-sky-500/20 hover:border-sky-400/40' },
      { key: 'zone',     label: 'Редактор зон',      icon: '🗺️', color: 'from-emerald-500/15 to-teal-500/10 border-emerald-500/20 hover:border-emerald-400/40' },
      { key: 'pricing',  label: 'Тарифы',            icon: '💰', color: 'from-violet-500/15 to-purple-500/10 border-violet-500/20 hover:border-violet-400/40' },
      { key: 'fleet',    label: 'Флот',              icon: '🚗', color: 'from-indigo-500/15 to-blue-500/10 border-indigo-500/20 hover:border-indigo-400/40' },
      { key: 'incidents',label: 'Инциденты',         icon: '🚨', color: 'from-rose-500/15 to-red-500/10 border-rose-500/20 hover:border-rose-400/40',         badge: '3' },
    ],
    recentTitle: 'Активные поездки',
    recentColumns: [
      { key: 'id',      label: 'ID',      align: 'center' },
      { key: 'driver',  label: 'Водитель', align: 'left'  },
      { key: 'vehicle', label: 'Авто',     align: 'left'  },
      { key: 'zone',    label: 'Зона',     align: 'center'},
      { key: 'eta',     label: 'ETA',      align: 'center'},
      { key: 'fare',    label: 'Стоимость',align: 'right' },
      { key: 'status',  label: 'Статус',   align: 'center'},
    ],
    heatmapLabel: 'Поездки по часам суток',
  },

  // ── FOOD ───────────────────────────────────────────────────
  food: {
    icon: '🍽️', label: 'Еда',
    accentColor: 'rgba(249,115,22,0.15)',
    accentClass: 'from-orange-500/20 to-red-500/10 border-orange-500/25',
    kpiLabel: 'Заказы', kpiIcon: '🛵',
    quickActions: [
      { key: 'menu',    label: 'Редактор меню',      icon: '🍕', color: 'from-orange-500/15 to-red-500/10 border-orange-500/20 hover:border-orange-400/40' },
      { key: 'ai-menu', label: 'AI-конструктор меню',icon: '🤖', color: 'from-sky-500/15 to-blue-500/10 border-sky-500/20 hover:border-sky-400/40',          badge: 'AI' },
      { key: 'promo',   label: 'Акция дня',          icon: '🎁', color: 'from-amber-500/15 to-yellow-500/10 border-amber-500/20 hover:border-amber-400/40' },
      { key: 'courier', label: 'Курьеры',            icon: '🛵', color: 'from-emerald-500/15 to-teal-500/10 border-emerald-500/20 hover:border-emerald-400/40' },
      { key: 'stock',   label: 'Склад ингредиентов', icon: '📦', color: 'from-indigo-500/15 to-blue-500/10 border-indigo-500/20 hover:border-indigo-400/40' },
      { key: 'reviews', label: 'Отзывы',             icon: '⭐', color: 'from-violet-500/15 to-purple-500/10 border-violet-500/20 hover:border-violet-400/40' },
    ],
    recentTitle: 'Последние заказы',
    recentColumns: [
      { key: 'id',       label: '#',        align: 'center' },
      { key: 'client',   label: 'Клиент',   align: 'left'   },
      { key: 'items',    label: 'Состав',   align: 'left'   },
      { key: 'courier',  label: 'Курьер',   align: 'left'   },
      { key: 'eta',      label: 'ETA',      align: 'center' },
      { key: 'total',    label: 'Сумма',    align: 'right'  },
      { key: 'status',   label: 'Статус',   align: 'center' },
    ],
    heatmapLabel: 'Заказы по часам и дням',
  },

  // ── HOTEL ──────────────────────────────────────────────────
  hotel: {
    icon: '🏨', label: 'Отели',
    accentColor: 'rgba(14,165,233,0.15)',
    accentClass: 'from-sky-500/20 to-blue-500/10 border-sky-500/25',
    kpiLabel: 'Бронирования', kpiIcon: '🛎️',
    quickActions: [
      { key: 'room',    label: 'Добавить номер',  icon: '🛏️', color: 'from-sky-500/15 to-blue-500/10 border-sky-500/20 hover:border-sky-400/40' },
      { key: 'ai-tour', label: 'AI 3D-тур',      icon: '🏙️', color: 'from-violet-500/15 to-purple-500/10 border-violet-500/20 hover:border-violet-400/40', badge: 'AI' },
      { key: 'promo',   label: 'Специальная цена',icon: '🎁', color: 'from-amber-500/15 to-orange-500/10 border-amber-500/20 hover:border-amber-400/40' },
      { key: 'checkin', label: 'Check-in сегодня',icon: '🔑', color: 'from-emerald-500/15 to-teal-500/10 border-emerald-500/20 hover:border-emerald-400/40' },
      { key: 'reviews', label: 'Отзывы',          icon: '⭐', color: 'from-rose-500/15 to-pink-500/10 border-rose-500/20 hover:border-rose-400/40' },
      { key: 'channel', label: 'Channel manager', icon: '🔗', color: 'from-indigo-500/15 to-blue-500/10 border-indigo-500/20 hover:border-indigo-400/40' },
    ],
    recentTitle: 'Активные бронирования',
    recentColumns: [
      { key: 'id',       label: '#',         align: 'center' },
      { key: 'guest',    label: 'Гость',     align: 'left'   },
      { key: 'room',     label: 'Номер',     align: 'left'   },
      { key: 'checkin',  label: 'Заезд',     align: 'center' },
      { key: 'checkout', label: 'Выезд',     align: 'center' },
      { key: 'nights',   label: 'Ночей',     align: 'center' },
      { key: 'total',    label: 'Сумма',     align: 'right'  },
      { key: 'status',   label: 'Статус',    align: 'center' },
    ],
    heatmapLabel: 'Загрузка номеров по дням',
  },

  // ── REAL ESTATE ────────────────────────────────────────────
  realEstate: {
    icon: '🏢', label: 'Недвижимость',
    accentColor: 'rgba(99,102,241,0.15)',
    accentClass: 'from-indigo-500/20 to-violet-500/10 border-indigo-500/25',
    kpiLabel: 'Показы', kpiIcon: '🏠',
    quickActions: [
      { key: 'listing', label: 'Добавить объект',  icon: '🏠', color: 'from-indigo-500/15 to-violet-500/10 border-indigo-500/20 hover:border-indigo-400/40' },
      { key: 'ai-3d',   label: 'AI 3D-ремонт',    icon: '🎨', color: 'from-violet-500/15 to-purple-500/10 border-violet-500/20 hover:border-violet-400/40', badge: 'AI' },
      { key: 'valuation',label: 'Оценка стоимости',icon: '💰', color: 'from-amber-500/15 to-orange-500/10 border-amber-500/20 hover:border-amber-400/40' },
      { key: 'leads',   label: 'Лиды',             icon: '📋', color: 'from-emerald-500/15 to-teal-500/10 border-emerald-500/20 hover:border-emerald-400/40' },
      { key: 'docs',    label: 'Документы',         icon: '📄', color: 'from-sky-500/15 to-blue-500/10 border-sky-500/20 hover:border-sky-400/40' },
      { key: 'map',     label: 'Карта объектов',    icon: '🗺️', color: 'from-rose-500/15 to-pink-500/10 border-rose-500/20 hover:border-rose-400/40' },
    ],
    recentTitle: 'Последние сделки',
    recentColumns: [
      { key: 'id',      label: '#',          align: 'center' },
      { key: 'object',  label: 'Объект',     align: 'left'   },
      { key: 'type',    label: 'Тип',        align: 'center' },
      { key: 'agent',   label: 'Агент',      align: 'left'   },
      { key: 'area',    label: 'Площадь',    align: 'center' },
      { key: 'price',   label: 'Цена',       align: 'right'  },
      { key: 'status',  label: 'Статус',     align: 'center' },
    ],
    heatmapLabel: 'Показы по дням недели',
  },

  // ── FLOWERS ────────────────────────────────────────────────
  flowers: {
    icon: '💐', label: 'Цветы',
    accentColor: 'rgba(236,72,153,0.15)',
    accentClass: 'from-fuchsia-500/20 to-pink-500/10 border-fuchsia-500/25',
    kpiLabel: 'Букеты', kpiIcon: '💐',
    quickActions: [
      { key: 'bouquet', label: 'Новый букет',       icon: '💐', color: 'from-fuchsia-500/15 to-pink-500/10 border-fuchsia-500/20 hover:border-fuchsia-400/40' },
      { key: 'ai',      label: 'AI-флористика',     icon: '🤖', color: 'from-sky-500/15 to-blue-500/10 border-sky-500/20 hover:border-sky-400/40',          badge: 'AI' },
      { key: 'stock',   label: 'Остатки цветов',    icon: '📦', color: 'from-emerald-500/15 to-teal-500/10 border-emerald-500/20 hover:border-emerald-400/40' },
      { key: 'delivery',label: 'Курьеры',           icon: '🛵', color: 'from-amber-500/15 to-orange-500/10 border-amber-500/20 hover:border-amber-400/40' },
      { key: 'promo',   label: 'Акция',             icon: '🎁', color: 'from-violet-500/15 to-purple-500/10 border-violet-500/20 hover:border-violet-400/40' },
      { key: 'seasonal',label: 'Сезонный каталог',  icon: '🌸', color: 'from-rose-500/15 to-pink-500/10 border-rose-500/20 hover:border-rose-400/40' },
    ],
    recentTitle: 'Последние заказы',
    recentColumns: [
      { key: 'id',      label: '#',        align: 'center' },
      { key: 'client',  label: 'Клиент',   align: 'left'   },
      { key: 'bouquet', label: 'Букет',    align: 'left'   },
      { key: 'delivery',label: 'Доставка', align: 'center' },
      { key: 'total',   label: 'Сумма',    align: 'right'  },
      { key: 'status',  label: 'Статус',   align: 'center' },
    ],
    heatmapLabel: 'Продажи по часам (пик — утро/вечер)',
  },

  // ── FASHION ────────────────────────────────────────────────
  fashion: {
    icon: '👗', label: 'Мода',
    accentColor: 'rgba(168,85,247,0.15)',
    accentClass: 'from-purple-500/20 to-violet-500/10 border-purple-500/25',
    kpiLabel: 'Продажи', kpiIcon: '🛍️',
    quickActions: [
      { key: 'product', label: 'Добавить товар',   icon: '👗', color: 'from-purple-500/15 to-violet-500/10 border-purple-500/20 hover:border-purple-400/40' },
      { key: 'ai-style',label: 'AI-стилист',       icon: '🤖', color: 'from-sky-500/15 to-blue-500/10 border-sky-500/20 hover:border-sky-400/40',          badge: 'AI' },
      { key: 'capsule', label: 'Капсула сезона',   icon: '✨', color: 'from-amber-500/15 to-orange-500/10 border-amber-500/20 hover:border-amber-400/40' },
      { key: 'size',    label: 'Размерная сетка',  icon: '📐', color: 'from-emerald-500/15 to-teal-500/10 border-emerald-500/20 hover:border-emerald-400/40' },
      { key: 'promo',   label: 'Распродажа',       icon: '🔥', color: 'from-rose-500/15 to-red-500/10 border-rose-500/20 hover:border-rose-400/40' },
      { key: 'lookbook',label: 'Lookbook',         icon: '📸', color: 'from-indigo-500/15 to-blue-500/10 border-indigo-500/20 hover:border-indigo-400/40' },
    ],
    recentTitle: 'Последние продажи',
    recentColumns: [
      { key: 'id',      label: '#',        align: 'center' },
      { key: 'client',  label: 'Клиент',   align: 'left'   },
      { key: 'item',    label: 'Товар',    align: 'left'   },
      { key: 'size',    label: 'Размер',   align: 'center' },
      { key: 'total',   label: 'Сумма',    align: 'right'  },
      { key: 'status',  label: 'Статус',   align: 'center' },
    ],
    heatmapLabel: 'Продажи по размерам и дням',
  },

  // ── FURNITURE ──────────────────────────────────────────────
  furniture: {
    icon: '🛋️', label: 'Мебель',
    accentColor: 'rgba(16,185,129,0.15)',
    accentClass: 'from-emerald-500/20 to-teal-500/10 border-emerald-500/25',
    kpiLabel: 'Проекты', kpiIcon: '🏡',
    quickActions: [
      { key: 'product', label: 'Добавить товар',  icon: '🛋️', color: 'from-emerald-500/15 to-teal-500/10 border-emerald-500/20 hover:border-emerald-400/40' },
      { key: 'ai-3d',   label: 'AI-интерьер',    icon: '🤖', color: 'from-sky-500/15 to-blue-500/10 border-sky-500/20 hover:border-sky-400/40',           badge: 'AI' },
      { key: 'designer',label: 'Дизайнер',       icon: '🎨', color: 'from-violet-500/15 to-purple-500/10 border-violet-500/20 hover:border-violet-400/40' },
      { key: 'delivery',label: 'Крупногабаритная доставка', icon: '🚛', color: 'from-amber-500/15 to-orange-500/10 border-amber-500/20 hover:border-amber-400/40' },
      { key: 'stock',   label: 'Склад',          icon: '📦', color: 'from-indigo-500/15 to-blue-500/10 border-indigo-500/20 hover:border-indigo-400/40' },
      { key: 'measure', label: 'Замерщики',      icon: '📏', color: 'from-rose-500/15 to-pink-500/10 border-rose-500/20 hover:border-rose-400/40' },
    ],
    recentTitle: 'Последние проекты',
    recentColumns: [
      { key: 'id',      label: '#',         align: 'center' },
      { key: 'client',  label: 'Клиент',    align: 'left'   },
      { key: 'project', label: 'Проект',    align: 'left'   },
      { key: 'designer',label: 'Дизайнер',  align: 'left'   },
      { key: 'total',   label: 'Бюджет',    align: 'right'  },
      { key: 'status',  label: 'Статус',    align: 'center' },
    ],
    heatmapLabel: 'Продажи по стилям и дням',
  },

  // ── FITNESS ────────────────────────────────────────────────
  fitness: {
    icon: '💪', label: 'Фитнес',
    accentColor: 'rgba(20,184,166,0.15)',
    accentClass: 'from-teal-500/20 to-cyan-500/10 border-teal-500/25',
    kpiLabel: 'Посещения', kpiIcon: '🏋️',
    quickActions: [
      { key: 'class',   label: 'Новое занятие',    icon: '🏋️', color: 'from-teal-500/15 to-cyan-500/10 border-teal-500/20 hover:border-teal-400/40' },
      { key: 'trainer', label: 'Тренеры',          icon: '🏅', color: 'from-indigo-500/15 to-blue-500/10 border-indigo-500/20 hover:border-indigo-400/40' },
      { key: 'ai-plan', label: 'AI-план питания',  icon: '🤖', color: 'from-sky-500/15 to-blue-500/10 border-sky-500/20 hover:border-sky-400/40',          badge: 'AI' },
      { key: 'promo',   label: 'Акция на абонемент',icon: '🎁', color: 'from-amber-500/15 to-orange-500/10 border-amber-500/20 hover:border-amber-400/40' },
      { key: 'schedule',label: 'Расписание',       icon: '📅', color: 'from-emerald-500/15 to-teal-500/10 border-emerald-500/20 hover:border-emerald-400/40' },
      { key: 'equip',   label: 'Оборудование',     icon: '🏗️', color: 'from-rose-500/15 to-red-500/10 border-rose-500/20 hover:border-rose-400/40' },
    ],
    recentTitle: 'Последние тренировки',
    recentColumns: [
      { key: 'id',      label: '#',        align: 'center' },
      { key: 'client',  label: 'Клиент',   align: 'left'   },
      { key: 'trainer', label: 'Тренер',   align: 'left'   },
      { key: 'class',   label: 'Занятие',  align: 'left'   },
      { key: 'time',    label: 'Время',    align: 'center' },
      { key: 'status',  label: 'Статус',   align: 'center' },
    ],
    heatmapLabel: 'Загрузка зала по часам',
  },

  // ── DEFAULT (универсальный) ─────────────────────────────────
  default: {
    icon: '🏪', label: 'Бизнес',
    accentColor: 'rgba(99,102,241,0.12)',
    accentClass: 'from-(--t-primary)/15 to-(--t-primary)/5 border-(--t-primary)/20',
    kpiLabel: 'Заказы', kpiIcon: '📦',
    quickActions: [
      { key: 'product',  label: 'Добавить товар',   icon: '➕', color: 'from-emerald-500/15 to-teal-500/10 border-emerald-500/20 hover:border-emerald-400/40',   shortcut: 'N' },
      { key: 'order',    label: 'Новый заказ',      icon: '📦', color: 'from-sky-500/15 to-blue-500/10 border-sky-500/20 hover:border-sky-400/40' },
      { key: 'ai',       label: 'AI-конструктор',   icon: '🤖', color: 'from-violet-500/15 to-purple-500/10 border-violet-500/20 hover:border-violet-400/40',   badge: 'AI' },
      { key: 'marketing',label: 'Запустить рекламу',icon: '📣', color: 'from-amber-500/15 to-orange-500/10 border-amber-500/20 hover:border-amber-400/40' },
      { key: 'warehouse',label: 'Инвентаризация',   icon: '📋', color: 'from-rose-500/15 to-pink-500/10 border-rose-500/20 hover:border-rose-400/40' },
      { key: 'team',     label: 'Команда',          icon: '👥', color: 'from-indigo-500/15 to-blue-500/10 border-indigo-500/20 hover:border-indigo-400/40' },
    ],
    recentTitle: 'Последние заказы',
    recentColumns: [
      { key: 'id',       label: '#',        align: 'center' },
      { key: 'customer', label: 'Клиент',   align: 'left'   },
      { key: 'total',    label: 'Сумма',    align: 'right'  },
      { key: 'status',   label: 'Статус',   align: 'center' },
      { key: 'date',     label: 'Дата',     align: 'center' },
    ],
    heatmapLabel: 'Активность по часам и дням',
  },
}

// ─────────────────────────────────────────
// CURRENT VERTICAL
// ─────────────────────────────────────────

const vc = computed<VerticalConfig>(
  () => VERTICAL_CONFIG[props.vertical] ?? VERTICAL_CONFIG.default
)

// ─────────────────────────────────────────
// PERIOD
// ─────────────────────────────────────────

type Period = '7d' | '30d' | '90d' | '1y'

const period = ref<Period>('30d')

const periods = [
  { key: '7d',  label: '7д',    icon: '📅' },
  { key: '30d', label: '30д',   icon: '📆' },
  { key: '90d', label: '90д',   icon: '🗓️' },
  { key: '1y',  label: 'Год',   icon: '📊' },
]

function changePeriod(p: Period) {
  period.value = p
  biz.fetchDashboard(p)
}

// ─────────────────────────────────────────
// SPARKLINE DATA  (SVG path helper)
// ─────────────────────────────────────────

interface SparkSeries {
  label: string
  color: string
  values: number[]
}

const sparkData = ref<SparkSeries[]>([
  { label: 'Выручка',      color: 'var(--t-primary)', values: [42,55,48,61,72,65,80,77,90,88,95,102,98,112] },
  { label: 'Расходы',      color: '#f43f5e',          values: [30,32,28,35,33,30,42,39,44,43,48,52,50,55]   },
  { label: 'Прибыль',      color: '#10b981',          values: [12,23,20,26,39,35,38,38,46,45,47,50,48,57]   },
])

const SPARK_W = 460
const SPARK_H = 120

function sparkPath(vals: number[]): string {
  if (!vals.length) return ''
  const maxV = Math.max(...vals, 1)
  const minV = Math.min(...vals)
  const range = maxV - minV || 1
  const pad = 8
  const w = SPARK_W - pad * 2
  const h = SPARK_H - pad * 2
  const pts = vals.map((v, i) => {
    const x = pad + (i / (vals.length - 1)) * w
    const y = pad + h - ((v - minV) / range) * h
    return [x, y] as [number, number]
  })
  return pts.reduce((d, [x, y], i) => {
    if (i === 0) return `M ${x} ${y}`
    const [px, py] = pts[i - 1]
    const cx = (px + x) / 2
    return `${d} C ${cx} ${py} ${cx} ${y} ${x} ${y}`
  }, '')
}

function sparkArea(vals: number[]): string {
  if (!vals.length) return ''
  const path = sparkPath(vals)
  const pad = 8
  const h = SPARK_H - pad
  const maxX = SPARK_W - pad
  return `${path} L ${maxX} ${h} L ${pad} ${h} Z`
}

// Активная серия графика
const activeSeriesIndex = ref(0)

// ─────────────────────────────────────────
// HEATMAP (7 дней × 24 часа → 168 ячеек)
// ─────────────────────────────────────────

// Генерируем демо-данные тепловой карты (заменить на реальный API)
const HOURS  = Array.from({ length: 24 }, (_, i) => i)
const DAYS   = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']

const heatmapData = ref<number[][]>(
  DAYS.map((_, di) =>
    HOURS.map((h) => {
      // Имитация: пик в будни 10–20, выходные пониже
      const isWeekend = di >= 5
      const base = isWeekend ? 30 : 60
      const peak = (h >= 10 && h <= 20) ? base + Math.random() * 40 : Math.random() * base * 0.6
      return Math.round(peak)
    })
  )
)

function heatColor(val: number): string {
  const t = Math.min(val / 100, 1)
  if (t < 0.25) return 'rgba(99,102,241,0.08)'
  if (t < 0.5)  return 'rgba(99,102,241,0.25)'
  if (t < 0.75) return 'rgba(99,102,241,0.50)'
  return 'rgba(99,102,241,0.85)'
}

const hoveredCell = ref<{ day: number; hour: number; val: number } | null>(null)

// ─────────────────────────────────────────
// DEMO RECENT DATA  (заменить на store)
// ─────────────────────────────────────────

const DEMO_RECENT: Record<string, RecentRow[]> = {
  beauty: [
    { id: 4812, client: 'Анна К.',     master: 'Мария С.',  service: 'Окрашивание',  slot: '11:00',  total: '4 800 ₽',  status: 'confirmed' },
    { id: 4813, client: 'Ольга М.',    master: 'Ирина В.',  service: 'Маникюр',      slot: '12:30',  total: '1 500 ₽',  status: 'in_progress' },
    { id: 4814, client: 'Дмитрий П.', master: 'Алексей Р.',service: 'Стрижка',      slot: '13:00',  total: '900 ₽',    status: 'confirmed' },
    { id: 4815, client: 'Елена В.',    master: 'Мария С.',  service: 'SPA-уход',     slot: '14:30',  total: '6 200 ₽',  status: 'pending' },
    { id: 4816, client: 'Карина Г.',   master: 'Юлия П.',   service: 'Брови',        slot: '15:00',  total: '2 400 ₽',  status: 'confirmed' },
  ],
  taxi: [
    { id: 'TX-24918', driver: 'А. Воронов',  vehicle: 'Kia Rio',        zone: 'ЦАО',  eta: '5 мин', fare: '740 ₽',    status: 'in_progress' },
    { id: 'TX-24921', driver: 'Е. Павлова',  vehicle: 'Skoda Octavia',  zone: 'СЗАО', eta: '7 мин', fare: '520 ₽',    status: 'pickup' },
    { id: 'TX-24925', driver: 'И. Миронов',  vehicle: 'Toyota Camry',   zone: 'ЮАО',  eta: '4 мин', fare: '910 ₽',    status: 'in_progress' },
    { id: 'TX-24927', driver: 'Н. Демина',   vehicle: 'VW Polo',        zone: 'ВАО',  eta: '9 мин', fare: '460 ₽',    status: 'delayed' },
    { id: 'TX-24928', driver: 'К. Романов',  vehicle: 'Hyundai Solaris',zone: 'ЦАО',  eta: '6 мин', fare: '650 ₽',    status: 'pickup' },
  ],
  default: [
    { id: 1042, customer: 'Анна К.',         total: '12 450 ₽',  status: 'delivered', date: '09.04.2026' },
    { id: 1041, customer: 'ООО «Альфа»',    total: '89 300 ₽',  status: 'in_transit',date: '09.04.2026' },
    { id: 1040, customer: 'Дмитрий П.',      total: '3 200 ₽',   status: 'pending',   date: '08.04.2026' },
    { id: 1039, customer: 'ИП Сидоров',      total: '156 000 ₽', status: 'assigned',  date: '08.04.2026' },
    { id: 1038, customer: 'Елена В.',         total: '7 800 ₽',   status: 'delivered', date: '07.04.2026' },
  ],
}

const recentRows = computed<RecentRow[]>(
  () => DEMO_RECENT[props.vertical] ?? DEMO_RECENT.default
)

// ─────────────────────────────────────────
// STATUS MAP
// ─────────────────────────────────────────

const statusMap: Record<string, { text: string; variant: string; pulse?: boolean }> = {
  confirmed:    { text: 'Подтверждён', variant: 'success'  },
  in_progress:  { text: 'В процессе',  variant: 'info',    pulse: true },
  pending:      { text: 'Ожидает',     variant: 'warning'  },
  assigned:     { text: 'Назначен',    variant: 'info'     },
  in_transit:   { text: 'В пути',      variant: 'info',    pulse: true },
  delivered:    { text: 'Доставлен',   variant: 'success'  },
  completed:    { text: 'Выполнен',    variant: 'success'  },
  cancelled:    { text: 'Отменён',     variant: 'danger'   },
  failed:       { text: 'Ошибка',      variant: 'danger'   },
  pickup:       { text: 'Подача',      variant: 'warning'  },
  delayed:      { text: 'Риск SLA',    variant: 'danger',  pulse: true },
  free:         { text: 'Свободен',    variant: 'neutral'  },
}

// ─────────────────────────────────────────
// ALERT ITEMS
// ─────────────────────────────────────────

interface AlertItem {
  id: number
  type: 'fraud' | 'stock' | 'delivery' | 'ai' | 'order'
  icon: string
  text: string
  time: string
  severity: 'critical' | 'warning' | 'info'
  unread: boolean
}

const alerts = ref<AlertItem[]>([
  { id: 1, type: 'fraud',    icon: '🚨', text: 'Fraud-alert: подозрительная транзакция на 48 000 ₽',     time: '2 мин',  severity: 'critical', unread: true  },
  { id: 2, type: 'stock',    icon: '📉', text: 'Low-stock: «Крем-уход» осталось 3 единицы',              time: '12 мин', severity: 'warning',  unread: true  },
  { id: 3, type: 'delivery', icon: '🛵', text: 'Заказ #1041 доставлен клиенту — рейтинг 5⭐',            time: '22 мин', severity: 'info',     unread: true  },
  { id: 4, type: 'ai',       icon: '🤖', text: 'AI-конструктор создал 18 новых рекомендаций за сегодня', time: '1 ч',    severity: 'info',     unread: false },
  { id: 5, type: 'order',    icon: '📦', text: 'Новый B2B-заказ от ООО «Мегастрой» на 420 000 ₽',       time: '2 ч',    severity: 'info',     unread: false },
])

const alertSeverityClass: Record<string, string> = {
  critical: 'border-l-red-500    bg-red-500/5',
  warning:  'border-l-amber-500  bg-amber-500/5',
  info:     'border-l-sky-500    bg-sky-500/5',
}

const alertDotClass: Record<string, string> = {
  critical: 'bg-red-500',
  warning:  'bg-amber-500',
  info:     'bg-sky-500',
}

function dismissAlert(id: number) {
  alerts.value = alerts.value.filter(a => a.id !== id)
}

// ─────────────────────────────────────────
// RIPPLE EFFECT
// ─────────────────────────────────────────

function ripple(e: MouseEvent) {
  const el = e.currentTarget as HTMLElement
  const circle = document.createElement('span')
  const diameter = Math.max(el.clientWidth, el.clientHeight)
  const rect = el.getBoundingClientRect()
  circle.style.cssText = `
    position:absolute; border-radius:50%; pointer-events:none; transform:scale(0);
    inline-size:${diameter}px; block-size:${diameter}px;
    inset-inline-start:${e.clientX - rect.left - diameter / 2}px;
    inset-block-start:${e.clientY - rect.top - diameter / 2}px;
    background:rgba(255,255,255,0.18);
    animation:ripple-anim 0.55s linear forwards;
  `
  el.style.position = 'relative'
  el.style.overflow  = 'hidden'
  el.appendChild(circle)
  setTimeout(() => circle.remove(), 600)
}

// ─────────────────────────────────────────
// BREAKDOWN DATA (today / week / month)
// ─────────────────────────────────────────

const breakdown = ref({
  today: { value: 42_500,   trend:  12.3 },
  week:  { value: 289_700,  trend:   8.1 },
  month: { value: 1_245_000,trend:  15.7 },
})

function fmtNum(n: number): string {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + ' млн'
  if (n >= 1_000)     return (n / 1_000).toFixed(0) + ' тыс'
  return n.toLocaleString('ru')
}

// ─────────────────────────────────────────
// LIFECYCLE
// ─────────────────────────────────────────

let pollTimer: ReturnType<typeof setInterval> | null = null

onMounted(async () => {
  await biz.fetchDashboard(period.value)
  notif.fetchNotifications()
  // Polling для demo: обновлять метрики каждые 30 сек
  pollTimer = setInterval(() => {
    biz.fetchDashboard(period.value)
  }, 30_000)
})

onBeforeUnmount(() => {
  if (pollTimer) clearInterval(pollTimer)
})

// Пересчитать тепловую карту при смене периода (в реальности — fetch с API)
watch(period, () => {
  heatmapData.value = DAYS.map(() =>
    HOURS.map(() => Math.round(Math.random() * 100))
  )
})
</script>

<template>
  <div :class="['space-y-6', compact ? 'space-y-4' : '']">

    <!-- ═══════════════════════════════════════════════════
         1. HEADER
    ══════════════════════════════════════════════════════ -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

      <!-- Business identity -->
      <div class="flex items-center gap-4 min-w-0">
        <!-- Vertical icon badge -->
        <div
          class="relative shrink-0 w-14 h-14 rounded-2xl flex items-center justify-center text-2xl
                 bg-linear-to-br border shadow-lg transition-transform duration-300 hover:scale-105"
          :class="vc.accentClass"
        >
          {{ vc.icon }}
          <!-- Live pulse -->
          <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-(--t-bg)">
            <span class="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-60" />
          </span>
        </div>

        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-bold text-(--t-text) lg:text-2xl truncate">
              <!-- АДАПТАЦИЯ: tenantName из authStore или из props -->
              {{ auth.tenantName || vc.label + ' · Dashboard' }}
            </h1>
            <VBadge v-if="auth.isB2BMode" text="B2B PRO" variant="b2b" size="sm" />
            <VBadge text="LIVE" variant="live" size="xs" :pulse="true" :dot="true" />
          </div>
          <p class="text-sm text-(--t-text-3) mt-0.5 truncate">
            {{ vc.label }} · Управление в реальном времени
          </p>
        </div>
      </div>

      <!-- Right controls -->
      <div class="flex flex-wrap items-center gap-2 sm:flex-nowrap">

        <!-- Notification bell -->
        <button
          class="relative w-10 h-10 rounded-xl flex items-center justify-center
                 bg-(--t-surface) border border-(--t-border)
                 text-(--t-text-2) hover:text-(--t-text)
                 hover:border-(--t-primary)/40 hover:bg-(--t-card-hover)
                 transition-all duration-200 active:scale-95"
          @click="notif.toggle()"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
          </svg>
          <span
            v-if="notif.unreadCount > 0"
            class="absolute -top-1 -right-1 min-w-4.5 h-4.5 rounded-full
                   bg-rose-500 text-white text-[10px] font-bold
                   flex items-center justify-center px-1 ring-2 ring-(--t-bg)"
          >
            {{ notif.unreadCount > 99 ? '99+' : notif.unreadCount }}
          </span>
        </button>

        <!-- Wallet balance chip -->
        <div
          class="flex items-center gap-2 px-3 h-10 rounded-xl
                 bg-(--t-surface) border border-(--t-border)
                 hover:border-emerald-500/40 hover:bg-(--t-card-hover)
                 transition-all duration-200 cursor-pointer group"
        >
          <span class="text-base">💰</span>
          <div>
            <div class="text-xs text-(--t-text-3) leading-none">Баланс</div>
            <div class="text-sm font-semibold text-emerald-400 leading-none">
              {{ Number(auth.walletBalance).toLocaleString('ru') }} ₽
            </div>
          </div>
        </div>

        <!-- Period selector -->
        <VTabs
          v-if="showPeriodSelector"
          :tabs="periods"
          v-model="period"
          variant="segment"
          size="sm"
          @update:model-value="(p: any) => changePeriod(p)"
        />
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         2. B2B CREDIT BAR
    ══════════════════════════════════════════════════════ -->
    <Transition name="slide-down">
      <div
        v-if="auth.isB2BMode"
        class="relative overflow-hidden rounded-2xl p-5
               bg-linear-to-r from-amber-500/10 via-orange-500/8 to-amber-500/10
               border border-amber-500/20"
      >
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
          <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-1">
              <VBadge text="B2B PRO" variant="b2b" size="sm" />
              <span class="text-sm font-semibold text-amber-200">Кредитная линия</span>
              <span class="text-xs text-amber-300/50">·</span>
              <span class="text-xs text-amber-300/50">
                Отсрочка {{ auth.businessGroup?.payment_term_days ?? 14 }} дней
              </span>
            </div>
            <div class="flex items-end gap-3 flex-wrap">
              <div class="text-2xl font-bold text-amber-100">
                {{ fmtNum(auth.creditAvailable) }} ₽
                <span class="text-sm font-normal text-amber-300/60">доступно</span>
              </div>
              <div class="text-xs text-amber-300/50">
                Использовано: <span class="text-amber-300">{{ fmtNum(auth.creditUsed) }} ₽</span>
                из {{ fmtNum(auth.creditLimit) }} ₽
              </div>
            </div>
            <!-- Credit progress -->
            <div class="mt-3 h-1.5 rounded-full bg-amber-900/30 overflow-hidden">
              <div
                class="h-full rounded-full bg-linear-to-r from-amber-400 to-orange-400
                       transition-all duration-700 ease-out"
                :style="{
                  inlineSize: auth.creditLimit
                    ? (auth.creditUsed / auth.creditLimit * 100) + '%'
                    : '0%'
                }"
              />
            </div>
          </div>
          <div class="flex gap-2 shrink-0">
            <VButton variant="b2b" size="sm">💳 Увеличить лимит</VButton>
            <VButton variant="ghost" size="sm">📄 Договор</VButton>
          </div>
        </div>
        <!-- Decorative blobs -->
        <div class="absolute -right-12 -top-12 w-40 h-40 rounded-full bg-amber-500/5 blur-3xl pointer-events-none" />
        <div class="absolute -left-8 -bottom-8 w-32 h-32 rounded-full bg-orange-500/5 blur-2xl pointer-events-none" />
      </div>
    </Transition>

    <!-- ═══════════════════════════════════════════════════
         3. STAT GRID (8 виджетов)
    ══════════════════════════════════════════════════════ -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-4 xl:grid-cols-8 gap-3">
      <VStatCard
        title="GMV"
        :value="fmtNum(biz.metrics.gmv) + ' ₽'"
        icon="💰" :trend="12.5" trend-label="vs прошлый период"
        color="emerald" :loading="biz.isLoading"
        class="col-span-1 sm:col-span-2 xl:col-span-2"
      />
      <VStatCard
        :title="vc.kpiLabel"
        :value="biz.metrics.ordersCount"
        :icon="vc.kpiIcon" :trend="8.3"
        color="primary" :loading="biz.isLoading"
        class="col-span-1 xl:col-span-1"
      />
      <VStatCard
        title="Конверсия"
        :value="biz.metrics.conversionRate + '%'"
        icon="📈" :trend="1.2"
        color="indigo" :loading="biz.isLoading"
        class="col-span-1 xl:col-span-1"
      />
      <VStatCard
        title="Новые клиенты"
        :value="biz.metrics.newUsers"
        icon="🧑" :trend="5.4"
        color="amber" :loading="biz.isLoading"
        class="col-span-1 xl:col-span-1"
      />
      <VStatCard
        title="ARPU"
        :value="fmtNum(biz.metrics.arpu) + ' ₽'"
        icon="🎯" :trend="3.1"
        color="rose" :loading="biz.isLoading"
        class="col-span-1 xl:col-span-1"
      />
      <VStatCard
        title="AI-запросы"
        :value="biz.metrics.aiUsage"
        icon="🤖" :trend="28.5"
        color="primary" :loading="biz.isLoading"
        class="col-span-1 xl:col-span-1"
      />
      <VStatCard
        title="Активных доставок"
        :value="biz.metrics.deliveryActive"
        icon="🛵" :trend="-2.1"
        color="amber" :loading="biz.isLoading"
        class="col-span-1 xl:col-span-1"
      />
    </div>

    <!-- ═══════════════════════════════════════════════════
         4. REVENUE ROW (sparkline + breakdown + heatmap)
    ══════════════════════════════════════════════════════ -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

      <!-- 4a. Sparkline Chart -->
      <VCard
        title="Динамика выручки"
        subtitle="Выручка, расходы, прибыль"
        :loading="biz.isLoading"
        class="lg:col-span-2"
        glow
      >
        <!-- Series selector -->
        <div class="flex flex-wrap gap-2 mb-4">
          <button
            v-for="(series, i) in sparkData"
            :key="series.label"
            :class="[
              'flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium transition-all duration-200',
              activeSeriesIndex === i
                ? 'bg-(--t-primary)/15 ring-1 ring-(--t-primary)/40 text-(--t-primary)'
                : 'text-(--t-text-3) hover:text-(--t-text-2) hover:bg-(--t-card-hover)'
            ]"
            @click="activeSeriesIndex = i"
          >
            <span class="w-2 h-2 rounded-full shrink-0" :style="{ background: series.color }" />
            {{ series.label }}
          </button>
        </div>

        <!-- SVG Sparkline -->
        <div class="relative overflow-hidden rounded-xl bg-(--t-card-hover)/50">
          <svg
            :viewBox="`0 0 ${SPARK_W} ${SPARK_H}`"
            preserveAspectRatio="none"
            class="w-full h-28 lg:h-32"
          >
            <defs>
              <linearGradient :id="`sg-${activeSeriesIndex}`" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%"   :stop-color="sparkData[activeSeriesIndex]?.color" stop-opacity="0.3" />
                <stop offset="100%" :stop-color="sparkData[activeSeriesIndex]?.color" stop-opacity="0" />
              </linearGradient>
            </defs>
            <!-- Area fill -->
            <path
              :d="sparkArea(sparkData[activeSeriesIndex]?.values ?? [])"
              :fill="`url(#sg-${activeSeriesIndex})`"
              class="transition-all duration-500"
            />
            <!-- Line -->
            <path
              :d="sparkPath(sparkData[activeSeriesIndex]?.values ?? [])"
              fill="none"
              :stroke="sparkData[activeSeriesIndex]?.color"
              stroke-width="2"
              stroke-linecap="round"
              class="transition-all duration-500"
            />
          </svg>

          <!-- X-axis labels -->
          <div class="absolute bottom-1.5 inset-x-2 flex justify-between pointer-events-none">
            <span
              v-for="(_, i) in sparkData[0].values"
              :key="i"
              class="text-[9px] text-(--t-text-3)"
            >
              {{ i % 2 === 0 ? `${i + 1}` : '' }}
            </span>
          </div>
        </div>

        <!-- Breakdown chips -->
        <div class="grid grid-cols-3 gap-3 mt-4">
          <div
            v-for="(item, key) in breakdown"
            :key="key"
            class="rounded-xl p-3 bg-(--t-card-hover) transition-all duration-200 hover:-translate-y-0.5"
          >
            <div class="text-xs text-(--t-text-3) capitalize">{{ key === 'today' ? 'Сегодня' : key === 'week' ? 'Неделя' : 'Месяц' }}</div>
            <div class="text-base font-bold text-(--t-text) mt-0.5">
              {{ fmtNum(item.value) }} ₽
            </div>
            <div :class="['text-xs font-semibold mt-0.5', item.trend >= 0 ? 'text-emerald-400' : 'text-rose-400']">
              {{ item.trend >= 0 ? '↑' : '↓' }} {{ Math.abs(item.trend) }}%
            </div>
          </div>
        </div>
      </VCard>

      <!-- 4b. Heatmap (7 дней × 24 часа) -->
      <VCard
        title="Тепловая карта"
        :subtitle="vc.heatmapLabel"
        :loading="biz.isLoading"
      >
        <!-- Tooltip -->
        <Transition name="fade">
          <div
            v-if="hoveredCell"
            class="mb-2 text-xs text-(--t-text-2) bg-(--t-card-hover) rounded-lg px-2.5 py-1.5"
          >
            {{ DAYS[hoveredCell.day] }}, {{ hoveredCell.hour }}:00
            — <span class="font-semibold text-(--t-text)">{{ hoveredCell.val }} ед.</span>
          </div>
        </Transition>

        <!-- Grid: 24 столбца (часы) × 7 строк (дни) -->
        <div class="overflow-x-auto">
          <div class="min-w-70">
            <!-- Hour labels -->
            <div class="flex pl-6 mb-1">
              <span
                v-for="h in HOURS"
                :key="h"
                class="flex-1 text-center text-[8px] text-(--t-text-3)"
              >
                {{ h % 6 === 0 ? h : '' }}
              </span>
            </div>
            <!-- Rows -->
            <div
              v-for="(row, di) in heatmapData"
              :key="di"
              class="flex items-center gap-0 mb-0.5"
            >
              <span class="w-6 shrink-0 text-[9px] text-(--t-text-3) text-right pr-1">
                {{ DAYS[di] }}
              </span>
              <div
                v-for="(val, hi) in row"
                :key="hi"
                class="flex-1 aspect-square rounded-xs cursor-pointer transition-transform duration-100 hover:scale-125 hover:z-10 relative"
                :style="{ background: heatColor(val) }"
                @mouseenter="hoveredCell = { day: di, hour: hi, val }"
                @mouseleave="hoveredCell = null"
              />
            </div>
          </div>
        </div>

        <!-- Legend -->
        <div class="flex items-center gap-2 mt-3 text-[10px] text-(--t-text-3)">
          <span>0</span>
          <div class="flex-1 h-2 rounded-full bg-linear-to-r from-[rgba(99,102,241,0.08)] to-[rgba(99,102,241,0.85)]" />
          <span>100</span>
        </div>
      </VCard>
    </div>

    <!-- ═══════════════════════════════════════════════════
         5. QUICK ACTIONS  (адаптированы под вертикаль)
    ══════════════════════════════════════════════════════ -->
    <VCard title="Быстрые действия" :subtitle="`Частые операции для вертикали «${vc.label}»`">
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <button
          v-for="action in vc.quickActions"
          :key="action.key"
          class="group relative flex flex-col items-center justify-center gap-2
                 rounded-2xl p-4 bg-linear-to-br border
                 transition-all duration-200 cursor-pointer
                 hover:-translate-y-1 active:scale-95 select-none"
          :class="action.color"
          @click="ripple($event)"
          :title="action.shortcut ? `Ctrl+${action.shortcut}` : undefined"
        >
          <!-- AI / count badge -->
          <span
            v-if="action.badge"
            class="absolute top-2 right-2 text-[9px] font-bold px-1.5 py-0.5 rounded-full
                   bg-(--t-primary)/20 text-(--t-primary) ring-1 ring-(--t-primary)/30"
          >
            {{ action.badge }}
          </span>
          <span class="text-2xl transition-transform duration-200 group-hover:scale-110">
            {{ action.icon }}
          </span>
          <span class="text-xs font-medium text-(--t-text) text-center leading-tight">
            {{ action.label }}
          </span>
          <!-- Keyboard shortcut hint -->
          <span
            v-if="action.shortcut"
            class="absolute bottom-2 right-2 text-[8px] text-(--t-text-3)
                   bg-(--t-surface)/60 rounded px-1 py-0.5 opacity-0
                   group-hover:opacity-100 transition-opacity duration-200"
          >
            N
          </span>
        </button>
      </div>
    </VCard>

    <!-- ═══════════════════════════════════════════════════
         6. VERTICAL METRICS STRIP
         Показывает метрики по под-вертикалям тенанта
    ══════════════════════════════════════════════════════ -->
    <VCard
      title="Вертикали тенанта"
      subtitle="Сравнение выручки и конверсии по направлениям"
      :loading="biz.isLoading"
    >
      <div
        v-if="biz.verticals.length === 0 && !biz.isLoading"
        class="flex flex-col items-center justify-center py-8 text-(--t-text-3)"
      >
        <span class="text-4xl mb-2">📊</span>
        <p class="text-sm">Данные по вертикалям загружаются...</p>
      </div>

      <!-- Demo fallback (заменить на biz.verticals) -->
      <div
        v-else
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3"
      >
        <div
          v-for="v in [
            { name: vc.label,   icon: vc.icon, revenue: 480_000, orders: 342, conversion: 4.8, trend: 12.3 },
            { name: 'Мебель',  icon: '🛋️',  revenue: 390_000, orders: 87,  conversion: 3.2, trend: -1.2 },
            { name: 'Еда',     icon: '🍕',  revenue: 245_000, orders: 1240,conversion: 7.1, trend: 18.5 },
            { name: 'Одежда',  icon: '👗',  revenue: 130_000, orders: 196, conversion: 5.4, trend: 4.6  },
          ]"
          :key="v.name"
          class="group flex flex-col gap-2 p-4 rounded-xl bg-(--t-card-hover)
                 border border-transparent hover:border-(--t-primary)/20
                 transition-all duration-200 cursor-pointer hover:-translate-y-0.5"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="text-xl">{{ v.icon }}</span>
              <span class="text-sm font-semibold text-(--t-text)">{{ v.name }}</span>
            </div>
            <span :class="['text-xs font-semibold', v.trend >= 0 ? 'text-emerald-400' : 'text-rose-400']">
              {{ v.trend >= 0 ? '↑' : '↓' }} {{ Math.abs(v.trend) }}%
            </span>
          </div>
          <div class="text-lg font-bold text-(--t-text)">{{ fmtNum(v.revenue) }} ₽</div>
          <div class="flex items-center justify-between text-xs text-(--t-text-3)">
            <span>{{ v.orders.toLocaleString('ru') }} операций</span>
            <span class="text-(--t-primary)">{{ v.conversion }}% конверсия</span>
          </div>
          <!-- Mini-bar -->
          <div class="h-1 rounded-full bg-(--t-border) overflow-hidden">
            <div
              class="h-full rounded-full bg-linear-to-r from-(--t-primary) to-sky-400 transition-all duration-700"
              :style="{ inlineSize: Math.min(v.conversion * 10, 100) + '%' }"
            />
          </div>
        </div>
      </div>
    </VCard>

    <!-- ═══════════════════════════════════════════════════
         7. RECENT LIST  (колонки зависят от вертикали)
    ══════════════════════════════════════════════════════ -->
    <VCard
      :title="vc.recentTitle"
      :loading="biz.isLoading"
      :no-padding="true"
    >
      <template #header>
        <div class="flex items-center justify-between px-5 pt-5">
          <div>
            <h3 class="text-base font-semibold text-(--t-text)">{{ vc.recentTitle }}</h3>
            <p class="text-xs text-(--t-text-3) mt-0.5">
              {{ recentRows.length }} записей · обновлено только что
            </p>
          </div>
          <div class="flex items-center gap-2">
            <VBadge text="LIVE" variant="live" :dot="true" :pulse="true" />
            <VButton variant="ghost" size="xs">Все →</VButton>
          </div>
        </div>
      </template>

      <!-- Responsive table -->
      <div class="overflow-x-auto pb-1">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-(--t-border)">
              <th
                v-for="col in vc.recentColumns"
                :key="col.key"
                :class="[
                  'py-3 px-4 text-xs font-semibold uppercase tracking-wider text-(--t-text-3)',
                  col.align === 'right'  ? 'text-right'  : '',
                  col.align === 'center' ? 'text-center' : '',
                  col.align === 'left' || !col.align ? 'text-left' : '',
                ]"
              >
                {{ col.label }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-(--t-border)/50">
            <tr
              v-for="row in recentRows"
              :key="row.id as string | number"
              class="group transition-colors duration-150 hover:bg-(--t-card-hover)
                     cursor-pointer"
            >
              <td
                v-for="col in vc.recentColumns"
                :key="col.key"
                :class="[
                  'py-3 px-4 text-(--t-text-2)',
                  col.align === 'right'  ? 'text-right font-medium text-(--t-text)' : '',
                  col.align === 'center' ? 'text-center' : '',
                ]"
              >
                <!-- Status column: special render -->
                <template v-if="col.key === 'status'">
                  <VBadge
                    :text="statusMap[row.status as string]?.text ?? String(row.status)"
                    :variant="statusMap[row.status as string]?.variant ?? 'neutral'"
                    :pulse="statusMap[row.status as string]?.pulse"
                    :dot="true"
                  />
                </template>
                <!-- ID column -->
                <template v-else-if="col.key === 'id'">
                  <span class="font-mono text-xs text-(--t-text-3)">#{{ row.id }}</span>
                </template>
                <!-- Default -->
                <template v-else>
                  {{ row[col.key] ?? '—' }}
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Load more -->
      <div class="flex justify-center py-4 border-t border-(--t-border)/50">
        <VButton variant="ghost" size="sm">Загрузить ещё</VButton>
      </div>
    </VCard>

    <!-- ═══════════════════════════════════════════════════
         8. ALERTS PANEL
    ══════════════════════════════════════════════════════ -->
    <VCard title="Оповещения" subtitle="Fraud, low-stock, доставка, AI">
      <TransitionGroup name="alert-list" tag="div" class="space-y-2">
        <div
          v-for="alert in alerts"
          :key="alert.id"
          :class="[
            'flex items-start gap-3 p-3.5 rounded-xl border-l-4',
            'transition-all duration-200 hover:bg-(--t-card-hover)',
            alertSeverityClass[alert.severity],
          ]"
        >
          <!-- Unread dot -->
          <div class="shrink-0 mt-0.5 relative">
            <span class="text-lg">{{ alert.icon }}</span>
            <span
              v-if="alert.unread"
              :class="['absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full ring-2 ring-(--t-bg)', alertDotClass[alert.severity]]"
            />
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-sm text-(--t-text) leading-snug">{{ alert.text }}</p>
            <p class="text-xs text-(--t-text-3) mt-0.5">{{ alert.time }} назад</p>
          </div>

          <!-- Dismiss -->
          <button
            class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center
                   text-(--t-text-3) hover:text-(--t-text)
                   hover:bg-(--t-surface) transition-all duration-150
                   opacity-0 group-hover:opacity-100"
            @click="dismissAlert(alert.id)"
            aria-label="Закрыть"
          >
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </TransitionGroup>

      <!-- Empty state -->
      <div
        v-if="alerts.length === 0"
        class="flex flex-col items-center py-8 text-(--t-text-3)"
      >
        <span class="text-3xl mb-2">✅</span>
        <p class="text-sm">Все тихо — новых оповещений нет</p>
      </div>
    </VCard>

  </div>
</template>

<style scoped>
/* ── Ripple keyframe ────────────────────────────────── */
@keyframes ripple-anim {
  to { transform: scale(3.5); opacity: 0; }
}

/* ── Transitions ────────────────────────────────────── */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.35s cubic-bezier(.4,0,.2,1);
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Alert list transitions */
.alert-list-enter-active { transition: all 0.3s ease-out; }
.alert-list-leave-active  { transition: all 0.25s ease-in; position: absolute; inline-size: 100%; }
.alert-list-enter-from    { opacity: 0; transform: translateX(-12px); }
.alert-list-leave-to      { opacity: 0; transform: translateX(12px); }
.alert-list-move          { transition: transform 0.3s ease; }

/* ── Scrollbar styling ──────────────────────────────── */
.overflow-x-auto::-webkit-scrollbar { block-size: 4px; }
.overflow-x-auto::-webkit-scrollbar-track { background: transparent; }
.overflow-x-auto::-webkit-scrollbar-thumb {
  background: var(--t-border);
  border-radius: 9999px;
}
</style>
