<template>
  <div class="inventory-items">
    <div class="header">
      <h2>Inventory Items</h2>
      <button @click="addItem" class="btn-primary">Add Item</button>
    </div>

    <div class="filters">
      <select v-model="categoryFilter">
        <option value="">All Categories</option>
        <option value="electronics">Electronics</option>
        <option value="clothing">Clothing</option>
        <option value="food">Food</option>
        <option value="other">Other</option>
      </select>
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="in_stock">In Stock</option>
        <option value="low_stock">Low Stock</option>
        <option value="out_of_stock">Out of Stock</option>
      </select>
    </div>

    <div class="items-table">
      <table>
        <thead>
          <tr>
            <th>SKU</th>
            <th>Name</th>
            <th>Category</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in filteredItems" :key="item.id">
            <td>{{ item.sku }}</td>
            <td>{{ item.name }}</td>
            <td>{{ item.category }}</td>
            <td>{{ item.quantity }}</td>
            <td>{{ formatCurrency(item.price) }}</td>
            <td>
              <span :class="['status-badge', item.status]">{{ item.status }}</span>
            </td>
            <td>
              <button @click="viewItem(item)" class="btn-sm">View</button>
              <button @click="editItem(item)" class="btn-sm">Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Item {
  id: number
  sku: string
  name: string
  category: string
  quantity: number
  price: number
  status: string
}

const items = ref<Item[]>([])
const categoryFilter = ref('')
const statusFilter = ref('')

const filteredItems = computed(() => {
  return items.value.filter(item => {
    if (categoryFilter.value && item.category !== categoryFilter.value) return false
    if (statusFilter.value && item.status !== statusFilter.value) return false
    return true
  })
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const addItem = () => {
  // Open modal to add new item
}

const viewItem = (item: Item) => {
  // Open item details
}

const editItem = (item: Item) => {
  // Open edit modal
}

const fetchItems = async () => {
  try {
    const response = await fetch('/api/inventory/items')
    const data = await response.json()
    items.value = data
  } catch (error) {
    console.error('Failed to fetch items:', error)
  }
}

onMounted(() => {
  fetchItems()
})
</script>

<style scoped>
.inventory-items {
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

.items-table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

th {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.status-badge.in_stock {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.low_stock {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.out_of_stock {
  background: #fee2e2;
  color: #991b1b;
}

.btn-sm {
  padding: 6px 12px;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
  margin-right: 4px;
}
</style>
