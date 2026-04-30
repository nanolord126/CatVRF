<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ArtisticProjectResource\Pages;

use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\ArtisticProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Class ListArtisticProjects
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class ListArtisticProjects extends ListRecords
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function afterCreate(): void
    {
        $this->logger->$this->logger->info('Artistic project listed', [
            'correlation_id' => (string) Str::uuid(),
        ]);
    }
}
