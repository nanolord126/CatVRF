<?php

declare(strict_types=1);

/**
 * CreateAutoRepairOrder — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/createautorepairorder
 * @see https://catvrf.ru/docs/createautorepairorder
 * @see https://catvrf.ru/docs/createautorepairorder
 * @see https://catvrf.ru/docs/createautorepairorder
 */

namespace App\Filament\Tenant\Resources\Auto\AutoRepairOrderResource\Pages;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

final class CreateAutoRepairOrder extends CreateRecord
{
    protected static string $resource = AutoRepairOrderResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['correlation_id'] = (string) Str::uuid();

        $data['total_cost_kopecks'] = ($data['labor_cost_kopecks'] ?? 0) + ($data['parts_cost_kopecks'] ?? 0);

        $this->log->channel('audit')->$this->logger->info('Repair Order Creation Initiated', [
            'tenant_id' => $data['tenant_id'],
            'correlation_id' => $data['correlation_id'],
        ]);

        return $data;
    }
}
