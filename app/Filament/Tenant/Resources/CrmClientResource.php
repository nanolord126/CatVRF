<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\CRM\Models\CrmClient;
use App\Filament\Tenant\Resources\CrmClientResource\Pages;
use App\Filament\Tenant\Resources\CrmClientResource\Widgets\CrmActivityTimelineWidget;
use App\Filament\Tenant\Resources\CrmClientResource\Widgets\CrmVerticalProfileWidget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * CrmClientResource — полноценный CRM-ресурс для Tenant Panel.
 * Карточка клиента, история взаимодействий, вертикальные профили,
 * сегментация, финансовая сводка.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmClientResource extends Resource
{
    protected static ?string $model = CrmClient::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Клиенты CRM';

    protected static ?string $modelLabel = 'CRM-клиент';

    protected static ?string $pluralModelLabel = 'CRM-клиенты';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant()?->id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('CRM-клиент')->tabs([
                // ── Основная информация ──
                Forms\Components\Tabs\Tab::make('Основное')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('first_name')
                                ->label('Имя')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('last_name')
                                ->label('Фамилия')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('middle_name')
                                ->label('Отчество')
                                ->maxLength(255),
                        ]),

                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('phone')
                                ->label('Телефон')
                                ->tel()
                                ->maxLength(30),

                            Forms\Components\DatePicker::make('birthday')
                                ->label('Дата рождения'),
                        ]),

                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('status')
                                ->label('Статус')
                                ->options([
                                    'active' => 'Активный',
                                    'inactive' => 'Неактивный',
                                    'vip' => 'VIP',
                                    'blacklist' => 'Чёрный список',
                                ])
                                ->default('active'),

                            Forms\Components\Select::make('client_type')
                                ->label('Тип клиента')
                                ->options([
                                    'individual' => 'Физлицо',
                                    'corporate' => 'Юрлицо',
                                    'partner' => 'Партнёр',
                                    'wholesaler' => 'Оптовик',
                                ]),

                            Forms\Components\Select::make('vertical')
                                ->label('Вертикаль')
                                ->options([
                                    'beauty' => 'Beauty',
                                    'hotel' => 'Hotels',
                                    'flowers' => 'Flowers',
                                    'food' => 'Food',
                                    'furniture' => 'Furniture',
                                    'fashion' => 'Fashion',
                                    'fitness' => 'Fitness',
                                    'travel' => 'Travel',
                                    'auto' => 'Auto',
                                    'realestate' => 'Real Estate',
                                    'taxi' => 'Taxi',
                                ]),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('company_name')
                                ->label('Компания')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('company_inn')
                                ->label('ИНН')
                                ->maxLength(20),
                        ]),

                        Forms\Components\Select::make('source')
                            ->label('Источник')
                            ->options([
                                'website' => 'Сайт',
                                'referral' => 'Реферал',
                                'social' => 'Соцсети',
                                'advertising' => 'Реклама',
                                'walk_in' => 'Пришёл сам',
                                'import' => 'Импорт',
                                'partner' => 'Партнёр',
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                // ── Финансовая сводка ──
                Forms\Components\Tabs\Tab::make('Финансы')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\TextInput::make('total_spent')
                                ->label('Общая сумма')
                                ->numeric()
                                ->prefix('₽')
                                ->disabled(),

                            Forms\Components\TextInput::make('total_orders')
                                ->label('Заказов')
                                ->numeric()
                                ->disabled(),

                            Forms\Components\TextInput::make('average_order_value')
                                ->label('Средний чек')
                                ->numeric()
                                ->prefix('₽')
                                ->disabled(),

                            Forms\Components\Select::make('loyalty_tier')
                                ->label('Уровень лояльности')
                                ->options([
                                    'standard' => 'Стандарт',
                                    'silver' => 'Серебро',
                                    'gold' => 'Золото',
                                    'vip' => 'VIP',
                                ]),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DateTimePicker::make('last_order_at')
                                ->label('Последний заказ')
                                ->disabled(),

                            Forms\Components\DateTimePicker::make('last_interaction_at')
                                ->label('Последнее взаимодействие')
                                ->disabled(),
                        ]),
                    ]),

                // ── Дополнительные данные вертикали ──
                Forms\Components\Tabs\Tab::make('Данные вертикали')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Forms\Components\KeyValue::make('vertical_data')
                            ->label('Данные вертикали (JSON)')
                            ->columnSpanFull(),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Клиент')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('vertical')
                    ->label('Вертикаль')
                    ->colors([
                        'success' => 'beauty',
                        'primary' => 'hotel',
                        'warning' => 'flowers',
                        'danger' => 'food',
                        'info' => 'fitness',
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'primary' => 'vip',
                        'danger' => 'blacklist',
                    ]),

                Tables\Columns\BadgeColumn::make('segment')
                    ->label('Сегмент')
                    ->colors([
                        'primary' => 'vip',
                        'success' => 'loyal',
                        'warning' => 'at_risk',
                        'danger' => 'sleeping',
                        'info' => 'new',
                    ]),

                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_orders')
                    ->label('Заказов')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_order_at')
                    ->label('Последний заказ')
                    ->dateTime('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('loyalty_tier')
                    ->label('Лояльность')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vertical')
                    ->label('Вертикаль')
                    ->options([
                        'beauty' => 'Beauty',
                        'hotel' => 'Hotels',
                        'flowers' => 'Flowers',
                        'food' => 'Food',
                        'furniture' => 'Furniture',
                        'fashion' => 'Fashion',
                        'fitness' => 'Fitness',
                        'travel' => 'Travel',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активный',
                        'inactive' => 'Неактивный',
                        'vip' => 'VIP',
                        'blacklist' => 'Чёрный список',
                    ]),

                Tables\Filters\SelectFilter::make('segment')
                    ->label('Сегмент')
                    ->options([
                        'vip' => 'VIP',
                        'loyal' => 'Лояльные',
                        'regular' => 'Обычные',
                        'new' => 'Новички',
                        'at_risk' => 'Под угрозой',
                        'sleeping' => 'Спящие',
                    ]),

                Tables\Filters\SelectFilter::make('client_type')
                    ->label('Тип')
                    ->options([
                        'individual' => 'Физлицо',
                        'corporate' => 'Юрлицо',
                        'partner' => 'Партнёр',
                    ]),

                Tables\Filters\SelectFilter::make('loyalty_tier')
                    ->label('Уровень лояльности')
                    ->options([
                        'standard' => 'Стандарт',
                        'silver' => 'Серебро',
                        'gold' => 'Золото',
                        'vip' => 'VIP',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_interaction_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            CrmVerticalProfileWidget::class,
            CrmActivityTimelineWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrmClients::route('/'),
            'create' => Pages\CreateCrmClient::route('/create'),
            'edit' => Pages\EditCrmClient::route('/{record}/edit'),
            'view' => Pages\ViewCrmClient::route('/{record}'),
        ];
    }
}
