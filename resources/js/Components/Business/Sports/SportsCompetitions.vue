<template>
  <div class="sports-competitions">
    <div class="header">
      <h2>Competitions</h2>
      <button @click="addCompetition" class="btn-primary">Add Competition</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="upcoming">Upcoming</option>
        <option value="ongoing">Ongoing</option>
        <option value="completed">Completed</option>
      </select>
      <select v-model="sportFilter">
        <option value="">All Sports</option>
        <option value="football">Football</option>
        <option value="basketball">Basketball</option>
        <option value="tennis">Tennis</option>
        <option value="swimming">Swimming</option>
      </select>
    </div>

    <div class="competitions-grid">
      <div v-for="competition in filteredCompetitions" :key="competition.id" class="competition-card">
        <div class="competition-header">
          <span class="sport">{{ competition.sport }}</span>
          <span :class="['status-badge', competition.status]">{{ competition.status }}</span>
        </div>
        <div class="competition-details">
          <h3>{{ competition.name }}</h3>
          <p class="location">{{ competition.location }}</p>
          <div class="dates">
            <span>{{ formatDate(competition.start_date) }}</span>
            <span>→</span>
            <span>{{ formatDate(competition.end_date) }}</span>
          </div>
          <div class="participants">{{ competition.participants }} participants</div>
        </div>
        <div class="competition-actions">
          <button @click="viewCompetition(competition)" class="btn-sm">View</button>
          <button @click="editCompetition(competition)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Competition {
  id: number
  name: string
  sport: string
  location: string
  start_date: string
  end_date: string
  participants: number
  status: string
}

const competitions = ref<Competition[]>([])
const statusFilter = ref('')
const sportFilter = ref('')

const filteredCompetitions = computed(() => {
  return competitions.value.filter(competition => {
    if (statusFilter.value && competition.status !== statusFilter.value) return false
    if (sportFilter.value && competition.sport !== sportFilter.value) return false
    return true
  })
})

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const addCompetition = () => {
  // Open modal to add new competition
}

const viewCompetition = (competition: Competition) => {
  // Open competition details
}

const editCompetition = (competition: Competition) => {
  // Open edit modal
}

const fetchCompetitions = async () => {
  try {
    const response = await fetch('/api/sports/competitions')
    const data = await response.json()
    competitions.value = data
  } catch (error) {
    console.error('Failed to fetch competitions:', error)
  }
}

onMounted(() => {
  fetchCompetitions()
})
</script>

<style scoped>
.sports-competitions {
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

.competitions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.competition-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.competition-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.sport {
  font-size: 12px;
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge.upcoming {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.ongoing {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.completed {
  background: #d1fae5;
  color: #065f46;
}

.competition-details {
  padding: 16px;
}

.competition-details h3 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.location {
  margin: 0 0 12px 0;
  font-size: 12px;
  color: #6b7280;
}

.dates {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 13px;
  color: #374151;
}

.participants {
  font-size: 12px;
  color: #6b7280;
}

.competition-actions {
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
