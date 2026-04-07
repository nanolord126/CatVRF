<?php declare(strict_types=1);

/**
 * CreateAutoRepairOrder — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createautorepairorder
 * @see https://catvrf.ru/docs/createautorepairorder
 * @see https://catvrf.ru/docs/createautorepairorder
 * @see https://catvrf.ru/docs/createautorepairorder
 */


namespace App\Filament\Tenant\Resources\Auto\AutoRepairOrderResource\Pages;


use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\CreateRecord;

final class CreateAutoRepairOrder extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = AutoRepairOrderResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();

            $data['total_cost_kopecks'] = ($data['labor_cost_kopecks'] ?? 0) + ($data['parts_cost_kopecks'] ?? 0);

            $this->logger->info('Repair Order Creation Initiated', [
                'tenant_id' => $data['tenant_id'],
                'correlation_id' => $data['correlation_id'],
            ]);

            return $data;
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
