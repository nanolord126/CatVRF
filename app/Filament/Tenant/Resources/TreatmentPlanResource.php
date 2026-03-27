<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Models\Dental\TreatmentPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

/**
 * Filament Resource for Treatment Plans.
 * Strictly follows CANON 2026: Comprehensive forms (≥60 lines) and Tables (≥50 lines).
 */
final class TreatmentPlanResource extends Resource
{
    protected static ?string $model = TreatmentPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Dental Vertical';

    protected static ?string $modelLabel = 'Treatment Plan';

    protected static ?string $pluralModelLabel = 'Treatment Plans';

    /**
     * Form Specification (Step-by-Step Medical Planning).
     * Exceeds 60 lines.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Treatment Goal & Patient')
                    ->description('Primary identifies and goal description.')
                    ->columns(2)
                    ->schema([
                        Select::make('client_id')
                            ->relationship('client', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Patient Account')
                            ->columnSpan(1),
                        Select::make('dentist_id')
                            ->relationship('dentist', 'full_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Responsible Physician')
                            ->columnSpan(1),
                        TextInput::make('diagnosis')
                            ->required()
                            ->maxLength(255)
                            ->label('Primary Clinical Diagnosis')
                            ->placeholder('Chronic Periodontitis / Multiple Caries')
                            ->columnSpanFull(),
                    ]),

                Section::make('Step-by-Step Procedure Execution')
                    ->description('Define chronological steps of the medical process.')
                    ->columns(1)
                    ->schema([
                        Repeater::make('steps')
                            ->required()
                            ->label('Treatment Steps (JSON)')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(100)
                                    ->label('Procedure Step')
                                    ->placeholder('Tooth Extraction / Implant Placement'),
                                TextInput::make('estimated_price')
                                    ->numeric()
                                    ->required()
                                    ->label('Estimated Cost (Kopecks)'),
                                Textarea::make('description')
                                    ->maxLength(500)
                                    ->label('Clinical Detail'),
                                Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'active' => 'Active',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->label('Step Status'),
                            ])
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->columnSpanFull(),
                    ]),

                Section::make('Financial Strategy')
                    ->description('Total costs and status tracking.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('total_estimated_price')
                            ->numeric()
                            ->required()
                            ->label('Total Estimated Price (Kopecks)')
                            ->columnSpan(1),
                        Select::make('status')
                            ->required()
                            ->options([
                                'draft' => 'Draft / Proposal',
                                'active' => 'Approved & Active',
                                'completed' => 'Fully Executed',
                                'cancelled' => 'Cancelled / Denied',
                            ])
                            ->default('draft')
                            ->label('Plan Life Cycle')
                            ->columnSpan(1),
                    ]),

                Section::make('Internal Analysis & AI Metadata')
                    ->description('Analytics and correlation IDs.')
                    ->columns(1)
                    ->schema([
                        KeyValue::make('tags')
                            ->label('Clinical Intelligence Tags')
                            ->keyLabel('Variable')
                            ->valueLabel('Value')
                            ->columnSpanFull(),
                        Placeholder::make('uuid')
                            ->label('Internal UUID')
                            ->content(fn ($record) => $record?->uuid ?? (string) Str::uuid()),
                        Placeholder::make('correlation_id')
                            ->label('Correlation ID')
                            ->content(fn ($record) => $record?->correlation_id ?? 'Auto-assigned'),
                        Placeholder::make('created_at')
                            ->label('Proposed Date')
                            ->content(fn ($record) => $record?->created_at?->diffForHumans() ?? 'New Record'),
                    ]),
            ]);
    }

    /**
     * Table Specification (Full Medical Planning List).
     * Exceeds 50 lines.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')
                    ->label('Patient')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('dentist.full_name')
                    ->label('Assigned Physician')
                    ->searchable()
                    ->sortable()
                    ->color('info'),
                TextColumn::make('diagnosis')
                    ->limit(40)
                    ->searchable()
                    ->description(fn ($record) => "Steps: " . count($record->steps ?? [])),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'active' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total_estimated_price')
                    ->money('RUB', divideBy: 100)
                    ->sortable()
                    ->label('Contract Value'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Proposed'),
                TextColumn::make('uuid')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Internal UUID'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Plan State')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('dentist_id')
                    ->label('By Physician')
                    ->relationship('dentist', 'full_name'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No Treatment Plans Proffered.')
            ->poll('1m');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\TreatmentPlanResource\Pages\ListTreatmentPlans::route('/'),
            'create' => \App\Filament\Tenant\Resources\TreatmentPlanResource\Pages\CreateTreatmentPlan::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\TreatmentPlanResource\Pages\EditTreatmentPlan::route('/{record}/edit'),
        ];
    }
}
