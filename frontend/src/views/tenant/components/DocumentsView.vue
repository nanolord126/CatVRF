<template>
  <div class="space-y-6">
    <!-- Document Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">Всего документов</p>
        <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
        <p class="mt-2 text-xs text-gray-500">За все время</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">За месяц</p>
        <p class="text-2xl font-bold text-blue-600">{{ stats.thisMonth }}</p>
        <p class="mt-2 text-xs text-green-600">+{{ stats.growth }}% vs прошлый</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">На подписи</p>
        <p class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</p>
        <p class="mt-2 text-xs text-gray-500">Требуют подписи</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">Истекают</p>
        <p class="text-2xl font-bold text-red-600">{{ stats.expiring }}</p>
        <p class="mt-2 text-xs text-gray-500">В ближайшие 7 дней</p>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <button class="p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow text-left">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-900">Создать документ</p>
            <p class="text-sm text-gray-500">Новый шаблон</p>
          </div>
        </div>
      </button>
      <button class="p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow text-left">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-green-100 rounded-lg">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-900">Загрузить</p>
            <p class="text-sm text-gray-500">Импорт документов</p>
          </div>
        </div>
      </button>
      <button class="p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow text-left">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-purple-100 rounded-lg">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-900">Архив</p>
            <p class="text-sm text-gray-500">Все документы</p>
          </div>
        </div>
      </button>
    </div>

    <!-- Document Categories -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-900">Категории документов</h3>
          <div class="flex space-x-3">
            <select class="px-3 py-2 border rounded-lg text-sm">
              <option>Все типы</option>
              <option>Договоры</option>
              <option>Счета</option>
              <option>Акты</option>
            </select>
            <input type="text" placeholder="Поиск..." class="px-3 py-2 border rounded-lg text-sm" />
          </div>
        </div>
      </div>

      <div class="p-4">
        <div v-for="category in documentCategories" :key="category.id" class="mb-6 last:mb-0">
          <div class="flex items-center justify-between mb-3 cursor-pointer" @click="toggleCategory(category.id)">
            <div class="flex items-center space-x-3">
              <svg :class="category.expanded ? 'rotate-90' : ''" class="w-4 h-4 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
              <p class="font-medium text-gray-900">{{ category.name }}</p>
              <span class="text-sm text-gray-500">({{ category.count }})</span>
            </div>
            <button class="px-3 py-1 border rounded text-sm hover:bg-gray-50">
              + Добавить
            </button>
          </div>
          
          <div v-if="category.expanded" class="ml-7 space-y-2">
            <div v-for="doc in category.documents" :key="doc.id" class="p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                  <div class="p-2 bg-gray-100 rounded">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ doc.name }}</p>
                    <p class="text-xs text-gray-500">{{ doc.date }} • {{ doc.author }}</p>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <span :class="getDocStatusClass(doc.status)" class="px-2 py-1 rounded text-xs font-medium">
                    {{ getDocStatusLabel(doc.status) }}
                  </span>
                  <button class="text-blue-600 hover:text-blue-800 text-sm">Скачать</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Documents -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-900">Последние документы</h3>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Документ</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Тип</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Дата</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Автор</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="doc in recentDocs" :key="doc.id">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="p-2 bg-gray-100 rounded mr-3">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </div>
                  <span class="text-sm font-medium text-gray-900">{{ doc.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ doc.type }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ doc.date }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ doc.author }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getDocStatusClass(doc.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                  {{ getDocStatusLabel(doc.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button class="text-blue-600 hover:text-blue-900 mr-3">Скачать</button>
                <button class="text-gray-600 hover:text-gray-900">Просмотр</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const stats = ref({
  total: 1234,
  thisMonth: 87,
  growth: 12,
  pending: 5,
  expiring: 3,
});

const documentCategories = ref([
  {
    id: 1,
    name: 'Договоры',
    count: 45,
    expanded: true,
    documents: [
      { id: 1, name: 'Договор поставки №123', date: '27.04.2026', author: 'Иванов И.И.', status: 'signed' },
      { id: 2, name: 'Договор с клиентом №456', date: '26.04.2026', author: 'Петрова А.С.', status: 'pending' },
    ],
  },
  {
    id: 2,
    name: 'Счета',
    count: 234,
    expanded: false,
    documents: [],
  },
  {
    id: 3,
    name: 'Акты',
    count: 156,
    expanded: false,
    documents: [],
  },
  {
    id: 4,
    name: 'Накладные',
    count: 298,
    expanded: false,
    documents: [],
  },
]);

const recentDocs = ref([
  { id: 1, name: 'Счет №С-001234', type: 'Счет', date: '27.04.2026 14:30', author: 'Иванов И.И.', status: 'sent' },
  { id: 2, name: 'Акт выполненных работ №А-0056', type: 'Акт', date: '27.04.2026 11:15', author: 'Петрова А.С.', status: 'signed' },
  { id: 3, name: 'Договор поставки №Д-0078', type: 'Договор', date: '26.04.2026 16:45', author: 'Сидоров П.П.', status: 'draft' },
  { id: 4, name: 'Накладная ТОРГ-12 №Н-0123', type: 'Накладная', date: '26.04.2026 10:20', author: 'Козлова М.В.', status: 'signed' },
]);

function toggleCategory(id: number) {
  const category = documentCategories.value.find(c => c.id === id);
  if (category) {
    category.expanded = !category.expanded;
  }
}

function getDocStatusClass(status: string): string {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    pending: 'bg-yellow-100 text-yellow-800',
    signed: 'bg-green-100 text-green-800',
    sent: 'bg-blue-100 text-blue-800',
    rejected: 'bg-red-100 text-red-800',
  };
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800';
}

function getDocStatusLabel(status: string): string {
  const labels = {
    draft: 'Черновик',
    pending: 'На подписи',
    signed: 'Подписан',
    sent: 'Отправлен',
    rejected: 'Отклонен',
  };
  return labels[status as keyof typeof labels] || status;
}
</script>
