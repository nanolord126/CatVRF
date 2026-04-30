<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\VetGrooming\Infrastructure\Models\SmallMammalGroomingSessionModel;

final class SmallMammalGroomingSessionResource extends Resource
{
    protected static ?string $model = SmallMammalGroomingSessionModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Vet & Grooming';

    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Сессия груминга мелких млекопитающих')
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

                        Forms\Components\Select::make('mammal_group')
                            ->options([
                                'ferret' => 'Хорёк',
                                'rabbit' => 'Кролик',
                                'chinchilla' => 'Шиншилла',
                                'guinea_pig' => 'Морская свинка',
                                'degu' => 'Дегу',
                                'hedgehog' => 'Ёж',
                                'rodents' => 'Грызуны',
                            ])
                            ->required()
                            ->reactive()
                            ->label('Группа животных'),

                        Forms\Components\Select::make('procedure_type')
                            ->options([
                                'claw_trim' => 'Стрижка когтей',
                                'ear_clean' => 'Чистка ушей',
                                'anal_gland' => 'Анальные железы',
                                'mat_removal' => 'Удаление колтунов',
                                'bath' => 'Купание',
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
                                'special_bag' => 'Специальный мешок',
                                'gloves' => 'Перчатки',
                                'no_restraint' => 'Без фиксации',
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

                Forms\Components\Section::make('Медицинские параметры')
                    ->schema([
                        Forms\Components\Toggle::make('sedation_used')
                            ->default(false)
                            ->label('Использовано успокоение'),

                        Forms\Components\Textarea::make('sedation_notes')
                            ->rows(2)
                            ->label('Заметки об успокоении'),

                        Forms\Components\Toggle::make('temperature_controlled')
                            ->default(false)
                            ->label('Контроль температуры'),

                        Forms\Components\TextInput::make('room_temperature')
                            ->numeric()
                            ->step(0.1)
                            ->label('Температура помещения (°C)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Протокол безопасности')
                    ->schema([
                        Forms\Components\KeyValue::make('protocol_checklist')
                            ->label('Чек-лист протокола'),
                    ]),

                Forms\Components\Section::make('Фото и заметки')
                    ->schema([
                        Forms\Components\FileUpload::make('before_photos')
                            ->multiple()
                            ->image()
                            ->directory('grooming/before')
                            ->label('Фото до'),

                        Forms\Components\FileUpload::make('after_photos')
                            ->multiple()
                            ->image()
                            ->directory('grooming/after')
                            ->label('Фото после'),

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

                Tables\Columns\TextColumn::make('mammal_group')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ferret' => 'danger',
                        'rabbit' => 'warning',
                        'chinchilla' => 'warning',
                        'guinea_pig' => 'warning',
                        'hedgehog' => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ferret' => 'Хорёк',
                        'rabbit' => 'Кролик',
                        'chinchilla' => 'Шиншилла',
                        'guinea_pig' => 'Морская свинка',
                        'degu' => 'Дегу',
                        'hedgehog' => 'Ёж',
                        'rodents' => 'Грызуны',
                        default => $state,
                    })
                    ->label('Вид'),

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

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->label('Статус'),

                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Начало'),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Длительность (мин)'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('mammal_group')
                    ->options([
                        'ferret' => 'Хорёк',
                        'rabbit' => 'Кролик',
                        'chinchilla' => 'Шиншилла',
                        'guinea_pig' => 'Морская свинка',
                        'degu' => 'Дегу',
                        'hedgehog' => 'Ёж',
                        'rodents' => 'Грызуны',
                    ])
                    ->label('Вид'),

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
            'index' => \Modules\VetGrooming\Filament\Resources\SmallMammalGroomingSessionResource\Pages\ListSmallMammalGroomingSessions::route('/'),
            'create' => \Modules\VetGrooming\Filament\Resources\SmallMammalGroomingSessionResource\Pages\CreateSmallMammalGroomingSession::route('/create'),
            'edit' => \Modules\VetGrooming\Filament\Resources\SmallMammalGroomingSessionResource\Pages\EditSmallMammalGroomingSession::route('/{record}/edit'),
        ];
    }
}
