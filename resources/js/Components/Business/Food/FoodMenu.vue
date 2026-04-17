<template>
  <div class="food-menu">
    <div class="header">
      <h2>Menu Management</h2>
      <button @click="addMenuItem" class="btn-primary">Add Item</button>
    </div>

    <div class="menu-grid">
      <div v-for="item in menuItems" :key="item.id" class="menu-item">
        <div class="item-image">
          <img :src="item.image" :alt="item.name" />
        </div>
        <div class="item-details">
          <h3>{{ item.name }}</h3>
          <p class="description">{{ item.description }}</p>
          <div class="item-footer">
            <span class="price">{{ formatCurrency(item.price) }}</span>
            <span class="category">{{ item.category }}</span>
          </div>
        </div>
        <div class="item-actions">
          <button @click="editItem(item)" class="btn-secondary">Edit</button>
          <button @click="deleteItem(item.id)" class="btn-danger">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface MenuItem {
  id: number
  name: string
  description: string
  price: number
  category: string
  image: string
}

const menuItems = ref<MenuItem[]>([])

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const addMenuItem = () => {
  // Open modal to add new menu item
}

const editItem = (item: MenuItem) => {
  // Open modal to edit item
}

const deleteItem = (id: number) => {
  // Delete item with confirmation
}

const fetchMenuItems = async () => {
  try {
    const response = await fetch('/api/food/menu')
    const data = await response.json()
    menuItems.value = data
  } catch (error) {
    console.error('Failed to fetch menu items:', error)
  }
}

onMounted(() => {
  fetchMenuItems()
})
</script>

<style scoped>
.food-menu {
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

.menu-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.menu-item {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.item-image {
  width: 100%;
  height: 200px;
  overflow: hidden;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.item-details {
  padding: 16px;
}

.item-details h3 {
  margin: 0 0 8px 0;
  font-size: 16px;
  font-weight: 600;
}

.description {
  margin: 0 0 12px 0;
  font-size: 14px;
  color: #6b7280;
}

.item-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.price {
  font-size: 16px;
  font-weight: 600;
  color: #059669;
}

.category {
  font-size: 12px;
  color: #6b7280;
  background: #f3f4f6;
  padding: 4px 8px;
  border-radius: 4px;
}

.item-actions {
  padding: 12px 16px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 8px;
}

.btn-secondary {
  flex: 1;
  background: #6b7280;
  color: white;
  border: none;
  padding: 8px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.btn-danger {
  flex: 1;
  background: #ef4444;
  color: white;
  border: none;
  padding: 8px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}
</style>
