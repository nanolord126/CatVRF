<!--
  ╔═══════════════════════════════════════════════════════════════════════════╗
  ║  TenantHelp.vue — помощь и поддержка в B2B Tenant Dashboard             ║
  ║  CatVRF 2026 — 9-слойная архитектура · Tailwind v4 · Vue 3 + TS        ║
  ║  Вертикали: beauty · taxi · food · hotel · realEstate · flowers ·       ║
  ║             fashion · furniture · fitness · travel · default             ║
  ╚═══════════════════════════════════════════════════════════════════════════╝
-->
<script setup lang="ts">
/**
 * TenantHelp.vue — Главная страница помощи и поддержки B2B Tenant Dashboard
 *
 * Поддержка всех 127 вертикалей CatVRF:
 *   Beauty (салоны) · Taxi (парки) · Food (рестораны, кафе)
 *   Hotels (отели) · RealEstate (агентства) · Flowers (магазины)
 *   Fashion (бутики) · Furniture (салоны мебели) · Fitness (клубы)
 *   Travel (турагентства) · Medical (клиники) · Auto (СТО) · и т.д.
 *
 * ─────────────────────────────────────────────────────────────
 *  Функционал:
 *   1. Верхняя панель с глобальным поиском по помощи
 *   2. Быстрые карточки с популярными вопросами
 *   3. Вкладки: FAQ · Видео · Документация · Чат · Обратная связь
 *   4. FAQ-аккордеон с категориями и тегами
 *   5. Видео-инструкции (сетка карточек + modal player)
 *   6. Документация (иерархия ссылок, просмотр статей)
 *   7. Чат с поддержкой (history + live)
 *   8. Форма обратной связи (тема, описание, прикрепление)
 *   9. Full-screen режим
 *  10. Vertical-specific FAQ / docs / videos
 *  11. B2B/B2C — контекстная помощь
 * ─────────────────────────────────────────────────────────────
 *  Адаптация под вертикаль:
 *   → props.vertical определяет терминологию и FAQ-контент
 *   → VERTICAL_HELP_CONFIG — маппинг конфигов
 * ─────────────────────────────────────────────────────────────
 */

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

type HelpTab = 'faq' | 'videos' | 'docs' | 'chat' | 'feedback'

type FaqCategory = 'general' | 'orders' | 'payments' | 'delivery' | 'ai' | 'b2b' | 'integrations' | 'account'

type TicketPriority = 'low' | 'medium' | 'high' | 'critical'

type TicketStatus = 'open' | 'pending' | 'resolved' | 'closed'

interface FaqItem {
  id:           number | string
  question:     string
  answer:       string
  category:     FaqCategory
  tags:         string[]
  helpful:      number
  unhelpful:    number
  updatedAt:    string
}

interface VideoTutorial {
  id:           number | string
  title:        string
  description:  string
  thumbnailUrl: string
  videoUrl:     string
  duration:     string
  category:     string
  tags:         string[]
  views:        number
  createdAt:    string
}

interface DocArticle {
  id:           number | string
  title:        string
  summary:      string
  category:     string
  icon:         string
  url:          string
  readTimeMin:  number
  updatedAt:    string
  children?:    DocArticle[]
}

interface ChatMessage {
  id:           number | string
  sender:       'user' | 'support' | 'bot'
  text:         string
  timestamp:    string
  attachments?: Array<{ name: string; url: string; size: string }>
  isRead:       boolean
}

interface SupportTicket {
  id:           number | string
  subject:      string
  description:  string
  priority:     TicketPriority
  status:       TicketStatus
  category:     string
  attachments:  File[]
  createdAt:    string
  updatedAt:    string
}

interface QuickHelpCard {
  icon:         string
  title:        string
  description:  string
  action:       string
  tab:          HelpTab
  category?:    FaqCategory
}

interface VerticalHelpConfig {
  label:           string
  icon:            string
  heroTitle:       string
  heroSubtitle:    string
  quickCards:      QuickHelpCard[]
  faqCategories:   Array<{ key: FaqCategory; label: string; icon: string }>
  docSections:     Array<{ key: string; label: string; icon: string }>
  videoCategories: string[]
  supportHint:     string
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   PROPS & EMITS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const props = withDefaults(defineProps<{
  vertical?:     string
  faqItems?:     FaqItem[]
  videos?:       VideoTutorial[]
  docs?:         DocArticle[]
  chatHistory?:  ChatMessage[]
  tickets?:      SupportTicket[]
  unreadChat?:   number
  loading?:      boolean
}>(), {
  vertical:    'default',
  faqItems:    () => [],
  videos:      () => [],
  docs:        () => [],
  chatHistory: () => [],
  tickets:     () => [],
  unreadChat:  0,
  loading:     false,
})

const emit = defineEmits<{
  'search':           [query: string]
  'faq-vote':         [faqId: number | string, helpful: boolean]
  'play-video':       [videoId: number | string]
  'open-doc':         [docId: number | string]
  'send-message':     [text: string, attachments: File[]]
  'submit-ticket':    [ticket: Omit<SupportTicket, 'id' | 'createdAt' | 'updatedAt' | 'status'>]
  'tab-change':       [tab: HelpTab]
  'faq-category':     [category: FaqCategory | '']
  'close-ticket':     [ticketId: number | string]
  'reopen-ticket':    [ticketId: number | string]
  'load-more-chat':   []
  'typing':           []
}>()

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   STORES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const auth          = useAuth()
const business      = useTenant()
const notifications = useNotifications()

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   VERTICAL HELP CONFIG
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const BASE_FAQ_CATEGORIES: VerticalHelpConfig['faqCategories'] = [
  { key: 'general',      label: 'Общие',        icon: '📋' },
  { key: 'orders',       label: 'Заказы',       icon: '🛒' },
  { key: 'payments',     label: 'Оплата',       icon: '💳' },
  { key: 'delivery',     label: 'Доставка',     icon: '🚚' },
  { key: 'ai',           label: 'AI-конструктор', icon: '🤖' },
  { key: 'b2b',          label: 'B2B',          icon: '🏢' },
  { key: 'integrations', label: 'Интеграции',   icon: '🔗' },
  { key: 'account',      label: 'Аккаунт',      icon: '👤' },
]

const BASE_DOC_SECTIONS: VerticalHelpConfig['docSections'] = [
  { key: 'getting-started', label: 'Начало работы',   icon: '🚀' },
  { key: 'dashboard',       label: 'Дашборд',         icon: '📊' },
  { key: 'orders',          label: 'Заказы',           icon: '🛒' },
  { key: 'payments',        label: 'Финансы',          icon: '💳' },
  { key: 'marketing',       label: 'Маркетинг',       icon: '📢' },
  { key: 'integrations',    label: 'Интеграции',       icon: '🔗' },
  { key: 'api',             label: 'API',              icon: '⚡' },
  { key: 'security',        label: 'Безопасность',     icon: '🛡️' },
]

const VERTICAL_HELP_CONFIG: Record<string, VerticalHelpConfig> = {

  // ── BEAUTY ──────────────────────────────────
  beauty: {
    label: 'Помощь — Салон красоты', icon: '💄',
    heroTitle: 'Центр помощи — Салон красоты',
    heroSubtitle: 'Ответы на вопросы по работе с платформой CatVRF для бьюти-бизнеса',
    quickCards: [
      { icon: '📅', title: 'Как управлять записями?',       description: 'Настройка онлайн-записи и расписаний мастеров',   action: 'Читать', tab: 'faq', category: 'orders' },
      { icon: '💄', title: 'AI-конструктор образа',          description: 'Настройка AR-примерки и AI-анализа внешности',    action: 'Смотреть', tab: 'videos' },
      { icon: '💳', title: 'Выплаты мастерам',               description: 'Как настроить зарплаты и процент мастера',        action: 'Читать', tab: 'docs' },
      { icon: '🔗', title: 'Интеграция с CRM',               description: 'Подключение iiko, R-Keeper, кассы ОФД',          action: 'Читать', tab: 'faq', category: 'integrations' },
      { icon: '📊', title: 'Аналитика салона',                description: 'Отчёты по загрузке мастеров и выручке',          action: 'Читать', tab: 'docs' },
      { icon: '🆘', title: 'Техническая поддержка',           description: 'Связаться с командой поддержки CatVRF',          action: 'Чат', tab: 'chat' },
    ],
    faqCategories: BASE_FAQ_CATEGORIES,
    docSections: [
      ...BASE_DOC_SECTIONS,
      { key: 'appointments', label: 'Записи и слоты', icon: '📅' },
      { key: 'masters',      label: 'Мастера',        icon: '💇' },
    ],
    videoCategories: ['Начало работы', 'Записи', 'AI-конструктор', 'Маркетинг', 'Финансы'],
    supportHint: 'Опишите проблему с салоном или работой мастеров',
  },

  // ── TAXI ────────────────────────────────────
  taxi: {
    label: 'Помощь — Таксопарк', icon: '🚕',
    heroTitle: 'Центр помощи — Таксопарк',
    heroSubtitle: 'Управление парком автомобилей и водителями на платформе CatVRF',
    quickCards: [
      { icon: '🚗', title: 'Как добавить водителя?',         description: 'Регистрация, документы и допуск к заказам',      action: 'Читать', tab: 'faq', category: 'general' },
      { icon: '📍', title: 'Геотрекинг в реальном времени',  description: 'Настройка GPS-мониторинга и маршрутов',          action: 'Смотреть', tab: 'videos' },
      { icon: '💰', title: 'Расчёт стоимости поездок',       description: 'Тарифы, коэффициент повышения, комиссии',        action: 'Читать', tab: 'docs' },
      { icon: '📊', title: 'Аналитика по водителям',          description: 'KPI, рейтинги и эффективность',                 action: 'Читать', tab: 'docs' },
      { icon: '🗺️', title: 'Оптимизация маршрутов',          description: 'AI-маршрутизация и экономия топлива',            action: 'Смотреть', tab: 'videos' },
      { icon: '🆘', title: 'Техническая поддержка',           description: 'Связаться с командой поддержки CatVRF',          action: 'Чат', tab: 'chat' },
    ],
    faqCategories: BASE_FAQ_CATEGORIES,
    docSections: [
      ...BASE_DOC_SECTIONS,
      { key: 'drivers',   label: 'Водители',         icon: '🚗' },
      { key: 'geotrack',  label: 'Геотрекинг',       icon: '📍' },
    ],
    videoCategories: ['Начало работы', 'Водители', 'Геотрекинг', 'Тарифы', 'Финансы'],
    supportHint: 'Опишите проблему с парком или навигацией',
  },

  // ── FOOD ────────────────────────────────────
  food: {
    label: 'Помощь — Ресторан', icon: '🍽️',
    heroTitle: 'Центр помощи — Ресторан и доставка',
    heroSubtitle: 'Управление меню, заказами и доставкой еды на платформе CatVRF',
    quickCards: [
      { icon: '📝', title: 'Как настроить меню?',            description: 'Добавление блюд, КБЖУ, аллергены и стоп-лист',  action: 'Читать', tab: 'faq', category: 'orders' },
      { icon: '🤖', title: 'AI-конструктор меню',            description: 'Автоматическая генерация рецептов и КБЖУ',       action: 'Смотреть', tab: 'videos' },
      { icon: '🚴', title: 'Настройка доставки',             description: 'Зоны, курьеры, трекинг заказов',                 action: 'Читать', tab: 'docs' },
      { icon: '💳', title: 'Расчёты с курьерами',            description: 'Выплаты, комиссии и бонусы',                    action: 'Читать', tab: 'faq', category: 'payments' },
      { icon: '🔗', title: 'Интеграция iiko / R-Keeper',     description: 'Синхронизация меню и заказов',                   action: 'Читать', tab: 'docs' },
      { icon: '🆘', title: 'Техническая поддержка',           description: 'Связаться с командой поддержки CatVRF',          action: 'Чат', tab: 'chat' },
    ],
    faqCategories: BASE_FAQ_CATEGORIES,
    docSections: [
      ...BASE_DOC_SECTIONS,
      { key: 'menu',      label: 'Меню и блюда',    icon: '🍽️' },
      { key: 'delivery',  label: 'Доставка',         icon: '🚴' },
    ],
    videoCategories: ['Начало работы', 'Меню', 'AI-конструктор', 'Доставка', 'Финансы'],
    supportHint: 'Опишите проблему с рестораном, меню или доставкой',
  },

  // ── HOTEL ───────────────────────────────────
  hotel: {
    label: 'Помощь — Отель', icon: '🏨',
    heroTitle: 'Центр помощи — Отель и гостиница',
    heroSubtitle: 'Управление номерным фондом, бронированиями и услугами',
    quickCards: [
      { icon: '🛏️', title: 'Управление номерами',           description: 'Типы номеров, тарифы, сезонность',              action: 'Читать', tab: 'faq', category: 'orders' },
      { icon: '🏗️', title: '3D-туры по номерам',             description: 'Настройка виртуальных туров и панорам',          action: 'Смотреть', tab: 'videos' },
      { icon: '📅', title: 'Система бронирования',            description: 'Реал-тайм доступность и автоматизация',         action: 'Читать', tab: 'docs' },
      { icon: '💳', title: 'Тарифы и оплата',                description: 'Сезонные цены, предоплата, штрафы',             action: 'Читать', tab: 'faq', category: 'payments' },
      { icon: '🔗', title: 'Booking / Островок / TravelLine', description: 'Синхронизация каналов продаж',                  action: 'Читать', tab: 'docs' },
      { icon: '🆘', title: 'Техническая поддержка',           description: 'Связаться с командой поддержки CatVRF',          action: 'Чат', tab: 'chat' },
    ],
    faqCategories: BASE_FAQ_CATEGORIES,
    docSections: [
      ...BASE_DOC_SECTIONS,
      { key: 'rooms',     label: 'Номерной фонд',   icon: '🛏️' },
      { key: 'bookings',  label: 'Бронирования',     icon: '📅' },
    ],
    videoCategories: ['Начало работы', 'Номера', '3D-туры', 'Бронирования', 'Финансы'],
    supportHint: 'Опишите проблему с отелем или бронированиями',
  },

  // ── REAL ESTATE ─────────────────────────────
  realEstate: {
    label: 'Помощь — Недвижимость', icon: '🏢',
    heroTitle: 'Центр помощи — Агентство недвижимости',
    heroSubtitle: 'Управление объектами, сделками и AI-дизайном на платформе CatVRF',
    quickCards: [
      { icon: '🏠', title: 'Публикация объектов',            description: 'Добавление объектов, фото, 3D-тур',             action: 'Читать', tab: 'faq', category: 'general' },
      { icon: '🤖', title: 'AI-дизайн квартиры',             description: 'Виртуальный ремонт и визуализация',             action: 'Смотреть', tab: 'videos' },
      { icon: '📊', title: 'Аналитика рынка',                description: 'Прогноз цен, спроса и конкуренции',             action: 'Читать', tab: 'docs' },
      { icon: '💰', title: 'Комиссии агентов',               description: 'Расчёт комиссий и выплаты',                    action: 'Читать', tab: 'faq', category: 'payments' },
      { icon: '🔗', title: 'ЦИАН / Авито / Домклик',         description: 'Автоматическая выгрузка объявлений',            action: 'Читать', tab: 'docs' },
      { icon: '🆘', title: 'Техническая поддержка',           description: 'Связаться с командой поддержки CatVRF',          action: 'Чат', tab: 'chat' },
    ],
    faqCategories: BASE_FAQ_CATEGORIES,
    docSections: [
      ...BASE_DOC_SECTIONS,
      { key: 'listings',  label: 'Объекты',          icon: '🏠' },
      { key: 'deals',     label: 'Сделки',           icon: '🤝' },
    ],
    videoCategories: ['Начало работы', 'Объекты', 'AI-дизайн', 'Сделки', 'Финансы'],
    supportHint: 'Опишите проблему с объектами или сделками',
  },

  // ── FLOWERS ─────────────────────────────────
  flowers: {
    label: 'Помощь — Цветы', icon: '💐',
    heroTitle: 'Центр помощи — Цветочный магазин',
    heroSubtitle: 'Управление букетами, доставкой и AI-подбором на CatVRF',
    quickCards: [
      { icon: '💐', title: 'Каталог букетов',                description: 'Добавление букетов и композиций',               action: 'Читать', tab: 'faq', category: 'orders' },
      { icon: '🎨', title: 'AI-конструктор букетов',         description: 'Автоматический подбор цветов и упаковки',        action: 'Смотреть', tab: 'videos' },
      { icon: '🚴', title: 'Экспресс-доставка',              description: 'Доставка за 1-2 часа, анонимная доставка',       action: 'Читать', tab: 'docs' },
      { icon: '📸', title: 'Фото-отчёт клиенту',            description: 'Как настроить фото букета перед доставкой',      action: 'Читать', tab: 'faq', category: 'delivery' },
      { icon: '📊', title: 'Аналитика продаж',               description: 'Популярные букеты, сезонность, прибыль',        action: 'Читать', tab: 'docs' },
      { icon: '🆘', title: 'Техническая поддержка',           description: 'Связаться с командой поддержки CatVRF',          action: 'Чат', tab: 'chat' },
    ],
    faqCategories: BASE_FAQ_CATEGORIES,
    docSections: [
      ...BASE_DOC_SECTIONS,
      { key: 'bouquets',  label: 'Букеты',           icon: '💐' },
      { key: 'freshness', label: 'Свежесть',         icon: '🌿' },
    ],
    videoCategories: ['Начало работы', 'Каталог', 'AI-конструктор', 'Доставка', 'Финансы'],
    supportHint: 'Опишите проблему с каталогом или доставкой цветов',
  },

  // ── FASHION ─────────────────────────────────
  fashion: {
    label: 'Помощь — Мода', icon: '👗',
    heroTitle: 'Центр помощи — Бутик и мода',
    heroSubtitle: 'Управление коллекциями, AR-примеркой и стилистикой на CatVRF',
    quickCards: [
      { icon: '👗', title: 'Управление коллекциями',         description: 'Добавление товаров, размеры, цвета, бренды',    action: 'Читать', tab: 'faq', category: 'orders' },
      { icon: '🤖', title: 'AI-подбор стиля',                description: 'Цветотип, капсульный гардероб, AR-примерка',     action: 'Смотреть', tab: 'videos' },
      { icon: '📦', title: 'Возвраты и обмены',              description: 'Настройка политики возвратов',                   action: 'Читать', tab: 'docs' },
      { icon: '💳', title: 'Оптовые закупки B2B',            description: 'MOQ, скидки, условия для B2B-клиентов',          action: 'Читать', tab: 'faq', category: 'b2b' },
      { icon: '🔗', title: 'Маркетплейсы',                   description: 'Выгрузка на Wildberries, Ozon, Lamoda',          action: 'Читать', tab: 'docs' },
      { icon: '🆘', title: 'Техническая поддержка',           description: 'Связаться с командой поддержки CatVRF',          action: 'Чат', tab: 'chat' },
    ],
    faqCategories: BASE_FAQ_CATEGORIES,
    docSections: [
      ...BASE_DOC_SECTIONS,
      { key: 'collections', label: 'Коллекции',       icon: '👗' },
      { key: 'ar-fitting',  label: 'AR-примерка',     icon: '📱' },
    ],
    videoCategories: ['Начало работы', 'Коллекции', 'AI-стилист', 'AR-примерка', 'Финансы'],
    supportHint: 'Опишите проблему с каталогом или AR-примеркой',
  },

  // ── FURNITURE ───────────────────────────────
  furniture: {
    label: 'Помощь — Мебель', icon: '🛋️',
    heroTitle: 'Центр помощи — Мебельный салон',
    heroSubtitle: 'Управление каталогом мебели, 3D-дизайном и доставкой на CatVRF',
    quickCards: [
      { icon: '🛋️', title: 'Каталог мебели',                description: 'Добавление мебели, материалов, размеров',        action: 'Читать', tab: 'faq', category: 'orders' },
      { icon: '🤖', title: 'AI-дизайн интерьера',           description: '3D-визуализация, расчёт стоимости ремонта',      action: 'Смотреть', tab: 'videos' },
      { icon: '🚚', title: 'Крупногабаритная доставка',      description: 'Доставка, сборка и монтаж мебели',              action: 'Читать', tab: 'docs' },
      { icon: '📐', title: 'Замеры и выезд',                 description: 'Как организовать выезд замерщика',              action: 'Читать', tab: 'faq', category: 'delivery' },
      { icon: '🔗', title: '3D-визуализация',                description: 'Интеграция с Blender и 3D-сервисами',           action: 'Читать', tab: 'docs' },
      { icon: '🆘', title: 'Техническая поддержка',           description: 'Связаться с командой поддержки CatVRF',          action: 'Чат', tab: 'chat' },
    ],
    faqCategories: BASE_FAQ_CATEGORIES,
    docSections: [
      ...BASE_DOC_SECTIONS,
      { key: 'furniture-catalog', label: 'Каталог',    icon: '🛋️' },
      { key: '3d-design',         label: '3D-дизайн',  icon: '🖼️' },
    ],
    videoCategories: ['Начало работы', 'Каталог', 'AI-дизайн', 'Доставка и сборка', 'Финансы'],
    supportHint: 'Опишите проблему с каталогом или 3D-визуализацией',
  },

  // ── FITNESS ─────────────────────────────────
  fitness: {
    label: 'Помощь — Фитнес', icon: '💪',
    heroTitle: 'Центр помощи — Фитнес-клуб',
    heroSubtitle: 'Управление абонементами, тренерами и AI-тренировками на CatVRF',
    quickCards: [
      { icon: '🎫', title: 'Абонементы и тарифы',            description: 'Настройка абонементов, пробных и заморозок',    action: 'Читать', tab: 'faq', category: 'orders' },
      { icon: '🤖', title: 'AI-виртуальный тренер',          description: 'Персонализированные планы тренировок',          action: 'Смотреть', tab: 'videos' },
      { icon: '🏋️', title: 'Расписание тренировок',          description: 'Групповые и персональные занятия',              action: 'Читать', tab: 'docs' },
      { icon: '🥗', title: 'Планы питания',                  description: 'AI-генерация рационов и КБЖУ',                 action: 'Читать', tab: 'faq', category: 'ai' },
      { icon: '🔗', title: 'Интеграция СКУД',                description: 'Подключение системы контроля доступа',          action: 'Читать', tab: 'docs' },
      { icon: '🆘', title: 'Техническая поддержка',           description: 'Связаться с командой поддержки CatVRF',          action: 'Чат', tab: 'chat' },
    ],
    faqCategories: BASE_FAQ_CATEGORIES,
    docSections: [
      ...BASE_DOC_SECTIONS,
      { key: 'memberships', label: 'Абонементы',     icon: '🎫' },
      { key: 'trainers',    label: 'Тренеры',        icon: '🏋️' },
    ],
    videoCategories: ['Начало работы', 'Абонементы', 'AI-тренер', 'Расписание', 'Финансы'],
    supportHint: 'Опишите проблему с клубом или абонементами',
  },

  // ── TRAVEL ──────────────────────────────────
  travel: {
    label: 'Помощь — Туризм', icon: '✈️',
    heroTitle: 'Центр помощи — Турагентство',
    heroSubtitle: 'Управление турами, бронированиями и AI-маршрутами на CatVRF',
    quickCards: [
      { icon: '🗺️', title: 'Создание туров',                description: 'Маршруты, отели, экскурсии и билеты',           action: 'Читать', tab: 'faq', category: 'orders' },
      { icon: '🤖', title: 'AI-планировщик путешествий',     description: 'Автоматическая генерация маршрутов',            action: 'Смотреть', tab: 'videos' },
      { icon: '✈️', title: 'Бронирование билетов',           description: 'Авиа, ж/д, автобус — интеграция GDS',           action: 'Читать', tab: 'docs' },
      { icon: '🏨', title: 'Бронирование отелей',            description: 'Подключение систем бронирования',               action: 'Читать', tab: 'faq', category: 'integrations' },
      { icon: '📊', title: 'Аналитика продаж',               description: 'Направления, сезонность, маржинальность',      action: 'Читать', tab: 'docs' },
      { icon: '🆘', title: 'Техническая поддержка',           description: 'Связаться с командой поддержки CatVRF',          action: 'Чат', tab: 'chat' },
    ],
    faqCategories: BASE_FAQ_CATEGORIES,
    docSections: [
      ...BASE_DOC_SECTIONS,
      { key: 'tours',     label: 'Туры',             icon: '🗺️' },
      { key: 'transfers', label: 'Трансферы',        icon: '🚐' },
    ],
    videoCategories: ['Начало работы', 'Туры', 'AI-маршруты', 'Бронирование', 'Финансы'],
    supportHint: 'Опишите проблему с турами или бронированиями',
  },

  // ── DEFAULT ─────────────────────────────────
  default: {
    label: 'Помощь', icon: '❓',
    heroTitle: 'Центр помощи CatVRF',
    heroSubtitle: 'Ответы на вопросы по работе с платформой для бизнеса',
    quickCards: [
      { icon: '🚀', title: 'Начало работы',                  description: 'Первые шаги на платформе CatVRF',               action: 'Читать', tab: 'faq', category: 'general' },
      { icon: '🤖', title: 'AI-конструктор',                 description: 'Настройка и использование AI-инструментов',     action: 'Смотреть', tab: 'videos' },
      { icon: '💳', title: 'Оплата и финансы',               description: 'Wallet, выплаты, комиссии, бонусы',             action: 'Читать', tab: 'docs' },
      { icon: '🔗', title: 'Интеграции и API',               description: 'Подключение внешних сервисов и B2B API',        action: 'Читать', tab: 'faq', category: 'integrations' },
      { icon: '🛡️', title: 'Безопасность',                   description: '2FA, фрод-защита, IP-фильтрация',              action: 'Читать', tab: 'docs' },
      { icon: '🆘', title: 'Техническая поддержка',           description: 'Связаться с командой поддержки CatVRF',          action: 'Чат', tab: 'chat' },
    ],
    faqCategories: BASE_FAQ_CATEGORIES,
    docSections: BASE_DOC_SECTIONS,
    videoCategories: ['Начало работы', 'AI-инструменты', 'Финансы', 'Маркетинг', 'B2B API'],
    supportHint: 'Опишите вашу проблему или вопрос',
  },
}

const vc = computed<VerticalHelpConfig>(() =>
  VERTICAL_HELP_CONFIG[props.vertical] ?? VERTICAL_HELP_CONFIG.default
)

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   STATUS / PRIORITY MAPS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const TICKET_STATUS_MAP: Record<TicketStatus, { label: string; dot: string; variant: string }> = {
  open:     { label: 'Открыт',    dot: 'bg-sky-400',     variant: 'info' },
  pending:  { label: 'В работе',  dot: 'bg-amber-400',   variant: 'warning' },
  resolved: { label: 'Решён',     dot: 'bg-emerald-400', variant: 'success' },
  closed:   { label: 'Закрыт',    dot: 'bg-zinc-500',    variant: 'neutral' },
}

const TICKET_PRIORITY_MAP: Record<TicketPriority, { label: string; dot: string; variant: string }> = {
  low:      { label: 'Низкий',    dot: 'bg-zinc-400',    variant: 'neutral' },
  medium:   { label: 'Средний',   dot: 'bg-sky-400',     variant: 'info' },
  high:     { label: 'Высокий',   dot: 'bg-amber-400',   variant: 'warning' },
  critical: { label: 'Критичный', dot: 'bg-rose-400',    variant: 'danger' },
}

const FAQ_CATEGORY_MAP: Record<FaqCategory, { label: string; icon: string }> = {
  general:      { label: 'Общие',         icon: '📋' },
  orders:       { label: 'Заказы',        icon: '🛒' },
  payments:     { label: 'Оплата',        icon: '💳' },
  delivery:     { label: 'Доставка',      icon: '🚚' },
  ai:           { label: 'AI',            icon: '🤖' },
  b2b:          { label: 'B2B',           icon: '🏢' },
  integrations: { label: 'Интеграции',    icon: '🔗' },
  account:      { label: 'Аккаунт',       icon: '👤' },
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   TABS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const HELP_TABS: Array<{ key: HelpTab; label: string; icon: string }> = [
  { key: 'faq',      label: 'FAQ',            icon: '❓' },
  { key: 'videos',   label: 'Видео',          icon: '🎬' },
  { key: 'docs',     label: 'Документация',   icon: '📚' },
  { key: 'chat',     label: 'Чат',            icon: '💬' },
  { key: 'feedback', label: 'Обратная связь',  icon: '📩' },
]

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   STATE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const rootEl             = ref<HTMLElement | null>(null)
const chatContainerEl    = ref<HTMLElement | null>(null)
const isFullscreen       = ref(false)
const activeTab          = ref<HelpTab>('faq')
const searchQuery        = ref('')
const searchFocused      = ref(false)

// FAQ
const faqCategory        = ref<FaqCategory | ''>('')
const expandedFaqIds     = ref<Set<number | string>>(new Set())
const faqSearchLocal     = ref('')

// Videos
const videoFilter        = ref('')
const showVideoModal     = ref(false)
const activeVideo        = ref<VideoTutorial | null>(null)

// Docs
const docFilter          = ref('')
const expandedDocIds     = ref<Set<number | string>>(new Set())

// Chat
const chatInput          = ref('')
const chatAttachments    = ref<File[]>([])
const chatScrollLocked   = ref(true)
const isTyping           = ref(false)

// Feedback
const feedbackForm       = reactive({
  subject:      '',
  description:  '',
  priority:     'medium' as TicketPriority,
  category:     'general',
  attachments:  [] as File[],
})
const feedbackSubmitting = ref(false)
const feedbackSuccess    = ref(false)

// Tickets sidebar
const showTickets        = ref(false)

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   COMPUTED — FAQ
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const filteredFaq = computed<FaqItem[]>(() => {
  let list = [...props.faqItems]

  if (faqCategory.value) {
    list = list.filter(f => f.category === faqCategory.value)
  }

  const q = (faqSearchLocal.value || searchQuery.value).toLowerCase().trim()
  if (q) {
    list = list.filter(f =>
      f.question.toLowerCase().includes(q) ||
      f.answer.toLowerCase().includes(q) ||
      f.tags.some(t => t.toLowerCase().includes(q))
    )
  }

  return list
})

const faqCategoryStats = computed(() => {
  const map: Record<string, number> = {}
  for (const item of props.faqItems) {
    map[item.category] = (map[item.category] ?? 0) + 1
  }
  return map
})

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   COMPUTED — VIDEOS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const filteredVideos = computed<VideoTutorial[]>(() => {
  let list = [...props.videos]

  if (videoFilter.value) {
    list = list.filter(v => v.category === videoFilter.value)
  }

  const q = searchQuery.value.toLowerCase().trim()
  if (q) {
    list = list.filter(v =>
      v.title.toLowerCase().includes(q) ||
      v.description.toLowerCase().includes(q) ||
      v.tags.some(t => t.toLowerCase().includes(q))
    )
  }

  return list
})

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   COMPUTED — DOCS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const filteredDocs = computed<DocArticle[]>(() => {
  let list = [...props.docs]

  if (docFilter.value) {
    list = list.filter(d => d.category === docFilter.value)
  }

  const q = searchQuery.value.toLowerCase().trim()
  if (q) {
    list = list.filter(d =>
      d.title.toLowerCase().includes(q) ||
      d.summary.toLowerCase().includes(q)
    )
  }

  return list
})

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   COMPUTED — CHAT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const sortedChat = computed<ChatMessage[]>(() =>
  [...props.chatHistory].sort((a, b) =>
    new Date(a.timestamp).getTime() - new Date(b.timestamp).getTime()
  )
)

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   COMPUTED — TICKETS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const openTickets = computed(() => props.tickets.filter(t => t.status === 'open' || t.status === 'pending'))
const resolvedTickets = computed(() => props.tickets.filter(t => t.status === 'resolved' || t.status === 'closed'))

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ACTIONS — FAQ
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function toggleFaqItem(id: number | string) {
  if (expandedFaqIds.value.has(id)) {
    expandedFaqIds.value.delete(id)
  } else {
    expandedFaqIds.value.add(id)
  }
}

function voteFaq(id: number | string, helpful: boolean) {
  emit('faq-vote', id, helpful)
}

function selectFaqCategory(cat: FaqCategory | '') {
  faqCategory.value = cat
  emit('faq-category', cat)
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ACTIONS — VIDEOS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function openVideo(video: VideoTutorial) {
  activeVideo.value = video
  showVideoModal.value = true
  emit('play-video', video.id)
}

function closeVideo() {
  showVideoModal.value = false
  activeVideo.value = null
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ACTIONS — DOCS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function toggleDocSection(id: number | string) {
  if (expandedDocIds.value.has(id)) {
    expandedDocIds.value.delete(id)
  } else {
    expandedDocIds.value.add(id)
  }
}

function openDoc(doc: DocArticle) {
  emit('open-doc', doc.id)
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ACTIONS — CHAT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function sendChatMessage() {
  const text = chatInput.value.trim()
  if (!text && chatAttachments.value.length === 0) return

  emit('send-message', text, [...chatAttachments.value])
  chatInput.value = ''
  chatAttachments.value = []

  nextTick(() => scrollChatToBottom())
}

function handleChatKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    sendChatMessage()
  }
}

function onChatTyping() {
  emit('typing')
}

function scrollChatToBottom() {
  if (chatContainerEl.value && chatScrollLocked.value) {
    chatContainerEl.value.scrollTop = chatContainerEl.value.scrollHeight
  }
}

function handleChatScroll() {
  if (!chatContainerEl.value) return
  const el = chatContainerEl.value
  const atBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 60
  chatScrollLocked.value = atBottom

  if (el.scrollTop < 40) {
    emit('load-more-chat')
  }
}

function addChatAttachment(e: Event) {
  const input = e.target as HTMLInputElement
  if (input.files) {
    chatAttachments.value.push(...Array.from(input.files))
    input.value = ''
  }
}

function removeChatAttachment(idx: number) {
  chatAttachments.value.splice(idx, 1)
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ACTIONS — FEEDBACK
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function submitFeedback() {
  if (!feedbackForm.subject.trim() || !feedbackForm.description.trim()) return
  feedbackSubmitting.value = true

  emit('submit-ticket', {
    subject:     feedbackForm.subject.trim(),
    description: feedbackForm.description.trim(),
    priority:    feedbackForm.priority,
    category:    feedbackForm.category,
    attachments: [...feedbackForm.attachments],
  })

  setTimeout(() => {
    feedbackSubmitting.value = false
    feedbackSuccess.value = true
    feedbackForm.subject = ''
    feedbackForm.description = ''
    feedbackForm.priority = 'medium'
    feedbackForm.category = 'general'
    feedbackForm.attachments = []
    setTimeout(() => { feedbackSuccess.value = false }, 3000)
  }, 800)
}

function addFeedbackAttachment(e: Event) {
  const input = e.target as HTMLInputElement
  if (input.files) {
    feedbackForm.attachments.push(...Array.from(input.files))
    input.value = ''
  }
}

function removeFeedbackAttachment(idx: number) {
  feedbackForm.attachments.splice(idx, 1)
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   TAB SWITCH
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function switchTab(tab: HelpTab) {
  activeTab.value = tab
  emit('tab-change', tab)

  if (tab === 'chat') {
    nextTick(() => scrollChatToBottom())
  }
}

function handleQuickCard(card: QuickHelpCard) {
  switchTab(card.tab)
  if (card.category) {
    faqCategory.value = card.category
  }
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   SEARCH
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

let searchDebounce: ReturnType<typeof setTimeout> | null = null

function handleSearch(val: string) {
  searchQuery.value = val
  if (searchDebounce) clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => emit('search', val), 350)
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
   RIPPLE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function ripple(e: MouseEvent) {
  const target = e.currentTarget as HTMLElement
  const rect = target.getBoundingClientRect()
  const diameter = Math.max(rect.width, rect.height) * 2
  const el = document.createElement('span')
  el.className = 'absolute rounded-full bg-white/10 pointer-events-none animate-[ripple-hp_0.6s_ease-out]'
  el.style.cssText = `inline-size:${diameter}px;block-size:${diameter}px;inset-inline-start:${e.clientX - rect.left - diameter / 2}px;inset-block-start:${e.clientY - rect.top - diameter / 2}px;`
  target.appendChild(el)
  setTimeout(() => el.remove(), 650)
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   FORMATTERS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function fmtDate(iso: string): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', year: 'numeric' })
}

function fmtDatetime(iso: string): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

function relativeTime(iso: string): string {
  if (!iso) return '—'
  const diffMin = Math.floor((Date.now() - new Date(iso).getTime()) / 60_000)
  if (diffMin < 1) return 'только что'
  if (diffMin < 60) return `${diffMin} мин назад`
  if (diffMin < 1440) return `${Math.floor(diffMin / 60)} ч назад`
  const diffDays = Math.floor(diffMin / 1440)
  if (diffDays < 7) return `${diffDays} дн. назад`
  return fmtDate(iso)
}

function fmtNum(n: number): string {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K'
  return String(n)
}

function fmtFileSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' Б'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' КБ'
  return (bytes / (1024 * 1024)).toFixed(1) + ' МБ'
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   LIFECYCLE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

onMounted(() => {
  document.addEventListener('fullscreenchange', onFullscreenChange)
  nextTick(() => {
    if (activeTab.value === 'chat') scrollChatToBottom()
  })
})

onBeforeUnmount(() => {
  document.removeEventListener('fullscreenchange', onFullscreenChange)
  if (searchDebounce) clearTimeout(searchDebounce)
})

// auto-scroll on new messages
watch(() => props.chatHistory.length, () => {
  nextTick(() => scrollChatToBottom())
})
</script>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     TEMPLATE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<template>
  <div
    ref="rootEl"
    :class="[
      'flex flex-col gap-4',
      isFullscreen ? 'fixed inset-0 z-90 bg-(--t-bg) p-4 overflow-auto' : '',
    ]"
  >
    <!-- ═══════════════════════════════════════════════
         1. HERO / SEARCH HEADER
    ═══════════════════════════════════════════════ -->
    <div class="relative rounded-2xl border border-(--t-border) bg-(--t-surface)/60 backdrop-blur-xl
                overflow-hidden">
      <!-- glow accent -->
      <div class="absolute inset-0 opacity-[0.04] pointer-events-none"
           :style="{ background: `radial-gradient(ellipse at 50% 0%, var(--t-primary), transparent 70%)` }" />

      <div class="relative z-10 flex flex-col items-center gap-4 px-4 py-8 sm:py-10 text-center">
        <span class="text-4xl">{{ vc.icon }}</span>
        <h1 class="text-xl sm:text-2xl font-bold text-(--t-text)">{{ vc.heroTitle }}</h1>
        <p class="text-sm text-(--t-text-3) max-w-lg">{{ vc.heroSubtitle }}</p>

        <!-- Search Bar -->
        <div class="relative inline-size-full max-w-xl mt-2">
          <div :class="[
            'flex items-center gap-2 rounded-xl border px-4 py-2.5 transition-all duration-200',
            searchFocused
              ? 'border-(--t-primary)/60 bg-(--t-surface) shadow-lg shadow-(--t-primary)/5'
              : 'border-(--t-border) bg-(--t-surface)/80',
          ]">
            <svg class="w-4 h-4 shrink-0 text-(--t-text-3)" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.35-4.35" stroke-linecap="round" />
            </svg>
            <input
              type="text"
              :value="searchQuery"
              placeholder="Поиск по FAQ, видео, документации…"
              class="flex-1 bg-transparent text-sm text-(--t-text) placeholder:text-(--t-text-3)
                     outline-none"
              @input="handleSearch(($event.target as HTMLInputElement).value)"
              @focus="searchFocused = true"
              @blur="searchFocused = false"
            />
            <button
              v-if="searchQuery"
              class="shrink-0 text-(--t-text-3) hover:text-(--t-text) transition-colors"
              @click="searchQuery = ''; emit('search', '')"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" d="M18 6 6 18M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- mode + fullscreen -->
        <div class="flex items-center gap-2 text-xs text-(--t-text-3)">
          <span>{{ auth.tenantName }} · {{ auth.isB2BMode ? 'B2B' : 'B2C' }}</span>
          <span class="text-(--t-border)">|</span>
          <button
            class="hover:text-(--t-text) transition-colors"
            @click="toggleFullscreen"
          >
            {{ isFullscreen ? '⛶ Свернуть' : '⛶ На весь экран' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════
         2. QUICK CARDS
    ═══════════════════════════════════════════════ -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
      <button
        v-for="(card, ci) in vc.quickCards"
        :key="ci"
        class="relative overflow-hidden group text-start rounded-xl border border-(--t-border)
               bg-(--t-surface)/60 backdrop-blur-md px-4 py-3.5
               hover:border-(--t-primary)/40 hover:bg-(--t-card-hover)
               transition-all duration-200 active:scale-[0.98]"
        @click="handleQuickCard(card)"
        @mousedown="ripple"
      >
        <div class="flex items-start gap-3">
          <span class="text-2xl shrink-0 mt-0.5">{{ card.icon }}</span>
          <div class="flex-1 min-w-0">
            <h3 class="text-sm font-semibold text-(--t-text) group-hover:text-(--t-primary)
                       transition-colors truncate">
              {{ card.title }}
            </h3>
            <p class="text-xs text-(--t-text-3) mt-0.5 line-clamp-2">
              {{ card.description }}
            </p>
          </div>
          <span class="text-xs text-(--t-primary) opacity-0 group-hover:opacity-100
                       transition-opacity shrink-0 mt-1">
            {{ card.action }} →
          </span>
        </div>
      </button>
    </div>

    <!-- ═══════════════════════════════════════════════
         3. TABS
    ═══════════════════════════════════════════════ -->
    <div class="flex items-center gap-1 overflow-x-auto pb-1 -mx-1 px-1">
      <button
        v-for="tab in HELP_TABS"
        :key="tab.key"
        :class="[
          'relative shrink-0 flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-medium',
          'transition-all duration-200 overflow-hidden',
          activeTab === tab.key
            ? 'bg-(--t-primary)/12 text-(--t-primary) border border-(--t-primary)/30'
            : 'text-(--t-text-2) hover:text-(--t-text) hover:bg-(--t-surface)',
        ]"
        @click="switchTab(tab.key)"
        @mousedown="ripple"
      >
        <span>{{ tab.icon }}</span>
        <span>{{ tab.label }}</span>
        <span
          v-if="tab.key === 'chat' && props.unreadChat > 0"
          class="ml-1 inline-flex items-center justify-center min-w-4 h-4 px-1 rounded-full
                 bg-rose-500 text-white text-[10px] font-bold leading-none"
        >
          {{ props.unreadChat > 99 ? '99+' : props.unreadChat }}
        </span>
      </button>

      <!-- Spacer -->
      <div class="flex-1" />

      <!-- Tickets toggle -->
      <button
        class="shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium
               text-(--t-text-2) hover:text-(--t-text) hover:bg-(--t-surface)
               transition-all duration-200 border border-transparent hover:border-(--t-border)"
        @click="showTickets = !showTickets"
      >
        <span>🎫</span>
        <span>Мои обращения</span>
        <span
          v-if="openTickets.length"
          class="ml-0.5 inline-flex items-center justify-center min-w-4 h-4 px-1 rounded-full
                 bg-amber-500/20 text-amber-400 text-[10px] font-bold leading-none"
        >
          {{ openTickets.length }}
        </span>
      </button>
    </div>

    <!-- ═══════════════════════════════════════════════
         4. TAB CONTENT
    ═══════════════════════════════════════════════ -->

    <!-- ──────── FAQ ──────── -->
    <div v-if="activeTab === 'faq'" class="flex flex-col gap-4">
      <!-- FAQ Category Pills -->
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 -mx-1 px-1">
        <button
          :class="[
            'shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200',
            faqCategory === ''
              ? 'bg-(--t-primary)/12 text-(--t-primary) border border-(--t-primary)/30'
              : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-surface) border border-transparent',
          ]"
          @click="selectFaqCategory('')"
        >
          Все ({{ props.faqItems.length }})
        </button>
        <button
          v-for="cat in vc.faqCategories"
          :key="cat.key"
          :class="[
            'shrink-0 flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200',
            faqCategory === cat.key
              ? 'bg-(--t-primary)/12 text-(--t-primary) border border-(--t-primary)/30'
              : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-surface) border border-transparent',
          ]"
          @click="selectFaqCategory(cat.key)"
        >
          <span>{{ cat.icon }}</span>
          <span>{{ cat.label }}</span>
          <span v-if="faqCategoryStats[cat.key]" class="opacity-60">({{ faqCategoryStats[cat.key] }})</span>
        </button>
      </div>

      <!-- FAQ local search -->
      <div class="relative max-w-sm">
        <input
          v-model="faqSearchLocal"
          type="text"
          placeholder="Поиск по вопросам…"
          class="inline-size-full rounded-lg border border-(--t-border) bg-(--t-surface)/60
                 px-3 py-2 text-xs text-(--t-text) placeholder:text-(--t-text-3) outline-none
                 focus:border-(--t-primary)/50 transition-colors"
        />
      </div>

      <!-- FAQ Accordion -->
      <div v-if="filteredFaq.length > 0" class="flex flex-col gap-2">
        <div
          v-for="item in filteredFaq"
          :key="item.id"
          class="rounded-xl border border-(--t-border) bg-(--t-surface)/60 backdrop-blur-sm
                 overflow-hidden transition-all duration-200 hover:border-(--t-primary)/20"
        >
          <!-- Question -->
          <button
            class="relative inline-size-full flex items-center gap-3 px-4 py-3 text-start overflow-hidden
                   group transition-colors hover:bg-(--t-card-hover)"
            @click="toggleFaqItem(item.id)"
            @mousedown="ripple"
          >
            <span class="shrink-0 text-base">
              {{ FAQ_CATEGORY_MAP[item.category]?.icon ?? '❓' }}
            </span>
            <span class="flex-1 text-sm font-medium text-(--t-text) group-hover:text-(--t-primary)
                         transition-colors">
              {{ item.question }}
            </span>
            <svg
              :class="[
                'w-4 h-4 shrink-0 text-(--t-text-3) transition-transform duration-200',
                expandedFaqIds.has(item.id) ? 'rotate-180' : '',
              ]"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
            </svg>
          </button>

          <!-- Answer (collapsible) -->
          <Transition name="faq-hp">
            <div v-if="expandedFaqIds.has(item.id)">
              <div class="px-4 pb-3 border-t border-(--t-border)/50">
                <p class="text-xs text-(--t-text-2) leading-relaxed mt-3 whitespace-pre-line">
                  {{ item.answer }}
                </p>

                <!-- Tags -->
                <div v-if="item.tags.length" class="flex flex-wrap gap-1 mt-3">
                  <span
                    v-for="tag in item.tags"
                    :key="tag"
                    class="px-2 py-0.5 rounded-full text-[10px] font-medium
                           bg-(--t-primary)/8 text-(--t-primary)/80"
                  >
                    {{ tag }}
                  </span>
                </div>

                <!-- Footer: vote + date -->
                <div class="flex items-center gap-3 mt-3 pt-2 border-t border-(--t-border)/30">
                  <span class="text-[10px] text-(--t-text-3)">Полезно?</span>
                  <button
                    class="flex items-center gap-1 text-[10px] text-(--t-text-3) hover:text-emerald-400 transition-colors"
                    @click.stop="voteFaq(item.id, true)"
                  >
                    👍 {{ item.helpful }}
                  </button>
                  <button
                    class="flex items-center gap-1 text-[10px] text-(--t-text-3) hover:text-rose-400 transition-colors"
                    @click.stop="voteFaq(item.id, false)"
                  >
                    👎 {{ item.unhelpful }}
                  </button>
                  <span class="flex-1" />
                  <span class="text-[10px] text-(--t-text-3)">{{ relativeTime(item.updatedAt) }}</span>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </div>

      <!-- FAQ empty -->
      <div v-else class="flex flex-col items-center gap-2 py-12 text-center">
        <span class="text-4xl opacity-40">🔍</span>
        <p class="text-sm text-(--t-text-3)">Ничего не найдено</p>
        <p class="text-xs text-(--t-text-3)/60">Попробуйте изменить запрос или категорию</p>
      </div>
    </div>

    <!-- ──────── VIDEOS ──────── -->
    <div v-if="activeTab === 'videos'" class="flex flex-col gap-4">
      <!-- Video category pills -->
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 -mx-1 px-1">
        <button
          :class="[
            'shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200',
            videoFilter === ''
              ? 'bg-(--t-primary)/12 text-(--t-primary) border border-(--t-primary)/30'
              : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-surface) border border-transparent',
          ]"
          @click="videoFilter = ''"
        >
          Все
        </button>
        <button
          v-for="cat in vc.videoCategories"
          :key="cat"
          :class="[
            'shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200',
            videoFilter === cat
              ? 'bg-(--t-primary)/12 text-(--t-primary) border border-(--t-primary)/30'
              : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-surface) border border-transparent',
          ]"
          @click="videoFilter = cat"
        >
          {{ cat }}
        </button>
      </div>

      <!-- Video Grid -->
      <div v-if="filteredVideos.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <button
          v-for="video in filteredVideos"
          :key="video.id"
          class="relative group text-start rounded-xl border border-(--t-border) bg-(--t-surface)/60
                 backdrop-blur-sm overflow-hidden hover:border-(--t-primary)/40
                 hover:bg-(--t-card-hover) transition-all duration-200 active:scale-[0.98]"
          @click="openVideo(video)"
          @mousedown="ripple"
        >
          <!-- Thumbnail -->
          <div class="relative aspect-video bg-(--t-border)/20 overflow-hidden">
            <div
              v-if="video.thumbnailUrl"
              class="absolute inset-0 bg-cover bg-center transition-transform duration-300
                     group-hover:scale-105"
              :style="{ backgroundImage: `url(${video.thumbnailUrl})` }"
            />
            <div v-else class="absolute inset-0 flex items-center justify-center text-3xl opacity-30">
              🎬
            </div>
            <!-- Play overlay -->
            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100
                        bg-black/30 transition-opacity duration-200">
              <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center">
                <svg class="w-5 h-5 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z" />
                </svg>
              </div>
            </div>
            <!-- Duration -->
            <span class="absolute inset-inline-end-2 inset-block-end-2 px-1.5 py-0.5 rounded text-[10px]
                         font-medium bg-black/60 text-white tabular-nums">
              {{ video.duration }}
            </span>
          </div>

          <!-- Info -->
          <div class="p-3">
            <h4 class="text-xs font-semibold text-(--t-text) line-clamp-2 group-hover:text-(--t-primary)
                       transition-colors">
              {{ video.title }}
            </h4>
            <p class="text-[10px] text-(--t-text-3) mt-1 line-clamp-2">{{ video.description }}</p>
            <div class="flex items-center gap-2 mt-2 text-[10px] text-(--t-text-3)">
              <span>👁 {{ fmtNum(video.views) }}</span>
              <span>·</span>
              <span>{{ relativeTime(video.createdAt) }}</span>
            </div>
          </div>
        </button>
      </div>

      <!-- Videos empty -->
      <div v-else class="flex flex-col items-center gap-2 py-12 text-center">
        <span class="text-4xl opacity-40">🎬</span>
        <p class="text-sm text-(--t-text-3)">Видео не найдены</p>
        <p class="text-xs text-(--t-text-3)/60">Попробуйте другую категорию</p>
      </div>
    </div>

    <!-- ──────── DOCS ──────── -->
    <div v-if="activeTab === 'docs'" class="flex flex-col gap-4">
      <!-- Doc section pills -->
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 -mx-1 px-1">
        <button
          :class="[
            'shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200',
            docFilter === ''
              ? 'bg-(--t-primary)/12 text-(--t-primary) border border-(--t-primary)/30'
              : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-surface) border border-transparent',
          ]"
          @click="docFilter = ''"
        >
          Все разделы
        </button>
        <button
          v-for="section in vc.docSections"
          :key="section.key"
          :class="[
            'shrink-0 flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200',
            docFilter === section.key
              ? 'bg-(--t-primary)/12 text-(--t-primary) border border-(--t-primary)/30'
              : 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-surface) border border-transparent',
          ]"
          @click="docFilter = section.key"
        >
          <span>{{ section.icon }}</span>
          <span>{{ section.label }}</span>
        </button>
      </div>

      <!-- Doc Articles -->
      <div v-if="filteredDocs.length > 0" class="flex flex-col gap-2">
        <div
          v-for="doc in filteredDocs"
          :key="doc.id"
          class="rounded-xl border border-(--t-border) bg-(--t-surface)/60 backdrop-blur-sm
                 overflow-hidden transition-all duration-200 hover:border-(--t-primary)/20"
        >
          <button
            class="relative inline-size-full flex items-center gap-3 px-4 py-3 text-start group
                   transition-colors hover:bg-(--t-card-hover) overflow-hidden"
            @click="doc.children?.length ? toggleDocSection(doc.id) : openDoc(doc)"
            @mousedown="ripple"
          >
            <span class="shrink-0 text-base">{{ doc.icon }}</span>
            <div class="flex-1 min-w-0">
              <h4 class="text-sm font-medium text-(--t-text) group-hover:text-(--t-primary)
                         transition-colors truncate">
                {{ doc.title }}
              </h4>
              <p class="text-[10px] text-(--t-text-3) mt-0.5 truncate">{{ doc.summary }}</p>
            </div>
            <div class="shrink-0 flex items-center gap-2">
              <span class="text-[10px] text-(--t-text-3) tabular-nums">{{ doc.readTimeMin }} мин</span>
              <svg
                v-if="doc.children?.length"
                :class="[
                  'w-3.5 h-3.5 text-(--t-text-3) transition-transform duration-200',
                  expandedDocIds.has(doc.id) ? 'rotate-180' : '',
                ]"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
              </svg>
              <svg
                v-else
                class="w-3.5 h-3.5 text-(--t-text-3) opacity-0 group-hover:opacity-100 transition-opacity"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
              >
                <path stroke-linecap="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
              </svg>
            </div>
          </button>

          <!-- Child docs -->
          <Transition name="faq-hp">
            <div v-if="doc.children?.length && expandedDocIds.has(doc.id)">
              <div class="border-t border-(--t-border)/50">
                <button
                  v-for="child in doc.children"
                  :key="child.id"
                  class="inline-size-full flex items-center gap-3 px-4 py-2.5 ps-10 text-start
                         text-xs text-(--t-text-2) hover:text-(--t-primary) hover:bg-(--t-card-hover)
                         transition-colors border-t border-(--t-border)/30 first:border-t-0"
                  @click="openDoc(child)"
                >
                  <span class="shrink-0">{{ child.icon }}</span>
                  <span class="flex-1 truncate">{{ child.title }}</span>
                  <span class="shrink-0 text-[10px] text-(--t-text-3) tabular-nums">{{ child.readTimeMin }} мин</span>
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>

      <!-- Docs empty -->
      <div v-else class="flex flex-col items-center gap-2 py-12 text-center">
        <span class="text-4xl opacity-40">📚</span>
        <p class="text-sm text-(--t-text-3)">Документы не найдены</p>
        <p class="text-xs text-(--t-text-3)/60">Попробуйте другой раздел</p>
      </div>
    </div>

    <!-- ──────── CHAT ──────── -->
    <div v-if="activeTab === 'chat'" class="flex flex-col rounded-xl border border-(--t-border)
         bg-(--t-surface)/60 backdrop-blur-sm overflow-hidden"
         :style="{ blockSize: isFullscreen ? 'calc(100vh - 340px)' : '480px' }"
    >
      <!-- Chat header -->
      <div class="flex items-center gap-3 px-4 py-3 border-b border-(--t-border)/50">
        <div class="w-8 h-8 rounded-full bg-(--t-primary)/15 flex items-center justify-center">
          <span class="text-sm">🐱</span>
        </div>
        <div class="flex-1 min-w-0">
          <h4 class="text-sm font-semibold text-(--t-text)">Поддержка CatVRF</h4>
          <p class="text-[10px] text-emerald-400">● Онлайн</p>
        </div>
        <span
          v-if="props.unreadChat > 0"
          class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400"
        >
          {{ props.unreadChat }} новых
        </span>
      </div>

      <!-- Messages -->
      <div
        ref="chatContainerEl"
        class="flex-1 overflow-y-auto px-4 py-3 flex flex-col gap-3"
        @scroll="handleChatScroll"
      >
        <div
          v-for="msg in sortedChat"
          :key="msg.id"
          :class="[
            'flex gap-2 max-w-[85%]',
            msg.sender === 'user' ? 'ms-auto flex-row-reverse' : '',
          ]"
        >
          <!-- Avatar -->
          <div
            :class="[
              'shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs',
              msg.sender === 'user'
                ? 'bg-(--t-primary)/15'
                : msg.sender === 'bot'
                  ? 'bg-violet-500/15'
                  : 'bg-emerald-500/15',
            ]"
          >
            {{ msg.sender === 'user' ? '👤' : msg.sender === 'bot' ? '🤖' : '🐱' }}
          </div>

          <!-- Bubble -->
          <div
            :class="[
              'rounded-xl px-3 py-2 text-xs leading-relaxed',
              msg.sender === 'user'
                ? 'bg-(--t-primary)/12 text-(--t-text) rounded-ee-sm'
                : 'bg-(--t-surface) border border-(--t-border)/50 text-(--t-text-2) rounded-es-sm',
            ]"
          >
            <p class="whitespace-pre-line">{{ msg.text }}</p>

            <!-- Attachments -->
            <div v-if="msg.attachments?.length" class="flex flex-wrap gap-1 mt-1.5">
              <a
                v-for="att in msg.attachments"
                :key="att.name"
                :href="att.url"
                target="_blank"
                class="flex items-center gap-1 px-2 py-0.5 rounded-md
                       bg-(--t-border)/30 text-[10px] text-(--t-text-3) hover:text-(--t-primary)
                       transition-colors"
              >
                📎 {{ att.name }} ({{ att.size }})
              </a>
            </div>

            <span class="block text-[9px] mt-1 opacity-50 tabular-nums text-end">
              {{ fmtDatetime(msg.timestamp) }}
            </span>
          </div>
        </div>

        <!-- Empty chat -->
        <div v-if="sortedChat.length === 0" class="flex-1 flex flex-col items-center justify-center gap-2 py-8">
          <span class="text-3xl opacity-30">💬</span>
          <p class="text-xs text-(--t-text-3)">Начните диалог с поддержкой</p>
          <p class="text-[10px] text-(--t-text-3)/60">{{ vc.supportHint }}</p>
        </div>
      </div>

      <!-- Chat input -->
      <div class="border-t border-(--t-border)/50 px-4 py-3">
        <!-- Attachments preview -->
        <div v-if="chatAttachments.length" class="flex flex-wrap gap-1.5 mb-2">
          <span
            v-for="(file, fi) in chatAttachments"
            :key="fi"
            class="flex items-center gap-1 px-2 py-1 rounded-md bg-(--t-border)/20 text-[10px] text-(--t-text-3)"
          >
            📎 {{ file.name }}
            <button class="hover:text-rose-400 transition-colors" @click="removeChatAttachment(fi)">×</button>
          </span>
        </div>

        <div class="flex items-end gap-2">
          <!-- Attach -->
          <label class="shrink-0 cursor-pointer w-8 h-8 rounded-lg flex items-center justify-center
                        text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-surface)
                        transition-all">
            <input type="file" class="hidden" multiple @change="addChatAttachment" />
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" />
            </svg>
          </label>

          <!-- Text -->
          <textarea
            v-model="chatInput"
            rows="1"
            :placeholder="vc.supportHint"
            class="flex-1 resize-none rounded-lg border border-(--t-border) bg-(--t-surface)/60
                   px-3 py-2 text-xs text-(--t-text) placeholder:text-(--t-text-3) outline-none
                   focus:border-(--t-primary)/50 transition-colors max-h-24"
            @keydown="handleChatKeydown"
            @input="onChatTyping"
          />

          <!-- Send -->
          <button
            :disabled="!chatInput.trim() && chatAttachments.length === 0"
            :class="[
              'shrink-0 w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-200',
              chatInput.trim() || chatAttachments.length
                ? 'bg-(--t-primary) text-white hover:opacity-90 active:scale-95'
                : 'bg-(--t-surface) text-(--t-text-3) cursor-not-allowed',
            ]"
            @click="sendChatMessage"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 3 9-3 9 19-9Z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 12h16" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- ──────── FEEDBACK ──────── -->
    <div v-if="activeTab === 'feedback'" class="flex flex-col gap-4">
      <div class="rounded-xl border border-(--t-border) bg-(--t-surface)/60 backdrop-blur-sm p-4">
        <h3 class="text-sm font-semibold text-(--t-text) mb-4">📩 Новое обращение</h3>

        <div class="flex flex-col gap-3">
          <!-- Subject -->
          <div>
            <label class="block text-[10px] font-medium text-(--t-text-3) mb-1">Тема</label>
            <input
              v-model="feedbackForm.subject"
              type="text"
              placeholder="Кратко опишите тему обращения"
              class="inline-size-full rounded-lg border border-(--t-border) bg-(--t-surface)/60
                     px-3 py-2 text-xs text-(--t-text) placeholder:text-(--t-text-3) outline-none
                     focus:border-(--t-primary)/50 transition-colors"
            />
          </div>

          <!-- Category + Priority row -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-medium text-(--t-text-3) mb-1">Категория</label>
              <select
                v-model="feedbackForm.category"
                class="inline-size-full rounded-lg border border-(--t-border) bg-(--t-surface)
                       px-3 py-2 text-xs text-(--t-text) outline-none
                       focus:border-(--t-primary)/50 transition-colors"
              >
                <option v-for="cat in vc.faqCategories" :key="cat.key" :value="cat.key">
                  {{ cat.icon }} {{ cat.label }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-[10px] font-medium text-(--t-text-3) mb-1">Приоритет</label>
              <select
                v-model="feedbackForm.priority"
                class="inline-size-full rounded-lg border border-(--t-border) bg-(--t-surface)
                       px-3 py-2 text-xs text-(--t-text) outline-none
                       focus:border-(--t-primary)/50 transition-colors"
              >
                <option value="low">🟢 Низкий</option>
                <option value="medium">🔵 Средний</option>
                <option value="high">🟡 Высокий</option>
                <option value="critical">🔴 Критичный</option>
              </select>
            </div>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-[10px] font-medium text-(--t-text-3) mb-1">Описание</label>
            <textarea
              v-model="feedbackForm.description"
              rows="5"
              :placeholder="vc.supportHint"
              class="inline-size-full rounded-lg border border-(--t-border) bg-(--t-surface)/60
                     px-3 py-2 text-xs text-(--t-text) placeholder:text-(--t-text-3) outline-none
                     focus:border-(--t-primary)/50 transition-colors resize-y"
            />
          </div>

          <!-- Attachments -->
          <div>
            <label class="block text-[10px] font-medium text-(--t-text-3) mb-1">Файлы</label>
            <div class="flex flex-wrap gap-1.5">
              <span
                v-for="(file, fi) in feedbackForm.attachments"
                :key="fi"
                class="flex items-center gap-1 px-2 py-1 rounded-md bg-(--t-border)/20 text-[10px] text-(--t-text-3)"
              >
                📎 {{ file.name }}
                <button class="hover:text-rose-400 transition-colors" @click="removeFeedbackAttachment(fi)">×</button>
              </span>
              <label class="cursor-pointer flex items-center gap-1 px-2 py-1 rounded-md border border-dashed
                            border-(--t-border) text-[10px] text-(--t-text-3) hover:text-(--t-text)
                            hover:border-(--t-primary)/40 transition-all">
                <input type="file" class="hidden" multiple @change="addFeedbackAttachment" />
                ➕ Прикрепить
              </label>
            </div>
          </div>

          <!-- Submit -->
          <div class="flex items-center gap-2 mt-1">
            <button
              :disabled="feedbackSubmitting || !feedbackForm.subject.trim() || !feedbackForm.description.trim()"
              :class="[
                'flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-medium transition-all duration-200',
                feedbackSubmitting || !feedbackForm.subject.trim() || !feedbackForm.description.trim()
                  ? 'bg-(--t-border)/30 text-(--t-text-3) cursor-not-allowed'
                  : 'bg-(--t-primary) text-white hover:opacity-90 active:scale-[0.98]',
              ]"
              @click="submitFeedback"
            >
              <template v-if="feedbackSubmitting">
                <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                Отправка…
              </template>
              <template v-else>📩 Отправить</template>
            </button>

            <!-- Success message -->
            <Transition name="fade-hp">
              <span v-if="feedbackSuccess" class="text-xs text-emerald-400 font-medium">
                ✅ Обращение отправлено!
              </span>
            </Transition>
          </div>
        </div>
      </div>

      <!-- My tickets -->
      <div v-if="props.tickets.length > 0"
           class="rounded-xl border border-(--t-border) bg-(--t-surface)/60 backdrop-blur-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-(--t-border)/50 flex items-center gap-2">
          <span class="text-sm">🎫</span>
          <h3 class="text-sm font-semibold text-(--t-text)">Мои обращения</h3>
          <span class="text-[10px] text-(--t-text-3) tabular-nums">({{ props.tickets.length }})</span>
        </div>
        <div class="divide-y divide-(--t-border)/30">
          <div
            v-for="ticket in props.tickets"
            :key="ticket.id"
            class="flex items-center gap-3 px-4 py-3 hover:bg-(--t-card-hover) transition-colors"
          >
            <span :class="['w-2 h-2 rounded-full shrink-0', TICKET_STATUS_MAP[ticket.status]?.dot ?? 'bg-zinc-500']" />
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium text-(--t-text) truncate">{{ ticket.subject }}</p>
              <p class="text-[10px] text-(--t-text-3) mt-0.5">
                {{ TICKET_PRIORITY_MAP[ticket.priority]?.label ?? ticket.priority }}
                · {{ relativeTime(ticket.updatedAt) }}
              </p>
            </div>
            <span class="shrink-0 text-[10px] px-2 py-0.5 rounded-full"
                  :class="[
                    ticket.status === 'open' ? 'bg-sky-500/15 text-sky-400' : '',
                    ticket.status === 'pending' ? 'bg-amber-500/15 text-amber-400' : '',
                    ticket.status === 'resolved' ? 'bg-emerald-500/15 text-emerald-400' : '',
                    ticket.status === 'closed' ? 'bg-zinc-500/15 text-zinc-400' : '',
                  ]"
            >
              {{ TICKET_STATUS_MAP[ticket.status]?.label ?? ticket.status }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════
         5. TICKETS SIDEBAR (overlay)
    ═══════════════════════════════════════════════ -->
    <Transition name="drawer-hp">
      <div
        v-if="showTickets"
        class="fixed inset-0 z-80 flex"
        @click.self="showTickets = false"
      >
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showTickets = false" />
        <div class="relative ms-auto inline-size-full max-w-sm bg-(--t-bg) border-s border-(--t-border)
                    shadow-2xl overflow-y-auto">
          <!-- Header -->
          <div class="sticky inset-block-start-0 z-10 bg-(--t-bg)/95 backdrop-blur-md border-b border-(--t-border)
                      px-4 py-3 flex items-center gap-2">
            <span class="text-sm">🎫</span>
            <h3 class="text-sm font-semibold text-(--t-text) flex-1">Мои обращения</h3>
            <button
              class="w-7 h-7 rounded-lg flex items-center justify-center text-(--t-text-3) hover:text-(--t-text)
                     hover:bg-(--t-surface) transition-all"
              @click="showTickets = false"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" d="M18 6 6 18M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Open tickets -->
          <div v-if="openTickets.length" class="px-4 pt-3">
            <p class="text-[10px] font-medium text-(--t-text-3) uppercase tracking-wide mb-2">
              Открытые ({{ openTickets.length }})
            </p>
            <div class="flex flex-col gap-2">
              <div
                v-for="ticket in openTickets"
                :key="ticket.id"
                class="rounded-lg border border-(--t-border) bg-(--t-surface)/60 p-3"
              >
                <div class="flex items-start gap-2">
                  <span :class="['w-2 h-2 rounded-full shrink-0 mt-1.5', TICKET_PRIORITY_MAP[ticket.priority]?.dot ?? 'bg-zinc-400']" />
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-(--t-text)">{{ ticket.subject }}</p>
                    <p class="text-[10px] text-(--t-text-3) mt-0.5 line-clamp-2">{{ ticket.description }}</p>
                    <div class="flex items-center gap-2 mt-2">
                      <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-400">
                        {{ TICKET_STATUS_MAP[ticket.status]?.label }}
                      </span>
                      <span class="text-[10px] text-(--t-text-3) tabular-nums">
                        {{ relativeTime(ticket.createdAt) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Resolved tickets -->
          <div v-if="resolvedTickets.length" class="px-4 pt-4 pb-4">
            <p class="text-[10px] font-medium text-(--t-text-3) uppercase tracking-wide mb-2">
              Решённые ({{ resolvedTickets.length }})
            </p>
            <div class="flex flex-col gap-2">
              <div
                v-for="ticket in resolvedTickets"
                :key="ticket.id"
                class="rounded-lg border border-(--t-border)/50 bg-(--t-surface)/30 p-3 opacity-70"
              >
                <p class="text-xs font-medium text-(--t-text-2) truncate">{{ ticket.subject }}</p>
                <div class="flex items-center gap-2 mt-1">
                  <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/15 text-emerald-400">
                    {{ TICKET_STATUS_MAP[ticket.status]?.label }}
                  </span>
                  <span class="text-[10px] text-(--t-text-3) tabular-nums">
                    {{ relativeTime(ticket.updatedAt) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- No tickets -->
          <div v-if="!props.tickets.length" class="flex flex-col items-center gap-2 py-12 text-center px-4">
            <span class="text-3xl opacity-30">🎫</span>
            <p class="text-xs text-(--t-text-3)">Нет обращений</p>
            <p class="text-[10px] text-(--t-text-3)/60">Создайте обращение во вкладке «Обратная связь»</p>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ═══════════════════════════════════════════════
         6. VIDEO PLAYER MODAL
    ═══════════════════════════════════════════════ -->
    <VModal :show="showVideoModal" :title="activeVideo?.title ?? 'Видео'" size="lg" @close="closeVideo">
      <template #default>
        <div v-if="activeVideo" class="flex flex-col gap-3">
          <!-- Video player area -->
          <div class="relative aspect-video rounded-lg bg-black overflow-hidden">
            <div class="absolute inset-0 flex items-center justify-center">
              <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-white/10 flex items-center justify-center mb-3">
                  <svg class="w-7 h-7 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                  </svg>
                </div>
                <p class="text-xs text-white/60">Видео загружается…</p>
              </div>
            </div>
          </div>

          <!-- Video info -->
          <div>
            <h4 class="text-sm font-semibold text-(--t-text)">{{ activeVideo.title }}</h4>
            <p class="text-xs text-(--t-text-3) mt-1">{{ activeVideo.description }}</p>
            <div class="flex items-center gap-3 mt-2 text-[10px] text-(--t-text-3)">
              <span>⏱ {{ activeVideo.duration }}</span>
              <span>👁 {{ fmtNum(activeVideo.views) }} просмотров</span>
              <span>{{ relativeTime(activeVideo.createdAt) }}</span>
            </div>
          </div>

          <!-- Tags -->
          <div v-if="activeVideo.tags.length" class="flex flex-wrap gap-1">
            <span
              v-for="tag in activeVideo.tags"
              :key="tag"
              class="px-2 py-0.5 rounded-full text-[10px] font-medium
                     bg-(--t-primary)/8 text-(--t-primary)/80"
            >
              {{ tag }}
            </span>
          </div>
        </div>
      </template>
      <template #footer>
        <VButton variant="ghost" size="sm" @click="closeVideo">Закрыть</VButton>
      </template>
    </VModal>

    <!-- ═══════════════════════════════════════════════
         7. LOADING OVERLAY
    ═══════════════════════════════════════════════ -->
    <Transition name="fade-hp">
      <div
        v-if="loading"
        class="fixed inset-0 z-80 bg-(--t-bg)/40 backdrop-blur-sm flex items-center justify-center
               pointer-events-none"
      >
        <div class="flex items-center gap-3 text-(--t-text-2) bg-(--t-surface) border border-(--t-border)
                    rounded-2xl px-6 py-4 shadow-2xl backdrop-blur-xl pointer-events-auto">
          <svg class="animate-spin w-5 h-5 text-(--t-primary)" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <span class="text-sm font-medium">Загрузка справки...</span>
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
@keyframes ripple-hp {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* Fade transition */
.fade-hp-enter-active,
.fade-hp-leave-active {
  transition: opacity 0.3s ease;
}
.fade-hp-enter-from,
.fade-hp-leave-to {
  opacity: 0;
}

/* FAQ accordion transition */
.faq-hp-enter-active {
  transition: all 0.25s ease-out;
  overflow: hidden;
}
.faq-hp-leave-active {
  transition: all 0.2s ease-in;
  overflow: hidden;
}
.faq-hp-enter-from {
  opacity: 0;
  max-block-size: 0;
}
.faq-hp-enter-to {
  opacity: 1;
  max-block-size: 600px;
}
.faq-hp-leave-from {
  opacity: 1;
  max-block-size: 600px;
}
.faq-hp-leave-to {
  opacity: 0;
  max-block-size: 0;
}

/* Drawer transition */
.drawer-hp-enter-active,
.drawer-hp-leave-active {
  transition: opacity 0.3s ease;
}
.drawer-hp-enter-active > :last-child,
.drawer-hp-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.drawer-hp-enter-from,
.drawer-hp-leave-to {
  opacity: 0;
}
.drawer-hp-enter-from > :last-child,
.drawer-hp-leave-to > :last-child {
  transform: translateX(100%);
}

/* Tabular nums */
.tabular-nums {
  font-variant-numeric: tabular-nums;
}

/* Scrollbar styling */
.overflow-y-auto::-webkit-scrollbar { inline-size: 4px; }
.overflow-y-auto::-webkit-scrollbar-track { background: transparent; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: var(--t-border); border-radius: 999px; }

.overflow-x-auto::-webkit-scrollbar { block-size: 5px; }
.overflow-x-auto::-webkit-scrollbar-track { background: transparent; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: var(--t-border); border-radius: 9999px; }
</style>
