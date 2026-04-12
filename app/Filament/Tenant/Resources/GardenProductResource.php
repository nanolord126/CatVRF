<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class GardenProductResource extends Resource
{

    protected static ?string $model = GardenProduct::class;
        protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
        protected static ?string $navigationGroup = 'Greenery & Outdoor';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('General Product Info')
                        ->columns(3)
                        ->schema([
                            Forms\Components\Select::make('store_id')
                                ->relationship('store', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('category_id')
                                ->relationship('category', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->hint('Product catalog name (e.g. Ficus Lyrata)'),
                            Forms\Components\TextInput::make('sku')
                                ->unique(ignoreRecord: true)
                                ->required()
                                ->maxLength(50)
                                ->hint('The unique SKU (must be immutable after save).'),
                            Forms\Components\Textarea::make('description')
                                ->maxLength(1000)
                                ->columnSpan(3),
                        ]),

                    Forms\Components\Section::make('Production & Mode-Based Pricing')
                        ->columns(3)
                        ->schema([
                            Forms\Components\TextInput::make('price_b2c')
                                ->numeric()
                                ->required()
                                ->suffix('Kopecks (RUB × 100)')
                                ->hint('Base Retail Price for consumers.'),
                            Forms\Components\TextInput::make('price_b2b')
                                ->numeric()
                                ->required()
                                ->suffix('Kopecks')
                                ->hint('Special wholesale price for landscapers.'),
                            Forms\Components\TextInput::make('stock_quantity')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->hint('Current real inventory count.'),
                            Forms\Components\Toggle::make('is_published')
                                ->label('Active Catalog Item')
                                ->required(),
                        ]),

                    Forms\Components\Section::make('Biological Metadata (Plant specific)')
                        ->relationship('plant')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('botanical_name')
                                ->maxLength(255)
                                ->columnSpan(2)
                                ->placeholder('e.g. Ficus lyrata'),
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Select::make('hardiness_zone')
                                        ->options(array_combine(range(1, 11), range(1, 11)))
                                        ->label('Hardiness Zone')
                                        ->required(),
                                    Forms\Components\Select::make('light_requirement')
                                        ->options([
                                            'full_sun' => 'Full Sun',
                                            'partial_shade' => 'Partial Shade',
                                            'shade' => 'Full Shade',
                                        ])
                                        ->required(),
                                    Forms\Components\Select::make('water_needs')
                                        ->options([
                                            'low' => 'Low Drought-Tolerant',
                                            'medium' => 'Regular Maintenance',
                                            'high' => 'High Humidity/Water',
                                        ])
                                        ->required(),
                                ]),
                            Forms\Components\Fieldset::make('Lifecycle Details')
                                ->schema([
                                    Forms\Components\Toggle::make('is_seedling')
                                        ->label('Seedling / Young plant')
                                        ->required(),
                                    Forms\Components\DatePicker::make('sowing_start')
                                        ->label('Best Sowing Start Month'),
                                    Forms\Components\DatePicker::make('harvest_start')
                                        ->label('Estimated Harvest/Bloom Start'),
                                ]),
                            Forms\Components\KeyValue::make('care_calendar.actions')
                                ->label('Seasonal Care Actions by Month')
                                ->keyLabel('Month (1-12)')
                                ->valueLabel('Action (e.g. Pruning)')
                                ->columnSpan(2),
                        ]),

                    Forms\Components\Section::make('Search & Correlation Tags')
                        ->schema([
                            Forms\Components\TagsInput::make('tags')
                                ->placeholder('e.g. drought-tolerant, perennial, decorative-leaf')
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('correlation_id')
                                ->disabled()
                                ->hint('Unique security tracking CID.')
                                ->columnSpan(2),
                        ]),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListGardenProduct::route('/'),
                'create' => Pages\CreateGardenProduct::route('/create'),
                'edit' => Pages\EditGardenProduct::route('/{record}/edit'),
                'view' => Pages\ViewGardenProduct::route('/{record}'),
            ];
        }
}
