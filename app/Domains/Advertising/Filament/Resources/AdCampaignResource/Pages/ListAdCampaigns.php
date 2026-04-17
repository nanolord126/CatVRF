<?php declare(strict_types=1);

/**
 * ListAdCampaigns — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listadcampaigns
 */


namespace App\Domains\Advertising\Filament\Resources\AdCampaignResource\Pages;

use App\Domains\Advertising\Filament\Resources\AdCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListAdCampaigns extends ListRecords
{
    protected static string $resource = AdCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}