<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Models\Appointment;
use App\Filament\Tenant\Resources\Beauty\AppointmentResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * AppointmentResource — Filament-ресурс записей (B2B Tenant Panel).
 *
 * Просмотр всех записей салонов tenant'а.
 * Действия: подтвердить, завершить, отменить.
 */
final class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Beauty';

    protected static ?string $navigationLabel = 'Записи';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Информация о записи')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('salon_id')
                        ->label('Салон')
                        ->relationship('salon', 'name')
                        ->disabled(),

                    Forms\Components\Select::make('master_id')
                        ->label('Мастер')
                        ->relationship('master', 'full_name')
                        ->disabled(),

                    Forms\Components\Select::make('service_id')
                        ->label('Услуга')
                        ->relationship('service', 'name')
                        ->disabled(),

                    Forms\Components\TextInput::make('status')
                        ->label('Статус')
                        ->disabled(),

                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Начало')
                        ->disabled(),

                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Окончание')
                        ->disabled(),

                    Forms\Components\TextInput::make('price_kopecks')
                        ->label('Цена (коп)')
                        ->disabled()
                        ->formatStateUsing(fn ($state) => number_format((int) $state / 100, 2) . ' ₽'),

                    Forms\Components\Textarea::make('client_comment')
                        ->label('Комментарий клиента')
                        ->disabled()
                        ->columnSpan(2),

                    Forms\Components\Textarea::make('cancellation_reason')
                        ->label('Причина отмены')
                        ->disabled()
                        ->columnSpan(2)
                        ->visible(fn ($record) => $record?->status === Appointment::STATUS_CANCELLED),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('salon.name')
                    ->label('Салон')
                    ->sortable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('master.full_name')
                    ->label('Мастер')
                    ->sortable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Услуга')
                    ->sortable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Дата/время')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_kopecks')
                    ->label('Цена')
                    ->formatStateUsing(fn ($state) => number_format((int) $state / 100, 0, '.', ' ') . ' ₽')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning'  => Appointment::STATUS_PENDING,
                        'info'     => Appointment::STATUS_CONFIRMED,
                        'primary'  => Appointment::STATUS_IN_PROGRESS,
                        'success'  => Appointment::STATUS_COMPLETED,
                        'danger'   => Appointment::STATUS_CANCELLED,
                        'gray'     => Appointment::STATUS_NO_SHOW,
                    ]),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        Appointment::STATUS_PENDING     => 'Ожидает',
                        Appointment::STATUS_CONFIRMED   => 'Подтверждена',
                        Appointment::STATUS_IN_PROGRESS => 'В процессе',
                        Appointment::STATUS_COMPLETED   => 'Завершена',
                        Appointment::STATUS_CANCELLED   => 'Отменена',
                        Appointment::STATUS_NO_SHOW     => 'Неявка',
                    ]),

                Tables\Filters\Filter::make('today')
                    ->label('Сегодня')
                    ->query(fn ($query) => $query->whereDate('starts_at', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'view'  => Pages\ViewAppointment::route('/{record}'),
        ];
    }
}

