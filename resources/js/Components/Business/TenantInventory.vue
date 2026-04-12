<script setup lang="ts">
/**
 * TenantInventory.vue — Главная страница инвентаризации и склада
 *                       в B2B Tenant Dashboard
 *
 * Поддержка всех 127 вертикалей CatVRF:
 *   Beauty (расходники, косметика) · Taxi (запчасти, масла)
 *   Food (продукты, ингредиенты) · Hotels (бельё, расходники)
 *   RealEstate (стройматериалы, мебель для стейджинга)
 *   Flowers (цветы, материалы, ленты)
 *   Fashion (одежда, аксессуары) · Furniture (мебель, фурнитура)
 *   Fitness (инвентарь, добавки) · Travel (промо-материалы)
 *   + default (универсальный товар)
 *
 * ─────────────────────────────────────────────────────────────
 *  Функционал:
 *   1. Верхняя панель: поиск + фильтры + «Провести инвентаризацию»
 *      + «Приход товара»
 *   2. Grid KPI-виджетов (общий остаток, низкий остаток, в пути,
 *      себестоимость, количество SKU, среднее время хранения)
 *   3. Основная таблица товаров (desktop) / карточки (mobile)
 *   4. Sidebar с категориями и оповещениями о низком остатке
 *   5. Full-screen режим
 *   6. Модальное окно детали товара
 *   7. Массовые действия (списание, перемещение, экспорт)
 *   8. Вертикаль-зависимая терминология через VERTICAL_INVENTORY_CONFIG
 *   9. B2B-фокус: учёт по складам, MOQ, себестоимость, приход/расход
 *  10. Пагинация + бесконечный скролл
 * ─────────────────────────────────────────────────────────────
 */

import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'

import VCard   from '../UI/VCard.vue'
import VButton from '../UI/VButton.vue'
import VBadge  from '../UI/VBadge.vue'
import VTabs   from '../UI/VTabs.vue'
import VModal  from '../UI/VModal.vue'
import VInput  from '../UI/VInput.vue'
import { useAuth, useTenant } from '@/stores'

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  TYPES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

type StockLevel   = 'normal' | 'low' | 'critical' | 'out_of_stock'
type MovementType = 'in' | 'out' | 'reserve' | 'release' | 'return' | 'adjustment' | 'write_off'
type AuditStatus  = 'planned' | 'in_progress' | 'completed' | 'discrepancy'

interface InventoryItem {
  id:             number | string
  name:           string
  sku:            string
  category:       string
  categoryId:     string
  unit:           string                // шт, кг, л, м, компл.
  quantity:        number
  reserved:       number
  available:      number                // quantity - reserved
  minStock:       number                // порог низкого остатка
  costPrice:      number                // себестоимость за единицу ₽
  sellPrice:      number                // цена продажи ₽
  stockLevel:     StockLevel
  warehouseId:    string
  warehouseName:  string
  supplier?:      string
  barcode?:       string
  image?:         string
  lastMovement?:  string                // ISO
  expiresAt?:     string                // ISO (для скоропортящихся)
  tags:           string[]
  correlationId?: string
  verticalData?:  Record<string, unknown>
}

interface InventoryCategory {
  id:       string
  name:     string
  icon:     string
  count:    number                     // кол-во SKU в категории
  lowStock: number                     // кол-во с низким остатком
}

interface StockMovement {
  id:           number | string
  itemId:       number | string
  itemName:     string
  type:         MovementType
  quantity:     number
  date:         string                 // ISO
  source:       string                 // order, cart, supplier, manual
  note?:        string
  correlationId?: string
}

interface InventoryAlert {
  id:        number | string
  itemId:    number | string
  itemName:  string
  message:   string
  severity:  'warning' | 'critical'
  createdAt: string
}

interface StockAudit {
  id:           number | string
  warehouseId:  string
  warehouseName: string
  status:       AuditStatus
  startedAt:    string
  completedAt?: string
  discrepancies: number
  employeeName: string
}

interface InventoryStats {
  totalSku:        number
  totalQuantity:   number
  totalValue:      number              // общая себестоимость на складе
  lowStockCount:   number
  outOfStockCount: number
  incomingCount:   number              // товары «в пути»
  reservedTotal:   number
  writeOffTotal:   number              // списано за период
  avgTurnoverDays: number              // среднее время оборачиваемости
}

interface InventoryFilter {
  search:       string
  category:     string
  stockLevel:   string
  warehouse:    string
  supplier:     string
  sortBy:       string
  sortDir:      'asc' | 'desc'
}

interface Warehouse {
  id:   string
  name: string
}

interface VerticalInventoryConfig {
  label:          string
  icon:           string
  itemLabel:      string              // «Товар» / «Ингредиент» / «Запчасть»
  itemLabelPlural: string             // «Товары» / «Ингредиенты» / «Запчасти»
  unitDefault:    string              // «шт.» / «кг» / «л»
  hasExpiry:      boolean             // скоропортящийся (Food, Flowers)
  categories:     InventoryCategory[]
  extraColumns:   Array<{ key: string; label: string }>
  quickActions:   Array<{ key: string; label: string; icon: string }>
  kpiLabels:      { totalItems: string; lowStock: string; incoming: string }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  PROPS & EMITS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const props = withDefaults(defineProps<{
  vertical?:    string
  items?:       InventoryItem[]
  stats?:       InventoryStats
  categories?:  InventoryCategory[]
  warehouses?:  Warehouse[]
  alerts?:      InventoryAlert[]
  movements?:   StockMovement[]
  audits?:      StockAudit[]
  totalItems?:  number
  loading?:     boolean
  perPage?:     number
}>(), {
  vertical:   'default',
  items:      () => [],
  stats:      () => ({
    totalSku: 0, totalQuantity: 0, totalValue: 0,
    lowStockCount: 0, outOfStockCount: 0, incomingCount: 0,
    reservedTotal: 0, writeOffTotal: 0, avgTurnoverDays: 0,
  }),
  categories: () => [],
  warehouses: () => [],
  alerts:     () => [],
  movements:  () => [],
  audits:     () => [],
  totalItems: 0,
  loading:    false,
  perPage:    25,
})

const emit = defineEmits<{
  'item-click':         [item: InventoryItem]
  'item-create':        []
  'item-edit':          [item: InventoryItem]
  'item-delete':        [itemIds: Array<number | string>]
  'stock-in':           []
  'stock-out':          [itemIds: Array<number | string>]
  'stock-adjust':       [item: InventoryItem]
  'start-audit':        []
  'category-change':    [categoryId: string]
  'filter-change':      [filters: InventoryFilter]
  'sort-change':        [sortBy: string, sortDir: 'asc' | 'desc']
  'page-change':        [page: number]
  'bulk-action':        [action: string, itemIds: Array<number | string>]
  'export':             [format: 'xlsx' | 'csv']
  'alert-dismiss':      [alertId: number | string]
  'load-more':          []
}>()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STORES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const auth     = useAuth()
const business = useTenant()

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  VERTICAL INVENTORY CONFIG
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const VERTICAL_INVENTORY_CONFIG: Record<string, VerticalInventoryConfig> = {
  // ── BEAUTY ───────────────────────────────
  beauty: {
    label: 'Склад салона', icon: '💄',
    itemLabel: 'Расходник', itemLabelPlural: 'Расходники',
    unitDefault: 'шт.', hasExpiry: true,
    categories: [
      { id: 'hair',     name: 'Средства для волос', icon: '💇', count: 0, lowStock: 0 },
      { id: 'nails',    name: 'Ногтевой сервис',    icon: '💅', count: 0, lowStock: 0 },
      { id: 'skin',     name: 'Уход за кожей',      icon: '🧴', count: 0, lowStock: 0 },
      { id: 'makeup',   name: 'Макияж',             icon: '💋', count: 0, lowStock: 0 },
      { id: 'tools',    name: 'Инструменты',        icon: '✂️', count: 0, lowStock: 0 },
    ],
    extraColumns: [
      { key: 'brand', label: 'Бренд' },
      { key: 'volume', label: 'Объём' },
    ],
    quickActions: [
      { key: 'stock-in',   label: 'Приход расходников', icon: '📦' },
      { key: 'write-off',  label: 'Списать',           icon: '🗑️' },
      { key: 'audit',      label: 'Инвентаризация',    icon: '📋' },
      { key: 'export',     label: 'Экспорт XLSX',      icon: '📥' },
    ],
    kpiLabels: { totalItems: 'Позиций на складе', lowStock: 'Заканчиваются', incoming: 'Ожидается поставка' },
  },

  // ── TAXI ─────────────────────────────────
  taxi: {
    label: 'Склад запчастей', icon: '🚕',
    itemLabel: 'Запчасть', itemLabelPlural: 'Запчасти',
    unitDefault: 'шт.', hasExpiry: false,
    categories: [
      { id: 'engine',  name: 'Двигатель',       icon: '⚙️', count: 0, lowStock: 0 },
      { id: 'tires',   name: 'Шины и колёса',   icon: '🛞', count: 0, lowStock: 0 },
      { id: 'brakes',  name: 'Тормозная система', icon: '🔴', count: 0, lowStock: 0 },
      { id: 'oils',    name: 'Масла и жидкости', icon: '🛢️', count: 0, lowStock: 0 },
      { id: 'body',    name: 'Кузов',           icon: '🚗', count: 0, lowStock: 0 },
    ],
    extraColumns: [
      { key: 'partNumber', label: '№ Детали' },
      { key: 'compatibility', label: 'Совместимость' },
    ],
    quickActions: [
      { key: 'stock-in',   label: 'Приход запчастей', icon: '📦' },
      { key: 'write-off',  label: 'Списать',          icon: '🗑️' },
      { key: 'audit',      label: 'Инвентаризация',   icon: '📋' },
      { key: 'export',     label: 'Экспорт XLSX',     icon: '📥' },
    ],
    kpiLabels: { totalItems: 'Позиций на складе', lowStock: 'Заканчиваются', incoming: 'В пути' },
  },

  // ── FOOD ─────────────────────────────────
  food: {
    label: 'Склад продуктов', icon: '🍽️',
    itemLabel: 'Продукт', itemLabelPlural: 'Продукты',
    unitDefault: 'кг', hasExpiry: true,
    categories: [
      { id: 'meat',       name: 'Мясо и птица',      icon: '🥩', count: 0, lowStock: 0 },
      { id: 'fish',       name: 'Рыба и морепродукты', icon: '🐟', count: 0, lowStock: 0 },
      { id: 'dairy',      name: 'Молочные продукты',  icon: '🥛', count: 0, lowStock: 0 },
      { id: 'vegetables', name: 'Овощи и фрукты',    icon: '🥬', count: 0, lowStock: 0 },
      { id: 'spices',     name: 'Специи и соусы',    icon: '🧂', count: 0, lowStock: 0 },
      { id: 'drinks',     name: 'Напитки',           icon: '🥤', count: 0, lowStock: 0 },
    ],
    extraColumns: [
      { key: 'expiresAt', label: 'Годен до' },
      { key: 'storage', label: 'Условия хранения' },
    ],
    quickActions: [
      { key: 'stock-in',   label: 'Приход продуктов', icon: '📦' },
      { key: 'write-off',  label: 'Списать (брак/просрочка)', icon: '🗑️' },
      { key: 'audit',      label: 'Ревизия',          icon: '📋' },
      { key: 'export',     label: 'Экспорт XLSX',     icon: '📥' },
    ],
    kpiLabels: { totalItems: 'Позиций на складе', lowStock: 'Заканчиваются', incoming: 'Ожидается поставка' },
  },

  // ── HOTEL ────────────────────────────────
  hotel: {
    label: 'Склад отеля', icon: '🏨',
    itemLabel: 'Позиция', itemLabelPlural: 'Позиции',
    unitDefault: 'шт.', hasExpiry: true,
    categories: [
      { id: 'linen',      name: 'Постельное бельё', icon: '🛏️', count: 0, lowStock: 0 },
      { id: 'towels',     name: 'Полотенца',        icon: '🧖', count: 0, lowStock: 0 },
      { id: 'amenities',  name: 'Косметика номера', icon: '🧴', count: 0, lowStock: 0 },
      { id: 'cleaning',   name: 'Уборочные средства', icon: '🧹', count: 0, lowStock: 0 },
      { id: 'minibar',    name: 'Мини-бар',         icon: '🍫', count: 0, lowStock: 0 },
    ],
    extraColumns: [
      { key: 'roomType', label: 'Тип номера' },
      { key: 'lastReplaced', label: 'Последняя замена' },
    ],
    quickActions: [
      { key: 'stock-in',   label: 'Приход на склад', icon: '📦' },
      { key: 'write-off',  label: 'Списать',         icon: '🗑️' },
      { key: 'audit',      label: 'Инвентаризация',  icon: '📋' },
      { key: 'export',     label: 'Экспорт XLSX',    icon: '📥' },
    ],
    kpiLabels: { totalItems: 'Позиций на складе', lowStock: 'Заканчиваются', incoming: 'Ожидается поставка' },
  },

  // ── REAL ESTATE ──────────────────────────
  realEstate: {
    label: 'Склад стейджинга', icon: '🏢',
    itemLabel: 'Предмет', itemLabelPlural: 'Предметы',
    unitDefault: 'шт.', hasExpiry: false,
    categories: [
      { id: 'furniture', name: 'Мебель',        icon: '🪑', count: 0, lowStock: 0 },
      { id: 'decor',     name: 'Декор',         icon: '🖼️', count: 0, lowStock: 0 },
      { id: 'lighting',  name: 'Освещение',     icon: '💡', count: 0, lowStock: 0 },
      { id: 'textiles',  name: 'Текстиль',      icon: '🪡', count: 0, lowStock: 0 },
    ],
    extraColumns: [
      { key: 'condition', label: 'Состояние' },
      { key: 'assignedProperty', label: 'На объекте' },
    ],
    quickActions: [
      { key: 'stock-in',   label: 'Принять на склад', icon: '📦' },
      { key: 'transfer',   label: 'Перемещение',      icon: '🔄' },
      { key: 'audit',      label: 'Инвентаризация',   icon: '📋' },
      { key: 'export',     label: 'Экспорт XLSX',     icon: '📥' },
    ],
    kpiLabels: { totalItems: 'Предметов на складе', lowStock: 'Требуют замены', incoming: 'На подходе' },
  },

  // ── FLOWERS ──────────────────────────────
  flowers: {
    label: 'Склад цветов', icon: '💐',
    itemLabel: 'Позиция', itemLabelPlural: 'Позиции',
    unitDefault: 'шт.', hasExpiry: true,
    categories: [
      { id: 'roses',      name: 'Розы',             icon: '🌹', count: 0, lowStock: 0 },
      { id: 'tulips',     name: 'Тюльпаны',         icon: '🌷', count: 0, lowStock: 0 },
      { id: 'mixed',      name: 'Сборные цветы',    icon: '💐', count: 0, lowStock: 0 },
      { id: 'greens',     name: 'Зелень и декор',   icon: '🌿', count: 0, lowStock: 0 },
      { id: 'packaging',  name: 'Упаковка',         icon: '🎁', count: 0, lowStock: 0 },
    ],
    extraColumns: [
      { key: 'freshness', label: 'Свежесть' },
      { key: 'expiresAt', label: 'Годен до' },
    ],
    quickActions: [
      { key: 'stock-in',   label: 'Приход цветов',  icon: '📦' },
      { key: 'write-off',  label: 'Списать (увядшие)', icon: '🗑️' },
      { key: 'audit',      label: 'Ревизия',        icon: '📋' },
      { key: 'export',     label: 'Экспорт XLSX',   icon: '📥' },
    ],
    kpiLabels: { totalItems: 'Позиций на складе', lowStock: 'Заканчиваются', incoming: 'Ожидается поставка' },
  },

  // ── FASHION ──────────────────────────────
  fashion: {
    label: 'Склад одежды', icon: '👗',
    itemLabel: 'Товар', itemLabelPlural: 'Товары',
    unitDefault: 'шт.', hasExpiry: false,
    categories: [
      { id: 'women',      name: 'Женская одежда',  icon: '👗', count: 0, lowStock: 0 },
      { id: 'men',        name: 'Мужская одежда',  icon: '👔', count: 0, lowStock: 0 },
      { id: 'shoes',      name: 'Обувь',           icon: '👠', count: 0, lowStock: 0 },
      { id: 'accessories', name: 'Аксессуары',     icon: '👜', count: 0, lowStock: 0 },
    ],
    extraColumns: [
      { key: 'size', label: 'Размер' },
      { key: 'color', label: 'Цвет' },
      { key: 'brand', label: 'Бренд' },
    ],
    quickActions: [
      { key: 'stock-in',   label: 'Приход коллекции', icon: '📦' },
      { key: 'return',     label: 'Оформить возврат', icon: '↩️' },
      { key: 'audit',      label: 'Инвентаризация',   icon: '📋' },
      { key: 'export',     label: 'Экспорт XLSX',     icon: '📥' },
    ],
    kpiLabels: { totalItems: 'SKU на складе', lowStock: 'Заканчиваются', incoming: 'В пути от поставщика' },
  },

  // ── FURNITURE ────────────────────────────
  furniture: {
    label: 'Склад мебели', icon: '🛋️',
    itemLabel: 'Товар', itemLabelPlural: 'Товары',
    unitDefault: 'шт.', hasExpiry: false,
    categories: [
      { id: 'sofas',    name: 'Диваны и кресла',  icon: '🛋️', count: 0, lowStock: 0 },
      { id: 'tables',   name: 'Столы',            icon: '🪑', count: 0, lowStock: 0 },
      { id: 'beds',     name: 'Кровати',          icon: '🛏️', count: 0, lowStock: 0 },
      { id: 'storage',  name: 'Шкафы и стеллажи', icon: '🗄️', count: 0, lowStock: 0 },
      { id: 'hardware', name: 'Фурнитура',        icon: '🔩', count: 0, lowStock: 0 },
    ],
    extraColumns: [
      { key: 'dimensions', label: 'Размеры (Д×Ш×В)' },
      { key: 'material', label: 'Материал' },
    ],
    quickActions: [
      { key: 'stock-in',   label: 'Приход товара',   icon: '📦' },
      { key: 'transfer',   label: 'Перемещение',     icon: '🔄' },
      { key: 'audit',      label: 'Инвентаризация',  icon: '📋' },
      { key: 'export',     label: 'Экспорт XLSX',    icon: '📥' },
    ],
    kpiLabels: { totalItems: 'SKU на складе', lowStock: 'Заканчиваются', incoming: 'В доставке' },
  },

  // ── FITNESS ──────────────────────────────
  fitness: {
    label: 'Склад клуба', icon: '💪',
    itemLabel: 'Позиция', itemLabelPlural: 'Позиции',
    unitDefault: 'шт.', hasExpiry: true,
    categories: [
      { id: 'equipment',  name: 'Оборудование',   icon: '🏋️', count: 0, lowStock: 0 },
      { id: 'supplements', name: 'Спорт-питание', icon: '🥤', count: 0, lowStock: 0 },
      { id: 'apparel',    name: 'Форма и одежда', icon: '👕', count: 0, lowStock: 0 },
      { id: 'accessories', name: 'Аксессуары',    icon: '🧤', count: 0, lowStock: 0 },
      { id: 'cleaning',   name: 'Уборочные средства', icon: '🧹', count: 0, lowStock: 0 },
    ],
    extraColumns: [
      { key: 'zone', label: 'Зона зала' },
    ],
    quickActions: [
      { key: 'stock-in',   label: 'Приход на склад', icon: '📦' },
      { key: 'write-off',  label: 'Списать',         icon: '🗑️' },
      { key: 'audit',      label: 'Инвентаризация',  icon: '📋' },
      { key: 'export',     label: 'Экспорт XLSX',    icon: '📥' },
    ],
    kpiLabels: { totalItems: 'Позиций на складе', lowStock: 'Заканчиваются', incoming: 'Ожидается' },
  },

  // ── TRAVEL ───────────────────────────────
  travel: {
    label: 'Склад материалов', icon: '✈️',
    itemLabel: 'Материал', itemLabelPlural: 'Материалы',
    unitDefault: 'шт.', hasExpiry: false,
    categories: [
      { id: 'promo',     name: 'Промо-материалы', icon: '📰', count: 0, lowStock: 0 },
      { id: 'luggage',   name: 'Багажные бирки',  icon: '🏷️', count: 0, lowStock: 0 },
      { id: 'guides',    name: 'Путеводители',    icon: '📖', count: 0, lowStock: 0 },
      { id: 'equipment', name: 'Туристическое оборудование', icon: '🎒', count: 0, lowStock: 0 },
    ],
    extraColumns: [],
    quickActions: [
      { key: 'stock-in',   label: 'Приход материалов', icon: '📦' },
      { key: 'write-off',  label: 'Списать',           icon: '🗑️' },
      { key: 'audit',      label: 'Инвентаризация',    icon: '📋' },
      { key: 'export',     label: 'Экспорт XLSX',      icon: '📥' },
    ],
    kpiLabels: { totalItems: 'Позиций на складе', lowStock: 'Заканчиваются', incoming: 'Ожидается' },
  },

  // ── DEFAULT ──────────────────────────────
  default: {
    label: 'Склад', icon: '📦',
    itemLabel: 'Товар', itemLabelPlural: 'Товары',
    unitDefault: 'шт.', hasExpiry: false,
    categories: [
      { id: 'general',    name: 'Общие товары', icon: '📦', count: 0, lowStock: 0 },
      { id: 'consumables', name: 'Расходники', icon: '🧴', count: 0, lowStock: 0 },
      { id: 'equipment',  name: 'Оборудование', icon: '🔧', count: 0, lowStock: 0 },
    ],
    extraColumns: [],
    quickActions: [
      { key: 'stock-in',   label: 'Приход товара',   icon: '📦' },
      { key: 'write-off',  label: 'Списать',         icon: '🗑️' },
      { key: 'audit',      label: 'Инвентаризация',  icon: '📋' },
      { key: 'export',     label: 'Экспорт XLSX',    icon: '📥' },
    ],
    kpiLabels: { totalItems: 'Позиций на складе', lowStock: 'Заканчиваются', incoming: 'В пути' },
  },
}

const vi = computed<VerticalInventoryConfig>(() =>
  VERTICAL_INVENTORY_CONFIG[props.vertical] ?? VERTICAL_INVENTORY_CONFIG.default
)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STOCK LEVEL MAP
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const STOCK_LEVEL_MAP: Record<StockLevel, { label: string; color: string; dot: string; bg: string }> = {
  normal:       { label: 'В норме',        color: 'text-emerald-400', dot: 'bg-emerald-400', bg: 'bg-emerald-500/12' },
  low:          { label: 'Низкий остаток',  color: 'text-amber-400',   dot: 'bg-amber-400',   bg: 'bg-amber-500/12' },
  critical:     { label: 'Критический',     color: 'text-rose-400',    dot: 'bg-rose-400',    bg: 'bg-rose-500/12' },
  out_of_stock: { label: 'Нет в наличии',   color: 'text-zinc-400',    dot: 'bg-zinc-500',    bg: 'bg-zinc-500/12' },
}

const MOVEMENT_TYPE_MAP: Record<MovementType, { label: string; icon: string; color: string }> = {
  in:          { label: 'Приход',        icon: '📥', color: 'text-emerald-400' },
  out:         { label: 'Расход',        icon: '📤', color: 'text-rose-400' },
  reserve:     { label: 'Резерв',        icon: '🔒', color: 'text-sky-400' },
  release:     { label: 'Снятие резерва', icon: '🔓', color: 'text-indigo-400' },
  return:      { label: 'Возврат',       icon: '↩️', color: 'text-violet-400' },
  adjustment:  { label: 'Корректировка', icon: '✏️', color: 'text-amber-400' },
  write_off:   { label: 'Списание',      icon: '🗑️', color: 'text-zinc-400' },
}

const AUDIT_STATUS_MAP: Record<AuditStatus, { label: string; color: string }> = {
  planned:       { label: 'Запланирована', color: 'text-sky-400' },
  in_progress:   { label: 'В процессе',    color: 'text-amber-400' },
  completed:     { label: 'Завершена',     color: 'text-emerald-400' },
  discrepancy:   { label: 'Расхождения',   color: 'text-rose-400' },
}

const ALL_STOCK_LEVELS: StockLevel[] = ['normal', 'low', 'critical', 'out_of_stock']

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  STATE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const rootEl            = ref<HTMLElement | null>(null)
const scrollSentinel    = ref<HTMLElement | null>(null)
const isFullscreen      = ref(false)
const showSidebar       = ref(true)
const showItemModal     = ref(false)
const showExportMenu    = ref(false)
const showFilterDrawer  = ref(false)
const showActionsMenu   = ref(false)
const selectedItem      = ref<InventoryItem | null>(null)
const currentPage       = ref(1)
const modalTab          = ref<'info' | 'movements' | 'audit'>('info')
const activeTab         = ref<'items' | 'movements' | 'audits'>('items')

// Bulk
const selectedIds = reactive<Set<number | string>>(new Set())
const isBulkMode  = ref(false)
const selectAll   = ref(false)

// View
const viewAs = ref<'table' | 'cards'>('table')

// Filters
const filters = reactive<InventoryFilter>({
  search:      '',
  category:    '',
  stockLevel:  '',
  warehouse:   '',
  supplier:    '',
  sortBy:      'name',
  sortDir:     'asc',
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  COMPUTED
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

const totalPages = computed(() => Math.ceil(props.totalItems / props.perPage) || 1)

const hasActiveFilters = computed(() =>
  filters.category !== '' ||
  filters.stockLevel !== '' ||
  filters.warehouse !== '' ||
  filters.supplier !== ''
)

const stockLevelCounts = computed<Record<StockLevel, number>>(() => {
  const map: Record<StockLevel, number> = { normal: 0, low: 0, critical: 0, out_of_stock: 0 }
  for (const item of props.items) {
    if (item.stockLevel in map) map[item.stockLevel]++
  }
  return map
})

/** Фильтрация на клиенте (живой поиск) */
const filteredItems = computed(() => {
  let result = [...props.items]
  const q = filters.search.toLowerCase().trim()
  if (q) {
    result = result.filter(
      (i) =>
        i.name.toLowerCase().includes(q) ||
        i.sku.toLowerCase().includes(q) ||
        (i.barcode && i.barcode.toLowerCase().includes(q)) ||
        (i.supplier && i.supplier.toLowerCase().includes(q))
    )
  }
  if (filters.category)   result = result.filter((i) => i.categoryId === filters.category)
  if (filters.stockLevel) result = result.filter((i) => i.stockLevel === filters.stockLevel)
  if (filters.warehouse)  result = result.filter((i) => i.warehouseId === filters.warehouse)
  if (filters.supplier)   result = result.filter((i) => i.supplier === filters.supplier)
  // Sort
  const dir = filters.sortDir === 'asc' ? 1 : -1
  result.sort((a, b) => {
    const av = Object(a)[filters.sortBy]
    const bv = Object(b)[filters.sortBy]
    if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir
    return String(av ?? '').localeCompare(String(bv ?? ''), 'ru') * dir
  })
  return result
})

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * props.perPage
  return filteredItems.value.slice(start, start + props.perPage)
})

const criticalAlerts = computed(() =>
  props.alerts.filter((a) => a.severity === 'critical')
)

const categoriesWithCounts = computed(() => {
  const cats = (vi.value.categories.length ? vi.value.categories : props.categories).map((c) => ({ ...c }))
  for (const item of props.items) {
    const cat = cats.find((c) => c.id === item.categoryId)
    if (cat) {
      cat.count++
      if (item.stockLevel === 'low' || item.stockLevel === 'critical') cat.lowStock++
    }
  }
  return cats
})

const isAllSelected = computed(() =>
  filteredItems.value.length > 0 && filteredItems.value.every((i) => selectedIds.has(i.id))
)

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  FORMATTERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function fmtCurrency(v: number): string {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency', currency: 'RUB', maximumFractionDigits: 0,
  }).format(v)
}

function fmtNumber(v: number): string {
  return new Intl.NumberFormat('ru-RU').format(v)
}

function fmtDate(iso: string): string {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('ru-RU', {
    day: 'numeric', month: 'short', year: 'numeric',
  }).format(new Date(iso))
}

function fmtDateShort(iso: string): string {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('ru-RU', {
    day: 'numeric', month: 'short',
  }).format(new Date(iso))
}

function fmtPercent(v: number, total: number): string {
  if (total === 0) return '0%'
  return ((v / total) * 100).toFixed(1) + '%'
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  ACTIONS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function openItem(item: InventoryItem) {
  selectedItem.value = item
  modalTab.value     = 'info'
  showItemModal.value = true
  emit('item-click', item)
}

function closeItemModal() {
  showItemModal.value = false
  selectedItem.value  = null
}

function toggleFullscreen() {
  if (!rootEl.value) return
  if (!isFullscreen.value) {
    rootEl.value.requestFullscreen?.()
  } else {
    document.exitFullscreen?.()
  }
}

function handleFullscreenChange() {
  isFullscreen.value = !!document.fullscreenElement
}

function toggleSidebar() {
  showSidebar.value = !showSidebar.value
}

function setCategory(catId: string) {
  filters.category = filters.category === catId ? '' : catId
  currentPage.value = 1
  emit('category-change', catId)
  emit('filter-change', { ...filters })
}

function setStockFilter(level: string) {
  filters.stockLevel = filters.stockLevel === level ? '' : level
  currentPage.value = 1
  emit('filter-change', { ...filters })
}

function clearAllFilters() {
  filters.search     = ''
  filters.category   = ''
  filters.stockLevel = ''
  filters.warehouse  = ''
  filters.supplier   = ''
  currentPage.value  = 1
  emit('filter-change', { ...filters })
}

function toggleSort(col: string) {
  if (filters.sortBy === col) {
    filters.sortDir = filters.sortDir === 'asc' ? 'desc' : 'asc'
  } else {
    filters.sortBy = col
    filters.sortDir = 'asc'
  }
  emit('sort-change', filters.sortBy, filters.sortDir)
}

function goPage(p: number) {
  if (p < 1 || p > totalPages.value) return
  currentPage.value = p
  emit('page-change', p)
}

// ── Bulk ──
function toggleSelectAll() {
  if (isAllSelected.value) {
    selectedIds.clear()
    selectAll.value = false
  } else {
    filteredItems.value.forEach((i) => selectedIds.add(i.id))
    selectAll.value = true
  }
}

function toggleItemSelect(id: number | string) {
  if (selectedIds.has(id)) selectedIds.delete(id)
  else selectedIds.add(id)
}

function executeBulkAction(action: string) {
  const ids = Array.from(selectedIds)
  emit('bulk-action', action, ids)
  selectedIds.clear()
  isBulkMode.value = false
}

// ── Quick actions ──
function handleQuickAction(key: string) {
  showActionsMenu.value = false
  switch (key) {
    case 'stock-in':  emit('stock-in'); break
    case 'write-off': emit('stock-out', Array.from(selectedIds)); break
    case 'audit':     emit('start-audit'); break
    case 'export':    emit('export', 'xlsx'); break
    case 'transfer':  emit('bulk-action', 'transfer', Array.from(selectedIds)); break
    case 'return':    emit('bulk-action', 'return', Array.from(selectedIds)); break
    default:          emit('bulk-action', key, Array.from(selectedIds))
  }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  KEYBOARD
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    if (showItemModal.value) { closeItemModal(); return }
    if (showFilterDrawer.value) { showFilterDrawer.value = false; return }
    if (isFullscreen.value) { toggleFullscreen(); return }
  }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  WATCHERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(() => filters.search, () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    currentPage.value = 1
    emit('filter-change', { ...filters })
  }, 300)
})

// ── Responsive view ──
function checkViewport() {
  viewAs.value = window.innerWidth < 768 ? 'cards' : 'table'
  if (window.innerWidth < 1024) showSidebar.value = false
  else showSidebar.value = true
}

// ── Infinite scroll (optional) ──
let io: IntersectionObserver | null = null

function setupInfiniteScroll() {
  if (!scrollSentinel.value) return
  io = new IntersectionObserver(
    (entries) => {
      if (entries[0]?.isIntersecting && !props.loading && currentPage.value < totalPages.value) {
        emit('load-more')
      }
    },
    { threshold: 0.1 }
  )
  io.observe(scrollSentinel.value)
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  LIFECYCLE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

onMounted(() => {
  document.addEventListener('keydown', onKeydown)
  document.addEventListener('fullscreenchange', handleFullscreenChange)
  window.addEventListener('resize', checkViewport)
  checkViewport()
  nextTick(() => setupInfiniteScroll())
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  document.removeEventListener('fullscreenchange', handleFullscreenChange)
  window.removeEventListener('resize', checkViewport)
  if (debounceTimer) clearTimeout(debounceTimer)
  io?.disconnect()
})

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  RIPPLE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

function ripple(e: MouseEvent) {
  const target = e.currentTarget as HTMLElement
  const rect   = target.getBoundingClientRect()
  const d      = Math.max(rect.width, rect.height) * 2
  const el     = document.createElement('span')
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-iv_0.6s_ease-out]'
  el.style.cssText = [
    `inline-size:${d}px`,
    `block-size:${d}px`,
    `inset-inline-start:${e.clientX - rect.left - d / 2}px`,
    `inset-block-start:${e.clientY - rect.top - d / 2}px`,
  ].join(';')
  target.appendChild(el)
  setTimeout(() => el.remove(), 650)
}
</script>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     TEMPLATE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<template>
  <div
    ref="rootEl"
    :class="[
      'relative flex flex-col bg-(--t-bg) text-(--t-text)',
      isFullscreen
        ? 'fixed inset-0 z-50 overflow-auto'
        : 'min-h-screen',
    ]"
  >
    <!-- ══════════════════════════════════════════════
         TOP BAR — Поиск + фильтры + действия
    ══════════════════════════════════════════════ -->
    <header class="sticky inset-block-start-0 z-30 bg-(--t-surface)/80 backdrop-blur-xl
                   border-b border-(--t-border)/60">
      <!-- ── Row 1: title + fullscreen ── -->
      <div class="flex items-center gap-3 px-4 pt-4 pb-2 sm:px-6">
        <!-- Sidebar toggle (mobile) -->
        <button
          class="lg:hidden relative overflow-hidden shrink-0 w-9 h-9 rounded-lg
                 border border-(--t-border)/50 bg-(--t-surface) flex items-center justify-center
                 text-(--t-text-2) hover:bg-(--t-card-hover) active:scale-95 transition-all"
          @click="toggleSidebar"
          @mousedown="ripple"
        >
          <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
          </svg>
        </button>

        <!-- Title -->
        <div class="flex items-center gap-2 flex-1 min-w-0">
          <span class="text-xl">{{ vi.icon }}</span>
          <h1 class="text-base sm:text-lg font-bold text-(--t-text) truncate">
            {{ vi.label }}
          </h1>
          <VBadge v-if="props.stats.lowStockCount > 0" variant="warning" size="sm">
            {{ props.stats.lowStockCount }} ⚠️
          </VBadge>
        </div>

        <!-- Fullscreen -->
        <button
          class="relative overflow-hidden shrink-0 w-9 h-9 rounded-lg border border-(--t-border)/50
                 bg-(--t-surface) flex items-center justify-center text-(--t-text-2)
                 hover:bg-(--t-card-hover) active:scale-95 transition-all"
          @click="toggleFullscreen"
          @mousedown="ripple"
          :title="isFullscreen ? 'Свернуть' : 'На весь экран'"
        >
          <svg v-if="!isFullscreen" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
          </svg>
          <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5m-4.5 0v4.5m0-4.5 5.25 5.25" />
          </svg>
        </button>
      </div>

      <!-- ── Row 2: search + filters + buttons ── -->
      <div class="flex flex-wrap items-center gap-2 px-4 pb-3 sm:px-6">
        <!-- Search -->
        <div class="relative flex-1 min-w-50 max-w-sm">
          <svg class="absolute inset-inline-start-3 inset-block-start-1/2 -translate-y-1/2 w-4 h-4
                      text-(--t-text-3) pointer-events-none"
               fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
          </svg>
          <input
            v-model="filters.search"
            type="text"
            :placeholder="`Поиск ${vi.itemLabelPlural.toLowerCase()}…`"
            class="inline-size-full py-2 ps-9 pe-3 text-sm rounded-xl
                   bg-(--t-bg)/60 border border-(--t-border)/50 text-(--t-text)
                   placeholder:text-(--t-text-3) focus:border-(--t-primary)/60
                   focus:ring-1 focus:ring-(--t-primary)/30 outline-none transition-all"
          />
        </div>

        <!-- Stock level filter chips -->
        <div class="hidden sm:flex items-center gap-1.5">
          <button
            v-for="sl in ALL_STOCK_LEVELS"
            :key="sl"
            :class="[
              'relative overflow-hidden text-xs px-2.5 py-1.5 rounded-lg border transition-all',
              'active:scale-95',
              filters.stockLevel === sl
                ? `${STOCK_LEVEL_MAP[sl].bg} ${STOCK_LEVEL_MAP[sl].color} border-transparent font-semibold`
                : 'border-(--t-border)/50 text-(--t-text-3) hover:text-(--t-text) hover:border-(--t-text-3)/40',
            ]"
            @click="setStockFilter(sl)"
            @mousedown="ripple"
          >
            <span class="inline-block w-1.5 h-1.5 rounded-full me-1"
                  :class="STOCK_LEVEL_MAP[sl].dot" />
            {{ STOCK_LEVEL_MAP[sl].label }}
            <span v-if="stockLevelCounts[sl] > 0" class="ms-1 opacity-60">
              {{ stockLevelCounts[sl] }}
            </span>
          </button>
        </div>

        <!-- Clear filters -->
        <button
          v-if="hasActiveFilters"
          class="text-xs text-(--t-text-3) hover:text-(--t-text) transition-colors underline
                 underline-offset-2 decoration-dotted"
          @click="clearAllFilters"
        >
          Сбросить
        </button>

        <!-- Spacer -->
        <div class="flex-1" />

        <!-- Filter drawer (mobile) -->
        <button
          class="sm:hidden relative overflow-hidden shrink-0 w-9 h-9 rounded-lg
                 border border-(--t-border)/50 bg-(--t-surface) flex items-center justify-center
                 text-(--t-text-2) hover:bg-(--t-card-hover) active:scale-95 transition-all"
          @click="showFilterDrawer = true"
          @mousedown="ripple"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
          </svg>
          <span
            v-if="hasActiveFilters"
            class="absolute inset-block-start-0 inset-inline-end-0 w-2 h-2
                   bg-(--t-primary) rounded-full ring-2 ring-(--t-surface)"
          />
        </button>

        <!-- Stock-in button -->
        <button
          class="relative overflow-hidden inline-flex items-center gap-1.5 px-3 py-2
                 rounded-xl text-xs font-semibold bg-emerald-500/12 text-emerald-400
                 border border-emerald-500/20 hover:bg-emerald-500/20 active:scale-[0.97]
                 transition-all"
          @click="emit('stock-in')"
          @mousedown="ripple"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          <span class="hidden sm:inline">Приход {{ vi.itemLabel.toLowerCase() }}а</span>
          <span class="sm:hidden">Приход</span>
        </button>

        <!-- Audit button -->
        <button
          class="relative overflow-hidden inline-flex items-center gap-1.5 px-3 py-2
                 rounded-xl text-xs font-semibold bg-(--t-primary)/12 text-(--t-primary)
                 border border-(--t-primary)/20 hover:bg-(--t-primary)/20 active:scale-[0.97]
                 transition-all"
          @click="emit('start-audit')"
          @mousedown="ripple"
        >
          📋
          <span class="hidden sm:inline">Инвентаризация</span>
        </button>

        <!-- Quick actions dropdown -->
        <div class="relative">
          <button
            class="relative overflow-hidden w-9 h-9 rounded-lg border border-(--t-border)/50
                   bg-(--t-surface) flex items-center justify-center text-(--t-text-2)
                   hover:bg-(--t-card-hover) active:scale-95 transition-all"
            @click="showActionsMenu = !showActionsMenu"
            @mousedown="ripple"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
            </svg>
          </button>
          <Transition name="dropdown-iv">
            <div
              v-if="showActionsMenu"
              class="absolute inset-inline-end-0 inset-block-start-full mt-1 z-40
                     inline-size-52 rounded-xl border border-(--t-border)/60 bg-(--t-surface)/95
                     backdrop-blur-xl shadow-xl overflow-hidden"
            >
              <button
                v-for="a in vi.quickActions"
                :key="a.key"
                class="relative overflow-hidden inline-size-full flex items-center gap-2
                       px-3 py-2.5 text-xs text-(--t-text-2) hover:bg-(--t-card-hover)
                       hover:text-(--t-text) transition-colors"
                @click="handleQuickAction(a.key)"
                @mousedown="ripple"
              >
                <span class="shrink-0">{{ a.icon }}</span>
                {{ a.label }}
              </button>
            </div>
          </Transition>
        </div>
      </div>

      <!-- ── Tabs ── -->
      <div class="flex items-center gap-1 px-4 sm:px-6 pb-0 border-b border-(--t-border)/30">
        <button
          v-for="t in [
            { key: 'items',     label: vi.itemLabelPlural, count: props.totalItems },
            { key: 'movements', label: 'Движения',          count: props.movements.length },
            { key: 'audits',    label: 'Ревизии',           count: props.audits.length },
          ] as const"
          :key="t.key"
          :class="[
            'relative px-3 py-2.5 text-xs font-medium transition-colors',
            activeTab === t.key
              ? 'text-(--t-primary) border-b-2 border-(--t-primary)'
              : 'text-(--t-text-3) hover:text-(--t-text)',
          ]"
          @click="activeTab = t.key as typeof activeTab"
        >
          {{ t.label }}
          <span v-if="t.count > 0" class="ms-1 text-[10px] opacity-60">({{ t.count }})</span>
        </button>
      </div>
    </header>

    <!-- ══════════════════════════════════════════════
         KPI WIDGETS
    ══════════════════════════════════════════════ -->
    <section class="px-4 sm:px-6 pt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
      <!-- Total SKU -->
      <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm
                  p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">{{ vi.kpiLabels.totalItems }}</span>
        <span class="text-lg font-bold text-(--t-text)">{{ fmtNumber(props.stats.totalSku) }}</span>
        <div class="flex items-center gap-1 text-[10px] text-(--t-text-3)">
          📦 {{ fmtNumber(props.stats.totalQuantity) }} {{ vi.unitDefault }}
        </div>
      </div>

      <!-- Total value -->
      <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm
                  p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">Себестоимость</span>
        <span class="text-lg font-bold text-(--t-text)">{{ fmtCurrency(props.stats.totalValue) }}</span>
        <div class="flex items-center gap-1 text-[10px] text-(--t-text-3)">
          💰 на складе
        </div>
      </div>

      <!-- Low stock -->
      <div :class="[
        'rounded-xl border p-3 flex flex-col gap-1 backdrop-blur-sm',
        props.stats.lowStockCount > 0
          ? 'border-amber-500/30 bg-amber-500/5'
          : 'border-(--t-border)/50 bg-(--t-surface)/60',
      ]">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">{{ vi.kpiLabels.lowStock }}</span>
        <span :class="[
          'text-lg font-bold',
          props.stats.lowStockCount > 0 ? 'text-amber-400' : 'text-(--t-text)',
        ]">
          {{ props.stats.lowStockCount }}
        </span>
        <div class="flex items-center gap-1 text-[10px] text-(--t-text-3)">
          ⚠️ требуют пополнения
        </div>
      </div>

      <!-- Out of stock -->
      <div :class="[
        'rounded-xl border p-3 flex flex-col gap-1 backdrop-blur-sm',
        props.stats.outOfStockCount > 0
          ? 'border-rose-500/30 bg-rose-500/5'
          : 'border-(--t-border)/50 bg-(--t-surface)/60',
      ]">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">Нет в наличии</span>
        <span :class="[
          'text-lg font-bold',
          props.stats.outOfStockCount > 0 ? 'text-rose-400' : 'text-(--t-text)',
        ]">
          {{ props.stats.outOfStockCount }}
        </span>
        <div class="flex items-center gap-1 text-[10px] text-(--t-text-3)">
          🚫 отсутствуют
        </div>
      </div>

      <!-- Incoming -->
      <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm
                  p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">{{ vi.kpiLabels.incoming }}</span>
        <span class="text-lg font-bold text-sky-400">{{ props.stats.incomingCount }}</span>
        <div class="flex items-center gap-1 text-[10px] text-(--t-text-3)">
          🚚 {{ vi.itemLabelPlural.toLowerCase() }}
        </div>
      </div>

      <!-- Reserved -->
      <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60 backdrop-blur-sm
                  p-3 flex flex-col gap-1">
        <span class="text-[10px] uppercase tracking-wider text-(--t-text-3)">В резерве</span>
        <span class="text-lg font-bold text-indigo-400">{{ fmtNumber(props.stats.reservedTotal) }}</span>
        <div class="flex items-center gap-1 text-[10px] text-(--t-text-3)">
          🔒 зарезервировано
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════════════
         BULK BAR
    ══════════════════════════════════════════════ -->
    <Transition name="slide-iv">
      <div
        v-if="selectedIds.size > 0"
        class="mx-4 sm:mx-6 mt-3 flex items-center gap-2 rounded-xl
               border border-(--t-primary)/30 bg-(--t-primary)/8 px-4 py-2.5"
      >
        <span class="text-xs font-medium text-(--t-text)">
          Выбрано: {{ selectedIds.size }}
        </span>
        <div class="flex-1" />
        <button
          class="relative overflow-hidden text-xs px-2.5 py-1.5 rounded-lg
                 bg-rose-500/12 text-rose-400 border border-rose-500/20
                 hover:bg-rose-500/20 active:scale-95 transition-all"
          @click="executeBulkAction('write-off')"
          @mousedown="ripple"
        >
          🗑️ Списать
        </button>
        <button
          class="relative overflow-hidden text-xs px-2.5 py-1.5 rounded-lg
                 bg-sky-500/12 text-sky-400 border border-sky-500/20
                 hover:bg-sky-500/20 active:scale-95 transition-all"
          @click="executeBulkAction('transfer')"
          @mousedown="ripple"
        >
          🔄 Переместить
        </button>
        <button
          class="text-xs text-(--t-text-3) hover:text-(--t-text) transition-colors"
          @click="selectedIds.clear()"
        >
          Отменить
        </button>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════════════
         MAIN AREA — Sidebar + Content
    ══════════════════════════════════════════════ -->
    <div class="flex-1 flex overflow-hidden px-4 sm:px-6 py-4 gap-4">

      <!-- ─── SIDEBAR ─── -->
      <Transition name="sidebar-iv">
        <aside
          v-if="showSidebar"
          class="hidden lg:flex shrink-0 flex-col gap-3 overflow-y-auto"
          style="inline-size: 240px"
        >
          <!-- Categories -->
          <div class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60
                      backdrop-blur-sm p-3">
            <h3 class="text-xs font-semibold text-(--t-text-2) mb-2">Категории</h3>
            <div class="flex flex-col gap-0.5">
              <button
                v-for="cat in categoriesWithCounts"
                :key="cat.id"
                :class="[
                  'relative overflow-hidden flex items-center gap-2 px-2.5 py-2 rounded-lg',
                  'text-xs transition-all active:scale-[0.97]',
                  filters.category === cat.id
                    ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold'
                    : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                ]"
                @click="setCategory(cat.id)"
                @mousedown="ripple"
              >
                <span class="shrink-0">{{ cat.icon }}</span>
                <span class="flex-1 truncate text-start">{{ cat.name }}</span>
                <span class="shrink-0 text-[10px] opacity-60">{{ cat.count }}</span>
                <span
                  v-if="cat.lowStock > 0"
                  class="shrink-0 w-1.5 h-1.5 rounded-full bg-amber-400"
                />
              </button>
            </div>
          </div>

          <!-- Warehouses -->
          <div
            v-if="props.warehouses.length > 1"
            class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60
                   backdrop-blur-sm p-3"
          >
            <h3 class="text-xs font-semibold text-(--t-text-2) mb-2">Склады</h3>
            <div class="flex flex-col gap-0.5">
              <button
                v-for="wh in props.warehouses"
                :key="wh.id"
                :class="[
                  'relative overflow-hidden flex items-center gap-2 px-2.5 py-2 rounded-lg',
                  'text-xs transition-all active:scale-[0.97]',
                  filters.warehouse === wh.id
                    ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold'
                    : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                ]"
                @click="filters.warehouse = filters.warehouse === wh.id ? '' : wh.id; emit('filter-change', { ...filters })"
                @mousedown="ripple"
              >
                🏭
                <span class="flex-1 truncate text-start">{{ wh.name }}</span>
              </button>
            </div>
          </div>

          <!-- Alerts -->
          <div
            v-if="props.alerts.length > 0"
            class="rounded-xl border border-amber-500/30 bg-amber-500/5 p-3"
          >
            <h3 class="text-xs font-semibold text-amber-400 mb-2">
              ⚠️ Оповещения ({{ props.alerts.length }})
            </h3>
            <div class="flex flex-col gap-1.5 max-h-48 overflow-y-auto">
              <div
                v-for="al in props.alerts.slice(0, 8)"
                :key="al.id"
                class="flex items-start gap-2 text-[11px] text-(--t-text-2)"
              >
                <span :class="[
                  'shrink-0 w-1.5 h-1.5 rounded-full mt-1',
                  al.severity === 'critical' ? 'bg-rose-400' : 'bg-amber-400',
                ]" />
                <div class="flex-1 min-w-0">
                  <p class="font-medium truncate">{{ al.itemName }}</p>
                  <p class="text-(--t-text-3) truncate">{{ al.message }}</p>
                </div>
                <button
                  class="shrink-0 text-(--t-text-3) hover:text-(--t-text) transition-colors"
                  @click="emit('alert-dismiss', al.id)"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>

          <!-- Latest audit -->
          <div
            v-if="props.audits.length > 0"
            class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60
                   backdrop-blur-sm p-3"
          >
            <h3 class="text-xs font-semibold text-(--t-text-2) mb-2">Последняя ревизия</h3>
            <div class="text-xs text-(--t-text-2) space-y-1">
              <p>
                <span class="text-(--t-text-3)">Статус:</span>
                <span :class="AUDIT_STATUS_MAP[props.audits[0].status]?.color">
                  {{ AUDIT_STATUS_MAP[props.audits[0].status]?.label }}
                </span>
              </p>
              <p>
                <span class="text-(--t-text-3)">Склад:</span>
                {{ props.audits[0].warehouseName }}
              </p>
              <p>
                <span class="text-(--t-text-3)">Начало:</span>
                {{ fmtDate(props.audits[0].startedAt) }}
              </p>
              <p v-if="props.audits[0].discrepancies > 0" class="text-rose-400 font-medium">
                Расхождений: {{ props.audits[0].discrepancies }}
              </p>
            </div>
          </div>
        </aside>
      </Transition>

      <!-- ─── CONTENT ─── -->
      <main class="flex-1 min-w-0 flex flex-col gap-4">

        <!-- ═══════════════════════════════════
             TAB: ITEMS
        ═══════════════════════════════════ -->
        <template v-if="activeTab === 'items'">

          <!-- Loading skeleton -->
          <div v-if="props.loading && props.items.length === 0" class="flex flex-col gap-3">
            <div v-for="n in 6" :key="n"
                 class="h-16 rounded-xl bg-(--t-surface)/60 animate-pulse" />
          </div>

          <!-- Empty state -->
          <div
            v-else-if="filteredItems.length === 0 && !props.loading"
            class="flex flex-col items-center justify-center py-16 text-center"
          >
            <span class="text-4xl mb-3">📦</span>
            <p class="text-sm font-medium text-(--t-text-2)">
              {{ filters.search || hasActiveFilters ? 'Ничего не найдено' : `${vi.itemLabelPlural} не добавлены` }}
            </p>
            <p class="text-xs text-(--t-text-3) mt-1 max-w-xs">
              {{ filters.search || hasActiveFilters
                ? 'Попробуйте изменить параметры поиска или сбросить фильтры'
                : `Добавьте первый ${vi.itemLabel.toLowerCase()} через кнопку «Приход»`
              }}
            </p>
            <button
              v-if="filters.search || hasActiveFilters"
              class="mt-3 text-xs text-(--t-primary) hover:underline"
              @click="clearAllFilters"
            >
              Сбросить фильтры
            </button>
          </div>

          <!-- ── TABLE VIEW (desktop) ── -->
          <div
            v-else-if="viewAs === 'table'"
            class="rounded-xl border border-(--t-border)/50 bg-(--t-surface)/40 backdrop-blur-sm
                   overflow-x-auto"
          >
            <table class="inline-size-full text-xs">
              <thead>
                <tr class="border-b border-(--t-border)/40">
                  <!-- Checkbox -->
                  <th class="ps-3 py-2.5 inline-size-8">
                    <input
                      type="checkbox"
                      :checked="isAllSelected"
                      class="w-3.5 h-3.5 rounded border-zinc-600 bg-zinc-800 accent-(--t-primary)"
                      @change="toggleSelectAll"
                    />
                  </th>
                  <!-- Image -->
                  <th class="px-2 py-2.5 inline-size-10" />
                  <!-- Name -->
                  <th
                    class="px-2 py-2.5 text-start font-medium text-(--t-text-3) cursor-pointer
                           hover:text-(--t-text) select-none"
                    @click="toggleSort('name')"
                  >
                    <span class="flex items-center gap-1">
                      {{ vi.itemLabel }}
                      <svg v-if="filters.sortBy === 'name'" class="w-3 h-3"
                           :class="filters.sortDir === 'asc' ? '' : 'rotate-180'"
                           fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                      </svg>
                    </span>
                  </th>
                  <!-- SKU -->
                  <th class="px-2 py-2.5 text-start font-medium text-(--t-text-3) hidden lg:table-cell">
                    SKU
                  </th>
                  <!-- Category -->
                  <th class="px-2 py-2.5 text-start font-medium text-(--t-text-3) hidden xl:table-cell">
                    Категория
                  </th>
                  <!-- Quantity -->
                  <th
                    class="px-2 py-2.5 text-end font-medium text-(--t-text-3) cursor-pointer
                           hover:text-(--t-text) select-none"
                    @click="toggleSort('quantity')"
                  >
                    <span class="flex items-center justify-end gap-1">
                      Остаток
                      <svg v-if="filters.sortBy === 'quantity'" class="w-3 h-3"
                           :class="filters.sortDir === 'asc' ? '' : 'rotate-180'"
                           fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                      </svg>
                    </span>
                  </th>
                  <!-- Reserved -->
                  <th class="px-2 py-2.5 text-end font-medium text-(--t-text-3) hidden md:table-cell">
                    Резерв
                  </th>
                  <!-- Cost -->
                  <th
                    class="px-2 py-2.5 text-end font-medium text-(--t-text-3) hidden lg:table-cell
                           cursor-pointer hover:text-(--t-text) select-none"
                    @click="toggleSort('costPrice')"
                  >
                    <span class="flex items-center justify-end gap-1">
                      Цена
                      <svg v-if="filters.sortBy === 'costPrice'" class="w-3 h-3"
                           :class="filters.sortDir === 'asc' ? '' : 'rotate-180'"
                           fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                      </svg>
                    </span>
                  </th>
                  <!-- Status -->
                  <th class="px-2 py-2.5 text-center font-medium text-(--t-text-3)">
                    Статус
                  </th>
                  <!-- Extra columns -->
                  <th
                    v-for="ec in vi.extraColumns"
                    :key="ec.key"
                    class="px-2 py-2.5 text-start font-medium text-(--t-text-3) hidden xl:table-cell"
                  >
                    {{ ec.label }}
                  </th>
                  <!-- Actions -->
                  <th class="px-2 py-2.5 inline-size-10" />
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in paginatedItems"
                  :key="item.id"
                  :class="[
                    'border-b border-(--t-border)/20 hover:bg-(--t-card-hover)/50',
                    'transition-colors cursor-pointer',
                    selectedIds.has(item.id) ? 'bg-(--t-primary)/5' : '',
                    item.stockLevel === 'out_of_stock' ? 'opacity-50 grayscale' : '',
                  ]"
                  @click="openItem(item)"
                >
                  <!-- Checkbox -->
                  <td class="ps-3 py-2.5" @click.stop>
                    <input
                      type="checkbox"
                      :checked="selectedIds.has(item.id)"
                      class="w-3.5 h-3.5 rounded border-zinc-600 bg-zinc-800 accent-(--t-primary)"
                      @change="toggleItemSelect(item.id)"
                    />
                  </td>
                  <!-- Image -->
                  <td class="px-2 py-2.5">
                    <div class="w-8 h-8 rounded-lg bg-(--t-bg)/60 border border-(--t-border)/30
                                flex items-center justify-center overflow-hidden">
                      <img v-if="item.image" :src="item.image" :alt="item.name"
                           class="w-full h-full object-cover" />
                      <span v-else class="text-sm">📦</span>
                    </div>
                  </td>
                  <!-- Name -->
                  <td class="px-2 py-2.5">
                    <p class="font-medium text-(--t-text) truncate max-w-50">{{ item.name }}</p>
                    <p class="text-[10px] text-(--t-text-3) truncate">{{ item.supplier || '—' }}</p>
                  </td>
                  <!-- SKU -->
                  <td class="px-2 py-2.5 text-(--t-text-3) font-mono hidden lg:table-cell">
                    {{ item.sku }}
                  </td>
                  <!-- Category -->
                  <td class="px-2 py-2.5 text-(--t-text-2) hidden xl:table-cell">
                    {{ item.category }}
                  </td>
                  <!-- Quantity -->
                  <td class="px-2 py-2.5 text-end">
                    <span :class="[
                      'font-semibold',
                      item.stockLevel === 'critical' ? 'text-rose-400' :
                      item.stockLevel === 'low' ? 'text-amber-400' :
                      item.stockLevel === 'out_of_stock' ? 'text-zinc-500' : 'text-(--t-text)',
                    ]">
                      {{ fmtNumber(item.available) }}
                    </span>
                    <span class="text-(--t-text-3)"> / {{ fmtNumber(item.quantity) }}</span>
                    <span class="text-(--t-text-3) ms-0.5">{{ item.unit }}</span>
                  </td>
                  <!-- Reserved -->
                  <td class="px-2 py-2.5 text-end text-(--t-text-3) hidden md:table-cell">
                    {{ item.reserved > 0 ? fmtNumber(item.reserved) : '—' }}
                  </td>
                  <!-- Cost price -->
                  <td class="px-2 py-2.5 text-end text-(--t-text-2) hidden lg:table-cell">
                    {{ fmtCurrency(item.costPrice) }}
                  </td>
                  <!-- Status -->
                  <td class="px-2 py-2.5 text-center">
                    <span :class="[
                      'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium',
                      STOCK_LEVEL_MAP[item.stockLevel].bg,
                      STOCK_LEVEL_MAP[item.stockLevel].color,
                    ]">
                      <span class="w-1.5 h-1.5 rounded-full"
                            :class="STOCK_LEVEL_MAP[item.stockLevel].dot" />
                      {{ STOCK_LEVEL_MAP[item.stockLevel].label }}
                    </span>
                  </td>
                  <!-- Extra columns -->
                  <td
                    v-for="ec in vi.extraColumns"
                    :key="ec.key"
                    class="px-2 py-2.5 text-(--t-text-3) hidden xl:table-cell"
                  >
                    {{ (item.verticalData as Record<string, unknown>)?.[ec.key] ?? '—' }}
                  </td>
                  <!-- Actions -->
                  <td class="px-2 py-2.5" @click.stop>
                    <button
                      class="w-7 h-7 rounded-md flex items-center justify-center
                             text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)
                             transition-colors"
                      @click="emit('stock-adjust', item)"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                           stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                      </svg>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- ── CARDS VIEW (mobile) ── -->
          <div
            v-else
            class="flex flex-col gap-2.5"
          >
            <div
              v-for="item in paginatedItems"
              :key="item.id"
              :class="[
                'rounded-xl border border-(--t-border)/50 bg-(--t-surface)/60',
                'backdrop-blur-sm p-3 transition-all active:scale-[0.99] cursor-pointer',
                'hover:border-(--t-border)',
                item.stockLevel === 'out_of_stock' ? 'opacity-50 grayscale' : '',
              ]"
              @click="openItem(item)"
            >
              <div class="flex items-start gap-3">
                <!-- checkbox -->
                <input
                  type="checkbox"
                  :checked="selectedIds.has(item.id)"
                  class="mt-1 w-3.5 h-3.5 rounded border-zinc-600 bg-zinc-800 accent-(--t-primary) shrink-0"
                  @click.stop
                  @change="toggleItemSelect(item.id)"
                />

                <!-- image -->
                <div class="shrink-0 w-12 h-12 rounded-lg bg-(--t-bg)/60 border border-(--t-border)/30
                            flex items-center justify-center overflow-hidden">
                  <img v-if="item.image" :src="item.image" :alt="item.name"
                       class="w-full h-full object-cover" />
                  <span v-else class="text-lg">📦</span>
                </div>

                <!-- info -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-1.5">
                    <p class="text-sm font-semibold text-(--t-text) truncate">{{ item.name }}</p>
                    <span :class="[
                      'shrink-0 w-2 h-2 rounded-full',
                      STOCK_LEVEL_MAP[item.stockLevel].dot,
                    ]" />
                  </div>
                  <p class="text-[10px] text-(--t-text-3) mt-0.5">
                    {{ item.sku }} · {{ item.category }}
                  </p>

                  <!-- qty row -->
                  <div class="flex items-center gap-3 mt-2">
                    <div class="text-xs">
                      <span class="text-(--t-text-3)">Остаток:</span>
                      <span :class="[
                        'font-semibold ms-1',
                        item.stockLevel === 'critical' ? 'text-rose-400' :
                        item.stockLevel === 'low' ? 'text-amber-400' : 'text-(--t-text)',
                      ]">
                        {{ fmtNumber(item.available) }}
                      </span>
                      <span class="text-(--t-text-3)"> / {{ fmtNumber(item.quantity) }} {{ item.unit }}</span>
                    </div>
                    <div v-if="item.reserved > 0" class="text-xs text-indigo-400">
                      🔒 {{ item.reserved }}
                    </div>
                  </div>

                  <!-- price row -->
                  <div class="flex items-center gap-3 mt-1 text-[10px] text-(--t-text-3)">
                    <span>💰 {{ fmtCurrency(item.costPrice) }}</span>
                    <span v-if="item.supplier">🏭 {{ item.supplier }}</span>
                    <span v-if="item.expiresAt && vi.hasExpiry" :class="[
                      new Date(item.expiresAt) < new Date() ? 'text-rose-400' : '',
                    ]">
                      ⏰ {{ fmtDateShort(item.expiresAt) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ── Pagination ── -->
          <div
            v-if="totalPages > 1 && !props.loading"
            class="flex items-center justify-center gap-1.5 mt-2"
          >
            <button
              :disabled="currentPage <= 1"
              class="w-8 h-8 rounded-lg border border-(--t-border)/50 flex items-center justify-center
                     text-(--t-text-3) hover:bg-(--t-card-hover) disabled:opacity-30
                     disabled:cursor-not-allowed transition-all"
              @click="goPage(currentPage - 1)"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
              </svg>
            </button>

            <template v-for="p in totalPages" :key="p">
              <button
                v-if="p <= 3 || p > totalPages - 2 || Math.abs(p - currentPage) <= 1"
                :class="[
                  'w-8 h-8 rounded-lg text-xs font-medium transition-all',
                  p === currentPage
                    ? 'bg-(--t-primary) text-white shadow-sm'
                    : 'text-(--t-text-3) hover:bg-(--t-card-hover)',
                ]"
                @click="goPage(p)"
              >
                {{ p }}
              </button>
              <span
                v-else-if="p === 4 || p === totalPages - 2"
                class="w-8 h-8 flex items-center justify-center text-(--t-text-3) text-xs"
              >
                …
              </span>
            </template>

            <button
              :disabled="currentPage >= totalPages"
              class="w-8 h-8 rounded-lg border border-(--t-border)/50 flex items-center justify-center
                     text-(--t-text-3) hover:bg-(--t-card-hover) disabled:opacity-30
                     disabled:cursor-not-allowed transition-all"
              @click="goPage(currentPage + 1)"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
              </svg>
            </button>
          </div>

          <!-- Infinite scroll sentinel -->
          <div ref="scrollSentinel" class="h-1" />

          <!-- Loading more indicator -->
          <div v-if="props.loading && props.items.length > 0" class="flex justify-center py-4">
            <div class="w-5 h-5 border-2 border-(--t-primary)/30 border-t-(--t-primary)
                        rounded-full animate-spin" />
          </div>
        </template>

        <!-- ═══════════════════════════════════
             TAB: MOVEMENTS
        ═══════════════════════════════════ -->
        <template v-if="activeTab === 'movements'">
          <div v-if="props.movements.length === 0"
               class="flex flex-col items-center justify-center py-16 text-center">
            <span class="text-4xl mb-3">📋</span>
            <p class="text-sm font-medium text-(--t-text-2)">Движений ещё нет</p>
            <p class="text-xs text-(--t-text-3) mt-1">
              Здесь будут приходы, расходы, резервы и списания
            </p>
          </div>

          <div v-else class="flex flex-col gap-2">
            <div
              v-for="mv in props.movements"
              :key="mv.id"
              class="flex items-center gap-3 rounded-xl border border-(--t-border)/40
                     bg-(--t-surface)/40 backdrop-blur-sm px-4 py-3"
            >
              <span class="shrink-0 text-lg">
                {{ MOVEMENT_TYPE_MAP[mv.type]?.icon || '📋' }}
              </span>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-(--t-text) truncate">{{ mv.itemName }}</p>
                <p class="text-[10px] text-(--t-text-3)">
                  {{ MOVEMENT_TYPE_MAP[mv.type]?.label }}
                  <span v-if="mv.note"> · {{ mv.note }}</span>
                </p>
              </div>
              <div class="text-end shrink-0">
                <p :class="[
                  'text-sm font-bold',
                  mv.type === 'in' || mv.type === 'return' || mv.type === 'release'
                    ? 'text-emerald-400' : 'text-rose-400',
                ]">
                  {{ mv.type === 'in' || mv.type === 'return' || mv.type === 'release' ? '+' : '−' }}{{ fmtNumber(mv.quantity) }}
                </p>
                <p class="text-[10px] text-(--t-text-3)">{{ fmtDateShort(mv.date) }}</p>
              </div>
            </div>
          </div>
        </template>

        <!-- ═══════════════════════════════════
             TAB: AUDITS
        ═══════════════════════════════════ -->
        <template v-if="activeTab === 'audits'">
          <div v-if="props.audits.length === 0"
               class="flex flex-col items-center justify-center py-16 text-center">
            <span class="text-4xl mb-3">📋</span>
            <p class="text-sm font-medium text-(--t-text-2)">Ревизий ещё нет</p>
            <p class="text-xs text-(--t-text-3) mt-1">
              Проведите первую инвентаризацию, чтобы сверить остатки
            </p>
            <button
              class="mt-3 text-xs text-(--t-primary) hover:underline"
              @click="emit('start-audit')"
            >
              Начать инвентаризацию
            </button>
          </div>

          <div v-else class="flex flex-col gap-2.5">
            <div
              v-for="audit in props.audits"
              :key="audit.id"
              class="rounded-xl border border-(--t-border)/40 bg-(--t-surface)/40
                     backdrop-blur-sm px-4 py-3"
            >
              <div class="flex items-center gap-2 mb-1.5">
                <span class="text-sm">📋</span>
                <p class="text-xs font-semibold text-(--t-text) flex-1">
                  {{ audit.warehouseName }}
                </p>
                <span :class="[
                  'text-[10px] px-2 py-0.5 rounded-full font-medium',
                  audit.status === 'completed' ? 'bg-emerald-500/12 text-emerald-400' :
                  audit.status === 'in_progress' ? 'bg-amber-500/12 text-amber-400' :
                  audit.status === 'discrepancy' ? 'bg-rose-500/12 text-rose-400' :
                  'bg-sky-500/12 text-sky-400',
                ]">
                  {{ AUDIT_STATUS_MAP[audit.status]?.label }}
                </span>
              </div>
              <div class="flex items-center gap-4 text-[10px] text-(--t-text-3)">
                <span>👤 {{ audit.employeeName }}</span>
                <span>📅 {{ fmtDate(audit.startedAt) }}</span>
                <span v-if="audit.discrepancies > 0" class="text-rose-400 font-medium">
                  ⚠️ Расхождений: {{ audit.discrepancies }}
                </span>
              </div>
            </div>
          </div>
        </template>
      </main>
    </div>

    <!-- ══════════════════════════════════════════════
         ITEM DETAIL MODAL
    ══════════════════════════════════════════════ -->
    <Transition name="modal-iv">
      <div
        v-if="showItemModal && selectedItem"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="closeItemModal"
      >
        <!-- backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeItemModal" />

        <!-- modal card -->
        <div class="relative z-10 inline-size-full max-w-lg rounded-2xl border border-(--t-border)
                    bg-(--t-surface)/90 backdrop-blur-xl shadow-2xl overflow-hidden">

          <!-- header -->
          <div class="flex items-center gap-3 px-5 pt-5 pb-3">
            <div class="w-10 h-10 rounded-lg bg-(--t-bg)/60 border border-(--t-border)/30
                        flex items-center justify-center overflow-hidden shrink-0">
              <img v-if="selectedItem.image" :src="selectedItem.image"
                   class="w-full h-full object-cover" />
              <span v-else class="text-lg">📦</span>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-bold text-(--t-text) truncate">{{ selectedItem.name }}</h3>
              <p class="text-[10px] text-(--t-text-3)">{{ selectedItem.sku }} · {{ selectedItem.category }}</p>
            </div>
            <span :class="[
              'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium shrink-0',
              STOCK_LEVEL_MAP[selectedItem.stockLevel].bg,
              STOCK_LEVEL_MAP[selectedItem.stockLevel].color,
            ]">
              <span class="w-1.5 h-1.5 rounded-full"
                    :class="STOCK_LEVEL_MAP[selectedItem.stockLevel].dot" />
              {{ STOCK_LEVEL_MAP[selectedItem.stockLevel].label }}
            </span>
            <button
              class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center
                     text-(--t-text-3) hover:bg-(--t-card-hover) hover:text-(--t-text) transition-colors"
              @click="closeItemModal"
            >
              ✕
            </button>
          </div>

          <!-- tabs -->
          <div class="flex items-center gap-1 px-5 border-b border-(--t-border)/40">
            <button
              v-for="t in [
                { key: 'info',      label: 'Информация' },
                { key: 'movements', label: 'Движения' },
                { key: 'audit',     label: 'Ревизии' },
              ] as const"
              :key="t.key"
              :class="[
                'px-3 py-2 text-xs font-medium transition-colors',
                modalTab === t.key
                  ? 'text-(--t-primary) border-b-2 border-(--t-primary)'
                  : 'text-(--t-text-3) hover:text-(--t-text)',
              ]"
              @click="modalTab = t.key as typeof modalTab"
            >
              {{ t.label }}
            </button>
          </div>

          <!-- body -->
          <div class="px-5 py-4 max-h-[60vh] overflow-y-auto">

            <!-- info tab -->
            <template v-if="modalTab === 'info'">
              <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                  <span class="text-(--t-text-3)">Остаток:</span>
                  <p class="font-semibold text-(--t-text) mt-0.5">
                    {{ fmtNumber(selectedItem.available) }} / {{ fmtNumber(selectedItem.quantity) }} {{ selectedItem.unit }}
                  </p>
                </div>
                <div>
                  <span class="text-(--t-text-3)">Резерв:</span>
                  <p class="font-semibold text-indigo-400 mt-0.5">{{ fmtNumber(selectedItem.reserved) }} {{ selectedItem.unit }}</p>
                </div>
                <div>
                  <span class="text-(--t-text-3)">Себестоимость:</span>
                  <p class="font-semibold text-(--t-text) mt-0.5">{{ fmtCurrency(selectedItem.costPrice) }}</p>
                </div>
                <div>
                  <span class="text-(--t-text-3)">Цена продажи:</span>
                  <p class="font-semibold text-emerald-400 mt-0.5">{{ fmtCurrency(selectedItem.sellPrice) }}</p>
                </div>
                <div>
                  <span class="text-(--t-text-3)">Склад:</span>
                  <p class="font-medium text-(--t-text) mt-0.5">{{ selectedItem.warehouseName }}</p>
                </div>
                <div>
                  <span class="text-(--t-text-3)">Мин. остаток:</span>
                  <p class="font-medium text-amber-400 mt-0.5">{{ fmtNumber(selectedItem.minStock) }} {{ selectedItem.unit }}</p>
                </div>
                <div v-if="selectedItem.supplier">
                  <span class="text-(--t-text-3)">Поставщик:</span>
                  <p class="font-medium text-(--t-text) mt-0.5">{{ selectedItem.supplier }}</p>
                </div>
                <div v-if="selectedItem.barcode">
                  <span class="text-(--t-text-3)">Штрих-код:</span>
                  <p class="font-mono text-(--t-text) mt-0.5">{{ selectedItem.barcode }}</p>
                </div>
                <div v-if="selectedItem.expiresAt && vi.hasExpiry" class="col-span-2">
                  <span class="text-(--t-text-3)">Срок годности:</span>
                  <p :class="[
                    'font-medium mt-0.5',
                    new Date(selectedItem.expiresAt) < new Date() ? 'text-rose-400' : 'text-(--t-text)',
                  ]">
                    {{ fmtDate(selectedItem.expiresAt) }}
                    <span v-if="new Date(selectedItem.expiresAt) < new Date()" class="ms-1">⚠️ Просрочен</span>
                  </p>
                </div>
                <div v-if="selectedItem.lastMovement" class="col-span-2">
                  <span class="text-(--t-text-3)">Последнее движение:</span>
                  <p class="font-medium text-(--t-text) mt-0.5">{{ fmtDate(selectedItem.lastMovement) }}</p>
                </div>
                <div v-if="selectedItem.tags.length > 0" class="col-span-2">
                  <span class="text-(--t-text-3)">Теги:</span>
                  <div class="flex flex-wrap gap-1 mt-1">
                    <span
                      v-for="tag in selectedItem.tags"
                      :key="tag"
                      class="text-[10px] px-1.5 py-0.5 rounded-md bg-(--t-bg)/60 text-(--t-text-3)
                             border border-(--t-border)/30"
                    >
                      {{ tag }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Stock bar visualization -->
              <div class="mt-4">
                <div class="flex items-center justify-between text-[10px] text-(--t-text-3) mb-1">
                  <span>Наполненность склада</span>
                  <span>{{ fmtPercent(selectedItem.available, selectedItem.quantity) }}</span>
                </div>
                <div class="h-2 rounded-full bg-(--t-bg)/80 overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="[
                      selectedItem.stockLevel === 'critical' || selectedItem.stockLevel === 'out_of_stock'
                        ? 'bg-rose-500'
                        : selectedItem.stockLevel === 'low' ? 'bg-amber-500' : 'bg-emerald-500',
                    ]"
                    :style="{ inlineSize: fmtPercent(selectedItem.available, selectedItem.quantity) }"
                  />
                </div>
              </div>
            </template>

            <!-- movements tab -->
            <template v-if="modalTab === 'movements'">
              <div v-if="props.movements.length === 0" class="text-center py-8">
                <p class="text-xs text-(--t-text-3)">Движений для этого товара нет</p>
              </div>
              <div v-else class="flex flex-col gap-2">
                <div
                  v-for="mv in props.movements.filter(m => String(m.itemId) === String(selectedItem?.id)).slice(0, 15)"
                  :key="mv.id"
                  class="flex items-center gap-2 text-xs"
                >
                  <span class="shrink-0">{{ MOVEMENT_TYPE_MAP[mv.type]?.icon || '📋' }}</span>
                  <span :class="MOVEMENT_TYPE_MAP[mv.type]?.color" class="font-medium">
                    {{ MOVEMENT_TYPE_MAP[mv.type]?.label }}
                  </span>
                  <span class="flex-1 text-(--t-text-3) truncate">{{ mv.source }}</span>
                  <span :class="[
                    'font-bold',
                    mv.type === 'in' || mv.type === 'return' || mv.type === 'release'
                      ? 'text-emerald-400' : 'text-rose-400',
                  ]">
                    {{ mv.type === 'in' || mv.type === 'return' || mv.type === 'release' ? '+' : '−' }}{{ mv.quantity }}
                  </span>
                  <span class="text-(--t-text-3) text-[10px]">{{ fmtDateShort(mv.date) }}</span>
                </div>
              </div>
            </template>

            <!-- audit tab -->
            <template v-if="modalTab === 'audit'">
              <div class="text-center py-8">
                <p class="text-xs text-(--t-text-3)">Данные ревизий для этого товара</p>
                <button
                  class="mt-2 text-xs text-(--t-primary) hover:underline"
                  @click="emit('start-audit')"
                >
                  Провести инвентаризацию
                </button>
              </div>
            </template>
          </div>

          <!-- footer -->
          <div class="px-5 pb-4 flex flex-col-reverse sm:flex-row items-stretch sm:items-center
                      gap-2 sm:justify-end border-t border-(--t-border)/30 pt-3">
            <button
              class="relative overflow-hidden px-4 py-2 rounded-xl text-xs font-medium
                     border border-(--t-border) text-(--t-text-2)
                     hover:bg-(--t-surface) hover:text-(--t-text) active:scale-[0.97]
                     transition-all"
              @click="closeItemModal"
              @mousedown="ripple"
            >
              Закрыть
            </button>
            <button
              class="relative overflow-hidden px-4 py-2 rounded-xl text-xs font-semibold
                     bg-(--t-primary) text-white hover:brightness-110 active:scale-[0.97]
                     transition-all shadow-sm"
              @click="emit('item-edit', selectedItem!); closeItemModal()"
              @mousedown="ripple"
            >
              ✏️ Редактировать
            </button>
            <button
              class="relative overflow-hidden px-4 py-2 rounded-xl text-xs font-semibold
                     bg-emerald-500/12 text-emerald-400 border border-emerald-500/20
                     hover:bg-emerald-500/20 active:scale-[0.97] transition-all"
              @click="emit('stock-adjust', selectedItem!); closeItemModal()"
              @mousedown="ripple"
            >
              📊 Корректировка
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════════════
         FILTER DRAWER (mobile)
    ══════════════════════════════════════════════ -->
    <Transition name="drawer-iv">
      <div
        v-if="showFilterDrawer"
        class="fixed inset-0 z-50 flex justify-end"
        @click.self="showFilterDrawer = false"
      >
        <!-- backdrop -->
        <div class="absolute inset-0 bg-black/40" @click="showFilterDrawer = false" />

        <!-- drawer panel -->
        <div class="relative z-10 inline-size-72 max-w-[85vw] bg-(--t-surface) border-s
                    border-(--t-border) h-full overflow-y-auto p-5 flex flex-col gap-4">

          <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-(--t-text)">Фильтры</h3>
            <button
              class="text-(--t-text-3) hover:text-(--t-text) transition-colors"
              @click="showFilterDrawer = false"
            >
              ✕
            </button>
          </div>

          <!-- Stock level -->
          <div>
            <p class="text-xs font-medium text-(--t-text-2) mb-2">Остаток</p>
            <div class="flex flex-col gap-1">
              <button
                v-for="sl in ALL_STOCK_LEVELS"
                :key="sl"
                :class="[
                  'relative overflow-hidden flex items-center gap-2 px-3 py-2 rounded-lg text-xs transition-all',
                  filters.stockLevel === sl
                    ? `${STOCK_LEVEL_MAP[sl].bg} ${STOCK_LEVEL_MAP[sl].color} font-semibold`
                    : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                ]"
                @click="setStockFilter(sl)"
                @mousedown="ripple"
              >
                <span class="w-2 h-2 rounded-full" :class="STOCK_LEVEL_MAP[sl].dot" />
                {{ STOCK_LEVEL_MAP[sl].label }}
              </button>
            </div>
          </div>

          <!-- Categories -->
          <div>
            <p class="text-xs font-medium text-(--t-text-2) mb-2">Категории</p>
            <div class="flex flex-col gap-1">
              <button
                v-for="cat in categoriesWithCounts"
                :key="cat.id"
                :class="[
                  'relative overflow-hidden flex items-center gap-2 px-3 py-2 rounded-lg text-xs transition-all',
                  filters.category === cat.id
                    ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold'
                    : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                ]"
                @click="setCategory(cat.id)"
                @mousedown="ripple"
              >
                <span class="shrink-0">{{ cat.icon }}</span>
                {{ cat.name }}
                <span class="ms-auto text-[10px] opacity-60">{{ cat.count }}</span>
              </button>
            </div>
          </div>

          <!-- Warehouses -->
          <div v-if="props.warehouses.length > 1">
            <p class="text-xs font-medium text-(--t-text-2) mb-2">Склад</p>
            <div class="flex flex-col gap-1">
              <button
                v-for="wh in props.warehouses"
                :key="wh.id"
                :class="[
                  'relative overflow-hidden flex items-center gap-2 px-3 py-2 rounded-lg text-xs transition-all',
                  filters.warehouse === wh.id
                    ? 'bg-(--t-primary)/12 text-(--t-primary) font-semibold'
                    : 'text-(--t-text-2) hover:bg-(--t-card-hover)',
                ]"
                @click="filters.warehouse = filters.warehouse === wh.id ? '' : wh.id; emit('filter-change', { ...filters })"
                @mousedown="ripple"
              >
                🏭 {{ wh.name }}
              </button>
            </div>
          </div>

          <!-- Clear -->
          <button
            v-if="hasActiveFilters"
            class="relative overflow-hidden mt-auto px-4 py-2.5 rounded-xl text-xs font-medium
                   border border-(--t-border) text-(--t-text-2) hover:bg-(--t-card-hover)
                   active:scale-[0.97] transition-all"
            @click="clearAllFilters(); showFilterDrawer = false"
            @mousedown="ripple"
          >
            Сбросить все фильтры
          </button>
        </div>
      </div>
    </Transition>

    <!-- Close actions menu on outside click -->
    <div
      v-if="showActionsMenu"
      class="fixed inset-0 z-30"
      @click="showActionsMenu = false"
    />
  </div>
</template>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     STYLES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<style scoped>
/* Ripple animation — unique suffix iv */
@keyframes ripple-iv {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* Dropdown transition */
.dropdown-iv-enter-active,
.dropdown-iv-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-iv-enter-from,
.dropdown-iv-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.96);
}

/* Slide transition (bulk bar) */
.slide-iv-enter-active,
.slide-iv-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.slide-iv-enter-from,
.slide-iv-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Sidebar transition */
.sidebar-iv-enter-active,
.sidebar-iv-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease, inline-size 0.25s ease;
}
.sidebar-iv-enter-from,
.sidebar-iv-leave-to {
  opacity: 0;
  transform: translateX(-12px);
  inline-size: 0 !important;
}

/* Modal transition */
.modal-iv-enter-active {
  transition: opacity 0.25s ease;
}
.modal-iv-enter-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}
.modal-iv-leave-active {
  transition: opacity 0.2s ease;
}
.modal-iv-leave-active > :last-child {
  transition: transform 0.2s ease-in, opacity 0.2s ease;
}
.modal-iv-enter-from,
.modal-iv-leave-to {
  opacity: 0;
}
.modal-iv-enter-from > :last-child {
  opacity: 0;
  transform: scale(0.92) translateY(12px);
}
.modal-iv-leave-to > :last-child {
  opacity: 0;
  transform: scale(0.95) translateY(6px);
}

/* Drawer transition */
.drawer-iv-enter-active,
.drawer-iv-leave-active {
  transition: opacity 0.3s ease;
}
.drawer-iv-enter-active > :last-child,
.drawer-iv-leave-active > :last-child {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.drawer-iv-enter-from,
.drawer-iv-leave-to {
  opacity: 0;
}
.drawer-iv-enter-from > :last-child,
.drawer-iv-leave-to > :last-child {
  transform: translateX(100%);
}

/* Custom scrollbar */
aside::-webkit-scrollbar { inline-size: 4px; }
aside::-webkit-scrollbar-track { background: transparent; }
aside::-webkit-scrollbar-thumb { background: var(--t-border); border-radius: 999px; }
</style>
