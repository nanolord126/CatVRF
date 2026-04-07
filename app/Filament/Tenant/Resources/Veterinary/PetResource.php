<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Veterinary;

use Filament\Resources\Resource;

final class PetResource extends Resource
{

    protected static ?string $model = Pet::class;

        protected static ?string $navigationIcon = 'heroicon-o-sparkles';

        protected static ?string $navigationGroup = 'Veterinary & Pets';

        protected static ?string $tenantOwnershipRelationshipName = 'tenant';

        protected static ?int $navigationSort = 2;

        /**
         * Comprehensive Form Design (>= 60 lines)
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Tabs::make('Pet Passport')
                        ->tabs([
                            Tabs\Tab::make('General')
                                ->icon('heroicon-o-sparkles')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            Section::make('Pet Details')
                                                ->description('Основная информация о питомце')
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->required()
                                                        ->maxLength(100)
                                                        ->placeholder('Барсик'),

                                                    Select::make('type')
                                                        ->required()
                                                        ->options([
                                                            'cat' => 'Cat',
                                                            'dog' => 'Dog',
                                                            'rabbit' => 'Rabbit',
                                                            'bird' => 'Bird',
                                                            'exotic' => 'Exotic',
                                                        ])
                                                        ->native(false),

                                                    TextInput::make('breed')
                                                        ->maxLength(100)
                                                        ->placeholder('Мейн-кун'),

                                                    DatePicker::make('birth_date')
                                                        ->label('Birthday')
                                                        ->native(false),

                                                    Textarea::make('health_info')
                                                        ->rows(3)
                                                        ->placeholder('Аллергия на курицу, чипирован...')
                                                        ->columnSpanFull(),
                                                ])->columnSpan(2),

                                            Section::make('Profiling & Media')
                                                ->description('Медиа и владелец')
                                                ->schema([
                                                    FileUpload::make('photo_url')
                                                        ->image()
                                                        ->imageEditor()
                                                        ->directory('pets/photos')
                                                        ->label('Pet Photo'),

                                                    Select::make('owner_id')
                                                        ->relationship('user', 'name')
                                                        ->label('Pet Owner')
                                                        ->required()
                                                        ->searchable()
                                                        ->preload(),

                                                    Toggle::make('is_active')
                                                        ->label('Active Pet Profile')
                                                        ->default(true),
                                                ])->columnSpan(1),
                                        ]),
                                ]),

                            Tabs\Tab::make('Official Passport')
                                ->icon('heroicon-o-identification')
                                ->schema([
                                    Section::make('Identifiers')
                                        ->schema([
                                            Grid::make(3)
                                                ->schema([
                                                    TextInput::make('chip_number')
                                                        ->label('Chip Number')
                                                        ->placeholder('123456789012345'),
                                                    DatePicker::make('chip_installed_at')
                                                        ->label('Chip Date'),
                                                    TextInput::make('passport_number')
                                                        ->label('Passport Number')
                                                        ->placeholder('RU-1234-5678'),
                                                ]),
                                            Toggle::make('is_neutered')
                                                ->label('Neutered / Spayed')
                                                ->default(false),
                                        ]),

                                    Section::make('Pedigree (Родословная)')
                                        ->relationship('pedigree')
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    TextInput::make('registration_number')
                                                        ->label('Pedigree ID (WCF/RKF)')
                                                        ->required(),
                                                    TextInput::make('breed_club')
                                                        ->label('Breed Club'),
                                                ]),
                                            Grid::make(2)
                                                ->schema([
                                                    TextInput::make('father_name')->label('Father Name'),
                                                    TextInput::make('mother_name')->label('Mother Name'),
                                                ]),
                                            FileUpload::make('document_url')
                                                ->directory('pets/pedigrees')
                                                ->label('Scan Document'),
                                        ]),
                                ]),

                            Tabs\Tab::make('Vaccinations')
                                ->icon('heroicon-o-shield-check')
                                ->schema([
                                    Repeater::make('vaccinations')
                                        ->relationship()
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    TextInput::make('vaccine_name')->required(),
                                                    TextInput::make('serial_number'),
                                                ]),
                                            Grid::make(2)
                                                ->schema([
                                                    DatePicker::make('vaccinated_at')->required(),
                                                    DatePicker::make('expires_at')->required(),
                                                ]),
                                            Select::make('veterinarian_id')
                                                ->relationship('veterinarian', 'full_name')
                                                ->searchable()
                                                ->preload(),
                                        ])
                                        ->columns(2)
                                        ->collapsible()
                                        ->columnSpanFull(),
                                ]),

                            Tabs\Tab::make('Metrics History')
                                ->icon('heroicon-o-presentation-chart-line')
                                ->schema([
                                    Repeater::make('metrics')
                                        ->relationship()
                                        ->schema([
                                            Select::make('metric_type')
                                                ->options([
                                                    'weight' => 'Weight (kg)',
                                                    'height' => 'Height (cm)',
                                                    'temperature' => 'Temp (Celsius)',
                                                ])->required(),
                                            TextInput::make('value')->numeric()->required(),
                                            TextInput::make('unit')->required(),
                                            DateTimePicker::make('measured_at')->required(),
                                        ])
                                        ->columns(4)
                                        ->defaultItems(0)
                                        ->columnSpanFull(),
                                ]),
                        ])->columnSpanFull(),
                ]);
        }

        /**
         * Comprehensive Table Design (>= 50 lines)
         */
        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    ImageColumn::make('photo_url')
                        ->circular()
                        ->label('Photo'),

                    TextColumn::make('name')
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('type')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'dog' => 'success',
                            'exotic' => 'warning',
                            default => 'gray',
                        })
                        ->sortable(),

                    TextColumn::make('breed')
                        ->searchable()
                        ->sortable()
                        ->toggleable(),

                    TextColumn::make('user.name')
                        ->label('Owner')
                        ->searchable()
                        ->sortable(),

                    IconColumn::make('is_active')
                        ->boolean()
                        ->label('Active'),

                    TextColumn::make('birth_date')
                        ->date('d.M.Y')
                        ->sortable(),

                    TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('updated_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    SelectFilter::make('type')
                        ->options([
                            'cat' => 'Cat',
                            'dog' => 'Dog',
                            'rabbit' => 'Rabbit',
                            'exotic' => 'Exotic',
                        ]),

                    SelectFilter::make('owner_id')
                        ->relationship('user', 'name')
                        ->label('By Owner')
                        ->searchable()
                        ->preload(),

                    Tables\Filters\TernaryFilter::make('is_active')
                        ->label('Status (Active)'),
                ])
                ->actions([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                ->bulkActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make(),
                    ]),
                ])
                ->emptyStateHeading('No Pets Registered')
                ->emptyStateDescription('Добавьте первого питомца вашего клиента, чтобы начать вести медицинскую карту.')
                ->emptyStateIcon('heroicon-o-sparkles');
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]);
        }
}
