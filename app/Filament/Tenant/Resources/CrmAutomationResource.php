<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\CRM\Models\CrmAutomation;
use App\Filament\Tenant\Resources\CrmAutomationResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * CrmAutomationResource — управление маркетинговыми автоматизациями CRM.
 * Триггерные кампании, пресеты для вертикалей, статистика.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmAutomationResource extends Resource
{
    protected static ?string $model = CrmAutomation::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'Автоматизации';

    protected static ?string $modelLabel = 'Автоматизация';

    protected static ?string $pluralModelLabel = 'Автоматизации';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant()?->id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основные настройки')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название кампании')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('vertical')
                            ->label('Вертикаль')
                            ->options([
                                'beauty' => 'Beauty',
                                'hotel' => 'Hotels',
                                'flowers' => 'Flowers',
                                'food' => 'Food',
                                'furniture' => 'Furniture',
                                'fashion' => 'Fashion',
                            ]),
                    ]),

                    Forms\Components\Textarea::make('description')
                        ->label('Описание')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Активна')
                        ->default(true),
                ]),

            Forms\Components\Section::make('Триггер')
                ->description('Условие запуска автоматизации')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('trigger_type')
                            ->label('Тип триггера')
                            ->options([
                                'birthday' => '🎂 День рождения',
                                'inactivity' => '😴 Неактивность',
                                'post_order' => '📦 После заказа',
                                'post_visit' => '🏠 После визита',
                                'signup' => '🆕 Новая регистрация',
                                'custom_date' => '📅 Конкретная дата',
                                'abandoned_cart' => '🛒 Брошенная корзина',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('delay_minutes')
                            ->label('Задержка (минут)')
                            ->numeric()
                            ->default(0)
                            ->helperText('Сколько минут ждать после срабатывания триггера'),
                    ]),

                    Forms\Components\KeyValue::make('trigger_config')
                        ->label('Параметры триггера')
                        ->columnSpanFull()
                        ->helperText('Например: days_before = 1, days_inactive = 60'),
                ]),

            Forms\Components\Section::make('Действие')
                ->description('Что делать при срабатывании')
                ->schema([
                    Forms\Components\Select::make('action_type')
                        ->label('Тип действия')
                        ->options([
                            'send_email' => '📧 Отправить Email',
                            'send_sms' => '📱 Отправить SMS',
                            'send_push' => '🔔 Push-уведомление',
                            'send_telegram' => '💬 Telegram',
                            'add_bonus' => '💰 Начислить бонус',
                            'change_segment' => '🏷 Изменить сегмент',
                            'create_task' => '📋 Создать задачу',
                        ])
                        ->required(),

                    Forms\Components\KeyValue::make('action_config')
                        ->label('Параметры действия')
                        ->columnSpanFull()
                        ->helperText('Например: template = birthday_beauty, bonus_amount = 500'),
                ]),

            Forms\Components\Section::make('Статистика')
                ->schema([
                    Forms\Components\Grid::make(4)->schema([
                        Forms\Components\TextInput::make('total_sent')
                            ->label('Отправлено')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('total_opened')
                            ->label('Открыто')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('total_clicked')
                            ->label('Кликов')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('total_converted')
                            ->label('Конверсий')
                            ->numeric()
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
                    ->label('Кампания')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('trigger_type')
                    ->label('Триггер')
                    ->colors([
                        'success' => 'birthday',
                        'warning' => 'inactivity',
                        'primary' => 'post_order',
                        'info' => 'post_visit',
                        'danger' => 'abandoned_cart',
                    ]),

                Tables\Columns\BadgeColumn::make('action_type')
                    ->label('Действие'),

                Tables\Columns\BadgeColumn::make('vertical')
                    ->label('Вертикаль'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),

                Tables\Columns\TextColumn::make('total_sent')
                    ->label('Отправлено')
                    ->sortable(),

                Tables\Columns\TextColumn::make('conversion_rate')
                    ->label('CR%')
                    ->getStateUsing(fn (CrmAutomation $record): string => $record->total_sent > 0
                        ? round(($record->total_converted / $record->total_sent) * 100, 1) . '%'
                        : '—'
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trigger_type')
                    ->label('Триггер')
                    ->options([
                        'birthday' => 'День рождения',
                        'inactivity' => 'Неактивность',
                        'post_order' => 'После заказа',
                        'post_visit' => 'После визита',
                        'abandoned_cart' => 'Брошенная корзина',
                    ]),

                Tables\Filters\SelectFilter::make('action_type')
                    ->label('Действие')
                    ->options([
                        'send_email' => 'Email',
                        'send_sms' => 'SMS',
                        'send_push' => 'Push',
                        'add_bonus' => 'Бонус',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активна'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (CrmAutomation $record): string => $record->is_active ? 'Остановить' : 'Запустить')
                    ->icon(fn (CrmAutomation $record): string => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn (CrmAutomation $record): string => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function (CrmAutomation $record): void {
                        $record->update(['is_active' => ! $record->is_active]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrmAutomations::route('/'),
            'create' => Pages\CreateCrmAutomation::route('/create'),
            'edit' => Pages\EditCrmAutomation::route('/{record}/edit'),
        ];
    }
}
