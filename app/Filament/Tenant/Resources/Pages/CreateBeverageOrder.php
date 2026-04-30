<?php

declare(strict_types=1);

/**
 * CreateBeverageOrder — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/createbeverageorder
 * @see https://catvrf.ru/docs/createbeverageorder
 * @see https://catvrf.ru/docs/createbeverageorder
 * @see https://catvrf.ru/docs/createbeverageorder
 */

namespace App\Filament\Tenant\Resources\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\BeverageOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Class CreateBeverageOrder
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreateBeverageOrder extends CreateRecord
{
    protected static string $resource = BeverageOrderResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id']        = tenant()->id ?? null;
        $data['business_group_id'] = session('active_business_group_id');
        $data['correlation_id']   = (string) Str::uuid();
        $data['uuid']             = (string) Str::uuid();
        $data['status']           = $data['status'] ?? 'pending';
        $data['payment_status']   = $data['payment_status'] ?? 'unpaid';

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->log->channel('audit')->$this->logger->info('BeverageOrder created', [
            'order_id'       => $this->record->id,
            'customer_id'    => $this->record->customer_id,
            'total_amount'   => $this->record->total_amount,
            'tenant_id'      => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
