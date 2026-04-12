<script setup lang="ts">
/**
 * TenantCRM.vue — Главная страница CRM в B2B Tenant Dashboard
 *
 * Полнофункциональная CRM для управления клиентской базой
 * всех 127 вертикалей CatVRF:
 *   Beauty (клиенты салона)  · Taxi (пассажиры)
 *   Food (заказчики)         · Hotels (гости)
 *   RealEstate (покупатели / арендаторы)
 *   Flowers (заказчики)      · Fashion (покупатели)
 *   Furniture (клиенты)      · Fitness (члены клуба)
 *   Travel (туристы)         · default (универсальный)
 *
 * ─────────────────────────────────────────────────────────────
 *  Функционал:
 *   1.  Верхняя панель: глобальный поиск + фильтры (статус,
 *       сегмент, источник, дата) + «Добавить клиента» + массовые
 *       действия
 *   2.  KPI-виджеты: всего, новые, VIP, отток, средний LTV
 *   3.  Левая sidebar с сегментами и быстрыми фильтрами
 *   4.  Таблица клиентов (desktop) / карточки (mobile)
 *   5.  Полная карточка клиента (модальное окно)
 *   6.  Массовые действия (рассылка, тег, экспорт)
 *   7.  Full-screen режим
 *   8.  VERTICAL_CRM_CONFIG — терминология по вертикалям
 *   9.  RFM-сегментация (Recency, Frequency, Monetary)
 *  10.  История взаимодействий (звонки, визиты, заказы)
 *  11.  Пагинация + живой поиск
 * ─────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'

import VCard   from '../UI/VCard.vue'
import VButton from '../UI/VButton.vue'
import VBadge  from '../UI/VBadge.vue'
import VTabs   from '../UI/VTabs.vue'
import VModal  from '../UI/VModal.vue'
import VInput  from '../UI/VInput.vue'
import { useAuth, useTenant } from '@/stores'

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  TYPES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

type ClientSegment  = 'vip' | 'loyal' | 'regular' | 'new' | 'at_risk' | 'dormant' | 'lost'
type ClientSource   = 'organic' | 'referral' | 'ads' | 'social' | 'b2b_api' | 'manual' | 'partner'
type ClientStatus   = 'active' | 'inactive' | 'blocked' | 'pending'
type InteractionType = 'call' | 'email' | 'visit' | 'order' | 'complaint' | 'feedback' | 'sms' | 'chat'

interface CrmClient {
  id:              number | string
  fullName:        string
  phone:           string
  email:           string
  avatar?:         string
  segment:         ClientSegment
  source:          ClientSource
  status:          ClientStatus
  isB2B:           boolean
  companyName?:    string
  inn?:            string
  tags:            string[]
  // RFM
  rfmScore:        number          // 0-100 сводный RFM-балл
  recencyDays:     number          // дней с последнего визита/заказа
  frequencyMonth:  number          // визитов/заказов за 30 дней
  monetaryTotal:   number          // LTV ₽
  monetaryMonth:   number          // оборот за текущий месяц ₽
  // Dates
  firstVisitAt:    string          // ISO
  lastVisitAt:     string          // ISO
  createdAt:       string          // ISO
  // Stats
  totalOrders:     number
  totalVisits:     number
  avgCheck:        number          // средний чек ₽
  bonusBalance:    number          // бонусный баланс ₽
  rating:          number          // оценка 0-5
  nps?:            number          // NPS −100..+100
  // Vertical-specific
  verticalData?:   Record<string, unknown>
  correlationId?:  string
  notes?:          string
}

interface Interaction {
  id:           number | string
  clientId:     number | string
  type:         InteractionType
  title:        string
  description?: string
  date:         string             // ISO
  employeeName: string
  result?:      string
  correlationId?: string
}

interface CrmSegmentInfo {
  id:    ClientSegment
  label: string
  icon:  string
  color: string
  bg:    string
  dot:   string
  count: number
}

interface CrmStats {
  totalClients:      number
  activeClients:     number
  newThisMonth:      number
  vipClients:        number
  atRiskClients:     number
  lostClients:       number
  avgLtv:            number        // средний LTV ₽
  avgCheck:          number        // средний чек ₽
  retentionRate:     number        // процент удержания 0-100
  npsAvg:            number        // средний NPS
  revenueThisMonth:  number        // выручка за месяц ₽
  repeatRate:        number        // процент повторных 0-100
}

interface CrmFilter {
  search:      string
  segment:     string
  status:      string
  source:      string
  tag:         string
  dateFrom:    string
  dateTo:      string
  sortBy:      string
  sortDir:     'asc' | 'desc'
}

interface VerticalCrmConfig {
  label:           string
  icon:            string
  clientLabel:     string          // «Клиент» / «Гость» / «Пассажир»
  clientLabelPlural: string        // «Клиенты» / «Гости» / «Пассажиры»
  visitLabel:      string          // «Визит» / «Поездка» / «Заказ»
  visitLabelPlural: string         // «Визиты» / «Поездки» / «Заказы»
  extraColumns:    Array<{ key: string; label: string }>
  quickActions:    Array<{ key: string; label: string; icon: string }>
  segmentLabels:   Record<string, string>
  kpiLabels:       { total: string; newMonth: string; atRisk: string; avgLtv: string }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  PROPS & EMITS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const props = withDefaults(defineProps<{
  vertical?:      string
  clients?:       CrmClient[]
  stats?:         CrmStats
  interactions?:  Interaction[]
  tags?:          string[]
  totalClients?:  number
  loading?:       boolean
  perPage?:       number
}>(), {
  vertical:      'default',
  clients:       () => [],
  stats:         () => ({
    totalClients: 0, activeClients: 0, newThisMonth: 0, vipClients: 0,
    atRiskClients: 0, lostClients: 0, avgLtv: 0, avgCheck: 0,
    retentionRate: 0, npsAvg: 0, revenueThisMonth: 0, repeatRate: 0,
  }),
  interactions:  () => [],
  tags:          () => [],
  totalClients:  0,
  loading:       false,
  perPage:       25,
})

const emit = defineEmits<{
  'client-click':       [client: CrmClient]
  'client-create':      []
  'client-edit':        [client: CrmClient]
  'client-delete':      [clientIds: Array<number | string>]
  'client-call':        [client: CrmClient]
  'client-email':       [client: CrmClient]
  'client-sms':         [client: CrmClient]
  'add-interaction':    [clientId: number | string, type: InteractionType]
  'add-tag':            [clientIds: Array<number | string>, tag: string]
  'segment-change':     [segmentId: string]
  'filter-change':      [filters: CrmFilter]
  'sort-change':        [sortBy: string, sortDir: 'asc' | 'desc']
  'page-change':        [page: number]
  'bulk-action':        [action: string, clientIds: Array<number | string>]
  'export':             [format: 'xlsx' | 'csv']
  'load-more':          []
}>()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STORES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const auth     = useAuth()
const business = useTenant()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  VERTICAL CRM CONFIG
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const VERTICAL_CRM_CONFIG: Record<string, VerticalCrmConfig> = {
  // ── BEAUTY ───────────────────────────────
  beauty: {
    label: 'CRM салона', icon: '💄',
    clientLabel: 'Клиент', clientLabelPlural: 'Клиенты',
    visitLabel: 'Визит', visitLabelPlural: 'Визиты',
    extraColumns: [
      { key: 'favoriteMaster', label: 'Любимый мастер' },
      { key: 'lastService',    label: 'Последняя услуга' },
    ],
    quickActions: [
      { key: 'book',     label: 'Записать к мастеру', icon: '📅' },
      { key: 'call',     label: 'Позвонить',          icon: '📞' },
      { key: 'sms',      label: 'Отправить SMS',      icon: '💬' },
      { key: 'promo',    label: 'Персональная акция',  icon: '🎁' },
      { key: 'export',   label: 'Экспорт XLSX',       icon: '📥' },
    ],
    segmentLabels: {
      vip: 'VIP-клиенты', loyal: 'Постоянные', regular: 'Обычные',
      new: 'Новые', at_risk: 'Рискуют уйти', dormant: 'Спящие', lost: 'Потерянные',
    },
    kpiLabels: { total: 'Клиентов в базе', newMonth: 'Новых за месяц', atRisk: 'Рискуют уйти', avgLtv: 'Средний LTV' },
  },

  // ── TAXI ─────────────────────────────────
  taxi: {
    label: 'CRM таксопарка', icon: '🚕',
    clientLabel: 'Пассажир', clientLabelPlural: 'Пассажиры',
    visitLabel: 'Поездка', visitLabelPlural: 'Поездки',
    extraColumns: [
      { key: 'favoriteRoute', label: 'Популярный маршрут' },
      { key: 'rideClass',     label: 'Класс поездок' },
    ],
    quickActions: [
      { key: 'call',     label: 'Позвонить',          icon: '📞' },
      { key: 'sms',      label: 'Отправить SMS',      icon: '💬' },
      { key: 'promo',    label: 'Промокод',            icon: '🎁' },
      { key: 'export',   label: 'Экспорт XLSX',       icon: '📥' },
    ],
    segmentLabels: {
      vip: 'VIP-пассажиры', loyal: 'Постоянные', regular: 'Обычные',
      new: 'Новые', at_risk: 'Рискуют уйти', dormant: 'Неактивные', lost: 'Потерянные',
    },
    kpiLabels: { total: 'Пассажиров в базе', newMonth: 'Новых за месяц', atRisk: 'Рискуют уйти', avgLtv: 'Средний LTV' },
  },

  // ── FOOD ─────────────────────────────────
  food: {
    label: 'CRM ресторана', icon: '🍽️',
    clientLabel: 'Заказчик', clientLabelPlural: 'Заказчики',
    visitLabel: 'Заказ', visitLabelPlural: 'Заказы',
    extraColumns: [
      { key: 'dietaryPrefs', label: 'Диета / Аллергены' },
      { key: 'favoriteDish', label: 'Любимое блюдо' },
    ],
    quickActions: [
      { key: 'call',     label: 'Позвонить',              icon: '📞' },
      { key: 'sms',      label: 'Отправить SMS',          icon: '💬' },
      { key: 'promo',    label: 'Персональная скидка',     icon: '🎁' },
      { key: 'reorder',  label: 'Повторить последний заказ', icon: '🔄' },
      { key: 'export',   label: 'Экспорт XLSX',           icon: '📥' },
    ],
    segmentLabels: {
      vip: 'VIP-гурманы', loyal: 'Постоянные', regular: 'Обычные',
      new: 'Новые', at_risk: 'Рискуют уйти', dormant: 'Спящие', lost: 'Потерянные',
    },
    kpiLabels: { total: 'Заказчиков в базе', newMonth: 'Новых за месяц', atRisk: 'Рискуют уйти', avgLtv: 'Средний LTV' },
  },

  // ── HOTEL ────────────────────────────────
  hotel: {
    label: 'CRM отеля', icon: '🏨',
    clientLabel: 'Гость', clientLabelPlural: 'Гости',
    visitLabel: 'Пребывание', visitLabelPlural: 'Пребывания',
    extraColumns: [
      { key: 'roomPreference', label: 'Предпочтения номера' },
      { key: 'loyaltyTier',    label: 'Уровень лояльности' },
    ],
    quickActions: [
      { key: 'book',     label: 'Забронировать',       icon: '📅' },
      { key: 'call',     label: 'Позвонить',           icon: '📞' },
      { key: 'email',    label: 'Отправить e-mail',    icon: '✉️' },
      { key: 'upgrade',  label: 'Предложить апгрейд',  icon: '⬆️' },
      { key: 'export',   label: 'Экспорт XLSX',        icon: '📥' },
    ],
    segmentLabels: {
      vip: 'VIP-гости', loyal: 'Постоянные', regular: 'Обычные',
      new: 'Новые гости', at_risk: 'Рискуют уйти', dormant: 'Спящие', lost: 'Потерянные',
    },
    kpiLabels: { total: 'Гостей в базе', newMonth: 'Новых за месяц', atRisk: 'Рискуют уйти', avgLtv: 'Средний LTV' },
  },

  // ── REAL ESTATE ──────────────────────────
  realEstate: {
    label: 'CRM агентства', icon: '🏢',
    clientLabel: 'Клиент', clientLabelPlural: 'Клиенты',
    visitLabel: 'Показ', visitLabelPlural: 'Показы',
    extraColumns: [
      { key: 'budget',        label: 'Бюджет' },
      { key: 'propertyType',  label: 'Тип недвижимости' },
    ],
    quickActions: [
      { key: 'call',       label: 'Позвонить',         icon: '📞' },
      { key: 'schedule',   label: 'Назначить показ',   icon: '📅' },
      { key: 'send-offers', label: 'Отправить подборку', icon: '📩' },
      { key: 'export',     label: 'Экспорт XLSX',      icon: '📥' },
    ],
    segmentLabels: {
      vip: 'Премиум-клиенты', loyal: 'Постоянные', regular: 'Обычные',
      new: 'Новые обращения', at_risk: 'Теряем интерес', dormant: 'Спящие', lost: 'Потерянные',
    },
    kpiLabels: { total: 'Клиентов в базе', newMonth: 'Новых за месяц', atRisk: 'Теряют интерес', avgLtv: 'Средний LTV' },
  },

  // ── FLOWERS ──────────────────────────────
  flowers: {
    label: 'CRM цветочного', icon: '💐',
    clientLabel: 'Заказчик', clientLabelPlural: 'Заказчики',
    visitLabel: 'Заказ', visitLabelPlural: 'Заказы',
    extraColumns: [
      { key: 'importantDates', label: 'Важные даты' },
      { key: 'favoriteFlower', label: 'Любимые цветы' },
    ],
    quickActions: [
      { key: 'call',      label: 'Позвонить',              icon: '📞' },
      { key: 'sms',       label: 'Отправить SMS',          icon: '💬' },
      { key: 'reminder',  label: 'Напоминание о дате',     icon: '🔔' },
      { key: 'promo',     label: 'Персональная скидка',     icon: '🎁' },
      { key: 'export',    label: 'Экспорт XLSX',           icon: '📥' },
    ],
    segmentLabels: {
      vip: 'VIP-заказчики', loyal: 'Постоянные', regular: 'Обычные',
      new: 'Новые', at_risk: 'Рискуют уйти', dormant: 'Спящие', lost: 'Потерянные',
    },
    kpiLabels: { total: 'Заказчиков в базе', newMonth: 'Новых за месяц', atRisk: 'Рискуют уйти', avgLtv: 'Средний LTV' },
  },

  // ── FASHION ──────────────────────────────
  fashion: {
    label: 'CRM магазина', icon: '👗',
    clientLabel: 'Покупатель', clientLabelPlural: 'Покупатели',
    visitLabel: 'Покупка', visitLabelPlural: 'Покупки',
    extraColumns: [
      { key: 'size',          label: 'Размер' },
      { key: 'favoriteBrand', label: 'Любимый бренд' },
    ],
    quickActions: [
      { key: 'call',     label: 'Позвонить',             icon: '📞' },
      { key: 'lookbook', label: 'Отправить лукбук',      icon: '📸' },
      { key: 'promo',    label: 'Персональная скидка',    icon: '🎁' },
      { key: 'export',   label: 'Экспорт XLSX',          icon: '📥' },
    ],
    segmentLabels: {
      vip: 'VIP-покупатели', loyal: 'Постоянные', regular: 'Обычные',
      new: 'Новые', at_risk: 'Рискуют уйти', dormant: 'Спящие', lost: 'Потерянные',
    },
    kpiLabels: { total: 'Покупателей в базе', newMonth: 'Новых за месяц', atRisk: 'Рискуют уйти', avgLtv: 'Средний LTV' },
  },

  // ── FURNITURE ────────────────────────────
  furniture: {
    label: 'CRM мебельного', icon: '🛋️',
    clientLabel: 'Клиент', clientLabelPlural: 'Клиенты',
    visitLabel: 'Заказ', visitLabelPlural: 'Заказы',
    extraColumns: [
      { key: 'projectType', label: 'Тип проекта' },
      { key: 'budget',       label: 'Бюджет' },
    ],
    quickActions: [
      { key: 'call',      label: 'Позвонить',           icon: '📞' },
      { key: 'design',    label: 'Отправить дизайн-проект', icon: '🎨' },
      { key: 'promo',     label: 'Персональная скидка',  icon: '🎁' },
      { key: 'export',    label: 'Экспорт XLSX',        icon: '📥' },
    ],
    segmentLabels: {
      vip: 'VIP-клиенты', loyal: 'Постоянные', regular: 'Обычные',
      new: 'Новые', at_risk: 'Рискуют уйти', dormant: 'Спящие', lost: 'Потерянные',
    },
    kpiLabels: { total: 'Клиентов в базе', newMonth: 'Новых за месяц', atRisk: 'Рискуют уйти', avgLtv: 'Средний LTV' },
  },

  // ── FITNESS ──────────────────────────────
  fitness: {
    label: 'CRM клуба', icon: '💪',
    clientLabel: 'Член клуба', clientLabelPlural: 'Члены клуба',
    visitLabel: 'Тренировка', visitLabelPlural: 'Тренировки',
    extraColumns: [
      { key: 'membership',    label: 'Абонемент' },
      { key: 'favoriteClass', label: 'Любимые занятия' },
    ],
    quickActions: [
      { key: 'call',     label: 'Позвонить',           icon: '📞' },
      { key: 'renew',    label: 'Продлить абонемент',  icon: '🔄' },
      { key: 'promo',    label: 'Персональная акция',   icon: '🎁' },
      { key: 'export',   label: 'Экспорт XLSX',        icon: '📥' },
    ],
    segmentLabels: {
      vip: 'VIP-члены', loyal: 'Постоянные', regular: 'Обычные',
      new: 'Новички', at_risk: 'Рискуют уйти', dormant: 'Заморозили', lost: 'Потерянные',
    },
    kpiLabels: { total: 'Членов клуба', newMonth: 'Новых за месяц', atRisk: 'Рискуют уйти', avgLtv: 'Средний LTV' },
  },

  // ── TRAVEL ───────────────────────────────
  travel: {
    label: 'CRM турагентства', icon: '✈️',
    clientLabel: 'Турист', clientLabelPlural: 'Туристы',
    visitLabel: 'Бронирование', visitLabelPlural: 'Бронирования',
    extraColumns: [
      { key: 'preferredDest', label: 'Любимое направление' },
      { key: 'travelStyle',   label: 'Стиль путешествий' },
    ],
    quickActions: [
      { key: 'call',     label: 'Позвонить',              icon: '📞' },
      { key: 'offer',    label: 'Отправить подборку туров', icon: '🌴' },
      { key: 'promo',    label: 'Горящее предложение',     icon: '🔥' },
      { key: 'export',   label: 'Экспорт XLSX',           icon: '📥' },
    ],
    segmentLabels: {
      vip: 'VIP-туристы', loyal: 'Постоянные', regular: 'Обычные',
      new: 'Новые', at_risk: 'Теряем', dormant: 'Спящие', lost: 'Потерянные',
    },
    kpiLabels: { total: 'Туристов в базе', newMonth: 'Новых за месяц', atRisk: 'Теряем', avgLtv: 'Средний LTV' },
  },

  // ── DEFAULT ──────────────────────────────
  default: {
    label: 'CRM', icon: '👥',
    clientLabel: 'Клиент', clientLabelPlural: 'Клиенты',
    visitLabel: 'Визит', visitLabelPlural: 'Визиты',
    extraColumns: [],
    quickActions: [
      { key: 'call',     label: 'Позвонить',         icon: '📞' },
      { key: 'email',    label: 'Отправить e-mail',  icon: '✉️' },
      { key: 'sms',      label: 'Отправить SMS',     icon: '💬' },
      { key: 'export',   label: 'Экспорт XLSX',      icon: '📥' },
    ],
    segmentLabels: {
      vip: 'VIP', loyal: 'Постоянные', regular: 'Обычные',
      new: 'Новые', at_risk: 'Рискуют уйти', dormant: 'Спящие', lost: 'Потерянные',
    },
    kpiLabels: { total: 'Клиентов в базе', newMonth: 'Новых за месяц', atRisk: 'Рискуют уйти', avgLtv: 'Средний LTV' },
  },
}

const vc = computed<VerticalCrmConfig>(() =>
  VERTICAL_CRM_CONFIG[props.vertical] ?? VERTICAL_CRM_CONFIG.default
)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  SEGMENT MAP
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const SEGMENT_MAP: Record<ClientSegment, { label: string; icon: string; color: string; dot: string; bg: string }> = {
  vip:      { label: 'VIP',          icon: '👑', color: 'text-amber-400',   dot: 'bg-amber-400',   bg: 'bg-amber-500/12' },
  loyal:    { label: 'Постоянные',   icon: '💎', color: 'text-violet-400',  dot: 'bg-violet-400',  bg: 'bg-violet-500/12' },
  regular:  { label: 'Обычные',      icon: '👤', color: 'text-sky-400',     dot: 'bg-sky-400',     bg: 'bg-sky-500/12' },
  new:      { label: 'Новые',        icon: '🌱', color: 'text-emerald-400', dot: 'bg-emerald-400', bg: 'bg-emerald-500/12' },
  at_risk:  { label: 'Рискуют уйти', icon: '⚠️', color: 'text-orange-400', dot: 'bg-orange-400',  bg: 'bg-orange-500/12' },
  dormant:  { label: 'Спящие',       icon: '😴', color: 'text-zinc-400',    dot: 'bg-zinc-400',    bg: 'bg-zinc-500/12' },
  lost:     { label: 'Потерянные',   icon: '💀', color: 'text-rose-400',    dot: 'bg-rose-400',    bg: 'bg-rose-500/12' },
}

const STATUS_MAP: Record<ClientStatus, { label: string; color: string; dot: string; bg: string }> = {
  active:   { label: 'Активен',   color: 'text-emerald-400', dot: 'bg-emerald-400', bg: 'bg-emerald-500/12' },
  inactive: { label: 'Неактивен', color: 'text-zinc-400',    dot: 'bg-zinc-400',    bg: 'bg-zinc-500/12' },
  blocked:  { label: 'Заблокирован', color: 'text-rose-400', dot: 'bg-rose-400',    bg: 'bg-rose-500/12' },
  pending:  { label: 'На модерации', color: 'text-amber-400', dot: 'bg-amber-400',  bg: 'bg-amber-500/12' },
}

const SOURCE_MAP: Record<ClientSource, { label: string; icon: string }> = {
  organic:  { label: 'Органика',     icon: '🌿' },
  referral: { label: 'Реферал',      icon: '🤝' },
  ads:      { label: 'Реклама',      icon: '📢' },
  social:   { label: 'Соц. сети',    icon: '📱' },
  b2b_api:  { label: 'B2B API',      icon: '🔗' },
  manual:   { label: 'Ручной ввод',  icon: '✏️' },
  partner:  { label: 'Партнёр',      icon: '🏢' },
}

const INTERACTION_MAP: Record<InteractionType, { label: string; icon: string; color: string }> = {
  call:      { label: 'Звонок',       icon: '📞', color: 'text-sky-400' },
  email:     { label: 'E-mail',       icon: '✉️', color: 'text-indigo-400' },
  visit:     { label: 'Визит',        icon: '🏠', color: 'text-emerald-400' },
  order:     { label: 'Заказ',        icon: '🛒', color: 'text-violet-400' },
  complaint: { label: 'Жалоба',       icon: '😠', color: 'text-rose-400' },
  feedback:  { label: 'Отзыв',        icon: '💬', color: 'text-amber-400' },
  sms:       { label: 'SMS',          icon: '💬', color: 'text-teal-400' },
  chat:      { label: 'Чат',          icon: '💬', color: 'text-cyan-400' },
}

const ALL_SEGMENTS: ClientSegment[] = ['vip', 'loyal', 'regular', 'new', 'at_risk', 'dormant', 'lost']

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STATE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const rootEl            = ref<HTMLElement | null>(null)
const scrollSentinel    = ref<HTMLElement | null>(null)
const isFullscreen      = ref(false)
const showSidebar       = ref(true)
const showClientModal   = ref(false)
const showActionsMenu   = ref(false)
const showFilterDrawer  = ref(false)
const showBulkMenu      = ref(false)
const selectedClient    = ref<CrmClient | null>(null)
const currentPage       = ref(1)
const modalTab          = ref<'info' | 'history' | 'analytics'>('info')

// Bulk
const selectedIds = reactive<Set<number | string>>(new Set())
const isBulkMode  = ref(false)
const selectAll   = ref(false)

// View
const viewAs = ref<'table' | 'cards'>('table')

// Filters
const filters = reactive<CrmFilter>({
  search:    '',
  segment:   '',
  status:    '',
  source:    '',
  tag:       '',
  dateFrom:  '',
  dateTo:    '',
  sortBy:    'lastVisitAt',
  sortDir:   'desc',
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  COMPUTED
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const totalPages = computed(() => Math.ceil(props.totalClients / props.perPage) || 1)

const hasActiveFilters = computed(() =>
  filters.segment !== '' || filters.status !== '' ||
  filters.source !== ''  || filters.tag !== '' ||
  filters.dateFrom !== '' || filters.dateTo !== ''
)

const segmentCounts = computed<Record<ClientSegment, number>>(() => {
  const map = { vip: 0, loyal: 0, regular: 0, new: 0, at_risk: 0, dormant: 0, lost: 0 }
  for (const c of props.clients) {
    if (c.segment in map) map[c.segment]++
  }
  return map
})

/** Живая фильтрация на клиенте */
const filteredClients = computed(() => {
  let result = [...props.clients]
  const q = filters.search.toLowerCase().trim()
  if (q) {
    result = result.filter(
      (c) =>
        c.fullName.toLowerCase().includes(q) ||
        c.phone.includes(q) ||
        c.email.toLowerCase().includes(q) ||
        (c.companyName && c.companyName.toLowerCase().includes(q)) ||
        (c.inn && c.inn.includes(q))
    )
  }
  if (filters.segment) result = result.filter((c) => c.segment === filters.segment)
  if (filters.status)  result = result.filter((c) => c.status === filters.status)
  if (filters.source)  result = result.filter((c) => c.source === filters.source)
  if (filters.tag)     result = result.filter((c) => c.tags.includes(filters.tag))
  // Sort
  const dir = filters.sortDir === 'asc' ? 1 : -1
  result.sort((a, b) => {
    const av = Object(a)[filters.sortBy]
    const bv = Object(b)[filters.sortBy]
    if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir
    return String(av ?? '').localeCompare(String(bv ?? ''), 'ru') * dir
  })
  return result
})

const paginatedClients = computed(() => {
  const start = (currentPage.value - 1) * props.perPage
  return filteredClients.value.slice(start, start + props.perPage)
})

const isAllSelected = computed(() =>
  filteredClients.value.length > 0 &&
  filteredClients.value.every((c) => selectedIds.has(c.id))
)

const clientInteractions = computed(() => {
  if (!selectedClient.value) return []
  return props.interactions
    .filter((i) => String(i.clientId) === String(selectedClient.value!.id))
    .slice(0, 20)
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  FORMATTERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function fmtCurrency(v: number): string {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency', currency: 'RUB', maximumFractionDigits: 0,
  }).format(v)
}

function fmtNumber(v: number): string {
  return new Intl.NumberFormat('ru-RU').format(v)
}

function fmtDate(iso: string): string {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('ru-RU', {
    day: 'numeric', month: 'short', year: 'numeric',
  }).format(new Date(iso))
}

function fmtDateShort(iso: string): string {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('ru-RU', {
    day: 'numeric', month: 'short',
  }).format(new Date(iso))
}

function fmtDaysAgo(iso: string): string {
  if (!iso) return '—'
  const days = Math.floor((Date.now() - new Date(iso).getTime()) / 86400000)
  if (days === 0) return 'Сегодня'
  if (days === 1) return 'Вчера'
  if (days < 7) return `${days} дн. назад`
  if (days < 30) return `${Math.floor(days / 7)} нед. назад`
  return `${Math.floor(days / 30)} мес. назад`
}

function fmtRfm(score: number): { label: string; color: string } {
  if (score >= 80) return { label: 'Отличный',  color: 'text-emerald-400' }
  if (score >= 60) return { label: 'Хороший',   color: 'text-sky-400' }
  if (score >= 40) return { label: 'Средний',    color: 'text-amber-400' }
  if (score >= 20) return { label: 'Низкий',     color: 'text-orange-400' }
  return { label: 'Критический', color: 'text-rose-400' }
}

function fmtPercent(v: number): string {
  return v.toFixed(1) + '%'
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  ACTIONS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function openClient(client: CrmClient) {
  selectedClient.value = client
  modalTab.value       = 'info'
  showClientModal.value = true
  emit('client-click', client)
}

function closeClientModal() {
  showClientModal.value = false
  selectedClient.value  = null
}

function toggleFullscreen() {
  if (!rootEl.value) return
  if (!isFullscreen.value) rootEl.value.requestFullscreen?.()
  else document.exitFullscreen?.()
}

function handleFullscreenChange() {
  isFullscreen.value = !!document.fullscreenElement
}

function toggleSidebar() {
  showSidebar.value = !showSidebar.value
}

function setSegmentFilter(seg: string) {
  filters.segment = filters.segment === seg ? '' : seg
  currentPage.value = 1
  emit('segment-change', seg)
  emit('filter-change', { ...filters })
}

function clearAllFilters() {
  filters.search   = ''
  filters.segment  = ''
  filters.status   = ''
  filters.source   = ''
  filters.tag      = ''
  filters.dateFrom = ''
  filters.dateTo   = ''
  currentPage.value = 1
  emit('filter-change', { ...filters })
}

function toggleSort(col: string) {
  if (filters.sortBy === col) filters.sortDir = filters.sortDir === 'asc' ? 'desc' : 'asc'
  else { filters.sortBy = col; filters.sortDir = 'desc' }
  emit('sort-change', filters.sortBy, filters.sortDir)
}

function goPage(p: number) {
  if (p < 1 || p > totalPages.value) return
  currentPage.value = p
  emit('page-change', p)
}

// ── Bulk ──
function toggleSelectAll() {
  if (isAllSelected.value) {
    selectedIds.clear(); selectAll.value = false
  } else {
    filteredClients.value.forEach((c) => selectedIds.add(c.id)); selectAll.value = true
  }
}

function toggleClientSelect(id: number | string) {
  if (selectedIds.has(id)) selectedIds.delete(id)
  else selectedIds.add(id)
}

function executeBulkAction(action: string) {
  emit('bulk-action', action, Array.from(selectedIds))
  selectedIds.clear()
  isBulkMode.value = false
  showBulkMenu.value = false
}

// ── Quick actions ──
function handleQuickAction(key: string, client?: CrmClient) {
  showActionsMenu.value = false
  const c = client || selectedClient.value
  if (!c) return
  switch (key) {
    case 'call':  emit('client-call', c); break
    case 'email': emit('client-email', c); break
    case 'sms':   emit('client-sms', c); break
    case 'export': emit('export', 'xlsx'); break
    default: emit('bulk-action', key, [c.id])
  }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  KEYBOARD
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    if (showClientModal.value) { closeClientModal(); return }
    if (showFilterDrawer.value) { showFilterDrawer.value = false; return }
    if (isFullscreen.value) { toggleFullscreen(); return }
  }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  WATCHERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(() => filters.search, () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    currentPage.value = 1
    emit('filter-change', { ...filters })
  }, 300)
})

function checkViewport() {
  viewAs.value = window.innerWidth < 768 ? 'cards' : 'table'
  showSidebar.value = window.innerWidth >= 1024
}

// ── Infinite scroll ──
let io: IntersectionObserver | null = null

function setupInfiniteScroll() {
  if (!scrollSentinel.value) return
  io = new IntersectionObserver(
    (entries) => {
      if (entries[0]?.isIntersecting && !props.loading && currentPage.value < totalPages.value) {
        emit('load-more')
      }
    },
    { threshold: 0.1 }
  )
  io.observe(scrollSentinel.value)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  LIFECYCLE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

onMounted(() => {
  document.addEventListener('keydown', onKeydown)
  document.addEventListener('fullscreenchange', handleFullscreenChange)
  window.addEventListener('resize', checkViewport)
  checkViewport()
  nextTick(() => setupInfiniteScroll())
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  document.removeEventListener('fullscreenchange', handleFullscreenChange)
  window.removeEventListener('resize', checkViewport)
  if (debounceTimer) clearTimeout(debounceTimer)
  io?.disconnect()
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  RIPPLE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function ripple(e: MouseEvent) {
  const target = e.currentTarget as HTMLElement
  const rect   = target.getBoundingClientRect()
  const d      = Math.max(rect.width, rect.height) * 2
  const el     = document.createElement('span')
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-cr_0.6s_ease-out]'
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

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     TEMPLATE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<template>
  <div
    ref="rootEl"
    :class="[
      'relative flex flex-col bg-(--t-bg) text-(--t-text)',
      isFullscreen ? 'fixed inset-0 z-50 overflow-auto' : 'min-h-screen',
    ]"
  >
    <!-- ══════════════════════════════════════════════
         TOP BAR
    ══════════════════════════════════════════════ -->
    <header class="sticky inset-block-start-0 z-30 bg-(--t-surface)/80 backdrop-blur-xl
                   border-b border-(--t-border)/60">
      <!-- Row 1: title + fullscreen -->
      <div class="flex items-center gap-3 px-4 pt-4 pb-2 sm:px-6">
        <button
          class="lg:hidden relative overflow-hidden shrink-0 w-9 h-9 rounded-lg
                 border border-(--t-border)/50 bg-(--t-surface) flex items-center justify-center
                 text-(--t-text-2) hover:bg-(--t-card-hover) active:scale-95 transition-all"
          @click="toggleSidebar"
          @mousedown="ripple"
        >
          <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
          </svg>
        </button>

        <div class="flex items-center gap-2 flex-1 min-w-0">
          <span class="text-xl">{{ vc.icon }}</span>
          <h1 class="text-base sm:text-lg font-bold text-(--t-text) truncate">{{ vc.label }}</h1>
          <VBadge v-if="props.stats.atRiskClients > 0" variant="warning" size="sm">
            {{ props.stats.atRiskClients }} ⚠️
          </VBadge>
        </div>

        <button
          class="relative overflow-hidden shrink-0 w-9 h-9 rounded-lg border border-(--t-border)/50
                 bg-(--t-surface) flex items-center justify-center text-(--t-text-2)
                 hover:bg-(--t-card-hover) active:scale-95 transition-all"
          @click="toggleFullscreen" @mousedown="ripple"
          :title="isFullscreen ? 'Свернуть' : 'На весь экран'"
        >
          <svg v-if="!isFullscreen" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
          </svg>
          <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5m-4.5 0v4.5m0-4.5 5.25 5.25" />
          </svg>
        </button>
      </div>

      <!-- Row 2: search + filters + buttons -->
      <div class="flex flex-wrap items-center gap-2 px-4 pb-3 sm:px-6">
        <!-- Search -->
        <div class="relative flex-1 min-w-50 max-w-sm">
          <svg class="absolute inset-inline-start-3 inset-block-start-1/2 -translate-y-1/2 w-4 h-4
                      text-(--t-text-3) pointer-events-none"
               fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
          </svg>
          <input
            v-model="filters.search"
            type="text"
            :placeholder="`Поиск ${vc.clientLabelPlural.toLowerCase()}…`"
            class="inline-size-full py-2 ps-9 pe-3 text-sm rounded-xl
                   bg-(--t-bg)/60 border border-(--t-border)/50 text-(--t-text)
                   placeholder:text-(--t-text-3) focus:border-(--t-primary)/60
                   focus:ring-1 focus:ring-(--t-primary)/30 outline-none transition-all"
          />
        </div>

        <!-- Segment chips (desktop) -->
        <div class="hidden sm:flex items-center gap-1.5">
          <button
            v-for="seg in (['vip', 'at_risk', 'new', 'lost'] as ClientSegment[])"
            :key="seg"
            :class="[
              'relative overflow-hidden text-xs px-2.5 py-1.5 rounded-lg border transition-all active:scale-95',
              filters.segment === seg
                ? `${SEGMENT_MAP[seg].bg} ${SEGMENT_MAP[seg].color} border-transparent font-semibold`
                : 'border-(--t-border)/50 text-(--t-text-3) hover:text-(--t-text) hover:border-(--t-text-3)/40',
            ]"
            @click="setSegmentFilter(seg)"
            @mousedown="ripple"
          >
            {{ SEGMENT_MAP[seg].icon }}
            {{ vc.segmentLabels[seg] ?? SEGMENT_MAP[seg].label }}
            <span v-if="segmentCounts[seg] > 0" class="ms-1 opacity-60">{{ segmentCounts[seg] }}</span>
          </button>
        </div>

        <!-- Clear filters -->
        <button
          v-if="hasActiveFilters"
          class="text-xs text-(--t-text-3) hover:text-(--t-text) transition-colors underline
                 underline-offset-2 decoration-dotted"
          @click="clearAllFilters"
        >
          Сбросить
        </button>

        <div class="flex-1" />

        <!-- Filter drawer toggle (mobile) -->
        <button
          class="sm:hidden relative overflow-hidden shrink-0 w-9 h-9 rounded-lg
                 border border-(--t-border)/50 bg-(--t-surface) flex items-center justify-center
                 text-(--t-text-2) hover:bg-(--t-card-hover) active:scale-95 transition-all"
          @click="showFilterDrawer = true" @mousedown="ripple"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
          </svg>
          <span v-if="hasActiveFilters"
                class="absolute inset-block-start-0 inset-inline-end-0 w-2 h-2
                       bg-(--t-primary) rounded-full ring-2 ring-(--t-surface)" />
        </button>

        <!-- Add client -->
        <button
          class="relative overflow-hidden inline-flex items-center gap-1.5 px-3 py-2
                 rounded-xl text-xs font-semibold bg-(--t-primary)/12 text-(--t-primary)
                 border border-(--t-primary)/20 hover:bg-(--t-primary)/20 active:scale-[0.97]
                 transition-all"
          @click="emit('client-create')" @mousedown="ripple"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          <span class="hidden sm:inline">Добавить {{ vc.clientLabel.toLowerCase() }}а</span>
          <span class="sm:hidden">Добавить</span>
        </button>

        <!-- Quick actions -->
        <div class="relative">
          <button
            class="relative overflow-hidden w-9 h-9 rounded-lg border border-(--t-border)/50
                   bg-(--t-surface) flex items-center justify-center text-(--t-text-2)
                   hover:bg-(--t-card-hover) active:scale-95 transition-all"
            @click="showActionsMenu = !showActionsMenu" @mousedown="ripple"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
            </svg>
          </button>
          <Transition name="dropdown-cr">
            <div v-if="showActionsMenu"
                 class="absolute inset-inline-end-0 inset-block-start-full mt-1 z-40
                        inline-size-52 rounded-xl border border-(--t-border)/60 bg-(--t-surface)/95
                        backdrop-blur-xl shadow-xl overflow-hidden">
              <button
                v-for="a in vc.quickActions" :key="a.key"
                class="relative overflow-hidden inline-size-full flex items-center gap-2
                       px-3 py-2.5 text-xs text-(--t-text-2) hover:bg-(--t-card-hover)
                       hover:text-(--t-text) transition-colors"
                @click="handleQuickAction(a.key)" @mousedown="ripple"
              >
                <span class="shrink-0">{{ a.icon }}</span>
                {{ a.label }}
              </button>
            </div>
          </Transition>
        </div>
      </div>
    </header>

    <!-- ══════════════════════════════════════════════
         KPI WIDGETS
    ══════════════════════════════════════════════ -->
    <section class="px-4 sm:px-6 pt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
      <!-- Total -->
      <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">{{ vc.kpiLabels.total }}</span>
        <span class="text-lg font-bold text-(--t-text)">{{ fmtNumber(props.stats.totalClients) }}</span>
        <span class="text-[10px] text-(--t-text-3)">👤 активных: {{ fmtNumber(props.stats.activeClients) }}</span>
      </div>
      <!-- New this month -->
      <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">{{ vc.kpiLabels.newMonth }}</span>
        <span class="text-lg font-bold text-emerald-400">+{{ fmtNumber(props.stats.newThisMonth) }}</span>
        <span class="text-[10px] text-(--t-text-3)">🌱 за этот месяц</span>
      </div>
      <!-- VIP -->
      <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 backdrop-blur-sm p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">VIP</span>
        <span class="text-lg font-bold text-amber-400">{{ fmtNumber(props.stats.vipClients) }}</span>
        <span class="text-[10px] text-(--t-text-3)">👑 премиум-клиенты</span>
      </div>
      <!-- At risk -->
      <div :class="[
        'rounded-xl border p-3 flex flex-col gap-1 backdrop-blur-sm',
        props.stats.atRiskClients > 0 ? 'border-orange-500/30 bg-orange-500/5' : 'border-(--t-border)/50 bg-(--t-surface)/60',
      ]">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">{{ vc.kpiLabels.atRisk }}</span>
        <span :class="['text-lg font-bold', props.stats.atRiskClients > 0 ? 'text-orange-400' : 'text-(--t-text)']">
          {{ props.stats.atRiskClients }}
        </span>
        <span class="text-[10px] text-(--t-text-3)">⚠️ внимание</span>
      </div>
      <!-- Avg LTV -->
      <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">{{ vc.kpiLabels.avgLtv }}</span>
        <span class="text-lg font-bold text-(--t-text)">{{ fmtCurrency(props.stats.avgLtv) }}</span>
        <span class="text-[10px] text-(--t-text-3)">💰 средний на клиента</span>
      </div>
      <!-- Retention -->
      <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">Удержание</span>
        <span class="text-lg font-bold text-violet-400">{{ fmtPercent(props.stats.retentionRate) }}</span>
        <span class="text-[10px] text-(--t-text-3)">🔄 повторные: {{ fmtPercent(props.stats.repeatRate) }}</span>
      </div>
    </section>

    <!-- ══════════════════════════════════════════════
         BULK BAR
    ══════════════════════════════════════════════ -->
    <Transition name="slide-cr">
      <div v-if="selectedIds.size > 0"
           class="mx-4 sm:mx-6 mt-3 flex items-center gap-2 rounded-xl
                  border border-(--t-primary)/30 bg-(--t-primary)/8 px-4 py-2.5">
        <span class="text-xs font-medium text-(--t-text)">Выбрано: {{ selectedIds.size }}</span>
        <div class="flex-1" />
        <button
          class="relative overflow-hidden text-xs px-2.5 py-1.5 rounded-lg
                 bg-sky-500/12 text-sky-400 border border-sky-500/20
                 hover:bg-sky-500/20 active:scale-95 transition-all"
          @click="executeBulkAction('email')" @mousedown="ripple"
        >
          ✉️ Рассылка
        </button>
        <button
          class="relative overflow-hidden text-xs px-2.5 py-1.5 rounded-lg
                 bg-violet-500/12 text-violet-400 border border-violet-500/20
                 hover:bg-violet-500/20 active:scale-95 transition-all"
          @click="executeBulkAction('tag')" @mousedown="ripple"
        >
          🏷️ Добавить тег
        </button>
        <button
          class="relative overflow-hidden text-xs px-2.5 py-1.5 rounded-lg
                 bg-emerald-500/12 text-emerald-400 border border-emerald-500/20
                 hover:bg-emerald-500/20 active:scale-95 transition-all"
          @click="executeBulkAction('export')" @mousedown="ripple"
        >
          📥 Экспорт
        </button>
        <button
          class="text-xs text-(--t-text-3) hover:text-(--t-text) transition-colors"
          @click="selectedIds.clear()"
        >
          Отменить
        </button>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════════════
         MAIN — Sidebar + Content
    ══════════════════════════════════════════════ -->
    <div class="flex-1 flex overflow-hidden px-4 sm:px-6 py-4 gap-4">

      <!-- ─── SIDEBAR ─── -->
      <Transition name="sidebar-cr">
        <aside
          v-if="showSidebar"
          class="hidden lg:flex shrink-0 flex-col gap-3 overflow-y-auto"
          style="inline-size: 240px"
        >
          <!-- Segments -->
          <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm p-3">
            <h3 class="text-xs font-semibold text-(--t-text-2) mb-2">Сегменты</h3>
            <div class="flex flex-col gap-0.5">
              <button
                v-for="seg in ALL_SEGMENTS" :key="seg"
                :class="[
                  'relative overflow-hidden flex items-center gap-2 px-2.5 py-2 rounded-lg',
                  'text-xs transition-all active:scale-[0.97]',
                  filters.segment === seg
                    ? `${SEGMENT_MAP[seg].bg} ${SEGMENT_MAP[seg].color} font-semibold`
                    : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                ]"
                @click="setSegmentFilter(seg)" @mousedown="ripple"
              >
                <span class="shrink-0">{{ SEGMENT_MAP[seg].icon }}</span>
                <span class="flex-1 truncate text-start">
                  {{ vc.segmentLabels[seg] ?? SEGMENT_MAP[seg].label }}
                </span>
                <span class="shrink-0 text-[10px] opacity-60">{{ segmentCounts[seg] }}</span>
              </button>
            </div>
          </div>

          <!-- Sources -->
          <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm p-3">
            <h3 class="text-xs font-semibold text-(--t-text-2) mb-2">Источники</h3>
            <div class="flex flex-col gap-0.5">
              <button
                v-for="(src, srcKey) in SOURCE_MAP" :key="srcKey"
                :class="[
                  'relative overflow-hidden flex items-center gap-2 px-2.5 py-2 rounded-lg',
                  'text-xs transition-all active:scale-[0.97]',
                  filters.source === srcKey
                    ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold'
                    : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                ]"
                @click="filters.source = filters.source === (srcKey as string) ? '' : (srcKey as string); emit('filter-change', { ...filters })"
                @mousedown="ripple"
              >
                <span class="shrink-0">{{ src.icon }}</span>
                <span class="flex-1 truncate text-start">{{ src.label }}</span>
              </button>
            </div>
          </div>

          <!-- Tags -->
          <div v-if="props.tags.length > 0"
               class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm p-3">
            <h3 class="text-xs font-semibold text-(--t-text-2) mb-2">Теги</h3>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="tag in props.tags.slice(0, 12)" :key="tag"
                :class="[
                  'text-[10px] px-2 py-1 rounded-md border transition-all active:scale-95',
                  filters.tag === tag
                    ? 'bg-(--t-primary)/12 text-(--t-primary) border-(--t-primary)/30 font-semibold'
                    : 'border-(--t-border)/40 text-(--t-text-3) hover:text-(--t-text) hover:border-(--t-text-3)/40',
                ]"
                @click="filters.tag = filters.tag === tag ? '' : tag; emit('filter-change', { ...filters })"
              >
                {{ tag }}
              </button>
            </div>
          </div>

          <!-- Quick stats mini -->
          <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm p-3">
            <h3 class="text-xs font-semibold text-(--t-text-2) mb-2">Аналитика</h3>
            <div class="space-y-2 text-xs">
              <div class="flex items-center justify-between">
                <span class="text-(--t-text-3)">Средний чек</span>
                <span class="font-semibold text-(--t-text)">{{ fmtCurrency(props.stats.avgCheck) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-(--t-text-3)">NPS</span>
                <span :class="['font-semibold', props.stats.npsAvg >= 50 ? 'text-emerald-400' : props.stats.npsAvg >= 0 ? 'text-amber-400' : 'text-rose-400']">
                  {{ props.stats.npsAvg > 0 ? '+' : '' }}{{ props.stats.npsAvg }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-(--t-text-3)">Выручка / мес.</span>
                <span class="font-semibold text-(--t-text)">{{ fmtCurrency(props.stats.revenueThisMonth) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-(--t-text-3)">Потерянные</span>
                <span class="font-semibold text-rose-400">{{ props.stats.lostClients }}</span>
              </div>
            </div>
          </div>
        </aside>
      </Transition>

      <!-- ─── CONTENT ─── -->
      <main class="flex-1 min-w-0 flex flex-col gap-4">

        <!-- Loading -->
        <div v-if="props.loading && props.clients.length === 0" class="flex flex-col gap-3">
          <div v-for="n in 6" :key="n" class="h-16 rounded-xl bg-(--t-surface)/60 animate-pulse" />
        </div>

        <!-- Empty -->
        <div v-else-if="filteredClients.length === 0 && !props.loading"
             class="flex flex-col items-center justify-center py-16 text-center">
          <span class="text-4xl mb-3">👥</span>
          <p class="text-sm font-medium text-(--t-text-2)">
            {{ filters.search || hasActiveFilters ? 'Никого не найдено' : `${vc.clientLabelPlural} не добавлены` }}
          </p>
          <p class="text-xs text-(--t-text-3) mt-1 max-w-xs">
            {{ filters.search || hasActiveFilters
              ? 'Попробуйте изменить параметры поиска или сбросить фильтры'
              : `Добавьте первого ${vc.clientLabel.toLowerCase()}а через кнопку выше`
            }}
          </p>
          <button v-if="filters.search || hasActiveFilters"
                  class="mt-3 text-xs text-(--t-primary) hover:underline"
                  @click="clearAllFilters">
            Сбросить фильтры
          </button>
        </div>

        <!-- ═══ TABLE (desktop) ═══ -->
        <div v-else-if="viewAs === 'table'"
             class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/40 backdrop-blur-sm overflow-x-auto">
          <table class="inline-size-full text-xs">
            <thead>
              <tr class="border-b border-(--t-border)/40">
                <th class="ps-3 py-2.5 inline-size-8">
                  <input type="checkbox" :checked="isAllSelected"
                         class="w-3.5 h-3.5 rounded border-zinc-600 bg-zinc-800 accent-(--t-primary)"
                         @change="toggleSelectAll" />
                </th>
                <th class="px-2 py-2.5 inline-size-10" />
                <th class="px-2 py-2.5 text-start font-medium text-(--t-text-3) cursor-pointer
                           hover:text-(--t-text) select-none" @click="toggleSort('fullName')">
                  <span class="flex items-center gap-1">
                    {{ vc.clientLabel }}
                    <svg v-if="filters.sortBy === 'fullName'" class="w-3 h-3"
                         :class="filters.sortDir === 'asc' ? '' : 'rotate-180'"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                    </svg>
                  </span>
                </th>
                <th class="px-2 py-2.5 text-start font-medium text-(--t-text-3) hidden lg:table-cell">Контакт</th>
                <th class="px-2 py-2.5 text-center font-medium text-(--t-text-3)">Сегмент</th>
                <th class="px-2 py-2.5 text-end font-medium text-(--t-text-3) cursor-pointer
                           hover:text-(--t-text) select-none hidden md:table-cell"
                    @click="toggleSort('monetaryTotal')">
                  <span class="flex items-center justify-end gap-1">
                    LTV
                    <svg v-if="filters.sortBy === 'monetaryTotal'" class="w-3 h-3"
                         :class="filters.sortDir === 'asc' ? '' : 'rotate-180'"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                    </svg>
                  </span>
                </th>
                <th class="px-2 py-2.5 text-end font-medium text-(--t-text-3) hidden lg:table-cell
                           cursor-pointer hover:text-(--t-text) select-none"
                    @click="toggleSort('totalOrders')">
                  <span class="flex items-center justify-end gap-1">
                    {{ vc.visitLabelPlural }}
                    <svg v-if="filters.sortBy === 'totalOrders'" class="w-3 h-3"
                         :class="filters.sortDir === 'asc' ? '' : 'rotate-180'"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                    </svg>
                  </span>
                </th>
                <th class="px-2 py-2.5 text-end font-medium text-(--t-text-3) hidden xl:table-cell
                           cursor-pointer hover:text-(--t-text) select-none"
                    @click="toggleSort('lastVisitAt')">
                  <span class="flex items-center justify-end gap-1">
                    Посл. визит
                    <svg v-if="filters.sortBy === 'lastVisitAt'" class="w-3 h-3"
                         :class="filters.sortDir === 'asc' ? '' : 'rotate-180'"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                    </svg>
                  </span>
                </th>
                <th class="px-2 py-2.5 text-center font-medium text-(--t-text-3) hidden md:table-cell">RFM</th>
                <th v-for="ec in vc.extraColumns" :key="ec.key"
                    class="px-2 py-2.5 text-start font-medium text-(--t-text-3) hidden xl:table-cell">
                  {{ ec.label }}
                </th>
                <th class="px-2 py-2.5 inline-size-10" />
              </tr>
            </thead>
            <tbody>
              <tr v-for="client in paginatedClients" :key="client.id"
                  :class="[
                    'border-b border-(--t-border)/20 hover:bg-(--t-card-hover)/50 transition-colors cursor-pointer',
                    selectedIds.has(client.id) ? 'bg-(--t-primary)/5' : '',
                    client.status === 'blocked' ? 'opacity-50' : '',
                  ]"
                  @click="openClient(client)">
                <!-- Checkbox -->
                <td class="ps-3 py-2.5" @click.stop>
                  <input type="checkbox" :checked="selectedIds.has(client.id)"
                         class="w-3.5 h-3.5 rounded border-zinc-600 bg-zinc-800 accent-(--t-primary)"
                         @change="toggleClientSelect(client.id)" />
                </td>
                <!-- Avatar -->
                <td class="px-2 py-2.5">
                  <div class="w-8 h-8 rounded-full bg-(--t-bg)/60 border border-(--t-border)/30
                              flex items-center justify-center overflow-hidden text-sm">
                    <img v-if="client.avatar" :src="client.avatar" :alt="client.fullName"
                         class="w-full h-full object-cover" />
                    <span v-else>{{ client.fullName.charAt(0) }}</span>
                  </div>
                </td>
                <!-- Name -->
                <td class="px-2 py-2.5">
                  <div class="flex items-center gap-1.5">
                    <p class="font-medium text-(--t-text) truncate max-w-50">{{ client.fullName }}</p>
                    <span v-if="client.isB2B" class="text-[9px] px-1 py-0.5 rounded bg-indigo-500/15 text-indigo-400 font-semibold">B2B</span>
                  </div>
                  <p v-if="client.companyName" class="text-[10px] text-(--t-text-3) truncate">{{ client.companyName }}</p>
                </td>
                <!-- Contact -->
                <td class="px-2 py-2.5 text-(--t-text-3) hidden lg:table-cell">
                  <p class="truncate">{{ client.phone }}</p>
                  <p class="text-[10px] truncate">{{ client.email }}</p>
                </td>
                <!-- Segment -->
                <td class="px-2 py-2.5 text-center">
                  <span :class="[
                    'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium',
                    SEGMENT_MAP[client.segment].bg, SEGMENT_MAP[client.segment].color,
                  ]">
                    {{ SEGMENT_MAP[client.segment].icon }}
                    {{ vc.segmentLabels[client.segment] ?? SEGMENT_MAP[client.segment].label }}
                  </span>
                </td>
                <!-- LTV -->
                <td class="px-2 py-2.5 text-end font-semibold text-(--t-text) hidden md:table-cell">
                  {{ fmtCurrency(client.monetaryTotal) }}
                </td>
                <!-- Orders -->
                <td class="px-2 py-2.5 text-end text-(--t-text-2) hidden lg:table-cell">
                  {{ client.totalOrders }}
                </td>
                <!-- Last visit -->
                <td class="px-2 py-2.5 text-end text-(--t-text-3) hidden xl:table-cell">
                  {{ fmtDaysAgo(client.lastVisitAt) }}
                </td>
                <!-- RFM -->
                <td class="px-2 py-2.5 text-center hidden md:table-cell">
                  <span :class="['text-[10px] font-bold', fmtRfm(client.rfmScore).color]">
                    {{ client.rfmScore }}
                  </span>
                </td>
                <!-- Extra columns -->
                <td v-for="ec in vc.extraColumns" :key="ec.key"
                    class="px-2 py-2.5 text-(--t-text-3) hidden xl:table-cell">
                  {{ (client.verticalData as Record<string, unknown> | undefined)?.[ec.key] ?? '—' }}
                </td>
                <!-- Actions -->
                <td class="px-2 py-2.5" @click.stop>
                  <button class="w-7 h-7 rounded-md flex items-center justify-center
                                 text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover) transition-colors"
                          @click="emit('client-call', client)">
                    📞
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ═══ CARDS (mobile) ═══ -->
        <div v-else class="flex flex-col gap-2.5">
          <div v-for="client in paginatedClients" :key="client.id"
               :class="[
                 'rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60',
                 'backdrop-blur-sm p-3 transition-all active:scale-[0.99] cursor-pointer',
                 'hover:border-(--t-border)',
                 client.status === 'blocked' ? 'opacity-50' : '',
               ]"
               @click="openClient(client)">
            <div class="flex items-start gap-3">
              <!-- checkbox -->
              <input type="checkbox" :checked="selectedIds.has(client.id)"
                     class="mt-1 w-3.5 h-3.5 rounded border-zinc-600 bg-zinc-800 accent-(--t-primary) shrink-0"
                     @click.stop @change="toggleClientSelect(client.id)" />
              <!-- avatar -->
              <div class="shrink-0 w-11 h-11 rounded-full bg-(--t-bg)/60 border border-(--t-border)/30
                          flex items-center justify-center overflow-hidden text-base">
                <img v-if="client.avatar" :src="client.avatar" class="w-full h-full object-cover" />
                <span v-else>{{ client.fullName.charAt(0) }}</span>
              </div>
              <!-- info -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-1.5">
                  <p class="text-sm font-semibold text-(--t-text) truncate">{{ client.fullName }}</p>
                  <span :class="['shrink-0 w-2 h-2 rounded-full', SEGMENT_MAP[client.segment].dot]" />
                  <span v-if="client.isB2B" class="shrink-0 text-[9px] px-1 py-0.5 rounded bg-indigo-500/15 text-indigo-400 font-semibold">B2B</span>
                </div>
                <p class="text-[10px] text-(--t-text-3) mt-0.5">{{ client.phone }} · {{ client.email }}</p>

                <!-- Metrics row -->
                <div class="flex items-center gap-3 mt-2 text-xs">
                  <div>
                    <span class="text-(--t-text-3)">LTV:</span>
                    <span class="font-semibold text-(--t-text) ms-1">{{ fmtCurrency(client.monetaryTotal) }}</span>
                  </div>
                  <div>
                    <span class="text-(--t-text-3)">{{ vc.visitLabelPlural }}:</span>
                    <span class="font-medium text-(--t-text) ms-1">{{ client.totalOrders }}</span>
                  </div>
                  <span :class="['text-[10px] font-bold ms-auto', fmtRfm(client.rfmScore).color]">
                    RFM {{ client.rfmScore }}
                  </span>
                </div>

                <!-- Bottom row -->
                <div class="flex items-center gap-2 mt-1.5 text-[10px] text-(--t-text-3)">
                  <span :class="[SEGMENT_MAP[client.segment].color]">
                    {{ SEGMENT_MAP[client.segment].icon }} {{ vc.segmentLabels[client.segment] ?? SEGMENT_MAP[client.segment].label }}
                  </span>
                  <span class="ms-auto">{{ fmtDaysAgo(client.lastVisitAt) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Pagination ── -->
        <div v-if="totalPages > 1 && !props.loading"
             class="flex items-center justify-center gap-1.5 mt-2">
          <button :disabled="currentPage <= 1"
                  class="w-8 h-8 rounded-lg border border-(--t-border)/50 flex items-center justify-center
                         text-(--t-text-3) hover:bg-(--t-card-hover) disabled:opacity-30
                         disabled:cursor-not-allowed transition-all"
                  @click="goPage(currentPage - 1)">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
          </button>
          <template v-for="p in totalPages" :key="p">
            <button v-if="p <= 3 || p > totalPages - 2 || Math.abs(p - currentPage) <= 1"
                    :class="[
                      'w-8 h-8 rounded-lg text-xs font-medium transition-all',
                      p === currentPage ? 'bg-(--t-primary) text-white shadow-sm' : 'text-(--t-text-3) hover:bg-(--t-card-hover)',
                    ]"
                    @click="goPage(p)">
              {{ p }}
            </button>
            <span v-else-if="p === 4 || p === totalPages - 2"
                  class="w-8 h-8 flex items-center justify-center text-(--t-text-3) text-xs">…</span>
          </template>
          <button :disabled="currentPage >= totalPages"
                  class="w-8 h-8 rounded-lg border border-(--t-border)/50 flex items-center justify-center
                         text-(--t-text-3) hover:bg-(--t-card-hover) disabled:opacity-30
                         disabled:cursor-not-allowed transition-all"
                  @click="goPage(currentPage + 1)">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
          </button>
        </div>

        <div ref="scrollSentinel" class="h-1" />

        <div v-if="props.loading && props.clients.length > 0" class="flex justify-center py-4">
          <div class="w-5 h-5 border-2 border-(--t-primary)/30 border-t-(--t-primary) rounded-full animate-spin" />
        </div>
      </main>
    </div>

    <!-- ══════════════════════════════════════════════
         CLIENT DETAIL MODAL
    ══════════════════════════════════════════════ -->
    <Transition name="modal-cr">
      <div v-if="showClientModal && selectedClient"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="closeClientModal">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeClientModal" />
        <div class="relative z-10 inline-size-full max-w-xl rounded-2xl border border-(--t-border)
                    bg-(--t-surface)/90 backdrop-blur-xl shadow-2xl overflow-hidden">

          <!-- Header -->
          <div class="flex items-center gap-3 px-5 pt-5 pb-3">
            <div class="w-12 h-12 rounded-full bg-(--t-bg)/60 border border-(--t-border)/30
                        flex items-center justify-center overflow-hidden text-lg shrink-0">
              <img v-if="selectedClient.avatar" :src="selectedClient.avatar" class="w-full h-full object-cover" />
              <span v-else>{{ selectedClient.fullName.charAt(0) }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-(--t-text) truncate">{{ selectedClient.fullName }}</h3>
                <span v-if="selectedClient.isB2B" class="text-[9px] px-1.5 py-0.5 rounded bg-indigo-500/15 text-indigo-400 font-semibold">B2B</span>
              </div>
              <p class="text-[10px] text-(--t-text-3)">{{ selectedClient.phone }} · {{ selectedClient.email }}</p>
            </div>
            <span :class="[
              'shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium',
              SEGMENT_MAP[selectedClient.segment].bg, SEGMENT_MAP[selectedClient.segment].color,
            ]">
              {{ SEGMENT_MAP[selectedClient.segment].icon }}
              {{ vc.segmentLabels[selectedClient.segment] ?? SEGMENT_MAP[selectedClient.segment].label }}
            </span>
            <button class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) hover:text-(--t-text) transition-colors"
                    @click="closeClientModal">✕</button>
          </div>

          <!-- Quick actions bar -->
          <div class="flex items-center gap-1.5 px-5 pb-3">
            <button v-for="a in vc.quickActions.slice(0, 4)" :key="a.key"
                    class="relative overflow-hidden inline-flex items-center gap-1 px-2.5 py-1.5
                           rounded-lg text-[10px] font-medium border border-(--t-border)/40
                           text-(--t-text-2) hover:bg-(--t-card-hover) hover:text-(--t-text)
                           active:scale-95 transition-all"
                    @click="handleQuickAction(a.key, selectedClient!)" @mousedown="ripple">
              <span>{{ a.icon }}</span>
              {{ a.label }}
            </button>
          </div>

          <!-- Tabs -->
          <div class="flex items-center gap-1 px-5 border-b border-(--t-border)/40">
            <button v-for="t in [
              { key: 'info',      label: 'Профиль' },
              { key: 'history',   label: 'История' },
              { key: 'analytics', label: 'Аналитика' },
            ] as const" :key="t.key"
                    :class="[
                      'px-3 py-2 text-xs font-medium transition-colors',
                      modalTab === t.key
                        ? 'text-(--t-primary) border-b-2 border-(--t-primary)'
                        : 'text-(--t-text-3) hover:text-(--t-text)',
                    ]"
                    @click="modalTab = t.key as typeof modalTab">
              {{ t.label }}
            </button>
          </div>

          <!-- Body -->
          <div class="px-5 py-4 max-h-[55vh] overflow-y-auto">
            <!-- ── Info tab ── -->
            <template v-if="modalTab === 'info'">
              <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                  <span class="text-(--t-text-3)">Статус:</span>
                  <p :class="['font-semibold mt-0.5', STATUS_MAP[selectedClient.status].color]">
                    {{ STATUS_MAP[selectedClient.status].label }}
                  </p>
                </div>
                <div>
                  <span class="text-(--t-text-3)">Источник:</span>
                  <p class="font-medium text-(--t-text) mt-0.5">
                    {{ SOURCE_MAP[selectedClient.source]?.icon }}
                    {{ SOURCE_MAP[selectedClient.source]?.label }}
                  </p>
                </div>
                <div>
                  <span class="text-(--t-text-3)">LTV:</span>
                  <p class="font-bold text-(--t-text) mt-0.5">{{ fmtCurrency(selectedClient.monetaryTotal) }}</p>
                </div>
                <div>
                  <span class="text-(--t-text-3)">Средний чек:</span>
                  <p class="font-semibold text-(--t-text) mt-0.5">{{ fmtCurrency(selectedClient.avgCheck) }}</p>
                </div>
                <div>
                  <span class="text-(--t-text-3)">{{ vc.visitLabelPlural }}:</span>
                  <p class="font-semibold text-(--t-text) mt-0.5">{{ selectedClient.totalOrders }}</p>
                </div>
                <div>
                  <span class="text-(--t-text-3)">Бонусный баланс:</span>
                  <p class="font-semibold text-violet-400 mt-0.5">{{ fmtCurrency(selectedClient.bonusBalance) }}</p>
                </div>
                <div>
                  <span class="text-(--t-text-3)">Первый визит:</span>
                  <p class="font-medium text-(--t-text) mt-0.5">{{ fmtDate(selectedClient.firstVisitAt) }}</p>
                </div>
                <div>
                  <span class="text-(--t-text-3)">Последний визит:</span>
                  <p class="font-medium text-(--t-text) mt-0.5">{{ fmtDaysAgo(selectedClient.lastVisitAt) }}</p>
                </div>
                <div v-if="selectedClient.companyName" class="col-span-2">
                  <span class="text-(--t-text-3)">Компания:</span>
                  <p class="font-medium text-(--t-text) mt-0.5">
                    {{ selectedClient.companyName }}
                    <span v-if="selectedClient.inn" class="text-(--t-text-3) ms-1">ИНН {{ selectedClient.inn }}</span>
                  </p>
                </div>
                <div v-if="selectedClient.rating > 0" class="col-span-2">
                  <span class="text-(--t-text-3)">Рейтинг:</span>
                  <p class="mt-0.5">
                    <span v-for="s in 5" :key="s" :class="s <= selectedClient.rating ? 'text-amber-400' : 'text-zinc-600'">★</span>
                    <span class="text-(--t-text-3) ms-1">({{ selectedClient.rating.toFixed(1) }})</span>
                  </p>
                </div>
                <div v-if="selectedClient.tags.length > 0" class="col-span-2">
                  <span class="text-(--t-text-3)">Теги:</span>
                  <div class="flex flex-wrap gap-1 mt-1">
                    <span v-for="tag in selectedClient.tags" :key="tag"
                          class="text-[10px] px-1.5 py-0.5 rounded-md bg-(--t-bg)/60 text-(--t-text-3) border border-(--t-border)/30">
                      {{ tag }}
                    </span>
                  </div>
                </div>
                <div v-if="selectedClient.notes" class="col-span-2">
                  <span class="text-(--t-text-3)">Заметки:</span>
                  <p class="text-(--t-text-2) mt-0.5 whitespace-pre-wrap">{{ selectedClient.notes }}</p>
                </div>
              </div>

              <!-- RFM bar -->
              <div class="mt-4 p-3 rounded-xl bg-(--t-bg)/40 border border-(--t-border)/30">
                <div class="flex items-center justify-between text-[10px] text-(--t-text-3) mb-1.5">
                  <span>RFM-скоринг</span>
                  <span :class="fmtRfm(selectedClient.rfmScore).color" class="font-bold">
                    {{ selectedClient.rfmScore }}/100 — {{ fmtRfm(selectedClient.rfmScore).label }}
                  </span>
                </div>
                <div class="h-2 rounded-full bg-(--t-bg)/80 overflow-hidden">
                  <div class="h-full rounded-full transition-all duration-500"
                       :class="[
                         selectedClient.rfmScore >= 60 ? 'bg-emerald-500' :
                         selectedClient.rfmScore >= 40 ? 'bg-amber-500' : 'bg-rose-500',
                       ]"
                       :style="{ inlineSize: selectedClient.rfmScore + '%' }" />
                </div>
                <div class="grid grid-cols-3 gap-2 mt-2 text-[10px]">
                  <div class="text-center">
                    <span class="text-(--t-text-3)">Recency</span>
                    <p class="font-semibold text-(--t-text)">{{ selectedClient.recencyDays }}д</p>
                  </div>
                  <div class="text-center">
                    <span class="text-(--t-text-3)">Frequency</span>
                    <p class="font-semibold text-(--t-text)">{{ selectedClient.frequencyMonth }}/мес</p>
                  </div>
                  <div class="text-center">
                    <span class="text-(--t-text-3)">Monetary</span>
                    <p class="font-semibold text-(--t-text)">{{ fmtCurrency(selectedClient.monetaryMonth) }}</p>
                  </div>
                </div>
              </div>
            </template>

            <!-- ── History tab ── -->
            <template v-if="modalTab === 'history'">
              <div v-if="clientInteractions.length === 0" class="text-center py-8">
                <p class="text-xs text-(--t-text-3)">История взаимодействий пуста</p>
                <button class="mt-2 text-xs text-(--t-primary) hover:underline"
                        @click="emit('add-interaction', selectedClient!.id, 'call')">
                  Добавить первый контакт
                </button>
              </div>
              <div v-else class="relative">
                <!-- Timeline -->
                <div class="absolute inset-inline-start-4 inset-block-start-0 inset-block-end-0
                            inline-size-px bg-(--t-border)/30" />
                <div v-for="inter in clientInteractions" :key="inter.id" class="relative ps-10 pb-4">
                  <div :class="[
                    'absolute inset-inline-start-2.5 inset-block-start-0 w-3.5 h-3.5 rounded-full',
                    'border-2 border-(--t-surface) flex items-center justify-center text-[8px]',
                    INTERACTION_MAP[inter.type]?.color ?? 'text-zinc-400',
                  ]"
                       :style="{ backgroundColor: 'var(--t-bg)' }">
                    {{ INTERACTION_MAP[inter.type]?.icon }}
                  </div>
                  <div class="rounded-lg border border-(--t-border)/30 bg-(--t-bg)/30 p-2.5">
                    <div class="flex items-center gap-2 text-[10px]">
                      <span :class="INTERACTION_MAP[inter.type]?.color" class="font-medium">
                        {{ INTERACTION_MAP[inter.type]?.label }}
                      </span>
                      <span class="text-(--t-text-3)">· {{ inter.employeeName }}</span>
                      <span class="ms-auto text-(--t-text-3)">{{ fmtDateShort(inter.date) }}</span>
                    </div>
                    <p class="text-xs text-(--t-text) mt-1 font-medium">{{ inter.title }}</p>
                    <p v-if="inter.description" class="text-[10px] text-(--t-text-3) mt-0.5">{{ inter.description }}</p>
                    <p v-if="inter.result" class="text-[10px] text-emerald-400 mt-0.5">→ {{ inter.result }}</p>
                  </div>
                </div>
              </div>
            </template>

            <!-- ── Analytics tab ── -->
            <template v-if="modalTab === 'analytics'">
              <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="rounded-lg border border-(--t-border)/30 bg-(--t-bg)/30 p-3 text-center">
                  <span class="text-(--t-text-3) text-[10px]">Всего {{ vc.visitLabelPlural.toLowerCase() }}</span>
                  <p class="text-xl font-bold text-(--t-text) mt-1">{{ selectedClient.totalOrders }}</p>
                </div>
                <div class="rounded-lg border border-(--t-border)/30 bg-(--t-bg)/30 p-3 text-center">
                  <span class="text-(--t-text-3) text-[10px]">LTV</span>
                  <p class="text-xl font-bold text-emerald-400 mt-1">{{ fmtCurrency(selectedClient.monetaryTotal) }}</p>
                </div>
                <div class="rounded-lg border border-(--t-border)/30 bg-(--t-bg)/30 p-3 text-center">
                  <span class="text-(--t-text-3) text-[10px]">Средний чек</span>
                  <p class="text-xl font-bold text-(--t-text) mt-1">{{ fmtCurrency(selectedClient.avgCheck) }}</p>
                </div>
                <div class="rounded-lg border border-(--t-border)/30 bg-(--t-bg)/30 p-3 text-center">
                  <span class="text-(--t-text-3) text-[10px]">Бонусы</span>
                  <p class="text-xl font-bold text-violet-400 mt-1">{{ fmtCurrency(selectedClient.bonusBalance) }}</p>
                </div>
              </div>
              <div class="mt-4 text-xs text-center text-(--t-text-3)">
                Детальная аналитика доступна в разделе «Аналитика» дашборда
              </div>
            </template>
          </div>

          <!-- Footer -->
          <div class="px-5 pb-4 flex flex-col-reverse sm:flex-row items-stretch sm:items-center
                      gap-2 sm:justify-end border-t border-(--t-border)/30 pt-3">
            <button class="relative overflow-hidden px-4 py-2 rounded-xl text-xs font-medium
                           border border-(--t-border) text-(--t-text-2)
                           hover:bg-(--t-surface) hover:text-(--t-text) active:scale-[0.97] transition-all"
                    @click="closeClientModal" @mousedown="ripple">
              Закрыть
            </button>
            <button class="relative overflow-hidden px-4 py-2 rounded-xl text-xs font-semibold
                           bg-(--t-primary) text-white hover:brightness-110 active:scale-[0.97]
                           transition-all shadow-sm"
                    @click="emit('client-edit', selectedClient!); closeClientModal()" @mousedown="ripple">
              ✏️ Редактировать
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════════════
         FILTER DRAWER (mobile)
    ══════════════════════════════════════════════ -->
    <Transition name="drawer-cr">
      <div v-if="showFilterDrawer" class="fixed inset-0 z-50 flex justify-end"
           @click.self="showFilterDrawer = false">
        <div class="absolute inset-0 bg-black/40" @click="showFilterDrawer = false" />
        <div class="relative z-10 inline-size-72 max-w-[85vw] bg-(--t-surface) border-s
                    border-(--t-border) h-full overflow-y-auto p-5 flex flex-col gap-4">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-(--t-text)">Фильтры</h3>
            <button class="text-(--t-text-3) hover:text-(--t-text) transition-colors"
                    @click="showFilterDrawer = false">✕</button>
          </div>

          <!-- Segments -->
          <div>
            <p class="text-xs font-medium text-(--t-text-2) mb-2">Сегменты</p>
            <div class="flex flex-col gap-1">
              <button v-for="seg in ALL_SEGMENTS" :key="seg"
                      :class="[
                        'relative overflow-hidden flex items-center gap-2 px-3 py-2 rounded-lg text-xs transition-all',
                        filters.segment === seg
                          ? `${SEGMENT_MAP[seg].bg} ${SEGMENT_MAP[seg].color} font-semibold`
                          : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                      ]"
                      @click="setSegmentFilter(seg)" @mousedown="ripple">
                <span>{{ SEGMENT_MAP[seg].icon }}</span>
                {{ vc.segmentLabels[seg] ?? SEGMENT_MAP[seg].label }}
                <span class="ms-auto text-[10px] opacity-60">{{ segmentCounts[seg] }}</span>
              </button>
            </div>
          </div>

          <!-- Status -->
          <div>
            <p class="text-xs font-medium text-(--t-text-2) mb-2">Статус</p>
            <div class="flex flex-col gap-1">
              <button v-for="(st, stKey) in STATUS_MAP" :key="stKey"
                      :class="[
                        'relative overflow-hidden flex items-center gap-2 px-3 py-2 rounded-lg text-xs transition-all',
                        filters.status === (stKey as string)
                          ? `${st.bg} ${st.color} font-semibold`
                          : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                      ]"
                      @click="filters.status = filters.status === (stKey as string) ? '' : (stKey as string); emit('filter-change', { ...filters })"
                      @mousedown="ripple">
                <span class="w-2 h-2 rounded-full" :class="st.dot" />
                {{ st.label }}
              </button>
            </div>
          </div>

          <!-- Sources -->
          <div>
            <p class="text-xs font-medium text-(--t-text-2) mb-2">Источник</p>
            <div class="flex flex-col gap-1">
              <button v-for="(src, srcKey) in SOURCE_MAP" :key="srcKey"
                      :class="[
                        'relative overflow-hidden flex items-center gap-2 px-3 py-2 rounded-lg text-xs transition-all',
                        filters.source === (srcKey as string)
                          ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold'
                          : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                      ]"
                      @click="filters.source = filters.source === (srcKey as string) ? '' : (srcKey as string); emit('filter-change', { ...filters })"
                      @mousedown="ripple">
                <span>{{ src.icon }}</span>
                {{ src.label }}
              </button>
            </div>
          </div>

          <!-- Clear -->
          <button v-if="hasActiveFilters"
                  class="relative overflow-hidden mt-auto px-4 py-2.5 rounded-xl text-xs font-medium
                         border border-(--t-border) text-(--t-text-2) hover:bg-(--t-card-hover)
                         active:scale-[0.97] transition-all"
                  @click="clearAllFilters(); showFilterDrawer = false" @mousedown="ripple">
            Сбросить все фильтры
          </button>
        </div>
      </div>
    </Transition>

    <!-- Backdrop for actions menu -->
    <div v-if="showActionsMenu" class="fixed inset-0 z-30" @click="showActionsMenu = false" />
  </div>
</template>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     STYLES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<style scoped>
/* Ripple — unique suffix cr */
@keyframes ripple-cr {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* Dropdown */
.dropdown-cr-enter-active,
.dropdown-cr-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-cr-enter-from,
.dropdown-cr-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.96);
}

/* Slide (bulk bar) */
.slide-cr-enter-active,
.slide-cr-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.slide-cr-enter-from,
.slide-cr-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Sidebar */
.sidebar-cr-enter-active,
.sidebar-cr-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease, inline-size 0.25s ease;
}
.sidebar-cr-enter-from,
.sidebar-cr-leave-to {
  opacity: 0;
  transform: translateX(-12px);
  inline-size: 0 !important;
}

/* Modal */
.modal-cr-enter-active {
  transition: opacity 0.25s ease;
}
.modal-cr-enter-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}
.modal-cr-leave-active {
  transition: opacity 0.2s ease;
}
.modal-cr-leave-active > :last-child {
  transition: transform 0.2s ease-in, opacity 0.2s ease;
}
.modal-cr-enter-from,
.modal-cr-leave-to {
  opacity: 0;
}
.modal-cr-enter-from > :last-child {
  opacity: 0;
  transform: scale(0.92) translateY(12px);
}
.modal-cr-leave-to > :last-child {
  opacity: 0;
  transform: scale(0.95) translateY(6px);
}

/* Drawer */
.drawer-cr-enter-active,
.drawer-cr-leave-active {
  transition: opacity 0.3s ease;
}
.drawer-cr-enter-active > :last-child,
.drawer-cr-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.drawer-cr-enter-from,
.drawer-cr-leave-to {
  opacity: 0;
}
.drawer-cr-enter-from > :last-child,
.drawer-cr-leave-to > :last-child {
  transform: translateX(100%);
}

/* Custom scrollbar */
aside::-webkit-scrollbar { inline-size: 4px; }
aside::-webkit-scrollbar-track { background: transparent; }
aside::-webkit-scrollbar-thumb { background: var(--t-border); border-radius: 999px; }
</style>
