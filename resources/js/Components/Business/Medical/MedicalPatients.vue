<template>
  <div class="medical-patients">
    <div class="header">
      <h2>Patient Records</h2>
      <button @click="addPatient" class="btn-primary">Add Patient</button>
    </div>

    <div class="search-bar">
      <input v-model="searchQuery" type="text" placeholder="Search patients..." class="search-input" />
    </div>

    <div class="patients-table">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Date of Birth</th>
            <th>Phone</th>
            <th>Last Visit</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="patient in filteredPatients" :key="patient.id">
            <td>#{{ patient.id }}</td>
            <td>{{ patient.name }}</td>
            <td>{{ formatDate(patient.dob) }}</td>
            <td>{{ patient.phone }}</td>
            <td>{{ formatDate(patient.last_visit) }}</td>
            <td>
              <span :class="['status-badge', patient.status]">{{ patient.status }}</span>
            </td>
            <td>
              <button @click="viewPatient(patient)" class="btn-sm">View</button>
              <button @click="editPatient(patient)" class="btn-sm">Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Patient {
  id: number
  name: string
  dob: string
  phone: string
  last_visit: string
  status: string
}

const patients = ref<Patient[]>([])
const searchQuery = ref('')

const filteredPatients = computed(() => {
  if (!searchQuery.value) return patients.value
  const query = searchQuery.value.toLowerCase()
  return patients.value.filter(patient => 
    patient.name.toLowerCase().includes(query) ||
    patient.phone.includes(query)
  )
})

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const addPatient = () => {
  // Open modal to add new patient
}

const viewPatient = (patient: Patient) => {
  // Open patient details
}

const editPatient = (patient: Patient) => {
  // Open edit modal
}

const fetchPatients = async () => {
  try {
    const response = await fetch('/api/medical/patients')
    const data = await response.json()
    patients.value = data
  } catch (error) {
    console.error('Failed to fetch patients:', error)
  }
}

onMounted(() => {
  fetchPatients()
})
</script>

<style scoped>
.medical-patients {
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
}

.search-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.patients-table {
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

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.inactive {
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
