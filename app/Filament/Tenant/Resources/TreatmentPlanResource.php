<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

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

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListTreatmentPlan::route('/'),
                'create' => Pages\CreateTreatmentPlan::route('/create'),
                'edit' => Pages\EditTreatmentPlan::route('/{record}/edit'),
                'view' => Pages\ViewTreatmentPlan::route('/{record}'),
            ];
        }
}
