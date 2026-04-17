<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;


use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\DentistResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

/**
 * Class CreateDentist
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class CreateDentist extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = DentistResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id']      = tenant()->id ?? null;
        $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();
        $data['uuid']           = (string) \Illuminate\Support\Str::uuid();

        return $data;
    }

    protected function afterCreate(): void
    {
        \Illuminate\Support\Facades\Log::channel('audit')->info('Dentist created', [
            'dentist_id'      => $this->record->id,
            'full_name'       => $this->record->full_name,
            'specialization'  => $this->record->specialization,
            'tenant_id'       => $this->record->tenant_id,
            'correlation_id'  => $this->record->correlation_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }
}
