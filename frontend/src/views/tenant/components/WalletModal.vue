<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
      <!-- Header -->
      <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-bold text-white">Кошелек</h2>
            <p class="text-blue-100 text-sm">Управление финансами</p>
          </div>
          <button @click="$emit('close')" class="text-white hover:text-blue-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Balance Card -->
      <div class="p-6 bg-gradient-to-br from-gray-900 to-gray-800">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-400 text-sm">Доступный баланс</p>
            <p class="text-3xl font-bold text-white">{{ formatBalance(balance) }} ₽</p>
          </div>
          <div class="flex space-x-3">
            <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
              Пополнить
            </button>
            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
              Вывести
            </button>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200">
        <nav class="flex space-x-8 px-6">
          <button 
            v-for="tab in tabs" 
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === tab.id 
                ? 'border-blue-500 text-blue-600' 
                : 'border-transparent text-gray-500 hover:text-gray-700'
            ]"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- Content -->
      <div class="p-6 overflow-y-auto max-h-[50vh]">
        <!-- Transactions -->
        <div v-if="activeTab === 'transactions'" class="space-y-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">История операций</h3>
            <input type="text" placeholder="Поиск..." class="px-3 py-2 border rounded-lg text-sm" />
          </div>
          
          <div v-for="tx in transactions" :key="tx.id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center space-x-3">
              <div :class="tx.type === 'in' ? 'bg-green-100' : 'bg-red-100'" class="p-2 rounded-lg">
                <svg v-if="tx.type === 'in'" class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                </svg>
                <svg v-else class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-gray-900">{{ tx.description }}</p>
                <p class="text-sm text-gray-500">{{ tx.date }}</p>
              </div>
            </div>
            <span :class="tx.type === 'in' ? 'text-green-600' : 'text-red-600'" class="font-semibold">
              {{ tx.type === 'in' ? '+' : '-' }}{{ formatCurrency(tx.amount) }}
            </span>
          </div>
        </div>

        <!-- Documents -->
        <div v-if="activeTab === 'documents'" class="space-y-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">Документы</h3>
            <button class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
              Сформировать документ
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="doc in documentTypes" :key="doc.id" class="p-4 border rounded-lg hover:border-blue-500 cursor-pointer transition-colors">
              <div class="flex items-center space-x-3">
                <div class="p-2 bg-blue-100 rounded-lg">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ doc.name }}</p>
                  <p class="text-sm text-gray-500">{{ doc.description }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Reconciliation -->
        <div v-if="activeTab === 'reconciliation'" class="space-y-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">Сверки и акты</h3>
            <button class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
              Создать сверку
            </button>
          </div>

          <div v-for="rec in reconciliations" :key="rec.id" class="p-4 border rounded-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="font-medium text-gray-900">{{ rec.name }}</p>
                <p class="text-sm text-gray-500">{{ rec.period }}</p>
              </div>
              <div class="flex items-center space-x-2">
                <span :class="rec.status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'" 
                      class="px-2 py-1 rounded text-xs font-medium">
                  {{ rec.status === 'completed' ? 'Завершено' : 'В процессе' }}
                </span>
                <button class="text-blue-600 hover:text-blue-800">Скачать</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Closing Documents -->
        <div v-if="activeTab === 'closing'" class="space-y-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">Закрывающие документы</h3>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div v-for="doc in closingDocs" :key="doc.id" class="p-4 border rounded-lg hover:border-blue-500 cursor-pointer transition-colors">
              <div class="text-center">
                <div class="p-3 bg-gray-100 rounded-lg mx-auto w-12 h-12 flex items-center justify-center mb-2">
                  <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <p class="font-medium text-gray-900 text-sm">{{ doc.name }}</p>
                <p class="text-xs text-gray-500">{{ doc.period }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

defineEmits(['close']);

const activeTab = ref('transactions');
const balance = ref(1250000);

const tabs = [
  { id: 'transactions', label: 'Операции' },
  { id: 'documents', label: 'Документы' },
  { id: 'reconciliation', label: 'Сверки' },
  { id: 'closing', label: 'Закрывающие' },
];

const transactions = ref([
  { id: 1, type: 'in', description: 'Оплата заказа #1234', amount: 15000, date: '27.04.2026 14:30' },
  { id: 2, type: 'out', description: 'Вывод средств', amount: 50000, date: '26.04.2026 10:15' },
  { id: 3, type: 'in', description: 'Оплата заказа #1233', amount: 8500, date: '26.04.2026 09:45' },
  { id: 4, type: 'in', description: 'Возврат заказа #1228', amount: 3200, date: '25.04.2026 16:20' },
]);

const documentTypes = ref([
  { id: 1, name: 'Счет на оплату', description: 'Формирование счета для клиента' },
  { id: 2, name: 'Акт выполненных работ', description: 'Подтверждение выполнения услуг' },
  { id: 3, name: 'Накладная', description: 'Товарная накладная ТОРГ-12' },
  { id: 4, name: 'УПД', description: 'Универсальный передаточный документ' },
  { id: 5, name: 'Счет-фактура', description: 'Для НДС' },
  { id: 6, name: 'Договор', description: 'Шаблон договора' },
]);

const reconciliations = ref([
  { id: 1, name: 'Сверка за апрель 2026', period: '01.04.2026 - 30.04.2026', status: 'completed' },
  { id: 2, name: 'Сверка за март 2026', period: '01.03.2026 - 31.03.2026', status: 'completed' },
  { id: 3, name: 'Сверка за февраль 2026', period: '01.02.2026 - 28.02.2026', status: 'completed' },
]);

const closingDocs = ref([
  { id: 1, name: 'Баланс', period: 'Апрель 2026' },
  { id: 2, name: 'Отчет о прибылях', period: 'Апрель 2026' },
  { id: 3, name: 'Отчет о движении средств', period: 'Апрель 2026' },
  { id: 4, name: 'Налоговая декларация', period: 'Q1 2026' },
]);

function formatBalance(balance: number): string {
  return new Intl.NumberFormat('ru-RU').format(balance);
}

function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(amount);
}
</script>
