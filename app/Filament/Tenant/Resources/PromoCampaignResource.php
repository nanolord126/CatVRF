<?php
namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use App\Models\PromoCampaign;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;

class PromoCampaignResource extends Resource {
    protected static ?string $model = PromoCampaign::class;

    public static function form($form): \Filament\Forms\Form {
        return $form->schema([
            TextInput::make('name')->required(),
            Select::make('type')->options([
                'B2G1' => '2 + 1 (Buy 2 Get 1)',
                'BOGO' => '1 + 1 (Buy 1 Get 1)',
                'DISCOUNT' => 'Fixed Discount %',
                'COUPON' => 'Promo Coupon'
            ])->required(),
            TextInput::make('rules.min_amount')->numeric()->prefix('$'),
            TextInput::make('rules.discount_value')->numeric()->suffix('%'),
            Toggle::make('is_active')->default(true)
        ]);
    }
}
