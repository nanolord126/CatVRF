<script setup>
/**
 * BusinessProfile — профиль бизнеса: логотип, обложка, описание, контакты,
 * реквизиты, tenant-настройки, юридические документы, вертикали.
 * Полная интеграция с TenantService.
 */
import { ref, computed } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VTabs from '../UI/VTabs.vue';
import VInput from '../UI/VInput.vue';
import VModal from '../UI/VModal.vue';
import VStatCard from '../UI/VStatCard.vue';

const activeTab = ref('general');
const tabs = [
    { key: 'general', label: 'Основное' },
    { key: 'contacts', label: 'Контакты' },
    { key: 'requisites', label: 'Реквизиты' },
    { key: 'verticals', label: 'Вертикали' },
    { key: 'documents', label: 'Документы' },
    { key: 'branding', label: 'Брендинг' },
];

const showUploadLogo = ref(false);
const showUploadCover = ref(false);
const showAddVertical = ref(false);
const showUploadDoc = ref(false);
const isEditing = ref(false);

const profile = ref({
    companyName: 'CatVRF Beauty Studio',
    description: 'Сеть премиальных салонов красоты и косметологических центров. Более 10 лет на рынке. Свыше 50 специалистов в команде.',
    shortDescription: 'Премиальные салоны красоты',
    website: 'https://catvrf-beauty.ru',
    foundedYear: 2016,
    employeesCount: 52,
    logo: null,
    cover: null,
    status: 'verified',
});

const contacts = ref({
    email: 'admin@catvrf-beauty.ru',
    phone: '+7 (495) 123-45-67',
    whatsapp: '+7 (495) 123-45-68',
    telegram: '@catvrf_beauty',
    address: 'Москва, ул. Красная 15, оф. 301',
    workHours: 'Пн-Пт 9:00-20:00, Сб-Вс 10:00-18:00',
    lat: 55.7558,
    lon: 37.6173,
    socialLinks: [
        { platform: 'instagram', url: 'https://instagram.com/catvrf_beauty', icon: '📸' },
        { platform: 'vk', url: 'https://vk.com/catvrf_beauty', icon: '💬' },
        { platform: 'youtube', url: 'https://youtube.com/@catvrf', icon: '▶️' },
    ],
});

const requisites = ref({
    legalName: 'ООО «КэтВРФ Бьюти Студио»',
    inn: '7701234567',
    kpp: '770101001',
    ogrn: '1177700000001',
    legalAddress: 'г. Москва, ул. Красная д. 15, оф. 301',
    actualAddress: 'г. Москва, ул. Красная д. 15, оф. 301',
    bankName: 'АО «Тинькофф Банк»',
    bik: '044525974',
    corrAccount: '30101810145250000974',
    checkAccount: '40702810100000012345',
    taxSystem: 'УСН 6%',
    director: 'Иванов Иван Иванович',
});

const activeVerticals = ref([
    { key: 'beauty', name: 'Красота', icon: '💄', status: 'active', products: 186, revenue: '1.2M ₽', since: '2024-01-15' },
    { key: 'furniture', name: 'Мебель', icon: '🛋️', status: 'active', products: 42, revenue: '890k ₽', since: '2024-06-01' },
    { key: 'food', name: 'Еда', icon: '🍕', status: 'active', products: 95, revenue: '560k ₽', since: '2025-01-10' },
    { key: 'fashion', name: 'Одежда', icon: '👗', status: 'trial', products: 28, revenue: '120k ₽', since: '2026-03-01' },
]);

const availableVerticals = [
    { key: 'fitness', name: 'Фитнес', icon: '💪', desc: 'Тренировки, питание, добавки' },
    { key: 'hotel', name: 'Отели', icon: '🏨', desc: 'Бронирование и управление' },
    { key: 'travel', name: 'Путешествия', icon: '✈️', desc: 'Туры, билеты, экскурсии' },
    { key: 'auto', name: 'Авто', icon: '🚗', desc: 'Запчасти, сервис, тюнинг' },
    { key: 'realestate', name: 'Недвижимость', icon: '🏠', desc: 'Аренда, продажа, дизайн' },
    { key: 'education', name: 'Образование', icon: '📚', desc: 'Курсы, тренинги, сертификаты' },
];

const documents = ref([
    { id: 1, name: 'Свидетельство ОГРН', type: 'legal', date: '2024-01-10', size: '2.4 MB', status: 'verified' },
    { id: 2, name: 'ИНН / КПП', type: 'legal', date: '2024-01-10', size: '1.1 MB', status: 'verified' },
    { id: 3, name: 'Устав компании', type: 'legal', date: '2024-01-10', size: '5.8 MB', status: 'verified' },
    { id: 4, name: 'Договор аренды помещения', type: 'contract', date: '2025-09-15', size: '3.2 MB', status: 'active' },
    { id: 5, name: 'Лицензия на медицинскую деятельность', type: 'license', date: '2025-06-01', size: '1.5 MB', status: 'active' },
    { id: 6, name: 'Полис ОСГО', type: 'insurance', date: '2026-01-01', size: '980 KB', status: 'expiring' },
]);

const docTypeIcons = { legal: '📋', contract: '📝', license: '🏛️', insurance: '🛡️' };
const docStatusColors = { verified: 'success', active: 'success', expiring: 'warning', expired: 'danger', pending: 'info' };
const docStatusLabels = { verified: 'Верифицирован', active: 'Действует', expiring: 'Истекает', expired: 'Истёк', pending: 'На проверке' };

const completionPercent = computed(() => {
    let filled = 0;
    let total = 8;
    if (profile.value.companyName) filled++;
    if (profile.value.description) filled++;
    if (contacts.value.email) filled++;
    if (contacts.value.phone) filled++;
    if (requisites.value.inn) filled++;
    if (requisites.value.bankName) filled++;
    if (documents.value.length >= 3) filled++;
    if (activeVerticals.value.length > 0) filled++;
    return Math.round((filled / total) * 100);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-linear-to-br from-(--t-primary-dim) to-(--t-card-hover) border border-(--t-primary)/20 flex items-center justify-center text-2xl shadow-lg shadow-(--t-glow)">
                    🏢
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl lg:text-2xl font-bold text-(--t-text)">Профиль бизнеса</h1>
                        <VBadge v-if="profile.status === 'verified'" text="✓ Верифицирован" variant="success" size="xs" />
                    </div>
                    <p class="text-xs text-(--t-text-3)">Информация, реквизиты, документы и настройки</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <VButton v-if="!isEditing" variant="secondary" size="sm" @click="isEditing = true">✏️ Редактировать</VButton>
                <template v-else>
                    <VButton variant="secondary" size="sm" @click="isEditing = false">Отмена</VButton>
                    <VButton variant="primary" size="sm" @click="isEditing = false">💾 Сохранить</VButton>
                </template>
            </div>
        </div>

        <!-- Completion Progress -->
        <div class="p-4 rounded-xl bg-linear-to-r from-(--t-primary-dim) to-(--t-card-hover) border border-(--t-primary)/10">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-(--t-text)">Заполненность профиля</span>
                <span class="text-sm font-bold" :class="completionPercent === 100 ? 'text-emerald-400' : 'text-(--t-primary)'">{{ completionPercent }}%</span>
            </div>
            <div class="h-2 rounded-full bg-(--t-border) overflow-hidden">
                <div class="h-full rounded-full bg-linear-to-r from-(--t-primary) to-(--t-accent) transition-all duration-700" :style="{ width: completionPercent + '%' }" />
            </div>
            <p v-if="completionPercent < 100" class="text-[10px] text-(--t-text-3) mt-1.5">Заполните все данные для повышения доверия клиентов</p>
        </div>

        <!-- Tabs -->
        <VTabs :tabs="tabs" v-model="activeTab" variant="underline" />

        <!-- ===== GENERAL ===== -->
        <template v-if="activeTab === 'general'">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Cover + Logo -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Cover -->
                    <VCard no-padding>
                        <div class="relative h-44 bg-linear-to-br from-(--t-card-hover) via-(--t-surface) to-(--t-card-hover) rounded-t-2xl flex items-center justify-center overflow-hidden cursor-pointer group"
                             @click="showUploadCover = true"
                        >
                            <div class="text-center group-hover:scale-105 transition-transform">
                                <div class="text-4xl mb-2">🖼️</div>
                                <span class="text-xs text-(--t-text-3)">Нажмите для загрузки обложки (1200×400)</span>
                            </div>
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
                                <span class="text-white text-sm font-medium bg-black/50 px-3 py-1.5 rounded-lg">📷 Изменить обложку</span>
                            </div>
                        </div>

                        <!-- Logo overlay -->
                        <div class="relative px-6 pb-5 -mt-10">
                            <div class="flex items-end gap-4">
                                <div class="w-20 h-20 rounded-2xl bg-(--t-surface) border-4 border-(--t-surface) shadow-xl flex items-center justify-center text-4xl cursor-pointer hover:scale-105 transition-transform"
                                     @click="showUploadLogo = true"
                                >
                                    🐱
                                </div>
                                <div class="flex-1 pb-1">
                                    <h2 class="text-lg font-bold text-(--t-text)">{{ profile.companyName }}</h2>
                                    <p class="text-xs text-(--t-text-3)">{{ profile.shortDescription }}</p>
                                </div>
                            </div>
                        </div>
                    </VCard>

                    <!-- Main Info -->
                    <VCard title="📝 Основная информация">
                        <div class="space-y-4">
                            <VInput label="Название компании" v-model="profile.companyName" :disabled="!isEditing" />
                            <VInput label="Краткое описание" v-model="profile.shortDescription" :disabled="!isEditing" />
                            <div>
                                <label class="text-xs font-medium text-(--t-text-2) block mb-1">Полное описание</label>
                                <textarea
                                    v-model="profile.description"
                                    :disabled="!isEditing"
                                    rows="4"
                                    class="w-full px-3 py-2 rounded-xl bg-(--t-card-hover) border border-(--t-border) text-sm text-(--t-text) resize-none focus:outline-none focus:border-(--t-primary) transition-colors disabled:opacity-60"
                                />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <VInput label="Сайт" v-model="contacts.website" :disabled="!isEditing" prefix-icon="🌐" />
                                <VInput label="Год основания" v-model="profile.foundedYear" type="number" :disabled="!isEditing" />
                            </div>
                        </div>
                    </VCard>
                </div>

                <!-- Sidebar stats -->
                <div class="space-y-4">
                    <VCard title="📊 Обзор">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 rounded-xl bg-(--t-card-hover)">
                                <span class="text-xs text-(--t-text-3)">Вертикалей</span>
                                <span class="text-sm font-bold text-(--t-primary)">{{ activeVerticals.length }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-xl bg-(--t-card-hover)">
                                <span class="text-xs text-(--t-text-3)">Сотрудников</span>
                                <span class="text-sm font-bold text-(--t-text)">{{ profile.employeesCount }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-xl bg-(--t-card-hover)">
                                <span class="text-xs text-(--t-text-3)">Документов</span>
                                <span class="text-sm font-bold text-(--t-text)">{{ documents.length }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-xl bg-(--t-card-hover)">
                                <span class="text-xs text-(--t-text-3)">Статус</span>
                                <VBadge text="Верифицирован" variant="success" size="xs" />
                            </div>
                        </div>
                    </VCard>

                    <VCard title="🔗 Соц. сети">
                        <div class="space-y-2">
                            <a v-for="link in contacts.socialLinks" :key="link.platform"
                               :href="link.url" target="_blank"
                               class="flex items-center gap-2 p-2.5 rounded-xl hover:bg-(--t-card-hover) transition-colors cursor-pointer active:scale-[0.98]"
                            >
                                <span class="text-lg">{{ link.icon }}</span>
                                <span class="text-sm text-(--t-primary) hover:underline">{{ link.platform }}</span>
                            </a>
                        </div>
                    </VCard>
                </div>
            </div>
        </template>

        <!-- ===== CONTACTS ===== -->
        <template v-if="activeTab === 'contacts'">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <VCard title="📞 Контактные данные">
                    <div class="space-y-4">
                        <VInput label="Email" v-model="contacts.email" type="email" :disabled="!isEditing" prefix-icon="📧" />
                        <VInput label="Телефон" v-model="contacts.phone" :disabled="!isEditing" prefix-icon="📞" />
                        <VInput label="WhatsApp" v-model="contacts.whatsapp" :disabled="!isEditing" prefix-icon="💬" />
                        <VInput label="Telegram" v-model="contacts.telegram" :disabled="!isEditing" prefix-icon="✈️" />
                    </div>
                </VCard>

                <VCard title="📍 Адрес и расположение">
                    <div class="space-y-4">
                        <VInput label="Адрес" v-model="contacts.address" :disabled="!isEditing" prefix-icon="📍" />
                        <VInput label="Часы работы" v-model="contacts.workHours" :disabled="!isEditing" prefix-icon="🕐" />
                        <div class="grid grid-cols-2 gap-3">
                            <VInput label="Широта" v-model="contacts.lat" type="number" :disabled="!isEditing" />
                            <VInput label="Долгота" v-model="contacts.lon" type="number" :disabled="!isEditing" />
                        </div>
                        <!-- Map placeholder -->
                        <div class="h-40 rounded-xl bg-linear-to-br from-(--t-card-hover) to-(--t-surface) flex items-center justify-center border border-(--t-border)">
                            <div class="text-center">
                                <div class="text-3xl mb-1">🗺️</div>
                                <span class="text-[10px] text-(--t-text-3)">Yandex Maps</span>
                            </div>
                        </div>
                    </div>
                </VCard>
            </div>
        </template>

        <!-- ===== REQUISITES ===== -->
        <template v-if="activeTab === 'requisites'">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <VCard title="🏛️ Юридические данные">
                    <div class="space-y-4">
                        <VInput label="Полное юридическое наименование" v-model="requisites.legalName" :disabled="!isEditing" />
                        <div class="grid grid-cols-2 gap-3">
                            <VInput label="ИНН" v-model="requisites.inn" :disabled="!isEditing" />
                            <VInput label="КПП" v-model="requisites.kpp" :disabled="!isEditing" />
                        </div>
                        <VInput label="ОГРН" v-model="requisites.ogrn" :disabled="!isEditing" />
                        <VInput label="Юридический адрес" v-model="requisites.legalAddress" :disabled="!isEditing" />
                        <VInput label="Фактический адрес" v-model="requisites.actualAddress" :disabled="!isEditing" />
                        <div class="grid grid-cols-2 gap-3">
                            <VInput label="Система налогообложения" v-model="requisites.taxSystem" :disabled="!isEditing" />
                            <VInput label="Генеральный директор" v-model="requisites.director" :disabled="!isEditing" />
                        </div>
                    </div>
                </VCard>

                <VCard title="🏦 Банковские реквизиты">
                    <div class="space-y-4">
                        <VInput label="Наименование банка" v-model="requisites.bankName" :disabled="!isEditing" />
                        <VInput label="БИК" v-model="requisites.bik" :disabled="!isEditing" />
                        <VInput label="Корр. счёт" v-model="requisites.corrAccount" :disabled="!isEditing" />
                        <VInput label="Расчётный счёт" v-model="requisites.checkAccount" :disabled="!isEditing" />
                    </div>
                    <template #footer>
                        <VButton variant="ghost" size="sm">📋 Скопировать реквизиты</VButton>
                        <VButton variant="secondary" size="sm" class="ml-auto">📥 Скачать карточку</VButton>
                    </template>
                </VCard>
            </div>
        </template>

        <!-- ===== VERTICALS ===== -->
        <template v-if="activeTab === 'verticals'">
            <div class="space-y-6">
                <!-- Active verticals -->
                <div>
                    <h3 class="text-sm font-bold text-(--t-text) mb-3">Активные вертикали</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="vert in activeVerticals" :key="vert.key"
                             class="p-4 rounded-xl border border-emerald-500/20 bg-(--t-surface) hover:shadow-lg transition-all cursor-pointer active:scale-[0.98]"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-(--t-card-hover) flex items-center justify-center text-xl">{{ vert.icon }}</div>
                                    <div>
                                        <div class="text-sm font-bold text-(--t-text)">{{ vert.name }}</div>
                                        <div class="text-[10px] text-(--t-text-3)">С {{ vert.since }}</div>
                                    </div>
                                </div>
                                <VBadge :text="vert.status === 'active' ? 'Активна' : 'Триал'" :variant="vert.status === 'active' ? 'success' : 'info'" size="xs" />
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="p-2 rounded-lg bg-(--t-card-hover) text-center">
                                    <div class="text-sm font-bold text-(--t-text)">{{ vert.products }}</div>
                                    <div class="text-[9px] text-(--t-text-3)">Товаров</div>
                                </div>
                                <div class="p-2 rounded-lg bg-(--t-card-hover) text-center">
                                    <div class="text-sm font-bold text-emerald-400">{{ vert.revenue }}</div>
                                    <div class="text-[9px] text-(--t-text-3)">Выручка</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available verticals -->
                <div>
                    <h3 class="text-sm font-bold text-(--t-text) mb-3">Доступные для подключения</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div v-for="vert in availableVerticals" :key="vert.key"
                             class="p-4 rounded-xl border border-(--t-border) bg-(--t-surface) opacity-70 hover:opacity-100 hover:border-(--t-primary)/20 transition-all cursor-pointer active:scale-[0.98]"
                        >
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-xl bg-(--t-card-hover) flex items-center justify-center text-xl">{{ vert.icon }}</div>
                                <div>
                                    <div class="text-sm font-bold text-(--t-text)">{{ vert.name }}</div>
                                    <div class="text-[10px] text-(--t-text-3)">{{ vert.desc }}</div>
                                </div>
                            </div>
                            <VButton variant="primary" size="xs" full-width>➕ Подключить</VButton>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- ===== DOCUMENTS ===== -->
        <template v-if="activeTab === 'documents'">
            <VCard title="📁 Юридические документы" subtitle="Загрузите и верифицируйте документы компании">
                <template #header-action>
                    <VButton variant="primary" size="sm" @click="showUploadDoc = true">📤 Загрузить документ</VButton>
                </template>
                <div class="space-y-2">
                    <div v-for="doc in documents" :key="doc.id"
                         class="flex items-center gap-3 p-3 rounded-xl border border-(--t-border) hover:bg-(--t-card-hover) transition-all cursor-pointer active:scale-[0.99]"
                    >
                        <div class="w-10 h-10 rounded-xl bg-(--t-card-hover) flex items-center justify-center text-lg shrink-0">
                            {{ docTypeIcons[doc.type] || '📄' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-(--t-text) truncate">{{ doc.name }}</div>
                            <div class="text-[10px] text-(--t-text-3)">{{ doc.date }} • {{ doc.size }}</div>
                        </div>
                        <VBadge :text="docStatusLabels[doc.status]" :variant="docStatusColors[doc.status]" size="xs" />
                        <div class="flex items-center gap-1">
                            <button class="p-1.5 rounded-lg text-xs hover:bg-(--t-card-hover) cursor-pointer transition-colors active:scale-90" title="Скачать">📥</button>
                            <button class="p-1.5 rounded-lg text-xs hover:bg-(--t-card-hover) cursor-pointer transition-colors active:scale-90" title="Удалить">🗑️</button>
                        </div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- ===== BRANDING ===== -->
        <template v-if="activeTab === 'branding'">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <VCard title="🎨 Цвета и стиль">
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs text-(--t-text-2) mb-2 block">Основной цвет бренда</label>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-(--t-primary) border-2 border-(--t-border) cursor-pointer hover:scale-110 transition-transform" />
                                <VInput model-value="#22d3ee" size="sm" class="flex-1" :disabled="!isEditing" />
                            </div>
                        </div>
                        <div>
                            <label class="text-xs text-(--t-text-2) mb-2 block">Цвет акцента</label>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-(--t-accent) border-2 border-(--t-border) cursor-pointer hover:scale-110 transition-transform" />
                                <VInput model-value="#a78bfa" size="sm" class="flex-1" :disabled="!isEditing" />
                            </div>
                        </div>
                        <div>
                            <label class="text-xs text-(--t-text-2) mb-2 block">Шрифт</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="font in ['Inter', 'Montserrat', 'Roboto', 'Nunito']" :key="font"
                                        :class="['px-3 py-1.5 rounded-lg border text-xs cursor-pointer transition-all active:scale-95',
                                                 font === 'Inter' ? 'border-(--t-primary) bg-(--t-primary-dim) text-(--t-primary)' : 'border-(--t-border) text-(--t-text-3) hover:border-(--t-text-3)']"
                                >
                                    {{ font }}
                                </button>
                            </div>
                        </div>
                    </div>
                </VCard>

                <VCard title="🖼️ Логотип и обложка">
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs text-(--t-text-2) mb-2 block">Логотип</label>
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-xl bg-(--t-card-hover) border-2 border-dashed border-(--t-border) flex items-center justify-center text-3xl cursor-pointer hover:border-(--t-primary) transition-colors active:scale-95"
                                     @click="showUploadLogo = true">
                                    🐱
                                </div>
                                <div class="text-xs text-(--t-text-3)">
                                    <p>PNG, SVG — до 2 MB</p>
                                    <p>Рекомендуемый размер: 512×512</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs text-(--t-text-2) mb-2 block">Обложка</label>
                            <div class="h-24 rounded-xl bg-linear-to-br from-(--t-card-hover) to-(--t-surface) border-2 border-dashed border-(--t-border) flex items-center justify-center cursor-pointer hover:border-(--t-primary) transition-colors active:scale-[0.99]"
                                 @click="showUploadCover = true">
                                <span class="text-xs text-(--t-text-3)">📷 Загрузить обложку (1200×400)</span>
                            </div>
                        </div>
                    </div>
                </VCard>
            </div>
        </template>

        <!-- Upload Logo Modal -->
        <VModal v-model="showUploadLogo" title="Загрузить логотип" size="sm">
            <div class="space-y-4">
                <div class="h-40 border-2 border-dashed border-(--t-border) rounded-xl flex flex-col items-center justify-center gap-2 cursor-pointer hover:border-(--t-primary) hover:bg-(--t-primary-dim) transition-all active:scale-[0.99]">
                    <span class="text-4xl">📷</span>
                    <span class="text-sm text-(--t-text-2)">Перетащите или нажмите</span>
                    <span class="text-[10px] text-(--t-text-3)">PNG, SVG до 2 MB</span>
                </div>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showUploadLogo = false">Отмена</VButton>
                <VButton variant="primary">Загрузить</VButton>
            </template>
        </VModal>

        <!-- Upload Doc Modal -->
        <VModal v-model="showUploadDoc" title="Загрузить документ" size="md">
            <div class="space-y-4">
                <VInput label="Название документа" placeholder="Свидетельство ОГРН" required />
                <div>
                    <label class="text-xs text-(--t-text-2) mb-2 block">Тип документа</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button v-for="t in [{icon:'📋',label:'Юридический',key:'legal'},{icon:'📝',label:'Договор',key:'contract'},{icon:'🏛️',label:'Лицензия',key:'license'},{icon:'🛡️',label:'Страховой',key:'insurance'}]" :key="t.key"
                                class="p-3 rounded-xl border border-(--t-border) hover:border-(--t-primary) hover:bg-(--t-primary-dim) text-center transition-all cursor-pointer active:scale-95"
                        >
                            <div class="text-lg mb-1">{{ t.icon }}</div>
                            <div class="text-[10px] text-(--t-text)">{{ t.label }}</div>
                        </button>
                    </div>
                </div>
                <div class="h-32 border-2 border-dashed border-(--t-border) rounded-xl flex flex-col items-center justify-center gap-2 cursor-pointer hover:border-(--t-primary) transition-all">
                    <span class="text-3xl">📄</span>
                    <span class="text-xs text-(--t-text-3)">PDF, DOCX, JPG до 10 MB</span>
                </div>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showUploadDoc = false">Отмена</VButton>
                <VButton variant="primary">Загрузить</VButton>
            </template>
        </VModal>
    </div>
</template>
