<script setup>
/**
 * B2BCart — Shopping cart for wholesale orders with bulk quantity editing,
 * tier-based pricing, and credit line integration.
 */
import { ref, computed } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VInput from '../UI/VInput.vue';

const cartItems = ref([
    {
        id: 1,
        productId: 1,
        name: 'Ноутбук ProBook 15',
        sku: 'NB-001',
        image: '💻',
        quantity: 15,
        minOrderQty: 10,
        pricePerUnit: 29000,
        totalPrice: 435000,
        stock: 500,
        tier: 'gold',
    },
    {
        id: 2,
        productId: 3,
        name: 'Оливковое масло Extra Virgin (10л)',
        sku: 'FD-003',
        image: '🫒',
        quantity: 25,
        minOrderQty: 20,
        pricePerUnit: 5000,
        totalPrice: 125000,
        stock: 300,
        tier: 'gold',
    },
    {
        id: 3,
        productId: 5,
        name: 'Медицинские перчатки (1000 шт)',
        sku: 'MD-005',
        image: '🧤',
        quantity: 150,
        minOrderQty: 100,
        pricePerUnit: 1600,
        totalPrice: 240000,
        stock: 5000,
        tier: 'gold',
    },
]);

const selectedItems = ref([]);
const showBulkEditModal = ref(false);

const subtotal = computed(() => cartItems.value.reduce((sum, item) => sum + item.totalPrice, 0));
const discount = computed(() => subtotal.value * 0.05); // 5% bulk discount
const vat = computed(() => (subtotal.value - discount.value) * 0.20); // 20% VAT
const total = computed(() => subtotal.value - discount.value + vat.value);

const totalItems = computed(() => cartItems.value.reduce((sum, item) => sum + item.quantity, 0));
const allSelected = computed(() => cartItems.value.length > 0 && selectedItems.value.length === cartItems.value.length);

function toggleSelectAll() {
    if (allSelected.value) {
        selectedItems.value = [];
    } else {
        selectedItems.value = cartItems.value.map(item => item.id);
    }
}

function toggleSelect(itemId) {
    const index = selectedItems.value.indexOf(itemId);
    if (index > -1) {
        selectedItems.value.splice(index, 1);
    } else {
        selectedItems.value.push(itemId);
    }
}

function updateQuantity(itemId, newQuantity) {
    const item = cartItems.value.find(i => i.id === itemId);
    if (item) {
        const qty = Math.max(item.minOrderQty, Math.min(newQuantity, item.stock));
        item.quantity = qty;
        item.totalPrice = qty * item.pricePerUnit;
    }
}

function removeItem(itemId) {
    const index = cartItems.value.findIndex(i => i.id === itemId);
    if (index > -1) {
        cartItems.value.splice(index, 1);
        selectedItems.value = selectedItems.value.filter(id => id !== itemId);
    }
}

function removeSelected() {
    cartItems.value = cartItems.value.filter(item => !selectedItems.value.includes(item.id));
    selectedItems.value = [];
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-(--t-text)">🛒 Корзина B2B</h1>
                <p class="text-sm text-(--t-text-3) mt-1">{{ totalItems }} товаров на сумму {{ total.toLocaleString() }} ₽</p>
            </div>
            <div class="flex items-center gap-2">
                <VButton 
                    v-if="selectedItems.length > 0" 
                    variant="danger" 
                    size="sm"
                    @click="removeSelected"
                >
                    Удалить выбранные ({{ selectedItems.length }})
                </VButton>
                <VButton variant="secondary" size="sm">📥 Импорт из Excel</VButton>
            </div>
        </div>

        <!-- Cart Items -->
        <div v-if="cartItems.length === 0" class="text-center py-16">
            <p class="text-5xl mb-4">🛒</p>
            <p class="text-(--t-text-2) text-lg">Корзина пуста</p>
            <p class="text-(--t-text-3) text-sm mt-2">Добавьте товары из каталога для оформления заказа</p>
            <VButton variant="b2b" size="md" class="mt-6">Перейти в каталог</VButton>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Cart Items List -->
            <div class="lg:col-span-2 space-y-4">
                <!-- Select All -->
                <label class="flex items-center gap-3 p-4 rounded-xl bg-(--t-surface) border border-(--t-border) cursor-pointer">
                    <input 
                        type="checkbox" 
                        :checked="allSelected"
                        @change="toggleSelectAll"
                        class="rounded border-(--t-border) text-(--t-primary) focus:ring-(--t-primary)"
                    />
                    <span class="text-sm text-(--t-text)">Выбрать все ({{ cartItems.length }})</span>
                </label>

                <!-- Cart Items -->
                <VCard 
                    v-for="item in cartItems" 
                    :key="item.id"
                    flat
                    :class="selectedItems.includes(item.id) ? 'border-(--t-primary)/50' : ''"
                >
                    <div class="flex gap-4">
                        <!-- Checkbox -->
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                :checked="selectedItems.includes(item.id)"
                                @change="toggleSelect(item.id)"
                                class="rounded border-(--t-border) text-(--t-primary) focus:ring-(--t-primary)"
                            />
                        </div>

                        <!-- Product Image -->
                        <div class="text-4xl shrink-0">{{ item.image }}</div>

                        <!-- Product Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-semibold text-(--t-text)">{{ item.name }}</h3>
                                    <p class="text-xs text-(--t-text-3) mt-1">SKU: {{ item.sku }}</p>
                                    <VBadge :text="item.tier.toUpperCase()" variant="b2b" size="xs" class="mt-2" />
                                </div>
                                <button 
                                    @click="removeItem(item.id)"
                                    class="text-(--t-text-3) hover:text-red-400 transition-colors"
                                >
                                    ✕
                                </button>
                            </div>

                            <!-- Quantity and Price -->
                            <div class="flex items-center justify-between mt-4">
                                <div class="flex items-center gap-2">
                                    <button 
                                        @click="updateQuantity(item.id, item.quantity - 10)"
                                        class="w-8 h-8 rounded-lg bg-(--t-card-hover) text-(--t-text) hover:bg-(--t-primary-dim) transition-colors"
                                    >
                                        −
                                    </button>
                                    <VInput 
                                        :model-value="item.quantity" 
                                        type="number"
                                        @update:model-value="updateQuantity(item.id, $event)"
                                        class="w-24"
                                    />
                                    <button 
                                        @click="updateQuantity(item.id, item.quantity + 10)"
                                        class="w-8 h-8 rounded-lg bg-(--t-card-hover) text-(--t-text) hover:bg-(--t-primary-dim) transition-colors"
                                    >
                                        +
                                    </button>
                                    <span class="text-xs text-(--t-text-3)">Мин: {{ item.minOrderQty }}</span>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-(--t-text-3)">{{ item.pricePerUnit.toLocaleString() }} ₽/шт</p>
                                    <p class="text-lg font-bold text-(--t-text)">{{ item.totalPrice.toLocaleString() }} ₽</p>
                                </div>
                            </div>

                            <!-- Stock Warning -->
                            <div v-if="item.quantity >= item.stock * 0.9" class="mt-2">
                                <VBadge 
                                    :text="item.quantity >= item.stock ? 'Доступно весь остаток' : 'Мало на складе'" 
                                    :variant="item.quantity >= item.stock ? 'warning' : 'warning'" 
                                    size="xs" 
                                />
                            </div>
                        </div>
                    </div>
                </VCard>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <VCard title="Итого" subtitle="Сводка заказа">
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-(--t-text-3)">Товары ({{ totalItems }} шт)</span>
                            <span class="text-(--t-text)">{{ subtotal.toLocaleString() }} ₽</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-(--t-text-3)">Скидка за объём (5%)</span>
                            <span class="text-emerald-400">-{{ discount.toLocaleString() }} ₽</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-(--t-text-3)">НДС (20%)</span>
                            <span class="text-(--t-text)">{{ vat.toLocaleString() }} ₽</span>
                        </div>
                        <div class="border-t border-(--t-border) pt-3">
                            <div class="flex justify-between">
                                <span class="font-bold text-(--t-text)">Итого</span>
                                <span class="text-xl font-bold text-(--t-primary)">{{ total.toLocaleString() }} ₽</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="mt-6 space-y-3">
                        <p class="text-sm font-medium text-(--t-text)">Способ оплаты</p>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-(--t-border) cursor-pointer hover:border-(--t-primary)/30 transition-colors">
                                <input type="radio" name="payment" checked class="text-(--t-primary)" />
                                <span class="text-2xl">💳</span>
                                <div>
                                    <p class="text-sm font-medium text-(--t-text)">Кредитная линия</p>
                                    <p class="text-xs text-(--t-text-3)">Отсрочка 14 дней</p>
                                </div>
                                <VBadge text="Рекомендуется" variant="b2b" size="xs" />
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-(--t-border) cursor-pointer hover:border-(--t-primary)/30 transition-colors">
                                <input type="radio" name="payment" class="text-(--t-primary)" />
                                <span class="text-2xl">💰</span>
                                <div>
                                    <p class="text-sm font-medium text-(--t-text)">Предоплата</p>
                                    <p class="text-xs text-(--t-text-3)">100% до отгрузки</p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-(--t-border) cursor-pointer hover:border-(--t-primary)/30 transition-colors">
                                <input type="radio" name="payment" class="text-(--t-primary)" />
                                <span class="text-2xl">🏦</span>
                                <div>
                                    <p class="text-sm font-medium text-(--t-text)">Безналичный расчёт</p>
                                    <p class="text-xs text-(--t-text-3)">Счёт на оплату</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 space-y-2">
                        <VButton variant="b2b" full-width size="lg">Оформить заказ</VButton>
                        <VButton variant="secondary" full-width>Сохранить как черновик</VButton>
                        <VButton variant="ghost" full-width>Продолжить покупки</VButton>
                    </div>
                </VCard>

                <!-- Credit Info -->
                <VCard title="💳 Кредитная линия" subtitle="Ваш лимит и использование" class="mt-4">
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-(--t-text-3)">Лимит</span>
                            <span class="text-(--t-text)">500 000 ₽</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-(--t-text-3)">Использовано</span>
                            <span class="text-(--t-text)">187 500 ₽</span>
                        </div>
                        <div class="h-2 rounded-full bg-(--t-border) overflow-hidden">
                            <div class="h-full rounded-full bg-linear-to-r from-amber-400 to-orange-400" style="width: 37.5%" />
                        </div>
                        <div class="flex justify-between text-xs text-(--t-text-3)">
                            <span>37.5%</span>
                            <span>Доступно: 312 500 ₽</span>
                        </div>
                        <p class="text-xs text-amber-400 mt-2">✓ Достаточно для этого заказа</p>
                    </div>
                </VCard>
            </div>
        </div>
    </div>
</template>
