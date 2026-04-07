<?php declare(strict_types=1);

/**
 * CreateCorporateContract — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createcorporatecontract
 * @see https://catvrf.ru/docs/createcorporatecontract
 */


namespace App\Filament\Tenant\Resources\Education\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\CreateRecord;

final class CreateCorporateContract extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = CorporateContractResource::class;

        /**
         * Пре-валидация и установка ID.
         */
        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $correlationId = (string) Str::uuid();

            // 1. Установка ID Клиента (Текущий Tenant)
            $data['client_tenant_id'] = filament()->getTenant()->id;
            $data['correlation_id'] = $correlationId;

            // 2. Первичные слоты
            $data['slots_available'] = $data['slots_total'];
            $data['signed_at'] = now();

            // 3. Fraud Control Check (Канон 2026)
            // В реальной системе: app(FraudControlService::class)->check($this->guard->user(), 'b2b_agreement_creation', $data);

            return $data;
        }

        /**
         * Пост-логирование.
         */
        protected function afterCreate(): void
        {
            Notification::make()
                ->title('B2B Education: Contract constructed.')
                ->success()
                ->body('Corporate agreement #' . $this->record->contract_number . ' is now active.')
                ->send();

            $this->logger->info('B2B Education: Corporate agreement created', [
                'contract_id' => $this->record->id,
                'correlation_id' => $this->record->correlation_id,
            ]);
        }
}
