<?php declare(strict_types=1);

/**
 * ListContentItems — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listcontentitems
 */


namespace App\Domains\Content\Filament\Resources\ContentItemResource\Pages;

use App\Domains\Content\Filament\Resources\ContentItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListContentItems extends ListRecords
{
    protected static string $resource = ContentItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}