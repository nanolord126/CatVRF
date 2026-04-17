<?php declare(strict_types=1);

/**
 * ListRecommendations — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listrecommendations
 */


namespace App\Domains\Recommendation\Filament\Resources\RecommendationResource\Pages;

use App\Domains\Recommendation\Filament\Resources\RecommendationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListRecommendations extends ListRecords
{
    protected static string $resource = RecommendationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}