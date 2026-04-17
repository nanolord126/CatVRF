<template>
  <div class="education-students">
    <div class="header">
      <h2>Students</h2>
      <button @click="addStudent" class="btn-primary">Add Student</button>
    </div>

    <div class="search-bar">
      <input v-model="searchQuery" type="text" placeholder="Search students..." class="search-input" />
      <select v-model="statusFilter" @change="fetchStudents" class="status-filter">
        <option value="">All Statuses</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
        <option value="graduated">Graduated</option>
      </select>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading students...</p>
    </div>

    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
      <button @click="fetchStudents" class="btn-sm">Retry</button>
    </div>

    <div v-else-if="filteredStudents.length === 0" class="empty-state">
      <p>No students found matching your criteria.</p>
    </div>

    <div v-else class="students-table">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Enrolled Courses</th>
            <th>Progress</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="student in filteredStudents" :key="student.id">
            <td>#{{ student.id }}</td>
            <td>{{ student.name }}</td>
            <td>{{ student.email }}</td>
            <td>{{ student.courses_count }}</td>
            <td>
              <div class="progress-bar">
                <div class="progress-fill" :style="{ width: student.progress + '%' }"></div>
              </div>
              <span class="progress-text">{{ student.progress }}%</span>
            </td>
            <td>
              <span :class="['status-badge', student.status]">{{ student.status }}</span>
            </td>
            <td>
              <button @click="viewStudent(student)" class="btn-sm">View</button>
              <button @click="editStudent(student)" class="btn-sm">Edit</button>
              <button @click="deleteStudent(student)" class="btn-sm btn-danger">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Student {
  id: number
  name: string
  email: string
  courses_count: number
  progress: number
  status: string
  enrolled_at?: string
  last_activity?: string
  total_hours?: number
}

const students = ref<Student[]>([])
const searchQuery = ref('')
const loading = ref(false)
const error = ref<string | null>(null)
const statusFilter = ref('')

const filteredStudents = computed(() => {
  let result = students.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(student =>
      student.name.toLowerCase().includes(query) ||
      student.email.toLowerCase().includes(query)
    )
  }

  if (statusFilter.value) {
    result = result.filter(student => student.status === statusFilter.value)
  }

  return result
})

const addStudent = async () => {
  console.log('Add student modal would open here')
}

const viewStudent = async (student: Student) => {
  try {
    const response = await fetch(`/api/education/students/${student.id}`)
    const studentDetails = await response.json()
    console.log('Student details:', studentDetails)
  } catch (error) {
    console.error('Failed to fetch student details:', error)
  }
}

const editStudent = async (student: Student) => {
  try {
    const response = await fetch(`/api/education/students/${student.id}`)
    const studentDetails = await response.json()
    console.log('Edit student:', studentDetails)
  } catch (error) {
    console.error('Failed to fetch student for editing:', error)
  }
}

const deleteStudent = async (student: Student) => {
  if (!confirm(`Are you sure you want to delete ${student.name}?`)) {
    return
  }

  try {
    const response = await fetch(`/api/education/students/${student.id}`, {
      method: 'DELETE',
    })

    if (response.ok) {
      students.value = students.value.filter(s => s.id !== student.id)
    } else {
      throw new Error('Failed to delete student')
    }
  } catch (error) {
    console.error('Failed to delete student:', error)
    error.value = 'Failed to delete student'
  }
}

const fetchStudents = async () => {
  loading.value = true
  error.value = null

  try {
    const params = new URLSearchParams()
    if (statusFilter.value) params.append('status', statusFilter.value)
    if (searchQuery.value) params.append('search', searchQuery.value)

    const response = await fetch(`/api/education/students?${params.toString()}`)

    if (!response.ok) {
      throw new Error('Failed to fetch students')
    }

    const data = await response.json()
    students.value = data.data || data
  } catch (err) {
    console.error('Failed to fetch students:', err)
    error.value = 'Failed to load students'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchStudents()
})
</script>

<style scoped>
.education-students {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.search-bar {
  margin-bottom: 20px;
  display: flex;
  gap: 12px;
}

.search-input {
  flex: 1;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.status-filter {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  min-width: 150px;
}

.students-table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

th {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
}

.progress-bar {
  width: 100px;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
  display: inline-block;
  margin-right: 8px;
}

.progress-fill {
  height: 100%;
  background: #3b82f6;
  border-radius: 4px;
}

.progress-text {
  font-size: 12px;
  color: #6b7280;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.inactive {
  background: #e5e7eb;
  color: #374151;
}

.status-badge.graduated {
  background: #dbeafe;
  color: #1e40af;
}

.btn-sm {
  padding: 6px 12px;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
  margin-right: 4px;
}

.btn-danger {
  border-color: #ef4444;
  color: #ef4444;
}

.btn-danger:hover {
  background: #ef4444;
  color: white;
}

.loading-state,
.error-state,
.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #6b7280;
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 16px;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
