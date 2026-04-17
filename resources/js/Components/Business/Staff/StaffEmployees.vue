<template>
  <div class="staff-employees">
    <div class="header">
      <h2>Employees</h2>
      <button @click="addEmployee" class="btn-primary">Add Employee</button>
    </div>

    <div class="filters">
      <select v-model="departmentFilter">
        <option value="">All Departments</option>
        <option value="engineering">Engineering</option>
        <option value="sales">Sales</option>
        <option value="marketing">Marketing</option>
        <option value="hr">HR</option>
      </select>
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="on_leave">On Leave</option>
        <option value="terminated">Terminated</option>
      </select>
    </div>

    <div class="employees-table">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Department</th>
            <th>Position</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="employee in filteredEmployees" :key="employee.id">
            <td>#{{ employee.id }}</td>
            <td>{{ employee.name }}</td>
            <td>{{ employee.email }}</td>
            <td>{{ employee.department }}</td>
            <td>{{ employee.position }}</td>
            <td>
              <span :class="['status-badge', employee.status]">{{ employee.status }}</span>
            </td>
            <td>
              <button @click="viewEmployee(employee)" class="btn-sm">View</button>
              <button @click="editEmployee(employee)" class="btn-sm">Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Employee {
  id: number
  name: string
  email: string
  department: string
  position: string
  status: string
}

const employees = ref<Employee[]>([])
const departmentFilter = ref('')
const statusFilter = ref('')

const filteredEmployees = computed(() => {
  return employees.value.filter(employee => {
    if (departmentFilter.value && employee.department !== departmentFilter.value) return false
    if (statusFilter.value && employee.status !== statusFilter.value) return false
    return true
  })
}

const addEmployee = () => {
  // Open modal to add new employee
}

const viewEmployee = (employee: Employee) => {
  // Open employee details
}

const editEmployee = (employee: Employee) => {
  // Open edit modal
}

const fetchEmployees = async () => {
  try {
    const response = await fetch('/api/staff/employees')
    const data = await response.json()
    employees.value = data
  } catch (error) {
    console.error('Failed to fetch employees:', error)
  }
}

onMounted(() => {
  fetchEmployees()
})
</script>

<style scoped>
.staff-employees {
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

.employees-table {
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

.status-badge.on_leave {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.terminated {
  background: #fee2e2;
  color: #991b1b;
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
