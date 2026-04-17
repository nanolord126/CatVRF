<template>
  <div class="electronics-catalog">
    <div class="header">
      <h2>Electronics Catalog</h2>
      <button @click="addProduct" class="btn-primary">Add Product</button>
    </div>

    <div class="filters">
      <select v-model="categoryFilter">
        <option value="">All Categories</option>
        <option value="smartphones">Smartphones</option>
        <option value="laptops">Laptops</option>
        <option value="tablets">Tablets</option>
        <option value="accessories">Accessories</option>
      </select>
      <select v-model="brandFilter">
        <option value="">All Brands</option>
        <option value="apple">Apple</option>
        <option value="samsung">Samsung</option>
        <option value="xiaomi">Xiaomi</option>
        <option value="sony">Sony</option>
      </select>
    </div>

    <div class="products-grid">
      <div v-for="product in filteredProducts" :key="product.id" class="product-card">
        <div class="product-image">
          <img :src="product.image" :alt="product.name" />
          <span v-if="product.isNew" class="badge-new">NEW</span>
          <span v-if="product.discount" class="badge-discount">-{{ product.discount }}%</span>
        </div>
        <div class="product-details">
          <h3>{{ product.name }}</h3>
          <p class="brand">{{ product.brand }}</p>
          <div class="specs">
            <span v-for="spec in product.specs" :key="spec" class="spec-tag">{{ spec }}</span>
          </div>
          <div class="price-row">
            <span class="price">{{ formatCurrency(product.price) }}</span>
            <span v-if="product.originalPrice" class="original-price">{{ formatCurrency(product.originalPrice) }}</span>
          </div>
          <p class="stock">Stock: {{ product.stock }}</p>
        </div>
        <div class="product-actions">
          <button @click="viewProduct(product)" class="btn-sm">View</button>
          <button @click="editProduct(product)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Product {
  id: number
  name: string
  brand: string
  category: string
  specs: string[]
  price: number
  originalPrice?: number
  stock: number
  isNew: boolean
  discount?: number
  image: string
}

const products = ref<Product[]>([])
const categoryFilter = ref('')
const brandFilter = ref('')

const filteredProducts = computed(() => {
  return products.value.filter(product => {
    if (categoryFilter.value && product.category !== categoryFilter.value) return false
    if (brandFilter.value && product.brand !== brandFilter.value) return false
    return true
  })
})

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const addProduct = () => {
  // Open modal to add new product
}

const viewProduct = (product: Product) => {
  // Open product details
}

const editProduct = (product: Product) => {
  // Open edit modal
}

const fetchProducts = async () => {
  try {
    const response = await fetch('/api/electronics/catalog')
    const data = await response.json()
    products.value = data
  } catch (error) {
    console.error('Failed to fetch products:', error)
  }
}

onMounted(() => {
  fetchProducts()
})
</script>

<style scoped>
.electronics-catalog {
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

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.product-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.product-image {
  position: relative;
  width: 100%;
  height: 200px;
}

.product-image img {
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

.badge-discount {
  position: absolute;
  top: 10px;
  right: 10px;
  background: #ef4444;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
}

.product-details {
  padding: 16px;
}

.product-details h3 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
  line-height: 1.4;
}

.brand {
  margin: 0 0 8px 0;
  font-size: 12px;
  color: #6b7280;
}

.specs {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 12px;
}

.spec-tag {
  background: #f3f4f6;
  color: #4b5563;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
}

.price-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.price {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.original-price {
  font-size: 14px;
  color: #9ca3af;
  text-decoration: line-through;
}

.stock {
  margin: 0;
  font-size: 12px;
  color: #6b7280;
}

.product-actions {
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
