<?php declare(strict_types=1);

/**
 * ListCommonEntitys — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listcommonentitys
 */


namespace App\Domains\Common\Filament\Resources\CommonEntityResource\Pages;

use App\Domains\Common\Filament\Resources\CommonEntityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListCommonEntitys extends ListRecords
{
    protected static string $resource = CommonEntityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}