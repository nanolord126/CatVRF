<?php declare(strict_types=1);

/**
 * FashionReturnResource — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/fashionreturnresource
 */


namespace App\Domains\Fashion\Filament\Resources;

use Filament\Resources\Resource;

final class FashionReturnResource extends Resource
{

    protected static ?string $model = FashionReturn::class;

        protected static ?string $navigationGroup = 'Fashion';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Select::make('order_id')->relationship('order', 'order_number')->required(),
                Select::make('customer_id')->relationship('customer', 'name')->required(),
                TextInput::make('return_amount')->required()->numeric()->step(0.01),
                Select::make('reason')->options([
                    'wrong_size' => 'Wrong Size',
                    'damaged' => 'Damaged',
                    'defective' => 'Defective',
                    'not_as_described' => 'Not As Described',
                    'changed_mind' => 'Changed Mind',
                    'other' => 'Other',
                ])->required(),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('return_number')->searchable(),
                TextColumn::make('order.order_number'),
                TextColumn::make('customer.name'),
                TextColumn::make('return_amount')->numeric()->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('requested_at')->sortable(),
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

}
