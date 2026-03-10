<?php
namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use App\Models\FoodVenue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class FoodSubVerticalResource extends Resource {
    protected static ?string $model = FoodVenue::class;
    
    public static function form($form): \Filament\Forms\Form {
        return $form->schema([
            TextInput::make('name')->required(),
            Select::make('sub_type')->options([
                'restaurant' => 'Restaurant', 'cafe' => 'Cafe',
                'coffee' => 'Coffee Shop', 'canteen' => 'Canteen',
                'culinary' => 'Culinary', 'ready_meals' => 'Ready Meals',
                'cuisine' => 'Specialized Cuisine'
            ])->required(),
            Select::make('cuisine_type')->options([
                'italian' => 'Italian', 'japanese' => 'Japanese',
                'georgian' => 'Georgian', 'russian' => 'Russian'
            ])->visible(fn ($get) => $get('sub_type') === 'cuisine')
        ]);
    }
}
