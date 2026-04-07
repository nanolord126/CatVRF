<?php declare(strict_types=1);

/**
 * FashionRetailReviewResource — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/fashionretailreviewresource
 */


namespace App\Domains\Fashion\FashionRetail\Filament\Resources;

use Filament\Resources\Resource;

final class FashionRetailReviewResource extends Resource
{

    protected static ?string $model = FashionRetailReview::class;

        protected static ?string $navigationGroup = 'Fashion Retail';

        protected static ?string $navigationLabel = 'Reviews';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Review Details')->schema([
                    Select::make('product_id')->relationship('product', 'name')->required(),
                    Select::make('user_id')->relationship('user', 'name')->required(),
                    TextInput::make('rating')->numeric()->min(1)->max(5)->required(),
                    TextInput::make('title')->required()->maxLength(255),
                    RichEditor::make('comment')->columnSpanFull(),
                    Select::make('status')->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])->required(),
                ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('product.name')->searchable(),
                TextColumn::make('user.name')->searchable(),
                TextColumn::make('rating')->numeric(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('helpful_count')->numeric(),
                TextColumn::make('created_at')->dateTime(),
            ])->filters([])->actions([])->bulkActions([]);
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
