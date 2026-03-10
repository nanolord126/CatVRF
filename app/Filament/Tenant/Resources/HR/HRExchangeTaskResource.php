<?php

namespace App\Filament\Tenant\Resources\HR;

use App\Filament\Tenant\Resources\HR\HRExchangeTaskResource\Pages;
use App\Models\HR\HRExchangeTask;
use App\Services\Common\Support\HelpdeskService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HRExchangeTaskResource extends Resource
{
    protected static ?string $model = HRExchangeTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'HR Биржа (Exchange)';
    protected static ?string $label = 'Задание';
    protected static ?string $pluralLabel = 'Биржа Заданий';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Детали смены / Вакансии')
                    ->schema([
                        Forms\Components\TextInput::make('title')->required()->label('Название задания (например: Официант на доп. смену)'),
                        Forms\Components\Select::make('category')
                            ->options([
                                'RESTAURANT' => 'Ресторанный сектор',
                                'CLINIC' => 'Медицина (Медсестры/Врачи)',
                                'TAXI' => 'Такси (Водители на подмену)',
                                'DELIVERY' => 'Курьерская служба',
                                'GENERAL' => 'Общие работы',
                            ])->required(),
                        Forms\Components\Textarea::make('description')->required()->label('Описание работы и требований'),
                        Forms\Components\TextInput::make('reward_amount')
                            ->numeric()
                            ->prefix('$')
                            ->label('Оплата за смену')
                            ->required(),
                        Forms\Components\DateTimePicker::make('start_at')->required()->label('Начало'),
                        Forms\Components\DateTimePicker::make('end_at')->required()->label('Конец'),
                        Forms\Components\TextInput::make('slots_available')->numeric()->default(1)->label('Количество участников'),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->label('Задание'),
                Tables\Columns\BadgeColumn::make('category')
                    ->colors([
                        'primary' => 'RESTAURANT',
                        'warning' => 'TAXI',
                        'success' => 'CLINIC',
                        'gray' => 'GENERAL',
                    ]),
                Tables\Columns\TextColumn::make('reward_amount')->money('USD')->label('Оплата'),
                Tables\Columns\TextColumn::make('start_at')->dateTime()->label('Когда'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'OPEN',
                        'warning' => 'IN_PROGRESS',
                        'gray' => 'COMPLETED',
                        'danger' => 'CANCELLED',
                    ]),
                Tables\Columns\TextColumn::make('slots_available')->label('Мест'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category'),
            ])
            ->actions([
                // Кнопка быстрого отклика для сотрудника
                Tables\Actions\Action::make('takeTask')
                    ->label('Взять смену')
                    ->color('success')
                    ->icon('heroicon-o-check-badge')
                    ->requiresConfirmation()
                    ->hidden(fn (HRExchangeTask $record) => $record->status !== 'OPEN' || $record->slots_available <= 0)
                    ->action(fn (HRExchangeTask $record) => (new \App\Services\HR\HRExchangePlatformService())->respondToTask($record, auth()->user())),
                
                // Глобальная поддержка по HR-задаче
                Tables\Actions\Action::make('support')
                    ->label('Помощь')
                    ->icon('heroicon-o-lifebuoy')
                    ->color('warning')
                    ->action(function ($record, HelpdeskService $helpdesk) {
                        $helpdesk->openTicket(tenant(), auth()->id(), [
                            'subject' => "HR Exchange Issue: {$record->title}",
                            'category' => 'technical',
                            'priority' => 'medium'
                        ]);
                        Notification::make()->title('Запрос отправлен в поддержку')->success()->send();
                    }),

                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHRExchangeTasks::route('/'),
            'create' => Pages\CreateHRExchangeTask::route('/create'),
            'edit' => Pages\EditHRExchangeTask::route('/{record}/edit'),
        ];
    }
}
