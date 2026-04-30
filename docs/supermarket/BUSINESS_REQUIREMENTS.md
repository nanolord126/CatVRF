# Supermarket Business Requirements & Compliance

## 1. Business Model Overview

### 1.1 Core Value Proposition
CatVRF Supermarket — AI-powered маркетплейс свежих продуктов с:
- Динамическим ценообразованием (управляется продавцом)
- Системой отзывов с валидацией (платные 50-500₽, бесплатные с ML-анализом)
- Лояльностью и кэшбэком
- Отслеживанием курьеров в реальном времени
- Холодной цепью и сроками годности

### 1.2 Revenue Streams
1. **Комиссия с продаж** — процент от каждой транзакции
2. **Маржа платформы** — % от роста цены при динамическом ценообразовании
3. **Платные отзывы** — магазин платит 100% за модерацию (50-500₽)
4. **Подписки** — НЕТ (удалены из модели)
5. **Реклама** — продвижение товаров и магазинов

### 1.3 Key Stakeholders
- **Покупатели (B2C)**: физические лица
- **Магазины/Продавцы (B2B)**: юридические лица
- **Курьеры**: фрилансеры или штат
- **Платформа (CatVRF)**: оператор маркетплейса
- **Администраторы**: модерация, аналитика, поддержка

---

## 2. Business Requirements (BRD)

### 2.1 Functional Requirements

#### FR-1: Заказы
- FR-1.1: Создание заказа с выбором доставки (курьер/самозабор)
- FR-1.2: Отслеживание статуса заказа в реальном времени
- FR-1.3: QR-код для самозабора
- FR-1.4: Поддержка холодной цепи (индикаторы температуры)
- FR-1.5: Интеграция с честным знаком (ЧестныйЗнак)

#### FR-2: Динамическое ценообразование
- FR-2.1: Продавец устанавливает пороги (min_price, max_price, platform_margin)
- FR-2.2: Если пороги не заданы:
  - min_price = base_price (цена не опускается ниже)
  - max_price = base_price * 1.5
  - platform_margin = 30%
- FR-2.3: При росте цены разница распределяется:
  - price_increase = dynamic_price - base_price
  - platform_margin = price_increase * platform_margin_percent
  - seller_amount = base_price + price_increase - platform_margin
- FR-2.4: Факторы влияния: спрос (30%), остатки (25%), время (10%), сезонность (20%), конкуренция (15%)
- FR-2.5: Кэширование расчётов на 15 минут

#### FR-3: Отзывы
- FR-3.1: Платные отзывы (50-500₽, шаг 50₽)
  - Магазин модерировал и оплатил 100%
  - Статус: paid, approved
- FR-3.2: Бесплатные отзывы
  - Магазин не модерировал
  - Платформа анализирует (justification_score >= 0.6)
  - Статус: free, published
- FR-3.3: Валидация:
  - Обязательная привязка к заказу
  - Товар должен быть в чеке
  - Нельзя написать негатив на молоко, если в чеке только рыба
  - ML-анализ обоснованности комментария

#### FR-4: Лояльность и Кэшбэк
- FR-4.1: Начисление баллов (1 балл = 1₽) с множителями по tier:
  - Bronze: 1x, Silver: 1.5x, Gold: 2x, Platinum: 3x
- FR-4.2: Бонусы: первый заказ (500), отзыв (50), реферал (1000), день рождения (2000)
- FR-4.3: Кэшбэк по tier: 1%-5%
- FR-4.4: Месячные лимиты: Bronze 1000₽, Silver 3000₽, Gold 5000₽, Platinum 10000₽
- FR-4.5: Использование кэшбэка как скидки (до 50% от заказа)

#### FR-5: CRM
- FR-5.1: Профиль клиента с историей заказов
- FR-5.2: RFM-анализ (Recency, Frequency, Monetary)
- FR-5.3: Сегментация клиентов
- FR-5.4: Автоматические кампании (email, push, SMS)
- FR-5.5: Анализ спроса и прогнозирование

#### FR-6: Аналитика для продавцов
- FR-6.1: Сводка бизнеса (выручка, заказы, клиенты)
- FR-6.2: Прогноз выручки
- FR-6.3: Топ продуктов и категорий
- FR-6.4: Конверсия и воронка продаж
- FR-6.5: Отслеживание выручки в реальном времени

#### FR-7: Возвраты
- FR-7.1: Правила по SubVertical (мясо - 1 день, кондитерка - 3 дня, веган - 5 дней)
- FR-7.2: Холодная цепь - возврат только при браке с фото
- FR-7.3: Авто-одобрение для брака по холодной цепи в течение 12 часов
- FR-7.4: Интеграция с PaymentAdapter для возврата денег

### 2.2 Non-Functional Requirements

#### NFR-1: Performance
- NFR-1.1: API response time < 200ms (P95)
- NFR-1.2: WebSocket latency < 100ms
- NFR-1.3: Поддержка 5000+ concurrent users

#### NFR-2: Security
- NFR-2.1: TLS 1.3 для всех соединений
- NFR-2.2: Fraud detection на всех финансовых операциях
- NFR-2.3: Audit logging для всех действий
- NFR-2.4: PII anonymization (152-ФЗ, ФЗ-323)

#### NFR-3: Availability
- NFR-3.1: Uptime 99.9%
- NFR-3.2: Graceful degradation при отказе сервисов
- NFR-3.3: Auto-scaling для пиковых нагрузок

#### NFR-4: Compliance
- NFR-4.1: 54-ФЗ (кассы, чеки)
- NFR-4.2: 152-ФЗ (персональные данные)
- NFR-4.3: ФЗ-323 (медицинские данные)
- NFR-4.4: ЧестныйЗнак (маркировка)
- NFR-4.5: FSTEC БДУ (угрозы)

---

## 3. Data Models

### 3.1 Core Entities

#### SupermarketOrder
```php
- id: uuid
- uuid: string
- order_number: string
- buyer_id: int
- seller_id: int
- tenant_id: int
- delivery_type: enum (courier, pickup)
- order_status: enum (pending, confirmed, processing, ready, delivering, delivered, picked_up, cancelled, failed)
- total_amount: decimal
- subtotal_amount: decimal
- delivery_fee: decimal
- service_fee: decimal
- discount_amount: decimal
- cashback_used: decimal
- loyalty_points_used: int
- courier_id?: int
- courier_name?: string
- courier_phone?: string
- courier_location_lat?: decimal
- courier_location_lng?: decimal
- delivery_eta?: datetime
- delivery_address?: json
- pickup_zone?: string
- locker_number?: string
- pickup_window_start?: datetime
- pickup_window_end?: datetime
- qr_code?: string
- total_calories?: int
- total_proteins?: decimal
- total_fats?: decimal
- total_carbs?: decimal
- allergen_warnings?: json
- dietary_restrictions?: json
- is_b2b: boolean
- honest_marks?: json
- created_at: datetime
- confirmed_at?: datetime
- processing_started_at?: datetime
- pickup_ready_at?: datetime
- courier_assigned_at?: datetime
- delivery_actual_at?: datetime
- pickup_actual_at?: datetime
```

#### SupermarketProduct
```php
- id: uuid
- uuid: string
- seller_id: int
- tenant_id: int
- name: string
- description?: text
- sku: string
- barcode?: string
- base_price: decimal
- weight?: decimal
- unit: enum (kg, g, l, ml, piece)
- requires_cold_chain: boolean
- shelf_life_days?: int
- sub_vertical: enum (meat_shops, vegan_products, confectionery, farm_direct, ...)
- attributes?: json
- is_active: boolean
- is_age_restricted: boolean
- min_age?: int
- honest_mark?: string
- nutritional_info?: json
- allergens?: json
- dietary?: json
- images?: json
- dynamic_pricing_settings?: json
  - enabled: boolean
  - min_price?: decimal
  - max_price?: decimal
  - platform_margin: int
- created_at: datetime
- updated_at: datetime
```

#### Review
```php
- id: uuid
- uuid: string
- customer_id: int
- order_id: int
- product_id: int
- seller_id: int
- rating: int (1-5)
- comment: text
- attachments?: json
- status: enum (pending, approved, rejected, published)
- type: enum (free, paid)
- payment_amount?: decimal
- justification_score?: decimal
- validation_notes?: json
- moderated_by?: int (seller_id or null for platform)
- moderated_at?: datetime
- published_at?: datetime
- created_at: datetime
```

#### CustomerProfile
```php
- id: uuid
- uuid: string
- user_id: int
- tenant_id: int
- loyalty_tier: enum (bronze, silver, gold, platinum)
- loyalty_points: int
- cashback_balance: decimal
- total_spent: decimal
- total_orders: int
- last_order_at?: datetime
- preferences?: json
- dietary_restrictions?: json
- allergens?: json
- created_at: datetime
- updated_at: datetime
```

---

## 4. Roles & Permissions

### 4.1 Roles

#### R1: Customer (Покупатель)
- Создание заказов
- Просмотр своих заказов
- Оставление отзывов
- Использование кэшбэка/баллов
- Просмотр профиля

#### R2: Seller (Продавец/Магазин)
- Управление товарами
- Настройка динамического ценообразования
- Модерация отзывов (платные)
- Просмотр аналитики
- Управление складом
- Обработка заказов

#### R3: Courier (Курьер)
- Просмотр назначенных заказов
- Обновление статуса доставки
- Обновление геолокации
- Связь с клиентом

#### R4: Moderator (Модератор платформы)
- Модерация всех отзывов
- Проверка обоснованности
- Блокировка нарушителей
- Разрешение споров

#### R5: Admin (Администратор)
- Полный доступ ко всем функциям
- Управление пользователями
- Настройка системы
- Просмотр всей аналитики
- Управление ролями

#### R6: Analyst (Аналитик)
- Просмотр аналитики
- Экспорт отчётов
- Настройка дашбордов
- Доступ к BI

### 4.2 Permissions Matrix

| Permission | Customer | Seller | Courier | Moderator | Admin | Analyst |
|------------|----------|--------|---------|-----------|-------|---------|
| orders.create | ✓ | ✗ | ✗ | ✗ | ✓ | ✗ |
| orders.view_own | ✓ | ✗ | ✓ | ✓ | ✓ | ✓ |
| orders.view_all | ✗ | ✓ | ✓ | ✓ | ✓ | ✓ |
| orders.update_status | ✗ | ✓ | ✓ | ✓ | ✓ | ✗ |
| products.manage | ✗ | ✓ | ✗ | ✗ | ✓ | ✗ |
| products.view | ✓ | ✓ | ✗ | ✓ | ✓ | ✓ |
| pricing.configure | ✗ | ✓ | ✗ | ✗ | ✓ | ✗ |
| reviews.create | ✓ | ✗ | ✗ | ✗ | ✓ | ✗ |
| reviews.moderate_own | ✗ | ✓ | ✗ | ✗ | ✓ | ✗ |
| reviews.moderate_all | ✗ | ✗ | ✗ | ✓ | ✓ | ✗ |
| analytics.view_own | ✓ | ✓ | ✗ | ✗ | ✓ | ✓ |
| analytics.view_all | ✗ | ✗ | ✗ | ✗ | ✓ | ✓ |
| users.manage | ✗ | ✗ | ✗ | ✗ | ✓ | ✗ |
| roles.manage | ✗ | ✗ | ✗ | ✗ | ✓ | ✗ |

---

## 5. Compliance Requirements

### 5.1 152-ФЗ (Персональные данные)
- Хранение PII только в зашифрованном виде
- Согласие на обработку
- Право на удаление
- Обработка в РФ

### 5.2 54-ФЗ (Кассовые чеки)
- Онлайн-касса для всех продаж
- Отправка чеков в ФНС
- Маркировка (ЧестныйЗнак)
- ЭДО для B2B

### 5.3 ФЗ-323 (Медицинские данные)
- Анонимизация перед отправкой в LLM
- Отдельное хранение
- Доступ по ролям

### 5.4 FSTEC БДУ (Угрозы ИБ)
- Защита от утечек
- Аудит действий
- Инцидент-менеджмент

---

## 6. Success Metrics (KPI)

### 6.1 Business KPI
- GMV (Gross Merchandise Value)
- Take rate (комиссия)
- Active sellers
- Active buyers
- Order completion rate
- Average order value

### 6.2 Product KPI
- API response time
- Uptime
- Conversion rate
- Retention rate
- NPS (Net Promoter Score)

### 6.3 Quality KPI
- On-time delivery rate
- Return rate
- Review quality score
- Fraud detection rate
