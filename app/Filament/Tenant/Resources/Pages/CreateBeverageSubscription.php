<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\BeverageSubscriptionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Class CreateBeverageSubscription
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreateBeverageSubscription extends CreateRecord
{
    protected static string $resource = BeverageSubscriptionResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id']         = tenant()->id ?? null;
        $data['business_group_id'] = session('active_business_group_id');
        $data['correlation_id']    = (string) Str::uuid();
        $data['uuid']              = (string) Str::uuid();
        $data['status']            = $data['status'] ?? 'active';
        $data['used_count']        = $data['used_count'] ?? 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->log->channel('audit')->$this->logger->info('BeverageSubscription created', [
            'subscription_id' => $this->record->id,
            'plan_type'       => $this->record->plan_type,
            'user_id'         => $this->record->user_id,
            'shop_id'         => $this->record->shop_id,
            'tenant_id'       => $this->record->tenant_id,
            'correlation_id'  => $this->record->correlation_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
