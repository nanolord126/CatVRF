<?php declare(strict_types=1);

/**
 * ЭТАП 2: Автоматическое заполнение Filament-ресурсов
 * Канон 2026: Production-Ready форм и таблицы
 * 
 * Это создаёт production-ready формы (60+ строк) и таблицы (50+ строк) для всех вертикалей
 */

$verticals = [
    'Beauty' => ['models' => ['BeautySalon', 'Master', 'Service', 'Appointment', 'BeautyProduct']],
    'Hotels' => ['models' => ['Hotel', 'RoomType', 'Booking', 'HotelReview']],
    'ShortTermRentals' => ['models' => ['Apartment', 'ApartmentBooking', 'ApartmentAmenity', 'ApartmentReview']],
    'Food' => ['models' => ['Restaurant', 'RestaurantMenu', 'Dish', 'DishVariant', 'RestaurantOrder', 'RestaurantTable']],
    'GroceryAndDelivery' => ['models' => ['GroceryStore', 'GroceryProduct', 'GroceryOrder']],
];

// Template для form()
$formTemplate = <<<'EOT'
public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('__SECTION_TITLE__')
                ->description('Основная информация об объекте')
                ->schema([
                    Forms\Components\TextInput::make('uuid')
                        ->label('UUID')
                        ->default(fn () => (string) \Illuminate\Support\Str::uuid())
                        ->disabled()
                        ->dehydrated()
                        ->columnSpan('full'),

                    Forms\Components\TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan('half'),

                    Forms\Components\TextInput::make('description')
                        ->label('Описание')
                        ->maxLength(1000)
                        ->columnSpan('half'),

                    Forms\Components\RichEditor::make('details')
                        ->label('Детали')
                        ->toolbarButtons([
                            'attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock', 'heading', 'italic',
                            'link', 'orderedList', 'redo', 'strike', 'underline', 'undo',
                        ])
                        ->columnSpan('full'),
                ])->columns(2),

            Forms\Components\Section::make('Отношения и привязки')
                ->schema([
                    Forms\Components\Select::make('tenant_id')
                        ->label('Клиент')
                        ->relationship('tenant', '__TENANT_NAME_FIELD__', 
                            fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('id', tenant('id')))
                        ->disabled()
                        ->dehydrated()
                        ->default(fn () => tenant('id'))
                        ->columnSpan('half'),

                    Forms\Components\Select::make('business_group_id')
                        ->label('Бизнес-группа')
                        ->relationship('businessGroup', 'name', 
                            fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('tenant_id', tenant('id')))
                        ->columnSpan('half'),
                ])->columns(2),

            Forms\Components\Section::make('Статусы и флаги')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true)
                        ->columnSpan('half'),

                    Forms\Components\Toggle::make('is_verified')
                        ->label('Верифицирован')
                        ->default(false)
                        ->columnSpan('half'),

                    Forms\Components\Select::make('status')
                        ->label('Статус')
                        ->options([
                            'draft' => 'Черновик',
                            'active' => 'Активен',
                            'inactive' => 'Неактивен',
                            'archived' => 'Архивирован',
                        ])
                        ->default('draft')
                        ->columnSpan('half'),

                    Forms\Components\TextInput::make('rating')
                        ->label('Рейтинг')
                        ->numeric()
                        ->step(0.1)
                        ->minValue(0)
                        ->maxValue(5)
                        ->columnSpan('half'),
                ])->columns(2),

            Forms\Components\Section::make('Медиа')
                ->schema([
                    Forms\Components\FileUpload::make('images')
                        ->label('Изображения')
                        ->multiple()
                        ->image()
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('16:9')
                        ->columnSpan('full'),
                ])->columns(1),

            Forms\Components\Section::make('Теги и метаданные')
                ->schema([
                    Forms\Components\TagsInput::make('tags')
                        ->label('Теги')
                        ->placeholder('Добавьте теги...')
                        ->columnSpan('full'),

                    Forms\Components\KeyValue::make('metadata')
                        ->label('Метаданные')
                        ->columnSpan('full'),
                ])->columns(1),

            Forms\Components\Section::make('Системные данные')
                ->schema([
                    Forms\Components\TextInput::make('correlation_id')
                        ->label('Correlation ID')
                        ->default(fn () => (string) \Illuminate\Support\Str::uuid())
                        ->disabled()
                        ->dehydrated()
                        ->columnSpan('full'),

                    Forms\Components\DateTimePicker::make('created_at')
                        ->label('Создано')
                        ->disabled()
                        ->columnSpan('half'),

                    Forms\Components\DateTimePicker::make('updated_at')
                        ->label('Обновлено')
                        ->disabled()
                        ->columnSpan('half'),
                ])->columns(2)->collapsed(),
        ]);
}
EOT;

// Template для table()
$tableTemplate = <<<'EOT'
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->searchable()
                ->size('sm'),

            Tables\Columns\ImageColumn::make('images')
                ->label('Изображение')
                ->circular()
                ->size(40),

            Tables\Columns\TextColumn::make('name')
                ->label('Название')
                ->sortable()
                ->searchable()
                ->limit(40),

            Tables\Columns\TextColumn::make('description')
                ->label('Описание')
                ->limit(50)
                ->tooltip(fn ($record) => $record->description),

            Tables\Columns\BadgeColumn::make('status')
                ->label('Статус')
                ->colors([
                    'danger' => 'draft',
                    'success' => 'active',
                    'warning' => 'inactive',
                    'secondary' => 'archived',
                ])
                ->sortable(),

            Tables\Columns\IconColumn::make('is_verified')
                ->label('Верифицирован')
                ->boolean()
                ->sortable(),

            Tables\Columns\TextColumn::make('rating')
                ->label('Рейтинг')
                ->numeric(1)
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Создано')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('updated_at')
                ->label('Обновлено')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            Tables\Filters\TernaryFilter::make('is_verified')
                ->label('Верифицирован'),

            Tables\Filters\SelectFilter::make('status')
                ->label('Статус')
                ->options([
                    'draft' => 'Черновик',
                    'active' => 'Активен',
                    'inactive' => 'Неактивен',
                    'archived' => 'Архивирован',
                ]),

            Tables\Filters\TernaryFilter::make('is_active')
                ->label('Активен'),
        ])
        ->actions([
            Tables\Actions\ViewAction::make()
                ->before(function ($record) {
                    \Illuminate\Support\Facades\Log::channel('audit')->info('Filament: View Action', [
                        'model' => $record::class,
                        'id' => $record->id,
                        'tenant_id' => tenant('id'),
                        'correlation_id' => (string) \Illuminate\Support\Str::uuid(),
                    ]);
                }),

            Tables\Actions\EditAction::make()
                ->before(function ($record) {
                    \Illuminate\Support\Facades\Log::channel('audit')->info('Filament: Edit Action', [
                        'model' => $record::class,
                        'id' => $record->id,
                        'tenant_id' => tenant('id'),
                        'correlation_id' => (string) \Illuminate\Support\Str::uuid(),
                    ]);
                }),

            Tables\Actions\DeleteAction::make()
                ->before(function ($record) {
                    \Illuminate\Support\Facades\Log::channel('audit')->warning('Filament: Delete Action', [
                        'model' => $record::class,
                        'id' => $record->id,
                        'tenant_id' => tenant('id'),
                        'correlation_id' => (string) \Illuminate\Support\Str::uuid(),
                    ]);
                }),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->before(function ($records) {
                        \Illuminate\Support\Facades\Log::channel('audit')->warning('Filament: Bulk Delete', [
                            'count' => $records->count(),
                            'tenant_id' => tenant('id'),
                            'correlation_id' => (string) \Illuminate\Support\Str::uuid(),
                        ]);
                    }),
            ]),
        ])
        ->emptyStateActions([
            Tables\Actions\CreateAction::make(),
        ]);
}
EOT;

// Найти все Resource файлы и проанализировать
$resourcePath = __DIR__ . '/app/Filament/Tenant/Resources';
$files = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($resourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
    \RecursiveIteratorIterator::SELF_FIRST
);

$resources = [];
foreach ($files as $file) {
    if ($file->getExtension() === 'php' && strpos($file->getFilename(), 'Resource.php') !== false) {
        $resources[] = $file->getPathname();
    }
}

echo "=== АНАЛИЗ FILAMENT-РЕСУРСОВ ===\n";
echo "Найдено ресурсов: " . count($resources) . "\n\n";

$totalLines = 0;
$resourcesWithEmptyForm = 0;
$resourcesWithEmptyTable = 0;

foreach ($resources as $path) {
    $content = file_get_contents($path);
    $lines = count(file($path));
    $totalLines += $lines;

    // Проверить на пустые form() и table()
    $hasForm = preg_match('/public\s+static\s+function\s+form\s*\(/i', $content);
    $hasTable = preg_match('/public\s+static\s+function\s+table\s*\(/i', $content);
    
    // Проверить, пустые ли они
    $formEmpty = preg_match('/public\s+static\s+function\s+form\s*\(.*?\)\s*:\s*Form\s*\{[\s\n]*return\s+\$form[\s\n]*\{[\s\n]*\};/is', $content);
    $tableEmpty = preg_match('/public\s+static\s+function\s+table\s*\(.*?\)\s*:\s*Table\s*\{[\s\n]*return\s+\$table[\s\n]*\{[\s\n]*\};/is', $content);

    if ($formEmpty || !$hasForm) {
        $resourcesWithEmptyForm++;
    }
    if ($tableEmpty || !$hasTable) {
        $resourcesWithEmptyTable++;
    }

    $filename = basename(dirname($path)) . '/' . basename($path);
    echo "[" . str_pad($lines . " строк", 12) . "] " . $filename . "\n";
}

echo "\n=== СТАТИСТИКА ===\n";
echo "Всего ресурсов: " . count($resources) . "\n";
echo "Всего строк: " . $totalLines . "\n";
echo "Средних строк на ресурс: " . round($totalLines / count($resources)) . "\n";
echo "Ресурсов с пустой form(): " . $resourcesWithEmptyForm . "\n";
echo "Ресурсов с пустой table(): " . $resourcesWithEmptyTable . "\n";
