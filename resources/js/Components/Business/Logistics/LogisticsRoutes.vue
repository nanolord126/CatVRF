<template>
  <div class="logistics-routes">
    <div class="header">
      <h2>Route Planning</h2>
      <button @click="addRoute" class="btn-primary">Add Route</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="planned">Planned</option>
        <option value="active">Active</option>
        <option value="completed">Completed</option>
      </select>
    </div>

    <div class="routes-grid">
      <div v-for="route in filteredRoutes" :key="route.id" class="route-card">
        <div class="route-header">
          <span class="route-id">Route #{{ route.id }}</span>
          <span :class="['status-badge', route.status]">{{ route.status }}</span>
        </div>
        <div class="route-details">
          <h3>{{ route.origin }} → {{ route.destination }}</h3>
          <p class="driver">Driver: {{ route.driver }}</p>
          <p class="vehicle">Vehicle: {{ route.vehicle }}</p>
          <div class="stops">{{ route.stops }} stops</div>
          <div class="distance">{{ route.distance }} km</div>
          <div class="eta">ETA: {{ route.estimated_time }}</div>
        </div>
        <div class="route-actions">
          <button @click="viewRoute(route)" class="btn-sm">View</button>
          <button @click="editRoute(route)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Route {
  id: number
  origin: string
  destination: string
  driver: string
  vehicle: string
  stops: number
  distance: number
  estimated_time: string
  status: string
}

const routes = ref<Route[]>([])
const statusFilter = ref('')

const filteredRoutes = computed(() => {
  if (!statusFilter.value) return routes.value
  return routes.value.filter(route => route.status === statusFilter.value)
})

const addRoute = () => {
  // Open modal to add new route
}

const viewRoute = (route: Route) => {
  // Open route details
}

const editRoute = (route: Route) => {
  // Open edit modal
}

const fetchRoutes = async () => {
  try {
    const response = await fetch('/api/logistics/routes')
    const data = await response.json()
    routes.value = data
  } catch (error) {
    console.error('Failed to fetch routes:', error)
  }
}

onMounted(() => {
  fetchRoutes()
})
</script>

<style scoped>
.logistics-routes {
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

.routes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.route-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.route-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.route-id {
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

.status-badge.planned {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.completed {
  background: #e5e7eb;
  color: #374151;
}

.route-details {
  padding: 16px;
}

.route-details h3 {
  margin: 0 0 8px 0;
  font-size: 14px;
  font-weight: 600;
}

.driver, .vehicle {
  margin: 0 0 4px 0;
  font-size: 12px;
  color: #6b7280;
}

.stops {
  margin-bottom: 4px;
  font-size: 13px;
  color: #374151;
}

.distance {
  margin-bottom: 4px;
  font-size: 13px;
  color: #374151;
}

.eta {
  font-size: 13px;
  color: #059669;
  font-weight: 500;
}

.route-actions {
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
