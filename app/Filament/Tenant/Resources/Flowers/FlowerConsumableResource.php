<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers;

use App\Domains\Flowers\Models\FlowerConsumable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class FlowerConsumableResource extends Resource
{
    protected static ?string $model = FlowerConsumable::class;
        protected static ?string $navigationIcon = 'heroicon-o-scissor';
        protected static ?string $navigationGroup = 'Flowers';
        protected static ?string $navigationLabel = 'Consumables';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Consumable Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('flower_shop_id')
                            ->relationship('shop', 'name')
                            ->required(),
                        Forms\Components\TextInput::make('unit')
                            ->placeholder('pcs, meters, sheets')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Inventory Management')
                    ->schema([
                        Forms\Components\TextInput::make('current_stock')
                            ->label('Stock Quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\TextInput::make('min_stock_threshold')
                            ->label('Alert Threshold')
                            ->required()
                            ->numeric()
                            ->default(10),
                        Forms\Components\TextInput::make('cost_price_kopecks')
                            ->label('Cost Price (in Kopecks)')
                            ->numeric()
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('System')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('correlation_id')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('unit')->badge()->color('secondary'),
                    Tables\Columns\TextColumn::make('current_stock')
                        ->label('Stock')
                        ->badge()
                        ->color(fn ($record) => $record->current_stock <= $record->min_stock_threshold ? 'danger' : 'success')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('cost_price_kopecks')
                        ->label('Cost')
                        ->money('RUB', divideBy: 100),
                    Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                ])
                ->filters([
                    Tables\Filters\Filter::make('low_stock')
                        ->query(fn ($query) => $query->whereColumn('current_stock', '<=', 'min_stock_threshold')),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListFlowerConsumables::route('/'),
                'create' => Pages\CreateFlowerConsumable::route('/create'),
                'edit' => Pages\EditFlowerConsumable::route('/{record}/edit'),
            ];
        }
}
