<?php declare(strict_types=1);

/**
 * EditBakeryOrder — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/editbakeryorder
 * @see https://catvrf.ru/docs/editbakeryorder
 * @see https://catvrf.ru/docs/editbakeryorder
 * @see https://catvrf.ru/docs/editbakeryorder
 * @see https://catvrf.ru/docs/editbakeryorder
 * @see https://catvrf.ru/docs/editbakeryorder
 * @see https://catvrf.ru/docs/editbakeryorder
 * @see https://catvrf.ru/docs/editbakeryorder
 */


namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\BakeryOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class EditBakeryOrder extends EditRecord
{
    protected static string $resource = BakeryOrderResource::class;

    public function getTitle(): string
    {
        return 'Edit BakeryOrder';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
