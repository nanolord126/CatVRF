<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Music;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class MusicStoreResource extends Resource
{

    protected static ?string $model = MusicStore::class;

        protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

        protected static ?string $navigationGroup = 'Music Management';

        protected static ?string $navigationLabel = 'Stores & Schools';

        /**
         * Build the form for creating and editing music stores.
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Store Identity')
                        ->description('Essential branding and location data.')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('name')
                                    ->label('Store Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Example: Yamaha Music School'),
                                TextInput::make('slug')
                                    ->label('Unique Slug')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('yamaha-music-school')
                                    ->maxLength(255),
                                Select::make('type')
                                    ->label('Business Type')
                                    ->options([
                                        'shop' => 'Music Shop',
                                        'school' => 'Music School',
                                        'studio' => 'Recording Studio',
                                        'mixed' => 'Mixed (Shop + School)',
                                    ])
                                    ->required()
                                    ->default('mixed'),
                            ]),
                        ]),

                    Section::make('Location and Contact')
                        ->description('Address and geographical attributes.')
                        ->schema([
                            Grid::make(1)->schema([
                                TextInput::make('address')
                                    ->label('Physical Address')
                                    ->required()
                                    ->maxLength(500)
                                    ->placeholder('Example: Moscow, Tverskaya 12'),
                                Grid::make(2)->schema([
                                    TextInput::make('geo_point.lat')
                                        ->label('Latitude')
                                        ->numeric()
                                        ->placeholder('Example: 55.7558'),
                                    TextInput::make('geo_point.lon')
                                        ->label('Longitude')
                                        ->numeric()
                                        ->placeholder('Example: 37.6173'),
                                ]),
                            ]),
                        ]),

                    Section::make('Schedule and Meta')
                        ->description('Operational hours and visibility status.')
                        ->schema([
                            Grid::make(2)->schema([
                                Textarea::make('schedule')
                                    ->label('Opening Schedule (JSON)')
                                    ->placeholder('{"mon": "09:00-18:00", "tue": "09:00-18:00"...}')
                                    ->rows(5),
                                Grid::make(1)->schema([
                                    Toggle::make('is_verified')
                                        ->label('Verified Business')
                                        ->default(false),
                                    TagsInput::make('tags')
                                        ->label('Store Tags')
                                        ->placeholder('Add labels like #premium, #verified, #certified'),
                                    TextInput::make('rating')
                                        ->label('Auto-Rating')
                                        ->disabled()
                                        ->placeholder('N/A (Calculated automatically from reviews)'),
                                    TextInput::make('review_count')
                                        ->label('Total Reviews')
                                        ->disabled()
                                        ->placeholder('0'),
                                ]),
                            ]),
                        ]),
                ]);
        }

        /**
         * Build the table for listing music stores.
         */
        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('name')
                        ->label('Store')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('type')
                        ->label('Type')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'school' => 'primary',
                            'studio' => 'warning',
                            default => 'info'
                        })
                        ->sortable(),
                    TextColumn::make('address')
                        ->label('Address')
                        ->searchable()
                        ->limit(50),
                    IconColumn::make('is_verified')
                        ->label('Verified')
                        ->boolean()
                        ->sortable(),
                    TextColumn::make('rating')
                        ->label('Rating')
                        ->numeric(decimalPlaces: 2)
                        ->sortable()
                        ->formatStateUsing(fn ($state) => $state ? $state . ' ★' : 'N/A'),
                    TextColumn::make('review_count')
                        ->label('Reviews')
                        ->numeric()
                        ->sortable(),
                    TextColumn::make('created_at')
                        ->label('Registered')
                        ->dateTime()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    SelectFilter::make('type')
                        ->options([
                            'shop' => 'Shop',
                            'school' => 'School',
                            'studio' => 'Studio',
                            'mixed' => 'Mixed',
                        ]),
                    SelectFilter::make('is_verified')
                        ->label('Verification Status')
                        ->options([
                            '1' => 'Verified Only',
                            '0' => 'Unverified Only',
                        ]),
                ])
                ->actions([
                    EditAction::make(),
                ])
                ->bulkActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make(),
                    ]),
                ]);
        }

        /**
         * Get Eloquent query with tenant scoping.
         */
        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->latest();
        }
}
