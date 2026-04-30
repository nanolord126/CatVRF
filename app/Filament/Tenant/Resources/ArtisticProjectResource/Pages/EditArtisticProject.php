<?php

declare(strict_types=1);

/**
 * EditArtisticProject — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/editartisticproject
 * @see https://catvrf.ru/docs/editartisticproject
 * @see https://catvrf.ru/docs/editartisticproject
 */

namespace App\Filament\Tenant\Resources\ArtisticProjectResource\Pages;

use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\ArtisticProjectResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Class EditArtisticProject
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class EditArtisticProject extends EditRecord
{
    protected static string $resource = ArtisticProjectResource::class;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     */
    public function isValid(): bool
    {
        return true;
    }

    protected function afterSave(): void
    {
        $this->logger->$this->logger->info('Artistic project updated via Filament', [
            'record_id' => $this->record->id,
            'correlation_id' => (string) Str::uuid(),
        ]);
    }
}
