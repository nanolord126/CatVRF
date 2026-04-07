<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\FitnessResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class CreateFitness
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class CreateFitness extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = FitnessResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid']           = (string) Str::uuid();
        $data['correlation_id'] = (string) Str::uuid();
        $data['tenant_id']      = $this->guard->user()?->tenant_id;
        $data['status']         ??= 'pending';

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $this->logger->info('B2B Fitness order created', [
            'order_id'       => $record->id,
            'order_number'   => $record->order_number,
            'total_amount'   => $record->total_amount,
            'correlation_id' => $record->correlation_id,
            'tenant_id'      => $record->tenant_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
