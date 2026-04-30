<template>
  <div class="ticket-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">#{{ ticket.id }}</h3>
          <p class="text-sm text-gray-500">{{ ticket.subject }}</p>
        </div>
        <span :class="getStatusClass(ticket.status)" class="px-3 py-1 rounded-full text-xs font-bold">
          {{ ticket.status }}
        </span>
      </div>
    </div>

    <!-- Priority -->
    <div class="mb-4">
      <span :class="getPriorityClass(ticket.priority)" class="px-2 py-1 rounded text-xs font-medium">
        {{ ticket.priority }}
      </span>
    </div>

    <!-- Customer Info -->
    <div class="flex items-center gap-2 mb-4">
      <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
        <span class="text-purple-600 font-bold text-xs">{{ customer.initials }}</span>
      </div>
      <div>
        <p class="text-sm font-medium text-gray-900">{{ customer.name }}</p>
        <p class="text-xs text-gray-500">{{ customer.email }}</p>
      </div>
    </div>

    <!-- Category & Assignee -->
    <div class="bg-gray-50 rounded-lg p-3 mb-4">
      <div class="space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Category:</span>
          <span class="font-medium">{{ ticket.category }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Assigned to:</span>
          <span class="font-medium">{{ ticket.assignee }}</span>
        </div>
      </div>
    </div>

    <!-- Time Info -->
    <div class="mb-4">
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Created:</span>
        <span class="font-medium">{{ ticket.createdAt }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Last Updated:</span>
        <span class="font-medium">{{ ticket.updatedAt }}</span>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="viewTicket"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        View Details
      </button>
      <button 
        @click="reply"
        class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Reply
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Customer {
  name: string
  initials: string
  email: string
}

interface SupportTicket {
  id: string
  subject: string
  status: 'Open' | 'In Progress' | 'Resolved' | 'Closed'
  priority: 'Low' | 'Medium' | 'High' | 'Urgent'
  category: string
  assignee: string
  createdAt: string
  updatedAt: string
}

const props = defineProps<{
  ticket: SupportTicket
  customer: Customer
}>()

const emit = defineEmits<{
  viewTicket: [ticket: SupportTicket]
  reply: [ticket: SupportTicket]
}>()

const getStatusClass = (status: string) => {
  const classes = {
    'Open': 'bg-blue-100 text-blue-900',
    'In Progress': 'bg-yellow-100 text-yellow-900',
    'Resolved': 'bg-green-100 text-green-900',
    'Closed': 'bg-gray-100 text-gray-900',
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const getPriorityClass = (priority: string) => {
  const classes = {
    'Low': 'bg-gray-100 text-gray-700',
    'Medium': 'bg-blue-100 text-blue-700',
    'High': 'bg-orange-100 text-orange-700',
    'Urgent': 'bg-red-100 text-red-700',
  }
  return classes[priority as keyof typeof classes] || 'bg-gray-100 text-gray-700'
}

const viewTicket = () => {
  emit('viewTicket', props.ticket)
}

const reply = () => {
  emit('reply', props.ticket)
}
</script>
