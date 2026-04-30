<script setup>
/**
 * B2BCheckout — Multi-step checkout for wholesale orders with
 * delivery options, payment terms, and invoice generation.
 */
import { ref, computed } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VInput from '../UI/VInput.vue';

const currentStep = ref(1);
const totalSteps = 4;

const formData = ref({
    // Step 1: Company
    companyId: '1',
    companyBranch: '',
    contactPerson: '',
    contactPhone: '',
    contactEmail: '',
    
    // Step 2: Delivery
    deliveryMethod: 'pickup',
    deliveryAddress: '',
    deliveryDate: '',
    deliveryComment: '',
    
    // Step 3: Payment
    paymentMethod: 'credit',
    invoiceRequired: true,
    invoiceDetails: {
        inn: '',
        kpp: '',
        bankName: '',
        bic: '',
        accountNumber: '',
    },
    
    // Step 4: Review
    agreedToTerms: false,
    agreedToProcessing: false,
});

const companies = [
    { id: '1', name: 'ООО «Альфа-Центр»', inn: '7712345678', address: 'Москва, ул. Ленина 15' },
    { id: '2', name: 'ИП Петров А.В.', inn: '771234567890', address: 'СПб, Невский 42' },
];

const deliveryMethods = [
    { id: 'pickup', name: 'Самовывоз', icon: '📦', price: 0, days: '1-2' },
    { id: 'courier', name: 'Курьерская доставка', icon: '🚚', price: 5000, days: '3-5' },
    { id: 'logistics', name: 'Логистическая компания', icon: '🚛', price: 12000, days: '5-7' },
];

const paymentMethods = [
    { id: 'credit', name: 'Кредитная линия', icon: '💳', description: 'Отсрочка 14 дней', recommended: true },
    { id: 'prepaid', name: 'Предоплата', icon: '💰', description: '100% до отгрузки' },
    { id: 'invoice', name: 'Безналичный расчёт', icon: '🏦', description: 'Счёт на оплату' },
];

const orderSummary = computed(() => ({
    subtotal: 800000,
    discount: 40000,
    vat: 152000,
    delivery: formData.value.deliveryMethod === 'pickup' ? 0 : 
               formData.value.deliveryMethod === 'courier' ? 5000 : 12000,
    total: 0,
}));

orderSummary.value.total = orderSummary.value.subtotal - orderSummary.value.discount + orderSummary.value.vat + orderSummary.value.delivery;

const canProceed = computed(() => {
    switch (currentStep.value) {
        case 1:
            return formData.value.companyBranch && formData.value.contactPerson && formData.value.contactPhone;
        case 2:
            return formData.value.deliveryMethod && 
                   (formData.value.deliveryMethod !== 'pickup' ? formData.value.deliveryAddress : true) &&
                   formData.value.deliveryDate;
        case 3:
            return formData.value.paymentMethod;
        case 4:
            return formData.value.agreedToTerms && formData.value.agreedToProcessing;
        default:
            return false;
    }
});

function nextStep() {
    if (canProceed.value && currentStep.value < totalSteps) {
        currentStep.value++;
    }
}

function prevStep() {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
}

function submitOrder() {
    // Submit logic here
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-(--t-text)">📋 Оформление заказа</h1>
            <p class="text-sm text-(--t-text-3) mt-1">Шаг {{ currentStep }} из {{ totalSteps }}</p>
        </div>

        <!-- Progress Steps -->
        <div class="flex items-center justify-between">
            <div v-for="step in totalSteps" :key="step" class="flex-1 flex items-center">
                <div 
                    class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all"
                    :class="currentStep >= step 
                        ? 'bg-(--t-primary) text-white shadow-lg shadow-(--t-glow)' 
                        : 'bg-(--t-surface) text-(--t-text-3) border border-(--t-border)'"
                >
                    {{ currentStep > step ? '✓' : step }}
                </div>
                <div v-if="step < totalSteps" class="flex-1 h-1 mx-2 rounded-full transition-all"
                     :class="currentStep > step ? 'bg-(--t-primary)' : 'bg-(--t-border)'" />
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Step 1: Company Information -->
                <VCard v-if="currentStep === 1" title="🏢 Информация о компании">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-(--t-text) mb-2">Юридическое лицо</label>
                            <select 
                                v-model="formData.companyId"
                                class="w-full px-4 py-3 rounded-xl bg-(--t-surface) border border-(--t-border) text-(--t-text) focus:outline-none focus:border-(--t-primary)"
                            >
                                <option v-for="company in companies" :key="company.id" :value="company.id">
                                    {{ company.name }} (ИНН: {{ company.innn }})
                                </option>
                            </select>
                        </div>
                        <VInput 
                            v-model="formData.companyBranch"
                            label="Филиал / Подразделение"
                            placeholder="Например: Главный офис"
                            required
                        />
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <VInput 
                                v-model="formData.contactPerson"
                                label="Контактное лицо"
                                placeholder="Иванов Иван Иванович"
                                required
                            />
                            <VInput 
                                v-model="formData.contactPhone"
                                label="Телефон"
                                placeholder="+7 (999) 123-45-67"
                                required
                            />
                        </div>
                        <VInput 
                            v-model="formData.contactEmail"
                            label="Email"
                            type="email"
                            placeholder="email@company.ru"
                        />
                    </div>
                </VCard>

                <!-- Step 2: Delivery -->
                <VCard v-if="currentStep === 2" title="🚚 Доставка">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-(--t-text) mb-3">Способ доставки</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <label 
                                    v-for="method in deliveryMethods" 
                                    :key="method.id"
                                    class="p-4 rounded-xl border cursor-pointer transition-all"
                                    :class="formData.deliveryMethod === method.id 
                                        ? 'border-(--t-primary) bg-(--t-primary-dim)' 
                                        : 'border-(--t-border) hover:border-(--t-primary)/30'"
                                >
                                    <input type="radio" v-model="formData.deliveryMethod" :value="method.id" class="sr-only" />
                                    <div class="text-center">
                                        <div class="text-3xl mb-2">{{ method.icon }}</div>
                                        <p class="font-medium text-(--t-text)">{{ method.name }}</p>
                                        <p class="text-xs text-(--t-text-3) mt-1">{{ method.days }} дн.</p>
                                        <p class="text-sm font-bold text-(--t-primary) mt-2">
                                            {{ method.price === 0 ? 'Бесплатно' : method.price.toLocaleString() + ' ₽' }}
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div v-if="formData.deliveryMethod !== 'pickup'">
                            <VInput 
                                v-model="formData.deliveryAddress"
                                label="Адрес доставки"
                                placeholder="Город, улица, дом, офис"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <VInput 
                                v-model="formData.deliveryDate"
                                label="Желаемая дата доставки"
                                type="date"
                                required
                            />
                            <VInput 
                                v-model="formData.deliveryComment"
                                label="Комментарий к доставке"
                                placeholder="Код домофона, ориентиры..."
                            />
                        </div>
                    </div>
                </VCard>

                <!-- Step 3: Payment -->
                <VCard v-if="currentStep === 3" title="💳 Оплата">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-(--t-text) mb-3">Способ оплаты</label>
                            <div class="space-y-3">
                                <label 
                                    v-for="method in paymentMethods" 
                                    :key="method.id"
                                    class="flex items-center gap-4 p-4 rounded-xl border cursor-pointer transition-all"
                                    :class="formData.paymentMethod === method.id 
                                        ? 'border-(--t-primary) bg-(--t-primary-dim)' 
                                        : 'border-(--t-border) hover:border-(--t-primary)/30'"
                                >
                                    <input type="radio" v-model="formData.paymentMethod" :value="method.id" class="text-(--t-primary)" />
                                    <span class="text-2xl">{{ method.icon }}</span>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <p class="font-medium text-(--t-text)">{{ method.name }}</p>
                                            <VBadge v-if="method.recommended" text="Рекомендуется" variant="b2b" size="xs" />
                                        </div>
                                        <p class="text-xs text-(--t-text-3) mt-1">{{ method.description }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <label class="flex items-center gap-3 p-4 rounded-xl border border-(--t-border) cursor-pointer hover:border-(--t-primary)/30 transition-colors">
                            <input type="checkbox" v-model="formData.invoiceRequired" class="rounded border-(--t-border) text-(--t-primary)" />
                            <div>
                                <p class="font-medium text-(--t-text)">Требуется счёт-фактура</p>
                                <p class="text-xs text-(--t-text-3)">Для бухгалтерии и налоговой отчётности</p>
                            </div>
                        </label>

                        <div v-if="formData.invoiceRequired" class="p-4 rounded-xl bg-(--t-card-hover) space-y-3">
                            <h4 class="font-medium text-(--t-text)">Реквизиты для счёта</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <VInput v-model="formData.invoiceDetails.inn" label="ИНН" placeholder="7712345678" />
                                <VInput v-model="formData.invoiceDetails.kpp" label="КПП" placeholder="771201001" />
                                <VInput v-model="formData.invoiceDetails.bankName" label="Банк" placeholder="ПАО Сбербанк" />
                                <VInput v-model="formData.invoiceDetails.bic" label="БИК" placeholder="044525225" />
                                <VInput v-model="formData.invoiceDetails.accountNumber" label="Расчётный счёт" placeholder="40702810..." class="md:col-span-2" />
                            </div>
                        </div>
                    </div>
                </VCard>

                <!-- Step 4: Review -->
                <VCard v-if="currentStep === 4" title="✅ Проверка заказа">
                    <div class="space-y-4">
                        <div class="p-4 rounded-xl bg-(--t-card-hover)">
                            <h4 class="font-medium text-(--t-text) mb-3">🏢 Компания</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-(--t-text-3)">Юридическое лицо</span>
                                    <span class="text-(--t-text)">{{ companies.find(c => c.id === formData.companyId)?.name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-(--t-text-3)">Филиал</span>
                                    <span class="text-(--t-text)">{{ formData.companyBranch }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-(--t-text-3)">Контакт</span>
                                    <span class="text-(--t-text)">{{ formData.contactPerson }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl bg-(--t-card-hover)">
                            <h4 class="font-medium text-(--t-text) mb-3">🚚 Доставка</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-(--t-text-3)">Способ</span>
                                    <span class="text-(--t-text)">{{ deliveryMethods.find(m => m.id === formData.deliveryMethod)?.name }}</span>
                                </div>
                                <div v-if="formData.deliveryMethod !== 'pickup'" class="flex justify-between">
                                    <span class="text-(--t-text-3)">Адрес</span>
                                    <span class="text-(--t-text)">{{ formData.deliveryAddress }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-(--t-text-3)">Дата</span>
                                    <span class="text-(--t-text)">{{ formData.deliveryDate }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl bg-(--t-card-hover)">
                            <h4 class="font-medium text-(--t-text) mb-3">💳 Оплата</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-(--t-text-3)">Способ</span>
                                    <span class="text-(--t-text)">{{ paymentMethods.find(m => m.id === formData.paymentMethod)?.name }}</span>
                                </div>
                                <div v-if="formData.invoiceRequired" class="flex justify-between">
                                    <span class="text-(--t-text-3)">Счёт-фактура</span>
                                    <VBadge text="Требуется" variant="info" size="xs" />
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" v-model="formData.agreedToTerms" class="mt-1 rounded border-(--t-border) text-(--t-primary)" />
                                <span class="text-sm text-(--t-text)">
                                    Я согласен с <a href="#" class="text-(--t-primary) hover:underline">условиями оферты</a> и 
                                    <a href="#" class="text-(--t-primary) hover:underline">политикой возврата</a>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" v-model="formData.agreedToProcessing" class="mt-1 rounded border-(--t-border) text-(--t-primary)" />
                                <span class="text-sm text-(--t-text)">
                                    Я согласен на обработку персональных данных в соответствии с 
                                    <a href="#" class="text-(--t-primary) hover:underline">политикой конфиденциальности</a>
                                </span>
                            </label>
                        </div>
                    </div>
                </VCard>

                <!-- Navigation -->
                <div class="flex justify-between mt-6">
                    <VButton 
                        variant="secondary" 
                        @click="prevStep"
                        :disabled="currentStep === 1"
                    >
                        ← Назад
                    </VButton>
                    <VButton 
                        v-if="currentStep < totalSteps"
                        variant="b2b"
                        @click="nextStep"
                        :disabled="!canProceed"
                    >
                        Далее →
                    </VButton>
                    <VButton 
                        v-else
                        variant="b2b"
                        @click="submitOrder"
                        :disabled="!canProceed"
                    >
                        ✓ Подтвердить заказ
                    </VButton>
                </div>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="lg:col-span-1">
                <VCard title="📦 Сводка заказа" subtitle="3 товара">
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-(--t-text-3)">Товары</span>
                            <span class="text-(--t-text)">{{ orderSummary.subtotal.toLocaleString() }} ₽</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-(--t-text-3)">Скидка (5%)</span>
                            <span class="text-emerald-400">-{{ orderSummary.discount.toLocaleString() }} ₽</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-(--t-text-3)">НДС (20%)</span>
                            <span class="text-(--t-text)">{{ orderSummary.vat.toLocaleString() }} ₽</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-(--t-text-3)">Доставка</span>
                            <span class="text-(--t-text)">{{ orderSummary.delivery === 0 ? 'Бесплатно' : orderSummary.delivery.toLocaleString() + ' ₽' }}</span>
                        </div>
                        <div class="border-t border-(--t-border) pt-3">
                            <div class="flex justify-between">
                                <span class="font-bold text-(--t-text)">Итого</span>
                                <span class="text-xl font-bold text-(--t-primary)">{{ orderSummary.total.toLocaleString() }} ₽</span>
                            </div>
                        </div>
                    </div>
                </VCard>

                <!-- Items Preview -->
                <VCard title="Товары в заказе" class="mt-4">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">💻</span>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-(--t-text)">Ноутбук ProBook 15</p>
                                <p class="text-xs text-(--t-text-3)">15 шт × 29 000 ₽</p>
                            </div>
                            <p class="font-bold text-(--t-text)">435 000 ₽</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🫒</span>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-(--t-text)">Оливковое масло</p>
                                <p class="text-xs text-(--t-text-3)">25 шт × 5 000 ₽</p>
                            </div>
                            <p class="font-bold text-(--t-text)">125 000 ₽</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🧤</span>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-(--t-text)">Медицинские перчатки</p>
                                <p class="text-xs text-(--t-text-3)">150 шт × 1 600 ₽</p>
                            </div>
                            <p class="font-bold text-(--t-text)">240 000 ₽</p>
                        </div>
                    </div>
                </VCard>
            </div>
        </div>
    </div>
</template>
