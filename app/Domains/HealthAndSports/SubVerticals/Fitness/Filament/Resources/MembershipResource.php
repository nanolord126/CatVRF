<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\MembershipModel;

final class MembershipResource extends Resource
{
    protected static ?string $model = MembershipModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Fitness CRM';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'last_name')
                            ->required()
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->last_name . ' ' . $record->first_name)
                            ->label('Клиент'),
                        Forms\Components\Select::make('type')
                            ->options([
                                'monthly' => 'Месячный',
                                'quarterly' => 'Квартальный',
                                'annual' => 'Годовой',
                                'unlimited' => 'Безлимитный',
                                'punch_card' => 'Карта посещений',
                                'corporate' => 'Корпоративный',
                                'trial' => 'Пробный',
                                'senior' => 'Сеньор (55+)',
                                'student' => 'Студенческий',
                            ])
                            ->required()
                            ->label('Тип абонемента'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Активен',
                                'frozen' => 'Заморожен',
                                'expired' => 'Истёк',
                                'cancelled' => 'Отменён',
                                'suspended' => 'Приостановлен',
                            ])
                            ->default('active')
                            ->label('Статус'),
                    ]),

                Forms\Components\Section::make('Период действия')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->label('Дата начала'),
                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->label('Дата окончания'),
                    ]),

                Forms\Components\Section::make('Посещения')
                    ->schema([
                        Forms\Components\TextInput::make('total_visits')
                            ->numeric()
                            ->label('Всего посещений')
                            ->hint('Оставьте пустым для безлимитного'),
                        Forms\Components\TextInput::make('remaining_visits')
                            ->numeric()
                            ->disabled()
                            ->label('Осталось посещений'),
                    ]),

                Forms\Components\Section::make('Цена и оплата')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('₽')
                            ->label('Цена'),
                    ]),

                Forms\Components\Section::make('Заморозка')
                    ->schema([
                        Forms\Components\Toggle::make('allow_freeze')
                            ->default(true)
                            ->label('Разрешить заморозку'),
                        Forms\Components\TextInput::make('max_freeze_days')
                            ->numeric()
                            ->default(30)
                            ->label('Макс. дней заморозки'),
                        Forms\Components\TextInput::make('freeze_days_used')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->label('Использовано дней'),
                    ]),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.last_name')
                    ->searchable()
                    ->label('Фамилия клиента'),
                Tables\Columns\TextColumn::make('client.first_name')
                    ->searchable()
                    ->label('Имя клиента'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'monthly' => 'info',
                        'quarterly' => 'info',
                        'annual' => 'success',
                        'unlimited' => 'success',
                        'trial' => 'warning',
                    })
                    ->label('Тип'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'frozen' => 'warning',
                        'expired' => 'danger',
                        'cancelled' => 'gray',
                    })
                    ->label('Статус'),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->label('Начало'),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->label('Окончание'),
                Tables\Columns\TextColumn::make('remaining_visits')
                    ->numeric()
                    ->label('Осталось'),
                Tables\Columns\TextColumn::make('price')
                    ->money('RUB')
                    ->label('Цена'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Активен',
                        'frozen' => 'Заморожен',
                        'expired' => 'Истёк',
                    ])
                    ->label('Статус'),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'monthly' => 'Месячный',
                        'quarterly' => 'Квартальный',
                        'annual' => 'Годовой',
                        'unlimited' => 'Безлимитный',
                    ])
                    ->label('Тип'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => \Modules\Fitness\Filament\Resources\MembershipResource\Pages\ListMemberships::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\MembershipResource\Pages\CreateMembership::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\MembershipResource\Pages\EditMembership::route('/{record}/edit'),
        ];
    }
}
