<template>
  <div class="fitness-memberships">
    <div class="header">
      <h2>Membership Management</h2>
      <button @click="addMembership" class="btn-primary">Add Membership</button>
    </div>

    <div class="memberships-table">
      <table>
        <thead>
          <tr>
            <th>Member</th>
            <th>Plan</th>
            <th>Status</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Remaining</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="membership in memberships" :key="membership.id">
            <td>{{ membership.member }}</td>
            <td>{{ membership.plan }}</td>
            <td>
              <span :class="['status-badge', membership.status]">{{ membership.status }}</span>
            </td>
            <td>{{ formatDate(membership.start_date) }}</td>
            <td>{{ formatDate(membership.end_date) }}</td>
            <td>{{ membership.remaining_days }} days</td>
            <td>
              <button @click="viewMembership(membership)" class="btn-sm">View</button>
              <button @click="editMembership(membership)" class="btn-sm">Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Membership {
  id: number
  member: string
  plan: string
  status: string
  start_date: string
  end_date: string
  remaining_days: number
}

const memberships = ref<Membership[]>([])

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const addMembership = () => {
  // Open modal to add new membership
}

const viewMembership = (membership: Membership) => {
  // Open membership details
}

const editMembership = (membership: Membership) => {
  // Open edit modal
}

const fetchMemberships = async () => {
  try {
    const response = await fetch('/api/fitness/memberships')
    const data = await response.json()
    memberships.value = data
  } catch (error) {
    console.error('Failed to fetch memberships:', error)
  }
}

onMounted(() => {
  fetchMemberships()
})
</script>

<style scoped>
.fitness-memberships {
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

.memberships-table {
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

.status-badge.expired {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
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
