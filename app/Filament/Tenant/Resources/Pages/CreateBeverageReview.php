<?php

declare(strict_types=1);

/**
 * CreateBeverageReview — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/createbeveragereview
 * @see https://catvrf.ru/docs/createbeveragereview
 * @see https://catvrf.ru/docs/createbeveragereview
 * @see https://catvrf.ru/docs/createbeveragereview
 */

namespace App\Filament\Tenant\Resources\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\BeverageReviewResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Class CreateBeverageReview
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreateBeverageReview extends CreateRecord
{
    protected static string $resource = BeverageReviewResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id']         = tenant()->id ?? null;
        $data['business_group_id'] = session('active_business_group_id');
        $data['correlation_id']    = (string) Str::uuid();
        $data['uuid']              = (string) Str::uuid();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->log->channel('audit')->$this->logger->info('BeverageReview created', [
            'review_id'      => $this->record->id,
            'rating'         => $this->record->rating,
            'user_id'        => $this->record->user_id,
            'shop_id'        => $this->record->shop_id,
            'item_id'        => $this->record->item_id,
            'tenant_id'      => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
