<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Beauty\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('salon.name')
                    ->searchable()
                    ->label('Салон'),
                Tables\Columns\TextColumn::make('master.full_name')
                    ->searchable()
                    ->label('Мастер'),
                Tables\Columns\TextColumn::make('service.name')
                    ->searchable()
                    ->label('Услуга'),
                Tables\Columns\TextColumn::make('client.name')
                    ->searchable()
                    ->label('Клиент'),
                Tables\Columns\TextColumn::make('datetime_start')
                    ->dateTime()
                    ->sortable()
                    ->label('Дата и время'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->label('Статус'),
                Tables\Columns\TextColumn::make('price')
                    ->money('RUB')
                    ->label('Цена'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждена',
                        'completed' => 'Завершена',
                        'cancelled' => 'Отменена',
                    ]),
                Tables\Filters\SelectFilter::make('salon_id')
                    ->relationship('salon', 'name')
                    ->label('Салон'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);

        if (session()->has('business_card_id')) {
            $query->whereHas('salon', function ($q) {
                $q->where('business_group_id', session('business_card_id'));
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\AppointmentResource\Pages\ListAppointments::route('/'),
            'create' => \App\Filament\Tenant\Resources\AppointmentResource\Pages\CreateAppointment::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\AppointmentResource\Pages\EditAppointment::route('/{record}/edit'),
            'view' => \App\Filament\Tenant\Resources\AppointmentResource\Pages\ViewAppointment::route('/{record}'),
        ];
    }
}
