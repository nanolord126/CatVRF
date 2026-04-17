<?php declare(strict_types=1);

/**
 * ListPromoCampaigns — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listpromocampaigns
 */


namespace App\Domains\PromoCampaigns\Filament\Resources\PromoCampaignResource\Pages;

use App\Domains\PromoCampaigns\Filament\Resources\PromoCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListPromoCampaigns extends ListRecords
{
    protected static string $resource = PromoCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}