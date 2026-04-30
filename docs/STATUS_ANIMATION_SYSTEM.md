# Единая система цветовой индикации и анимации статусов

**Версия:** 1.0  
**Дата:** 24.04.2026  
**Проект:** CatVRF (CatCRM)

## Обзор

Единая система цветовой индикации и анимации статусов предоставляет семантическую визуализацию изменений статусов заказов и бронирований во всех вертикалях CatVRF (Restaurant, Hotels, Beauty, Fitness и др.). Анимации появляются только при изменении статуса, обеспечивая мгновенную обратную связь для пользователя без перегрузки интерфейса.

## Архитектура

### Компоненты

1. **resources/views/components/status-badge.blade.php** — переиспользуемый компонент для отображения статусов с анимациями
2. **tailwind.config.js** — CSS анимации и keyframes
3. **Filament Resources** — ресурсы с анимированными статусами для всех вертикалей
4. **Livewire Components** — компоненты для KDS, дашбордов и мобильных интерфейсов

## Цветовая и анимационная система

### Статусы заказов (Restaurant Orders)

| Статус | Цвет | Иконка | Анимация | Семантика |
|--------|------|--------|----------|-----------|
| `draft` | Secondary (серый) | document | Без анимации | Черновик |
| `pending_payment` | Warning (жёлтый) | clock | gentle-shake | Ожидает оплаты |
| `partially_paid` | Amber (янтарный) | credit-card | gentle-shake | Частично оплачен |
| `paid` | Success (зелёный) | check-circle | success-pulse | Оплачен |
| `in_kitchen` | Info (синий) | fire | wave | На кухне |
| `ready` | Emerald (изумрудный) | hand-raised | success-pulse | Готов к выдаче |
| `completed` | Success (зелёный) | check-badge | success-pulse | Завершён |
| `cancelled` | Danger (красный) | x-circle | danger-flash | Отменён |

### Статусы бронирований (Restaurant Bookings)

| Статус | Цвет | Иконка | Анимация | Семантика |
|--------|------|--------|----------|-----------|
| `pending` | Secondary (серый) | clock | Без анимации | Ожидает подтверждения |
| `confirmed` | Info (синий) | calendar | wave | Подтверждено |
| `arrived` | Success (зелёный) | user | success-pulse | Гости прибыли |
| `completed` | Emerald (изумрудный) | check-badge | success-pulse | Завершено |
| `cancelled` | Danger (красный) | x-circle | danger-flash | Отменено |
| `no_show` | Warning (оранжевый) | users | danger-flash | Не пришли |

### Статусы бронирований (Hotels Bookings)

| Статус | Цвет | Иконка | Анимация | Семантика |
|--------|------|--------|----------|-----------|
| `pending` | Warning (жёлтый) | clock | gentle-shake | Pending |
| `prepaid` | Info (синий) | credit-card | wave | Prepaid |
| `paid` | Success (зелёный) | check-circle | success-pulse | Paid |
| `checked_in` | Primary (индиго) | user | success-pulse | Checked In |
| `checked_out` | Secondary (серый) | check-badge | Без анимации | Checked Out |
| `cancelled` | Danger (красный) | x-circle | danger-flash | Cancelled |
| `no_show` | Danger (красный) | users | danger-flash | No Show |

## CSS Анимации

### Описание анимаций

1. **status-change** (500ms) — базовая анимация при изменении статуса
   - Scale: 0.95 → 1.05 → 1.0
   - Opacity: 0.7 → 1.0
   - Используется для всех изменений статуса

2. **success-pulse** (2s, infinite) — мягкий пульс для успешных статусов
   - Box-shadow пульсация
   - Цвет: rgba(16, 185, 129, 0.4) → прозрачный
   - Используется для: paid, completed, ready, arrived, checked_in

3. **gentle-shake** (0.5s) — лёгкое покачивание для предупреждений
   - TranslateX: 0 → -2px → 0 → 2px → 0
   - Используется для: pending_payment, partially_paid, pending

4. **wave** (0.6s) — волна слева направо для процессов
   - TranslateX: -10px → 0
   - Opacity: 0.5 → 1.0
   - Используется для: in_kitchen, confirmed, prepaid

5. **danger-flash** (0.8s, 2 раза) — мигание для проблем
   - Background-color: светло-красный → красный → светло-красный
   - Используется для: cancelled, no_show

## Использование

### В Blade шаблонах

```blade
<x-status-badge 
    status="paid"
    label="Оплачен"
    color="success"
    icon="check-circle"
/>
```

### В Filament Resources

```php
use Filament\Tables\ViewColumn;

ViewColumn::make('status')
    ->label('Статус')
    ->view('components.status-badge')
    ->viewData(fn ($record): array => [
        'status' => $record->status,
        'label' => match ($record->status) {
            'paid' => 'Оплачен',
            // ...
        },
        'color' => match ($record->status) {
            'paid' => 'success',
            // ...
        },
        'icon' => match ($record->status) {
            'paid' => 'check-circle',
            // ...
        },
    ])
```

### В Livewire компонентах

```blade
<div x-data="{ status: '{{ $order->status }}' }" x-init="...">
    <x-status-badge 
        :status="$order->status"
        :label="..."
        :color="..."
        :icon="..."
    />
</div>
```

## Логика анимаций

### Определение изменения статуса

Анимация появляется только при изменении статуса, используя localStorage:

```javascript
x-data="{ 
    status: '{{ $status }}',
    key: 'status-{{ $status }}-{{ $label ?? $status }}',
    init() {
        const previous = localStorage.getItem(this.key);
        if (previous !== this.status) {
            // Запускаем анимацию
            this.$el.classList.add('animate-status-change');
            setTimeout(() => {
                this.$el.classList.remove('animate-status-change');
            }, 600);
        }
        localStorage.setItem(this.key, this.status);
    }
}"
```

## Где применяется

### Restaurant Vertical
- **OrderResource** — список заказов ресторана
- **BookingResource** — список бронирований столов
- **KitchenOrderCard** — KDS карточки заказов на кухне

### Hotels Vertical
- **BookingResource** — список бронирований номеров
- **Dashboard** — виджеты с изменением статусов

### Другие вертикали (Beauty, Fitness, и др.)
- Filament ресурсы с использованием status-badge компонента
- Livewire компоненты для реального времени

## Правила использования

### ✅ Делайте

- Используйте анимации только при изменении статуса
- Длительность анимации: 400-600 мс
- Семантические анимации: успех → пульс, проблема → мигание, процесс → волна
- Поддержка dark mode
- Минимальная нагрузка на браузер

### ❌ Не делайте

- Не анимируйте каждую загрузку страницы
- Не используйте тяжёлые библиотеки (только Tailwind + Alpine.js)
- Не создавайте "новогоднюю ёлку" (избыточные анимации)
- Не превышайте длительность 600 мс для основной анимации

## Тестирование

### Запуск тестов

```bash
php artisan test --filter StatusBadgeAnimationTest
```

### Покрытие тестами

- Проверка наличия CSS классов анимации
- Проверка правильного маппинга цветов
- Проверка переходов статусов
- Проверка длительности анимаций в конфиге

## Производительность

- CSS анимации (GPU ускоренные)
- Минимальный JavaScript (только Alpine.js)
- Локальное хранение предыдущего статуса (localStorage)
- Нет внешних зависимостей

## Совместимость

- **Браузеры:** Chrome, Firefox, Safari, Edge (последние 2 версии)
- **Устройства:** Десктоп, планшет (кухня), мобильный
- **Filament:** 3.x
- **Tailwind:** 3.x
- **Alpine.js:** 3.x

## Файлы

```
resources/views/components/status-badge.blade.php  # Компонент бейджа
tailwind.config.js                                 # CSS анимации
modules/Restaurant/Presentation/Resources/
  OrderResource.php                                # Заказы с анимациями
  BookingResource.php                              # Бронирования с анимациями
modules/Hotels/Filament/Resources/
  BookingResource.php                              # Бронирования с анимациями
modules/Restaurant/Livewire/
  KitchenOrderCard.php                             # KDS карточка
modules/Restaurant/resources/views/livewire/
  kitchen-order-card.blade.php                     # Шаблон KDS карточки
tests/Feature/Restaurant/
  StatusBadgeAnimationTest.php                     # Тесты
docs/
  STATUS_ANIMATION_SYSTEM.md                       # Этот файл
```

## Примеры для владельца

### Отслеживание оплаты заказа (Restaurant)

1. Заказ создаётся → статус `pending_payment` (жёлтый, покачивание)
2. Клиент оплачивает → статус `paid` (зелёный, пульс + scale)
3. Владелец сразу видит изменение благодаря анимации

### Отслеживание бронирования (Hotels)

1. Бронирование создаётся → статус `pending` (жёлтый, покачивание)
2. Предоплата → статус `prepaid` (синий, волна)
3. Полная оплата → статус `paid` (зелёный, пульс)
4. Заезд → статус `checked_in` (индиго, пульс)
5. Выезд → статус `checked_out` (серый, без анимации)

## Примеры для персонала (кухня)

### KDS карточка заказа

1. Заказ поступает → статус `in_kitchen` (синий, волна)
2. Заказ готов → статус `ready` (изумрудный, пульс)
3. Заказ оплачен → статус `paid` (зелёный, яркий пульс)
4. Персонал сразу видит готовность и оплату

## Будущие улучшения

- [ ] Добавить звуковые уведомления для критических статусов
- [ ] Интеграция с WebSocket для real-time обновлений
- [ ] Кастомизируемые анимации через настройки
- [ ] Анимации для batch операций
- [ ] Статистика времени реакции на изменения статусов
- [ ] Поддержка других вертикалей (Beauty, Fitness, и др.)

## Поддержка

При проблемах с анимациями:
1. Проверьте консоль браузера на ошибки JavaScript
2. Убедитесь, что Tailwind скомпилирован с новыми анимациями
3. Очистите localStorage для сброса отслеживания статусов
4. Проверьте совместимость браузера

---

**Автор:** CatVRF Team  
**Лицензия:** Proprietary  
**Статус:** Production Ready
