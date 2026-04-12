<script setup>
/**
 * ProductsCatalog — управление каталогом товаров и услуг.
 * Вертикаль-зависимые карточки, наличие, B2C/B2B цены.
 */
import { ref, computed } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VTabs from '../UI/VTabs.vue';
import VInput from '../UI/VInput.vue';
import VModal from '../UI/VModal.vue';
import VStatCard from '../UI/VStatCard.vue';

const viewMode = ref('grid');
const searchQuery = ref('');
const activeCategory = ref('all');
const showAddProduct = ref(false);
const showProductDetail = ref(false);
const selectedProduct = ref(null);

const categories = [
    { key: 'all', label: 'Все', badge: 256 },
    { key: 'beauty', label: '💄 Красота' },
    { key: 'fashion', label: '👗 Одежда' },
    { key: 'food', label: '🍕 Еда' },
    { key: 'furniture', label: '🛋️ Мебель' },
    { key: 'fitness', label: '💪 Фитнес' },
];

const products = ref([
    { id: 1, name: 'Набор для макияжа Premium', vertical: 'beauty', price: 4500, priceB2b: 3200, stock: 145, image: '💄', rating: 4.8, sold: 342, status: 'active' },
    { id: 2, name: 'Диван угловой «Монте-Карло»', vertical: 'furniture', price: 89000, priceB2b: 67000, stock: 8, image: '🛋️', rating: 4.9, sold: 28, status: 'active' },
    { id: 3, name: 'Сет роллов «Токио»', vertical: 'food', price: 1200, priceB2b: 850, stock: 0, image: '🍣', rating: 4.7, sold: 1205, status: 'out_of_stock' },
    { id: 4, name: 'Платье вечернее Silk Dream', vertical: 'fashion', price: 15600, priceB2b: 11200, stock: 32, image: '👗', rating: 4.6, sold: 87, status: 'active' },
    { id: 5, name: 'Протеин WheyMax 2кг', vertical: 'fitness', price: 3800, priceB2b: 2700, stock: 210, image: '💪', rating: 4.5, sold: 567, status: 'active' },
    { id: 6, name: 'Стол обеденный Oak Classic', vertical: 'furniture', price: 42000, priceB2b: 31000, stock: 3, image: '🪑', rating: 4.8, sold: 15, status: 'low_stock' },
    { id: 7, name: 'Кроссовки RunFast Pro', vertical: 'fashion', price: 8900, priceB2b: 6200, stock: 0, image: '👟', rating: 4.4, sold: 234, status: 'out_of_stock' },
    { id: 8, name: 'Маска для лица Bio-Lift', vertical: 'beauty', price: 890, priceB2b: 620, stock: 450, image: '✨', rating: 4.9, sold: 1892, status: 'active' },
]);

const filteredProducts = computed(() => {
    let filtered = products.value;
    if (activeCategory.value !== 'all') {
        filtered = filtered.filter(p => p.vertical === activeCategory.value);
    }
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        filtered = filtered.filter(p => p.name.toLowerCase().includes(q));
    }
    return filtered;
});

const statusClasses = {
    active: 'border-emerald-500/20',
    low_stock: 'border-amber-500/20',
    out_of_stock: 'border-rose-500/20 opacity-60 grayscale',
};

const showImportModal = ref(false);
const showModifiers = ref(false);
const showSchedule = ref(false);

const modifiers = ref([
    { id: 1, name: 'Размер S', group: 'Размер', priceAdd: 0, active: true },
    { id: 2, name: 'Размер M', group: 'Размер', priceAdd: 0, active: true },
    { id: 3, name: 'Размер L', group: 'Размер', priceAdd: 200, active: true },
    { id: 4, name: 'Размер XL', group: 'Размер', priceAdd: 400, active: true },
    { id: 5, name: 'Подарочная упаковка', group: 'Доп. услуги', priceAdd: 350, active: true },
    { id: 6, name: 'Экспресс-доставка', group: 'Доп. услуги', priceAdd: 500, active: false },
]);

const scheduleSlots = ref([
    { id: 1, day: 'Понедельник', from: '09:00', to: '21:00', active: true },
    { id: 2, day: 'Вторник', from: '09:00', to: '21:00', active: true },
    { id: 3, day: 'Среда', from: '09:00', to: '21:00', active: true },
    { id: 4, day: 'Четверг', from: '09:00', to: '21:00', active: true },
    { id: 5, day: 'Пятница', from: '09:00', to: '22:00', active: true },
    { id: 6, day: 'Суббота', from: '10:00', to: '20:00', active: true },
    { id: 7, day: 'Воскресенье', from: '10:00', to: '18:00', active: false },
]);

function openProduct(product) {
    selectedProduct.value = product;
    showProductDetail.value = true;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl lg:text-2xl font-bold text-(--t-text)">📦 Товары и услуги</h1>
                <p class="text-xs text-(--t-text-3)">Каталог по всем вертикалям</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex items-center bg-(--t-surface) border border-(--t-border) rounded-lg overflow-hidden">
                    <button @click="viewMode = 'grid'" :class="['p-2 text-xs cursor-pointer transition-colors', viewMode === 'grid' ? 'bg-(--t-primary-dim) text-(--t-primary)' : 'text-(--t-text-3) hover:text-(--t-text)']">▦</button>
                    <button @click="viewMode = 'list'" :class="['p-2 text-xs cursor-pointer transition-colors', viewMode === 'list' ? 'bg-(--t-primary-dim) text-(--t-primary)' : 'text-(--t-text-3) hover:text-(--t-text)']">☰</button>
                </div>
                <VButton variant="secondary" size="sm">📥 Импорт</VButton>
                <VButton variant="primary" size="sm" @click="showAddProduct = true">➕ Добавить</VButton>
            </div>
        </div>

        <!-- Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <VStatCard title="Всего товаров" value="256" icon="📦" color="primary" clickable />
            <VStatCard title="В наличии" value="198" icon="✅" color="emerald" clickable />
            <VStatCard title="Мало на складе" value="24" icon="⚠️" color="amber" clickable />
            <VStatCard title="Нет в наличии" value="34" icon="❌" color="rose" clickable />
        </div>

        <!-- Filters -->
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <VTabs :tabs="categories" v-model="activeCategory" variant="pills" size="sm" />
            </div>
            <div class="w-full sm:w-64">
                <VInput v-model="searchQuery" placeholder="Поиск товаров..." prefix-icon="🔍" clearable size="sm" />
            </div>
        </div>

        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div v-for="product in filteredProducts" :key="product.id"
                 :class="['group relative rounded-2xl border bg-(--t-surface) backdrop-blur-sm overflow-hidden transition-all duration-300 cursor-pointer hover:-translate-y-1 hover:shadow-lg hover:shadow-(--t-primary)/5 active:scale-[0.98]', statusClasses[product.status] || 'border-(--t-border)']"
                 @click="openProduct(product)"
            >
                <!-- Image area -->
                <div class="h-32 bg-linear-to-br from-(--t-card-hover) to-(--t-surface) flex items-center justify-center text-5xl relative">
                    <span :class="{'grayscale': product.status === 'out_of_stock'}">{{ product.image }}</span>
                    <div v-if="product.status === 'out_of_stock'" class="absolute inset-0 flex items-center justify-center bg-black/30 backdrop-blur-[2px]">
                        <span class="text-xs font-bold text-white/80 bg-rose-500/80 px-2 py-1 rounded">Нет в наличии</span>
                    </div>
                    <div v-else-if="product.status === 'low_stock'" class="absolute top-2 right-2">
                        <VBadge text="Мало" variant="warning" size="xs" />
                    </div>
                </div>

                <!-- Info -->
                <div class="p-3 space-y-2">
                    <h3 class="text-xs font-semibold text-(--t-text) line-clamp-2 leading-tight group-hover:text-(--t-primary) transition-colors">{{ product.name }}</h3>
                    <div class="flex items-center gap-1 text-[10px]">
                        <span class="text-amber-400">★</span>
                        <span class="text-(--t-text-2)">{{ product.rating }}</span>
                        <span class="text-(--t-text-3)">• {{ product.sold }} продаж</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-sm font-bold text-(--t-text)">{{ Number(product.price).toLocaleString('ru') }} ₽</span>
                        <span class="text-[10px] text-amber-400 font-medium">B2B: {{ Number(product.priceB2b).toLocaleString('ru') }} ₽</span>
                    </div>
                    <div class="text-[10px] text-(--t-text-3)">Склад: {{ product.stock }} шт</div>
                    <VButton v-if="product.stock > 0" variant="primary" size="xs" full-width>В корзину</VButton>
                </div>
            </div>
        </div>

        <!-- List View -->
        <div v-if="viewMode === 'list'" class="space-y-2">
            <div v-for="product in filteredProducts" :key="product.id"
                 :class="['flex items-center gap-4 p-4 rounded-xl border transition-all cursor-pointer hover:bg-(--t-card-hover) active:scale-[0.99]', statusClasses[product.status] || 'border-(--t-border)']"
                 @click="openProduct(product)"
            >
                <div class="w-12 h-12 rounded-xl bg-(--t-card-hover) flex items-center justify-center text-2xl shrink-0" :class="{'grayscale': product.status === 'out_of_stock'}">{{ product.image }}</div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold text-(--t-text) truncate">{{ product.name }}</div>
                    <div class="flex items-center gap-2 text-[10px] text-(--t-text-3)">
                        <span class="text-amber-400">★ {{ product.rating }}</span>
                        <span>• {{ product.sold }} продаж</span>
                        <span>• Склад: {{ product.stock }} шт</span>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <div class="text-sm font-bold text-(--t-text)">{{ Number(product.price).toLocaleString('ru') }} ₽</div>
                    <div class="text-[10px] text-amber-400">B2B: {{ Number(product.priceB2b).toLocaleString('ru') }} ₽</div>
                </div>
                <VBadge v-if="product.status === 'out_of_stock'" text="Нет" variant="danger" size="xs" />
                <VBadge v-else-if="product.status === 'low_stock'" text="Мало" variant="warning" size="xs" />
            </div>
        </div>

        <!-- Product Detail Modal -->
        <VModal v-model="showProductDetail" :title="selectedProduct?.name" size="lg">
            <template v-if="selectedProduct">
                <div class="space-y-4">
                    <div class="h-40 bg-linear-to-br from-(--t-card-hover) to-(--t-surface) rounded-xl flex items-center justify-center text-7xl">
                        {{ selectedProduct.image }}
                    </div>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="p-3 rounded-xl bg-(--t-card-hover)">
                            <div class="text-[10px] text-(--t-text-3)">Цена B2C</div>
                            <div class="text-lg font-bold text-(--t-text)">{{ Number(selectedProduct.price).toLocaleString('ru') }} ₽</div>
                        </div>
                        <div class="p-3 rounded-xl bg-amber-500/5 border border-amber-500/10">
                            <div class="text-[10px] text-amber-400/60">Цена B2B</div>
                            <div class="text-lg font-bold text-amber-300">{{ Number(selectedProduct.priceB2b).toLocaleString('ru') }} ₽</div>
                        </div>
                        <div class="p-3 rounded-xl bg-(--t-card-hover)">
                            <div class="text-[10px] text-(--t-text-3)">Остаток</div>
                            <div class="text-lg font-bold" :class="selectedProduct.stock > 10 ? 'text-emerald-400' : selectedProduct.stock > 0 ? 'text-amber-400' : 'text-rose-400'">
                                {{ selectedProduct.stock }} шт
                            </div>
                        </div>
                        <div class="p-3 rounded-xl bg-(--t-card-hover)">
                            <div class="text-[10px] text-(--t-text-3)">Продажи</div>
                            <div class="text-lg font-bold text-(--t-text)">{{ selectedProduct.sold }}</div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <VButton variant="secondary" @click="showProductDetail = false">Закрыть</VButton>
                <VButton variant="ghost">✏️ Редактировать</VButton>
                <VButton variant="primary">В корзину</VButton>
            </template>
        </VModal>

        <!-- Toolbar: extra actions -->
        <div class="flex items-center gap-2 flex-wrap">
            <VButton variant="secondary" size="sm" @click="showImportModal = true">📥 Импорт CSV/Excel</VButton>
            <VButton variant="secondary" size="sm">📤 Экспорт каталога</VButton>
            <VButton variant="secondary" size="sm" @click="showModifiers = true">🔧 Модификаторы</VButton>
            <VButton variant="secondary" size="sm" @click="showSchedule = true">📅 Расписание слотов</VButton>
        </div>

        <!-- Add Product Modal -->
        <VModal v-model="showAddProduct" title="Добавить товар" size="lg">
            <div class="space-y-4">
                <VInput label="Название товара" placeholder="Введите название" required />
                <div class="grid grid-cols-2 gap-3">
                    <VInput label="Цена B2C" type="number" placeholder="0 ₽" required />
                    <VInput label="Цена B2B" type="number" placeholder="0 ₽" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <VInput label="Количество на складе" type="number" placeholder="0" />
                    <VInput label="Артикул" placeholder="SKU-..." />
                </div>
                <VInput label="Описание" placeholder="Подробное описание товара" />
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showAddProduct = false">Отмена</VButton>
                <VButton variant="primary">Добавить товар</VButton>
            </template>
        </VModal>

        <!-- Import Modal -->
        <VModal v-model="showImportModal" title="Импорт товаров" size="md">
            <div class="space-y-4">
                <div class="p-6 rounded-xl border-2 border-dashed border-(--t-border) text-center cursor-pointer hover:border-(--t-primary) transition-colors">
                    <div class="text-3xl mb-2">📁</div>
                    <div class="text-sm text-(--t-text)">Перетащите файл или нажмите для выбора</div>
                    <div class="text-[10px] text-(--t-text-3) mt-1">CSV, XLSX — до 10 МБ</div>
                </div>
                <div class="p-3 rounded-lg bg-amber-500/5 border border-amber-500/10">
                    <div class="text-xs text-amber-400">⚠️ Колонки: name, price_b2c, price_b2b, stock, sku, vertical</div>
                </div>
                <VButton variant="ghost" size="sm">📥 Скачать шаблон CSV</VButton>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showImportModal = false">Отмена</VButton>
                <VButton variant="primary">Загрузить</VButton>
            </template>
        </VModal>

        <!-- Modifiers Modal -->
        <VModal v-model="showModifiers" title="Модификаторы товаров" size="lg">
            <div class="space-y-3">
                <div v-for="mod in modifiers" :key="mod.id"
                     class="flex items-center justify-between p-3 rounded-xl bg-(--t-card-hover)"
                >
                    <div class="flex items-center gap-3">
                        <button :class="['w-9 h-5 rounded-full transition-colors cursor-pointer', mod.active ? 'bg-emerald-500' : 'bg-(--t-border)']"
                                @click="mod.active = !mod.active">
                            <div :class="['w-3.5 h-3.5 rounded-full bg-white shadow transition-transform', mod.active ? 'translate-x-4' : 'translate-x-0.5']" />
                        </button>
                        <div>
                            <div class="text-sm text-(--t-text)">{{ mod.name }}</div>
                            <div class="text-[10px] text-(--t-text-3)">{{ mod.group }}</div>
                        </div>
                    </div>
                    <div class="text-sm font-semibold" :class="mod.priceAdd > 0 ? 'text-amber-400' : 'text-(--t-text-3)'">{{ mod.priceAdd > 0 ? '+' + mod.priceAdd + ' ₽' : '—' }}</div>
                </div>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showModifiers = false">Закрыть</VButton>
                <VButton variant="primary">➕ Добавить модификатор</VButton>
            </template>
        </VModal>

        <!-- Schedule Modal -->
        <VModal v-model="showSchedule" title="Расписание слотов" size="md">
            <div class="space-y-2">
                <div v-for="slot in scheduleSlots" :key="slot.id"
                     class="flex items-center justify-between p-3 rounded-xl bg-(--t-card-hover)"
                >
                    <div class="flex items-center gap-3">
                        <button :class="['w-9 h-5 rounded-full transition-colors cursor-pointer', slot.active ? 'bg-emerald-500' : 'bg-(--t-border)']"
                                @click="slot.active = !slot.active">
                            <div :class="['w-3.5 h-3.5 rounded-full bg-white shadow transition-transform', slot.active ? 'translate-x-4' : 'translate-x-0.5']" />
                        </button>
                        <span class="text-sm text-(--t-text) w-28">{{ slot.day }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-(--t-text-2)">
                        <span>{{ slot.from }}</span>
                        <span class="text-(--t-text-3)">—</span>
                        <span>{{ slot.to }}</span>
                    </div>
                </div>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showSchedule = false">Закрыть</VButton>
                <VButton variant="primary">💾 Сохранить</VButton>
            </template>
        </VModal>
    </div>
</template>
