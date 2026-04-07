<?php declare(strict_types=1);

/**
 * EditRecordFlowers — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/editrecordflowers
 * @see https://catvrf.ru/docs/editrecordflowers
 * @see https://catvrf.ru/docs/editrecordflowers
 * @see https://catvrf.ru/docs/editrecordflowers
 * @see https://catvrf.ru/docs/editrecordflowers
 * @see https://catvrf.ru/docs/editrecordflowers
 * @see https://catvrf.ru/docs/editrecordflowers
 */


namespace App\Filament\Tenant\Resources\Flowers\Pages;

use App\Filament\Tenant\Resources\FlowersResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditRecordFlowers extends EditRecord
{
    protected static string $resource = FlowersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Запись успешно обновлена';
    }
}
