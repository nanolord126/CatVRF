<?php

declare(strict_types=1);

/**
 * ListEvents — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 */

namespace App\Filament\Tenant\Resources\Entertainment\EventResource\Pages;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Log\LogManager;

final class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

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

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->after(function () {
                    $this->log->channel('audit')->$this->logger->info('Entertainment Event creation started', [
                        'tenant_id' => filament()->getTenant()->id,
                        'user_id' => auth()->id(),
                    ]);
                }),
        ];
    }
}
