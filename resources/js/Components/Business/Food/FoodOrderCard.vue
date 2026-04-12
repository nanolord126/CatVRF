<script setup lang="ts">
/**
 * CatVRF 2026 — FoodOrderCard
 * Карточка заказа еды: ресторан, блюда, статус, курьер, сумма
 */
import type { FoodOrder } from '@/types/food';
import {
    ClockIcon,
    TruckIcon,
    PhoneIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps<{
    order: FoodOrder;
}>();

const emit = defineEmits<{
    select: [order: FoodOrder];
    'accept-order': [order: FoodOrder];
    'cancel-order': [order: FoodOrder];
}>();

function statusColor(status: FoodOrder['status']): string {
    switch (status) {
        case 'pending': return 'bg-amber-500';
        case 'accepted': return 'bg-blue-500';
        case 'preparing': return 'bg-indigo-500';
        case 'ready': return 'bg-violet-500';
        case 'delivering': return 'bg-cyan-500';
        case 'delivered': return 'bg-emerald-500';
        case 'cancelled': return 'bg-red-500';
        default: return 'bg-gray-400';
    }
}

function statusLabel(status: FoodOrder['status']): string {
    switch (status) {
        case 'pending': return 'Ожидает';
        case 'accepted': return 'Принят';
        case 'preparing': return 'Готовится';
        case 'ready': return 'Готов';
        case 'delivering': return 'Доставляется';
        case 'delivered': return 'Доставлен';
        case 'cancelled': return 'Отменён';
        default: return status;
    }
}

function formatMoney(amount: number): string {
    return amount.toLocaleString('ru-RU') + ' ₽';
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleTimeString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <div
        class="cursor-pointer rounded-2xl border border-(--t-border) bg-(--t-surface) p-4 transition-shadow hover:shadow-md"
        @click="emit('select', order)"
    >
        <!-- Header: restaurant + status -->
        <div class="mb-3 flex items-center justify-between">
            <div class="min-w-0">
                <h3 class="truncate font-semibold text-(--t-text)">{{ order.restaurant_name }}</h3>
                <p class="text-xs text-(--t-text-secondary)">#{{ order.id }} · {{ formatDate(order.created_at) }}</p>
            </div>
            <span
                class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium text-white"
                :class="statusColor(order.status)"
            >
                {{ statusLabel(order.status) }}
            </span>
        </div>

        <!-- Items -->
        <div class="mb-3 space-y-1">
            <div
                v-for="item in order.items.slice(0, 3)"
                :key="item.dish_id"
                class="flex items-center justify-between text-sm"
            >
                <span class="truncate text-(--t-text)">
                    {{ item.quantity }}× {{ item.dish_name }}
                </span>
                <span class="shrink-0 text-(--t-text-secondary)">{{ formatMoney(item.total) }}</span>
            </div>
            <p
                v-if="order.items.length > 3"
                class="text-xs text-(--t-text-secondary)"
            >
                ещё {{ order.items.length - 3 }} позиций…
            </p>
        </div>

        <!-- Customer + Delivery -->
        <div class="mb-3 rounded-xl bg-(--t-surface-secondary) p-2.5">
            <div class="flex items-center justify-between text-sm">
                <span class="text-(--t-text)">{{ order.customer_name }}</span>
                <a
                    :href="`tel:${order.customer_phone}`"
                    class="flex items-center gap-1 text-xs text-(--t-primary)"
                    @click.stop
                >
                    <PhoneIcon class="size-3.5" />
                    {{ order.customer_phone }}
                </a>
            </div>
            <p class="mt-1 truncate text-xs text-(--t-text-secondary)">
                {{ order.delivery_address }}
            </p>
            <div v-if="order.estimated_delivery" class="mt-1 flex items-center gap-1 text-xs text-(--t-text-secondary)">
                <ClockIcon class="size-3.5" />
                Ожидаемая доставка: {{ formatDate(order.estimated_delivery) }}
            </div>
        </div>

        <!-- Footer: total + actions -->
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-(--t-text-secondary)">
                    Доставка: {{ formatMoney(order.delivery_fee) }}
                </p>
                <p class="text-lg font-bold text-(--t-text)">{{ formatMoney(order.total) }}</p>
            </div>
            <div v-if="order.status === 'pending'" class="flex gap-2">
                <button
                    class="rounded-xl bg-(--t-primary) px-4 py-2 text-sm font-medium text-white hover:bg-(--t-primary)/90"
                    @click.stop="emit('accept-order', order)"
                >
                    Принять
                </button>
                <button
                    class="rounded-xl border border-red-300 px-3 py-2 text-sm text-red-500 hover:bg-red-50"
                    @click.stop="emit('cancel-order', order)"
                >
                    Отмена
                </button>
            </div>
            <div v-else-if="order.courier_id" class="flex items-center gap-1 text-xs text-(--t-text-secondary)">
                <TruckIcon class="size-4" />
                Курьер назначен
            </div>
        </div>
    </div>
</template>
