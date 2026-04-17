<script setup lang="ts">
import { ref } from 'vue';
import CarImportService from '@/services/CarImportService';

const vin = ref('');
const country = ref('');
const declaredValue = ref(0);
const currency = ref('eur');
const engineType = ref('petrol');
const engineVolume = ref<number | null>(null);
const manufactureYear = ref(new Date().getFullYear());
const isCalculating = ref(false);
const calculationResult = ref<any>(null);
const errorMessage = ref('');

const currencies = [
    { value: 'eur', label: 'EUR (Евро)', symbol: '€' },
    { value: 'usd', label: 'USD (Доллар США)', symbol: '$' },
    { value: 'jpy', label: 'JPY (Японская иена)', symbol: '¥' },
    { value: 'cny', label: 'CNY (Китайский юань)', symbol: '¥' },
    { value: 'krw', label: 'KRW (Южнокорейская вона)', symbol: '₩' },
];

const engineTypes = [
    { value: 'petrol', label: 'Бензин' },
    { value: 'diesel', label: 'Дизель' },
    { value: 'electric', label: 'Электро' },
    { value: 'hybrid', label: 'Гибрид' },
];

const calculateDuties = async () => {
    if (!vin.value || !country.value || !declaredValue.value) {
        errorMessage.value = 'Заполните все обязательные поля';
        return;
  }

  isCalculating.value = true;
  errorMessage.value = '';

  try {
    const result = await CarImportService.calculateCustomsDuties({
      vin: vin.value,
      country: country.value,
      declaredValue: declaredValue.value,
      currency: currency.value,
      engineType: engineType.value,
      engineVolume: engineVolume.value,
      manufactureYear: manufactureYear.value,
    });

    calculationResult.value = result;
  } catch (error: any) {
    errorMessage.value = error.message || 'Ошибка при расчете';
  } finally {
    isCalculating.value = false;
  }
};

const formatCurrency = (amount: number, curr: string) => {
  const currencyInfo = currencies.find(c => c.value === curr);
  const symbol = currencyInfo?.symbol || '';
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: curr.toUpperCase(),
  }).format(amount);
};

const resetCalculator = () => {
  calculationResult.value = null;
  errorMessage.value = '';
};
</script>

<template>
  <div class="car-import-calculator max-w-4xl mx-auto p-6 bg-white rounded-xl shadow-lg">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Калькулятор растаможки авто</h2>

    <div v-if="!calculationResult" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">VIN код</label>
          <input
            v-model="vin"
            type="text"
            maxlength="17"
            placeholder="X7X12345678901234"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase"
            @input="vin = vin.toUpperCase()"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Страна происхождения</label>
          <input
            v-model="country"
            type="text"
            placeholder="DE, JP, CN..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Объявленная стоимость</label>
          <input
            v-model.number="declaredValue"
            type="number"
            step="0.01"
            placeholder="10000"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Валюта</label>
          <select
            v-model="currency"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option v-for="curr in currencies" :key="curr.value" :value="curr.value">
              {{ curr.label }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Тип двигателя</label>
          <select
            v-model="engineType"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option v-for="type in engineTypes" :key="type.value" :value="type.value">
              {{ type.label }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Объем двигателя (л)</label>
          <input
            v-model.number="engineVolume"
            type="number"
            step="0.1"
            placeholder="2.0"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Год производства</label>
          <input
            v-model.number="manufactureYear"
            type="number"
            min="1900"
            :max="new Date().getFullYear() + 1"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
      </div>

      <div v-if="errorMessage" class="p-4 bg-red-50 text-red-700 rounded-lg">
        {{ errorMessage }}
      </div>

      <button
        @click="calculateDuties"
        :disabled="isCalculating || !vin || !country || !declaredValue"
        class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
      >
        {{ isCalculating ? 'Расчитываю...' : 'Рассчитать пошлины' }}
      </button>
    </div>

    <div v-else class="space-y-6">
      <div class="p-4 bg-green-50 text-green-700 rounded-lg">
        ✅ Расчет завершен
      </div>

      <div class="p-4 bg-gray-50 rounded-lg">
        <h3 class="font-semibold text-gray-900 mb-3">Курс валют</h3>
        <p class="text-sm text-gray-600">
          1 {{ calculationResult.currency.toUpperCase() }} = {{ calculationResult.exchange_rate }} RUB
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 bg-blue-50 rounded-lg">
          <h4 class="font-semibold text-gray-900 mb-2">Таможенная пошлина</h4>
          <p class="text-sm text-gray-600">Ставка: {{ (calculationResult.customs_duty.base_rate * 100).toFixed(0) }}%</p>
          <p class="text-lg font-bold text-blue-600">
            {{ formatCurrency(calculationResult.customs_duty.amount_rub, 'rub') }}
          </p>
        </div>

        <div class="p-4 bg-purple-50 rounded-lg">
          <h4 class="font-semibold text-gray-900 mb-2">Акцизный налог</h4>
          <p class="text-sm text-gray-600">Ставка: {{ (calculationResult.excise_tax.engine_rate * 100).toFixed(0) }}%</p>
          <p class="text-lg font-bold text-purple-600">
            {{ formatCurrency(calculationResult.excise_tax.amount_rub, 'rub') }}
          </p>
        </div>

        <div class="p-4 bg-yellow-50 rounded-lg">
          <h4 class="font-semibold text-gray-900 mb-2">НДС (20%)</h4>
          <p class="text-lg font-bold text-yellow-600">
            {{ formatCurrency(calculationResult.vat.amount_rub, 'rub') }}
          </p>
        </div>

        <div class="p-4 bg-orange-50 rounded-lg">
          <h4 class="font-semibold text-gray-900 mb-2">Утилизационный сбор</h4>
          <p class="text-lg font-bold text-orange-600">
            {{ formatCurrency(calculationResult.recycling_fee.amount_rub, 'rub') }}
          </p>
        </div>
      </div>

      <div class="p-4 bg-red-50 rounded-lg">
        <h3 class="font-semibold text-gray-900 mb-2">Общая сумма пошлин</h3>
        <p class="text-2xl font-bold text-red-600">
          {{ formatCurrency(calculationResult.total_duties.amount_rub, 'rub') }}
        </p>
      </div>

      <div class="p-4 bg-gray-100 rounded-lg">
        <h3 class="font-semibold text-gray-900 mb-2">Итоговая стоимость с пошлинами</h3>
        <p class="text-2xl font-bold text-gray-900">
          {{ formatCurrency(calculationResult.estimated_import_cost.amount_rub, 'rub') }}
        </p>
      </div>

      <div v-if="calculationResult.restrictions.length > 0" class="p-4 bg-yellow-50 text-yellow-800 rounded-lg">
        <h3 class="font-semibold mb-2">⚠️ Обнаружены ограничения:</h3>
        <ul class="list-disc list-inside space-y-1">
          <li v-for="(restriction, index) in calculationResult.restrictions" :key="index">
            {{ restriction.message }}
          </li>
        </ul>
      </div>

      <div class="flex gap-4">
        <button
          @click="resetCalculator"
          class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition-colors"
        >
          Новый расчет
        </button>
        <button
          class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
        >
          Начать импорт
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.car-import-calculator {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>
