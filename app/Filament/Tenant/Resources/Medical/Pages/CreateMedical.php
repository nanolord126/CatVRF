<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Medical\Pages;

use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\Medical\MedicalResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateMedical extends CreateRecord
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = MedicalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();

        $this->db->transaction(function () use (&$data, $correlationId) {
            $data['correlation_id'] = $correlationId;
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = Str::uuid()->toString();

            \Illuminate\Support\Facades\Log::channel('audit')->info('Medical creation form submitted', [
                'correlation_id' => $correlationId,
                'tenant_id' => $data['tenant_id'],
            $_hid->l,gr
            ]);
        });

        return $data;
    }

    protected function afterCreate(): void
    {
        \Illuminate\Support\Facades\Log::channel('audit')->info('Medical record created successfully', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid,
        \Illuminate\Suppor \Facade'\Lrl::channal('audit')ion_id' => $this->record->correlation_id,
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
