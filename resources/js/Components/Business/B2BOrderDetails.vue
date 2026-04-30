<script setup>
/**
 * B2BOrderDetails — Detailed view of a wholesale order with items,
 * tracking, payment history, and document generation.
 */
import { ref, computed } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VInput from '../UI/VInput.vue';
import VModal from '../UI/VModal.vue';

const props = defineProps({
    orderId: { type: String, required: true },
});

const showTrackingModal = ref(false);
const showInvoiceModal = ref(false);
const showCancelModal = ref(false);
const cancelReason = ref('');

const order = ref({
    id: 'B-2042',
    company: {
        id: '1',
        name: 'ООО «Альфа-Центр»',
        inn: '7712345678',
        kpp: '771201001',
        address: 'Москва, ул. Ленина 15',
        contact: 'Иванов Иван Иванович',
        phone: '+7 (999) 123-45-67',
        email: 'ivanov@alpha-center.ru',
    },
    status: 'shipped',
    payment: {
        method: 'credit',
        status: 'pending',
        amount: 435000,
        dueDate: '2026-05-01',
        paidAmount: 0,
    },
    delivery: {
        method: 'courier',
        address: 'Москва, ул. Ленина 15, офис 301',
        date: '2026-04-20',
        trackingNumber: 'TRK123456789',
        carrier: 'СДЭК',
    },
    items: [
        {
            id: 1,
            productId: 1,
            name: 'Ноутбук ProBook 15',
            sku: 'NB-001',
            image: '💻',
            quantity: 15,
            pricePerUnit: 29000,
            totalPrice: 435000,
            tier: 'gold',
        },
    ],
    documents: [
        { id: 1, type: 'invoice', name: 'Счёт на оплату №1234', date: '2026-04-15', status: 'sent' },
        { id: 2, type: 'contract', name: 'Договор поставки', date: '2026-04-15', status: 'signed' },
        { id: 3, type: 'invoice_factura', name: 'Счёт-фактура', date: '2026-04-17', status: 'pending' },
    ],
    timeline: [
        { date: '2026-04-15 10:30', event: 'Заказ создан', status: 'completed' },
        { date: '2026-04-15 11:00', event: 'Подтверждение оплаты (кредит)', status: 'completed' },
        { date: '2026-04-16 09:00', event: 'Сборка заказа на складе', status: 'completed' },
        { date: '2026-04-17 14:00', event: 'Отгрузка со склада', status: 'completed' },
        { date: '2026-04-20', event: 'Планируемая доставка', status: 'pending' },
    ],
    createdAt: '2026-04-15 10:30',
    updatedAt: '2026-04-17 14:00',
});

const subtotal = computed(() => order.value.items.reduce((sum, item) => sum + item.totalPrice, 0));
const discount = computed(() => subtotal.value * 0.05);
const vat = computed(() => (subtotal.value - discount.value) * 0.20);
const deliveryCost = computed(() => order.value.delivery.method === 'pickup' ? 0 : 5000);
const total = computed(() => subtotal.value - discount.value + vat.value + deliveryCost.value);

const statusColors = {
    pending: { bg: 'rgba(251,191,36,0.12)', text: '#fbbf24', border: 'rgba(251,191,36,0.3)' },
    processing: { bg: 'rgba(96,165,250,0.12)', text: '#60a5fa', border: 'rgba(96,165,250,0.3)' },
    shipped: { bg: 'rgba(168,85,247,0.12)', text: '#a855f7', border: 'rgba(168,85,247,0.3)' },
    completed: { bg: 'rgba(52,211,153,0.12)', text: '#34d399', border: 'rgba(52,211,153,0.3)' },
    cancelled: { bg: 'rgba(248,113,113,0.12)', text: '#f87171', border: 'rgba(248,113,113,0.3)' },
};

const statusLabels = {
    pending: 'Ожидает',
    processing: 'В обработке',
    shipped: 'Отгружен',
    completed: 'Выполнен',
    cancelled: 'Отменён',
};

const documentTypeIcons = {
    invoice: '📄',
    contract: '📋',
    invoice_factura: '🧾',
    act: '📝',
    upd: '📊',
};

function cancelOrder() {
    // Cancel order logic
    showCancelModal.value = false;
}

function generateInvoice() {
    // Generate invoice logic
    showInvoiceModal.value = false;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center gap-4">
                <VButton variant="ghost" size="sm">← Назад</VButton>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold text-(--t-text)">Заказ {{ order.id }}</h1>
                        <VBadge 
                            :text="statusLabels[order.status]" 
                            :variant="order.status === 'pending' ? 'warning' : order.status === 'processing' ? 'info' : order.status === 'shipped' ? 'b2b' : order.status === 'completed' ? 'success' : 'danger'" 
                            size="sm" 
                            dot 
                        />
                    </div>
                    <p class="text-sm text-(--t-text-3) mt-1">от {{ order.createdAt }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <VButton variant="secondary" size="sm">📄 Печать</VButton>
                <VButton variant="secondary" size="sm">📧 Отправить</VButton>
                <VButton 
                    v-if="order.status !== 'completed' && order.status !== 'cancelled'" 
                    variant="danger" 
                    size="sm"
                    @click="showCancelModal = true"
                >
                    Отменить
                </VButton>
            </div>
        </div>

        <!-- Status Banner -->
        <div 
            class="p-4 rounded-xl"
            :style="{ background: statusColors[order.status]?.bg, borderColor: statusColors[order.status]?.border }"
        >
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">
                        {{ order.status === 'pending' ? '⏳' : order.status === 'processing' ? '⚙️' : order.status === 'shipped' ? '🚚' : order.status === 'completed' ? '✅' : '❌' }}
                    </span>
                    <div>
                        <p class="font-bold" :style="{ color: statusColors[order.status]?.text }">
                            {{ statusLabels[order.status] }}
                        </p>
                        <p class="text-sm text-(--t-text-3)">
                            {{ order.status === 'shipped' ? `Доставка ожидается ${order.delivery.date}` : '' }}
                        </p>
                    </div>
                </div>
                <VButton 
                    v-if="order.status === 'shipped'" 
                    variant="b2b" 
                    size="sm"
                    @click="showTrackingModal = true"
                >
                    📍 Отследить
                </VButton>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Company Info -->
                <VCard title="🏢 Компания">
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">Название</span>
                            <span class="text-sm font-medium text-(--t-text)">{{ order.company.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">ИНН</span>
                            <span class="text-sm font-medium text-(--t-text)">{{ order.company.inn }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">КПП</span>
                            <span class="text-sm font-medium text-(--t-text)">{{ order.company.kpp }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">Адрес</span>
                            <span class="text-sm font-medium text-(--t-text)">{{ order.company.address }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">Контакт</span>
                            <span class="text-sm font-medium text-(--t-text)">{{ order.company.contact }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">Телефон</span>
                            <span class="text-sm font-medium text-(--t-text)">{{ order.company.phone }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">Email</span>
                            <span class="text-sm font-medium text-(--t-text)">{{ order.company.email }}</span>
                        </div>
                    </div>
                </VCard>

                <!-- Order Items -->
                <VCard title="📦 Товары">
                    <div class="space-y-4">
                        <div v-for="item in order.items" :key="item.id" class="flex gap-4 p-4 rounded-xl bg-(--t-card-hover)">
                            <div class="text-4xl">{{ item.image }}</div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-(--t-text)">{{ item.name }}</h3>
                                <p class="text-xs text-(--t-text-3) mt-1">SKU: {{ item.sku }}</p>
                                <VBadge :text="item.tier.toUpperCase()" variant="b2b" size="xs" class="mt-2" />
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-(--t-text-3)">{{ item.quantity }} шт × {{ item.pricePerUnit.toLocaleString() }} ₽</p>
                                <p class="text-lg font-bold text-(--t-text)">{{ item.totalPrice.toLocaleString() }} ₽</p>
                            </div>
                        </div>
                    </div>
                </VCard>

                <!-- Timeline -->
                <VCard title="📅 История заказа">
                    <div class="space-y-4">
                        <div v-for="(event, index) in order.timeline" :key="index" class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <div 
                                    class="w-3 h-3 rounded-full"
                                    :class="event.status === 'completed' ? 'bg-emerald-400' : 'bg-(--t-border)'"
                                />
                                <div v-if="index < order.timeline.length - 1" class="w-0.5 flex-1 mt-1" :class="event.status === 'completed' ? 'bg-emerald-400' : 'bg-(--t-border)'" />
                            </div>
                            <div class="flex-1 pb-4">
                                <p class="text-sm font-medium text-(--t-text)">{{ event.event }}</p>
                                <p class="text-xs text-(--t-text-3)">{{ event.date }}</p>
                            </div>
                        </div>
                    </div>
                </VCard>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Order Summary -->
                <VCard title="💰 Итого">
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-(--t-text-3)">Товары</span>
                            <span class="text-(--t-text)">{{ subtotal.toLocaleString() }} ₽</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-(--t-text-3)">Скидка (5%)</span>
                            <span class="text-emerald-400">-{{ discount.toLocaleString() }} ₽</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-(--t-text-3)">НДС (20%)</span>
                            <span class="text-(--t-text)">{{ vat.toLocaleString() }} ₽</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-(--t-text-3)">Доставка</span>
                            <span class="text-(--t-text)">{{ deliveryCost === 0 ? 'Бесплатно' : deliveryCost.toLocaleString() + ' ₽' }}</span>
                        </div>
                        <div class="border-t border-(--t-border) pt-3">
                            <div class="flex justify-between">
                                <span class="font-bold text-(--t-text)">Итого</span>
                                <span class="text-xl font-bold text-(--t-primary)">{{ total.toLocaleString() }} ₽</span>
                            </div>
                        </div>
                    </div>
                </VCard>

                <!-- Payment Info -->
                <VCard title="💳 Оплата">
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">Способ</span>
                            <VBadge :text="order.payment.method === 'credit' ? 'Кредитная линия' : 'Предоплата'" variant="b2b" size="xs" />
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">Статус</span>
                            <VBadge 
                                :text="order.payment.status === 'paid' ? 'Оплачен' : order.payment.status === 'pending' ? 'Ожидает' : 'Возвращён'" 
                                :variant="order.payment.status === 'paid' ? 'success' : order.payment.status === 'pending' ? 'warning' : 'danger'" 
                                size="xs" 
                            />
                        </div>
                        <div v-if="order.payment.method === 'credit'" class="p-3 rounded-xl bg-(--t-card-hover)">
                            <div class="flex justify-between text-sm">
                                <span class="text-(--t-text-3)">Сумма к оплате</span>
                                <span class="font-bold text-(--t-text)">{{ order.payment.amount.toLocaleString() }} ₽</span>
                            </div>
                            <div class="flex justify-between text-sm mt-2">
                                <span class="text-(--t-text-3)">Срок оплаты</span>
                                <span class="text-(--t-text)">{{ order.payment.dueDate }}</span>
                            </div>
                        </div>
                    </div>
                </VCard>

                <!-- Delivery Info -->
                <VCard title="🚚 Доставка">
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-(--t-text-3)">Способ</span>
                            <span class="text-sm font-medium text-(--t-text)">
                                {{ order.delivery.method === 'pickup' ? 'Самовывоз' : 'Курьерская доставка' }}
                            </span>
                        </div>
                        <div v-if="order.delivery.method !== 'pickup'">
                            <div class="flex justify-between text-sm">
                                <span class="text-(--t-text-3)">Адрес</span>
                                <span class="text-sm font-medium text-(--t-text) text-right">{{ order.delivery.address }}</span>
                            </div>
                            <div class="flex justify-between text-sm mt-2">
                                <span class="text-(--t-text-3)">Дата</span>
                                <span class="text-sm font-medium text-(--t-text)">{{ order.delivery.date }}</span>
                            </div>
                            <div v-if="order.status === 'shipped'" class="mt-3 p-3 rounded-xl bg-(--t-card-hover)">
                                <div class="flex justify-between text-sm">
                                    <span class="text-(--t-text-3)">Трек-номер</span>
                                    <span class="font-mono text-xs text-(--t-primary)">{{ order.delivery.trackingNumber }}</span>
                                </div>
                                <div class="flex justify-between text-sm mt-2">
                                    <span class="text-(--t-text-3)">Перевозчик</span>
                                    <span class="text-sm font-medium text-(--t-text)">{{ order.delivery.carrier }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </VCard>

                <!-- Documents -->
                <VCard title="📄 Документы">
                    <div class="space-y-3">
                        <div v-for="doc in order.documents" :key="doc.id" class="flex items-center justify-between p-3 rounded-xl bg-(--t-card-hover)">
                            <div class="flex items-center gap-3">
                                <span class="text-xl">{{ documentTypeIcons[doc.type] || '📄' }}</span>
                                <div>
                                    <p class="text-sm font-medium text-(--t-text)">{{ doc.name }}</p>
                                    <p class="text-xs text-(--t-text-3)">{{ doc.date }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <VBadge 
                                    :text="doc.status === 'sent' ? 'Отправлен' : doc.status === 'signed' ? 'Подписан' : 'Ожидает'" 
                                    :variant="doc.status === 'sent' ? 'info' : doc.status === 'signed' ? 'success' : 'warning'" 
                                    size="xs" 
                                />
                                <VButton variant="ghost" size="xs">📥</VButton>
                            </div>
                        </div>
                        <VButton variant="secondary" size="sm" full-width @click="showInvoiceModal = true">
                            ➕ Создать документ
                        </VButton>
                    </div>
                </VCard>
            </div>
        </div>

        <!-- Tracking Modal -->
        <VModal v-model="showTrackingModal" title="📍 Отслеживание доставки" size="md">
            <div class="space-y-4">
                <div class="p-4 rounded-xl bg-(--t-card-hover)">
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-(--t-text-3)">Трек-номер</span>
                        <span class="font-mono text-sm text-(--t-primary)">{{ order.delivery.trackingNumber }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-(--t-text-3)">Перевозчик</span>
                        <span class="text-sm font-medium text-(--t-text)">{{ order.delivery.carrier }}</span>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex gap-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-400 mt-1.5" />
                        <div>
                            <p class="text-sm font-medium text-(--t-text)">Отгружен со склада</p>
                            <p class="text-xs text-(--t-text-3)">Москва, склад CatVRF</p>
                            <p class="text-xs text-(--t-text-3)">17.04.2026 14:00</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-400 mt-1.5" />
                        <div>
                            <p class="text-sm font-medium text-(--t-text)">В пути</p>
                            <p class="text-xs text-(--t-text-3)">Транзитный склад</p>
                            <p class="text-xs text-(--t-text-3)">18.04.2026 09:30</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="w-2 h-2 rounded-full bg-(--t-border) mt-1.5" />
                        <div>
                            <p class="text-sm font-medium text-(--t-text-3)">Доставка</p>
                            <p class="text-xs text-(--t-text-3)">{{ order.delivery.address }}</p>
                            <p class="text-xs text-(--t-text-3)">Ожидается: {{ order.delivery.date }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showTrackingModal = false">Закрыть</VButton>
                <VButton variant="b2b">Открыть на сайте перевозчика</VButton>
            </template>
        </VModal>

        <!-- Cancel Modal -->
        <VModal v-model="showCancelModal" title="❌ Отмена заказа" size="md">
            <div class="space-y-4">
                <p class="text-sm text-(--t-text-3)">Вы уверены, что хотите отменить этот заказ? Это действие нельзя отменить.</p>
                <VInput 
                    v-model="cancelReason"
                    label="Причина отмены"
                    placeholder="Укажите причину отмены..."
                    required
                />
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showCancelModal = false">Отмена</VButton>
                <VButton variant="danger" @click="cancelOrder">Подтвердить отмену</VButton>
            </template>
        </VModal>

        <!-- Create Document Modal -->
        <VModal v-model="showInvoiceModal" title="📄 Создать документ" size="md">
            <div class="space-y-4">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-(--t-text)">Тип документа</label>
                    <select class="w-full px-4 py-3 rounded-xl bg-(--t-surface) border border-(--t-border) text-(--t-text)">
                        <option>Счёт-фактура</option>
                        <option>Акт приёмки-передачи</option>
                        <option>УПД</option>
                        <option>Товарная накладная</option>
                    </select>
                </div>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showInvoiceModal = false">Отмена</VButton>
                <VButton variant="b2b" @click="generateInvoice">Создать</VButton>
            </template>
        </VModal>
    </div>
</template>
