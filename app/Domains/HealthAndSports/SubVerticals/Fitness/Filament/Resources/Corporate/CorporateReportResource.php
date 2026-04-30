<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Corporate;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\Corporate\CorporateReportModel;

final class CorporateReportResource extends Resource
{
    protected static ?string $model = CorporateReportModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Fitness - Corporate';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Report Details')
                    ->schema([
                        Forms\Components\Select::make('corporate_enrollment_id')
                            ->relationship('corporateEnrollment', 'id')
                            ->searchable()
                            ->required()
                            ->label('Corporate Enrollment'),
                        
                        Forms\Components\DatePicker::make('period_start')
                            ->required()
                            ->label('Period Start'),
                        
                        Forms\Components\DatePicker::make('period_end')
                            ->required()
                            ->label('Period End')
                            ->after('period_start'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Statistics')
                    ->schema([
                        Forms\Components\TextInput::make('attendance_rate')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->label('Attendance Rate'),
                        
                        Forms\Components\TextInput::make('active_employees')
                            ->numeric()
                            ->minValue(0)
                            ->label('Active Employees'),
                        
                        Forms\Components\TextInput::make('total_workouts')
                            ->numeric()
                            ->minValue(0)
                            ->label('Total Workouts'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Additional Data')
                    ->schema([
                        Forms\Components\KeyValue::make('top_workouts')
                            ->label('Top Workouts')
                            ->keyLabel('Workout')
                            ->valueLabel('Count')
                            ->addable()
                            ->editable()
                            ->deletable(),
                        
                        Forms\Components\KeyValue::make('employee_stats')
                            ->label('Employee Statistics')
                            ->keyLabel('Employee ID')
                            ->valueLabel('Stats')
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
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('corporateEnrollment.corporateClient.name')
                    ->searchable()
                    ->label('Company'),
                
                Tables\Columns\TextColumn::make('period_start')
                    ->date()
                    ->label('Period Start'),
                
                Tables\Columns\TextColumn::make('period_end')
                    ->date()
                    ->label('Period End'),
                
                Tables\Columns\TextColumn::make('attendance_rate')
                    ->numeric()
                    ->suffix('%')
                    ->label('Attendance')
                    ->color(fn ($state) => $state >= 70 ? 'success' : ($state >= 50 ? 'warning' : 'danger')),
                
                Tables\Columns\TextColumn::make('active_employees')
                    ->numeric()
                    ->label('Active Employees'),
                
                Tables\Columns\TextColumn::make('total_workouts')
                    ->numeric()
                    ->label('Total Workouts'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Generated At')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('period')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn ($query) => $query->where('period_start', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn ($query) => $query->where('period_end', '<=', $data['until'])
                            );
                    }),
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
            'index' => \Modules\Fitness\Filament\Resources\Corporate\Pages\ListCorporateReports::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\Corporate\Pages\CreateCorporateReport::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\Corporate\Pages\EditCorporateReport::route('/{record}/edit'),
        ];
    }
}
