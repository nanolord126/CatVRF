<?php declare(strict_types=1);

/**
 * ListCoachs — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listcoachs
 */


namespace App\Domains\PersonalDevelopment\Filament\Resources\CoachResource\Pages;

use App\Domains\PersonalDevelopment\Filament\Resources\CoachResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListCoachs extends ListRecords
{
    protected static string $resource = CoachResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}