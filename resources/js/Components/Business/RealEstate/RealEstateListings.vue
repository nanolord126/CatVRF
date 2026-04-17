<template>
  <div class="real-estate-listings">
    <div class="header">
      <h2>Property Listings</h2>
      <button @click="addListing" class="btn-primary">Add Listing</button>
    </div>

    <div class="filters">
      <select v-model="typeFilter">
        <option value="">All Types</option>
        <option value="apartment">Apartment</option>
        <option value="house">House</option>
        <option value="commercial">Commercial</option>
      </select>
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="available">Available</option>
        <option value="pending">Pending</option>
        <option value="sold">Sold</option>
      </select>
    </div>

    <div class="listings-grid">
      <div v-for="listing in filteredListings" :key="listing.id" class="listing-card">
        <div class="listing-image">
          <img :src="listing.image" :alt="listing.title" />
          <span :class="['status-badge', listing.status]">{{ listing.status }}</span>
        </div>
        <div class="listing-details">
          <h3>{{ listing.title }}</h3>
          <p class="address">{{ listing.address }}</p>
          <div class="specs">
            <span>{{ listing.area }} m²</span>
            <span>{{ listing.bedrooms }} beds</span>
            <span>{{ listing.bathrooms }} baths</span>
          </div>
          <div class="price">{{ formatCurrency(listing.price) }}</div>
        </div>
        <div class="listing-actions">
          <button @click="viewListing(listing)" class="btn-sm">View</button>
          <button @click="editListing(listing)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Listing {
  id: number
  title: string
  address: string
  area: number
  bedrooms: number
  bathrooms: number
  price: number
  status: string
  type: string
  image: string
}

const listings = ref<Listing[]>([])
const typeFilter = ref('')
const statusFilter = ref('')

const filteredListings = computed(() => {
  return listings.value.filter(listing => {
    if (typeFilter.value && listing.type !== typeFilter.value) return false
    if (statusFilter.value && listing.status !== statusFilter.value) return false
    return true
  })
})

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const addListing = () => {
  // Open modal to add new listing
}

const viewListing = (listing: Listing) => {
  // Open listing details
}

const editListing = (listing: Listing) => {
  // Open edit modal
}

const fetchListings = async () => {
  try {
    const response = await fetch('/api/real-estate/listings')
    const data = await response.json()
    listings.value = data
  } catch (error) {
    console.error('Failed to fetch listings:', error)
  }
}

onMounted(() => {
  fetchListings()
})
</script>

<style scoped>
.real-estate-listings {
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

.listings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.listing-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.listing-image {
  position: relative;
  width: 100%;
  height: 200px;
}

.listing-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.status-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  color: white;
}

.status-badge.available {
  background: #10b981;
}

.status-badge.pending {
  background: #f59e0b;
}

.status-badge.sold {
  background: #6b7280;
}

.listing-details {
  padding: 16px;
}

.listing-details h3 {
  margin: 0 0 8px 0;
  font-size: 16px;
  font-weight: 600;
}

.address {
  margin: 0 0 12px 0;
  font-size: 14px;
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

.listing-actions {
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
