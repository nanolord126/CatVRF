<template>
  <div class="space-y-6">
    <!-- Settings Navigation -->
    <div class="bg-white rounded-lg shadow">
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

      <!-- General Settings -->
      <div v-if="activeTab === 'general'" class="p-6 space-y-6">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Общие настройки</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Название компании</label>
              <input type="text" v-model="settings.companyName" class="w-full px-3 py-2 border rounded-lg" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">ИНН</label>
              <input type="text" v-model="settings.inn" class="w-full px-3 py-2 border rounded-lg" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input type="email" v-model="settings.email" class="w-full px-3 py-2 border rounded-lg" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
              <input type="tel" v-model="settings.phone" class="w-full px-3 py-2 border rounded-lg" />
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Адрес</label>
              <input type="text" v-model="settings.address" class="w-full px-3 py-2 border rounded-lg" />
            </div>
          </div>
        </div>

        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Часовой пояс</h3>
          <select v-model="settings.timezone" class="w-full md:w-64 px-3 py-2 border rounded-lg">
            <option value="Europe/Moscow">Москва (UTC+3)</option>
            <option value="Europe/Kaliningrad">Калининград (UTC+2)</option>
            <option value="Asia/Yekaterinburg">Екатеринбург (UTC+5)</option>
            <option value="Asia/Novosibirsk">Новосибирск (UTC+7)</option>
          </select>
        </div>

        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Валюты</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Основная валюта</label>
              <select v-model="settings.currency" class="w-full px-3 py-2 border rounded-lg">
                <option value="RUB">Российский рубль (₽)</option>
                <option value="USD">Доллар США ($)</option>
                <option value="EUR">Евро (€)</option>
              </select>
            </div>
          </div>
        </div>

        <div class="flex justify-end">
          <button class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Сохранить изменения
          </button>
        </div>
      </div>

      <!-- Notifications Settings -->
      <div v-if="activeTab === 'notifications'" class="p-6 space-y-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Уведомления</h3>
        
        <div class="space-y-4">
          <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
              <p class="font-medium text-gray-900">Email уведомления</p>
              <p class="text-sm text-gray-500">Получать уведомления на email</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="notifications.email" class="sr-only peer" />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
          </div>

          <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
              <p class="font-medium text-gray-900">SMS уведомления</p>
              <p class="text-sm text-gray-500">Получать SMS для важных событий</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="notifications.sms" class="sr-only peer" />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
          </div>

          <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
              <p class="font-medium text-gray-900">Push уведомления</p>
              <p class="text-sm text-gray-500">Push уведомления в браузере</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="notifications.push" class="sr-only peer" />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
          </div>

          <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
              <p class="font-medium text-gray-900">Telegram</p>
              <p class="text-sm text-gray-500">Уведомления в Telegram боте</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="notifications.telegram" class="sr-only peer" />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
          </div>
        </div>
      </div>

      <!-- Security Settings -->
      <div v-if="activeTab === 'security'" class="p-6 space-y-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Безопасность</h3>
        
        <div class="space-y-4">
          <div class="p-4 bg-gray-50 rounded-lg">
            <p class="font-medium text-gray-900 mb-2">Смена пароля</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Текущий пароль</label>
                <input type="password" class="w-full px-3 py-2 border rounded-lg" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Новый пароль</label>
                <input type="password" class="w-full px-3 py-2 border rounded-lg" />
              </div>
            </div>
            <button class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
              Изменить пароль
            </button>
          </div>

          <div class="p-4 bg-gray-50 rounded-lg">
            <p class="font-medium text-gray-900 mb-2">Двухфакторная аутентификация</p>
            <p class="text-sm text-gray-500 mb-4">Дополнительный уровень безопасности для вашего аккаунта</p>
            <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
              Включить 2FA
            </button>
          </div>

          <div class="p-4 bg-gray-50 rounded-lg">
            <p class="font-medium text-gray-900 mb-2">API ключи</p>
            <p class="text-sm text-gray-500 mb-4">Управление ключами для API доступа</p>
            <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
              Управлять ключами
            </button>
          </div>
        </div>
      </div>

      <!-- Integrations Settings -->
      <div v-if="activeTab === 'integrations'" class="p-6 space-y-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Интеграции</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="integration in integrations" :key="integration.id" class="p-4 border rounded-lg">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center space-x-3">
                <div :class="integration.iconBg" class="p-2 rounded-lg">
                  <svg :class="integration.iconColor" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ integration.name }}</p>
                  <p class="text-sm text-gray-500">{{ integration.description }}</p>
                </div>
              </div>
              <span :class="integration.connected ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="px-2 py-1 rounded text-xs font-medium">
                {{ integration.connected ? 'Подключено' : 'Не подключено' }}
              </span>
            </div>
            <button :class="integration.connected ? 'border border-gray-300 hover:bg-gray-50' : 'bg-blue-600 text-white hover:bg-blue-700'" class="w-full px-4 py-2 rounded-lg text-sm">
              {{ integration.connected ? 'Настроить' : 'Подключить' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const activeTab = ref('general');

const tabs = [
  { id: 'general', label: 'Общие' },
  { id: 'notifications', label: 'Уведомления' },
  { id: 'security', label: 'Безопасность' },
  { id: 'integrations', label: 'Интеграции' },
];

const settings = ref({
  companyName: 'ООО Пример Компания',
  inn: '1234567890',
  email: 'info@example.com',
  phone: '+7 (900) 123-45-67',
  address: 'г. Москва, ул. Примерная, д. 1',
  timezone: 'Europe/Moscow',
  currency: 'RUB',
});

const notifications = ref({
  email: true,
  sms: true,
  push: false,
  telegram: true,
});

const integrations = ref([
  { id: 1, name: '1С', description: 'Интеграция с 1С:Предприятие', connected: true, iconBg: 'bg-yellow-100', iconColor: 'text-yellow-600' },
  { id: 2, name: 'Банк', description: 'Эквайринг и выплаты', connected: true, iconBg: 'bg-blue-100', iconColor: 'text-blue-600' },
  { id: 3, name: 'CRM', description: 'Внешняя CRM система', connected: false, iconBg: 'bg-green-100', iconColor: 'text-green-600' },
  { id: 4, name: 'Аналитика', description: 'Google Analytics', connected: true, iconBg: 'bg-red-100', iconColor: 'text-red-600' },
]);
</script>
