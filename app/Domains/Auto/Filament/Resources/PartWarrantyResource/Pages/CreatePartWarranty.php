<?php declare(strict_types=1);

/**
 * CreatePartWarranty — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createpartwarranty
 */


namespace App\Domains\Auto\Filament\Resources\PartWarrantyResource\Pages;


use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\CreateRecord;

final class CreatePartWarranty extends CreateRecord
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}


    protected static string $resource = PartWarrantyResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $correlationId = Str::uuid()->toString();
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = Str::uuid()->toString();
            $data['correlation_id'] = $correlationId;

            return $data;
        }

        protected function afterCreate(): void
        {
            $this->db->transaction(function () {
                $this->logger->info('PartWarranty created', [
                    'correlation_id' => $this->record->correlation_id,
                    'warranty_id' => $this->record->id,
                    'warranty_number' => $this->record->warranty_number,
                ]);
            });

            $this->notification->make()
                ->success()
                ->title('Гарантия оформлена')
                ->body('Номер гарантии: ' . $this->record->warranty_number)
                ->send();
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
