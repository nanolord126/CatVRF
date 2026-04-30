<template>
  <div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">Всего сотрудников</p>
        <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
        <p class="mt-2 text-xs text-green-600">{{ stats.active }} активных</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">Онлайн</p>
        <p class="text-2xl font-bold text-green-600">{{ stats.online }}</p>
        <p class="mt-2 text-xs text-gray-500">Сейчас на смене</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">На смене</p>
        <p class="text-2xl font-bold text-blue-600">{{ stats.onShift }}</p>
        <p class="mt-2 text-xs text-gray-500">Текущая смена</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">Отпуск</p>
        <p class="text-2xl font-bold text-yellow-600">{{ stats.onLeave }}</p>
        <p class="mt-2 text-xs text-gray-500">В отпуске</p>
      </div>
    </div>

    <!-- Staff List -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-900">Сотрудники</h3>
          <div class="flex space-x-3">
            <input type="text" placeholder="Поиск..." class="px-3 py-2 border rounded-lg text-sm" />
            <select class="px-3 py-2 border rounded-lg text-sm">
              <option>Все отделы</option>
              <option>Кухня</option>
              <option>Зал</option>
              <option>Уборка</option>
            </select>
            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
              Добавить
            </button>
          </div>
        </div>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сотрудник</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Должность</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Смена</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="staff in staffList" :key="staff.id">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                      {{ staff.initials }}
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ staff.name }}</div>
                    <div class="text-sm text-gray-500">{{ staff.email }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ staff.position }}</div>
                <div class="text-sm text-gray-500">{{ staff.department }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusClass(staff.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                  {{ getStatusLabel(staff.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ staff.shift || '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button class="text-blue-600 hover:text-blue-900 mr-3">Просмотр</button>
                <button class="text-gray-600 hover:text-gray-900">Редактировать</button>
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
  total: 47,
  active: 42,
  online: 18,
  onShift: 15,
  onLeave: 5,
});

const staffList = ref([
  { id: 1, name: 'Иванов Иван Иванович', email: 'ivanov@example.com', position: 'Шеф-повар', department: 'Кухня', status: 'online', shift: 'Утренняя', initials: 'ИИ' },
  { id: 2, name: 'Петрова Анна Сергеевна', email: 'petrova@example.com', position: 'Официант', department: 'Зал', status: 'on_shift', shift: 'Утренняя', initials: 'ПА' },
  { id: 3, name: 'Сидоров Петр Петрович', email: 'sidorov@example.com', position: 'Повар', department: 'Кухня', status: 'offline', shift: '-', initials: 'СП' },
  { id: 4, name: 'Козлова Мария Владимировна', email: 'kozlova@example.com', position: 'Уборщица', department: 'Уборка', status: 'on_shift', shift: 'Утренняя', initials: 'КМ' },
  { id: 5, name: 'Новиков Дмитрий Александрович', email: 'novikov@example.com', position: 'Официант', department: 'Зал', status: 'on_leave', shift: '-', initials: 'НД' },
]);

function getStatusClass(status: string): string {
  const classes = {
    online: 'bg-green-100 text-green-800',
    on_shift: 'bg-blue-100 text-blue-800',
    offline: 'bg-gray-100 text-gray-800',
    on_leave: 'bg-yellow-100 text-yellow-800',
  };
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800';
}

function getStatusLabel(status: string): string {
  const labels = {
    online: 'Онлайн',
    on_shift: 'На смене',
    offline: 'Оффлайн',
    on_leave: 'В отпуске',
  };
  return labels[status as keyof typeof labels] || status;
}
</script>
