<script setup>
/**
 * B2BCatalog — B2B product catalog with filtering, bulk ordering,
 * wholesale pricing tiers, and product comparison.
 */
import { ref, computed } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VInput from '../UI/VInput.vue';
import VModal from '../UI/VModal.vue';

const searchQuery = ref('');
const selectedCategory = ref('all');
const selectedTier = ref('all');
const sortBy = ref('popular');
const showProductModal = ref(false);
const selectedProduct = ref(null);
const comparisonList = ref([]);

const categories = [
    { id: 'all', label: 'Все категории', icon: '📦' },
    { id: 'electronics', label: 'Электроника', icon: '📱' },
    { id: 'office', label: 'Офисные supplies', icon: '📎' },
    { id: 'food', label: 'Продукты питания', icon: '🍎' },
    { id: 'beauty', label: 'Косметика', icon: '💄' },
    { id: 'medical', label: 'Медицинские товары', icon: '💊' },
];

const sortOptions = [
    { id: 'popular', label: 'Популярные' },
    { id: 'price_asc', label: 'Цена: по возрастанию' },
    { id: 'price_desc', label: 'Цена: по убыванию' },
    { id: 'newest', label: 'Новые' },
];

const products = [
    {
        id: 1,
        name: 'Ноутбук ProBook 15',
        sku: 'NB-001',
        category: 'electronics',
        image: '💻',
        retailPrice: 45000,
        wholesalePrice: 35000,
        minOrderQty: 10,
        stock: 500,
        tier: { standard: 35000, silver: 32000, gold: 29000 },
        rating: 4.8,
        reviews: 124,
        featured: true,
    },
    {
        id: 2,
        name: 'Бумага A4 (коробка 5000 листов)',
        sku: 'OFF-002',
        category: 'office',
        image: '📄',
        retailPrice: 2500,
        wholesalePrice: 1800,
        minOrderQty: 50,
        stock: 2000,
        tier: { standard: 1800, silver: 1600, gold: 1400 },
        rating: 4.5,
        reviews: 89,
        featured: false,
    },
    {
        id: 3,
        name: 'Оливковое масло Extra Virgin (10л)',
        sku: 'FD-003',
        category: 'food',
        image: '🫒',
        retailPrice: 8500,
        wholesalePrice: 6200,
        minOrderQty: 20,
        stock: 300,
        tier: { standard: 6200, silver: 5600, gold: 5000 },
        rating: 4.9,
        reviews: 256,
        featured: true,
    },
    {
        id: 4,
        name: 'Крем для лица увлажняющий',
        sku: 'BT-004',
        category: 'beauty',
        image: '🧴',
        retailPrice: 1200,
        wholesalePrice: 850,
        minOrderQty: 100,
        stock: 1500,
        tier: { standard: 850, silver: 750, gold: 650 },
        rating: 4.7,
        reviews: 178,
        featured: false,
    },
    {
        id: 5,
        name: 'Медицинские перчатки (1000 шт)',
        sku: 'MD-005',
        category: 'medical',
        image: '🧤',
        retailPrice: 3500,
        wholesalePrice: 2200,
        minOrderQty: 100,
        stock: 5000,
        tier: { standard: 2200, silver: 1900, gold: 1600 },
        rating: 4.6,
        reviews: 312,
        featured: true,
    },
    {
        id: 6,
        name: 'Монитор 27" 4K',
        sku: 'EL-006',
        category: 'electronics',
        image: '🖥️',
        retailPrice: 28000,
        wholesalePrice: 22000,
        minOrderQty: 5,
        stock: 150,
        tier: { standard: 22000, silver: 20000, gold: 18000 },
        rating: 4.8,
        reviews: 95,
        featured: false,
    },
];

const filteredProducts = computed(() => {
    let result = [...products];

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(p => 
            p.name.toLowerCase().includes(query) || 
            p.sku.toLowerCase().includes(query)
        );
    }

    // Category filter
    if (selectedCategory.value !== 'all') {
        result = result.filter(p => p.category === selectedCategory.value);
    }

    // Sort
    if (sortBy.value === 'price_asc') {
        result.sort((a, b) => a.wholesalePrice - b.wholesalePrice);
    } else if (sortBy.value === 'price_desc') {
        result.sort((a, b) => b.wholesalePrice - a.wholesalePrice);
    } else if (sortBy.value === 'newest') {
        result.reverse();
    }

    return result;
});

const featuredProducts = computed(() => products.filter(p => p.featured));

const isInComparison = (productId) => comparisonList.value.includes(productId);

function toggleComparison(productId) {
    const index = comparisonList.value.indexOf(productId);
    if (index > -1) {
        comparisonList.value.splice(index, 1);
    } else if (comparisonList.value.length < 4) {
        comparisonList.value.push(productId);
    }
}

function openProductModal(product) {
    selectedProduct.value = product;
    showProductModal.value = true;
}

function getDiscountPercent(retail, wholesale) {
    return Math.round((1 - wholesale / retail) * 100);
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-(--t-text)">📦 B2B Каталог</h1>
                <p class="text-sm text-(--t-text-3) mt-1">Оптовые закупки по специальным ценам</p>
            </div>
            <div class="flex items-center gap-2">
                <VButton v-if="comparisonList.length > 0" variant="secondary" size="sm">
                    Сравнить ({{ comparisonList.length }})
                </VButton>
                <VButton variant="b2b" size="sm">📥 Импорт из Excel</VButton>
            </div>
        </div>

        <!-- Search and Filters -->
        <VCard flat>
            <div class="flex flex-col lg:flex-row gap-4">
                <VInput 
                    v-model="searchQuery" 
                    placeholder="Поиск по названию или SKU..." 
                    prefix-icon="🔍"
                    class="flex-1"
                />
                <div class="flex gap-2 flex-wrap">
                    <select 
                        v-model="selectedCategory"
                        class="px-4 py-2 rounded-xl bg-(--t-surface) border border-(--t-border) text-(--t-text) text-sm focus:outline-none focus:border-(--t-primary)"
                    >
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.icon }} {{ cat.label }}
                        </option>
                    </select>
                    <select 
                        v-model="sortBy"
                        class="px-4 py-2 rounded-xl bg-(--t-surface) border border-(--t-border) text-(--t-text) text-sm focus:outline-none focus:border-(--t-primary)"
                    >
                        <option v-for="opt in sortOptions" :key="opt.id" :value="opt.id">
                            {{ opt.label }}
                        </option>
                    </select>
                </div>
            </div>
        </VCard>

        <!-- Featured Products -->
        <div v-if="featuredProducts.length > 0 && searchQuery === '' && selectedCategory === 'all'">
            <h2 class="text-lg font-semibold text-(--t-text) mb-3">⭐ Рекомендуемые</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <VCard 
                    v-for="product in featuredProducts" 
                    :key="product.id"
                    clickable
                    glow
                    @click="openProductModal(product)"
                >
                    <div class="text-center">
                        <div class="text-4xl mb-2">{{ product.image }}</div>
                        <h3 class="font-semibold text-(--t-text) text-sm">{{ product.name }}</h3>
                        <p class="text-xs text-(--t-text-3) mt-1">SKU: {{ product.sku }}</p>
                        <div class="mt-3 space-y-1">
                            <div class="text-xs text-(--t-text-3) line-through">{{ product.retailPrice.toLocaleString() }} ₽</div>
                            <div class="text-lg font-bold text-emerald-400">{{ product.wholesalePrice.toLocaleString() }} ₽</div>
                            <VBadge :text="`-${getDiscountPercent(product.retailPrice, product.wholesalePrice)}%`" variant="success" size="xs" />
                        </div>
                    </div>
                </VCard>
            </div>
        </div>

        <!-- Products Grid -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-(--t-text)">
                    {{ searchQuery || selectedCategory !== 'all' ? 'Результаты поиска' : 'Все товары' }}
                    <span class="text-sm text-(--t-text-3) font-normal">({{ filteredProducts.length }})</span>
                </h2>
            </div>

            <div v-if="filteredProducts.length === 0" class="text-center py-16">
                <p class="text-5xl mb-4">🔍</p>
                <p class="text-(--t-text-2)">Товары не найдены</p>
                <VButton variant="secondary" size="sm" class="mt-4" @click="searchQuery = ''; selectedCategory = 'all'">
                    Сбросить фильтры
                </VButton>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <VCard 
                    v-for="product in filteredProducts" 
                    :key="product.id"
                    clickable
                    @click="openProductModal(product)"
                >
                    <div class="relative">
                        <!-- Product Image -->
                        <div class="text-4xl text-center mb-3">{{ product.image }}</div>
                        
                        <!-- Comparison Button -->
                        <button 
                            @click.stop="toggleComparison(product.id)"
                            class="absolute top-0 right-0 p-2 rounded-lg hover:bg-(--t-card-hover) transition-colors"
                            :class="isInComparison(product.id) ? 'text-(--t-primary)' : 'text-(--t-text-3)'"
                        >
                            ⚖️
                        </button>

                        <!-- Product Info -->
                        <h3 class="font-semibold text-(--t-text) text-sm line-clamp-2">{{ product.name }}</h3>
                        <p class="text-xs text-(--t-text-3) mt-1">SKU: {{ product.sku }}</p>

                        <!-- Rating -->
                        <div class="flex items-center gap-1 mt-2">
                            <span class="text-yellow-400">⭐</span>
                            <span class="text-xs text-(--t-text)">{{ product.rating }}</span>
                            <span class="text-xs text-(--t-text-3)">({{ product.reviews }})</span>
                        </div>

                        <!-- Pricing -->
                        <div class="mt-3 space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-(--t-text-3) line-through">{{ product.retailPrice.toLocaleString() }} ₽</span>
                                <VBadge :text="`-${getDiscountPercent(product.retailPrice, product.wholesalePrice)}%`" variant="success" size="xs" />
                            </div>
                            <div class="text-lg font-bold text-(--t-text)">{{ product.wholesalePrice.toLocaleString() }} ₽</div>
                            <div class="text-xs text-(--t-text-3)">Мин. заказ: {{ product.minOrderQty }} шт.</div>
                        </div>

                        <!-- Stock -->
                        <div class="mt-3 flex items-center gap-2">
                            <VBadge 
                                :text="product.stock > 100 ? 'В наличии' : product.stock > 0 ? 'Мало' : 'Нет в наличии'" 
                                :variant="product.stock > 100 ? 'success' : product.stock > 0 ? 'warning' : 'danger'" 
                                size="xs" 
                            />
                            <span class="text-xs text-(--t-text-3)">{{ product.stock }} шт.</span>
                        </div>
                    </div>
                </VCard>
            </div>
        </div>

        <!-- Product Detail Modal -->
        <VModal v-model="showProductModal" :title="selectedProduct?.name" size="lg" v-if="selectedProduct">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="text-center">
                    <div class="text-8xl mb-4">{{ selectedProduct.image }}</div>
                </div>
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-(--t-text-3)">SKU</p>
                        <p class="font-mono text-(--t-text)">{{ selectedProduct.sku }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-(--t-text-3)">Категория</p>
                        <p class="text-(--t-text)">{{ categories.find(c => c.id === selectedProduct.category)?.label }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-(--t-text-3)">Розничная цена</p>
                        <p class="text-(--t-text)">{{ selectedProduct.retailPrice.toLocaleString() }} ₽</p>
                    </div>
                    <div>
                        <p class="text-sm text-(--t-text-3)">Оптовые цены по тарифам</p>
                        <div class="space-y-2 mt-2">
                            <div class="flex items-center justify-between p-2 rounded-lg bg-(--t-card-hover)">
                                <VBadge text="STANDARD" variant="neutral" size="xs" />
                                <span class="font-bold text-(--t-text)">{{ selectedProduct.tier.standard.toLocaleString() }} ₽</span>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded-lg bg-(--t-card-hover)">
                                <VBadge text="SILVER" variant="info" size="xs" />
                                <span class="font-bold text-(--t-text)">{{ selectedProduct.tier.silver.toLocaleString() }} ₽</span>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded-lg bg-(--t-card-hover)">
                                <VBadge text="GOLD" variant="b2b" size="xs" />
                                <span class="font-bold text-(--t-text)">{{ selectedProduct.tier.gold.toLocaleString() }} ₽</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-(--t-text-3)">Минимальный заказ</p>
                        <p class="text-(--t-text)">{{ selectedProduct.minOrderQty }} шт.</p>
                    </div>
                    <div>
                        <p class="text-sm text-(--t-text-3)">Наличие на складе</p>
                        <p class="text-(--t-text)">{{ selectedProduct.stock.toLocaleString() }} шт.</p>
                    </div>
                </div>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showProductModal = false">Закрыть</VButton>
                <VButton variant="b2b">Добавить в корзину</VButton>
            </template>
        </VModal>
    </div>
</template>
