<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\VetGrooming\Infrastructure\Models\ExoticGroomingSessionModel;

final class ExoticGroomingSessionResource extends Resource
{
    protected static ?string $model = ExoticGroomingSessionModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-paw-print';

    protected static ?string $navigationGroup = 'Vet & Grooming';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Сессия груминга')
                    ->schema([
                        Forms\Components\Select::make('pet_id')
                            ->relationship('pet', 'name')
                            ->required()
                            ->searchable()
                            ->label('Питомец'),

                        Forms\Components\Select::make('groomer_id')
                            ->relationship('groomer', 'name')
                            ->required()
                            ->searchable()
                            ->label('Грумер'),

                        Forms\Components\Select::make('exotic_type')
                            ->options([
                                'birds' => 'Птицы',
                                'reptiles' => 'Рептилии',
                            ])
                            ->required()
                            ->reactive()
                            ->label('Тип животного'),

                        Forms\Components\TextInput::make('species_group')
                            ->required()
                            ->placeholder('Например: large_parrots, iguanas')
                            ->label('Группа видов'),

                        Forms\Components\Select::make('procedure_type')
                            ->options([
                                'claw_trim' => 'Стрижка когтей',
                                'beak_trim' => 'Подрезка клюва',
                                'wing_clip' => 'Подрезка крыльев',
                                'feather_care' => 'Уход за перьями',
                                'scale_care' => 'Уход за чешуей',
                                'shell_cleaning' => 'Чистка панциря',
                                'shedding_assist' => 'Помощь при линьке',
                                'nail_trim' => 'Стрижка когтей',
                                'basic_care' => 'Базовый уход',
                            ])
                            ->required()
                            ->label('Тип процедуры'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Параметры сессии')
                    ->schema([
                        Forms\Components\Select::make('handling_method')
                            ->options([
                                'towel' => 'Полотенце',
                                'scruff' => 'За шкирку',
                                'gentle_restraint' => 'Мягкая фиксация',
                                'two_person' => 'Два человека',
                                'muzzle' => 'Намордник',
                                'gentle_leader' => 'Gentle Leader',
                                'special_bag' => 'Специальный мешок',
                                'gloves' => 'Перчатки',
                                'no_restraint' => 'Без фиксации',
                                'chemical_sedation' => 'Химическое успокоение',
                            ])
                            ->required()
                            ->label('Метод фиксации'),

                        Forms\Components\TextInput::make('stress_level_before')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->default(1)
                            ->required()
                            ->label('Уровень стресса до'),

                        Forms\Components\TextInput::make('stress_level_after')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->default(1)
                            ->label('Уровень стресса после'),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->label('Длительность (минуты)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Температурный контроль')
                    ->schema([
                        Forms\Components\Toggle::make('temperature_controlled')
                            ->label('Контроль температуры'),

                        Forms\Components\TextInput::make('room_temperature')
                            ->numeric()
                            ->step(0.1)
                            ->label('Температура помещения (°C)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Медицинские параметры')
                    ->schema([
                        Forms\Components\Toggle::make('sedation_used')
                            ->label('Использовано успокоение'),

                        Forms\Components\Textarea::make('sedation_notes')
                            ->rows(2)
                            ->label('Заметки об успокоении'),

                        Forms\Components\KeyValue::make('protocol_checklist')
                            ->label('Чек-лист протокола'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Фото и заметки')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->label('Заметки'),

                        Forms\Components\Textarea::make('medical_notes')
                            ->rows(3)
                            ->label('Медицинские заметки'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'in_progress' => 'В процессе',
                                'completed' => 'Завершена',
                                'cancelled' => 'Отменена',
                            ])
                            ->default('in_progress')
                            ->required()
                            ->label('Статус'),

                        Forms\Components\DateTimePicker::make('started_at')
                            ->required()
                            ->default(now())
                            ->label('Начало'),

                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Завершение'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pet.name')
                    ->label('Питомец')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('groomer.name')
                    ->label('Грумер')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('exotic_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'birds' => 'warning',
                        'reptiles' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'birds' => 'Птицы',
                        'reptiles' => 'Рептилии',
                        default => $state,
                    })
                    ->label('Тип'),

                Tables\Columns\TextColumn::make('species_group')
                    ->label('Вид')
                    ->searchable(),

                Tables\Columns\TextColumn::make('procedure_type')
                    ->badge()
                    ->label('Процедура'),

                Tables\Columns\TextColumn::make('stress_level_before')
                    ->label('Стресс до')
                    ->color(fn (int $state): string => $state >= 7 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('stress_level_after')
                    ->label('Стресс после')
                    ->color(fn (int $state): string => $state >= 7 ? 'danger' : 'success'),

                Tables\Columns\IconColumn::make('sedation_used')
                    ->boolean()
                    ->label('Успокоение'),

                ViewColumn::make('status')
                    ->label('Статус')
                    ->view('components.status-badge')
                    ->viewData(fn ($record): array => [
                        'status' => $record->status,
                        'label' => match ($record->status) {
                            'in_progress' => 'В процессе',
                            'completed' => 'Завершена',
                            'cancelled' => 'Отменена',
                            default => ucfirst($record->status),
                        },
                        'color' => match ($record->status) {
                            'in_progress' => 'warning',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                            default => 'secondary',
                        },
                        'icon' => match ($record->status) {
                            'in_progress' => 'scissors',
                            'completed' => 'check-circle',
                            'cancelled' => 'x-circle',
                            default => null,
                        },
                    ]),

                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Начало'),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Длительность (мин)'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exotic_type')
                    ->options([
                        'birds' => 'Птицы',
                        'reptiles' => 'Рептилии',
                    ])
                    ->label('Тип'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'in_progress' => 'В процессе',
                        'completed' => 'Завершена',
                        'cancelled' => 'Отменена',
                    ])
                    ->label('Статус'),

                Tables\Filters\Filter::make('high_stress')
                    ->query(fn ($query) => $query->where('stress_level_after', '>=', 7))
                    ->label('Высокий стресс'),

                Tables\Filters\Filter::make('with_sedation')
                    ->query(fn ($query) => $query->where('sedation_used', true))
                    ->label('С успокоением'),
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
            'index' => \Modules\VetGrooming\Filament\Resources\ExoticGroomingSessionResource\Pages\ListExoticGroomingSessions::route('/'),
            'create' => \Modules\VetGrooming\Filament\Resources\ExoticGroomingSessionResource\Pages\CreateExoticGroomingSession::route('/create'),
            'edit' => \Modules\VetGrooming\Filament\Resources\ExoticGroomingSessionResource\Pages\EditExoticGroomingSession::route('/{record}/edit'),
        ];
    }
}
