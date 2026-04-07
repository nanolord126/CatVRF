<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Filament;

use App\Domains\Beauty\Models\Appointment;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Записи';

    protected static ?string $pluralModelLabel = 'Записи на услуги';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('salon_id')->label('Салон')->required(),
            Select::make('master_id')->label('Мастер')->required(),
            Select::make('service_id')->label('Услуга')->required(),
            DateTimePicker::make('starts_at')->label('Начало')->required(),
            DateTimePicker::make('ends_at')->label('Окончание')->required(),
            TextInput::make('price')->label('Цена')->numeric()->required(),
            Select::make('status')->label('Статус')->options([
                'pending' => 'Ожидание',
                'confirmed' => 'Подтверждена',
                'completed' => 'Завершена',
                'cancelled' => 'Отменена',
            ])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('salon.name')->label('Салон'),
                TextColumn::make('master.full_name')->label('Мастер'),
                TextColumn::make('starts_at')->label('Начало')->dateTime(),
                TextColumn::make('status')->badge()->label('Статус'),
                TextColumn::make('price')->label('Цена')->money('RUB'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Ожидание',
                    'confirmed' => 'Подтверждена',
                    'completed' => 'Завершена',
                    'cancelled' => 'Отменена',
                ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Beauty\Filament\Pages\ListAppointments::route('/'),
        ];
    }
}