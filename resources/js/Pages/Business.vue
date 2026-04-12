<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import BusinessDashboard from '@/Components/Business/BusinessDashboard.vue';
import OrdersManagement from '@/Components/Business/OrdersManagement.vue';
import ProductsCatalog from '@/Components/Business/ProductsCatalog.vue';
import WalletFinance from '@/Components/Business/WalletFinance.vue';
import AnalyticsPanel from '@/Components/Business/AnalyticsPanel.vue';
import EmployeeManagement from '@/Components/Business/EmployeeManagement.vue';
import WarehouseInventory from '@/Components/Business/WarehouseInventory.vue';
import DeliveryTracking from '@/Components/Business/DeliveryTracking.vue';
import MarketingPanel from '@/Components/Business/MarketingPanel.vue';
import AIConstructors from '@/Components/Business/AIConstructors.vue';
import B2BPanel from '@/Components/Business/B2BPanel.vue';
import SettingsPage from '@/Components/Business/SettingsPage.vue';
import BusinessProfile from '@/Components/Business/BusinessProfile.vue';
import ClientsCRM from '@/Components/Business/ClientsCRM.vue';
import IntegrationsPanel from '@/Components/Business/IntegrationsPanel.vue';
import BeautyPanel from '@/Components/Business/Beauty/BeautyPanel.vue';
import TaxiPanel from '@/Components/Business/Taxi/TaxiPanel.vue';
import LogisticsPanel from '@/Components/Business/Logistics/LogisticsPanel.vue';
import PaymentPanel from '@/Components/Business/Payment/PaymentPanel.vue';
import WalletPanel from '@/Components/Business/Wallet/WalletPanel.vue';
import InventoryPanel from '@/Components/Business/Inventory/InventoryPanel.vue';
import CRMPanel from '@/Components/Business/CRM/CRMPanel.vue';
import CommunicationPanel from '@/Components/Business/Communication/CommunicationPanel.vue';
import DeliveryPanel from '@/Components/Business/Delivery/DeliveryPanel.vue';
import FraudMLPanel from '@/Components/Business/FraudML/FraudMLPanel.vue';
import AutoPanel from '@/Components/Business/Auto/AutoPanel.vue';
import FoodPanel from '@/Components/Business/Food/FoodPanel.vue';
import FurniturePanel from '@/Components/Business/Furniture/FurniturePanel.vue';
import RealEstatePanel from '@/Components/Business/RealEstate/RealEstatePanel.vue';
import MedicalPanel from '@/Components/Business/Medical/MedicalPanel.vue';
import TravelPanel from '@/Components/Business/Travel/TravelPanel.vue';
import HotelsPanel from '@/Components/Business/Hotels/HotelsPanel.vue';
import FashionPanel from '@/Components/Business/Fashion/FashionPanel.vue';
import FitnessPanel from '@/Components/Business/Fitness/FitnessPanel.vue';
import AdvertisingPanel from '@/Components/Business/Advertising/AdvertisingPanel.vue';
import AIPanel from '@/Components/Business/AI/AIPanel.vue';
import AnalyticsVerticalPanel from '@/Components/Business/Analytics/AnalyticsPanel.vue';
import ArtPanel from '@/Components/Business/Art/ArtPanel.vue';
import BooksAndLiteraturePanel from '@/Components/Business/BooksAndLiterature/BooksAndLiteraturePanel.vue';
import CarRentalPanel from '@/Components/Business/CarRental/CarRentalPanel.vue';
import CleaningServicesPanel from '@/Components/Business/CleaningServices/CleaningServicesPanel.vue';
import CollectiblesPanel from '@/Components/Business/Collectibles/CollectiblesPanel.vue';
import CommonPanel from '@/Components/Business/Common/CommonPanel.vue';
import ConfectioneryPanel from '@/Components/Business/Confectionery/ConfectioneryPanel.vue';
import ConstructionAndRepairPanel from '@/Components/Business/ConstructionAndRepair/ConstructionAndRepairPanel.vue';
import ConsultingPanel from '@/Components/Business/Consulting/ConsultingPanel.vue';
import ContentPanel from '@/Components/Business/Content/ContentPanel.vue';
import EducationPanel from '@/Components/Business/Education/EducationPanel.vue';
import ElectronicsPanel from '@/Components/Business/Electronics/ElectronicsPanel.vue';
import EventPlanningPanel from '@/Components/Business/EventPlanning/EventPlanningPanel.vue';
import FarmDirectPanel from '@/Components/Business/FarmDirect/FarmDirectPanel.vue';
import FlowersPanel from '@/Components/Business/Flowers/FlowersPanel.vue';
import FreelancePanel from '@/Components/Business/Freelance/FreelancePanel.vue';
import GardeningPanel from '@/Components/Business/Gardening/GardeningPanel.vue';
import GeoPanel from '@/Components/Business/Geo/GeoPanel.vue';
import GeoLogisticsPanel from '@/Components/Business/GeoLogistics/GeoLogisticsPanel.vue';
import GroceryAndDeliveryPanel from '@/Components/Business/GroceryAndDelivery/GroceryAndDeliveryPanel.vue';
import HomeServicesPanel from '@/Components/Business/HomeServices/HomeServicesPanel.vue';

const props = defineProps({
    section: { type: String, default: '' },
});

const activeSection = ref(props.section || 'dashboard');
const dynamicPanelModules = import.meta.glob('../Components/Business/*/*Panel.vue', { eager: true });

const sectionComponents = {
    dashboard: BusinessDashboard,
    orders: OrdersManagement,
    products: ProductsCatalog,
    wallet: WalletFinance,
    analytics: AnalyticsPanel,
    employees: EmployeeManagement,
    warehouses: WarehouseInventory,
    delivery: DeliveryTracking,
    marketing: MarketingPanel,
    ai: AIConstructors,
    b2b: B2BPanel,
    settings: SettingsPage,
    profile: BusinessProfile,
    crm: ClientsCRM,
    integrations: IntegrationsPanel,
    beauty: BeautyPanel,
    logistics: LogisticsPanel,
    payment: PaymentPanel,
    walletVertical: WalletPanel,
    inventory: InventoryPanel,
    crmVertical: CRMPanel,
    communication: CommunicationPanel,
    deliveryVertical: DeliveryPanel,
    fraudml: FraudMLPanel,
    taxi: TaxiPanel,
    auto: AutoPanel,
    food: FoodPanel,
    furniture: FurniturePanel,
    realestate: RealEstatePanel,
    medical: MedicalPanel,
    travel: TravelPanel,
    hotels: HotelsPanel,
    fashion: FashionPanel,
    fitness: FitnessPanel,
    advertising: AdvertisingPanel,
    aiVertical: AIPanel,
    analyticsVertical: AnalyticsVerticalPanel,
    art: ArtPanel,
    booksandliterature: BooksAndLiteraturePanel,
    carrental: CarRentalPanel,
    cleaningservices: CleaningServicesPanel,
    collectibles: CollectiblesPanel,
    commonVertical: CommonPanel,
    confectionery: ConfectioneryPanel,
    constructionandrepair: ConstructionAndRepairPanel,
    consulting: ConsultingPanel,
    contentVertical: ContentPanel,
    education: EducationPanel,
    electronics: ElectronicsPanel,
    eventplanning: EventPlanningPanel,
    farmdirect: FarmDirectPanel,
    flowers: FlowersPanel,
    freelance: FreelancePanel,
    gardening: GardeningPanel,
    geo: GeoPanel,
    geologistics: GeoLogisticsPanel,
    groceryanddelivery: GroceryAndDeliveryPanel,
    homeservices: HomeServicesPanel,
};

function resolveDynamicPanelBySection(section) {
    const key = String(section || '').toLowerCase();
    if (!key) {
        return null;
    }

    const found = Object.entries(dynamicPanelModules).find(([path]) =>
        path.toLowerCase().includes(`/business/${key}/`) && path.toLowerCase().endsWith('panel.vue')
    );

    return found?.[1]?.default ?? null;
}

const currentComponent = computed(() => {
    return sectionComponents[activeSection.value] || resolveDynamicPanelBySection(activeSection.value) || BusinessDashboard;
});

const pageTitles = {
    dashboard: 'Дашборд',
    orders: 'Заказы',
    products: 'Товары и услуги',
    wallet: 'Кошелёк',
    analytics: 'Аналитика',
    employees: 'Персонал',
    warehouses: 'Склады',
    delivery: 'Доставка',
    marketing: 'Маркетинг',
    ai: 'AI-конструкторы',
    b2b: 'B2B-панель',
    settings: 'Настройки',
    profile: 'Профиль бизнеса',
    crm: 'CRM и клиенты',
    integrations: 'Интеграции',
    beauty: 'Beauty B2B',
    logistics: 'Logistics',
    payment: 'Payment',
    walletVertical: 'Wallet (Vertical)',
    inventory: 'Inventory',
    crmVertical: 'CRM (Vertical)',
    communication: 'Communication',
    deliveryVertical: 'Delivery (Vertical)',
    fraudml: 'FraudML',
    taxi: 'Taxi',
    auto: 'Auto',
    food: 'Food',
    furniture: 'Furniture',
    realestate: 'RealEstate',
    medical: 'Medical',
    travel: 'Travel',
    hotels: 'Hotels',
    fashion: 'Fashion',
    fitness: 'Fitness',
    advertising: 'Advertising',
    aiVertical: 'AI (Vertical)',
    analyticsVertical: 'Analytics (Vertical)',
    art: 'Art',
    booksandliterature: 'BooksAndLiterature',
    carrental: 'CarRental',
    cleaningservices: 'CleaningServices',
    collectibles: 'Collectibles',
    commonVertical: 'Common (Vertical)',
    confectionery: 'Confectionery',
    constructionandrepair: 'ConstructionAndRepair',
    consulting: 'Consulting',
    contentVertical: 'Content (Vertical)',
    education: 'Education',
    electronics: 'Electronics',
    eventplanning: 'EventPlanning',
    farmdirect: 'FarmDirect',
    flowers: 'Flowers',
    freelance: 'Freelance',
    gardening: 'Gardening',
    geo: 'Geo',
    geologistics: 'GeoLogistics',
    groceryanddelivery: 'GroceryAndDelivery',
    homeservices: 'HomeServices',
};

const pageTitle = computed(() => {
    if (pageTitles[activeSection.value]) {
        return pageTitles[activeSection.value];
    }

    const dynamicTitle = String(activeSection.value || 'dashboard');
    return dynamicTitle ? dynamicTitle.charAt(0).toUpperCase() + dynamicTitle.slice(1) : 'Дашборд';
});

function handleNavigate(section) {
    activeSection.value = section;
    window.history.replaceState({}, '', `/business${section !== 'dashboard' ? `?section=${section}` : ''}`);
}

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const sectionParam = params.get('section');
    if (sectionParam && (sectionComponents[sectionParam] || resolveDynamicPanelBySection(sectionParam))) {
        activeSection.value = sectionParam;
    }
});
</script>

<template>
    <Head :title="`${pageTitle} — Бизнес-кабинет CatVRF`" />

    <BusinessLayout
        :activeSection="activeSection"
        @navigate="handleNavigate">
        <Transition
            enter-from-class="opacity-0 translate-y-2"
            enter-active-class="transition-all duration-300"
            enter-to-class="opacity-100 translate-y-0"
            leave-from-class="opacity-100"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
            mode="out-in">
            <component :is="currentComponent" :key="activeSection" />
        </Transition>
    </BusinessLayout>
</template>
