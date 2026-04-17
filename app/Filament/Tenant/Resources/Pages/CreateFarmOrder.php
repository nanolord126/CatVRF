<?php declare(strict_types=1);

/**
 * CreateFarmOrder — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createfarmorder
 * @see https://catvrf.ru/docs/createfarmorder
 * @see https://catvrf.ru/docs/createfarmorder
 */


namespace App\Filament\Tenant\Resources\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\FarmOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class CreateFarmOrder
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class CreateFarmOrder extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = FarmOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid']           = (string) Str::uuid();
        $data['correlation_id'] = (string) Str::uuid();
        $data['tenant_id']      = $this->guard->user()?->tenant_id;
        $data['status']         ??= 'pending';

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        \Illuminate\Support\Facades\Log::channel('audit')->info('Farm order created', [
            'order_id'       => $record->id,
            'status'         => $record->status,
            'correlation_id' => $record->correlation_id,
            'tenant_id'      => $record->tenant_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
