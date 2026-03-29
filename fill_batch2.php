<?php declare(strict_types=1);

/**
 * УМНОЕ ЗАПОЛНЕНИЕ FILAMENT-РЕСУРСОВ - ПАКЕТ 2 (ИСПРАВЛЕННАЯ ВЕРСИЯ)
 */

$projectRoot = __DIR__;
$resourcesPath = $projectRoot . '/app/Filament/Tenant/Resources';

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║     ЭТАП 2: ПАКЕТ 2 - Заполнение ресурсов              ║\n";
echo "║     Время: " . date('Y-m-d H:i:s') . " ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// Найти все ресурсы рекурсивно
function findResources($dir) {
    $resources = [];
    if (!is_dir($dir)) return $resources;
    
    $items = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($items as $item) {
        $path = $dir . '/' . $item;
        
        if (is_dir($path)) {
            $resources = array_merge($resources, findResources($path));
        } elseif (substr($item, -11) === 'Resource.php') {
            $resources[] = $path;
        }
    }
    
    return $resources;
}

$resourceFiles = findResources($resourcesPath);

echo "📊 Найдено ресурсов: " . count($resourceFiles) . "\n";
echo "📦 Начало обработки...\n\n";

$updated = 0;
$skipped = 0;

foreach ($resourceFiles as $index => $filePath) {
    $filename = basename($filePath);
    $content = file_get_contents($filePath);
    
    // Пропустить уже хорошо заполненные
    if (strlen($content) > 7000) {
        $skipped++;
        continue;
    }
    
    // Простой шаблон для всех
    $hasForm = strpos($content, 'public static function form') !== false;
    $hasTable = strpos($content, 'public static function table') !== false;
    
    if (!$hasForm || !$hasTable) {
        $skipped++;
        continue;
    }
    
    // Замена пустого form()
    if (preg_match('/public static function form\(Form \$form\): Form\s*\{[^}]{0,300}\}/', $content)) {
        $newForm = <<<'PHP'
public static function form(Form $form): Form
    {
        return $form->schema([
            \Filament\Forms\Components\Section::make('Информация')
                ->schema([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('Название')
                        ->maxLength(255),
                    
                    \Filament\Forms\Components\Textarea::make('description')
                        ->label('Описание')
                        ->rows(3),
                ]),
            
            \Filament\Forms\Components\Section::make('Параметры')
                ->schema([
                    \Filament\Forms\Components\Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true),
                ]),
        ]);
    }
PHP;
        
        $content = preg_replace(
            '/public static function form\(Form \$form\): Form\s*\{[^}]{0,300}\}/',
            $newForm,
            $content,
            1
        );
        $updated++;
    }
    
    // Замена пустого table()
    if (preg_match('/public static function table\(Table \$table\): Table\s*\{[^}]{0,300}\}/', $content)) {
        $newTable = <<<'PHP'
public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                
                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->actions([
                \Filament\Tables\Actions\ViewAction::make(),
                \Filament\Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
PHP;
        
        $content = preg_replace(
            '/public static function table\(Table \$table\): Table\s*\{[^}]{0,300}\}/',
            $newTable,
            $content,
            1
        );
    }
    
    // Сохранить
    if (file_put_contents($filePath, $content)) {
        if (($index + 1) % 10 === 0) {
            echo "✅ Обработано " . ($index + 1) . " ресурсов\n";
        }
    }
}

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║               ✅ ОТЧЁТ ПАКЕТА 2                         ║\n";
echo "╠════════════════════════════════════════════════════════╣\n";
echo "║ Обновлено ресурсов:  " . str_pad($updated, 35) . "║\n";
echo "║ Пропущено (готовые): " . str_pad($skipped, 35) . "║\n";
echo "║ Всего ресурсов:      " . str_pad(count($resourceFiles), 35) . "║\n";
echo "║ Прогресс:            " . str_pad(round((count($resourceFiles) - $skipped) / count($resourceFiles) * 100) . '%', 35) . "║\n";
echo "╚════════════════════════════════════════════════════════╝\n";
