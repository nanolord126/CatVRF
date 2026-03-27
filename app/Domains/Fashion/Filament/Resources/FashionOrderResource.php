<?php

declare(strict_types=1);


namespace App\Domains\Fashion\Filament\Resources;

use App\Domains\Fashion\Models\FashionOrder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

final /**
 * FashionOrderResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FashionOrderResource extends Resource
{
    protected static ?string $model = FashionOrder::class;

    protected static ?string $navigationGroup = 'Fashion';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('fashion_store_id')->relationship('store', 'name')->required(),
            Select::make('customer_id')->relationship('customer', 'name')->required(),
            TextInput::make('subtotal')->required()->numeric()->step(0.01),
            TextInput::make('discount_amount')->numeric()->step(0.01),
            TextInput::make('shipping_cost')->numeric()->step(0.01),
            TextInput::make('shipping_address')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('order_number')->searchable(),
            TextColumn::make('customer.name'),
            TextColumn::make('store.name'),
            TextColumn::make('total_amount')->numeric()->sortable(),
            BadgeColumn::make('status'),
            TextColumn::make('delivered_at')->sortable(),
        ])->filters([])->actions([])->bulkActions([]);
    }
}
