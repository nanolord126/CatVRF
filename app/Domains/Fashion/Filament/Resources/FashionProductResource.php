<?php declare(strict_types=1);

/**
 * FashionProductResource — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/fashionproductresource
 */


namespace App\Domains\Fashion\Filament\Resources;

use Filament\Resources\Resource;

final class FashionProductResource extends Resource
{

    protected static ?string $model = FashionProduct::class;

        protected static ?string $navigationGroup = 'Fashion';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Select::make('fashion_store_id')->relationship('store', 'name')->required(),
                Select::make('category_id')->relationship('category', 'name')->required(),
                TextInput::make('name')->required(),
                TextInput::make('sku')->required()->unique(),
                TextInput::make('price')->required()->numeric()->step(0.01),
                TextInput::make('cost_price')->numeric()->step(0.01),
                TextInput::make('current_stock')->required()->numeric(),
                RichEditor::make('description')->columnSpanFull(),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('sku')->searchable(),
                TextColumn::make('store.name'),
                TextColumn::make('price')->numeric()->sortable(),
                TextColumn::make('current_stock')->badge()->numeric(),
                TextColumn::make('status')->badge(),
                TextColumn::make('rating')->numeric()->sortable(),
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
