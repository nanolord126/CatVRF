<template>
  <div class="auto-catalog">
    <div class="header">
      <h2>Auto Catalog</h2>
      <button @click="addCar" class="btn-primary">Add Car</button>
    </div>

    <div class="filters">
      <select v-model="brandFilter">
        <option value="">All Brands</option>
        <option value="bmw">BMW</option>
        <option value="mercedes">Mercedes</option>
        <option value="audi">Audi</option>
        <option value="toyota">Toyota</option>
      </select>
      <select v-model="typeFilter">
        <option value="">All Types</option>
        <option value="sedan">Sedan</option>
        <option value="suv">SUV</option>
        <option value="coupe">Coupe</option>
        <option value="hatchback">Hatchback</option>
      </select>
    </div>

    <div class="cars-grid">
      <div v-for="car in filteredCars" :key="car.id" class="car-card">
        <div class="car-image">
          <img :src="car.image" :alt="car.name" />
          <span v-if="car.isNew" class="badge-new">NEW</span>
        </div>
        <div class="car-details">
          <h3>{{ car.name }}</h3>
          <p class="brand">{{ car.brand }}</p>
          <div class="specs">
            <span>{{ car.year }}</span>
            <span>{{ car.mileage }} km</span>
            <span>{{ car.fuel }}</span>
          </div>
          <div class="price">{{ formatCurrency(car.price) }}</div>
        </div>
        <div class="car-actions">
          <button @click="viewCar(car)" class="btn-sm">View</button>
          <button @click="editCar(car)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Car {
  id: number
  name: string
  brand: string
  type: string
  year: number
  mileage: number
  fuel: string
  price: number
  isNew: boolean
  image: string
}

const cars = ref<Car[]>([])
const brandFilter = ref('')
const typeFilter = ref('')

const filteredCars = computed(() => {
  return cars.value.filter(car => {
    if (brandFilter.value && car.brand !== brandFilter.value) return false
    if (typeFilter.value && car.type !== typeFilter.value) return false
    return true
  })
})

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const addCar = () => {
  // Open modal to add new car
}

const viewCar = (car: Car) => {
  // Open car details
}

const editCar = (car: Car) => {
  // Open edit modal
}

const fetchCars = async () => {
  try {
    const response = await fetch('/api/auto/catalog')
    const data = await response.json()
    cars.value = data
  } catch (error) {
    console.error('Failed to fetch cars:', error)
  }
}

onMounted(() => {
  fetchCars()
})
</script>

<style scoped>
.auto-catalog {
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

.cars-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.car-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.car-image {
  position: relative;
  width: 100%;
  height: 200px;
}

.car-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.badge-new {
  position: absolute;
  top: 10px;
  left: 10px;
  background: #10b981;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
}

.car-details {
  padding: 16px;
}

.car-details h3 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.brand {
  margin: 0 0 8px 0;
  font-size: 12px;
  color: #6b7280;
}

.specs {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
  font-size: 13px;
  color: #6b7280;
}

.price {
  font-size: 18px;
  font-weight: 600;
  color: #059669;
}

.car-actions {
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
