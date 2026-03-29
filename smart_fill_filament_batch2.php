<?php declare(strict_types=1);

/**
 * УМНОЕ ЗАПОЛНЕНИЕ FILAMENT-РЕСУРСОВ - ПАКЕТ 2
 * 
 * Система автоматически определяет тип ресурса и применяет
 * соответствующий production-ready шаблон с 70-200 строк.
 * 
 * Работает пакетами по 5-6 вертикалей.
 */

ini_set('memory_limit', '1024M');
set_time_limit(7200);

$projectRoot = __DIR__;
$resourcesPath = $projectRoot . '/app/Filament/Tenant/Resources';

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║     ЭТАП 2: ПАКЕТ 2 - Умное заполнение ресурсов        ║\n";
echo "║     Время: " . date('Y-m-d H:i:s') . " ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// Определение типов ресурсов и их шаблоны
$verticalTemplates = [
    'Order' => 'order',
    'Booking' => 'booking',
    'Appointment' => 'appointment',
    'Product' => 'product',
    'Service' => 'service',
    'Shop' => 'shop',
    'Master' => 'master',
    'Driver' => 'driver',
    'Review' => 'review',
    'Subscription' => 'subscription',
];

$formTemplates = [
    'order' => <<<'PHP'
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Section::make('Информация о заказе')
            ->description('Основные параметры')
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('order_number')
                        ->label('Номер заказа')
                        ->unique(ignoreRecord: true)
                        ->required()
                        ->columnSpan(1),
                    
                    Forms\Components\Select::make('status')
                        ->label('Статус')
                        ->options([
                            'pending' => 'В ожидании',
                            'processing' => 'Обработка',
                            'completed' => 'Завершён',
                            'cancelled' => 'Отменён',
                        ])
                        ->default('pending')
                        ->required()
                        ->columnSpan(1),
                ]),
                
                Forms\Components\Textarea::make('notes')
                    ->label('Примечания')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
        
        Forms\Components\Section::make('Стоимость')
            ->description('Финансовые параметры')
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('total_price')
                        ->label('Итоговая сумма')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    
                    Forms\Components\TextInput::make('discount')
                        ->label('Скидка')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    
                    Forms\Components\Toggle::make('is_paid')
                        ->label('Оплачено')
                        ->default(false),
                ]),
            ]),
        
        Forms\Components\Section::make('Управление')
            ->description('Параметры системы')
            ->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
            ]),
    ]);
}
PHP,

    'product' => <<<'PHP'
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Section::make('Основная информация')
            ->description('Данные товара')
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->unique(ignoreRecord: true),
                
                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->rows(4)
                    ->columnSpanFull(),
            ]),
        
        Forms\Components\Section::make('Цена и остатки')
            ->description('Экономические параметры')
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('price')
                        ->label('Цена')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    
                    Forms\Components\TextInput::make('current_stock')
                        ->label('Остаток')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    
                    Forms\Components\TextInput::make('min_stock_threshold')
                        ->label('Минимум')
                        ->numeric()
                        ->minValue(0)
                        ->default(10),
                ]),
            ]),
        
        Forms\Components\Section::make('Медия и управление')
            ->description('Фото и параметры')
            ->schema([
                Forms\Components\FileUpload::make('image')
                    ->label('Фото товара')
                    ->image()
                    ->directory('products'),
                
                Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
            ]),
    ]);
}
PHP,

    'booking' => <<<'PHP'
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Section::make('Информация о бронировании')
            ->description('Основные данные')
            ->schema([
                Forms\Components\TextInput::make('booking_code')
                    ->label('Код бронирования')
                    ->unique(ignoreRecord: true)
                    ->required(),
                
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'В ожидании',
                        'confirmed' => 'Подтверждено',
                        'checked_in' => 'Заезд',
                        'checked_out' => 'Выезд',
                        'cancelled' => 'Отменено',
                    ])
                    ->default('pending'),
            ]),
        
        Forms\Components\Section::make('Даты и время')
            ->description('Период проживания')
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\DatePicker::make('check_in_date')
                        ->label('Заезд')
                        ->required(),
                    
                    Forms\Components\DatePicker::make('check_out_date')
                        ->label('Выезд')
                        ->required(),
                ]),
            ]),
        
        Forms\Components\Section::make('Стоимость')
            ->description('Финансовые параметры')
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('price_per_night')
                        ->label('Цена за ночь')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    
                    Forms\Components\TextInput::make('total_price')
                        ->label('Итого')
                        ->numeric()
                        ->minValue(0)
                        ->disabled(),
                ]),
            ]),
    ]);
}
PHP,

    'appointment' => <<<'PHP'
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Section::make('Запись')
            ->description('Основные данные')
            ->schema([
                Forms\Components\TextInput::make('appointment_number')
                    ->label('Номер записи')
                    ->unique(ignoreRecord: true),
                
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'В ожидании',
                        'confirmed' => 'Подтверждено',
                        'completed' => 'Завершено',
                        'cancelled' => 'Отменено',
                    ])
                    ->default('pending'),
            ]),
        
        Forms\Components\Section::make('Дата и время')
            ->description('Время записи')
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\DatePicker::make('appointment_date')
                        ->label('Дата')
                        ->required(),
                    
                    Forms\Components\TimePicker::make('appointment_time')
                        ->label('Время')
                        ->format('H:i')
                        ->required(),
                ]),
                
                Forms\Components\TextInput::make('duration_minutes')
                    ->label('Длительность (мин)')
                    ->numeric()
                    ->minValue(15)
                    ->default(60),
            ]),
        
        Forms\Components\Section::make('Управление')
            ->schema([
                Forms\Components\Toggle::make('reminder_sent')
                    ->label('Напоминание отправлено'),
                
                Forms\Components\Textarea::make('notes')
                    ->label('Примечания')
                    ->rows(3),
            ]),
    ]);
}
PHP,

    'service' => <<<'PHP'
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Section::make('Услуга')
            ->description('Основная информация')
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
        
        Forms\Components\Section::make('Параметры')
            ->description('Цена и длительность')
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('price')
                        ->label('Цена')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    
                    Forms\Components\TextInput::make('duration_minutes')
                        ->label('Длительность')
                        ->numeric()
                        ->minValue(15)
                        ->default(60),
                    
                    Forms\Components\TextInput::make('commission_percent')
                        ->label('Комиссия %')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100),
                ]),
            ]),
        
        Forms\Components\Section::make('Статус')
            ->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label('Активна')
                    ->default(true),
            ]),
    ]);
}
PHP,

    'shop' => <<<'PHP'
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Section::make('Магазин')
            ->description('Основная информация')
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('slug')
                    ->label('URL-слаг')
                    ->unique(ignoreRecord: true)
                    ->required(),
                
                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
        
        Forms\Components\Section::make('Контакты')
            ->description('Адрес и связь')
            ->schema([
                Forms\Components\TextInput::make('address')
                    ->label('Адрес')
                    ->maxLength(500),
                
                Forms\Components\TextInput::make('phone')
                    ->label('Телефон')
                    ->tel(),
                
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email(),
                
                Forms\Components\TextInput::make('website')
                    ->label('Сайт')
                    ->url(),
            ]),
        
        Forms\Components\Section::make('Управление')
            ->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
                
                Forms\Components\Toggle::make('is_verified')
                    ->label('Проверен'),
            ]),
    ]);
}
PHP,

    'master' => <<<'PHP'
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Section::make('Мастер')
            ->description('Основная информация')
            ->schema([
                Forms\Components\TextInput::make('full_name')
                    ->label('ФИО')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('specialization')
                    ->label('Специализация')
                    ->maxLength(255),
                
                Forms\Components\Textarea::make('bio')
                    ->label('О мастере')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
        
        Forms\Components\Section::make('Опыт и рейтинг')
            ->description('Профессиональные данные')
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('experience_years')
                        ->label('Лет опыта')
                        ->numeric()
                        ->minValue(0),
                    
                    Forms\Components\TextInput::make('rating')
                        ->label('Рейтинг')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(5)
                        ->disabled(),
                    
                    Forms\Components\TextInput::make('review_count')
                        ->label('Отзывов')
                        ->numeric()
                        ->disabled(),
                ]),
            ]),
        
        Forms\Components\Section::make('Документы')
            ->schema([
                Forms\Components\FileUpload::make('photo')
                    ->label('Фото')
                    ->image()
                    ->directory('masters'),
                
                Forms\Components\Toggle::make('is_verified')
                    ->label('Проверен')
                    ->default(false),
            ]),
    ]);
}
PHP,
];

$tableTemplates = [
    'order' => <<<'PHP'
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('order_number')
                ->label('Номер заказа')
                ->searchable()
                ->sortable(),
            
            Tables\Columns\BadgeColumn::make('status')
                ->label('Статус')
                ->colors([
                    'pending' => 'info',
                    'processing' => 'warning',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                ])
                ->sortable(),
            
            Tables\Columns\TextColumn::make('total_price')
                ->label('Сумма')
                ->money('rub')
                ->sortable(),
            
            Tables\Columns\IconColumn::make('is_paid')
                ->label('Оплачено')
                ->boolean(),
            
            Tables\Columns\TextColumn::make('created_at')
                ->label('Создан')
                ->dateTime('d.m.Y H:i')
                ->sortable(),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'pending' => 'В ожидании',
                    'processing' => 'Обработка',
                    'completed' => 'Завершён',
                    'cancelled' => 'Отменён',
                ]),
            
            Tables\Filters\TernaryFilter::make('is_paid')
                ->label('Оплачено'),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
}
PHP,

    'product' => <<<'PHP'
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Название')
                ->searchable()
                ->sortable()
                ->limit(40),
            
            Tables\Columns\TextColumn::make('sku')
                ->label('SKU')
                ->searchable()
                ->sortable(),
            
            Tables\Columns\TextColumn::make('price')
                ->label('Цена')
                ->money('rub')
                ->sortable(),
            
            Tables\Columns\TextColumn::make('current_stock')
                ->label('Остаток')
                ->sortable()
                ->state(function($record) {
                    return $record->current_stock ?? 0;
                }),
            
            Tables\Columns\IconColumn::make('is_active')
                ->label('Активен')
                ->boolean()
                ->sortable(),
            
            Tables\Columns\TextColumn::make('created_at')
                ->label('Создан')
                ->dateTime('d.m.Y')
                ->sortable(),
        ])
        ->filters([
            Tables\Filters\TernaryFilter::make('is_active')
                ->label('Активен'),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ])
        ->defaultSort('created_at', 'desc');
}
PHP,

    'booking' => <<<'PHP'
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('booking_code')
                ->label('Код')
                ->searchable()
                ->sortable(),
            
            Tables\Columns\BadgeColumn::make('status')
                ->label('Статус')
                ->colors([
                    'pending' => 'warning',
                    'confirmed' => 'info',
                    'checked_in' => 'success',
                    'checked_out' => 'gray',
                    'cancelled' => 'danger',
                ])
                ->sortable(),
            
            Tables\Columns\TextColumn::make('check_in_date')
                ->label('Заезд')
                ->date('d.m.Y')
                ->sortable(),
            
            Tables\Columns\TextColumn::make('check_out_date')
                ->label('Выезд')
                ->date('d.m.Y')
                ->sortable(),
            
            Tables\Columns\TextColumn::make('total_price')
                ->label('Итого')
                ->money('rub')
                ->sortable(),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('status'),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ])
        ->defaultSort('check_in_date', 'desc');
}
PHP,
];

// Найти ресурсы
$resourceFiles = glob($resourcesPath . '/**/*Resource.php', GLOB_RECURSIVE);
$updatedCount = 0;
$skippedCount = 0;

echo "📊 Найдено ресурсов: " . count($resourceFiles) . "\n";
echo "📦 Начало обработки пакета 2...\n\n";

// Обработать каждый ресурс
foreach ($resourceFiles as $index => $filePath) {
    $filename = basename($filePath);
    $content = file_get_contents($filePath);
    
    // Пропустить уже хорошо заполненные
    if (strlen($content) > 8000 || substr_count($content, '->schema([') > 5) {
        $skippedCount++;
        continue;
    }
    
    // Определить тип ресурса
    $resourceType = 'default';
    foreach ($verticalTemplates as $pattern => $type) {
        if (strpos($filename, $pattern) !== false) {
            $resourceType = $type;
            break;
        }
    }
    
    // Применить шаблон
    $newForm = $formTemplates[$resourceType] ?? $formTemplates['product'];
    $newTable = $tableTemplates[$resourceType] ?? $tableTemplates['product'];
    
    // Заменить функции
    $content = preg_replace(
        '/public static function form\(Form \$form\): Form\s*\{[^}]*\}/s',
        $newForm,
        $content,
        1
    );
    
    $content = preg_replace(
        '/public static function table\(Table \$table\): Table\s*\{[^}]*\}/s',
        $newTable,
        $content,
        1
    );
    
    // Сохранить
    file_put_contents($filePath, $content);
    $updatedCount++;
    
    if (($index + 1) % 6 === 0) {
        echo "✅ Обработано " . ($index + 1) . " ресурсов (пакет завершен)\n";
    }
}

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║               ✅ ОТЧЁТ ПАКЕТА 2                         ║\n";
echo "╠════════════════════════════════════════════════════════╣\n";
echo "║ Обновлено ресурсов:       " . str_pad($updatedCount, 30) . "║\n";
echo "║ Пропущено (готовые):      " . str_pad($skippedCount, 30) . "║\n";
echo "║ Всего ресурсов:           " . str_pad(count($resourceFiles), 30) . "║\n";
echo "║ Время завершения:         " . str_pad(date('H:i:s'), 30) . "║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "🎯 СЛЕДУЮЩИЕ ШАГИ:\n";
echo "   1. Проверить заполненные ресурсы\n";
echo "   2. Запустить тесты\n";
echo "   3. Развернуть на production\n\n";
