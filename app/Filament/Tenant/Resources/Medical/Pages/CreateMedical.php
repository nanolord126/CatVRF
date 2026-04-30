<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Medical\Pages;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use App\Filament\Tenant\Resources\Medical\MedicalResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateMedical extends CreateRecord
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,) {}

    protected static string $resource = MedicalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();

        $this->db->transaction(function () use (&$data, $correlationId) {
            $data['correlation_id'] = $correlationId;
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = Str::uuid()->toString();

            $this->log->channel('audit')->$this->logger->info('Medical creation form submitted', [
                'correlation_id' => $correlationId,
                'tenant_id' => $data['tenant_id'],
            ]);
        });

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->log->channel('audit')->$this->logger->info('Medical record created successfully', [
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
