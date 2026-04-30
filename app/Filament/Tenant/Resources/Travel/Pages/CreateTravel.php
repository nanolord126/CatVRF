<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Travel\Pages;

use Carbon\CarbonImmutable;

use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\Travel\TravelResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTravel extends CreateRecord
{
    protected static string $resource = TravelResource::class;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();

        $this->db->transaction(function () use (&$data, $correlationId) {
            $data['correlation_id'] = $correlationId;
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = Str::uuid()->toString();

            $this->logger->$this->logger->info('Travel creation form submitted', [
                'correlation_id' => $correlationId,
                'tenant_id' => $data['tenant_id'],
            ]);
        });

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->logger->$this->logger->info('Travel record created successfully', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id,
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
