<?php declare(strict_types=1);

namespace App\Domains\Hotels\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookingResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = Booking::class;

        protected static ?string $navigationIcon = 'heroicon-o-bookmark';
        protected static ?string $navigationGroup = 'Hotels';
        protected static ?int $navigationSort = 3;

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Select::make('hotel_id')
                        ->relationship('hotel', 'name')
                        ->required(),
                    Forms\Components\Select::make('room_type_id')
                        ->relationship('roomType', 'name')
                        ->required(),
                    Forms\Components\DatePicker::make('check_in_date')
                        ->required(),
                    Forms\Components\DatePicker::make('check_out_date')
                        ->required(),
                    Forms\Components\TextInput::make('number_of_guests')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('nights_count')
                        ->numeric()
                        ->disabled(),
                    Forms\Components\TextInput::make('subtotal_price')
                        ->numeric()
                        ->disabled(),
                    Forms\Components\TextInput::make('commission_price')
                        ->numeric()
                        ->disabled(),
                    Forms\Components\TextInput::make('total_price')
                        ->numeric()
                        ->disabled(),
                    Forms\Components\Select::make('booking_status')
                        ->options([
                            'confirmed' => 'Confirmed',
                            'checked_in' => 'Checked In',
                            'checked_out' => 'Checked Out',
                            'cancelled' => 'Cancelled',
                        ]),
                    Forms\Components\Select::make('payment_status')
                        ->options([
                            'pending' => 'Pending',
                            'paid' => 'Paid',
                            'refunded' => 'Refunded',
                        ]),
                    Forms\Components\Textarea::make('special_requests'),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('hotel.name')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('confirmation_code')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('check_in_date')
                        ->date()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('check_out_date')
                        ->date()
                        ->sortable(),
                    Tables\Columns\BadgeColumn::make('booking_status')
                        ->colors([
                            'success' => 'checked_out',
                            'warning' => 'confirmed',
                            'info' => 'checked_in',
                            'danger' => 'cancelled',
                        ]),
                    Tables\Columns\TextColumn::make('total_price')
                        ->numeric()
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('booking_status')
                        ->options([
                            'confirmed' => 'Confirmed',
                            'checked_in' => 'Checked In',
                            'checked_out' => 'Checked Out',
                            'cancelled' => 'Cancelled',
                        ]),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
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
                'index' => Pages\ListBookings::route('/'),
                'create' => Pages\CreateBooking::route('/create'),
                'edit' => Pages\EditBooking::route('/{record}/edit'),
            ];
        }
}
