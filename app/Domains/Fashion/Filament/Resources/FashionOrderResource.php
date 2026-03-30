<?php declare(strict_types=1);

namespace App\Domains\Fashion\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionOrderResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
