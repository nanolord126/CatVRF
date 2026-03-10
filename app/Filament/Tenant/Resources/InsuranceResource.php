<?php
namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use App\Models\InsurancePolicy;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;

class InsuranceResource extends Resource {
    protected static ?string $model = InsurancePolicy::class;

    public static function form($form): \Filament\Forms\Form {
        return $form->schema([
            TextInput::make('number')->required()->unique(),
            Select::make('type')->options([
                'osago' => 'OSAGO (Mandatory)',
                'kasko' => 'KASKO (Premium)',
                'life' => 'Worker Health'
            ])->required(),
            DatePicker::make('expires_at'),
            TextInput::make('premium_amount')->numeric()->prefix('$')
        ]);
    }
}
