<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Filament\Resources;

use App\Domains\RealEstate\Models\ViewingAppointment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePickerInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

/**
 * Filament Resource для управления просмотрами объектов.
 * Production 2026.
 */
final class ViewingAppointmentResource extends Resource
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
                BadgeColumn::make('status')
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
            'index' => \App\Domains\RealEstate\Filament\Resources\ViewingAppointmentResource\Pages\ListViewingAppointments::route('/'),
        ];
    }
}
