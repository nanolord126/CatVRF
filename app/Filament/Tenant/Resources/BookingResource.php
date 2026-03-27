<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Photography\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — PHOTOGRAPHY BOOKING RESOURCE
 */
final class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'Photography';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Детали Бронирования')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Клиент')
                            ->relationship('client', 'name')
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('session_id')
                            ->label('Тип сессии')
                            ->relationship('session', 'name')
                            ->required(),

                        Forms\Components\Select::make('photographer_id')
                            ->label('Фотограф')
                            ->relationship('photographer', 'full_name')
                            ->searchable(),

                        Forms\Components\Select::make('studio_id')
                            ->label('Студия')
                            ->relationship('studio', 'name')
                            ->searchable(),
                    ])->columns(2),

                Forms\Components\Section::make('Время и Стоимость')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Начало')
                            ->required(),

                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Окончание')
                            ->required(),

                        Forms\Components\TextInput::make('total_amount_kopecks')
                            ->label('Сумма (в копейках)')
                            ->numeric()
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Ожидание',
                                'confirmed' => 'Подтверждено',
                                'completed' => 'Завершено',
                                'cancelled' => 'Отменено',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Технические поля')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->default(fn() => (string) Str::uuid())
                            ->disabled(),
                        Forms\Components\TextInput::make('correlation_id')
                            ->default(fn() => (string) Str::uuid())
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')->label('Клиент'),
                Tables\Columns\TextColumn::make('session.name')->label('Услуга'),
                Tables\Columns\TextColumn::make('photographer.full_name')->label('Мастер'),
                Tables\Columns\TextColumn::make('starts_at')->label('Время')->dateTime(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('total_amount_kopecks')
                    ->label('Цена')
                    ->money('RUB', divideBy: 100),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\BookingResource\Pages\ListBookings::route('/'),
            'create' => \App\Filament\Tenant\Resources\BookingResource\Pages\CreateBooking::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\BookingResource\Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
