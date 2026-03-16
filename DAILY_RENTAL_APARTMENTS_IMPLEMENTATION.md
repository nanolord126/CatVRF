# 🏠 Посуточные квартиры (Daily Rental Apartments) - Реализация

## Структура реализации

### 1. **Модель Property**

Квартиры посуточно реализованы через модель `Property`, которая представляет единицу недвижимости с поддержкой различных типов аренды.

**Файл:** `app/Models/Tenants/Property.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Tenants;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

final class Property extends Model
{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;

    protected $table = 'properties';

    // 24-часовая сдача: price = цена за сутки
    // Долгосрочная аренда: price = цена в месяц
    protected $fillable = [
        'title',                    // Название: "Уютная 2-комнатная квартира в центре"
        'description',              // Полное описание
        'property_type',            // apartment, house, room, etc.
        'rental_type',              // daily, monthly, long_term
        'address',                  // Полный адрес
        'geo_lat',                  // Широта для карты
        'geo_lng',                  // Долгота для карты
        'price',                    // Цена за сутки (для daily) или месяц (для monthly)
        'area',                     // Площадь в кв.м
        'rooms_count',              // Количество комнат
        'floor',                    // Этаж
        'total_floors',             // Всего этажей в доме
        'amenities',                // JSON: wifi, tv, kitchen, washing_machine и т.д.
        'images',                   // JSON: массив URL изображений
        'status',                   // active, inactive, booked, archived
        'correlation_id',           // UUID для отслеживания
        'tenant_id',                // Принадлежит тенанту
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'area' => 'float',
        'rooms_count' => 'integer',
        'floor' => 'integer',
        'total_floors' => 'integer',
        'geo_lat' => 'float',
        'geo_lng' => 'float',
        'amenities' => 'json',      // array ['wifi', 'tv', 'kitchen']
        'images' => 'json',         // array ['https://...', 'https://...']
    ];
}
```

### 2. **Таблица базы данных**

**Миграция:** (из `2026_03_06_000001_create_tenant_data_tables.php`)

Таблица `properties` содержит все данные о квартирах:

```sql
CREATE TABLE properties (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,                      -- "Уютная 2-комнатная..."
    description TEXT,                                 -- Полное описание
    property_type VARCHAR(50),                        -- apartment, house, room
    rental_type VARCHAR(50),                          -- daily, monthly, long_term
    address VARCHAR(500),                             -- Адрес
    geo_lat DECIMAL(10, 8),                          -- Широта
    geo_lng DECIMAL(11, 8),                          -- Долгота
    price DECIMAL(15, 2),                            -- Цена/сутки или /месяц
    area FLOAT,                                       -- Площадь
    rooms_count INT,                                  -- Комнаты
    floor INT,                                        -- Этаж
    total_floors INT,                                 -- Всего этажей
    amenities JSON,                                   -- Удобства
    images JSON,                                      -- Фотографии
    status VARCHAR(50),                              -- Статус
    correlation_id UUID INDEX,                        -- Отслеживание
    tenant_id BIGINT INDEX,                          -- Multi-tenancy
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 📋 Реализация в Filament

### Property Resource

**Файл:** `app/Filament/Tenant/Resources/Marketplace/PropertyResource.php`

> ⚠️ **Примечание:** Текущая версия пуста, но вот как она должна быть реализована для посуточных квартир:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Marketplace;

use App\Models\Tenants\Property;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationGroup = 'Маркетплейс';
    protected static ?int $navigationSort = 15;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            // === ОСНОВНАЯ ИНФОРМАЦИЯ ===
            Forms\Components\Section::make('Основная информация')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Название объявления')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Уютная 2-комнатная квартира в центре'),

                    Forms\Components\Select::make('property_type')
                        ->label('Тип объекта')
                        ->options([
                            'apartment' => 'Квартира',
                            'house' => 'Дом',
                            'room' => 'Комната',
                            'suite' => 'Апартаменты',
                            'studio' => 'Студия',
                        ])
                        ->required(),

                    Forms\Components\Select::make('rental_type')
                        ->label('Тип аренды')
                        ->options([
                            'daily' => 'Посуточно',
                            'monthly' => 'Ежемесячно',
                            'long_term' => 'Долгосрочно',
                        ])
                        ->required()
                        ->afterStateUpdated(function ($state) {
                            // Помогает переключаться между видами цен
                        }),

                    Forms\Components\Textarea::make('description')
                        ->label('Описание')
                        ->required()
                        ->maxLength(2000)
                        ->rows(5)
                        ->columnSpanFull(),
                ]),

            // === ЦЕНА И ХАРАКТЕРИСТИКИ ===
            Forms\Components\Section::make('Цена и параметры')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('price')
                        ->label('Цена (руб./сутки или руб./месяц)')
                        ->numeric()
                        ->required()
                        ->step('0.01'),

                    Forms\Components\TextInput::make('area')
                        ->label('Площадь (м²)')
                        ->numeric()
                        ->required(),

                    Forms\Components\TextInput::make('rooms_count')
                        ->label('Количество комнат')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->maxValue(10),

                    Forms\Components\TextInput::make('floor')
                        ->label('Этаж')
                        ->numeric(),

                    Forms\Components\TextInput::make('total_floors')
                        ->label('Всего этажей')
                        ->numeric(),
                ]),

            // === МЕСТОПОЛОЖЕНИЕ ===
            Forms\Components\Section::make('Местоположение')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('address')
                        ->label('Адрес')
                        ->required()
                        ->maxLength(500)
                        ->placeholder('ул. Тверская, 15, Москва'),

                    Forms\Components\Grid::make(2)
                        ->columnSpan(2)
                        ->schema([
                            Forms\Components\TextInput::make('geo_lat')
                                ->label('Широта')
                                ->numeric()
                                ->step(0.00001),

                            Forms\Components\TextInput::make('geo_lng')
                                ->label('Долгота')
                                ->numeric()
                                ->step(0.00001),
                        ]),
                ]),

            // === УДОБСТВА ===
            Forms\Components\Section::make('Удобства')
                ->schema([
                    Forms\Components\CheckboxList::make('amenities')
                        ->label('Выберите удобства')
                        ->options([
                            'wifi' => 'Wi-Fi',
                            'tv' => 'Телевизор',
                            'kitchen' => 'Кухня',
                            'washing_machine' => 'Стиральная машина',
                            'dishwasher' => 'Посудомойка',
                            'ac' => 'Кондиционер',
                            'heating' => 'Отопление',
                            'hot_water' => 'Горячая вода',
                            'parking' => 'Парковка',
                            'balcony' => 'Балкон',
                            'pet_friendly' => 'Питомцы приветствуются',
                            'workspace' => 'Рабочее место',
                        ])
                        ->columns(3),
                ]),

            // === ФОТО ===
            Forms\Components\Section::make('Изображения')
                ->schema([
                    Forms\Components\TagsInput::make('images')
                        ->label('URLs изображений')
                        ->placeholder('https://example.com/photo1.jpg')
                        ->separator(','),
                ]),

            // === СТАТУС ===
            Forms\Components\Section::make('Статус')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Статус объявления')
                        ->options([
                            'active' => 'Активное',
                            'inactive' => 'Неактивное',
                            'booked' => 'Забронировано',
                            'archived' => 'Архивировано',
                        ])
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('Фото')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => $record->images[0] ?? null),

                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('property_type')
                    ->label('Тип')
                    ->colors([
                        'info' => 'apartment',
                        'success' => 'house',
                        'warning' => 'room',
                    ]),

                Tables\Columns\BadgeColumn::make('rental_type')
                    ->label('Аренда')
                    ->colors([
                        'success' => 'daily',
                        'info' => 'monthly',
                        'warning' => 'long_term',
                    ]),

                Tables\Columns\TextColumn::make('address')
                    ->label('Адрес')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('area')
                    ->label('м²')
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),

                Tables\Columns\TextColumn::make('rooms_count')
                    ->label('Комнаты')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'booked',
                        'secondary' => 'archived',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('property_type')
                    ->label('Тип объекта')
                    ->options([
                        'apartment' => 'Квартира',
                        'house' => 'Дом',
                        'room' => 'Комната',
                    ]),

                Tables\Filters\SelectFilter::make('rental_type')
                    ->label('Тип аренды')
                    ->options([
                        'daily' => 'Посуточно',
                        'monthly' => 'Ежемесячно',
                        'long_term' => 'Долгосрочно',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активное',
                        'inactive' => 'Неактивное',
                        'booked' => 'Забронировано',
                    ]),

                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Forms\Components\TextInput::make('price_from')
                            ->label('Цена от')
                            ->numeric(),
                        Forms\Components\TextInput::make('price_to')
                            ->label('Цена до')
                            ->numeric(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['price_from'] ?? null, fn ($q) => $q->where('price', '>=', $data['price_from']))
                            ->when($data['price_to'] ?? null, fn ($q) => $q->where('price', '<=', $data['price_to']));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

---

## 📅 Процесс бронирования

### Модель PropertyBooking (Расширение)

Для посуточных квартир нужна таблица бронирований:

```php
// app/Models/Tenants/PropertyBooking.php

final class PropertyBooking extends Model
{
    use BelongsToTenant;
    use StrictTenantIsolation;

    protected $fillable = [
        'property_id',          // FK -> properties
        'customer_id',          // FK -> users
        'check_in_date',        // DATE
        'check_out_date',       // DATE
        'nights_count',         // Количество ночей
        'price_per_night',      // Цена за ночь
        'total_price',          // Сумма
        'status',               // pending, confirmed, checked_in, completed, cancelled
        'contact_phone',        // Телефон гостя
        'contact_email',        // Email гостя
        'special_requests',     // Особые пожелания
        'correlation_id',       // Отслеживание
        'tenant_id',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
```

### Таблица бронирований

```sql
CREATE TABLE property_bookings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    property_id BIGINT NOT NULL,
    customer_id BIGINT NOT NULL,
    check_in_date DATE,
    check_out_date DATE,
    nights_count INT,
    price_per_night DECIMAL(15, 2),
    total_price DECIMAL(15, 2),
    status VARCHAR(50),
    contact_phone VARCHAR(20),
    contact_email VARCHAR(255),
    special_requests TEXT,
    correlation_id UUID,
    tenant_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id),
    FOREIGN KEY (customer_id) REFERENCES users(id)
);
```

---

## 🎯 Ключевые особенности реализации

### 1. **Multi-tenancy**
- Каждая квартира привязана к `tenant_id`
- Строгая изоляция данных между тенантами

### 2. **Гибкая модель цен**
```php
// Для посуточной аренды:
$property->price = 3500;  // рублей в сутки
$rental_type = 'daily';

// Для месячной аренды:
$property->price = 80000; // рублей в месяц
$rental_type = 'monthly';
```

### 3. **Отслеживание через correlation_id**
```php
// Все операции с одной квартирой отслеживаются:
correlation_id: '550e8400-e29b-41d4-a716-446655440000'
```

### 4. **JSON Amenities**
```php
$property->amenities = json_encode([
    'wifi',
    'tv',
    'kitchen',
    'washing_machine',
    'parking',
    'pet_friendly'
]);
```

### 5. **Геолокация для поиска**
```php
$property->geo_lat = 55.7595;  // Москва
$property->geo_lng = 37.6194;

// Можно использовать для фильтрации по радиусу
```

---

## 💾 Примеры данных

### Пример посуточной квартиры

```json
{
  "id": 1,
  "title": "Уютная 2-комнатная квартира в центре Москвы",
  "description": "Современная квартира с ремонтом, вся необходимая техника и мебель. Расположена в центре, рядом метро, магазины и рестораны.",
  "property_type": "apartment",
  "rental_type": "daily",
  "address": "ул. Тверская, 15, кв. 42, Москва, Россия",
  "geo_lat": 55.7595,
  "geo_lng": 37.6194,
  "price": 3500.00,
  "area": 65.5,
  "rooms_count": 2,
  "floor": 3,
  "total_floors": 9,
  "amenities": ["wifi", "tv", "kitchen", "washing_machine", "parking", "balcony"],
  "images": [
    "https://example.com/property1/room1.jpg",
    "https://example.com/property1/room2.jpg",
    "https://example.com/property1/kitchen.jpg"
  ],
  "status": "active",
  "created_at": "2026-03-15 10:30:00"
}
```

### Пример бронирования

```json
{
  "id": 1,
  "property_id": 1,
  "customer_id": 42,
  "check_in_date": "2026-04-01",
  "check_out_date": "2026-04-05",
  "nights_count": 4,
  "price_per_night": 3500.00,
  "total_price": 14000.00,
  "status": "confirmed",
  "contact_phone": "+7 (999) 123-45-67",
  "contact_email": "guest@example.com",
  "special_requests": "Позднее заселение после 22:00",
  "created_at": "2026-03-15 11:15:00"
}
```

---

## 🌐 Публичный каталог

Посуточные квартиры показываются в публичном маркетплейсе с фильтрацией:

```php
// routes/web.php

Route::get('/apartments', function () {
    $apartments = Property::where('rental_type', 'daily')
        ->where('status', 'active')
        ->paginate(12);
    
    return view('apartments.catalog', ['properties' => $apartments]);
});

Route::get('/apartment/{id}', function ($id) {
    $property = Property::findOrFail($id);
    return view('apartments.show', ['property' => $property]);
});
```

---

## 🔒 Безопасность

### Authorization Policy

```php
// app/Policies/PropertyPolicy.php

class PropertyPolicy extends Policy
{
    public function view(User $user, Property $property): bool
    {
        return $property->tenant_id === tenant('id');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('property_manager');
    }

    public function update(User $user, Property $property): bool
    {
        return $property->tenant_id === tenant('id') && 
               $user->hasRole('property_manager');
    }

    public function delete(User $user, Property $property): bool
    {
        return $property->tenant_id === tenant('id') && 
               $user->hasRole('owner');
    }
}
```

---

## 📊 Статистика

**Текущее состояние:**
- ✅ Модель Property (готова)
- ✅ Таблица migrations (готова)
- ⚠️ PropertyResource (пуста, требует заполнения)
- ⚠️ PropertyBooking (примерная структура)
- ⚠️ Публичный каталог (требует разработки)

**Требуемые часы разработки:** ~4-6 часов для полного развертывания с UI

---

## 🚀 Развертывание

```bash
# Применить миграции
php artisan tenants:migrate --tenants=all

# Создать seeder с примерами квартир
php artisan tenants:seed --seeder=PropertySeeder --tenants=all

# Кэширование и очистка
php artisan cache:clear
php artisan config:clear
```
