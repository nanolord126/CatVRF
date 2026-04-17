<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';

interface Tour {
  id: number;
  uuid: string;
  title: string;
  destination: string;
  base_price: number;
  duration_days: number;
  difficulty: string;
  rating: number;
}

interface BookingData {
  tourUuid: string;
  personCount: number;
  startDate: string;
  endDate: string;
  totalAmount: number;
  paymentMethod: 'card' | 'wallet' | 'sbp' | 'split';
  splitPaymentEnabled: boolean;
}

const router = useRouter();
const currentStep = ref(1);
const selectedTour = ref<Tour | null>(null);
const bookingData = ref<BookingData>({
  tourUuid: '',
  personCount: 1,
  startDate: '',
  endDate: '',
  totalAmount: 0,
  paymentMethod: 'card',
  splitPaymentEnabled: false,
});
const loading = ref(false);
const error = ref('');
const correlationId = ref('');

onMounted(() => {
  correlationId.value = crypto.randomUUID();
  document.documentElement.setAttribute('data-correlation-id', correlationId.value);
});

const steps = [
  { id: 1, title: 'Выбор тура' },
  { id: 2, title: 'Даты и участники' },
  { id: 3, title: 'Оплата' },
  { id: 4, title: 'Биометрия' },
  { id: 5, title: 'Подтверждение' },
];

const nextStep = () => {
  if (currentStep.value < steps.length) {
    currentStep.value++;
  }
};

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

const selectTour = (tour: Tour) => {
  selectedTour.value = tour;
  bookingData.value.tourUuid = tour.uuid;
  bookingData.value.totalAmount = tour.base_price * bookingData.value.personCount;
};

const calculateTotal = () => {
  if (selectedTour.value) {
    bookingData.value.totalAmount = selectedTour.value.base_price * bookingData.value.personCount;
  }
};

const createBooking = async () => {
  loading.value = true;
  error.value = '';

  try {
    const response = await fetch('/api/v1/tourism/bookings', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': correlationId.value,
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
      },
      body: JSON.stringify(bookingData.value),
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Failed to create booking');
    }

    router.push(`/tourism/bookings/${data.data.uuid}`);
  } catch (err: any) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
};

const isStepValid = computed(() => {
  switch (currentStep.value) {
    case 1:
      return !!selectedTour.value;
    case 2:
      return bookingData.value.personCount > 0
        && bookingData.value.startDate
        && bookingData.value.endDate
        && new Date(bookingData.value.endDate) > new Date(bookingData.value.startDate);
    case 3:
      return !!bookingData.value.paymentMethod;
    case 4:
      return true; // Biometric verification handled separately
    case 5:
      return true;
    default:
      return false;
  }
});
</script>

<template>
  <div class="tourism-booking-wizard">
    <div class="wizard-header">
      <h1>Бронирование тура</h1>
      <div class="steps">
        <div
          v-for="step in steps"
          :key="step.id"
          :class="['step', { active: currentStep === step.id, completed: currentStep > step.id }]"
        >
          <div class="step-number">{{ step.id }}</div>
          <div class="step-title">{{ step.title }}</div>
        </div>
      </div>
    </div>

    <div class="wizard-content">
      <!-- Step 1: Tour Selection -->
      <div v-if="currentStep === 1" class="step-content">
        <h2>Выберите тур</h2>
        <div class="tour-grid">
          <div
            v-for="tour in tours"
            :key="tour.id"
            :class="['tour-card', { selected: selectedTour?.id === tour.id }]"
            @click="selectTour(tour)"
          >
            <img :src="tour.image" :alt="tour.title" class="tour-image" />
            <div class="tour-info">
              <h3>{{ tour.title }}</h3>
              <p class="destination">{{ tour.destination }}</p>
              <div class="tour-details">
                <span class="duration">{{ tour.duration_days }} дней</span>
                <span class="difficulty">{{ tour.difficulty }}</span>
                <span class="rating">★ {{ tour.rating }}</span>
              </div>
              <div class="price">{{ formatPrice(tour.base_price) }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 2: Dates and Participants -->
      <div v-if="currentStep === 2" class="step-content">
        <h2>Даты и участники</h2>
        <div class="form-group">
          <label>Количество участников</label>
          <input
            v-model.number="bookingData.personCount"
            type="number"
            min="1"
            max="50"
            @input="calculateTotal"
          />
        </div>
        <div class="form-group">
          <label>Дата начала</label>
          <input
            v-model="bookingData.startDate"
            type="date"
            :min="minDate"
          />
        </div>
        <div class="form-group">
          <label>Дата окончания</label>
          <input
            v-model="bookingData.endDate"
            type="date"
            :min="bookingData.startDate || minDate"
          />
        </div>
        <div class="summary">
          <h3>Итого: {{ formatPrice(bookingData.totalAmount) }}</h3>
        </div>
      </div>

      <!-- Step 3: Payment -->
      <div v-if="currentStep === 3" class="step-content">
        <h2>Способ оплаты</h2>
        <div class="payment-methods">
          <div
            v-for="method in ['card', 'wallet', 'sbp', 'split']"
            :key="method"
            :class="['payment-method', { selected: bookingData.paymentMethod === method }]"
            @click="bookingData.paymentMethod = method as any"
          >
            <div class="method-icon">{{ getPaymentIcon(method) }}</div>
            <div class="method-name">{{ getPaymentMethodName(method) }}</div>
          </div>
        </div>
        <div v-if="bookingData.paymentMethod === 'split'" class="split-payment">
          <label>
            <input v-model="bookingData.splitPaymentEnabled" type="checkbox" />
            Разделить оплату между участниками
          </label>
        </div>
      </div>

      <!-- Step 4: Biometric Verification -->
      <div v-if="currentStep === 4" class="step-content">
        <h2>Биометрическая верификация</h2>
        <p>Для подтверждения бронирования требуется пройти биометрическую верификацию</p>
        <div class="biometric-container">
          <div class="camera-placeholder">
            <div class="camera-icon">📷</div>
            <p>Нажмите для запуска камеры</p>
          </div>
          <button class="btn-primary" @click="startBiometricVerification">
            Запустить верификацию
          </button>
        </div>
      </div>

      <!-- Step 5: Confirmation -->
      <div v-if="currentStep === 5" class="step-content">
        <h2>Подтверждение бронирования</h2>
        <div class="booking-summary">
          <div class="summary-item">
            <span>Тур:</span>
            <span>{{ selectedTour?.title }}</span>
          </div>
          <div class="summary-item">
            <span>Участников:</span>
            <span>{{ bookingData.personCount }}</span>
          </div>
          <div class="summary-item">
            <span>Дата начала:</span>
            <span>{{ formatDate(bookingData.startDate) }}</span>
          </div>
          <div class="summary-item">
            <span>Дата окончания:</span>
            <span>{{ formatDate(bookingData.endDate) }}</span>
          </div>
          <div class="summary-item total">
            <span>Итого:</span>
            <span>{{ formatPrice(bookingData.totalAmount) }}</span>
          </div>
          <div class="summary-item cashback">
            <span>Кэшбэк (5%):</span>
            <span>{{ formatPrice(bookingData.totalAmount * 0.05) }}</span>
          </div>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="error-message">
        {{ error }}
      </div>
    </div>

    <div class="wizard-footer">
      <button
        v-if="currentStep > 1"
        class="btn-secondary"
        @click="prevStep"
      >
        Назад
      </button>
      <button
        v-if="currentStep < steps.length"
        class="btn-primary"
        :disabled="!isStepValid || loading"
        @click="nextStep"
      >
        Далее
      </button>
      <button
        v-if="currentStep === steps.length"
        class="btn-success"
        :disabled="loading"
        @click="createBooking"
      >
        {{ loading ? 'Загрузка...' : 'Подтвердить бронирование' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.tourism-booking-wizard {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.wizard-header {
  text-align: center;
  margin-bottom: 2rem;
}

.steps {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-top: 1rem;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  opacity: 0.5;
}

.step.active,
.step.completed {
  opacity: 1;
}

.step-number {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #e0e0e0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.step.active .step-number {
  background: #3b82f6;
  color: white;
}

.step.completed .step-number {
  background: #10b981;
  color: white;
}

.step-title {
  font-size: 0.875rem;
}

.wizard-content {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.step-content h2 {
  margin-bottom: 1.5rem;
}

.tour-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.tour-card {
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s;
}

.tour-card:hover,
.tour-card.selected {
  border-color: #3b82f6;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

.tour-image {
  width: 100%;
  height: 200px;
  object-fit: cover;
}

.tour-info {
  padding: 1rem;
}

.tour-info h3 {
  margin: 0 0 0.5rem 0;
}

.destination {
  color: #666;
  margin-bottom: 0.5rem;
}

.tour-details {
  display: flex;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #666;
  margin-bottom: 0.5rem;
}

.price {
  font-size: 1.25rem;
  font-weight: bold;
  color: #3b82f6;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
}

.payment-methods {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.payment-method {
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  padding: 1.5rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s;
}

.payment-method:hover,
.payment-method.selected {
  border-color: #3b82f6;
  background: #f0f9ff;
}

.method-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.method-name {
  font-weight: 500;
}

.split-payment {
  margin-top: 1rem;
}

.biometric-container {
  text-align: center;
  padding: 2rem;
}

.camera-placeholder {
  width: 300px;
  height: 300px;
  background: #f0f0f0;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
}

.camera-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.booking-summary {
  background: #f9fafb;
  padding: 1.5rem;
  border-radius: 8px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #e0e0e0;
}

.summary-item:last-child {
  border-bottom: none;
}

.summary-item.total {
  font-weight: bold;
  font-size: 1.25rem;
  margin-top: 1rem;
  padding-top: 1rem;
}

.summary-item.cashback {
  color: #10b981;
}

.error-message {
  background: #fee2e2;
  color: #991b1b;
  padding: 1rem;
  border-radius: 4px;
  margin-top: 1rem;
}

.wizard-footer {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
}

.btn-primary,
.btn-secondary,
.btn-success {
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  border: none;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
}

.btn-secondary {
  background: #e0e0e0;
  color: #333;
}

.btn-secondary:hover {
  background: #d0d0d0;
}

.btn-success {
  background: #10b981;
  color: white;
}

.btn-success:hover:not(:disabled) {
  background: #059669;
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
