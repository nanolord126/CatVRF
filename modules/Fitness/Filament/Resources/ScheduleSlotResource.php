<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\ScheduleSlotModel;

final class ScheduleSlotResource extends Resource
{
    protected static ?string $model = ScheduleSlotModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Fitness CRM';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('venue_id')
                            ->relationship('venue', 'name')
                            ->required()
                            ->searchable()
                            ->label('Зал'),
                        Forms\Components\Select::make('trainer_id')
                            ->relationship('trainer', 'last_name')
                            ->required()
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->last_name . ' ' . $record->first_name)
                            ->label('Тренер'),
                        Forms\Components\Select::make('workout_type_id')
                            ->relationship('workoutType', 'name')
                            ->required()
                            ->searchable()
                            ->label('Тип тренировки'),
                    ]),

                Forms\Components\Section::make('Время')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_time')
                            ->required()
                            ->label('Начало'),
                        Forms\Components\DateTimePicker::make('end_time')
                            ->required()
                            ->label('Окончание'),
                    ]),

                Forms\Components\Section::make('Вместимость')
                    ->schema([
                        Forms\Components\TextInput::make('capacity')
                            ->required()
                            ->numeric()
                            ->default(20)
                            ->label('Вместимость'),
                        Forms\Components\TextInput::make('booked_count')
                            ->numeric()
                            ->disabled()
                            ->default(0)
                            ->label('Забронировано'),
                    ]),

                Forms\Components\Section::make('Повторение')
                    ->schema([
                        Forms\Components\Toggle::make('is_recurring')
                            ->default(false)
                            ->label('Повторяющийся слот'),
                        Forms\Components\TextInput::make('recurrence_pattern')
                            ->label('Шаблон повторения')
                            ->placeholder('weekly, daily, etc.')
                            ->visible(fn (Forms\Get $get) => $get('is_recurring')),
                        Forms\Components\DatePicker::make('recurrence_end')
                            ->label('Дата окончания повторения')
                            ->visible(fn (Forms\Get $get) => $get('is_recurring')),
                    ]),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки')
                            ->rows(3),
                    ]),

                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Активен'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('venue.name')
                    ->searchable()
                    ->label('Зал'),
                Tables\Columns\TextColumn::make('trainer.last_name')
                    ->searchable()
                    ->label('Тренер'),
                Tables\Columns\TextColumn::make('workoutType.name')
                    ->searchable()
                    ->label('Тип тренировки'),
                Tables\Columns\TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable()
                    ->label('Начало'),
                Tables\Columns\TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable()
                    ->label('Окончание'),
                Tables\Columns\TextColumn::make('capacity')
                    ->numeric()
                    ->label('Вместимость'),
                Tables\Columns\TextColumn::make('booked_count')
                    ->numeric()
                    ->label('Забронировано'),
                Tables\Columns\TextColumn::make('occupancy')
                    ->label('Заполненность')
                    ->getStateUsing(fn ($record): string => $record->capacity > 0 
                        ? round(($record->booked_count / $record->capacity) * 100) . '%' 
                        : '0%'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активен'),
            ])
            ->defaultSort('start_time', 'asc')
            ->filters([
                Tables\Filters\Filter::make('is_active')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->label('Только активные'),
                Tables\Filters\Filter::make('upcoming')
                    ->query(fn ($query) => $query->where('start_time', '>=', now()))
                    ->label('Предстоящие'),
                Tables\Filters\SelectFilter::make('venue_id')
                    ->relationship('venue', 'name')
                    ->label('Зал'),
                Tables\Filters\SelectFilter::make('trainer_id')
                    ->relationship('trainer', 'last_name')
                    ->label('Тренер'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => \Modules\Fitness\Filament\Resources\ScheduleSlotResource\Pages\ListScheduleSlots::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\ScheduleSlotResource\Pages\CreateScheduleSlot::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\ScheduleSlotResource\Pages\EditScheduleSlot::route('/{record}/edit'),
        ];
    }
}
