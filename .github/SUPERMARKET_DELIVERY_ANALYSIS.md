# Supermarket Delivery & Pickup - Детальный алгоритмический анализ

**Дата:** 2026-04-27  
**Версия:** 1.0  
**Вертикаль:** Supermarket (Продуктовый маркетплейс)

## 1. Жизненный цикл заказа: Доставка курьером vs Самозабор

### 1.1 Общие этапы (для обоих типов)

```
1. Создание заказа (Order Created)
   └─> Валидация корзины
   └─> Проверка年龄 (если 18+)
   └─> Проверка маркировки Честный ЗНАК
   └─> Резервирование товаров на складе

2. Подтверждение заказа (Order Confirmed)
   └─> Оплата подтверждена
   └─> Склад начал сборку
   └─> Уведомление клиенту

3. Сборка заказа (Order Processing)
   └─> Проверка наличия
   └─> Контроль качества (особенно холодная цепь)
   └─> Проверка сроков годности
   └─> Маркировка собранного заказа

4. Готовность к выдаче/доставке (Order Ready)
   └─> Заказ собран
   └─> Проверка allergens и dietary_restrictions
   └─> Подготовка чека с составами
```

### 1.2 Специфичные этапы: Доставка курьером

```
5. Назначение курьера (Courier Assigned)
   ├─> courier_id
   ├─> courier_name
   ├─> courier_phone
   ├─> courier_vehicle_type (авто/велосипед/пешком)
   ├─> delivery_route (оптимизированный маршрут)
   └─> estimated_delivery_time

6. Курьер в пути (Courier En Route)
   ├─> courier_location (GPS координаты)
   ├─> eta (расчетное время прибытия)
   ├─> delivery_progress (%)
   └─> temperature_control_status (для холодной цепи)

7. Курьер на точке (Courier Arrived)
   ├─> arrival_time
   ├─> notification_sent_to_client
   └─> waiting_for_client

8. Передача заказа (Delivery Handoff)
   ├─> handoff_time
   ├─> handoff_method (в руки / в ячейку / у двери)
   ├─> delivery_photo (фото передачи)
   ├─> client_signature (цифровая подпись)
   └─> temperature_check (финальная проверка температуры)

9. Доставка завершена (Delivery Completed)
   ├─> delivery_actual_time
   ├─> delivery_rating (оценка клиентом)
   ├─> delivery_notes
   └─> courier_feedback
```

### 1.3 Специфичные этапы: Самозабор

```
5. Уведомление о готовности (Pickup Ready Notification)
   ├─> notification_time
   ├─> pickup_window (временное окно самозабора)
   ├─> pickup_zone (зона выдачи)
   ├─> locker_number (если в ячейку)
   └─> qr_code (для доступа)

6. Клиент прибыл (Customer Arrived)
   ├─> arrival_time
   ├─> check_in_method (QR/телефон/имя)
   └─> queue_position

7. Выдача заказа (Pickup Handoff)
   ├─> handoff_time
   ├─> handoff_operator (сотрудник выдачи)
   ├─> id_verification (проверка документов)
   ├─> pickup_photo (фото выдачи)
   └─> temperature_check (проверка температуры при выдаче)

8. Самозабор завершен (Pickup Completed)
   ├─> pickup_actual_time
   ├─> pickup_rating (оценка клиентом)
   ├─> pickup_notes
   └─> feedback
```

## 2. CRM Данные по этапам

### 2.1 Этап создания заказа (Order Created)

**Обязательные поля CRM:**
- `order_number` - уникальный номер заказа
- `order_type` - one_time/subscription/return
- `delivery_type` - courier/pickup
- `customer_id` - ссылка на клиента
- `tenant_id` - тенант
- `business_group_id` - бизнес-группа
- `created_at` - время создания
- `correlation_id` - для трассировки

**Финансовые данные:**
- `subtotal` - сумма товаров
- `discount_amount` - скидка
- `delivery_fee` - стоимость доставки
- `total_amount` - итоговая сумма
- `payment_status` - pending/paid/failed
- `payment_method` - карта/наличные/SBP

**Товарные данные:**
- `items_count` - количество товаров
- `items_weight` - общий вес (кг)
- `items_volume` - общий объем (м³)
- `contains_perishable` - содержит скоропортящиеся
- `contains_cold_chain` - требует холодной цепи
- `contains_age_restricted` - содержит 18+

**Алергены и ограничения:**
- `allergies` - массив аллергенов клиента
- `dietary_restrictions` - диетические ограничения
- `allergen_warnings` - предупреждения об аллергенах в заказе

### 2.2 Этап подтверждения (Order Confirmed)

**CRM данные:**
- `confirmed_at` - время подтверждения
- `payment_confirmed_at` - время оплаты
- `payment_transaction_id` - ID транзакции
- `warehouse_id` - склад сборки
- `estimated_fulfillment_time` - прогнозное время сборки

### 2.3 Этап сборки (Order Processing)

**CRM данные:**
- `processing_started_at` - начало сборки
- `processing_operator_id` - оператор сборки
- `items_picked_count` - собрано товаров
- `items_missing` - отсутствующие товары
- `quality_check_passed` - проверка качества пройдена
- `cold_chain_temperature` - температура холодной цепи
- `expiry_check_passed` - проверка сроков годности

**Нутриенты (суммарно по заказу):**
- `total_calories` - общие калории
- `total_proteins` - белки (г)
- `total_fats` - жиры (г)
- `total_carbs` - углеводы (г)
- `total_fiber` - клетчатка (г)
- `total_sugar` - сахар (г)
- `total_sodium` - натрий (мг)

### 2.4 Этап готовности (Order Ready)

**CRM данные:**
- `ready_at` - время готовности
- `ready_for_pickup_at` - готов к самозабору
- `ready_for_delivery_at` - готов к доставке
- `storage_location` - место хранения
- `storage_temperature` - температура хранения
- `expiry_time` - время окончания хранения

**Для самозабора:**
- `pickup_window_start` - начало окна самозабора
- `pickup_window_end` - конец окна самозабора
- `pickup_zone` - зона выдачи
- `locker_number` - номер ячейки
- `qr_code` - QR код доступа

**Для доставки:**
- `courier_assignment_status` - статус назначения курьера
- `estimated_delivery_start` - начало доставки

### 2.5 Этап доставки курьером (Courier Stages)

**Назначение курьера:**
- `courier_id` - ID курьера
- `courier_name` - имя курьера
- `courier_phone` - телефон курьера
- `courier_vehicle_type` - тип транспорта
- `courier_assigned_at` - время назначения
- `delivery_route` - маршрут (JSON)
- `estimated_delivery_time` - прогнозное время доставки

**В пути:**
- `courier_location_lat` - широта
- `courier_location_lng` - долгота
- `courier_location_updated_at` - время обновления локации
- `delivery_eta` - ETA
- `delivery_progress` - прогресс (%)
- `temperature_current` - текущая температура
- `temperature_alerts` - alerts по температуре

**На точке:**
- `courier_arrived_at` - время прибытия
- `client_notified_at` - время уведомления клиента
- `waiting_start_at` - начало ожидания

**Передача:**
- `handoff_at` - время передачи
- `handoff_method` - метод передачи
- `delivery_photo_url` - фото передачи
- `client_signature_url` - подпись клиента
- `temperature_final` - финальная температура
- `condition_report` - состояние заказа

**Завершение:**
- `delivery_completed_at` - время завершения
- `delivery_rating` - оценка доставки (1-5)
- `delivery_feedback` - отзыв о доставке
- `courier_rating` - оценка курьера
- `delivery_issues` - проблемы при доставке

### 2.6 Этап самозабора (Pickup Stages)

**Готовность к самозабору:**
- `pickup_ready_at` - готов к выдаче
- `pickup_window_start` - начало окна
- `pickup_window_end` - конец окна
- `pickup_zone` - зона выдачи
- `locker_number` - номер ячейки
- `qr_code` - QR код
- `notification_sent_at` - время отправки уведомления

**Прибытие клиента:**
- `customer_arrived_at` - время прибытия
- `check_in_method` - метод проверки
- `queue_number` - номер в очереди
- `queue_wait_time` - время ожидания

**Выдача:**
- `pickup_started_at` - начало выдачи
- `pickup_operator_id` - оператор выдачи
- `id_verified_at` - верификация ID
- `pickup_photo_url` - фото выдачи
- `temperature_check_at` - проверка температуры
- `temperature_at_pickup` - температура при выдаче

**Завершение:**
- `pickup_completed_at` - время завершения
- `pickup_rating` - оценка самозабора (1-5)
- `pickup_feedback` - отзыв о самозаборе
- `pickup_issues` - проблемы при самозаборе

### 2.7 Этап завершения (Order Completed)

**CRM данные:**
- `completed_at` - время завершения
- `fulfillment_duration` - длительность выполнения (мин)
- `on_time_delivery` - вовремя ли (boolean)
- `customer_satisfaction` - удовлетворенность клиента
- `repeat_purchase_score` - скор повторной покупки
- `churn_risk` - риск оттока

## 3. Нутриенты и составы (Nutritional Information)

### 3.1 Обязательные поля для каждого товара

**Базовая нутриционная информация:**
- `calories_per_100g` - калории на 100г
- `proteins_per_100g` - белки на 100г (г)
- `fats_per_100g` - жиры на 100г (г)
- `carbs_per_100g` - углеводы на 100г (г)
- `fiber_per_100g` - клетчатка на 100г (г)
- `sugar_per_100g` - сахар на 100г (г)
- `sodium_per_100g` - натрий на 100г (мг)
- `serving_size` - размер порции (г)
- `servings_per_package` - порций в упаковке

**Дополнительные нутриенты:**
- `saturated_fats_per_100g` - насыщенные жиры
- `trans_fats_per_100g` - трансжиры
- `cholesterol_per_100g` - холестерин
- `potassium_per_100g` - калий
- `calcium_per_100g` - кальций
- `iron_per_100g` - железо
- `vitamin_a_per_100g` - витамин A
- `vitamin_c_per_100g` - витамин C
- `vitamin_d_per_100g` - витамин D

### 3.2 Состав (Ingredients)

**Поля состава:**
- `ingredients_list` - полный список ингредиентов (текст)
- `ingredients_json` - структурированный JSON ингредиентов
- `additives` - добавки (E-коды)
- `preservatives` - консерванты
- `colorants` - красители
- `flavor_enhancers` - усилители вкуса

**Формат JSON:**
```json
{
  "ingredients": [
    {
      "name": "Мука пшеничная",
      "percentage": 45.5,
      "order": 1
    },
    {
      "name": "Сахар",
      "percentage": 20.0,
      "order": 2
    }
  ],
  "additives": [
    {"code": "E330", "name": "Лимонная кислота"},
    {"code": "E322", "name": "Лецитин"}
  ]
}
```

### 3.3 Аглергены (Allergens)

**Обязательные аллергены (по 152-ФЗ):**
- `contains_gluten` - содержит глютен
- `contains_crustaceans` - ракообразные
- `contains_eggs` - яйца
- `contains_fish` - рыба
- `contains_peanuts` - арахис
- `contains_soy` - соя
- `contains_milk` - молоко
- `contains_nuts` - орехи
- `contains_celery` - сельдерей
- `contains_mustard` - горчица
- `contains_sesame` - кунжут
- `contains_sulfites` - сульфиты
- `contains_lupin` - люпин
- `contains_molluscs` - моллюски

**Дополнительные аллергены:**
- `contains_lactose` - лактоза
- `contains_fructose` - фруктоза
- `contains_corn` - кукуруза
- `contains_yeast` - дрожжи

**Формат хранения:**
```json
{
  "allergens": {
    "contains_milk": true,
    "contains_eggs": true,
    "contains_nuts": false,
    "milk_derivatives": ["молоко сухое", "сливки"],
    "traces_of": ["орехи", "соя"]
  }
}
```

### 3.4 Противопоказания (Contraindications)

**Медицинские противопоказания:**
- `diabetic_friendly` - для диабетиков
- `gluten_free` - без глютена
- `lactose_free` - без лактозы
- `low_sodium` - низкое содержание натрия
- `low_sugar` - низкое содержание сахара
- `low_fat` - низкое содержание жиров
- `vegan` - веганское
- `vegetarian` - вегетарианское
- `halal` - халяль
- `kosher` - кошерное
- `organic` - органическое

**Возрастные ограничения:**
- `min_age` - минимальный возраст
- `max_age` - максимальный возраст
- `pregnancy_warning` - предупреждение для беременных
- `breastfeeding_warning` - предупреждение для кормящих

## 4. Агрегация данных для CRM

### 4.1 Суммарные нутриенты по заказу

**Расчет на основе товаров:**
```php
$total_calories = sum(item.calories_per_100g * item.weight / 100);
$total_proteins = sum(item.proteins_per_100g * item.weight / 100);
$total_fats = sum(item.fats_per_100g * item.weight / 100);
$total_carbs = sum(item.carbs_per_100g * item.weight / 100);
```

### 4.2 Проверка аллергенов

**Кросс-проверка аллергенов клиента с товарами:**
```php
$client_allergies = $customer->allergies; // ['milk', 'nuts']
$order_allergens = collect($order->items)
    ->flatMap(fn($item) => $item->allergens)
    ->unique()
    ->values();

$warnings = array_intersect($client_allergies, $order_allergens);
```

### 4.3 Диетические рекомендации

**Автоматические рекомендации:**
- Если клиент диабетик → рекомендовать low_sugar товары
- Если клиент веган → фильтровать non-vegan товары
- Если аллергия на молоко → фильтровать contains_milk

## 5. Статусы заказа (Order Statuses)

### 5.1 Общие статусы
- `pending` - ожидает подтверждения
- `confirmed` - подтвержден
- `processing` - в сборке
- `ready` - готов к выдаче/доставке
- `completed` - завершен
- `cancelled` - отменен
- `failed` - ошибка

### 5.2 Статусы доставки
- `courier_assigned` - курьер назначен
- `courier_en_route` - курьер в пути
- `courier_arrived` - курьер на точке
- `delivery_handoff` - передача заказа
- `delivered` - доставлен

### 5.3 Статусы самозабора
- `pickup_ready` - готов к самозабору
- `customer_arrived` - клиент прибыл
- `pickup_handoff` - выдача заказа
- `picked_up` - получен

## 6. Уведомления (Notifications)

### 6.1 Для доставки курьером
- SMS/Push: "Курьер {name} назначен. Телефон: {phone}"
- SMS/Push: "Курьер в пути. ETA: {eta}"
- SMS/Push: "Курьер на точке. Встречайте!"
- SMS/Push: "Заказ доставлен. Оцените доставку"

### 6.2 Для самозабора
- SMS/Push: "Заказ готов! Самозабор с {start} до {end}"
- SMS/Push: "QR код для доступа: {qr_code}"
- SMS/Push: "Напоминаем: заберите заказ до {end}"
- SMS/Push: "Заказ получен. Оцените сервис"

## 7. Метрики для аналитики

### 7.1 Ключевые метрики доставки
- `average_delivery_time` - среднее время доставки
- `on_time_delivery_rate` - % вовремя доставленных
- `courier_rating_avg` - средняя оценка курьеров
- `delivery_failures` - количество неудачных доставок

### 7.2 Ключевые метрики самозабора
- `average_pickup_wait_time` - среднее время ожидания
- `pickup_completion_rate` - % успешных самозаборов
- `pickup_rating_avg` - средняя оценка самозабора
- `expired_pickups` - количество не забранных заказов

### 7.3 Нутриционные метрики
- `avg_order_calories` - средние калории на заказ
- `healthy_order_rate` - % "здоровых" заказов
- `allergen_alert_rate` - % заказов с предупреждениями об аллергенах

## 8. Следующие шаги реализации

1. **Создать миграции** для новых полей в таблицах
2. **Обновить SupermarketOrder** с новыми полями
3. **Создать сущность ProductNutrition** для нутриентов товаров
4. **Обновить SupermarketCRMService** для трекинга этапов
5. **Создать DeliveryTrackingService** для отслеживания курьеров
6. **Создать PickupService** для самозабора
7. **Обновить уведомления** для новых этапов
8. **Создать аналитические отчеты** по доставке и самозабору
