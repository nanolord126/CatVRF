<template>
  <div class="wedding-service-booking bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 mb-2">Book Wedding Service</h2>
      <p class="text-gray-600">Plan your perfect day with professional wedding services</p>
    </div>

    <!-- Service Category -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Service Category</label>
      <div class="flex gap-2 flex-wrap">
        <button 
          v-for="category in serviceCategories"
          :key="category.id"
          @click="selectCategory(category)"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition-all',
            selectedCategory?.id === category.id 
              ? 'bg-pink-600 text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          {{ category.name }}
        </button>
      </div>
    </div>

    <!-- Service Providers -->
    <div v-if="selectedCategory" class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Select Service Provider</label>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <button 
          v-for="provider in filteredProviders"
          :key="provider.id"
          @click="selectProvider(provider)"
          :class="[
            'p-4 rounded-lg border-2 transition-all text-left',
            selectedProvider?.id === provider.id 
              ? 'border-pink-500 bg-pink-50' 
              : 'border-gray-200 hover:border-pink-300'
          ]"
        >
          <div class="flex justify-between items-start mb-2">
            <div>
              <p class="font-medium text-gray-900">{{ provider.name }}</p>
              <p class="text-sm text-gray-500">{{ provider.location }}</p>
            </div>
            <div class="text-right">
              <p class="font-bold text-pink-600">{{ formatPrice(provider.startingPrice) }}</p>
              <p class="text-xs text-gray-400">{{ provider.rating }} ⭐</p>
            </div>
          </div>
          <p class="text-xs text-gray-400">{{ provider.specialty }}</p>
        </button>
      </div>
    </div>

    <!-- Services -->
    <div v-if="selectedProvider" class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Select Service Package</label>
      <div class="space-y-3">
        <button 
          v-for="service in services"
          :key="service.id"
          @click="selectService(service)"
          :class="[
            'p-4 rounded-lg border-2 transition-all text-left w-full',
            selectedService?.id === service.id 
              ? 'border-pink-500 bg-pink-50' 
              : 'border-gray-200 hover:border-pink-300'
          ]"
        >
          <div class="flex justify-between items-start mb-2">
            <div>
              <p class="font-medium text-gray-900">{{ service.name }}</p>
              <p class="text-sm text-gray-500">{{ service.duration }}</p>
            </div>
            <p class="font-bold text-pink-600">{{ formatPrice(service.price) }}</p>
          </div>
          <p class="text-xs text-gray-400">{{ service.description }}</p>
          <ul class="mt-2 text-xs text-gray-500">
            <li v-for="feature in service.features" :key="feature" class="flex items-center gap-1">
              <span class="text-green-500">✓</span> {{ feature }}
            </li>
          </ul>
        </button>
      </div>
    </div>

    <!-- Wedding Date -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Wedding Date</label>
      <input 
        v-model="weddingDate"
        type="date"
        :min="minDate"
        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-500 focus:border-transparent"
      />
    </div>

    <!-- Guest Count -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Expected Guests</label>
      <input 
        v-model="guestCount"
        type="number"
        min="1"
        placeholder="Number of guests"
        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-500 focus:border-transparent"
      />
    </div>

    <!-- Additional Requirements -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Additional Requirements</label>
      <textarea 
        v-model="requirements"
        rows="3"
        placeholder="Special requests, dietary restrictions, theme preferences..."
        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-500 focus:border-transparent"
      />
    </div>

    <!-- Summary -->
    <div v-if="selectedService && weddingDate" class="mb-6 p-4 bg-pink-50 rounded-lg">
      <h3 class="font-semibold text-gray-900 mb-3">Booking Summary</h3>
      <div class="space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Provider:</span>
          <span class="font-medium">{{ selectedProviderName }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Service:</span>
          <span class="font-medium">{{ selectedService.name }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Wedding Date:</span>
          <span class="font-medium">{{ formatDate(weddingDate) }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Guests:</span>
          <span class="font-medium">{{ guestCount || 'Not specified' }}</span>
        </div>
        <div class="border-t pt-2 mt-2 flex justify-between">
          <span class="text-gray-600 font-medium">Total:</span>
          <span class="font-bold text-pink-600">{{ formatPrice(selectedService.price) }}</span>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-3">
      <button 
        @click="cancel"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-3 px-6 rounded-lg font-medium transition-colors"
      >
        Cancel
      </button>
      <button 
        @click="confirmBooking"
        :disabled="!canConfirm"
        class="flex-1 bg-pink-600 hover:bg-pink-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-3 px-6 rounded-lg font-medium transition-colors"
      >
        Request Quote
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface ServiceCategory {
  id: string
  name: string
}

interface ServiceProvider {
  id: string
  categoryId: string
  name: string
  location: string
  specialty: string
  rating: number
  startingPrice: number
}

interface Service {
  id: string
  providerId: string
  name: string
  description: string
  price: number
  duration: string
  features: string[]
}

const emit = defineEmits<{
  cancel: []
  confirmBooking: [booking: any]
}>()

const selectedCategory = ref<ServiceCategory>()
const selectedProvider = ref<ServiceProvider>()
const selectedService = ref<Service>()
const weddingDate = ref<string>()
const guestCount = ref<number>()
const requirements = ref('')

const serviceCategories = ref<ServiceCategory[]>([
  { id: '1', name: 'Venue' },
  { id: '2', name: 'Catering' },
  { id: '3', name: 'Photography' },
  { id: '4', name: 'Florist' },
  { id: '5', name: 'Music & DJ' },
  { id: '6', name: 'Decor' },
  { id: '7', name: 'Makeup & Hair' },
  { id: '8', name: 'Planning' },
])

const providers = ref<ServiceProvider[]>([
  { id: '1', categoryId: '1', name: 'Grand Ballroom', location: 'City Center', specialty: 'Luxury Venues', rating: 4.9, startingPrice: 500000 },
  { id: '2', categoryId: '1', name: 'Garden Estate', location: 'Suburbs', specialty: 'Outdoor Venues', rating: 4.8, startingPrice: 300000 },
  { id: '3', categoryId: '2', name: 'Gourmet Catering', location: 'City Wide', specialty: 'Fine Dining', rating: 4.7, startingPrice: 3000 },
  { id: '4', categoryId: '3', name: 'Moments Photography', location: 'City Center', specialty: 'Wedding Photography', rating: 4.9, startingPrice: 150000 },
  { id: '5', categoryId: '4', name: 'Bloom Florist', location: 'City Center', specialty: 'Wedding Flowers', rating: 4.8, startingPrice: 50000 },
])

const services = ref<Service[]>([
  { id: '1', providerId: '1', name: 'Full Day Venue Package', description: 'Complete venue with decor and staff', price: 500000, duration: 'Full day', features: ['Venue rental', 'Basic decor', 'Staff', 'Tables & chairs'] },
  { id: '2', providerId: '1', name: 'Premium Venue Package', description: 'Venue with premium decor and catering', price: 800000, duration: 'Full day', features: ['Venue rental', 'Premium decor', 'Full catering', 'Staff', 'Tables & chairs', 'Lighting'] },
  { id: '3', providerId: '2', name: 'Garden Wedding Package', description: 'Outdoor venue with garden setup', price: 300000, duration: 'Full day', features: ['Venue rental', 'Garden decor', 'Tent setup', 'Staff'] },
  { id: '4', providerId: '3', name: 'Standard Catering', description: 'Per person catering package', price: 3000, duration: 'Event duration', features: ['Main course', 'Appetizers', 'Soft drinks', 'Service staff'] },
  { id: '5', providerId: '3', name: 'Premium Catering', description: 'Full service catering with alcohol', price: 5000, duration: 'Event duration', features: ['Full course meal', 'Appetizers', 'Alcoholic beverages', 'Service staff', 'Cake'] },
  { id: '6', providerId: '4', name: 'Photography Package', description: 'Full day wedding photography', price: 150000, duration: 'Full day', features: ['Full day coverage', 'Edited photos', 'Online gallery', 'Photographer'] },
  { id: '7', providerId: '5', name: 'Bridal Bouquet Package', description: 'Bridal bouquet and boutonnieres', price: 50000, duration: 'Event', features: ['Bridal bouquet', 'Bridesmaid bouquets', 'Boutonnieres', 'Centerpieces'] },
])

const minDate = computed(() => {
  const today = new Date()
  today.setMonth(today.getMonth() + 3) // Minimum 3 months advance booking
  return today.toISOString().split('T')[0]
})

const filteredProviders = computed(() => {
  if (!selectedCategory.value) return []
  return providers.value.filter(p => p.categoryId === selectedCategory.value?.id)
})

const filteredServices = computed(() => {
  if (!selectedProvider.value) return []
  return services.value.filter(s => s.providerId === selectedProvider.value?.id)
})

const selectedProviderName = computed(() => {
  return selectedProvider.value?.name || ''
})

const canConfirm = computed(() => {
  return selectedProvider.value && selectedService.value && weddingDate.value
})

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const selectCategory = (category: ServiceCategory) => {
  selectedCategory.value = category
  selectedProvider.value = undefined
  selectedService.value = undefined
}

const selectProvider = (provider: ServiceProvider) => {
  selectedProvider.value = provider
  selectedService.value = undefined
}

const selectService = (service: Service) => {
  selectedService.value = service
}

const cancel = () => {
  emit('cancel')
}

const confirmBooking = () => {
  if (canConfirm.value) {
    emit('confirmBooking', {
      providerId: selectedProvider.value?.id,
      serviceId: selectedService.value?.id,
      weddingDate: weddingDate.value,
      guestCount: guestCount.value,
      requirements: requirements.value,
    })
  }
}
</script>
