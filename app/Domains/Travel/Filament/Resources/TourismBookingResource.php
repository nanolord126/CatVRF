<?php declare(strict_types=1);

namespace App\Domains\Travel\Filament\Resources;

use App\Domains\Travel\Filament\Resources\TourismBookingResource\Pages;
use App\Domains\Travel\Models\TourBooking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tourism Booking Filament Resource
 * 
 * Admin panel resource for managing tourism bookings.
 * Follows CatVRF canonical rules for Filament resources.
 */
final class TourismBookingResource extends Resource
{
    protected static ?string $model = TourBooking::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-americas';

    protected static ?string $navigationLabel = 'Tourism Bookings';

    protected static ?string $modelLabel = 'Tourism Booking';

    protected static ?string $navigationGroup = 'Travel';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Booking Information')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->disabled()
                            ->maxLength(255),
                        Forms\Components\Select::make('tour_id')
                            ->relationship('tour', 'title')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'email')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('person_count')
                            ->label('Person Count')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->required()
                            ->after('start_date'),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount')
                            ->required()
                            ->numeric()
                            ->prefix('₽'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing & Commission')
                    ->schema([
                        Forms\Components\TextInput::make('base_price')
                            ->label('Base Price')
                            ->required()
                            ->numeric()
                            ->prefix('₽')
                            ->disabled(),
                        Forms\Components\TextInput::make('dynamic_price')
                            ->label('Dynamic Price')
                            ->required()
                            ->numeric()
                            ->prefix('₽')
                            ->disabled(),
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Discount Amount')
                            ->required()
                            ->numeric()
                            ->prefix('₽')
                            ->disabled(),
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Commission Rate')
                            ->required()
                            ->numeric()
                            ->suffix('%')
                            ->disabled(),
                        Forms\Components\TextInput::make('commission_amount')
                            ->label('Commission Amount')
                            ->required()
                            ->numeric()
                            ->prefix('₽')
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Status & Verification')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'held' => 'Held',
                                'confirmed' => 'Confirmed',
                                'cancelled' => 'Cancelled',
                                'completed' => 'Completed',
                                'no_show' => 'No Show',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('biometric_verified')
                            ->label('Biometric Verified')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('hold_expires_at')
                            ->label('Hold Expires At')
                            ->disabled(),
                        Forms\Components\Toggle::make('virtual_tour_viewed')
                            ->label('Virtual Tour Viewed')
                            ->disabled(),
                        Forms\Components\Toggle::make('video_call_scheduled')
                            ->label('Video Call Scheduled')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('video_call_time')
                            ->label('Video Call Time')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Payment & Cashback')
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'card' => 'Card',
                                'wallet' => 'Wallet',
                                'sbp' => 'SBP',
                                'split' => 'Split',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('split_payment_enabled')
                            ->label('Split Payment Enabled')
                            ->disabled(),
                        Forms\Components\TextInput::make('cashback_amount')
                            ->label('Cashback Amount')
                            ->numeric()
                            ->prefix('₽')
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Cancellation & Fraud')
                    ->schema([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->rows(3),
                        Forms\Components\TextInput::make('refund_amount')
                            ->label('Refund Amount')
                            ->numeric()
                            ->prefix('₽')
                            ->disabled(),
                        Forms\Components\TextInput::make('fraud_score')
                            ->label('Fraud Score')
                            ->numeric()
                            ->step(0.0001)
                            ->minValue(0)
                            ->maxValue(1)
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('cancelled_at')
                            ->label('Cancelled At')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('tags')
                            ->label('Tags')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->reorderable(),
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->reorderable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->limit(20),
                Tables\Columns\TextColumn::make('tour.title')
                    ->label('Tour')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('person_count')
                    ->label('Persons')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'held' => 'Held',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                        'no_show' => 'No Show',
                    ])
                    ->sortable(),
                Tables\Columns\IconColumn::make('biometric_verified')
                    ->label('Biometric')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\IconColumn::make('virtual_tour_viewed')
                    ->label('Virtual Tour')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('fraud_score')
                    ->label('Fraud Score')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 4) : '-')
                    ->color(fn ($state) => $state > 0.7 ? 'danger' : ($state > 0.4 ? 'warning' : 'success')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'held' => 'Held',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                        'no_show' => 'No Show',
                    ]),
                Tables\Filters\Filter::make('biometric_verified')
                    ->label('Biometric Verified')
                    ->query(fn (Builder $query): Builder => $query->where('biometric_verified', true)),
                Tables\Filters\Filter::make('high_fraud_risk')
                    ->label('High Fraud Risk')
                    ->query(fn (Builder $query): Builder => $query->where('fraud_score', '>', 0.7)),
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

    public static function getRelations(): array
    {
        return [
            'tour',
            'user',
            'businessGroup',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTourismBookings::route('/'),
            'create' => Pages\CreateTourismBooking::route('/create'),
            'view' => Pages\ViewTourismBooking::route('/{record}'),
            'edit' => Pages\EditTourismBooking::route('/{record}/edit'),
        ];
    }
}
