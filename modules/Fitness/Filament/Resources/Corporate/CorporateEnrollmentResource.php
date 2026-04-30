<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Corporate;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\Corporate\CorporateEnrollmentModel;

final class CorporateEnrollmentResource extends Resource
{
    protected static ?string $model = CorporateEnrollmentModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Fitness - Corporate';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Enrollment Details')
                    ->schema([
                        Forms\Components\Select::make('corporate_client_id')
                            ->relationship('corporateClient', 'name')
                            ->searchable()
                            ->required()
                            ->label('Corporate Client'),
                        
                        Forms\Components\Select::make('corporate_package_id')
                            ->relationship('corporatePackage', 'name')
                            ->searchable()
                            ->required()
                            ->label('Package'),
                        
                        Forms\Components\TextInput::make('number_of_employees')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->label('Number of Employees'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Financials')
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('₽')
                            ->required()
                            ->label('Total Amount'),
                        
                        Forms\Components\TextInput::make('invoice_number')
                            ->maxLength(255)
                            ->label('Invoice Number'),
                        
                        Forms\Components\DatePicker::make('invoice_date')
                            ->label('Invoice Date'),
                        
                        Forms\Components\DatePicker::make('paid_at')
                            ->label('Paid At'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->label('Start Date'),
                        
                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->label('End Date')
                            ->after('start_date'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->required()
                            ->label('Status'),
                    ]),
                
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
                Tables\Columns\TextColumn::make('corporateClient.name')
                    ->searchable()
                    ->label('Company'),
                
                Tables\Columns\TextColumn::make('corporatePackage.name')
                    ->searchable()
                    ->label('Package'),
                
                Tables\Columns\TextColumn::make('number_of_employees')
                    ->numeric()
                    ->label('Employees'),
                
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('RUB')
                    ->label('Total'),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'suspended' => 'warning',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                    })
                    ->label('Status'),
                
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->label('Start'),
                
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->label('End'),
                
                Tables\Columns\IconColumn::make('paid_at')
                    ->boolean()
                    ->label('Paid')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                
                Tables\Filters\Filter::make('expiring_soon')
                    ->query(fn ($query) => $query->where('status', 'active')
                        ->where('end_date', '<=', now()->addDays(30))
                        ->where('end_date', '>', now()))
                    ->label('Expiring Soon (30 days)'),
                
                Tables\Filters\Filter::make('unpaid')
                    ->query(fn ($query) => $query->whereNull('paid_at'))
                    ->label('Unpaid'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('activate')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(function ($record) {
                        $service = app(\Modules\Fitness\Application\Services\CorporateFitnessService::class);
                        $service->activateCorporateEnrollment($record->id);
                    }),
                Tables\Actions\Action::make('mark_paid')
                    ->icon('heroicono-o-currency-dollar')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->paid_at === null)
                    ->action(function ($record) {
                        $service = app(\Modules\Fitness\Application\Services\CorporateFitnessService::class);
                        $service->markEnrollmentAsPaid($record->id);
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
            'index' => \Modules\Fitness\Filament\Resources\Corporate\Pages\ListCorporateEnrollments::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\Corporate\Pages\CreateCorporateEnrollment::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\Corporate\Pages\EditCorporateEnrollment::route('/{record}/edit'),
        ];
    }
}
