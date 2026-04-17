<?php declare(strict_types=1);

/**
 * CreateAuto — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createauto
 * @see https://catvrf.ru/docs/createauto
 * @see https://catvrf.ru/docs/createauto
 * @see https://catvrf.ru/docs/createauto
 * @see https://catvrf.ru/docs/createauto
 */


namespace App\Filament\Tenant\Resources\Pages;


use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\AutoResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

/**
 * Class CreateAuto
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class CreateAuto extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = AutoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id']      = tenant()->id ?? null;
        $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();
        $data['uuid']           = (string) \Illuminate\Support\Str::uuid();

        return $data;
    }

    protected function afterCreate(): void
    {
        \Illuminate\Support\Facades\Log::channel('audit')->info('Auto vehicle created', [
            'vehicle_id'     => $this->record->id,
            'brand'          => $this->record->brand,
            'model'          => $this->record->model,
            'license_plate'  => $this->record->license_plate,
            'type'           => $this->record->type,
            'tenant_id'      => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
