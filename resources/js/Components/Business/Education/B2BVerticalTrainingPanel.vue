<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'

interface VerticalCourse {
  id: number
  uuid: string
  course: {
    id: number
    title: string
    description: string
  }
  vertical: string
  target_role: string | null
  difficulty_level: string
  duration_hours: number
  is_required: boolean
}

interface EmployeeProgress {
  employee_id: number
  employee_name: string
  employee_email: string
  total_courses: number
  completed_courses: number
  in_progress_courses: number
  not_started_courses: number
  average_progress_percent: number
  completion_rate_percent: number
}

const props = defineProps<{
  vertical: string
}>()

const verticalName = computed(() => {
  const names: Record<string, string> = {
    beauty: 'Бьюти-салоны',
    hotels: 'Гостиницы',
    flowers: 'Флористика',
    auto: 'Автосервис',
    medical: 'Медицина',
    fitness: 'Фитнес',
    restaurants: 'Рестораны',
    pharmacy: 'Аптеки',
  }
  return names[props.vertical] || props.vertical
})

const courses = ref<VerticalCourse[]>([])
const employeesProgress = ref<EmployeeProgress[]>([])
const loading = ref(false)
const selectedRole = ref<string>('')
const selectedDifficulty = ref<string>('')
const activeTab = ref<'courses' | 'progress'>('courses')

const verticalRoles = computed(() => {
  const roles: Record<string, string[]> = {
    beauty: ['manager', 'master', 'administrator', 'receptionist'],
    hotels: ['manager', 'receptionist', 'housekeeper', 'concierge'],
    flowers: ['florist', 'manager', 'delivery', 'administrator'],
    auto: ['mechanic', 'manager', 'administrator', 'advisor'],
    medical: ['doctor', 'nurse', 'administrator', 'receptionist'],
    fitness: ['trainer', 'manager', 'receptionist', 'administrator'],
    restaurants: ['waiter', 'chef', 'manager', 'administrator'],
    pharmacy: ['pharmacist', 'manager', 'administrator', 'assistant'],
  }
  return roles[props.vertical] || []
})

const fetchCourses = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (selectedRole.value) params.append('target_role', selectedRole.value)
    if (selectedDifficulty.value) params.append('difficulty_level', selectedDifficulty.value)
    
    const response = await fetch(`/api/education/b2b/v1/verticals/${props.vertical}/courses?${params}`)
    const data = await response.json()
    courses.value = data.courses || []
  } catch (error) {
    console.error('Failed to fetch courses:', error)
  } finally {
    loading.value = false
  }
}

const fetchEmployeesProgress = async () => {
  loading.value = true
  try {
    const response = await fetch(`/api/education/b2b/v1/verticals/${props.vertical}/company/progress`)
    const data = await response.json()
    employeesProgress.value = data.employees || []
  } catch (error) {
    console.error('Failed to fetch employees progress:', error)
  } finally {
    loading.value = false
  }
}

const enrollEmployee = async (employeeId: number, courseId: number) => {
  try {
    const response = await fetch(`/api/education/b2b/v1/verticals/${props.vertical}/courses/${courseId}/enroll`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        employee_id: employeeId,
        contract_id: 1, // TODO: Получить из контекста
      }),
    })
    if (response.ok) {
      await fetchEmployeesProgress()
    }
  } catch (error) {
    console.error('Failed to enroll employee:', error)
  }
}

const getDifficultyColor = (level: string) => {
  const colors: Record<string, string> = {
    beginner: 'text-green-600',
    intermediate: 'text-yellow-600',
    advanced: 'text-red-600',
  }
  return colors[level] || 'text-gray-600'
}

const getDifficultyLabel = (level: string) => {
  const labels: Record<string, string> = {
    beginner: 'Начинающий',
    intermediate: 'Средний',
    advanced: 'Продвинутый',
  }
  return labels[level] || level
}

onMounted(() => {
  fetchCourses()
})
</script>

<template>
  <div class="b2b-vertical-training-panel">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold">B2B Обучение: {{ verticalName }}</h2>
      <div class="flex gap-2">
        <button
          @click="activeTab = 'courses'"
          :class="['px-4 py-2 rounded-lg', activeTab === 'courses' ? 'bg-blue-600 text-white' : 'bg-gray-200']"
        >
          Курсы
        </button>
        <button
          @click="activeTab = 'progress'"
          :class="['px-4 py-2 rounded-lg', activeTab === 'progress' ? 'bg-blue-600 text-white' : 'bg-gray-200']"
        >
          Прогресс сотрудников
        </button>
      </div>
    </div>

    <!-- Курсы -->
    <div v-if="activeTab === 'courses'">
      <div class="flex gap-4 mb-6">
        <select
          v-model="selectedRole"
          @change="fetchCourses"
          class="px-4 py-2 border rounded-lg"
        >
          <option value="">Все роли</option>
          <option v-for="role in verticalRoles" :key="role" :value="role">
            {{ role }}
          </option>
        </select>
        <select
          v-model="selectedDifficulty"
          @change="fetchCourses"
          class="px-4 py-2 border rounded-lg"
        >
          <option value="">Все уровни</option>
          <option value="beginner">Начинающий</option>
          <option value="intermediate">Средний</option>
          <option value="advanced">Продвинутый</option>
        </select>
      </div>

      <div v-if="loading" class="text-center py-8">Загрузка...</div>
      <div v-else-if="courses.length === 0" class="text-center py-8 text-gray-500">
        Курсы не найдены
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="course in courses"
          :key="course.id"
          class="border rounded-lg p-4 hover:shadow-lg transition-shadow"
        >
          <div class="flex items-center justify-between mb-2">
            <span
              v-if="course.is_required"
              class="px-2 py-1 bg-red-100 text-red-600 text-xs rounded"
            >
              Обязательный
            </span>
            <span
              :class="['px-2 py-1 text-xs rounded', getDifficultyColor(course.difficulty_level)]"
            >
              {{ getDifficultyLabel(course.difficulty_level) }}
            </span>
          </div>
          <h3 class="font-bold text-lg mb-2">{{ course.course.title }}</h3>
          <p class="text-gray-600 text-sm mb-4">{{ course.course.description }}</p>
          <div class="flex items-center justify-between text-sm text-gray-500">
            <span v-if="course.target_role">Роль: {{ course.target_role }}</span>
            <span v-if="course.duration_hours">{{ course.duration_hours }} ч</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Прогресс сотрудников -->
    <div v-if="activeTab === 'progress'">
      <button
        @click="fetchEmployeesProgress"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg mb-4"
      >
        Обновить данные
      </button>

      <div v-if="loading" class="text-center py-8">Загрузка...</div>
      <div v-else-if="employeesProgress.length === 0" class="text-center py-8 text-gray-500">
        Данные о прогрессе не найдены
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full border-collapse">
          <thead>
            <tr class="border-b">
              <th class="text-left p-3">Сотрудник</th>
              <th class="text-center p-3">Всего курсов</th>
              <th class="text-center p-3">Завершено</th>
              <th class="text-center p-3">В процессе</th>
              <th class="text-center p-3">Не начато</th>
              <th class="text-center p-3">Средний прогресс</th>
              <th class="text-center p-3">Т Completion</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="employee in employeesProgress"
              :key="employee.employee_id"
              class="border-b hover:bg-gray-50"
            >
              <td class="p-3">
                <div>
                  <div class="font-medium">{{ employee.employee_name }}</div>
                  <div class="text-sm text-gray-500">{{ employee.employee_email }}</div>
                </div>
              </td>
              <td class="text-center p-3">{{ employee.total_courses }}</td>
              <td class="text-center p-3 text-green-600">{{ employee.completed_courses }}</td>
              <td class="text-center p-3 text-yellow-600">{{ employee.in_progress_courses }}</td>
              <td class="text-center p-3 text-gray-400">{{ employee.not_started_courses }}</td>
              <td class="text-center p-3">{{ employee.average_progress_percent }}%</td>
              <td class="text-center p-3">
                <div class="flex items-center justify-center gap-2">
                  <div class="w-24 bg-gray-200 rounded-full h-2">
                    <div
                      class="bg-blue-600 h-2 rounded-full"
                      :style="{ width: employee.completion_rate_percent + '%' }"
                    ></div>
                  </div>
                  <span class="text-sm">{{ employee.completion_rate_percent }}%</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.b2b-vertical-training-panel {
  padding: 20px;
}
</style>
