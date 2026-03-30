<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FarmOrderResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = FarmOrder::class;
        protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
        protected static ?string $navigationLabel = 'Farm Orders';
        protected static ?string $navigationGroup = 'FarmDirect';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Select::make('product_id')
                        ->relationship('product', 'name')
                        ->required()
                        ->searchable(),

                    Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'processing' => 'Processing',
                            'shipped' => 'Shipped',
                            'delivered' => 'Delivered',
                            'cancelled' => 'Cancelled',
                        ])
                        ->required(),

                    TextInput::make('total_price')
                        ->numeric()
                        ->required()
                        ->minValue(0),

                    TextInput::make('quantity_kg')
                        ->numeric()
                        ->required()
                        ->minValue(0.5)
                        ->maxValue(500),

                    DateTimePickerColumn::make('delivery_date')
                        ->required(),

                    Textarea::make('notes')
                        ->columnSpanFull(),
                ]);

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListFarmOrder::route('/'),
                'create' => Pages\\CreateFarmOrder::route('/create'),
                'edit' => Pages\\EditFarmOrder::route('/{record}/edit'),
                'view' => Pages\\ViewFarmOrder::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListFarmOrder::route('/'),
                'create' => Pages\\CreateFarmOrder::route('/create'),
                'edit' => Pages\\EditFarmOrder::route('/{record}/edit'),
                'view' => Pages\\ViewFarmOrder::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListFarmOrder::route('/'),
                'create' => Pages\\CreateFarmOrder::route('/create'),
                'edit' => Pages\\EditFarmOrder::route('/{record}/edit'),
                'view' => Pages\\ViewFarmOrder::route('/{record}'),
            ];
        }
}
