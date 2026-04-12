<script setup>
/**
 * DeliveryTracking — управление доставками, курьерами, геотрекинг.
 * Реал-тайм карта, статусы, маршруты.
 */
import { ref } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VTabs from '../UI/VTabs.vue';
import VStatCard from '../UI/VStatCard.vue';
import VModal from '../UI/VModal.vue';

const activeTab = ref('active');
const tabs = [
    { key: 'active', label: 'Активные', badge: 8 },
    { key: 'couriers', label: 'Курьеры' },
    { key: 'zones', label: 'Зоны доставки' },
    { key: 'history', label: 'История' },
];

const showDeliveryDetail = ref(false);
const selectedDelivery = ref(null);

const activeDeliveries = [
    { id: 'DEL-4521', orderId: 'ORD-20492', courier: 'Алексей К.', courierPhone: '+7 912 ***-45-67', status: 'in_transit', eta: '15 мин', distance: '3.2 км', customer: 'Иванов А.', address: 'ул. Ленина 15, кв. 42', progress: 65 },
    { id: 'DEL-4520', orderId: 'ORD-20491', courier: 'Дмитрий В.', courierPhone: '+7 903 ***-12-34', status: 'picked_up', eta: '28 мин', distance: '7.8 км', customer: 'ООО «Альфа»', address: 'ул. Мира 8, офис 301', progress: 35 },
    { id: 'DEL-4519', orderId: 'ORD-20490', courier: null, courierPhone: null, status: 'pending', eta: '—', distance: '5.1 км', customer: 'Петрова М.', address: 'пр. Победы 22, кв. 7', progress: 0 },
    { id: 'DEL-4518', orderId: 'ORD-20489', courier: 'Сергей Н.', courierPhone: '+7 926 ***-78-90', status: 'in_transit', eta: '8 мин', distance: '1.4 км', customer: 'ИП Сидоров', address: 'ул. Гагарина 5', progress: 85 },
];

const couriers = [
    { id: 1, name: 'Алексей Козлов', vehicle: '🚗 Авто', rating: 4.9, deliveries: 342, isOnline: true, currentLocation: 'ул. Ленина 10' },
    { id: 2, name: 'Дмитрий Волков', vehicle: '🛵 Скутер', rating: 4.7, deliveries: 215, isOnline: true, currentLocation: 'пр. Мира 3' },
    { id: 3, name: 'Сергей Носов', vehicle: '🚲 Велосипед', rating: 4.8, deliveries: 189, isOnline: true, currentLocation: 'ул. Гагарина 2' },
    { id: 4, name: 'Андрей Петров', vehicle: '🚗 Авто', rating: 4.6, deliveries: 456, isOnline: false, currentLocation: '—' },
    { id: 5, name: 'Мария Иванова', vehicle: '🛵 Скутер', rating: 4.9, deliveries: 128, isOnline: false, currentLocation: '—' },
];

const statusMap = {
    pending: { label: 'Ожидает курьера', variant: 'warning', icon: '⏳' },
    assigned: { label: 'Назначен', variant: 'info', icon: '📋' },
    picked_up: { label: 'Забран', variant: 'info', icon: '📦' },
    in_transit: { label: 'В пути', variant: 'live', icon: '🚀' },
    delivered: { label: 'Доставлен', variant: 'success', icon: '✅' },
    failed: { label: 'Ошибка', variant: 'danger', icon: '❌' },
};

function openDelivery(delivery) {
    selectedDelivery.value = delivery;
    showDeliveryDetail.value = true;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl lg:text-2xl font-bold text-(--t-text)">🚚 Доставка</h1>
                <p class="text-xs text-(--t-text-3)">Курьеры, геотрекинг и маршруты</p>
            </div>
            <div class="flex items-center gap-2">
                <VBadge text="3 онлайн" variant="live" pulse />
                <VButton variant="primary" size="sm">🗺️ Открыть карту</VButton>
            </div>
        </div>

        <!-- Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <VStatCard title="Активных доставок" value="8" icon="🚚" color="primary" clickable />
            <VStatCard title="Курьеры онлайн" value="3/5" icon="👤" color="emerald" clickable />
            <VStatCard title="Среднее время" value="24 мин" icon="⏱️" :trend="-8.3" trend-label="быстрее" color="indigo" clickable />
            <VStatCard title="Доставлено сегодня" value="42" icon="✅" :trend="15.6" color="amber" clickable />
        </div>

        <!-- Tabs -->
        <VTabs :tabs="tabs" v-model="activeTab" variant="underline" />

        <!-- Active Deliveries -->
        <template v-if="activeTab === 'active'">
            <!-- Map Placeholder -->
            <VCard no-padding>
                <div class="h-48 bg-linear-to-br from-(--t-surface) via-(--t-card-hover) to-(--t-surface) flex items-center justify-center relative overflow-hidden rounded-t-2xl">
                    <div class="absolute inset-0 opacity-10">
                        <div class="grid grid-cols-8 grid-rows-4 h-full w-full gap-px">
                            <div v-for="i in 32" :key="i" class="bg-(--t-primary)" :style="{opacity: Math.random() * 0.3}" />
                        </div>
                    </div>
                    <div class="relative z-10 text-center">
                        <div class="text-4xl mb-2">🗺️</div>
                        <div class="text-sm font-medium text-(--t-text-2)">Реал-тайм карта доставок</div>
                        <VButton variant="primary" size="sm" class="mt-2">Открыть полную карту</VButton>
                    </div>
                    <!-- Animated courier dots -->
                    <div v-for="(d, i) in activeDeliveries.filter(x => x.status === 'in_transit')" :key="d.id"
                         class="absolute w-3 h-3 rounded-full bg-emerald-400 shadow-lg shadow-emerald-400/50 animate-pulse"
                         :style="{top: (25 + i * 20) + '%', left: (20 + i * 18) + '%'}"
                    />
                </div>
            </VCard>

            <!-- Delivery List -->
            <div class="space-y-3">
                <div v-for="delivery in activeDeliveries" :key="delivery.id"
                     class="p-4 rounded-xl border border-(--t-border) hover:border-(--t-primary)/20 bg-(--t-surface) transition-all cursor-pointer active:scale-[0.99] hover:shadow-lg hover:shadow-(--t-primary)/5"
                     @click="openDelivery(delivery)"
                >
                    <div class="flex items-start gap-3">
                        <!-- Status icon -->
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0"
                             :class="delivery.status === 'in_transit' ? 'bg-emerald-500/10' : delivery.status === 'pending' ? 'bg-amber-500/10' : 'bg-(--t-primary-dim)'"
                        >
                            {{ statusMap[delivery.status]?.icon || '📦' }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-mono font-bold text-(--t-primary)">{{ delivery.id }}</span>
                                <VBadge :text="statusMap[delivery.status]?.label" :variant="statusMap[delivery.status]?.variant" size="xs" :pulse="delivery.status === 'in_transit'" dot />
                            </div>
                            <div class="text-sm text-(--t-text)">{{ delivery.customer }}</div>
                            <div class="text-xs text-(--t-text-3)">{{ delivery.address }}</div>
                        </div>

                        <div class="text-right shrink-0">
                            <div class="text-lg font-bold text-(--t-text)">{{ delivery.eta }}</div>
                            <div class="text-[10px] text-(--t-text-3)">{{ delivery.distance }}</div>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="mt-3 flex items-center gap-3">
                        <div class="flex-1 h-1.5 rounded-full bg-(--t-border) overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-1000"
                                 :class="delivery.status === 'in_transit' ? 'bg-linear-to-r from-emerald-400 to-emerald-300' : delivery.status === 'pending' ? 'bg-amber-400' : 'bg-(--t-primary)'"
                                 :style="{width: delivery.progress + '%'}"
                            />
                        </div>
                        <span class="text-[10px] text-(--t-text-3) w-8 text-right">{{ delivery.progress }}%</span>
                    </div>

                    <!-- Courier info -->
                    <div v-if="delivery.courier" class="mt-2 flex items-center gap-2 text-xs text-(--t-text-3)">
                        <span class="w-5 h-5 rounded-full bg-emerald-500/20 flex items-center justify-center text-[10px]">👤</span>
                        <span>{{ delivery.courier }}</span>
                        <span>•</span>
                        <span>{{ delivery.courierPhone }}</span>
                    </div>
                    <div v-else class="mt-2">
                        <VButton variant="primary" size="xs">Назначить курьера</VButton>
                    </div>
                </div>
            </div>
        </template>

        <!-- Couriers Tab -->
        <template v-if="activeTab === 'couriers'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="courier in couriers" :key="courier.id"
                     :class="['p-4 rounded-xl border transition-all cursor-pointer hover:shadow-lg active:scale-[0.98]', courier.isOnline ? 'border-emerald-500/20 bg-(--t-surface)' : 'border-(--t-border) bg-(--t-surface) opacity-60']"
                >
                    <div class="flex items-center gap-3 mb-3">
                        <div class="relative">
                            <div class="w-12 h-12 rounded-full bg-linear-to-br from-(--t-primary-dim) to-(--t-card-hover) flex items-center justify-center text-lg">👤</div>
                            <div v-if="courier.isOnline" class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full bg-emerald-400 border-2 border-(--t-surface) shadow-lg shadow-emerald-400/50 animate-pulse" />
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-(--t-text)">{{ courier.name }}</div>
                            <div class="text-xs text-(--t-text-3)">{{ courier.vehicle }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <div class="p-2 rounded-lg bg-(--t-card-hover)">
                            <div class="text-[10px] text-(--t-text-3)">Рейтинг</div>
                            <div class="text-sm font-bold text-amber-400">★ {{ courier.rating }}</div>
                        </div>
                        <div class="p-2 rounded-lg bg-(--t-card-hover)">
                            <div class="text-[10px] text-(--t-text-3)">Доставки</div>
                            <div class="text-sm font-bold text-(--t-text)">{{ courier.deliveries }}</div>
                        </div>
                    </div>

                    <div v-if="courier.isOnline" class="text-xs text-(--t-text-3) flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400" />
                        {{ courier.currentLocation }}
                    </div>
                    <div v-else class="text-xs text-(--t-text-3)">Оффлайн</div>
                </div>
            </div>
        </template>

        <!-- Zones Tab -->
        <template v-if="activeTab === 'zones'">
            <VCard title="🗺️ Зоны доставки" subtitle="Настройка зон и тарифов">
                <div class="space-y-3">
                    <div v-for="zone in [
                        {name:'Центр города',radius:'5 км',fee:'150 ₽',time:'20-30 мин',color:'emerald'},
                        {name:'Ближний район',radius:'10 км',fee:'250 ₽',time:'30-45 мин',color:'amber'},
                        {name:'Дальний район',radius:'20 км',fee:'450 ₽',time:'45-60 мин',color:'rose'},
                    ]" :key="zone.name"
                       class="flex items-center gap-4 p-4 rounded-xl border border-(--t-border) hover:bg-(--t-card-hover) transition-all cursor-pointer active:scale-[0.99]"
                    >
                        <div :class="`w-4 h-4 rounded-full bg-${zone.color}-400 shrink-0`" />
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-(--t-text)">{{ zone.name }}</div>
                            <div class="text-xs text-(--t-text-3)">Радиус: {{ zone.radius }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-(--t-text)">{{ zone.fee }}</div>
                            <div class="text-xs text-(--t-text-3)">{{ zone.time }}</div>
                        </div>
                        <VButton variant="ghost" size="xs">✏️</VButton>
                    </div>
                </div>
                <template #footer>
                    <VButton variant="primary" size="sm" full-width>➕ Добавить зону доставки</VButton>
                </template>
            </VCard>
        </template>

        <!-- History Tab -->
        <template v-if="activeTab === 'history'">
            <VCard title="📋 История доставок" subtitle="Все завершённые доставки">
                <div class="space-y-2">
                    <div v-for="h in [
                        {id:'DEL-4517',customer:'Козлова А.',time:'22 мин',rating:5,date:'2026-04-08 11:00'},
                        {id:'DEL-4516',customer:'Волков Д.',time:'35 мин',rating:4,date:'2026-04-08 09:30'},
                        {id:'DEL-4515',customer:'ООО «Бета»',time:'18 мин',rating:5,date:'2026-04-07 17:45'},
                    ]" :key="h.id"
                       class="flex items-center gap-3 p-3 rounded-xl hover:bg-(--t-card-hover) transition-colors cursor-pointer active:scale-[0.99]"
                    >
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-sm">✅</div>
                        <div class="flex-1">
                            <div class="text-sm text-(--t-text)">{{ h.customer }}</div>
                            <div class="text-[10px] text-(--t-text-3)">{{ h.id }} • {{ h.date }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-(--t-text)">{{ h.time }}</div>
                            <div class="text-amber-400 text-xs">{{ '★'.repeat(h.rating) }}</div>
                        </div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- Detail Modal -->
        <VModal v-model="showDeliveryDetail" title="Детали доставки" size="lg">
            <template v-if="selectedDelivery">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-xl bg-(--t-card-hover)">
                            <div class="text-[10px] text-(--t-text-3)">Заказ</div>
                            <div class="text-sm font-bold text-(--t-primary)">{{ selectedDelivery.orderId }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-(--t-card-hover)">
                            <div class="text-[10px] text-(--t-text-3)">Статус</div>
                            <VBadge :text="statusMap[selectedDelivery.status]?.label" :variant="statusMap[selectedDelivery.status]?.variant" size="sm" dot />
                        </div>
                    </div>
                    <div class="p-4 rounded-xl border border-(--t-border)">
                        <div class="text-xs text-(--t-text-3) mb-1">Адрес доставки</div>
                        <div class="text-sm text-(--t-text)">{{ selectedDelivery.address }}</div>
                    </div>
                </div>
            </template>
            <template #footer>
                <VButton variant="secondary" @click="showDeliveryDetail = false">Закрыть</VButton>
                <VButton variant="primary">Связаться с курьером</VButton>
            </template>
        </VModal>
    </div>
</template>
