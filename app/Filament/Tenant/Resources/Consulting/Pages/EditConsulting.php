<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Consulting\Pages;

use Carbon\CarbonImmutable;

use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\Consulting\ConsultingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditConsulting extends EditRecord
{
    protected static string $resource = ConsultingResource::class;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Удалить')
                ->icon('heroicon-m-trash'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->db->transaction(function () use (&$data) {
            $data['correlation_id'] = Str::uuid()->toString();
            $data['tenant_id'] = filament()->getTenant()->id;

            $this->logger->$this->logger->info('Consulting service updated', [
                'user_id' => auth()->id(),
                'correlation_id' => $data['correlation_id'],
                'tenant_id' => $data['tenant_id'],
                'service_id' => $this->record->id,
            ]);
        });

        return $data;
    }

    protected function afterSave(): void
    {
        $this->logger->$this->logger->info('Consulting edit page saved', [
            'record_id' => $this->record->id,
            'user_id' => auth()->id(),
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }
}
