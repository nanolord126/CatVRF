<template>
  <div class="contact-list">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-lg font-semibold text-gray-900">Контакты</h3>
      <button
        @click="showCreateContact = true"
        class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition text-sm"
      >
        + Создать контакт
      </button>
    </div>

    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-cyan-600"></div>
    </div>

    <div v-else-if="contacts.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
      <p class="text-gray-600">Нет контактов</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="contact in contacts"
        :key="contact.id"
        class="bg-white border rounded-lg p-6 hover:shadow-md transition cursor-pointer"
        @click="viewContact(contact.id)"
      >
        <div class="flex items-center gap-4 mb-4">
          <div class="w-12 h-12 bg-cyan-100 rounded-full flex items-center justify-center">
            <span class="text-cyan-600 font-bold text-lg">{{ contact.initials }}</span>
          </div>
          <div>
            <h4 class="font-medium text-gray-900">{{ contact.name }}</h4>
            <p class="text-sm text-gray-600">{{ contact.company }}</p>
          </div>
        </div>

        <div class="space-y-2 text-sm text-gray-600">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <span>{{ contact.email }}</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
            <span>{{ contact.phone }}</span>
          </div>
        </div>

        <div class="mt-4 flex gap-2">
          <span
            :class="[
              'px-2 py-1 text-xs font-medium rounded',
              getTypeClass(contact.type)
            ]"
          >
            {{ getTypeLabel(contact.type) }}
          </span>
          <span
            v-if="contact.is_primary"
            class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded"
          >
            Основной
          </span>
        </div>
      </div>
    </div>

    <!-- Create contact modal -->
    <ContactCreate
      v-if="showCreateContact"
      @close="showCreateContact = false"
      @created="onContactCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Contact {
  id: number
  name: string
  initials: string
  company: string
  email: string
  phone: string
  type: 'customer' | 'supplier' | 'partner' | 'employee'
  is_primary: boolean
}

const contacts = ref<Contact[]>([])
const loading = ref(false)
const showCreateContact = ref(false)

const getTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    customer: 'Клиент',
    supplier: 'Поставщик',
    partner: 'Партнер',
    employee: 'Сотрудник'
  }
  return labels[type] || type
}

const getTypeClass = (type: string) => {
  const classes: Record<string, string> = {
    customer: 'bg-blue-100 text-blue-800',
    supplier: 'bg-green-100 text-green-800',
    partner: 'bg-purple-100 text-purple-800',
    employee: 'bg-orange-100 text-orange-800'
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const viewContact = (id: number) => {
  console.log('View contact:', id)
}

const onContactCreated = () => {
  showCreateContact.value = false
  // Reload contacts
}
</script>
