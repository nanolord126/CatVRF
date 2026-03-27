<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers;

use App\Domains\Flowers\Models\FlowerProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * КАНОН 2026: FlowerProductResource (Flowers).
 * Управление складом цветов (Inventory Management).
 */
final class FlowerProductResource extends Resource
{
    protected static ?string $model = FlowerProduct::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Flowers';
    protected static ?string $navigationLabel = 'Flower Warehouse';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Product Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('flower_shop_id')
                        ->relationship('shop', 'name')
                        ->required(),
                    Forms\Components\TextInput::make('sku')
                        ->label('SKU')
                        ->unique(ignoreRecord: true)
                        ->required(),
                ])->columns(3),

            Forms\Components\Section::make('Inventory & Freshness')
                ->schema([
                    Forms\Components\TextInput::make('price_kopecks')
                        ->money('RUB', divideBy: 100)
                        ->required()
                        ->numeric(),
                    Forms\Components\TextInput::make('current_stock')
                        ->required()
                        ->numeric(),
                    Forms\Components\DatePicker::make('freshness_date')
                        ->label('Best Before')
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'fresh' => 'Fresh',
                            'expires_soon' => 'Expires Soon',
                            'expired' => 'Expired',
                        ])
                        ->default('fresh')
                        ->required(),
                ])->columns(4),
                
            Forms\Components\Section::make('Metadata')
                ->schema([
                    Forms\Components\KeyValue::make('tags')
                        ->label('Tags & Analysis'),
                    Forms\Components\TextInput::make('uuid')
                        ->disabled()
                        ->placeholder('Generated on save')
                        ->dehydrated(false),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sku')->label('SKU'),
                Tables\Columns\TextColumn::make('current_stock')->label('Stock')->badge()->color(fn ($state) => $state < 10 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('price_kopecks')->label('Price')->money('RUB', divideBy: 100),
                Tables\Columns\TextColumn::make('freshness_date')->label('Best Before')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn ($state) => match($state) {
                    'fresh' => 'success',
                    'expires_soon' => 'warning',
                    'expired' => 'danger',
                    default => 'secondary'
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'fresh' => 'Fresh',
                    'expires_soon' => 'Expires Soon',
                    'expired' => 'Expired',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlowerProducts::route('/'),
            'create' => Pages\CreateFlowerProduct::route('/create'),
            'edit' => Pages\EditFlowerProduct::route('/{record}/edit'),
        ];
    }
}
