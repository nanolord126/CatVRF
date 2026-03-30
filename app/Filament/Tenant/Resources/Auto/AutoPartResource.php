<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AutoPartResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = AutoPart::class;

        protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

        protected static ?string $navigationGroup = 'Автосервис';

        protected static ?string $label = 'Запчасть';

        protected static ?string $pluralLabel = 'Запчасти';

        public static function form(Forms\Form $form): Forms\Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Основная информация')
                        ->icon('heroicon-m-cog-6-tooth')
                        ->description('Данные о запчасти')
                        ->schema([
                            Forms\Components\TextInput::make('uuid')
                                ->label('UUID')
                                ->default(fn () => Str::uuid())
                                ->disabled()
                                ->dehydrated()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('sku')
                                ->label('Артикул (SKU)')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->copyable()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('name')
                                ->label('Название')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('oem_number')
                                ->label('OEM Номер')
                                ->maxLength(100)
                                ->columnSpan(2),

                            Forms\Components\Select::make('auto_catalog_brand_id')
                                ->label('Производитель')
                                ->relationship('brand', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(2),

                            Forms\Components\Select::make('category')
                                ->label('Категория')
                                ->options([
                                    'engine' => 'Двигатель',
                                    'suspension' => 'Подвеска',
                                    'brakes' => 'Тормоза',
                                    'electrical' => 'Электрика',
                                    'cooling' => 'Охлаждение',
                                    'fuel' => 'Топливная система',
                                    'transmission' => 'Коробка передач',
                                    'interior' => 'Салон',
                                    'exterior' => 'Внешнее оборудование',
                                    'tools' => 'Инструменты',
                                ])
                                ->required()
                                ->columnSpan(2),

                            Forms\Components\Textarea::make('description')
                                ->label('Описание')
                                ->maxLength(2000)
                                ->columnSpan(4),
                        ])->columns(4),

                    Forms\Components\Section::make('Цены и рентабельность')
                        ->icon('heroicon-m-banknote')
                        ->description('Прайс-лист')
                        ->schema([
                            Forms\Components\TextInput::make('cost_price_kopecks')
                                ->label('Себестоимость (коп)')
                                ->numeric()
                                ->default(0)
                                ->suffix('₽')
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('wholesale_price_kopecks')
                                ->label('Оптовая цена (коп)')
                                ->numeric()
                                ->required()
                                ->suffix('₽')
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('price_kopecks')
                                ->label('Розничная цена (коп)')
                                ->numeric()
                                ->required()
                                ->suffix('₽')
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('margin_percent')
                                ->label('Маржа (%)')
                                ->numeric()
                                ->default(30)
                                ->disabled()
                                ->dehydrated()
                                ->columnSpan(2),
                        ])->columns(4),

                    Forms\Components\Section::make('Управление запасами')
                        ->icon('heroicon-m-squares-2x2')
                        ->description('Остатки и пороги')
                        ->schema([
                            Forms\Components\TextInput::make('stock_quantity')
                                ->label('Текущий остаток')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('min_threshold')
                                ->label('Минимальный порог')
                                ->numeric()
                                ->default(5)
                                ->required()
                                ->helperText('При достижении отправляется уведомление')
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('reorder_point')
                                ->label('Точка переоформления заказа')
                                ->numeric()
                                ->default(10)
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('max_stock')
                                ->label('Максимальный запас')
                                ->numeric()
                                ->default(100)
                                ->columnSpan(2),

                            Forms\Components\Toggle::make('low_stock_email')
                                ->label('Отправлять email при низком остатке')
                                ->default(true)
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('last_reorder_date')
                                ->label('Последний заказ')
                                ->disabled()
                                ->columnSpan(2),
                        ])->columns(4),

                    Forms\Components\Section::make('Совместимость')
                        ->icon('heroicon-m-link')
                        ->description('Модели автомобилей')
                        ->schema([
                            Forms\Components\TagsInput::make('compatibility_masks')
                                ->label('Маски VIN для совместимости')
                                ->placeholder('Добавить маску VIN')
                                ->columnSpan(2),

                            Forms\Components\TagsInput::make('compatible_models')
                                ->label('Совместимые модели')
                                ->placeholder('Марка Model')
                                ->columnSpan(2),

                            Forms\Components\TagsInput::make('tags')
                                ->label('Теги для поиска')
                                ->placeholder('Добавить тег')
                                ->columnSpan(4),
                        ])->columns(4),

                    Forms\Components\Section::make('Поставщик')
                        ->icon('heroicon-m-truck')
                        ->description('Информация поставщика')
                        ->schema([
                            Forms\Components\Select::make('supplier_id')
                                ->label('Поставщик')
                                ->relationship('supplier', 'name')
                                ->searchable()
                                ->preload()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('supplier_sku')
                                ->label('Артикул поставщика')
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('lead_time_days')
                                ->label('Время доставки (дни)')
                                ->numeric()
                                ->default(7)
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('minimum_order_quantity')
                                ->label('Минимальный заказ')
                                ->numeric()
                                ->default(1)
                                ->columnSpan(2),
                        ])->columns(4),

                    Forms\Components\Section::make('Служебная информация')
                        ->icon('heroicon-m-cog-6-tooth')
                        ->schema([
                            Forms\Components\Hidden::make('tenant_id')
                                ->default(fn () => tenant('id')),

                            Forms\Components\Hidden::make('correlation_id')
                                ->default(fn () => Str::uuid()),

                            Forms\Components\Hidden::make('business_group_id')
                                ->default(fn () => filament()->getTenant()?->active_business_group_id),

                            Forms\Components\TextInput::make('created_at')
                                ->label('Создан')
                                ->disabled()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('updated_at')
                                ->label('Обновлён')
                                ->disabled()
                                ->columnSpan(2),
                        ])->columns(4),
                ]);
        }

        public static function table(Tables\Table $table): Tables\Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('uuid')
                        ->label('UUID')
                        ->copyable()
                        ->hidden()
                        ->searchable(),

                    Tables\Columns\TextColumn::make('sku')
                        ->label('Артикул (SKU)')
                        ->searchable()
                        ->sortable()
                        ->copyable()
                        ->icon('heroicon-m-qr-code')
                        ->weight('bold'),

                    Tables\Columns\TextColumn::make('name')
                        ->label('Название')
                        ->searchable()
                        ->sortable()
                        ->limit(40),

                    Tables\Columns\TextColumn::make('brand.name')
                        ->label('Производитель')
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('category')
                        ->label('Категория')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'engine' => 'primary',
                            'suspension' => 'info',
                            'brakes' => 'danger',
                            'electrical' => 'warning',
                            'cooling' => 'success',
                            default => 'gray',
                        })
                        ->sortable(),

                    Tables\Columns\TextColumn::make('cost_price_kopecks')
                        ->label('Себестоимость')
                        ->money('RUB', divideBy: 100)
                        ->hidden()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('wholesale_price_kopecks')
                        ->label('Оптовая цена')
                        ->money('RUB', divideBy: 100)
                        ->sortable(),

                    Tables\Columns\TextColumn::make('price_kopecks')
                        ->label('Розничная цена')
                        ->money('RUB', divideBy: 100)
                        ->sortable()
                        ->weight('bold'),

                    Tables\Columns\TextColumn::make('margin_percent')
                        ->label('Маржа (%)')
                        ->formatStateUsing(fn ($state, $record) => $record->price_kopecks > 0 ? (int)(($record->price_kopecks - ($record->cost_price_kopecks ?? 0)) / $record->price_kopecks * 100) : 0)
                        ->badge()
                        ->color(fn ($record) => ((int)(($record->price_kopecks - ($record->cost_price_kopecks ?? 0)) / $record->price_kopecks * 100) ?? 0) > 40 ? 'success' : 'warning')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('stock_quantity')
                        ->label('Остаток')
                        ->numeric()
                        ->badge()
                        ->color(fn ($record) => $record->stock_quantity <= $record->min_threshold ? 'danger' : ($record->stock_quantity >= $record->max_stock ? 'info' : 'success'))
                        ->sortable(),

                    Tables\Columns\TextColumn::make('min_threshold')
                        ->label('Порог переза')
                        ->numeric()
                        ->hidden()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('lead_time_days')
                        ->label('Доставка (дн)')
                        ->numeric()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Добавлена')
                        ->dateTime('d.m.Y')
                        ->sortable()
                        ->hidden(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('brand')
                        ->label('Производитель')
                        ->relationship('brand', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable(),

                    Tables\Filters\SelectFilter::make('category')
                        ->label('Категория')
                        ->options([
                            'engine' => 'Двигатель',
                            'suspension' => 'Подвеска',
                            'brakes' => 'Тормоза',
                            'electrical' => 'Электрика',
                            'cooling' => 'Охлаждение',
                            'fuel' => 'Топливная система',
                            'transmission' => 'Коробка передач',
                            'interior' => 'Салон',
                            'exterior' => 'Внешнее оборудование',
                            'tools' => 'Инструменты',
                        ])
                        ->multiple()
                        ->preload()
                        ->searchable(),

                    Tables\Filters\Filter::make('low_stock')
                        ->label('Низкий остаток')
                        ->query(fn (Builder $query) => $query->whereColumn('stock_quantity', '<=', 'min_threshold')),

                    Tables\Filters\Filter::make('high_margin')
                        ->label('Маржа > 40%')
                        ->query(fn (Builder $query) => $query->whereRaw('CAST((price_kopecks - COALESCE(cost_price_kopecks, 0)) * 100 / price_kopecks AS INTEGER) > 40')),

                    Tables\Filters\Filter::make('needs_reorder')
                        ->label('Нужен переза кал')
                        ->query(fn (Builder $query) => $query->whereColumn('stock_quantity', '<=', 'reorder_point')),

                    Tables\Filters\TernaryFilter::make('low_stock_email')
                        ->label('Уведомления включены')
                        ->queries(
                            true: fn (Builder $query) => $query->where('low_stock_email', true),
                            false: fn (Builder $query) => $query->where('low_stock_email', false)
                        ),

                    Tables\Filters\TrashedFilter::make(),
                ])
                ->actions([
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\ViewAction::make(),
                        Tables\Actions\EditAction::make(),
                        Tables\Actions\DeleteAction::make()
                            ->after(function () {
                                Log::channel('audit')->info('AutoPart deleted', [
                                    'resource' => 'AutoPart',
                                    'user_id' => auth()->id(),
                                    'correlation_id' => Str::uuid(),
                                ]);
                            }),
                        Tables\Actions\RestoreAction::make()
                            ->after(function () {
                                Log::channel('audit')->info('AutoPart restored', [
                                    'resource' => 'AutoPart',
                                    'user_id' => auth()->id(),
                                    'correlation_id' => Str::uuid(),
                                ]);
                            }),
                    ]),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make()
                            ->after(function () {
                                Log::channel('audit')->info('AutoParts bulk deleted', [
                                    'resource' => 'AutoPart',
                                    'user_id' => auth()->id(),
                                    'correlation_id' => Str::uuid(),
                                ]);
                            }),
                        Tables\Actions\BulkAction::make('reorder_all')
                            ->label('Пересчитать на все')
                            ->icon('heroicon-m-arrow-path')
                            ->deselectRecordsAfterCompletion()
                            ->action(function (Collection $records) {
                                foreach ($records as $record) {
                                    $record->update(['last_reorder_date' => now()]);
                                    Log::channel('audit')->info('AutoPart reorder marked', [
                                        'resource' => 'AutoPart',
                                        'resource_id' => $record->id,
                                        'user_id' => auth()->id(),
                                        'correlation_id' => $record->correlation_id,
                                    ]);
                                }
                            }),
                    ]),
                ]);
        }

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages\ListAutoParts::route('/'),
                'create' => \App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages\CreateAutoPart::route('/create'),
                'view' => \App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages\ViewAutoPart::route('/{record}'),
                'edit' => \App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages\EditAutoPart::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()->id);
        }
}
