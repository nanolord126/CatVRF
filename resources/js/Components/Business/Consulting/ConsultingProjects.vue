<template>
  <div class="consulting-projects">
    <div class="header">
      <h2>Consulting Projects</h2>
      <button @click="addProject" class="btn-primary">Add Project</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="proposal">Proposal</option>
        <option value="active">Active</option>
        <option value="on_hold">On Hold</option>
        <option value="completed">Completed</option>
      </select>
      <select v-model="typeFilter">
        <option value="">All Types</option>
        <option value="strategy">Strategy</option>
        <option value="operations">Operations</option>
        <option value="technology">Technology</option>
        <option value="finance">Finance</option>
      </select>
    </div>

    <div class="projects-table">
      <table>
        <thead>
          <tr>
            <th>Project ID</th>
            <th>Client</th>
            <th>Type</th>
            <th>Budget</th>
            <th>Start Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="project in filteredProjects" :key="project.id">
            <td>#{{ project.id }}</td>
            <td>{{ project.client }}</td>
            <td>{{ project.type }}</td>
            <td>{{ formatCurrency(project.budget) }}</td>
            <td>{{ formatDate(project.start_date) }}</td>
            <td>
              <span :class="['status-badge', project.status]">{{ project.status }}</span>
            </td>
            <td>
              <button @click="viewProject(project)" class="btn-sm">View</button>
              <button @click="editProject(project)" class="btn-sm">Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Project {
  id: number
  client: string
  type: string
  budget: number
  start_date: string
  status: string
}

const projects = ref<Project[]>([])
const statusFilter = ref('')
const typeFilter = ref('')

const filteredProjects = computed(() => {
  return projects.value.filter(project => {
    if (statusFilter.value && project.status !== statusFilter.value) return false
    if (typeFilter.value && project.type !== typeFilter.value) return false
    return true
  })
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const addProject = () => {
  // Open modal to add new project
}

const viewProject = (project: Project) => {
  // Open project details
}

const editProject = (project: Project) => {
  // Open edit modal
}

const fetchProjects = async () => {
  try {
    const response = await fetch('/api/consulting/projects')
    const data = await response.json()
    projects.value = data
  } catch (error) {
    console.error('Failed to fetch projects:', error)
  }
}

onMounted(() => {
  fetchProjects()
})
</script>

<style scoped>
.consulting-projects {
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

.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.filters select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.projects-table {
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

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.status-badge.proposal {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.on_hold {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.completed {
  background: #e5e7eb;
  color: #374151;
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
</style>
