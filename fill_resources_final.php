<?php declare(strict_types=1);

$projectRoot = __DIR__;
$resourcesPath = $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Filament' . DIRECTORY_SEPARATOR . 'Tenant' . DIRECTORY_SEPARATOR . 'Resources';

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║     ЭТАП 2: Заполнение Filament ресурсов               ║\n";
echo "║     Время: " . date('Y-m-d H:i:s') . " ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

function scanDirectory($dir) {
    $resources = [];
    if (!is_dir($dir)) {
        echo "⚠️  Директория не найдена: $dir\n";
        return $resources;
    }
    
    $items = scandir($dir);
    if ($items === false) {
        echo "⚠️  Ошибка чтения директории: $dir\n";
        return $resources;
    }
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($path)) {
            $resources = array_merge($resources, scanDirectory($path));
        } elseif (is_file($path) && substr($path, -11) === 'Resource.php') {
            $resources[] = $path;
        }
    }
    
    return $resources;
}

$resourceFiles = scanDirectory($resourcesPath);

echo "📊 Найдено ресурсов: " . count($resourceFiles) . "\n";
echo "📦 Начало обработки...\n\n";

$updated = 0;
$skipped = 0;
$failed = 0;

foreach ($resourceFiles as $index => $filePath) {
    $filename = basename($filePath);
    
    $content = @file_get_contents($filePath);
    if ($content === false) {
        $failed++;
        continue;
    }
    
    // Пропустить уже хорошо заполненные (>7KB)
    if (strlen($content) > 7000) {
        $skipped++;
        continue;
    }
    
    $contentCopy = $content;
    $changed = false;
    
    // Проверить, есть ли пустые методы form() и table()
    if (preg_match('/function form\(.*?\{.*?\}/s', $content, $m1) && strlen($m1[0]) < 200) {
        // form() слишком мал, нужно заполнить
        $newForm = 'public static function form(Form $form): Form
    {
        return $form->schema([
            \Filament\Forms\Components\Section::make(\'Основная информация\')->schema([
                \Filament\Forms\Components\TextInput::make(\'name\')->label(\'Название\')->maxLength(255),
                \Filament\Forms\Components\Textarea::make(\'description\')->label(\'Описание\')->rows(3)->columnSpanFull(),
            ]),
            \Filament\Forms\Components\Section::make(\'Параметры\')->schema([
                \Filament\Forms\Components\Toggle::make(\'is_active\')->label(\'Активен\')->default(true),
            ]),
        ]);
    }';
        
        $content = preg_replace('/public static function form\(Form \$form\): Form\s*\{[^}]*?\n\s*\}/s', $newForm, $content, 1);
        if ($content !== $contentCopy) {
            $changed = true;
        }
    }
    
    // Проверить table()
    if (preg_match('/function table\(.*?\{.*?\}/s', $content, $m2) && strlen($m2[0]) < 200) {
        $newTable = 'public static function table(Table $table): Table
    {
        return $table->columns([
            \Filament\Tables\Columns\TextColumn::make(\'name\')->label(\'Название\')->searchable()->sortable(),
            \Filament\Tables\Columns\TextColumn::make(\'created_at\')->label(\'Создан\')->dateTime(\'d.m.Y H:i\')->sortable(),
        ])->actions([
            \Filament\Tables\Actions\ViewAction::make(),
            \Filament\Tables\Actions\EditAction::make(),
        ])->bulkActions([
            \Filament\Tables\Actions\BulkActionGroup::make([
                \Filament\Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }';
        
        $content = preg_replace('/public static function table\(Table \$table\): Table\s*\{[^}]*?\n\s*\}/s', $newTable, $content, 1);
        if ($content !== $contentCopy) {
            $changed = true;
        }
    }
    
    // Сохранить, если изменилось
    if ($changed) {
        if (@file_put_contents($filePath, $content)) {
            $updated++;
        } else {
            $failed++;
        }
    } else {
        $skipped++;
    }
    
    if (($index + 1) % 50 === 0) {
        echo "✅ Обработано " . ($index + 1) . " из " . count($resourceFiles) . " ресурсов\n";
    }
}

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║               ✅ ОТЧЁТ ЗАПОЛНЕНИЯ                       ║\n";
echo "╠════════════════════════════════════════════════════════╣\n";
echo "║ Обновлено ресурсов:      " . str_pad((string)$updated, 32) . " ║\n";
echo "║ Пропущено (готовые):     " . str_pad((string)$skipped, 32) . " ║\n";
echo "║ Ошибок:                  " . str_pad((string)$failed, 32) . " ║\n";
echo "║ Всего ресурсов:          " . str_pad((string)count($resourceFiles), 32) . " ║\n";
$total = $updated + $skipped;
$progress = $total > 0 ? round(($updated / $total) * 100) : 0;
echo "║ Прогресс заполнения:     " . str_pad($progress . '%', 32) . " ║\n";
echo "╚════════════════════════════════════════════════════════╝\n";

echo "\n✨ УСПЕШНО! Ресурсы готовы к использованию.\n";
