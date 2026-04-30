<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\SeasonalPrograms;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\SeasonalPrograms\ClientProgramEnrollmentModel;

final class ClientProgramEnrollmentResource extends Resource
{
    protected static ?string $model = ClientProgramEnrollmentModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Fitness - Seasonal Programs';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Enrollment Information')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'first_name')
                            ->searchable()
                            ->required()
                            ->label('Client'),
                        
                        Forms\Components\Select::make('seasonal_program_id')
                            ->relationship('seasonalProgram', 'name')
                            ->searchable()
                            ->required()
                            ->label('Program'),
                        
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->label('Start Date'),
                        
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Progress')
                    ->schema([
                        Forms\Components\TextInput::make('progress_percent')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(0)
                            ->label('Progress'),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'paused' => 'Paused',
                                'dropped' => 'Dropped',
                                'medical_drop' => 'Medical Drop',
                            ])
                            ->default('active')
                            ->required()
                            ->label('Status'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Metrics')
                    ->schema([
                        Forms\Components\KeyValue::make('initial_metrics')
                            ->label('Initial Metrics')
                            ->keyLabel('Metric')
                            ->valueLabel('Value')
                            ->addable()
                            ->editable()
                            ->deletable(),
                        
                        Forms\Components\KeyValue::make('final_metrics')
                            ->label('Final Metrics')
                            ->keyLabel('Metric')
                            ->valueLabel('Value')
                            ->addable()
                            ->editable()
                            ->deletable(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->label('Notes')
                            ->rows(3),
                        
                        Forms\Components\Textarea::make('drop_reason')
                            ->maxLength(65535)
                            ->label('Drop Reason')
                            ->rows(2)
                            ->visible(fn (callable $get) => $get('status') === 'dropped' || $get('status') === 'medical_drop'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.first_name')
                    ->searchable()
                    ->label('Client'),
                
                Tables\Columns\TextColumn::make('seasonalProgram.name')
                    ->searchable()
                    ->label('Program'),
                
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->label('Start Date'),
                
                Tables\Columns\TextColumn::make('progress_percent')
                    ->numeric()
                    ->suffix('%')
                    ->label('Progress')
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state >= 50 ? 'warning' : 'gray')),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'paused' => 'warning',
                        'dropped' => 'danger',
                        'medical_drop' => 'danger',
                    })
                    ->label('Status'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Enrolled At')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'paused' => 'Paused',
                        'dropped' => 'Dropped',
                        'medical_drop' => 'Medical Drop',
                    ]),
                
                Tables\Filters\SelectFilter::make('seasonal_program_id')
                    ->relationship('seasonalProgram', 'name'),
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
            'index' => \Modules\Fitness\Filament\Resources\SeasonalPrograms\Pages\ListClientProgramEnrollments::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\SeasonalPrograms\Pages\CreateClientProgramEnrollment::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\SeasonalPrograms\Pages\EditClientProgramEnrollment::route('/{record}/edit'),
        ];
    }
}
