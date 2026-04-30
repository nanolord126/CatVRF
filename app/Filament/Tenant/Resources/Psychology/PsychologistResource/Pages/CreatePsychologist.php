<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Psychology\PsychologistResource\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\Psychology\PsychologistResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Class CreatePsychologist
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreatePsychologist extends CreateRecord
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = (string) Str::uuid();

        $this->log->channel('audit')->$this->logger->info('Creating Psychologist via Filament', [
            'data' => $data,
            'correlation_id' => $correlationId,
        ]);

        $data['correlation_id'] = $correlationId;
        $data['tenant_id'] = $this->guard->user()->tenant_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
