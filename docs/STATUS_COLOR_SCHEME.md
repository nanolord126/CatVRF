# CatCRM Standard Color Scheme 2026

Единая цветовая индикация статусов для всех вертикалей CatVRF.

## Общая легенда цветов

| Цвет | Класс Filament | Класс Tailwind | Семантика |
|------|----------------|----------------|-----------|
| **Зелёный** | `success`, `emerald` | `bg-emerald-100 text-emerald-800` | Всё хорошо, действие завершено успешно |
| **Синий** | `info`, `sky` | `bg-blue-100 text-blue-800` | В процессе, активное состояние |
| **Жёлтый / Оранжевый** | `warning`, `amber` | `bg-yellow-100 text-yellow-800`, `bg-amber-100 text-amber-800` | Требует внимания, частичная оплата, ожидание |
| **Красный** | `danger` | `bg-red-100 text-red-800` | Проблема, просрочка, отменено |
| **Серый** | `secondary`, `gray` | `bg-gray-100 text-gray-800` | Черновик, неактивно |
| **Фиолетовый** | `violet` | `bg-violet-100 text-violet-800` | Специальное состояние (заезд гостя) |

## Статусы заказов (Ресторан)

### Order (Restaurant)

| Статус | Цвет Filament | Иконка (Heroicon) | Описание |
|--------|---------------|-------------------|----------|
| `draft` | `secondary` | `heroicon-o-document` | Черновик заказа |
| `pending_payment` | `warning` | `heroicon-o-clock` | Ожидает оплаты |
| `partially_paid` | `amber` | `heroicon-o-credit-card` | Частично оплачен |
| `paid` | `success` | `heroicon-o-check-circle` | Полностью оплачен |
| `in_kitchen` | `info` | `heroicon-o-fire` | На кухне |
| `ready` | `emerald` | `heroicon-o-hand-raised` | Готов к выдаче |
| `completed` | `success` | `heroicon-o-check-badge` | Завершён |
| `cancelled` | `danger` | `heroicon-o-x-circle` | Отменён |

### Legacy статусы (для обратной совместимости)

| Статус | Цвет Filament | Иконка (Heroicon) |
|--------|---------------|-------------------|
| `pending` | `gray` | `heroicon-o-clock` |
| `confirmed` | `blue` | `heroicon-o-check-circle` |
| `preparing` | `yellow` | `heroicon-o-fire` |
| `served` | `emerald` | `heroicon-o-check` |
| `delivering` | `indigo` | `heroicon-o-truck` |
| `delivered` | `green` | `heroicon-o-check-circle` |
| `picked_up` | `green` | `heroicon-o-hand-raised` |
| `refunded` | `orange` | `heroicon-o-arrow-uturn-left` |

## Статусы бронирований (Гостиницы)

### Booking (Hotels)

| Статус | Цвет Filament | Иконка (Heroicon) | Описание |
|--------|---------------|-------------------|----------|
| `pending` | `warning` | `heroicon-o-clock` | Ожидает подтверждения |
| `prepaid` | `sky` | `heroicon-o-banknotes` | Предоплата внесена |
| `paid` | `success` | `heroicon-o-check-circle` | Полностью оплачен |
| `confirmed` | `info` | `heroicon-o-shield-check` | Подтверждён |
| `checked_in` | `violet` | `heroicon-o-arrow-down-on-square` | Гость заехал |
| `checked_out` | `success` | `heroicon-o-arrow-up-on-square` | Гость выехал |
| `cancelled` | `danger` | `heroicon-o-x-circle` | Отменён |
| `no_show` | `danger` | `heroicon-o-user-minus` | Неявка |

## Реализация в Filament

### BadgeColumn с цветами и иконками

```php
use Filament\Tables\Columns\TextColumn;

TextColumn::make('status')
    ->label('Статус')
    ->badge()
    ->color(fn (string $state): string => match ($state) {
        'draft' => 'secondary',
        'pending_payment' => 'warning',
        'partially_paid' => 'amber',
        'paid' => 'success',
        'in_kitchen' => 'info',
        'ready' => 'emerald',
        'completed' => 'success',
        'cancelled' => 'danger',
        default => 'gray',
    })
    ->icon(fn (string $state): ?string => match ($state) {
        'draft' => 'heroicon-o-document',
        'pending_payment' => 'heroicon-o-clock',
        'partially_paid' => 'heroicon-o-credit-card',
        'paid' => 'heroicon-o-check-circle',
        'in_kitchen' => 'heroicon-o-fire',
        'ready' => 'heroicon-o-hand-raised',
        'completed' => 'heroicon-o-check-badge',
        'cancelled' => 'heroicon-o-x-circle',
        default => null,
    })
    ->formatStateUsing(fn (string $state): string => match ($state) {
        'draft' => 'Черновик',
        'pending_payment' => 'Ожидает оплаты',
        'partially_paid' => 'Частично оплачен',
        'paid' => 'Оплачен',
        'in_kitchen' => 'На кухне',
        'ready' => 'Готов к выдаче',
        'completed' => 'Завершён',
        'cancelled' => 'Отменён',
        default => ucfirst($state),
    });
```

## Реализация в KDS (Livewire карточки)

### Использование Tailwind классов напрямую

```blade
<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium
    {{ match($order->status->value) {
        'paid', 'completed' => 'bg-emerald-100 text-emerald-800',
        'partially_paid' => 'bg-amber-100 text-amber-800',
        'pending_payment' => 'bg-yellow-100 text-yellow-800',
        'in_kitchen' => 'bg-blue-100 text-blue-800',
        'cancelled' => 'bg-red-100 text-red-800',
        'draft' => 'bg-gray-100 text-gray-800',
        default => 'bg-gray-100 text-gray-800',
    } }}">
    <x-dynamic-component :component="'heroicon-o-' . str_replace('heroicon-o-', '', $order->status->icon())" class="w-4 h-4" />
    {{ $order->status->label() }}
</span>
```

## Использование в моделях

### Методы для получения цвета, иконки и метки

```php
// В модели Order (Restaurant)
public function getStatusColor(): string
{
    return $this->status->filamentColor();
}

public function getStatusIcon(): string
{
    return $this->status->icon();
}

public function getStatusLabel(): string
{
    return $this->status->label();
}

// Использование в blade
<span class="bg-{{ $order->getStatusColor() }}-100 text-{{ $order->getStatusColor() }}-700">
    {{ $order->getStatusLabel() }}
</span>
```

## Файлы реализации

### Рестораны
- **Enum**: `modules/Restaurant/Enums/OrderStatus.php`
- **Модель**: `modules/Restaurant/Models/Order.php`
- **Filament Resource**: `modules/Restaurant/Presentation/Resources/OrderResource.php`
- **KDS Blade**: `modules/Restaurant/Presentation/Views/livewire/order-card.blade.php`
- **Тесты**: `tests/Unit/Modules/Restaurant/OrderStatusColorTest.php`

### Гостиницы
- **Enum**: `modules/Hotels/Enums/BookingStatus.php`
- **Модель**: `modules/Hotels/Models/Booking.php`
- **Filament Resource**: `app/Domains/Hotels/Filament/Resources/BookingResource.php`
- **Тесты**: `tests/Unit/Modules/Hotels/BookingStatusColorTest.php`

## Принципы дизайна

1. **Интуитивность**: Зелёный = хорошо, красный = плохо, жёлтый = внимание
2. **Консистентность**: Одинаковые цвета для одинаковых смыслов во всех вертикалях
3. **Доступность**: Высокий контраст цветов для удобства чтения
4. **Иконочная поддержка**: Каждому статусу соответствует понятная иконка
5. **Локализация**: Все метки на русском языке

## Запуск тестов

```bash
# Тесты для ресторанов
php artisan test tests/Unit/Modules/Restaurant/OrderStatusColorTest.php

# Тесты для гостиниц
php artisan test tests/Unit/Modules/Hotels/BookingStatusColorTest.php
```

## Версия

**Версия**: 1.0  
**Дата**: 23.04.2026  
**Стандарт**: CatCRM Standard Color Scheme 2026
