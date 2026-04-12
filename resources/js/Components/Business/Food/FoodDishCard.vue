<script setup lang="ts">
/**
 * CatVRF 2026 — FoodDishCard
 * Карточка блюда: фото, название, цена, КБЖУ, аллергены, статус наличия
 */
import type { FoodDish } from '@/types/food';
import { ShoppingCartIcon, ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps<{
    dish: FoodDish;
    isB2B?: boolean;
}>();

const emit = defineEmits<{
    'add-to-cart': [dish: FoodDish];
    select: [dish: FoodDish];
}>();

function formatMoney(amount: number): string {
    return amount.toLocaleString('ru-RU') + ' ₽';
}

const displayPrice = props.isB2B && props.dish.price_b2b ? props.dish.price_b2b : props.dish.price;
</script>

<template>
    <div
        class="group cursor-pointer overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface) transition-shadow hover:shadow-md"
        :class="{ 'grayscale opacity-60': !dish.is_available }"
        @click="emit('select', dish)"
    >
        <!-- Image -->
        <div class="relative aspect-[4/3] overflow-hidden bg-(--t-surface-secondary)">
            <img
                v-if="dish.image_url"
                :src="dish.image_url"
                :alt="dish.name"
                class="size-full object-cover transition-transform group-hover:scale-105"
                loading="lazy"
            />
            <div v-else class="flex size-full items-center justify-center text-4xl">
                🍽️
            </div>

            <!-- Tags -->
            <div class="absolute left-2 top-2 flex gap-1">
                <span v-if="dish.is_vegetarian" class="rounded-lg bg-emerald-500 px-2 py-0.5 text-xs font-medium text-white">
                    🌱 Вегетарианское
                </span>
                <span v-if="dish.is_vegan" class="rounded-lg bg-green-600 px-2 py-0.5 text-xs font-medium text-white">
                    🥬 Веганское
                </span>
            </div>

            <!-- Unavailable overlay -->
            <div v-if="!dish.is_available" class="absolute inset-0 flex items-center justify-center bg-black/40">
                <span class="rounded-lg bg-black/70 px-3 py-1 text-sm font-bold text-white">Нет в наличии</span>
            </div>
        </div>

        <!-- Content -->
        <div class="p-3">
            <!-- Name + category -->
            <p class="text-xs text-(--t-text-secondary)">{{ dish.category }}</p>
            <h3 class="mb-1 truncate font-semibold text-(--t-text)">{{ dish.name }}</h3>

            <!-- Description -->
            <p v-if="dish.description" class="mb-2 line-clamp-2 text-xs text-(--t-text-secondary)">
                {{ dish.description }}
            </p>

            <!-- КБЖУ -->
            <div v-if="dish.calories" class="mb-2 flex gap-2 text-xs text-(--t-text-secondary)">
                <span>{{ dish.calories }} ккал</span>
                <span v-if="dish.proteins">Б: {{ dish.proteins }}г</span>
                <span v-if="dish.fats">Ж: {{ dish.fats }}г</span>
                <span v-if="dish.carbs">У: {{ dish.carbs }}г</span>
            </div>

            <!-- Weight + prep time -->
            <div class="mb-3 flex items-center gap-3 text-xs text-(--t-text-secondary)">
                <span v-if="dish.weight_grams">{{ dish.weight_grams }}г</span>
                <span v-if="dish.preparation_time_min" class="flex items-center gap-1">
                    <ClockIcon class="size-3.5" />
                    {{ dish.preparation_time_min }} мин
                </span>
            </div>

            <!-- Allergens -->
            <div v-if="dish.allergens.length > 0" class="mb-3 flex flex-wrap gap-1">
                <span
                    v-for="allergen in dish.allergens"
                    :key="allergen"
                    class="rounded bg-amber-100 px-1.5 py-0.5 text-xs text-amber-700 dark:bg-amber-900/40 dark:text-amber-300"
                >
                    ⚠️ {{ allergen }}
                </span>
            </div>

            <!-- Price + Cart button -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-lg font-bold text-(--t-text)">{{ formatMoney(displayPrice) }}</p>
                    <p v-if="isB2B && dish.price_b2b" class="text-xs text-(--t-text-secondary) line-through">
                        {{ formatMoney(dish.price) }}
                    </p>
                </div>
                <button
                    v-if="dish.is_available"
                    class="flex items-center gap-1.5 rounded-xl bg-(--t-primary) px-3 py-2 text-sm font-medium text-white hover:bg-(--t-primary)/90"
                    @click.stop="emit('add-to-cart', dish)"
                >
                    <ShoppingCartIcon class="size-4" />
                    В корзину
                </button>
            </div>
        </div>
    </div>
</template>
