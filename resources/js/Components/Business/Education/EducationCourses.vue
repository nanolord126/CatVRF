<template>
  <div class="education-courses">
    <div class="header">
      <h2>Courses</h2>
      <button @click="addCourse" class="btn-primary">Add Course</button>
    </div>

    <div class="filters">
      <select v-model="categoryFilter" @change="fetchCourses">
        <option value="">All Categories</option>
        <option value="programming">Programming</option>
        <option value="design">Design</option>
        <option value="business">Business</option>
        <option value="languages">Languages</option>
      </select>
      <select v-model="levelFilter" @change="fetchCourses">
        <option value="">All Levels</option>
        <option value="beginner">Beginner</option>
        <option value="intermediate">Intermediate</option>
        <option value="advanced">Advanced</option>
      </select>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading courses...</p>
    </div>

    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
      <button @click="fetchCourses" class="btn-sm">Retry</button>
    </div>

    <div v-else-if="filteredCourses.length === 0" class="empty-state">
      <p>No courses found matching your filters.</p>
    </div>

    <div v-else class="courses-grid">
      <div v-for="course in filteredCourses" :key="course.id" class="course-card">
        <div class="course-image">
          <img :src="course.image" :alt="course.name" />
          <span v-if="course.isPopular" class="badge-popular">POPULAR</span>
        </div>
        <div class="course-details">
          <h3>{{ course.name }}</h3>
          <p class="instructor">{{ course.instructor }}</p>
          <div class="stats">
            <span>{{ course.lessons }} lessons</span>
            <span>{{ course.hours }} hours</span>
            <span v-if="course.rating" class="rating">⭐ {{ course.rating }}</span>
          </div>
          <div class="enrolled">{{ course.enrolled }} enrolled</div>
          <div class="price">{{ course.price === 0 ? 'Free' : formatCurrency(course.price) }}</div>
        </div>
        <div class="course-actions">
          <button @click="viewCourse(course)" class="btn-sm">View</button>
          <button @click="editCourse(course)" class="btn-sm">Edit</button>
          <button @click="deleteCourse(course)" class="btn-sm btn-danger">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Course {
  id: number
  name: string
  instructor: string
  category: string
  level: string
  lessons: number
  hours: number
  enrolled: number
  price: number
  isPopular: boolean
  image: string
  rating?: number
  description?: string
}

const courses = ref<Course[]>([])
const categoryFilter = ref('')
const levelFilter = ref('')
const loading = ref(false)
const error = ref<string | null>(null)

const filteredCourses = computed(() => {
  return courses.value.filter(course => {
    if (categoryFilter.value && course.category !== categoryFilter.value) return false
    if (levelFilter.value && course.level !== levelFilter.value) return false
    return true
  })
})

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const addCourse = async () => {
  // Open modal to add new course
  console.log('Add course modal would open here')
}

const viewCourse = async (course: Course) => {
  try {
    const response = await fetch(`/api/education/courses/${course.id}`)
    const courseDetails = await response.json()
    console.log('Course details:', courseDetails)
    // Open course details modal or navigate to course page
  } catch (error) {
    console.error('Failed to fetch course details:', error)
  }
}

const editCourse = async (course: Course) => {
  try {
    const response = await fetch(`/api/education/courses/${course.id}`)
    const courseDetails = await response.json()
    console.log('Edit course:', courseDetails)
    // Open edit modal with course data
  } catch (error) {
    console.error('Failed to fetch course for editing:', error)
  }
}

const deleteCourse = async (course: Course) => {
  if (!confirm(`Are you sure you want to delete ${course.name}?`)) {
    return
  }

  try {
    const response = await fetch(`/api/education/courses/${course.id}`, {
      method: 'DELETE',
    })

    if (response.ok) {
      courses.value = courses.value.filter(c => c.id !== course.id)
    } else {
      throw new Error('Failed to delete course')
    }
  } catch (error) {
    console.error('Failed to delete course:', error)
    error.value = 'Failed to delete course'
  }
}

const fetchCourses = async () => {
  loading.value = true
  error.value = null

  try {
    const params = new URLSearchParams()
    if (categoryFilter.value) params.append('category', categoryFilter.value)
    if (levelFilter.value) params.append('level', levelFilter.value)

    const response = await fetch(`/api/education/courses?${params.toString()}`)
    
    if (!response.ok) {
      throw new Error('Failed to fetch courses')
    }

    const data = await response.json()
    courses.value = data.data || data
  } catch (err) {
    console.error('Failed to fetch courses:', err)
    error.value = 'Failed to load courses'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchCourses()
})
</script>

<style scoped>
.education-courses {
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

.courses-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.course-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.course-image {
  position: relative;
  width: 100%;
  height: 160px;
}

.course-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.badge-popular {
  position: absolute;
  top: 10px;
  right: 10px;
  background: #f59e0b;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
}

.course-details {
  padding: 16px;
}

.course-details h3 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.instructor {
  margin: 0 0 12px 0;
  font-size: 12px;
  color: #6b7280;
}

.stats {
  display: flex;
  gap: 12px;
  margin-bottom: 8px;
  font-size: 13px;
  color: #374151;
}

.enrolled {
  margin-bottom: 8px;
  font-size: 12px;
  color: #6b7280;
}

.price {
  font-size: 16px;
  font-weight: 600;
  color: #059669;
}

.course-actions {
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

.btn-danger {
  border-color: #ef4444;
  color: #ef4444;
}

.btn-danger:hover {
  background: #ef4444;
  color: white;
}

.loading-state,
.error-state,
.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #6b7280;
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 16px;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.rating {
  color: #f59e0b;
  font-weight: 500;
}
</style>
