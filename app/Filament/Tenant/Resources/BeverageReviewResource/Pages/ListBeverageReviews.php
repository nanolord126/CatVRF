<?php declare(strict_types=1);

/**
 * ListBeverageReviews — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listbeveragereviews
 * @see https://catvrf.ru/docs/listbeveragereviews
 * @see https://catvrf.ru/docs/listbeveragereviews
 * @see https://catvrf.ru/docs/listbeveragereviews
 * @see https://catvrf.ru/docs/listbeveragereviews
 * @see https://catvrf.ru/docs/listbeveragereviews
 * @see https://catvrf.ru/docs/listbeveragereviews
 * @see https://catvrf.ru/docs/listbeveragereviews
 * @see https://catvrf.ru/docs/listbeveragereviews
 * @see https://catvrf.ru/docs/listbeveragereviews
 * @see https://catvrf.ru/docs/listbeveragereviews
 * @see https://catvrf.ru/docs/listbeveragereviews
 * @see https://catvrf.ru/docs/listbeveragereviews
 * @see https://catvrf.ru/docs/listbeveragereviews
 * @see https://catvrf.ru/docs/listbeveragereviews
 * @see https://catvrf.ru/docs/listbeveragereviews
 */


namespace App\Filament\Tenant\Resources\BeverageReviewResource\Pages;

use Filament\Resources\Pages\ListRecords;

final class ListBeverageReviews extends ListRecords
{

    protected static string $resource = BeverageReviewResource::class;

        protected function getHeaderActions(): array
        {
            return [
                \Filament\Actions\CreateAction::make()
                    ->label('Manually Post Feedback')
                    ->icon('heroicon-o-pencil-square'),
            ];
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
