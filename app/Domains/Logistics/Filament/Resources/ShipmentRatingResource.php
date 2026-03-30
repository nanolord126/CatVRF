<?php declare(strict_types=1);

namespace App\Domains\Logistics\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShipmentRatingResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
