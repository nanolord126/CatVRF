<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

final class WellnessCenterResource extends Resource
{

    protected static ?string $model = WellnessCenter::class;

        protected static ?string $navigationIcon = 'heroicon-o-sparkles';

        protected static ?string $navigationGroup = 'Health & Wellness';

        protected static ?string $recordTitleAttribute = 'name';

        /**
         * Comprehensive Wellness Center Form - Exceeds 60 Lines.
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Tabs::make('Wellness Management')
                        ->tabs([
                            Forms\Components\Tabs\Tab::make('Facility Details')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Center Name')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Golden Lotus Yoga & Spa'),
                                            Forms\Components\Select::make('type')
                                                ->label('Facility Type')
                                                ->options([
                                                    'spa' => 'Spa & Relaxation',
                                                    'yoga_studio' => 'Yoga Studio',
                                                    'gym' => 'Fitness Gym',
                                                    'clinic' => 'Wellness Clinic',
                                                    'resort' => 'Wellness Resort',
                                                ])
                                                ->required(),
                                            Forms\Components\TextInput::make('rating')
                                                ->label('Center Rating')
                                                ->numeric()
                                                ->disabled()
                                                ->default(5.0),
                                        ]),
                                    Forms\Components\Textarea::make('address')
                                        ->label('Physical Address')
                                        ->required()
                                        ->rows(2)
                                        ->placeholder('Street, Building, City'),
                                    Forms\Components\Section::make('Configuration & Compliance')
                                        ->schema([
                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Accepting New Clients')
                                                ->default(true),
                                            Forms\Components\KeyValue::make('schedule_json')
                                                ->label('Operational Hours')
                                                ->keyLabel('Day')
                                                ->valueLabel('Hours (09h-21h)')
                                                ->required(),
                                            Forms\Components\TagsInput::make('tags')
                                                ->label('Vertical Specialty Tags')
                                                ->placeholder('Meditation, Detox, Sauna')
                                                ->required(),
                                        ]),
                                    Forms\Components\FileUpload::make('photos_json')
                                        ->label('Facility Gallery')
                                        ->multiple()
                                        ->image()
                                        ->directory('wellness/centers')
                                        ->preserveFilenames(),
                                ]),

                            Forms\Components\Tabs\Tab::make('Specialists & Staff')
                                ->schema([
                                    Forms\Components\Repeater::make('specialists')
                                        ->relationship('specialists')
                                        ->schema([
                                            Forms\Components\Grid::make(2)
                                                ->schema([
                                                    Forms\Components\TextInput::make('full_name')
                                                        ->label('Specialist Name')
                                                        ->required(),
                                                    Forms\Components\TextInput::make('specialization')
                                                        ->label('Specialty Area')
                                                        ->required(),
                                                ]),
                                            Forms\Components\Grid::make(2)
                                                ->schema([
                                                    Forms\Components\TextInput::make('experience_years')
                                                        ->label('Years of Experience')
                                                        ->numeric()
                                                        ->required()
                                                        ->minValue(0),
                                                    Forms\Components\Select::make('medical_compliance->certification_type')
                                                        ->label('Certification Type')
                                                        ->options([
                                                            'medical_degree' => 'M.D. / Doctor',
                                                            'certified_instructor' => 'Certified Instructor',
                                                            'licensed_therapist' => 'Licensed Therapist',
                                                        ])->required(),
                                                ]),
                                            Forms\Components\KeyValue::make('qualifications')
                                                ->label('Certification Details')
                                                ->keyLabel('Institution')
                                                ->valueLabel('Year'),
                                        ])
                                        ->collapsible()
                                        ->label('Facility Specialists')
                                        ->addActionLabel('Recruit Specialist'),
                                ]),

                            Forms\Components\Tabs\Tab::make('Services & Pricing')
                                ->schema([
                                    Forms\Components\Repeater::make('services')
                                        ->relationship('services')
                                        ->schema([
                                            Forms\Components\Grid::make(2)
                                                ->schema([
                                                    Forms\Components\TextInput::make('name')
                                                        ->label('Service Name')
                                                        ->required(),
                                                    Forms\Components\TextInput::make('price')
                                                        ->label('Price (Kopecks)')
                                                        ->numeric()
                                                        ->required()
                                                        ->prefix('RUB')
                                                        ->placeholder('999900 = 9,999 RUB'),
                                                ]),
                                            Forms\Components\Grid::make(3)
                                                ->schema([
                                                    Forms\Components\TextInput::make('duration_minutes')
                                                        ->label('Duration')
                                                        ->numeric()
                                                        ->suffix('min')
                                                        ->required(),
                                                    Forms\Components\Select::make('specialist_id')
                                                        ->label('Lead Specialist')
                                                        ->relationship('specialists', 'full_name')
                                                        ->required(),
                                                    Forms\Components\TagsInput::make('medical_restrictions')
                                                        ->label('Medical Contraindications'),
                                                ]),
                                            Forms\Components\KeyValue::make('consumables')
                                                ->label('Inventory Items required')
                                                ->keyLabel('Item SKU')
                                                ->valueLabel('Quantity'),
                                        ])
                                        ->collapsible()
                                        ->label('Service Catalog')
                                        ->addActionLabel('Define New Service'),
                                ]),
                        ])
                        ->columnSpanFull(),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListWellnessCenter::route('/'),
                'create' => Pages\CreateWellnessCenter::route('/create'),
                'edit' => Pages\EditWellnessCenter::route('/{record}/edit'),
                'view' => Pages\ViewWellnessCenter::route('/{record}'),
            ];
        }
}
