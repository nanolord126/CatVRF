<template>
  <div class="itinerary-builder">
    <div class="builder-header">
      <h2>Travel Itinerary Builder</h2>
      <div class="header-actions">
        <button @click="clearItinerary" class="btn-clear">Clear All</button>
        <button @click="saveItinerary" class="btn-save">Save Itinerary</button>
      </div>
    </div>

    <div class="builder-content">
      <!-- Search Section -->
      <div class="search-section">
        <h3>Add to Itinerary</h3>
        <div class="search-tabs">
          <button 
            v-for="tab in searchTabs" 
            :key="tab.id"
            :class="['tab-btn', { active: activeTab === tab.id }]"
            @click="activeTab = tab.id"
          >
            {{ tab.label }}
          </button>
        </div>

        <div class="search-inputs">
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Search destinations, tours, or activities..."
            class="search-input"
            @input="handleSearch"
          />
          <select v-model="destinationFilter" class="filter-select">
            <option value="">All Destinations</option>
            <option v-for="dest in destinations" :key="dest" :value="dest">{{ dest }}</option>
          </select>
        </div>

        <div class="search-results" v-if="searchResults.length">
          <div 
            v-for="item in searchResults" 
            :key="item.id" 
            class="search-result-item"
            @click="addToItinerary(item)"
          >
            <div class="item-image">
              <img :src="item.image" :alt="item.name" />
            </div>
            <div class="item-info">
              <h4>{{ item.name }}</h4>
              <p>{{ item.location }}</p>
              <div class="item-meta">
                <span>{{ item.duration }}</span>
                <span>{{ formatCurrency(item.price) }}</span>
              </div>
            </div>
            <button class="btn-add">+</button>
          </div>
        </div>
      </div>

      <!-- Itinerary Timeline -->
      <div class="itinerary-section">
        <h3>Your Itinerary</h3>
        <div class="itinerary-timeline">
          <div 
            v-for="(day, dayIndex) in itinerary.days" 
            :key="dayIndex" 
            class="day-block"
          >
            <div class="day-header">
              <h4>Day {{ dayIndex + 1 }} - {{ formatDate(day.date) }}</h4>
              <button @click="removeDay(dayIndex)" class="btn-remove-day">×</button>
            </div>
            
            <div class="day-activities">
              <draggable 
                v-model="day.activities" 
                item-key="id"
                @end="handleDragEnd"
              >
                <template #item="{ element: activity, index }">
                  <div class="activity-item">
                    <div class="activity-time">
                      <input 
                        v-model="activity.time" 
                        type="time" 
                        class="time-input"
                      />
                    </div>
                    <div class="activity-content">
                      <div class="activity-image">
                        <img :src="activity.image" :alt="activity.name" />
                      </div>
                      <div class="activity-info">
                        <h5>{{ activity.name }}</h5>
                        <p>{{ activity.location }}</p>
                        <span class="activity-duration">{{ activity.duration }}</span>
                      </div>
                      <button @click="removeActivity(dayIndex, index)" class="btn-remove-activity">×</button>
                    </div>
                  </div>
                </template>
              </draggable>

              <button @click="addBreak(dayIndex)" class="btn-add-break">
                + Add Break
              </button>
            </div>
          </div>

          <button @click="addDay" class="btn-add-day">
            + Add Day
          </button>
        </div>

        <!-- Itinerary Summary -->
        <div class="itinerary-summary">
          <h4>Itinerary Summary</h4>
          <div class="summary-item">
            <span>Total Duration:</span>
            <span>{{ itinerary.days.length }} days</span>
          </div>
          <div class="summary-item">
            <span>Total Activities:</span>
            <span>{{ totalActivities }}</span>
          </div>
          <div class="summary-item">
            <span>Estimated Cost:</span>
            <span>{{ formatCurrency(totalCost) }}</span>
          </div>
          <div class="summary-item total">
            <span>Per Person:</span>
            <span>{{ formatCurrency(totalCost / participants) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Save Modal -->
    <div v-if="showSaveModal" class="modal-overlay" @click="showSaveModal = false">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>Save Itinerary</h3>
          <button @click="showSaveModal = false" class="btn-close">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Itinerary Name *</label>
            <input v-model="itineraryName" type="text" class="form-input" placeholder="My Summer Trip" />
          </div>
          <div class="form-group">
            <label>Number of Travelers *</label>
            <input v-model="participants" type="number" class="form-input" min="1" />
          </div>
          <div class="form-group">
            <label>Notes</label>
            <textarea v-model="itineraryNotes" class="form-textarea" rows="3"></textarea>
          </div>
          <button @click="confirmSave" class="btn-save-confirm">Save Itinerary</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Activity {
  id: number
  name: string
  location: string
  image: string
  duration: string
  price: number
  time: string
}

interface Day {
  date: string
  activities: Activity[]
}

interface Itinerary {
  days: Day[]
}

const searchTabs = [
  { id: 'all', label: 'All' },
  { id: 'tours', label: 'Tours' },
  { id: 'activities', label: 'Activities' },
  { id: 'hotels', label: 'Hotels' },
  { id: 'restaurants', label: 'Restaurants' },
]

const activeTab = ref('all')
const searchQuery = ref('')
const destinationFilter = ref('')
const searchResults = ref<any[]>([])

const destinations = ref(['Moscow', 'Saint Petersburg', 'Sochi', 'Istanbul', 'Dubai'])

const itinerary = ref<Itinerary>({
  days: [
    {
      date: new Date().toISOString().split('T')[0],
      activities: [],
    }
  ]
})

const itineraryName = ref('')
const participants = ref(1)
const itineraryNotes = ref('')
const showSaveModal = ref(false)

const totalActivities = computed(() => {
  return itinerary.value.days.reduce((total, day) => total + day.activities.length, 0)
})

const totalCost = computed(() => {
  return itinerary.value.days.reduce((total, day) => {
    return total + day.activities.reduce((dayTotal, activity) => dayTotal + activity.price, 0)
  }, 0)
})

const handleSearch = () => {
  // Mock search results
  searchResults.value = [
    {
      id: 1,
      name: 'Red Square Tour',
      location: 'Moscow',
      image: '/images/red-square.jpg',
      duration: '3 hours',
      price: 5000,
    },
    {
      id: 2,
      name: 'Hermitage Museum',
      location: 'Saint Petersburg',
      image: '/images/hermitage.jpg',
      duration: '4 hours',
      price: 3000,
    },
    {
      id: 3,
      name: 'Beach Day',
      location: 'Sochi',
      image: '/images/beach.jpg',
      duration: 'Full day',
      price: 2000,
    },
  ]
}

const addToItinerary = (item: any) => {
  const activity: Activity = {
    id: Date.now(),
    name: item.name,
    location: item.location,
    image: item.image,
    duration: item.duration,
    price: item.price,
    time: '09:00',
  }
  
  itinerary.value.days[0].activities.push(activity)
  searchResults.value = searchResults.value.filter(r => r.id !== item.id)
}

const removeActivity = (dayIndex: number, activityIndex: number) => {
  itinerary.value.days[dayIndex].activities.splice(activityIndex, 1)
}

const addBreak = (dayIndex: number) => {
  const breakActivity: Activity = {
    id: Date.now(),
    name: 'Free Time / Break',
    location: 'Flexible',
    image: '/images/break.jpg',
    duration: '1 hour',
    price: 0,
    time: '12:00',
  }
  itinerary.value.days[dayIndex].activities.push(breakActivity)
}

const addDay = () => {
  const lastDate = new Date(itinerary.value.days[itinerary.value.days.length - 1].date)
  lastDate.setDate(lastDate.getDate() + 1)
  
  itinerary.value.days.push({
    date: lastDate.toISOString().split('T')[0],
    activities: [],
  })
}

const removeDay = (dayIndex: number) => {
  if (itinerary.value.days.length > 1) {
    itinerary.value.days.splice(dayIndex, 1)
  }
}

const handleDragEnd = () => {
  // Handle drag end if needed
}

const clearItinerary = () => {
  itinerary.value.days = [{
    date: new Date().toISOString().split('T')[0],
    activities: [],
  }]
}

const saveItinerary = () => {
  showSaveModal.value = true
}

const confirmSave = () => {
  console.log('Saving itinerary:', {
    name: itineraryName.value,
    participants: participants.value,
    notes: itineraryNotes.value,
    itinerary: itinerary.value,
  })
  showSaveModal.value = false
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
  })
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}
</script>

<style scoped>
.itinerary-builder {
  padding: 24px;
  background: #f9fafb;
  min-height: 100vh;
}

.builder-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.builder-header h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.btn-clear {
  padding: 10px 20px;
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
}

.btn-save {
  padding: 10px 20px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
}

.builder-content {
  display: grid;
  grid-template-columns: 400px 1fr;
  gap: 24px;
}

.search-section {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.search-section h3 {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
}

.search-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
}

.tab-btn {
  padding: 8px 16px;
  background: #f3f4f6;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #6b7280;
}

.tab-btn.active {
  background: #3b82f6;
  color: white;
}

.search-inputs {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.search-input,
.filter-select {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.search-results {
  max-height: 400px;
  overflow-y: auto;
}

.search-result-item {
  display: flex;
  gap: 12px;
  padding: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  margin-bottom: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.search-result-item:hover {
  border-color: #3b82f6;
  background: #f9fafb;
}

.item-image {
  width: 60px;
  height: 60px;
  border-radius: 6px;
  overflow: hidden;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.item-info {
  flex: 1;
}

.item-info h4 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.item-info p {
  margin: 0 0 8px 0;
  font-size: 12px;
  color: #6b7280;
}

.item-meta {
  display: flex;
  gap: 12px;
  font-size: 12px;
  color: #6b7280;
}

.btn-add {
  width: 32px;
  height: 32px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  font-size: 20px;
  font-weight: 600;
}

.itinerary-section {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.itinerary-section h3 {
  margin: 0 0 24px 0;
  font-size: 18px;
  font-weight: 600;
}

.day-block {
  margin-bottom: 24px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
}

.day-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.day-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.btn-remove-day {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #ef4444;
  padding: 0;
  width: 28px;
  height: 28px;
}

.activity-item {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
  padding: 12px;
  background: white;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.activity-time {
  display: flex;
  align-items: center;
}

.time-input {
  padding: 6px 8px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 14px;
}

.activity-content {
  flex: 1;
  display: flex;
  gap: 12px;
  align-items: center;
}

.activity-content .activity-image {
  width: 50px;
  height: 50px;
}

.activity-content h5 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.activity-content p {
  margin: 0 0 4px 0;
  font-size: 12px;
  color: #6b7280;
}

.activity-duration {
  font-size: 12px;
  color: #6b7280;
}

.btn-remove-activity {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  color: #ef4444;
  padding: 0;
  width: 24px;
  height: 24px;
}

.btn-add-break {
  width: 100%;
  padding: 8px;
  background: #f3f4f6;
  border: 1px dashed #d1d5db;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  color: #6b7280;
}

.btn-add-day {
  width: 100%;
  padding: 16px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
}

.itinerary-summary {
  margin-top: 24px;
  padding: 16px;
  background: #eff6ff;
  border-radius: 8px;
}

.itinerary-summary h4 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  font-size: 14px;
}

.summary-item.total {
  border-top: 2px solid #dbeafe;
  margin-top: 8px;
  padding-top: 12px;
  font-weight: 600;
  font-size: 16px;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 8px;
  max-width: 500px;
  width: 100%;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.btn-close {
  background: none;
  border: none;
  font-size: 28px;
  cursor: pointer;
  color: #6b7280;
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 500;
}

.form-input,
.form-textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.btn-save-confirm {
  width: 100%;
  padding: 12px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
}

@media (max-width: 1024px) {
  .builder-content {
    grid-template-columns: 1fr;
  }
}
</style>
