<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

interface Slot {
  id: number
  title: string
  start_time: string
  end_time: string
  capacity: number
  booked_count: number
  slot_type: 'webinar' | 'tutoring' | 'exam' | 'consultation'
  status: 'available' | 'held' | 'booked'
}

const props = defineProps<{
  courseId: number
}>()

const slots = ref<Slot[]>([])
const loading = ref(true)
const selectedSlot = ref<Slot | null>(null)
const holding = ref(false)
const booking = ref(false)
const showBookingForm = ref(false)
const biometricHash = ref('')

const fetchSlots = async () => {
  try {
    loading.value = true
    const response = await axios.get(`/api/v1/education/slots/course/${props.courseId}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'X-Correlation-ID': crypto.randomUUID()
      }
    })
    slots.value = response.data
  } catch (err: any) {
    console.error('Failed to load slots:', err)
  } finally {
    loading.value = false
  }
}

const holdSlot = async (slotId: number) => {
  try {
    holding.value = true
    await axios.post(`/api/v1/education/slots/${slotId}/hold`, {
      user_id: localStorage.getItem('userId')
    }, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'X-Correlation-ID': crypto.randomUUID()
      }
    })
    selectedSlot.value = slots.value.find(s => s.id === slotId) || null
    showBookingForm.value = true
    await fetchSlots()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to hold slot')
  } finally {
    holding.value = false
  }
}

const releaseSlot = async () => {
  if (!selectedSlot.value) return
  
  try {
    await axios.post(`/api/v1/education/slots/${selectedSlot.value.id}/release`, {
      user_id: localStorage.getItem('userId')
    }, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'X-Correlation-ID': crypto.randomUUID()
      }
    })
    selectedSlot.value = null
    showBookingForm.value = false
    await fetchSlots()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to release slot')
  }
}

const bookSlot = async () => {
  if (!selectedSlot.value) return
  
  try {
    booking.value = true
    await axios.post('/api/v1/education/slots/book', {
      user_id: localStorage.getItem('userId'),
      slot_id: selectedSlot.value.id,
      biometric_hash: biometricHash.value
    }, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'X-Correlation-ID': crypto.randomUUID()
      }
    })
    alert('Slot booked successfully!')
    selectedSlot.value = null
    showBookingForm.value = false
    biometricHash.value = ''
    await fetchSlots()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to book slot')
  } finally {
    booking.value = false
  }
}

const getSlotTypeColor = (type: string) => {
  const colors = {
    webinar: '#3b82f6',
    tutoring: '#8b5cf6',
    exam: '#ef4444',
    consultation: '#10b981'
  }
  return colors[type as keyof typeof colors] || '#6b7280'
}

const formatTime = (time: string) => {
  return new Date(time).toLocaleString('ru-RU', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  fetchSlots()
})
</script>

<template>
  <div class="slot-booking">
    <h2>Available Slots</h2>
    
    <div v-if="loading" class="loading">Loading slots...</div>
    
    <div v-else-if="slots.length === 0" class="empty">
      No available slots at the moment.
    </div>
    
    <div v-else class="slots-grid">
      <div v-for="slot in slots" :key="slot.id" class="slot-card" :class="{ unavailable: slot.status !== 'available' }">
        <div class="slot-type" :style="{ backgroundColor: getSlotTypeColor(slot.slot_type) }">
          {{ slot.slot_type }}
        </div>
        <h3>{{ slot.title }}</h3>
        <div class="slot-time">
          <span>{{ formatTime(slot.start_time) }}</span>
          <span>→</span>
          <span>{{ formatTime(slot.end_time) }}</span>
        </div>
        <div class="slot-capacity">
          {{ slot.booked_count }}/{{ slot.capacity }} booked
        </div>
        <button 
          v-if="slot.status === 'available'"
          @click="holdSlot(slot.id)"
          :disabled="holding"
          class="btn-hold"
        >
          {{ holding ? 'Holding...' : 'Hold Slot' }}
        </button>
        <span v-else class="status-text">{{ slot.status }}</span>
      </div>
    </div>

    <div v-if="showBookingForm && selectedSlot" class="booking-modal">
      <div class="modal-content">
        <h3>Confirm Booking</h3>
        <p><strong>Slot:</strong> {{ selectedSlot.title }}</p>
        <p><strong>Time:</strong> {{ formatTime(selectedSlot.start_time) }}</p>
        
        <div class="form-group">
          <label>Biometric Hash (optional)</label>
          <input v-model="biometricHash" type="text" placeholder="Enter biometric hash" />
        </div>

        <div class="modal-actions">
          <button @click="releaseSlot" class="btn-cancel">Cancel</button>
          <button @click="bookSlot" :disabled="booking" class="btn-confirm">
            {{ booking ? 'Booking...' : 'Confirm Booking' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.slot-booking {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.slots-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.slot-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1.5rem;
  transition: all 0.2s;
}

.slot-card.unavailable {
  opacity: 0.6;
}

.slot-type {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.slot-card h3 {
  margin: 0 0 1rem 0;
  font-size: 1.125rem;
}

.slot-time {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6b7280;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

.slot-capacity {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 1rem;
}

.btn-hold {
  width: 100%;
  padding: 0.75rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.btn-hold:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.status-text {
  display: block;
  text-align: center;
  color: #6b7280;
  font-weight: 600;
}

.booking-modal {
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

.modal-content {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  max-width: 400px;
  width: 100%;
}

.form-group {
  margin: 1.5rem 0;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.btn-cancel, .btn-confirm {
  flex: 1;
  padding: 0.75rem;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.btn-cancel {
  background: #e5e7eb;
}

.btn-confirm {
  background: #10b981;
  color: white;
}

.btn-confirm:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
