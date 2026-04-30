<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Corporate;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\Corporate\EmployeeMembershipModel;

final class EmployeeMembershipResource extends Resource
{
    protected static ?string $model = EmployeeMembershipModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Fitness - Corporate';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Membership Details')
                    ->schema([
                        Forms\Components\Select::make('corporate_enrollment_id')
                            ->relationship('corporateEnrollment', 'id')
                            ->searchable()
                            ->required()
                            ->label('Corporate Enrollment'),
                        
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'first_name')
                            ->searchable()
                            ->required()
                            ->label('Employee'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Visits')
                    ->schema([
                        Forms\Components\TextInput::make('total_visits')
                            ->numeric()
                            ->minValue(0)
                            ->label('Total Visits'),
                        
                        Forms\Components\TextInput::make('remaining_visits')
                            ->numeric()
                            ->minValue(0)
                            ->label('Remaining Visits'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->label('Start Date'),
                        
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->after('start_date'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'expired' => 'Expired',
                            ])
                            ->default('active')
                            ->required()
                            ->label('Status'),
                    ]),
                
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
                    ->label('Employee'),
                
                Tables\Columns\TextColumn::make('corporateEnrollment.corporateClient.name')
                    ->searchable()
                    ->label('Company'),
                
                Tables\Columns\TextColumn::make('remaining_visits')
                    ->numeric()
                    ->label('Remaining Visits')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                
                Tables\Columns\TextColumn::make('total_visits')
                    ->numeric()
                    ->label('Total Visits'),
                
                Tables\Columns\TextColumn::make('usage_percentage')
                    ->numeric()
                    ->suffix('%')
                    ->label('Usage')
                    ->getStateUsing(fn ($record) => $record->total_visits > 0 
                        ? round((($record->total_visits - $record->remaining_visits) / $record->total_visits) * 100, 1)
                        : 0),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'expired' => 'danger',
                    })
                    ->label('Status'),
                
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->label('Start'),
                
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->label('End'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'expired' => 'Expired',
                    ]),
                
                Tables\Filters\Filter::make('low_visits')
                    ->query(fn ($query) => $query->where('remaining_visits', '<', 5))
                    ->label('Low Visits (<5)'),
                
                Tables\Filters\Filter::make('no_visits')
                    ->query(fn ($query) => $query->where('remaining_visits', 0))
                    ->label('No Visits Left'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('use_visit')
                    ->icon('heroicon-o-minus-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->remaining_visits > 0 && $record->status === 'active')
                    ->action(function ($record) {
                        $service = app(\Modules\Fitness\Application\Services\CorporateFitnessService::class);
                        $service->useEmployeeVisit($record->id);
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
            'index' => \Modules\Fitness\Filament\Resources\Corporate\Pages\ListEmployeeMemberships::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\Corporate\Pages\CreateEmployeeMembership::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\Corporate\Pages\EditEmployeeMembership::route('/{record}/edit'),
        ];
    }
}
