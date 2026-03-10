<?php

namespace App\Filament\B2B\Resources;

use App\Filament\B2B\Resources\B2BProductResource\Pages;
use App\Models\B2BProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\B2B\B2BAIAnalyticsService;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class B2BProductResource extends Resource
{
    protected static ?string $model = B2BProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    
    protected static ?string $navigationGroup = 'Catalog Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->schema([
                        Forms\Components\Select::make('manufacturer_id')
                            ->relationship('manufacturer', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Pricing & Inventory')
                    ->schema([
                        Forms\Components\TextInput::make('base_wholesale_price')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\TextInput::make('unit')
                            ->placeholder('kg, box, pallet')
                            ->required(),
                        Forms\Components\TextInput::make('min_order_quantity')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        Forms\Components\TextInput::make('stock_quantity')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Specifications')
                    ->schema([
                        Forms\Components\KeyValue::make('specifications')
                            ->keyLabel('Feature')
                            ->valueLabel('Value'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('manufacturer.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('base_wholesale_price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit'),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('manufacturer')
                    ->relationship('manufacturer', 'name'),
            ])
            ->actions([
                Action::make('ai_optimize')
                    ->label('AI Optimizer')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->action(function (B2BProduct $record, B2BAIAnalyticsService $service) {
                        $suggestion = $service->suggestOptimalPrice($record);
                        
                        Notification::make()
                            ->title('AI Price Suggestion')
                            ->body("Suggested Price: \${$suggestion['suggested_price']} ({$suggestion['price_change_pc']}% change). Reasoning: {$suggestion['reasoning']}")
                            ->info()
                            ->persistent()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListB2BProducts::route('/'),
            'create' => Pages\CreateB2BProduct::route('/create'),
            'edit' => Pages\EditB2BProduct::route('/{record}/edit'),
        ];
    }
}
