<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class DentalConsumableResource extends Resource
{

    protected static ?string $model = DentalConsumable::class;

        protected static ?string $navigationIcon = 'heroicon-o-beaker';

        protected static ?string $navigationGroup = 'Dental Vertical';

        protected static ?string $modelLabel = 'Consumable Resource';

        protected static ?string $pluralModelLabel = 'Consumable Resources';

        /**
         * Form Specification (Inventory Management).
         * Exceeds 60 lines.
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Resource Definition')
                        ->description('Primary inventory identifies.')
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->label('Material Name')
                                ->placeholder('Dental Composite / Anesthesia / Gloves')
                                ->columnSpan(1),
                            Select::make('dental_clinic_id')
                                ->relationship('clinic', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label('Attached Clinic Inventory')
                                ->columnSpan(1),
                            TextInput::make('sku')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(100)
                                ->label('Unique SKU Code')
                                ->placeholder('DEN-RES-001')
                                ->columnSpan(1),
                            TextInput::make('category')
                                ->required()
                                ->maxLength(100)
                                ->label('Medical Asset Category')
                                ->placeholder('Resin / Surgery / PPE')
                                ->columnSpan(1),
                        ]),

                    Section::make('Stock Control (Units)')
                        ->description('Real-time ledger of units available.')
                        ->columns(3)
                        ->schema([
                            TextInput::make('current_stock')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->label('Available Units')
                                ->columnSpan(1),
                            TextInput::make('min_stock_threshold')
                                ->numeric()
                                ->required()
                                ->default(10)
                                ->label('Critical Low Trigger')
                                ->columnSpan(1),
                            TextInput::make('max_stock_threshold')
                                ->numeric()
                                ->required()
                                ->default(100)
                                ->label('Optimal Restock Point')
                                ->columnSpan(1),
                        ]),

                    Section::make('Financial Costing')
                        ->description('Acquisition and value details.')
                        ->columns(2)
                        ->schema([
                            TextInput::make('unit_price')
                                ->numeric()
                                ->required()
                                ->label('Unit Acquisition Cost (Kopecks)')
                                ->columnSpan(1),
                            Toggle::make('is_active')
                                ->label('Active in Supply Chain')
                                ->default(true)
                                ->columnSpan(1),
                        ]),

                    Section::make('Technical & Audit Metadata')
                        ->description('Identifiers and JSON metadata.')
                        ->columns(1)
                        ->schema([
                            KeyValue::make('tags')
                                ->label('Warehouse Attributes')
                                ->keyLabel('Variable')
                                ->valueLabel('Value')
                                ->columnSpanFull(),
                            Placeholder::make('uuid')
                                ->label('Global UUID')
                                ->content(fn ($record) => $record?->uuid ?? (string) Str::uuid()),
                            Placeholder::make('correlation_id')
                                ->label('Correlation ID')
                                ->content(fn ($record) => $record?->correlation_id ?? 'Auto-assigned'),
                            Placeholder::make('last_restock')
                                ->label('Inventory Update')
                                ->content(fn ($record) => $record?->updated_at?->diffForHumans() ?? 'New Asset'),
                        ]),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListDentalConsumable::route('/'),
                'create' => Pages\CreateDentalConsumable::route('/create'),
                'edit' => Pages\EditDentalConsumable::route('/{record}/edit'),
                'view' => Pages\ViewDentalConsumable::route('/{record}'),
            ];
        }
}
