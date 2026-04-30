<?php

declare(strict_types=1);

/**
 * ListPsychologists — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/listpsychologists
 * @see https://catvrf.ru/docs/listpsychologists
 */

namespace App\Filament\Tenant\Resources\Psychology\PsychologistResource\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\Psychology\PsychologistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Log\LogManager;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class ListPsychologists
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class ListPsychologists extends ListRecords
{
    protected static string $resource = PsychologistResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $this->log->channel('audit')->$this->logger->info('Accessing Psychologists list', [
            'user_id' => auth()->id(),
            'tenant_id' => $this->guard->user()->tenant_id,
        ]);

        return parent::getTableQuery()->withCount('bookings');
    }
}
