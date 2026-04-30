<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Enterprise\Pages;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use Illuminate\Database\DatabaseManager;
use App\Filament\Tenant\Resources\Enterprise\EnterpriseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

final class CreateEnterprise extends CreateRecord
{
    protected static string $resource = EnterpriseResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,) {}

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();

        $this->db->transaction(function () use (&$data, $correlationId) {
            $data['correlation_id'] = $correlationId;
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = Str::uuid()->toString();

            $this->log->channel('audit')->$this->logger->info('Enterprise creation form submitted', [
                'correlation_id' => $correlationId,
                'tenant_id' => $data['tenant_id'],
                'user_id' => auth()->id(),
            ]);
        });

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->log->channel('audit')->$this->logger->info('Enterprise record created successfully', [
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
