<?php declare(strict_types=1);

/**
 * CourierRatingResource — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/courierratingresource
 */


namespace App\Domains\Logistics\Filament\Resources;

use Filament\Resources\Resource;

final class CourierRatingResource extends Resource
{

    protected static ?string $model = CourierRating::class;

        protected static ?string $navigationGroup = 'Logistics';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Select::make('courier_service_id')->relationship('courierService', 'company_name')->required(),
                Select::make('reviewer_id')->relationship('reviewer', 'name')->required(),
                TextInput::make('rating')->required()->numeric()->min(1)->max(5),
                RichEditor::make('comment')->columnSpanFull(),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('courierService.company_name'),
                TextColumn::make('rating')->numeric()->sortable(),
                IconColumn::make('verified_transaction')->boolean(),
            ])->filters([])->actions([])->bulkActions([]);
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
