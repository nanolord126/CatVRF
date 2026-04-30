<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Senior;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\Senior\SeniorProgramEnrollmentModel;

final class SeniorProgramEnrollmentResource extends Resource
{
    protected static ?string $model = SeniorProgramEnrollmentModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationGroup = 'Fitness - Senior (55+)';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Enrollment Details')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'first_name')
                            ->searchable()
                            ->required()
                            ->label('Client'),
                        
                        Forms\Components\Select::make('senior_program_id')
                            ->relationship('seniorProgram', 'name')
                            ->searchable()
                            ->required()
                            ->label('Program'),
                        
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->label('Start Date'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Medical Clearance')
                    ->schema([
                        Forms\Components\Select::make('medical_clearance_status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->default('pending')
                            ->required()
                            ->label('Clearance Status'),
                        
                        Forms\Components\DatePicker::make('medical_clearance_date')
                            ->label('Clearance Date')
                            ->disabled(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Progress')
                    ->schema([
                        Forms\Components\TextInput::make('progress_percent')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->label('Progress'),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending_clearance' => 'Pending Clearance',
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending_clearance')
                            ->required()
                            ->label('Status'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Assessments')
                    ->schema([
                        Forms\Components\Textarea::make('initial_assessment')
                            ->label('Initial Assessment')
                            ->rows(3),
                        
                        Forms\Components\Textarea::make('final_assessment')
                            ->label('Final Assessment')
                            ->rows(3),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->label('Notes')
                            ->rows(2),
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
                
                Tables\Columns\TextColumn::make('seniorProgram.name')
                    ->searchable()
                    ->label('Program'),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'pending_clearance' => 'warning',
                        'active' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                    })
                    ->label('Status'),
                
                Tables\Columns\TextColumn::make('medical_clearance_status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    })
                    ->label('Medical Clearance'),
                
                Tables\Columns\TextColumn::make('progress_percent')
                    ->numeric()
                    ->suffix('%')
                    ->label('Progress')
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state >= 50 ? 'warning' : 'gray')),
                
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->label('Start Date'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending_clearance' => 'Pending Clearance',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                
                Tables\Filters\SelectFilter::make('medical_clearance_status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('approve_clearance')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->medical_clearance_status === 'pending')
                    ->action(function ($record) {
                        $service = app(\Modules\Fitness\Application\Services\SeniorFitnessService::class);
                        $service->approveMedicalClearance($record->id);
                    }),
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
            'index' => \Modules\Fitness\Filament\Resources\Senior\Pages\ListSeniorProgramEnrollments::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\Senior\Pages\CreateSeniorProgramEnrollment::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\Senior\Pages\EditSeniorProgramEnrollment::route('/{record}/edit'),
        ];
    }
}
