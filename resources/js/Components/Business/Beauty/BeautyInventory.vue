<script setup>
/**
 * BeautyInventory — модуль инвентаризации для вертикали Beauty.
 * 7 табов: склад, бренды/палитры, привязка к услугам, расход мастеров,
 * история клиентов (ML-данные), списания, закупки.
 * Ключевая связка: Материал → Услуга → Мастер → Клиент → ML-рекомендации.
 */
import { ref, computed, reactive, watch } from 'vue';
import VButton from '../../UI/VButton.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import VModal from '../../UI/VModal.vue';
import VInput from '../../UI/VInput.vue';
import VStatCard from '../../UI/VStatCard.vue';

const props = defineProps({
    masters:  { type: Array, default: () => [] },
    salons:   { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    clients:  { type: Array, default: () => [] },
});

const emit = defineEmits([
    'writeoff', 'restock', 'export-report',
    'open-master', 'open-client', 'low-stock-alert',
]);

/* ─── Helpers ─── */
function fmtMoney(n) { return new Intl.NumberFormat('ru-RU').format(n) + ' ₽'; }
function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }

/* ═══════════════ TABS ═══════════════ */
const tabs = [
    { key: 'stock',           icon: '📦', label: 'Склад' },
    { key: 'brands',          icon: '🏷️', label: 'Бренды и палитры' },
    { key: 'service-mapping', icon: '🔗', label: 'Привязка к услугам' },
    { key: 'master-prefs',    icon: '👩‍🎨', label: 'Расход мастеров' },
    { key: 'client-history',  icon: '🤖', label: 'ML-данные клиентов' },
    { key: 'writeoffs',       icon: '📝', label: 'Списания' },
    { key: 'orders',          icon: '🛒', label: 'Закупки' },
];
const activeTab = ref('stock');

/* ═══════════════ TOAST ═══════════════ */
const showToast = ref(false);
const toastMessage = ref('');
function toast(msg) {
    toastMessage.value = msg;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3000);
}

/* ═══════════════════════════════════════════════════════════════ */
/*  CORE DATA — BRANDS, PALETTES, SHADES                         */
/* ═══════════════════════════════════════════════════════════════ */
const brands = ref([
    { id: 1,  name: 'L\'Oréal Professionnel', country: 'Франция',  logo: '🇫🇷', category: 'hair_color',  tier: 'premium' },
    { id: 2,  name: 'Wella Professionals',     country: 'Германия', logo: '🇩🇪', category: 'hair_color',  tier: 'premium' },
    { id: 3,  name: 'Schwarzkopf IGORA',       country: 'Германия', logo: '🇩🇪', category: 'hair_color',  tier: 'premium' },
    { id: 4,  name: 'Olaplex',                 country: 'США',      logo: '🇺🇸', category: 'hair_care',   tier: 'luxury' },
    { id: 5,  name: 'Davines',                 country: 'Италия',   logo: '🇮🇹', category: 'hair_care',   tier: 'luxury' },
    { id: 6,  name: 'OPI',                     country: 'США',      logo: '🇺🇸', category: 'nail_polish', tier: 'premium' },
    { id: 7,  name: 'CND Shellac',             country: 'США',      logo: '🇺🇸', category: 'nail_gel',    tier: 'premium' },
    { id: 8,  name: 'Luxio',                   country: 'Канада',   logo: '🇨🇦', category: 'nail_gel',    tier: 'luxury' },
    { id: 9,  name: 'ESTEL Professional',      country: 'Россия',   logo: '🇷🇺', category: 'hair_color',  tier: 'standard' },
    { id: 10, name: 'TNL Professional',        country: 'Россия',   logo: '🇷🇺', category: 'nail_gel',    tier: 'standard' },
    { id: 11, name: 'MAC Cosmetics',           country: 'Канада',   logo: '🇨🇦', category: 'makeup',      tier: 'premium' },
    { id: 12, name: 'NYX Professional',        country: 'США',      logo: '🇺🇸', category: 'makeup',      tier: 'standard' },
]);

const categoryLabels = {
    hair_color: '🎨 Краска для волос', hair_care: '🧴 Уход для волос',
    nail_polish: '💅 Лак для ногтей', nail_gel: '✨ Гель-лак',
    makeup: '💄 Косметика', skincare: '🧖 Уход для кожи',
    tools: '🔧 Инструменты', consumables: '🧻 Расходники',
};
const tierLabels = { luxury: '💎 Люкс', premium: '⭐ Премиум', standard: '📦 Стандарт' };
const tierColors = { luxury: 'purple', premium: 'blue', standard: 'gray' };

/* ═══════════════════════════════════════════════════════════════ */
/*  PALETTES (цветовые палитры — ключ для ML)                     */
/* ═══════════════════════════════════════════════════════════════ */
const palettes = ref([
    { id: 1,  brandId: 1,  brandName: 'L\'Oréal Professionnel', line: 'Majirel',        category: 'hair_color', shades: [
        { code: '6.0',  name: 'Тёмный блондин',          hex: '#7B5B3A', popularity: 89 },
        { code: '7.1',  name: 'Блондин пепельный',       hex: '#A89078', popularity: 76 },
        { code: '8.3',  name: 'Светлый блондин золотист.',hex: '#C4A265', popularity: 65 },
        { code: '5.15', name: 'Светлый шатен пеп.-красн.',hex: '#6B4C3B', popularity: 52 },
        { code: '9.21', name: 'Очень светл. блонд перл.', hex: '#D4C5A9', popularity: 44 },
    ]},
    { id: 2,  brandId: 2,  brandName: 'Wella Professionals', line: 'Koleston Perfect', category: 'hair_color', shades: [
        { code: '66/0', name: 'Тёмный блондин интенсивн.', hex: '#7A5C42', popularity: 72 },
        { code: '8/1',  name: 'Светлый блондин пепельн.',  hex: '#B8A08C', popularity: 68 },
        { code: '9/3',  name: 'Очень светл. блонд золот.', hex: '#D0B97B', popularity: 55 },
        { code: '7/44', name: 'Блондин красный интенсивн.',hex: '#A05030', popularity: 41 },
    ]},
    { id: 3,  brandId: 6,  brandName: 'OPI', line: 'Infinite Shine', category: 'nail_polish', shades: [
        { code: 'ISL-F16', name: 'Tickle My France-y',    hex: '#E8C8C0', popularity: 94 },
        { code: 'ISL-H22', name: 'Funny Bunny',           hex: '#F5E8E0', popularity: 91 },
        { code: 'ISL-N52', name: 'Humidi-Tea',            hex: '#C07060', popularity: 78 },
        { code: 'ISL-W56', name: 'Engaged to Be Gingered',hex: '#A04030', popularity: 62 },
        { code: 'ISL-T80', name: 'Rice Rice Baby',        hex: '#F0E5D5', popularity: 58 },
    ]},
    { id: 4,  brandId: 7,  brandName: 'CND Shellac', line: 'Original', category: 'nail_gel', shades: [
        { code: 'CND-001', name: 'Cream Puff',            hex: '#FAEAE0', popularity: 96 },
        { code: 'CND-040', name: 'Romantique',            hex: '#F0C8C0', popularity: 88 },
        { code: 'CND-092', name: 'Tundra',                hex: '#8A7068', popularity: 71 },
        { code: 'CND-188', name: 'Chandelier',            hex: '#D4B080', popularity: 63 },
    ]},
    { id: 5,  brandId: 8,  brandName: 'Luxio', line: 'Colour Collection', category: 'nail_gel', shades: [
        { code: 'LX-WHIM',   name: 'Whimsy',             hex: '#E8D0D0', popularity: 85 },
        { code: 'LX-AMORE',  name: 'Amore',              hex: '#C04040', popularity: 74 },
        { code: 'LX-SOUL',   name: 'Soul',               hex: '#706060', popularity: 60 },
        { code: 'LX-GRACE',  name: 'Grace',              hex: '#E0D0C0', popularity: 55 },
    ]},
    { id: 6,  brandId: 3,  brandName: 'Schwarzkopf IGORA', line: 'Royal', category: 'hair_color', shades: [
        { code: '6-0',  name: 'Тёмный русый',            hex: '#7C5E45', popularity: 81 },
        { code: '8-11', name: 'Светлый русый сандре экст.',hex: '#A89888', popularity: 69 },
        { code: '9-1',  name: 'Блондин сандре',          hex: '#C0B098', popularity: 57 },
    ]},
]);

/* ═══════════════════════════════════════════════════════════════ */
/*  1. STOCK — WAREHOUSE ITEMS                                    */
/* ═══════════════════════════════════════════════════════════════ */
const stockFilter = reactive({ search: '', category: '', brand: '', lowOnly: false });

const stockItems = ref([
    { id: 1,  name: 'Majirel 6.0 Тёмный блондин',      brandId: 1, brandName: 'L\'Oréal Prof.', category: 'hair_color',  unit: 'тюбик 50мл', qty: 24, minQty: 5,  costPrice: 650,  salePrice: 0, salon: 'BeautyLab Центр',  shadeCode: '6.0',  lastRestock: '01.04.2026' },
    { id: 2,  name: 'Majirel 7.1 Блондин пепельный',    brandId: 1, brandName: 'L\'Oréal Prof.', category: 'hair_color',  unit: 'тюбик 50мл', qty: 18, minQty: 5,  costPrice: 650,  salePrice: 0, salon: 'BeautyLab Центр',  shadeCode: '7.1',  lastRestock: '01.04.2026' },
    { id: 3,  name: 'Majirel 8.3 Светл. блонд золотист.',brandId: 1, brandName: 'L\'Oréal Prof.', category: 'hair_color',  unit: 'тюбик 50мл', qty: 3,  minQty: 5,  costPrice: 650,  salePrice: 0, salon: 'BeautyLab Центр',  shadeCode: '8.3',  lastRestock: '15.03.2026' },
    { id: 4,  name: 'Koleston 8/1 Светл. блонд пепел.', brandId: 2, brandName: 'Wella Prof.',    category: 'hair_color',  unit: 'тюбик 60мл', qty: 12, minQty: 4,  costPrice: 780,  salePrice: 0, salon: 'BeautyLab Центр',  shadeCode: '8/1',  lastRestock: '25.03.2026' },
    { id: 5,  name: 'Оксид L\'Oréal 6%',                brandId: 1, brandName: 'L\'Oréal Prof.', category: 'hair_color',  unit: 'литр',       qty: 8,  minQty: 3,  costPrice: 420,  salePrice: 0, salon: 'BeautyLab Центр',  shadeCode: null,   lastRestock: '01.04.2026' },
    { id: 6,  name: 'Olaplex No.1 Bond Multiplier',     brandId: 4, brandName: 'Olaplex',        category: 'hair_care',   unit: 'флакон 100мл', qty: 6, minQty: 2, costPrice: 3200, salePrice: 0, salon: 'BeautyLab Центр',  shadeCode: null,   lastRestock: '20.03.2026' },
    { id: 7,  name: 'Olaplex No.2 Bond Perfector',      brandId: 4, brandName: 'Olaplex',        category: 'hair_care',   unit: 'флакон 100мл', qty: 5, minQty: 2, costPrice: 2800, salePrice: 0, salon: 'BeautyLab Центр',  shadeCode: null,   lastRestock: '20.03.2026' },
    { id: 8,  name: 'OPI ISL-F16 Tickle My France-y',   brandId: 6, brandName: 'OPI',            category: 'nail_polish', unit: 'флакон 15мл',  qty: 2, minQty: 3, costPrice: 890,  salePrice: 0, salon: 'BeautyLab Центр',  shadeCode: 'ISL-F16', lastRestock: '10.03.2026' },
    { id: 9,  name: 'CND Shellac Cream Puff',           brandId: 7, brandName: 'CND Shellac',    category: 'nail_gel',    unit: 'флакон 7.3мл', qty: 14, minQty: 3, costPrice: 1250, salePrice: 0, salon: 'BeautyLab Центр',  shadeCode: 'CND-001', lastRestock: '01.04.2026' },
    { id: 10, name: 'CND Shellac Romantique',           brandId: 7, brandName: 'CND Shellac',    category: 'nail_gel',    unit: 'флакон 7.3мл', qty: 9,  minQty: 3, costPrice: 1250, salePrice: 0, salon: 'BeautyLab Центр',  shadeCode: 'CND-040', lastRestock: '01.04.2026' },
    { id: 11, name: 'Luxio Whimsy',                     brandId: 8, brandName: 'Luxio',           category: 'nail_gel',    unit: 'флакон 15мл',  qty: 7,  minQty: 2, costPrice: 1680, salePrice: 0, salon: 'BeautyLab Центр',  shadeCode: 'LX-WHIM', lastRestock: '28.03.2026' },
    { id: 12, name: 'Luxio Amore',                      brandId: 8, brandName: 'Luxio',           category: 'nail_gel',    unit: 'флакон 15мл',  qty: 1,  minQty: 2, costPrice: 1680, salePrice: 0, salon: 'BeautyLab Центр',  shadeCode: 'LX-AMORE', lastRestock: '10.03.2026' },
    { id: 13, name: 'Безлатексные перчатки S',           brandId: 0, brandName: '—',              category: 'consumables', unit: 'пачка 100шт',  qty: 15, minQty: 5, costPrice: 380,  salePrice: 0, salon: 'BeautyLab Центр',  shadeCode: null,   lastRestock: '01.04.2026' },
    { id: 14, name: 'Безлатексные перчатки M',           brandId: 0, brandName: '—',              category: 'consumables', unit: 'пачка 100шт',  qty: 22, minQty: 5, costPrice: 380,  salePrice: 0, salon: 'BeautyLab Центр',  shadeCode: null,   lastRestock: '01.04.2026' },
    { id: 15, name: 'Фольга для мелирования',           brandId: 0, brandName: '—',              category: 'consumables', unit: 'рулон 100м',   qty: 4,  minQty: 2, costPrice: 320,  salePrice: 0, salon: 'BeautyLab Центр',  shadeCode: null,   lastRestock: '25.03.2026' },
    { id: 16, name: 'IGORA Royal 6-0 Тёмный русый',     brandId: 3, brandName: 'Schwarzkopf',    category: 'hair_color',  unit: 'тюбик 60мл',  qty: 10, minQty: 4, costPrice: 720,  salePrice: 0, salon: 'BeautyLab Юг',     shadeCode: '6-0',  lastRestock: '01.04.2026' },
    { id: 17, name: 'TNL Гель-лак #142 Пыльная роза',   brandId: 10, brandName: 'TNL Prof.',     category: 'nail_gel',    unit: 'флакон 10мл',  qty: 11, minQty: 3, costPrice: 290,  salePrice: 0, salon: 'BeautyLab Юг',     shadeCode: 'TNL-142', lastRestock: '01.04.2026' },
    { id: 18, name: 'ESTEL De Luxe 7/1 Русый пепельный',brandId: 9, brandName: 'ESTEL Prof.',    category: 'hair_color',  unit: 'тюбик 60мл',  qty: 16, minQty: 5, costPrice: 320,  salePrice: 0, salon: 'BeautyLab Юг',     shadeCode: '7/1',  lastRestock: '01.04.2026' },
]);

const filteredStock = computed(() => {
    let list = [...stockItems.value];
    const q = stockFilter.search.toLowerCase();
    if (q) list = list.filter(s => s.name.toLowerCase().includes(q) || s.brandName.toLowerCase().includes(q) || (s.shadeCode && s.shadeCode.toLowerCase().includes(q)));
    if (stockFilter.category) list = list.filter(s => s.category === stockFilter.category);
    if (stockFilter.brand) list = list.filter(s => s.brandName === stockFilter.brand);
    if (stockFilter.lowOnly) list = list.filter(s => s.qty <= s.minQty);
    return list;
});

const stockSummary = computed(() => ({
    totalItems: stockItems.value.length,
    totalQty: stockItems.value.reduce((a, c) => a + c.qty, 0),
    totalCost: stockItems.value.reduce((a, c) => a + c.qty * c.costPrice, 0),
    lowStock: stockItems.value.filter(s => s.qty <= s.minQty).length,
    outOfStock: stockItems.value.filter(s => s.qty === 0).length,
    categories: [...new Set(stockItems.value.map(s => s.category))].length,
    brandCount: [...new Set(stockItems.value.filter(s => s.brandId > 0).map(s => s.brandId))].length,
}));

/* ═══════════════════════════════════════════════════════════════ */
/*  2. SERVICE-MATERIAL MAPPING (привязка к услугам)              */
/* ═══════════════════════════════════════════════════════════════ */
const serviceMaterialMap = ref([
    { serviceId: 1, serviceName: '✂️ Стрижка женская',       materials: [
        { itemName: 'Безлатексные перчатки',  qty: 1, unit: 'пара',  costPer: 7.6 },
    ]},
    { serviceId: 2, serviceName: '🎨 Окрашивание AirTouch',  materials: [
        { itemName: 'Краска (основной тон)',  qty: 2, unit: 'тюбик', costPer: 650,  brandNote: 'По выбору мастера' },
        { itemName: 'Оксид 6%',              qty: 0.3, unit: 'литр', costPer: 126 },
        { itemName: 'Фольга для мелирования', qty: 0.15, unit: 'рулон', costPer: 48 },
        { itemName: 'Olaplex No.1',           qty: 0.1, unit: 'флакон', costPer: 320 },
        { itemName: 'Безлатексные перчатки',  qty: 2, unit: 'пара',  costPer: 15.2 },
    ]},
    { serviceId: 3, serviceName: '🎨 Окрашивание балаяж',    materials: [
        { itemName: 'Краска (2 тона)',        qty: 2, unit: 'тюбик', costPer: 650, brandNote: 'По выбору мастера' },
        { itemName: 'Оксид 6%',              qty: 0.2, unit: 'литр', costPer: 84 },
        { itemName: 'Фольга для мелирования', qty: 0.1, unit: 'рулон', costPer: 32 },
        { itemName: 'Безлатексные перчатки',  qty: 2, unit: 'пара',  costPer: 15.2 },
    ]},
    { serviceId: 4, serviceName: '💇 Укладка',               materials: [
        { itemName: 'Стайлинг-средство',     qty: 0.05, unit: 'флакон', costPer: 45 },
    ]},
    { serviceId: 5, serviceName: '✨ Тонирование',           materials: [
        { itemName: 'Краска (тонирующая)',    qty: 1, unit: 'тюбик', costPer: 650, brandNote: 'По выбору мастера' },
        { itemName: 'Оксид 1.5%',            qty: 0.1, unit: 'литр', costPer: 42 },
        { itemName: 'Безлатексные перчатки',  qty: 1, unit: 'пара',  costPer: 7.6 },
    ]},
    { serviceId: 6, serviceName: '💎 Уход Olaplex',          materials: [
        { itemName: 'Olaplex No.1',           qty: 0.15, unit: 'флакон', costPer: 480 },
        { itemName: 'Olaplex No.2',           qty: 0.15, unit: 'флакон', costPer: 420 },
    ]},
    { serviceId: 7, serviceName: '💅 Маникюр + покрытие',    materials: [
        { itemName: 'Гель-лак (цвет)',       qty: 0.1, unit: 'флакон', costPer: 125, brandNote: 'Цвет выбирает клиент' },
        { itemName: 'Гель-лак (база)',       qty: 0.05, unit: 'флакон', costPer: 62 },
        { itemName: 'Гель-лак (топ)',        qty: 0.05, unit: 'флакон', costPer: 62 },
        { itemName: 'Безлатексные перчатки',  qty: 1, unit: 'пара',  costPer: 7.6 },
        { itemName: 'Пилка одноразовая',     qty: 1, unit: 'шт',    costPer: 15 },
        { itemName: 'Салфетки безворсовые',  qty: 3, unit: 'шт',    costPer: 4.5 },
    ]},
    { serviceId: 8, serviceName: '🧴 Кератиновое выпрямление', materials: [
        { itemName: 'Кератиновый состав',    qty: 0.2, unit: 'литр', costPer: 1200 },
        { itemName: 'Безлатексные перчатки',  qty: 2, unit: 'пара',  costPer: 15.2 },
    ]},
]);

function serviceTotalCost(svc) {
    return svc.materials.reduce((a, m) => a + m.costPer, 0);
}

/* ═══════════════════════════════════════════════════════════════ */
/*  3. MASTER BRAND PREFERENCES (расход + предпочтения мастеров)  */
/* ═══════════════════════════════════════════════════════════════ */
const masterPreferences = ref([
    { masterId: 1, masterName: 'Анна Соколова', position: 'Стилист-колорист', preferredBrands: [
        { brandId: 1, brandName: 'L\'Oréal Professionnel', category: 'hair_color', usageCount: 186, rating: 5, note: 'Основной бренд для окрашивания' },
        { brandId: 4, brandName: 'Olaplex', category: 'hair_care', usageCount: 97, rating: 5, note: 'Всегда добавляет при окрашивании' },
        { brandId: 2, brandName: 'Wella Professionals', category: 'hair_color', usageCount: 42, rating: 4, note: 'Для специфических оттенков' },
    ], topShades: [
        { code: '6.0',  name: 'Тёмный блондин',    brand: 'L\'Oréal', count: 48, hex: '#7B5B3A' },
        { code: '7.1',  name: 'Блондин пепельный',  brand: 'L\'Oréal', count: 38, hex: '#A89078' },
        { code: '8/1',  name: 'Светл. блонд пепел.',brand: 'Wella',    count: 22, hex: '#B8A08C' },
        { code: '9.21', name: 'Оч. светл. блонд',   brand: 'L\'Oréal', count: 19, hex: '#D4C5A9' },
    ], monthlyConsumption: { hair_color: 32, hair_care: 14, consumables: 65 }, materialCost: 21450 },

    { masterId: 2, masterName: 'Ольга Демидова', position: 'Мастер маникюра', preferredBrands: [
        { brandId: 7, brandName: 'CND Shellac', category: 'nail_gel', usageCount: 245, rating: 5, note: 'Основной гель-лак' },
        { brandId: 6, brandName: 'OPI', category: 'nail_polish', usageCount: 89, rating: 4, note: 'Для классического покрытия' },
        { brandId: 8, brandName: 'Luxio', category: 'nail_gel', usageCount: 67, rating: 5, note: 'Премиальные клиенты' },
    ], topShades: [
        { code: 'CND-001', name: 'Cream Puff',        brand: 'CND',  count: 67, hex: '#FAEAE0' },
        { code: 'CND-040', name: 'Romantique',        brand: 'CND',  count: 52, hex: '#F0C8C0' },
        { code: 'ISL-F16', name: 'Tickle My France-y',brand: 'OPI',  count: 38, hex: '#E8C8C0' },
        { code: 'LX-WHIM', name: 'Whimsy',            brand: 'Luxio',count: 31, hex: '#E8D0D0' },
        { code: 'ISL-H22', name: 'Funny Bunny',       brand: 'OPI',  count: 28, hex: '#F5E8E0' },
    ], monthlyConsumption: { nail_gel: 42, nail_polish: 15, consumables: 90 }, materialCost: 15800 },

    { masterId: 3, masterName: 'Светлана Романова', position: 'Мастер-косметолог', preferredBrands: [
        { brandId: 11, brandName: 'MAC Cosmetics', category: 'makeup', usageCount: 112, rating: 5, note: 'Тональные и румяна' },
        { brandId: 12, brandName: 'NYX Professional', category: 'makeup', usageCount: 56, rating: 4, note: 'Для повседневных образов' },
    ], topShades: [], monthlyConsumption: { makeup: 18, skincare: 12, consumables: 40 }, materialCost: 12600 },

    { masterId: 9, masterName: 'Татьяна Новикова', position: 'Косметолог', preferredBrands: [
        { brandId: 3, brandName: 'Schwarzkopf IGORA', category: 'hair_color', usageCount: 154, rating: 5, note: 'Основной бренд' },
        { brandId: 9, brandName: 'ESTEL Professional', category: 'hair_color', usageCount: 67, rating: 4, note: 'Бюджетные окрашивания' },
        { brandId: 4, brandName: 'Olaplex', category: 'hair_care', usageCount: 85, rating: 5, note: 'Всегда использует' },
    ], topShades: [
        { code: '6-0', name: 'Тёмный русый',    brand: 'IGORA', count: 42, hex: '#7C5E45' },
        { code: '8-11', name: 'Светл. русый сандре',brand: 'IGORA', count: 31, hex: '#A89888' },
        { code: '7/1', name: 'Русый пепельный',  brand: 'ESTEL', count: 28, hex: '#8C7868' },
    ], monthlyConsumption: { hair_color: 38, hair_care: 16, consumables: 70 }, materialCost: 24200 },

    { masterId: 13, masterName: 'Наталья Семёнова', position: 'Лешмейкер', preferredBrands: [
        { brandId: 8, brandName: 'Luxio', category: 'nail_gel', usageCount: 198, rating: 5, note: 'Основной' },
        { brandId: 10, brandName: 'TNL Professional', category: 'nail_gel', usageCount: 45, rating: 3, note: 'Только для учеников' },
    ], topShades: [
        { code: 'LX-WHIM', name: 'Whimsy',  brand: 'Luxio', count: 56, hex: '#E8D0D0' },
        { code: 'LX-GRACE', name: 'Grace',  brand: 'Luxio', count: 41, hex: '#E0D0C0' },
        { code: 'LX-AMORE', name: 'Amore',  brand: 'Luxio', count: 32, hex: '#C04040' },
    ], monthlyConsumption: { nail_gel: 35, consumables: 55 }, materialCost: 18400 },
]);

/* ═══════════════════════════════════════════════════════════════ */
/*  4. CLIENT MATERIAL HISTORY (ML-данные)                        */
/*  Каждая запись = конкретный визит клиента с использованными    */
/*  материалами, оттенками, брендами → для ML-рекомендаций.       */
/* ═══════════════════════════════════════════════════════════════ */
const clientMaterialHistory = ref([
    { clientId: 1, clientName: 'Мария Козлова',     visits: [
        { date: '02.04.2026', masterId: 1, masterName: 'Анна Соколова', service: 'Окрашивание AirTouch', shades: [{ code: '7.1', brand: 'L\'Oréal', name: 'Блондин пепельный', hex: '#A89078' }], extras: ['Olaplex No.1'], satisfaction: 5, photo: true },
        { date: '05.03.2026', masterId: 1, masterName: 'Анна Соколова', service: 'Тонирование',         shades: [{ code: '7.1', brand: 'L\'Oréal', name: 'Блондин пепельный', hex: '#A89078' }], extras: [], satisfaction: 5, photo: true },
        { date: '10.02.2026', masterId: 1, masterName: 'Анна Соколова', service: 'Окрашивание AirTouch', shades: [{ code: '8.3', brand: 'L\'Oréal', name: 'Светл. блонд золот.', hex: '#C4A265' }], extras: ['Olaplex No.1'], satisfaction: 4, photo: true },
        { date: '15.01.2026', masterId: 1, masterName: 'Анна Соколова', service: 'Стрижка + укладка',   shades: [], extras: [], satisfaction: 5, photo: false },
    ], mlProfile: { preferredShadeFamily: 'blonde_ash', colorTone: 'cool', colorDepth: 'light', repeatRate: 0.85, loyalToMaster: true, avgSatisfaction: 4.75 } },

    { clientId: 2, clientName: 'Елена Петрова',     visits: [
        { date: '05.04.2026', masterId: 2, masterName: 'Ольга Демидова', service: 'Маникюр + покрытие', shades: [{ code: 'CND-001', brand: 'CND', name: 'Cream Puff', hex: '#FAEAE0' }], extras: [], satisfaction: 5, photo: true },
        { date: '22.03.2026', masterId: 2, masterName: 'Ольга Демидова', service: 'Маникюр + покрытие', shades: [{ code: 'CND-040', brand: 'CND', name: 'Romantique', hex: '#F0C8C0' }], extras: [], satisfaction: 5, photo: true },
        { date: '08.03.2026', masterId: 2, masterName: 'Ольга Демидова', service: 'Маникюр + покрытие', shades: [{ code: 'ISL-F16', brand: 'OPI', name: 'Tickle My France-y', hex: '#E8C8C0' }], extras: [], satisfaction: 4, photo: true },
        { date: '20.02.2026', masterId: 2, masterName: 'Ольга Демидова', service: 'Маникюр + покрытие', shades: [{ code: 'CND-001', brand: 'CND', name: 'Cream Puff', hex: '#FAEAE0' }], extras: [], satisfaction: 5, photo: false },
        { date: '05.02.2026', masterId: 2, masterName: 'Ольга Демидова', service: 'Маникюр + покрытие', shades: [{ code: 'LX-WHIM', brand: 'Luxio', name: 'Whimsy', hex: '#E8D0D0' }], extras: [], satisfaction: 5, photo: true },
    ], mlProfile: { preferredShadeFamily: 'nude_pink', colorTone: 'warm', colorDepth: 'light', repeatRate: 0.92, loyalToMaster: true, avgSatisfaction: 4.8 } },

    { clientId: 3, clientName: 'Анастасия Иванова', visits: [
        { date: '03.04.2026', masterId: 1, masterName: 'Анна Соколова', service: 'Окрашивание балаяж',   shades: [{ code: '6.0', brand: 'L\'Oréal', name: 'Тёмный блондин', hex: '#7B5B3A' }, { code: '9.21', brand: 'L\'Oréal', name: 'Оч. светл. блонд', hex: '#D4C5A9' }], extras: ['Olaplex No.1'], satisfaction: 5, photo: true },
        { date: '10.01.2026', masterId: 1, masterName: 'Анна Соколова', service: 'Окрашивание балаяж',   shades: [{ code: '5.15', brand: 'L\'Oréal', name: 'Светл. шатен', hex: '#6B4C3B' }, { code: '8.3', brand: 'L\'Oréal', name: 'Светл. блонд золот.', hex: '#C4A265' }], extras: ['Olaplex No.1'], satisfaction: 4, photo: true },
    ], mlProfile: { preferredShadeFamily: 'balayage_contrast', colorTone: 'neutral', colorDepth: 'medium', repeatRate: 0.70, loyalToMaster: true, avgSatisfaction: 4.5 } },

    { clientId: 4, clientName: 'Ксения Морозова',  visits: [
        { date: '07.04.2026', masterId: 13, masterName: 'Наталья Семёнова', service: 'Маникюр + покрытие', shades: [{ code: 'LX-AMORE', brand: 'Luxio', name: 'Amore', hex: '#C04040' }], extras: [], satisfaction: 5, photo: true },
        { date: '25.03.2026', masterId: 13, masterName: 'Наталья Семёнова', service: 'Маникюр + покрытие', shades: [{ code: 'LX-SOUL', brand: 'Luxio', name: 'Soul', hex: '#706060' }], extras: [], satisfaction: 4, photo: true },
        { date: '10.03.2026', masterId: 2,  masterName: 'Ольга Демидова',   service: 'Маникюр + покрытие', shades: [{ code: 'CND-092', brand: 'CND', name: 'Tundra', hex: '#8A7068' }], extras: [], satisfaction: 4, photo: true },
    ], mlProfile: { preferredShadeFamily: 'dark_bold', colorTone: 'cool', colorDepth: 'deep', repeatRate: 0.65, loyalToMaster: false, avgSatisfaction: 4.3 } },

    { clientId: 5, clientName: 'Дарья Смирнова',   visits: [
        { date: '06.04.2026', masterId: 9, masterName: 'Татьяна Новикова', service: 'Окрашивание AirTouch', shades: [{ code: '8-11', brand: 'IGORA', name: 'Светл. русый сандре', hex: '#A89888' }], extras: ['Olaplex No.1'], satisfaction: 5, photo: true },
        { date: '01.03.2026', masterId: 9, masterName: 'Татьяна Новикова', service: 'Тонирование',          shades: [{ code: '9-1', brand: 'IGORA', name: 'Блондин сандре', hex: '#C0B098' }], extras: [], satisfaction: 5, photo: false },
    ], mlProfile: { preferredShadeFamily: 'blonde_sandy', colorTone: 'neutral', colorDepth: 'light', repeatRate: 0.80, loyalToMaster: true, avgSatisfaction: 5.0 } },
]);

const clientFilter = reactive({ search: '', category: '' });
const filteredClients = computed(() => {
    let list = [...clientMaterialHistory.value];
    const q = clientFilter.search.toLowerCase();
    if (q) list = list.filter(c => c.clientName.toLowerCase().includes(q));
    return list;
});

/* ── ML-рекомендации (вычисленные на основе истории) ── */
function getMLRecommendations(client) {
    const allShades = client.visits.flatMap(v => v.shades);
    const shadeCount = {};
    allShades.forEach(s => { shadeCount[s.code] = (shadeCount[s.code] || 0) + 1; });
    const sorted = Object.entries(shadeCount).sort((a, b) => b[1] - a[1]);
    const topShade = sorted[0] ? allShades.find(s => s.code === sorted[0][0]) : null;

    const familyMap = { nude_pink: 'нюдово-розовые', blonde_ash: 'пепельный блонд', dark_bold: 'тёмные насыщенные', balayage_contrast: 'контрастный балаяж', blonde_sandy: 'песочный блонд' };
    const ml = client.mlProfile;

    return {
        topShade,
        family: familyMap[ml.preferredShadeFamily] || ml.preferredShadeFamily,
        tone: ml.colorTone === 'cool' ? 'холодный' : ml.colorTone === 'warm' ? 'тёплый' : 'нейтральный',
        depth: ml.colorDepth === 'light' ? 'светлый' : ml.colorDepth === 'medium' ? 'средний' : 'глубокий',
        repeatRate: Math.round(ml.repeatRate * 100),
        loyalToMaster: ml.loyalToMaster,
    };
}

/* ═══════════════════════════════════════════════════════════════ */
/*  5. WRITE-OFFS (списания)                                      */
/* ═══════════════════════════════════════════════════════════════ */
const writeoffs = ref([
    { id: 1, date: '08.04.2026', masterId: 1, masterName: 'Анна Соколова', clientName: 'Мария Козлова',     service: 'Окрашивание AirTouch', items: [{ name: 'Majirel 7.1', qty: 2, cost: 1300 }, { name: 'Оксид 6%', qty: 0.3, cost: 126 }, { name: 'Olaplex No.1', qty: 0.1, cost: 320 }, { name: 'Фольга', qty: 0.15, cost: 48 }], total: 1794 },
    { id: 2, date: '08.04.2026', masterId: 2, masterName: 'Ольга Демидова', clientName: 'Елена Петрова',    service: 'Маникюр + покрытие',   items: [{ name: 'CND Cream Puff', qty: 0.1, cost: 125 }, { name: 'База CND', qty: 0.05, cost: 62 }, { name: 'Топ CND', qty: 0.05, cost: 62 }], total: 249 },
    { id: 3, date: '07.04.2026', masterId: 13, masterName: 'Наталья Семёнова', clientName: 'Ксения Морозова', service: 'Маникюр + покрытие', items: [{ name: 'Luxio Amore', qty: 0.1, cost: 168 }, { name: 'База Luxio', qty: 0.05, cost: 84 }], total: 252 },
    { id: 4, date: '07.04.2026', masterId: 1, masterName: 'Анна Соколова', clientName: 'Анастасия Иванова', service: 'Окрашивание балаяж',  items: [{ name: 'Majirel 6.0', qty: 1, cost: 650 }, { name: 'Majirel 9.21', qty: 1, cost: 650 }, { name: 'Olaplex No.1', qty: 0.1, cost: 320 }, { name: 'Оксид 6%', qty: 0.2, cost: 84 }], total: 1704 },
    { id: 5, date: '06.04.2026', masterId: 9, masterName: 'Татьяна Новикова', clientName: 'Дарья Смирнова', service: 'Окрашивание AirTouch', items: [{ name: 'IGORA 8-11', qty: 2, cost: 1440 }, { name: 'Оксид 6%', qty: 0.3, cost: 126 }, { name: 'Olaplex No.1', qty: 0.1, cost: 320 }], total: 1886 },
    { id: 6, date: '05.04.2026', masterId: 2, masterName: 'Ольга Демидова', clientName: 'Елена Петрова',    service: 'Маникюр + покрытие',   items: [{ name: 'CND Romantique', qty: 0.1, cost: 125 }], total: 249 },
]);

const writeoffTotals = computed(() => ({
    count: writeoffs.value.length,
    total: writeoffs.value.reduce((a, c) => a + c.total, 0),
    avgPerService: writeoffs.value.length > 0 ? Math.round(writeoffs.value.reduce((a, c) => a + c.total, 0) / writeoffs.value.length) : 0,
}));

/* ═══════════════════════════════════════════════════════════════ */
/*  6. PURCHASE ORDERS (закупки)                                   */
/* ═══════════════════════════════════════════════════════════════ */
const purchaseOrders = ref([
    { id: 1, date: '01.04.2026', supplier: 'L\'Oréal Professionnel РФ', status: 'delivered', items: 12, total: 78400, salon: 'BeautyLab Центр' },
    { id: 2, date: '28.03.2026', supplier: 'CND / OPI дистрибьютор',   status: 'delivered', items: 8,  total: 42600, salon: 'BeautyLab Центр' },
    { id: 3, date: '25.03.2026', supplier: 'Wella Professionals',       status: 'delivered', items: 6,  total: 31200, salon: 'BeautyLab Центр' },
    { id: 4, date: '20.03.2026', supplier: 'Olaplex Official',          status: 'delivered', items: 4,  total: 24000, salon: 'BeautyLab Центр' },
    { id: 5, date: '15.04.2026', supplier: 'Luxio Canada',              status: 'pending',   items: 6,  total: 52080, salon: 'BeautyLab Центр' },
    { id: 6, date: '12.04.2026', supplier: 'Schwarzkopf Professional',  status: 'pending',   items: 10, total: 36000, salon: 'BeautyLab Юг' },
]);
const poStatusLabels = { pending: '⏳ Ожидание', shipped: '🚚 В пути', delivered: '✅ Доставлено', cancelled: '🚫 Отменено' };
const poStatusColors = { pending: 'yellow', shipped: 'blue', delivered: 'green', cancelled: 'red' };

/* ═══════════════════════════════════════════════════════════════ */
/*  ADD STOCK MODAL                                               */
/* ═══════════════════════════════════════════════════════════════ */
const showAddStockModal = ref(false);
const addStockForm = reactive({
    name: '', brandName: '', category: 'hair_color', unit: 'тюбик', qty: 10, minQty: 3, costPrice: 0, shadeCode: '', salon: 'BeautyLab Центр',
});
function openAddStock() {
    Object.assign(addStockForm, { name: '', brandName: '', category: 'hair_color', unit: 'тюбик', qty: 10, minQty: 3, costPrice: 0, shadeCode: '', salon: 'BeautyLab Центр' });
    showAddStockModal.value = true;
}
function saveNewStock() {
    if (!addStockForm.name.trim()) return;
    stockItems.value.push({
        id: Date.now(), ...addStockForm,
        brandId: brands.value.find(b => b.name === addStockForm.brandName)?.id || 0,
        salePrice: 0, lastRestock: new Date().toLocaleDateString('ru-RU'),
    });
    toast(`✅ «${addStockForm.name}» добавлен на склад (${addStockForm.qty} ${addStockForm.unit})`);
    showAddStockModal.value = false;
}

/* ═══════════════════════════════════════════════════════════════ */
/*  RESTOCK MODAL                                                 */
/* ═══════════════════════════════════════════════════════════════ */
const showRestockModal = ref(false);
const restockTarget = ref(null);
const restockQty = ref(10);
function openRestock(item) {
    restockTarget.value = item;
    restockQty.value = item.minQty * 3;
    showRestockModal.value = true;
}
function confirmRestock() {
    if (restockTarget.value) {
        restockTarget.value.qty += restockQty.value;
        restockTarget.value.lastRestock = new Date().toLocaleDateString('ru-RU');
        emit('restock', { itemId: restockTarget.value.id, qty: restockQty.value });
        toast(`📦 Пополнено: ${restockTarget.value.name} +${restockQty.value} ${restockTarget.value.unit}`);
    }
    showRestockModal.value = false;
}

/* ═══════════════════════════════════════════════════════════════ */
/*  EXPORT                                                        */
/* ═══════════════════════════════════════════════════════════════ */
function exportInventory(format) {
    const header = '\uFEFF' + 'Наименование;Бренд;Категория;Кол-во;Мин.;Цена закупки;Салон;Оттенок\n';
    const rows = stockItems.value.map(s =>
        `${s.name};${s.brandName};${categoryLabels[s.category]||s.category};${s.qty};${s.minQty};${s.costPrice};${s.salon};${s.shadeCode||''}`
    ).join('\n');
    const blob = new Blob([header + rows], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `inventory_${Date.now()}.csv`;
    a.click();
    URL.revokeObjectURL(url);
    toast('📥 Инвентаризация скачана');
    emit('export-report', { type: 'inventory', format });
}

function exportClientML() {
    const data = clientMaterialHistory.value.map(c => ({
        clientId: c.clientId,
        clientName: c.clientName,
        visits: c.visits.length,
        mlProfile: c.mlProfile,
        allShades: c.visits.flatMap(v => v.shades.map(s => s.code)),
    }));
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `client_ml_data_${Date.now()}.json`;
    a.click();
    URL.revokeObjectURL(url);
    toast('🤖 ML-данные клиентов экспортированы');
}
</script>

<template>
<div class="space-y-4">
    <!-- ═══ HEADER ═══ -->
    <div class="flex justify-between items-center flex-wrap gap-3">
        <div>
            <h2 class="text-lg font-semibold" style="color:var(--t-text)">📦 Инвентаризация и материалы</h2>
            <div class="text-xs" style="color:var(--t-text-3)">
                {{ stockSummary.totalItems }} позиций · {{ fmt(stockSummary.totalQty) }} единиц · {{ stockSummary.lowStock }} ⚠️ мало
            </div>
        </div>
        <div class="flex items-center gap-2">
            <VButton size="sm" variant="outline" @click="exportInventory('csv')">📥 Экспорт</VButton>
            <VButton size="sm" @click="openAddStock">➕ Новая позиция</VButton>
        </div>
    </div>

    <!-- ═══ STATS ═══ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <VStatCard label="Позиции на складе" :value="String(stockSummary.totalItems)" icon="📦" />
        <VStatCard label="Стоимость склада" :value="fmtMoney(stockSummary.totalCost)" icon="💰" />
        <VStatCard label="⚠️ Мало на складе" :value="String(stockSummary.lowStock)" icon="🔴" />
        <VStatCard label="Брендов" :value="String(stockSummary.brandCount)" icon="🏷️" />
    </div>

    <!-- ═══ TABS ═══ -->
    <div class="flex items-center gap-1 overflow-x-auto pb-1">
        <button v-for="tab in tabs" :key="tab.key"
                class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors"
                :style="activeTab === tab.key
                    ? 'background:var(--t-primary);color:#fff'
                    : 'background:var(--t-surface);color:var(--t-text-2)'"
                @click="activeTab = tab.key">
            {{ tab.icon }} {{ tab.label }}
        </button>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  TAB 1: STOCK                                         -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'stock'" class="space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <VInput v-model="stockFilter.search" placeholder="🔍 Поиск материала или оттенка..." />
            <select v-model="stockFilter.category" class="px-3 py-2 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option value="">Все категории</option>
                <option v-for="(label, key) in categoryLabels" :key="key" :value="key">{{ label }}</option>
            </select>
            <select v-model="stockFilter.brand" class="px-3 py-2 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option value="">Все бренды</option>
                <option v-for="b in brands" :key="b.id" :value="b.name">{{ b.name }}</option>
            </select>
            <label class="flex items-center gap-2 text-sm" style="color:var(--t-text-2)">
                <input type="checkbox" v-model="stockFilter.lowOnly" class="w-4 h-4 rounded" style="accent-color:var(--t-primary)">
                ⚠️ Только мало
            </label>
        </div>

        <VCard>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left" style="color:var(--t-text-3)">
                            <th class="p-2">Наименование</th>
                            <th class="p-2">Бренд</th>
                            <th class="p-2">Категория</th>
                            <th class="p-2">Оттенок</th>
                            <th class="p-2 text-center">Кол-во</th>
                            <th class="p-2 text-right">Цена</th>
                            <th class="p-2">Салон</th>
                            <th class="p-2 text-center">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in filteredStock" :key="item.id"
                            class="border-t transition-colors"
                            :style="`border-color:var(--t-border);${item.qty <= item.minQty ? 'background:rgba(239,68,68,0.05)' : ''}`">
                            <td class="p-2 font-medium" style="color:var(--t-text)">{{ item.name }}</td>
                            <td class="p-2 text-xs" style="color:var(--t-text-2)">{{ item.brandName }}</td>
                            <td class="p-2">
                                <span class="text-[10px] px-2 py-0.5 rounded-full" style="background:var(--t-primary-dim);color:var(--t-primary)">
                                    {{ categoryLabels[item.category]?.split(' ').slice(1).join(' ') || item.category }}
                                </span>
                            </td>
                            <td class="p-2">
                                <div v-if="item.shadeCode" class="flex items-center gap-1.5">
                                    <span class="w-4 h-4 rounded-full border" :style="`background:${palettes.flatMap(p => p.shades).find(s => s.code === item.shadeCode)?.hex || '#ccc'};border-color:var(--t-border)`"></span>
                                    <span class="text-xs" style="color:var(--t-text-2)">{{ item.shadeCode }}</span>
                                </div>
                                <span v-else class="text-xs" style="color:var(--t-text-3)">—</span>
                            </td>
                            <td class="p-2 text-center font-bold"
                                :style="`color:${item.qty <= item.minQty ? '#ef4444' : item.qty <= item.minQty * 2 ? '#f59e0b' : '#22c55e'}`">
                                {{ item.qty }} <span class="font-normal text-[10px]" style="color:var(--t-text-3)">/ min {{ item.minQty }}</span>
                            </td>
                            <td class="p-2 text-right text-xs" style="color:var(--t-text)">{{ fmtMoney(item.costPrice) }}</td>
                            <td class="p-2 text-xs" style="color:var(--t-text-3)">{{ item.salon }}</td>
                            <td class="p-2 text-center">
                                <VButton size="sm" variant="outline" @click="openRestock(item)">📦+</VButton>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-xs pt-3 border-t" style="color:var(--t-text-3);border-color:var(--t-border)">
                {{ filteredStock.length }} из {{ stockItems.length }} позиций · Стоимость: {{ fmtMoney(filteredStock.reduce((a,c) => a + c.qty * c.costPrice, 0)) }}
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  TAB 2: BRANDS & PALETTES                             -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'brands'" class="space-y-4">
        <!-- Brands grid -->
        <VCard title="🏷️ Бренды">
            <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-3">
                <div v-for="b in brands" :key="b.id"
                     class="p-3 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-lg">{{ b.logo }}</span>
                        <div class="flex-1">
                            <div class="text-sm font-semibold" style="color:var(--t-text)">{{ b.name }}</div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">{{ b.country }}</div>
                        </div>
                        <VBadge :color="tierColors[b.tier]" size="sm">{{ tierLabels[b.tier]?.split(' ').pop() }}</VBadge>
                    </div>
                    <div class="text-[10px]" style="color:var(--t-text-2)">
                        {{ categoryLabels[b.category] || b.category }}
                        · {{ stockItems.filter(s => s.brandId === b.id).length }} позиций на складе
                    </div>
                </div>
            </div>
        </VCard>

        <!-- Palettes with color swatches -->
        <VCard title="🎨 Палитры и оттенки">
            <div class="space-y-4">
                <div v-for="pal in palettes" :key="pal.id"
                     class="p-4 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-sm font-bold" style="color:var(--t-text)">{{ pal.brandName }}</span>
                        <span class="text-xs" style="color:var(--t-text-3)">· {{ pal.line }}</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full" style="background:var(--t-primary-dim);color:var(--t-primary)">
                            {{ categoryLabels[pal.category]?.split(' ').slice(1).join(' ') }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <div v-for="shade in pal.shades" :key="shade.code"
                             class="flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs"
                             style="border-color:var(--t-border)">
                            <span class="w-5 h-5 rounded-full border shadow-sm"
                                  :style="`background:${shade.hex};border-color:var(--t-border)`"></span>
                            <div>
                                <div class="font-medium" style="color:var(--t-text)">{{ shade.code }}</div>
                                <div class="text-[10px]" style="color:var(--t-text-3)">{{ shade.name }}</div>
                            </div>
                            <div class="text-[10px] font-bold"
                                 :style="`color:${shade.popularity > 80 ? '#22c55e' : shade.popularity > 50 ? 'var(--t-primary)' : 'var(--t-text-3)'}`">
                                🔥 {{ shade.popularity }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  TAB 3: SERVICE-MATERIAL MAPPING                      -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'service-mapping'" class="space-y-4">
        <VCard title="🔗 Привязка материалов к услугам (себестоимость)">
            <div class="space-y-3">
                <div v-for="svc in serviceMaterialMap" :key="svc.serviceId"
                     class="p-4 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm font-bold" style="color:var(--t-text)">{{ svc.serviceName }}</div>
                        <div class="text-sm font-bold" style="color:var(--t-primary)">
                            Себестоимость: {{ fmtMoney(serviceTotalCost(svc)) }}
                        </div>
                    </div>
                    <table class="w-full text-xs">
                        <thead>
                            <tr style="color:var(--t-text-3)">
                                <th class="text-left pb-1">Материал</th>
                                <th class="text-center pb-1">Расход</th>
                                <th class="text-right pb-1">Стоимость</th>
                                <th class="text-left pb-1 pl-3">Примечание</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(m, idx) in svc.materials" :key="idx" class="border-t" style="border-color:var(--t-border)">
                                <td class="py-1" style="color:var(--t-text)">{{ m.itemName }}</td>
                                <td class="py-1 text-center" style="color:var(--t-text-2)">{{ m.qty }} {{ m.unit }}</td>
                                <td class="py-1 text-right font-medium" style="color:var(--t-primary)">{{ fmtMoney(m.costPer) }}</td>
                                <td class="py-1 pl-3 text-[10px]" style="color:var(--t-text-3)">{{ m.brandNote || '' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  TAB 4: MASTER PREFERENCES & CONSUMPTION              -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'master-prefs'" class="space-y-4">
        <div v-for="mp in masterPreferences" :key="mp.masterId"
             class="space-y-3">
            <VCard>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold"
                         style="background:var(--t-primary-dim);color:var(--t-primary)">{{ mp.masterName.charAt(0) }}</div>
                    <div class="flex-1">
                        <div class="text-sm font-bold" style="color:var(--t-text)">{{ mp.masterName }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">{{ mp.position }} · Расход: {{ fmtMoney(mp.materialCost) }}/мес</div>
                    </div>
                    <VButton size="sm" variant="outline" @click="emit('open-master', { id: mp.masterId, name: mp.masterName })">📋 Карточка</VButton>
                </div>

                <!-- Preferred brands -->
                <div class="mb-4">
                    <div class="text-xs font-bold mb-2" style="color:var(--t-text-2)">🏷️ Предпочитаемые бренды</div>
                    <div class="space-y-1.5">
                        <div v-for="b in mp.preferredBrands" :key="b.brandId"
                             class="flex items-center gap-2 p-2 rounded-lg" style="background:var(--t-bg)">
                            <VBadge :color="tierColors[brands.find(br => br.id === b.brandId)?.tier] || 'gray'" size="sm">
                                {{ b.brandName }}
                            </VBadge>
                            <span class="text-[10px]" style="color:var(--t-text-3)">{{ categoryLabels[b.category]?.split(' ').slice(1).join(' ') }}</span>
                            <span class="text-[10px] font-bold" style="color:var(--t-primary)">{{ b.usageCount }}x</span>
                            <span class="text-[10px]">{{ '⭐'.repeat(b.rating) }}</span>
                            <span class="flex-1 text-[10px] text-right" style="color:var(--t-text-3)">{{ b.note }}</span>
                        </div>
                    </div>
                </div>

                <!-- Top shades used by master -->
                <div v-if="mp.topShades.length">
                    <div class="text-xs font-bold mb-2" style="color:var(--t-text-2)">🎨 Топ-оттенки мастера</div>
                    <div class="flex flex-wrap gap-2">
                        <div v-for="s in mp.topShades" :key="s.code"
                             class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-xs"
                             style="border-color:var(--t-border)">
                            <span class="w-4 h-4 rounded-full border" :style="`background:${s.hex};border-color:var(--t-border)`"></span>
                            <span class="font-medium" style="color:var(--t-text)">{{ s.code }}</span>
                            <span style="color:var(--t-text-3)">{{ s.brand }}</span>
                            <span class="font-bold" style="color:var(--t-primary)">{{ s.count }}x</span>
                        </div>
                    </div>
                </div>

                <!-- Monthly consumption -->
                <div class="mt-3 pt-3 border-t" style="border-color:var(--t-border)">
                    <div class="text-xs font-bold mb-2" style="color:var(--t-text-2)">📊 Расход за месяц (ед.)</div>
                    <div class="flex flex-wrap gap-3">
                        <div v-for="(val, cat) in mp.monthlyConsumption" :key="cat"
                             class="text-center px-3 py-1.5 rounded-lg" style="background:var(--t-bg)">
                            <div class="text-xs font-bold" style="color:var(--t-primary)">{{ val }}</div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">{{ categoryLabels[cat]?.split(' ').slice(1).join(' ') || cat }}</div>
                        </div>
                    </div>
                </div>
            </VCard>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  TAB 5: CLIENT MATERIAL HISTORY (ML-DATA)             -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'client-history'" class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm font-bold" style="color:var(--t-text)">🤖 ML-данные: история материалов клиентов</div>
                <div class="text-xs" style="color:var(--t-text-3)">Оттенки, бренды и предпочтения для ML-рекомендаций</div>
            </div>
            <VButton size="sm" variant="outline" @click="exportClientML">📥 Экспорт ML-данных</VButton>
        </div>

        <VInput v-model="clientFilter.search" placeholder="🔍 Поиск по имени клиента..." />

        <div v-for="client in filteredClients" :key="client.clientId" class="space-y-2">
            <VCard>
                <!-- Client header + ML profile -->
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold"
                         style="background:var(--t-accent);color:#fff">{{ client.clientName.charAt(0) }}</div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold cursor-pointer hover:underline" style="color:var(--t-text)"
                                  @click="emit('open-client', { id: client.clientId, name: client.clientName })">
                                {{ client.clientName }}
                            </span>
                            <VBadge color="blue" size="sm">{{ client.visits.length }} визитов</VBadge>
                        </div>
                        <div class="text-xs mt-1" style="color:var(--t-text-3)">
                            Посл. визит: {{ client.visits[0]?.date }} · Мастер: {{ client.visits[0]?.masterName }}
                        </div>
                    </div>
                </div>

                <!-- ML recommendation block -->
                <div class="p-3 rounded-xl mb-4" style="background:var(--t-primary-dim)">
                    <div class="text-xs font-bold mb-2" style="color:var(--t-primary)">🤖 ML-профиль клиента</div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                        <div>
                            <span style="color:var(--t-text-3)">Семейство:</span>
                            <span class="font-bold ml-1" style="color:var(--t-text)">{{ getMLRecommendations(client).family }}</span>
                        </div>
                        <div>
                            <span style="color:var(--t-text-3)">Тон:</span>
                            <span class="font-bold ml-1" style="color:var(--t-text)">{{ getMLRecommendations(client).tone }}</span>
                        </div>
                        <div>
                            <span style="color:var(--t-text-3)">Глубина:</span>
                            <span class="font-bold ml-1" style="color:var(--t-text)">{{ getMLRecommendations(client).depth }}</span>
                        </div>
                        <div>
                            <span style="color:var(--t-text-3)">Повтор:</span>
                            <span class="font-bold ml-1" style="color:var(--t-primary)">{{ getMLRecommendations(client).repeatRate }}%</span>
                        </div>
                    </div>
                    <div v-if="getMLRecommendations(client).topShade" class="mt-2 flex items-center gap-2 text-xs">
                        <span style="color:var(--t-text-3)">Топ-оттенок:</span>
                        <span class="w-4 h-4 rounded-full border"
                              :style="`background:${getMLRecommendations(client).topShade.hex};border-color:var(--t-border)`"></span>
                        <span class="font-bold" style="color:var(--t-text)">
                            {{ getMLRecommendations(client).topShade.code }} {{ getMLRecommendations(client).topShade.name }}
                        </span>
                        <span style="color:var(--t-text-3)">({{ getMLRecommendations(client).topShade.brand }})</span>
                    </div>
                </div>

                <!-- Visit history with materials -->
                <div class="text-xs font-bold mb-2" style="color:var(--t-text-2)">📋 История визитов и оттенков</div>
                <div class="space-y-2">
                    <div v-for="(visit, idx) in client.visits" :key="idx"
                         class="flex items-center gap-3 p-2.5 rounded-lg border text-xs"
                         style="background:var(--t-bg);border-color:var(--t-border)">
                        <span class="w-16 shrink-0 font-medium" style="color:var(--t-text-3)">{{ visit.date }}</span>
                        <span class="shrink-0" style="color:var(--t-text)">{{ visit.service }}</span>
                        <div class="flex items-center gap-1 flex-1">
                            <template v-for="shade in visit.shades" :key="shade.code">
                                <span class="w-4 h-4 rounded-full border" :style="`background:${shade.hex};border-color:var(--t-border)`"></span>
                                <span style="color:var(--t-text-2)">{{ shade.code }}</span>
                            </template>
                            <span v-if="!visit.shades.length" style="color:var(--t-text-3)">—</span>
                        </div>
                        <span v-if="visit.extras.length" class="text-[10px]" style="color:var(--t-primary)">+{{ visit.extras.join(', ') }}</span>
                        <span>{{ '⭐'.repeat(visit.satisfaction) }}</span>
                        <span v-if="visit.photo" class="text-[10px]">📸</span>
                        <span class="text-[10px]" style="color:var(--t-text-3)">{{ visit.masterName }}</span>
                    </div>
                </div>
            </VCard>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  TAB 6: WRITE-OFFS                                    -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'writeoffs'" class="space-y-4">
        <div class="grid grid-cols-3 gap-3">
            <VStatCard label="Списаний" :value="String(writeoffTotals.count)" icon="📝" />
            <VStatCard label="Сумма списаний" :value="fmtMoney(writeoffTotals.total)" icon="💸" />
            <VStatCard label="Средняя на услугу" :value="fmtMoney(writeoffTotals.avgPerService)" icon="📊" />
        </div>

        <VCard title="📝 Журнал списаний">
            <div class="space-y-2">
                <div v-for="wo in writeoffs" :key="wo.id"
                     class="p-3 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium" style="color:var(--t-text-3)">{{ wo.date }}</span>
                            <span class="text-sm font-bold" style="color:var(--t-text)">{{ wo.service }}</span>
                        </div>
                        <span class="text-sm font-bold" style="color:var(--t-primary)">{{ fmtMoney(wo.total) }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs mb-2" style="color:var(--t-text-2)">
                        <span>👩‍🎨 {{ wo.masterName }}</span>
                        <span>👤 {{ wo.clientName }}</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="(item, idx) in wo.items" :key="idx"
                              class="px-2 py-0.5 rounded-full text-[10px]"
                              style="background:var(--t-surface);color:var(--t-text-2)">
                            {{ item.name }} × {{ item.qty }} = {{ fmtMoney(item.cost) }}
                        </span>
                    </div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  TAB 7: PURCHASE ORDERS                               -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'orders'" class="space-y-4">
        <VCard title="🛒 Заказы поставщикам">
            <div class="space-y-2">
                <div v-for="po in purchaseOrders" :key="po.id"
                     class="flex items-center gap-3 p-3 rounded-xl border"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex-1">
                        <div class="text-sm font-bold" style="color:var(--t-text)">{{ po.supplier }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">{{ po.date }} · {{ po.items }} позиций · {{ po.salon }}</div>
                    </div>
                    <span class="text-sm font-bold" style="color:var(--t-primary)">{{ fmtMoney(po.total) }}</span>
                    <VBadge :color="poStatusColors[po.status]" size="sm">{{ poStatusLabels[po.status] }}</VBadge>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  MODALS                                               -->
    <!-- ══════════════════════════════════════════════════════ -->

    <!-- Add Stock Item -->
    <VModal :show="showAddStockModal" @close="showAddStockModal = false" title="➕ Новая позиция на складе">
        <div class="space-y-3">
            <VInput v-model="addStockForm.name" placeholder="Наименование" />
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Бренд</label>
                    <select v-model="addStockForm.brandName" class="w-full px-3 py-2 rounded-lg text-sm border"
                            style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                        <option value="">Без бренда</option>
                        <option v-for="b in brands" :key="b.id" :value="b.name">{{ b.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Категория</label>
                    <select v-model="addStockForm.category" class="w-full px-3 py-2 rounded-lg text-sm border"
                            style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                        <option v-for="(label, key) in categoryLabels" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Кол-во</label>
                    <VInput v-model="addStockForm.qty" type="number" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Мин. остаток</label>
                    <VInput v-model="addStockForm.minQty" type="number" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Цена закупки</label>
                    <VInput v-model="addStockForm.costPrice" type="number" />
                </div>
            </div>
            <VInput v-model="addStockForm.shadeCode" placeholder="Код оттенка (если есть)" />
        </div>
        <template #footer>
            <VButton variant="outline" @click="showAddStockModal = false">Отмена</VButton>
            <VButton @click="saveNewStock">➕ Добавить</VButton>
        </template>
    </VModal>

    <!-- Restock Modal -->
    <VModal :show="showRestockModal" @close="showRestockModal = false" title="📦 Пополнение остатка">
        <div class="space-y-3">
            <div class="text-sm" style="color:var(--t-text)">
                <b>{{ restockTarget?.name }}</b>
            </div>
            <div class="text-xs" style="color:var(--t-text-3)">
                Текущий остаток: <b :style="`color:${restockTarget?.qty <= restockTarget?.minQty ? '#ef4444' : '#22c55e'}`">{{ restockTarget?.qty }}</b>
                · Минимум: {{ restockTarget?.minQty }}
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:var(--t-text-2)">Количество</label>
                <VInput v-model="restockQty" type="number" />
            </div>
            <div class="text-xs" style="color:var(--t-text-3)">
                Стоимость: {{ fmtMoney(restockQty * (restockTarget?.costPrice || 0)) }}
            </div>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showRestockModal = false">Отмена</VButton>
            <VButton @click="confirmRestock">📦 Пополнить</VButton>
        </template>
    </VModal>

    <!-- Toast -->
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="showToast" class="fixed bottom-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-medium"
                 :style="{ background: 'var(--t-primary)', color: '#fff' }">
                {{ toastMessage }}
            </div>
        </Transition>
    </Teleport>
</div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
