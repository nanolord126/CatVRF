<?php declare(strict_types=1);

namespace App\Domains\Travel\Filament\Resources;

use Filament\Resources\Resource;

final class TravelBookingResource extends Resource
{

    protected static ?string $model = TravelBooking::class;
        protected static ?string $navigationIcon = 'heroicon-o-ticket';
        protected static ?string $navigationGroup = 'Travel';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Booking Information')
                    ->columns(2)
                    ->schema([
                        Select::make('tour_id')
                            ->relationship('tour', 'name')
                            ->required(),
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required(),
                        TextInput::make('booking_number')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('participants_count')
                            ->numeric()
                            ->required(),
                        TextInput::make('price_per_person')
                            ->numeric()
                            ->disabled(),
                        TextInput::make('total_amount')
                            ->numeric()
                            ->disabled(),
                        TextInput::make('commission_amount')
                            ->numeric()
                            ->disabled(),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'paid' => 'Paid',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                        Select::make('payment_status')
                            ->options([
                                'unpaid' => 'Unpaid',
                                'paid' => 'Paid',
                                'refunded' => 'Refunded',
                            ])
                            ->required(),
                        KeyValue::make('participants_data'),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('booking_number')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('tour.name')
                        ->searchable(),
                    TextColumn::make('user.name')
                        ->searchable(),
                    TextColumn::make('participants_count'),
                    NumericColumn::make('total_amount')
                        ->numeric(2),
                    TextColumn::make('status')
                        ->badge(),
                    TextColumn::make('payment_status')
                        ->badge(),
                    TextColumn::make('booked_at')
                        ->dateTime(),
                ])
                ->filters([
                    SelectFilter::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'confirmed' => 'Confirmed',
                            'paid' => 'Paid',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ]),
                    SelectFilter::make('payment_status')
                        ->options([
                            'unpaid' => 'Unpaid',
                            'paid' => 'Paid',
                            'refunded' => 'Refunded',
                        ]),
                ])
                ->actions([
                    ViewAction::make(),
                    EditAction::make(),
                ])
                ->bulkActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make(),
                    ]),
                ])
                ->defaultSort('created_at', 'desc');
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', tenant()->id);
        }
}
