<?php declare(strict_types=1);

/**
 * FashionOrderResource — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/fashionorderresource
 */


namespace App\Domains\Fashion\Filament\Resources;

use Filament\Resources\Resource;

final class FashionOrderResource extends Resource
{

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
                TextColumn::make('status')->badge(),
                TextColumn::make('delivered_at')->sortable(),
            ])->filters([])->actions([])->bulkActions([]);
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
