<template>
  <div class="fitness-workouts">
    <div class="header">
      <h2>Workout Plans</h2>
      <button @click="addWorkout" class="btn-primary">Add Workout</button>
    </div>

    <div class="filters">
      <select v-model="difficultyFilter">
        <option value="">All Levels</option>
        <option value="beginner">Beginner</option>
        <option value="intermediate">Intermediate</option>
        <option value="advanced">Advanced</option>
      </select>
      <select v-model="typeFilter">
        <option value="">All Types</option>
        <option value="cardio">Cardio</option>
        <option value="strength">Strength</option>
        <option value="flexibility">Flexibility</option>
        <option value="hiit">HIIT</option>
      </select>
    </div>

    <div class="workouts-grid">
      <div v-for="workout in filteredWorkouts" :key="workout.id" class="workout-card">
        <div class="workout-header">
          <span class="workout-type">{{ workout.type }}</span>
          <span :class="['difficulty-badge', workout.difficulty]">{{ workout.difficulty }}</span>
        </div>
        <div class="workout-details">
          <h3>{{ workout.name }}</h3>
          <p class="description">{{ workout.description }}</p>
          <div class="stats">
            <span>{{ workout.duration }} min</span>
            <span>{{ workout.exercises }} exercises</span>
            <span>{{ workout.calories }} cal</span>
          </div>
          <div class="equipment">
            <span v-for="item in workout.equipment" :key="item" class="equipment-tag">{{ item }}</span>
          </div>
        </div>
        <div class="workout-actions">
          <button @click="viewWorkout(workout)" class="btn-sm">View</button>
          <button @click="editWorkout(workout)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Workout {
  id: number
  name: string
  type: string
  difficulty: string
  description: string
  duration: number
  exercises: number
  calories: number
  equipment: string[]
}

const workouts = ref<Workout[]>([])
const difficultyFilter = ref('')
const typeFilter = ref('')

const filteredWorkouts = computed(() => {
  return workouts.value.filter(workout => {
    if (difficultyFilter.value && workout.difficulty !== difficultyFilter.value) return false
    if (typeFilter.value && workout.type !== typeFilter.value) return false
    return true
  })
})

const addWorkout = () => {
  // Open modal to add new workout
}

const viewWorkout = (workout: Workout) => {
  // Open workout details
}

const editWorkout = (workout: Workout) => {
  // Open edit modal
}

const fetchWorkouts = async () => {
  try {
    const response = await fetch('/api/fitness/workouts')
    const data = await response.json()
    workouts.value = data
  } catch (error) {
    console.error('Failed to fetch workouts:', error)
  }
}

onMounted(() => {
  fetchWorkouts()
})
</script>

<style scoped>
.fitness-workouts {
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

.workouts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.workout-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.workout-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.workout-type {
  font-size: 12px;
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
}

.difficulty-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}

.difficulty-badge.beginner {
  background: #d1fae5;
  color: #065f46;
}

.difficulty-badge.intermediate {
  background: #dbeafe;
  color: #1e40af;
}

.difficulty-badge.advanced {
  background: #fef3c7;
  color: #92400e;
}

.workout-details {
  padding: 16px;
}

.workout-details h3 {
  margin: 0 0 8px 0;
  font-size: 16px;
  font-weight: 600;
}

.description {
  margin: 0 0 12px 0;
  font-size: 13px;
  color: #6b7280;
  line-height: 1.5;
}

.stats {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
  font-size: 13px;
  color: #374151;
}

.equipment {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 12px;
}

.equipment-tag {
  background: #f3f4f6;
  color: #4b5563;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
}

.workout-actions {
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
