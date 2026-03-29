<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Sports;

use App\Domains\Sports\Models\SportProduct;
use Filament\Forms\{Form, Components\Section, Components\TextInput, Components\Select, Components\RichEditor, Components\Toggle, Components\TagsInput, Components\Hidden, Components\FileUpload, Components\Repeater, Components\Grid};
use Filament\Resources\Resource;
use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\Filter, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter};
use Filament\Tables\Actions\{Action, EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction, ActionGroup};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

final class SportProductResource extends Resource
{
    protected static ?string $model = SportProduct::class;
    protected static ?string $navigationIcon = 'heroicon-o-fire';
    protected static ?string $navigationGroup = 'Sports & Active';
    protected static ?string $label = 'Товары';
    protected static ?string $pluralLabel = 'Спорттовары';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')
                ->icon('heroicon-m-tag')
                ->description('Базовые данные товара')
                ->schema([
                    TextInput::make('uuid')
                        ->label('UUID')
                        ->default(fn () => Str::uuid())
                        ->disabled()
                        ->dehydrated()
                        ->columnSpan(2),

                    TextInput::make('sku')
                        ->label('SKU')
                        ->required()
                        ->unique('sport_products', 'sku', ignoreRecord: true)
                        ->columnSpan(2),

                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->columnSpan(2),

                    Select::make('brand_id')
                        ->label('Бренд')
                        ->relationship('brand', 'name')
                        ->searchable()
                        ->columnSpan(2),

                    Select::make('sport_type')
                        ->label('Вид спорта')
                        ->options([
                            'fitness' => '🏋️ Фитнес',
                            'running' => '🏃 Бег',
                            'cycling' => '🚴 Велоспорт',
                            'hiking' => '⛰️ Туризм',
                            'swimming' => '🏊 Плавание',
                            'team_sports' => '⚽ Командные спорты',
                            'tennis' => '🎾 Теннис',
                            'yoga' => '🧘 Йога',
                            'water_sports' => '🏄 Водные виды',
                            'winter_sports' => '⛷️ Зимние виды',
                            'outdoor' => '🏕️ Туризм и кемпинг',
                        ])
                        ->required()
                        ->columnSpan(2),

                    Select::make('category_id')
                        ->label('Категория')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->columnSpan(2),

                    RichEditor::make('description')
                        ->label('Описание')
                        ->columnSpan('full'),

                    FileUpload::make('main_image')
                        ->label('Основное фото')
                        ->image()
                        ->directory('sports')
                        ->required()
                        ->columnSpan(1),

                    FileUpload::make('gallery_images')
                        ->label('Галерея')
                        ->image()
                        ->multiple()
                        ->directory('sports')
                        ->columnSpan(1),
                ])->columns(4),

            Section::make('Характеристики')
                ->icon('heroicon-m-adjustments-vertical')
                ->description('Спецификация товара')
                ->schema([
                    TextInput::make('material')
                        ->label('Материал')
                        ->columnSpan(2),

                    TextInput::make('color')
                        ->label('Цвет')
                        ->columnSpan(2),

                    TextInput::make('weight')
                        ->label('Вес (г)')
                        ->numeric()
                        ->columnSpan(1),

                    TextInput::make('dimensions')
                        ->label('Размеры')
                        ->columnSpan(1),

                    Select::make('difficulty_level')
                        ->label('Уровень сложности')
                        ->options([
                            'beginner' => '🟢 Начинающий',
                            'amateur' => '🟡 Любитель',
                            'professional' => '🔴 Профессионал',
                            'universal' => '🔵 Универсальный',
                        ])
                        ->columnSpan(2),

                    TextInput::make('warranty_months')
                        ->label('Гарантия (месяцы)')
                        ->numeric()
                        ->columnSpan(2),

                    TagsInput::make('tags')
                        ->label('Теги/Особенности')
                        ->columnSpan('full'),
                ])->columns(4),

                Section::make('Размеры и варианты')
                    ->icon('heroicon-m-squares-2x2')
                    ->description('Размерная сетка')
                    ->schema([
                        Repeater::make('variants')
                            ->label('Варианты размеров')
                            ->relationship()
                            ->schema([
                                TextInput::make('size')
                                    ->label('Размер')
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('price')
                                    ->label('Цена (₽)')
                                    ->numeric()
                                    ->required()
                                    ->suffix('₽')
                                    ->columnSpan(1),

                                TextInput::make('stock')
                                    ->label('Остаток')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(1),

                                Toggle::make('is_active')
                                    ->label('Активен')
                                    ->default(true)
                                    ->columnSpan(1),
                            ])
                            ->columns(4)
                            ->columnSpan('full'),
                    ])->columnSpan('full'),

            Section::make('Цены и комиссии')
                ->icon('heroicon-m-banknote')
                ->description('Финансовые параметры')
                ->schema([
                    TextInput::make('price_base')
                        ->label('Базовая цена (₽)')
                        ->numeric()
                        ->required()
                        ->suffix('₽')
                        ->columnSpan(2),

                    TextInput::make('discount_percent')
                        ->label('Скидка (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->columnSpan(1),

                    TextInput::make('final_price')
                        ->label('Финальная цена (₽)')
                        ->numeric()
                        ->disabled()
                        ->columnSpan(1),

                    TextInput::make('commission_percent')
                        ->label('Комиссия платформы (%)')
                        ->numeric()
                        ->disabled()
                        ->columnSpan(2),

                    TextInput::make('cost_price')
                        ->label('Себестоимость (₽)')
                        ->numeric()
                        ->columnSpan(2),
                ])->columns(4),

            Section::make('Рейтинг и популярность')
                ->icon('heroicon-m-star')
                ->description('Оценки пользователей')
                ->schema([
                    TextInput::make('rating')
                        ->label('Рейтинг')
                        ->numeric()
                        ->disabled()
                        ->columnSpan(2),

                    TextInput::make('review_count')
                        ->label('Количество отзывов')
                        ->numeric()
                        ->disabled()
                        ->columnSpan(2),

                    TextInput::make('sales_count')
                        ->label('Продано')
                        ->numeric()
                        ->disabled()
                        ->columnSpan(2),

                    TextInput::make('return_rate')
                        ->label('% возвратов')
                        ->numeric()
                        ->disabled()
                        ->columnSpan(2),
                ])->columns(4),

            Section::make('Статус и управление')
                ->icon('heroicon-m-cog-6-tooth')
                ->description('Видимость и активность')
                ->schema([
                    Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true)
                        ->columnSpan(1),

                    Toggle::make('is_featured')
                        ->label('⭐ Рекомендуемый')
                        ->columnSpan(1),

                    Toggle::make('is_verified')
                        ->label('✓ Проверен')
                        ->columnSpan(1),

                    Toggle::make('is_bestseller')
                        ->label('🏆 Бестселлер')
                        ->columnSpan(1),

                    TextInput::make('position')
                        ->label('Позиция в каталоге')
                        ->numeric()
                        ->columnSpan(2),
                ])->columns(4),

            Section::make('Служебная информация')
                ->icon('heroicon-m-cog-6-tooth')
                ->schema([
                    Hidden::make('tenant_id')
                        ->default(fn () => tenant('id')),

                    Hidden::make('correlation_id')
                        ->default(fn () => Str::uuid()),

                    Hidden::make('business_group_id')
                        ->default(fn () => filament()->getTenant()?->active_business_group_id),

                    TextInput::make('created_at')
                        ->label('Создан')
                        ->disabled()
                        ->columnSpan(2),

                    TextInput::make('updated_at')
                        ->label('Обновлён')
                        ->disabled()
                        ->columnSpan(2),
                ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')
                ->label('Название')
                ->searchable()
                ->sortable()
                ->icon('heroicon-m-tag')
                ->limit(40),

            TextColumn::make('sku')
                ->label('SKU')
                ->searchable()
                ->sortable()
                ->limit(12),

            TextColumn::make('brand.name')
                ->label('Бренд')
                ->searchable()
                ->sortable(),

            BadgeColumn::make('sport_type')
                ->label('Вид спорта')
                ->formatStateUsing(fn ($state) => match($state) {
                    'fitness' => 'Фитнес',
                    'running' => 'Бег',
                    'cycling' => 'Велоспорт',
                    'hiking' => 'Туризм',
                    'swimming' => 'Плавание',
                    'team_sports' => 'Командные',
                    'tennis' => 'Теннис',
                    'yoga' => 'Йога',
                    'water_sports' => 'Водные',
                    'winter_sports' => 'Зимние',
                    default => $state,
                })
                ->color(fn ($state) => match($state) {
                    'fitness' => 'purple',
                    'running' => 'blue',
                    'cycling' => 'info',
                    'swimming' => 'cyan',
                    'yoga' => 'pink',
                    'team_sports' => 'red',
                    default => 'gray',
                }),

            TextColumn::make('category.name')
                ->label('Категория')
                ->searchable()
                ->sortable(),

            TextColumn::make('color')
                ->label('Цвет'),

            TextColumn::make('price_base')
                ->label('Цена (₽)')
                ->money('RUB', divideBy: 100)
                ->sortable(),

            TextColumn::make('discount_percent')
                ->label('Скидка (%)')
                ->alignment('center'),

            TextColumn::make('final_price')
                ->label('Финал (₽)')
                ->money('RUB', divideBy: 100)
                ->sortable(),

            TextColumn::make('rating')
                ->label('Рейтинг')
                ->formatStateUsing(fn ($state) => '★ ' . number_format($state, 1))
                ->badge()
                ->color(fn ($state) => match(true) {
                    $state >= 4.5 => 'success',
                    $state >= 4 => 'info',
                    $state >= 3.5 => 'warning',
                    default => 'danger',
                })
                ->sortable(),

            TextColumn::make('review_count')
                ->label('Отзывы')
                ->numeric()
                ->alignment('center'),

            TextColumn::make('sales_count')
                ->label('Продано')
                ->numeric()
                ->alignment('center'),

            TextColumn::make('return_rate')
                ->label('Возвраты (%)')
                ->alignment('center'),

            BooleanColumn::make('is_bestseller')
                ->label('🏆 Бестселлер')
                ->toggleable(),

            BooleanColumn::make('is_featured')
                ->label('⭐ Рекомендуемый')
                ->toggleable(),

            BooleanColumn::make('is_verified')
                ->label('✓ Проверен')
                ->toggleable(),

            BooleanColumn::make('is_active')
                ->label('Активен')
                ->toggleable()
                ->sortable(),

            TextColumn::make('created_at')
                ->label('Создан')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            SelectFilter::make('sport_type')
                ->label('Вид спорта')
                ->options([
                    'fitness' => 'Фитнес',
                    'running' => 'Бег',
                    'cycling' => 'Велоспорт',
                    'hiking' => 'Туризм',
                    'swimming' => 'Плавание',
                    'team_sports' => 'Командные спорты',
                    'tennis' => 'Теннис',
                    'yoga' => 'Йога',
                    'water_sports' => 'Водные виды',
                    'winter_sports' => 'Зимние виды',
                ])
                ->multiple(),

            SelectFilter::make('brand_id')
                ->label('Бренд')
                ->relationship('brand', 'name')
                ->searchable()
                ->multiple(),

            SelectFilter::make('category_id')
                ->label('Категория')
                ->relationship('category', 'name')
                ->searchable()
                ->multiple(),

            SelectFilter::make('difficulty_level')
                ->label('Уровень')
                ->options([
                    'beginner' => 'Начинающий',
                    'amateur' => 'Любитель',
                    'professional' => 'Профессионал',
                    'universal' => 'Универсальный',
                ])
                ->multiple(),

            TernaryFilter::make('is_featured')
                ->label('Рекомендуемый'),

            TernaryFilter::make('is_verified')
                ->label('Проверен'),

            TernaryFilter::make('is_bestseller')
                ->label('Бестселлер'),

            TernaryFilter::make('is_active')
                ->label('Активен'),

            Filter::make('high_rating')
                ->label('Высокий рейтинг (≥4.0)')
                ->query(fn (Builder $query) => $query->where('rating', '>=', 4.0)),

            Filter::make('discounted')
                ->label('Со скидкой')
                ->query(fn (Builder $query) => $query->where('discount_percent', '>', 0)),

            Filter::make('popular')
                ->label('Популярное (>50 продаж)')
                ->query(fn (Builder $query) => $query->where('sales_count', '>', 50)),

            TrashedFilter::make(),
        ])
        ->actions([
            ActionGroup::make([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),

                Action::make('verify')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->label('Подтвердить')
                    ->visible(fn ($record) => !$record->is_verified)
                    ->action(function ($record) {
                        $record->update(['is_verified' => true]);
                        Log::channel('audit')->info('Sport product verified', [
                            'product_id' => $record->id,
                            'user_id' => auth()->id(),
                            'correlation_id' => $record->correlation_id,
                        ]);
                    })
                    ->successNotification(),

                Action::make('feature')
                    ->icon('heroicon-m-star')
                    ->color('warning')
                    ->label('В рекомендуемые')
                    ->visible(fn ($record) => !$record->is_featured)
                    ->action(function ($record) {
                        $record->update(['is_featured' => true]);
                        Log::channel('audit')->info('Sport product featured', [
                            'product_id' => $record->id,
                            'user_id' => auth()->id(),
                            'correlation_id' => $record->correlation_id,
                        ]);
                    })
                    ->successNotification(),

                Action::make('mark_bestseller')
                    ->icon('heroicon-m-fire')
                    ->color('danger')
                    ->label('Бестселлер')
                    ->visible(fn ($record) => !$record->is_bestseller)
                    ->action(function ($record) {
                        $record->update(['is_bestseller' => true]);
                        Log::channel('audit')->info('Sport product marked as bestseller', [
                            'product_id' => $record->id,
                            'user_id' => auth()->id(),
                            'correlation_id' => $record->correlation_id,
                        ]);
                    })
                    ->successNotification(),
            ]),
        ])
        ->bulkActions([
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->action(function ($records) {
                        $records->each(function ($record) {
                            Log::channel('audit')->info('Sport product bulk deleted', [
                                'product_id' => $record->id,
                                'user_id' => auth()->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        });
                    }),

                BulkAction::make('activate')
                    ->label('Активировать')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->action(function ($records) {
                        $records->each(function ($record) {
                            $record->update(['is_active' => true]);
                            Log::channel('audit')->info('Sport product activated', [
                                'product_id' => $record->id,
                                'user_id' => auth()->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        });
                    })
                    ->deselectRecordsAfterCompletion()
                    ->successNotification(),

                BulkAction::make('verify')
                    ->label('Подтвердить')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->action(function ($records) {
                        $records->each(function ($record) {
                            $record->update(['is_verified' => true]);
                            Log::channel('audit')->info('Sport product bulk verified', [
                                'product_id' => $record->id,
                                'user_id' => auth()->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        });
                    })
                    ->deselectRecordsAfterCompletion()
                    ->successNotification(),
            ]),
        ])
        ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\Sports\SportProductResource\Pages\ListSportProducts::route('/'),
            'create' => \App\Filament\Tenant\Resources\Sports\SportProductResource\Pages\CreateSportProduct::route('/create'),
            'view' => \App\Filament\Tenant\Resources\Sports\SportProductResource\Pages\ViewSportProduct::route('/{record}'),
            'edit' => \App\Filament\Tenant\Resources\Sports\SportProductResource\Pages\EditSportProduct::route('/{record}/edit'),
        ];
    }

    protected static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant('id'));
    }
}
