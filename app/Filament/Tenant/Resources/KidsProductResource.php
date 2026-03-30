<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsProductResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = KidsProduct::class;

        protected static ?string $navigationIcon = 'heroicon-o-gift';
        protected static ?string $navigationGroup = 'Baby & Kids';
        protected static ?string $tenantOwnershipRelationshipName = 'tenant';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Section::make('Core Information')
                                ->description('Basic product identity.')
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('e.g. Wooden Block Set'),
                                    Forms\Components\RichEditor::make('description')
                                        ->required()
                                        ->columnSpanFull(),
                                    Forms\Components\Select::make('store_id')
                                        ->label('Store / Warehouse')
                                        ->required()
                                        ->options(fn() => KidsStore::pluck('name', 'id'))
                                        ->searchable(),
                                ])->columns(2),

                            Forms\Components\Section::make('Finance & Stock')
                                ->schema([
                                    Forms\Components\TextInput::make('price')
                                        ->label('Price (Kopecks)')
                                        ->required()
                                        ->numeric()
                                        ->default(0)
                                        ->suffix('RUB kop'),
                                    Forms\Components\TextInput::make('stock_quantity')
                                        ->label('Inventory Count')
                                        ->required()
                                        ->numeric()
                                        ->default(0),
                                    Forms\Components\TextInput::make('sku')
                                        ->label('Stock Keeping Unit (SKU)')
                                        ->required()
                                        ->unique(ignoreRecord: true),
                                    Forms\Components\TextInput::make('barcode')
                                        ->label('UPC / EAN Barcode')
                                        ->maxLength(50),
                                ])->columns(2),
                        ])->columnSpan(['lg' => 2]),

                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Section::make('Safety & Quality')
                                ->description('Mandatory children safety attributes.')
                                ->schema([
                                    Forms\Components\Select::make('safety_class')
                                        ->label('Safety Level')
                                        ->required()
                                        ->options([
                                            'A' => 'Premium Safety (0+ months)',
                                            'B' => 'Standard Safety (3+ years)',
                                            'C' => 'Advanced Safety (8+ years)',
                                        ])
                                        ->default('B'),
                                    Forms\Components\Fieldset::make('Age Range (Months)')
                                        ->schema([
                                            Forms\Components\TextInput::make('age_range.min_months')
                                                ->label('Min Months')
                                                ->numeric()
                                                ->default(0),
                                            Forms\Components\TextInput::make('age_range.max_months')
                                                ->label('Max Months')
                                                ->numeric()
                                                ->default(120),
                                        ])->columns(2),
                                    Forms\Components\KeyValue::make('material_details')
                                        ->label('Material Composition')
                                        ->keyLabel('Material')
                                        ->valueLabel('Percentage (%)')
                                        ->default(['Wood' => '100']),
                                    Forms\Components\TextInput::make('origin_country')
                                        ->label('Country of Origin')
                                        ->required()
                                        ->default('Russia'),
                                ]),

                            Forms\Components\Section::make('Identity & Tracking')
                                ->schema([
                                    Forms\Components\TextInput::make('uuid')
                                        ->label('UUID')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->placeholder('Auto-generated'),
                                    Forms\Components\TextInput::make('correlation_id')
                                        ->label('Correlation Trace ID')
                                        ->disabled()
                                        ->dehydrated(false),
                                    Forms\Components\TagsInput::make('tags')
                                        ->label('Analytics Tags')
                                        ->placeholder('e.g. educational, eco-friendly'),
                                ]),
                        ])->columnSpan(['lg' => 1]),
                ])->columns(3);

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListKidsProduct::route('/'),
                'create' => Pages\\CreateKidsProduct::route('/create'),
                'edit' => Pages\\EditKidsProduct::route('/{record}/edit'),
                'view' => Pages\\ViewKidsProduct::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListKidsProduct::route('/'),
                'create' => Pages\\CreateKidsProduct::route('/create'),
                'edit' => Pages\\EditKidsProduct::route('/{record}/edit'),
                'view' => Pages\\ViewKidsProduct::route('/{record}'),
            ];
        }
}
