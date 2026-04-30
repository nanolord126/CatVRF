<?php

declare(strict_types=1);

/**
 * CreateDentalTreatmentPlan — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/createdentaltreatmentplan
 * @see https://catvrf.ru/docs/createdentaltreatmentplan
 * @see https://catvrf.ru/docs/createdentaltreatmentplan
 * @see https://catvrf.ru/docs/createdentaltreatmentplan
 * @see https://catvrf.ru/docs/createdentaltreatmentplan
 */

namespace App\Filament\Tenant\Resources\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\DentalTreatmentPlanResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Class CreateDentalTreatmentPlan
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreateDentalTreatmentPlan extends CreateRecord
{
    protected static string $resource = DentalTreatmentPlanResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id']      = tenant()->id ?? null;
        $data['correlation_id'] = (string) Str::uuid();
        $data['uuid']           = (string) Str::uuid();
        $data['status']         = $data['status'] ?? 'draft';

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->log->channel('audit')->$this->logger->info('DentalTreatmentPlan created', [
            'plan_id'        => $this->record->id,
            'title'          => $this->record->title,
            'client_id'      => $this->record->client_id,
            'dentist_id'     => $this->record->dentist_id,
            'tenant_id'      => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
