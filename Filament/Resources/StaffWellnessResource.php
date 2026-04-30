<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * StaffWellnessResource — Layer 1: Presentation/UI Layer
 * 
 * Filament resource for Staff Wellness metrics management in Tenant Panel
 * Part of 9-layer architecture
 */
final class StaffWellnessResource extends Resource
{
    protected static ?string $model = null; // TODO: Create Eloquent model

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Благополучие';

    protected static ?string $navigationGroup = 'HRM';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Сотрудник')
                            ->relationship('employee', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('recorded_at')
                            ->label('Дата записи')
                            ->required()
                            ->default(now()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Метрики')
                    ->schema([
                        Forms\Components\TextInput::make('stress_level')
                            ->label('Уровень стресса (1-10)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->required(),
                        Forms\Components\TextInput::make('sleep_hours')
                            ->label('Часы сна')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(24)
                            ->required(),
                        Forms\Components\TextInput::make('work_hours')
                            ->label('Рабочие часы')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(24)
                            ->required(),
                        Forms\Components\TextInput::make('physical_activity_hours')
                            ->label('Часы физической активности')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(24)
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Сотрудник')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stress_level')
                    ->label('Стресс')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 8 => 'danger',
                        $state >= 5 => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('sleep_hours')
                    ->label('Сон (ч)')
                    ->numeric(),
                Tables\Columns\TextColumn::make('work_hours')
                    ->label('Работа (ч)')
                    ->numeric(),
                Tables\Columns\TextColumn::make('recorded_at')
                    ->label('Дата')
                    ->date(),
            ])
            ->filters([
                Tables\Filters\Filter::make('high_stress')
                    ->query(fn ($query) => $query->where('stress_level', '>=', 8))
                    ->label('Высокий стресс'),
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
