<template>
  <div class="luxury-experiences">
    <div class="header">
      <h2>Luxury Experiences</h2>
      <button @click="addExperience" class="btn-primary">Add Experience</button>
    </div>

    <div class="filters">
      <select v-model="categoryFilter">
        <option value="">All Categories</option>
        <option value="travel">Travel</option>
        <option value="dining">Dining</option>
        <option value="wellness">Wellness</option>
        <option value="entertainment">Entertainment</option>
      </select>
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="available">Available</option>
        <option value="booked">Booked</option>
        <option value="completed">Completed</option>
      </select>
    </div>

    <div class="experiences-grid">
      <div v-for="experience in filteredExperiences" :key="experience.id" class="experience-card">
        <div class="experience-image">
          <img :src="experience.image" :alt="experience.name" />
          <span class="badge-luxury">LUXURY</span>
        </div>
        <div class="experience-details">
          <h3>{{ experience.name }}</h3>
          <p class="category">{{ experience.category }}</p>
          <p class="description">{{ experience.description }}</p>
          <div class="duration">{{ experience.duration }}</div>
          <div class="price">{{ formatCurrency(experience.price) }}</div>
        </div>
        <div class="experience-actions">
          <button @click="viewExperience(experience)" class="btn-sm">View</button>
          <button @click="editExperience(experience)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Experience {
  id: number
  name: string
  category: string
  description: string
  duration: string
  price: number
  status: string
  image: string
}

const experiences = ref<Experience[]>([])
const categoryFilter = ref('')
const statusFilter = ref('')

const filteredExperiences = computed(() => {
  return experiences.value.filter(experience => {
    if (categoryFilter.value && experience.category !== categoryFilter.value) return false
    if (statusFilter.value && experience.status !== statusFilter.value) return false
    return true
  })
})

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const addExperience = () => {
  // Open modal to add new experience
}

const viewExperience = (experience: Experience) => {
  // Open experience details
}

const editExperience = (experience: Experience) => {
  // Open edit modal
}

const fetchExperiences = async () => {
  try {
    const response = await fetch('/api/luxury/experiences')
    const data = await response.json()
    experiences.value = data
  } catch (error) {
    console.error('Failed to fetch experiences:', error)
  }
}

onMounted(() => {
  fetchExperiences()
})
</script>

<style scoped>
.luxury-experiences {
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

.experiences-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.experience-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.experience-image {
  position: relative;
  width: 100%;
  height: 200px;
}

.experience-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.badge-luxury {
  position: absolute;
  top: 10px;
  left: 10px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.experience-details {
  padding: 16px;
}

.experience-details h3 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.category {
  margin: 0 0 8px 0;
  font-size: 12px;
  color: #6b7280;
}

.description {
  margin: 0 0 12px 0;
  font-size: 13px;
  color: #4b5563;
  line-height: 1.5;
}

.duration {
  margin-bottom: 8px;
  font-size: 13px;
  color: #374151;
}

.price {
  font-size: 18px;
  font-weight: 600;
  color: #059669;
}

.experience-actions {
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
