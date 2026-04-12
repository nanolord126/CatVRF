<script setup lang="ts">
/**
 * TenantMailings.vue — Главная страница рассылок и уведомлений
 * в B2B Tenant Dashboard.
 *
 * Полнофункциональный модуль управления рассылками для всех
 * 127 вертикалей CatVRF:
 *   Beauty (напоминания о записи) · Taxi (промокоды пассажирам)
 *   Food (акции и меню дня)       · Hotels (welcome / post-stay)
 *   RealEstate (подборки объектов) · Flowers (напоминания о датах)
 *   Fashion (лукбуки / sale)       · Furniture (дизайн-проекты)
 *   Fitness (продление абонемента) · Travel (горящие туры)
 *   default (универсальный)
 *
 * ─────────────────────────────────────────────────────────────
 *  Функционал:
 *   1.  KPI-виджеты: отправлено, открыто, кликов, конверсия,
 *       отписки, ROI
 *   2.  Верхняя панель: «Создать рассылку» + фильтры
 *   3.  Таблица/карточки кампаний со статусами
 *   4.  Детальная модалка кампании (статистика + получатели)
 *   5.  Шаблоны и триггерные кампании
 *   6.  Массовые действия, экспорт
 *   7.  Full-screen режим
 *   8.  VERTICAL_MAILING_CONFIG — терминология по вертикалям
 *   9.  Пагинация, живой поиск, infinite scroll
 *  10.  Каналы: Email · SMS · Push · In-app · Telegram
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

type CampaignStatus   = 'draft' | 'scheduled' | 'sending' | 'sent' | 'paused' | 'cancelled' | 'failed'
type ChannelType      = 'email' | 'sms' | 'push' | 'in_app' | 'telegram'
type SegmentTarget    = 'all' | 'vip' | 'loyal' | 'new' | 'at_risk' | 'dormant' | 'b2b' | 'custom'
type TriggerType      = 'welcome' | 'abandoned_cart' | 'birthday' | 'reactivation' | 'post_purchase' | 'reminder' | 'loyalty' | 'custom'
type TemplateCategory = 'promo' | 'transactional' | 'trigger' | 'newsletter' | 'seasonal'

interface Campaign {
  id:              number | string
  name:            string
  subject:         string
  channel:         ChannelType
  status:          CampaignStatus
  segment:         SegmentTarget
  segmentLabel?:   string
  scheduledAt?:    string          // ISO
  sentAt?:         string          // ISO
  createdAt:       string          // ISO
  // Stats
  totalRecipients: number
  delivered:       number
  opened:          number
  clicked:         number
  converted:       number
  unsubscribed:    number
  bounced:         number
  revenue:         number          // ₽ заработано
  // Meta
  templateId?:     number | string
  templateName?:   string
  triggerType?:    TriggerType
  isAutomated:     boolean
  tags:            string[]
  correlationId?:  string
  previewHtml?:    string
  notes?:          string
}

interface MailTemplate {
  id:           number | string
  name:         string
  category:     TemplateCategory
  channel:      ChannelType
  previewUrl?:  string
  description?: string
  usageCount:   number
  lastUsedAt?:  string
  isDefault:    boolean
}

interface TriggerCampaign {
  id:            number | string
  name:          string
  triggerType:   TriggerType
  channel:       ChannelType
  isActive:      boolean
  totalSent:     number
  openRate:      number           // 0-100
  conversionRate: number          // 0-100
  createdAt:     string
}

interface MailingStats {
  totalCampaigns:    number
  totalSent:         number
  avgOpenRate:       number       // 0-100
  avgClickRate:      number       // 0-100
  avgConversion:     number       // 0-100
  totalRevenue:      number       // ₽
  unsubscribeRate:   number       // 0-100
  bounceRate:        number       // 0-100
  activeTriggers:    number
  templatesCount:    number
  sentThisMonth:     number
  roiPercent:        number       // ROI %
}

interface MailingFilter {
  search:    string
  status:    string
  channel:   string
  segment:   string
  dateFrom:  string
  dateTo:    string
  sortBy:    string
  sortDir:   'asc' | 'desc'
}

interface VerticalMailingConfig {
  label:            string
  icon:             string
  recipientLabel:   string         // «Клиент» / «Гость» / «Пассажир»
  recipientPlural:  string
  defaultTemplates: Array<{ name: string; desc: string; trigger: TriggerType }>
  channelHints:     Record<ChannelType, string>
  segmentHints:     Record<string, string>
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  PROPS & EMITS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const props = withDefaults(defineProps<{
  vertical?:      string
  campaigns?:     Campaign[]
  templates?:     MailTemplate[]
  triggers?:      TriggerCampaign[]
  stats?:         MailingStats
  totalCampaigns?: number
  loading?:       boolean
  perPage?:       number
}>(), {
  vertical:       'default',
  campaigns:      () => [],
  templates:      () => [],
  triggers:       () => [],
  stats:          () => ({
    totalCampaigns: 0, totalSent: 0, avgOpenRate: 0, avgClickRate: 0,
    avgConversion: 0, totalRevenue: 0, unsubscribeRate: 0, bounceRate: 0,
    activeTriggers: 0, templatesCount: 0, sentThisMonth: 0, roiPercent: 0,
  }),
  totalCampaigns: 0,
  loading:        false,
  perPage:        20,
})

const emit = defineEmits<{
  'campaign-click':       [campaign: Campaign]
  'campaign-create':      []
  'campaign-edit':        [campaign: Campaign]
  'campaign-duplicate':   [campaign: Campaign]
  'campaign-delete':      [ids: Array<number | string>]
  'campaign-pause':       [campaign: Campaign]
  'campaign-resume':      [campaign: Campaign]
  'campaign-send':        [campaign: Campaign]
  'campaign-schedule':    [campaign: Campaign]
  'template-click':       [template: MailTemplate]
  'template-create':      []
  'template-use':         [template: MailTemplate]
  'trigger-toggle':       [trigger: TriggerCampaign, active: boolean]
  'trigger-edit':         [trigger: TriggerCampaign]
  'trigger-create':       []
  'filter-change':        [filters: MailingFilter]
  'sort-change':          [sortBy: string, sortDir: 'asc' | 'desc']
  'page-change':          [page: number]
  'bulk-action':          [action: string, ids: Array<number | string>]
  'export':               [format: 'xlsx' | 'csv']
  'load-more':            []
}>()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STORES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const auth     = useAuth()
const business = useTenant()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  VERTICAL MAILING CONFIG
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const VERTICAL_MAILING_CONFIG: Record<string, VerticalMailingConfig> = {
  beauty: {
    label: 'Рассылки салона', icon: '💄',
    recipientLabel: 'Клиент', recipientPlural: 'Клиенты',
    defaultTemplates: [
      { name: 'Напоминание о записи',  desc: 'За 24ч до визита',       trigger: 'reminder' },
      { name: 'После визита',          desc: 'Спасибо + бонусы',       trigger: 'post_purchase' },
      { name: 'Давно не были',         desc: 'Реактивация 30+ дней',   trigger: 'reactivation' },
      { name: 'День рождения',         desc: 'Скидка в подарок',       trigger: 'birthday' },
    ],
    channelHints: {
      email: 'Информация об акциях и новых услугах', sms: 'Напоминания о записи',
      push: 'Быстрые уведомления', in_app: 'Акции внутри приложения', telegram: 'Канал бота салона',
    },
    segmentHints: { vip: 'VIP-клиенты (высокий LTV)', at_risk: 'Не были 30+ дней', new: 'Первый визит < 7 дн.' },
  },

  taxi: {
    label: 'Рассылки таксопарка', icon: '🚕',
    recipientLabel: 'Пассажир', recipientPlural: 'Пассажиры',
    defaultTemplates: [
      { name: 'Промокод на поездку',   desc: 'Скидка для неактивных', trigger: 'reactivation' },
      { name: 'Welcome бонус',         desc: 'Первая поездка -50%',   trigger: 'welcome' },
      { name: 'Постоянным клиентам',   desc: 'Кешбэк за 10 поездок', trigger: 'loyalty' },
    ],
    channelHints: {
      email: 'Отчёты и квитанции', sms: 'Промокоды и статус', push: 'Статус поездки',
      in_app: 'Промо в приложении', telegram: 'Бот-трекер',
    },
    segmentHints: { vip: 'Бизнес-класс', at_risk: 'Нет поездок 14+ дней', new: 'Первая поездка' },
  },

  food: {
    label: 'Рассылки ресторана', icon: '🍽️',
    recipientLabel: 'Заказчик', recipientPlural: 'Заказчики',
    defaultTemplates: [
      { name: 'Меню дня',              desc: 'Ежедневная рассылка',    trigger: 'custom' },
      { name: 'Скидка на повторный',   desc: '-15% на следующий заказ', trigger: 'post_purchase' },
      { name: 'Брошенная корзина',     desc: 'Напоминание о заказе',   trigger: 'abandoned_cart' },
      { name: 'Новое в меню',          desc: 'Анонс блюд',            trigger: 'custom' },
    ],
    channelHints: {
      email: 'Меню дня и акции', sms: 'Статус заказа', push: 'Заказ готов / курьер',
      in_app: 'Промо-баннеры', telegram: 'Бот доставки',
    },
    segmentHints: { vip: 'Гурманы (LTV > 10k)', at_risk: 'Нет заказов 21+ день', new: 'Первый заказ' },
  },

  hotel: {
    label: 'Рассылки отеля', icon: '🏨',
    recipientLabel: 'Гость', recipientPlural: 'Гости',
    defaultTemplates: [
      { name: 'Welcome',               desc: 'Добро пожаловать в отель', trigger: 'welcome' },
      { name: 'Post-stay',             desc: 'Спасибо за пребывание',    trigger: 'post_purchase' },
      { name: 'Апгрейд номера',        desc: 'Персональное предложение', trigger: 'loyalty' },
      { name: 'Сезонное предложение',   desc: 'Горячие даты',            trigger: 'custom' },
    ],
    channelHints: {
      email: 'Подтверждения и офферы', sms: 'Статус бронирования', push: 'Check-in / room ready',
      in_app: 'Услуги отеля', telegram: 'Консьерж-бот',
    },
    segmentHints: { vip: 'VIP-гости (3+ визита)', at_risk: 'Не бронировали 6+ мес.', new: 'Первое бронирование' },
  },

  realEstate: {
    label: 'Рассылки агентства', icon: '🏢',
    recipientLabel: 'Клиент', recipientPlural: 'Клиенты',
    defaultTemplates: [
      { name: 'Подборка объектов',     desc: 'По критериям клиента', trigger: 'custom' },
      { name: 'Снижение цены',         desc: 'Объект стал дешевле',  trigger: 'custom' },
      { name: 'Новые поступления',     desc: 'Свежие объекты',      trigger: 'custom' },
    ],
    channelHints: {
      email: 'Подборки и документы', sms: 'Напоминания о показах', push: 'Новые объекты',
      in_app: 'Избранное обновлено', telegram: 'Бот-поиск',
    },
    segmentHints: { vip: 'Премиум (бюджет > 15M)', at_risk: 'Нет активности 30+ дн.', new: 'Новое обращение' },
  },

  flowers: {
    label: 'Рассылки цветочного', icon: '💐',
    recipientLabel: 'Заказчик', recipientPlural: 'Заказчики',
    defaultTemplates: [
      { name: 'Напоминание о дате',    desc: 'День рождения / годовщина', trigger: 'birthday' },
      { name: 'Сезонные букеты',       desc: 'Весна / лето / осень',     trigger: 'custom' },
      { name: 'Скидка постоянным',     desc: '-10% на следующий заказ',   trigger: 'loyalty' },
    ],
    channelHints: {
      email: 'Каталог и акции', sms: 'Напоминания о важных датах', push: 'Доставка в пути',
      in_app: 'Промо-баннеры', telegram: 'Бот заказов',
    },
    segmentHints: { vip: 'Постоянные (5+ заказов)', at_risk: 'Пропустил дату', new: 'Первый заказ' },
  },

  fashion: {
    label: 'Рассылки магазина', icon: '👗',
    recipientLabel: 'Покупатель', recipientPlural: 'Покупатели',
    defaultTemplates: [
      { name: 'Новая коллекция',       desc: 'Анонс сезонной коллекции', trigger: 'custom' },
      { name: 'Sale',                  desc: 'Распродажа -30%',         trigger: 'custom' },
      { name: 'Лукбук',               desc: 'Персональная подборка',    trigger: 'loyalty' },
      { name: 'Брошенная корзина',     desc: 'Товар ждёт вас',          trigger: 'abandoned_cart' },
    ],
    channelHints: {
      email: 'Лукбуки и sale', sms: 'Флеш-акции', push: 'Новинки',
      in_app: 'Персональный стиль', telegram: 'Fashion-бот',
    },
    segmentHints: { vip: 'Шопоголики (LTV > 30k)', at_risk: 'Нет покупок 45+ дней', new: 'Первая покупка' },
  },

  furniture: {
    label: 'Рассылки мебельного', icon: '🛋️',
    recipientLabel: 'Клиент', recipientPlural: 'Клиенты',
    defaultTemplates: [
      { name: 'Дизайн-проект готов',   desc: '3D-визуализация',   trigger: 'custom' },
      { name: 'Скидка на комплект',    desc: 'Комплект со скидкой', trigger: 'loyalty' },
      { name: 'Сезонная распродажа',   desc: 'Ликвидация коллекции', trigger: 'custom' },
    ],
    channelHints: {
      email: 'Каталоги и проекты', sms: 'Статус заказа / доставки', push: 'Мебель доставлена',
      in_app: 'Акции', telegram: 'Бот-консультант',
    },
    segmentHints: { vip: 'Проектные (бюджет > 100k)', at_risk: 'Проект на паузе', new: 'Первый запрос' },
  },

  fitness: {
    label: 'Рассылки клуба', icon: '💪',
    recipientLabel: 'Член клуба', recipientPlural: 'Члены клуба',
    defaultTemplates: [
      { name: 'Продление абонемента',  desc: 'Осталось 7 дней',   trigger: 'reminder' },
      { name: 'Новое расписание',      desc: 'Обновление групп',  trigger: 'custom' },
      { name: 'Заморозка кончилась',   desc: 'Добро пожаловать!', trigger: 'reactivation' },
    ],
    channelHints: {
      email: 'Расписание и акции', sms: 'Напоминания о тренировке', push: 'Тренировка через час',
      in_app: 'Программы', telegram: 'Фитнес-бот',
    },
    segmentHints: { vip: 'Premium-абонемент', at_risk: 'Нет визитов 14+ дней', new: 'Новичок' },
  },

  travel: {
    label: 'Рассылки турагентства', icon: '✈️',
    recipientLabel: 'Турист', recipientPlural: 'Туристы',
    defaultTemplates: [
      { name: 'Горящие туры',          desc: 'Вылет в ближайшие 7 дн.', trigger: 'custom' },
      { name: 'Персональная подборка', desc: 'По предпочтениям',        trigger: 'loyalty' },
      { name: 'Post-travel',           desc: 'Спасибо за путешествие',  trigger: 'post_purchase' },
    ],
    channelHints: {
      email: 'Подборки туров', sms: 'Изменения по бронированию', push: 'Вылет скоро',
      in_app: 'Промо', telegram: 'Travel-бот',
    },
    segmentHints: { vip: 'Премиум-путешественники', at_risk: 'Нет поездок 6+ мес.', new: 'Первое обращение' },
  },

  default: {
    label: 'Рассылки', icon: '📨',
    recipientLabel: 'Клиент', recipientPlural: 'Клиенты',
    defaultTemplates: [
      { name: 'Приветственное',  desc: 'Welcome-серия',          trigger: 'welcome' },
      { name: 'Реактивация',    desc: 'Возвращаем неактивных',   trigger: 'reactivation' },
      { name: 'После покупки',  desc: 'Отзыв + бонус',          trigger: 'post_purchase' },
    ],
    channelHints: {
      email: 'Информация и акции', sms: 'Быстрые уведомления', push: 'Push-уведомления',
      in_app: 'Внутренние уведомления', telegram: 'Telegram-бот',
    },
    segmentHints: { vip: 'VIP-клиенты', at_risk: 'Рискуют уйти', new: 'Новые' },
  },
}

const vc = computed<VerticalMailingConfig>(() =>
  VERTICAL_MAILING_CONFIG[props.vertical] ?? VERTICAL_MAILING_CONFIG.default
)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  MAPS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const STATUS_MAP: Record<CampaignStatus, { label: string; icon: string; color: string; dot: string; bg: string }> = {
  draft:     { label: 'Черновик',    icon: '📝', color: 'text-zinc-400',    dot: 'bg-zinc-400',    bg: 'bg-zinc-500/12' },
  scheduled: { label: 'Запланирована', icon: '📅', color: 'text-sky-400',   dot: 'bg-sky-400',     bg: 'bg-sky-500/12' },
  sending:   { label: 'Отправляется', icon: '🚀', color: 'text-amber-400', dot: 'bg-amber-400',   bg: 'bg-amber-500/12' },
  sent:      { label: 'Отправлена',   icon: '✅', color: 'text-emerald-400', dot: 'bg-emerald-400', bg: 'bg-emerald-500/12' },
  paused:    { label: 'На паузе',     icon: '⏸️', color: 'text-orange-400', dot: 'bg-orange-400',  bg: 'bg-orange-500/12' },
  cancelled: { label: 'Отменена',     icon: '🚫', color: 'text-rose-400',  dot: 'bg-rose-400',    bg: 'bg-rose-500/12' },
  failed:    { label: 'Ошибка',       icon: '❌', color: 'text-red-400',   dot: 'bg-red-400',     bg: 'bg-red-500/12' },
}

const CHANNEL_MAP: Record<ChannelType, { label: string; icon: string; color: string; bg: string }> = {
  email:    { label: 'E-mail',   icon: '✉️',  color: 'text-sky-400',     bg: 'bg-sky-500/12' },
  sms:      { label: 'SMS',      icon: '💬',  color: 'text-emerald-400', bg: 'bg-emerald-500/12' },
  push:     { label: 'Push',     icon: '🔔',  color: 'text-violet-400',  bg: 'bg-violet-500/12' },
  in_app:   { label: 'In-app',   icon: '📱',  color: 'text-amber-400',   bg: 'bg-amber-500/12' },
  telegram: { label: 'Telegram', icon: '✈️',  color: 'text-cyan-400',    bg: 'bg-cyan-500/12' },
}

const SEGMENT_MAP: Record<SegmentTarget, { label: string; icon: string }> = {
  all:     { label: 'Все',           icon: '👥' },
  vip:     { label: 'VIP',           icon: '👑' },
  loyal:   { label: 'Постоянные',    icon: '💎' },
  new:     { label: 'Новые',         icon: '🌱' },
  at_risk: { label: 'Рискуют уйти', icon: '⚠️' },
  dormant: { label: 'Спящие',        icon: '😴' },
  b2b:     { label: 'B2B',           icon: '🏢' },
  custom:  { label: 'Свой сегмент',  icon: '🎯' },
}

const TRIGGER_MAP: Record<TriggerType, { label: string; icon: string; color: string }> = {
  welcome:        { label: 'Welcome-серия',       icon: '👋', color: 'text-emerald-400' },
  abandoned_cart: { label: 'Брошенная корзина',   icon: '🛒', color: 'text-amber-400' },
  birthday:       { label: 'День рождения',       icon: '🎂', color: 'text-pink-400' },
  reactivation:   { label: 'Реактивация',         icon: '🔄', color: 'text-orange-400' },
  post_purchase:  { label: 'После покупки',       icon: '🎉', color: 'text-violet-400' },
  reminder:       { label: 'Напоминание',         icon: '⏰', color: 'text-sky-400' },
  loyalty:        { label: 'Программа лояльности', icon: '💎', color: 'text-indigo-400' },
  custom:         { label: 'Настраиваемый',       icon: '⚙️', color: 'text-zinc-400' },
}

const TEMPLATE_CAT_MAP: Record<TemplateCategory, { label: string; icon: string }> = {
  promo:         { label: 'Промо',         icon: '🔥' },
  transactional: { label: 'Транзакционные', icon: '📄' },
  trigger:       { label: 'Триггерные',    icon: '⚡' },
  newsletter:    { label: 'Дайджест',      icon: '📰' },
  seasonal:      { label: 'Сезонные',      icon: '🌸' },
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STATE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const rootEl             = ref<HTMLElement | null>(null)
const scrollSentinel     = ref<HTMLElement | null>(null)
const isFullscreen       = ref(false)
const showCampaignModal  = ref(false)
const showActionsMenu    = ref(false)
const showFilterDrawer   = ref(false)
const selectedCampaign   = ref<Campaign | null>(null)
const currentPage        = ref(1)
const activeMainTab      = ref<'campaigns' | 'templates' | 'triggers'>('campaigns')

// View mode: table or cards
const viewAs = ref<'table' | 'cards'>('table')

// Bulk selection
const selectedIds = reactive<Set<number | string>>(new Set())

// Filters
const filters = reactive<MailingFilter>({
  search:    '',
  status:    '',
  channel:   '',
  segment:   '',
  dateFrom:  '',
  dateTo:    '',
  sortBy:    'createdAt',
  sortDir:   'desc',
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  COMPUTED
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const totalPages = computed(() => Math.ceil(props.totalCampaigns / props.perPage) || 1)

const hasActiveFilters = computed(() =>
  filters.status !== '' || filters.channel !== '' ||
  filters.segment !== '' || filters.dateFrom !== '' || filters.dateTo !== ''
)

/** Client-side filtering */
const filteredCampaigns = computed(() => {
  let result = [...props.campaigns]
  const q = filters.search.toLowerCase().trim()
  if (q) {
    result = result.filter(
      (c) =>
        c.name.toLowerCase().includes(q) ||
        c.subject.toLowerCase().includes(q) ||
        (c.templateName && c.templateName.toLowerCase().includes(q))
    )
  }
  if (filters.status)  result = result.filter((c) => c.status === filters.status)
  if (filters.channel) result = result.filter((c) => c.channel === filters.channel)
  if (filters.segment) result = result.filter((c) => c.segment === filters.segment)
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

const paginatedCampaigns = computed(() => {
  const start = (currentPage.value - 1) * props.perPage
  return filteredCampaigns.value.slice(start, start + props.perPage)
})

const isAllSelected = computed(() =>
  filteredCampaigns.value.length > 0 &&
  filteredCampaigns.value.every((c) => selectedIds.has(c.id))
)

const statusCounts = computed(() => {
  const m: Record<string, number> = {}
  for (const c of props.campaigns) {
    m[c.status] = (m[c.status] || 0) + 1
  }
  return m
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

function fmtPercent(v: number): string {
  return v.toFixed(1) + '%'
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

function fmtDateTime(iso: string): string {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('ru-RU', {
    day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
  }).format(new Date(iso))
}

function openRate(c: Campaign): number {
  return c.delivered > 0 ? Math.round((c.opened / c.delivered) * 100) : 0
}

function clickRate(c: Campaign): number {
  return c.opened > 0 ? Math.round((c.clicked / c.opened) * 100) : 0
}

function conversionRate(c: Campaign): number {
  return c.delivered > 0 ? Math.round((c.converted / c.delivered) * 100) : 0
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  ACTIONS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function openCampaign(campaign: Campaign) {
  selectedCampaign.value  = campaign
  showCampaignModal.value = true
  emit('campaign-click', campaign)
}

function closeCampaignModal() {
  showCampaignModal.value = false
  selectedCampaign.value  = null
}

function toggleFullscreen() {
  if (!rootEl.value) return
  if (!isFullscreen.value) rootEl.value.requestFullscreen?.()
  else document.exitFullscreen?.()
}

function handleFullscreenChange() {
  isFullscreen.value = !!document.fullscreenElement
}

function clearAllFilters() {
  filters.search   = ''
  filters.status   = ''
  filters.channel  = ''
  filters.segment  = ''
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
    selectedIds.clear()
  } else {
    filteredCampaigns.value.forEach((c) => selectedIds.add(c.id))
  }
}

function toggleCampaignSelect(id: number | string) {
  if (selectedIds.has(id)) selectedIds.delete(id)
  else selectedIds.add(id)
}

function executeBulkAction(action: string) {
  emit('bulk-action', action, Array.from(selectedIds))
  selectedIds.clear()
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  KEYBOARD
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    if (showCampaignModal.value)  { closeCampaignModal(); return }
    if (showFilterDrawer.value)   { showFilterDrawer.value = false; return }
    if (isFullscreen.value)       { toggleFullscreen(); return }
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
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-ml_0.6s_ease-out]'
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
        <div class="flex items-center gap-2 flex-1 min-w-0">
          <span class="text-xl">{{ vc.icon }}</span>
          <h1 class="text-base sm:text-lg font-bold text-(--t-text) truncate">{{ vc.label }}</h1>
          <VBadge v-if="props.stats.activeTriggers > 0" variant="info" size="sm">
            {{ props.stats.activeTriggers }} триггеров
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

      <!-- Row 2: search + channel chips + buttons -->
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
            placeholder="Поиск рассылок…"
            class="inline-size-full py-2 ps-9 pe-3 text-sm rounded-xl
                   bg-(--t-bg)/60 border border-(--t-border)/50 text-(--t-text)
                   placeholder:text-(--t-text-3) focus:border-(--t-primary)/60
                   focus:ring-1 focus:ring-(--t-primary)/30 outline-none transition-all"
          />
        </div>

        <!-- Channel chips (desktop) -->
        <div class="hidden sm:flex items-center gap-1.5">
          <button
            v-for="(ch, chKey) in CHANNEL_MAP" :key="chKey"
            :class="[
              'relative overflow-hidden text-xs px-2.5 py-1.5 rounded-lg border transition-all active:scale-95',
              filters.channel === (chKey as string)
                ? `${ch.bg} ${ch.color} border-transparent font-semibold`
                : 'border-(--t-border)/50 text-(--t-text-3) hover:text-(--t-text) hover:border-(--t-text-3)/40',
            ]"
            @click="filters.channel = filters.channel === (chKey as string) ? '' : (chKey as string); emit('filter-change', { ...filters })"
            @mousedown="ripple"
          >
            {{ ch.icon }} {{ ch.label }}
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

        <!-- Create campaign -->
        <button
          class="relative overflow-hidden inline-flex items-center gap-1.5 px-3 py-2
                 rounded-xl text-xs font-semibold bg-(--t-primary)/12 text-(--t-primary)
                 border border-(--t-primary)/20 hover:bg-(--t-primary)/20 active:scale-[0.97]
                 transition-all"
          @click="emit('campaign-create')" @mousedown="ripple"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          <span class="hidden sm:inline">Создать рассылку</span>
          <span class="sm:hidden">Создать</span>
        </button>

        <!-- Export -->
        <button
          class="relative overflow-hidden w-9 h-9 rounded-lg border border-(--t-border)/50
                 bg-(--t-surface) flex items-center justify-center text-(--t-text-2)
                 hover:bg-(--t-card-hover) active:scale-95 transition-all"
          @click="emit('export', 'xlsx')" @mousedown="ripple" title="Экспорт XLSX"
        >
          📥
        </button>
      </div>

      <!-- Row 3: main tabs -->
      <div class="flex items-center gap-1 px-4 sm:px-6">
        <button v-for="t in [
          { key: 'campaigns', label: 'Кампании',  icon: '📨', count: props.stats.totalCampaigns },
          { key: 'templates', label: 'Шаблоны',   icon: '📋', count: props.stats.templatesCount },
          { key: 'triggers',  label: 'Триггеры',  icon: '⚡', count: props.stats.activeTriggers },
        ] as const" :key="t.key"
                :class="[
                  'relative overflow-hidden px-3 py-2.5 text-xs font-medium transition-colors',
                  activeMainTab === t.key
                    ? 'text-(--t-primary) border-b-2 border-(--t-primary)'
                    : 'text-(--t-text-3) hover:text-(--t-text)',
                ]"
                @click="activeMainTab = t.key as typeof activeMainTab" @mousedown="ripple">
          {{ t.icon }} {{ t.label }}
          <span v-if="t.count > 0" class="ms-1 text-[10px] opacity-60">({{ t.count }})</span>
        </button>
      </div>
    </header>

    <!-- ══════════════════════════════════════════════
         KPI WIDGETS
    ══════════════════════════════════════════════ -->
    <section class="px-4 sm:px-6 pt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
      <!-- Total sent -->
      <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">Отправлено / мес.</span>
        <span class="text-lg font-bold text-(--t-text)">{{ fmtNumber(props.stats.sentThisMonth) }}</span>
        <span class="text-[10px] text-(--t-text-3)">📨 всего: {{ fmtNumber(props.stats.totalSent) }}</span>
      </div>
      <!-- Open rate -->
      <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">Открытия</span>
        <span :class="['text-lg font-bold', props.stats.avgOpenRate >= 25 ? 'text-emerald-400' : props.stats.avgOpenRate >= 15 ? 'text-amber-400' : 'text-rose-400']">
          {{ fmtPercent(props.stats.avgOpenRate) }}
        </span>
        <span class="text-[10px] text-(--t-text-3)">👁️ средний Open Rate</span>
      </div>
      <!-- Click rate -->
      <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">Клики</span>
        <span :class="['text-lg font-bold', props.stats.avgClickRate >= 5 ? 'text-emerald-400' : 'text-amber-400']">
          {{ fmtPercent(props.stats.avgClickRate) }}
        </span>
        <span class="text-[10px] text-(--t-text-3)">🖱️ средний CTR</span>
      </div>
      <!-- Conversion -->
      <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">Конверсия</span>
        <span class="text-lg font-bold text-violet-400">{{ fmtPercent(props.stats.avgConversion) }}</span>
        <span class="text-[10px] text-(--t-text-3)">🎯 заказов из рассылок</span>
      </div>
      <!-- Revenue -->
      <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 backdrop-blur-sm p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">Выручка</span>
        <span class="text-lg font-bold text-emerald-400">{{ fmtCurrency(props.stats.totalRevenue) }}</span>
        <span class="text-[10px] text-(--t-text-3)">💰 от рассылок</span>
      </div>
      <!-- ROI -->
      <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">ROI</span>
        <span :class="['text-lg font-bold', props.stats.roiPercent >= 100 ? 'text-emerald-400' : 'text-amber-400']">
          {{ props.stats.roiPercent > 0 ? '+' : '' }}{{ fmtPercent(props.stats.roiPercent) }}
        </span>
        <span class="text-[10px] text-(--t-text-3)">📈 отдача от вложений</span>
      </div>
    </section>

    <!-- ══════════════════════════════════════════════
         BULK BAR
    ══════════════════════════════════════════════ -->
    <Transition name="slide-ml">
      <div v-if="selectedIds.size > 0"
           class="mx-4 sm:mx-6 mt-3 flex items-center gap-2 rounded-xl
                  border border-(--t-primary)/30 bg-(--t-primary)/8 px-4 py-2.5">
        <span class="text-xs font-medium text-(--t-text)">Выбрано: {{ selectedIds.size }}</span>
        <div class="flex-1" />
        <button
          class="relative overflow-hidden text-xs px-2.5 py-1.5 rounded-lg
                 bg-rose-500/12 text-rose-400 border border-rose-500/20
                 hover:bg-rose-500/20 active:scale-95 transition-all"
          @click="executeBulkAction('delete')" @mousedown="ripple"
        >
          🗑️ Удалить
        </button>
        <button
          class="relative overflow-hidden text-xs px-2.5 py-1.5 rounded-lg
                 bg-sky-500/12 text-sky-400 border border-sky-500/20
                 hover:bg-sky-500/20 active:scale-95 transition-all"
          @click="executeBulkAction('duplicate')" @mousedown="ripple"
        >
          📋 Дублировать
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
         MAIN CONTENT
    ══════════════════════════════════════════════ -->
    <div class="flex-1 flex flex-col gap-4 px-4 sm:px-6 py-4">

      <!-- ═══ TAB: CAMPAIGNS ═══ -->
      <template v-if="activeMainTab === 'campaigns'">

        <!-- Status chips row -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
          <button
            v-for="(st, stKey) in STATUS_MAP" :key="stKey"
            :class="[
              'relative overflow-hidden shrink-0 text-[10px] px-2.5 py-1.5 rounded-lg border transition-all active:scale-95',
              filters.status === (stKey as string)
                ? `${st.bg} ${st.color} border-transparent font-semibold`
                : 'border-(--t-border)/50 text-(--t-text-3) hover:text-(--t-text)',
            ]"
            @click="filters.status = filters.status === (stKey as string) ? '' : (stKey as string); currentPage = 1; emit('filter-change', { ...filters })"
            @mousedown="ripple"
          >
            {{ st.icon }} {{ st.label }}
            <span v-if="statusCounts[stKey as string]" class="ms-1 opacity-60">{{ statusCounts[stKey as string] }}</span>
          </button>
        </div>

        <!-- Loading -->
        <div v-if="props.loading && props.campaigns.length === 0" class="flex flex-col gap-3">
          <div v-for="n in 5" :key="n" class="h-16 rounded-xl bg-(--t-surface)/60 animate-pulse" />
        </div>

        <!-- Empty -->
        <div v-else-if="filteredCampaigns.length === 0 && !props.loading"
             class="flex flex-col items-center justify-center py-16 text-center">
          <span class="text-4xl mb-3">📨</span>
          <p class="text-sm font-medium text-(--t-text-2)">
            {{ filters.search || hasActiveFilters ? 'Ничего не найдено' : 'Рассылки ещё не создавались' }}
          </p>
          <p class="text-xs text-(--t-text-3) mt-1 max-w-xs">
            {{ filters.search || hasActiveFilters
              ? 'Попробуйте изменить фильтры или поиск'
              : 'Создайте первую кампанию для ваших ' + vc.recipientPlural.toLowerCase()
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
                <th class="px-2 py-2.5 text-start font-medium text-(--t-text-3) cursor-pointer
                           hover:text-(--t-text) select-none" @click="toggleSort('name')">
                  <span class="flex items-center gap-1">
                    Кампания
                    <svg v-if="filters.sortBy === 'name'" class="w-3 h-3"
                         :class="filters.sortDir === 'asc' ? '' : 'rotate-180'"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                    </svg>
                  </span>
                </th>
                <th class="px-2 py-2.5 text-center font-medium text-(--t-text-3)">Канал</th>
                <th class="px-2 py-2.5 text-center font-medium text-(--t-text-3)">Статус</th>
                <th class="px-2 py-2.5 text-center font-medium text-(--t-text-3) hidden md:table-cell">Сегмент</th>
                <th class="px-2 py-2.5 text-end font-medium text-(--t-text-3) hidden md:table-cell cursor-pointer
                           hover:text-(--t-text) select-none" @click="toggleSort('delivered')">
                  <span class="flex items-center justify-end gap-1">
                    Доставлено
                    <svg v-if="filters.sortBy === 'delivered'" class="w-3 h-3"
                         :class="filters.sortDir === 'asc' ? '' : 'rotate-180'"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                    </svg>
                  </span>
                </th>
                <th class="px-2 py-2.5 text-end font-medium text-(--t-text-3) hidden lg:table-cell">Open%</th>
                <th class="px-2 py-2.5 text-end font-medium text-(--t-text-3) hidden lg:table-cell">CTR%</th>
                <th class="px-2 py-2.5 text-end font-medium text-(--t-text-3) hidden xl:table-cell cursor-pointer
                           hover:text-(--t-text) select-none" @click="toggleSort('revenue')">
                  <span class="flex items-center justify-end gap-1">
                    Выручка
                    <svg v-if="filters.sortBy === 'revenue'" class="w-3 h-3"
                         :class="filters.sortDir === 'asc' ? '' : 'rotate-180'"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                    </svg>
                  </span>
                </th>
                <th class="px-2 py-2.5 text-end font-medium text-(--t-text-3) hidden lg:table-cell">Дата</th>
                <th class="px-2 py-2.5 inline-size-10" />
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in paginatedCampaigns" :key="c.id"
                  :class="[
                    'border-b border-(--t-border)/20 hover:bg-(--t-card-hover)/50 transition-colors cursor-pointer',
                    selectedIds.has(c.id) ? 'bg-(--t-primary)/5' : '',
                  ]"
                  @click="openCampaign(c)">
                <td class="ps-3 py-2.5" @click.stop>
                  <input type="checkbox" :checked="selectedIds.has(c.id)"
                         class="w-3.5 h-3.5 rounded border-zinc-600 bg-zinc-800 accent-(--t-primary)"
                         @change="toggleCampaignSelect(c.id)" />
                </td>
                <!-- Name -->
                <td class="px-2 py-2.5">
                  <div class="flex items-center gap-1.5">
                    <p class="font-medium text-(--t-text) truncate max-w-60">{{ c.name }}</p>
                    <span v-if="c.isAutomated" class="text-[9px] px-1 py-0.5 rounded bg-violet-500/15 text-violet-400 font-semibold">авто</span>
                  </div>
                  <p class="text-[10px] text-(--t-text-3) truncate max-w-60">{{ c.subject }}</p>
                </td>
                <!-- Channel -->
                <td class="px-2 py-2.5 text-center">
                  <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium', CHANNEL_MAP[c.channel].bg, CHANNEL_MAP[c.channel].color]">
                    {{ CHANNEL_MAP[c.channel].icon }} {{ CHANNEL_MAP[c.channel].label }}
                  </span>
                </td>
                <!-- Status -->
                <td class="px-2 py-2.5 text-center">
                  <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium', STATUS_MAP[c.status].bg, STATUS_MAP[c.status].color]">
                    <span :class="['w-1.5 h-1.5 rounded-full', STATUS_MAP[c.status].dot]" />
                    {{ STATUS_MAP[c.status].label }}
                  </span>
                </td>
                <!-- Segment -->
                <td class="px-2 py-2.5 text-center text-(--t-text-3) hidden md:table-cell">
                  {{ SEGMENT_MAP[c.segment]?.icon ?? '' }} {{ c.segmentLabel ?? SEGMENT_MAP[c.segment]?.label ?? c.segment }}
                </td>
                <!-- Delivered -->
                <td class="px-2 py-2.5 text-end font-medium text-(--t-text) hidden md:table-cell">
                  {{ fmtNumber(c.delivered) }}
                  <span class="text-(--t-text-3)"> / {{ fmtNumber(c.totalRecipients) }}</span>
                </td>
                <!-- Open rate -->
                <td class="px-2 py-2.5 text-end hidden lg:table-cell">
                  <span :class="openRate(c) >= 25 ? 'text-emerald-400' : openRate(c) >= 15 ? 'text-amber-400' : 'text-rose-400'" class="font-medium">
                    {{ openRate(c) }}%
                  </span>
                </td>
                <!-- CTR -->
                <td class="px-2 py-2.5 text-end hidden lg:table-cell">
                  <span :class="clickRate(c) >= 5 ? 'text-emerald-400' : 'text-amber-400'" class="font-medium">
                    {{ clickRate(c) }}%
                  </span>
                </td>
                <!-- Revenue -->
                <td class="px-2 py-2.5 text-end font-semibold text-emerald-400 hidden xl:table-cell">
                  {{ c.revenue > 0 ? fmtCurrency(c.revenue) : '—' }}
                </td>
                <!-- Date -->
                <td class="px-2 py-2.5 text-end text-(--t-text-3) hidden lg:table-cell">
                  {{ fmtDateShort(c.sentAt ?? c.scheduledAt ?? c.createdAt) }}
                </td>
                <!-- Actions -->
                <td class="px-2 py-2.5" @click.stop>
                  <button class="w-7 h-7 rounded-md flex items-center justify-center
                                 text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover) transition-colors"
                          @click="emit('campaign-edit', c)">
                    ✏️
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ═══ CARDS (mobile) ═══ -->
        <div v-else class="flex flex-col gap-2.5">
          <div v-for="c in paginatedCampaigns" :key="c.id"
               :class="[
                 'rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60',
                 'backdrop-blur-sm p-3 transition-all active:scale-[0.99] cursor-pointer',
                 'hover:border-(--t-border)',
               ]"
               @click="openCampaign(c)">
            <div class="flex items-start gap-3">
              <input type="checkbox" :checked="selectedIds.has(c.id)"
                     class="mt-1 w-3.5 h-3.5 rounded border-zinc-600 bg-zinc-800 accent-(--t-primary) shrink-0"
                     @click.stop @change="toggleCampaignSelect(c.id)" />
              <div class="flex-1 min-w-0">
                <!-- Title row -->
                <div class="flex items-center gap-1.5">
                  <p class="text-sm font-semibold text-(--t-text) truncate">{{ c.name }}</p>
                  <span v-if="c.isAutomated" class="shrink-0 text-[9px] px-1 py-0.5 rounded bg-violet-500/15 text-violet-400 font-semibold">авто</span>
                </div>
                <p class="text-[10px] text-(--t-text-3) truncate mt-0.5">{{ c.subject }}</p>

                <!-- Badges row -->
                <div class="flex items-center gap-2 mt-2">
                  <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium', CHANNEL_MAP[c.channel].bg, CHANNEL_MAP[c.channel].color]">
                    {{ CHANNEL_MAP[c.channel].icon }} {{ CHANNEL_MAP[c.channel].label }}
                  </span>
                  <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium', STATUS_MAP[c.status].bg, STATUS_MAP[c.status].color]">
                    <span :class="['w-1.5 h-1.5 rounded-full', STATUS_MAP[c.status].dot]" />
                    {{ STATUS_MAP[c.status].label }}
                  </span>
                </div>

                <!-- Stats row -->
                <div class="flex items-center gap-3 mt-2 text-xs">
                  <div>
                    <span class="text-(--t-text-3)">Доставлено:</span>
                    <span class="font-medium text-(--t-text) ms-1">{{ fmtNumber(c.delivered) }}</span>
                  </div>
                  <div>
                    <span class="text-(--t-text-3)">Open:</span>
                    <span :class="['font-medium ms-1', openRate(c) >= 25 ? 'text-emerald-400' : 'text-amber-400']">{{ openRate(c) }}%</span>
                  </div>
                  <div v-if="c.revenue > 0" class="ms-auto">
                    <span class="font-semibold text-emerald-400">{{ fmtCurrency(c.revenue) }}</span>
                  </div>
                </div>

                <!-- Date -->
                <div class="text-[10px] text-(--t-text-3) mt-1.5">
                  {{ fmtDateShort(c.sentAt ?? c.scheduledAt ?? c.createdAt) }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
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
        <div v-if="props.loading && props.campaigns.length > 0" class="flex justify-center py-4">
          <div class="w-5 h-5 border-2 border-(--t-primary)/30 border-t-(--t-primary) rounded-full animate-spin" />
        </div>
      </template>

      <!-- ═══ TAB: TEMPLATES ═══ -->
      <template v-if="activeMainTab === 'templates'">
        <div class="flex items-center justify-between mb-2">
          <h2 class="text-sm font-bold text-(--t-text)">Шаблоны рассылок</h2>
          <button
            class="relative overflow-hidden inline-flex items-center gap-1.5 px-3 py-2
                   rounded-xl text-xs font-semibold bg-(--t-primary)/12 text-(--t-primary)
                   border border-(--t-primary)/20 hover:bg-(--t-primary)/20 active:scale-[0.97] transition-all"
            @click="emit('template-create')" @mousedown="ripple"
          >
            + Создать шаблон
          </button>
        </div>

        <!-- Default templates from vertical -->
        <div v-if="vc.defaultTemplates.length > 0" class="mb-4">
          <h3 class="text-xs font-medium text-(--t-text-3) mb-2">Рекомендуемые для {{ vc.label.toLowerCase() }}</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
            <div v-for="dt in vc.defaultTemplates" :key="dt.name"
                 class="rounded-xl border border-dashed border-(--t-primary)/30 bg-(--t-primary)/5
                        backdrop-blur-sm p-3 cursor-pointer hover:border-(--t-primary)/50
                        hover:bg-(--t-primary)/10 transition-all active:scale-[0.98]"
                 @click="emit('template-create')">
              <div class="flex items-center gap-2">
                <span class="text-base">{{ TRIGGER_MAP[dt.trigger]?.icon ?? '📋' }}</span>
                <p class="text-xs font-semibold text-(--t-text)">{{ dt.name }}</p>
              </div>
              <p class="text-[10px] text-(--t-text-3) mt-1">{{ dt.desc }}</p>
              <span :class="['inline-block mt-2 text-[9px] px-1.5 py-0.5 rounded', TRIGGER_MAP[dt.trigger]?.color ?? 'text-zinc-400']">
                {{ TRIGGER_MAP[dt.trigger]?.label ?? dt.trigger }}
              </span>
            </div>
          </div>
        </div>

        <!-- Existing templates -->
        <div v-if="props.templates.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
          <div v-for="tpl in props.templates" :key="tpl.id"
               class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60
                      backdrop-blur-sm p-3 cursor-pointer hover:border-(--t-border)
                      hover:bg-(--t-card-hover)/30 transition-all active:scale-[0.98]"
               @click="emit('template-click', tpl)">
            <div class="flex items-center justify-between">
              <span :class="['text-xs font-medium', CHANNEL_MAP[tpl.channel]?.color ?? 'text-(--t-text-2)']">
                {{ CHANNEL_MAP[tpl.channel]?.icon }} {{ CHANNEL_MAP[tpl.channel]?.label }}
              </span>
              <span class="text-[10px] px-1.5 py-0.5 rounded bg-(--t-bg)/60 text-(--t-text-3)">
                {{ TEMPLATE_CAT_MAP[tpl.category]?.icon }} {{ TEMPLATE_CAT_MAP[tpl.category]?.label }}
              </span>
            </div>
            <p class="text-sm font-semibold text-(--t-text) mt-2 truncate">{{ tpl.name }}</p>
            <p v-if="tpl.description" class="text-[10px] text-(--t-text-3) mt-1 line-clamp-2">{{ tpl.description }}</p>
            <div class="flex items-center justify-between mt-3 text-[10px] text-(--t-text-3)">
              <span>Используется: {{ tpl.usageCount }}×</span>
              <span v-if="tpl.lastUsedAt">{{ fmtDateShort(tpl.lastUsedAt) }}</span>
            </div>
            <button
              class="relative overflow-hidden inline-size-full mt-2 py-1.5 rounded-lg text-xs
                     font-medium border border-(--t-border)/40 text-(--t-text-2)
                     hover:bg-(--t-card-hover) hover:text-(--t-text) active:scale-[0.97] transition-all"
              @click.stop="emit('template-use', tpl)" @mousedown="ripple"
            >
              Использовать шаблон
            </button>
          </div>
        </div>

        <div v-else-if="vc.defaultTemplates.length === 0" class="text-center py-16">
          <span class="text-4xl mb-3 block">📋</span>
          <p class="text-sm text-(--t-text-2)">Шаблонов пока нет</p>
          <p class="text-xs text-(--t-text-3) mt-1">Создайте первый шаблон для быстрого запуска кампаний</p>
        </div>
      </template>

      <!-- ═══ TAB: TRIGGERS ═══ -->
      <template v-if="activeMainTab === 'triggers'">
        <div class="flex items-center justify-between mb-2">
          <h2 class="text-sm font-bold text-(--t-text)">Триггерные кампании</h2>
          <button
            class="relative overflow-hidden inline-flex items-center gap-1.5 px-3 py-2
                   rounded-xl text-xs font-semibold bg-(--t-primary)/12 text-(--t-primary)
                   border border-(--t-primary)/20 hover:bg-(--t-primary)/20 active:scale-[0.97] transition-all"
            @click="emit('trigger-create')" @mousedown="ripple"
          >
            + Создать триггер
          </button>
        </div>

        <div v-if="props.triggers.length > 0" class="flex flex-col gap-3">
          <div v-for="tr in props.triggers" :key="tr.id"
               class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60
                      backdrop-blur-sm p-4 transition-all hover:border-(--t-border)">
            <div class="flex items-start gap-3">
              <!-- Toggle -->
              <button
                :class="[
                  'shrink-0 mt-0.5 w-10 h-6 rounded-full transition-all relative',
                  tr.isActive ? 'bg-emerald-500' : 'bg-zinc-700',
                ]"
                @click="emit('trigger-toggle', tr, !tr.isActive)"
              >
                <span :class="[
                  'absolute inset-block-start-0.5 w-5 h-5 rounded-full bg-white shadow transition-all',
                  tr.isActive ? 'inset-inline-start-[18px]' : 'inset-inline-start-0.5',
                ]" />
              </button>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <span :class="TRIGGER_MAP[tr.triggerType]?.color" class="text-sm">
                    {{ TRIGGER_MAP[tr.triggerType]?.icon }}
                  </span>
                  <p class="text-sm font-semibold text-(--t-text) truncate">{{ tr.name }}</p>
                  <span :class="['text-[10px] px-1.5 py-0.5 rounded font-medium', CHANNEL_MAP[tr.channel]?.bg, CHANNEL_MAP[tr.channel]?.color]">
                    {{ CHANNEL_MAP[tr.channel]?.label }}
                  </span>
                </div>
                <p class="text-[10px] text-(--t-text-3) mt-0.5">
                  Тип: {{ TRIGGER_MAP[tr.triggerType]?.label }}
                </p>

                <!-- Trigger stats -->
                <div class="flex items-center gap-4 mt-2 text-xs">
                  <div>
                    <span class="text-(--t-text-3)">Отправлено:</span>
                    <span class="font-medium text-(--t-text) ms-1">{{ fmtNumber(tr.totalSent) }}</span>
                  </div>
                  <div>
                    <span class="text-(--t-text-3)">Open:</span>
                    <span :class="['font-medium ms-1', tr.openRate >= 25 ? 'text-emerald-400' : 'text-amber-400']">
                      {{ fmtPercent(tr.openRate) }}
                    </span>
                  </div>
                  <div>
                    <span class="text-(--t-text-3)">Конверсия:</span>
                    <span :class="['font-medium ms-1', tr.conversionRate >= 5 ? 'text-emerald-400' : 'text-amber-400']">
                      {{ fmtPercent(tr.conversionRate) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Edit -->
              <button class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center
                             text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover) transition-colors"
                      @click="emit('trigger-edit', tr)">
                ✏️
              </button>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-16">
          <span class="text-4xl mb-3 block">⚡</span>
          <p class="text-sm text-(--t-text-2)">Триггеров пока нет</p>
          <p class="text-xs text-(--t-text-3) mt-1">
            Настройте автоматические рассылки: welcome-серия, брошенная корзина, реактивация
          </p>
        </div>
      </template>
    </div>

    <!-- ══════════════════════════════════════════════
         CAMPAIGN DETAIL MODAL
    ══════════════════════════════════════════════ -->
    <Transition name="modal-ml">
      <div v-if="showCampaignModal && selectedCampaign"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="closeCampaignModal">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeCampaignModal" />
        <div class="relative z-10 inline-size-full max-w-xl rounded-2xl border border-(--t-border)
                    bg-(--t-surface)/90 backdrop-blur-xl shadow-2xl overflow-hidden">

          <!-- Header -->
          <div class="flex items-center gap-3 px-5 pt-5 pb-3">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-(--t-text) truncate">{{ selectedCampaign.name }}</h3>
                <span v-if="selectedCampaign.isAutomated" class="shrink-0 text-[9px] px-1.5 py-0.5 rounded bg-violet-500/15 text-violet-400 font-semibold">авто</span>
              </div>
              <p class="text-[10px] text-(--t-text-3) mt-0.5 truncate">{{ selectedCampaign.subject }}</p>
            </div>
            <span :class="['shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium', STATUS_MAP[selectedCampaign.status].bg, STATUS_MAP[selectedCampaign.status].color]">
              <span :class="['w-1.5 h-1.5 rounded-full', STATUS_MAP[selectedCampaign.status].dot]" />
              {{ STATUS_MAP[selectedCampaign.status].label }}
            </span>
            <button class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center
                           text-(--t-text-3) hover:bg-(--t-card-hover) hover:text-(--t-text) transition-colors"
                    @click="closeCampaignModal">✕</button>
          </div>

          <!-- Info -->
          <div class="px-5 pb-3 flex flex-wrap items-center gap-2 text-xs">
            <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full', CHANNEL_MAP[selectedCampaign.channel].bg, CHANNEL_MAP[selectedCampaign.channel].color]">
              {{ CHANNEL_MAP[selectedCampaign.channel].icon }} {{ CHANNEL_MAP[selectedCampaign.channel].label }}
            </span>
            <span class="text-(--t-text-3)">
              {{ SEGMENT_MAP[selectedCampaign.segment]?.icon }} {{ selectedCampaign.segmentLabel ?? SEGMENT_MAP[selectedCampaign.segment]?.label }}
            </span>
            <span class="text-(--t-text-3)">·</span>
            <span class="text-(--t-text-3)">{{ fmtDateTime(selectedCampaign.sentAt ?? selectedCampaign.scheduledAt ?? selectedCampaign.createdAt) }}</span>
          </div>

          <!-- Stats grid -->
          <div class="px-5 pb-4 grid grid-cols-3 sm:grid-cols-6 gap-2">
            <div class="rounded-lg bg-(--t-bg)/40 border border-(--t-border)/30 p-2 text-center">
              <span class="text-[10px] text-(--t-text-3)">Получатели</span>
              <p class="text-sm font-bold text-(--t-text)">{{ fmtNumber(selectedCampaign.totalRecipients) }}</p>
            </div>
            <div class="rounded-lg bg-(--t-bg)/40 border border-(--t-border)/30 p-2 text-center">
              <span class="text-[10px] text-(--t-text-3)">Доставлено</span>
              <p class="text-sm font-bold text-emerald-400">{{ fmtNumber(selectedCampaign.delivered) }}</p>
            </div>
            <div class="rounded-lg bg-(--t-bg)/40 border border-(--t-border)/30 p-2 text-center">
              <span class="text-[10px] text-(--t-text-3)">Открыто</span>
              <p class="text-sm font-bold text-sky-400">{{ fmtNumber(selectedCampaign.opened) }}</p>
              <span class="text-[9px] text-(--t-text-3)">{{ openRate(selectedCampaign) }}%</span>
            </div>
            <div class="rounded-lg bg-(--t-bg)/40 border border-(--t-border)/30 p-2 text-center">
              <span class="text-[10px] text-(--t-text-3)">Кликов</span>
              <p class="text-sm font-bold text-violet-400">{{ fmtNumber(selectedCampaign.clicked) }}</p>
              <span class="text-[9px] text-(--t-text-3)">{{ clickRate(selectedCampaign) }}%</span>
            </div>
            <div class="rounded-lg bg-(--t-bg)/40 border border-(--t-border)/30 p-2 text-center">
              <span class="text-[10px] text-(--t-text-3)">Конверсий</span>
              <p class="text-sm font-bold text-amber-400">{{ fmtNumber(selectedCampaign.converted) }}</p>
              <span class="text-[9px] text-(--t-text-3)">{{ conversionRate(selectedCampaign) }}%</span>
            </div>
            <div class="rounded-lg bg-emerald-500/5 border border-emerald-500/20 p-2 text-center">
              <span class="text-[10px] text-(--t-text-3)">Выручка</span>
              <p class="text-sm font-bold text-emerald-400">{{ selectedCampaign.revenue > 0 ? fmtCurrency(selectedCampaign.revenue) : '—' }}</p>
            </div>
          </div>

          <!-- Delivery funnel -->
          <div class="px-5 pb-3">
            <p class="text-[10px] text-(--t-text-3) mb-1.5">Воронка доставки</p>
            <div class="flex gap-1 h-3 rounded-full overflow-hidden bg-(--t-bg)/60">
              <div v-if="selectedCampaign.delivered > 0"
                   class="bg-emerald-500 rounded-s-full transition-all duration-500"
                   :style="{ inlineSize: (selectedCampaign.delivered / selectedCampaign.totalRecipients * 100) + '%' }" />
              <div v-if="selectedCampaign.bounced > 0"
                   class="bg-rose-500 transition-all duration-500"
                   :style="{ inlineSize: (selectedCampaign.bounced / selectedCampaign.totalRecipients * 100) + '%' }" />
            </div>
            <div class="flex items-center justify-between text-[9px] text-(--t-text-3) mt-1">
              <span>✅ Доставлено: {{ fmtNumber(selectedCampaign.delivered) }}</span>
              <span>❌ Bounced: {{ fmtNumber(selectedCampaign.bounced) }}</span>
              <span>🚫 Отписки: {{ fmtNumber(selectedCampaign.unsubscribed) }}</span>
            </div>
          </div>

          <!-- Tags -->
          <div v-if="selectedCampaign.tags.length > 0" class="px-5 pb-3">
            <div class="flex flex-wrap gap-1.5">
              <span v-for="tag in selectedCampaign.tags" :key="tag"
                    class="text-[10px] px-1.5 py-0.5 rounded-md bg-(--t-bg)/60 text-(--t-text-3) border border-(--t-border)/30">
                {{ tag }}
              </span>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="selectedCampaign.notes" class="px-5 pb-3">
            <p class="text-[10px] text-(--t-text-3) mb-0.5">Заметки:</p>
            <p class="text-xs text-(--t-text-2) whitespace-pre-wrap">{{ selectedCampaign.notes }}</p>
          </div>

          <!-- Footer -->
          <div class="px-5 pb-4 flex flex-col-reverse sm:flex-row items-stretch sm:items-center
                      gap-2 sm:justify-end border-t border-(--t-border)/30 pt-3">
            <button class="relative overflow-hidden px-4 py-2 rounded-xl text-xs font-medium
                           border border-(--t-border) text-(--t-text-2)
                           hover:bg-(--t-surface) hover:text-(--t-text) active:scale-[0.97] transition-all"
                    @click="closeCampaignModal" @mousedown="ripple">
              Закрыть
            </button>
            <button v-if="selectedCampaign.status === 'draft'"
                    class="relative overflow-hidden px-4 py-2 rounded-xl text-xs font-semibold
                           bg-emerald-500/12 text-emerald-400 border border-emerald-500/20
                           hover:bg-emerald-500/20 active:scale-[0.97] transition-all"
                    @click="emit('campaign-send', selectedCampaign!); closeCampaignModal()" @mousedown="ripple">
              🚀 Отправить
            </button>
            <button v-if="selectedCampaign.status === 'paused'"
                    class="relative overflow-hidden px-4 py-2 rounded-xl text-xs font-semibold
                           bg-sky-500/12 text-sky-400 border border-sky-500/20
                           hover:bg-sky-500/20 active:scale-[0.97] transition-all"
                    @click="emit('campaign-resume', selectedCampaign!); closeCampaignModal()" @mousedown="ripple">
              ▶️ Возобновить
            </button>
            <button class="relative overflow-hidden px-4 py-2 rounded-xl text-xs font-medium
                           bg-sky-500/12 text-sky-400 border border-sky-500/20
                           hover:bg-sky-500/20 active:scale-[0.97] transition-all"
                    @click="emit('campaign-duplicate', selectedCampaign!); closeCampaignModal()" @mousedown="ripple">
              📋 Дублировать
            </button>
            <button class="relative overflow-hidden px-4 py-2 rounded-xl text-xs font-semibold
                           bg-(--t-primary) text-white hover:brightness-110 active:scale-[0.97]
                           transition-all shadow-sm"
                    @click="emit('campaign-edit', selectedCampaign!); closeCampaignModal()" @mousedown="ripple">
              ✏️ Редактировать
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════════════
         FILTER DRAWER (mobile)
    ══════════════════════════════════════════════ -->
    <Transition name="drawer-ml">
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

          <!-- Channels -->
          <div>
            <p class="text-xs font-medium text-(--t-text-2) mb-2">Канал</p>
            <div class="flex flex-col gap-1">
              <button v-for="(ch, chKey) in CHANNEL_MAP" :key="chKey"
                      :class="[
                        'relative overflow-hidden flex items-center gap-2 px-3 py-2 rounded-lg text-xs transition-all',
                        filters.channel === (chKey as string)
                          ? `${ch.bg} ${ch.color} font-semibold`
                          : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                      ]"
                      @click="filters.channel = filters.channel === (chKey as string) ? '' : (chKey as string); emit('filter-change', { ...filters })"
                      @mousedown="ripple">
                <span>{{ ch.icon }}</span> {{ ch.label }}
              </button>
            </div>
          </div>

          <!-- Statuses -->
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
                <span :class="['w-2 h-2 rounded-full', st.dot]" />
                {{ st.label }}
                <span v-if="statusCounts[stKey as string]" class="ms-auto text-[10px] opacity-60">{{ statusCounts[stKey as string] }}</span>
              </button>
            </div>
          </div>

          <!-- Segments -->
          <div>
            <p class="text-xs font-medium text-(--t-text-2) mb-2">Сегмент</p>
            <div class="flex flex-col gap-1">
              <button v-for="(seg, segKey) in SEGMENT_MAP" :key="segKey"
                      :class="[
                        'relative overflow-hidden flex items-center gap-2 px-3 py-2 rounded-lg text-xs transition-all',
                        filters.segment === (segKey as string)
                          ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold'
                          : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                      ]"
                      @click="filters.segment = filters.segment === (segKey as string) ? '' : (segKey as string); emit('filter-change', { ...filters })"
                      @mousedown="ripple">
                <span>{{ seg.icon }}</span> {{ seg.label }}
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
  </div>
</template>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     STYLES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<style scoped>
/* Ripple — unique suffix ml */
@keyframes ripple-ml {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* Slide (bulk bar) */
.slide-ml-enter-active,
.slide-ml-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.slide-ml-enter-from,
.slide-ml-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Modal */
.modal-ml-enter-active {
  transition: opacity 0.25s ease;
}
.modal-ml-enter-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}
.modal-ml-leave-active {
  transition: opacity 0.2s ease;
}
.modal-ml-leave-active > :last-child {
  transition: transform 0.2s ease-in, opacity 0.2s ease;
}
.modal-ml-enter-from,
.modal-ml-leave-to {
  opacity: 0;
}
.modal-ml-enter-from > :last-child {
  opacity: 0;
  transform: scale(0.92) translateY(12px);
}
.modal-ml-leave-to > :last-child {
  opacity: 0;
  transform: scale(0.95) translateY(6px);
}

/* Drawer */
.drawer-ml-enter-active,
.drawer-ml-leave-active {
  transition: opacity 0.3s ease;
}
.drawer-ml-enter-active > :last-child,
.drawer-ml-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.drawer-ml-enter-from,
.drawer-ml-leave-to {
  opacity: 0;
}
.drawer-ml-enter-from > :last-child,
.drawer-ml-leave-to > :last-child {
  transform: translateX(100%);
}
</style>
