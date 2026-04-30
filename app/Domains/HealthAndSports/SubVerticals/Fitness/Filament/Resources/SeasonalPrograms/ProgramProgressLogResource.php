<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\SeasonalPrograms;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\SeasonalPrograms\ProgramProgressLogModel;

final class ProgramProgressLogResource extends Resource
{
    protected static ?string $model = ProgramProgressLogModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Fitness - Seasonal Programs';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Log Information')
                    ->schema([
                        Forms\Components\Select::make('enrollment_id')
                            ->relationship('enrollment', 'id')
                            ->searchable()
                            ->required()
                            ->label('Enrollment'),
                        
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->label('Date'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Physical Metrics')
                    ->schema([
                        Forms\Components\TextInput::make('weight')
                            ->numeric()
                            ->suffix('kg')
                            ->label('Weight'),
                        
                        Forms\Components\TextInput::make('body_fat_percentage')
                            ->numeric()
                            ->suffix('%')
                            ->label('Body Fat %'),
                        
                        Forms\Components\KeyValue::make('measurements')
                            ->label('Measurements (cm)')
                            ->keyLabel('Body Part')
                            ->valueLabel('Circumference')
                            ->addable()
                            ->editable()
                            ->deletable(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Wellbeing Scores')
                    ->schema([
                        Forms\Components\TextInput::make('wellbeing_score')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->label('Wellbeing (1-10)'),
                        
                        Forms\Components\TextInput::make('energy_level')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->label('Energy Level (1-10)'),
                        
                        Forms\Components\TextInput::make('sleep_quality')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->label('Sleep Quality (1-10)'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\KeyValue::make('metrics')
                            ->label('Custom Metrics')
                            ->keyLabel('Metric')
                            ->valueLabel('Value')
                            ->addable()
                            ->editable()
                            ->deletable(),
                        
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->label('Client Notes')
                            ->rows(2),
                        
                        Forms\Components\Textarea::make('trainer_notes')
                            ->maxLength(65535)
                            ->label('Trainer Notes')
                            ->rows(2),
                        
                        Forms\Components\Toggle::make('completed_workout')
                            ->label('Completed Workout'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('enrollment.client.first_name')
                    ->searchable()
                    ->label('Client'),
                
                Tables\Columns\TextColumn::make('enrollment.seasonalProgram.name')
                    ->searchable()
                    ->label('Program'),
                
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->label('Date')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('weight')
                    ->numeric()
                    ->suffix(' kg')
                    ->label('Weight'),
                
                Tables\Columns\TextColumn::make('body_fat_percentage')
                    ->numeric()
                    ->suffix('%')
                    ->label('Body Fat'),
                
                Tables\Columns\IconColumn::make('completed_workout')
                    ->boolean()
                    ->label('Workout Done'),
                
                Tables\Columns\TextColumn::make('wellbeing_score')
                    ->numeric()
                    ->label('Wellbeing')
                    ->color(fn ($state) => $state >= 7 ? 'success' : ($state >= 4 ? 'warning' : 'danger')),
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn ($query) => $query->whereDate('date', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn ($query) => $query->whereDate('date', '<=', $data['until'])
                            );
                    }),
                
                Tables\Filters\TernaryFilter::make('completed_workout')
                    ->label('Workout Completed'),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Fitness\Filament\Resources\SeasonalPrograms\Pages\ListProgramProgressLogs::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\SeasonalPrograms\Pages\CreateProgramProgressLog::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\SeasonalPrograms\Pages\EditProgramProgressLog::route('/{record}/edit'),
        ];
    }
}
