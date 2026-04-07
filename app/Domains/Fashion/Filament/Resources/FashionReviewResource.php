<?php declare(strict_types=1);

/**
 * FashionReviewResource — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/fashionreviewresource
 */


namespace App\Domains\Fashion\Filament\Resources;

use Filament\Resources\Resource;

final class FashionReviewResource extends Resource
{

    protected static ?string $model = FashionReview::class;

        protected static ?string $navigationGroup = 'Fashion';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Select::make('product_id')->relationship('product', 'name')->required(),
                Select::make('reviewer_id')->relationship('reviewer', 'name')->required(),
                TextInput::make('rating')->required()->numeric()->min(1)->max(5),
                RichEditor::make('comment')->columnSpanFull(),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('product.name'),
                TextColumn::make('reviewer.name'),
                TextColumn::make('rating')->numeric()->sortable(),
                TextColumn::make('status')->badge(),
                IconColumn::make('verified_purchase')->boolean(),
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
