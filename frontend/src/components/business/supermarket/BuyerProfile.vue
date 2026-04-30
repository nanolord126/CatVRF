<template>
  <div class="buyer-profile min-h-screen bg-gray-50 pb-20">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-4 py-4">
      <h1 class="text-xl font-bold text-gray-900">Профиль</h1>
    </div>

    <!-- Profile Content -->
    <div class="p-4 space-y-4">
      <!-- User Info Card -->
      <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center text-white text-2xl font-bold">
            {{ userInitials }}
          </div>
          <div>
            <h2 class="text-lg font-bold text-gray-900">{{ dashboardData?.user?.name || 'Пользователь' }}</h2>
            <p class="text-sm text-gray-500">{{ dashboardData?.user?.email }}</p>
          </div>
        </div>

        <!-- Age Verification Status -->
        <div :class="ageVerificationClass" class="rounded-lg p-4 mb-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <component :is="ageVerificationIcon" class="w-6 h-6" />
              <div>
                <p class="font-semibold">{{ ageVerificationTitle }}</p>
                <p class="text-sm opacity-90">{{ ageVerificationDescription }}</p>
              </div>
            </div>
            <button 
              v-if="!dashboardData?.user?.age_verified"
              @click="handleAgeVerification"
              class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg font-medium text-sm transition-colors"
            >
              Верифицировать
            </button>
          </div>
        </div>

        <!-- Bonus Balance -->
        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Бонусный баланс</p>
              <p class="text-2xl font-bold text-gray-900">{{ dashboardData?.user?.bonus_balance || 0 }} ₽</p>
            </div>
            <button class="text-orange-600 hover:text-orange-700 font-medium text-sm">
              История
            </button>
          </div>
        </div>
      </div>

      <!-- Personal Data -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <button 
          @click="toggleSection('personal')"
          class="w-full px-4 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors"
        >
          <div class="flex items-center gap-3">
            <User class="w-5 h-5 text-gray-600" />
            <span class="font-medium text-gray-900">Личные данные</span>
          </div>
          <ChevronRight :class="expandedSections.personal ? 'rotate-90' : ''" class="w-5 h-5 text-gray-400 transition-transform" />
        </button>
        <div v-if="expandedSections.personal" class="px-4 pb-4 space-y-3">
          <div class="flex justify-between py-2 border-b border-gray-100">
            <span class="text-sm text-gray-600">Имя</span>
            <span class="text-sm font-medium text-gray-900">{{ dashboardData?.user?.name || '-' }}</span>
          </div>
          <div class="flex justify-between py-2 border-b border-gray-100">
            <span class="text-sm text-gray-600">Email</span>
            <span class="text-sm font-medium text-gray-900">{{ dashboardData?.user?.email || '-' }}</span>
          </div>
          <div class="flex justify-between py-2">
            <span class="text-sm text-gray-600">Телефон</span>
            <span class="text-sm font-medium text-gray-900">+7 (999) 123-45-67</span>
          </div>
          <button class="w-full mt-2 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 rounded-lg font-medium text-sm transition-colors">
            Редактировать
          </button>
        </div>
      </section>

      <!-- Delivery Addresses -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <button 
          @click="toggleSection('addresses')"
          class="w-full px-4 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors"
        >
          <div class="flex items-center gap-3">
            <MapPin class="w-5 h-5 text-gray-600" />
            <span class="font-medium text-gray-900">Адреса доставки</span>
          </div>
          <ChevronRight :class="expandedSections.addresses ? 'rotate-90' : ''" class="w-5 h-5 text-gray-400 transition-transform" />
        </button>
        <div v-if="expandedSections.addresses" class="px-4 pb-4 space-y-3">
          <div class="p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between mb-1">
              <span class="text-sm font-medium text-gray-900">Дом</span>
              <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Основной</span>
            </div>
            <p class="text-sm text-gray-600">г. Москва, ул. Примерная, д. 123, кв. 45</p>
          </div>
          <button class="w-full bg-green-50 hover:bg-green-100 text-green-700 py-2 rounded-lg font-medium text-sm transition-colors">
            + Добавить адрес
          </button>
        </div>
      </section>

      <!-- Connected Channels -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <button 
          @click="toggleSection('channels')"
          class="w-full px-4 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors"
        >
          <div class="flex items-center gap-3">
            <Link class="w-5 h-5 text-gray-600" />
            <span class="font-medium text-gray-900">Привязки</span>
          </div>
          <ChevronRight :class="expandedSections.channels ? 'rotate-90' : ''" class="w-5 h-5 text-gray-400 transition-transform" />
        </button>
        <div v-if="expandedSections.channels" class="px-4 pb-4 space-y-3">
          <div class="flex items-center justify-between py-2 border-b border-gray-100">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <MessageCircle class="w-4 h-4 text-blue-600" />
              </div>
              <span class="text-sm text-gray-900">Telegram</span>
            </div>
            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Подключен</span>
          </div>
          <div class="flex items-center justify-between py-2 border-b border-gray-100">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                <MessageSquare class="w-4 h-4 text-green-600" />
              </div>
              <span class="text-sm text-gray-900">WhatsApp</span>
            </div>
            <button class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded-full hover:bg-gray-200 transition-colors">
              Подключить
            </button>
          </div>
          <div class="flex items-center justify-between py-2">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                <Bell class="w-4 h-4 text-purple-600" />
              </div>
              <span class="text-sm text-gray-900">Push-уведомления</span>
            </div>
            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Включены</span>
          </div>
        </div>
      </section>

      <!-- Notification Settings -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <button 
          @click="toggleSection('notifications')"
          class="w-full px-4 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors"
        >
          <div class="flex items-center gap-3">
            <Bell class="w-5 h-5 text-gray-600" />
            <span class="font-medium text-gray-900">Настройки уведомлений</span>
          </div>
          <ChevronRight :class="expandedSections.notifications ? 'rotate-90' : ''" class="w-5 h-5 text-gray-400 transition-transform" />
        </button>
        <div v-if="expandedSections.notifications" class="px-4 pb-4 space-y-3">
          <div class="flex items-center justify-between py-2">
            <span class="text-sm text-gray-900">Подписки и доставки</span>
            <button class="w-12 h-6 bg-green-500 rounded-full relative">
              <span class="absolute right-1 top-1 w-4 h-4 bg-white rounded-full"></span>
            </button>
          </div>
          <div class="flex items-center justify-between py-2">
            <span class="text-sm text-gray-900">Акции и промокоды</span>
            <button class="w-12 h-6 bg-green-500 rounded-full relative">
              <span class="absolute right-1 top-1 w-4 h-4 bg-white rounded-full"></span>
            </button>
          </div>
          <div class="flex items-center justify-between py-2">
            <span class="text-sm text-gray-900">Заказы</span>
            <button class="w-12 h-6 bg-gray-300 rounded-full relative">
              <span class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full"></span>
            </button>
          </div>
        </div>
      </section>

      <!-- Favorites -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <button 
          @click="toggleSection('favorites')"
          class="w-full px-4 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors"
        >
          <div class="flex items-center gap-3">
            <Heart class="w-5 h-5 text-gray-600" />
            <span class="font-medium text-gray-900">Избранное</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500">12 товаров</span>
            <ChevronRight :class="expandedSections.favorites ? 'rotate-90' : ''" class="w-5 h-5 text-gray-400 transition-transform" />
          </div>
        </button>
        <div v-if="expandedSections.favorites" class="px-4 pb-4">
          <div class="grid grid-cols-4 gap-3">
            <div v-for="i in 4" :key="i" class="aspect-square bg-gray-100 rounded-lg flex items-center justify-center">
              <Package class="w-6 h-6 text-gray-400" />
            </div>
          </div>
          <button class="w-full mt-3 text-green-600 hover:text-green-700 font-medium text-sm">
            Смотреть все
          </button>
        </div>
      </section>

      <!-- Logout Button -->
      <button class="w-full bg-red-50 hover:bg-red-100 text-red-600 py-3 rounded-xl font-medium transition-colors flex items-center justify-center gap-2">
        <LogOut class="w-5 h-5" />
        Выйти из аккаунта
      </button>

      <!-- App Version -->
      <p class="text-center text-xs text-gray-400">CatVRF v2.1.0</p>
    </div>

    <!-- Bottom Navigation -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-6 py-3">
      <div class="flex items-center justify-around">
        <button class="flex flex-col items-center gap-1 text-gray-400 hover:text-gray-600 transition-colors">
          <Home class="w-6 h-6" />
          <span class="text-xs">Главная</span>
        </button>
        <button class="flex flex-col items-center gap-1 text-gray-400 hover:text-gray-600 transition-colors">
          <ShoppingBag class="w-6 h-6" />
          <span class="text-xs">Заказы</span>
        </button>
        <button class="flex flex-col items-center gap-1 text-gray-400 hover:text-gray-600 transition-colors">
          <Infinity class="w-6 h-6" />
          <span class="text-xs">Подписки</span>
        </button>
        <button class="flex flex-col items-center gap-1 text-green-600">
          <User class="w-6 h-6" />
          <span class="text-xs font-medium">Профиль</span>
        </button>
      </div>
    </div>

    <!-- Age Verification Modal -->
    <div v-if="showAgeVerificationModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Верификация возраста 18+</h3>
        <p class="text-sm text-gray-600 mb-4">Для покупки товаров с возрастным ограничением необходимо подтвердить ваш возраст.</p>
        
        <div class="space-y-3 mb-6">
          <button class="w-full p-3 border border-gray-200 rounded-lg hover:border-gray-300 hover:bg-gray-50 transition-colors text-left flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
              <Camera class="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <p class="font-medium text-gray-900">Паспорт</p>
              <p class="text-xs text-gray-500">Сфотографируйте паспорт</p>
            </div>
          </button>
          <button class="w-full p-3 border border-gray-200 rounded-lg hover:border-gray-300 hover:bg-gray-50 transition-colors text-left flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
              <Camera class="w-5 h-5 text-green-600" />
            </div>
            <div>
              <p class="font-medium text-gray-900">Селфи</p>
              <p class="text-xs text-gray-500">AI-определение возраста</p>
            </div>
          </button>
          <button class="w-full p-3 border border-gray-200 rounded-lg hover:border-gray-300 hover:bg-gray-50 transition-colors text-left flex items-center gap-3">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
              <Globe class="w-5 h-5 text-purple-600" />
            </div>
            <div>
              <p class="font-medium text-gray-900">Госуслуги</p>
              <p class="text-xs text-gray-500">ЕСИА авторизация</p>
            </div>
          </button>
        </div>
        
        <button @click="showAgeVerificationModal = false" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 rounded-lg font-medium transition-colors">
          Отмена
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { 
  User, MapPin, Link, Bell, Heart, LogOut, Home, ShoppingBag, Infinity,
  ChevronRight, MessageCircle, MessageSquare, Package, CheckCircle, AlertCircle,
  Camera, Globe
} from 'lucide-vue-next'
import { useSupermarketApi } from '@/composables/useSupermarketApi'

const api = useSupermarketApi()
const dashboardData = ref<any>(null)
const expandedSections = ref({
  personal: false,
  addresses: false,
  channels: false,
  notifications: false,
  favorites: false
})
const showAgeVerificationModal = ref(false)

const userInitials = computed(() => {
  const name = dashboardData.value?.user?.name || 'Пользователь'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

const ageVerificationClass = computed(() => {
  return dashboardData.value?.user?.age_verified 
    ? 'bg-green-50 text-green-900' 
    : 'bg-red-50 text-red-900'
})

const ageVerificationIcon = computed(() => {
  return dashboardData.value?.user?.age_verified ? CheckCircle : AlertCircle
})

const ageVerificationTitle = computed(() => {
  return dashboardData.value?.user?.age_verified 
    ? 'Верификация пройдена' 
    : 'Требуется верификация 18+'
})

const ageVerificationDescription = computed(() => {
  return dashboardData.value?.user?.age_verified 
    ? `Подтверждено: ${formatDate(dashboardData.value.user.age_verified_at)}` 
    : 'Для покупки товаров с возрастным ограничением'
})

const loadDashboard = async () => {
  try {
    dashboardData.value = await api.fetchBuyerDashboard()
  } catch (error) {
    console.error('Failed to load dashboard:', error)
  }
}

const toggleSection = (section: keyof typeof expandedSections.value) => {
  expandedSections.value[section] = !expandedSections.value[section]
}

const formatDate = (dateString?: string): string => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', year: 'numeric' })
}

const handleAgeVerification = () => {
  showAgeVerificationModal.value = true
}

onMounted(() => {
  loadDashboard()
})
</script>
