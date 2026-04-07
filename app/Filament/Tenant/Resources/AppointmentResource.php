<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

final class AppointmentResource extends Resource
{

    protected static ?string $model = Appointment::class;

        protected static ?string $navigationIcon = 'heroicon-o-calendar';

        protected static ?string $navigationLabel = 'Записи';

        protected static ?string $navigationGroup = 'Beauty';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Select::make('salon_id')
                    ->relationship('salon', 'name')
                    ->required()
                    ->label('Салон'),
                Forms\Components\Select::make('master_id')
                    ->relationship('master', 'full_name')
                    ->required()
                    ->label('Мастер'),
                Forms\Components\Select::make('service_id')
                    ->relationship('service', 'name')
                    ->required()
                    ->label('Услуга'),
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'name')
                    ->required()
                    ->label('Клиент'),
                Forms\Components\DateTimePicker::make('datetime_start')
                    ->required()
                    ->label('Дата и время начала'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждена',
                        'completed' => 'Завершена',
                        'cancelled' => 'Отменена',
                    ])
                    ->default('pending')
                    ->required()
                    ->label('Статус'),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->required()
                    ->label('Цена'),
                Forms\Components\Select::make('payment_status')
                    ->options([
                        'pending' => 'Ожидает оплаты',
                        'paid' => 'Оплачено',
                        'refunded' => 'Возврат',
                    ])
                    ->default('pending')
                    ->label('Статус оплаты'),
            ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListAppointment::route('/'),
                'create' => Pages\CreateAppointment::route('/create'),
                'edit' => Pages\EditAppointment::route('/{record}/edit'),
                'view' => Pages\ViewAppointment::route('/{record}'),
            ];
        }
}
