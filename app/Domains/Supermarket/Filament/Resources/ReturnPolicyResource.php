<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Resources;

use App\Domains\Supermarket\Models\ReturnPolicy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * ReturnPolicyResource - Filament ресурс для управления политиками возврата.
 *
 * Позволяет администраторам:
 * - Создавать и редактировать политики возврата для разных под-вертикалей
 * - Настраивать сроки, причины, требования к доказательствам
 * - Управлять B2B/B2C политиками
 * - Просматривать историю изменений
 */
class ReturnPolicyResource extends Resource
{
    protected static ?string $model = ReturnPolicy::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Политики возврата';

    protected static ?string $modelLabel = 'Политика возврата';

    protected static ?string $pluralModelLabel = 'Политики возврата';

    protected static ?string $navigationGroup = 'Supermarket';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основные параметры')
                    ->schema([
                        Forms\Components\Select::make('vertical')
                            ->label('Вертикаль')
                            ->options([
                                'supermarket' => 'Supermarket',
                                'restaurant' => 'Restaurant',
                            ])
                            ->required()
                            ->default('supermarket')
                            ->disabled(),

                        Forms\Components\Select::make('sub_vertical')
                            ->label('Под-вертикаль')
                            ->options([
                                'meat_shops' => 'Мясные лавки',
                                'vegan_products' => 'Веган-продукты',
                                'confectionery' => 'Кондитерка',
                                'farm_direct' => 'Фермерские продукты',
                                'grocery_and_delivery' => 'Бакалея и доставка',
                                'household' => 'Товары для дома',
                                'food' => 'Готовая еда',
                                'bakery' => 'Выпечка',
                                'dairy' => 'Молочные продукты',
                            ])
                            ->nullable()
                            ->searchable()
                            ->helperText('Оставьте пустым для общей политики вертикали'),

                        Forms\Components\Select::make('customer_type')
                            ->label('Тип клиента')
                            ->options([
                                'b2c' => 'B2C (розница)',
                                'b2b' => 'B2B (опт)',
                            ])
                            ->required()
                            ->default('b2c'),

                        Forms\Components\TextInput::make('max_days')
                            ->label('Максимальное количество дней')
                            ->numeric()
                            ->required()
                            ->suffix('дней')
                            ->default(7)
                            ->minValue(1)
                            ->maxValue(365),

                        Forms\Components\TextInput::make('priority')
                            ->label('Приоритет')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->helperText('Выше = важнее. При конфликте применяется политика с более высоким приоритетом.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Разрешённые причины')
                    ->schema([
                        Forms\Components\CheckboxList::make('allowed_reasons')
                            ->label('Причины возврата')
                            ->options([
                                'spoiled' => 'Испорчено',
                                'wrong_item' => 'Не тот товар',
                                'changed_mind' => 'Передумал',
                                'damaged' => 'Повреждено',
                                'expired' => 'Просрочено',
                                'other' => 'Другое',
                            ])
                            ->required()
                            ->default(['spoiled', 'wrong_item', 'damaged']),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Требования к доказательствам')
                    ->schema([
                        Forms\Components\Toggle::make('cold_chain_only_defect')
                            ->label('Только при браке для холодной цепи')
                            ->default(false)
                            ->helperText('Для товаров с холодной цепью возврат возможен только при обнаружении брака'),

                        Forms\Components\Toggle::make('requires_photo')
                            ->label('Требуются фото доказательства')
                            ->default(false)
                            ->helperText('Покупатель должен предоставить фото товара'),

                        Forms\Components\Toggle::make('requires_temperature')
                            ->label('Требуется проверка температуры')
                            ->default(false)
                            ->helperText('Для холодной цепи требуется проверка температуры'),

                        Forms\Components\Toggle::make('requires_receipt')
                            ->label('Обязателен чек')
                            ->default(true)
                            ->helperText('Покупатель должен предоставить чек'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Лимиты возврата')
                    ->schema([
                        Forms\Components\TextInput::make('max_refund_percent')
                            ->label('Максимальный процент возврата')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->default(100)
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Максимальный процент от суммы, который может быть возвращён'),

                        Forms\Components\TextInput::make('max_refund_amount')
                            ->label('Максимальная сумма возврата')
                            ->numeric()
                            ->suffix('коп.')
                            ->nullable()
                            ->helperText('Максимальная сумма возврата в копейках. Оставьте пустым для безлимитного возврата.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Период действия')
                    ->schema([
                        Forms\Components\DateTimePicker::make('valid_from')
                            ->label('Действует с')
                            ->nullable()
                            ->seconds(false),

                        Forms\Components\DateTimePicker::make('valid_until')
                            ->label('Действует до')
                            ->nullable()
                            ->seconds(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->nullable()
                            ->helperText('Описание политики для внутреннего использования'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true)
                            ->helperText('Неактивные политики не применяются'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vertical')
                    ->label('Вертикаль')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('sub_vertical')
                    ->label('Под-вертикаль')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'meat_shops' => 'Мясные лавки',
                        'vegan_products' => 'Веган-продукты',
                        'confectionery' => 'Кондитерка',
                        'farm_direct' => 'Фермерские продукты',
                        'grocery_and_delivery' => 'Бакалея',
                        'household' => 'Товары для дома',
                        'food' => 'Готовая еда',
                        'bakery' => 'Выпечка',
                        'dairy' => 'Молочные продукты',
                        null => 'Общая политика',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('customer_type')
                    ->label('Тип клиента')
                    ->colors([
                        'primary' => 'b2c',
                        'success' => 'b2b',
                    ])
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                Tables\Columns\TextColumn::make('max_days')
                    ->label('Дней')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Приоритет')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valid_from')
                    ->label('Действует с')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Действует до')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vertical')
                    ->label('Вертикаль')
                    ->options([
                        'supermarket' => 'Supermarket',
                        'restaurant' => 'Restaurant',
                    ]),

                Tables\Filters\SelectFilter::make('sub_vertical')
                    ->label('Под-вертикаль')
                    ->options([
                        'meat_shops' => 'Мясные лавки',
                        'vegan_products' => 'Веган-продукты',
                        'confectionery' => 'Кондитерка',
                        'farm_direct' => 'Фермерские продукты',
                        'grocery_and_delivery' => 'Бакалея',
                        'household' => 'Товары для дома',
                        'food' => 'Готовая еда',
                        'bakery' => 'Выпечка',
                        'dairy' => 'Молочные продукты',
                    ]),

                Tables\Filters\SelectFilter::make('customer_type')
                    ->label('Тип клиента')
                    ->options([
                        'b2c' => 'B2C',
                        'b2b' => 'B2B',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активность'),

                Tables\Filters\Filter::make('valid_period')
                    ->form([
                        Forms\Components\DatePicker::make('valid_from')
                            ->label('Действует с'),
                        Forms\Components\DatePicker::make('valid_until')
                            ->label('Действует до'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['valid_from'],
                                fn (Builder $query, $date): Builder => $query->where('valid_from', '>=', $date),
                            )
                            ->when(
                                $data['valid_until'],
                                fn (Builder $query, $date): Builder => $query->where('valid_until', '<=', $date),
                            );
                    }),
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
            ])
            ->defaultSort('priority', 'desc')
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            'histories' => Tables\RelationManagers\RelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnPolicies::route('/'),
            'create' => Pages\CreateReturnPolicy::route('/create'),
            'view' => Pages\ViewReturnPolicy::route('/{record}'),
            'edit' => Pages\EditReturnPolicy::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
