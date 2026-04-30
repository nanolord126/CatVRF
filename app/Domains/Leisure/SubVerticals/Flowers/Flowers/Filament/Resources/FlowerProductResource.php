<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\Flowers\Filament\Resources;

use App\Domains\Flowers\Models\FlowerProduct;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseOptimizedResource;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * FlowerProductResource — CatVRF 2026 Component.
 *
 * Filament resource for managing flower products.
 * Tenant-scoped: all data filtered by current tenant.
 */
final class FlowerProductResource extends BaseOptimizedResource
{
    protected static ?string $model = FlowerProduct::class;

    protected static ?string $slug = 'flower-products';

    protected static ?string $navigationGroup = 'Flowers';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Textarea::make('description'),
            Forms\Components\Select::make('category')->options([
                'bouquet' => 'Букет',
                'arrangement' => 'Аранжировка',
                'subscription' => 'Подписка',
            ])->required(),
            Forms\Components\TextInput::make('price')->numeric()->required(),
            Forms\Components\TextInput::make('stock')->numeric(),
            Forms\Components\Toggle::make('in_stock'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('category')->badge(),
            Tables\Columns\TextColumn::make('price'),
            Tables\Columns\TextColumn::make('stock'),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }

    /**
     * Relations to eager load for Flowers
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}
