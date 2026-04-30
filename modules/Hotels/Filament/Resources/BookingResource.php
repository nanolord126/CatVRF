<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Hotels\Infrastructure\Models\BookingModel;
use Modules\Hotels\Filament\Resources\BookingResource\Pages;
use Carbon\CarbonImmutable;

final class BookingResource extends Resource
{
    protected static ?string $model = BookingModel::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Hotels CRM';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Booking Information')
                    ->schema([
                        Forms\Components\Select::make('venue_id')
                            ->label('Venue')
                            ->relationship('venue', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('guest_id')
                            ->label('Guest')
                            ->relationship('guest', 'first_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->first_name . ' ' . $record->last_name),
                        Forms\Components\TextInput::make('confirmation_code')
                            ->label('Confirmation Code')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'prepaid' => 'Prepaid',
                                'paid' => 'Paid',
                                'checked_in' => 'Checked In',
                                'checked_out' => 'Checked Out',
                                'cancelled' => 'Cancelled',
                                'no_show' => 'No Show',
                            ])
                            ->required(),
                        Forms\Components\Select::make('source')
                            ->label('Source')
                            ->options([
                                'direct' => 'Direct',
                                'marketplace' => 'Marketplace',
                                'booking_com' => 'Booking.com',
                                'ostrovok' => 'Ostrovok',
                                'airbnb' => 'Airbnb',
                                'corporate' => 'Corporate',
                                'agency' => 'Agency',
                            ])
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Dates & Guests')
                    ->schema([
                        Forms\Components\DateTimePicker::make('check_in_date')
                            ->label('Check-in Date')
                            ->required(),
                        Forms\Components\DateTimePicker::make('check_out_date')
                            ->label('Check-out Date')
                            ->required()
                            ->after('check_in_date'),
                        Forms\Components\TextInput::make('adults')
                            ->label('Adults')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        Forms\Components\TextInput::make('children')
                            ->label('Children')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('infants')
                            ->label('Infants')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount')
                            ->numeric()
                            ->prefix('₽')
                            ->required(),
                        Forms\Components\TextInput::make('paid_amount')
                            ->label('Paid Amount')
                            ->numeric()
                            ->prefix('₽')
                            ->required(),
                        Forms\Components\TextInput::make('deposit_amount')
                            ->label('Deposit Amount')
                            ->numeric()
                            ->prefix('₽')
                            ->default(0),
                        Forms\Components\Select::make('payment_status')
                            ->label('Payment Status')
                            ->options([
                                'pending' => 'Pending',
                                'partially_paid' => 'Partially Paid',
                                'paid' => 'Paid',
                                'refunded' => 'Refunded',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Special Requests')
                    ->schema([
                        Forms\Components\KeyValue::make('room_preferences')
                            ->label('Room Preferences')
                            ->keyLabel('Preference')
                            ->valueLabel('Value'),
                        Forms\Components\Toggle::make('early_checkin_requested')
                            ->label('Early Check-in Requested'),
                        Forms\Components\Toggle::make('late_checkout_requested')
                            ->label('Late Check-out Requested'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Check-in/Check-out')
                    ->schema([
                        Forms\Components\DateTimePicker::make('actual_check_in')
                            ->label('Actual Check-in')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('actual_check_out')
                            ->label('Actual Check-out')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('confirmation_code')
                    ->label('Confirmation')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('guest.first_name')
                    ->label('Guest')
                    ->formatStateUsing(fn ($record) => $record->guest->first_name . ' ' . $record->guest->last_name)
                    ->searchable(),
                Tables\Columns\TextColumn::make('venue.name')
                    ->label('Venue')
                    ->searchable(),
                Tables\Columns\ViewColumn::make('status')
                    ->label('Status')
                    ->view('components.status-badge')
                    ->viewData(fn ($record): array => [
                        'status' => $record->status,
                        'label' => match ($record->status) {
                            'pending' => 'Pending',
                            'prepaid' => 'Prepaid',
                            'paid' => 'Paid',
                            'checked_in' => 'Checked In',
                            'checked_out' => 'Checked Out',
                            'cancelled' => 'Cancelled',
                            'no_show' => 'No Show',
                            default => ucfirst($record->status),
                        },
                        'color' => match ($record->status) {
                            'pending' => 'warning',
                            'prepaid' => 'info',
                            'paid' => 'success',
                            'checked_in' => 'primary',
                            'checked_out' => 'secondary',
                            'cancelled' => 'danger',
                            'no_show' => 'danger',
                            default => 'secondary',
                        },
                        'icon' => match ($record->status) {
                            'pending' => 'clock',
                            'prepaid' => 'credit-card',
                            'paid' => 'check-circle',
                            'checked_in' => 'user',
                            'checked_out' => 'check-badge',
                            'cancelled' => 'x-circle',
                            'no_show' => 'users',
                            default => null,
                        },
                    ]),
                Tables\Columns\TextColumn::make('check_in_date')
                    ->label('Check-in')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_out_date')
                    ->label('Check-out')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('RUB'),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'partially_paid' => 'info',
                        'paid' => 'success',
                        'refunded' => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'prepaid' => 'Prepaid',
                        'paid' => 'Paid',
                        'checked_in' => 'Checked In',
                        'checked_out' => 'Checked Out',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('source')
                    ->label('Source')
                    ->options([
                        'direct' => 'Direct',
                        'marketplace' => 'Marketplace',
                        'booking_com' => 'Booking.com',
                        'ostrovok' => 'Ostrovok',
                        'airbnb' => 'Airbnb',
                    ]),
                Tables\Filters\Filter::make('check_in_date')
                    ->label('Check-in Date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($query) => $query->whereDate('check_in_date', '>=', $data['from']))
                            ->when($data['until'], fn ($query) => $query->whereDate('check_in_date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('check-in')
                    ->label('Check In')
                    ->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->color('success')
                    ->visible(fn ($record) => $record->canCheckIn())
                    ->action(function ($record) {
                        app(\Modules\Hotels\Application\Services\BookingService::class)->checkIn($record->id, auth()->id());
                    }),
                Tables\Actions\Action::make('check-out')
                    ->label('Check Out')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'checked_in')
                    ->action(function ($record) {
                        app(\Modules\Hotels\Application\Services\BookingService::class)->checkOut($record->id, auth()->id());
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'items' => Tables\Columns\TextColumn::make('items_count')->counts('items'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'view' => Pages\ViewBooking::route('/{record}'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
