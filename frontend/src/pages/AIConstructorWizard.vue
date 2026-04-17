<script setup lang="ts">
/**
 * AIConstructorWizard — универсальный многошаговый AI-конструктор.
 *
 * Шаги:
 *   1. Upload   — загрузка фото / ввод параметров
 *   2. Analyze  — индикатор анализа (Vision API)
 *   3. Results  — рекомендации, AR-ссылки, цены
 *   4. Saved    — дизайн сохранён в профиль
 *
 * Открывается через custom event «ai-constructor-open»,
 * который диспатчит Livewire-компонент AIConstructorButton.
 *
 * @see app/Livewire/Shared/AIConstructorButton.php
 */
import { ref, computed, onMounted, onUnmounted } from 'vue'

// ──────────────────────────── Types ────────────────────────────

type WizardStep = 'upload' | 'analyze' | 'results' | 'saved'
type Vertical = 'beauty' | 'fashion' | 'food' | 'furniture' | 'fitness' | 'hotel' | 'travel' |
  'weddingplanning' | 'wallet' | 'veterinary' | 'veganproducts' | 'toysandgames' | 'tickets' | 'taxi' |
  'staff' | 'sportsnutrition' | 'shorttermrentals' | 'sports' | 'referral' | 'recommendation' |
  'realestate' | 'promocampaigns' | 'photography' | 'pharmacy' | 'personaldevelopment' | 'pet' |
  'payment' | 'officecatering' | 'partysupplies' | 'musicandinstruments' | 'medical' | 'meatshops' |
  'marketplace' | 'luxury' | 'logistics' | 'legal' | 'inventory' | 'insurance' | 'householdgoods' |
  'homeservices' | 'hobbyandcraft' | 'groceryanddelivery' | 'geologistics' | 'geo' | 'gardening' |
  'freelance' | 'fraudml' | 'flowers' | 'finances' | 'farmdirect' | 'eventplanning' | 'electronics' |
  'education' | 'demandforecast' | 'delivery' | 'crm' | 'content' | 'consulting' |
  'constructionandrepair' | 'communication' | 'confectionery' | 'collectibles' | 'cleaningservices' |
  'carrental' | 'booksandliterature' | 'art' | 'auto' | 'analytics' | 'advertising' |
  'ai' | 'audit' | 'b2b' | 'bigdata' | 'bonuses' | 'cart' | 'commissions' | 'compliance' |
  'common' | 'userprofile' | 'webhooks' | string

interface AIRecommendation {
  productId: number
  name: string
  image?: string
  amountRub: number
  b2bAmountRub?: number
  inStock: boolean
  arTryOnUrl?: string
  confidence: number
}

interface AIConstructionResult {
  vertical: Vertical
  type: string
  payload: Record<string, unknown>
  suggestions: AIRecommendation[]
  confidenceScore: number
  correlationId: string
  arLink?: string
  totalCostRub?: number
}

interface OpenEvent extends CustomEvent {
  detail: {
    vertical: Vertical
    isB2B: boolean
    correlationId: string
  }
}

// ──────────────────────────── State ────────────────────────────

const isOpen       = ref<boolean>(false)
const currentStep  = ref<WizardStep>('upload')
const vertical     = ref<Vertical>('beauty')
const isB2B        = ref<boolean>(false)
const correlationId = ref<string>('')

const selectedFile = ref<File | null>(null)
const previewUrl   = ref<string | null>(null)
const parameters   = ref<Record<string, string>>({})
const isLoading    = ref<boolean>(false)
const errorMsg     = ref<string | null>(null)
const result       = ref<AIConstructionResult | null>(null)
const analyzeProgress = ref<number>(0)
let progressTimer: ReturnType<typeof setInterval> | null = null

// ──────────────────────────── Computed ────────────────────────────

const verticalMeta = computed(() => {
  const map: Record<string, { title: string; icon: string; prompt: string }> = {
    beauty: { title: 'AI-конструктор образа', icon: '✦', prompt: 'Загрузите селфи для анализа типа лица и подбора образа' },
    fashion: { title: 'AI-подбор стиля', icon: '👗', prompt: 'Загрузите фото для определения цветотипа и стиля' },
    food: { title: 'AI-конструктор меню', icon: '🍽️', prompt: 'Укажите ингредиенты и предпочтения' },
    furniture: { title: 'AI-дизайн интерьера', icon: '🪑', prompt: 'Загрузите фото комнаты' },
    fitness: { title: 'AI-план тренировок', icon: '💪', prompt: 'Загрузите фото для анализа телосложения' },
    hotel: { title: 'AI-подбор номера', icon: '🏨', prompt: 'Укажите предпочтения по проживанию' },
    travel: { title: 'AI-планировщик путешествий', icon: '✈️', prompt: 'Опишите желаемое путешествие' },
    weddingplanning: { title: 'AI-планировщик свадьбы', icon: '💒', prompt: 'Опишите идеи для свадьбы' },
    wallet: { title: 'AI-финансовый помощник', icon: '💳', prompt: 'Загрузите данные о финансах' },
    veterinary: { title: 'AI-ветеринарный помощник', icon: '🐾', prompt: 'Загрузите фото питомца' },
    veganproducts: { title: 'AI-веган-конструктор', icon: '🥬', prompt: 'Укажите предпочтения' },
    toysandgames: { title: 'AI-подбор игрушек', icon: '🎮', prompt: 'Укажите возраст и интересы' },
    tickets: { title: 'AI-подбор билетов', icon: '🎫', prompt: 'Укажите мероприятие' },
    taxi: { title: 'AI-оптимизатор такси', icon: '🚕', prompt: 'Укажите маршрут' },
    staff: { title: 'AI-подбор персонала', icon: '👥', prompt: 'Опишите требования' },
    sportsnutrition: { title: 'AI-спортпит', icon: '💊', prompt: 'Укажите цели тренировок' },
    shorttermrentals: { title: 'AI-подбор жилья', icon: '🏠', prompt: 'Укажите предпочтения' },
    sports: { title: 'AI-спорт-конструктор', icon: '⚽', prompt: 'Укажите вид спорта' },
    referral: { title: 'AI-реферальная система', icon: '🔗', prompt: 'Настройте программу лояльности' },
    recommendation: { title: 'AI-рекомендации', icon: '💡', prompt: 'Укажите контекст' },
    realestate: { title: 'AI-дизайн квартиры', icon: '🏢', prompt: 'Загрузите план или фото' },
    promocampaigns: { title: 'AI-маркетинговые кампании', icon: '📢', prompt: 'Опишите продукт' },
    photography: { title: 'AI-фотограф', icon: '📷', prompt: 'Загрузите фото для обработки' },
    pharmacy: { title: 'AI-аптека', icon: '💊', prompt: 'Опишите симптомы' },
    personaldevelopment: { title: 'AI-коуч развития', icon: '📚', prompt: 'Укажите цели' },
    pet: { title: 'AI-уход за питомцами', icon: '🐕', prompt: 'Загрузите фото питомца' },
    payment: { title: 'AI-платежи', icon: '💳', prompt: 'Настройте платежи' },
    officecatering: { title: 'AI-корпоративное питание', icon: '🍱', prompt: 'Укажите количество человек' },
    partysupplies: { title: 'AI-праздничные товары', icon: '🎉', prompt: 'Опишите мероприятие' },
    musicandinstruments: { title: 'AI-музыка', icon: '🎵', prompt: 'Загрузите аудио или опишите стиль' },
    medical: { title: 'AI-медпомощник', icon: '🏥', prompt: 'Опишите симптомы' },
    meatshops: { title: 'AI-мясной магазин', icon: '🥩', prompt: 'Укажите предпочтения' },
    marketplace: { title: 'AI-маркетплейс', icon: '🛒', prompt: 'Опишите потребности' },
    luxury: { title: 'AI-люкс товары', icon: '💎', prompt: 'Укажите предпочтения' },
    logistics: { title: 'AI-логистика', icon: '🚚', prompt: 'Укажите маршрут' },
    legal: { title: 'AI-юридические документы', icon: '⚖️', prompt: 'Опишите тип документа' },
    inventory: { title: 'AI-инвентарь', icon: '📦', prompt: 'Загрузите фото склада' },
    insurance: { title: 'AI-страхование', icon: '🛡️', prompt: 'Укажите тип страхования' },
    householdgoods: { title: 'AI-товары для дома', icon: '🏠', prompt: 'Загрузите фото комнаты' },
    homeservices: { title: 'AI-бытовые услуги', icon: '🔧', prompt: 'Опишите задачу' },
    hobbyandcraft: { title: 'AI-хобби и рукоделие', icon: '🎨', prompt: 'Укажите интерес' },
    groceryanddelivery: { title: 'AI-продукты', icon: '🛒', prompt: 'Укажите список продуктов' },
    geologistics: { title: 'AI-гео-логистика', icon: '🗺️', prompt: 'Укажите маршрут' },
    geo: { title: 'AI-геолокация', icon: '📍', prompt: 'Укажите локацию' },
    gardening: { title: 'AI-садоводство', icon: '🌱', prompt: 'Загрузите фото сада' },
    freelance: { title: 'AI-фриланс проекты', icon: '💼', prompt: 'Опишите проект' },
    fraudml: { title: 'AI-детекция мошенничества', icon: '🔒', prompt: 'Загрузите данные' },
    flowers: { title: 'AI-букеты', icon: '💐', prompt: 'Укажите повод' },
    finances: { title: 'AI-финансовый советник', icon: '💰', prompt: 'Укажите финансовые цели' },
    farmdirect: { title: 'AI-фермерские продукты', icon: '🌾', prompt: 'Укажите предпочтения' },
    eventplanning: { title: 'AI-планировщик событий', icon: '📅', prompt: 'Опишите мероприятие' },
    electronics: { title: 'AI-электроника', icon: '📱', prompt: 'Укажите требования' },
    education: { title: 'AI-обучение', icon: '🎓', prompt: 'Укажите тему обучения' },
    demandforecast: { title: 'AI-прогноз спроса', icon: '📈', prompt: 'Загрузите данные продаж' },
    delivery: { title: 'AI-доставка', icon: '🚚', prompt: 'Укажите маршрут' },
    crm: { title: 'AI-CRM', icon: '👥', prompt: 'Загрузите данные клиентов' },
    content: { title: 'AI-контент', icon: '✍️', prompt: 'Опишите тему' },
    consulting: { title: 'AI-консалтинг', icon: '💼', prompt: 'Опишите проблему' },
    constructionandrepair: { title: 'AI-строительство', icon: '🏗️', prompt: 'Загрузите фото объекта' },
    communication: { title: 'AI-коммуникации', icon: '💬', prompt: 'Опишите контекст' },
    confectionery: { title: 'AI-кондитерские изделия', icon: '🍰', prompt: 'Укажите предпочтения' },
    collectibles: { title: 'AI-коллекционирование', icon: '🏆', prompt: 'Опишите интерес' },
    cleaningservices: { title: 'AI-уборка', icon: '🧹', prompt: 'Укажите площадь' },
    carrental: { title: 'AI-аренда авто', icon: '🚗', prompt: 'Укажите требования' },
    booksandliterature: { title: 'AI-книги', icon: '📖', prompt: 'Укажите жанр' },
    art: { title: 'AI-искусство', icon: '🎨', prompt: 'Загрузите изображение' },
    auto: { title: 'AI-авто', icon: '🚙', prompt: 'Загрузите фото авто' },
    analytics: { title: 'AI-аналитика', icon: '📊', prompt: 'Загрузите данные' },
    advertising: { title: 'AI-реклама', icon: '📣', prompt: 'Опишите продукт' },
    ai: { title: 'AI-конструктор', icon: '🤖', prompt: 'Загрузите файл или введите параметры' },
    audit: { title: 'AI-аудит', icon: '📋', prompt: 'Загрузите данные для аудита' },
    b2b: { title: 'AI-B2B', icon: '🏢', prompt: 'Опишите B2B контекст' },
    bigdata: { title: 'AI-BigData', icon: '📊', prompt: 'Загрузите данные' },
    bonuses: { title: 'AI-бонусы', icon: '🎁', prompt: 'Настройте бонусы' },
    cart: { title: 'AI-корзина', icon: '🛒', prompt: 'Опишите товары' },
    commissions: { title: 'AI-комиссии', icon: '💰', prompt: 'Укажите контекст' },
    compliance: { title: 'AI-комплаенс', icon: '⚖️', prompt: 'Опишите требования' },
    common: { title: 'AI-конструктор', icon: '⚡', prompt: 'Загрузите файл или введите параметры' },
    userprofile: { title: 'AI-профиль пользователя', icon: '👤', prompt: 'Опишите предпочтения' },
    webhooks: { title: 'AI-вебхуки', icon: '🔗', prompt: 'Настройте вебхуки' },
  }
  return map[vertical.value] ?? { title: 'AI-конструктор', icon: '⚡', prompt: 'Загрузите файл или введите параметры' }
})

const stepLabel = computed<string>(() => {
  const labels: Record<WizardStep, string> = {
    upload:  '1 / 4 — Загрузка',
    analyze: '2 / 4 — Анализ',
    results: '3 / 4 — Результаты',
    saved:   '4 / 4 — Сохранено',
  }
  return labels[currentStep.value]
})

// ──────────────────────────── Event bus ────────────────────────────

function onAIConstructorOpen(e: Event): void {
  const ev = e as OpenEvent
  vertical.value      = ev.detail?.vertical     ?? 'beauty'
  isB2B.value         = ev.detail?.isB2B        ?? false
  correlationId.value = ev.detail?.correlationId ?? crypto.randomUUID()

  selectedFile.value  = null
  previewUrl.value    = null
  parameters.value    = {}
  errorMsg.value      = null
  result.value        = null
  currentStep.value   = 'upload'
  isOpen.value        = true
}

function close(): void {
  isOpen.value = false
  stopProgress()
}

onMounted(() => {
  document.addEventListener('ai-constructor-open', onAIConstructorOpen)
  document.addEventListener('keydown', onKeyDown)
})
onUnmounted(() => {
  document.removeEventListener('ai-constructor-open', onAIConstructorOpen)
  document.removeEventListener('keydown', onKeyDown)
  stopProgress()
})

function onKeyDown(e: KeyboardEvent): void {
  if (e.key === 'Escape' && isOpen.value) close()
}

// ──────────────────────────── File handling ────────────────────────────

function onFileChange(e: Event): void {
  const input = e.target as HTMLInputElement
  const file  = input.files?.[0] ?? null
  if (!file) return

  selectedFile.value = file
  previewUrl.value   = URL.createObjectURL(file)
}

function onDrop(e: DragEvent): void {
  e.preventDefault()
  const file = e.dataTransfer?.files[0] ?? null
  if (!file) return
  selectedFile.value = file
  previewUrl.value   = URL.createObjectURL(file)
}

// ──────────────────────────── Analysis flow ────────────────────────────

async function startAnalysis(): Promise<void> {
  if (!selectedFile.value && !Object.keys(parameters.value).length) {
    errorMsg.value = 'Загрузите фото или введите параметры'
    return
  }

  errorMsg.value = null
  currentStep.value = 'analyze'
  isLoading.value = true
  analyzeProgress.value = 0

  startProgress()

  try {
    const formData = new FormData()
    if (selectedFile.value) formData.append('photo', selectedFile.value)
    formData.append('vertical', vertical.value)
    formData.append('is_b2b', isB2B.value ? '1' : '0')
    formData.append('parameters', JSON.stringify(parameters.value))

    const response = await fetch('/api/ai-constructor/analyze', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN':    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
        'X-Correlation-ID': correlationId.value,
      },
      body: formData,
    })

    if (!response.ok) throw new Error(`HTTP ${response.status}`)

    result.value      = await response.json() as AIConstructionResult
    currentStep.value = 'results'
  } catch (err) {
    errorMsg.value = err instanceof Error ? err.message : 'Ошибка анализа. Попробуйте снова.'
    currentStep.value = 'upload'
  } finally {
    isLoading.value = false
    stopProgress()
  }
}

async function saveToProfile(): Promise<void> {
  if (!result.value) return

  isLoading.value = true
  try {
    await fetch('/api/ai-constructor/save', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN':    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
        'X-Correlation-ID': correlationId.value,
      },
      body: JSON.stringify({ result: result.value }),
    })
    currentStep.value = 'saved'
  } catch {
    errorMsg.value = 'Не удалось сохранить дизайн'
  } finally {
    isLoading.value = false
  }
}

function addToCart(item: AIRecommendation): void {
  // Dispatch Livewire event to CartService (Livewire 3 listens on window)
  window.dispatchEvent(new CustomEvent('livewire:dispatch', {
    detail: { name: 'cart-add', params: { productId: item.productId, correlationId: correlationId.value } },
  }))
}

// ──────────────────────────── Progress bar ────────────────────────────

function startProgress(): void {
  analyzeProgress.value = 0
  progressTimer = setInterval(() => {
    if (analyzeProgress.value < 90) {
      analyzeProgress.value += Math.random() * 8
    }
  }, 400)
}

function stopProgress(): void {
  if (progressTimer !== null) {
    clearInterval(progressTimer)
    progressTimer = null
  }
  analyzeProgress.value = 100
}
</script>

<template>
  <Teleport to="body">
    <Transition name="wizard-fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="`ai-wizard-title`"
      >
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-black/60 backdrop-blur-sm"
          @click="close"
          aria-hidden="true"
        />

        <!-- Panel -->
        <div class="relative w-full sm:max-w-2xl max-h-[90vh] overflow-y-auto
                    bg-[#0d0d1a] border border-white/10 rounded-t-3xl sm:rounded-3xl
                    shadow-[0_-8px_40px_rgba(99,102,241,0.15)] sm:shadow-[0_8px_40px_rgba(99,102,241,0.2)]">

          <!-- Header -->
          <div class="sticky top-0 z-10 flex items-center justify-between
                      px-5 pt-5 pb-4 bg-[#0d0d1a]/95 backdrop-blur-xl border-b border-white/5">
            <div>
              <p class="text-[10px] font-medium text-indigo-400 tracking-widest uppercase mb-0.5">{{ stepLabel }}</p>
              <h2 id="ai-wizard-title" class="text-base font-bold text-white flex items-center gap-2">
                <span aria-hidden="true">{{ verticalMeta.icon }}</span>
                {{ verticalMeta.title }}
              </h2>
            </div>
            <button
              @click="close"
              data-cy="wizard-close"
              class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
              aria-label="Закрыть конструктор"
            >
              <svg class="w-4 h-4 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Body -->
          <div class="p-5">

            <!-- Error banner -->
            <Transition name="slide-down">
              <div v-if="errorMsg" data-cy="error-banner" role="alert" class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-xs text-red-300 flex items-start gap-2">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                {{ errorMsg }}
              </div>
            </Transition>

            <!-- ── Step 1: Upload ── -->
            <div v-if="currentStep === 'upload'" data-cy="step-upload" class="space-y-5">
              <p class="text-xs text-white/50">{{ verticalMeta.prompt }}</p>

              <!-- Drop zone -->
              <label
                class="block w-full rounded-2xl border-2 border-dashed border-white/10 hover:border-indigo-500/50
                       bg-white/[0.02] transition-colors cursor-pointer overflow-hidden"
                @dragover.prevent
                @drop="onDrop"
              >
                <input type="file" class="sr-only" accept="image/*" @change="onFileChange" aria-label="Загрузить фото">
                <div v-if="previewUrl" class="relative aspect-video">
                  <img :src="previewUrl" class="w-full h-full object-cover" alt="Предпросмотр фото">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"/>
                  <span class="absolute bottom-3 left-3 text-xs text-white/70">Нажмите для замены</span>
                </div>
                <div v-else class="flex flex-col items-center justify-center py-10 gap-3 text-center">
                  <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 flex items-center justify-center" aria-hidden="true">
                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-white">Перетащите фото или нажмите</p>
                    <p class="text-xs text-white/40 mt-0.5">JPG, PNG, WEBP — до 10 МБ</p>
                  </div>
                </div>
              </label>

              <button
                @click="startAnalysis"
                data-cy="btn-analyze"
                :disabled="!selectedFile && !Object.keys(parameters).length"
                class="w-full py-3 rounded-2xl font-semibold text-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400
                       bg-gradient-to-r from-indigo-600 to-teal-500 text-white hover:opacity-90
                       disabled:opacity-40 disabled:cursor-not-allowed disabled:from-white/10 disabled:to-white/10 disabled:text-white/40"
              >
                Запустить AI-анализ
              </button>
            </div>

            <!-- ── Step 2: Analyze ── -->
            <div v-else-if="currentStep === 'analyze'" class="py-8 space-y-6 text-center">
              <div class="w-16 h-16 mx-auto rounded-3xl bg-gradient-to-br from-indigo-600/20 to-teal-500/20 flex items-center justify-center" aria-hidden="true">
                <svg class="w-8 h-8 text-indigo-400 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-semibold text-white mb-1">AI анализирует...</p>
                <p class="text-xs text-white/40">Обычно 5–15 секунд</p>
              </div>
              <!-- Progress bar -->
              <div class="w-full bg-white/5 rounded-full h-1.5 overflow-hidden" role="progressbar" :aria-valuenow="Math.round(analyzeProgress)" aria-valuemin="0" aria-valuemax="100">
                <div
                  class="h-full bg-gradient-to-r from-indigo-500 to-teal-400 rounded-full transition-all duration-500"
                  :style="{ width: `${Math.min(analyzeProgress, 100)}%` }"
                />
              </div>
              <p class="text-xs text-white/30" aria-live="polite">{{ Math.round(Math.min(analyzeProgress, 100)) }}%</p>
            </div>

            <!-- ── Step 3: Results ── -->
            <div v-else-if="currentStep === 'results' && result" data-cy="step-results" class="space-y-5">
              <!-- Confidence -->
              <div class="flex items-center justify-between p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
                <div class="flex items-center gap-2">
                  <span class="text-emerald-400 text-sm" aria-hidden="true">✓</span>
                  <span class="text-xs font-medium text-emerald-300">Анализ завершён</span>
                </div>
                <span class="text-xs text-white/40">Уверенность: {{ Math.round(result.confidenceScore * 100) }}%</span>
              </div>

              <!-- AR Link -->
              <a
                v-if="result.arLink"
                :href="result.arLink"
                target="_blank"
                rel="noopener"
                class="flex items-center gap-3 p-3.5 bg-indigo-500/10 border border-indigo-500/20 rounded-xl hover:bg-indigo-500/15 transition-colors"
              >
                <span class="text-lg" aria-hidden="true">🥽</span>
                <div>
                  <p class="text-xs font-medium text-indigo-300">AR-примерка</p>
                  <p class="text-[10px] text-white/40">Попробуйте в дополненной реальности</p>
                </div>
                <svg class="w-4 h-4 text-indigo-400 ml-auto shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
              </a>

              <!-- Recommendations -->
              <div v-if="result.suggestions?.length">
                <h3 class="text-xs font-semibold text-white/60 uppercase tracking-wider mb-3">Рекомендации</h3>
                <ul class="space-y-3" role="list">
                  <li
                    v-for="item in result.suggestions"
                    :key="item.productId"
                    class="flex items-center gap-3 p-3 rounded-2xl border transition-colors"
                    :class="item.inStock
                      ? 'bg-white/[0.03] border-white/5 hover:bg-white/[0.05]'
                      : 'bg-white/[0.01] border-white/[0.03] opacity-60'"
                  >
                    <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0 bg-white/5"
                         :class="{ 'grayscale': !item.inStock }">
                      <img v-if="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-xs font-medium text-white truncate">{{ item.name }}</p>
                      <p class="text-xs text-white/40">
                        <span v-if="isB2B && item.b2bAmountRub">{{ item.b2bAmountRub.toLocaleString('ru') }} ₽</span>
                        <span v-else>{{ item.amountRub.toLocaleString('ru') }} ₽</span>
                        <span v-if="!item.inStock" class="ml-2 text-red-400/70">нет в наличии</span>
                      </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                      <a
                        v-if="item.arTryOnUrl && item.inStock"
                        :href="item.arTryOnUrl"
                        target="_blank"
                        rel="noopener"
                        class="text-[10px] text-indigo-400 hover:text-indigo-300 transition-colors focus:outline-none focus-visible:underline"
                        :aria-label="`AR-примерка: ${item.name}`"
                      >AR</a>
                      <button
                        v-if="item.inStock"
                        @click="addToCart(item)"
                        :data-cy="`btn-add-to-cart-${item.productId}`"
                        class="text-[10px] px-2.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                        :aria-label="`Добавить в корзину: ${item.name}`"
                      >+ В корзину</button>
                    </div>
                  </li>
                </ul>
              </div>

              <!-- Total cost (if applicable) -->
              <div v-if="result.totalCostRub" class="p-3 bg-white/[0.03] rounded-xl border border-white/5 flex items-center justify-between">
                <span class="text-xs text-white/50">Общая стоимость</span>
                <span class="text-sm font-bold text-white">{{ result.totalCostRub.toLocaleString('ru') }} ₽</span>
              </div>

              <!-- Actions -->
              <div class="flex gap-3 pt-1">
                <button
                  @click="currentStep = 'upload'"
                  class="flex-1 py-2.5 rounded-2xl text-xs font-medium bg-white/5 hover:bg-white/10 text-white/60 hover:text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white/20"
                >Сначала</button>
                <button
                  @click="saveToProfile"
                  data-cy="btn-save"
                  :disabled="isLoading"
                  class="flex-[2] py-2.5 rounded-2xl text-xs font-semibold bg-gradient-to-r from-indigo-600 to-teal-500 text-white hover:opacity-90 transition-opacity disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                  <span v-if="isLoading">Сохраняем...</span>
                  <span v-else>💾 Сохранить в профиль</span>
                </button>
              </div>
            </div>

            <!-- ── Step 4: Saved ── -->
            <div v-else-if="currentStep === 'saved'" data-cy="step-saved" class="py-10 text-center space-y-4">
              <div class="w-16 h-16 mx-auto rounded-3xl bg-emerald-500/10 flex items-center justify-center" aria-hidden="true">
                <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-bold text-white">Дизайн сохранён!</p>
                <p class="text-xs text-white/40 mt-1">Найдёте в личном кабинете → «Мои дизайны»</p>
              </div>
              <a
                href="/profile/designs"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-medium bg-indigo-600 hover:bg-indigo-500 text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
              >
                Открыть мои дизайны →
              </a>
              <button @click="close" class="block w-full text-xs text-white/30 hover:text-white/60 transition-colors focus:outline-none focus-visible:underline mt-2">
                Закрыть
              </button>
            </div>

          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.wizard-fade-enter-active,
.wizard-fade-leave-active {
  transition: opacity 0.2s ease;
}
.wizard-fade-enter-active .relative,
.wizard-fade-leave-active .relative {
  transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s ease;
}
.wizard-fade-enter-from,
.wizard-fade-leave-to {
  opacity: 0;
}
.wizard-fade-enter-from .relative {
  transform: translateY(24px) scale(0.97);
  opacity: 0;
}

.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.2s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
