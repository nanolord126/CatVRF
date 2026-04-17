<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Property
        </label>
        <select
          v-model="form.property_id"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          required
        >
          <option value="">Select a property</option>
          <option v-for="property in properties" :key="property.id" :value="property.id">
            {{ property.title }} - {{ formatPrice(property.price) }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Viewing Slot
        </label>
        <input
          v-model="form.viewing_slot"
          type="datetime-local"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          required
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Amount (₽)
        </label>
        <input
          v-model.number="form.amount"
          type="number"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          required
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Use Escrow
        </label>
        <input
          v-model="form.use_escrow"
          type="checkbox"
          class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
      </div>

      <div v-if="isB2B">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Business Group
        </label>
        <select
          v-model="form.business_group_id"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
          <option value="">Select business group</option>
          <option v-for="group in businessGroups" :key="group.id" :value="group.id">
            {{ group.name }}
          </option>
        </select>
      </div>

      <div v-if="isB2B">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          INN
        </label>
        <input
          v-model="form.inn"
          type="text"
          pattern="[0-9]{10,12}"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          placeholder="10 or 12 digits"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Face ID Token
        </label>
        <input
          v-model="form.face_id_token"
          type="text"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
      </div>
    </div>

    <div v-if="dealScore" class="bg-blue-50 p-4 rounded-md">
      <h3 class="text-sm font-medium text-blue-900 mb-2">Deal Score</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div>
          <span class="text-gray-600">Overall:</span>
          <span class="font-medium ml-1">{{ (dealScore.overall * 100).toFixed(0) }}%</span>
        </div>
        <div>
          <span class="text-gray-600">Credit:</span>
          <span class="font-medium ml-1">{{ (dealScore.credit * 100).toFixed(0) }}%</span>
        </div>
        <div>
          <span class="text-gray-600">Legal:</span>
          <span class="font-medium ml-1">{{ (dealScore.legal * 100).toFixed(0) }}%</span>
        </div>
        <div>
          <span class="text-gray-600">Liquidity:</span>
          <span class="font-medium ml-1">{{ (dealScore.liquidity * 100).toFixed(0) }}%</span>
        </div>
      </div>
    </div>

    <div v-if="error" class="bg-red-50 p-4 rounded-md text-red-700">
      {{ error }}
    </div>

    <button
      type="submit"
      :disabled="loading"
      class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
    >
      {{ loading ? 'Processing...' : 'Create Booking' }}
    </button>
  </form>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

interface Property {
  id: number;
  title: string;
  price: number;
}

interface BusinessGroup {
  id: number;
  name: string;
}

interface DealScore {
  overall: number;
  credit: number;
  legal: number;
  liquidity: number;
  recommended: boolean;
}

const props = defineProps<{
  properties: Property[];
  businessGroups: BusinessGroup[];
}>();

const emit = defineEmits<{
  bookingCreated: [booking: any];
}>();

const form = ref({
  property_id: '',
  viewing_slot: '',
  amount: 0,
  use_escrow: false,
  business_group_id: '',
  inn: '',
  face_id_token: '',
});

const loading = ref(false);
const error = ref('');
const dealScore = ref<DealScore | null>(null);

const isB2B = computed(() => !!(form.value.business_group_id && form.value.inn));

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
  }).format(price);
};

const handleSubmit = async () => {
  loading.value = true;
  error.value = '';

  try {
    const correlationId = crypto.randomUUID();
    const response = await axios.post('/api/v1/real-estate/bookings', {
      ...form.value,
      correlation_id: correlationId,
    }, {
      headers: {
        'X-Correlation-ID': correlationId,
      },
    });

    dealScore.value = response.data.data.deal_score;
    emit('bookingCreated', response.data.data);
  } catch (err: any) {
    error.value = err.response?.data?.error || 'Failed to create booking';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (props.properties.length > 0) {
    form.value.property_id = props.properties[0].id.toString();
    form.value.amount = props.properties[0].price;
  }
});
</script>
