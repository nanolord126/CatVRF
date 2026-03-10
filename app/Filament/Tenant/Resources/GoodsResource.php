<?php
namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use App\Models\RetailCategory;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class GoodsResource extends Resource {
    protected static ?string $model = RetailCategory::class;
    
    public static function form($form): \Filament\Forms\Form {
        return $form->schema([
            TextInput::make('name')->required(),
            Select::make('category')->options([
                'household' => 'Household Chemicals',
                'toys' => 'Kids Toys',
                'clothing' => 'Clothing',
                'shoes' => 'Shoes',
                'electronics' => 'Electronics'
            ])->required(),
            TextInput::make('sku_prefix')->required(),
        ]);
    }
}
