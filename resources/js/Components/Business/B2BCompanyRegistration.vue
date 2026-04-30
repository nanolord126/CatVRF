<script setup>
/**
 * B2BCompanyRegistration — Multi-step company registration for B2B partners
 * with validation, document upload, and compliance checks.
 */
import { ref, computed } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VInput from '../UI/VInput.vue';

const currentStep = ref(1);
const totalSteps = 4;

const formData = ref({
    // Step 1: Company Type
    companyType: 'ooo',
    
    // Step 2: Company Details
    companyName: '',
    inn: '',
    kpp: '',
    ogrn: '',
    legalAddress: '',
    actualAddress: '',
    registrationDate: '',
    
    // Step 3: Contacts
    contactPerson: '',
    contactPosition: '',
    contactPhone: '',
    contactEmail: '',
    
    // Step 4: Banking
    bankName: '',
    bic: '',
    accountNumber: '',
    corrAccount: '',
    
    // Agreement
    agreedToTerms: false,
    agreedToProcessing: false,
});

const companyTypes = [
    { id: 'ooo', name: 'ООО', description: 'Общество с ограниченной ответственностью', icon: '🏢' },
    { id: 'ip', name: 'ИП', description: 'Индивидуальный предприниматель', icon: '👤' },
    { id: 'ao', name: 'АО', description: 'Акционерное общество', icon: '📊' },
];

const uploadedDocuments = ref([
    { id: 1, name: 'Устав организации', type: 'charter', status: 'uploaded' },
    { id: 2, name: 'Выписка ЕГРЮЛ', type: 'egrul', status: 'uploaded' },
]);

const canProceed = computed(() => {
    switch (currentStep.value) {
        case 1:
            return formData.value.companyType;
        case 2:
            return formData.value.companyName && formData.value.inn && formData.value.legalAddress;
        case 3:
            return formData.value.contactPerson && formData.value.contactPhone && formData.value.contactEmail;
        case 4:
            return formData.value.bankName && formData.value.bic && formData.value.accountNumber;
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

function submitRegistration() {
    // Submit logic here
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-(--t-text)">🏢 Регистрация компании</h1>
            <p class="text-sm text-(--t-text-3) mt-1">Станьте B2B-партнёром CatVRF</p>
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

        <div class="max-w-3xl mx-auto">
            <!-- Step 1: Company Type -->
            <VCard v-if="currentStep === 1" title="Тип организации">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label 
                        v-for="type in companyTypes" 
                        :key="type.id"
                        class="p-6 rounded-xl border cursor-pointer transition-all text-center"
                        :class="formData.companyType === type.id 
                            ? 'border-(--t-primary) bg-(--t-primary-dim)' 
                            : 'border-(--t-border) hover:border-(--t-primary)/30'"
                    >
                        <input type="radio" v-model="formData.companyType" :value="type.id" class="sr-only" />
                        <div class="text-4xl mb-3">{{ type.icon }}</div>
                        <p class="font-semibold text-(--t-text)">{{ type.name }}</p>
                        <p class="text-xs text-(--t-text-3) mt-2">{{ type.description }}</p>
                    </label>
                </div>
            </VCard>

            <!-- Step 2: Company Details -->
            <VCard v-if="currentStep === 2" title="Реквизиты организации">
                <div class="space-y-4">
                    <VInput 
                        v-model="formData.companyName"
                        label="Полное название"
                        placeholder="Общество с ограниченной ответственностью «...»"
                        required
                    />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <VInput 
                            v-model="formData.inn"
                            label="ИНН"
                            placeholder="7712345678"
                            required
                        />
                        <VInput 
                            v-if="formData.companyType !== 'ip'"
                            v-model="formData.kpp"
                            label="КПП"
                            placeholder="771201001"
                        />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <VInput 
                            v-if="formData.companyType !== 'ip'"
                            v-model="formData.ogrn"
                            label="ОГРН"
                            placeholder="1027700132195"
                        />
                        <VInput 
                            v-model="formData.registrationDate"
                            label="Дата регистрации"
                            type="date"
                        />
                    </div>
                    <VInput 
                        v-model="formData.legalAddress"
                        label="Юридический адрес"
                        placeholder="Город, улица, дом, офис"
                        required
                    />
                    <VInput 
                        v-model="formData.actualAddress"
                        label="Фактический адрес"
                        placeholder="Город, улица, дом, офис"
                    />
                </div>
            </VCard>

            <!-- Step 3: Contacts -->
            <VCard v-if="currentStep === 3" title="Контактная информация">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <VInput 
                            v-model="formData.contactPerson"
                            label="ФИО контактного лица"
                            placeholder="Иванов Иван Иванович"
                            required
                        />
                        <VInput 
                            v-model="formData.contactPosition"
                            label="Должность"
                            placeholder="Генеральный директор"
                        />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <VInput 
                            v-model="formData.contactPhone"
                            label="Телефон"
                            placeholder="+7 (999) 123-45-67"
                            required
                        />
                        <VInput 
                            v-model="formData.contactEmail"
                            label="Email"
                            type="email"
                            placeholder="email@company.ru"
                            required
                        />
                    </div>
                </div>
            </VCard>

            <!-- Step 4: Banking -->
            <VCard v-if="currentStep === 4" title="Банковские реквизиты">
                <div class="space-y-4">
                    <VInput 
                        v-model="formData.bankName"
                        label="Название банка"
                        placeholder="ПАО Сбербанк"
                        required
                    />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <VInput 
                            v-model="formData.bic"
                            label="БИК"
                            placeholder="044525225"
                            required
                        />
                        <VInput 
                            v-model="formData.corrAccount"
                            label="Корр. счёт"
                            placeholder="30101810400000000225"
                        />
                    </div>
                    <VInput 
                        v-model="formData.accountNumber"
                        label="Расчётный счёт"
                        placeholder="40702810..."
                        required
                    />
                </div>
            </VCard>

            <!-- Documents Upload (shown on all steps) -->
            <VCard title="📄 Необходимые документы" class="mt-6">
                <div class="space-y-3">
                    <div class="p-4 rounded-xl border border-(--t-border)">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-xl">📋</span>
                                <div>
                                    <p class="font-medium text-(--t-text)">Устав организации</p>
                                    <p class="text-xs text-(--t-text-3)">Обязательный документ</p>
                                </div>
                            </div>
                            <VBadge text="Загружен" variant="success" size="xs" />
                        </div>
                    </div>
                    <div class="p-4 rounded-xl border border-(--t-border)">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-xl">📄</span>
                                <div>
                                    <p class="font-medium text-(--t-text)">Выписка ЕГРЮЛ</p>
                                    <p class="text-xs text-(--t-text-3)">Не старше 30 дней</p>
                                </div>
                            </div>
                            <VBadge text="Загружен" variant="success" size="xs" />
                        </div>
                    </div>
                    <div class="p-4 rounded-xl border border-dashed border-(--t-border) cursor-pointer hover:border-(--t-primary)/50 transition-colors">
                        <div class="flex items-center justify-center gap-3 text-(--t-text-3)">
                            <span class="text-xl">➕</span>
                            <span class="text-sm">Добавить документ</span>
                        </div>
                    </div>
                </div>
            </VCard>

            <!-- Agreement (final step) -->
            <VCard v-if="currentStep === totalSteps" title="📜 Согласие" class="mt-6">
                <div class="space-y-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" v-model="formData.agreedToTerms" class="mt-1 rounded border-(--t-border) text-(--t-primary)" />
                        <span class="text-sm text-(--t-text)">
                            Я согласен с <a href="#" class="text-(--t-primary) hover:underline">условиями сотрудничества</a> и 
                            <a href="#" class="text-(--t-primary) hover:underline">политикой конфиденциальности</a>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" v-model="formData.agreedToProcessing" class="mt-1 rounded border-(--t-border) text-(--t-primary)" />
                        <span class="text-sm text-(--t-text)">
                            Я согласен на обработку персональных данных в соответствии с 
                            <a href="#" class="text-(--t-primary) hover:underline">ФЗ-152</a>
                        </span>
                    </label>
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
                    @click="submitRegistration"
                    :disabled="!canProceed || !formData.agreedToTerms || !formData.agreedToProcessing"
                >
                    ✓ Отправить заявку
                </VButton>
            </div>
        </div>

        <!-- Benefits -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
            <VCard flat>
                <div class="text-center p-4">
                    <div class="text-3xl mb-2">💳</div>
                    <h3 class="font-semibold text-(--t-text)">Кредитная линия</h3>
                    <p class="text-xs text-(--t-text-3) mt-1">Отсрочка оплаты до 30 дней</p>
                </div>
            </VCard>
            <VCard flat>
                <div class="text-center p-4">
                    <div class="text-3xl mb-2">📦</div>
                    <h3 class="font-semibold text-(--t-text)">Специальные цены</h3>
                    <p class="text-xs text-(--t-text-3) mt-1">Оптовые скидки до 30%</p>
                </div>
            </VCard>
            <VCard flat>
                <div class="text-center p-4">
                    <div class="text-3xl mb-2">🔑</div>
                    <h3 class="font-semibold text-(--t-text)">API-доступ</h3>
                    <p class="text-xs text-(--t-text-3) mt-1">Интеграция с вашими системами</p>
                </div>
            </VCard>
        </div>
    </div>
</template>
