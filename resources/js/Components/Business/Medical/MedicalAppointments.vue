<template>
  <div class="medical-appointments">
    <div class="header">
      <h2>Appointments</h2>
      <button @click="addAppointment" class="btn-primary">Add Appointment</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="scheduled">Scheduled</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <select v-model="typeFilter">
        <option value="">All Types</option>
        <option value="consultation">Consultation</option>
        <option value="checkup">Checkup</option>
        <option value="procedure">Procedure</option>
        <option value="followup">Follow-up</option>
      </select>
    </div>

    <div class="appointments-grid">
      <div v-for="appointment in filteredAppointments" :key="appointment.id" class="appointment-card">
        <div class="appointment-header">
          <span class="doctor">{{ appointment.doctor }}</span>
          <span :class="['status-badge', appointment.status]">{{ appointment.status }}</span>
        </div>
        <div class="appointment-details">
          <h3>{{ appointment.patient }}</h3>
          <p class="type">{{ appointment.type }}</p>
          <div class="datetime">
            <span>{{ formatDate(appointment.date) }}</span>
            <span>{{ appointment.time }}</span>
          </div>
          <p class="department">{{ appointment.department }}</p>
        </div>
        <div class="appointment-actions">
          <button @click="viewAppointment(appointment)" class="btn-sm">View</button>
          <button @click="editAppointment(appointment)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Appointment {
  id: number
  patient: string
  doctor: string
  type: string
  department: string
  date: string
  time: string
  status: string
}

const appointments = ref<Appointment[]>([])
const statusFilter = ref('')
const typeFilter = ref('')

const filteredAppointments = computed(() => {
  return appointments.value.filter(appointment => {
    if (statusFilter.value && appointment.status !== statusFilter.value) return false
    if (typeFilter.value && appointment.type !== typeFilter.value) return false
    return true
  })
})

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const addAppointment = () => {
  // Open modal to add new appointment
}

const viewAppointment = (appointment: Appointment) => {
  // Open appointment details
}

const editAppointment = (appointment: Appointment) => {
  // Open edit modal
}

const fetchAppointments = async () => {
  try {
    const response = await fetch('/api/medical/appointments')
    const data = await response.json()
    appointments.value = data
  } catch (error) {
    console.error('Failed to fetch appointments:', error)
  }
}

onMounted(() => {
  fetchAppointments()
})
</script>

<style scoped>
.medical-appointments {
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

.appointments-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.appointment-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.appointment-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.doctor {
  font-size: 12px;
  font-weight: 600;
  color: #374151;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge.scheduled {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.in_progress {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.completed {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.appointment-details {
  padding: 16px;
}

.appointment-details h3 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.type {
  margin: 0 0 12px 0;
  font-size: 12px;
  color: #6b7280;
}

.datetime {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 13px;
  color: #374151;
}

.department {
  margin: 0;
  font-size: 12px;
  color: #6b7280;
}

.appointment-actions {
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
