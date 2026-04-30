<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\VetGrooming\Infrastructure\Models\LargeMammalGroomingSessionModel;

final class LargeMammalGroomingSessionResource extends Resource
{
    protected static ?string $model = LargeMammalGroomingSessionModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Vet & Grooming';

    protected static ?int $navigationSort = 14;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Сессия груминга крупных млекопитающих')
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
                                'large_dog' => 'Крупная собака',
                                'giant_dog' => 'Гигантская порода',
                                'large_cat' => 'Крупная кошка',
                                'other_large' => 'Другие крупные',
                            ])
                            ->required()
                            ->reactive()
                            ->label('Группа животных'),

                        Forms\Components\Select::make('procedure_type')
                            ->options([
                                'full_groom' => 'Полный груминг',
                                'deshedding' => 'Вычёсывание',
                                'sanitary_trim' => 'Санитарная стрижка',
                                'show_groom' => 'Шоу-груминг',
                                'nail_trim' => 'Стрижка когтей',
                                'basic_care' => 'Базовый уход',
                            ])
                            ->required()
                            ->label('Тип процедуры'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Оценка агрессии и стресса')
                    ->schema([
                        Forms\Components\TextInput::make('aggression_level')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->default(1)
                            ->required()
                            ->label('Уровень агрессии')
                            ->helperText('1-10, где 10 - максимальная агрессия'),

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
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Методы фиксации')
                    ->schema([
                        Forms\Components\Select::make('restraint_method')
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

                        Forms\Components\Select::make('second_groomer_id')
                            ->relationship('secondGroomer', 'name')
                            ->searchable()
                            ->label('Второй грумер')
                            ->helperText('Обязателен при агрессии ≥6'),

                        Forms\Components\Select::make('veterinarian_id')
                            ->relationship('veterinarian', 'name')
                            ->searchable()
                            ->label('Ветеринар')
                            ->helperText('Обязателен при агрессии ≥8'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Инциденты безопасности')
                    ->schema([
                        Forms\Components\Toggle::make('safety_incident')
                            ->default(false)
                            ->reactive()
                            ->label('Инцидент безопасности'),

                        Forms\Components\Textarea::make('safety_incident_description')
                            ->rows(3)
                            ->visible(fn (callable $get) => $get('safety_incident'))
                            ->required(fn (callable $get) => $get('safety_incident'))
                            ->label('Описание инцидента'),
                    ]),

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

                        Forms\Components\TextInput::make('duration_minutes')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->label('Длительность (минуты)'),
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
                        'giant_dog' => 'danger',
                        'large_dog' => 'warning',
                        'large_cat' => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'large_dog' => 'Крупная собака',
                        'giant_dog' => 'Гигантская порода',
                        'large_cat' => 'Крупная кошка',
                        'other_large' => 'Другие крупные',
                        default => $state,
                    })
                    ->label('Вид'),

                Tables\Columns\TextColumn::make('procedure_type')
                    ->badge()
                    ->label('Процедура'),

                Tables\Columns\TextColumn::make('aggression_level')
                    ->label('Агрессия')
                    ->color(fn (int $state): string => match (true) {
                        $state >= 8 => 'danger',
                        $state >= 6 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('stress_level_before')
                    ->label('Стресс до')
                    ->color(fn (int $state): string => $state >= 7 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('stress_level_after')
                    ->label('Стресс после')
                    ->color(fn (int $state): string => $state >= 7 ? 'danger' : 'success'),

                Tables\Columns\IconColumn::make('safety_incident')
                    ->boolean()
                    ->color('danger')
                    ->label('Инцидент'),

                Tables\Columns\IconColumn::make('secondGroomer')
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => $record->second_groomer_id !== null)
                    ->label('2-й грумер'),

                Tables\Columns\IconColumn::make('veterinarian')
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => $record->veterinarian_id !== null)
                    ->label('Ветеринар'),

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
                        'large_dog' => 'Крупная собака',
                        'giant_dog' => 'Гигантская порода',
                        'large_cat' => 'Крупная кошка',
                        'other_large' => 'Другие крупные',
                    ])
                    ->label('Вид'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'in_progress' => 'В процессе',
                        'completed' => 'Завершена',
                        'cancelled' => 'Отменена',
                    ])
                    ->label('Статус'),

                Tables\Filters\Filter::make('high_aggression')
                    ->query(fn ($query) => $query->where('aggression_level', '>=', 7))
                    ->label('Высокая агрессия'),

                Tables\Filters\Filter::make('safety_incidents')
                    ->query(fn ($query) => $query->where('safety_incident', true))
                    ->label('Инциденты'),
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
            'index' => \Modules\VetGrooming\Filament\Resources\LargeMammalGroomingSessionResource\Pages\ListLargeMammalGroomingSessions::route('/'),
            'create' => \Modules\VetGrooming\Filament\Resources\LargeMammalGroomingSessionResource\Pages\CreateLargeMammalGroomingSession::route('/create'),
            'edit' => \Modules\VetGrooming\Filament\Resources\LargeMammalGroomingSessionResource\Pages\EditLargeMammalGroomingSession::route('/{record}/edit'),
        ];
    }
}
