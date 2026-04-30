<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Hotels\Infrastructure\Models\ShiftModel;

final class ShiftResource extends Resource
{
    protected static ?string $model = ShiftModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Смены';
    protected static ?string $navigationGroup = 'Отели';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('venue_id')
                            ->relationship('venue', 'name')
                            ->searchable()
                            ->required()
                            ->label('Отель'),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required()
                            ->label('Сотрудник'),
                        Forms\Components\Select::make('role')
                            ->options([
                                'receptionist' => 'Рецепционист',
                                'housekeeper' => 'Горничная',
                                'manager' => 'Менеджер',
                                'concierge' => 'Консьерж',
                                'security' => 'Охрана',
                            ])
                            ->required()
                            ->label('Роль'),
                        Forms\Components\Select::make('supervisor_id')
                            ->relationship('supervisor', 'name')
                            ->searchable()
                            ->label('Руководитель'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Время')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_time')
                            ->required()
                            ->label('Начало'),
                        Forms\Components\DateTimePicker::make('end_time')
                            ->required()
                            ->label('Окончание'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Детали')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'scheduled' => 'Запланирована',
                                'active' => 'Активна',
                                'completed' => 'Завершена',
                                'cancelled' => 'Отменена',
                            ])
                            ->default('scheduled')
                            ->required()
                            ->label('Статус'),
                        Forms\Components\TextInput::make('location')
                            ->label('Локация'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('venue.name')
                    ->label('Отель')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Сотрудник')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('role')
                    ->label('Роль')
                    ->colors([
                        'primary' => 'receptionist',
                        'success' => 'housekeeper',
                        'warning' => 'manager',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'gray' => 'scheduled',
                        'success' => 'active',
                        'info' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Начало')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Окончание')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Запланирована',
                        'active' => 'Активна',
                        'completed' => 'Завершена',
                        'cancelled' => 'Отменена',
                    ]),
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'receptionist' => 'Рецепционист',
                        'housekeeper' => 'Горничная',
                        'manager' => 'Менеджер',
                        'concierge' => 'Консьерж',
                    ]),
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Hotels\Filament\Resources\ShiftResource\Pages\ListShifts::route('/'),
            'create' => \Modules\Hotels\Filament\Resources\ShiftResource\Pages\CreateShift::route('/create'),
            'view' => \Modules\Hotels\Filament\Resources\ShiftResource\Pages\ViewShift::route('/{record}'),
            'edit' => \Modules\Hotels\Filament\Resources\ShiftResource\Pages\EditShift::route('/{record}/edit'),
        ];
    }
}
