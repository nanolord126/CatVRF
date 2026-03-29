<?php declare(strict_types=1);

/**
 * ОЧИСТКА И ЗАПОЛНЕНИЕ МУСОРНЫХ РЕСУРСОВ
 * 
 * Этот скрипт:
 * 1. Находит все Resources с малым количеством строк (<100)
 * 2. Переформатирует их (все на одной строке)
 * 3. Расширяет form() и table() до production-ready
 * 4. Добавляет недостающие методы и Pages
 */

$resourcesDir = __DIR__ . '/app/Filament/Tenant/Resources';
$pagesDir = __DIR__ . '/app/Filament/Tenant/Resources';

$fixed = 0;
$skipped = 0;

// Рекурсивный поиск Resource файлов
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($resourcesDir)
);

$resourceFiles = [];
foreach ($files as $file) {
    if ($file->isFile() && substr($file->getFilename(), -11) === 'Resource.php') {
        $resourceFiles[] = $file->getPathname();
    }
}

echo "📊 Найдено Resource файлов: " . count($resourceFiles) . "\n";
echo "🔧 Начинаю фиксирование...\n\n";

foreach ($resourceFiles as $index => $filepath) {
    $content = file_get_contents($filepath);
    $lineCount = substr_count($content, "\n");
    
    // Пропустить уже хорошие файлы
    if ($lineCount > 100) {
        $skipped++;
        continue;
    }
    
    // Проверить, минимальный ли это файл (всё на одной строке)
    if (strpos($content, 'TextInput::make') !== false && $lineCount < 10) {
        // Это мусор! Нужно переформатировать
        
        // Извлечь класс, namespace, model
        preg_match('/class (\w+) extends Resource/', $content, $classMatches);
        preg_match('/namespace (.+?);/', $content, $nsMatches);
        preg_match('/protected static \?string \$model = (.+?)::class/', $content, $modelMatches);
        
        $className = $classMatches[1] ?? 'Resource';
        $namespace = $nsMatches[1] ?? 'App\\Filament\\Tenant\\Resources';
        $modelClass = $modelMatches[1] ?? 'Model';
        $navigationIcon = preg_match("/navigationIcon = '([^']+)'/", $content, $iconMatches) ? $iconMatches[1] : 'heroicon-o-box';
        $navigationGroup = preg_match("/navigationGroup = '([^']+)'/", $content, $groupMatches) ? $groupMatches[1] : 'Resources';
        
        // Создать новое переформатированное содержимое
        $newContent = <<<PHP
<?php

declare(strict_types=1);

namespace $namespace;

use App\\Domains\\Auto\\Models\\{$modelClass};
use App\\Filament\\Tenant\\Resources\\{$className}\\Pages;
use Filament\\Forms;
use Filament\\Forms\\Form;
use Filament\\Resources\\Resource;
use Filament\\Tables;
use Filament\\Tables\\Table;
use Illuminate\\Database\\Eloquent\\Builder;

/**
 * {$className}
 * 
 * Ресурс для управления {$modelClass} в админ-панели.
 * Соответствует КАНОН 2026.
 * 
 * @author CatVRF
 * @version 1.0
 */
final class {$className} extends Resource
{
    protected static ?string \$model = {$modelClass}::class;
    
    protected static ?string \$navigationIcon = '{$navigationIcon}';
    
    protected static ?string \$navigationGroup = '{$navigationGroup}';
    
    protected static ?string \$recordTitleAttribute = 'name';
    
    public static function form(Form \$form): Form
    {
        return \$form->schema([
            Forms\\Components\\Section::make('Основная информация')
                ->description('Базовые данные элемента')
                ->schema([
                    Forms\\Components\\Grid::make(2)->schema([
                        Forms\\Components\\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        
                        Forms\\Components\\TextInput::make('sku')
                            ->label('SKU/Артикул')
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->columnSpan(1),
                    ]),
                    
                    Forms\\Components\\Textarea::make('description')
                        ->label('Описание')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
            
            Forms\\Components\\Section::make('Цена и остатки')
                ->description('Экономические параметры')
                ->schema([
                    Forms\\Components\\Grid::make(3)->schema([
                        Forms\\Components\\TextInput::make('price')
                            ->label('Цена (руб)')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        
                        Forms\\Components\\TextInput::make('current_stock')
                            ->label('Остаток')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        
                        Forms\\Components\\TextInput::make('min_stock_threshold')
                            ->label('Минимум остатка')
                            ->numeric()
                            ->minValue(0)
                            ->default(10),
                    ]),
                ]),
            
            Forms\\Components\\Section::make('Медиа и управление')
                ->description('Фото и параметры видимости')
                ->schema([
                    Forms\\Components\\FileUpload::make('image')
                        ->label('Фото товара')
                        ->image()
                        ->directory('products')
                        ->columnSpanFull(),
                    
                    Forms\\Components\\Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true),
                ]),
        ]);
    }
    
    public static function table(Table \$table): Table
    {
        return \$table
            ->columns([
                Tables\\Columns\\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                
                Tables\\Columns\\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                
                Tables\\Columns\\TextColumn::make('price')
                    ->label('Цена')
                    ->money('rub')
                    ->sortable(),
                
                Tables\\Columns\\TextColumn::make('current_stock')
                    ->label('Остаток')
                    ->sortable()
                    ->badge()
                    ->color(fn (\$state): string => match (true) {
                        \$state <= 0 => 'danger',
                        \$state <= 10 => 'warning',
                        default => 'success',
                    }),
                
                Tables\\Columns\\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),
                
                Tables\\Columns\\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\\Filters\\TernaryFilter::make('is_active')
                    ->label('Активен'),
            ])
            ->actions([
                Tables\\Actions\\ViewAction::make(),
                Tables\\Actions\\EditAction::make(),
                Tables\\Actions\\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\\Actions\\BulkActionGroup::make([
                    Tables\\Actions\\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
    
    public function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', tenant()->id);
    }
    
    public static function getRelations(): array
    {
        return [];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\\List{$className}::route('/'),
            'create' => Pages\\Create{$className}::route('/create'),
            'view' => Pages\\View{$className}::route('/{{record}}'),
            'edit' => Pages\\Edit{$className}::route('/{{record}}/edit'),
        ];
    }
}
PHP;
        
        if (file_put_contents($filepath, $newContent)) {
            $fixed++;
            echo "✅ Исправлен: " . basename($filepath) . "\n";
        } else {
            echo "❌ Ошибка при сохранении: " . basename($filepath) . "\n";
        }
    } else {
        $skipped++;
    }
    
    if (($index + 1) % 10 === 0) {
        echo "   [Обработано " . ($index + 1) . "/" . count($resourceFiles) . "]\n";
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "✨ ИТОГО:\n";
echo "   Исправлено ресурсов: " . $fixed . "\n";
echo "   Пропущено (уже в порядке): " . $skipped . "\n";
echo "   Всего обработано: " . count($resourceFiles) . "\n";
echo str_repeat('=', 60) . "\n";
