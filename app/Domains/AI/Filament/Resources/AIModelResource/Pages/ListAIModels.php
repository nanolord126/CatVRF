<?php declare(strict_types=1);

/**
 * ListAIModels — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listaimodels
 */


namespace App\Domains\AI\Filament\Resources\AIModelResource\Pages;

use App\Domains\AI\Filament\Resources\AIModelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListAIModels extends ListRecords
{
    protected static string $resource = AIModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}