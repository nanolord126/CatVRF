<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\VetGrooming\Domain\Enums\VaccineType;
use Modules\VetGrooming\Domain\Enums\VaccinationStatus;
use Modules\VetGrooming\Infrastructure\Models\PetVaccinationModel;

class PetVaccinationResource extends Resource
{
    protected static ?string $model = PetVaccinationModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-syringe';

    protected static ?string $navigationGroup = 'Ветеринария';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о вакцинации')
                    ->schema([
                        Forms\Components\Select::make('pet_id')
                            ->label('Питомец')
                            ->relationship('pet', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('vaccine_type')
                            ->label('Тип вакцины')
                            ->options(VaccineType::class)
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('vaccine_name')
                            ->label('Название вакцины')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('manufacturer')
                            ->label('Производитель')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('batch_number')
                            ->label('Номер серии')
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('expiration_date')
                            ->label('Срок годности'),

                        Forms\Components\Select::make('veterinarian_id')
                            ->label('Ветеринар')
                            ->relationship('veterinarian', 'full_name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('clinic_id')
                            ->label('Клиника')
                            ->relationship('clinic', 'name')
                            ->searchable()
                            ->preload(),
                    ]),

                Forms\Components\Section::make('Дозировка и даты')
                    ->schema([
                        Forms\Components\TextInput::make('dose_number')
                            ->label('Номер дозы')
                            ->numeric()
                            ->default(1)
                            ->required(),

                        Forms\Components\TextInput::make('total_doses')
                            ->label('Всего доз в серии')
                            ->numeric()
                            ->default(1)
                            ->required(),

                        Forms\Components\DatePicker::make('planned_date')
                            ->label('Планируемая дата'),

                        Forms\Components\DatePicker::make('actual_date')
                            ->label('Фактическая дата'),

                        Forms\Components\DatePicker::make('next_due_date')
                            ->label('Следующая вакцинация'),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options(VaccinationStatus::class)
                            ->default(VaccinationStatus::PLANNED->value)
                            ->required(),
                    ]),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки')
                            ->rows(3),

                        Forms\Components\KeyValue::make('risk_factors')
                            ->label('Факторы риска')
                            ->keyLabel('Фактор')
                            ->valueLabel('Значение'),

                        Forms\Components\KeyValue::make('reaction_data')
                            ->label('Реакция на вакцину')
                            ->keyLabel('Параметр')
                            ->valueLabel('Значение'),
                    ]),
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

                Tables\Columns\TextColumn::make('vaccine_type')
                    ->label('Тип вакцины')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        VaccineType::RABIES->value => 'danger',
                        VaccineType::CORE_DHP->value, VaccineType::CORE_FPV->value => 'success',
                        default => 'info',
                    }),

                Tables\Columns\TextColumn::make('vaccine_name')
                    ->label('Вакцина')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        VaccinationStatus::COMPLETED->value => 'success',
                        VaccinationStatus::OVERDUE->value => 'danger',
                        VaccinationStatus::PLANNED->value => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('planned_date')
                    ->label('Планируемая дата')
                    ->date(),

                Tables\Columns\TextColumn::make('actual_date')
                    ->label('Фактическая дата')
                    ->date(),

                Tables\Columns\TextColumn::make('next_due_date')
                    ->label('Следующая вакцинация')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('veterinarian.full_name')
                    ->label('Ветеринар')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(VaccinationStatus::class),

                Tables\Filters\SelectFilter::make('vaccine_type')
                    ->label('Тип вакцины')
                    ->options(VaccineType::class),

                Tables\Filters\Filter::make('overdue')
                    ->label('Просроченные')
                    ->query(fn ($query) => $query->where('status', VaccinationStatus::OVERDUE->value)
                        ->orWhere(function ($q) {
                            $q->where('next_due_date', '<', now())
                                ->where('status', '!=', VaccinationStatus::COMPLETED->value);
                        })),
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
}
