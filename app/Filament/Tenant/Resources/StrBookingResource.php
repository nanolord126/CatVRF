<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StrBookingResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = StrBooking::class;

        protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

        protected static ?string $navigationGroup = 'Short-Term Rentals';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Booking Details')
                        ->schema([
                            Forms\Components\Select::make('str_apartment_id')
                                ->relationship('apartment', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('user_id')
                                ->relationship('user', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\DateTimePicker::make('check_in_at')
                                ->required(),
                            Forms\Components\DateTimePicker::make('check_out_at')
                                ->required(),
                        ])->columns(2),

                    Forms\Components\Section::make('Finance')
                        ->schema([
                            Forms\Components\TextInput::make('total_price')
                                ->numeric()
                                ->prefix('RUB')
                                ->required(),
                            Forms\Components\TextInput::make('deposit_amount')
                                ->numeric()
                                ->prefix('RUB')
                                ->required(),
                            Forms\Components\Select::make('status')
                                ->options(StrBookingStatus::class)
                                ->required(),
                            Forms\Components\Select::make('deposit_status')
                                ->options(StrDepositStatus::class)
                                ->required(),
                        ])->columns(2),
                ]);

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListStrBooking::route('/'),
                'create' => Pages\\CreateStrBooking::route('/create'),
                'edit' => Pages\\EditStrBooking::route('/{record}/edit'),
                'view' => Pages\\ViewStrBooking::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListStrBooking::route('/'),
                'create' => Pages\\CreateStrBooking::route('/create'),
                'edit' => Pages\\EditStrBooking::route('/{record}/edit'),
                'view' => Pages\\ViewStrBooking::route('/{record}'),
            ];
        }
}
