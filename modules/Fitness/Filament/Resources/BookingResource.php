<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ViewColumn;
use Modules\Fitness\Infrastructure\Models\BookingModel;

final class BookingResource extends Resource
{
    protected static ?string $model = BookingModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Fitness CRM';

    protected static ?int $navigationSort = 7;

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
                        Forms\Components\Select::make('schedule_slot_id')
                            ->relationship('scheduleSlot', 'id')
                            ->required()
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                $record->workoutType->name . ' - ' . $record->start_time->format('d.m.Y H:i')
                            )
                            ->label('Слот расписания'),
                        Forms\Components\Select::make('membership_id')
                            ->relationship('membership', 'type')
                            ->searchable()
                            ->label('Абонемент'),
                    ]),

                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Ожидает',
                                'confirmed' => 'Подтверждён',
                                'checked_in' => 'Пришёл',
                                'completed' => 'Завершён',
                                'cancelled' => 'Отменён',
                                'no_show' => 'Не пришёл',
                                'waitlist' => 'Лист ожидания',
                            ])
                            ->default('pending')
                            ->required()
                            ->label('Статус'),
                    ]),

                Forms\Components\Section::make('Оплата')
                    ->schema([
                        Forms\Components\Toggle::make('is_paid')
                            ->default(false)
                            ->label('Оплачено'),
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('₽')
                            ->default(0)
                            ->label('Цена'),
                    ]),

                Forms\Components\Section::make('Время')
                    ->schema([
                        Forms\Components\DateTimePicker::make('booked_at')
                            ->default(now())
                            ->label('Время бронирования'),
                        Forms\Components\DateTimePicker::make('checked_in_at')
                            ->label('Время check-in'),
                        Forms\Components\DateTimePicker::make('cancelled_at')
                            ->label('Время отмены'),
                    ]),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\TextInput::make('cancellation_reason')
                            ->label('Причина отмены'),
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
                    ->label('Фамилия'),
                Tables\Columns\TextColumn::make('client.first_name')
                    ->searchable()
                    ->label('Имя'),
                Tables\Columns\TextColumn::make('scheduleSlot.workoutType.name')
                    ->label('Тип тренировки'),
                Tables\Columns\TextColumn::make('scheduleSlot.start_time')
                    ->dateTime()
                    ->sortable()
                    ->label('Время'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'checked_in' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'no_show' => 'danger',
                        'waitlist' => 'gray',
                    })
                    ->label('Статус'),
                Tables\Columns\IconColumn::make('is_paid')
                    ->boolean()
                    ->label('Оплачено'),
                Tables\Columns\TextColumn::make('price')
                    ->money('RUB')
                    ->label('Цена'),
                Tables\Columns\TextColumn::make('booked_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Забронирован'),
            ])
            ->defaultSort('booked_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждён',
                        'checked_in' => 'Пришёл',
                        'completed' => 'Завершён',
                        'cancelled' => 'Отменён',
                    ])
                    ->label('Статус'),
                Tables\Filters\Filter::make('is_paid')
                    ->query(fn ($query) => $query->where('is_paid', true))
                    ->label('Только оплаченные'),
                Tables\Filters\Filter::make('upcoming')
                    ->query(fn ($query) => $query->whereHas('scheduleSlot', fn ($q) => $q->where('start_time', '>=', now())))
                    ->label('Предстоящие'),
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
            'index' => \Modules\Fitness\Filament\Resources\BookingResource\Pages\ListBookings::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\BookingResource\Pages\CreateBooking::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\BookingResource\Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
