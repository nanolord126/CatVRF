<script setup lang="ts">
/**
 * CatVRF 2026 — FoodRestaurantCard
 * Карточка ресторана: лого, рейтинг, время доставки, кухня, мин. заказ
 */
import type { FoodRestaurant } from '@/types/food';
import { StarIcon, ClockIcon, TruckIcon } from '@heroicons/vue/24/outline';
import { StarIcon as StarSolidIcon } from '@heroicons/vue/24/solid';

const props = defineProps<{
    restaurant: FoodRestaurant;
}>();

const emit = defineEmits<{
    select: [restaurant: FoodRestaurant];
}>();

function formatMoney(amount: number): string {
    return amount.toLocaleString('ru-RU') + ' ₽';
}
</script>

<template>
    <div
        class="group cursor-pointer overflow-hidden rounded-2xl border border-(--t-border) bg-(--t-surface) transition-shadow hover:shadow-lg"
        @click="emit('select', restaurant)"
    >
        <!-- Cover image -->
        <div class="relative aspect-[16/9] overflow-hidden bg-(--t-surface-secondary)">
            <img
                v-if="restaurant.cover_url"
                :src="restaurant.cover_url"
                :alt="restaurant.name"
                class="size-full object-cover transition-transform group-hover:scale-105"
                loading="lazy"
            />
            <div v-else class="flex size-full items-center justify-center text-5xl">
                🍴
            </div>

            <!-- Open/Closed badge -->
            <div class="absolute right-2 top-2">
                <span
                    class="rounded-full px-2.5 py-0.5 text-xs font-medium text-white"
                    :class="restaurant.is_open ? 'bg-emerald-500' : 'bg-red-500'"
                >
                    {{ restaurant.is_open ? 'Открыто' : 'Закрыто' }}
                </span>
            </div>

            <!-- Logo overlay -->
            <div class="absolute -bottom-5 left-4">
                <img
                    v-if="restaurant.logo_url"
                    :src="restaurant.logo_url"
                    :alt="restaurant.name"
                    class="size-12 rounded-xl border-2 border-(--t-surface) object-cover shadow-md"
                />
                <div
                    v-else
                    class="flex size-12 items-center justify-center rounded-xl border-2 border-(--t-surface) bg-(--t-primary) text-lg font-bold text-white shadow-md"
                >
                    {{ restaurant.name.charAt(0) }}
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4 pt-8">
            <!-- Name -->
            <h3 class="mb-1 truncate text-lg font-bold text-(--t-text)">{{ restaurant.name }}</h3>

            <!-- Cuisine tags -->
            <div class="mb-2 flex flex-wrap gap-1">
                <span
                    v-for="cuisine in restaurant.cuisine_types.slice(0, 3)"
                    :key="cuisine"
                    class="rounded-lg bg-(--t-surface-secondary) px-2 py-0.5 text-xs text-(--t-text-secondary)"
                >
                    {{ cuisine }}
                </span>
                <span
                    v-if="restaurant.cuisine_types.length > 3"
                    class="text-xs text-(--t-text-secondary)"
                >
                    +{{ restaurant.cuisine_types.length - 3 }}
                </span>
            </div>

            <!-- Description -->
            <p v-if="restaurant.description" class="mb-3 line-clamp-2 text-sm text-(--t-text-secondary)">
                {{ restaurant.description }}
            </p>

            <!-- Stats row -->
            <div class="flex items-center gap-4 text-sm">
                <!-- Rating -->
                <div class="flex items-center gap-1">
                    <StarSolidIcon class="size-4 text-amber-400" />
                    <span class="font-medium text-(--t-text)">{{ restaurant.rating.toFixed(1) }}</span>
                    <span class="text-xs text-(--t-text-secondary)">({{ restaurant.reviews_count }})</span>
                </div>

                <!-- Delivery time -->
                <div class="flex items-center gap-1 text-(--t-text-secondary)">
                    <ClockIcon class="size-4" />
                    <span>{{ restaurant.delivery_time_min }}–{{ restaurant.delivery_time_max }} мин</span>
                </div>

                <!-- Delivery fee -->
                <div class="flex items-center gap-1 text-(--t-text-secondary)">
                    <TruckIcon class="size-4" />
                    <span>
                        {{ restaurant.delivery_fee > 0 ? formatMoney(restaurant.delivery_fee) : 'Бесплатно' }}
                    </span>
                </div>
            </div>

            <!-- Min order -->
            <p v-if="restaurant.min_order > 0" class="mt-2 text-xs text-(--t-text-secondary)">
                Минимальный заказ: {{ formatMoney(restaurant.min_order) }}
            </p>
        </div>
    </div>
</template>
