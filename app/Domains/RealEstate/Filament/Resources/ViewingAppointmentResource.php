<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Filament\Resources;

use App\Filament\Resources\BaseOptimizedResource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Domains\RealEstate\Filament\Resources\ViewingAppointmentResource\Pages\ListViewingAppointments;

final class ViewingAppointmentResource extends BaseOptimizedResource
{
    protected static ?string $model = ViewingAppointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Real Estate';

    protected static ?string $label = 'Просмотр';

    protected static ?string $pluralLabel = 'Просмотры объектов';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Информация о просмотре')
                    ->schema([
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'scheduled' => 'Запланирован',
                                'confirmed' => 'Подтверждён',
                                'completed' => 'Завершён',
                                'cancelled' => 'Отменён',
                                'no_show' => 'Не явился',
                            ]),
                        DateTimePickerInput::make('datetime')
                            ->label('Дата и время'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('property.address')
                    ->label('Объект')
                    ->searchable(),
                TextColumn::make('datetime')
                    ->label('Когда')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')->badge()
                    ->label('Статус')
                    ->colors([
                        'info' => 'scheduled',
                        'success' => 'completed',
                        'warning' => 'confirmed',
                        'danger' => 'cancelled',
                        'secondary' => 'no_show',
                    ]),
                TextColumn::make('client_rating')
                    ->label('Оценка'),
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'scheduled' => 'Запланирован',
                        'confirmed' => 'Подтверждён',
                        'completed' => 'Завершён',
                        'cancelled' => 'Отменён',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListViewingAppointments::route('/'),
        ];
    }

    /**
     * Relations to eager load for RealEstate
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}
