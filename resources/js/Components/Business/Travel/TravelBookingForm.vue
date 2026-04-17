<template>
  <div class="travel-booking-form">
    <div class="booking-summary">
      <h4>Booking Summary</h4>
      <div class="summary-item">
        <span>Flight:</span>
        <span>{{ flight?.airline }} {{ flight?.flight_number }}</span>
      </div>
      <div class="summary-item">
        <span>Route:</span>
        <span>{{ flight?.origin }} → {{ flight?.destination }}</span>
      </div>
      <div class="summary-item">
        <span>Date:</span>
        <span>{{ formatDate(searchParams?.date) }}</span>
      </div>
      <div class="summary-item">
        <span>Passengers:</span>
        <span>{{ searchParams?.passengers }}</span>
      </div>
      <div class="summary-item total">
        <span>Total:</span>
        <span>{{ formatCurrency(flight?.price * (searchParams?.passengers || 1)) }}</span>
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="booking-form">
      <div class="form-section">
        <h4>Passenger Information</h4>
        
        <div v-for="(passenger, index) in passengers" :key="index" class="passenger-card">
          <h5>Passenger {{ index + 1 }} - {{ index === 0 ? 'Adult' : 'Adult' }}</h5>
          
          <div class="form-row">
            <div class="form-group">
              <label>First Name *</label>
              <input 
                v-model="passenger.firstName" 
                type="text" 
                class="form-input"
                required
              />
            </div>
            <div class="form-group">
              <label>Last Name *</label>
              <input 
                v-model="passenger.lastName" 
                type="text" 
                class="form-input"
                required
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Date of Birth *</label>
              <input 
                v-model="passenger.dateOfBirth" 
                type="date" 
                class="form-input"
                required
                :max="maxBirthDate"
              />
            </div>
            <div class="form-group">
              <label>Gender *</label>
              <select v-model="passenger.gender" class="form-input" required>
                <option value="">Select</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Nationality *</label>
              <select v-model="passenger.nationality" class="form-input" required>
                <option value="">Select</option>
                <option value="RU">Russia</option>
                <option value="US">United States</option>
                <option value="GB">United Kingdom</option>
                <option value="DE">Germany</option>
                <option value="FR">France</option>
                <option value="CN">China</option>
                <option value="JP">Japan</option>
                <option value="IN">India</option>
              </select>
            </div>
            <div class="form-group">
              <label>Passport Number *</label>
              <input 
                v-model="passenger.passportNumber" 
                type="text" 
                class="form-input"
                required
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Passport Expiry *</label>
              <input 
                v-model="passenger.passportExpiry" 
                type="date" 
                class="form-input"
                required
                :min="minPassportExpiry"
              />
            </div>
            <div class="form-group">
              <label>Email *</label>
              <input 
                v-model="passenger.email" 
                type="email" 
                class="form-input"
                required
              />
            </div>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h4>Contact Information</h4>
        <div class="form-row">
          <div class="form-group">
            <label>Phone Number *</label>
            <input 
              v-model="contact.phone" 
              type="tel" 
              class="form-input"
              placeholder="+7 (999) 999-99-99"
              required
            />
          </div>
          <div class="form-group">
            <label>Emergency Contact</label>
            <input 
              v-model="contact.emergencyPhone" 
              type="tel" 
              class="form-input"
              placeholder="+7 (999) 999-99-99"
            />
          </div>
        </div>
      </div>

      <div class="form-section">
        <h4>Additional Services</h4>
        <div class="services-grid">
          <label class="service-card">
            <input type="checkbox" v-model="services.travelInsurance" />
            <div class="service-content">
              <span class="service-name">Travel Insurance</span>
              <span class="service-price">+{{ formatCurrency(1500) }}</span>
            </div>
          </label>
          <label class="service-card">
            <input type="checkbox" v-model="services.seatSelection" />
            <div class="service-content">
              <span class="service-name">Seat Selection</span>
              <span class="service-price">+{{ formatCurrency(500) }}</span>
            </div>
          </label>
          <label class="service-card">
            <input type="checkbox" v-model="services.extraBaggage" />
            <div class="service-content">
              <span class="service-name">Extra Baggage (23kg)</span>
              <span class="service-price">+{{ formatCurrency(3000) }}</span>
            </div>
          </label>
          <label class="service-card">
            <input type="checkbox" v-model="services.priorityBoarding" />
            <div class="service-content">
              <span class="service-name">Priority Boarding</span>
              <span class="service-price">+{{ formatCurrency(1000) }}</span>
            </div>
          </label>
        </div>
      </div>

      <div class="form-section">
        <h4>Special Requests</h4>
        <textarea 
          v-model="specialRequests" 
          class="form-textarea"
          placeholder="Any special requests or dietary requirements..."
          rows="3"
        ></textarea>
      </div>

      <div class="form-section">
        <label class="terms-checkbox">
          <input type="checkbox" v-model="acceptTerms" required />
          <span>I agree to the <a href="#" target="_blank">Terms and Conditions</a> and <a href="#" target="_blank">Privacy Policy</a></span>
        </label>
      </div>

      <div class="form-actions">
        <button type="button" @click="$emit('cancel')" class="btn-cancel">Cancel</button>
        <button type="submit" class="btn-submit" :disabled="!acceptTerms || isSubmitting">
          <span v-if="isSubmitting">Processing...</span>
          <span v-else>Complete Booking - {{ formatCurrency(totalPrice) }}</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Flight {
  id: string
  airline: string
  flight_number: string
  origin: string
  destination: string
  price: number
  currency: string
}

interface FlightSearchParams {
  date: string
  return_date?: string
  passengers: number
  class: string
}

interface Props {
  flight: Flight | null
  searchParams: FlightSearchParams | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'submit', bookingData: any): void
  (e: 'cancel'): void
}>()

const passengers = ref([
  {
    firstName: '',
    lastName: '',
    dateOfBirth: '',
    gender: '',
    nationality: '',
    passportNumber: '',
    passportExpiry: '',
    email: '',
  }
])

const contact = ref({
  phone: '',
  emergencyPhone: '',
})

const services = ref({
  travelInsurance: false,
  seatSelection: false,
  extraBaggage: false,
  priorityBoarding: false,
})

const specialRequests = ref('')
const acceptTerms = ref(false)
const isSubmitting = ref(false)

const maxBirthDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const minPassportExpiry = computed(() => {
  const today = new Date()
  today.setMonth(today.getMonth() + 6)
  return today.toISOString().split('T')[0]
})

const totalPrice = computed(() => {
  let total = props.flight?.price * (props.searchParams?.passengers || 1) || 0
  
  if (services.value.travelInsurance) total += 1500
  if (services.value.seatSelection) total += 500
  if (services.value.extraBaggage) total += 3000
  if (services.value.priorityBoarding) total += 1000
  
  return total
})

const formatDate = (date: string): string => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

const handleSubmit = () => {
  isSubmitting.value = true

  const bookingData = {
    flight: props.flight,
    passengers: passengers.value,
    contact: contact.value,
    services: services.value,
    specialRequests: specialRequests.value,
    totalPrice: totalPrice.value,
  }

  setTimeout(() => {
    emit('submit', bookingData)
    isSubmitting.value = false
  }, 1500)
}

onMounted(() => {
  // Initialize passengers based on search params
  if (props.searchParams?.passengers && props.searchParams.passengers > 1) {
    const additionalPassengers = props.searchParams.passengers - 1
    for (let i = 0; i < additionalPassengers; i++) {
      passengers.value.push({
        firstName: '',
        lastName: '',
        dateOfBirth: '',
        gender: '',
        nationality: '',
        passportNumber: '',
        passportExpiry: '',
        email: '',
      })
    }
  }
})
</script>

<style scoped>
.travel-booking-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.booking-summary {
  background: #f9fafb;
  border-radius: 8px;
  padding: 20px;
}

.booking-summary h4 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  font-size: 14px;
}

.summary-item.total {
  border-top: 2px solid #e5e7eb;
  margin-top: 8px;
  padding-top: 12px;
  font-weight: 600;
  font-size: 16px;
}

.booking-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.form-section h4 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
  color: #111827;
}

.passenger-card {
  background: #f9fafb;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
}

.passenger-card h5 {
  margin: 0 0 16px 0;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.form-row {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
}

.form-group {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 13px;
  font-weight: 500;
  color: #374151;
}

.form-input,
.form-textarea {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  width: 100%;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.services-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.service-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.service-card:hover {
  border-color: #3b82f6;
  background: #f9fafb;
}

.service-card input[type="checkbox"] {
  width: 18px;
  height: 18px;
}

.service-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.service-name {
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.service-price {
  font-size: 12px;
  color: #6b7280;
}

.terms-checkbox {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  font-size: 14px;
  cursor: pointer;
}

.terms-checkbox input {
  margin-top: 2px;
}

.terms-checkbox a {
  color: #3b82f6;
  text-decoration: underline;
}

.form-actions {
  display: flex;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.btn-cancel {
  padding: 12px 24px;
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  flex: 1;
}

.btn-cancel:hover {
  background: #e5e7eb;
}

.btn-submit {
  padding: 12px 24px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  flex: 2;
}

.btn-submit:hover:not(:disabled) {
  background: #2563eb;
}

.btn-submit:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .form-row {
    flex-direction: column;
  }
  
  .services-grid {
    grid-template-columns: 1fr;
  }
  
  .form-actions {
    flex-direction: column;
  }
}
</style>
