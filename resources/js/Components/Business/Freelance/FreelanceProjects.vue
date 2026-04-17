<template>
  <div class="freelance-projects">
    <div class="header">
      <h2>Freelance Projects</h2>
      <button @click="addProject" class="btn-primary">Add Project</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="open">Open</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <select v-model="categoryFilter">
        <option value="">All Categories</option>
        <option value="development">Development</option>
        <option value="design">Design</option>
        <option value="writing">Writing</option>
        <option value="marketing">Marketing</option>
      </select>
    </div>

    <div class="projects-grid">
      <div v-for="project in filteredProjects" :key="project.id" class="project-card">
        <div class="project-header">
          <span class="project-name">{{ project.name }}</span>
          <span :class="['status-badge', project.status]">{{ project.status }}</span>
        </div>
        <div class="project-details">
          <p class="client">Client: {{ project.client }}</p>
          <p class="category">{{ project.category }}</p>
          <div class="budget">Budget: {{ formatCurrency(project.budget) }}</div>
          <div class="deadline">Deadline: {{ formatDate(project.deadline) }}</div>
          <div class="freelancer">Freelancer: {{ project.freelancer || 'Unassigned' }}</div>
        </div>
        <div class="project-actions">
          <button @click="viewProject(project)" class="btn-sm">View</button>
          <button @click="editProject(project)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Project {
  id: number
  name: string
  client: string
  category: string
  budget: number
  deadline: string
  freelancer: string
  status: string
}

const projects = ref<Project[]>([])
const statusFilter = ref('')
const categoryFilter = ref('')

const filteredProjects = computed(() => {
  return projects.value.filter(project => {
    if (statusFilter.value && project.status !== statusFilter.value) return false
    if (categoryFilter.value && project.category !== categoryFilter.value) return false
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
    const response = await fetch('/api/freelance/projects')
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
.freelance-projects {
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

.projects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.project-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.project-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.project-name {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge.open {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.in_progress {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.completed {
  background: #e5e7eb;
  color: #374151;
}

.status-badge.cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.project-details {
  padding: 16px;
}

.client, .category {
  margin: 0 0 8px 0;
  font-size: 12px;
  color: #6b7280;
}

.budget {
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 600;
  color: #059669;
}

.deadline, .freelancer {
  margin-bottom: 4px;
  font-size: 13px;
  color: #374151;
}

.project-actions {
  padding: 12px 16px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 8px;
}

.btn-sm {
  flex: 1;
  padding: 8px;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}
</style>
