<?php
namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use App\Models\Automotive;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;

class AutoResource extends Resource {
    protected static ?string $model = Automotive::class;
    
    public static function form($form): \Filament\Forms\Form {
        return $form->schema([
            TextInput::make('name')->required(),
            Select::make('type')->options([
                'cars' => 'Cars', 'repair' => 'Auto Repair',
                'parts' => 'Auto Parts', 'wash' => 'Car Wash',
                'sto' => 'STO', 'tuning' => 'Tuning'
            ])->required(),
            TextInput::make('inn')->numeric(),
        ]);
    }
}
