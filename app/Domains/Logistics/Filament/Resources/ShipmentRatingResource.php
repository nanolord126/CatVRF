<?php

declare(strict_types=1);


namespace App\Domains\Logistics\Filament\Resources;

use App\Domains\Logistics\Models\ShipmentRating;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

final /**
 * ShipmentRatingResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ShipmentRatingResource extends Resource
{
    protected static ?string $model = ShipmentRating::class;

    protected static ?string $navigationGroup = 'Logistics';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('shipment_id')->relationship('shipment', 'tracking_number')->required(),
            Select::make('reviewer_id')->relationship('reviewer', 'name')->required(),
            TextInput::make('rating')->required()->numeric()->min(1)->max(5),
            RichEditor::make('comment')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('shipment.tracking_number'),
            TextColumn::make('rating')->numeric()->sortable(),
            IconColumn::make('verified_purchase')->boolean(),
        ])->filters([])->actions([])->bulkActions([]);
    }
}
