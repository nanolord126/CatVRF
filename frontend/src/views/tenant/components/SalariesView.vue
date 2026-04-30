<template>
  <div class="space-y-6">
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">ФОТ за месяц</p>
        <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(stats.fot) }}</p>
        <p class="mt-2 text-xs text-gray-500">Текущий месяц</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">К выплате</p>
        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats.toPay) }}</p>
        <p class="mt-2 text-xs text-gray-500">{{ stats.employeesCount } сотрудников</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">Бонусы</p>
        <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(stats.bonuses) }}</p>
        <p class="mt-2 text-xs text-gray-500">За текущий месяц</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">Налоги</p>
        <p class="text-2xl font-bold text-red-600">{{ formatCurrency(stats.taxes) }}</p>
        <p class="mt-2 text-xs text-gray-500">НДФЛ + соц. взносы</p>
      </div>
    </div>

    <!-- Payroll Actions -->
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-semibold text-gray-900">Расчетный период: Апрель 2026</h3>
          <p class="text-sm text-gray-500">Следующая выплата: 10 мая 2026</p>
        </div>
        <div class="flex space-x-3">
          <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
            Рассчитать
          </button>
          <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
            Сформировать ведомость
          </button>
          <button class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
            Выплатить
          </button>
        </div>
      </div>
    </div>

    <!-- Employee Salaries -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-900">Зарплаты сотрудников</h3>
          <div class="flex space-x-3">
            <select class="px-3 py-2 border rounded-lg text-sm">
              <option>Все отделы</option>
              <option>Кухня</option>
              <option>Зал</option>
              <option>Администрация</option>
            </select>
          </div>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Сотрудник</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Должность</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Оклад</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Премия</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Налоги</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">К выплате</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="employee in employees" :key="employee.id">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                    {{ employee.initials }}
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ employee.name }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ employee.position }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatCurrency(employee.salary) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                {{ formatCurrency(employee.bonus) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                {{ formatCurrency(employee.taxes) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                {{ formatCurrency(employee.toPay) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusClass(employee.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                  {{ getStatusLabel(employee.status) }}
                </span>
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
  fot: 2500000,
  toPay: 1800000,
  bonuses: 350000,
  taxes: 450000,
  employeesCount: 47,
});

const employees = ref([
  {
    id: 1,
    name: 'Иванов Иван Иванович',
    initials: 'ИИ',
    position: 'Шеф-повар',
    salary: 80000,
    bonus: 15000,
    taxes: 19000,
    toPay: 76000,
    status: 'paid',
  },
  {
    id: 2,
    name: 'Петрова Анна Сергеевна',
    initials: 'ПА',
    position: 'Официант',
    salary: 45000,
    bonus: 8000,
    taxes: 10600,
    toPay: 42400,
    status: 'pending',
  },
  {
    id: 3,
    name: 'Сидоров Петр Петрович',
    initials: 'СП',
    position: 'Повар',
    salary: 55000,
    bonus: 10000,
    taxes: 13000,
    toPay: 52000,
    status: 'pending',
  },
]);

function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(amount);
}

function getStatusClass(status: string): string {
  const classes = {
    paid: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
  };
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800';
}

function getStatusLabel(status: string): string {
  const labels = {
    paid: 'Выплачено',
    pending: 'Ожидает',
    processing: 'В обработке',
  };
  return labels[status as keyof typeof labels] || status;
}
</script>
