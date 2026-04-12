<script setup lang="ts">
/**
 * TenantSettings.vue — Главная страница настроек бизнеса B2B Tenant Dashboard
 *
 * Поддержка всех 127 вертикалей CatVRF:
 *   Beauty (салоны) · Taxi (парки) · Food (рестораны, кафе)
 *   Hotels (отели) · RealEstate (агентства) · Flowers (магазины)
 *   Fashion (бутики) · Furniture (салоны мебели) · Fitness (клубы)
 *   Travel (турагентства) · Medical (клиники) · Auto (СТО) · и т.д.
 *
 * ─────────────────────────────────────────────────────────────
 *  Функционал:
 *   1. Боковое меню категорий настроек (sidebar / drawer на mobile)
 *   2. Общие настройки (название, описание, тайм-зона, язык, валюта)
 *   3. Профиль бизнеса (лого, адрес, контакты, соцсети)
 *   4. Персонал и роли (шаблоны ролей, права, расписания)
 *   5. Финансы (реквизиты, способы оплаты, график выплат, комиссии)
 *   6. Интеграции (API-ключи, вебхуки, 1С, CRM, внешние сервисы)
 *   7. Безопасность (2FA, IP-белый список, управление сессиями)
 *   8. Уведомления (Email, Push, Telegram, SMS — каналы и события)
 *   9. Настройки вертикали (специфичные параметры конкретной вертикали)
 *  10. Full-screen режим
 *  11. Сохранение с подтверждением + отмена изменений
 *  12. B2B/B2C — переключение режима
 * ─────────────────────────────────────────────────────────────
 *  Адаптация под вертикаль:
 *   → props.vertical определяет терминологию и доп. поля
 *   → VERTICAL_SETTINGS_CONFIG — маппинг конфигов
 * ─────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import type { Ref } from 'vue'

import VCard   from '../UI/VCard.vue'
import VButton from '../UI/VButton.vue'
import VBadge  from '../UI/VBadge.vue'
import VTabs   from '../UI/VTabs.vue'
import VModal  from '../UI/VModal.vue'
import VInput  from '../UI/VInput.vue'
import { useAuth, useTenant, useNotifications } from '@/stores'

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  TYPES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

type SettingsCategory =
  | 'general'
  | 'profile'
  | 'staff'
  | 'finance'
  | 'integrations'
  | 'security'
  | 'notifications'
  | 'vertical'

type NotifChannel = 'email' | 'push' | 'telegram' | 'sms'

interface CategoryItem {
  key:    SettingsCategory
  label:  string
  icon:   string
  badge?: number
}

interface GeneralSettings {
  businessName:  string
  description:   string
  timezone:      string
  language:      string
  currency:      string
  dateFormat:    string
  isB2BEnabled:  boolean
  isPublic:      boolean
  maxCarts:      number
  reserveMinutes: number
}

interface ProfileSettings {
  logo:          string
  coverImage:    string
  phone:         string
  email:         string
  website:       string
  address:       string
  city:          string
  postalCode:    string
  lat:           number
  lon:           number
  socialLinks:   Array<{ platform: string; url: string }>
  workingHours:  Array<{ day: string; from: string; to: string; enabled: boolean }>
}

interface StaffRoleTemplate {
  key:         string
  label:       string
  icon:        string
  permissions: string[]
  isDefault:   boolean
}

interface StaffSettings {
  roles:             StaffRoleTemplate[]
  defaultSchedule:   string
  maxEmployees:      number
  requireApproval:   boolean
  autoClockIn:       boolean
  kpiEnabled:        boolean
}

interface FinanceSettings {
  legalName:        string
  inn:              string
  kpp:              string
  bankAccount:      string
  bik:              string
  corrAccount:      string
  paymentMethods:   Array<{ key: string; label: string; enabled: boolean }>
  payoutSchedule:   'daily' | 'weekly' | 'biweekly' | 'monthly'
  commissionRate:   number
  b2bCreditLimit:   number
  b2bPaymentTerms:  number
  autoPayouts:      boolean
}

interface IntegrationItem {
  key:          string
  label:        string
  icon:         string
  status:       'active' | 'inactive' | 'error'
  description:  string
  lastSync?:    string
}

interface ApiKeyItem {
  id:          string
  name:        string
  key:         string
  permissions: string[]
  createdAt:   string
  expiresAt?:  string
  lastUsed?:   string
}

interface SecuritySettings {
  twoFactorEnabled:     boolean
  twoFactorMethod:      'app' | 'sms' | 'email'
  ipWhitelist:          string[]
  sessionTimeoutMin:    number
  passwordMinLength:    number
  requireUpperCase:     boolean
  requireSpecialChars:  boolean
  maxLoginAttempts:     number
  activeSessions:       Array<{ id: string; device: string; ip: string; lastActive: string; current: boolean }>
}

interface NotificationRule {
  event:    string
  label:    string
  channels: Record<NotifChannel, boolean>
}

interface NotificationSettings {
  emailEnabled:    boolean
  pushEnabled:     boolean
  telegramEnabled: boolean
  smsEnabled:      boolean
  telegramBotId?:  string
  rules:           NotificationRule[]
}

interface VerticalSettingsConfig {
  label:             string
  icon:              string
  categoryLabel:     string
  extraFields:       Array<{ key: string; label: string; type: 'text' | 'number' | 'toggle' | 'select'; options?: Array<{ value: string; label: string }> }>
  staffRoles:        Array<{ key: string; label: string; icon: string }>
  integrationHints:  string[]
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  PROPS & EMITS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const props = withDefaults(defineProps<{
  vertical?:          string
  general?:           GeneralSettings
  profile?:           ProfileSettings
  staffSettings?:     StaffSettings
  finance?:           FinanceSettings
  integrations?:      IntegrationItem[]
  apiKeys?:           ApiKeyItem[]
  security?:          SecuritySettings
  notifications?:     NotificationSettings
  branches?:          Array<{ id: string; name: string }>
  loading?:           boolean
}>(), {
  vertical:       'default',
  general:        () => ({
    businessName: '', description: '', timezone: 'Europe/Moscow',
    language: 'ru', currency: 'RUB', dateFormat: 'DD.MM.YYYY',
    isB2BEnabled: false, isPublic: true, maxCarts: 20, reserveMinutes: 20,
  }),
  profile:        () => ({
    logo: '', coverImage: '', phone: '', email: '', website: '',
    address: '', city: '', postalCode: '', lat: 0, lon: 0,
    socialLinks: [], workingHours: [],
  }),
  staffSettings:  () => ({
    roles: [], defaultSchedule: '9:00-18:00',
    maxEmployees: 100, requireApproval: true, autoClockIn: false, kpiEnabled: true,
  }),
  finance:        () => ({
    legalName: '', inn: '', kpp: '', bankAccount: '', bik: '', corrAccount: '',
    paymentMethods: [], payoutSchedule: 'weekly' as const,
    commissionRate: 14, b2bCreditLimit: 0, b2bPaymentTerms: 14, autoPayouts: false,
  }),
  integrations:   () => [],
  apiKeys:        () => [],
  security:       () => ({
    twoFactorEnabled: false, twoFactorMethod: 'app' as const,
    ipWhitelist: [], sessionTimeoutMin: 60,
    passwordMinLength: 8, requireUpperCase: true, requireSpecialChars: true,
    maxLoginAttempts: 5, activeSessions: [],
  }),
  notifications:  () => ({
    emailEnabled: true, pushEnabled: true, telegramEnabled: false, smsEnabled: false,
    telegramBotId: '', rules: [],
  }),
  branches:       () => [],
  loading:        false,
})

const emit = defineEmits<{
  'save':              [category: SettingsCategory, data: Record<string, unknown>]
  'save-all':          []
  'discard':           [category: SettingsCategory]
  'upload-logo':       [file: File]
  'upload-cover':      [file: File]
  'toggle-b2b':        [enabled: boolean]
  'toggle-2fa':        [enabled: boolean]
  'revoke-session':    [sessionId: string]
  'revoke-all':        []
  'create-api-key':    [name: string, permissions: string[]]
  'revoke-api-key':    [keyId: string]
  'test-integration':  [integrationKey: string]
  'enable-integration': [integrationKey: string, enabled: boolean]
  'test-webhook':      [url: string]
  'add-ip-whitelist':  [ip: string]
  'remove-ip':         [ip: string]
  'update-notif-rule': [event: string, channels: Record<NotifChannel, boolean>]
  'category-change':   [category: SettingsCategory]
}>()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STORES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const auth          = useAuth()
const business      = useTenant()
const notifications = useNotifications()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  VERTICAL SETTINGS CONFIG
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const VERTICAL_SETTINGS_CONFIG: Record<string, VerticalSettingsConfig> = {
  // ── BEAUTY ──────────────────────────────────
  beauty: {
    label: 'Настройки салона', icon: '💄', categoryLabel: 'Салон красоты',
    extraFields: [
      { key: 'bookingSlotMin', label: 'Минимальный слот записи (мин)', type: 'number' },
      { key: 'cancelPenaltyHours', label: 'Штраф за отмену (часов до)', type: 'number' },
      { key: 'arEnabled', label: 'AR-примерка', type: 'toggle' },
      { key: 'aiConstructorEnabled', label: 'AI-конструктор образа', type: 'toggle' },
      { key: 'defaultServiceDuration', label: 'Длительность услуги по умолчанию (мин)', type: 'number' },
    ],
    staffRoles: [
      { key: 'stylist', label: 'Стилист', icon: '💇' },
      { key: 'colorist', label: 'Колорист', icon: '🎨' },
      { key: 'nail_master', label: 'Мастер маникюра', icon: '💅' },
      { key: 'cosmetologist', label: 'Косметолог', icon: '🧴' },
    ],
    integrationHints: ['Запись онлайн', 'CRM салона', 'Касса (ОФД)'],
  },

  // ── TAXI ────────────────────────────────────
  taxi: {
    label: 'Настройки парка', icon: '🚕', categoryLabel: 'Таксопарк',
    extraFields: [
      { key: 'surgeMultiplier', label: 'Макс. коэффициент повышения', type: 'number' },
      { key: 'minFare', label: 'Минимальная стоимость поездки ₽', type: 'number' },
      { key: 'autoDispatch', label: 'Авто-назначение заказов', type: 'toggle' },
      { key: 'geotrackingInterval', label: 'Интервал трекинга (сек)', type: 'number' },
    ],
    staffRoles: [
      { key: 'driver_economy', label: 'Эконом', icon: '🚗' },
      { key: 'driver_comfort', label: 'Комфорт', icon: '🚙' },
      { key: 'driver_business', label: 'Бизнес', icon: '🚘' },
      { key: 'dispatcher', label: 'Диспетчер', icon: '📞' },
    ],
    integrationHints: ['Навигация', 'Яндекс.Карты', 'Таксометр'],
  },

  // ── FOOD ────────────────────────────────────
  food: {
    label: 'Настройки ресторана', icon: '🍽️', categoryLabel: 'Ресторан',
    extraFields: [
      { key: 'deliveryRadius', label: 'Радиус доставки (км)', type: 'number' },
      { key: 'minOrderAmount', label: 'Минимальная сумма заказа ₽', type: 'number' },
      { key: 'avgCookTimeMin', label: 'Среднее время готовки (мин)', type: 'number' },
      { key: 'showCalories', label: 'Показывать КБЖУ', type: 'toggle' },
      { key: 'allergenWarning', label: 'Предупреждения об аллергенах', type: 'toggle' },
    ],
    staffRoles: [
      { key: 'chef', label: 'Шеф-повар', icon: '👨‍🍳' },
      { key: 'cook', label: 'Повар', icon: '🍳' },
      { key: 'courier', label: 'Курьер', icon: '🚴' },
      { key: 'waiter', label: 'Официант', icon: '🍽️' },
    ],
    integrationHints: ['iiko', 'R-Keeper', 'Доставка', 'Яндекс.Еда'],
  },

  // ── HOTEL ───────────────────────────────────
  hotel: {
    label: 'Настройки отеля', icon: '🏨', categoryLabel: 'Отель',
    extraFields: [
      { key: 'checkInTime', label: 'Время заезда', type: 'text' },
      { key: 'checkOutTime', label: 'Время выезда', type: 'text' },
      { key: 'earlyCheckInFee', label: 'Доплата за ранний заезд ₽', type: 'number' },
      { key: 'lateCheckOutFee', label: 'Доплата за поздний выезд ₽', type: 'number' },
      { key: 'virtualTourEnabled', label: '3D-тур по номерам', type: 'toggle' },
    ],
    staffRoles: [
      { key: 'receptionist', label: 'Ресепшн', icon: '🛎️' },
      { key: 'concierge', label: 'Консьерж', icon: '🎩' },
      { key: 'housekeeper', label: 'Горничная', icon: '🧹' },
      { key: 'manager', label: 'Менеджер', icon: '📋' },
    ],
    integrationHints: ['Booking.com', 'Островок', 'TravelLine', 'PMS'],
  },

  // ── REAL ESTATE ─────────────────────────────
  realEstate: {
    label: 'Настройки агентства', icon: '🏢', categoryLabel: 'Недвижимость',
    extraFields: [
      { key: 'defaultCommission', label: 'Комиссия агента (%)', type: 'number' },
      { key: 'autoPublish', label: 'Авто-публикация объявлений', type: 'toggle' },
      { key: 'virtualTourEnabled', label: '3D-тур по объектам', type: 'toggle' },
      { key: 'mortgageCalcEnabled', label: 'Калькулятор ипотеки', type: 'toggle' },
    ],
    staffRoles: [
      { key: 'agent', label: 'Агент', icon: '🔑' },
      { key: 'appraiser', label: 'Оценщик', icon: '📐' },
      { key: 'broker', label: 'Брокер', icon: '🤝' },
    ],
    integrationHints: ['ЦИАН', 'Авито', 'Домклик', 'Яндекс.Недвижимость'],
  },

  // ── FLOWERS ─────────────────────────────────
  flowers: {
    label: 'Настройки магазина', icon: '💐', categoryLabel: 'Цветы',
    extraFields: [
      { key: 'deliveryRadius', label: 'Радиус доставки (км)', type: 'number' },
      { key: 'freshnessDays', label: 'Срок свежести (дней)', type: 'number' },
      { key: 'customBouquets', label: 'Сборка букетов на заказ', type: 'toggle' },
      { key: 'anonymousDelivery', label: 'Анонимная доставка', type: 'toggle' },
    ],
    staffRoles: [
      { key: 'florist', label: 'Флорист', icon: '💐' },
      { key: 'courier', label: 'Курьер', icon: '🚴' },
      { key: 'designer', label: 'Декоратор', icon: '🎨' },
    ],
    integrationHints: ['Доставка', 'Касса (ОФД)', 'Фото-каталог'],
  },

  // ── FASHION ─────────────────────────────────
  fashion: {
    label: 'Настройки бутика', icon: '👗', categoryLabel: 'Мода',
    extraFields: [
      { key: 'returnDays', label: 'Срок возврата (дней)', type: 'number' },
      { key: 'virtualFitting', label: 'AR-примерка', type: 'toggle' },
      { key: 'sizeGuideEnabled', label: 'Таблица размеров', type: 'toggle' },
      { key: 'capsuleGenerator', label: 'AI капсульный гардероб', type: 'toggle' },
    ],
    staffRoles: [
      { key: 'stylist', label: 'Стилист', icon: '👗' },
      { key: 'consultant', label: 'Консультант', icon: '🛍️' },
      { key: 'tailor', label: 'Портной', icon: '🪡' },
    ],
    integrationHints: ['Wildberries', 'Ozon', 'Lamoda', '1C:Торговля'],
  },

  // ── FURNITURE ───────────────────────────────
  furniture: {
    label: 'Настройки салона', icon: '🛋️', categoryLabel: 'Мебель',
    extraFields: [
      { key: 'assemblyService', label: 'Услуга сборки', type: 'toggle' },
      { key: 'measurementService', label: 'Выезд замерщика', type: 'toggle' },
      { key: 'interiorDesigner', label: 'AI дизайн интерьера', type: 'toggle' },
      { key: 'repairCalculator', label: 'Калькулятор ремонта', type: 'toggle' },
    ],
    staffRoles: [
      { key: 'measurer', label: 'Замерщик', icon: '📐' },
      { key: 'assembler', label: 'Сборщик', icon: '🔧' },
      { key: 'designer', label: 'Дизайнер', icon: '🎨' },
      { key: 'courier', label: 'Доставщик', icon: '🚚' },
    ],
    integrationHints: ['3D-визуализация', 'Blender', '1C:Мебельщик'],
  },

  // ── FITNESS ─────────────────────────────────
  fitness: {
    label: 'Настройки клуба', icon: '💪', categoryLabel: 'Фитнес',
    extraFields: [
      { key: 'trialDays', label: 'Пробный период (дней)', type: 'number' },
      { key: 'freezeDaysMax', label: 'Макс. заморозка (дней)', type: 'number' },
      { key: 'virtualTrainer', label: 'AI виртуальный тренер', type: 'toggle' },
      { key: 'nutritionPlans', label: 'Планы питания', type: 'toggle' },
    ],
    staffRoles: [
      { key: 'personal_trainer', label: 'Персональный тренер', icon: '🏋️' },
      { key: 'group_trainer', label: 'Групповой тренер', icon: '👥' },
      { key: 'nutritionist', label: 'Нутрициолог', icon: '🥗' },
    ],
    integrationHints: ['1C:Фитнес', 'СКУД', 'Трекеры здоровья'],
  },

  // ── TRAVEL ──────────────────────────────────
  travel: {
    label: 'Настройки агентства', icon: '✈️', categoryLabel: 'Туризм',
    extraFields: [
      { key: 'defaultMarkup', label: 'Наценка по умолчанию (%)', type: 'number' },
      { key: 'visaAssistance', label: 'Визовая поддержка', type: 'toggle' },
      { key: 'insurancePartner', label: 'Партнёр по страхованию', type: 'text' },
      { key: 'itineraryAI', label: 'AI генерация маршрутов', type: 'toggle' },
    ],
    staffRoles: [
      { key: 'tour_manager', label: 'Тур-менеджер', icon: '🗺️' },
      { key: 'guide', label: 'Гид', icon: '🧭' },
      { key: 'transfer', label: 'Трансфер', icon: '🚐' },
    ],
    integrationHints: ['Amadeus', 'Sirena', 'Bronevik', 'Avia API'],
  },

  // ── DEFAULT ─────────────────────────────────
  default: {
    label: 'Настройки бизнеса', icon: '⚙️', categoryLabel: 'Бизнес',
    extraFields: [
      { key: 'aiConstructorEnabled', label: 'AI-конструктор', type: 'toggle' },
      { key: 'reserveMinutes', label: 'Время резерва (мин)', type: 'number' },
    ],
    staffRoles: [
      { key: 'employee', label: 'Сотрудник', icon: '👤' },
      { key: 'manager', label: 'Менеджер', icon: '📋' },
    ],
    integrationHints: ['1С', 'CRM', 'Касса'],
  },
}

const vc = computed<VerticalSettingsConfig>(() =>
  VERTICAL_SETTINGS_CONFIG[props.vertical] ?? VERTICAL_SETTINGS_CONFIG.default
)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  CATEGORIES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const SETTINGS_CATEGORIES = computed<CategoryItem[]>(() => [
  { key: 'general',        label: 'Общие',          icon: '⚙️' },
  { key: 'profile',        label: 'Профиль',        icon: '🏢' },
  { key: 'staff',          label: 'Персонал',       icon: '👥' },
  { key: 'finance',        label: 'Финансы',        icon: '💳' },
  { key: 'integrations',   label: 'Интеграции',     icon: '🔗', badge: props.integrations.filter(i => i.status === 'error').length || undefined },
  { key: 'security',       label: 'Безопасность',   icon: '🛡️', badge: props.security.activeSessions.length || undefined },
  { key: 'notifications',  label: 'Уведомления',    icon: '🔔' },
  { key: 'vertical',       label: vc.value.categoryLabel, icon: vc.value.icon },
])

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STATE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const rootEl               = ref<HTMLElement | null>(null)
const isFullscreen         = ref(false)
const activeCategory       = ref<SettingsCategory>('general')
const showMobileDrawer     = ref(false)
const showConfirmModal     = ref(false)
const showApiKeyModal      = ref(false)
const showDeleteConfirm    = ref(false)
const pendingSaveCategory  = ref<SettingsCategory | null>(null)
const isSaving             = ref(false)
const saveSuccess          = ref(false)
const newApiKeyName        = ref('')
const deleteTarget         = ref<{ type: string; id: string } | null>(null)
const newIpAddress         = ref('')

// Local form copies (reactive)
const formGeneral = reactive<GeneralSettings>({ ...props.general })
const formProfile = reactive<ProfileSettings>({
  ...props.profile,
  socialLinks: [...(props.profile.socialLinks ?? [])],
  workingHours: [...(props.profile.workingHours ?? [])],
})
const formStaff         = reactive<StaffSettings>({ ...props.staffSettings, roles: [...(props.staffSettings.roles ?? [])] })
const formFinance       = reactive<FinanceSettings>({ ...props.finance, paymentMethods: [...(props.finance.paymentMethods ?? [])] })
const formSecurity      = reactive<SecuritySettings>({ ...props.security, ipWhitelist: [...(props.security.ipWhitelist ?? [])], activeSessions: [...(props.security.activeSessions ?? [])] })
const formNotifications = reactive<NotificationSettings>({ ...props.notifications, rules: [...(props.notifications.rules ?? [])] })
const formVertical      = reactive<Record<string, unknown>>({})

// Track dirty state
const dirtyCategories = reactive<Set<SettingsCategory>>(new Set())

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  COMPUTED
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const hasDirtyChanges = computed(() => dirtyCategories.size > 0)

const activeIntegrations = computed(() => props.integrations.filter(i => i.status === 'active').length)
const errorIntegrations  = computed(() => props.integrations.filter(i => i.status === 'error').length)

const activeSessionsCount = computed(() => props.security.activeSessions.length)

const enabledNotifChannels = computed(() => {
  const channels: NotifChannel[] = []
  if (formNotifications.emailEnabled) channels.push('email')
  if (formNotifications.pushEnabled) channels.push('push')
  if (formNotifications.telegramEnabled) channels.push('telegram')
  if (formNotifications.smsEnabled) channels.push('sms')
  return channels
})

const CHANNEL_LABELS: Record<NotifChannel, { label: string; icon: string }> = {
  email:    { label: 'Email',    icon: '📧' },
  push:     { label: 'Push',     icon: '🔔' },
  telegram: { label: 'Telegram', icon: '✈️' },
  sms:      { label: 'SMS',      icon: '📱' },
}

const PAYOUT_SCHEDULE_LABELS: Record<string, string> = {
  daily:    'Ежедневно',
  weekly:   'Еженедельно',
  biweekly: 'Раз в 2 недели',
  monthly:  'Ежемесячно',
}

const TIMEZONE_OPTIONS = [
  { value: 'Europe/Moscow',       label: 'Москва (UTC+3)' },
  { value: 'Europe/Kaliningrad',  label: 'Калининград (UTC+2)' },
  { value: 'Europe/Samara',       label: 'Самара (UTC+4)' },
  { value: 'Asia/Yekaterinburg',  label: 'Екатеринбург (UTC+5)' },
  { value: 'Asia/Omsk',           label: 'Омск (UTC+6)' },
  { value: 'Asia/Krasnoyarsk',    label: 'Красноярск (UTC+7)' },
  { value: 'Asia/Irkutsk',        label: 'Иркутск (UTC+8)' },
  { value: 'Asia/Yakutsk',        label: 'Якутск (UTC+9)' },
  { value: 'Asia/Vladivostok',    label: 'Владивосток (UTC+10)' },
  { value: 'Asia/Kamchatka',      label: 'Камчатка (UTC+12)' },
]

const INTEGRATION_STATUS_MAP: Record<string, { label: string; dot: string; variant: string }> = {
  active:   { label: 'Активна',  dot: 'bg-emerald-400', variant: 'success' },
  inactive: { label: 'Отключена', dot: 'bg-zinc-500',   variant: 'neutral' },
  error:    { label: 'Ошибка',   dot: 'bg-rose-400',    variant: 'danger' },
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  WATCHERS (dirty tracking)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

watch(formGeneral, () => { dirtyCategories.add('general') }, { deep: true })
watch(formProfile, () => { dirtyCategories.add('profile') }, { deep: true })
watch(formStaff,   () => { dirtyCategories.add('staff') },   { deep: true })
watch(formFinance, () => { dirtyCategories.add('finance') }, { deep: true })
watch(formSecurity, () => { dirtyCategories.add('security') }, { deep: true })
watch(formNotifications, () => { dirtyCategories.add('notifications') }, { deep: true })
watch(formVertical, () => { dirtyCategories.add('vertical') }, { deep: true })

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  CATEGORY NAVIGATION
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function selectCategory(cat: SettingsCategory) {
  activeCategory.value = cat
  showMobileDrawer.value = false
  emit('category-change', cat)
}

function handleCategorySwitch(cat: SettingsCategory) {
  if (hasDirtyChanges.value && dirtyCategories.has(activeCategory.value)) {
    pendingSaveCategory.value = cat
    showConfirmModal.value = true
  } else {
    selectCategory(cat)
  }
}

function confirmDiscardAndSwitch() {
  discardCurrent()
  if (pendingSaveCategory.value) {
    selectCategory(pendingSaveCategory.value)
    pendingSaveCategory.value = null
  }
  showConfirmModal.value = false
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  SAVE / DISCARD
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function saveCurrentCategory() {
  const cat = activeCategory.value
  isSaving.value = true

  const dataMap: Record<SettingsCategory, Record<string, unknown>> = {
    general:       { ...formGeneral },
    profile:       { ...formProfile },
    staff:         { ...formStaff },
    finance:       { ...formFinance },
    integrations:  {},
    security:      { ...formSecurity },
    notifications: { ...formNotifications },
    vertical:      { ...formVertical },
  }

  emit('save', cat, dataMap[cat])

  setTimeout(() => {
    isSaving.value = false
    saveSuccess.value = true
    dirtyCategories.delete(cat)
    setTimeout(() => { saveSuccess.value = false }, 2000)
  }, 600)
}

function discardCurrent() {
  const cat = activeCategory.value
  dirtyCategories.delete(cat)
  emit('discard', cat)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  SECURITY HANDLERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function revokeSession(sessionId: string) {
  emit('revoke-session', sessionId)
}

function revokeAllSessions() {
  emit('revoke-all')
}

function addIpToWhitelist() {
  const ip = newIpAddress.value.trim()
  if (ip && !formSecurity.ipWhitelist.includes(ip)) {
    formSecurity.ipWhitelist.push(ip)
    emit('add-ip-whitelist', ip)
    newIpAddress.value = ''
  }
}

function removeIp(ip: string) {
  formSecurity.ipWhitelist = formSecurity.ipWhitelist.filter(i => i !== ip)
  emit('remove-ip', ip)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  API KEY HANDLERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function createApiKey() {
  if (!newApiKeyName.value.trim()) return
  emit('create-api-key', newApiKeyName.value.trim(), ['orders.read', 'orders.write'])
  newApiKeyName.value = ''
  showApiKeyModal.value = false
}

function revokeApiKey(keyId: string) {
  deleteTarget.value = { type: 'api_key', id: keyId }
  showDeleteConfirm.value = true
}

function confirmDelete() {
  if (deleteTarget.value?.type === 'api_key') {
    emit('revoke-api-key', deleteTarget.value.id)
  }
  deleteTarget.value = null
  showDeleteConfirm.value = false
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  INTEGRATION HANDLERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function toggleIntegration(key: string, enabled: boolean) {
  emit('enable-integration', key, enabled)
}

function testIntegration(key: string) {
  emit('test-integration', key)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  NOTIFICATION HANDLERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function toggleNotifChannel(channel: NotifChannel, enabled: boolean) {
  const key = `${channel}Enabled` as keyof NotificationSettings
  ;(formNotifications as Record<string, unknown>)[key] = enabled
}

function toggleRuleChannel(ruleIdx: number, channel: NotifChannel) {
  const rule = formNotifications.rules[ruleIdx]
  if (rule) {
    rule.channels[channel] = !rule.channels[channel]
    emit('update-notif-rule', rule.event, { ...rule.channels })
  }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  SOCIAL LINKS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function addSocialLink() {
  formProfile.socialLinks.push({ platform: '', url: '' })
}

function removeSocialLink(idx: number) {
  formProfile.socialLinks.splice(idx, 1)
}

const SOCIAL_PLATFORMS = [
  { value: 'vk',        label: 'ВКонтакте',  icon: '🟦' },
  { value: 'telegram',  label: 'Telegram',    icon: '✈️' },
  { value: 'instagram', label: 'Instagram',   icon: '📸' },
  { value: 'youtube',   label: 'YouTube',     icon: '▶️' },
  { value: 'tiktok',    label: 'TikTok',      icon: '🎵' },
  { value: 'whatsapp',  label: 'WhatsApp',    icon: '💬' },
  { value: 'website',   label: 'Сайт',        icon: '🌐' },
]

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  FULLSCREEN
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

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

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  RIPPLE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function ripple(e: MouseEvent) {
  const target = e.currentTarget as HTMLElement
  const rect = target.getBoundingClientRect()
  const diameter = Math.max(rect.width, rect.height) * 2
  const el = document.createElement('span')
  el.className = 'absolute rounded-full bg-white/10 pointer-events-none animate-[ripple-st_0.6s_ease-out]'
  el.style.cssText = `inline-size:${diameter}px;block-size:${diameter}px;inset-inline-start:${e.clientX - rect.left - diameter / 2}px;inset-block-start:${e.clientY - rect.top - diameter / 2}px;`
  target.appendChild(el)
  setTimeout(() => el.remove(), 650)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  FORMATTERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

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

function maskApiKey(key: string): string {
  if (key.length <= 12) return '••••••••'
  return key.slice(0, 8) + '••••••••' + key.slice(-4)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  LIFECYCLE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

onMounted(() => {
  document.addEventListener('fullscreenchange', onFullscreenChange)
  nextTick(() => {
    dirtyCategories.clear()
  })
})

onBeforeUnmount(() => {
  document.removeEventListener('fullscreenchange', onFullscreenChange)
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
         1. HEADER
    ═══════════════════════════════════════════════ -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-3">
        <span class="text-2xl">{{ vc.icon }}</span>
        <div>
          <h1 class="text-xl font-bold text-(--t-text)">{{ vc.label }}</h1>
          <p class="text-xs text-(--t-text-3)">
            {{ auth.tenantName }}
            · {{ auth.isB2BMode ? 'B2B' : 'B2C' }}
            <span v-if="hasDirtyChanges" class="ml-2 text-amber-400">● Есть несохранённые изменения</span>
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2 flex-wrap">
        <!-- Save -->
        <VButton
          v-if="dirtyCategories.has(activeCategory)"
          variant="primary"
          size="sm"
          :disabled="isSaving"
          @click="saveCurrentCategory"
        >
          <template v-if="isSaving">
            <svg class="animate-spin w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            Сохранение…
          </template>
          <template v-else-if="saveSuccess">✅ Сохранено</template>
          <template v-else>💾 Сохранить</template>
        </VButton>

        <!-- Discard -->
        <VButton
          v-if="dirtyCategories.has(activeCategory)"
          variant="ghost"
          size="sm"
          @click="discardCurrent"
        >
          ↩️ Отменить
        </VButton>

        <!-- Mobile menu -->
        <button
          class="sm:hidden w-9 h-9 rounded-xl flex items-center justify-center
                 bg-(--t-surface) border border-(--t-border) text-(--t-text-2)
                 hover:text-(--t-text) transition-all active:scale-95"
          @click="showMobileDrawer = !showMobileDrawer"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

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
         2. LAYOUT: SIDEBAR + CONTENT
    ═══════════════════════════════════════════════ -->
    <div class="flex gap-4 min-h-0">

      <!-- ─── SIDEBAR (desktop) ─── -->
      <aside class="hidden sm:flex flex-col gap-1 shrink-0 w-56 rounded-2xl border border-(--t-border)
                    bg-(--t-surface) backdrop-blur-xl p-3 overflow-y-auto self-start sticky top-4">
        <button
          v-for="cat in SETTINGS_CATEGORIES"
          :key="cat.key"
          :class="[
            'relative overflow-hidden flex items-center gap-3 w-full rounded-xl px-3 py-2.5 text-sm',
            'transition-all duration-200',
            activeCategory === cat.key
              ? 'bg-(--t-primary)/15 text-(--t-primary) font-semibold'
              : 'text-(--t-text-2) hover:text-(--t-text) hover:bg-(--t-card-hover)',
          ]"
          @click="handleCategorySwitch(cat.key)"
          @mousedown="ripple($event)"
        >
          <span class="text-base shrink-0">{{ cat.icon }}</span>
          <span class="truncate">{{ cat.label }}</span>
          <span
            v-if="dirtyCategories.has(cat.key)"
            class="ml-auto w-2 h-2 rounded-full bg-amber-400 shrink-0"
          />
          <span
            v-else-if="cat.badge"
            class="ml-auto text-[10px] font-bold bg-rose-500/20 text-rose-400 rounded-full px-1.5 py-0.5"
          >
            {{ cat.badge }}
          </span>
        </button>
      </aside>

      <!-- ─── CONTENT ─── -->
      <div class="flex-1 min-w-0">

        <!-- ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌
             GENERAL
        ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌ -->
        <div v-if="activeCategory === 'general'" class="flex flex-col gap-4">
          <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-5">
            <h2 class="text-base font-bold text-(--t-text) mb-4">⚙️ Общие настройки</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Business name -->
              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Название бизнеса</label>
                <input
                  v-model="formGeneral.businessName"
                  type="text"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) placeholder:text-(--t-text-3)
                         focus:outline-none focus:border-(--t-primary)/60
                         focus:shadow-[0_0_20px_var(--t-glow)] transition-all duration-200"
                  placeholder="Введите название"
                />
              </div>

              <!-- Timezone -->
              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Часовой пояс</label>
                <select
                  v-model="formGeneral.timezone"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
                >
                  <option v-for="tz in TIMEZONE_OPTIONS" :key="tz.value" :value="tz.value">{{ tz.label }}</option>
                </select>
              </div>

              <!-- Language -->
              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Язык</label>
                <select
                  v-model="formGeneral.language"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
                >
                  <option value="ru">🇷🇺 Русский</option>
                  <option value="en">🇬🇧 English</option>
                  <option value="kz">🇰🇿 Қазақша</option>
                  <option value="uz">🇺🇿 O'zbek</option>
                </select>
              </div>

              <!-- Currency -->
              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Валюта</label>
                <select
                  v-model="formGeneral.currency"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
                >
                  <option value="RUB">🇷🇺 Рубль (₽)</option>
                  <option value="KZT">🇰🇿 Тенге (₸)</option>
                  <option value="UZS">🇺🇿 Сум (сўм)</option>
                  <option value="USD">🇺🇸 Доллар ($)</option>
                </select>
              </div>

              <!-- Description (full width) -->
              <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Описание</label>
                <textarea
                  v-model="formGeneral.description"
                  rows="3"
                  class="w-full rounded-xl px-3 py-2 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) placeholder:text-(--t-text-3)
                         focus:outline-none focus:border-(--t-primary)/60
                         focus:shadow-[0_0_20px_var(--t-glow)] transition-all duration-200 resize-none"
                  placeholder="Краткое описание вашего бизнеса"
                />
              </div>
            </div>

            <!-- Toggles row -->
            <div class="mt-5 flex flex-col gap-3">
              <h3 class="text-xs font-bold text-(--t-text-3) uppercase tracking-wider">Режимы</h3>

              <label class="flex items-center justify-between gap-3 rounded-xl bg-(--t-bg) border border-(--t-border) p-3 cursor-pointer group">
                <div>
                  <div class="text-sm font-medium text-(--t-text)">B2B-режим</div>
                  <div class="text-xs text-(--t-text-3)">Оптовые цены, кредитный лимит, отсрочка</div>
                </div>
                <div
                  :class="[
                    'relative shrink-0 w-11 h-6 rounded-full transition-colors duration-200 cursor-pointer',
                    formGeneral.isB2BEnabled ? 'bg-(--t-primary)' : 'bg-(--t-border)',
                  ]"
                  @click="formGeneral.isB2BEnabled = !formGeneral.isB2BEnabled; emit('toggle-b2b', formGeneral.isB2BEnabled)"
                >
                  <div
                    :class="[
                      'absolute inset-block-start-0.5 w-5 h-5 rounded-full bg-white shadow transition-all duration-200',
                      formGeneral.isB2BEnabled ? 'inset-inline-start-[22px]' : 'inset-inline-start-0.5',
                    ]"
                  />
                </div>
              </label>

              <label class="flex items-center justify-between gap-3 rounded-xl bg-(--t-bg) border border-(--t-border) p-3 cursor-pointer">
                <div>
                  <div class="text-sm font-medium text-(--t-text)">Публичный профиль</div>
                  <div class="text-xs text-(--t-text-3)">Отображается в каталоге маркетплейса</div>
                </div>
                <div
                  :class="[
                    'relative shrink-0 w-11 h-6 rounded-full transition-colors duration-200 cursor-pointer',
                    formGeneral.isPublic ? 'bg-(--t-primary)' : 'bg-(--t-border)',
                  ]"
                  @click="formGeneral.isPublic = !formGeneral.isPublic"
                >
                  <div
                    :class="[
                      'absolute inset-block-start-0.5 w-5 h-5 rounded-full bg-white shadow transition-all duration-200',
                      formGeneral.isPublic ? 'inset-inline-start-[22px]' : 'inset-inline-start-0.5',
                    ]"
                  />
                </div>
              </label>
            </div>
          </div>
        </div>

        <!-- ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌
             PROFILE
        ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌ -->
        <div v-else-if="activeCategory === 'profile'" class="flex flex-col gap-4">
          <!-- Contact info -->
          <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-5">
            <h2 class="text-base font-bold text-(--t-text) mb-4">🏢 Профиль бизнеса</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Телефон</label>
                <input v-model="formProfile.phone" type="tel"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all"
                  placeholder="+7 (___) ___-__-__" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Email</label>
                <input v-model="formProfile.email" type="email"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all"
                  placeholder="info@example.com" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Сайт</label>
                <input v-model="formProfile.website" type="url"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all"
                  placeholder="https://example.com" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Город</label>
                <input v-model="formProfile.city" type="text"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all"
                  placeholder="Москва" />
              </div>

              <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Адрес</label>
                <input v-model="formProfile.address" type="text"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all"
                  placeholder="ул. Примерная, д. 1" />
              </div>
            </div>
          </div>

          <!-- Social links -->
          <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-bold text-(--t-text)">🔗 Социальные сети</h3>
              <VButton variant="ghost" size="sm" @click="addSocialLink">+ Добавить</VButton>
            </div>

            <div v-if="formProfile.socialLinks.length" class="flex flex-col gap-3">
              <div
                v-for="(link, idx) in formProfile.socialLinks"
                :key="idx"
                class="flex items-center gap-3"
              >
                <select
                  v-model="link.platform"
                  class="h-9 rounded-xl px-2 text-xs bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 shrink-0 w-36"
                >
                  <option value="">Платформа</option>
                  <option v-for="sp in SOCIAL_PLATFORMS" :key="sp.value" :value="sp.value">
                    {{ sp.icon }} {{ sp.label }}
                  </option>
                </select>
                <input
                  v-model="link.url"
                  type="url"
                  class="flex-1 h-9 rounded-xl px-3 text-xs bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all"
                  placeholder="https://..."
                />
                <button
                  class="w-8 h-8 rounded-lg flex items-center justify-center text-xs text-rose-400
                         hover:bg-rose-500/10 transition-all shrink-0"
                  @click="removeSocialLink(idx)"
                >✕</button>
              </div>
            </div>
            <p v-else class="text-xs text-(--t-text-3)">Нет добавленных ссылок</p>
          </div>
        </div>

        <!-- ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌
             STAFF
        ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌ -->
        <div v-else-if="activeCategory === 'staff'" class="flex flex-col gap-4">
          <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-5">
            <h2 class="text-base font-bold text-(--t-text) mb-4">👥 Персонал и роли</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Макс. сотрудников</label>
                <input v-model.number="formStaff.maxEmployees" type="number" min="1"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Расписание по умолчанию</label>
                <input v-model="formStaff.defaultSchedule" type="text"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all"
                  placeholder="9:00-18:00" />
              </div>
            </div>

            <!-- Toggles -->
            <div class="flex flex-col gap-3">
              <label class="flex items-center justify-between gap-3 rounded-xl bg-(--t-bg) border border-(--t-border) p-3 cursor-pointer">
                <div>
                  <div class="text-sm font-medium text-(--t-text)">Согласование новых сотрудников</div>
                  <div class="text-xs text-(--t-text-3)">Требовать одобрения от администратора</div>
                </div>
                <div
                  :class="['relative shrink-0 w-11 h-6 rounded-full transition-colors duration-200 cursor-pointer',
                    formStaff.requireApproval ? 'bg-(--t-primary)' : 'bg-(--t-border)']"
                  @click="formStaff.requireApproval = !formStaff.requireApproval"
                >
                  <div :class="['absolute inset-block-start-0.5 w-5 h-5 rounded-full bg-white shadow transition-all duration-200',
                    formStaff.requireApproval ? 'inset-inline-start-[22px]' : 'inset-inline-start-0.5']" />
                </div>
              </label>

              <label class="flex items-center justify-between gap-3 rounded-xl bg-(--t-bg) border border-(--t-border) p-3 cursor-pointer">
                <div>
                  <div class="text-sm font-medium text-(--t-text)">KPI и метрики</div>
                  <div class="text-xs text-(--t-text-3)">Отслеживание эффективности каждого сотрудника</div>
                </div>
                <div
                  :class="['relative shrink-0 w-11 h-6 rounded-full transition-colors duration-200 cursor-pointer',
                    formStaff.kpiEnabled ? 'bg-(--t-primary)' : 'bg-(--t-border)']"
                  @click="formStaff.kpiEnabled = !formStaff.kpiEnabled"
                >
                  <div :class="['absolute inset-block-start-0.5 w-5 h-5 rounded-full bg-white shadow transition-all duration-200',
                    formStaff.kpiEnabled ? 'inset-inline-start-[22px]' : 'inset-inline-start-0.5']" />
                </div>
              </label>
            </div>
          </div>

          <!-- Roles grid -->
          <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-5">
            <h3 class="text-sm font-bold text-(--t-text) mb-3">Роли для вертикали «{{ vc.categoryLabel }}»</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
              <div
                v-for="role in vc.staffRoles"
                :key="role.key"
                class="rounded-xl border border-(--t-border) bg-(--t-bg) p-3 text-center
                       hover:border-(--t-primary)/30 transition-all duration-200 cursor-pointer group"
                @mousedown="ripple($event)"
              >
                <div class="text-2xl mb-1 transition-transform duration-200 group-hover:scale-110">{{ role.icon }}</div>
                <div class="text-xs font-medium text-(--t-text)">{{ role.label }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌
             FINANCE
        ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌ -->
        <div v-else-if="activeCategory === 'finance'" class="flex flex-col gap-4">
          <!-- Requisites -->
          <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-5">
            <h2 class="text-base font-bold text-(--t-text) mb-4">💳 Реквизиты и платежи</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Юр. лицо</label>
                <input v-model="formFinance.legalName" type="text"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all"
                  placeholder="ООО «Название»" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">ИНН</label>
                <input v-model="formFinance.inn" type="text" maxlength="12"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all font-mono"
                  placeholder="1234567890" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">КПП</label>
                <input v-model="formFinance.kpp" type="text" maxlength="9"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all font-mono"
                  placeholder="123456789" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">БИК</label>
                <input v-model="formFinance.bik" type="text" maxlength="9"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all font-mono"
                  placeholder="044525974" />
              </div>

              <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Расчётный счёт</label>
                <input v-model="formFinance.bankAccount" type="text" maxlength="20"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all font-mono"
                  placeholder="40702810938000012345" />
              </div>
            </div>
          </div>

          <!-- Payout & commission -->
          <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-5">
            <h3 class="text-sm font-bold text-(--t-text) mb-4">📊 Выплаты и комиссии</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">График выплат</label>
                <select
                  v-model="formFinance.payoutSchedule"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
                >
                  <option v-for="(label, key) in PAYOUT_SCHEDULE_LABELS" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Комиссия платформы (%)</label>
                <input v-model.number="formFinance.commissionRate" type="number" min="0" max="100" step="0.5"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all tabular-nums" />
              </div>
            </div>

            <!-- B2B fields -->
            <div v-if="formGeneral.isB2BEnabled" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-(--t-border)">
              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Кредитный лимит B2B (₽)</label>
                <input v-model.number="formFinance.b2bCreditLimit" type="number" min="0"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all tabular-nums" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Срок отсрочки (дней)</label>
                <input v-model.number="formFinance.b2bPaymentTerms" type="number" min="0" max="90"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all tabular-nums" />
              </div>
            </div>

            <!-- Auto payouts -->
            <label class="flex items-center justify-between gap-3 rounded-xl bg-(--t-bg) border border-(--t-border) p-3 cursor-pointer mt-4">
              <div>
                <div class="text-sm font-medium text-(--t-text)">Автовыплаты</div>
                <div class="text-xs text-(--t-text-3)">Автоматически выплачивать по графику</div>
              </div>
              <div
                :class="['relative shrink-0 w-11 h-6 rounded-full transition-colors duration-200 cursor-pointer',
                  formFinance.autoPayouts ? 'bg-(--t-primary)' : 'bg-(--t-border)']"
                @click="formFinance.autoPayouts = !formFinance.autoPayouts"
              >
                <div :class="['absolute inset-block-start-0.5 w-5 h-5 rounded-full bg-white shadow transition-all duration-200',
                  formFinance.autoPayouts ? 'inset-inline-start-[22px]' : 'inset-inline-start-0.5']" />
              </div>
            </label>
          </div>
        </div>

        <!-- ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌
             INTEGRATIONS
        ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌ -->
        <div v-else-if="activeCategory === 'integrations'" class="flex flex-col gap-4">
          <!-- Integration list -->
          <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-5">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-base font-bold text-(--t-text)">🔗 Интеграции</h2>
              <div class="flex items-center gap-2 text-xs text-(--t-text-3)">
                <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block" /> {{ activeIntegrations }} активных
                <span v-if="errorIntegrations" class="ml-2"><span class="w-2 h-2 rounded-full bg-rose-400 inline-block" /> {{ errorIntegrations }} с ошибкой</span>
              </div>
            </div>

            <div v-if="props.integrations.length" class="flex flex-col gap-3">
              <div
                v-for="intg in props.integrations"
                :key="intg.key"
                class="flex items-center gap-4 rounded-xl bg-(--t-bg) border border-(--t-border)
                       p-4 transition-all duration-200 hover:border-(--t-primary)/20"
              >
                <div class="text-2xl shrink-0">{{ intg.icon }}</div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-(--t-text)">{{ intg.label }}</span>
                    <VBadge
                      :text="INTEGRATION_STATUS_MAP[intg.status]?.label ?? intg.status"
                      :variant="INTEGRATION_STATUS_MAP[intg.status]?.variant ?? 'neutral'"
                      size="xs"
                    />
                  </div>
                  <p class="text-xs text-(--t-text-3) truncate mt-0.5">{{ intg.description }}</p>
                  <p v-if="intg.lastSync" class="text-[10px] text-(--t-text-3) mt-1">
                    Последняя синхронизация: {{ relativeTime(intg.lastSync) }}
                  </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <VButton variant="ghost" size="sm" @click="testIntegration(intg.key)">🔄 Тест</VButton>
                  <div
                    :class="['relative shrink-0 w-11 h-6 rounded-full transition-colors duration-200 cursor-pointer',
                      intg.status === 'active' ? 'bg-(--t-primary)' : 'bg-(--t-border)']"
                    @click="toggleIntegration(intg.key, intg.status !== 'active')"
                  >
                    <div :class="['absolute inset-block-start-0.5 w-5 h-5 rounded-full bg-white shadow transition-all duration-200',
                      intg.status === 'active' ? 'inset-inline-start-[22px]' : 'inset-inline-start-0.5']" />
                  </div>
                </div>
              </div>
            </div>
            <p v-else class="text-xs text-(--t-text-3)">Нет настроенных интеграций</p>

            <!-- Hints -->
            <div v-if="vc.integrationHints.length" class="mt-4 pt-4 border-t border-(--t-border)">
              <p class="text-xs text-(--t-text-3) mb-2">Рекомендуемые интеграции для «{{ vc.categoryLabel }}»:</p>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="hint in vc.integrationHints"
                  :key="hint"
                  class="px-3 py-1 text-xs rounded-full bg-(--t-primary)/10 text-(--t-primary) border border-(--t-primary)/20"
                >
                  {{ hint }}
                </span>
              </div>
            </div>
          </div>

          <!-- API keys -->
          <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-bold text-(--t-text)">🔑 API-ключи</h3>
              <VButton variant="primary" size="sm" @click="showApiKeyModal = true">+ Создать ключ</VButton>
            </div>

            <div v-if="props.apiKeys.length" class="flex flex-col gap-3">
              <div
                v-for="ak in props.apiKeys"
                :key="ak.id"
                class="flex items-center gap-3 rounded-xl bg-(--t-bg) border border-(--t-border) p-3"
              >
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-medium text-(--t-text)">{{ ak.name }}</div>
                  <div class="text-xs font-mono text-(--t-text-3) mt-0.5">{{ maskApiKey(ak.key) }}</div>
                  <div class="text-[10px] text-(--t-text-3) mt-1">
                    Создан {{ fmtDate(ak.createdAt) }}
                    <span v-if="ak.lastUsed"> · Использован {{ relativeTime(ak.lastUsed) }}</span>
                  </div>
                </div>
                <button
                  class="px-3 py-1.5 text-xs text-rose-400 rounded-lg hover:bg-rose-500/10 transition-all"
                  @click="revokeApiKey(ak.id)"
                >Отозвать</button>
              </div>
            </div>
            <p v-else class="text-xs text-(--t-text-3)">Нет активных API-ключей</p>
          </div>
        </div>

        <!-- ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌
             SECURITY
        ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌ -->
        <div v-else-if="activeCategory === 'security'" class="flex flex-col gap-4">
          <!-- 2FA -->
          <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-5">
            <h2 class="text-base font-bold text-(--t-text) mb-4">🛡️ Безопасность</h2>

            <label class="flex items-center justify-between gap-3 rounded-xl bg-(--t-bg) border border-(--t-border) p-3 cursor-pointer mb-3">
              <div>
                <div class="text-sm font-medium text-(--t-text)">Двухфакторная аутентификация (2FA)</div>
                <div class="text-xs text-(--t-text-3)">Дополнительная защита при входе</div>
              </div>
              <div
                :class="['relative shrink-0 w-11 h-6 rounded-full transition-colors duration-200 cursor-pointer',
                  formSecurity.twoFactorEnabled ? 'bg-emerald-500' : 'bg-(--t-border)']"
                @click="formSecurity.twoFactorEnabled = !formSecurity.twoFactorEnabled; emit('toggle-2fa', formSecurity.twoFactorEnabled)"
              >
                <div :class="['absolute inset-block-start-0.5 w-5 h-5 rounded-full bg-white shadow transition-all duration-200',
                  formSecurity.twoFactorEnabled ? 'inset-inline-start-[22px]' : 'inset-inline-start-0.5']" />
              </div>
            </label>

            <div v-if="formSecurity.twoFactorEnabled" class="mb-4">
              <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Метод 2FA</label>
              <select
                v-model="formSecurity.twoFactorMethod"
                class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                       text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
              >
                <option value="app">📱 Приложение (Google Authenticator)</option>
                <option value="sms">📲 SMS-код</option>
                <option value="email">📧 Email-код</option>
              </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Тайм-аут сессии (мин)</label>
                <input v-model.number="formSecurity.sessionTimeoutMin" type="number" min="5" max="1440"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all tabular-nums" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Макс. попыток входа</label>
                <input v-model.number="formSecurity.maxLoginAttempts" type="number" min="3" max="20"
                  class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                         text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all tabular-nums" />
              </div>
            </div>
          </div>

          <!-- IP whitelist -->
          <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-5">
            <h3 class="text-sm font-bold text-(--t-text) mb-3">🌐 IP-адреса (белый список)</h3>

            <div class="flex items-center gap-2 mb-3">
              <input
                v-model="newIpAddress"
                type="text"
                class="flex-1 h-9 rounded-xl px-3 text-xs bg-(--t-bg) border border-(--t-border)
                       text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all font-mono"
                placeholder="192.168.1.0/24"
                @keyup.enter="addIpToWhitelist"
              />
              <VButton variant="primary" size="sm" @click="addIpToWhitelist">Добавить</VButton>
            </div>

            <div v-if="formSecurity.ipWhitelist.length" class="flex flex-wrap gap-2">
              <span
                v-for="ip in formSecurity.ipWhitelist"
                :key="ip"
                class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-(--t-bg) border border-(--t-border) text-xs font-mono text-(--t-text)"
              >
                {{ ip }}
                <button class="text-rose-400 hover:text-rose-300 text-xs" @click="removeIp(ip)">✕</button>
              </span>
            </div>
            <p v-else class="text-xs text-(--t-text-3)">Все IP разрешены (белый список пуст)</p>
          </div>

          <!-- Active sessions -->
          <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-5">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-bold text-(--t-text)">📱 Активные сессии</h3>
              <VButton
                v-if="formSecurity.activeSessions.length > 1"
                variant="ghost"
                size="sm"
                @click="revokeAllSessions"
              >
                🚫 Завершить все
              </VButton>
            </div>

            <div v-if="formSecurity.activeSessions.length" class="flex flex-col gap-2">
              <div
                v-for="sess in formSecurity.activeSessions"
                :key="sess.id"
                :class="[
                  'flex items-center gap-3 rounded-xl p-3 border transition-all',
                  sess.current
                    ? 'bg-emerald-500/5 border-emerald-500/20'
                    : 'bg-(--t-bg) border-(--t-border)',
                ]"
              >
                <div class="text-lg shrink-0">{{ sess.device.includes('Mobile') ? '📱' : '💻' }}</div>
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-medium text-(--t-text)">
                    {{ sess.device }}
                    <VBadge v-if="sess.current" text="Текущая" variant="success" size="xs" class="ml-2" />
                  </div>
                  <div class="text-xs text-(--t-text-3)">
                    IP: {{ sess.ip }} · {{ relativeTime(sess.lastActive) }}
                  </div>
                </div>
                <button
                  v-if="!sess.current"
                  class="px-3 py-1.5 text-xs text-rose-400 rounded-lg hover:bg-rose-500/10 transition-all shrink-0"
                  @click="revokeSession(sess.id)"
                >Завершить</button>
              </div>
            </div>
            <p v-else class="text-xs text-(--t-text-3)">Нет активных сессий</p>
          </div>
        </div>

        <!-- ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌
             NOTIFICATIONS
        ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌ -->
        <div v-else-if="activeCategory === 'notifications'" class="flex flex-col gap-4">
          <!-- Channels -->
          <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-5">
            <h2 class="text-base font-bold text-(--t-text) mb-4">🔔 Каналы уведомлений</h2>

            <div class="flex flex-col gap-3">
              <label
                v-for="(meta, ch) in CHANNEL_LABELS"
                :key="ch"
                class="flex items-center justify-between gap-3 rounded-xl bg-(--t-bg) border border-(--t-border) p-3 cursor-pointer"
              >
                <div class="flex items-center gap-3">
                  <span class="text-lg">{{ meta.icon }}</span>
                  <span class="text-sm font-medium text-(--t-text)">{{ meta.label }}</span>
                </div>
                <div
                  :class="['relative shrink-0 w-11 h-6 rounded-full transition-colors duration-200 cursor-pointer',
                    formNotifications[`${ch}Enabled` as keyof NotificationSettings] ? 'bg-(--t-primary)' : 'bg-(--t-border)']"
                  @click="toggleNotifChannel(ch as NotifChannel, !(formNotifications[`${ch}Enabled` as keyof NotificationSettings]))"
                >
                  <div :class="['absolute inset-block-start-0.5 w-5 h-5 rounded-full bg-white shadow transition-all duration-200',
                    formNotifications[`${ch}Enabled` as keyof NotificationSettings] ? 'inset-inline-start-[22px]' : 'inset-inline-start-0.5']" />
                </div>
              </label>
            </div>

            <!-- Telegram bot ID -->
            <div v-if="formNotifications.telegramEnabled" class="mt-4">
              <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Telegram Bot ID</label>
              <input v-model="formNotifications.telegramBotId" type="text"
                class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                       text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all font-mono"
                placeholder="bot123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11" />
            </div>
          </div>

          <!-- Event rules -->
          <div v-if="formNotifications.rules.length" class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-5">
            <h3 class="text-sm font-bold text-(--t-text) mb-4">📋 Настройка событий</h3>

            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="text-(--t-text-3) border-b border-(--t-border)">
                    <th class="text-start py-2 px-3 font-semibold">Событие</th>
                    <th v-for="(meta, ch) in CHANNEL_LABELS" :key="ch" class="text-center py-2 px-2 font-semibold">
                      {{ meta.icon }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(rule, idx) in formNotifications.rules"
                    :key="rule.event"
                    class="border-b border-(--t-border)/50 hover:bg-(--t-card-hover) transition-colors"
                  >
                    <td class="py-2.5 px-3 text-(--t-text)">{{ rule.label }}</td>
                    <td
                      v-for="ch in (['email', 'push', 'telegram', 'sms'] as NotifChannel[])"
                      :key="ch"
                      class="text-center py-2.5 px-2"
                    >
                      <button
                        :class="[
                          'w-6 h-6 rounded-lg inline-flex items-center justify-center transition-all',
                          rule.channels[ch]
                            ? 'bg-(--t-primary)/20 text-(--t-primary)'
                            : 'bg-(--t-border)/30 text-(--t-text-3) hover:bg-(--t-border)/50',
                        ]"
                        @click="toggleRuleChannel(idx, ch)"
                      >
                        {{ rule.channels[ch] ? '✓' : '—' }}
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌
             VERTICAL
        ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌ -->
        <div v-else-if="activeCategory === 'vertical'" class="flex flex-col gap-4">
          <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl p-5">
            <h2 class="text-base font-bold text-(--t-text) mb-4">
              {{ vc.icon }} Настройки вертикали «{{ vc.categoryLabel }}»
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-for="field in vc.extraFields" :key="field.key">
                <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">{{ field.label }}</label>

                <!-- Toggle -->
                <template v-if="field.type === 'toggle'">
                  <div
                    :class="['relative shrink-0 w-11 h-6 rounded-full transition-colors duration-200 cursor-pointer',
                      formVertical[field.key] ? 'bg-(--t-primary)' : 'bg-(--t-border)']"
                    @click="formVertical[field.key] = !formVertical[field.key]"
                  >
                    <div :class="['absolute inset-block-start-0.5 w-5 h-5 rounded-full bg-white shadow transition-all duration-200',
                      formVertical[field.key] ? 'inset-inline-start-[22px]' : 'inset-inline-start-0.5']" />
                  </div>
                </template>

                <!-- Select -->
                <template v-else-if="field.type === 'select' && field.options">
                  <select
                    v-model="formVertical[field.key]"
                    class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                           text-(--t-text) focus:outline-none focus:border-(--t-primary)/60"
                  >
                    <option v-for="opt in field.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                  </select>
                </template>

                <!-- Number -->
                <template v-else-if="field.type === 'number'">
                  <input
                    v-model.number="formVertical[field.key]"
                    type="number"
                    class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                           text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all tabular-nums"
                  />
                </template>

                <!-- Text -->
                <template v-else>
                  <input
                    v-model="formVertical[field.key]"
                    type="text"
                    class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                           text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all"
                  />
                </template>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ═══════════════════════════════════════════════
         MOBILE DRAWER
    ═══════════════════════════════════════════════ -->
    <Transition name="drawer-st">
      <div
        v-if="showMobileDrawer"
        class="fixed inset-0 z-80 sm:hidden"
      >
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showMobileDrawer = false" />
        <div class="absolute inset-block-start-0 inset-inline-start-0 inset-block-end-0 w-64
                    bg-(--t-surface) border-e border-(--t-border) p-4 overflow-y-auto">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-(--t-text)">Настройки</h3>
            <button class="text-(--t-text-3) hover:text-(--t-text)" @click="showMobileDrawer = false">✕</button>
          </div>
          <div class="flex flex-col gap-1">
            <button
              v-for="cat in SETTINGS_CATEGORIES"
              :key="cat.key"
              :class="[
                'relative overflow-hidden flex items-center gap-3 w-full rounded-xl px-3 py-2.5 text-sm transition-all duration-200',
                activeCategory === cat.key
                  ? 'bg-(--t-primary)/15 text-(--t-primary) font-semibold'
                  : 'text-(--t-text-2) hover:text-(--t-text) hover:bg-(--t-card-hover)',
              ]"
              @click="handleCategorySwitch(cat.key)"
              @mousedown="ripple($event)"
            >
              <span class="text-base shrink-0">{{ cat.icon }}</span>
              <span class="truncate">{{ cat.label }}</span>
              <span v-if="dirtyCategories.has(cat.key)" class="ml-auto w-2 h-2 rounded-full bg-amber-400 shrink-0" />
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ═══════════════════════════════════════════════
         CONFIRM DISCARD MODAL
    ═══════════════════════════════════════════════ -->
    <VModal :show="showConfirmModal" title="Несохранённые изменения" size="sm" @close="showConfirmModal = false">
      <template #default>
        <p class="text-sm text-(--t-text-2)">
          У вас есть несохранённые изменения. Отменить их и перейти к другому разделу?
        </p>
      </template>
      <template #footer>
        <VButton variant="ghost" size="sm" @click="showConfirmModal = false">Остаться</VButton>
        <VButton variant="danger" size="sm" @click="confirmDiscardAndSwitch">Отменить и перейти</VButton>
      </template>
    </VModal>

    <!-- ═══════════════════════════════════════════════
         CREATE API KEY MODAL
    ═══════════════════════════════════════════════ -->
    <VModal :show="showApiKeyModal" title="🔑 Новый API-ключ" size="sm" @close="showApiKeyModal = false">
      <template #default>
        <div>
          <label class="block text-xs font-semibold text-(--t-text-2) mb-1.5">Название ключа</label>
          <input
            v-model="newApiKeyName"
            type="text"
            class="w-full h-10 rounded-xl px-3 text-sm bg-(--t-bg) border border-(--t-border)
                   text-(--t-text) focus:outline-none focus:border-(--t-primary)/60 transition-all"
            placeholder="Интеграция с 1С"
            @keyup.enter="createApiKey"
          />
        </div>
      </template>
      <template #footer>
        <VButton variant="ghost" size="sm" @click="showApiKeyModal = false">Отмена</VButton>
        <VButton variant="primary" size="sm" :disabled="!newApiKeyName.trim()" @click="createApiKey">Создать</VButton>
      </template>
    </VModal>

    <!-- ═══════════════════════════════════════════════
         DELETE CONFIRM MODAL
    ═══════════════════════════════════════════════ -->
    <VModal :show="showDeleteConfirm" title="⚠️ Подтверждение" size="sm" @close="showDeleteConfirm = false">
      <template #default>
        <p class="text-sm text-(--t-text-2)">Вы уверены? Это действие нельзя отменить.</p>
      </template>
      <template #footer>
        <VButton variant="ghost" size="sm" @click="showDeleteConfirm = false">Отмена</VButton>
        <VButton variant="danger" size="sm" @click="confirmDelete">Удалить</VButton>
      </template>
    </VModal>

    <!-- ═══════════════════════════════════════════════
         LOADING OVERLAY
    ═══════════════════════════════════════════════ -->
    <Transition name="fade-st">
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
          <span class="text-sm font-medium">Загрузка настроек...</span>
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
@keyframes ripple-st {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* Fade transition */
.fade-st-enter-active,
.fade-st-leave-active {
  transition: opacity 0.3s ease;
}
.fade-st-enter-from,
.fade-st-leave-to {
  opacity: 0;
}

/* Drawer transition */
.drawer-st-enter-active,
.drawer-st-leave-active {
  transition: opacity 0.3s ease;
}
.drawer-st-enter-active > :last-child,
.drawer-st-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.drawer-st-enter-from,
.drawer-st-leave-to {
  opacity: 0;
}
.drawer-st-enter-from > :last-child,
.drawer-st-leave-to > :last-child {
  transform: translateX(-100%);
}

/* Tabular nums */
.tabular-nums {
  font-variant-numeric: tabular-nums;
}

/* Custom scrollbar */
aside::-webkit-scrollbar { inline-size: 4px; }
aside::-webkit-scrollbar-track { background: transparent; }
aside::-webkit-scrollbar-thumb { background: var(--t-border); border-radius: 999px; }

.overflow-x-auto::-webkit-scrollbar { block-size: 5px; }
.overflow-x-auto::-webkit-scrollbar-track { background: transparent; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: var(--t-border); border-radius: 9999px; }
</style>
