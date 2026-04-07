<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Psychology\PsychologistResource\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\Psychology\PsychologistResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class CreatePsychologist
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Psychology\PsychologistResource\Pages
 */
final class CreatePsychologist extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = PsychologistResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('Creating Psychologist via Filament', [
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

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }
}
