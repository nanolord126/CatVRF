<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import FurnitureDashboard from './FurnitureDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд', icon: '📊' },
    { key: 'design', label: 'Дизайн-проекты', icon: '🛋️' },
    { key: 'stock', label: 'Склад', icon: '📦' },
    { key: 'delivery', label: 'Логистика', icon: '🚚' },
];

const designProjects = [
    { id: 'FN-5501', style: 'Сканди', stage: '3D-рендер', budget: '420 000 ₽' },
    { id: 'FN-5507', style: 'Минимализм', stage: 'Смета', budget: '680 000 ₽' },
    { id: 'FN-5510', style: 'Лофт', stage: 'Согласование', budget: '1 120 000 ₽' },
];

const stockAlerts = [
    { sku: 'SF-00812', name: 'Диван Nord', stock: 4 },
    { sku: 'TB-11301', name: 'Стол Oakline', stock: 6 },
    { sku: 'CH-77215', name: 'Стул Flex', stock: 9 },
];

const routes = [
    { route: 'Склад Центр → Мск Сити', eta: '3 ч 20 мин', load: 'Крупногабарит' },
    { route: 'Склад Север → Одинцово', eta: '2 ч 10 мин', load: 'Сборный' },
    { route: 'Склад Восток → Люберцы', eta: '1 ч 45 мин', load: 'Сборный' },
];
</script>

<template>
    <section class="space-y-4">
        <header class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold" style="color: var(--t-text);">Furniture Panel</h2>
                <p class="text-sm" style="color: var(--t-text-3);">3D-дизайн, смета, склад и доставка крупногабарита.</p>
            </div>
            <VBadge text="PRODUCTION" variant="success" size="sm" dot />
        </header>

        <VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" />
        <FurnitureDashboard v-if="activeTab === 'dashboard'" />

        <VCard v-else-if="activeTab === 'design'" title="Активные дизайн-проекты">
            <div class="space-y-2">
                <article v-for="project in designProjects" :key="project.id" class="rounded-xl border p-3" style="border-color: var(--t-border);">
                    <div class="font-semibold" style="color: var(--t-text);">{{ project.id }} · {{ project.style }}</div>
                    <div class="text-sm mt-1" style="color: var(--t-text-2);">Этап: {{ project.stage }}</div>
                    <div class="text-xs" style="color: var(--t-text-3);">Бюджет: {{ project.budget }}</div>
                </article>
            </div>
        </VCard>

        <VCard v-else-if="activeTab === 'stock'" title="Критичные остатки">
            <div class="space-y-2">
                <article v-for="item in stockAlerts" :key="item.sku" class="rounded-xl border p-3 flex items-center justify-between gap-2" style="border-color: var(--t-border);">
                    <div>
                        <div class="font-semibold" style="color: var(--t-text);">{{ item.name }}</div>
                        <div class="text-xs" style="color: var(--t-text-3);">{{ item.sku }}</div>
                    </div>
                    <VBadge :text="item.stock + ' шт'" variant="warning" size="xs" />
                </article>
            </div>
        </VCard>

        <VCard v-else title="Логистические маршруты">
            <div class="space-y-2">
                <article v-for="row in routes" :key="row.route" class="rounded-xl border p-3" style="border-color: var(--t-border);">
                    <div class="font-semibold" style="color: var(--t-text);">{{ row.route }}</div>
                    <div class="text-sm mt-1" style="color: var(--t-text-2);">ETA: {{ row.eta }}</div>
                    <div class="text-xs" style="color: var(--t-text-3);">Тип: {{ row.load }}</div>
                </article>
            </div>
        </VCard>
    </section>
</template>
