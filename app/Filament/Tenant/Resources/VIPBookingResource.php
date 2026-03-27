<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Luxury\Models\VIPBooking;
use App\Domains\Luxury\Models\LuxuryClient;
use App\Filament\Tenant\Resources\VIPBookingResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * VIPBookingResource
 * 
 * Filament Resource для управления элитными бронированиями.
 * Соблюдает tenant scoping и канон 2026.
 * 
 * @version 1.0.0
 * @author CatVRF
 */
final class VIPBookingResource extends Resource
{
    protected static ?string $model = VIPBooking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Luxury & VIP';

    protected static ?string $modelLabel = 'Бронирование VIP';

    protected static ?string $pluralModelLabel = 'Бронирования VIP';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Статус и Клиент')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'uuid')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "Client ID: {$record->uuid} (VIP: {$record->vip_level})")
                            ->required()
                            ->searchable()
                            ->label('Клиент'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Ожидание',
                                'confirmed' => 'Подтверждено',
                                'fulfilled' => 'Выполнено',
                                'cancelled' => 'Отменено',
                            ])
                            ->required()
                            ->label('Статус брони'),
                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'unpaid' => 'Не оплачено',
                                'deposited' => 'Депозит внесен',
                                'paid' => 'Оплачено',
                                'refunded' => 'Возвращено',
                            ])
                            ->required()
                            ->label('Статус оплаты'),
                    ])->columns(3),

                Forms\Components\Section::make('Детали бронирования')
                    ->schema([
                        Forms\Components\DateTimePicker::make('booking_at')
                            ->required()
                            ->label('Время брони'),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->numeric()
                            ->label('Длительность (мин)'),
                        Forms\Components\TextInput::make('total_price_kopecks')
                            ->numeric()
                            ->suffix('коп.')
                            ->label('Итоговая стоимость'),
                        Forms\Components\TextInput::make('deposit_kopecks')
                            ->numeric()
                            ->suffix('коп.')
                            ->label('Депозит'),
                    ])->columns(2),

                Forms\Components\Section::make('Объект бронирования (Polymorphic)')
                    ->schema([
                        Forms\Components\TextInput::make('bookable_type')
                            ->disabled()
                            ->label('Тип объекта'),
                        Forms\Components\TextInput::make('bookable_id')
                            ->disabled()
                            ->label('ID объекта'),
                    ])->columns(2),

                Forms\Components\Textarea::make('notes')
                    ->label('Заметки консьержа')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID (Booking)'),
                Tables\Columns\TextColumn::make('client.uuid')
                    ->label('Client'),
                Tables\Columns\TextColumn::make('booking_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Дата'),
                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'pending' => 'Ожидание',
                        'confirmed' => 'Подтверждено',
                        'fulfilled' => 'Выполнено',
                        'cancelled' => 'Отменено',
                    ])
                    ->label('Статус'),
                Tables\Columns\TextColumn::make('total_price_kopecks')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . ' ₽')
                    ->label('Сумма'),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->label('Оплата'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Ожидание',
                        'confirmed' => 'Подтверждено',
                        'fulfilled' => 'Выполнено',
                        'cancelled' => 'Отменено',
                    ])
                    ->label('Статус'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVIPBookings::route('/'),
            'create' => Pages\CreateVIPBooking::route('/create'),
            'edit' => Pages\EditVIPBooking::route('/{record}/edit'),
        ];
    }
}
