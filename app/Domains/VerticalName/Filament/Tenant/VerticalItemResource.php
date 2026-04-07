<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Filament\Tenant;

use App\Domains\VerticalName\Models\VerticalItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Filament Resource: управление VerticalItem в Tenant Panel.
 *
 * CANON 2026 — Layer 9: Filament (только B2B / Tenant Panel).
 * Доступ: только владельцы бизнеса и менеджеры через /tenant.
 * Все данные tenant-scoped автоматически через модель.
 *
 * @package App\Domains\VerticalName\Filament\Tenant
 */
final class VerticalItemResource extends Resource
{
    protected static ?string $model = VerticalItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'VerticalName';

    protected static ?string $navigationLabel = 'Товары';

    protected static ?string $modelLabel = 'Товар';

    protected static ?string $pluralModelLabel = 'Товары';

    protected static ?int $navigationSort = 1;

    /**
     * Форма создания/редактирования товара.
     *
     * Включает: основные поля, цена, SKU, инвентарь, B2B-настройки, теги.
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')
                ->description('Название, описание и категория товара.')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->minLength(2)
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->label('Описание')
                        ->maxLength(5000)
                        ->rows(4)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('category')
                        ->label('Категория')
                        ->maxLength(100),

                    Forms\Components\TextInput::make('sku')
                        ->label('Артикул (SKU)')
                        ->maxLength(100)
                        ->unique(ignoreRecord: true),
                ])
                ->columns(2),

            Forms\Components\Section::make('Цена и инвентарь')
                ->description('Ценообразование и управление остатками.')
                ->schema([
                    Forms\Components\TextInput::make('price_kopecks')
                        ->label('Цена (копейки)')
                        ->required()
                        ->numeric()
                        ->minValue(100)
                        ->maxValue(100000000)
                        ->helperText('1 рубль = 100 копеек. Минимум 100 (1 ₽).'),

                    Forms\Components\TextInput::make('stock_quantity')
                        ->label('Остаток на складе')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(1000000)
                        ->default(0),
                ])
                ->columns(2),

            Forms\Components\Section::make('Настройки доступности')
                ->description('Публикация, B2B-доступность и изображение.')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true)
                        ->helperText('Неактивные товары скрыты из каталога.'),

                    Forms\Components\Toggle::make('is_b2b_available')
                        ->label('Доступен для B2B')
                        ->default(false)
                        ->helperText('Отображается в B2B-каталоге по оптовым ценам.'),

                    Forms\Components\Select::make('status')
                        ->label('Статус')
                        ->options([
                            'draft' => 'Черновик',
                            'published' => 'Опубликован',
                            'archived' => 'В архиве',
                        ])
                        ->default('draft')
                        ->required(),

                    Forms\Components\TextInput::make('image_url')
                        ->label('URL изображения')
                        ->url()
                        ->maxLength(2048),
                ])
                ->columns(2),

            Forms\Components\Section::make('Теги и метаданные')
                ->schema([
                    Forms\Components\TagsInput::make('tags')
                        ->label('Теги')
                        ->helperText('Максимум 20 тегов.')
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ]);
    }

    /**
     * Таблица списка товаров.
     *
     * Колонки: имя, категория, цена, остаток, рейтинг, статус, B2B.
     * Фильтры: категория, статус, наличие, B2B.
     * Действия: edit, delete.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('category')
                    ->label('Категория')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('price_kopecks')
                    ->label('Цена')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ') . ' ₽')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Остаток')
                    ->sortable()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable()
                    ->formatStateUsing(fn (float $state): string => number_format($state, 1) . ' ★'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_b2b_available')
                    ->label('B2B')
                    ->boolean(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'warning',
                        'archived' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'published' => 'Опубликован',
                        'archived' => 'В архиве',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),

                Tables\Filters\TernaryFilter::make('is_b2b_available')
                    ->label('B2B-доступность'),

                Tables\Filters\Filter::make('in_stock')
                    ->label('В наличии')
                    ->query(fn ($query) => $query->where('stock_quantity', '>', 0)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Страницы ресурса.
     *
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\VerticalName\Filament\Tenant\Pages\ListVerticalItems::route('/'),
            'create' => \App\Domains\VerticalName\Filament\Tenant\Pages\CreateVerticalItem::route('/create'),
            'edit' => \App\Domains\VerticalName\Filament\Tenant\Pages\EditVerticalItem::route('/{record}/edit'),
        ];
    }
}
