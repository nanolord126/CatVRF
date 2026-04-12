<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\CRM\Models\CrmSegment;
use App\Filament\Tenant\Resources\CrmSegmentResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * CrmSegmentResource — управление сегментами CRM в Tenant Panel.
 * Создание правил сегментации, пересчёт, пресеты для вертикалей.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmSegmentResource extends Resource
{
    protected static ?string $model = CrmSegment::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationLabel = 'Сегменты';

    protected static ?string $modelLabel = 'Сегмент';

    protected static ?string $pluralModelLabel = 'Сегменты';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant()?->id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Настройки сегмента')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(100)
                            ->helperText('Автогенерация из названия'),
                    ]),

                    Forms\Components\Textarea::make('description')
                        ->label('Описание')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Select::make('vertical')
                            ->label('Вертикаль')
                            ->options([
                                'beauty' => 'Beauty',
                                'hotel' => 'Hotels',
                                'flowers' => 'Flowers',
                                'food' => 'Food',
                                'furniture' => 'Furniture',
                            ]),

                        Forms\Components\Toggle::make('is_dynamic')
                            ->label('Динамический')
                            ->helperText('Автоматический пересчёт клиентов')
                            ->default(true),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Активный')
                            ->default(true),
                    ]),

                    Forms\Components\Repeater::make('rules')
                        ->label('Правила сегментации')
                        ->schema([
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\Select::make('field')
                                    ->label('Поле')
                                    ->options([
                                        'total_spent' => 'Общая сумма',
                                        'total_orders' => 'Кол-во заказов',
                                        'average_order_value' => 'Средний чек',
                                        'last_order_at' => 'Последний заказ',
                                        'last_interaction_at' => 'Последнее взаимодействие',
                                        'created_at' => 'Дата регистрации',
                                        'status' => 'Статус',
                                        'client_type' => 'Тип клиента',
                                        'loyalty_tier' => 'Уровень лояльности',
                                        'segment' => 'Сегмент',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('operator')
                                    ->label('Оператор')
                                    ->options([
                                        '=' => '= (равно)',
                                        '!=' => '!= (не равно)',
                                        '>' => '> (больше)',
                                        '<' => '< (меньше)',
                                        '>=' => '>= (больше или равно)',
                                        '<=' => '<= (меньше или равно)',
                                        'like' => 'Содержит',
                                        'in' => 'В списке',
                                        'days_ago_gt' => 'Дней назад больше',
                                        'days_ago_lt' => 'Дней назад меньше',
                                        'is_null' => 'Пусто',
                                        'not_null' => 'Не пусто',
                                    ])
                                    ->required(),

                                Forms\Components\TextInput::make('value')
                                    ->label('Значение'),
                            ]),
                        ])
                        ->columns(1)
                        ->columnSpanFull()
                        ->defaultItems(1),
                ]),

            Forms\Components\Section::make('Статистика')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('clients_count')
                            ->label('Клиентов в сегменте')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('last_calculated_at')
                            ->label('Последний пересчёт')
                            ->disabled(),
                    ]),
                ])
                ->hiddenOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('vertical')
                    ->label('Вертикаль'),

                Tables\Columns\TextColumn::make('clients_count')
                    ->label('Клиентов')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_dynamic')
                    ->label('Динамический')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активный')
                    ->boolean(),

                Tables\Columns\TextColumn::make('last_calculated_at')
                    ->label('Пересчитан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vertical')
                    ->label('Вертикаль')
                    ->options([
                        'beauty' => 'Beauty',
                        'hotel' => 'Hotels',
                        'flowers' => 'Flowers',
                    ]),

                Tables\Filters\TernaryFilter::make('is_dynamic')
                    ->label('Динамический'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активный'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('recalculate')
                    ->label('Пересчитать')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (CrmSegment $record): void {
                        app(\App\Domains\CRM\Services\CrmSegmentationService::class)
                            ->recalculateSegment($record, \Illuminate\Support\Str::uuid()->toString());
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('clients_count', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrmSegments::route('/'),
            'create' => Pages\CreateCrmSegment::route('/create'),
            'edit' => Pages\EditCrmSegment::route('/{record}/edit'),
        ];
    }
}
