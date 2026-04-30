<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * StaffShiftResource — Layer 1: Presentation/UI Layer
 * 
 * Filament resource for Shift management
 */
final class StaffShiftResource extends Resource
{
    protected static ?string $model = \Modules\CatCRM\Domain\Staff\Models\ShiftModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Смены';

    protected static ?string $navigationGroup = 'HRM - График';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о смене')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Сотрудник')
                            ->relationship('employee', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DateTimePicker::make('start_time')
                            ->label('Начало')
                            ->required(),
                        Forms\Components\DateTimePicker::make('end_time')
                            ->label('Конец')
                            ->required(),
                        Forms\Components\TextInput::make('location')
                            ->label('Локация'),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'scheduled' => 'Запланирована',
                                'in_progress' => 'В процессе',
                                'completed' => 'Завершена',
                                'cancelled' => 'Отменена',
                                'no_show' => 'Не явился',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Геолокация')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('Широта')
                            ->numeric(),
                        Forms\Components\TextInput::make('longitude')
                            ->label('Долгота')
                            ->numeric(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Сотрудник')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Начало')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Конец')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Локация'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'info',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'no_show' => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Запланирована',
                        'in_progress' => 'В процессе',
                        'completed' => 'Завершена',
                        'cancelled' => 'Отменена',
                        'no_show' => 'Не явился',
                    ]),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('От'),
                        Forms\Components\DatePicker::make('until')
                            ->label('До'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($query, $date) => $query->whereDate('start_time', '>=', $date))
                            ->when($data['until'], fn ($query, $date) => $query->whereDate('end_time', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
