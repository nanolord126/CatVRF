<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\WeddingPlanning;

use Filament\Resources\Resource;

final class WeddingVendorResource extends Resource
{

    protected static ?string $model = WeddingVendor::class;

        protected static ?string $navigationIcon = 'heroicon-o-briefcase';

        protected static ?string $navigationGroup = 'Wedding Planning';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Vendor Profile')
                    ->description('Provide profile details of the professional service provider.')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Legal or Brand Name'),
                                Forms\Components\Select::make('category')
                                    ->options([
                                        'photographer' => 'Photography',
                                        'videographer' => 'Videography',
                                        'catering' => 'Catering & Food',
                                        'venue' => 'Venue / Location',
                                        'florist' => 'Florist & Decor',
                                        'music' => 'Music / DJ / Live Band',
                                        'makeup' => 'Stylist / Makeup / Hair',
                                        'cakes' => 'Cakes & Desserts',
                                        'transport' => 'Transportation',
                                    ])
                                    ->required()
                                    ->searchable(),
                                Forms\Components\TextInput::make('contact_person')
                                    ->maxLength(255)
                                    ->placeholder('Full Name'),
                                Forms\Components\TextInput::make('email')
                                    ->label('Contact Email')
                                    ->email()
                                    ->required(),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Contact Phone')
                                    ->tel()
                                    ->placeholder('+7 (900) ...'),
                                Forms\Components\TextInput::make('rating')
                                    ->numeric()
                                    ->label('External Rating')
                                    ->default(0.0)
                                    ->placeholder('0.0-5.0'),
                                Forms\Components\TextInput::make('min_price')
                                    ->numeric()
                                    ->label('Starting Price (RUB)')
                                    ->suffix('Kopecks (RUB)')
                                    ->helperText('Min service cost in kopecks (e.g. 5 000 000 for 50 000 RUB)'),
                                Forms\Components\Select::make('availability_status')
                                    ->options([
                                        'active' => 'Active',
                                        'busy' => 'Busy / Reserved',
                                        'inactive' => 'Inactive',
                                    ])
                                    ->default('active')
                                    ->required(),
                                Forms\Components\Select::make('business_group_id')
                                    ->relationship('businessGroup', 'name')
                                    ->label('Affiliate / Branch')
                                    ->searchable(),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Section: Meta-Data (B2B terms, tags, etc.)
                Forms\Components\Section::make('Advanced Terms & Data')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\KeyValue::make('terms_json')
                                    ->label('Custom B2B Terms / Policies')
                                    ->keyLabel('Key (Prepayment / Refund / Hold)')
                                    ->valueLabel('Policy Detail'),
                                Forms\Components\TagsInput::make('tags')
                                    ->label('Search Tags (Luxury, Budget, Winter, Outdoor)'),
                                Forms\Components\Textarea::make('description')
                                    ->label('Service Description')
                                    ->columnSpanFull()
                                    ->rows(3),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Review Section (ReadOnly for stats check)
                Forms\Components\Section::make('Security & Analysis')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Is Verified Vendor')
                                    ->default(true),
                                Forms\Components\TextInput::make('correlation_id')
                                    ->label('Tracing ID')
                                    ->disabled()
                                    ->placeholder('Generated on save'),
                                Forms\Components\TextInput::make('uuid')
                                    ->label('Global UUID')
                                    ->disabled()
                                    ->placeholder('System generated'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('min_price')
                    ->label('Start (RUB)')
                    ->money('RUB')
                    ->formatStateUsing(fn ($state) => (float)$state / 100),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rank')
                    ->numeric(1)
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Vrf'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'busy' => 'warning',
                        'inactive' => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'photographer' => 'Photography',
                        'catering' => 'Catering',
                        'venue' => 'Venue',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Verified Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('verify')
                    ->label('Verify & Log Account')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->action(fn (WeddingVendor $record) => $record->update(['is_active' => true])),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('wedding_vendors.tenant_id', filament()->getTenant()->id);
        }
}
