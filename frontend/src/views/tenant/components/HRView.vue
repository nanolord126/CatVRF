<template>
  <div class="space-y-6">
    <!-- HR Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">Вакансии</p>
        <p class="text-2xl font-bold text-blue-600">{{ stats.vacancies }}</p>
        <p class="mt-2 text-xs text-gray-500">Открытых позиций</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">Кандидаты</p>
        <p class="text-2xl font-bold text-green-600">{{ stats.candidates }}</p>
        <p class="mt-2 text-xs text-gray-500">{{ stats.newCandidates }} новых</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">Интервью</p>
        <p class="text-2xl font-bold text-yellow-600">{{ stats.interviews }}</p>
        <p class="mt-2 text-xs text-gray-500">На этой неделе</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">Текучесть</p>
        <p class="text-2xl font-bold" :class="stats.turnover < 10 ? 'text-green-600' : 'text-red-600'">
          {{ stats.turnover }}%
        </p>
        <p class="mt-2 text-xs text-gray-500">За месяц</p>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <button class="p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow text-left">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-900">Создать вакансию</p>
            <p class="text-sm text-gray-500">Новая позиция</p>
          </div>
        </div>
      </button>
      <button class="p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow text-left">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-green-100 rounded-lg">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-900">Кандидаты</p>
            <p class="text-sm text-gray-500">Управление</p>
          </div>
        </div>
      </button>
      <button class="p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow text-left">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-purple-100 rounded-lg">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-900">Онбординг</p>
            <p class="text-sm text-gray-500">Новые сотрудники</p>
          </div>
        </div>
      </button>
    </div>

    <!-- Vacancies -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-900">Открытые вакансии</h3>
          <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
            Все вакансии
          </button>
        </div>
      </div>
      <div class="p-4">
        <div v-for="vacancy in vacancies" :key="vacancy.id" class="p-4 border rounded-lg mb-4 last:mb-0 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-2">
            <div>
              <p class="font-medium text-gray-900">{{ vacancy.title }}</p>
              <p class="text-sm text-gray-500">{{ vacancy.department }} • {{ vacancy.location }}</p>
            </div>
            <span :class="getVacancyStatusClass(vacancy.status)" class="px-3 py-1 rounded-full text-xs font-medium">
              {{ getVacancyStatusLabel(vacancy.status) }}
            </span>
          </div>
          <div class="flex items-center justify-between mt-3">
            <div class="flex space-x-4 text-sm text-gray-500">
              <span>Зарплата: {{ vacancy.salary }}</span>
              <span>Откликов: {{ vacancy.applications }}</span>
              <span>Опубликовано: {{ vacancy.published }}</span>
            </div>
            <div class="flex space-x-2">
              <button class="px-3 py-1 border rounded text-sm hover:bg-gray-50">Редактировать</button>
              <button class="px-3 py-1 border rounded text-sm hover:bg-gray-50">Кандидаты</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Upcoming Interviews -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-900">Ближайшие интервью</h3>
      </div>
      <div class="p-4">
        <div v-for="interview in interviews" :key="interview.id" class="py-3 border-b border-gray-100 last:border-0">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 font-semibold">
                {{ interview.initials }}
              </div>
              <div>
                <p class="font-medium text-gray-900">{{ interview.candidate }}</p>
                <p class="text-sm text-gray-500">{{ interview.position }}</p>
              </div>
            </div>
            <div class="text-right">
              <p class="font-medium text-gray-900">{{ interview.dateTime }}</p>
              <p class="text-sm text-gray-500">{{ interview.interviewer }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const stats = ref({
  vacancies: 5,
  candidates: 23,
  newCandidates: 8,
  interviews: 12,
  turnover: 4.5,
});

const vacancies = ref([
  {
    id: 1,
    title: 'Повар горячего цеха',
    department: 'Кухня',
    location: 'Основной ресторан',
    status: 'active',
    salary: '55 000 - 70 000 ₽',
    applications: 12,
    published: '20.04.2026',
  },
  {
    id: 2,
    title: 'Официант',
    department: 'Зал',
    location: 'Основной ресторан',
    status: 'active',
    salary: '40 000 - 55 000 ₽',
    applications: 8,
    published: '18.04.2026',
  },
  {
    id: 3,
    title: 'Управляющий складом',
    department: 'Склад',
    location: 'Склад №1',
    status: 'draft',
    salary: '70 000 - 90 000 ₽',
    applications: 3,
    published: '-',
  },
]);

const interviews = ref([
  {
    id: 1,
    candidate: 'Смирнов Алексей',
    initials: 'СА',
    position: 'Повар',
    dateTime: '28.04.2026 10:00',
    interviewer: 'Иванов И.И.',
  },
  {
    id: 2,
    candidate: 'Козлова Елена',
    initials: 'КЕ',
    position: 'Официант',
    dateTime: '28.04.2026 14:00',
    interviewer: 'Петрова А.С.',
  },
  {
    id: 3,
    candidate: 'Новиков Дмитрий',
    initials: 'НД',
    position: 'Повар',
    dateTime: '29.04.2026 11:00',
    interviewer: 'Иванов И.И.',
  },
]);

function getVacancyStatusClass(status: string): string {
  const classes = {
    active: 'bg-green-100 text-green-800',
    draft: 'bg-gray-100 text-gray-800',
    closed: 'bg-red-100 text-red-800',
  };
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800';
}

function getVacancyStatusLabel(status: string): string {
  const labels = {
    active: 'Активна',
    draft: 'Черновик',
    closed: 'Закрыта',
  };
  return labels[status as keyof typeof labels] || status;
}
</script>
