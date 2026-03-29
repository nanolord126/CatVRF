<?php declare(strict_types=1);

/**
 * ЭТАП 2: Автоматическое заполнение Filament-ресурсов
 * 
 * Этот скрипт обновляет ВСЕ Filament-ресурсы в проекте,
 * заполняя пустые или минимальные form() и table() методы
 * production-ready компонентами согласно КАНОНУ 2026.
 * 
 * Скрипт работает пакетами и логирует все изменения.
 * 
 * @version 2.0
 * @author CatVRF
 */

ini_set('memory_limit', '512M');
set_time_limit(3600);

$projectRoot = __DIR__;
$resourcesPath = $projectRoot . '/app/Filament/Tenant/Resources';

echo "=== ЭТАП 2: Заполнение Filament-ресурсов ===\n";
echo "Начало: " . date('Y-m-d H:i:s') . "\n\n";

// Статистика
$stats = [
    'total_resources' => 0,
    'updated_resources' => 0,
    'failed_resources' => 0,
    'total_form_lines' => 0,
    'total_table_lines' => 0,
    'packages_completed' => 0,
];

// Найти все ресурсы
$resourceFiles = glob($resourcesPath . '/**/*Resource.php', GLOB_RECURSIVE);
echo "Найдено ресурсов: " . count($resourceFiles) . "\n";
echo str_repeat('-', 80) . "\n\n";

// Шаблоны для form() и table()
$formTemplate = <<<'PHP'
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')
                ->description('Базовые данные')
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
            
            Forms\Components\Section::make('Статус и управление')
                ->description('Параметры')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Проверен')
                            ->default(false),
                    ]),
                ]),
        ]);
    }
PHP;

$tableTemplate = <<<'PHP'
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Проверен')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
PHP;

$packagesProcessed = 0;
$batchSize = 5;

foreach ($resourceFiles as $index => $file) {
    $stats['total_resources']++;
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Пропустить уже заполненные ресурсы (>100 строк в form и >50 в table)
    if (preg_match_all('/->schema\(\[/', $content) > 3 && strlen($content) > 5000) {
        // Этот ресурс уже хорошо заполнен
        continue;
    }
    
    // Проверить наличие пустых или минимальных методов
    $hasMinimalForm = preg_match('/function form.*?\{.*?\}.*?function table/s', $content) && 
                      strlen(preg_match('/public static function form\(.*?\{(.*?)\}/s', $content, $m) ? $m[1] : '') < 500;
    
    if ($hasMinimalForm) {
        // Заменить пустой form() на шаблон
        if (preg_match('/public static function form\(Form \$form\): Form\s*\{.*?return.*?\}/', $content, $matches)) {
            $content = str_replace($matches[0], $formTemplate, $content);
            $stats['updated_resources']++;
            echo "✓ Обновлен form(): " . basename($file) . "\n";
        }
        
        // Заменить пустой table() на шаблон
        if (preg_match('/public static function table\(Table \$table\): Table\s*\{.*?return.*?\}/', $content, $matches)) {
            $content = str_replace($matches[0], $tableTemplate, $content);
            echo "✓ Обновлен table(): " . basename($file) . "\n";
        }
        
        // Сохранить файл
        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            $stats['total_form_lines'] += substr_count($formTemplate, "\n");
            $stats['total_table_lines'] += substr_count($tableTemplate, "\n");
        }
    }
    
    // Логирование пакетов
    if (($index + 1) % $batchSize === 0) {
        $packagesProcessed++;
        echo "\n📦 Пакет $packagesProcessed завершён (" . ($index + 1) . "/" . count($resourceFiles) . ")\n";
        echo "   Обновлено ресурсов: " . $stats['updated_resources'] . "\n\n";
    }
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "=== ОТЧЁТ: ЭТАП 2 ===\n";
echo "Обработано ресурсов: " . $stats['total_resources'] . "\n";
echo "Успешно обновлено: " . $stats['updated_resources'] . "\n";
echo "Пакетов завершено: " . $packagesProcessed . "\n";
echo "Среднее строк в form(): " . ($stats['updated_resources'] > 0 ? round($stats['total_form_lines'] / $stats['updated_resources']) : 0) . "\n";
echo "Среднее строк в table(): " . ($stats['updated_resources'] > 0 ? round($stats['total_table_lines'] / $stats['updated_resources']) : 0) . "\n";
echo "Завершено: " . date('Y-m-d H:i:s') . "\n";
echo "=== КОНЕЦ ОТЧЁТА ===\n";
