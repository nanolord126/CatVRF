<template>
  <div class="task-list">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Tasks</h1>
        <p class="text-gray-600">Manage your internal tasks</p>
      </div>
      <button 
        @click="showCreateModal = true"
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        New Task
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="flex flex-wrap gap-4">
        <select v-model="filters.status" class="border border-gray-300 rounded-lg px-4 py-2">
          <option value="all">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="review">Review</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <select v-model="filters.priority" class="border border-gray-300 rounded-lg px-4 py-2">
          <option value="all">All Priorities</option>
          <option value="urgent">Urgent</option>
          <option value="high">High</option>
          <option value="medium">Medium</option>
          <option value="low">Low</option>
        </select>
        <select v-model="filters.type" class="border border-gray-300 rounded-lg px-4 py-2">
          <option value="all">All Types</option>
          <option value="notification">Notification</option>
          <option value="campaign">Campaign</option>
          <option value="report">Report</option>
          <option value="review">Review</option>
        </select>
        <label class="flex items-center gap-2">
          <input v-model="filters.overdueOnly" type="checkbox" class="rounded text-indigo-600" />
          <span>Overdue Only</span>
        </label>
      </div>
    </div>

    <!-- Task Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <TaskCard
        v-for="task in filteredTasks"
        :key="task.id"
        :task="task"
        @view-details="viewTask(task)"
        @update-status="updateTaskStatus(task, $event)"
        @mark-complete="markTaskComplete(task)"
      />
    </div>

    <!-- Empty State -->
    <div v-if="filteredTasks.length === 0" class="text-center py-16">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
      </svg>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No tasks found</h3>
      <p class="text-gray-600 mb-4">Create your first task to get started</p>
      <button 
        @click="showCreateModal = true"
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium transition-colors"
      >
        Create Task
      </button>
    </div>

    <!-- Create Task Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">Create New Task</h2>
            <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        <div class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
            <input
              v-model="newTask.title"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
              placeholder="Task title"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea
              v-model="newTask.description"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
              rows="3"
              placeholder="Task description"
            />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
              <select v-model="newTask.priority" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
              <select v-model="newTask.type" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <option value="notification">Notification</option>
                <option value="campaign">Campaign</option>
                <option value="report">Report</option>
                <option value="review">Review</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Due Date</label>
              <input
                v-model="newTask.due_date"
                type="datetime-local"
                class="w-full border border-gray-300 rounded-lg px-4 py-2"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Assignee</label>
              <select v-model="newTask.assignee_id" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <option :value="null">Unassigned</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
            </div>
          </div>
        </div>
        <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
          <button 
            @click="showCreateModal = false"
            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
          >
            Cancel
          </button>
          <button 
            @click="createTask"
            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors"
          >
            Create Task
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import TaskCard from './TaskCard.vue'

interface Task {
  id: number
  title: string
  description: string | null
  status: string
  priority: string
  type: string
  due_date: string | null
  progress: number
  is_overdue: boolean
  assignee: { name: string } | null
  controller: { name: string } | null
}

interface User {
  id: number
  name: string
}

const tasks = ref<Task[]>([])
const users = ref<User[]>([])
const showCreateModal = ref(false)

const filters = ref({
  status: 'all',
  priority: 'all',
  type: 'all',
  overdueOnly: false,
})

const newTask = ref({
  title: '',
  description: '',
  priority: 'medium',
  type: 'notification',
  due_date: '',
  assignee_id: null as number | null,
})

const filteredTasks = computed(() => {
  return tasks.value.filter(task => {
    const matchStatus = filters.value.status === 'all' || task.status === filters.value.status
    const matchPriority = filters.value.priority === 'all' || task.priority === filters.value.priority
    const matchType = filters.value.type === 'all' || task.type === filters.value.type
    const matchOverdue = !filters.value.overdueOnly || task.is_overdue
    return matchStatus && matchPriority && matchType && matchOverdue
  })
})

const loadTasks = async () => {
  // API call to load tasks
  const tenantId = 1 // Get from auth
  const response = await fetch(`/api/notifications/internal-tasks?tenant_id=${tenantId}`)
  const data = await response.json()
  tasks.value = data.data
}

const loadUsers = async () => {
  // API call to load users
  const response = await fetch('/api/users')
  const data = await response.json()
  users.value = data.data
}

const createTask = async () => {
  const response = await fetch('/api/notifications/internal-tasks', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      ...newTask.value,
      tenant_id: 1,
      creator_id: 1,
    }),
  })
  
  if (response.ok) {
    await loadTasks()
    showCreateModal.value = false
    newTask.value = {
      title: '',
      description: '',
      priority: 'medium',
      type: 'notification',
      due_date: '',
      assignee_id: null,
    }
  }
}

const viewTask = (task: Task) => {
  // Navigate to task details
  console.log('View task:', task.id)
}

const updateTaskStatus = async (task: Task, status: string) => {
  const response = await fetch(`/api/notifications/internal-tasks/${task.id}/status`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ status }),
  })
  
  if (response.ok) {
    await loadTasks()
  }
}

const markTaskComplete = async (task: Task) => {
  await updateTaskStatus(task, 'completed')
}

onMounted(() => {
  loadTasks()
  loadUsers()
})
</script>

<style scoped>
.task-list {
  padding: 2rem;
}
</style>
