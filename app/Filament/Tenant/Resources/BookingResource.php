<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookingResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListBooking::route('/'),
                'create' => Pages\\CreateBooking::route('/create'),
                'edit' => Pages\\EditBooking::route('/{record}/edit'),
                'view' => Pages\\ViewBooking::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListBooking::route('/'),
                'create' => Pages\\CreateBooking::route('/create'),
                'edit' => Pages\\EditBooking::route('/{record}/edit'),
                'view' => Pages\\ViewBooking::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListBooking::route('/'),
                'create' => Pages\\CreateBooking::route('/create'),
                'edit' => Pages\\EditBooking::route('/{record}/edit'),
                'view' => Pages\\ViewBooking::route('/{record}'),
            ];
        }
}
