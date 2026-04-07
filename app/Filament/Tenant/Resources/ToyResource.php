<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

final class ToyResource extends Resource
{

    protected static ?string $model = Toy::class;

        protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
        protected static ?string $navigationGroup = 'Toys & Games Management';
        protected static ?string $tenantOwnershipRelationshipName = 'store'; // Assuming Toys belongs to Stores, scoped to Tenant

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('General Information')
                    ->description('Core toy identification and categorisation')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn(string $state, $set) => $set('sku', 'TOY-' . strtoupper(Str::slug($state)) . '-' . rand(100, 999))),
                            TextInput::make('sku')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(50),
                            Select::make('store_id')
                                ->label('Store/Brand')
                                ->relationship('store', 'name')
                                ->searchable()
                                ->required(),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('category_id')
                                ->label('Toy Category')
                                ->relationship('category', 'name')
                                ->required(),
                            Select::make('age_group_id')
                                ->label('Age Group')
                                ->relationship('ageGroup', 'name')
                                ->required(),
                            TextInput::make('brand_name')
                                ->label('Brand/Manufacturer')
                                ->maxLength(100),
                        ]),
                    ]),

                Section::make('Pricing & Inventory')
                    ->description('Dual pricing (B2C/B2B) and stock management')
                    ->schema([
                        Grid::make(4)->schema([
                            TextInput::make('price_b2c')
                                ->label('Price B2C (cop.)')
                                ->numeric()
                                ->prefix('RUB')
                                ->required(),
                            TextInput::make('price_b2b')
                                ->label('Price B2B (cop.)')
                                ->numeric()
                                ->prefix('RUB')
                                ->required(),
                            TextInput::make('stock_quantity')
                                ->label('Stock qty')
                                ->numeric()
                                ->required()
                                ->default(0),
                            TextInput::make('safety_certification')
                                ->label('Safety Cert.')
                                ->placeholder('e.g. CE, EAC, ASTM F963'),
                        ]),
                    ]),

                Section::make('Details & Media')
                    ->schema([
                        RichEditor::make('description')
                            ->required()
                            ->columnSpanFull(),
                        RichEditor::make('specifications')
                            ->columnSpanFull(),
                        Grid::make(2)->schema([
                            FileUpload::make('images')
                                ->multiple()
                                ->image()
                                ->directory('toys/media')
                                ->columnSpanFull(),
                            Forms\Components\TagsInput::make('tags')
                                ->separator(',')
                                ->placeholder('e.g. educational, lego, wooden'),
                        ]),
                    ]),

                Section::make('Flags')
                    ->schema([
                        Grid::make(4)->schema([
                            Toggle::make('is_active')->default(true),
                            Toggle::make('is_gift_wrappable')->default(true),
                            Toggle::make('is_b2b_only')->default(false),
                            TextInput::make('material_type')
                                ->placeholder('e.g. Wood, Bio-plastic'),
                        ]),
                    ]),
            ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListToy::route('/'),
                'create' => Pages\CreateToy::route('/create'),
                'edit' => Pages\EditToy::route('/{record}/edit'),
                'view' => Pages\ViewToy::route('/{record}'),
            ];
        }
}
