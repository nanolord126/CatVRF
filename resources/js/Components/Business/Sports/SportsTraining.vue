<template>
  <div class="sports-training">
    <div class="header">
      <h2>Training Programs</h2>
      <button @click="addProgram" class="btn-primary">Add Program</button>
    </div>

    <div class="filters">
      <select v-model="sportFilter">
        <option value="">All Sports</option>
        <option value="football">Football</option>
        <option value="basketball">Basketball</option>
        <option value="tennis">Tennis</option>
        <option value="swimming">Swimming</option>
      </select>
      <select v-model="levelFilter">
        <option value="">All Levels</option>
        <option value="beginner">Beginner</option>
        <option value="intermediate">Intermediate</option>
        <option value="professional">Professional</option>
      </select>
    </div>

    <div class="programs-grid">
      <div v-for="program in filteredPrograms" :key="program.id" class="program-card">
        <div class="program-header">
          <span class="sport">{{ program.sport }}</span>
          <span :class="['level-badge', program.level]">{{ program.level }}</span>
        </div>
        <div class="program-details">
          <h3>{{ program.name }}</h3>
          <p class="coach">Coach: {{ program.coach }}</p>
          <div class="schedule">
            <span>{{ program.sessions }}/week</span>
            <span>{{ program.duration }} weeks</span>
          </div>
          <div class="price">{{ formatCurrency(program.price) }}</div>
        </div>
        <div class="program-actions">
          <button @click="viewProgram(program)" class="btn-sm">View</button>
          <button @click="editProgram(program)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Program {
  id: number
  name: string
  sport: string
  level: string
  coach: string
  sessions: number
  duration: number
  price: number
}

const programs = ref<Program[]>([])
const sportFilter = ref('')
const levelFilter = ref('')

const filteredPrograms = computed(() => {
  return programs.value.filter(program => {
    if (sportFilter.value && program.sport !== sportFilter.value) return false
    if (levelFilter.value && program.level !== levelFilter.value) return false
    return true
  })
})

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const addProgram = () => {
  // Open modal to add new program
}

const viewProgram = (program: Program) => {
  // Open program details
}

const editProgram = (program: Program) => {
  // Open edit modal
}

const fetchPrograms = async () => {
  try {
    const response = await fetch('/api/sports/programs')
    const data = await response.json()
    programs.value = data
  } catch (error) {
    console.error('Failed to fetch programs:', error)
  }
}

onMounted(() => {
  fetchPrograms()
})
</script>

<style scoped>
.sports-training {
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

.programs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.program-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.program-header {
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

.level-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}

.level-badge.beginner {
  background: #d1fae5;
  color: #065f46;
}

.level-badge.intermediate {
  background: #dbeafe;
  color: #1e40af;
}

.level-badge.professional {
  background: #fef3c7;
  color: #92400e;
}

.program-details {
  padding: 16px;
}

.program-details h3 {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
}

.coach {
  margin: 0 0 12px 0;
  font-size: 13px;
  color: #6b7280;
}

.schedule {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
  font-size: 13px;
  color: #374151;
}

.price {
  font-size: 18px;
  font-weight: 600;
  color: #059669;
}

.program-actions {
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
