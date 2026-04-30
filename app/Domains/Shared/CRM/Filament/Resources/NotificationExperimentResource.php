<?php

declare(strict_types=1);

namespace App\Domains\Shared\CRM\Filament\Resources;

use App\Domains\Shared\Notifications\Models\NotificationExperiment;
use App\Domains\Shared\Notifications\Services\ABTestService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

final class NotificationExperimentResource extends Resource
{
    protected static ?string $model = NotificationExperiment::class;
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'A/B Тесты уведомлений';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основные настройки')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Название эксперимента')
                            ->maxLength(255),
                        
                        Forms\Components\Select::make('event_type')
                            ->required()
                            ->label('Тип события')
                            ->options([
                                'pre_delivery_reminder' => 'Напоминание о доставке',
                                'subscription_created' => 'Создание подписки',
                                'payment_failed' => 'Ошибка оплаты',
                                'subscription_paused' => 'Приостановка подписки',
                            ])
                            ->default('pre_delivery_reminder'),
                        
                        Forms\Components\TextInput::make('traffic_percent')
                            ->required()
                            ->label('Процент трафика для варианта A')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(50)
                            ->suffix('%'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(false),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Вариант A')
                    ->schema([
                        Forms\Components\Textarea::make('variant_a.text')
                            ->label('Текст сообщения')
                            ->rows(3)
                            ->required(),
                        
                        Forms\Components\TagsInput::make('variant_a.buttons')
                            ->label('Кнопки')
                            ->placeholder('Добавить кнопку')
                            ->splitKeys([','])
                            ->suggestions([
                                'track' => 'Отследить',
                                'pause' => 'Приостановить',
                                'cancel' => 'Отменить',
                                'update_payment' => 'Обновить оплату',
                            ]),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Вариант B')
                    ->schema([
                        Forms\Components\Textarea::make('variant_b.text')
                            ->label('Текст сообщения')
                            ->rows(3)
                            ->required(),
                        
                        Forms\Components\TagsInput::make('variant_b.buttons')
                            ->label('Кнопки')
                            ->placeholder('Добавить кнопку')
                            ->splitKeys([','])
                            ->suggestions([
                                'track' => 'Отследить',
                                'pause' => 'Приостановить',
                                'cancel' => 'Отменить',
                                'update_payment' => 'Обновить оплату',
                            ]),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Период проведения')
                    ->schema([
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Дата начала')
                            ->seconds(false)
                            ->required(),
                        
                        Forms\Components\DateTimePicker::make('ended_at')
                            ->label('Дата окончания')
                            ->seconds(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('event_type')
                    ->label('Тип события')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('traffic_percent')
                    ->label('Трафик A')
                    ->suffix('%'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Начало')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('ended_at')
                    ->label('Окончание')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->label('Тип события')
                    ->options([
                        'pre_delivery_reminder' => 'Напоминание о доставке',
                        'subscription_created' => 'Создание подписки',
                        'payment_failed' => 'Ошибка оплаты',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен')
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('stats')
                    ->label('Статистика')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->action(function (NotificationExperiment $record) {
                        $stats = app(ABTestService::class)->getExperimentStats($record->id);
                        // This would open a modal or redirect to a stats page
                        dd($stats);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Shared\CRM\Filament\Resources\NotificationExperimentResource\Pages\ListNotificationExperiments::route('/'),
            'create' => \App\Domains\Shared\CRM\Filament\Resources\NotificationExperimentResource\Pages\CreateNotificationExperiment::route('/create'),
            'view' => \App\Domains\Shared\CRM\Filament\Resources\NotificationExperimentResource\Pages\ViewNotificationExperiment::route('/{record}'),
            'edit' => \App\Domains\Shared\CRM\Filament\Resources\NotificationExperimentResource\Pages\EditNotificationExperiment::route('/{record}/edit'),
        ];
    }
}
