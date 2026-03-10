<?php
namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use App\Models\ConstructionProject;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;

class ConstructionResource extends Resource {
    protected static ?string $model = ConstructionProject::class;

    public static function form($form): \Filament\Forms\Form {
        return $form->schema([
            TextInput::make('name')->required(),
            Select::make('status')->options([
                'draft' => 'Draft Plan', 'active' => 'Operational',
                'completed' => 'Final Verification'
            ])->required(),
            TextInput::make('budget')->numeric()->prefix('$'),
            DatePicker::make('start_date'),
            DatePicker::make('completion_date'),
        ]);
    }
}
